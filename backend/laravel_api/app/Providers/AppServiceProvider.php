<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();
        $this->configurePulseAuthorization();
    }

    /**
     * Konfigurasi rate limiters untuk API.
     */
    private function configureRateLimiters(): void
    {
        // Login: 5 percobaan per menit per email+IP
        RateLimiter::for('login', function (Request $request) {
            $key = ($request->input('email') ?? 'unknown') . '|' . ($request->ip() ?? 'unknown');
            return Limit::perMinute(5)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit.',
                ], 429);
            });
        });

        // API umum: 60 request per menit per user/IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Konfigurasi authorization gate untuk Laravel Pulse dashboard.
     * Hanya user dengan role admin yang bisa mengakses /pulse.
     */
    private function configurePulseAuthorization(): void
    {
        Gate::define('viewPulse', function (User $user) {
            return in_array($user->email, [
                // Daftar email yang diizinkan mengakses Pulse dashboard
                // Sesuaikan dengan admin email di production
                'admin@aurex.app',
            ]);
        });
    }
}
