<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Support\AccountNotificationSchema;
use App\Support\PasswordRecovery;
use App\Support\SystemSettings;
use App\Services\ProfilePhotoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AdminAccountController extends Controller
{
    public function edit()
    {
        AccountNotificationSchema::ensure();

        return $this->mobileView('admin.account', [
            'admin' => request()->user(),
        ]);
    }

    public function updateProfile(Request $request, ProfilePhotoStorage $photoStorage)
    {
        AccountNotificationSchema::ensure();

        $admin = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $admin->isLocalAccount()
                ? ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)]
                : ['required', 'string', 'email', 'max:255', Rule::in([(string) $admin->email])],
            'target_position' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
        ]);

        $admin->name = $validated['name'];
        if ($admin->isLocalAccount()) {
            $admin->email = $validated['email'];
        }
        $admin->target_position = $validated['target_position'] ?? null;

        $previousPhoto = $admin->profile_photo_path;
        $newPhotoPath = null;

        if ($request->hasFile('profile_photo')) {
            $image = $request->file('profile_photo');
            $newPhotoPath = $photoStorage->store($image);
            $admin->profile_photo_path = $newPhotoPath;
        }

        try {
            $admin->save();
        } catch (\Throwable $error) {
            if ($newPhotoPath) {
                $photoStorage->delete($newPhotoPath);
            }

            throw $error;
        }

        if ($newPhotoPath && $previousPhoto !== $newPhotoPath) {
            $photoStorage->delete($previousPhoto);
        }

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

        $admin = $request->user();

        if (! $admin->isLocalAccount()) {
            return redirect()->route('admin.account')->withErrors([
                'current_password' => 'Google Login accounts do not have a local password.',
            ]);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => $this->newPasswordRules(),
            'confirm_password' => ['required', 'same:new_password'],
        ]);

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

    public function rotateRecoveryCode(Request $request)
    {
        $admin = $request->user();

        if (! $admin->isLocalAccount()) {
            return redirect()->route('admin.account')->withErrors([
                'recovery_code' => 'Google Login accounts do not use local password recovery codes.',
            ]);
        }

        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $recoveryCode = PasswordRecovery::generate();

        $admin->forceFill([
            'password_recovery_code_hash' => PasswordRecovery::hash($recoveryCode),
            'password_recovery_code_generated_at' => now(),
            'password_recovery_code_expires_at' => null,
        ])->save();

        ActivityLogger::log(
            $admin,
            'admin_password_recovery_code_rotated',
            "{$admin->name} generated a new password recovery code.",
            $request->ip(),
            false
        );

        return redirect()->route('admin.account')
            ->with('success', 'A new recovery code was generated. Save it somewhere safe; the old code no longer works.')
            ->with('recovery_code', $recoveryCode);
    }

    private function newPasswordRules(): array
    {
        $rule = PasswordRule::min(8);

        if (SystemSettings::enabled('sec_strong_pass', false)) {
            $rule = $rule->mixedCase()->numbers()->symbols();
        }

        return ['required', 'string', $rule];
    }
}
