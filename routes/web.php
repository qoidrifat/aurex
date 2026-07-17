<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', [SiteController::class, 'landing'])->name('landing');

/*
|--------------------------------------------------------------------------
| Guest auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated user
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/analyze', [AnalysisController::class, 'create'])->name('analysis.create');
    Route::post('/analyze', [AnalysisController::class, 'store'])->name('analysis.store');
    Route::get('/analyze/{analysis}/processing', [AnalysisController::class, 'processing'])->name('analysis.processing');
    Route::post('/analyze/{analysis}/run', [AnalysisController::class, 'run'])->name('analysis.run');
    Route::get('/analyze/{analysis}', [AnalysisController::class, 'show'])->name('analysis.show');
    Route::delete('/analyze/{analysis}', [AnalysisController::class, 'destroy'])->name('analysis.destroy');
    Route::get('/history', [AnalysisController::class, 'history'])->name('analysis.history');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{analysis}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/{analysis}/save', [ReportController::class, 'save'])->name('reports.save');
    Route::get('/reports/{analysis}/download', [ReportController::class, 'download'])->name('reports.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    Route::put('/settings', [ProfileController::class, 'updatePreferences'])->name('profile.preferences');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/analyses', [AdminController::class, 'analyses'])->name('analyses');
        Route::get('/images', [AdminController::class, 'images'])->name('images');
    });
