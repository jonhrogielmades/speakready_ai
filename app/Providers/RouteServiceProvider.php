<?php

namespace App\Providers;

use App\Http\Controllers\HealthController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            $identifier = strtolower(trim((string) $request->input('username', $request->input('email', ''))));
            $limit = max(1, (int) env('AUTH_RATE_LIMIT_PER_MINUTE', 10));

            return [
                Limit::perMinute(min(60, $limit * 3))->by('auth-ip|'.$request->ip()),
                Limit::perMinute($limit)->by('auth-identity|'.$request->ip().'|'.$identifier),
            ];
        });

        RateLimiter::for('ai', function (Request $request) {
            $actor = $request->user()?->getAuthIdentifier() ?: $request->ip();
            $limit = max(1, (int) env('AI_RATE_LIMIT_PER_MINUTE', 20));

            return [
                Limit::perMinute($limit)->by('ai-actor|'.$actor),
                Limit::perMinute(min(60, $limit * 2))->by('ai-ip|'.$request->ip()),
            ];
        });

        RateLimiter::for('ai-expensive', function (Request $request) {
            $actor = $request->user()?->getAuthIdentifier() ?: $request->ip();
            $limit = max(1, (int) env('AI_EXPENSIVE_RATE_LIMIT_PER_MINUTE', 6));

            return [
                Limit::perMinute($limit)->by('ai-expensive-actor|'.$actor),
                Limit::perMinute(min(30, $limit * 2))->by('ai-expensive-ip|'.$request->ip()),
            ];
        });

        RateLimiter::for('ai-transcribe', function (Request $request) {
            $actor = $request->user()?->getAuthIdentifier() ?: $request->ip();
            $limit = max(1, (int) config('services.ai_transcription.rate_limit_per_minute', 20));

            return [
                Limit::perMinute($limit)->by('ai-transcribe-actor|'.$actor),
                Limit::perMinute(min(60, $limit * 2))->by('ai-transcribe-ip|'.$request->ip()),
            ];
        });

        RateLimiter::for('admin-ai', function (Request $request) {
            return Limit::perMinute((int) env('ADMIN_AI_RATE_LIMIT_PER_MINUTE', 5))
                ->by('admin-ai|'.($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });

        RateLimiter::for('contact', function (Request $request) {
            return Limit::perMinute((int) env('CONTACT_RATE_LIMIT_PER_MINUTE', 3))
                ->by('contact|'.$request->ip());
        });

        RateLimiter::for('push', function (Request $request) {
            return Limit::perMinute((int) env('PUSH_RATE_LIMIT_PER_MINUTE', 20))
                ->by('push|'.($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });

        $this->routes(function () {
            // Readiness deliberately avoids session/cookie middleware so probes stay stateless.
            Route::get('/up', HealthController::class)->name('health');

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
