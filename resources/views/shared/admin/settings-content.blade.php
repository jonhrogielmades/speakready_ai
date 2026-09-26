@php
    $checked = fn (string $key) => (($settings[$key] ?? 'false') === 'true') ? 'checked' : '';
    $value = fn (string $key, mixed $default = '') => old($key, $settings[$key] ?? $default);
    $cards = [
        ['id' => 'settings-general', 'icon' => 'fa-globe', 'label' => 'General'],
        ['id' => 'settings-account', 'icon' => 'fa-user-gear', 'label' => 'Account'],
        ['id' => 'settings-roles', 'icon' => 'fa-users-gear', 'label' => 'Roles'],
        ['id' => 'settings-interview', 'icon' => 'fa-microphone-lines', 'label' => 'Interview'],
        ['id' => 'settings-ai-coach', 'icon' => 'fa-robot', 'label' => 'AI Coach'],
        ['id' => 'settings-learning', 'icon' => 'fa-flask', 'label' => 'Learning Lab'],
        ['id' => 'settings-notifications', 'icon' => 'fa-bell', 'label' => 'Notifications'],
        ['id' => 'settings-security', 'icon' => 'fa-shield', 'label' => 'Security'],
        ['id' => 'settings-backup', 'icon' => 'fa-database', 'label' => 'Backup'],
        ['id' => 'settings-files', 'icon' => 'fa-folder-open', 'label' => 'Files'],
        ['id' => 'settings-appearance', 'icon' => 'fa-palette', 'label' => 'Appearance'],
        ['id' => 'settings-retention', 'icon' => 'fa-clock-rotate-left', 'label' => 'Retention'],
        ['id' => 'settings-reports', 'icon' => 'fa-chart-pie', 'label' => 'Reports'],
        ['id' => 'settings-audit', 'icon' => 'fa-list-check', 'label' => 'Audit Logs'],
        ['id' => 'settings-info', 'icon' => 'fa-circle-info', 'label' => 'System Info'],
    ];
@endphp

<div class="db-section active admin-settings-shell" id="sec-admin-settings">
    @if(session('message'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <strong>Check these settings:</strong> {{ $errors->first() }}
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 admin-settings-header">
        <div class="admin-settings-heading">
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-sliders me-2"></i>System Settings</h4>
            <p style="font-size:0.95rem;margin:0;">Configure platform behavior, access, learning tools, notifications, and appearance.</p>
        </div>
        <button type="submit" form="systemSettingsForm" class="btn btn-primary settings-save-top admin-settings-save-top">
            <i class="fa-solid fa-floppy-disk me-2"></i>Save All
        </button>
    </div>

    <div class="row g-3 settings-grid admin-settings-jump-grid mb-4">
        @foreach($cards as $card)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="#{{ $card['id'] }}" class="settings-jump">
                    <span class="settings-jump-icon"><i class="fa-solid {{ $card['icon'] }}"></i></span>
                    <span class="settings-jump-label">{{ $card['label'] }}</span>
                    <span class="settings-jump-kicker">Settings</span>
                </a>
            </div>
        @endforeach
    </div>

    <form id="systemSettingsForm" class="admin-settings-form" action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <section class="settings-panel" id="settings-general">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-globe"></i>General</h5>
                <p>Branding, contact details, public copy, and default language.</p>
            </div>
            <div class="row g-3 admin-settings-fields-grid">
                <div class="col-md-6">
                    <label class="form-label">System Name</label>
                    <input type="text" class="form-control" name="sys_name" value="{{ $value('sys_name', 'SpeakReady AI') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Email</label>
                    <input type="email" class="form-control" name="sys_contact_email" value="{{ $value('sys_contact_email', 'support@speakready.ai') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input type="text" class="form-control" name="sys_contact_number" value="{{ $value('sys_contact_number', '+123456789') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Default Language</label>
                    <select class="form-select" name="sys_language">
                        @foreach($supportedLanguages as $languageCode => $language)
                            <option value="{{ $languageCode }}" {{ $value('sys_language', 'en') == $languageCode ? 'selected' : '' }}>{{ $language['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">System Description</label>
                    <textarea class="form-control" name="sys_desc" rows="3">{{ $value('sys_desc', 'SpeakReady AI helps users master communication skills.') }}</textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Footer Text</label>
                    <input type="text" class="form-control" name="sys_footer" value="{{ $value('sys_footer', '&copy; 2026 SpeakReady AI. All Rights Reserved.') }}">
                </div>
            </div>
        </section>

        <section class="settings-panel" id="settings-account">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-user-gear"></i>Account</h5>
                <p>Registration, verified-account checks, and session lifetime.</p>
            </div>
            <div class="custom-switch-container">
                <div>
                    <h6 class="mb-1">Enable User Registration</h6>
                    <small>Allow new users to sign up using password or Google registration.</small>
                </div>
                <div class="form-check form-switch fs-4 mb-0">
                    <input class="form-check-input" type="checkbox" name="acc_registration" value="true" {{ $checked('acc_registration') }}>
                </div>
            </div>
            <div class="custom-switch-container">
                <div>
                    <h6 class="mb-1">Email Verification Required</h6>
                    <small>When enabled, users without a verified email cannot sign in again after logout.</small>
                </div>
                <div class="form-check form-switch fs-4 mb-0">
                    <input class="form-check-input" type="checkbox" name="acc_verify_email" value="true" {{ $checked('acc_verify_email') }}>
                </div>
            </div>
            <div class="row g-3 mt-1 admin-settings-fields-grid">
                <div class="col-md-6">
                    <label class="form-label">Session Timeout (Minutes)</label>
                    <input type="number" class="form-control" name="acc_session_timeout" min="5" max="1440" value="{{ $value('acc_session_timeout', 120) }}">
                </div>
            </div>
        </section>

        <section class="settings-panel" id="settings-roles">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-users-gear"></i>Roles</h5>
                <p>Administrator access stays always on. Candidate permissions are enforced in user routes.</p>
            </div>
            <h6 class="settings-mini-title">Administrator Permissions</h6>
            <div class="row g-2 mb-4 admin-settings-check-grid">
                @foreach(['View', 'Create', 'Edit', 'Delete', 'Export'] as $perm)
                    <div class="col-md-4">
                        <div class="form-check settings-check-row">
                            <input class="form-check-input" type="checkbox" checked disabled>
                            <label class="form-check-label">{{ $perm }} (Always On)</label>
                        </div>
                    </div>
                @endforeach
            </div>
            <h6 class="settings-mini-title">User/Candidate Permissions</h6>
            <div class="row g-2 admin-settings-check-grid">
                @foreach(['View Interview Content', 'Take Interview', 'Delete Own Account', 'Export Reports'] as $i => $perm)
                    <div class="col-md-6">
                        <div class="form-check settings-check-row">
                            <input class="form-check-input" type="checkbox" name="role_user_perm_{{ $i }}" value="true" {{ $checked('role_user_perm_'.$i) }}>
                            <label class="form-check-label">{{ $perm }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="settings-panel" id="settings-interview">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-microphone-lines"></i>Interview</h5>
                <p>Question counts, time limits, follow-up behavior, and AI evaluation.</p>
            </div>
            <div class="row g-3 mb-3 admin-settings-fields-grid">
                <div class="col-md-4">
                    <label class="form-label">Default Number of Questions</label>
                    <select class="form-select" name="int_default_questions">
                        @foreach([1, 3, 5, 10, 15, 20, 25, 30] as $count)
                            <option value="{{ $count }}" {{ (string) $value('int_default_questions', 10) === (string) $count ? 'selected' : '' }}>{{ $count }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Maximum Questions Allowed</label>
                    <select class="form-select" name="int_max_questions">
                        @foreach([1, 3, 5, 10, 15, 20, 25, 30] as $count)
                            <option value="{{ $count }}" {{ (string) $value('int_max_questions', 20) === (string) $count ? 'selected' : '' }}>{{ $count }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Default Time Limit</label>
                    <select class="form-select" name="int_time_limit">
                        <option value="0" {{ (string) $value('int_time_limit', 0) === '0' ? 'selected' : '' }}>No Limit</option>
                        @foreach([1, 2, 3] as $minutes)
                            <option value="{{ $minutes }}" {{ (string) $value('int_time_limit', 0) === (string) $minutes ? 'selected' : '' }}>{{ $minutes }} Minute(s)</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="custom-switch-container">
                <div><h6 class="mb-1">Enable Follow-Up Questions</h6><small>Allow the AI interviewer to ask dynamic follow-up questions.</small></div>
                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="int_follow_up" value="true" {{ $checked('int_follow_up') }}></div>
            </div>
            <div class="custom-switch-container">
                <div><h6 class="mb-1">Enable AI Evaluation</h6><small>Use AI feedback when finalizing interview reports. If disabled, local scoring fallback is used.</small></div>
                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="int_ai_eval" value="true" {{ $checked('int_ai_eval') }}></div>
            </div>
        </section>

        <section class="settings-panel" id="settings-ai-coach">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-robot"></i>AI Coach</h5>
                <p>Enable or limit the user-side readiness coach features.</p>
            </div>
            @foreach([
                'aic_enable' => ['Enable AI Coach', 'Allow users to open and chat with the AI coach.'],
                'aic_sample' => ['Enable Fact-Grounded Revision Guidance', 'Allow improved-answer coaching based on user facts.'],
                'aic_follow' => ['Enable Follow-Up Questions', 'Allow the coach to ask clarifying practice questions.'],
                'aic_recommend' => ['Enable Learning Recommendations', 'Show learning recommendations from coach context.'],
            ] as $key => [$title, $copy])
                <div class="custom-switch-container">
                    <div><h6 class="mb-1">{{ $title }}</h6><small>{{ $copy }}</small></div>
                    <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="{{ $key }}" value="true" {{ $checked($key) }}></div>
                </div>
            @endforeach
        </section>

        <section class="settings-panel" id="settings-learning">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-flask"></i>Learning Lab</h5>
                <p>Control modules, quizzes, certificates, and achievement features.</p>
            </div>
            @foreach([
                'll_modules' => ['Enable Interview Learning Modules', 'Allow users to browse and complete learning modules.'],
                'll_quizzes' => ['Enable Quizzes', 'Allow module quiz/progress submissions.'],
                'll_certs' => ['Enable Certificates', 'Allow challenge certificate downloads.'],
                'll_achievements' => ['Enable Achievements', 'Allow skill perks and achievement unlocks.'],
            ] as $key => [$title, $copy])
                <div class="custom-switch-container">
                    <div><h6 class="mb-1">{{ $title }}</h6><small>{{ $copy }}</small></div>
                    <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="{{ $key }}" value="true" {{ $checked($key) }}></div>
                </div>
            @endforeach
        </section>

        <section class="settings-panel" id="settings-notifications">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-bell"></i>Notifications</h5>
                <p>Control in-app notifications and optional email delivery channels.</p>
            </div>
            @foreach([
                'notif_sys' => ['System Notifications', 'Allow admins to send platform notifications.'],
                'notif_email' => ['Email Notifications', 'Send supported notifications through email too.'],
                'notif_reminders' => ['Interview Reminders', 'Allow reminder-style notification records.'],
                'notif_achieve' => ['Achievement Notifications', 'Allow achievement and completion notifications.'],
            ] as $key => [$title, $copy])
                <div class="custom-switch-container">
                    <div><h6 class="mb-1">{{ $title }}</h6><small>{{ $copy }}</small></div>
                    <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="{{ $key }}" value="true" {{ $checked($key) }}></div>
                </div>
            @endforeach
        </section>

        <section class="settings-panel" id="settings-security">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-shield"></i>Security</h5>
                <p>Password rules, login throttling, and optional two-factor availability flag.</p>
            </div>
            <div class="custom-switch-container">
                <div><h6 class="mb-1">Require Strong Passwords</h6><small>Require mixed case letters, numbers, and symbols on new password changes.</small></div>
                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="sec_strong_pass" value="true" {{ $checked('sec_strong_pass') }}></div>
            </div>
            <div class="custom-switch-container">
                <div><h6 class="mb-1">Two-Factor Authentication Flag</h6><small>Stores the platform preference for future or connected 2FA flows.</small></div>
                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="sec_2fa" value="true" {{ $checked('sec_2fa') }}></div>
            </div>
            <div class="row g-3 mt-1 admin-settings-fields-grid">
                <div class="col-md-6">
                    <label class="form-label">Login Attempt Limit</label>
                    <input type="number" class="form-control" name="sec_login_limit" min="1" max="20" value="{{ $value('sec_login_limit', 5) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Lockout Duration (Minutes)</label>
                    <input type="number" class="form-control" name="sec_lockout" min="1" max="1440" value="{{ $value('sec_lockout', 15) }}">
                </div>
            </div>
        </section>

        <section class="settings-panel" id="settings-backup">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-database"></i>Backup</h5>
                <p>Export or restore a settings backup and choose the saved backup schedule preference.</p>
            </div>
            <div class="row g-3 align-items-end admin-settings-fields-grid admin-settings-backup-grid">
                <div class="col-md-4">
                    <label class="form-label">Automatic Backup Schedule</label>
                    <select class="form-select" name="backup_schedule">
                        @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'never' => 'Never'] as $key => $label)
                            <option value="{{ $key }}" {{ $value('backup_schedule', 'never') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Restore Settings Backup</label>
                    <input type="file" class="form-control" name="settings_backup_file" accept=".json,application/json">
                </div>
                <div class="col-md-4 d-flex gap-2 flex-wrap admin-settings-backup-actions">
                    <button type="submit" class="btn btn-outline-light admin-settings-backup-btn" formaction="{{ route('admin.settings.backup') }}">
                        <i class="fa-solid fa-download me-2"></i>Download
                    </button>
                    <button type="submit" class="btn btn-outline-warning admin-settings-backup-btn" formaction="{{ route('admin.settings.restore') }}">
                        <i class="fa-solid fa-upload me-2"></i>Restore
                    </button>
                </div>
            </div>
        </section>

        <section class="settings-panel" id="settings-files">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-folder-open"></i>Files</h5>
                <p>File limits applied to user coach attachments and uploads that use system settings.</p>
            </div>
            <div class="row g-3 admin-settings-fields-grid">
                <div class="col-md-4">
                    <label class="form-label">Maximum Upload Size (MB)</label>
                    <input type="number" class="form-control" name="file_max_size" min="1" max="100" value="{{ $value('file_max_size', 10) }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Allowed File Types</label>
                    <input type="text" class="form-control" name="file_types" value="{{ $value('file_types', 'PDF, DOCX, PPTX, PNG, JPG') }}">
                    <small>Comma separated extensions.</small>
                </div>
            </div>
        </section>

        <section class="settings-panel" id="settings-appearance">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-palette"></i>Appearance</h5>
                <p>Logo, favicon, and accent colors shared with layouts.</p>
            </div>
            <div class="row g-3 admin-settings-fields-grid">
                <div class="col-md-6">
                    <label class="form-label">Upload System Logo</label>
                    <input type="file" class="form-control" name="system_logo" accept="image/*">
                    @if(!empty($settings['system_logo']))
                        <img src="{{ asset($settings['system_logo']) }}" class="mt-2 settings-logo-preview" alt="Current system logo">
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload Favicon</label>
                    <input type="file" class="form-control" name="system_favicon" accept="image/*,.ico">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Primary Color</label>
                    <input type="color" class="form-control form-control-color" name="color_primary" value="{{ $value('color_primary', '#3b82f6') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Secondary Color</label>
                    <input type="color" class="form-control form-control-color" name="color_secondary" value="{{ $value('color_secondary', '#34d399') }}">
                </div>
            </div>
        </section>

        <section class="settings-panel" id="settings-retention">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-clock-rotate-left"></i>Retention</h5>
                <p>Retention values are saved for cleanup jobs and reporting policy display.</p>
            </div>
            <div class="row g-3 admin-settings-fields-grid">
                <div class="col-md-4">
                    <label class="form-label">Interview Data Retention (Days)</label>
                    <input type="number" class="form-control" name="retention_interview" min="1" max="3650" value="{{ $value('retention_interview', 365) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Feedback Retention (Days)</label>
                    <input type="number" class="form-control" name="retention_feedback" min="1" max="3650" value="{{ $value('retention_feedback', 365) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Archive Duration (Days)</label>
                    <input type="number" class="form-control" name="retention_archive" min="1" max="3650" value="{{ $value('retention_archive', 730) }}">
                </div>
            </div>
        </section>

        <section class="settings-panel" id="settings-reports">
            <div class="settings-panel-head">
                <h5><i class="fa-solid fa-chart-pie"></i>Reports</h5>
                <p>Report header, footer, logo preference, and approval label.</p>
            </div>
            <div class="row g-3 admin-settings-fields-grid">
                <div class="col-md-6">
                    <label class="form-label">Report Header Text</label>
                    <input type="text" class="form-control" name="rep_header" value="{{ $value('rep_header', 'SpeakReady AI Official Report') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Report Footer Text</label>
                    <input type="text" class="form-control" name="rep_footer" value="{{ $value('rep_footer', 'Generated by SpeakReady AI System.') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Include System Logo</label>
                    <select class="form-select" name="rep_logo">
                        <option value="yes" {{ $value('rep_logo', 'yes') === 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ $value('rep_logo', 'yes') === 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Signature Area Title</label>
                    <input type="text" class="form-control" name="rep_signature" value="{{ $value('rep_signature', 'Approved By') }}">
                </div>
            </div>
        </section>

        <div class="btn-save-fixed admin-settings-save-bar mt-4">
            <span>Save all functional settings changes.</span>
            <button type="submit" class="btn btn-primary px-4 admin-settings-save-bottom"><i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings</button>
        </div>
    </form>

    <section class="settings-panel" id="settings-audit">
        <div class="settings-panel-head">
            <h5><i class="fa-solid fa-list-check"></i>Audit Logs</h5>
            <p>Recent real activity records from the admin audit feed.</p>
        </div>
        <div class="table-responsive" id="mainAuditLogsTableWrapper">
            <table class="table table-dark table-hover" id="mainAuditLogsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $activity)
                        <tr>
                            <td data-label="Date">{{ optional($activity->created_at)->format('Y-m-d H:i') }}</td>
                            <td data-label="Action">{{ $activity->description ?: ucwords(str_replace('_', ' ', $activity->action)) }}</td>
                            <td data-label="User">{{ $activity->user?->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted admin-settings-empty-cell">No audit activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="settings-panel" id="settings-info">
        <div class="settings-panel-head">
            <h5><i class="fa-solid fa-circle-info"></i>System Info</h5>
            <p>Runtime details detected from the current application environment.</p>
        </div>
        <ul class="list-group list-group-flush rounded admin-settings-info-list">
            @foreach($systemInfo as $label => $info)
                <li class="list-group-item d-flex justify-content-between align-items-center admin-settings-info-item">
                    <span>{{ $label }}</span>
                    <span class="{{ $label === 'Server Status' ? 'text-success' : 'text-muted' }}">{{ $info }}</span>
                </li>
            @endforeach
        </ul>
    </section>
</div>
