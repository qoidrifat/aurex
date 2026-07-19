<?php

namespace App\Providers;

use App\Models\Analysis;
use App\Models\Image;
use App\Policies\AnalysisPolicy;
use App\Policies\ImagePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

/**
 * Service Provider untuk registrasi Policy authorization.
 *
 * Menghubungkan Policy classes dengan Model-nya agar
 * `$this->authorize('view', $analysis)` dan `$this->authorize('view', $image)`
 * berfungsi secara otomatis di controller.
 *
 * @see https://laravel.com/docs/12.x/authorization#registering-policies
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Analysis::class => AnalysisPolicy::class,
        Image::class => ImagePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
