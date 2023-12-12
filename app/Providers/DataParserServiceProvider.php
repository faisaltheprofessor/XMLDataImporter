<?php

namespace App\Providers;

use App\Services\XMLParserService;
use Illuminate\Support\ServiceProvider;

class DataParserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(XMLParserService::class, function ($app) {
            return new XMLParserService();
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
