# AUREX

AI-powered personal style intelligence for modern men. Upload a selfie → the
platform analyzes your face shape, skin undertone, and proportions, then
returns a personalized style report with hairstyle, color palette, and outfit
recommendations.

## Architecture

```
┌────────────────┐      ┌─────────────────────┐      ┌──────────────────┐
│  Laravel app   │ ───▶ │ FastAPI AI service  │      │      MySQL       │
│ Blade + Tailwind│      │  (mock analysis)    │ ◀──▶ │  (or SQLite for  │
│ + AlpineJS     │      │  ai-service/        │      │   local dev)     │
└────────────────┘      └─────────────────────┘      └──────────────────┘
       │
       ▼
  /public/storage/selfies/  (local)  or  s3://...  (optional)
```

- **Backend**: Laravel 13 (PHP 8.3) — controllers, Eloquent models, REST
  routes, Sanctum-protected API, Blade views.
- **Frontend**: Blade + TailwindCSS v4 + AlpineJS — dark-mode first, Poppins
  typography, AUREX color palette.
- **Database**: MySQL 8 for production, SQLite for local dev / CI.
- **AI microservice**: Python FastAPI (`ai-service/`) returning deterministic
  mock analyses. Swap for a real model (MediaPipe / OpenCV / fine-tuned CNN)
  without touching the Laravel side.
- **Storage**: `public` disk by default; configure S3-compatible storage via
  `AWS_*` variables in `.env`.

## Folder structure

```
aurex/
├── app/
│   ├── Http/Controllers/      # SiteController, DashboardController, AnalysisController, ReportController, ProfileController, Auth/*, Admin/AdminController
│   ├── Http/Middleware/       # EnsureUserIsAdmin
│   ├── Models/                # User, UploadedImage, Analysis, StyleReport, Recommendation, ActivityLog
│   └── Services/              # AurexAiClient (HTTP → FastAPI), StyleReportComposer
├── ai-service/                # FastAPI microservice (mock face analysis)
├── database/
│   ├── migrations/            # users, uploaded_images, analyses, style_reports, recommendations, activity_logs
│   ├── factories/             # one factory per model
│   └── seeders/               # DatabaseSeeder — admin + demo user + sample analyses
├── resources/
│   ├── css/app.css            # Tailwind v4 with AUREX theme tokens
│   ├── js/app.js              # Alpine bootstrap
│   └── views/
│       ├── layouts/           # app, marketing, dashboard, auth
│       ├── components/        # brand-mark, icon, score-ring, color-swatch, stat-card, nav-link
│       ├── partials/          # nav, sidebar, topbar, footer
│       ├── site/              # landing
│       ├── auth/              # login, register
│       ├── dashboard/         # index
│       ├── analysis/          # upload, processing, result, history
│       ├── reports/           # index, show, pdf
│       ├── profile/           # edit, settings
│       └── admin/             # dashboard, users, analyses, images
├── routes/
│   ├── web.php                # public + authenticated + admin routes
│   └── api.php                # Sanctum-protected REST API
├── docker-compose.yml         # MySQL + AI service
└── .env.example
```

## Getting started

### Prerequisites

- PHP 8.3+
- Composer 2
- Node 18+ / npm
- MySQL 8 (or SQLite for quickest setup)
- Python 3.11+ (for the AI microservice)

### 1. Install and configure the Laravel app

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
```

By default `.env` uses SQLite (no server required). To use MySQL locally, set:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aurex
DB_USERNAME=aurex
DB_PASSWORD=aurex
```

Create the storage symlink and run migrations + seeds:

```bash
php artisan storage:link
php artisan migrate --seed
```

Build the frontend:

```bash
npm run build     # production
# or
npm run dev       # HMR dev server
```

Run the app:

```bash
php artisan serve --port=8000
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000).

### 2. Run the AI microservice

In a second terminal:

```bash
cd ai-service
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
```

The Laravel app points at `http://127.0.0.1:8001` via `AUREX_AI_URL`. If the
service is unreachable, the client transparently falls back to an in-process
mock so the flow still works end-to-end.

### 3. Or run everything with docker-compose

```bash
docker compose up --build
```

This starts MySQL and the AI microservice. The Laravel app still runs on your
host (`php artisan serve`) — point it at the containers with:

```
DB_HOST=127.0.0.1
DB_PORT=3306
AUREX_AI_URL=http://127.0.0.1:8001
```

## Seeded accounts

After `php artisan migrate --seed` you can log in with:

| Email              | Password   | Role  |
| ------------------ | ---------- | ----- |
| `admin@aurex.app`  | `password` | admin |
| `demo@aurex.app`   | `password` | user  |

The admin account has access to `/admin`.

## Tests & linting

```bash
# Laravel
./vendor/bin/pint --test          # code style check
php artisan test                  # feature tests

# AI microservice
cd ai-service
PYTHONPATH=. pytest
```

## API

`/api/*` routes are Sanctum-protected (Bearer token). Issue a token in tinker:

```bash
php artisan tinker
>>> App\Models\User::where('email','demo@aurex.app')->first()->createToken('cli')->plainTextToken
```

Then:

```bash
curl -H "Authorization: Bearer <token>" http://127.0.0.1:8000/api/analyses
```

## Design system

- Color palette: `charcoal #1C1C1C`, `olive #556B2F`, `rust #B7410E`,
  `cream #F5F5F5` (see `resources/css/app.css` → `@theme`).
- Typography: Poppins (loaded from Google Fonts in `layouts/app.blade.php`).
- Iconography: thin-line SVGs rendered by `<x-icon name="..." />`.
- Animations: AUREX scan line, pulse glow, card hover lift — all in
  `resources/css/app.css`.

## Scope of this scaffold

This is a production-ready scaffold, not a fully-trained product:

- Real face analysis is mocked — replace the mock in
  `ai-service/app/analysis.py` with your own model.
- Google OAuth button is wired but requires `laravel/socialite` + a Google
  client ID to complete. See `App\Http\Controllers\Auth\GoogleController`.
- PDF export is served as printable HTML; plug in `dompdf`/`browsershot` if
  you need binary PDFs.
- Billing for "AUREX Pro" is stubbed — wire Stripe / Laravel Cashier when
  ready.
