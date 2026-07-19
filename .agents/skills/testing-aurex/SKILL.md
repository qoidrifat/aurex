# Testing AUREX locally

AUREX = Laravel 13 (PHP 8.3) + Blade/Tailwind/Alpine + MySQL (prod) / SQLite (dev) + a Python FastAPI microservice for face analysis. Upload a selfie, Laravel calls FastAPI, persists the analysis, renders the result.

## Boot both services

```bash
# Laravel app (port 8000)
cd ~/repos/aurex
rm -f database/database.sqlite
php artisan migrate:fresh --seed --force
php artisan storage:link || true
nohup php artisan serve --host=127.0.0.1 --port=8000 >/tmp/laravel.log 2>&1 &

# FastAPI microservice (port 8001)
cd ~/repos/aurex/ai-service
[ -d .venv ] || python3 -m venv .venv
source .venv/bin/activate && pip install -q -r requirements.txt
nohup uvicorn app.main:app --host 127.0.0.1 --port 8001 >/tmp/fastapi.log 2>&1 &

# Verify
curl -s http://127.0.0.1:8000/ >/dev/null && echo laravel_up
curl -s http://127.0.0.1:8001/health
```

Seeded accounts (after `migrate:fresh --seed`):
- `admin@aurex.app` / `password` — role=admin, plan=pro
- `demo@aurex.app` / `password` — role=user, plan=free

The default AI service URL is `http://127.0.0.1:8001` (configurable via `AUREX_AI_URL` in `.env`).

## The key adversarial signal

`app/Services/AurexAiClient.php::analyze()` falls back to a deterministic mock if FastAPI is unreachable. The UI renders identically either way. The only reliable proof of a real round-trip is in the DB:

```sql
SELECT context FROM activity_logs
WHERE action = 'analysis.completed'
ORDER BY id DESC LIMIT 1;
-- pass: {"source":"service"}
-- fail: {"source":"mock"}  <- silent fallback
```

Always assert this after any upload-triggered test — rendering looks fine under fallback, so visual checks alone don't prove integration.

## Scripted HTTP end-to-end (no browser required)

Laravel is standard web middleware (session + VerifyCsrfToken). Script flow:
1. `curl -c jar /login` — grabs `XSRF-TOKEN` cookie + session cookie; scrape `_token` from the form HTML.
2. `curl -b jar -c jar -X POST /login` with `_token` form field, `X-XSRF-TOKEN` header (URL-decode the cookie value!), and `email` + `password`. Expect 302.
3. `curl -b jar /analyze` — grab a fresh `_token`.
4. `curl -b jar -c jar -X POST /analyze -F selfie=@/tmp/test.jpg -F _token=...` — expect 302 to `/analyze/{id}/processing`; extract `{id}` from `Location`.
5. `curl -b jar -X POST /analyze/{id}/run -H 'Accept: application/json' -H 'Content-Type: application/json' -H 'X-XSRF-TOKEN: ...' -d '{}'` — expect 200 JSON `{"status":"completed", ...}`.
6. Inspect DB (see adversarial signal above).
7. `GET /analyze/{id}` — verify page HTML contains the persisted `style_score`, `face_shape`, `skin_undertone`, and at least one label from each recommendation type.
8. `POST /reports/{id}/save` → 302; check `style_reports` row exists.
9. `GET /history` — should link to `/analyze/{id}`.
10. As demo user: `GET /admin` → 403. Re-login as admin: `GET /admin` → 200.

Inspect DB easily via tinker:
```bash
php artisan tinker --execute="echo \\App\\Models\\Analysis::find(1)->toJson();"
```

## VM / browser flakiness

Headed Chrome may misbehave on this VM (window manager drifts it off-screen after the first click; Chrome can then refuse to relaunch, CDP port fails to bind, `--headless --screenshot` exits 0 with no output). When this happens: don't fight it — use the scripted HTTP flow above. Visual checks (scan animation, score ring, dark-mode palette) are genuinely blocked until the VM is rebooted; mark them untested rather than guessing.

If you do want visual evidence: try `wmctrl -r :ACTIVE: -b add,maximized_vert,maximized_horz` first; if it's not installed or doesn't work, a VM reboot is usually faster than debugging window-manager state.

## Running the Laravel test suite

```bash
cd ~/repos/aurex
./vendor/bin/pint --test       # style
php artisan test               # 11 feature + 2 unit
cd ai-service && PYTHONPATH=. .venv/bin/pytest -q
```

## Devin secrets needed

None. Fully self-contained on the VM (SQLite dev DB, deterministic mock AI). Google OAuth is a placeholder — if you ever need to test the real flow, you'll need `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` env vars, but don't request them unless the user explicitly asks for OAuth testing.
