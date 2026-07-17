<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormRequestTest extends TestCase
{
    use RefreshDatabase;

    // ==================== REGISTER REQUEST ====================

    public function test_register_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_validates_name_max_length()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => str_repeat('a', 256),
            'email' => 'test@example.com',
            'password' => 'Test@1234',
            'password_confirmation' => 'Test@1234',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_register_validates_email_format()
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

    public function test_register_validates_email_uniqueness()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'Test@1234',
            'password_confirmation' => 'Test@1234',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_register_validates_password_min_length()
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

    public function test_register_validates_password_confirmation()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_register_returns_indonesian_error_messages()
    {
        $response = $this->postJson('/api/v1/register', []);

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $this->assertEquals('Nama lengkap wajib diisi.', $errors['name'][0]);
        $this->assertEquals('Email wajib diisi.', $errors['email'][0]);
        $this->assertEquals('Password wajib diisi.', $errors['password'][0]);
    }

    // ==================== PASSWORD STRENGTH RULES ====================

    public function test_register_fails_without_uppercase()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'lowercase@123',  // no uppercase
            'password_confirmation' => 'lowercase@123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);

        $errors = $response->json('errors');
        $this->assertEquals(
            'Password harus mengandung huruf besar, angka, dan simbol.',
            $errors['password'][0]
        );
    }

    public function test_register_fails_without_number()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Uppercase@only',  // no number
            'password_confirmation' => 'Uppercase@only',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);

        $errors = $response->json('errors');
        $this->assertEquals(
            'Password harus mengandung huruf besar, angka, dan simbol.',
            $errors['password'][0]
        );
    }

    public function test_register_fails_without_symbol()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Uppercase1NoSymbol',  // no symbol
            'password_confirmation' => 'Uppercase1NoSymbol',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);

        $errors = $response->json('errors');
        $this->assertEquals(
            'Password harus mengandung huruf besar, angka, dan simbol.',
            $errors['password'][0]
        );
    }

    public function test_register_succeeds_with_strong_password()
    {
        // Strong password meets all rules: uppercase + number + symbol + min 8
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Strong User',
            'email' => 'strong@example.com',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['access_token', 'user']);

        $this->assertDatabaseHas('users', ['email' => 'strong@example.com']);
    }

    // ==================== LOGIN REQUEST ===

    public function test_login_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/login', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_validates_email_format()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'invalid',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_returns_indonesian_error_messages()
    {
        $response = $this->postJson('/api/v1/login', []);

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $this->assertEquals('Email wajib diisi.', $errors['email'][0]);
        $this->assertEquals('Password wajib diisi.', $errors['password'][0]);
    }

    // ==================== UPLOAD SELFIE REQUEST ====================

    public function test_upload_selfie_validates_required_image()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/upload-selfie', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_selfie_validates_file_type()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/upload-selfie', ['image' => $file]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_selfie_validates_file_size()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        Storage::fake('public');

        $file = UploadedFile::fake()->image('large.jpg')->size(6000);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/upload-selfie', ['image' => $file]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_selfie_returns_indonesian_error_messages()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/upload-selfie', []);

        $errors = $response->json('errors');
        $this->assertEquals('File gambar wajib diupload.', $errors['image'][0]);
    }

    // ==================== ANALYZE REQUEST ====================

    public function test_analyze_validates_required_image_id()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/analyze', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image_id']);
    }

    public function test_analyze_validates_image_id_exists()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/analyze', ['image_id' => 999]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image_id']);
    }

    public function test_analyze_returns_indonesian_error_messages()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/v1/analyze', []);

        $errors = $response->json('errors');
        $this->assertEquals('ID gambar wajib diisi.', $errors['image_id'][0]);
    }

    // ==================== FORGOT PASSWORD REQUEST ====================

    public function test_forgot_password_validates_required_email()
    {
        $response = $this->postJson('/api/v1/forgot-password', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_validates_email_exists()
    {
        $response = $this->postJson('/api/v1/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_returns_indonesian_error_messages()
    {
        $response = $this->postJson('/api/v1/forgot-password', []);

        $errors = $response->json('errors');
        $this->assertEquals('Email wajib diisi.', $errors['email'][0]);
    }

    // ==================== RESET PASSWORD REQUEST ====================

    public function test_reset_password_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/reset-password', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['token', 'email', 'password']);
    }

    public function test_reset_password_validates_email_exists()
    {
        $response = $this->postJson('/api/v1/reset-password', [
            'token' => 'some-token',
            'email' => 'nonexistent@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_validates_min_length()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/reset-password', [
            'token' => 'some-token',
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_password_validates_confirmation()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/reset-password', [
            'token' => 'some-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    // ==================== RESEND VERIFICATION REQUEST ====================

    public function test_resend_verification_validates_required_email()
    {
        $response = $this->postJson('/api/v1/resend-verification', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_resend_verification_validates_email_exists()
    {
        $response = $this->postJson('/api/v1/resend-verification', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }
}
