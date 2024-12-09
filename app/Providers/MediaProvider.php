<?php

namespace App\Providers;

use App\Services\MediaService;
use Illuminate\Support\ServiceProvider;

class MediaProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // Bind MediaService as a singleton to the container
        $this->app->singleton(MediaService::class, function ($app) {
            return new MediaService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
