<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Validation\Rule;

class SystemSettings
{
    public const USER_PERMISSION_KEYS = [
        'view_interview_content' => 'role_user_perm_0',
        'take_interview' => 'role_user_perm_1',
        'delete_own_account' => 'role_user_perm_2',
        'export_reports' => 'role_user_perm_3',
    ];

    private const DEFINITIONS = [
        'sys_name' => ['value' => 'SpeakReady AI', 'group' => 'general', 'type' => 'string'],
        'sys_contact_email' => ['value' => 'support@speakready.ai', 'group' => 'general', 'type' => 'string'],
        'sys_contact_number' => ['value' => '+123456789', 'group' => 'general', 'type' => 'string'],
        'sys_desc' => ['value' => 'SpeakReady AI helps users master communication skills.', 'group' => 'general', 'type' => 'string'],
        'sys_footer' => ['value' => '&copy; 2026 SpeakReady AI. All Rights Reserved.', 'group' => 'general', 'type' => 'string'],
        'sys_language' => ['value' => 'en', 'group' => 'general', 'type' => 'string'],

        'acc_registration' => ['value' => true, 'group' => 'account', 'type' => 'boolean'],
        'acc_verify_email' => ['value' => false, 'group' => 'account', 'type' => 'boolean'],
        'acc_session_timeout' => ['value' => 120, 'group' => 'account', 'type' => 'integer'],

        'role_user_perm_0' => ['value' => true, 'group' => 'roles', 'type' => 'boolean'],
        'role_user_perm_1' => ['value' => true, 'group' => 'roles', 'type' => 'boolean'],
        'role_user_perm_2' => ['value' => true, 'group' => 'roles', 'type' => 'boolean'],
        'role_user_perm_3' => ['value' => true, 'group' => 'roles', 'type' => 'boolean'],

        'int_default_questions' => ['value' => 10, 'group' => 'interview', 'type' => 'integer'],
        'int_max_questions' => ['value' => 20, 'group' => 'interview', 'type' => 'integer'],
        'int_time_limit' => ['value' => 0, 'group' => 'interview', 'type' => 'integer'],
        'int_follow_up' => ['value' => true, 'group' => 'interview', 'type' => 'boolean'],
        'int_ai_eval' => ['value' => true, 'group' => 'interview', 'type' => 'boolean'],

        'aic_enable' => ['value' => true, 'group' => 'ai_coach', 'type' => 'boolean'],
        'aic_sample' => ['value' => true, 'group' => 'ai_coach', 'type' => 'boolean'],
        'aic_follow' => ['value' => true, 'group' => 'ai_coach', 'type' => 'boolean'],
        'aic_recommend' => ['value' => true, 'group' => 'ai_coach', 'type' => 'boolean'],

        'll_modules' => ['value' => true, 'group' => 'learning_lab', 'type' => 'boolean'],
        'll_quizzes' => ['value' => true, 'group' => 'learning_lab', 'type' => 'boolean'],
        'll_certs' => ['value' => true, 'group' => 'learning_lab', 'type' => 'boolean'],
        'll_achievements' => ['value' => true, 'group' => 'learning_lab', 'type' => 'boolean'],

        'notif_sys' => ['value' => true, 'group' => 'notifications', 'type' => 'boolean'],
        'notif_email' => ['value' => false, 'group' => 'notifications', 'type' => 'boolean'],
        'notif_reminders' => ['value' => true, 'group' => 'notifications', 'type' => 'boolean'],
        'notif_achieve' => ['value' => true, 'group' => 'notifications', 'type' => 'boolean'],

        'mail_host' => ['value' => '', 'group' => 'email', 'type' => 'string'],
        'mail_port' => ['value' => '', 'group' => 'email', 'type' => 'string'],
        'mail_user' => ['value' => '', 'group' => 'email', 'type' => 'string'],
        'mail_pass' => ['value' => '', 'group' => 'email', 'type' => 'string'],

        'sec_strong_pass' => ['value' => false, 'group' => 'security', 'type' => 'boolean'],
        'sec_2fa' => ['value' => false, 'group' => 'security', 'type' => 'boolean'],
        'sec_login_limit' => ['value' => 5, 'group' => 'security', 'type' => 'integer'],
        'sec_lockout' => ['value' => 15, 'group' => 'security', 'type' => 'integer'],

        'backup_schedule' => ['value' => 'never', 'group' => 'backup', 'type' => 'string'],
        'file_max_size' => ['value' => 10, 'group' => 'files', 'type' => 'integer'],
        'file_types' => ['value' => 'PDF, DOCX, PPTX, PNG, JPG', 'group' => 'files', 'type' => 'string'],

        'system_logo' => ['value' => 'img/logo.png', 'group' => 'appearance', 'type' => 'string'],
        'system_favicon' => ['value' => 'favicon.ico', 'group' => 'appearance', 'type' => 'string'],
        'color_primary' => ['value' => '#3b82f6', 'group' => 'appearance', 'type' => 'string'],
        'color_secondary' => ['value' => '#34d399', 'group' => 'appearance', 'type' => 'string'],

        'retention_interview' => ['value' => 365, 'group' => 'retention', 'type' => 'integer'],
        'retention_feedback' => ['value' => 365, 'group' => 'retention', 'type' => 'integer'],
        'retention_archive' => ['value' => 730, 'group' => 'retention', 'type' => 'integer'],

        'rep_header' => ['value' => 'SpeakReady AI Official Report', 'group' => 'reports', 'type' => 'string'],
        'rep_footer' => ['value' => 'Generated by SpeakReady AI System.', 'group' => 'reports', 'type' => 'string'],
        'rep_logo' => ['value' => 'yes', 'group' => 'reports', 'type' => 'string'],
        'rep_signature' => ['value' => 'Approved By', 'group' => 'reports', 'type' => 'string'],
    ];

    public static function definitions(): array
    {
        return self::DEFINITIONS;
    }

    public static function keys(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    public static function booleanKeys(): array
    {
        return array_keys(array_filter(
            self::DEFINITIONS,
            static fn (array $definition): bool => $definition['type'] === 'boolean'
        ));
    }

    public static function typeFor(string $key): string
    {
        return self::DEFINITIONS[$key]['type'] ?? 'string';
    }

    public static function groupFor(string $key): string
    {
        return self::DEFINITIONS[$key]['group'] ?? 'general';
    }

    public static function default(string $key, mixed $fallback = null): mixed
    {
        return self::DEFINITIONS[$key]['value'] ?? $fallback;
    }

    public static function value(string $key, mixed $fallback = null): mixed
    {
        try {
            return Setting::getVal($key, self::default($key, $fallback));
        } catch (\Throwable) {
            return self::default($key, $fallback);
        }
    }

    public static function enabled(string $key, ?bool $fallback = null): bool
    {
        try {
            return (bool) Setting::getVal($key, $fallback ?? (bool) self::default($key, true));
        } catch (\Throwable) {
            return $fallback ?? (bool) self::default($key, true);
        }
    }

    public static function userCan(string $permission, bool $default = true): bool
    {
        $key = self::USER_PERMISSION_KEYS[$permission] ?? $permission;

        return self::enabled($key, $default);
    }

    public static function forView(): array
    {
        $settings = [];

        foreach (self::DEFINITIONS as $key => $definition) {
            try {
                $value = Setting::getVal($key, $definition['value']);
            } catch (\Throwable) {
                $value = $definition['value'];
            }
            $settings[$key] = $definition['type'] === 'boolean'
                ? ($value ? 'true' : 'false')
                : (string) $value;
        }

        return $settings;
    }

    public static function validationRules(): array
    {
        return [
            'sys_name' => ['nullable', 'string', 'max:120'],
            'sys_contact_email' => ['nullable', 'email', 'max:255'],
            'sys_contact_number' => ['nullable', 'string', 'max:60'],
            'sys_desc' => ['nullable', 'string', 'max:1000'],
            'sys_footer' => ['nullable', 'string', 'max:255'],
            'sys_language' => ['nullable', 'string', Rule::in(array_keys(Setting::supportedLanguages()))],

            'acc_session_timeout' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'int_default_questions' => ['nullable', 'integer', Rule::in([1, 3, 5, 10, 15, 20, 25, 30])],
            'int_max_questions' => ['nullable', 'integer', Rule::in([1, 3, 5, 10, 15, 20, 25, 30])],
            'int_time_limit' => ['nullable', 'integer', Rule::in([0, 1, 2, 3])],

            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_user' => ['nullable', 'string', 'max:255'],
            'mail_pass' => ['nullable', 'string', 'max:255'],

            'sec_login_limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'sec_lockout' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'backup_schedule' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'never'])],
            'file_max_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'file_types' => ['nullable', 'string', 'max:255'],

            'color_primary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_secondary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'retention_interview' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'retention_feedback' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'retention_archive' => ['nullable', 'integer', 'min:1', 'max:3650'],

            'rep_header' => ['nullable', 'string', 'max:255'],
            'rep_footer' => ['nullable', 'string', 'max:255'],
            'rep_logo' => ['nullable', Rule::in(['yes', 'no'])],
            'rep_signature' => ['nullable', 'string', 'max:120'],
        ];
    }

    public static function coerceForStorage(string $key, mixed $value): mixed
    {
        return match (self::typeFor($key)) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            default => is_string($value) ? trim($value) : $value,
        };
    }

    public static function uploadMaxKilobytes(): int
    {
        return max(1, (int) self::value('file_max_size', 10)) * 1024;
    }

    public static function allowedUploadExtensions(): array
    {
        $raw = (string) self::value('file_types', self::default('file_types'));

        return collect(preg_split('/[,\s]+/', $raw) ?: [])
            ->map(fn (string $extension): string => strtolower(trim($extension, " .\t\n\r\0\x0B")))
            ->filter(fn (string $extension): bool => $extension !== '' && preg_match('/^[a-z0-9]+$/', $extension))
            ->unique()
            ->values()
            ->all();
    }
}
