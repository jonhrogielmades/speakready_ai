@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<style>
    /* Mobile Card-based Table Layout for Main Audit Logs Table */
    @media (max-width: 767px) {
        #mainAuditLogsTableWrapper {
            overflow-x: visible !important;
            -webkit-overflow-scrolling: auto !important;
        }
        #mainAuditLogsTable thead {
            display: none;
        }
        #mainAuditLogsTable tbody tr {
            display: flex;
            flex-direction: column;
            background: var(--bg3, rgba(255,255,255,0.02));
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--bd, rgba(255,255,255,0.1));
            padding: 12px;
        }
        #mainAuditLogsTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-top: none !important;
            text-align: right;
        }
        #mainAuditLogsTable tbody td:last-child {
            border-bottom: none !important;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px !important;
        }
        #mainAuditLogsTable tbody td::before {
            font-size: 0.8rem;
            color: var(--tx3, #888);
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
            text-align: left;
        }
        #mainAuditLogsTable tbody td:nth-child(1)::before { content: "Date"; }
        #mainAuditLogsTable tbody td:nth-child(2)::before { content: "Action"; }
        #mainAuditLogsTable tbody td:nth-child(3)::before { content: "User"; }
        
        #mainAuditLogsTable tbody td:nth-child(2) {
            order: -1;
            justify-content: flex-start;
            border-bottom: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
            padding-bottom: 12px !important;
            margin-bottom: 4px;
            text-align: left;
        }
        #mainAuditLogsTable tbody td:nth-child(2)::before { content: none; }
    }

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
            <!-- Settings Grid -->
<div class="col-12 mb-4">
    <div class="row g-3">
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-1">
                <i class="fa-solid fa-globe fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">General</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-2">
                <i class="fa-solid fa-user-gear fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Account</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-3">
                <i class="fa-solid fa-users-gear fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Roles</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-4">
                <i class="fa-solid fa-microphone-lines fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Interview</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-5">
                <i class="fa-solid fa-podcast fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Voice Rehearsal</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-6">
                <i class="fa-solid fa-robot fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">AI Coach</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-7">
                <i class="fa-solid fa-flask fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Learning Lab</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-8">
                <i class="fa-solid fa-star fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Readiness Score</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-9">
                <i class="fa-solid fa-bell fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Notifications</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-10">
                <i class="fa-solid fa-envelope fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Email</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-11">
                <i class="fa-solid fa-shield fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Security</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-12">
                <i class="fa-solid fa-database fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Backup</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-13">
                <i class="fa-solid fa-folder-open fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Files</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-14">
                <i class="fa-solid fa-person-digging fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Maintenance</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-15">
                <i class="fa-solid fa-clipboard-list fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Audit Logs</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-16">
                <i class="fa-solid fa-palette fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Appearance</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-17">
                <i class="fa-solid fa-language fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Language</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-18">
                <i class="fa-solid fa-clock-rotate-left fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Retention</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-19">
                <i class="fa-solid fa-file-pdf fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">Reports</span>
            </button>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <button type="button" class="btn w-100 p-4 premium-card text-center text-white h-100" style="transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.05)'; this.style.borderColor='var(--pur, #3b82f6)'" onmouseout="this.style.background='var(--sf, #1e1e2d)'; this.style.borderColor='var(--bd, rgba(255,255,255,0.1))'" data-bs-toggle="modal" data-bs-target="#modal-20">
                <i class="fa-solid fa-circle-info fa-fw fa-2x mb-3" style="color: #3b82f6;"></i><br>
                <span class="fw-semibold">System Info</span>
            </button>
        </div>
            </div>
        </div>
    </div>
</div>

<!-- 1. General Settings -->
                    <!-- Modal 1 -->
<div class="modal fade" id="modal-1" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">General</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 2. Account Settings -->
                    <!-- Modal 2 -->
<div class="modal fade" id="modal-2" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                            
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
    </div>
</div>

<!-- 3. User Role Management -->
                    <!-- Modal 3 -->
<div class="modal fade" id="modal-3" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Roles</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 4. Interview Settings -->
                    <!-- Modal 4 -->
<div class="modal fade" id="modal-4" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Interview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 5. Voice Rehearsal Settings -->
                    <!-- Modal 5 -->
<div class="modal fade" id="modal-5" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Voice Rehearsal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                            
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
    </div>
</div>

<!-- 6. AI Coach Settings -->
                    <!-- Modal 6 -->
<div class="modal fade" id="modal-6" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">AI Coach</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 7. Learning Lab Settings -->
                    <!-- Modal 7 -->
<div class="modal fade" id="modal-7" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Learning Lab</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 8. Readiness Score Settings -->
                    <!-- Modal 8 -->
<div class="modal fade" id="modal-8" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Readiness Score</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 9. Notification Settings -->
                    <!-- Modal 9 -->
<div class="modal fade" id="modal-9" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Notifications</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 10. Email Settings -->
                    <!-- Modal 10 -->
<div class="modal fade" id="modal-10" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Email</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 11. Security Settings -->
                    <!-- Modal 11 -->
<div class="modal fade" id="modal-11" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Security</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 12. Backup & Restore -->
                    <!-- Modal 12 -->
<div class="modal fade" id="modal-12" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Backup</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 13. File Management Settings -->
                    <!-- Modal 13 -->
<div class="modal fade" id="modal-13" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Files</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 14. System Maintenance Mode -->
                    <!-- Modal 15 -->
<div class="modal fade" id="modal-15" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Audit Logs</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                            <p class="text-muted">Recent system changes tracked by the audit system.</p>
                            <div class="table-responsive" id="mainAuditLogsTableWrapper">
                                <table class="table table-dark table-hover text-white" id="mainAuditLogsTable">
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
        </div>
    </div>
</div>

<!-- 16. Appearance Settings -->
                    <!-- Modal 16 -->
<div class="modal fade" id="modal-16" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Appearance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 17. Language Settings -->
                    <!-- Modal 17 -->
<div class="modal fade" id="modal-17" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Language</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 18. Data Retention Settings -->
                    <!-- Modal 18 -->
<div class="modal fade" id="modal-18" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Retention</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 19. Report Settings -->
                    <!-- Modal 19 -->
<div class="modal fade" id="modal-19" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">Reports</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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
    </div>
</div>

<!-- 20. System Information -->
                    <!-- Modal 20 -->
<div class="modal fade" id="modal-20" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255, 255, 255, 0.1)); border-radius: 16px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-white fw-bold">System Info</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
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

