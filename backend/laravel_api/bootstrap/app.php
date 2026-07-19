<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            // API Response Caching (#4 Bulan 3)
            // Usage: Route::middleware(['cache.response:60'])->group(...)
            'cache.response' => \App\Http\Middleware\CacheResponseMiddleware::class,
        ]);
        $middleware->statefulApi();

        // Logging terstruktur untuk semua request API
        $middleware->api(prepend: [
            \App\Http\Middleware\LogContextMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sentry: tangkap exception via bound helper (graceful jika Sentry belum terinstall)
        $exceptions->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        // Integrasi Sentry (hanya jika package terinstall)
        if (class_exists(\Sentry\Laravel\Integration::class)) {
            \Sentry\Laravel\Integration::handles($exceptions);
        }
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('sanctum:prune-expired --hours=24')->daily();

        // Pulse: monitoring (hanya jika pulse terinstall)
        if (class_exists(\Laravel\Pulse\PulseServiceProvider::class)) {
            $schedule->command('pulse:check')->everyMinute()->withoutOverlapping();
        }

        // Health Monitor: periodic health check dengan alerting
        $schedule->command('health:monitor')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/health-monitor.log'));

        // Data Cleanup (#2 Bulan 3): hapus data lama sesuai retention policy
        $schedule->command('data:cleanup --force')
            ->dailyAt('02:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/data-cleanup.log'));
    })
    ->create();
