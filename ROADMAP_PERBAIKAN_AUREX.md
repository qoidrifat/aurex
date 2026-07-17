# 🗺️ ROADMAP PERBAIKAN AUREX
## Improvement Roadmap — Week-by-Week Task Breakdown
### Target: Dari Prototype → Production Candidate (Skor 42 → 85)

---

## 📋 Legenda

| Simbol | Arti |
|--------|------|
| 🔴 | Critical Path — Wajib dikerjakan pertama |
| 🟠 | High Priority — Penting untuk stabilitas |
| 🟡 | Medium Priority — Meningkatkan kualitas |
| 🟢 | Low Priority — Nice to have |
| ⚪ | Enhancement — Polishing & optimalisasi |

**Estimasi Total: 12 Minggu (3 Bulan)**
**Estimated Effort: ~250-350 jam pengembangan**

---

# FASE 1: FOUNDATION FIX
## Minggu 1-2 — Memperbaiki Fundamental yang Rusak
### Tujuan: Aplikasi benar-benar berfungsi end-to-end

---

### 📅 Minggu 1 — Integrasi & Core Fix (30-40 jam)

#### Day 1: Setup & Dependency Fix (6-8 jam)

| Task | Prioritas | Detail | File yang Akan Dimodifikasi |
|------|-----------|--------|----------------------------|
| 1.1 | 🔴 | Install `laravel/sanctum` via Composer | `composer.json`, `config/sanctum.php` |
| 1.2 | 🔴 | Buat `.env.example` dari konfigurasi yang ada | `.env.example` (file baru) |
| 1.3 | 🔴 | Konfigurasi Sanctum di Laravel (models, routes, middleware) | `app/Models/User.php`, `bootstrap/app.php` |
| 1.4 | 🟠 | Setup koneksi database lokal | `.env`, `config/database.php` |
| 1.5 | 🟠 | Jalankan `composer install` & `php artisan migrate` | — |

**Definition of Done:** Backend bisa diakses via Postman (register → login → upload → analyze chain berfungsi)

#### Day 2-3: AI Service Fix & Backend Security (8-10 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 2.1 | 🔴 | **Hapus `np.random.randint`** — implementasi scoring logic nyata berdasarkan analisis face shape & undertone | `ai_service/python_ai/main.py` |
| 2.2 | 🔴 | Tambahkan API Key authentication di AI Service (sederhana: static token di header) | `ai_service/python_ai/main.py` |
| 2.3 | 🔴 | Hapus error message leak — jangan expose `$e->getMessage()` ke response | `backend/laravel_api/app/Http/Controllers/AnalysisController.php` |
| 2.4 | 🟠 | Tambahkan input validation di AI Service (tipe file, ukuran maksimal 10MB, dimensi) | `ai_service/python_ai/main.py` |
| 2.5 | 🟠 | Tambahkan logging di AI Service (request, processing time, error) | `ai_service/python_ai/main.py` |

**Definition of Done:** AI Service memberikan skor yang valid (tidak random), request tanpa API key ditolak.

#### Day 4-5: Flutter-Backend Integration (8-10 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 3.1 | 🔴 | Integrasikan `login_screen.dart` dengan `AuthService.login()` — bukan mock navigation | `mobile_app/flutter_app/lib/screens/login_screen.dart` |
| 3.2 | 🔴 | Integrasikan `register_screen.dart` dengan `AuthService.register()` | `mobile_app/flutter_app/lib/screens/register_screen.dart` |
| 3.3 | 🔴 | Integrasikan `upload_screen.dart` dengan `ApiService.uploadSelfie()` | `mobile_app/flutter_app/lib/screens/upload_screen.dart` |
| 3.4 | 🔴 | Integrasikan `analysis_screen.dart` dengan `ApiService.analyze()` — hapus `Future.delayed(5)` | `mobile_app/flutter_app/lib/screens/analysis_screen.dart` |
| 3.5 | 🔴 | Integrasikan `result_screen.dart` dengan data dari API (bukan hardcoded) | `mobile_app/flutter_app/lib/screens/result_screen.dart` |
| 3.6 | 🔴 | Integrasikan `profile_screen.dart` dengan data user dari API | `mobile_app/flutter_app/lib/screens/profile_screen.dart` |

**Definition of Done:** User bisa registrasi → login → upload foto → lihat analisis → lihat profil secara real end-to-end.

#### Day 6-7: Error Handling & Loading States (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 4.1 | 🔴 | Tambahkan loading state di tombol Login (CircularProgressIndicator) | `login_screen.dart` |
| 4.2 | 🔴 | Tambahkan loading state di tombol Register | `register_screen.dart` |
| 4.3 | 🔴 | Tambahkan loading state di tombol Upload & Take Photo | `upload_screen.dart` |
| 4.4 | 🔴 | Tambahkan error dialog/snackbar untuk semua API failure | Semua screen |
| 4.5 | 🔴 | Tambahkan form validation di Login & Register (email format, password match, required fields) | `login_screen.dart`, `register_screen.dart` |
| 4.6 | 🟠 | Implementasi try-catch yang proper di AuthService — jangan rethrow mentah-mentah | `auth_service.dart` |
| 4.7 | 🟠 | Tambahkan retry mechanism di ApiService untuk network timeout | `api_service.dart` |

**Definition of Done:** Semua operasi menunjukkan indikator loading, error ditampilkan dengan user-friendly message, form tervalidasi.

---

### 📅 Minggu 2 — State Management & Data Flow (20-25 jam)

#### Day 8-9: Riverpod State Management Refactor (8-10 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 5.1 | 🟠 | Buat `AuthNotifier` — Riverpod StateNotifier untuk auth state (loading, user, error) | `lib/providers/auth_provider.dart` (baru) |
| 5.2 | 🟠 | Buat `AnalysisNotifier` — StateNotifier untuk analysis state (loading, result, error) | `lib/providers/analysis_provider.dart` (baru) |
| 5.3 | 🟠 | Buat `HistoryNotifier` — StateNotifier untuk history analysis | `lib/providers/history_provider.dart` (baru) |
| 5.4 | 🟠 | Refactor screens untuk menggunakan Riverpod providers (bukan setState manual) | Semua screen |
| 5.5 | 🟡 | Tambahkan `AsyncValue` handling pattern (data/loading/error) di UI | Semua screen |

**Definition of Done:** State management menggunakan Riverpod secara konsisten. Setiap screen punya loading/error/data state.

#### Day 10-11: Secure Storage & Navigation (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 6.1 | 🟠 | Ganti `SharedPreferences` dengan `flutter_secure_storage` untuk token | `api_service.dart`, `auth_service.dart` |
| 6.2 | 🟠 | Install `go_router` — setup centralized routing | `pubspec.yaml`, `lib/router/app_router.dart` (baru) |
| 6.3 | 🟠 | Implementasi redirect logic: jika tidak ada token → login screen | `app_router.dart` |
| 6.4 | 🟡 | Tambahkan deep linking support untuk result/{id} | `app_router.dart` |

**Definition of Done:** Token aman di secure storage. Routing terpusat dengan GoRouter.

#### Day 12-14: Testing Setup & API Consistency (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 7.1 | 🟠 | Setup PHPUnit untuk backend — pastikan test suite jalan | `phpunit.xml`, test files |
| 7.2 | 🟠 | Setup pytest untuk AI Service — buat test dasar | `ai_service/python_ai/test_main.py` (baru) |
| 7.3 | 🟠 | Setup Flutter test untuk widgets | `test/` directory Flutter |
| 7.4 | 🟠 | Buat API Resource classes untuk response consistency | `app/Http/Resources/AnalysisResource.php`, `UserResource.php` |
| 7.5 | 🟡 | Tambahkan timestamp + pagination metadata di semua response | `AnalysisController.php` |

**Definition of Done:** Minimal 1 test berjalan di setiap service. API response format konsisten.

---

### ✅ Milestone Fase 1 Checklist

- [ ] Sanctum terinstall dan berfungsi
- [ ] .env.example tersedia
- [ ] AI Service tidak memberikan random score
- [ ] AI Service memiliki autentikasi
- [ ] Error message tidak bocor ke client
- [ ] Flutter terintegrasi dengan backend (end-to-end flow berfungsi)
- [ ] Loading states di semua operasi async
- [ ] Error handling di semua screen
- [ ] Form validation di Login & Register
- [ ] Token disimpan di flutter_secure_storage
- [ ] Routing menggunakan GoRouter

**Target Skor Setelah Fase 1: 42 → 55 📈**

---

# FASE 2: SECURITY & STABILITY
## Minggu 3-4 — Hardening Sistem
### Tujuan: Aplikasi aman dari serangan umum dan stabil di production

---

### 📅 Minggu 3 — Security Hardening (25-30 jam)

#### Day 15-16: Authentication & Authorization (8-10 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 8.1 | 🟠 | Implementasi email verification flow (Laravel MustVerifyEmail) | `User.php`, routes, controller |
| 8.2 | 🟠 | Tambahkan `throttle:5,1` middleware di login endpoint | `routes/api.php` |
| 8.3 | 🟠 | Implementasi password reset flow (Forgot Password) | `AuthController.php`, routes |
| 8.4 | 🟠 | Buat middleware untuk authorization check (ownership verification) | `app/Http/Middleware/EnsureAnalysisOwner.php` (baru) |
| 8.5 | 🟡 | Implementasi refresh token mechanism | Sanctum config |

**Definition of Done:** Rate limiting aktif, email verification jalan, password reset berfungsi.

#### Day 17-18: CORS, CSRF & Input Sanitization (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 9.1 | 🟠 | Konfigurasi CORS dengan daftar origin yang diizinkan | `config/cors.php` (baru) |
| 9.2 | 🟠 | Tambahkan XSS sanitization di semua input | Middleware atau Helper |
| 9.3 | 🟠 | Validasi password strength (huruf besar, angka, simbol) | `AuthController.php` |
| 9.4 | 🟡 | Implementasi request logging middleware (IP, user agent, timestamp) | `app/Http/Middleware/LogRequests.php` (baru) |

**Definition of Done:** CORS terbatasi, input disanitasi, request tercatat.

#### Day 19-21: AI Service Security & File Handling (8-10 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 10.1 | 🟠 | Implementasi API key rotation mechanism di AI Service | `main.py` |
| 10.2 | 🟠 | Tambahkan file type validation strict (hanya jpeg/png, maks 10MB) | `main.py` |
| 10.3 | 🟠 | Implementasi file cleanup setelah processing (hapus temporary files) | `main.py` |
| 10.4 | 🟠 | Tambahkan timeout handling di backend saat call AI service (maks 30 detik) | `AnalysisController.php` |
| 10.5 | 🟡 | Implementasi retry logic (3x attempt dengan exponential backoff) | `AnalysisController.php` atau Service class |

**Definition of Done:** AI Service aman dari file berbahaya, timeout tertangani, file dibersihkan.

---

### 📅 Minggu 4 — Service Layer & Architecture (20-25 jam)

#### Day 22-23: Service Layer Implementation (8-10 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 11.1 | 🟠 | Buat `AnalysisService` — pindahkan business logic dari controller | `app/Services/AnalysisService.php` (baru) |
| 11.2 | 🟠 | Buat `AuthService` — pindahkan logic auth dari controller | `app/Services/AuthService.php` (baru) |
| 11.3 | 🟠 | Buat `AIService` — abstraksi untuk komunikasi dengan AI service | `app/Services/AIService.php` (baru) |
| 11.4 | 🟠 | Buat `ImageService` — handle upload, optimasi, dan storage | `app/Services/ImageService.php` (baru) |
| 11.5 | 🟠 | Refactor controller untuk memanggil services (bukan logic langsung) | Semua controller |

**Definition of Done:** Controller menjadi thin — hanya handle request/response. Logic ada di services.

#### Day 24-25: Image Optimization & Queue Setup (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 12.1 | 🟠 | Implementasi image compression sebelum upload (resize ke 1024px max) | `ImageService.php` |
| 12.2 | 🟠 | Setup Laravel Queue (database driver untuk development) | `.env`, `config/queue.php` |
| 12.3 | 🟠 | Buat `AnalyzeImageJob` — pindahkan AI processing ke queue | `app/Jobs/AnalyzeImageJob.php` (baru) |
| 12.4 | 🟡 | Implementasi webhook atau polling mechanism untuk async result | `AnalysisController.php`, Flutter |

**Definition of Done:** Upload gambar dikompresi. AI processing berjalan async via queue.

#### Day 26-28: Database & Schema Enhancement (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 13.1 | 🟠 | Buat migration baru: tambahkan `analysis_id` ke tabel `images` | Migration baru |
| 13.2 | 🟠 | Tambahkan index untuk foreign key columns | Migration baru |
| 13.3 | 🟡 | Ubah `style_score` dari integer ke decimal(5,2) | Migration alter |
| 13.4 | 🟡 | Tambahkan `softDeletes()` ke semua tabel | Migration alter |
| 13.5 | 🟡 | Buat database seeder untuk development data | `database/seeders/` |

**Definition of Done:** Schema database optimal, images terhubung ke analyses, index terpasang.

---

### ✅ Milestone Fase 2 Checklist

- [ ] Email verification berfungsi
- [ ] Rate limiting aktif di login
- [ ] Password reset flow berfungsi
- [ ] CORS terkonfigurasi
- [ ] Input disanitasi (XSS protection)
- [ ] AI Service memiliki autentikasi
- [ ] File type validation ketat
- [ ] Service layer terbentuk (Analysis, Auth, AI, Image)
- [ ] Queue system berfungsi untuk AI processing
- [ ] Image compression aktif
- [ ] Database schema optimal dengan index & soft deletes

**Target Skor Setelah Fase 2: 55 → 68 📈**

---

# FASE 3: PERFORMANCE & SCALABILITY
## Minggu 5-7 — Optimalisasi & Infrastructure
### Tujuan: Aplikasi cepat, efisien, dan bisa diskalakan

---

### 📅 Minggu 5 — Docker & Caching (25-30 jam)

#### Day 29-30: Docker Setup (10-12 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 14.1 | 🟠 | Buat `Dockerfile` untuk Laravel backend (PHP 8.2 + FPM + Nginx) | `backend/laravel_api/Dockerfile` (baru) |
| 14.2 | 🟠 | Buat `Dockerfile` untuk Python AI Service | `ai_service/python_ai/Dockerfile` (baru) |
| 14.3 | 🟠 | Buat `docker-compose.yml` untuk orchestrasi semua service | `docker-compose.yml` (baru) |
| 14.4 | 🟠 | Konfigurasi MySQL/MariaDB di docker-compose | `docker-compose.yml` |
| 14.5 | 🟠 | Konfigurasi volume untuk persistent data & file uploads | `docker-compose.yml` |
| 14.6 | 🟡 | Setup Nginx reverse proxy untuk routing ke Laravel + Flutter web | `docker/nginx/default.conf` (baru) |

**Definition of Done:** `docker-compose up` menjalankan semua service.

#### Day 31-33: Caching Implementation (8-10 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 15.1 | 🟡 | Setup Redis di docker-compose | `docker-compose.yml` |
| 15.2 | 🟡 | Implementasi query caching untuk hasil analisis (cache selama 1 jam) | `AnalysisService.php` |
| 15.3 | 🟡 | Implementasi cache untuk user profile data | `AuthService.php` |
| 15.4 | 🟡 | Tambahkan cache headers untuk API response (ETag, Last-Modified) | Middleware |
| 15.5 | 🟡 | Implementasi image caching di Flutter (cached_network_image) | `pubspec.yaml`, result screen |

**Definition of Done:** Analisis yang sama tidak perlu diproses ulang. API response memiliki cache headers.

#### Day 34-35: Performance Optimization (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 16.1 | 🟠 | Implementasi pagination di endpoint `/history` (default 10 per page) | `AnalysisController.php` |
| 16.2 | 🟡 | Optimasi Flutter bundle — lazy load screens dengan `deferred as` | `main.dart` |
| 16.3 | 🟡 | Implementasi infinite scroll di History screen | Flutter |
| 16.4 | 🟡 | Image lazy loading + placeholder di result screen | Flutter |
| 16.5 | 🟡 | Analisis bundle size dengan `flutter build apk --analyze-size` | — |

**Definition of Done:** History response ter-paginate. Flutter app size teroptimasi.

---

### 📅 Minggu 6 — AI Enhancement & Form Request (20-25 jam)

#### Day 36-38: AI Algorithm Improvement (10-12 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 17.1 | 🟡 | Implementasi scoring algorithm yang lebih akurat (bobot: face symmetry 30%, proportion 30%, undertone match 20%, feature harmony 20%) | `main.py` |
| 17.2 | 🟡 | Tambahkan confidence score untuk setiap analisis (0-100%) | `main.py` |
| 17.3 | 🟡 | Implementasi skin undertone detection yang robust (HSV color space analysis + multiple sample regions) | `main.py` |
| 17.4 | 🟡 | Tambahkan face mesh error handling yang spesifik (no face, multiple faces, low light) | `main.py` |
| 17.5 | 🟡 | Implementasi model versioning (return model version in response) | `main.py` |
| 17.6 | 🟢 | Setup model caching agar tidak reload setiap request | `main.py` |

**Definition of Done:** AI memberikan skor yang valid dengan confidence score. Deteksi undertone lebih akurat.

#### Day 39-40: Form Request & Validation (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 18.1 | 🟡 | Buat `RegisterRequest` — Form Request untuk validasi registrasi | `app/Http/Requests/RegisterRequest.php` (baru) |
| 18.2 | 🟡 | Buat `LoginRequest` — Form Request untuk login | `app/Http/Requests/LoginRequest.php` (baru) |
| 18.3 | 🟡 | Buat `UploadSelfieRequest` — validasi upload gambar | `app/Http/Requests/UploadSelfieRequest.php` (baru) |
| 18.4 | 🟡 | Buat `AnalyzeRequest` — validasi analysis request | `app/Http/Requests/AnalyzeRequest.php` (baru) |
| 18.5 | 🟡 | Refactor controller untuk menggunakan Form Requests | Semua controller |

**Definition of Done:** Semua validasi reusable melalui Form Request classes.

#### Day 41-42: API Versioning & Docs (4-6 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 19.1 | 🟡 | Implementasi API versioning (`/api/v1/...`) | `routes/api.php` |
| 19.2 | 🟡 | Update Flutter ApiService untuk menggunakan versioned endpoints | `api_service.dart` |
| 19.3 | 🟡 | Setup API documentation generator (Scribe atau Swagger) | `composer.json` |
| 19.4 | 🟡 | Update API_DOCS.md dengan endpoint terbaru | `docs/API_DOCS.md` |

**Definition of Done:** API memiliki versioning. Dokumentasi ter-update.

---

### 📅 Minggu 7 — Testing & Flutter Enhancement (20-25 jam)

#### Day 43-45: Comprehensive Testing (10-12 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 20.1 | 🟠 | Buat unit test untuk `AnalysisService` (PHPUnit + Mockery) | `tests/Unit/Services/AnalysisServiceTest.php` |
| 20.2 | 🟠 | Buat unit test untuk `AuthService` | `tests/Unit/Services/AuthServiceTest.php` |
| 20.3 | 🟠 | Buat feature test untuk semua endpoint (register, login, upload, analyze, history) | `tests/Feature/` |
| 20.4 | 🟠 | Buat unit test untuk AI Service dengan pytest | `ai_service/python_ai/test_main.py` |
| 20.5 | 🟡 | Buat widget test untuk Flutter (login screen, result screen, profile) | Flutter `test/` |
| 20.6 | 🟡 | Setup CI pipeline untuk menjalankan semua test otomatis | `.github/workflows/main.yml` |

**Definition of Done:** Coverage minimal: Backend 60%, AI Service 50%, Flutter 30%.

#### Day 46-47: Flutter UI Enhancement (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 21.1 | 🟡 | Buat reusable widgets: `LoadingButton`, `ErrorBanner`, `EmptyState`, `InfoCard` | `lib/widgets/` |
| 21.2 | 🟡 | Implementasi pull-to-refresh di History screen | History screen |
| 21.3 | 🟡 | Tambahkan shimmer loading effect untuk skeleton screen | Result screen |
| 21.4 | 🟡 | Implementasi scroll-to-top FAB | Profile screen |

**Definition of Done:** UI library reusable terbentuk. Micro-interactions halus.

#### Day 48-49: Code Quality & Refactoring (4-6 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 22.1 | 🟡 | Hapus semua dead code (unused imports, unused dependencies) | Semua file |
| 22.2 | 🟡 | Hapus dependency yang tidak terpakai: `camera`, `google_sign_in`, `firebase_*` (jika tidak digunakan) | `pubspec.yaml` |
| 22.3 | 🟡 | Tambahkan PHPDoc + Dart doc di semua method publik | Semua file |
| 22.4 | 🟢 | Setup Dart analyzer dengan strict rules | `analysis_options.yaml` |
| 22.5 | 🟢 | Setup Laravel Pint untuk code formatting | `composer.json` |

**Definition of Done:** Tidak ada dead code. Semua method publik memiliki dokumentasi.

---

### ✅ Milestone Fase 3 Checklist

- [ ] Docker & Docker Compose siap
- [ ] Caching (Redis) terimplementasi
- [ ] History endpoint ter-paginate
- [ ] AI algorithm memberikan skor valid
- [ ] AI memiliki confidence score
- [ ] Form Request classes terbentuk
- [ ] API versioning aktif
- [ ] Testing coverage Backend ≥ 60%
- [ ] Testing AI Service ≥ 50%
- [ ] Flutter UI enhancement selesai
- [ ] Dead code dibersihkan

**Target Skor Setelah Fase 3: 68 → 78 📈**

---

# FASE 4: UX & AKSESIBILITAS
## Minggu 8-9 — Pengalaman Pengguna & Inklusivitas
### Tujuan: Aplikasi ramah untuk semua pengguna

---

### 📅 Minggu 8 — Aksesibilitas & WCAG Compliance (25-30 jam)

#### Day 50-52: Semantic Widgets & Screen Reader (10-12 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 23.1 | 🟠 | Tambahkan `Semantics()` widget di semua interactive elements | Semua screen |
| 23.2 | 🟠 | Tambahkan `label`, `hint`, dan `value` di Semantics untuk setiap widget | Semua screen |
| 23.3 | 🟠 | Tambahkan `ExcludeSemantics` untuk decorative elements | Semua screen |
| 23.4 | 🟠 | Implementasi `MergeSemantics` untuk grouped elements (kartu, list item) | StyleCard, dll |
| 23.5 | 🟡 | Test dengan TalkBack (Android) dan VoiceOver (iOS) | — |

**Definition of Done:** Screen reader bisa membaca semua konten dengan konteks yang benar.

#### Day 53-54: Keyboard Navigation & Focus (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 24.1 | 🟠 | Implementasi `FocusTraversalGroup` untuk logical tab order | Semua form |
| 24.2 | 🟠 | Tambahkan `FocusNode` dan `onSubmitted` untuk form fields | Login, Register |
| 24.3 | 🟠 | Implementasi custom focus indicator (warna kontras, tebal 2px offset 2px) | Theme |
| 24.4 | 🟡 | Test keyboard navigation end-to-end | — |

**Definition of Done:** Keyboard navigation logis. Focus indicator terlihat jelas.

#### Day 55-56: Color Contrast & Dynamic Font (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 25.1 | 🟠 | Audit dan fix color contrast — pastikan minimal 4.5:1 untuk text normal, 3:1 untuk large text | `colors.dart` |
| 25.2 | 🟠 | Implementasi `MediaQuery.textScaleFactor` untuk dynamic font sizing | Theme, typography |
| 25.3 | 🟠 | Tambahkan `prefers-reduced-motion` detection dan disable animasi | `main.dart` |
| 25.4 | 🟡 | Implementasi dark mode toggle yang proper (ikuti system setting) | Theme |

**Definition of Done:** WCAG 2.2 AA compliant untuk contrast. Font bisa di-scale. Motion bisa dimatikan.

---

### 📅 Minggu 9 — UX Enhancement (20-25 jam)

#### Day 57-59: Onboarding & Empty States (8-10 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 26.1 | 🟡 | Buat onboarding screen (3-4 slide: tentang AUREX, fitur, cara pakai) | `lib/screens/onboarding_screen.dart` (baru) |
| 26.2 | 🟡 | Implementasi "show onboarding only once" logic (SharedPreferences) | `main.dart`, onboarding |
| 26.3 | 🟡 | Buat EmptyState widget untuk history kosong | `lib/widgets/empty_state.dart` (baru) |
| 26.4 | 🟡 | Buat Error state widget untuk network failure (dengan tombol retry) | `lib/widgets/error_state.dart` (baru) |
| 26.5 | 🟡 | Implementasi confetti animation saat result pertama muncul | Result screen |

**Definition of Done:** Pengguna baru melihat onboarding. Empty/error states informatif.

#### Day 60-61: Micro-interactions & Animation (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 27.1 | 🟡 | Tambahkan staggered animation di result screen (card muncul satu per satu) | Result screen |
| 27.2 | 🟡 | Implementasi Lottie animation di analysis screen (loading face scan) | Analysis screen |
| 27.3 | 🟡 | Tambahkan subtle hover/tap effect di buttons & cards | PrimaryButton, StyleCard |
| 27.4 | 🟢 | Implementasi page transition animation (slide + fade) | GoRouter config |

**Definition of Done:** Animasi halus, tidak mengganggu, dan bisa dimatikan via reduced motion.

#### Day 62-63: Loading & Skeleton Screens (4-6 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 28.1 | 🟡 | Implementasi skeleton loading screen untuk result page | Result screen |
| 28.2 | 🟡 | Tambahkan shimmer effect di card saat loading | StyleCard |
| 28.3 | 🟡 | Implementasi progressive image loading (low-res placeholder → high-res) | Upload, Result |

**Definition of Done:** Loading state halus dengan skeleton/shimmer, bukan spinner doang.

---

### ✅ Milestone Fase 4 Checklist

- [ ] Screen reader support (Semantics labels)
- [ ] Keyboard navigation berfungsi
- [ ] Focus indicator proper
- [ ] Color contrast WCAG 2.2 AA compliant
- [ ] Dynamic font sizing
- [ ] Reduced motion support
- [ ] Onboarding screen berfungsi
- [ ] Empty states untuk semua list
- [ ] Error state dengan retry button
- [ ] Animasi halus dan bisa dimatikan
- [ ] Skeleton loading screen

**Target Skor Setelah Fase 4: 78 → 85 📈**

---

# FASE 5: PRODUCTION READINESS
## Minggu 10-12 — Final Polishing & Deployment
### Tujuan: Aplikasi siap untuk public release

---

### 📅 Minggu 10 — CI/CD & DevOps Final (25-30 jam)

#### Day 64-66: CI/CD Pipeline Enhancement (10-12 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 29.1 | 🟡 | Update GitHub Actions: tambahkan Flutter test runner | `.github/workflows/main.yml` |
| 29.2 | 🟡 | Update GitHub Actions: tambahkan AI Service test (pytest) | `.github/workflows/main.yml` |
| 29.3 | 🟡 | Tambahkan job untuk build APK release (flutter build apk --release) | `.github/workflows/main.yml` |
| 29.4 | 🟡 | Tambahkan job untuk build App Bundle (aab) | `.github/workflows/main.yml` |
| 29.5 | 🟡 | Setup Docker image build & push ke container registry | `.github/workflows/deploy.yml` (baru) |
| 29.6 | 🟡 | Setup deployment ke staging server otomatis | `.github/workflows/deploy.yml` |

**Definition of Done:** Push ke main → auto test → auto build → auto deploy ke staging.

#### Day 67-68: Monitoring & Observability (8-10 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 30.1 | 🟡 | Setup Laravel log channel untuk production (daily + slack/webhook) | `config/logging.php` |
| 30.2 | 🟡 | Setup error tracking (Sentry.io atau Flare) | `composer.json`, config |
| 30.3 | 🟡 | Tambahkan health check endpoint (`/api/v1/health`) | Route + Controller |
| 30.4 | 🟡 | Setup monitoring dashboard (Laravel Pulse atau similar) | `composer.json`, config |
| 30.5 | 🟢 | Implementasi structured logging (JSON format untuk log aggregation) | `config/logging.php` |

**Definition of Done:** Error ter-track. Health check berfungsi. Monitoring aktif.

#### Day 69-70: Production Configuration (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 31.1 | 🟡 | Buat `.env.production.example` dengan production-safe defaults | `.env.production.example` (baru) |
| 31.2 | 🟡 | Optimasi Laravel untuk production: cache config, route, view | Setup script |
| 31.3 | 🟡 | Setup OPcache untuk PHP | Docker |
| 31.4 | 🟡 | Setup SSL/TLS (Let's Encrypt via certbot atau Traefik) | Docker / Nginx |
| 31.5 | 🟡 | Konfigurasi queue worker untuk production | Docker / Supervisor |

**Definition of Done:** Production environment aman, cepat, dan SSL-enabled.

---

### 📅 Minggu 11 — Final Feature Polish (20-25 jam)

#### Day 71-73: Flutter Production Polish (10-12 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 32.1 | 🟡 | Setup Firebase Crashlytics untuk error reporting | Flutter |
| 32.2 | 🟡 | Setup Firebase Analytics untuk user behavior tracking | Flutter |
| 32.3 | 🟡 | Implementasi push notification untuk analysis complete | Flutter + Laravel |
| 32.4 | 🟡 | Splash screen yang proper (native splash dengan flutter_native_splash) | Flutter |
| 32.5 | 🟢 | App icon design dan adaptive icon | Android/iOS config |
| 32.6 | 🟢 | Play Store listing preparation (screenshots, description, privacy policy) | — |

**Definition of Done:** Aplikasi siap untuk store submission.

#### Day 74-75: Documentation Complete (6-8 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 33.1 | 🟡 | Update README dengan status project + badges | `README.md` |
| 33.2 | 🟡 | Lengkapi `AI_ARCHITECTURE.md` (kosong) dengan detail arsitektur AI | `docs/AI_ARCHITECTURE.md` |
| 33.3 | 🟡 | Lengkapi `TESTING_GUIDE.md` (kosong) | `docs/TESTING_GUIDE.md` |
| 33.4 | 🟡 | Buat CONTRIBUTING.md untuk open source contributors | `CONTRIBUTING.md` (baru) |
| 33.5 | 🟢 | Buat CHANGELOG.md | `CHANGELOG.md` (baru) |

**Definition of Done:** Semua dokumentasi lengkap dan akurat.

#### Day 76-77: Security Audit Final (4-6 jam)

| Task | Prioritas | Detail | File |
|------|-----------|--------|------|
| 34.1 | 🟡 | Dependency vulnerability scan (composer audit, npm audit, flutter pub outdated) | — |
| 34.2 | 🟡 | Manual security testing: SQL injection, XSS, CSRF | — |
| 34.3 | 🟡 | Penetration test untuk API endpoints | — |
| 34.4 | 🟢 | Implementasi security headers (HSTS, X-Frame-Options, X-Content-Type-Options) | Nginx / Middleware |

**Definition of Done:** Tidak ada critical/high severity vulnerabilities.

---

### 📅 Minggu 12 — Deployment & Launch (20-25 jam)

#### Day 78-80: Staging Deployment & Testing (10-12 jam)

| Task | Prioritas | Detail |
|------|-----------|--------|
| 35.1 | 🟡 | Deploy ke staging server (VPS / cloud VM) |
| 35.2 | 🟡 | End-to-end testing di staging (all user flows) |
| 35.3 | 🟡 | Load testing dengan k6 atau Apache Bench (target: 100 concurrent users) |
| 35.4 | 🟡 | Performance testing — measure Core Web Vitals |
| 35.5 | 🟡 | Fix bugs yang ditemukan selama staging test |

**Definition of Done:** Staging environment stabil, semua test pass, load test OK.

#### Day 81-82: Production Deployment (6-8 jam)

| Task | Prioritas | Detail |
|------|-----------|--------|
| 36.1 | 🟡 | Setup production server (minimum: 2GB RAM, 2 CPU, 20GB SSD) |
| 36.2 | 🟡 | Deploy backend + database + queue worker |
| 36.3 | 🟡 | Deploy AI Service + model |
| 36.4 | 🟡 | Deploy Flutter web (opsional) |
| 36.5 | 🟡 | Setup monitoring & alerting |
| 36.6 | 🟡 | SSL certificate installation & verification |

**Definition of Done:** Production environment live dan dapat diakses.

#### Day 83-84: Launch Preparation (4-6 jam)

| Task | Prioritas | Detail |
|------|-----------|--------|
| 37.1 | 🟡 | Production smoke test (semua flow dari early access user) |
| 37.2 | 🟡 | Monitoring check (error rate, response time, memory usage) |
| 37.3 | 🟡 | Rollback plan documentation |
| 37.4 | 🟡 | GO / NO-GO decision meeting |

**Definition of Done:** **LAUNCH 🚀**

---

### ✅ Milestone Fase 5 Checklist

- [ ] CI/CD pipeline lengkap dengan test & build
- [ ] Monitoring & observability aktif
- [ ] Production configuration optimal
- [ ] Firebase Analytics & Crashlytics terintegrasi
- [ ] Dokumentasi lengkap
- [ ] Security vulnerabilities resolved
- [ ] Load testing pass (100 concurrent users)
- [ ] Staging environment stabil
- [ ] Production server siap
- [ ] SSL/TLS active

**Target Skor Setelah Fase 5: 85 → 90+ 📈**

---

# 📊 TOTAL EFFORT ESTIMATION

| Fase | Minggu | Fokus Utama | Estimated Hours | Skor Target |
|------|--------|-------------|-----------------|-------------|
| 1 | 1-2 | Foundation Fix | 50-65 jam | 42 → 55 |
| 2 | 3-4 | Security & Stability | 45-55 jam | 55 → 68 |
| 3 | 5-7 | Performance & Scalability | 65-80 jam | 68 → 78 |
| 4 | 8-9 | UX & Accessibility | 45-55 jam | 78 → 85 |
| 5 | 10-12 | Production Readiness | 65-75 jam | 85 → 90+ |
| **Total** | **12 Minggu** | **End-to-end** | **270-330 jam** | **42 → 90+** |

---

# ⚡ PRIORITAS HARIAN REKOMENDASI

## Untuk Developer Solo (Prioritaskan):

1. ✅ **Kerjakan per minggu secara berurutan** — jangan loncat
2. ✅ **Setiap pagi, review task hari itu** — checklist Definition of Done
3. ✅ **Akhir minggu, jalankan semua test** — pastikan tidak ada regression
4. ✅ **Commit setiap hari** — jangan menumpuk perubahan
5. ❌ **Jangan tambah fitur baru** selama masih ada technical debt dari fase sebelumnya
6. ❌ **Jangan skip testing** — ini yang membedakan prototype dari production app

## Time Management Tips:

- **Weekday (Senin-Jumat):** 3-4 jam/hari setelah kerja/kuliah = 15-20 jam/minggu
- **Weekend (Sabtu-Minggu):** 5-6 jam/hari = 10-12 jam/minggu
- **Total per minggu:** 25-30 jam (realistis untuk side project)
- **Estimasi selesai:** 10-12 minggu (3 bulan)

---

# 🔄 RISK & MITIGATION

| Risk | Probability | Impact | Mitigation |
|------|------------|--------|------------|
| Kehilangan motivasi di tengah jalan | 🟡 Medium | 🔴 High | Bagi roadmap menjadi milestone kecil, rayakan setiap selesai fase |
| Dependency break (Flutter/PHP update) | 🟡 Medium | 🟠 High | Lock dependency version, test secara rutin |
| Firebase setup bermasalah | 🟡 Medium | 🟡 Medium | Siapkan fallback authentication (local auth + JWT) |
| AI model accuracy rendah | 🟡 Medium | 🟠 High | Kumpulkan dataset validasi, iterasi algoritma |
| Performance bottleneck | 🟢 Low | 🟠 High | Load testing di minggu 10, siapkan opsi upgrade server |

---

# 📋 FINAL CHECKLIST PRODUCTION READINESS

Sebelum public launch, pastikan semua ini ✅:

### Keamanan
- [ ] Semua endpoint HTTPS
- [ ] Autentikasi di semua protected route
- [ ] AI Service memiliki autentikasi
- [ ] Rate limiting aktif
- [ ] CORS terbatas
- [ ] Error tidak leak informasi sensitif
- [ ] Password policy kuat
- [ ] Email verification aktif

### Performa
- [ ] Average response time < 500ms
- [ ] P95 response time < 2 detik
- [ ] AI processing < 10 detik
- [ ] Core Web Vitals pass
- [ ] Pagination di semua list endpoint

### Testing
- [ ] Backend test coverage ≥ 60%
- [ ] AI Service test coverage ≥ 50%
- [ ] Flutter widget test untuk flow kritis
- [ ] Load test dengan 100 concurrent users pass

### DevOps
- [ ] CI/CD pipeline berfungsi
- [ ] Monitoring & alerting aktif
- [ ] Backup strategy terdokumentasi
- [ ] Rollback plan siap
- [ ] Health check endpoint aktif

### Legal
- [ ] Privacy Policy
- [ ] Terms of Service
- [ ] Data retention policy
- [ ] GDPR compliance (jika target Eropa)

---

## 🎯 FINAL WORDS

Roadmap ini dirancang untuk membawa AUREX dari **prototype visual** menjadi **production-grade application** dalam 12 minggu.

Kunci suksesnya adalah:
1. **Konsistensi** — 25-30 jam per minggu selama 12 minggu
2. **Jangan skip testing** — testing adalah investasi, bukan beban
3. **Prioritaskan integrasi** — pastikan Flutter ↔ Backend ↔ AI Service berkomunikasi dengan benar sebelum menambahkan fitur baru
4. **Keamanan sejak awal** — jangan tunda security fixes

**Selamat mengerjakan! 🚀**

---

*Roadmap ini disusun berdasarkan hasil audit menyeluruh HASIL_AUDIT_AUREX.md*
*Estimasi waktu bersifat indikatif dan dapat disesuaikan dengan kecepatan development aktual*

---
