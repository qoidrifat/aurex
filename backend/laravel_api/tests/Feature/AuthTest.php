<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\PasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ==================== REGISTRATION ====================

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Test@1234',
            'password_confirmation' => 'Test@1234',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'user' => ['id', 'name', 'email'],
                     'message',
                 ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertEquals('Registrasi berhasil. Silakan cek email untuk verifikasi.', $response->json('message'));
    }

    public function test_register_sends_verification_email()
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Test@1234',
            'password_confirmation' => 'Test@1234',
        ]);

        $response->assertStatus(200);

        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_register_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Test@1234',
            'password_confirmation' => 'Test@1234',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_with_short_password()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_register_fails_with_missing_name()
    {
        $response = $this->postJson('/api/v1/register', [
            'email' => 'test@example.com',
            'password' => 'Test@1234',
            'password_confirmation' => 'Test@1234',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_register_fails_with_password_mismatch()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Test@1234',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    // ==================== LOGIN ====================

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_nonexistent_email()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_unverified_email_and_require_verified_flag()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
            'require_verified' => true,
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'needs_verification' => true,
                 ]);
    }

    public function test_login_without_require_verified_flag_allows_unverified_email()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Without require_verified flag, unverified users can still login
        $response->assertStatus(200)
                 ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    // ==================== LOGOUT ====================

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_logout_fails_without_token()
    {
        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(401);
    }

    public function test_token_deleted_from_database_after_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        $tokenId = $user->tokens()->first()->id;

        // Logout
        $this->withHeader('Authorization', 'Bearer ' . $token)
             ->postJson('/api/v1/logout')
             ->assertStatus(200);

        // Token sudah dihapus dari database
        $this->assertNull($user->tokens()->find($tokenId));
    }

    // ==================== RATE LIMITING ====================

    public function test_login_rate_limited_after_5_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Kirim 6 request login dengan password salah
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong_password_' . $i,
            ]);
        }

        // Request ke-7 harus kena rate limit
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password_7',
        ]);

        $response->assertStatus(429);
        $response->assertJson(['message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit.']);
    }

    public function test_forgot_password_rate_limited_after_5_attempts()
    {
        User::factory()->create(['email' => 'test@example.com']);

        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/forgot-password', [
                'email' => 'test@example.com',
            ]);
        }

        $response->assertStatus(429);
    }

    // ==================== EMAIL VERIFICATION ====================

    public function test_user_can_verify_email_with_valid_link()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $hash = sha1($user->getEmailForVerification());

        $response = $this->getJson("/api/v1/verify-email/{$user->id}/{$hash}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Email berhasil diverifikasi!']);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_fails_with_invalid_hash()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->getJson("/api/v1/verify-email/{$user->id}/invalid-hash");

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Link verifikasi tidak valid.']);
    }

    public function test_verify_email_fails_with_already_verified()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $hash = sha1($user->getEmailForVerification());

        $response = $this->getJson("/api/v1/verify-email/{$user->id}/{$hash}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Email sudah diverifikasi.']);
    }

    public function test_user_can_resend_verification_email()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Email verifikasi telah dikirim ulang.']);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_resend_verification_fails_if_already_verified()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Email sudah diverifikasi.']);
    }

    public function test_resend_verification_fails_with_nonexistent_email()
    {
        $response = $this->postJson('/api/v1/resend-verification', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422);
    }

    // ==================== PASSWORD RESET ====================

    public function test_user_can_request_password_reset()
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Link reset password telah dikirim ke email Anda.']);

        Notification::assertSentTo($user, PasswordResetNotification::class);
    }

    public function test_forgot_password_fails_with_nonexistent_email()
    {
        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_reset_password_with_valid_token()
    {
        $user = User::factory()->create([
            'password' => bcrypt('old_password'),
        ]);

        // Buat token reset password yang valid
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewP@ss123',
            'password_confirmation' => 'NewP@ss123',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Password berhasil direset. Silakan login dengan password baru.']);

        // Verifikasi password sudah berubah
        $this->assertTrue(Hash::check('NewP@ss123', $user->fresh()->password));
        $this->assertFalse(Hash::check('old_password', $user->fresh()->password));
    }

    public function test_reset_password_fails_with_invalid_token()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'NewP@ss123',
            'password_confirmation' => 'NewP@ss123',
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Token reset password tidak valid atau sudah kadaluarsa.']);
    }

    public function test_reset_password_fails_with_short_password()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_password_fails_with_password_mismatch()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new_password_123',
            'password_confirmation' => 'different_password',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_password_fails_with_nonexistent_email()
    {
        $response = $this->postJson('/api/v1/reset-password', [
            'email' => 'nonexistent@example.com',
            'token' => 'some-token',
            'password' => 'NewP@ss123',
            'password_confirmation' => 'NewP@ss123',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_token_can_only_be_used_once()
    {
        $user = User::factory()->create([
            'password' => bcrypt('old_password'),
        ]);

        $token = Password::createToken($user);

        // Reset pertama - berhasil
        $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewP@ss1',
            'password_confirmation' => 'NewP@ss1',
        ])->assertStatus(200);

        // Reset kedua dengan token yang sama - gagal
        $this->postJson('/api/v1/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewP@ss2',
            'password_confirmation' => 'NewP@ss2',
        ])->assertStatus(400);
    }

    // ==================== VALIDATION EDGE CASES ====================

    public function test_register_fails_with_invalid_email_format()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'Test@1234',
            'password_confirmation' => 'Test@1234',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_missing_email()
    {
        $response = $this->postJson('/api/v1/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_missing_password()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }
}
