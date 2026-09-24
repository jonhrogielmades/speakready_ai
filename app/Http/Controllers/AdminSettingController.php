<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSettingController extends Controller
{
    /**
     * Display the system settings.
     */
    public function index()
    {
        $settings = SystemSettings::forView();
        $auditLogs = ActivityLog::with('user')
            ->latest('id')
            ->take(12)
            ->get();
        $systemInfo = $this->systemInfo();

        return $this->mobileView('admin.settings', compact('settings', 'auditLogs', 'systemInfo'));
    }

    /**
     * Update the system settings.
     */
    public function update(Request $request)
    {
        $request->validate(array_merge(SystemSettings::validationRules(), [
            'system_logo' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'system_favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg,webp,svg|max:1024',
        ]));

        $saved = $this->persistRequestSettings($request);

        // Handle file uploads separately if any
        if ($request->hasFile('system_logo')) {
            $logo = $request->file('system_logo');
            $logoName = 'logo.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('img'), $logoName);
            Setting::setVal('system_logo', 'img/' . $logoName, 'appearance', 'string');
            $saved[] = 'system_logo';
        }

        if ($request->hasFile('system_favicon')) {
            $favicon = $request->file('system_favicon');
            $faviconName = 'favicon.' . $favicon->getClientOriginalExtension();
            $favicon->move(public_path('img'), $faviconName);
            Setting::setVal('system_favicon', 'img/' . $faviconName, 'appearance', 'string');
            $saved[] = 'system_favicon';
        }

        $this->logSettingsActivity($request, 'admin_settings_updated', 'updated system settings', $saved);

        return redirect()->route('admin.settings.index')->with('message', 'Settings updated successfully.');
    }

    public function downloadBackup(Request $request)
    {
        $payload = [
            'exported_at' => now()->toIso8601String(),
            'app' => SystemSettings::value('sys_name', config('app.name')),
            'settings' => Setting::orderBy('key')
                ->get(['key', 'value', 'type', 'group'])
                ->mapWithKeys(fn (Setting $setting): array => [
                    $setting->key => [
                        'value' => $setting->value,
                        'type' => $setting->type,
                        'group' => $setting->group,
                    ],
                ])
                ->all(),
        ];

        $this->logSettingsActivity($request, 'admin_settings_backup_downloaded', 'downloaded a settings backup', []);

        $fileName = 'speakready-settings-'.now()->format('Ymd-His').'.json';

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function restoreBackup(Request $request)
    {
        $request->validate([
            'settings_backup_file' => ['required', 'file', 'mimes:json,txt', 'max:2048'],
        ]);

        $contents = file_get_contents($request->file('settings_backup_file')->getRealPath());
        $payload = json_decode((string) $contents, true);

        if (! is_array($payload) || ! is_array($payload['settings'] ?? null)) {
            return redirect()
                ->route('admin.settings.index')
                ->with('error', 'The selected settings backup could not be read.');
        }

        $restored = [];
        DB::transaction(function () use ($payload, &$restored): void {
            foreach ($payload['settings'] as $key => $setting) {
                if (! in_array($key, SystemSettings::keys(), true)) {
                    continue;
                }

                $value = is_array($setting) && array_key_exists('value', $setting)
                    ? $setting['value']
                    : $setting;

                $type = SystemSettings::typeFor($key);
                if ($type === 'boolean') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } elseif ($type === 'integer') {
                    $value = (int) $value;
                }

                Setting::setVal($key, $value, SystemSettings::groupFor($key), $type);
                $restored[] = $key;
            }
        });

        $this->logSettingsActivity($request, 'admin_settings_backup_restored', 'restored a settings backup', $restored);

        return redirect()
            ->route('admin.settings.index')
            ->with('message', count($restored).' setting(s) restored successfully.');
    }

    private function persistRequestSettings(Request $request): array
    {
        $saved = [];

        foreach (SystemSettings::booleanKeys() as $key) {
            Setting::setVal($key, $request->boolean($key), SystemSettings::groupFor($key), 'boolean');
            $saved[] = $key;
        }

        foreach (SystemSettings::keys() as $key) {
            if (in_array($key, SystemSettings::booleanKeys(), true) || ! $request->has($key)) {
                continue;
            }

            $value = SystemSettings::coerceForStorage($key, $request->input($key));
            Setting::setVal($key, $value, SystemSettings::groupFor($key), SystemSettings::typeFor($key));
            $saved[] = $key;
        }

        return array_values(array_unique($saved));
    }

    private function systemInfo(): array
    {
        return [
            'System Version' => config('app.version', '1.0.0'),
            'Laravel Version' => app()->version(),
            'PHP Version' => PHP_VERSION,
            'Database Driver' => config('database.default'),
            'Environment' => app()->environment(),
            'Server Status' => 'Online',
        ];
    }

    private function logSettingsActivity(Request $request, string $action, string $summary, array $keys): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }

        $keySummary = $keys === []
            ? ''
            : ' ('.Str::limit(implode(', ', array_values(array_unique($keys))), 220).')';

        ActivityLogger::log(
            $user,
            $action,
            $user->name.' '.$summary.$keySummary.'.',
            $request->ip(),
            false
        );
    }
}
