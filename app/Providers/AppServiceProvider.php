<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Force Socialite to use IPv4 to prevent 504 Gateway Timeouts on Render
        if (class_exists(\Laravel\Socialite\Facades\Socialite::class)) {
            \Laravel\Socialite\Facades\Socialite::extend('google', function ($app) {
                $config = $app['config']['services.google'];
                $provider = \Laravel\Socialite\Facades\Socialite::buildProvider(\Laravel\Socialite\Two\GoogleProvider::class, $config);
                $provider->setHttpClient(new \GuzzleHttp\Client(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]]));
                return $provider;
            });
        }
    }
}
