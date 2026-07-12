@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<style>
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
    .stat-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.8rem;
    }
    .stat-badge.success { background: rgba(52, 211, 153, 0.15); color: #34d399; }
    .stat-badge.warning { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .stat-badge.danger { background: rgba(248, 113, 113, 0.15); color: #f87171; }
    .stat-badge.primary { background: rgba(96, 165, 250, 0.15); color: #60a5fa; }
    .stat-badge.secondary { background: rgba(156, 163, 175, 0.15); color: #9ca3af; }
    .chart-container { position: relative; height: 250px; width: 100%; }
    .session-page-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--tx);
    }
    .session-page-title i {
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
    .session-top-action,
    .session-filter-btn,
    .session-row-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
    }
    .session-confirm-modal .modal-content {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        color: var(--tx);
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.35);
    }
    .session-confirm-modal .modal-header,
    .session-confirm-modal .modal-footer {
        border-color: var(--bd);
    }
    .session-confirm-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(248, 113, 113, 0.14);
        color: #f87171;
        flex: 0 0 auto;
    }
    .session-confirm-icon.archive {
        background: rgba(251, 191, 36, 0.14);
        color: #fbbf24;
    }

    /* Mobile Card-based Table Layout for Main Sessions Table */
    @media (max-width: 767px) {
        .db-section > .d-flex:first-of-type {
            margin-bottom: 14px !important;
        }
        .session-page-title {
            justify-content: center;
            font-size: clamp(1.04rem, 5vw, 1.14rem) !important;
            line-height: 1.14;
            margin-bottom: 6px !important;
            max-width: 19rem;
            text-wrap: balance;
        }
        .session-page-title i {
            width: 30px;
            height: 30px;
            border-radius: 11px;
            font-size: 0.84rem;
        }
        .session-page-subtitle {
            font-size: 0.78rem !important;
            line-height: 1.45;
        }
        .session-header-actions {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px !important;
            width: 100%;
        }
        .session-header-actions > *,
        .session-header-actions form,
        .session-header-actions .btn {
            width: 100%;
            min-width: 0;
        }
        .session-header-actions form:last-child {
            grid-column: 1 / -1;
        }
        .session-top-action {
            min-height: 40px;
            border-radius: 11px !important;
            padding: 8px 10px !important;
            font-size: 0.76rem;
            line-height: 1.15;
            white-space: normal;
        }
        .session-top-action i {
            margin-right: 0 !important;
            font-size: 0.82rem;
        }
        .premium-card {
            border-radius: 14px !important;
            padding: 14px !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        .premium-card:hover {
            transform: none;
        }
        .session-stat-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 10px !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            margin-bottom: 14px !important;
        }
        .session-stat-grid > [class*="col-"] {
            width: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .session-stat-card {
            min-height: 126px;
            border-radius: 16px !important;
            padding: 10px !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .session-stat-card > div:first-child {
            width: 38px;
            height: 38px;
            border-radius: 13px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.04rem !important;
            margin-bottom: 8px !important;
            background: rgba(255, 255, 255, 0.06);
        }
        .session-stat-card > div:nth-child(2) {
            font-size: 1.22rem !important;
            line-height: 1.1;
            margin-bottom: 5px;
        }
        .session-stat-card > div:nth-child(3) {
            font-size: 0.6rem !important;
            line-height: 1.25;
            letter-spacing: 0.03em !important;
        }
        .row.g-4,
        .row.g-3 {
            --bs-gutter-x: 12px;
            --bs-gutter-y: 12px;
        }
        .chart-container {
            height: 188px !important;
        }
        .premium-card h6 {
            font-size: 0.9rem;
            line-height: 1.3;
            margin-bottom: 12px !important;
        }
        .session-filter-form {
            margin-bottom: 14px !important;
        }
        .session-filter-form .form-control,
        .session-filter-form .form-select {
            min-height: 42px;
            border-radius: 11px;
            font-size: 0.82rem;
        }
        .session-filter-btn {
            min-height: 42px;
            border-radius: 11px !important;
            font-size: 0.82rem;
            font-weight: 700;
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
        #mainSessionsTableWrapper {
            overflow-x: visible !important;
            -webkit-overflow-scrolling: auto !important;
        }
        #mainSessionsTable thead {
            display: none;
        }
        #mainSessionsTable tbody tr {
            display: flex;
            flex-direction: column;
            background: var(--bg3);
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--bd);
            padding: 11px;
        }
        #mainSessionsTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 7px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            text-align: right;
        }
        #mainSessionsTable tbody td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            gap: 8px;
            padding-top: 12px !important;
        }
        #mainSessionsTable tbody td::before {
            font-size: 0.68rem;
            color: var(--tx3);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: left;
        }
        #mainSessionsTable tbody td:nth-child(1)::before { content: "ID"; }
        #mainSessionsTable tbody td:nth-child(3)::before { content: "Category"; }
        #mainSessionsTable tbody td:nth-child(4)::before { content: "Status"; }
        #mainSessionsTable tbody td:nth-child(5)::before { content: "Score"; }
        #mainSessionsTable tbody td:nth-child(6)::before { content: "Duration"; }
        #mainSessionsTable tbody td:nth-child(7)::before { content: "Date"; }
        
        #mainSessionsTable tbody td:nth-child(2) {
            order: -1;
            justify-content: flex-start;
            border-bottom: 1px solid var(--bd);
            padding-bottom: 12px !important;
            margin-bottom: 4px;
        }
        #mainSessionsTable tbody td:nth-child(2)::before { content: none; }
        #mainSessionsTable tbody td:nth-child(2) .d-flex {
            width: 100%;
            flex-direction: row;
            justify-content: flex-end;
            text-align: right;
        }
        #mainSessionsTable tbody td:nth-child(2) span {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        #mainSessionsTable tbody td:nth-child(2) .d-flex > div:first-child {
            order: 2;
            margin-left: 8px;
            flex: 0 0 auto;
        }
        #mainSessionsTable tbody td:nth-child(2) .d-flex > span {
            order: 1;
            flex: 0 1 auto;
        }
        #mainSessionsTable tbody td.text-end {
            text-align: right;
        }
        .session-row-action {
            width: 34px !important;
            height: 34px !important;
            min-width: 34px !important;
            min-height: 34px !important;
            padding: 0 !important;
            border-radius: 10px !important;
            font-size: 0.76rem !important;
        }
        #mainSessionsTable tbody td:last-child form {
            display: inline-flex !important;
        }
        .pagination {
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
        }
        .page-link {
            min-width: 34px;
            min-height: 34px;
            border-radius: 9px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
        }
    }

    @media (max-width: 380px) {
        .session-stat-grid {
            gap: 8px !important;
        }
        .session-stat-card {
            min-height: 116px;
            padding: 8px !important;
        }
        .session-stat-card > div:nth-child(2) {
            font-size: 1.08rem !important;
        }
        .session-stat-card > div:nth-child(3) {
            font-size: 0.56rem !important;
        }
    }
</style>

<div class="db-section active">
    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="session-page-title fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-video"></i>Session Monitoring</h4>
            <p class="session-page-subtitle" style="font-size:0.95rem;color:var(--tx2);margin:0;">Track and analyze interview session performance and activity.</p>
        </div>
        <div class="session-header-actions d-flex flex-wrap gap-2">
            <a href="{{ route('admin.sessions.export', request()->query()) }}" class="btn session-top-action" style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);border-radius:12px;">
                <i class="fa-solid fa-file-export text-success me-2"></i>Export CSV
            </a>
            <a href="{{ route('admin.sessions.archive') }}" class="btn session-top-action" style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);border-radius:12px;">
                <i class="fa-solid fa-box-archive text-warning me-2"></i>Archive
            </a>
            @if($totalSessions > 0)
            <form action="{{ route('admin.sessions.clear') }}" method="POST" id="clearSessionsForm">
                @csrf
                @method('DELETE')
                <button type="button" class="btn session-top-action" style="background:rgba(248,113,113,0.12);border:1px solid rgba(248,113,113,0.35);color:#f87171;border-radius:12px;" data-session-delete-trigger data-session-delete-form="clearSessionsForm" data-session-delete-title="Clear all interview sessions?" data-session-delete-message="This will delete all interview sessions, including archived sessions. This cannot be undone.">
                    <i class="fa-solid fa-broom me-2"></i>Clear All
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row g-3 mb-4 session-stat-grid">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card session-stat-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#3b82f6;margin-bottom:8px;"><i class="fa-solid fa-list-ul"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($totalSessions) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Total Sessions</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card session-stat-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#34d399;margin-bottom:8px;"><i class="fa-solid fa-tower-broadcast"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($activeSessionsToday) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Active Today</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card session-stat-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#10b981;margin-bottom:8px;"><i class="fa-solid fa-check-double"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($completedSessions) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Completed</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card session-stat-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#fbbf24;margin-bottom:8px;"><i class="fa-solid fa-star"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($avgScore, 1) }}%</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Avg Score</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card session-stat-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#a855f7;margin-bottom:8px;"><i class="fa-solid fa-stopwatch"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ gmdate("i:s", $avgDuration) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Avg Duration</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card session-stat-card text-center p-3 h-100" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(96,165,250,0.1) 100%);">
                <div style="font-size:1.5rem;color:#60a5fa;margin-bottom:8px;"><i class="fa-solid fa-percent"></i></div>
                <div style="font-size:1.5rem;font-weight:700;color:#60a5fa;">{{ number_format($sessionCompletionRate, 1) }}%</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Completion Rate</div>
            </div>
        </div>
    </div>

    <!-- Analytics Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-4">Daily Sessions Trend</h6>
                <div class="chart-container">
                    <canvas id="dailySessionChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-4">Most Used Category</h6>
                @if($mostUsedCategory)
                    <div class="text-center mt-4">
                        <div style="font-size:3rem;color:#3b82f6;margin-bottom:10px;"><i class="fa-solid fa-layer-group"></i></div>
                        <h4 class="fw-bold text-primary">{{ $mostUsedCategory->name }}</h4>
                        <div style="font-size:0.9rem;color:var(--tx2);">{{ $mostUsedCategory->total }} Sessions</div>
                    </div>
                @else
                    <div class="text-center text-muted mt-5">No data available</div>
                @endif
            </div>
        </div>
        <div class="col-lg-3">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-4">Readiness Distribution</h6>
                <div class="chart-container" style="height: 200px;">
                    <canvas id="readinessChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Session List -->
    <div class="premium-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-bold m-0">All Sessions</h6>
        </div>
        
        <form method="GET" action="{{ route('admin.sessions.index') }}" class="session-filter-form row g-2 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search user or ID..." value="{{ request('search') }}" style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="abandoned" {{ request('status') == 'abandoned' ? 'selected' : '' }}>Abandoned</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select" style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);">
                    <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Date</option>
                    <option value="score" {{ request('sort') == 'score' ? 'selected' : '' }}>Score</option>
                    <option value="duration_seconds" {{ request('sort') == 'duration_seconds' ? 'selected' : '' }}>Duration</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary session-filter-btn w-100" style="border-radius:8px;"><i class="fa-solid fa-filter"></i>Filter</button>
            </div>
        </form>

        <div class="table-responsive" id="mainSessionsTableWrapper">
            <table class="table custom-table mb-0 w-100" id="mainSessionsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Duration</th>
                        <th>Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    <tr>
                        <td>#{{ $session->id }}</td>
                        <td>
                            @if($session->user)
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:32px;height:32px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:0.8rem;">
                                        {{ strtoupper(substr($session->user->name, 0, 2)) }}
                                    </div>
                                    <span>{{ $session->user->name }}</span>
                                </div>
                            @else
                                <span class="text-muted">Deleted User</span>
                            @endif
                        </td>
                        <td>{{ $session->category ? $session->category->title : 'N/A' }}</td>
                        <td>
                            @if($session->status == 'completed')
                                <span class="stat-badge success">Completed</span>
                            @elseif($session->status == 'pending')
                                <span class="stat-badge warning">In Progress</span>
                            @elseif($session->status == 'abandoned')
                                <span class="stat-badge danger">Abandoned</span>
                            @else
                                <span class="stat-badge secondary">{{ ucfirst($session->status) }}</span>
                            @endif
                            
                            @if($session->flag_reason)
                                <i class="fa-solid fa-flag text-danger ms-1" title="Flagged: {{ $session->flag_reason }}"></i>
                            @endif
                        </td>
                        <td>
                            @if($session->score)
                                <span class="fw-bold {{ $session->score->overall_readiness_score >= 80 ? 'text-success' : ($session->score->overall_readiness_score >= 60 ? 'text-warning' : 'text-danger') }}">
                                    {{ $session->score->overall_readiness_score }}%
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ gmdate("i:s", $session->duration_seconds) }}</td>
                        <td style="color:var(--tx2);">{{ $session->created_at->format('M d, Y h:i A') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.sessions.show', $session->id) }}" class="btn btn-sm session-row-action" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);" title="View Details" aria-label="View Details"><i class="fa-solid fa-eye"></i></a>
                            <form action="{{ route('admin.sessions.doArchive', $session->id) }}" method="POST" class="d-inline" id="archiveSessionForm{{ $session->id }}">
                                @csrf
                                <button type="button" class="btn btn-sm session-row-action" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);" title="Archive" aria-label="Archive" data-session-archive-trigger data-session-archive-form="archiveSessionForm{{ $session->id }}" data-session-archive-title="Archive interview session #{{ $session->id }}?" data-session-archive-message="This session will move to the archive and can be restored later."><i class="fa-solid fa-box-archive text-warning"></i></button>
                            </form>
                            <form action="{{ route('admin.sessions.destroy', $session->id) }}" method="POST" class="d-inline" id="deleteSessionForm{{ $session->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm session-row-action" style="background:var(--bg3);color:#f87171;border:1px solid rgba(248,113,113,0.35);" title="Delete" aria-label="Delete" data-session-delete-trigger data-session-delete-form="deleteSessionForm{{ $session->id }}" data-session-delete-title="Delete interview session #{{ $session->id }}?" data-session-delete-message="This session and its related records will be permanently deleted. This cannot be undone."><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No sessions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $sessions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<div class="modal fade session-confirm-modal" id="sessionDeleteConfirmModal" tabindex="-1" aria-labelledby="sessionDeleteConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="session-confirm-icon"><i class="fa-solid fa-trash-can"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="sessionDeleteConfirmTitle">Delete session?</h5>
                        <div style="font-size:.8rem;color:var(--tx3);">Please confirm this destructive action.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body">
                <p id="sessionDeleteConfirmMessage" style="margin:0;color:var(--tx2);line-height:1.5;">This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="sessionDeleteConfirmButton"><i class="fa-solid fa-trash-can me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade session-confirm-modal" id="sessionArchiveConfirmModal" tabindex="-1" aria-labelledby="sessionArchiveConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="session-confirm-icon archive"><i class="fa-solid fa-box-archive"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="sessionArchiveConfirmTitle">Archive session?</h5>
                        <div style="font-size:.8rem;color:var(--tx3);">Please confirm this archive action.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body">
                <p id="sessionArchiveConfirmMessage" style="margin:0;color:var(--tx2);line-height:1.5;">This session can be restored later.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="sessionArchiveConfirmButton"><i class="fa-solid fa-box-archive me-1"></i>Archive</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const deleteModalEl = document.getElementById('sessionDeleteConfirmModal');
    const deleteTitleEl = document.getElementById('sessionDeleteConfirmTitle');
    const deleteMessageEl = document.getElementById('sessionDeleteConfirmMessage');
    const deleteConfirmButton = document.getElementById('sessionDeleteConfirmButton');
    const archiveModalEl = document.getElementById('sessionArchiveConfirmModal');
    const archiveTitleEl = document.getElementById('sessionArchiveConfirmTitle');
    const archiveMessageEl = document.getElementById('sessionArchiveConfirmMessage');
    const archiveConfirmButton = document.getElementById('sessionArchiveConfirmButton');
    let pendingDeleteForm = null;
    let pendingArchiveForm = null;

    if (deleteModalEl && deleteConfirmButton && typeof bootstrap !== 'undefined') {
        const deleteModal = new bootstrap.Modal(deleteModalEl);

        document.querySelectorAll('[data-session-delete-trigger]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                pendingDeleteForm = document.getElementById(trigger.dataset.sessionDeleteForm || '');
                if (!pendingDeleteForm) return;
                if (deleteTitleEl) deleteTitleEl.textContent = trigger.dataset.sessionDeleteTitle || 'Delete session?';
                if (deleteMessageEl) deleteMessageEl.textContent = trigger.dataset.sessionDeleteMessage || 'This cannot be undone.';
                deleteModal.show();
            });
        });

        deleteConfirmButton.addEventListener('click', () => {
            if (!pendingDeleteForm) return;
            deleteConfirmButton.disabled = true;
            pendingDeleteForm.submit();
        });

        deleteModalEl.addEventListener('hidden.bs.modal', () => {
            pendingDeleteForm = null;
            deleteConfirmButton.disabled = false;
        });
    }

    if (archiveModalEl && archiveConfirmButton && typeof bootstrap !== 'undefined') {
        const archiveModal = new bootstrap.Modal(archiveModalEl);

        document.querySelectorAll('[data-session-archive-trigger]').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                pendingArchiveForm = document.getElementById(trigger.dataset.sessionArchiveForm || '');
                if (!pendingArchiveForm) return;
                if (archiveTitleEl) archiveTitleEl.textContent = trigger.dataset.sessionArchiveTitle || 'Archive session?';
                if (archiveMessageEl) archiveMessageEl.textContent = trigger.dataset.sessionArchiveMessage || 'This session can be restored later.';
                archiveModal.show();
            });
        });

        archiveConfirmButton.addEventListener('click', () => {
            if (!pendingArchiveForm) return;
            archiveConfirmButton.disabled = true;
            pendingArchiveForm.submit();
        });

        archiveModalEl.addEventListener('hidden.bs.modal', () => {
            pendingArchiveForm = null;
            archiveConfirmButton.disabled = false;
        });
    }

    if (typeof window.Chart === 'undefined') return;

    Chart.defaults.color = '#808090';
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Daily Sessions Chart
    const dailyCanvas = document.getElementById('dailySessionChart');
    if (!dailyCanvas) return;
    const dailyCtx = dailyCanvas.getContext('2d');
    const dailyLabels = {!! json_encode($dailySessionCount->pluck('date')->reverse()->values()) !!};
    const dailyData = {!! json_encode($dailySessionCount->pluck('total')->reverse()->values()) !!};
    
    let gradientLine = dailyCtx.createLinearGradient(0, 0, 0, 300);
    gradientLine.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
    gradientLine.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Sessions',
                data: dailyData,
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

    // Readiness Distribution Chart
    const readCanvas = document.getElementById('readinessChart');
    if (!readCanvas) return;
    const readCtx = readCanvas.getContext('2d');
    const readLabels = ['Excellent', 'Good', 'Fair', 'Needs Imp.'];
    const readData = [
        {{ $readinessDistribution['Excellent'] }},
        {{ $readinessDistribution['Good'] }},
        {{ $readinessDistribution['Fair'] }},
        {{ $readinessDistribution['Needs Improvement'] }}
    ];

    new Chart(readCtx, {
        type: 'doughnut',
        data: {
            labels: readLabels,
            datasets: [{
                data: readData,
                backgroundColor: ['#34d399', '#60a5fa', '#fbbf24', '#f87171'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, font: {size:10} } }
            }
        }
    });
});
</script>
@endsection

