<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class AdminSettingController extends Controller
{
    /**
     * Display the system settings.
     */
    public function index()
    {
        // Fetch all settings and pluck into key => value array for easy access in view
        $settings = Setting::all()
            ->mapWithKeys(function (Setting $setting) {
                if ($setting->type === 'boolean') {
                    return [$setting->key => Setting::getVal($setting->key) ? 'true' : 'false'];
                }

                return [$setting->key => $setting->value];
            })
            ->toArray();

        return view('admin.settings', compact('settings'));
    }

    /**
     * Update the system settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'system_logo' => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'system_favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg,webp,svg|max:1024',
        ]);

        $booleanKeys = [
            'acc_registration',
            'acc_verify_email',
            'int_follow_up',
            'int_ai_eval',
            'vr_recording',
            'vr_stt',
            'vr_confidence',
            'vr_filler',
            'aic_enable',
            'aic_sample',
            'aic_follow',
            'aic_recommend',
            'll_modules',
            'll_quizzes',
            'll_certs',
            'll_achievements',
            'notif_sys',
            'notif_email',
            'notif_reminders',
            'notif_achieve',
            'sec_strong_pass',
            'sec_2fa',
        ];

        foreach (range(0, 3) as $index) {
            $booleanKeys[] = "role_user_perm_{$index}";
        }

        // Validate request depending on what's submitted
        $data = $request->except(['_token', '_method', 'system_logo', 'system_favicon']);

        foreach ($booleanKeys as $key) {
            $data[$key] = $request->boolean($key) ? 'true' : 'false';
        }

        foreach ($data as $key => $value) {
            // Group and Type can be determined by the input key prefix or we just default them
            $group = 'general';
            $type = 'string';

            if (is_array($value)) {
                $type = 'json';
            } elseif ($value === 'true' || $value === 'false') {
                $type = 'boolean';
                $value = $value === 'true';
            }

            Setting::setVal($key, $value, $group, $type);
        }

        // Handle file uploads separately if any
        if ($request->hasFile('system_logo')) {
            $logo = $request->file('system_logo');
            $logoName = 'logo.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('img'), $logoName);
            Setting::setVal('system_logo', 'img/' . $logoName, 'appearance', 'string');
        }

        if ($request->hasFile('system_favicon')) {
            $favicon = $request->file('system_favicon');
            $faviconName = 'favicon.' . $favicon->getClientOriginalExtension();
            $favicon->move(public_path('img'), $faviconName);
            Setting::setVal('system_favicon', 'img/' . $faviconName, 'appearance', 'string');
        }

        return redirect()->route('admin.settings.index')->with('message', 'Settings updated successfully.');
    }
}
