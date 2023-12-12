<?php

namespace App\Providers;

use App\Services\DataParserService;
use Illuminate\Support\ServiceProvider;

class DataParserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(DataParserService::class, function ($app) {
            return new DataParserService();
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
