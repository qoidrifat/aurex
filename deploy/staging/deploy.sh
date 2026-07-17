#!/usr/bin/env bash
#
# ─── AUREX Staging Deployment Script ─────────────────
# Deploy Laravel + MySQL + Redis + AI Service ke VPS.
#
# Prerequisites:
#   1. VPS dengan Docker & Docker Compose terinstall
#   2. Domain (staging-api.aurex.app) mengarah ke IP VPS
#   3. SSH key sudah disetup untuk passwordless login
#   4. File .env.staging sudah diisi dengan benar
#
# Usage:
#   ./deploy/staging/deploy.sh                    # Deploy ke staging
#   ./deploy/staging/deploy.sh --rollback          # Rollback ke versi sebelumnya
#   ./deploy/staging/deploy.sh --status            # Cek status deployment
#
# Environment Variables:
#   SSH_HOST      - SSH host (default: root@staging-api.aurex.app)
#   APP_KEY       - Laravel app key (required)
#   DB_PASSWORD   - MySQL password (required)
#   AI_API_KEY    - AI Service API key (required)
#   SENTRY_DSN    - Sentry DSN (optional)
# =====================================================

set -euo pipefail

# ─── Configuration ────────────────────────────────────
SSH_HOST="${SSH_HOST:-root@staging-api.aurex.app}"
DEPLOY_DIR="/opt/aurex"
BACKUP_DIR="/opt/aurex-backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info()  { echo -e "${GREEN}[INFO]${NC}  $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC}  $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# ─── Argument Parsing ─────────────────────────────────
ROLLBACK=false
STATUS=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        --rollback) ROLLBACK=true; shift ;;
        --status)   STATUS=true; shift ;;
        *) log_error "Unknown option: $1"; exit 1 ;;
    esac
done

# ─── Status Check ─────────────────────────────────────
if [ "$STATUS" = true ]; then
    log_info "Memeriksa status deployment di $SSH_HOST..."
    ssh "$SSH_HOST" "
        echo '=== Docker Containers ==='
        docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
        echo ''
        echo '=== Resource Usage ==='
        docker stats --no-stream --format 'table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}'
        echo ''
        echo '=== Health Check ==='
        curl -s http://localhost/api/v1/health | python3 -m json.tool 2>/dev/null || echo 'Health check failed'
    "
    exit 0
fi

# ─── Rollback ─────────────────────────────────────────
if [ "$ROLLBACK" = true ]; then
    log_warn "Rollback ke versi sebelumnya..."
    ssh "$SSH_HOST" "
        if [ -d '$BACKUP_DIR/latest' ]; then
            rm -rf $DEPLOY_DIR/current
            cp -r $BACKUP_DIR/latest $DEPLOY_DIR/current
            cd $DEPLOY_DIR/current
            docker compose -f docker-compose.staging.yml down
            docker compose -f docker-compose.staging.yml up -d --build
            log_info 'Rollback selesai!'
        else
            log_error 'Tidak ada backup untuk rollback.'
            exit 1
        fi
    "
    exit 0
fi

# ─── Validate Env Vars ────────────────────────────────
if [ -z "${APP_KEY:-}" ]; then
    log_error "APP_KEY is required. Generate dengan: php artisan key:generate --show"
    exit 1
fi

if [ -z "${DB_PASSWORD:-}" ]; then
    log_error "DB_PASSWORD is required!"
    exit 1
fi

if [ -z "${AI_API_KEY:-}" ]; then
    log_error "AI_API_KEY is required!"
    exit 1
fi

# ─── Deploy ───────────────────────────────────────────
log_info "Memulai deployment ke $SSH_HOST..."

# 1. Buat direktori di VPS
log_info "Menyiapkan direktori..."
ssh "$SSH_HOST" "
    mkdir -p $DEPLOY_DIR/$TIMESTAMP
    mkdir -p $BACKUP_DIR
"

# 2. Copy file project ke VPS
log_info "Mengupload file project..."
rsync -avz --progress \
    --exclude 'vendor/' \
    --exclude 'node_modules/' \
    --exclude '.git/' \
    --exclude 'storage/framework/cache/' \
    --exclude 'storage/logs/' \
    ./ \
    "$SSH_HOST:$DEPLOY_DIR/$TIMESTAMP/"

# 3. Backup versi sebelumnya (jika ada)
log_info "Membackup versi sebelumnya..."
ssh "$SSH_HOST" "
    if [ -d '$DEPLOY_DIR/current' ] && [ -f '$DEPLOY_DIR/current/docker-compose.staging.yml' ]; then
        cp -r $DEPLOY_DIR/current $BACKUP_DIR/$TIMESTAMP
        rm -f $BACKUP_DIR/latest
        ln -s $BACKUP_DIR/$TIMESTAMP $BACKUP_DIR/latest
    fi
"

# 4. Buat symlink ke versi baru
log_info "Mengaktifkan versi baru..."
ssh "$SSH_HOST" "
    rm -f $DEPLOY_DIR/current
    ln -s $DEPLOY_DIR/$TIMESTAMP $DEPLOY_DIR/current
"

# 5. Setup .env
log_info "Mengkonfigurasi environment..."
ssh "$SSH_HOST" "
    cd $DEPLOY_DIR/current
    cp backend/laravel_api/.env.staging backend/laravel_api/.env
    # Update dengan env vars dari CI/CD
    sed -i 's/^APP_KEY=.*/APP_KEY=$APP_KEY/' backend/laravel_api/.env
    sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/' backend/laravel_api/.env
    sed -i 's/^AI_SERVICE_API_KEY=.*/AI_SERVICE_API_KEY=$AI_API_KEY/' backend/laravel_api/.env
    sed -i 's/^APP_ENV=staging/APP_ENV=staging/' backend/laravel_api/.env
    sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' backend/laravel_api/.env
"

# 6. Build & up containers
log_info "Membangun dan menjalankan containers..."
ssh "$SSH_HOST" "
    cd $DEPLOY_DIR/current
    docker compose -f docker-compose.staging.yml build
    docker compose -f docker-compose.staging.yml up -d
"

# 7. Run migrations
log_info "Menjalankan database migrations..."
ssh "$SSH_HOST" "
    cd $DEPLOY_DIR/current
    sleep 10  # Tunggu MySQL siap
    docker compose -f docker-compose.staging.yml exec -T laravel php artisan migrate --force
"

# 8. Seed database (jika diperlukan)
log_info "Menjalankan database seeders..."
ssh "$SSH_HOST" "
    cd $DEPLOY_DIR/current
    docker compose -f docker-compose.staging.yml exec -T laravel php artisan db:seed --force
"

# 9. Health check
log_info "Menunggu service siap..."
sleep 15
HEALTH_STATUS=$(ssh "$SSH_HOST" "curl -s -o /dev/null -w '%{http_code}' http://localhost/api/v1/health" 2>/dev/null || echo "000")

if [ "$HEALTH_STATUS" = "200" ] || [ "$HEALTH_STATUS" = "503" ]; then
    log_info "Deployment berhasil! HTTP status: $HEALTH_STATUS"
    log_info "Detail: https://staging-api.aurex.app/api/v1/health"
else
    log_warn "Health check mengembalikan status: $HEALTH_STATUS"
    log_warn "Periksa log untuk detail lebih lanjut: docker compose -f docker-compose.staging.yml logs"
fi

# 10. Cleanup — hapus backup lebih dari 7 hari
log_info "Membersihkan backup lama..."
ssh "$SSH_HOST" "find $BACKUP_DIR -maxdepth 1 -type d -mtime +7 -exec rm -rf {} + 2>/dev/null || true"

log_info "✅ Deployment selesai!"
log_info ""
log_info "Endpoint:"
log_info "  API:  https://staging-api.aurex.app/api/v1/health"
log_info "  Pulse: https://staging-api.aurex.app/pulse (jika dikonfigurasi)"
log_info "  Horizon: https://staging-api.aurex.app/horizon (jika dikonfigurasi)"
