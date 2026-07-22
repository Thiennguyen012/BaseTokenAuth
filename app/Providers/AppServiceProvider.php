<?php

namespace App\Providers;

use App\Repositories\RefreshToken\RefreshTokenInterface;
use App\Repositories\RefreshToken\RefreshTokenRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RefreshTokenInterface::class, RefreshTokenRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
