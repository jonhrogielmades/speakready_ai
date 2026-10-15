<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Support\SystemSettings;
use Illuminate\Support\Str;

class AdminSettingController extends Controller
{
    /**
     * Display the system settings.
     */
    public function index()
    {
        $settings = SystemSettings::forView();
        $systemInfo = $this->systemInfo();

        return $this->mobileView('admin.settings', compact('settings', 'systemInfo'));
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
