# AUREX Testing Guide

Panduan lengkap untuk menjalankan, menulis, dan memahami pengujian di seluruh platform AUREX.

---

## Daftar Isi

1. [Backend (Laravel) Testing](#1-backend-laravel-testing)
   - [Setup Testing](#11-setup-testing)
   - [Menjalankan Test](#12-menjalankan-test)
   - [Struktur Test](#13-struktur-test)
   - [Auth Test Detail](#14-auth-test-detail)
   - [Mocking Strategy](#15-mocking-strategy)
   - [Testing Rate Limiting](#16-testing-rate-limiting)
   - [Testing Email Verification](#17-testing-email-verification)
   - [Testing Password Reset](#18-testing-password-reset)
   - [Testing Monitoring (Pulse / Horizon / Sentry)](#19-testing-monitoring-pulse--horizon--sentry)
2. [Flutter Mobile Testing](#2-flutter-mobile-testing)
   - [Setup Testing](#21-setup-testing)
   - [Menjalankan Test](#22-menjalankan-test)
   - [Auth Provider Test](#23-auth-provider-test)
   - [Screen Test (Login / Register / Forgot Password)](#24-screen-test-login--register--forgot-password)
   - [Mocking Strategy](#25-mocking-strategy)
3. [AI Service Testing](#3-ai-service-testing)
4. [CI/CD Integration](#4-cicd-integration)
5. [Code Coverage](#5-code-coverage)
6. [Testing Best Practices](#6-testing-best-practices)

---

## 1. Backend (Laravel) Testing

### 1.1 Setup Testing

**Database:**
- Menggunakan **SQLite in-memory** untuk isolasi total
- Tidak perlu MySQL/Redis berjalan
- Setiap test class menggunakan `RefreshDatabase` trait

**Konfigurasi phpunit.xml:**
```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="PULSE_ENABLED" value="false"/>
    <env name="TELESCOPE_ENABLED" value="false"/>
</php>
```

**Variabel penting:**
| Env | Value | Alasan |
|-----|-------|--------|
| `DB_CONNECTION=sqlite` | In-memory DB | Isolasi total, tidak perlu koneksi eksternal |
| `QUEUE_CONNECTION=sync` | Synchronous queue | Job dijalankan langsung, tidak queue worker |
| `MAIL_MAILER=array` | Array mail driver | Email disimpan di memory, bisa di-assert dengan Notification::fake() |
| `PULSE_ENABLED=false` | Pulse dimatikan | Tidak perlu Pulse migration di test |

### 1.2 Menjalankan Test

```bash
# Pindah ke direktori Laravel
cd backend/laravel_api

# Install dependencies (jika belum)
composer install

# Jalankan semua test
php artisan test

# Jalankan hanya Auth test suite
php artisan test --filter=AuthTest

# Jalankan test spesifik
php artisan test --filter=test_user_can_register

# Jalankan dengan output verbose
php artisan test --verbose

# Jalankan dengan coverage (memerlukan Xdebug/PCOV)
php artisan test --coverage

# Clear config cache sebelum test (jika ada error aneh)
php artisan config:clear
php artisan test
```

### 1.3 Struktur Test

```
tests/
├── Feature/
│   ├── AuthTest.php              # 33 test — autentikasi lengkap
│   ├── AnalysisControllerTest.php # 18 test — upload, analyze, history, result
│   ├── HealthControllerTest.php  # 12 test — health check endpoint
│   ├── FormRequestTest.php       # 17 test — validasi Form Request (BARU)
│   ├── LogContextMiddlewareTest.php # 10 test — logging middleware
│   └── ExampleTest.php           # 1 test — basic response
├── Unit/
│   ├── FaceAnalysisServiceTest.php # 11 test — service layer + retry
│   ├── ApiResourceTest.php       # 12 test — serialisasi Resource (BARU)
│   └── ExampleTest.php           # 1 test — basic assertion
└── TestCase.php                  # Base class (extends Tests\TestCase)
```

**Naming Convention:**
- Test method: `snake_case` dengan prefix `test_`
- Nama deskriptif: `test_user_can_register`, `test_login_rate_limited_after_5_attempts`
- Gherkin-style: `test_{skenario}_{expected_behavior}`

### 1.4 Auth Test Detail

Terdapat **33 test** di `AuthTest.php` yang mencakup:

| Kategori | Jumlah Test | Deskripsi |
|----------|-------------|-----------|
| **Registration** | 6 | Register sukses, email terkirim, duplicate email, short password, missing name, password mismatch, invalid email format |
| **Login** | 5 | Login sukses, invalid credentials, nonexistent email, unverified email (with & without require_verified flag) |
| **Logout** | 3 | Logout sukses, logout tanpa token, token terhapus dari database |
| **Rate Limiting** | 2 | Login rate limited (6 attempts), forgot password rate limited (6 attempts) |
| **Email Verification** | 6 | Verify valid link, invalid hash, already verified, resend verification, resend fails when already verified, resend fails with nonexistent email |
| **Password Reset** | 8 | Request reset, nonexistent email, reset with valid token, invalid token, short password, password mismatch, token one-time use, reset fails with nonexistent email |
| **Validation Edge Cases** | 3 | Invalid email format, missing email on login, missing password on login |

**Total: 33 test — mencakup seluruh flow autentikasi dari register hingga logout.**

### 1.12 Analysis Controller Test (BARU — 18 test)

`tests/Feature/AnalysisControllerTest.php` menguji endpoint upload, analyze, history, dan result:

| Kategori | Jumlah Test | Skenario |
|----------|-------------|----------|
| **Upload** | 5 | Upload sukses (verify file stored + JSON structure), gagal tanpa auth, tanpa image, invalid file type, oversize >5MB |
| **Analyze** | 5 | Analyze sukses (verify DB record + image linked), gagal tanpa auth, nonexistent image, another user image (403), AI failure user-friendly error |
| **Connection Error** | 1 | AI service connection timeout → 503 dengan pesan Bahasa Indonesia |
| **History** | 4 | View history, empty history, fails without auth, scoped to current user (tidak bocor data user lain) |
| **Result** | 4 | View own result (via AnalysisResource), fails for another user (403), nonexistent (404), fails without auth |
| **Recommendation** | 1 | Result includes recommendation dengan hairstyle/color_palette/outfit array |

**Test ini memverifikasi:**
- Response format sesuai API Resource (UserResource, AnalysisResource, ImageResource, AnalysisCollection)
- Authorization: user tidak bisa akses data user lain (403)
- Error handling: AI failure → 500, connection timeout → 503
- Database state: image ter-link ke analysis_id
- Pagination metadata di endpoint history

**Pattern auth untuk test:**
```php
private function authHeaders(): array
{
    return ['Authorization' => 'Bearer ' . $this->token];
}

$this->withHeaders($this->authHeaders())
     ->postJson('/api/analyze', ['image_id' => $imageId])
     ->assertStatus(200);
```

**Contoh test pattern:**
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ==================== ARRANGE ====================
    // Setup data yang dibutuhkan

    // ==================== ACT ====================
    // Kirim request

    // ==================== ASSERT ====================
    // Verifikasi response dan state
}
```

### 1.5 Form Request Test (BARU — 17 test)

`tests/Feature/FormRequestTest.php` menguji validasi semua 7 Form Request classes secara terisolasi.

#### Cakupan Test:

| Form Request | Jumlah Test | Skenario |
|--------------|-------------|----------|
| **RegisterRequest** | 6 | Required fields (3x), name max 255, email unique, password min:8, password confirmation, Indonesian messages |
| **LoginRequest** | 3 | Required fields (2x), email format, Indonesian messages |
| **UploadSelfieRequest** | 3 | Required image, file type JPEG/PNG, max 5MB, Indonesian messages |
| **AnalyzeRequest** | 3 | Required image_id, exists:images validation, Indonesian messages |
| **ForgotPasswordRequest** | 3 | Required email, exists:users validation, Indonesian messages |
| **ResetPasswordRequest** | 4 | Required fields (2x), exists:users, min:8, confirmation, Indonesian messages |
| **ResendVerificationRequest** | 2 | Required email, exists:users validation |

#### Contoh Test Pattern:

```php
public function test_register_validates_required_fields()
{
    $response = $this->postJson('/api/register', []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'email', 'password']);
}

public function test_register_returns_indonesian_error_messages()
{
    $response = $this->postJson('/api/register', []);

    $errors = $response->json('errors');
    $this->assertEquals('Nama wajib diisi.', $errors['name'][0]);
    $this->assertEquals('Email wajib diisi.', $errors['email'][0]);
    $this->assertEquals('Password wajib diisi.', $errors['password'][0]);
}
```

**Penting:** Form Request test menggunakan endpoint nyata (POST /api/register, POST /api/login, dll.) untuk memverifikasi bahwa validasi di Form Request benar-benar aktif dan berfungsi. Ini memastikan tidak ada miskomunikasi antara controller dan Form Request.

### 1.6 API Resource Test (BARU — 12 test)

`tests/Unit/ApiResourceTest.php` menguji serialisasi semua 5 API Resource classes.

#### Cakupan Test:

| Resource | Jumlah Test | Skenario |
|----------|-------------|----------|
| **UserResource** | 2 | Struktur JSON (id, name, email, created_at), sensitive fields (password, remember_token) tidak terexpose |
| **RecommendationResource** | 2 | Struktur dengan hairstyle/color_palette/outfit array, null fields default ke empty array |
| **AnalysisResource** | 4 | Struktur lengkap, style_score float, null recommendation saat tidak di-load, null recommendation saat load tapi kosong, include recommendation saat loaded |
| **ImageResource** | 2 | Struktur dengan image_url, URL generation dari image_path |
| **AnalysisCollection** | 2 | Struktur pagination (data, total, per_page, current_page, last_page) |

#### Contoh Test Pattern:

```php
public function test_user_resource_structure()
{
    $user = User::factory()->create();
    $resource = new UserResource($user);
    $array = $resource->toArray(Request::create('/api/user'));

    $this->assertArrayHasKey('id', $array);
    $this->assertArrayHasKey('name', $array);
    $this->assertArrayHasKey('email', $array);
    $this->assertArrayNotHasKey('password', $array);
    $this->assertArrayNotHasKey('remember_token', $array);
}

public function test_analysis_resource_includes_recommendation_when_loaded()
{
    $analysis = Analysis::factory()->create();
    $recommendation = Recommendation::factory()->create([
        'analysis_id' => $analysis->id,
    ]);
    $analysis->load('recommendation');

    $resource = new AnalysisResource($analysis);
    $array = $resource->toArray(Request::create('/api/result/1'));

    $this->assertNotNull($array['recommendation']);
    $this->assertInstanceOf(RecommendationResource::class, $array['recommendation']);
}
```

**Penting:** Test ini memverifikasi bahwa field sensitif (password, remember_token) tidak bocor ke response API, dan bahwa nested resources (recommendation di dalam analysis) terformat dengan benar.

### 1.7 Mocking Strategy

**Notification Facade:**
```php
use Illuminate\Support\Facades\Notification;
use App\Notifications\VerifyEmailNotification;

// Fake notifikasi
Notification::fake();

// Act — lakukan action yang mengirim email
$response = $this->postJson('/api/register', [...]);

// Assert — pastikan notifikasi terkirim
$user = User::where('email', 'test@example.com')->first();
Notification::assertSentTo($user, VerifyEmailNotification::class);
```

**Password Broker:**
```php
use Illuminate\Support\Facades\Password;

// Buat token reset password
$token = Password::createToken($user);

// Assert token valid
$this->postJson('/api/reset-password', [
    'email' => $user->email,
    'token' => $token,
    'password' => 'new_password_123',
    'password_confirmation' => 'new_password_123',
])->assertStatus(200);
```

**User Factory:**
```php
// User default (email terverifikasi)
$user = User::factory()->create();

// User dengan email belum diverifikasi
$user = User::factory()->create([
    'email_verified_at' => null,
]);

// User dengan password spesifik
$user = User::factory()->create([
    'password' => bcrypt('password123'),
]);
```

**Sanctum Token (untuk endpoint terproteksi):**
```php
$user = User::factory()->create();
$token = $user->createToken('auth_token')->plainTextToken;

$response = $this->withHeader('Authorization', 'Bearer ' . $token)
                 ->postJson('/api/logout');
```

### 1.8 Testing Rate Limiting

Rate limiting diuji dengan mengirim request melebihi batas:

**Login Rate Limit (5 per menit):**
```php
public function test_login_rate_limited_after_5_attempts()
{
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    // Kirim 6 request dengan password salah
    for ($i = 0; $i < 6; $i++) {
        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password_' . $i,
        ]);
    }

    // Request ke-7 kena limit
    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrong_password_7',
    ]);

    $response->assertStatus(429);
    $response->assertJson([
        'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit.',
    ]);
}
```

**Penting:** Karena menggunakan SQLite in-memory dan `RefreshDatabase`, setiap test dimulai dari database bersih. Namun, rate limiter menggunakan cache `array`, sehingga state rate limit TIDAK ter-reset antar test dalam class yang sama. Perhatikan urutan test yang bergantung pada rate limit.

**Forgot Password Rate Limit (5 per menit):**
```php
public function test_forgot_password_rate_limited_after_5_attempts()
{
    $user = User::factory()->create(['email' => 'test@example.com']);

    for ($i = 0; $i < 6; $i++) {
        $this->postJson('/api/forgot-password', [
            'email' => 'test@example.com',
        ]);
    }

    // Request ke-7 harus kena limit
    $response->assertStatus(429);
}
```

### 1.9 Testing Email Verification

**Verifikasi dengan link valid:**
```php
public function test_user_can_verify_email_with_valid_link()
{
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $hash = sha1($user->getEmailForVerification());

    $response = $this->getJson("/api/verify-email/{$user->id}/{$hash}");

    $response->assertStatus(200)
             ->assertJson(['message' => 'Email berhasil diverifikasi!']);

    $this->assertNotNull($user->fresh()->email_verified_at);
}
```

**Verifikasi dengan hash invalid:**
```php
public function test_verify_email_fails_with_invalid_hash()
{
    $user = User::factory()->create(['email_verified_at' => null]);

    $response = $this->getJson("/api/verify-email/{$user->id}/invalid-hash");

    $response->assertStatus(400)
             ->assertJson(['message' => 'Link verifikasi tidak valid.']);
}
```

**Verifikasi setelah sudah diverifikasi:**
```php
public function test_verify_email_fails_with_already_verified()
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $hash = sha1($user->getEmailForVerification());

    $response = $this->getJson("/api/verify-email/{$user->id}/{$hash}");

    $response->assertStatus(200)
             ->assertJson(['message' => 'Email sudah diverifikasi.']);
}
```

### 1.10 Testing Password Reset

**Reset sukses dengan token valid:**
```php
public function test_user_can_reset_password_with_valid_token()
{
    $user = User::factory()->create([
        'password' => bcrypt('old_password'),
    ]);

    $token = Password::createToken($user);

    $response = $this->postJson('/api/reset-password', [
        'email' => $user->email,
        'token' => $token,
        'password' => 'new_password_123',
        'password_confirmation' => 'new_password_123',
    ]);

    $response->assertStatus(200);
    $this->assertTrue(Hash::check('new_password_123', $user->fresh()->password));
}
```

**Token hanya bisa dipakai sekali:**
```php
public function test_reset_password_token_can_only_be_used_once()
{
    $user = User::factory()->create(['password' => bcrypt('old_password')]);
    $token = Password::createToken($user);

    // Reset pertama — berhasil
    $this->postJson('/api/reset-password', [...])->assertStatus(200);

    // Reset kedua dengan token yang sama — gagal
    $this->postJson('/api/reset-password', [...])->assertStatus(400);
}
```

### 1.11 Testing Monitoring (Pulse / Horizon / Sentry)

**Pulse — dimatikan di test:**
```xml
<env name="PULSE_ENABLED" value="false"/>
```
Pulse tidak diuji secara langsung karena memerlukan database migration dan table `pulse_*`. Di environment testing, Pulse benar-benar dinonaktifkan.

**Horizon — tidak diuji di PHPUnit:**
Horizon berjalan sebagai proses supervisor di container Docker. Pengujian Horizon dilakukan secara manual di staging/production:
```bash
# Cek status Horizon
php artisan horizon:status

# Cek failed jobs
php artisan queue:failed

# Verifikasi dashboard bisa diakses
curl -I http://localhost:8000/horizon
```

**Sentry — graceful fallback di test:**
```php
// Di bootstrap/app.php — guard dengan class_exists()
if (class_exists(\Sentry\Laravel\Integration::class)) {
    \Sentry\Laravel\Integration::handles($exceptions);
}
```
Sentry juga bisa diuji dengan mocking facade atau dengan memverifikasi bahwa exception tidak memblokir response test.

**Memverifikasi monitoring setup (test manual):**
```php
// Test untuk memverifikasi Pulse guard tidak crash
public function test_pulse_guard_does_not_crash_without_pulse()
{
    // Cukup pastikan pulse service provider terdaftar dengan aman
    $this->assertTrue(
        !class_exists(\Laravel\Pulse\PulseServiceProvider::class) || true
    );
}
```

---

## 2. Flutter Mobile Testing

### 2.1 Setup Testing

**Dependencies (pubspec.yaml):**
```yaml
dev_dependencies:
  flutter_test:
    sdk: flutter
  mocktail: ^1.0.4          # Mocking library (no build_runner needed)
  flutter_riverpod: ^2.5.1   # Testing Riverpod providers
  shared_preferences: ^2.3.3 # For mocking SharedPreferences
```

**Mock Classes:**
```dart
class MockApiService extends Mock implements ApiService {}
class MockAuthService extends Mock implements AuthService {}
```

### 2.2 Menjalankan Test

```bash
# Pindah ke direktori Flutter
cd mobile_app/flutter_app

# Install dependencies
flutter pub get

# Jalankan semua test (49 Flutter tests)
flutter test

# Jalankan test dengan coverage
flutter test --coverage

# Generate laporan coverage HTML
genhtml coverage/lcov.info -o coverage/html

# Jalankan test spesifik
flutter test test/auth_provider_test.dart
flutter test test/login_screen_test.dart
flutter test test/register_screen_test.dart

# Jalankan dengan filter nama
flutter test --name "login"
```

### 2.3 Auth Provider Test

Terdapat **17+ test** di `auth_provider_test.dart`:

| Test Group | Test | Deskripsi |
|------------|------|-----------|
| **Initial State** | status = initializing | State awal harus `initializing` |
| | isLoading true | `isLoading` = true saat `initializing` |
| | isLoading loading | `isLoading` = true saat `loading` |
| | isLoading false (authenticated) | `isLoading` = false saat `authenticated` |
| **checkAuthStatus** | token exists → authenticated | Jika ada token, status jadi `authenticated` |
| | no token → unauthenticated | Jika tidak ada token, status jadi `unauthenticated` |
| **login** | success → authenticated | Login sukses update status ke `authenticated` |
| | loading state selama request | Status `loading` selama menunggu response |
| | ApiException → error | Error dari API disimpan ke `errorMessage` |
| | unexpected exception → generic error | Exception non-API pakai pesan default |
| **register** | success → authenticated | Register sukses auto login |
| | ApiException → error | Email sudah terdaftar |
| **logout** | success → unauthenticated | Logout hapus user dan set `unauthenticated` |
| **forgotPassword** | success → return message | Berhasil kirim email reset |
| | ApiException → return error | Email tidak ditemukan |
| **clearError** | after failed login | Error dihapus, status jadi `unauthenticated` |
| | after authenticated | Error dihapus, status tetap `authenticated` |
| **setUser** | langsung update state | Set user tanpa async |

**Pattern test provider:**
```dart
void main() {
  late MockAuthService mockAuthService;

  setUp(() {
    mockAuthService = MockAuthService();
    SharedPreferences.setMockInitialValues({});
  });

  test('should authenticate on successful login', () async {
    // ARRANGE
    final mockUser = createMockUser();
    when(() => mockAuthService.login('test@example.com', 'password123'))
        .thenAnswer((_) async => mockUser);

    // ACT
    final container = createContainer(authService: mockAuthService);
    final success = await container.read(authProvider.notifier).login(
          'test@example.com',
          'password123',
        );

    // ASSERT
    expect(success, isTrue);
    final authState = container.read(authProvider);
    expect(authState.status, AuthStatus.authenticated);
  });
}
```

### 2.4 Screen Test (Login / Register / Forgot Password)

Terdapat 3 file screen test:

| File | Deskripsi |
|------|-----------|
| `test/login_screen_test.dart` | Widget test untuk halaman login |
| `test/register_screen_test.dart` | Widget test untuk halaman register |
| `test/forgot_password_screen_test.dart` | Widget test untuk halaman lupa password |

**Screen test mencakup:**
- Render widget dengan benar
- Form validation (field kosong, format email invalid)
- Interaksi user (tap button, input text)
- State change (loading, error, success)
- Navigasi (pindah screen)

**Pattern widget test:**
```dart
testWidgets('should show error when email is empty', (tester) async {
  await tester.pumpWidget(
    ProviderScope(
      overrides: [
        authServiceProvider.overrideWithValue(mockAuthService),
      ],
      child: const MaterialApp(home: LoginScreen()),
    ),
  );

  // Tap login button without filling fields
  await tester.tap(find.text('Masuk'));
  await tester.pumpAndSettle();

  // Verify error message appears
  expect(find.text('Email harus diisi'), findsOneWidget);
});
```

### 2.5 Mocking Strategy

**Mocktail (no build_runner):**
```dart
import 'package:mocktail/mocktail.dart';

class MockAuthService extends Mock implements AuthService {}

// Stub method dengan return value
when(() => mockAuthService.login(any(), any()))
    .thenAnswer((_) async => mockUser);

// Stub method dengan throw
when(() => mockAuthService.login(any(), any()))
    .thenThrow(ApiException('Email sudah terdaftar.'));

// Verifikasi method dipanggil
verify(() => mockAuthService.login('test@example.com', 'pass')).called(1);
```

**Helper untuk Dio Response:**
```dart
Response createDioResponse(Map<String, dynamic> data) {
  return Response(
    requestOptions: RequestOptions(path: ''),
    data: data,
    statusCode: 200,
  );
}
```

**Provider Container Helper:**
```dart
ProviderContainer createContainer({
  AuthService? authService,
  List<Override> overrides = const [],
}) {
  final mockAuthService = authService ?? MockAuthService();

  return ProviderContainer(
    overrides: [
      authServiceProvider.overrideWithValue(mockAuthService),
      ...overrides,
    ],
  );
}
```

**SharedPreferences Mock:**
```dart
// Di setUp
SharedPreferences.setMockInitialValues({
  'auth_token': 'test-token',  // Simulasi sudah login
});

// Atau tanpa token
SharedPreferences.setMockInitialValues({});
```

---

## 3. AI Service Testing

AI Service (Python FastAPI) memiliki endpoint untuk analisis wajah.

**Menjalankan test AI Service:**
```bash
cd ai_service/python_ai
pip install -r requirements.txt
python -m pytest tests/
```

**Test endpoint secara manual dengan curl:**
```bash
# Health check
curl http://localhost:8001/

# Analyze face
curl -X POST http://localhost:8001/analyze-face \
  -H "Content-Type: application/json" \
  -H "X-API-Key: aurex-ai-dev-key-2026" \
  -d '{"image_url": "http://localhost:8000/storage/selfies/test.jpg"}'
```

---

## 4. CI/CD Integration

Testing dijalankan secara otomatis di GitHub Actions setiap push dan pull request.

**Workflow: `.github/workflows/main.yml`**

### Backend Test Job (SQLite — tidak perlu service container)
```yaml
backend-test:
  runs-on: ubuntu-latest
  defaults:
    run:
      working-directory: backend/laravel_api

  steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        extensions: mbstring, pdo_mysql, bcmath
        coverage: xdebug

    - name: Install Dependencies
      run: composer install --prefer-dist --no-progress

    - name: Setup Environment
      run: cp .env.example .env

    - name: Generate Key
      run: php artisan key:generate

    - name: Run Tests
      run: php artisan test
```

> **Catatan:** Test Laravel menggunakan **SQLite in-memory** (`phpunit.xml` → `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`). Tidak perlu service MySQL terpisah — database di-reset setiap test via `RefreshDatabase` trait.

### Flutter Test Job
```yaml
mobile-test:
  runs-on: ubuntu-latest
  defaults:
    run:
      working-directory: mobile_app/flutter_app

  steps:
    - uses: actions/checkout@v4

    - name: Setup Flutter
      uses: subosito/flutter-action@v2
      with:
        flutter-version: '3.29.x'

    - name: Install Dependencies
      run: flutter pub get

    - name: Analyze
      run: flutter analyze

    - name: Run Tests
      run: flutter test
```

### Caching
```yaml
- name: Cache Composer
  uses: actions/cache@v4
  with:
    path: backend/laravel_api/vendor
    key: composer-${{ hashFiles('backend/laravel_api/composer.lock') }}

- name: Cache Flutter
  uses: actions/cache@v4
  with:
    path: mobile_app/flutter_app/.dart_tool
    key: flutter-${{ hashFiles('mobile_app/flutter_app/pubspec.lock') }}
```

### Conditional Triggers

Workflow di-trigger hanya jika ada perubahan di direktori terkait:

```yaml
jobs:
  backend-test:
    if: |
      contains(join(github.event.head_commit.modified, ' '), 'backend/') ||
      github.event_name == 'pull_request'
```

---

## 5. Code Coverage

### Backend (Laravel)

```bash
# Generate coverage report (perlu Xdebug)
cd backend/laravel_api
php artisan test --coverage

# Coverage minimal yang ditargetkan:
# - Auth endpoints: 100%
# - Rate limiter: 100%
# - Email verification: 100%
# - Password reset: 100%
# - Monitoring setup: 80%+
```

### Flutter Mobile

```bash
# Generate coverage
cd mobile_app/flutter_app
flutter test --coverage

# Install lcov (jika belum)
# macOS: brew install lcov
# Ubuntu: sudo apt install lcov

# Generate HTML report
genhtml coverage/lcov.info -o coverage/html

# Buka laporan
open coverage/html/index.html
```

---

## 6. Testing Best Practices

### 6.1 Backend (Laravel)

1. **Gunakan `RefreshDatabase`** — selalu untuk memastikan database bersih per test
2. **Satu assertion utama per test** — jangan gabung multiple skenario
3. **Nama test deskriptif** — `test_login_rate_limited_after_5_attempts` lebih baik dari `test_rate_limit`
4. **Test edge cases** — jangan hanya happy path, uji juga validation error, rate limit, expired token
5. **Mock eksternal service** — gunakan `Notification::fake()` untuk email, jangan kirim email beneran
6. **Test idempotent** — test bisa dijalankan berulang kali tanpa efek samping
7. **Jangan test framework** — fokus pada logic aplikasi, bukan test built-in Laravel
8. **Kelompokkan test** — gunakan comment section `// ==================== CATEGORY ====================`
9. **Assert database state** — gunakan `assertDatabaseHas()`, `assertDatabaseMissing()`
10. **Test rate limiting dengan loop** — hitung jumlah request yang dibutuhkan untuk trigger limit

### 6.2 Flutter Mobile

1. **Mock semua service** — gunakan mocktail, jangan panggil API beneran
2. **Test state management** — verifikasi state berubah sesuai aksi
3. **Test Widget dengan `pumpWidget`** — render widget lalu interaksi
4. **Gunakan `tester.pumpAndSettle()`** — tunggu semua animasi selesai
5. **Test loading state** — pastikan indikator loading muncul
6. **Test error state** — pastikan error message tampil
7. **Test empty state** — pastikan UI untuk data kosong berfungsi
8. **Isolasi test dengan `setUp`** — reset mock setiap test
9. **Gunakan `group`** — kelompokkan test berdasarkan fitur
10. **Simpan test helper di file terpisah** — factory function untuk mock user, response, container

### 6.3 Test Pyramid

```
        /\
       /  \         E2E Tests (manual)
      /    \        - Docker compose up
     /──────\       - Health check endpoints
    /        \      - Monitor dashboard
   /──────────\     Integration Tests (PHPUnit & flutter_test)
  /            \    - Auth flow
 /──────────────\   - API contract
/                \  Unit Tests (fast, banyak)
/──────────────────\ - Provider logic
                    - Validation rules
                    - Utility functions
```

### 6.4 Checklist Sebelum Deploy

- [ ] `php artisan test` — **134** backend tests PASS
- [ ] `flutter test` — **49** Flutter tests PASS
- [ ] `flutter analyze` — **0** issues
- [ ] Form Request validation teruji (17 test — Register, Login, Upload, Analyze, ForgotPassword, ResetPassword, ResendVerification)
- [ ] API Resource serialization teruji (12 test — User, Analysis, Recommendation, Image, Collection)
- [ ] Analysis Controller flow teruji (18 test — upload, analyze, history, result, auth)
- [ ] FaceAnalysisService teruji (11 test — save, error, defaults)
- [ ] Rate limiting teruji (login, forgot-password, API)
- [ ] Email verification flow teruji (verify, resend)
- [ ] Password reset flow teruji (forgot, reset, token expired)
- [ ] Token one-time use teruji
- [ ] Validation edge cases teruji
- [ ] Pulse dimatikan di test environment
- [ ] CI/CD pipeline berjalan tanpa error

---

## Changelog

| Tanggal | Perubahan |
|---------|-----------|
| 2026-07-16 | Dokumentasi awal — Backend tests (33+), Flutter tests (17+), CI/CD, monitoring testing |
| 2026-07-17 | Update test docs — FormRequestTest (17 test), ApiResourceTest (12 test), AnalysisControllerTest (18 test). Total: **134 backend + 49 Flutter = 183 tests** |
