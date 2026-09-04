<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\DatabaseIdSequences;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Helpers/ViewHelper.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        $this->resetEmptyIdSequencesAfterDeletes();

        View::composer('*', function ($view) {
            $request = request();

            if (!$request->attributes->has('languageViewData')) {
                $languageCode = 'en';

                try {
                    if (Schema::hasTable('settings')) {
                        $languageCode = (string) Setting::getVal('sys_language', 'en');
                    }

                    if (auth()->check()) {
                        $preferredLanguage = Setting::preferredLanguageFor(auth()->user());

                        if ($preferredLanguage) {
                            $languageCode = $preferredLanguage;
                        }
                    }
                } catch (\Throwable $e) {
                    $languageCode = 'en';
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

    private function resetEmptyIdSequencesAfterDeletes(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $query): void {
            $sequences = app(DatabaseIdSequences::class);
            $table = $sequences->tableNameFromDeleteSql($query->sql);

            if ($table === null) {
                return;
            }

            $connectionName = $query->connectionName;
            $reset = static fn () => $sequences->normalizeTableIfEmpty($table, $connectionName);

            if (DB::connection($connectionName)->transactionLevel() > 0) {
                DB::connection($connectionName)->afterCommit($reset);

                return;
            }

            $reset();
        });
    }
}
