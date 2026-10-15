@php
    $checked = fn (string $key) => (($settings[$key] ?? 'false') === 'true') ? 'checked' : '';
    $value = fn (string $key, mixed $default = '') => old($key, $settings[$key] ?? $default);
    $cards = [
        ['id' => 'settings-general', 'icon' => 'fa-globe', 'label' => 'General'],
        ['id' => 'settings-account', 'icon' => 'fa-user-gear', 'label' => 'Account'],
        ['id' => 'settings-roles', 'icon' => 'fa-users-gear', 'label' => 'Roles'],
        ['id' => 'settings-interview', 'icon' => 'fa-microphone-lines', 'label' => 'Interview'],
        ['id' => 'settings-ai-coach', 'icon' => 'fa-robot', 'label' => 'Interview Coach'],
        ['id' => 'settings-learning', 'icon' => 'fa-briefcase', 'label' => 'Interview Prep'],
        ['id' => 'settings-notifications', 'icon' => 'fa-bell', 'label' => 'Notifications'],
        ['id' => 'settings-security', 'icon' => 'fa-shield', 'label' => 'Security'],
        ['id' => 'settings-files', 'icon' => 'fa-folder-open', 'label' => 'Files'],
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
            <p style="font-size:0.95rem;margin:0;">Configure platform behavior, access, learning tools, notifications, and uploads.</p>
        </div>
        <button type="submit" form="systemSettingsForm" class="btn btn-primary settings-save-top admin-settings-save-top">
            <i class="fa-solid fa-floppy-disk me-2"></i>Save All
        </button>
    </div>

    <div class="row g-3 settings-grid admin-settings-jump-grid mb-4">
        @foreach($cards as $card)
            <div class="col-6 col-md-4 col-lg-3">
                <button type="button" class="settings-jump" data-bs-toggle="modal" data-bs-target="#{{ $card['id'] }}" aria-controls="{{ $card['id'] }}">
                    <span class="settings-jump-icon"><i class="fa-solid {{ $card['icon'] }}"></i></span>
                    <span class="settings-jump-label">{{ $card['label'] }}</span>
                    <span class="settings-jump-kicker">Settings</span>
                </button>
            </div>
        @endforeach
    </div>

    <form id="systemSettingsForm" class="admin-settings-form" action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <section class="modal fade settings-modal" id="settings-general" tabindex="-1" aria-labelledby="settings-general-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content settings-modal-content">
                    <div class="modal-header settings-panel-head">
                        <div class="settings-modal-heading">
                            <h5 id="settings-general-title"><i class="fa-solid fa-globe"></i>General</h5>
                            <p>Branding, contact details, public copy, and default language.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body settings-modal-body">
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
                    <textarea class="form-control" name="sys_desc" rows="3">{{ $value('sys_desc', 'SpeakReady AI helps users prepare for job interviews with realistic practice, coaching, and feedback.') }}</textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Footer Text</label>
                    <input type="text" class="form-control" name="sys_footer" value="{{ $value('sys_footer', '&copy; 2026 SpeakReady AI. All Rights Reserved.') }}">
                </div>
            </div>
                    </div>
                    <div class="modal-footer settings-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="modal fade settings-modal" id="settings-account" tabindex="-1" aria-labelledby="settings-account-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content settings-modal-content">
                    <div class="modal-header settings-panel-head">
                        <div class="settings-modal-heading">
                            <h5 id="settings-account-title"><i class="fa-solid fa-user-gear"></i>Account</h5>
                            <p>Registration, verified-account checks, and session lifetime.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body settings-modal-body">
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
                    </div>
                    <div class="modal-footer settings-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="modal fade settings-modal" id="settings-roles" tabindex="-1" aria-labelledby="settings-roles-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content settings-modal-content">
                    <div class="modal-header settings-panel-head">
                        <div class="settings-modal-heading">
                            <h5 id="settings-roles-title"><i class="fa-solid fa-users-gear"></i>Roles</h5>
                            <p>Administrator access stays always on. Candidate permissions are enforced in user routes.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body settings-modal-body">
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
                    </div>
                    <div class="modal-footer settings-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="modal fade settings-modal" id="settings-interview" tabindex="-1" aria-labelledby="settings-interview-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content settings-modal-content">
                    <div class="modal-header settings-panel-head">
                        <div class="settings-modal-heading">
                            <h5 id="settings-interview-title"><i class="fa-solid fa-microphone-lines"></i>Interview</h5>
                            <p>Question counts, time limits, follow-up behavior, and AI evaluation.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body settings-modal-body">
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
                    </div>
                    <div class="modal-footer settings-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="modal fade settings-modal" id="settings-ai-coach" tabindex="-1" aria-labelledby="settings-ai-coach-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content settings-modal-content">
                    <div class="modal-header settings-panel-head">
                        <div class="settings-modal-heading">
                            <h5 id="settings-ai-coach-title"><i class="fa-solid fa-robot"></i>Interview Coach</h5>
                            <p>Enable or limit the user-side job interview coach features.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body settings-modal-body">
            @foreach([
                'aic_enable' => ['Enable Interview Coach', 'Allow users to open and chat with the job interview coach.'],
                'aic_sample' => ['Enable Fact-Grounded Revision Guidance', 'Allow improved-answer coaching based on user facts.'],
                'aic_follow' => ['Enable Follow-Up Questions', 'Allow the coach to ask clarifying practice questions.'],
                'aic_recommend' => ['Enable Interview Prep Recommendations', 'Show interview-prep recommendations from coach context.'],
            ] as $key => [$title, $copy])
                <div class="custom-switch-container">
                    <div><h6 class="mb-1">{{ $title }}</h6><small>{{ $copy }}</small></div>
                    <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="{{ $key }}" value="true" {{ $checked($key) }}></div>
                </div>
            @endforeach
                    </div>
                    <div class="modal-footer settings-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="modal fade settings-modal" id="settings-learning" tabindex="-1" aria-labelledby="settings-learning-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content settings-modal-content">
                    <div class="modal-header settings-panel-head">
                        <div class="settings-modal-heading">
                            <h5 id="settings-learning-title"><i class="fa-solid fa-briefcase"></i>Interview Prep Tools</h5>
                            <p>Control optional interview-prep tools, certificates, and achievement features.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body settings-modal-body">
            @foreach([
                'll_modules' => ['Enable Interview Prep Modules', 'Allow users to browse and complete job interview preparation modules.'],
                'll_certs' => ['Enable Interview Certificates', 'Allow interview challenge certificate downloads.'],
                'll_achievements' => ['Enable Interview Achievements', 'Allow interview-focused perks and achievement unlocks.'],
            ] as $key => [$title, $copy])
                <div class="custom-switch-container">
                    <div><h6 class="mb-1">{{ $title }}</h6><small>{{ $copy }}</small></div>
                    <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="{{ $key }}" value="true" {{ $checked($key) }}></div>
                </div>
            @endforeach
                    </div>
                    <div class="modal-footer settings-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="modal fade settings-modal" id="settings-notifications" tabindex="-1" aria-labelledby="settings-notifications-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content settings-modal-content">
                    <div class="modal-header settings-panel-head">
                        <div class="settings-modal-heading">
                            <h5 id="settings-notifications-title"><i class="fa-solid fa-bell"></i>Notifications</h5>
                            <p>Control in-app notifications and optional email delivery channels.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body settings-modal-body">
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
                    </div>
                    <div class="modal-footer settings-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="modal fade settings-modal" id="settings-security" tabindex="-1" aria-labelledby="settings-security-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content settings-modal-content">
                    <div class="modal-header settings-panel-head">
                        <div class="settings-modal-heading">
                            <h5 id="settings-security-title"><i class="fa-solid fa-shield"></i>Security</h5>
                            <p>Password rules, login throttling, and optional two-factor availability flag.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body settings-modal-body">
            <div class="custom-switch-container">
                <div><h6 class="mb-1">Require Strong Passwords</h6><small>Require mixed case letters, numbers, and symbols on new password changes.</small></div>
                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="sec_strong_pass" value="true" {{ $checked('sec_strong_pass') }}></div>
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
                    </div>
                    <div class="modal-footer settings-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="modal fade settings-modal" id="settings-files" tabindex="-1" aria-labelledby="settings-files-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content settings-modal-content">
                    <div class="modal-header settings-panel-head">
                        <div class="settings-modal-heading">
                            <h5 id="settings-files-title"><i class="fa-solid fa-folder-open"></i>Files</h5>
                            <p>File limits applied to user coach attachments and uploads that use system settings.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body settings-modal-body">
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
                    </div>
                    <div class="modal-footer settings-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings</button>
                    </div>
                </div>
            </div>
        </section>

    </form>

    <section class="modal fade settings-modal" id="settings-info" tabindex="-1" aria-labelledby="settings-info-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content settings-modal-content">
                <div class="modal-header settings-panel-head">
                    <div class="settings-modal-heading">
                        <h5 id="settings-info-title"><i class="fa-solid fa-circle-info"></i>System Info</h5>
                        <p>Runtime details detected from the current application environment.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body settings-modal-body">
                    <ul class="list-group list-group-flush rounded admin-settings-info-list">
                        @foreach($systemInfo as $label => $info)
                            <li class="list-group-item d-flex justify-content-between align-items-center admin-settings-info-item">
                                <span>{{ $label }}</span>
                                <span class="{{ $label === 'Server Status' ? 'text-success' : 'text-muted' }}">{{ $info }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="modal-footer settings-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </section>
</div>
