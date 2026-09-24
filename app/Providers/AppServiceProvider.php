<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\DatabaseIdSequences;
use App\Support\SystemSettings;
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
        $this->applyRuntimeSystemSettings();

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
                $systemSettings = SystemSettings::forView();

                $request->attributes->set('languageViewData', [
                    'supportedLanguages' => Setting::supportedLanguages(),
                    'currentLanguageCode' => $languageConfig['code'],
                    'currentLanguageLabel' => $languageConfig['label'],
                    'currentLanguageAiLabel' => $languageConfig['ai_label'],
                    'systemHtmlLocale' => $languageConfig['html_locale'],
                    'systemSpeechLocale' => $languageConfig['speech_locale'],
                    'systemSettings' => $systemSettings,
                    'systemName' => $systemSettings['sys_name'] ?? config('app.name', 'SpeakReady AI'),
                    'systemLogo' => $systemSettings['system_logo'] ?? 'img/logo.png',
                    'systemFavicon' => $systemSettings['system_favicon'] ?? 'favicon.ico',
                    'systemPrimaryColor' => $systemSettings['color_primary'] ?? '#3b82f6',
                    'systemSecondaryColor' => $systemSettings['color_secondary'] ?? '#34d399',
                    'systemContactEmail' => $systemSettings['sys_contact_email'] ?? 'support@speakready.ai',
                    'systemContactNumber' => $systemSettings['sys_contact_number'] ?? '',
                    'systemDescription' => $systemSettings['sys_desc'] ?? 'SpeakReady AI helps users master communication skills.',
                    'systemFooter' => $systemSettings['sys_footer'] ?? '&copy; 2026 SpeakReady AI. All Rights Reserved.',
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

    private function applyRuntimeSystemSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $appName = (string) SystemSettings::value('sys_name', config('app.name'));
            if ($appName !== '') {
                config(['app.name' => $appName]);
                config(['mail.from.name' => $appName]);
            }

            $contactEmail = (string) SystemSettings::value('sys_contact_email', config('mail.from.address'));
            if ($contactEmail !== '') {
                config(['mail.from.address' => $contactEmail]);
            }

            $sessionLifetime = max(5, min(1440, (int) SystemSettings::value('acc_session_timeout', config('session.lifetime', 120))));
            config(['session.lifetime' => $sessionLifetime]);

            $mailHost = (string) SystemSettings::value('mail_host', '');
            if ($mailHost !== '') {
                config(['mail.mailers.smtp.host' => $mailHost]);
            }

            $mailPort = (string) SystemSettings::value('mail_port', '');
            if ($mailPort !== '') {
                config(['mail.mailers.smtp.port' => (int) $mailPort]);
            }

            $mailUser = (string) SystemSettings::value('mail_user', '');
            if ($mailUser !== '') {
                config(['mail.mailers.smtp.username' => $mailUser]);
            }

            $mailPass = (string) SystemSettings::value('mail_pass', '');
            if ($mailPass !== '') {
                config(['mail.mailers.smtp.password' => $mailPass]);
            }
        } catch (\Throwable) {
            // Keep boot resilient during installation, migration, and test setup.
        }
    }
}
