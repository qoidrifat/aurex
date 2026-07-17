# 🔷 AUREX — Upgrade Your Look With AI

<div align="center">

**AI-Powered Appearance Analysis for Gen Z & Young Professionals**

[![CI/CD Status](https://img.shields.io/badge/CI%2FCD-GitHub%20Actions-2088FF?style=flat-square&logo=githubactions&logoColor=white)](.github/workflows/main.yml)
[![Backend Tests](https://img.shields.io/badge/Tests-138%20passing-22c55e?style=flat-square&logo=php&logoColor=white)](docs/TESTING_GUIDE.md)
[![Flutter Tests](https://img.shields.io/badge/Flutter%20Tests-49%20passing-22c55e?style=flat-square&logo=flutter&logoColor=white)](docs/TESTING_GUIDE.md)
[![Docker Pulls](https://img.shields.io/badge/Docker-4%20images-2496ED?style=flat-square&logo=docker&logoColor=white)](backend/laravel_api/Dockerfile)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](backend/laravel_api/composer.json)
[![Flutter](https://img.shields.io/badge/Flutter-3.29-02569B?style=flat-square&logo=flutter&logoColor=white)](mobile_app/flutter_app/pubspec.yaml)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](backend/laravel_api/composer.json)
[![Python](https://img.shields.io/badge/Python-3.11-3776AB?style=flat-square&logo=python&logoColor=white)](ai_service/python_ai/requirements.txt)
[![Redis](https://img.shields.io/badge/Redis-7-DC382D?style=flat-square&logo=redis&logoColor=white)](docker-compose.yml)
[![License](https://img.shields.io/badge/License-MIT-3b82f6?style=flat-square)](LICENSE)

</div>

---

## 📋 Overview

**AUREX** is a production-grade SaaS platform that uses AI to analyze facial features and provide personalized style recommendations — face shape detection, skin undertone analysis, hairstyle suggestions, color palette matching, and outfit recommendations.

Built with modern engineering practices: multi-stage Docker builds, Redis queue with Horizon auto-scaling, real-time performance monitoring (Pulse), error tracking (Sentry), comprehensive test coverage, and automated CI/CD.

---

## ✨ Features

| Feature | Description | Status |
|---------|-------------|--------|
| 🔐 **Auth System** | Register, login, email verification, password reset, rate limiting, password strength rules | ✅ Production |
| 🤖 **AI Face Analysis** | Face shape, skin tone, style score via Python FastAPI + MediaPipe | ✅ Production |
| 💾 **Queue Processing** | Redis + Laravel Horizon with auto-scaling workers | ✅ Production |
| 📊 **Performance Monitoring** | Laravel Pulse — slow queries, requests, jobs, cache | ✅ Production |
| 🐛 **Error Tracking** | Sentry integration for Laravel + Flutter | ✅ Production |
| 🗺 **GoRouter Navigation** | Deep linking, named routes, centralized routing | ✅ Production |
| 🌓 **Dark/Light Mode** | Toggle with DarkModeNotifier + Settings Screen | ✅ Production |
| 📱 **Mobile App** | Flutter + Riverpod + Dio + flutter_secure_storage | ✅ Production |
| 🐳 **Docker Deploy** | Multi-stage build, OPcache JIT, Supervisor, Nginx, PHP-FPM | ✅ Production |
| 🔄 **CI/CD** | GitHub Actions — automated tests, lint, build, Docker push | ✅ Production |
| 📋 **Queue Dashboard** | Laravel Horizon — job metrics, failed jobs, throughput | ✅ Production |
| 🏷️ **API Versioning** | /api/v1/ prefix, backward compatibility redirect | ✅ Production |

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────┐
│                    Flutter App                       │
│              (Riverpod + Dio + Sentry)               │
└──────────────────┬──────────────────────────────────┘
                   │ HTTP/JSON + Bearer Token
                   ▼
┌─────────────────────────────────────────────────────┐
│              Laravel API (REST)                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │  Auth    │  │Analysis  │  │  Rate Limiter    │  │
│  │(Sanctum) │  │Controller│  │  5/min · 60/min  │  │
│  └──────────┘  └────┬─────┘  └──────────────────┘  │
│                     │                               │
│  ┌──────────────────▼──────────────────────────┐    │
│  │            AI Service (FastAPI)              │    │
│  │     Face Shape · Skin Tone · Style Score     │    │
│  └─────────────────────────────────────────────┘    │
│                                                     │
│  ┌────────────┐  ┌────────────┐  ┌──────────────┐  │
│  │   MySQL    │  │   Redis    │  │   Horizon    │  │
│  │ (Database) │  │(Cache/Queue│  │ (Queue Mgmt) │  │
│  └────────────┘  │ /Session)  │  └──────────────┘  │
│                  └────────────┘                     │
└─────────────────────────────────────────────────────┘
```

### Service Communication

| Internal | Protocol | Purpose |
|----------|----------|---------|
| Laravel ↔ MySQL | TCP :3306 | Database storage |
| Laravel ↔ Redis | TCP :6379 | Cache, session, queue |
| Laravel ↔ AI Service | HTTP :8001 (X-API-Key) | Face analysis |
| Horizon ↔ Redis | TCP :6379 | Queue management |
| Pulse → MySQL | TCP :3306 | Performance metrics |

---

## 🛠️ Tech Stack

### Backend
| Technology | Version | Purpose |
|------------|---------|---------|
| [Laravel](https://laravel.com) | 12.x | REST API framework |
| [PHP](https://php.net) | 8.3 | Runtime with JIT + OPcache |
| [Sanctum](https://laravel.com/docs/sanctum) | 4.x | API token authentication |
| [Horizon](https://laravel.com/docs/horizon) | 5.x | Redis queue dashboard |
| [Pulse](https://laravel.com/docs/pulse) | 1.x | Performance monitoring |
| [Sentry](https://sentry.io) | 4.x | Error tracking |

### Mobile
| Technology | Version | Purpose |
|------------|---------|---------|
| [Flutter](https://flutter.dev) | 3.29 | Cross-platform mobile app |
| [Riverpod](https://riverpod.dev) | 2.x | State management |
| [Dio](https://pub.dev/packages/dio) | 5.x | HTTP client |
| [Mocktail](https://pub.dev/packages/mocktail) | 1.x | Testing mocks |

### AI Service
| Technology | Version | Purpose |
|------------|---------|---------|
| [Python](https://python.org) | 3.11 | AI runtime |
| [FastAPI](https://fastapi.tiangolo.com) | Latest | REST API framework |
| [MediaPipe](https://mediapipe.dev) | Latest | Face landmark detection |

### Infrastructure
| Technology | Version | Purpose |
|------------|---------|---------|
| [Docker](https://docker.com) | 24+ | Containerization |
| [MySQL](https://mysql.com) | 8.4 | Database |
| [Redis](https://redis.io) | 7.4 | Cache + Session + Queue |
| [Nginx](https://nginx.com) | Latest | Reverse proxy |
| [Supervisor](http://supervisord.org) | Latest | Process manager |

---

## 🚀 Quick Start

### Prerequisites

```bash
PHP 8.2+    pdo_mysql, mbstring, gd, zip, bcmath, redis extensions
Composer 2.x
MySQL 8.0+
Redis 7.x
Python 3.9+
Flutter 3.29+
Docker 24+    (optional, for production deployment)
```

### 1. Backend Setup

```bash
cd backend/laravel_api
composer install
cp .env.example .env
# Edit .env — set DB credentials, APP_URL, AI_SERVICE_API_KEY
php artisan key:generate
php artisan migrate
php artisan serve
```

### 2. AI Service

```bash
cd ai_service/python_ai
pip install -r requirements.txt
export AI_SERVICE_API_KEY=aurex-ai-dev-key-2026
python main.py
```

### 3. Flutter App

```bash
cd mobile_app/flutter_app
flutter pub get
flutter run
```

### 4. Docker Production

```bash
cp .env.example .env
# Edit .env — set APP_KEY, DB credentials
docker-compose up -d
# Access: http://localhost:8000
```

> 📖 **Detailed setup:** [docs/SETUP_GUIDE.md](docs/SETUP_GUIDE.md)

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [![API Docs](https://img.shields.io/badge/📖%20API_DOCS-View-3b82f6?style=flat-square)](docs/API_DOCS.md) | Full API reference with request/response schemas, error codes, rate limits, and monitoring endpoints |
| [![Testing Guide](https://img.shields.io/badge/🧪%20TESTING_GUIDE-View-22c55e?style=flat-square)](docs/TESTING_GUIDE.md) | Comprehensive testing guide — 33+ Laravel tests, 17+ Flutter tests, CI/CD integration |
| [![Setup Guide](https://img.shields.io/badge/⚙️%20SETUP_GUIDE-View-f59e0b?style=flat-square)](docs/SETUP_GUIDE.md) | Development & production setup, Docker deployment, Horizon, Pulse, Sentry configuration |
| [![AI Architecture](https://img.shields.io/badge/🤖%20AI_ARCHITECTURE-View-8b5cf6?style=flat-square)](docs/AI_ARCHITECTURE.md) | AI service architecture, face analysis pipeline |

---

## 📊 Monitoring

| Dashboard | URL | Description |
|-----------|-----|-------------|
| **Horizon** | `http://localhost:8000/horizon` | Queue job metrics, failed jobs, throughput charts, auto-scaling workers |
| **Pulse** | `http://localhost:8000/pulse` | Performance monitoring — slow queries, requests, exceptions, cache, servers |
| **Sentry** | Sentry.io dashboard | Error tracking with stack traces, user context, release tracking |

### Dashboard Access

Both Pulse and Horizon dashboards are protected by authorization gates. Only users with specific email addresses (configured in code) can access them.

```bash
# Queue: check Horizon status
php artisan horizon:status

# Performance: run Pulse check
php artisan pulse:check

# Error tracking: test Sentry integration
php artisan sentry:test
```

---

## 🧪 Testing

```bash
# Backend (Laravel) — 138 tests
cd backend/laravel_api && php artisan test

# Mobile (Flutter) — 49 tests
cd mobile_app/flutter_app && flutter test

# AI Service (Python)
cd ai_service/python_ai && python -m pytest

# Load testing (k6)
k6 run tests/k6/load-test.js

# Full CI/CD pipeline (GitHub Actions)
# Push to main/develop triggers automated tests, linting, and Docker build
```

> 📖 **Test details:** [docs/TESTING_GUIDE.md](docs/TESTING_GUIDE.md)

---

## 🔒 Security

| Layer | Protection |
|-------|-----------|
| **API** | Sanctum token authentication |
| **Rate Limiting** | Login: 5/min · Forgot password: 5/min · API: 60/min |
| **Email** | Mandatory email verification flow |
| **Password** | Reset with one-time tokens, min 8 chars, bcrypt hashing |
| **CORS** | Middleware configured for specific origins |
| **AI Service** | API key authentication between services |
| **Docker** | Non-root `www-data` user, health checks |

---

## 📦 Docker Images

```bash
# Build all services
docker-compose build

# Run production stack
docker-compose up -d

# Service images
aurex-laravel       # Nginx + PHP-FPM + Horizon + Supervisor
aurex-ai-service    # Python FastAPI face analysis
aurex-mysql         # MySQL 8.4 database
aurex-redis         # Redis 7.4 cache/queue/session
```

> 📖 **Docker details & optimization:** [docs/SETUP_GUIDE.md#2-docker-production-deployment](docs/SETUP_GUIDE.md#2-docker-production-deployment)

---

## 📄 License

This project is licensed under the MIT License.

---

<div align="center">
  <sub>Built with ❤️ using Laravel, Flutter, FastAPI, and Redis</sub>
  <br>
  <sub>Documentation updated: July 17, 2026</sub>
</div>
