# Contributing to AUREX

Terima kasih telah berkontribusi pada **AUREX** — AI-powered appearance analysis platform.

Panduan ini berisi standar, konvensi, dan workflow yang harus diikuti oleh semua kontributor. Tujuannya adalah menjaga kualitas kode, konsistensi, dan kemudahan onboarding developer baru.

---

## Daftar Isi

1. [Code of Conduct](#1-code-of-conduct)
2. [Quick Start](#2-quick-start)
3. [Project Structure](#3-project-structure)
4. [Branching Strategy](#4-branching-strategy)
5. [Commit Convention](#5-commit-convention)
6. [Pull Request Process](#6-pull-request-process)
7. [Coding Standards](#7-coding-standards)
   - [Laravel (Backend)](#71-laravel-backend)
   - [Flutter (Mobile)](#72-flutter-mobile)
   - [Python AI Service](#73-python-ai-service)
8. [Testing Requirements](#8-testing-requirements)
9. [Documentation Requirements](#9-documentation-requirements)
10. [Code Review Checklist](#10-code-review-checklist)
11. [Environment Setup](#11-environment-setup)
12. [Need Help?](#12-need-help)

---

## 1. Code of Conduct

Proyek ini mengadopsi [Contributor Covenant Code of Conduct](https://www.contributor-covenant.org/version/2/1/code_of_conduct/). Dengan berkontribusi, Anda setuju untuk menjaga lingkungan yang terbuka dan ramah.

**Harap diperhatikan:**
- Gunakan bahasa yang inklusif dan hormat
- Terima kritik konstruktif dengan lapang dada
- Fokus pada apa yang terbaik untuk proyek
- Tunjukkan empati terhadap sesama kontributor

---

## 2. Quick Start

### Prasyarat

```bash
PHP 8.2+    # Extensions: pdo_mysql, mbstring, gd, zip, bcmath, redis
Composer 2.x
MySQL 8.0+  # Atau SQLite untuk development ringan
Redis 7.x   # Untuk queue, cache, session
Python 3.9+
Flutter 3.29+
Docker 24+  # Opsional, untuk production deployment
```

### Setup 5 Menit

```bash
# 1. Clone repository
git clone https://github.com/your-username/AUREX.git
cd AUREX

# 2. Backend
cd backend/laravel_api
cp .env.example .env   # Edit DB credentials
composer install
php artisan key:generate
php artisan migrate
php artisan serve &    # Berjalan di :8000

# 3. AI Service
cd ../../ai_service/python_ai
pip install -r requirements.txt
export AI_SERVICE_API_KEY=aurex-ai-dev-key-2026
python main.py &       # Berjalan di :8001

# 4. Flutter App
cd ../../mobile_app/flutter_app
flutter pub get
flutter run             # Atau flutter test
```

> **Catatan:** Untuk development, gunakan `QUEUE_CONNECTION=sync` di `.env` agar job diproses langsung tanpa worker. Untuk Redis queue, jalankan `php artisan queue:work redis --tries=3`.

---

## 3. Project Structure

```
AUREX/
├── .github/
│   └── workflows/
│       └── main.yml              # CI/CD pipeline
├── backend/
│   └── laravel_api/              # REST API (Laravel 12)
│       ├── app/
│       │   ├── Http/
│       │   │   └── Controllers/  # AuthController, AnalysisController
│       │   ├── Models/           # User model
│       │   ├── Notifications/    # VerifyEmailNotification, PasswordResetNotification
│       │   └── Providers/        # AppServiceProvider, HorizonServiceProvider
│       ├── config/               # horizon.php, pulse.php, queue.php
│       ├── docker/               # PHP, Nginx, Supervisor configs
│       ├── routes/               # api.php, web.php, console.php
│       ├── tests/                # PHPUnit tests (Feature + Unit)
│       ├── Dockerfile            # Multi-stage build
│       └── composer.json
├── mobile_app/
│   └── flutter_app/              # Mobile App (Flutter)
│       ├── lib/
│       │   ├── core/             # colors.dart, constants
│       │   ├── models/           # UserModel, analysis models
│       │   ├── providers/        # auth_provider.dart (Riverpod)
│       │   ├── screens/          # Login, Register, Upload, Result, Profile, dll
│       │   ├── services/         # api_service.dart, auth_service.dart
│       │   └── widgets/          # EmptyState, SkeletonLoader, PrimaryButton, StyleCard
│       └── test/                 # Flutter tests
├── ai_service/
│   └── python_ai/                # AI Service (FastAPI)
│       ├── main.py               # Face analysis endpoints
│       └── Dockerfile
├── docs/                         # Dokumentasi
│   ├── API_DOCS.md               # Dokumentasi API lengkap
│   ├── TESTING_GUIDE.md          # Panduan testing
│   ├── SETUP_GUIDE.md            # Panduan setup & deployment
│   └── AI_ARCHITECTURE.md        # Arsitektur AI
├── docker-compose.yml            # Production stack
├── README.md
└── CONTRIBUTING.md               # ← Anda di sini
```

### Aturan Struktur

| Layer | Aturan |
|-------|--------|
| **Controllers** | 1 controller per resource. Letakkan di `app/Http/Controllers/` |
| **Providers** | Tambahkan provider baru di `app/Providers/` dan daftarkan di `bootstrap/providers.php` |
| **Flutter Screens** | 1 file per screen di `lib/screens/` |
| **Flutter Widgets** | Widget reusable di `lib/widgets/` |
| **Tests** | Mirror struktur source (1 test file per class) |
| **Routes** | API routes di `routes/api.php`, web di `routes/web.php` |

---

## 4. Branching Strategy

Kita menggunakan **GitFlow** yang disederhanakan:

```
main        → Production-ready code (hanya dari develop/release)
develop     → Integration branch (default untuk PR)
feature/*   → Fitur baru (branch dari develop)
fix/*       → Bug fix (branch dari develop)
release/*   → Persiapan rilis (branch dari develop, merge ke main + develop)
hotfix/*    → Emergency fix production (branch dari main, merge ke main + develop)
```

### Aturan

| Branch | Sumber | Target Merge | Usia |
|--------|--------|--------------|------|
| `feature/xxx` | `develop` | `develop` | Maks 3 hari |
| `fix/xxx` | `develop` | `develop` | Maks 1 hari |
| `release/x.x.x` | `develop` | `main` + `develop` | Maks 1 minggu |
| `hotfix/xxx` | `main` | `main` + `develop` | Maks 1 hari |

### Naming Convention

```bash
# Feature
git checkout -b feature/add-google-sign-in develop
git checkout -b feature/analysis-history-pagination develop

# Fix
git checkout -b fix/login-rate-limit-error develop
git checkout -b fix/selfie-upload-timeout develop

# Release
git checkout -b release/1.2.0 develop

# Hotfix
git checkout -b hotfix/critical-auth-bug main
```

---

## 5. Commit Convention

Gunakan [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Types

| Type | Penggunaan |
|------|------------|
| `feat` | Fitur baru |
| `fix` | Bug fix |
| `refactor` | Refaktor kode (tanpa perubahan fungsional) |
| `test` | Menambah atau memperbaiki test |
| `docs` | Dokumentasi |
| `style` | Formatting, whitespace (bukan perubahan CSS/Style) |
| `perf` | Optimasi performa |
| `chore` | Build, dependencies, config |
| `ci` | CI/CD workflow |
| `security` | Perbaikan keamanan |

### Scopes

| Scope | Area |
|-------|------|
| `api` | Laravel backend |
| `mobile` | Flutter app |
| `ai` | Python AI service |
| `docker` | Dockerfile, docker-compose |
| `docs` | Dokumentasi |
| `ci` | GitHub Actions |

### Contoh

```bash
git commit -m "feat(api): add email verification endpoint"
git commit -m "fix(mobile): handle network timeout on selfie upload"
git commit -m "refactor(api): extract rate limiter configuration to AppServiceProvider"
git commit -m "test(mobile): add auth provider unit tests for login error states"
git commit -m "docs: update API_DOCS.md with new Horizon monitoring section"
git commit -m "perf(docker): enable OPcache JIT for PHP 8.3"
git commit -m "security(api): add rate limiting to forgot-password endpoint"
git commit -m "ci: add Flutter analyze step to CI/CD workflow"
```

---

## 6. Pull Request Process

### Template

Setiap PR **wajib** menyertakan:

```markdown
## Deskripsi
[Jelaskan perubahan yang dilakukan]

## Related Issue
Closes #[issue_number]

## Type of Change
- [ ] feat (fitur baru)
- [ ] fix (bug fix)
- [ ] refactor (refaktor)
- [ ] test (testing)
- [ ] docs (dokumentasi)
- [ ] perf (optimasi)
- [ ] security (keamanan)

## Testing
- [ ] Backend tests: php artisan test (wajib)
- [ ] Flutter tests: flutter test
- [ ] Flutter analyze: flutter analyze (wajib)
- [ ] Manual testing dijelaskan

## Checklist
- [ ] Kode mengikuti coding standards
- [ ] Tidak ada debug code / commented code
- [ ] Dokumentasi diupdate jika perlu
- [ ] Environment variables diupdate di .env.example
- [ ] Migration sudah dibuat (jika ada perubahan DB)
```

### Flow

```
1. Branch dari develop         → git checkout -b feature/xxx develop
2. Implementasi + commit       → git commit -m "feat(api): ..."
3. Push ke remote              → git push origin feature/xxx
4. Buat PR ke develop          → Gunakan template di atas
5. Tunggu review               → Minimal 1 approval
6. Merge (Squash & Merge)      → Hapus branch setelah merge
7. Deploy ke staging           → Setelah merge ke develop
```

### Aturan PR

| Aturan | Keterangan |
|--------|------------|
| **Ukuran** | Maks 400 baris perubahan. PR besar → pecah jadi beberapa PR |
| **Test** | Wajib menyertakan test untuk kode baru |
| **CI** | Semua GitHub Actions checks harus hijau |
| **Review** | Minimal 1 approval dari maintainer |
| **Conflict** | Harus di-rebase dengan develop sebelum merge |
| **Branch** | Hapus branch setelah merge |

---

## 7. Coding Standards

### 7.1 Laravel (Backend)

#### PSR Standards

- Kode **wajib** mengikuti [PSR-12](https://www.php-fig.org/psr/psr-12/)
- Autoloading mengikuti [PSR-4](https://www.php-fig.org/psr/psr-4/)
- Format otomatis dengan Laravel Pint: `./vendor/bin/pint`

#### Naming Convention

| Element | Convention | Contoh |
|---------|------------|--------|
| Class | `PascalCase` | `AuthController` |
| Method | `camelCase` | `uploadSelfie()` |
| Variable | `camelCase` | `$imageId` |
| Route | `snake_case` | `/forgot-password` |
| Database Column | `snake_case` | `email_verified_at` |
| Config | `snake_case` | `QUEUE_CONNECTION` |
| Test Method | `snake_case` | `test_user_can_register()` |

#### Controller Pattern

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExampleController extends Controller
{
    public function index(Request $request)
    {
        // Validasi
        $validated = $request->validate([...]);

        // Logic → panggil service jika kompleks
        // Response
        return response()->json([...], 200);
    }
}
```

#### Best Practices

1. **Controllers tipis, Services gemuk** — pindahkan logic bisnis ke service class
2. **Validasi di controller** — gunakan `$request->validate()` atau Form Request
3. **Jangan gunakan `dd()`/`dump()`** — gunakan `Log::info()` atau `logger()` untuk debugging
4. **Gunakan injeksi dependency** — hindari `app()` helper di controller
5. **Query optimization** — hindari N+1, gunakan `with()` untuk eager loading
6. **Rate limiter** — daftarkan di `AppServiceProvider::boot()`, bukan di routes
7. **Konfigurasi** — semua env vars via `config/` files, bukan `env()` langsung di kode

### 7.2 Flutter (Mobile)

#### Naming Convention

| Element | Convention | Contoh |
|---------|------------|--------|
| File | `snake_case` | `login_screen.dart` |
| Class | `PascalCase` | `LoginScreen` |
| Function | `camelCase` | `checkAuthStatus()` |
| Variable | `camelCase` | `authState` |
| Const | `camelCase` | `const olive = Color(0xFF556B2F)` |
| Test File | `snake_case` | `auth_provider_test.dart` |
| Test Name | `descriptive string` | `should authenticate on successful login` |

#### State Management (Riverpod)

```dart
// 1. Define State class
class AuthState {
  const AuthState({this.status = AuthStatus.initializing, this.user});

  final AuthStatus status;
  final UserModel? user;
}

// 2. Define Notifier
class AuthNotifier extends StateNotifier<AuthState> {
  AuthNotifier(this._authService) : super(const AuthState());

  Future<bool> login(String email, String password) async {
    state = state.copyWith(status: AuthStatus.loading);
    try {
      final user = await _authService.login(email, password);
      state = AuthState(status: AuthStatus.authenticated, user: user);
      return true;
    } on ApiException catch (e) {
      state = state.copyWith(status: AuthStatus.error, errorMessage: e.message);
      return false;
    }
  }
}
```

#### Widget Pattern

```dart
class ExampleScreen extends ConsumerStatefulWidget {
  const ExampleScreen({super.key});

  @override
  ConsumerState<ExampleScreen> createState() => _ExampleScreenState();
}

class _ExampleScreenState extends ConsumerState<ExampleScreen> {
  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Example')),
      body: // UI here
    );
  }
}
```

#### Best Practices

1. **Gunakan `const` constructor** untuk semua widget jika memungkinkan
2. **Pisahkan UI dan logic** — screen hanya build UI, logic di provider
3. **Jangan panggil API langsung di widget** — gunakan service class
4. **Gunakan `SkeletonLoader` untuk loading state** — jangan `CircularProgressIndicator` saja
5. **Gunakan `EmptyState` widget** untuk data kosong — konsisten di semua screen
6. **Gunakan `AurexColors`** — jangan hardcode warna
7. **Semua string user-facing** dalam Bahasa Indonesia
8. **Tes widget dengan `pumpWidget`** — verifikasi UI berfungsi

### 7.3 Python AI Service

#### Naming Convention

| Element | Convention | Contoh |
|---------|------------|--------|
| File | `snake_case` | `face_analyzer.py` |
| Class | `PascalCase` | `FaceAnalyzer` |
| Function | `snake_case` | `analyze_face()` |
| Variable | `snake_case` | `style_score` |
| Constant | `UPPER_SNAKE_CASE` | `API_KEY` |

#### Guidelines

```python
from fastapi import FastAPI, HTTPException, Depends
from pydantic import BaseModel

app = FastAPI(title="AUREX AI Service")

class AnalysisRequest(BaseModel):
    image_url: str

@app.post("/analyze-face")
async def analyze_face(request: AnalysisRequest, api_key: str = Depends(verify_api_key)):
    # Logic
    return {"face_shape": "Oval", "style_score": 85.5}
```

#### Best Practices

1. **Gunakan type hints** — semua fungsi wajib annotated
2. **Pydantic models** — untuk request/response validation
3. **API key authentication** — via dependency injection `Depends(verify_api_key)`
4. **Error handling** — gunakan HTTPException dengan status code yang sesuai
5. **Async endpoints** — untuk I/O operations (image download)

---

## 8. Testing Requirements

### Minimum Coverage

**Backend (Laravel):** Semua endpoint baru wajib memiliki test.

```bash
cd backend/laravel_api
php artisan test --filter=NamaTest
```

**Format test method:**
```php
public function test_user_can_register()
public function test_register_fails_with_duplicate_email()
```

**Flutter:** Semua provider baru wajib memiliki unit test.

```bash
cd mobile_app/flutter_app
flutter test
flutter test --coverage
```

**Format test:**
```dart
test('should authenticate on successful login', () async { ... });
```

### Yang Harus Di-test

| Komponen | Harus Di-test | Contoh |
|----------|---------------|--------|
| Controller | Response status, struktur JSON | `assertStatus(200)`, `assertJsonStructure([...])` |
| Validation | Valid input, invalid input, edge cases | Email kosong, password pendek, duplicate |
| Rate Limiting | Request melebihi batas | 6x login → 429 |
| Auth Flow | Login, logout, token lifecycle | Token terhapus setelah logout |
| Provider | State changes, error handling, loading state | `AuthStatus.loading`, `AuthStatus.error` |
| Widget | Render, form validation, user interaction | Email kosong → error message muncul |

### Pre-deploy Checklist

- [ ] `php artisan test` — **35+** backend tests PASS
- [ ] `flutter test` — **17+** Flutter tests PASS
- [ ] `flutter analyze` — **0** issues
- [ ] Rate limiting teruji (login, forgot-password, API)
- [ ] Email verification flow teruji
- [ ] Password reset flow teruji (token one-time use)
- [ ] Pulse dimatikan di test environment
- [ ] CI/CD pipeline berjalan tanpa error

> 📖 **Detail testing:** [docs/TESTING_GUIDE.md](docs/TESTING_GUIDE.md)

---

## 9. Documentation Requirements

Setiap perubahan **wajib** diikuti update dokumentasi yang sesuai:

| Perubahan | Dokumen yang Harus Diupdate |
|-----------|-----------------------------|
| Endpoint baru / perubahan response | `docs/API_DOCS.md` |
| Perubahan setup / environment | `docs/SETUP_GUIDE.md` |
| Test baru | `docs/TESTING_GUIDE.md` |
| Perubahan env vars | `.env.example` + `docker-compose.yml` |
| Perubahan konfigurasi monitoring | `docs/SETUP_GUIDE.md` (monitoring section) |
| Perubahan Docker | `Dockerfile` + `docker-compose.yml` + `docs/SETUP_GUIDE.md` |
| Fitur AI baru | `docs/AI_ARCHITECTURE.md` |

### Aturan Dokumentasi

1. **Gunakan Bahasa Indonesia** — dokumentasi utama dalam Bahasa Indonesia
2. **Kode examples** — setiap endpoint punya contoh request dan response
3. **Markdown** — semua dokumentasi dalam format Markdown
4. **Update README.md** — jika ada fitur baru, update feature table
5. **Jelaskan "why"** — bukan hanya "what". Beri konteks.

---

## 10. Code Review Checklist

Reviewer akan memeriksa hal-hal berikut:

### Functionality

- [ ] Apakah kode melakukan apa yang dijelaskan di PR description?
- [ ] Apakah ada edge cases yang tidak tertangani?
- [ ] Apakah error handling sudah benar?

### Security

- [ ] Apakah input sudah divalidasi?
- [ ] Apakah ada potensi SQL injection / XSS?
- [ ] Apakah rate limiting sudah sesuai?
- [ ] Apakah API key / token tidak bocor?

### Performance

- [ ] Apakah ada N+1 query?
- [ ] Apakah query sudah di-index?
- [ ] Apakah ada expensive operation yang perlu async?

### Code Quality

- [ ] Apakah mengikuti PSR-12 / Flutter conventions?
- [ ] Apakah ada kode mati / commented code?
- [ ] Apakah nama variabel/fungsi deskriptif?
- [ ] Apakah tidak ada debug code (`dd()`, `print()`, `console.log()`)?

### Testing

- [ ] Apakah ada test untuk kode baru?
- [ ] Apakah test mencakup error cases?
- [ ] Apakah test berjalan di CI?

### Documentation

- [ ] Apakah API docs diupdate jika ada endpoint baru?
- [ ] Apakah `.env.example` diupdate jika ada env var baru?
- [ ] Apakah changelog perlu diupdate?

---

## 11. Environment Setup

### File Environment

| File | Lokasi | Digunakan Untuk |
|------|--------|-----------------|
| `.env` (root) | `./.env` | Docker Compose (APP_KEY, DB_PASSWORD, AI_SERVICE_API_KEY, SENTRY_DSN) |
| `.env` (Laravel) | `backend/laravel_api/.env` | Development local |
| `.env.example` | `backend/laravel_api/.env.example` | Template env vars |

### Environment Variables Checklist

```env
# === WAJIB DIISI ===
APP_KEY=                          # Generate: php artisan key:generate --show
AI_SERVICE_API_KEY=               # Harus sama antara Laravel & AI Service

# === DATABASE ===
DB_CONNECTION=mysql               # Atau sqlite untuk development ringan
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aurex
DB_USERNAME=root
DB_PASSWORD=

# === REDIS (untuk queue & cache) ===
QUEUE_CONNECTION=redis            # Gunakan 'sync' jika tanpa Redis
CACHE_STORE=redis                 # Gunakan 'file' jika tanpa Redis
SESSION_DRIVER=redis              # Gunakan 'file' jika tanpa Redis

# === OPSIONAL ===
SENTRY_LARAVEL_DSN=               # Error tracking
PULSE_ENABLED=true                # Performance monitoring
```

---

## 12. Need Help?

| Sumber | Link |
|--------|------|
| 📖 **API Documentation** | [docs/API_DOCS.md](docs/API_DOCS.md) |
| 🧪 **Testing Guide** | [docs/TESTING_GUIDE.md](docs/TESTING_GUIDE.md) |
| ⚙️ **Setup Guide** | [docs/SETUP_GUIDE.md](docs/SETUP_GUIDE.md) |
| 🤖 **AI Architecture** | [docs/AI_ARCHITECTURE.md](docs/AI_ARCHITECTURE.md) |
| 🐳 **Docker Compose** | [docker-compose.yml](docker-compose.yml) |
| 🔄 **CI/CD Workflow** | [.github/workflows/main.yml](.github/workflows/main.yml) |

### Quick Commands Reference

```bash
# Backend
cd backend/laravel_api
composer install                  # Install dependencies
php artisan migrate               # Run migrations
php artisan test                  # Run all tests
./vendor/bin/pint                 # Format kode (PSR-12)

# Mobile
cd mobile_app/flutter_app
flutter pub get                   # Install dependencies
flutter analyze                   # Lint check
flutter test                      # Run all tests
flutter test --coverage           # Test with coverage

# AI Service
cd ai_service/python_ai
pip install -r requirements.txt   # Install dependencies
python main.py                    # Run service

# Docker
docker-compose up -d              # Start all services
docker-compose logs -f            # View logs
docker-compose down               # Stop all services
```

---

*Terakhir diperbarui: 16 Juli 2026*

*Dokumen ini hidup — jika ada proses yang perlu diperbaiki, buat PR untuk update CONTRIBUTING.md!*
