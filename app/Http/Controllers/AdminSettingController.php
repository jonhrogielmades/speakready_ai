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
        $settings = Setting::pluck('value', 'key')->toArray();

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

        // Validate request depending on what's submitted
        $data = $request->except(['_token', '_method', 'system_logo', 'system_favicon']);

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
