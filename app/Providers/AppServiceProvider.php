<?php

namespace App\Providers;

use App\Services\News\GoogleNewsResolver;
use App\Services\News\NewsResolverInterface;
use App\Services\Trends\SerpApiTrendsClient;
use App\Services\Trends\TrendsClientInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TrendsClientInterface::class, SerpApiTrendsClient::class);
        $this->app->bind(NewsResolverInterface::class, GoogleNewsResolver::class);
    }

    public function boot(): void
    {
        //
    }
}
