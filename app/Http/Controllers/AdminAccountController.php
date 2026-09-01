<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Support\AccountNotificationSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminAccountController extends Controller
{
    public function edit()
    {
        AccountNotificationSchema::ensure();

        return $this->mobileView('admin.account', [
            'admin' => request()->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        AccountNotificationSchema::ensure();

        $admin = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'target_position' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $admin->name = $validated['name'];
        $admin->email = $validated['email'];
        $admin->target_position = $validated['target_position'] ?? null;

        if ($request->hasFile('profile_photo')) {
            $image = $request->file('profile_photo');
            $imageData = base64_encode(file_get_contents($image->getRealPath()));
            $admin->profile_photo_path = 'data:'.$image->getClientMimeType().';base64,'.$imageData;
        }

        $admin->save();

        ActivityLogger::log(
            $admin,
            'admin_profile_updated',
            "{$admin->name} updated administrator account information.",
            $request->ip(),
            true,
            [
                'title' => 'Admin Profile Updated',
                'message' => 'Your administrator profile information was updated.',
                'icon' => 'fa-user-shield',
                'type' => 'success',
            ]
        );

        return redirect()->route('admin.account')->with('success', 'Admin account updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        AccountNotificationSchema::ensure();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        $admin = $request->user();
        $admin->password = Hash::make($validated['new_password']);
        $admin->save();

        ActivityLogger::log(
            $admin,
            'admin_password_changed',
            "{$admin->name} changed their administrator account password.",
            $request->ip(),
            true,
            [
                'title' => 'Admin Password Changed',
                'message' => 'Your administrator password was recently changed.',
                'icon' => 'fa-lock',
                'type' => 'warning',
            ]
        );

        return redirect()->route('admin.account')->with('success', 'Admin password updated successfully.');
    }
}
