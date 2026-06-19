@extends('layouts.admin')

@section('content')
<style>
    /* Premium Admin Users Styles */
    .premium-card {
        background: var(--sf, #1e1e2d);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .custom-table th {
        color: var(--tx3, #808090);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        border-bottom: 1px solid var(--bd);
        padding: 12px 16px;
    }
    .custom-table td {
        padding: 16px;
        border-bottom: 1px solid var(--bd);
        color: var(--tx, #e0e0e0);
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .custom-table tr:last-child td { border-bottom: none; }
    
    .stat-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
    }
    .stat-badge.success { background: rgba(52, 211, 153, 0.15); color: #34d399; }
    .stat-badge.danger { background: rgba(248, 113, 113, 0.15); color: #f87171; }
    .stat-badge.warning { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .stat-badge.secondary { background: rgba(128, 128, 144, 0.15); color: #a0a0b0; }

    .action-btn {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid var(--bd);
        background: var(--bg3);
        color: var(--tx2);
        transition: 0.2s;
    }
    .action-btn:hover { background: var(--pur); border-color: var(--pur); color: #fff; }

    /* Modal Styles */
    .custom-modal .modal-content {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        color: var(--tx);
    }
    .custom-modal .modal-header {
        border-bottom: 1px solid var(--bd);
    }
    .custom-modal .modal-footer {
        border-top: 1px solid var(--bd);
    }
    .custom-modal .form-control, .custom-modal .form-select {
        background: var(--bg3);
        border: 1px solid var(--bd);
        color: var(--tx);
    }
    .custom-modal .form-control:focus, .custom-modal .form-select:focus {
        border-color: var(--pur);
        box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25);
    }
    .nav-tabs .nav-link {
        color: var(--tx2);
        border: none;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        font-weight: 500;
        padding: 10px 16px;
    }
    .nav-tabs .nav-link.active {
        color: var(--pur);
        background: transparent;
        border-bottom: 2px solid var(--pur);
    }
    
    .timeline {
        position: relative;
        padding-left: 20px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 5px; top: 5px; bottom: 5px;
        width: 2px;
        background: var(--bd);
    }
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -20px; top: 4px;
        width: 12px; height: 12px;
        border-radius: 50%;
        background: var(--pur);
        border: 2px solid var(--sf);
    }
</style>

<div class="db-section active" id="sec-admin-users">
    <!-- Top Header & Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-users-gear me-2" style="color:#3b82f6;"></i>User Management</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Manage users, track performance, and broadcast announcements.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <!-- Feature 14 & 15: Broadcast Announcements -->
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#broadcastModal"><i class="fa-solid fa-bullhorn me-2"></i>Broadcast</button>
            <!-- Feature 3: Add User -->
            <button class="btn btn-primary" style="background:#3b82f6;border-color:#3b82f6;" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fa-solid fa-user-plus me-2"></i>Add User</button>
            <!-- Feature 18: Export -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fa-solid fa-file-export me-2"></i>Export</button>
                <ul class="dropdown-menu dropdown-menu-end" style="background:var(--sf);border:1px solid var(--bd);">
                    <li><a class="dropdown-item" href="#" style="color:var(--tx);"><i class="fa-solid fa-file-pdf me-2 text-danger"></i>PDF</a></li>
                    <li><a class="dropdown-item" href="#" style="color:var(--tx);"><i class="fa-solid fa-file-excel me-2 text-success"></i>Excel</a></li>
                    <li><a class="dropdown-item" href="#" style="color:var(--tx);"><i class="fa-solid fa-file-csv me-2 text-info"></i>CSV</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Feature 16 & 17: Top Users & Needs Improvement -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-trophy me-2 text-warning"></i>Top Performing Users</h6>
                <div class="d-flex justify-content-between p-2 mb-2 rounded" style="background:rgba(251,191,36,0.1);border:1px solid rgba(251,191,36,0.3);">
                    <div class="d-flex align-items-center gap-2"><span class="fw-bold text-warning" style="width:20px;">1</span><div style="width:28px;height:28px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;">MS</div><span style="font-size:0.9rem;">Maria Santos</span></div>
                    <span class="fw-bold text-success">98% Avg</span>
                </div>
                <div class="d-flex justify-content-between p-2 mb-2 rounded" style="background:var(--bg3);">
                    <div class="d-flex align-items-center gap-2"><span class="fw-bold text-secondary" style="width:20px;">2</span><div style="width:28px;height:28px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;">JD</div><span style="font-size:0.9rem;">Juan Dela Cruz</span></div>
                    <span class="fw-bold text-success">94% Avg</span>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="premium-card h-100" style="border-left: 4px solid #f87171;">
                <h6 class="fw-bold mb-3 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Users Needing Improvement</h6>
                <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded" style="background:var(--bg3);">
                    <div class="d-flex align-items-center gap-2"><div style="width:28px;height:28px;border-radius:50%;background:#ef4444;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;">PP</div><span style="font-size:0.9rem;">Peter Parker</span></div>
                    <span class="stat-badge danger">Score < 70%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--bg3);">
                    <div class="d-flex align-items-center gap-2"><div style="width:28px;height:28px;border-radius:50%;background:#6b7280;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;">BW</div><span style="font-size:0.9rem;">Bruce Wayne</span></div>
                    <span class="stat-badge warning">Inactive 30d</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 1: User List Main Table -->
    <div class="premium-card">
        <!-- Search and Filter -->
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text" style="background:var(--bg3);border-color:var(--bd);color:var(--tx2);"><i class="fa-solid fa-search"></i></span>
                    <input type="text" class="form-control" placeholder="Search users..." style="background:var(--bg3);border-color:var(--bd);color:var(--tx);">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" style="background:var(--bg3);border-color:var(--bd);color:var(--tx);">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" style="background:var(--bg3);border-color:var(--bd);color:var(--tx);">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table custom-table mb-0 w-100">
                <thead>
                    <tr>
                        <th>Profile & Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Date Registered</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Mock Data to demonstrate UI -->
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:40px;height:40px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;">JD</div>
                                <div><div class="fw-bold">Juan Dela Cruz</div><div style="font-size:0.75rem;color:var(--tx3);">IT Student</div></div>
                            </div>
                        </td>
                        <td>juan@example.com</td>
                        <td><span class="stat-badge secondary">Candidate</span></td>
                        <td><span class="stat-badge success">🟢 Active</span></td>
                        <td style="color:var(--tx2);">Oct 12, 2026</td>
                        <td class="text-end">
                            <button class="action-btn" title="View Detail Dashboard" data-bs-toggle="modal" data-bs-target="#userDetailModal"><i class="fa-solid fa-eye"></i></button>
                            <button class="action-btn" title="Edit User" data-bs-toggle="modal" data-bs-target="#editUserModal"><i class="fa-solid fa-pen"></i></button>
                            <button class="action-btn" title="Reset Password"><i class="fa-solid fa-key"></i></button>
                            <button class="action-btn text-danger" title="Delete User" data-bs-toggle="modal" data-bs-target="#deleteUserModal"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:40px;height:40px;border-radius:50%;background:#f43f5e;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;">SA</div>
                                <div><div class="fw-bold">System Admin</div><div style="font-size:0.75rem;color:var(--tx3);">Administrator</div></div>
                            </div>
                        </td>
                        <td>admin@nexusai.com</td>
                        <td><span class="stat-badge primary" style="background:rgba(59,130,246,0.15);color:#60a5fa;">Admin</span></td>
                        <td><span class="stat-badge success">🟢 Active</span></td>
                        <td style="color:var(--tx2);">Jan 01, 2026</td>
                        <td class="text-end">
                            <button class="action-btn" title="View Detail Dashboard" data-bs-toggle="modal" data-bs-target="#userDetailModal"><i class="fa-solid fa-eye"></i></button>
                            <button class="action-btn" title="Edit User" data-bs-toggle="modal" data-bs-target="#editUserModal"><i class="fa-solid fa-pen"></i></button>
                            <button class="action-btn" title="Reset Password"><i class="fa-solid fa-key"></i></button>
                            <button class="action-btn text-danger" title="Delete User" data-bs-toggle="modal" data-bs-target="#deleteUserModal"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:40px;height:40px;border-radius:50%;background:#6b7280;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;">BW</div>
                                <div><div class="fw-bold">Bruce Wayne</div><div style="font-size:0.75rem;color:var(--tx3);">Business</div></div>
                            </div>
                        </td>
                        <td>bruce@wayne.com</td>
                        <td><span class="stat-badge secondary">Candidate</span></td>
                        <td><span class="stat-badge warning">🔴 Inactive</span></td>
                        <td style="color:var(--tx2);">Sep 05, 2026</td>
                        <td class="text-end">
                            <button class="action-btn" title="View Detail Dashboard" data-bs-toggle="modal" data-bs-target="#userDetailModal"><i class="fa-solid fa-eye"></i></button>
                            <button class="action-btn" title="Edit User" data-bs-toggle="modal" data-bs-target="#editUserModal"><i class="fa-solid fa-pen"></i></button>
                            <button class="action-btn" title="Reset Password"><i class="fa-solid fa-key"></i></button>
                            <button class="action-btn text-danger" title="Delete User" data-bs-toggle="modal" data-bs-target="#deleteUserModal"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:40px;height:40px;border-radius:50%;background:#fbbf24;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;">LG</div>
                                <div><div class="fw-bold">Loki God</div><div style="font-size:0.75rem;color:var(--tx3);">Unknown</div></div>
                            </div>
                        </td>
                        <td>loki@asgard.com</td>
                        <td><span class="stat-badge secondary">Candidate</span></td>
                        <td><span class="stat-badge danger">⚫ Suspended</span></td>
                        <td style="color:var(--tx2);">Nov 11, 2026</td>
                        <td class="text-end">
                            <button class="action-btn" title="View Detail Dashboard" data-bs-toggle="modal" data-bs-target="#userDetailModal"><i class="fa-solid fa-eye"></i></button>
                            <button class="action-btn" title="Edit User" data-bs-toggle="modal" data-bs-target="#editUserModal"><i class="fa-solid fa-pen"></i></button>
                            <button class="action-btn" title="Reset Password"><i class="fa-solid fa-key"></i></button>
                            <button class="action-btn text-danger" title="Delete User" data-bs-toggle="modal" data-bs-target="#deleteUserModal"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Mock -->
        <div class="d-flex justify-content-between align-items-center mt-3 pt-3" style="border-top:1px solid var(--bd);">
            <span style="font-size:0.85rem;color:var(--tx2);">Showing 1 to 4 of 1,250 entries</span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#" style="background:var(--bg3);border-color:var(--bd);color:var(--tx2);">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#" style="background:#3b82f6;border-color:#3b82f6;">1</a></li>
                <li class="page-item"><a class="page-link" href="#" style="background:var(--bg3);border-color:var(--bd);color:var(--tx2);">2</a></li>
                <li class="page-item"><a class="page-link" href="#" style="background:var(--bg3);border-color:var(--bd);color:var(--tx2);">Next</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- Feature 20: User Detail Dashboard (Large Modal) -->
<div class="modal fade custom-modal" id="userDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center pb-0 border-0">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:60px;height:60px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:1.5rem;">JD</div>
                    <div>
                        <h4 class="mb-0 fw-bold">Juan Dela Cruz</h4>
                        <div style="color:var(--tx2);font-size:0.9rem;">ID: USR-98241 <span class="stat-badge success ms-2">🟢 Active</span></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            
            <div class="modal-header pt-0">
                <ul class="nav nav-tabs border-0 w-100" id="userTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">Overview</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-interviews" type="button">Interviews</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-progress" type="button" onclick="setTimeout(()=>window.dispatchEvent(new Event('resize')), 100)">Progress</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-learning" type="button">Learning</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-activity" type="button">Activity Logs</button></li>
                </ul>
            </div>

            <div class="modal-body p-4" style="background:var(--bg);min-height:500px;">
                <div class="tab-content">
                    
                    <!-- Feature 2 & 8: Overview Tab -->
                    <div class="tab-pane fade show active" id="tab-overview">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="premium-card p-3">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2" style="border-color:var(--bd)!important;">Personal Information</h6>
                                    <div class="row mb-2"><div class="col-4 text-muted">Email</div><div class="col-8">juan@example.com <span class="badge bg-success ms-1">Verified</span></div></div>
                                    <div class="row mb-2"><div class="col-4 text-muted">Contact</div><div class="col-8">+63 912 345 6789</div></div>
                                    <div class="row mb-2"><div class="col-4 text-muted">Education</div><div class="col-8">College Graduate</div></div>
                                    <div class="row mb-2"><div class="col-4 text-muted">Course</div><div class="col-8">BS Information Technology</div></div>
                                    <div class="row mb-2"><div class="col-4 text-muted">Registered</div><div class="col-8">Oct 12, 2026</div></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="premium-card p-3" style="background:linear-gradient(135deg, var(--sf) 0%, rgba(59,130,246,0.1) 100%);">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2" style="border-color:var(--bd)!important;"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>Interview Statistics</h6>
                                    <div class="row text-center mb-3">
                                        <div class="col-6 mb-3">
                                            <h3 class="fw-bold text-primary mb-0">25</h3>
                                            <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Interviews Completed</div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <h3 class="fw-bold text-success mb-0">88%</h3>
                                            <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Average Score</div>
                                        </div>
                                        <div class="col-6">
                                            <h3 class="fw-bold text-warning mb-0">96%</h3>
                                            <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Highest Score</div>
                                        </div>
                                        <div class="col-6">
                                            <h3 class="fw-bold text-info mb-0">7 Days</h3>
                                            <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Practice Streak</div>
                                        </div>
                                    </div>
                                    <div class="text-center p-2 rounded" style="background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.3);">
                                        <span class="text-success fw-bold">Readiness Rating: Highly Acceptable</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 9: Interview History Tab -->
                    <div class="tab-pane fade" id="tab-interviews">
                        <div class="premium-card p-3">
                            <div class="table-responsive">
                                <table class="table custom-table w-100">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Category</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Today, 10:30 AM</td>
                                            <td><span class="stat-badge primary">Job Interview</span></td>
                                            <td><span class="fw-bold text-success">88%</span></td>
                                            <td>Completed</td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-secondary">View Feedback</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Yesterday</td>
                                            <td><span class="stat-badge warning">IT Interview</span></td>
                                            <td><span class="fw-bold text-warning">72%</span></td>
                                            <td>Completed</td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-secondary">View Feedback</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 10: Progress Tab -->
                    <div class="tab-pane fade" id="tab-progress">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <div class="premium-card p-3 h-100">
                                    <h6 class="fw-bold mb-3">Readiness Trend (Monthly)</h6>
                                    <div class="chart-container" style="height:250px;">
                                        <canvas id="userProgressChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="premium-card p-3 h-100">
                                    <h6 class="fw-bold mb-3">Category Performance</h6>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Job Interview</span><span class="fw-bold">90%</span></div>
                                        <div class="progress-track"><div class="progress-fill" style="width:90%;background:#3b82f6;"></div></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>IT Interview</span><span class="fw-bold">85%</span></div>
                                        <div class="progress-track"><div class="progress-fill" style="width:85%;background:#3b82f6;"></div></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Scholarship</span><span class="fw-bold">75%</span></div>
                                        <div class="progress-track"><div class="progress-fill" style="width:75%;background:#f59e0b;"></div></div>
                                    </div>
                                    <div class="p-3 mt-4 rounded text-center" style="background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.3);">
                                        <div style="font-size:0.8rem;color:var(--tx2);">Improvement Rate</div>
                                        <h4 class="fw-bold text-success mb-0">+12%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 11: Learning Progress Tab -->
                    <div class="tab-pane fade" id="tab-learning">
                        <div class="premium-card p-3">
                            <h6 class="fw-bold mb-4">Completed Learning Modules</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 rounded" style="background:var(--bg3);border:1px solid var(--bd);">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="fw-bold">STAR Method Mastery</span>
                                            <span class="text-success fw-bold">100%</span>
                                        </div>
                                        <div class="progress-track"><div class="progress-fill" style="width:100%;background:#34d399;"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 rounded" style="background:var(--bg3);border:1px solid var(--bd);">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="fw-bold">Communication Skills</span>
                                            <span class="text-primary fw-bold">80%</span>
                                        </div>
                                        <div class="progress-track"><div class="progress-fill" style="width:80%;background:#3b82f6;"></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 12 & 13: Activity Tab -->
                    <div class="tab-pane fade" id="tab-activity">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="premium-card p-3 h-100">
                                    <h6 class="fw-bold mb-4">User Activity Logs</h6>
                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <div style="font-size:0.85rem;">Report Downloaded</div>
                                            <div style="font-size:0.75rem;color:var(--tx3);">Today, 11:00 AM</div>
                                        </div>
                                        <div class="timeline-item">
                                            <div style="font-size:0.85rem;">Interview Completed (Job Interview)</div>
                                            <div style="font-size:0.75rem;color:var(--tx3);">Today, 10:30 AM</div>
                                        </div>
                                        <div class="timeline-item">
                                            <div style="font-size:0.85rem;">Interview Started</div>
                                            <div style="font-size:0.75rem;color:var(--tx3);">Today, 10:00 AM</div>
                                        </div>
                                        <div class="timeline-item">
                                            <div style="font-size:0.85rem;">Logged In</div>
                                            <div style="font-size:0.75rem;color:var(--tx3);">Today, 09:55 AM</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="premium-card p-3 h-100">
                                    <h6 class="fw-bold mb-4">AI Feedback History</h6>
                                    <div class="p-3 mb-3 rounded" style="background:var(--bg3);border:1px solid var(--bd);">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="fw-bold text-primary">Job Interview Feedback</span>
                                            <span style="font-size:0.75rem;color:var(--tx3);">Today</span>
                                        </div>
                                        <p style="font-size:0.85rem;color:var(--tx2);margin-bottom:8px;">"Excellent structure using the STAR method, but try to speak a bit slower during the technical explanation."</p>
                                        <div style="font-size:0.75rem;"><span class="text-success">Score: 88%</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
            <div class="modal-footer border-0 pb-3 pt-0">
                <button type="button" class="btn btn-outline-danger me-auto"><i class="fa-solid fa-ban me-2"></i>Suspend Account</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" style="background:#3b82f6;border-color:#3b82f6;"><i class="fa-solid fa-download me-2"></i>Export Full Report</button>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade custom-modal" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Full Name</label>
                    <input type="text" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Email</label>
                    <input type="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Password</label>
                    <input type="password" class="form-control">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted">Role</label>
                        <select class="form-select">
                            <option>Candidate/User</option>
                            <option>Admin</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted">Status</label>
                        <select class="form-select">
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" style="background:#3b82f6;border-color:#3b82f6;">Create User</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade custom-modal" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Full Name</label>
                    <input type="text" class="form-control" value="Juan Dela Cruz">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Email</label>
                    <input type="email" class="form-control" value="juan@example.com">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted">Role</label>
                        <select class="form-select">
                            <option selected>Candidate/User</option>
                            <option>Admin</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted">Status</label>
                        <select class="form-select">
                            <option selected>Active</option>
                            <option>Inactive</option>
                            <option>Suspended</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" style="background:#3b82f6;border-color:#3b82f6;">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade custom-modal" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3"><i class="fa-solid fa-triangle-exclamation text-danger" style="font-size:3rem;"></i></div>
                <h5 class="fw-bold mb-3">Delete User</h5>
                <p style="font-size:0.9rem;color:var(--tx2);">Are you sure you want to delete this user? You can choose to soft delete or permanently remove them.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-danger">Permanent Delete</button>
                    <button class="btn btn-outline-warning">Soft Delete</button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Broadcast Modal -->
<div class="modal fade custom-modal" id="broadcastModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Send Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Type</label>
                    <select class="form-select">
                        <option>Broadcast to All Users</option>
                        <option>Bulk Notification</option>
                        <option>Individual Notification</option>
                        <option>Dashboard Announcement</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Subject</label>
                    <input type="text" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Message</label>
                    <textarea class="form-control" rows="4"></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="scheduleCheck">
                    <label class="form-check-label text-muted" for="scheduleCheck">Schedule for later</label>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning"><i class="fa-solid fa-paper-plane me-2"></i>Send</button>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.color = '#808090';
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Re-render chart when Progress tab is clicked
    document.querySelector('button[data-bs-target="#tab-progress"]').addEventListener('shown.bs.tab', function () {
        if(window.progressChartObj) return; // already rendered
        
        const progCtx = document.getElementById('userProgressChart').getContext('2d');
        let gradientLine = progCtx.createLinearGradient(0, 0, 0, 300);
        gradientLine.addColorStop(0, 'rgba(52, 211, 153, 0.4)');
        gradientLine.addColorStop(1, 'rgba(52, 211, 153, 0.0)');

        window.progressChartObj = new Chart(progCtx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Score Avg',
                    data: [65, 75, 82, 88],
                    borderColor: '#34d399',
                    backgroundColor: gradientLine,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#1e1e2d',
                    pointBorderColor: '#34d399',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max:100, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    });
});
</script>
@endsection