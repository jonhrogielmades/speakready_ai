@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/' . (($isMobile ?? false) ? 'mobile' : 'desktop') . '/admin/users.css?v=1') }}" data-page-style="admin-users">

@php
    $formatLastActiveShort = function ($lastActiveAt) {
        if (!$lastActiveAt) {
            return null;
        }

        $minutes = max(1, $lastActiveAt->diffInMinutes(now()));

        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $hours = (int) floor($minutes / 60);
        if ($hours < 24) {
            return $hours . 'h';
        }

        $days = (int) floor($hours / 24);
        if ($days < 7) {
            return $days . 'd';
        }

        $weeks = (int) floor($days / 7);
        if ($weeks < 5) {
            return $weeks . 'w';
        }

        return (int) floor($days / 30) . 'mo';
    };

    $presenceText = function ($user, $isOnline) use ($lastActiveByUserId, $formatLastActiveShort) {
        if ($isOnline) {
            return 'Online';
        }

        $lastActive = $formatLastActiveShort($lastActiveByUserId->get($user->id) ?? $user->created_at);

        return $lastActive ? $lastActive . ' ago' : 'Offline';
    };

    $presenceBadgeText = fn ($user) => $formatLastActiveShort($lastActiveByUserId->get($user->id) ?? $user->created_at);
@endphp

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
                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); window.print();" style="color:var(--tx);"><i class="fa-solid fa-file-pdf me-2 text-danger"></i>PDF</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.users.export', array_merge(request()->query(), ['format' => 'excel'])) }}" style="color:var(--tx);"><i class="fa-solid fa-file-excel me-2 text-success"></i>Excel CSV</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.users.export', array_merge(request()->query(), ['format' => 'csv'])) }}" style="color:var(--tx);"><i class="fa-solid fa-file-csv me-2 text-info"></i>CSV</a></li>
                </ul>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#f87171">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#f87171">
        <div class="fw-bold mb-1">Please fix the highlighted user form fields.</div>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <!-- Feature 16 & 17: Top Users & Needs Improvement -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-trophy me-2 text-warning"></i>Top Performing Users</h6>
                @forelse($topUsers as $index => $tUser)
                <div class="d-flex justify-content-between p-2 mb-2 rounded user-summary-card-row" style="background:{{ $index === 0 ? 'rgba(251,191,36,0.1)' : 'var(--bg3)' }};border:{{ $index === 0 ? '1px solid rgba(251,191,36,0.3)' : 'none' }};">
                    <div class="d-flex align-items-center gap-2 user-summary-card-identity">
                        <span class="fw-bold {{ $index === 0 ? 'text-warning' : 'text-secondary' }}" style="width:20px;">{{ $index + 1 }}</span>
                        @if($tUser->profile_photo_path)
                            @php
                                $photoPath = $tUser->profile_photo_path;
                                $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                                $isOnline = $onlineUserIds->contains($tUser->id);
                            @endphp
                            <div class="admin-user-avatar-wrap" title="{{ $presenceText($tUser, $isOnline) }}">
                                <div class="admin-user-avatar" style="width:28px;height:28px;font-size:0.72rem;">
                                    <img src="{{ $photoUrl }}" alt="Avatar">
                                </div>
                                @if($isOnline)
                                    <span class="admin-user-presence-dot online"></span>
                                @elseif($presenceBadgeText($tUser))
                                    <span class="admin-user-last-active-badge">{{ $presenceBadgeText($tUser) }}</span>
                                @else
                                    <span class="admin-user-presence-dot offline"></span>
                                @endif
                            </div>
                        @else
                            @php $isOnline = $onlineUserIds->contains($tUser->id); @endphp
                            <div class="admin-user-avatar-wrap" title="{{ $presenceText($tUser, $isOnline) }}">
                                <div class="admin-user-avatar" style="width:28px;height:28px;background:#{{ substr(md5($tUser->id), 0, 6) }};font-size:0.75rem;">
                                    {{ strtoupper(substr($tUser->name, 0, 2)) }}
                                </div>
                                @if($isOnline)
                                    <span class="admin-user-presence-dot online"></span>
                                @elseif($presenceBadgeText($tUser))
                                    <span class="admin-user-last-active-badge">{{ $presenceBadgeText($tUser) }}</span>
                                @else
                                    <span class="admin-user-presence-dot offline"></span>
                                @endif
                            </div>
                        @endif
                        <span class="user-summary-card-name" style="font-size:0.9rem;">{{ $tUser->name }}</span>
                    </div>
                    <span class="fw-bold text-success user-summary-card-meta">{{ $tUser->avg_score }}% Avg</span>
                </div>
                @empty
                <div class="admin-users-empty-state text-center py-3 px-3">No data available yet.</div>
                @endforelse
            </div>
        </div>
        <div class="col-lg-6">
            <div class="premium-card h-100" style="border-left: 4px solid var(--danger-tx); background: var(--danger-bg);">
                <h6 class="fw-bold mb-3" style="color: var(--danger-tx);"><i class="fa-solid fa-triangle-exclamation me-2"></i>Users Needing Improvement</h6>
                @forelse($needingImprovement as $nUser)
                <div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded user-summary-card-row" style="background:var(--bg3);">
                    <div class="d-flex align-items-center gap-2 user-summary-card-identity">
                        @if($nUser->profile_photo_path)
                            @php
                                $photoPath = $nUser->profile_photo_path;
                                $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                                $isOnline = $onlineUserIds->contains($nUser->id);
                            @endphp
                            <div class="admin-user-avatar-wrap" title="{{ $presenceText($nUser, $isOnline) }}">
                                <div class="admin-user-avatar" style="width:28px;height:28px;font-size:0.72rem;">
                                    <img src="{{ $photoUrl }}" alt="Avatar">
                                </div>
                                @if($isOnline)
                                    <span class="admin-user-presence-dot online"></span>
                                @elseif($presenceBadgeText($nUser))
                                    <span class="admin-user-last-active-badge">{{ $presenceBadgeText($nUser) }}</span>
                                @else
                                    <span class="admin-user-presence-dot offline"></span>
                                @endif
                            </div>
                        @else
                            @php $isOnline = $onlineUserIds->contains($nUser->id); @endphp
                            <div class="admin-user-avatar-wrap" title="{{ $presenceText($nUser, $isOnline) }}">
                                <div class="admin-user-avatar" style="width:28px;height:28px;background:#{{ substr(md5($nUser->id), 0, 6) }};font-size:0.75rem;">
                                    {{ strtoupper(substr($nUser->name, 0, 2)) }}
                                </div>
                                @if($isOnline)
                                    <span class="admin-user-presence-dot online"></span>
                                @elseif($presenceBadgeText($nUser))
                                    <span class="admin-user-last-active-badge">{{ $presenceBadgeText($nUser) }}</span>
                                @else
                                    <span class="admin-user-presence-dot offline"></span>
                                @endif
                            </div>
                        @endif
                        <span class="user-summary-card-name" style="font-size:0.9rem;">{{ $nUser->name }}</span>
                    </div>
                    <span class="stat-badge {{ $nUser->issue_class }} user-summary-card-meta">{{ $nUser->issue }}</span>
                </div>
                @empty
                <div class="admin-users-empty-state text-center py-3 px-3">All users are performing well!</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Feature 1: User List Main Table -->
    <div class="premium-card">
        <!-- Search and Filter -->
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3 mb-3" id="filterForm">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text" style="background:var(--bg3);border-color:var(--bd);color:var(--tx2);"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search users..." style="background:var(--bg3);border-color:var(--bd);color:var(--tx);" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select" style="background:var(--bg3);border-color:var(--bd);color:var(--tx);" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>User</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" style="background:var(--bg3);border-color:var(--bd);color:var(--tx);" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="col-md-2">
                 <button type="submit" class="btn btn-primary w-100" style="background:#3b82f6;border-color:#3b82f6;">Filter</button>
            </div>
        </form>

        <div class="table-responsive" id="mainUsersTableWrapper">
            <table class="table custom-table mb-0 w-100" id="mainUsersTable">
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
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if($user->profile_photo_path)
                                    @php
                                        $photoPath = $user->profile_photo_path;
                                        $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                                        $isOnline = $onlineUserIds->contains($user->id);
                                    @endphp
                                    <div class="admin-user-avatar-wrap" title="{{ $presenceText($user, $isOnline) }}">
                                        <div class="admin-user-avatar" style="width:40px;height:40px;">
                                            <img src="{{ $photoUrl }}" alt="Avatar">
                                        </div>
                                        @if($isOnline)
                                            <span class="admin-user-presence-dot online"></span>
                                        @elseif($presenceBadgeText($user))
                                            <span class="admin-user-last-active-badge">{{ $presenceBadgeText($user) }}</span>
                                        @else
                                            <span class="admin-user-presence-dot offline"></span>
                                        @endif
                                    </div>
                                @else
                                    @php $isOnline = $onlineUserIds->contains($user->id); @endphp
                                    <div class="admin-user-avatar-wrap" title="{{ $presenceText($user, $isOnline) }}">
                                        <div class="admin-user-avatar" style="width:40px;height:40px;background:#{{ substr(md5($user->id), 0, 6) }};">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        @if($isOnline)
                                            <span class="admin-user-presence-dot online"></span>
                                        @elseif($presenceBadgeText($user))
                                            <span class="admin-user-last-active-badge">{{ $presenceBadgeText($user) }}</span>
                                        @else
                                            <span class="admin-user-presence-dot offline"></span>
                                        @endif
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold">{{ $user->name }}</div>
                                    <div style="font-size:0.75rem;color:var(--tx3);">ID: {{ $user->id }}</div>
                                    <span class="admin-user-presence-label {{ $isOnline ? 'online' : 'offline' }}">{{ $presenceText($user, $isOnline) }}</span>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->is_admin)
                                <span class="stat-badge primary" style="background:rgba(59,130,246,0.15);color:#60a5fa;">Admin</span>
                            @else
                                <span class="stat-badge secondary">User</span>
                            @endif
                        </td>
                        <td>
                            @if($user->status === 'active')
                                <span class="stat-badge success"><i class="fa-solid fa-circle me-1"></i>Active</span>
                            @elseif($user->status === 'inactive')
                                <span class="stat-badge warning"><i class="fa-solid fa-circle me-1"></i>Inactive</span>
                            @else
                                <span class="stat-badge danger"><i class="fa-solid fa-circle me-1"></i>Suspended</span>
                            @endif
                            @if($user->reactivation_requested_at)
                                <div class="mt-1"><span class="stat-badge text-white" style="background:#f59e0b;font-size:0.65rem;">Req. Reactivation</span></div>
                            @endif
                        </td>
                        <td style="color:var(--tx2);">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            <div class="user-action-cell">
                            @if($user->reactivation_requested_at)
                            <form action="{{ route('admin.users.approve-reactivation', $user) }}" method="POST">
                                @csrf
                                <button type="submit" class="action-btn text-success border-success" title="Approve Reactivation"><i class="fa-solid fa-check"></i></button>
                            </form>
                            @endif
                            <button type="button" class="action-btn" title="View Detail Dashboard" onclick="viewUser({{ $user->id }})"><i class="fa-solid fa-eye"></i></button>
                            <button
                                type="button"
                                class="action-btn"
                                title="Edit User"
                                data-update-url="{{ route('admin.users.update', $user) }}"
                                data-user-name="{{ $user->name }}"
                                data-user-email="{{ $user->email }}"
                                data-user-role="{{ $user->is_admin ? 'admin' : 'user' }}"
                                data-user-status="{{ $user->status }}"
                                onclick="editUser(this)"
                            ><i class="fa-solid fa-pen"></i></button>
                            <button
                                type="button"
                                class="action-btn text-danger"
                                title="Delete User"
                                data-delete-url="{{ route('admin.users.destroy', $user) }}"
                                onclick="deleteUser(this)"
                            ><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Mock -->
        <div class="d-flex justify-content-between align-items-center mt-3 pt-3" style="border-top:1px solid var(--bd);">
            <div class="w-100">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- User Detail Dashboard -->
<div class="modal fade custom-modal" id="userDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center pb-0 border-0">
                <div class="d-flex align-items-center gap-3 mb-3 user-detail-heading">
                    <div class="admin-user-avatar-wrap" id="userDetailAvatarWrap" title="Offline">
                        <div id="userDetailAvatar" class="admin-user-avatar" style="width:60px;height:60px;font-size:1.5rem;">--</div>
                        <span id="userDetailPresenceDot" class="admin-user-presence-dot offline" style="width:14px;height:14px;"></span>
                    </div>
                    <div>
                        <h4 id="userDetailName" class="mb-0 fw-bold">Loading user...</h4>
                        <div style="color:var(--tx2);font-size:0.9rem;">ID: <span id="userDetailId">--</span> <span id="userDetailStatus" class="ms-2"></span></div>
                        <span id="userDetailPresenceLabel" class="admin-user-presence-label offline">Offline</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
            </div>
            
            <div class="modal-header pt-0">
                <ul class="nav nav-tabs border-0 w-100" id="userTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">Overview</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-interviews" type="button">PH Interviews</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-user-updates" type="button">User Updates</button></li>
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
                                    <div class="row mb-2 user-detail-info-row"><div class="col-4 text-muted user-detail-info-label">Email</div><div id="userDetailEmail" class="col-8 user-detail-info-value">--</div></div>
                                    <div class="row mb-2 user-detail-info-row"><div class="col-4 text-muted user-detail-info-label">Role</div><div id="userDetailRole" class="col-8 user-detail-info-value">--</div></div>
                                    <div class="row mb-2 user-detail-info-row"><div class="col-4 text-muted user-detail-info-label">Target Role</div><div id="userDetailTarget" class="col-8 user-detail-info-value">--</div></div>
                                    <div class="row mb-2 user-detail-info-row"><div class="col-4 text-muted user-detail-info-label">Language</div><div id="userDetailLanguage" class="col-8 user-detail-info-value">--</div></div>
                                    <div class="row mb-2 user-detail-info-row"><div class="col-4 text-muted user-detail-info-label">Registered</div><div id="userDetailRegistered" class="col-8 user-detail-info-value">--</div></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="premium-card p-3" style="background:linear-gradient(135deg, var(--sf) 0%, rgba(59,130,246,0.1) 100%);">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2" style="border-color:var(--bd)!important;"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>Philippines Interview Statistics</h6>
                                    <div class="row text-center mb-3">
                                        <div class="col-6 mb-3">
                                            <h3 id="userDetailCompleted" class="fw-bold text-primary mb-0">0</h3>
                                            <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">PH Interviews Completed</div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <h3 id="userDetailAverage" class="fw-bold text-success mb-0">N/A</h3>
                                            <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Average Score</div>
                                        </div>
                                        <div class="col-6">
                                            <h3 id="userDetailHighest" class="fw-bold text-warning mb-0">N/A</h3>
                                            <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Highest Score</div>
                                        </div>
                                        <div class="col-6">
                                            <h3 id="userDetailStreak" class="fw-bold text-info mb-0">0 Days</h3>
                                            <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Practice Streak</div>
                                        </div>
                                    </div>
                                    <div class="text-center p-2 rounded" style="background:rgba(52,211,153,0.1);border:1px solid rgba(52,211,153,0.3);">
                                        <span id="userDetailRating" class="text-success fw-bold">Readiness Rating: No scored sessions</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-4 mt-1">
                            <div class="col-md-12">
                                <div class="premium-card p-3">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2" style="border-color:var(--bd)!important;"><i class="fa-solid fa-arrows-rotate me-2 text-info"></i>User Side Updates</h6>
                                    <div class="row text-center">
                                        <div class="col-6 col-md-3 mb-3 mb-md-0">
                                            <h4 id="userDetailLearningCompleted" class="fw-bold text-success mb-0">0</h4>
                                            <div style="font-size:0.72rem;color:var(--tx3);text-transform:uppercase;">Modules Completed</div>
                                        </div>
                                        <div class="col-6 col-md-3 mb-3 mb-md-0">
                                            <h4 id="userDetailVoiceSessions" class="fw-bold text-info mb-0">0</h4>
                                            <div style="font-size:0.72rem;color:var(--tx3);text-transform:uppercase;">Voice Rehearsals</div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <h4 id="userDetailGameLevels" class="fw-bold text-warning mb-0">0</h4>
                                            <div style="font-size:0.72rem;color:var(--tx3);text-transform:uppercase;">Game Levels Done</div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <h4 id="userDetailXp" class="fw-bold text-primary mb-0">0 XP</h4>
                                            <div style="font-size:0.72rem;color:var(--tx3);text-transform:uppercase;">Total XP</div>
                                        </div>
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
                                    <tbody id="userDetailInterviews">
                                        <tr><td colspan="5" class="text-center text-muted py-3">Select a user to load Philippines interview history.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- User Side Updates Tab -->
                    <div class="tab-pane fade" id="tab-user-updates">
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <div class="premium-card p-3">
                                    <h6 class="fw-bold mb-3">Learning Module Progress</h6>
                                    <div class="table-responsive">
                                        <table class="table custom-table w-100">
                                            <thead>
                                                <tr>
                                                    <th>Module</th>
                                                    <th>Status</th>
                                                    <th>Progress</th>
                                                    <th>Quiz</th>
                                                    <th>Updated</th>
                                                </tr>
                                            </thead>
                                            <tbody id="userDetailLearningProgress">
                                                <tr><td colspan="5" class="text-center text-muted py-3">Select a user to load learning updates.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="premium-card p-3 h-100">
                                    <h6 class="fw-bold mb-3">Voice Rehearsals</h6>
                                    <div id="userDetailVoiceHistory" class="timeline">
                                        <div class="text-muted text-center py-3" style="font-size:0.9rem;">Select a user to load voice updates.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="premium-card p-3 h-100">
                                    <h6 class="fw-bold mb-3">Learning Games</h6>
                                    <div id="userDetailGameHistory" class="timeline">
                                        <div class="text-muted text-center py-3" style="font-size:0.9rem;">Select a user to load game updates.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="premium-card p-3 h-100">
                                    <h6 class="fw-bold mb-3">AI Coach Conversations</h6>
                                    <div id="userDetailCoachHistory" class="timeline">
                                        <div class="text-muted text-center py-3" style="font-size:0.9rem;">Select a user to load coach updates.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="premium-card p-3 h-100">
                                    <h6 class="fw-bold mb-3">Certificates & Skill Perks</h6>
                                    <div id="userDetailCertificates" class="timeline mb-3">
                                        <div class="text-muted text-center py-3" style="font-size:0.9rem;">Select a user to load certificates.</div>
                                    </div>
                                    <div id="userDetailPerks" class="d-flex flex-wrap gap-2"></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="premium-card p-3 h-100">
                                    <h6 class="fw-bold mb-3">Answer Retries</h6>
                                    <div id="userDetailRetryHistory" class="timeline">
                                        <div class="text-muted text-center py-3" style="font-size:0.9rem;">Select a user to load retry updates.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="premium-card p-3 h-100">
                                    <h6 class="fw-bold mb-3">Shared Review Links</h6>
                                    <div id="userDetailSharedReviews" class="timeline">
                                        <div class="text-muted text-center py-3" style="font-size:0.9rem;">Select a user to load shared review links.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Tab -->
                    <div class="tab-pane fade" id="tab-activity">
                        <div class="premium-card p-3 h-100">
                            <h6 class="fw-bold mb-4">User Activity Logs</h6>
                            <div id="userDetailActivities" class="timeline">
                                <div class="text-muted text-center py-3" style="font-size:0.9rem;">Select a user to load activity.</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
            <div class="modal-footer border-0 pb-3 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade custom-modal" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Password</label>
                        <div class="password-field">
                            <input type="password" name="password" id="createUserPassword" class="form-control" required minlength="8">
                            <button class="password-toggle toggle-password" type="button" onclick="togglePasswordVisibility('createUserPassword', this)" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="user">Candidate/User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:#3b82f6;border-color:#3b82f6;">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade custom-modal" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editUserForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Full Name</label>
                        <input type="text" name="name" id="editUserName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Email</label>
                        <input type="email" name="email" id="editUserEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Password (Leave blank to keep current)</label>
                        <div class="password-field">
                            <input type="password" name="password" id="editUserPassword" class="form-control" minlength="8">
                            <button class="password-toggle toggle-password" type="button" onclick="togglePasswordVisibility('editUserPassword', this)" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted">Role</label>
                            <select name="role" id="editUserRole" class="form-select" required>
                                <option value="user">Candidate/User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted">Status</label>
                            <select name="status" id="editUserStatus" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:#3b82f6;border-color:#3b82f6;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade custom-modal" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form id="deleteUserForm" action="" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body text-center p-4">
                    <div class="mb-3"><i class="fa-solid fa-triangle-exclamation text-danger" style="font-size:3rem;"></i></div>
                    <h5 class="fw-bold mb-3">Delete User</h5>
                    <p style="font-size:0.9rem;color:var(--tx2);">Are you sure you want to delete this user? You can choose to soft delete or permanently remove them.</p>
                    <div class="d-grid gap-2">
                        <button type="submit" name="delete_type" value="permanent" class="btn btn-danger">Permanent Delete</button>
                        <button type="submit" name="delete_type" value="soft" class="btn btn-outline-warning">Soft Delete</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Broadcast Modal -->
<div class="modal fade custom-modal" id="broadcastModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.notifications.store') }}" method="POST">
                @csrf
                <input type="hidden" name="target" value="all">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Broadcast Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1);"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Alert Type</label>
                        <select class="form-select" name="type" required>
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Danger</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Subject</label>
                        <input type="text" name="title" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Message</label>
                        <textarea name="message" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fa-solid fa-paper-plane me-2"></i>Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (!input || !icon) return;
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            btn.setAttribute('aria-label', 'Hide password');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            btn.setAttribute('aria-label', 'Show password');
        }
    }

    function editUser(button) {
        const form = document.getElementById('editUserForm');
        form.action = button.dataset.updateUrl;
        document.getElementById('editUserName').value = button.dataset.userName || '';
        document.getElementById('editUserEmail').value = button.dataset.userEmail || '';
        document.getElementById('editUserPassword').value = ''; // Clear password field
        document.getElementById('editUserRole').value = button.dataset.userRole || 'user';
        document.getElementById('editUserStatus').value = button.dataset.userStatus || 'active';
        
        var modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        modal.show();
    }

    function deleteUser(button) {
        const form = document.getElementById('deleteUserForm');
        form.action = button.dataset.deleteUrl;
        
        var modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        modal.show();
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function scoreText(score) {
        return score === null || score === undefined ? 'N/A' : `${score}%`;
    }

    function durationText(seconds) {
        if (seconds === null || seconds === undefined) return 'N/A';
        const total = Number(seconds) || 0;
        const minutes = Math.floor(total / 60);
        const remaining = total % 60;
        return minutes > 0 ? `${minutes}m ${remaining}s` : `${remaining}s`;
    }

    function lastActiveText(lastActiveAt) {
        if (!lastActiveAt) {
            return 'Offline';
        }

        const diffMinutes = Math.max(1, Math.floor((Date.now() - new Date(lastActiveAt).getTime()) / 60000));
        if (diffMinutes < 60) return `${diffMinutes}m ago`;

        const hours = Math.floor(diffMinutes / 60);
        if (hours < 24) return `${hours}h ago`;

        const days = Math.floor(hours / 24);
        if (days < 7) return `${days}d ago`;

        const weeks = Math.floor(days / 7);
        if (weeks < 5) return `${weeks}w ago`;

        return `${Math.floor(days / 30)}mo ago`;
    }

    function viewUser(id) {
        fetch(`/admin/users/${id}`)
            .then(res => {
                if (!res.ok) {
                    throw new Error('Unable to load user details.');
                }
                return res.json();
            })
            .then(data => {
                const user = data.user;
                const stats = data.stats || {};
                const initials = (user.name || '--').trim().split(/\s+/).map(part => part[0]).join('').slice(0, 2).toUpperCase() || '--';
                const isOnline = Boolean(user.is_online);
                const avatar = document.getElementById('userDetailAvatar');
                const avatarWrap = document.getElementById('userDetailAvatarWrap');
                const presenceDot = document.getElementById('userDetailPresenceDot');
                const presenceLabel = document.getElementById('userDetailPresenceLabel');

                if (user.profile_photo_url) {
                    avatar.innerHTML = `<img src="${escapeHtml(user.profile_photo_url)}" alt="Avatar">`;
                    avatar.style.background = '#3b82f6';
                } else {
                    avatar.innerHTML = '';
                    avatar.textContent = initials;
                    avatar.style.background = `#${String(user.id || 1).padStart(6, '0').slice(-6)}`;
                }
                const presenceText = isOnline ? 'Online' : lastActiveText(user.last_active_at);
                avatarWrap.title = presenceText;
                presenceDot.classList.toggle('online', isOnline);
                presenceDot.classList.toggle('offline', !isOnline);
                presenceLabel.classList.toggle('online', isOnline);
                presenceLabel.classList.toggle('offline', !isOnline);
                presenceLabel.textContent = presenceText;
                document.getElementById('userDetailName').textContent = user.name || 'Unnamed user';
                document.getElementById('userDetailId').textContent = user.id;
                document.getElementById('userDetailStatus').innerHTML = data.status_badge || '';
                document.getElementById('userDetailEmail').innerHTML = `${escapeHtml(user.email)} ${user.email_verified_at ? '<span class="badge bg-success ms-1">Verified</span>' : ''}`;
                document.getElementById('userDetailRole').innerHTML = data.role_badge || '';
                document.getElementById('userDetailTarget').textContent = user.target_position || 'Not set';
                document.getElementById('userDetailLanguage').textContent = user.preferred_language_label || 'English';
                document.getElementById('userDetailRegistered').textContent = data.formatted_date || '--';
                document.getElementById('userDetailCompleted').textContent = stats.completed_interviews ?? 0;
                document.getElementById('userDetailAverage').textContent = scoreText(stats.average_score);
                document.getElementById('userDetailHighest').textContent = scoreText(stats.highest_score);
                document.getElementById('userDetailStreak').textContent = `${stats.current_streak ?? 0} Days`;
                document.getElementById('userDetailRating').textContent = `Readiness Rating: ${stats.readiness_rating || 'No scored sessions'}`;
                document.getElementById('userDetailLearningCompleted').textContent = stats.learning_completed ?? 0;
                document.getElementById('userDetailVoiceSessions').textContent = stats.voice_rehearsals ?? 0;
                document.getElementById('userDetailGameLevels').textContent = stats.game_levels_completed ?? 0;
                document.getElementById('userDetailXp').textContent = `${stats.experience_points ?? 0} XP`;

                const interviewRows = (data.interviews || []).map(session => `
                    <tr>
                        <td>${escapeHtml(session.date)}</td>
                        <td><span class="stat-badge primary">${escapeHtml(session.category)}</span></td>
                        <td><span class="fw-bold">${scoreText(session.score)}</span></td>
                        <td>${escapeHtml(session.status)}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="${escapeHtml(session.review_url)}">View Feedback</a></td>
                    </tr>
                `).join('');
                document.getElementById('userDetailInterviews').innerHTML = interviewRows || '<tr><td colspan="5" class="text-center text-muted py-3">No completed Philippines interviews found.</td></tr>';

                const learningRows = (data.learning_progress || []).map(item => `
                    <tr>
                        <td>${escapeHtml(item.module)}</td>
                        <td><span class="stat-badge primary">${escapeHtml(String(item.status || 'enrolled').replace(/_/g, ' '))}</span></td>
                        <td><span class="fw-bold">${item.progress_percentage ?? 0}%</span></td>
                        <td>${item.quiz_score === null || item.quiz_score === undefined ? 'N/A' : escapeHtml(item.quiz_score) + '%'}</td>
                        <td>${escapeHtml(item.updated || '--')}</td>
                    </tr>
                `).join('');
                document.getElementById('userDetailLearningProgress').innerHTML = learningRows || '<tr><td colspan="5" class="text-center text-muted py-3">No learning module updates found.</td></tr>';

                const voiceRows = (data.voice_sessions || []).map(session => `
                    <div class="timeline-item">
                        <div style="font-size:0.85rem;"><strong>${escapeHtml(session.category)}</strong></div>
                        <div style="font-size:0.78rem;color:var(--tx2);">Clarity ${scoreText(session.clarity_score)} - Confidence ${scoreText(session.confidence_score)} - ${durationText(session.duration_seconds)}</div>
                        <div style="font-size:0.75rem;color:var(--tx3);">${escapeHtml(session.created || '--')}</div>
                    </div>
                `).join('');
                document.getElementById('userDetailVoiceHistory').innerHTML = voiceRows || '<div class="text-muted text-center py-3" style="font-size:0.9rem;">No voice rehearsal updates found.</div>';

                const gameRows = (data.game_sessions || []).map(session => `
                    <div class="timeline-item">
                        <div style="font-size:0.85rem;"><strong>${escapeHtml(session.level)}</strong></div>
                        <div style="font-size:0.78rem;color:var(--tx2);">Status ${escapeHtml(session.result_status || session.status || 'in progress')} - Score ${scoreText(session.score)} - ${escapeHtml(session.xp_earned || 0)} XP</div>
                        <div style="font-size:0.75rem;color:var(--tx3);">${escapeHtml(session.updated || '--')}</div>
                    </div>
                `).join('');
                document.getElementById('userDetailGameHistory').innerHTML = gameRows || '<div class="text-muted text-center py-3" style="font-size:0.9rem;">No learning game updates found.</div>';

                const coachRows = (data.coach_conversations || []).map(conversation => `
                    <div class="timeline-item">
                        <div style="font-size:0.85rem;"><strong>${escapeHtml(conversation.title || 'Untitled conversation')}</strong></div>
                        <div style="font-size:0.78rem;color:var(--tx2);">${escapeHtml(conversation.messages_count || 0)} messages</div>
                        <div style="font-size:0.75rem;color:var(--tx3);">${escapeHtml(conversation.updated || '--')}</div>
                    </div>
                `).join('');
                document.getElementById('userDetailCoachHistory').innerHTML = coachRows || '<div class="text-muted text-center py-3" style="font-size:0.9rem;">No AI coach conversations found.</div>';

                const certificateRows = (data.game_certificates || []).map(certificate => `
                    <div class="timeline-item">
                        <div style="font-size:0.85rem;"><strong>${escapeHtml(certificate.path)}</strong></div>
                        <div style="font-size:0.78rem;color:var(--tx2);">${escapeHtml(certificate.certificate_code)}</div>
                        <div style="font-size:0.75rem;color:var(--tx3);">Issued ${escapeHtml(certificate.issued_at || '--')}</div>
                    </div>
                `).join('');
                document.getElementById('userDetailCertificates').innerHTML = certificateRows || '<div class="text-muted text-center py-3" style="font-size:0.9rem;">No certificates issued yet.</div>';

                const perkRows = (data.unlocked_perks || []).map(perk => `
                    <span class="stat-badge success">${escapeHtml(perk.name)}</span>
                `).join('');
                document.getElementById('userDetailPerks').innerHTML = perkRows || '<span class="text-muted" style="font-size:0.9rem;">No skill perks unlocked.</span>';

                const retryRows = (data.recent_retries || []).map(retry => `
                    <div class="timeline-item">
                        <div style="font-size:0.85rem;"><strong>Session #${escapeHtml(retry.session_id)} attempt ${escapeHtml(retry.attempt_number)}</strong></div>
                        <div style="font-size:0.78rem;color:var(--tx2);">${escapeHtml(retry.question)}</div>
                        <div style="font-size:0.75rem;color:var(--tx3);">Score ${scoreText(retry.score)} - ${escapeHtml(retry.created || '--')}</div>
                    </div>
                `).join('');
                document.getElementById('userDetailRetryHistory').innerHTML = retryRows || '<div class="text-muted text-center py-3" style="font-size:0.9rem;">No answer retries found.</div>';

                const sharedRows = (data.shared_reviews || []).map(review => `
                    <div class="timeline-item">
                        <div style="font-size:0.85rem;"><strong>Session #${escapeHtml(review.session_id)} - ${escapeHtml(review.category)}</strong></div>
                        <div style="font-size:0.78rem;color:var(--tx2);">${review.is_public ? 'Active' : 'Disabled'} shared review link</div>
                        <div style="font-size:0.75rem;color:var(--tx3);">Expires ${escapeHtml(review.expires_at || '--')} - ${escapeHtml(review.updated || '--')}</div>
                    </div>
                `).join('');
                document.getElementById('userDetailSharedReviews').innerHTML = sharedRows || '<div class="text-muted text-center py-3" style="font-size:0.9rem;">No shared review links found.</div>';

                const activityRows = (data.activities || []).map(activity => `
                    <div class="timeline-item">
                        <div style="font-size:0.85rem;">${escapeHtml(activity.text)}</div>
                        <div style="font-size:0.75rem;color:var(--tx3);">${escapeHtml(activity.time)}</div>
                    </div>
                `).join('');
                document.getElementById('userDetailActivities').innerHTML = activityRows || '<div class="text-muted text-center py-3" style="font-size:0.9rem;">No activity recorded.</div>';

                var modal = new bootstrap.Modal(document.getElementById('userDetailModal'));
                modal.show();
            })
            .catch(err => {
                console.error('Error fetching user details:', err);
                alert('Unable to load this user right now. Please refresh and try again.');
            });
    }
</script>
@endsection
