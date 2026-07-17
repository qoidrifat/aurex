# AUREX API Documentation

Base URL (Development): `http://localhost:8000/api`

Base URL (Docker): `http://localhost:8000/api`

---

## Daftar Isi

1. [Autentikasi](#1-autentikasi)
   - [Register](#11-register)
   - [Login](#12-login)
   - [Logout](#13-logout)
2. [Email Verification](#2-email-verification)
   - [Verify Email](#21-verify-email)
   - [Resend Verification](#22-resend-verification)
3. [Password Reset](#3-password-reset)
   - [Forgot Password](#31-forgot-password)
   - [Reset Password](#32-reset-password)
4. [Analysis](#4-analysis)
   - [Upload Selfie](#41-upload-selfie)
   - [Analyze Face](#42-analyze-face)
   - [History](#43-history)
   - [Result Detail](#44-result-detail)
5. [Rate Limiting](#5-rate-limiting)
6. [Error Response Format](#6-error-response-format)
7. [HTTP Status Codes](#7-http-status-codes)
8. [Queue & Monitoring](#8-queue--monitoring)
   - [Laravel Horizon](#81-laravel-horizon)
   - [Laravel Pulse](#82-laravel-pulse)
   - [Sentry Error Tracking](#83-sentry-error-tracking)
9. [Docker Service Endpoints](#9-docker-service-endpoints)
10. [Health Check & System](#10-health-check--system)

---

## 1. Autentikasi

### 1.1 Register

Mendaftarkan user baru. Email verifikasi akan dikirim secara otomatis.

**Endpoint:** `POST /register`

**Rate Limit:** Tidak ada rate limit khusus (menggunakan limit API default).

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (200 — Success):**
```json
{
  "access_token": "1|abc123def456...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2026-07-16T10:00:00.000000Z"
  },
  "message": "Registrasi berhasil. Silakan cek email untuk verifikasi."
}
```

**Response (422 — Validation Error):**
```json
{
  "message": "Nama lengkap wajib diisi. (and 2 more errors)",
  "errors": {
    "name": ["Nama lengkap wajib diisi."],
    "email": ["Email wajib diisi."],
    "password": ["Password wajib diisi."]
  }
}
```

Catatan: Semua error message dari Form Request dalam Bahasa Indonesia.

**Validation Rules:**
| Field | Rule |
|-------|------|
| `name` | Required, string, max:255 |
| `email` | Required, valid email format, unique:users |
| `password` | Required, string, min:8, confirmed |
| `password_confirmation` | Required, must match `password` |

---

### 1.2 Login

Login dengan email dan password. Mendukung flag `require_verified` untuk memeriksa verifikasi email.

**Endpoint:** `POST /login`

**Rate Limit:** `throttle:login` — **5 percobaan per menit** per kombinasi email + IP.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123",
  "require_verified": false
}
```

| Parameter | Tipe | Required | Default | Deskripsi |
|-----------|------|----------|---------|-----------|
| `email` | string | ✅ | - | Email user |
| `password` | string | ✅ | - | Password user |
| `require_verified` | boolean | ❌ | `false` | Jika `true`, hanya user dengan email terverifikasi yang bisa login |

**Response (200 — Success):**
```json
{
  "access_token": "1|abc123def456...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2026-07-16T10:00:00.000000Z",
    "created_at": "2026-07-16T10:00:00.000000Z"
  }
}
```

**Response (403 — Email Belum Diverifikasi):**
Hanya muncul jika `require_verified: true` dan email belum diverifikasi.
```json
{
  "message": "Email belum diverifikasi. Silakan cek email Anda.",
  "needs_verification": true,
  "email": "john@example.com"
}
```

**Response (422 — Invalid Credentials / Validation Error):**
```json
{
  "message": "Email atau password salah.",
  "errors": {
    "email": ["Email atau password salah."]
  }
}
```

Atau untuk validasi field:
```json
{
  "message": "Email wajib diisi. (and 1 more errors)",
  "errors": {
    "email": ["Email wajib diisi."],
    "password": ["Password wajib diisi."]
  }
}
```

**Response (429 — Rate Limited):**
```json
{
  "message": "Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit."
}
```

---

### 1.3 Logout

Menghapus token Sanctum yang sedang aktif.

**Endpoint:** `POST /logout`

**Headers:** `Authorization: Bearer {token}`

**Response (200 — Success):**
```json
{
  "message": "Logged out successfully"
}
```

**Response (401 — Unauthenticated):**
```json
{
  "message": "Unauthenticated."
}
```

---

## 2. Email Verification

### 2.1 Verify Email

Memverifikasi alamat email menggunakan link yang dikirim ke email user.

**Endpoint:** `GET /verify-email/{id}/{hash}`

**Parameters:**
| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| `id` | integer | ID user |
| `hash` | string | SHA1 hash dari email user (`sha1($user->email)`) |

**Response (200 — Success / Already Verified):**
```json
{
  "message": "Email berhasil diverifikasi!"
}
```
Atau jika sudah diverifikasi sebelumnya:
```json
{
  "message": "Email sudah diverifikasi."
}
```

**Response (400 — Invalid Link):**
```json
{
  "message": "Link verifikasi tidak valid."
}
```

---

### 2.2 Resend Verification

Mengirim ulang email verifikasi.

**Endpoint:** `POST /resend-verification`

**Rate Limit:** Mengikuti limit API default.

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Response (200 — Success):**
```json
{
  "message": "Email verifikasi telah dikirim ulang."
}
```

**Response (200 — Already Verified):**
```json
{
  "message": "Email sudah diverifikasi."
}
```

**Response (422 — Email Not Found):**
```json
{
  "message": "The selected email is invalid.",
  "errors": {
    "email": ["The selected email is invalid."]
  }
}
```

---

## 3. Password Reset

### 3.1 Forgot Password

Mengirim link reset password ke email user.

**Endpoint:** `POST /forgot-password`

**Rate Limit:** `throttle:5,1` — **5 request per menit** (tidak peduli email).

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Response (200 — Success):**
```json
{
  "message": "Link reset password telah dikirim ke email Anda."
}
```

**Response (422 — Email Not Found):**
```json
{
  "message": "The selected email is invalid.",
  "errors": {
    "email": ["The selected email is invalid."]
  }
}
```

**Response (429 — Rate Limited):**
```json
{
  "message": "Too Many Attempts."
}
```

---

### 3.2 Reset Password

Mereset password menggunakan token dari email.

**Endpoint:** `POST /reset-password`

**Rate Limit:** Tidak ada rate limit khusus.

**Request Body:**
```json
{
  "email": "john@example.com",
  "token": "reset-token-from-email",
  "password": "new_password_123",
  "password_confirmation": "new_password_123"
}
```

**Response (200 — Success):**
```json
{
  "message": "Password berhasil direset. Silakan login dengan password baru."
}
```

**Response (400 — Invalid / Expired Token):**
```json
{
  "message": "Token reset password tidak valid atau sudah kadaluarsa."
}
```

**Response (422 — Validation Error):**
```json
{
  "message": "The password field must be at least 8 characters. (and 1 more errors)",
  "errors": {
    "password": ["The password field must be at least 8 characters.", "The password confirmation does not match."]
  }
}
```

---

## 4. Analysis

Semua endpoint Analysis membutuhkan autentikasi.

**Headers:** `Authorization: Bearer {token}`

**Rate Limit:** `throttle:api` — **60 request per menit** per user.

### 4.1 Upload Selfie

Mengupload foto selfie untuk dianalisis.

**Endpoint:** `POST /upload-selfie`

**Content-Type:** `multipart/form-data`

**Request Body:**
| Parameter | Tipe | Required | Deskripsi |
|-----------|------|----------|-----------|
| `image` | file | ✅ | File gambar (JPG, PNG, maks 5MB) |

**Response (200 — Success):**
```json
{
  "message": "Image uploaded successfully",
  "image": {
    "id": 1,
    "user_id": 1,
    "analysis_id": null,
    "image_path": "selfies/abc123.jpg",
    "image_url": "http://localhost:8000/storage/selfies/abc123.jpg",
    "created_at": "2026-07-16T10:00:00.000000Z"
  }
}
```

**Response (422 — Validation Error):**
```json
{
  "message": "File gambar wajib diupload. (and 1 more errors)",
  "errors": {
    "image": ["File gambar wajib diupload."]
  }
}
```

---

### 4.2 Analyze Face

Menganalisis fitur wajah dari foto yang sudah diupload. Mengirim request ke AI Service untuk perhitungan skor style.

**Endpoint:** `POST /analyze`

**Request Body:**
```json
{
  "image_id": 1
}
```

**Response (200 — Success):**
```json
{
  "message": "Analysis completed",
  "analysis": {
    "id": 1,
    "user_id": 1,
    "face_shape": "oval",
    "undertone": "warm",
    "style_score": 85.5,
    "created_at": "2026-07-16T10:00:00.000000Z",
    "updated_at": "2026-07-16T10:00:00.000000Z",
    "recommendation": {
      "id": 1,
      "analysis_id": 1,
      "hairstyle": ["Pompadour", "Quiff"],
      "color_palette": ["Earth tones", "Olive green"],
      "outfit": ["Casual blazer", "Jeans"],
      "created_at": "2026-07-16T10:00:00.000000Z"
    }
  }
}
```

**Response (503 — AI Service Unavailable):**
```json
{
  "message": "Gagal terhubung ke AI Service setelah beberapa percobaan. Silakan coba lagi nanti."
}
```

**Response (500 — Analysis Error):**
```json
{
  "message": "Analisis AI gagal. Silakan coba lagi."
}
```

---

### 4.3 History

Mendapatkan riwayat analisis user.

**Endpoint:** `GET /history`

**Response (200 — Success):**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "face_shape": "oval",
      "undertone": "warm",
      "style_score": 85.5,
      "created_at": "2026-07-16T10:00:00.000000Z",
      "updated_at": "2026-07-16T10:00:00.000000Z",
      "recommendation": {
        "id": 1,
        "analysis_id": 1,
        "hairstyle": ["Pompadour", "Quiff"],
        "color_palette": ["Earth tones", "Olive green"],
        "outfit": ["Casual blazer", "Jeans"],
        "created_at": "2026-07-16T10:00:00.000000Z"
      }
    }
  ],
  "pagination": {
    "total": 1,
    "per_page": 10,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  }
}
```

---

### 4.4 Result Detail

Mendapatkan detail hasil analisis berdasarkan ID.

**Endpoint:** `GET /result/{id}`

**Parameters:**
| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| `id` | integer | ID hasil analisis |

**Response (200 — Success):**
> Response langsung dari `AnalysisResource` dibungkus dalam key `data` oleh Laravel.

```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "face_shape": "oval",
    "undertone": "warm",
    "style_score": 85.5,
    "created_at": "2026-07-16T10:00:00.000000Z",
    "updated_at": "2026-07-16T10:00:00.000000Z",
    "recommendation": {
      "id": 1,
      "analysis_id": 1,
      "hairstyle": ["Pompadour", "Quiff"],
      "color_palette": ["Earth tones", "Olive green"],
      "outfit": ["Casual blazer", "Jeans"],
      "created_at": "2026-07-16T10:00:00.000000Z"
    }
  }
}
```

**Response (404 — Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\Analysis] 999"
}
```

---

## 5. Rate Limiting

AUREX menggunakan **Laravel Rate Limiter** dengan konfigurasi berikut:

| Limiter | Endpoint | Batas | Window | Key |
|---------|----------|-------|--------|-----|
| `login` | `POST /login` | **5 request** | 1 menit | Email + IP |
| `5,1` | `POST /forgot-password` | **5 request** | 1 menit | IP |
| `api` | Semua endpoint `/api/*` (auth) | **60 request** | 1 menit | User ID atau IP |

**Response saat kena limit (429):**
```json
{
  "message": "Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit."
}
```

Headers yang dikirim:
```
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 0
Retry-After: 42
```

---

## 6. Error Response Format

Semua error response mengikuti format konsisten:

### Validation Error (422)
```json
{
  "message": "The email field is required. (and 1 more errors)",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

### Authentication Error (401)
```json
{
  "message": "Unauthenticated."
}
```

### Unverified Email (403)
```json
{
  "message": "Email belum diverifikasi.",
  "needs_verification": true
}
```

### Not Found (404)
```json
{
  "message": "Analisis tidak ditemukan."
}
```

### Rate Limited (429)
```json
{
  "message": "Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit."
}
```

### Forbidden (403)
```json
{
  "message": "Unauthorized"
}
```

### Server Error (500)
```json
{
  "message": "Terjadi kesalahan saat analisis. Silakan coba lagi."
}
```

### Service Unavailable (503)
```json
{
  "message": "Gagal terhubung ke AI Service setelah beberapa percobaan. Silakan coba lagi nanti."
}
```

---

## 7. HTTP Status Codes

| Code | Deskripsi |
|------|-----------|
| `200` | Success — Request berhasil |
| `400` | Bad Request — Token invalid / Link expired |
| `401` | Unauthenticated — Token tidak ada / tidak valid |
| `403` | Forbidden — Email belum diverifikasi |
| `404` | Not Found — Resource tidak ditemukan |
| `422` | Unprocessable Entity — Validasi gagal (Form Request) |
| `429` | Too Many Requests — Rate limit tercapai |
| `500` | Internal Server Error — Error server / AI Service error |
| `503` | Service Unavailable — AI Service tidak merespon |

---

## 8. Queue & Monitoring

### 8.1 Laravel Horizon

Horizon adalah dashboard queue monitoring untuk Redis. Digunakan untuk memantau job queue, failed jobs, dan performa worker.

**URL Dashboard:** `http://localhost:8000/{HORIZON_PATH}` (default: `/horizon`)

**Konfigurasi:**

| Setting | Value | Deskripsi |
|---------|-------|-----------|
| Connection | `redis` | Redis sebagai queue driver |
| Queue | `default`, `high`, `low` | Queue yang dipantau |
| Balance | `auto` | Auto-scaling worker |
| Max Processes | 10 (production) / 3 (local) | Maksimal worker per supervisor |
| Tries | 3 | Maksimal percobaan ulang job |
| Timeout | 300s | Timeout per job |
| Retry After | 90s | Delay sebelum retry |
| Memory Limit | 128MB | Memory limit per worker |

**Environment Variables:**
```
QUEUE_CONNECTION=redis
HORIZON_DOMAIN=
HORIZON_PATH=horizon
```

**Supervisor Config (Docker):**
```ini
[program:horizon]
command=php /var/www/html/artisan horizon
autostart=true
autorestart=true
user=www-data
stopwaitsecs=3600
```

**Commands:**
```bash
# Melihat status Horizon
php artisan horizon:status

# Terminate Horizon (graceful restart untuk deployment)
php artisan horizon:terminate

# Pause semua worker
php artisan horizon:pause

# Resume worker
php artisan horizon:continue

# Melihat failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

**Gate (Authorization):**
Akses ke dashboard Horizon dibatasi untuk user dengan email tertentu:
```php
// config di app/Providers/HorizonServiceProvider.php
Gate::define('viewHorizon', function ($user) {
    return in_array($user->email, [
        'admin@aurex.app',
    ]);
});
```

---

### 8.2 Laravel Pulse

Pulse adalah dashboard performance monitoring untuk Laravel. Merekam metrics secara real-time.

**URL Dashboard:** `http://localhost:8000/pulse`

**Recorders yang Aktif:**

| Recorder | Deskripsi | Sampling Rate |
|----------|-----------|---------------|
| Cache Interactions | Memantau operasi cache | 1/1 |
| Exceptions | Merekam semua exception | 1/1 |
| Queues | Memantau queue throughput | 1/1 |
| Servers | CPU, memory, load average | Setiap detik |
| Slow Jobs | Job yang lambat (>1s) | 1/1 |
| Slow Outgoing Requests | HTTP call lambat | 1/1 |
| Slow Queries | Query lambat (>1s) | 1/1 |
| Slow Requests | Request lambat (>1s) | 1/1 |
| User Jobs | Job per user | 1/1 |
| User Requests | Request per user | 1/1 |

**Environment Variables:**
```
PULSE_ENABLED=true
PULSE_DB_CONNECTION=mysql
PULSE_PATH=pulse
```

**Schedule (bootstrap/app.php):**
```php
$schedule->command('pulse:check')->everyMinute()->withoutOverlapping();
```

**Gate (Authorization):**
```php
// config di app/Providers/AppServiceProvider.php
Gate::define('viewPulse', function (User $user) {
    return in_array($user->email, [
        'admin@aurex.app',
    ]);
});
```

**Disable Pulse di Testing:**
```xml
<!-- phpunit.xml -->
<env name="PULSE_ENABLED" value="false"/>
```

---

### 8.3 Sentry Error Tracking

Sentry digunakan untuk error tracking di production.

**Laravel Backend:**
```env
SENTRY_LARAVEL_DSN=https://xxx@sentry.io/xxx
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_SEND_DEFAULT_PII=true
```

Integrasi dilakukan di `bootstrap/app.php` dengan `class_exists()` guard agar tidak crash jika package belum terinstall.

**Flutter Mobile:**
```dart
// main.dart — menggunakan --dart-define untuk DSN
final sentryDsn = String.fromEnvironment('SENTRY_DSN');
if (sentryDsn.isNotEmpty) {
  await SentryFlutter.init(
    (options) => options.dsn = sentryDsn,
    appRunner: () => runApp(ProviderScope(child: AurexApp())),
  );
} else {
  runApp(ProviderScope(child: AurexApp()));
}
```

Build dengan Sentry:
```bash
flutter build apk --release --dart-define=SENTRY_DSN=https://xxx@sentry.io/xxx
```

---

## 9. Docker Service Endpoints

Ketika berjalan di Docker (`docker-compose up -d`), service-service berikut tersedia:

| Service | Internal URL | External URL | Deskripsi |
|---------|-------------|-------------|-----------|
| Laravel API | `http://laravel:80/api` | `http://localhost:8000/api` | REST API |
| MySQL | `mysql:3306` | `localhost:3307` | Database |
| Redis | `redis:6379` | `localhost:6379` | Cache/Queue |
| AI Service | `http://ai-service:8001` | `http://localhost:8001` | Face Analysis |
| Horizon Dashboard | `http://laravel:80/horizon` | `http://localhost:8000/horizon` | Queue Monitoring |
| Pulse Dashboard | `http://laravel:80/pulse` | `http://localhost:8000/pulse` | Performance Monitoring |
| Health Check | `http://laravel:80/up` | `http://localhost:8000/up` | App Health |

**Internal Communication:**
```
Laravel (Port 80)
  ├── MySQL (Port 3306)      → Database
  ├── Redis (Port 6379)       → Cache / Session / Queue
  └── AI Service (Port 8001) → Face Analysis API (dengan API Key)
```

---

## 10. Health Check & System

### Application Health
**Endpoint:** `GET /up`

Response (200 — Healthy):
```json
{
  "status": "OK"
}
```

### System Commands
```bash
# Cek status Horizon
php artisan horizon:status

# Cek koneksi Redis (via artisan tinker)
php artisan tinker
Redis::connection()->ping()

# Cek versi Laravel
php artisan --version

# Cek status migrasi
php artisan migrate:status
```

---

## Changelog

| Tanggal | Perubahan |
|---------|-----------|
| 2026-07-16 | Dokumentasi awal — semua endpoint, rate limiting, queue, monitoring |
