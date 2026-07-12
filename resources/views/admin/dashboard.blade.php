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
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .premium-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    .progress-track {
        background: var(--bd, rgba(255,255,255,0.1));
        border-radius: 10px;
        height: 8px;
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-fill {
        height: 100%;
        border-radius: 10px;
    }
    .stat-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.8rem;
    }
    .stat-badge.success { background: rgba(52, 211, 153, 0.15); color: #34d399; }
    .stat-badge.warning { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .stat-badge.danger { background: var(--danger-bg); color: var(--danger-tx); }
    .stat-badge.primary { background: rgba(96, 165, 250, 0.15); color: #60a5fa; }
    
    .quick-action-btn {
        background: var(--bg3, #2b2b40);
        border: 1px solid var(--bd, rgba(255,255,255,0.1));
        color: var(--tx, #e0e0e0);
        border-radius: 12px;
        padding: 10px 16px;
        font-size: 0.9rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        text-decoration: none;
    }
    .quick-action-btn:hover {
        background: var(--pur, #3b82f6);
        border-color: var(--pur, #3b82f6);
        color: #fff;
    }
    
    .activity-timeline {
        position: relative;
        padding-left: 20px;
    }
    .activity-timeline::before {
        content: '';
        position: absolute;
        left: 5px;
        top: 5px;
        bottom: 5px;
        width: 2px;
        background: var(--bd);
    }
    .activity-item {
        position: relative;
        margin-bottom: 20px;
    }
    .activity-item::before {
        content: '';
        position: absolute;
        left: -20px;
        top: 4px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #3b82f6;
        border: 2px solid var(--sf);
    }
    .activity-item:last-child { margin-bottom: 0; }
    
    .chart-container {
        position: relative;
        height: 250px;
        width: 100%;
    }

    .admin-dashboard-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--tx);
    }
    .admin-dashboard-title i {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(59, 130, 246, 0.14);
        color: #3b82f6;
        margin-right: 0 !important;
        flex: 0 0 auto;
    }
    .mobile-action-btn,
    .premium-card .btn {
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
    }
    
    /* Mobile Responsive Tables to Cards */
    @media (max-width: 767.98px) {
        #sec-overview {
            --admin-mobile-gap: 12px;
        }
        #sec-overview > .d-flex:first-of-type {
            margin-bottom: 14px !important;
        }
        .admin-dashboard-title {
            font-size: 1.18rem !important;
            line-height: 1.2;
            margin-bottom: 6px !important;
        }
        .admin-dashboard-title i {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            font-size: 0.98rem;
        }
        .admin-dashboard-subtitle {
            font-size: 0.78rem !important;
            line-height: 1.45;
            max-width: 30rem;
        }
        .premium-card {
            border-radius: 14px !important;
            padding: 14px !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        .premium-card:hover {
            transform: none;
        }
        .dashboard-overview-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 8px !important;
            margin-right: 0 !important;
            margin-left: 0 !important;
            margin-bottom: 14px !important;
        }
        .dashboard-overview-grid > .col-6 {
            width: 100% !important;
            max-width: 100% !important;
            padding-right: 0 !important;
            padding-left: 0 !important;
        }
        .overview-card {
            aspect-ratio: auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 8px 4px !important;
            width: 100%;
            max-width: none;
            margin: 0 auto;
            min-height: 88px;
            border-radius: 12px !important;
        }
        .overview-card > div:nth-child(1) {
            width: 26px;
            height: 24px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.86rem !important;
            margin-bottom: 5px !important;
            background: transparent;
        }
        .overview-card > div:nth-child(2) {
            font-size: 1rem !important;
            line-height: 1.1;
            margin-bottom: 3px !important;
        }
        .overview-card > div:nth-child(3) {
            font-size: 0.48rem !important;
            line-height: 1.25;
            text-align: center;
            word-wrap: break-word;
            white-space: normal;
            letter-spacing: 0.01em !important;
            max-width: 100%;
        }
        .row.g-4,
        .row.g-3 {
            --bs-gutter-x: var(--admin-mobile-gap);
            --bs-gutter-y: var(--admin-mobile-gap);
        }
        .chart-container {
            height: 188px !important;
        }
        .premium-card h6 {
            font-size: 0.9rem;
            line-height: 1.3;
            margin-bottom: 12px !important;
        }
        .premium-card .d-flex.justify-content-between.align-items-center.mb-3 {
            align-items: flex-start !important;
            gap: 10px;
        }
        .premium-card .btn,
        .mobile-action-btn {
            min-height: 36px;
            border-radius: 11px !important;
            padding: 7px 10px !important;
            font-size: 0.76rem !important;
            line-height: 1.15;
        }
        .premium-card .btn-sm {
            min-width: 36px;
        }
        .admin-dashboard-mini-action {
            min-height: 32px !important;
            min-width: 32px !important;
            width: auto !important;
            padding: 6px 10px !important;
            border-radius: 9px !important;
            font-size: 0.72rem !important;
        }
        .admin-dashboard-icon-action {
            width: 32px !important;
            min-width: 32px !important;
            height: 32px !important;
            min-height: 32px !important;
            padding: 0 !important;
            border-radius: 9px !important;
            font-size: 0.72rem !important;
            flex: 0 0 32px;
        }
        .stat-badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            border-radius: 9px;
            padding: 5px 8px;
            font-size: 0.68rem;
            line-height: 1.15;
            white-space: normal;
        }
        #sec-overview .table-responsive {
            overflow: visible !important;
        }
        #sec-overview .custom-table thead {
            display: none;
        }
        #sec-overview .custom-table,
        #sec-overview .custom-table tbody,
        #sec-overview .custom-table tr,
        #sec-overview .custom-table td {
            display: block;
            width: 100% !important;
        }
        #sec-overview .custom-table tr {
            border: 1px solid var(--bd);
            border-radius: 13px;
            background: var(--bg3);
            padding: 10px;
            margin-bottom: 10px;
        }
        #sec-overview .custom-table td {
            border: 0 !important;
            padding: 7px 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: var(--tx);
            text-align: right !important;
        }
        #sec-overview .custom-table td::before {
            content: attr(data-label);
            color: var(--tx3);
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            flex: 0 0 auto;
            text-align: left;
        }
        #sec-overview .custom-table td[colspan] {
            display: block;
            text-align: center !important;
        }
        #sec-overview .custom-table td[colspan]::before {
            content: '';
            display: none;
        }
        .activity-timeline {
            padding-left: 16px;
        }
        .activity-item {
            margin-bottom: 14px;
        }
        .activity-item::before {
            left: -16px;
            width: 10px;
            height: 10px;
        }
        #commsTabs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        #commsTabs .nav-item,
        #commsTabs .nav-link {
            width: 100%;
        }
        #commsTabs .nav-link {
            min-height: 40px;
            font-size: 0.78rem !important;
        }
        #announcements > .d-flex {
            align-items: flex-start !important;
            gap: 10px;
            padding: 10px !important;
        }
        #announcements > .d-flex > div {
            min-width: 0;
            font-size: 0.78rem !important;
            line-height: 1.35;
        }
        .form-select {
            min-height: 42px;
            border-radius: 11px;
            font-size: 0.82rem;
        }
        .badge {
            white-space: normal;
            line-height: 1.25;
            padding: 7px 9px;
            border-radius: 9px;
        }
    }

    @media (max-width: 380px) {
        .dashboard-overview-grid {
            gap: 7px !important;
        }
        .overview-card {
            min-height: 82px;
            padding: 7px 3px !important;
        }
        .overview-card > div:nth-child(1) {
            width: 22px;
            height: 21px;
            font-size: 0.78rem !important;
            margin-bottom: 4px !important;
        }
        .overview-card > div:nth-child(2) {
            font-size: 0.92rem !important;
        }
        .overview-card > div:nth-child(3) {
            font-size: 0.43rem !important;
        }
    }
</style>

<div class="db-section active" id="sec-overview">
    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="admin-dashboard-title fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-chart-pie"></i>Admin Dashboard</h4>
            <p class="admin-dashboard-subtitle" style="font-size:0.95rem;color:var(--tx2);margin:0;">System overview, user analytics, and platform health.</p>
        </div>
    </div>

    <!-- Feature 1: Dashboard Overview Cards -->
    <div class="row g-3 mb-4 dashboard-overview-grid">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card overview-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#3b82f6;margin-bottom:8px;"><i class="fa-solid fa-users"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($registeredUsersCount) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Registered Users</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card overview-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#34d399;margin-bottom:8px;"><i class="fa-solid fa-user-check"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($activeTodayCount) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Active Today</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card overview-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#60a5fa;margin-bottom:8px;"><i class="fa-solid fa-microphone"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($mockInterviewsCount) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Mock Interviews</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card overview-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#fbbf24;margin-bottom:8px;"><i class="fa-solid fa-robot"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($aiFeedbacksCount) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">AI Feedbacks</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card overview-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#f472b6;margin-bottom:8px;"><i class="fa-solid fa-graduation-cap"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($modulesCompletedCount) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Modules Completed</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card overview-card text-center p-3 h-100" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(52,211,153,0.1) 100%); border-color: rgba(52,211,153,0.3);">
                <div style="font-size:1.5rem;color:#34d399;margin-bottom:8px;"><i class="fa-solid fa-server"></i></div>
                <div style="font-size:1.5rem;font-weight:700;color:#34d399;">99.9%</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">System Online</div>
            </div>
        </div>
    </div>

    <!-- Analytics Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Feature 2: User Analytics (Line Chart) -->
        <div class="col-lg-6">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-4">User Growth Trend</h6>
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Feature 3: Interview Analytics (Donut Chart) -->
        <div class="col-lg-3">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-4">Interview Categories</h6>
                <div class="chart-container" style="height: 200px;">
                    <canvas id="categoryDonutChart"></canvas>
                </div>
                <div class="text-center mt-3" style="font-size: 0.8rem; color:var(--tx2);">Job Interview is the most active.</div>
            </div>
        </div>
        <!-- Feature 5: Readiness Score Analytics (Bar Chart) -->
        <div class="col-lg-3">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-4">Readiness Distribution</h6>
                <div class="chart-container" style="height: 200px;">
                    <canvas id="readinessBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT COLUMN (Main Content) -->
        <div class="col-lg-8">
            
            <!-- Feature 6: Recent Interview Sessions -->
            <div class="premium-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold m-0">Recent Interview Sessions</h6>
                    <a href="{{ route('admin.sessions.index') }}" class="btn btn-sm mobile-action-btn admin-dashboard-mini-action" style="border-radius:8px;border:1px solid var(--bd);color:var(--tx2);background:var(--bg3);"><i class="fa-solid fa-list"></i>View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table custom-table mb-0 w-100">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Category</th>
                                <th>Score</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSessions as $session)
                            <tr>
                                <td data-label="User">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:32px;height:32px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:0.8rem;">
                                            {{ strtoupper(substr($session->user->name ?? 'U', 0, 2)) }}
                                        </div>
                                        <span>{{ $session->user->name ?? 'Unknown' }}</span>
                                    </div>
                                </td>
                                <td data-label="Category"><span class="stat-badge primary">{{ $session->category->title ?? 'N/A' }}</span></td>
                                <td data-label="Score"><span class="fw-bold text-success">{{ $session->score->overall_readiness_score ?? 'N/A' }}%</span></td>
                                <td data-label="Date" style="color:var(--tx2);">{{ $session->created_at->format('M d, Y') }}</td>
                                <td data-label="Actions" class="text-end">
                                    <a href="{{ route('admin.sessions.show', $session) }}" class="btn btn-sm admin-dashboard-icon-action" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);" title="View Session" aria-label="View Session"><i class="fa-solid fa-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No recent sessions found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Feature 14: Activity Logs & Feature 10: Top Users -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="premium-card h-100">
                        <h6 class="fw-bold mb-4">Activity Logs</h6>
                        <div class="activity-timeline">
                            @forelse($recentActivities as $activity)
                            <div class="activity-item">
                                <div style="font-size:0.85rem;color:var(--tx);"><strong>{{ $activity['text'] }}</strong></div>
                                <div style="font-size:0.75rem;color:var(--tx3);">{{ $activity['time'] }}</div>
                            </div>
                            @empty
                            <div class="activity-item">
                                <div style="font-size:0.85rem;color:var(--tx3);">No recent activity</div>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="premium-card h-100">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-trophy me-2 text-warning"></i>Leaderboard</h6>
                        @forelse($leaderboard as $index => $score)
                        <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded" style="background:{{ $index === 0 ? 'rgba(251,191,36,0.1)' : 'var(--bg3)' }};border:1px solid {{ $index === 0 ? 'rgba(251,191,36,0.3)' : 'transparent' }};">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold" style="color:{{ $index === 0 ? '#fbbf24' : 'var(--tx3)' }};width:20px;">{{ $index + 1 }}</span>
                                <div style="width:30px;height:30px;border-radius:50%;background:#{{ $index === 0 ? '3b82f6' : ($index === 1 ? '3b82f6' : '10b981') }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;">
                                    {{ strtoupper(substr($score->user_name, 0, 2)) }}
                                </div>
                                <span style="font-size:0.9rem;">{{ $score->user_name }}</span>
                            </div>
                            <span class="fw-bold text-success">{{ $score->overall_readiness_score }}%</span>
                        </div>
                        @empty
                        <div class="text-center text-muted mt-4">No scores available yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Feature 11: Users Needing Improvement -->
            <div class="premium-card mb-4" style="border-left: 4px solid var(--danger-tx); background: var(--danger-bg);">
                <h6 class="fw-bold mb-3" style="color: var(--danger-tx);"><i class="fa-solid fa-triangle-exclamation me-2"></i>Users Needing Support</h6>
                <div class="table-responsive">
                    <table class="table custom-table mb-0 w-100">
                        <tbody>
                            @forelse($usersNeedingSupport as $score)
                            <tr>
                                <td data-label="User">{{ $score->user_name }}</td>
                                <td data-label="Status"><span class="stat-badge danger">Low Readiness ({{ $score->overall_readiness_score }}%)</span></td>
                                <td data-label="Note">Needs more practice</td>
                                <td data-label="Actions" class="text-end"><button class="btn btn-sm" style="border-radius:8px;border:1px solid #f87171;color:#f87171;background:rgba(248,113,113,0.1);">Message</button></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No users currently needing urgent support.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Feature 12 & 13: Communications Center -->
            <div class="premium-card mb-4">
                <ul class="nav nav-pills mb-3" id="commsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#announcements" type="button" style="border-radius:8px;font-size:0.9rem;">Announcements</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#notifications" type="button" style="border-radius:8px;font-size:0.9rem;">Notifications</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="announcements">
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background:var(--bg3);">
                            <div style="font-size:0.9rem;"><span class="stat-badge success me-2">Active</span> Scheduled Maintenance on Oct 20.</div>
                            <button class="btn btn-sm text-primary"><i class="fa-solid fa-pen"></i></button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--bg3);">
                            <div style="font-size:0.9rem;"><span class="stat-badge danger me-2">Expired</span> Welcome to SpeakReady Beta.</div>
                            <button class="btn btn-sm text-primary"><i class="fa-solid fa-pen"></i></button>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="notifications">
                        <div class="row text-center mt-3">
                            <div class="col-4">
                                <h4 class="fw-bold text-primary mb-0">1,200</h4><span style="font-size:0.75rem;color:var(--tx3)">Sent</span>
                            </div>
                            <div class="col-4">
                                <h4 class="fw-bold text-warning mb-0">450</h4><span style="font-size:0.75rem;color:var(--tx3)">Unread</span>
                            </div>
                            <div class="col-4">
                                <h4 class="fw-bold text-success mb-0">12</h4><span style="font-size:0.75rem;color:var(--tx3)">Broadcasts</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN (Sidebar) -->
        <div class="col-lg-4">

            <!-- Feature 7: AI Usage Monitoring -->
            <div class="premium-card mb-4" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(59,130,246,0.05) 100%);">
                <h6 class="fw-bold mb-4"><i class="fa-solid fa-microchip me-2 text-primary"></i>AI Usage Monitoring</h6>
                
                <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;">
                    <span style="color:var(--tx2);">Total API Requests</span>
                    <span class="fw-bold">15,000</span>
                </div>
                <div class="d-flex justify-content-between mb-3" style="font-size:0.85rem;">
                    <span style="color:var(--tx2);">Today's Requests</span>
                    <span class="fw-bold text-primary">520</span>
                </div>
                
                <div class="p-3 mb-3" style="background:var(--bg3);border-radius:12px;border:1px solid var(--bd);">
                    <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Avg Response Time</div>
                    <div class="fw-bold" style="font-size:1.5rem;color:#34d399;">1.5s</div>
                </div>
                
                <h6 style="font-size:0.85rem;color:var(--tx2);margin-bottom:10px;">Provider Status</h6>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div style="font-size:0.85rem;"><i class="fa-solid fa-robot me-2"></i>OpenAI</div>
                    <span class="stat-badge success">Active</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div style="font-size:0.85rem;"><i class="fa-solid fa-sparkles me-2"></i>Gemini</div>
                    <span class="stat-badge success">Active</span>
                </div>
            </div>

            <!-- Feature 4: Interview Performance Analytics -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-4">Avg Performance Metrics</h6>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Clarity</span><span class="fw-bold">{{ $avgClarity }}%</span></div>
                    <div class="progress-track"><div class="progress-fill" style="width:{{ $avgClarity }}%;background:#34d399;"></div></div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Relevance</span><span class="fw-bold">{{ $avgRelevance }}%</span></div>
                    <div class="progress-track"><div class="progress-fill" style="width:{{ $avgRelevance }}%;background:#60a5fa;"></div></div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Grammar</span><span class="fw-bold">{{ $avgGrammar }}%</span></div>
                    <div class="progress-track"><div class="progress-fill" style="width:{{ $avgGrammar }}%;background:#3b82f6;"></div></div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Professionalism</span><span class="fw-bold">{{ $avgProfessionalism }}%</span></div>
                    <div class="progress-track"><div class="progress-fill" style="width:{{ $avgProfessionalism }}%;background:#fbbf24;"></div></div>
                </div>
            </div>

            <!-- Feature 15: System Monitoring -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-server me-2 text-info"></i>System Health</h6>
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background:var(--bg3);">
                    <span style="font-size:0.85rem;">Server</span>
                    <span class="text-success fw-bold" style="font-size:0.85rem;">Online</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background:var(--bg3);">
                    <span style="font-size:0.85rem;">Database</span>
                    <span class="text-success fw-bold" style="font-size:0.85rem;">Connected</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background:var(--bg3);">
                    <span style="font-size:0.85rem;">AI API</span>
                    <span class="text-success fw-bold" style="font-size:0.85rem;">Operational</span>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Storage Usage</span><span class="text-warning fw-bold">65%</span></div>
                    <div class="progress-track" style="height:6px;"><div class="progress-fill" style="width:65%;background:#fbbf24;"></div></div>
                </div>
            </div>

            <!-- Feature 8: Question Bank Statistics -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-3">Question Bank Stat</h6>
                <div class="text-center mb-3">
                    <h3 class="fw-bold text-primary mb-0">500</h3>
                    <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Total Questions</div>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <span class="badge" style="background:var(--bg3);color:var(--tx);border:1px solid var(--bd);">Behavioral: 150</span>
                    <span class="badge" style="background:var(--bg3);color:var(--tx);border:1px solid var(--bd);">Technical: 180</span>
                    <span class="badge" style="background:var(--bg3);color:var(--tx);border:1px solid var(--bd);">Situational: 100</span>
                    <span class="badge" style="background:var(--bg3);color:var(--tx);border:1px solid var(--bd);">Personal: 70</span>
                </div>
            </div>

            <!-- Feature 9: Learning Lab Analytics -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-3">Learning Lab Stats</h6>
                <div class="p-2 mb-2 rounded" style="background:var(--bg3);border:1px solid var(--bd);">
                    <div style="font-size:0.75rem;color:var(--tx3);">Most Viewed & Completed</div>
                    <div class="fw-bold" style="color:var(--tx);font-size:0.9rem;">Communication Skills</div>
                </div>
                <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--bg3);border:1px solid var(--bd);">
                    <div style="font-size:0.85rem;color:var(--tx2);">Avg Completion Rate</div>
                    <div class="fw-bold text-success">95%</div>
                </div>
            </div>

            <!-- Feature 17: Reports & Exports -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-file-export me-2 text-primary"></i>Generate Reports</h6>
                <select class="form-select mb-3" style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);">
                    <option>User Reports</option>
                    <option>Interview Reports</option>
                    <option>AI Usage Reports</option>
                    <option>Analytics Reports</option>
                </select>
                <div class="d-flex gap-2">
                    <button class="btn w-100 btn-sm" style="background:rgba(248,113,113,0.1);color:#f87171;border:1px solid rgba(248,113,113,0.2);">PDF</button>
                    <button class="btn w-100 btn-sm" style="background:rgba(52,211,153,0.1);color:#34d399;border:1px solid rgba(52,211,153,0.2);">Excel</button>
                    <button class="btn w-100 btn-sm" style="background:rgba(96,165,250,0.1);color:#60a5fa;border:1px solid rgba(96,165,250,0.2);">CSV</button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Scripts for Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof window.Chart === 'undefined') return;

    Chart.defaults.color = '#808090';
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Feature 2: User Analytics (Line Chart)
    const userCanvas = document.getElementById('userGrowthChart');
    if (!userCanvas) return;
    const userCtx = userCanvas.getContext('2d');
    let gradientLine = userCtx.createLinearGradient(0, 0, 0, 300);
    gradientLine.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
    gradientLine.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

    const userGrowthLabels = {!! json_encode($userGrowthLabels) !!};
    const userGrowthDataValues = {!! json_encode($userGrowthData) !!};

    new Chart(userCtx, {
        type: 'line',
        data: {
            labels: userGrowthLabels,
            datasets: [{
                label: 'New Registrations',
                data: userGrowthDataValues,
                borderColor: '#3b82f6',
                backgroundColor: gradientLine,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#1e1e2d',
                pointBorderColor: '#3b82f6',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Feature 3: Interview Analytics (Donut Chart)
    const catCanvas = document.getElementById('categoryDonutChart');
    if (!catCanvas) return;
    const catCtx = catCanvas.getContext('2d');
    const chartLabels = {!! json_encode($chartLabels) !!};
    const chartDataValues = {!! json_encode($chartData) !!};

    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: chartLabels.length > 0 ? chartLabels : ['No Data'],
            datasets: [{
                data: chartDataValues.length > 0 ? chartDataValues : [1],
                backgroundColor: ['#3b82f6', '#34d399', '#fbbf24', '#60a5fa', '#f472b6', '#a855f7'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 10, font: {size:10} } }
            }
        }
    });

    // Feature 5: Readiness Score Analytics (Bar Chart)
    const readCanvas = document.getElementById('readinessBarChart');
    if (!readCanvas) return;
    const readCtx = readCanvas.getContext('2d');
    const readinessDataValues = {!! json_encode($readinessData) !!};

    new Chart(readCtx, {
        type: 'bar',
        data: {
            labels: ['Highly Acc.', 'Acceptable', 'Needs Imp.', 'Poor'],
            datasets: [{
                label: 'Users',
                data: readinessDataValues,
                backgroundColor: ['#34d399', '#60a5fa', '#fbbf24', '#f87171'],
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endsection
