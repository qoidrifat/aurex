<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeMailNotifications();
        // Horizon::routeSlackNotifications();
        // Horizon::routeSmsNotifications();
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return in_array($user->email, [
                // Daftar email admin yang diizinkan mengakses dashboard
                // Sesuaikan dengan admin email di production
                'admin@aurex.app',
            ]);
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
