@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/' . (($isMobile ?? false) ? 'mobile' : 'desktop') . '/admin/sessions/index.css?v=1') }}" data-page-style="admin-sessions-index">
@endpush

@section('content')

<div class="db-section active">
    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="session-page-title fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-video"></i> Philippines Interview Session Monitoring</h4>
            <p class="session-page-subtitle" style="font-size:0.95rem;color:var(--tx2);margin:0;">Track and analyze Philippine interview practice performance and activity.</p>
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
                <button type="button" class="btn session-top-action" style="background:rgba(248,113,113,0.12);border:1px solid rgba(248,113,113,0.35);color:#f87171;border-radius:12px;" data-session-delete-trigger data-session-delete-form="clearSessionsForm" data-session-delete-title="Clear all Philippines interview sessions?" data-session-delete-message="This will delete all Philippines interview sessions, including archived sessions. This cannot be undone.">
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
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Total PH Sessions</div>
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
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Completed PH Interviews</div>
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
                <h6 class="fw-bold mb-4">Daily PH Interview Trend</h6>
                <div class="chart-container">
                    <canvas id="dailySessionChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-4">Most Used PH Category</h6>
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
            <h6 class="fw-bold m-0">All Philippines Interview Sessions</h6>
        </div>
        
        <form method="GET" action="{{ route('admin.sessions.index') }}" class="session-filter-form row g-2 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search user or PH session ID..." value="{{ request('search') }}" style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);">
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
                                <button type="button" class="btn btn-sm session-row-action" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);" title="Archive" aria-label="Archive" data-session-archive-trigger data-session-archive-form="archiveSessionForm{{ $session->id }}" data-session-archive-title="Archive Philippines interview session #{{ $session->id }}?" data-session-archive-message="This Philippines interview session will move to the archive and can be restored later."><i class="fa-solid fa-box-archive text-warning"></i></button>
                            </form>
                            <form action="{{ route('admin.sessions.destroy', $session->id) }}" method="POST" class="d-inline" id="deleteSessionForm{{ $session->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm session-row-action" style="background:var(--bg3);color:#f87171;border:1px solid rgba(248,113,113,0.35);" title="Delete" aria-label="Delete" data-session-delete-trigger data-session-delete-form="deleteSessionForm{{ $session->id }}" data-session-delete-title="Delete Philippines interview session #{{ $session->id }}?" data-session-delete-message="This Philippines interview session and its related records will be permanently deleted. This cannot be undone."><i class="fa-solid fa-trash-can"></i></button>
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

