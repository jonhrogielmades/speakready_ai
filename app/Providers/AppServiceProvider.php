<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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

        View::composer('*', function ($view) {
            $request = request();

            if (!$request->attributes->has('languageViewData')) {
                $languageConfig = Setting::SUPPORTED_LANGUAGES['en'];
                $languageCode = null;

                try {
                    if (Schema::hasTable('settings')) {
                        $languageCode = (string) Setting::getVal('sys_language', 'en');
                    }
                } catch (\Throwable $e) {
                    $languageCode = 'en';
                }

                if (auth()->check() && auth()->user()->preferred_language) {
                    $languageCode = auth()->user()->preferred_language;
                }

                $languageConfig = Setting::languageConfig($languageCode);

                $request->attributes->set('languageViewData', [
                    'supportedLanguages' => Setting::supportedLanguages(),
                    'currentLanguageCode' => $languageConfig['code'],
                    'currentLanguageLabel' => $languageConfig['label'],
                    'currentLanguageAiLabel' => $languageConfig['ai_label'],
                    'systemHtmlLocale' => $languageConfig['html_locale'],
                    'systemSpeechLocale' => $languageConfig['speech_locale'],
                ]);
            }

            $view->with($request->attributes->get('languageViewData'));
        });
    }
}
