@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<style>
    /* Admin Premium Styles */
    .premium-card {
        background: var(--sf, #1e1e2d);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    
    .settings-nav .nav-link {
        color: var(--tx2, #a0a0b0);
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 8px;
        font-weight: 500;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .settings-nav .nav-link:hover {
        background: rgba(255, 255, 255, 0.05);
        color: var(--tx, #e0e0e0);
    }
    .settings-nav .nav-link.active {
        background: var(--pur, #3b82f6);
        color: #fff;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
    }
    
    .form-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--tx2, #a0a0b0);
        margin-bottom: 8px;
    }
    .form-control, .form-select {
        background: var(--bg3, #2b2b40);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        color: var(--tx, #e0e0e0);
        border-radius: 10px;
        padding: 12px;
        transition: all 0.3s;
    }
    .form-control:focus, .form-select:focus {
        background: var(--sf, #1e1e2d);
        border-color: var(--pur, #3b82f6);
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        color: var(--tx, #e0e0e0);
    }
    .form-check-input {
        background-color: var(--bg3, #2b2b40);
        border-color: var(--bd, rgba(255, 255, 255, 0.2));
    }
    .form-check-input:checked {
        background-color: var(--pur, #3b82f6);
        border-color: var(--pur, #3b82f6);
    }
    .form-check-label {
        color: var(--tx, #e0e0e0);
        font-weight: 500;
        cursor: pointer;
    }
    .custom-switch-container {
        padding: 16px;
        border-radius: 12px;
        background: var(--bg3, #2b2b40);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .btn-save-fixed {
        position: sticky;
        bottom: 20px;
        z-index: 100;
        background: var(--sf, #1e1e2d);
        padding: 16px 24px;
        border-radius: 16px;
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>

<div class="db-section active">
    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-sliders me-2" style="color:#3b82f6;"></i>System Settings</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Configure platform behavior, features, and appearance.</p>
        </div>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-4 position-relative">
            <!-- Sidebar Navigation -->
            <div class="col-lg-3 col-xl-2">
                <div class="nav flex-column nav-pills settings-nav" id="settings-tab" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#tab-1" type="button"><i class="fa-solid fa-globe fa-fw"></i> General</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-2" type="button"><i class="fa-solid fa-user-gear fa-fw"></i> Account</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-3" type="button"><i class="fa-solid fa-users-gear fa-fw"></i> Roles</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-4" type="button"><i class="fa-solid fa-microphone-lines fa-fw"></i> Interview</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-5" type="button"><i class="fa-solid fa-podcast fa-fw"></i> Voice Rehearsal</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-6" type="button"><i class="fa-solid fa-robot fa-fw"></i> AI Coach</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-7" type="button"><i class="fa-solid fa-flask fa-fw"></i> Learning Lab</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-8" type="button"><i class="fa-solid fa-star fa-fw"></i> Readiness Score</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-9" type="button"><i class="fa-solid fa-bell fa-fw"></i> Notifications</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-10" type="button"><i class="fa-solid fa-envelope fa-fw"></i> Email</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-11" type="button"><i class="fa-solid fa-shield-halved fa-fw"></i> Security</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-12" type="button"><i class="fa-solid fa-database fa-fw"></i> Backup</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-13" type="button"><i class="fa-solid fa-folder-open fa-fw"></i> Files</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-14" type="button"><i class="fa-solid fa-person-digging fa-fw"></i> Maintenance</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-15" type="button"><i class="fa-solid fa-clipboard-list fa-fw"></i> Audit Logs</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-16" type="button"><i class="fa-solid fa-palette fa-fw"></i> Appearance</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-17" type="button"><i class="fa-solid fa-language fa-fw"></i> Language</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-18" type="button"><i class="fa-solid fa-clock-rotate-left fa-fw"></i> Retention</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-19" type="button"><i class="fa-solid fa-file-pdf fa-fw"></i> Reports</button>
                    <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-20" type="button"><i class="fa-solid fa-circle-info fa-fw"></i> System Info</button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="col-lg-9 col-xl-10 pb-5">
                <div class="tab-content" id="settings-tabContent">
                    
                    <!-- 1. General Settings -->
                    <div class="tab-pane fade show active" id="tab-1" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">1. General Settings</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">System Name</label>
                                    <input type="text" class="form-control" name="sys_name" value="{{ $settings['sys_name'] ?? 'SpeakReady AI' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Email</label>
                                    <input type="email" class="form-control" name="sys_contact_email" value="{{ $settings['sys_contact_email'] ?? 'support@speakready.ai' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" name="sys_contact_number" value="{{ $settings['sys_contact_number'] ?? '+123456789' }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">System Description</label>
                                    <textarea class="form-control" name="sys_desc" rows="3">{{ $settings['sys_desc'] ?? 'SpeakReady AI helps users master communication skills.' }}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Footer Text</label>
                                    <input type="text" class="form-control" name="sys_footer" value="{{ $settings['sys_footer'] ?? '© 2026 SpeakReady AI. All Rights Reserved.' }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Account Settings -->
                    <div class="tab-pane fade" id="tab-2" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">2. Account Settings</h5>
                            
                            <div class="custom-switch-container">
                                <div>
                                    <h6 class="mb-1 text-white">Enable User Registration</h6>
                                    <small class="text-muted">Allow new users to sign up on the platform.</small>
                                </div>
                                <div class="form-check form-switch fs-4 mb-0">
                                    <input class="form-check-input" type="checkbox" name="acc_registration" value="true" {{ ($settings['acc_registration'] ?? 'true') == 'true' ? 'checked' : '' }}>
                                    <input type="hidden" name="acc_registration" value="false" disabled>
                                </div>
                            </div>

                            <div class="custom-switch-container">
                                <div>
                                    <h6 class="mb-1 text-white">Email Verification Required</h6>
                                    <small class="text-muted">Users must verify their email before logging in.</small>
                                </div>
                                <div class="form-check form-switch fs-4 mb-0">
                                    <input class="form-check-input" type="checkbox" name="acc_verify_email" value="true" {{ ($settings['acc_verify_email'] ?? 'false') == 'true' ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <label class="form-label">Session Timeout (Minutes)</label>
                                    <input type="number" class="form-control" name="acc_session_timeout" value="{{ $settings['acc_session_timeout'] ?? '120' }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. User Role Management -->
                    <div class="tab-pane fade" id="tab-3" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">3. User Role Management</h5>
                            <div class="alert alert-info" style="background:rgba(59,130,246,0.1); border-color:rgba(59,130,246,0.2); color:#60a5fa;">
                                <i class="fa-solid fa-circle-info me-2"></i> Roles configuration. This feature dictates default permissions for standard users versus administrators.
                            </div>
                            
                            <h6 class="text-white mt-4 mb-3">Administrator Permissions</h6>
                            <div class="row g-3">
                                @foreach(['View', 'Create', 'Edit', 'Delete', 'Export'] as $perm)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked disabled>
                                        <label class="form-check-label">{{ $perm }} (Always On)</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <h6 class="text-white mt-4 mb-3">User/Candidate Permissions</h6>
                            <div class="row g-3">
                                @foreach(['View Content', 'Take Interview', 'Delete Own Account', 'Export Reports'] as $i => $perm)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="role_user_perm_{{$i}}" value="true" checked>
                                        <label class="form-check-label">{{ $perm }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- 4. Interview Settings -->
                    <div class="tab-pane fade" id="tab-4" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">4. Interview Settings ⭐</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Default Number of Questions</label>
                                    <input type="number" class="form-control" name="int_default_questions" value="{{ $settings['int_default_questions'] ?? '10' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Maximum Questions Allowed</label>
                                    <input type="number" class="form-control" name="int_max_questions" value="{{ $settings['int_max_questions'] ?? '20' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Default Time Limit (Minutes)</label>
                                    <input type="number" class="form-control" name="int_time_limit" value="{{ $settings['int_time_limit'] ?? '2' }}">
                                </div>
                            </div>
                            
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Follow-Up Questions</h6></div>
                                <div class="form-check form-switch fs-4 mb-0">
                                    <input class="form-check-input" type="checkbox" name="int_follow_up" value="true" {{ ($settings['int_follow_up'] ?? 'true') == 'true' ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable AI Evaluation</h6></div>
                                <div class="form-check form-switch fs-4 mb-0">
                                    <input class="form-check-input" type="checkbox" name="int_ai_eval" value="true" {{ ($settings['int_ai_eval'] ?? 'true') == 'true' ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Voice Rehearsal Settings -->
                    <div class="tab-pane fade" id="tab-5" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">5. Voice Rehearsal Settings</h5>
                            
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Voice Recording</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="vr_recording" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Speech-to-Text</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="vr_stt" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Confidence Analysis</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="vr_confidence" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Filler Word Detection</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="vr_filler" value="true" checked></div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. AI Coach Settings -->
                    <div class="tab-pane fade" id="tab-6" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">6. AI Coach Settings</h5>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable AI Coach</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="aic_enable" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Sample Answer Generation</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="aic_sample" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Follow-Up Questions</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="aic_follow" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Learning Recommendations</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="aic_recommend" value="true" checked></div>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Learning Lab Settings -->
                    <div class="tab-pane fade" id="tab-7" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">7. Learning Lab Settings</h5>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Learning Modules</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="ll_modules" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Quizzes</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="ll_quizzes" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Certificates</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="ll_certs" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Enable Achievements</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="ll_achievements" value="true" checked></div>
                            </div>
                        </div>
                    </div>

                    <!-- 8. Readiness Score Settings -->
                    <div class="tab-pane fade" id="tab-8" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">8. Readiness Score Settings ⭐</h5>
                            <p class="text-muted mb-4">Adjust the weight distribution for calculating the overall readiness score. Ensure total equals 100%.</p>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Communication (%)</label>
                                    <input type="number" class="form-control" name="rs_comm" value="{{ $settings['rs_comm'] ?? '30' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Relevance (%)</label>
                                    <input type="number" class="form-control" name="rs_rel" value="{{ $settings['rs_rel'] ?? '25' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confidence (%)</label>
                                    <input type="number" class="form-control" name="rs_conf" value="{{ $settings['rs_conf'] ?? '20' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Grammar (%)</label>
                                    <input type="number" class="form-control" name="rs_gram" value="{{ $settings['rs_gram'] ?? '15' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Professionalism (%)</label>
                                    <input type="number" class="form-control" name="rs_prof" value="{{ $settings['rs_prof'] ?? '10' }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 9. Notification Settings -->
                    <div class="tab-pane fade" id="tab-9" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">9. Notification Settings</h5>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">System Notifications</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="notif_sys" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Email Notifications</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="notif_email" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Interview Reminders</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="notif_reminders" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Achievement Notifications</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="notif_achieve" value="true" checked></div>
                            </div>
                        </div>
                    </div>

                    <!-- 10. Email Settings -->
                    <div class="tab-pane fade" id="tab-10" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">10. Email Settings</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" name="mail_host" value="{{ $settings['mail_host'] ?? 'smtp.mailtrap.io' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="text" class="form-control" name="mail_port" value="{{ $settings['mail_port'] ?? '2525' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" class="form-control" name="mail_user" value="{{ $settings['mail_user'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" name="mail_pass" value="{{ $settings['mail_pass'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 11. Security Settings -->
                    <div class="tab-pane fade" id="tab-11" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">11. Security Settings ⭐</h5>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Require Strong Passwords</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="sec_strong_pass" value="true" checked></div>
                            </div>
                            <div class="custom-switch-container">
                                <div><h6 class="mb-1 text-white">Two-Factor Authentication (Optional)</h6></div>
                                <div class="form-check form-switch fs-4 mb-0"><input class="form-check-input" type="checkbox" name="sec_2fa" value="true"></div>
                            </div>
                            <div class="row mt-4 g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Login Attempt Limits</label>
                                    <input type="number" class="form-control" name="sec_login_limit" value="{{ $settings['sec_login_limit'] ?? '5' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Account Lockout Duration (Minutes)</label>
                                    <input type="number" class="form-control" name="sec_lockout" value="{{ $settings['sec_lockout'] ?? '15' }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 12. Backup & Restore -->
                    <div class="tab-pane fade" id="tab-12" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">12. Backup & Restore</h5>
                            <div class="d-flex gap-3 mb-4">
                                <button type="button" class="btn btn-primary"><i class="fa-solid fa-download me-2"></i>Create Backup</button>
                                <button type="button" class="btn btn-outline-light"><i class="fa-solid fa-upload me-2"></i>Restore Backup</button>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Automatic Backup Schedule</label>
                                <select class="form-select" name="backup_schedule">
                                    <option value="daily" {{ ($settings['backup_schedule'] ?? '') == 'daily' ? 'selected' : '' }}>Daily</option>
                                    <option value="weekly" {{ ($settings['backup_schedule'] ?? '') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="monthly" {{ ($settings['backup_schedule'] ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="never" {{ ($settings['backup_schedule'] ?? '') == 'never' ? 'selected' : '' }}>Never</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 13. File Management Settings -->
                    <div class="tab-pane fade" id="tab-13" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">13. File Management Settings</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Maximum Upload Size (MB)</label>
                                    <input type="number" class="form-control" name="file_max_size" value="{{ $settings['file_max_size'] ?? '10' }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Allowed File Types</label>
                                    <input type="text" class="form-control" name="file_types" value="{{ $settings['file_types'] ?? 'PDF, DOCX, PPTX, PNG, JPG' }}">
                                    <small class="text-muted">Comma separated values</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 14. System Maintenance Mode -->
                    <div class="tab-pane fade" id="tab-14" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4 text-warning">14. System Maintenance Mode</h5>
                            <div class="custom-switch-container border-warning">
                                <div>
                                    <h6 class="mb-1 text-white">Enable Maintenance Mode</h6>
                                    <small class="text-muted">Users will see a maintenance page instead of the app.</small>
                                </div>
                                <div class="form-check form-switch fs-4 mb-0">
                                    <input class="form-check-input" type="checkbox" name="sys_maintenance" value="true" {{ ($settings['sys_maintenance'] ?? 'false') == 'true' ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Maintenance Message</label>
                                <textarea class="form-control" name="sys_maintenance_msg" rows="3">{{ $settings['sys_maintenance_msg'] ?? 'System Under Maintenance. Please try again later.' }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 15. Audit Logs -->
                    <div class="tab-pane fade" id="tab-15" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">15. Audit Logs ⭐</h5>
                            <p class="text-muted">Recent system changes tracked by the audit system.</p>
                            <table class="table table-dark table-hover text-white">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>{{ now()->subHours(2)->format('Y-m-d H:i') }}</td><td>Settings Updated</td><td>Admin</td></tr>
                                    <tr><td>{{ now()->subDays(1)->format('Y-m-d H:i') }}</td><td>User Created</td><td>Admin</td></tr>
                                    <tr><td>{{ now()->subDays(2)->format('Y-m-d H:i') }}</td><td>Question Added</td><td>Admin</td></tr>
                                    <tr><td>{{ now()->subDays(3)->format('Y-m-d H:i') }}</td><td>Module Updated</td><td>Admin</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 16. Appearance Settings -->
                    <div class="tab-pane fade" id="tab-16" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">16. Appearance Settings</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Upload System Logo</label>
                                    <input type="file" class="form-control" name="system_logo" accept="image/*">
                                    @if(isset($settings['system_logo']))
                                    <img src="{{ asset($settings['system_logo']) }}" class="mt-2" style="max-height: 40px;">
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Upload Favicon</label>
                                    <input type="file" class="form-control" name="system_favicon" accept="image/*">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Primary Color (Hex)</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" class="form-control form-control-color" style="width: 50px;" name="color_primary" value="{{ $settings['color_primary'] ?? '#3b82f6' }}">
                                        <input type="text" class="form-control" value="{{ $settings['color_primary'] ?? '#3b82f6' }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Secondary Color (Hex)</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" class="form-control form-control-color" style="width: 50px;" name="color_secondary" value="{{ $settings['color_secondary'] ?? '#34d399' }}">
                                        <input type="text" class="form-control" value="{{ $settings['color_secondary'] ?? '#34d399' }}" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 17. Language Settings -->
                    <div class="tab-pane fade" id="tab-17" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">17. Language Settings</h5>
                            <div class="col-md-6">
                                <label class="form-label">Default Language</label>
                                <select class="form-select" name="sys_language">
                                    <option value="en" {{ ($settings['sys_language'] ?? '') == 'en' ? 'selected' : '' }}>English</option>
                                    <option value="fil" {{ ($settings['sys_language'] ?? '') == 'fil' ? 'selected' : '' }}>Filipino</option>
                                </select>
                                <small class="text-muted mt-2 d-block">Future-ready for multilingual support.</small>
                            </div>
                        </div>
                    </div>

                    <!-- 18. Data Retention Settings -->
                    <div class="tab-pane fade" id="tab-18" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">18. Data Retention Settings</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Interview Data Retention (Days)</label>
                                    <input type="number" class="form-control" name="retention_interview" value="{{ $settings['retention_interview'] ?? '365' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Feedback Retention (Days)</label>
                                    <input type="number" class="form-control" name="retention_feedback" value="{{ $settings['retention_feedback'] ?? '365' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Archive Duration (Days)</label>
                                    <input type="number" class="form-control" name="retention_archive" value="{{ $settings['retention_archive'] ?? '730' }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 19. Report Settings -->
                    <div class="tab-pane fade" id="tab-19" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">19. Report Settings</h5>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Report Header text</label>
                                    <input type="text" class="form-control" name="rep_header" value="{{ $settings['rep_header'] ?? 'SpeakReady AI Official Report' }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Report Footer text</label>
                                    <input type="text" class="form-control" name="rep_footer" value="{{ $settings['rep_footer'] ?? 'Generated by SpeakReady AI System.' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Include School Logo</label>
                                    <select class="form-select" name="rep_logo">
                                        <option value="yes" {{ ($settings['rep_logo'] ?? '') == 'yes' ? 'selected' : '' }}>Yes</option>
                                        <option value="no" {{ ($settings['rep_logo'] ?? '') == 'no' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Signature Area Title</label>
                                    <input type="text" class="form-control" name="rep_signature" value="{{ $settings['rep_signature'] ?? 'Approved By' }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 20. System Information -->
                    <div class="tab-pane fade" id="tab-20" role="tabpanel">
                        <div class="premium-card">
                            <h5 class="fw-bold mb-4">20. System Information</h5>
                            <ul class="list-group list-group-flush rounded" style="background:var(--bg3);">
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-white border-bottom border-secondary">
                                    System Version <span class="badge bg-primary rounded-pill">1.0.0</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-white border-bottom border-secondary">
                                    Laravel Version <span class="text-muted">12.x</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-white border-bottom border-secondary">
                                    PHP Version <span class="text-muted">8.3</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-white border-bottom border-secondary">
                                    Database Version <span class="text-muted">MySQL 8+</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent text-white border-bottom border-secondary">
                                    Server Status <span class="text-success"><i class="fa-solid fa-circle fa-xs me-1"></i> Online</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Save Button Sticky -->
            <div class="col-12">
                <div class="btn-save-fixed shadow w-100">
                    <div class="text-muted">
                        <i class="fa-solid fa-circle-info me-1"></i> Make sure to save your changes before leaving this page.
                    </div>
                    <button type="submit" class="btn text-white px-4 py-2" style="background:#3b82f6; border-radius:10px; font-weight:600;">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Save All Settings
                    </button>
                </div>
            </div>
            
        </div>
    </form>
</div>
@endsection

