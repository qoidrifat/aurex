<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==================== V1 API ROUTES ====================
// Semua endpoint API menggunakan prefix /api/v1/

Route::prefix('v1')->group(function () {

    // ── Public Routes (tanpa autentikasi) ──────────────

    // Health check (untuk monitoring & Docker healthcheck)
    Route::get('/health', HealthController::class);

    // Autentikasi (dengan rate limiting)
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Email Verification
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);

    // Password Reset
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // ── Protected Routes (perlu autentikasi) ──────────

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::post('/upload-selfie', [AnalysisController::class, 'uploadSelfie']);
        Route::post('/analyze', [AnalysisController::class, 'analyze']);
        Route::get('/history', [AnalysisController::class, 'history']);
        Route::get('/result/{id}', [AnalysisController::class, 'getResult']);
    });
});

// ── Backward Compatibility Redirects ──────────────
// Old /api/* routes will redirect to /api/v1/*
// Hapus redirect ini setelah semua client migrasi ke v1
/*
Route::permanentRedirect('/health', '/v1/health');
Route::permanentRedirect('/register', '/v1/register');
Route::permanentRedirect('/login', '/v1/login');
Route::permanentRedirect('/logout', '/v1/logout');
Route::permanentRedirect('/upload-selfie', '/v1/upload-selfie');
Route::permanentRedirect('/analyze', '/v1/analyze');
Route::permanentRedirect('/history', '/v1/history');
Route::permanentRedirect('/forgot-password', '/v1/forgot-password');
Route::permanentRedirect('/reset-password', '/v1/reset-password');
Route::permanentRedirect('/resend-verification', '/v1/resend-verification');
*/
