@extends('mobile.layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/admin/dashboard.css?v=6') }}" data-page-style="admin-dashboard">
@endpush

@section('content')
<div class="db-section active admin-dashboard-shell" id="sec-overview">
    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="admin-dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="admin-dashboard-title fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-chart-pie"></i><span class="admin-dashboard-title-text">Interview Admin Dashboard</span></h4>
            <p class="admin-dashboard-subtitle" style="font-size:0.95rem;color:var(--tx2);margin:0;">Overview for interview practice, user analytics, and platform health.</p>
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
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($onlineTodayCount) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Online Today</div>
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
                <div style="font-size:1.5rem;color:#34d399;margin-bottom:8px;"><i class="fa-solid fa-arrows-rotate"></i></div>
                <div style="font-size:1.5rem;font-weight:700;color:#34d399;">{{ number_format($userUpdatesCount) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">User Updates</div>
            </div>
        </div>
    </div>

    <!-- Analytics Charts Row -->
    <div class="row g-4 mb-4 dashboard-analytics-grid">
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
                <div class="text-center mt-3" style="font-size: 0.8rem; color:var(--tx2);">local job interview practice is the most active.</div>
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

    <div class="row g-4 dashboard-work-grid">
        <!-- LEFT COLUMN (Main Content) -->
        <div class="col-lg-8 dashboard-primary-column">
            
            <!-- Feature 6: Recent Interview Sessions -->
            <div class="premium-card dashboard-panel dashboard-panel-table mb-4">
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

            @include('shared.admin.dashboard-recent-user-updates')

            <!-- Feature 11: Users Needing Improvement -->
            <div class="premium-card dashboard-panel dashboard-panel-alert mb-4" style="border-left: 4px solid var(--danger-tx); background: var(--danger-bg);">
                <h6 class="fw-bold mb-3" style="color: var(--danger-tx);"><i class="fa-solid fa-triangle-exclamation me-2"></i>Users Needing Support</h6>
                <div class="table-responsive">
                    <table class="table custom-table mb-0 w-100">
                        <tbody>
                            @forelse($usersNeedingSupport as $score)
                            <tr>
                                <td data-label="User">{{ $score->user_name }}</td>
                                <td data-label="Status"><span class="stat-badge danger">Low Readiness ({{ $score->overall_readiness_score }}%)</span></td>
                                <td data-label="Note">Needs more practice</td>
                                <td data-label="Actions" class="text-end"><a href="{{ route('admin.notifications.index', ['compose' => 1, 'user_id' => $score->user_id]) }}" class="btn btn-sm" style="border-radius:8px;border:1px solid #f87171;color:#f87171;background:rgba(248,113,113,0.1);"><i class="fa-solid fa-paper-plane me-1"></i>Notify</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No users currently needing urgent support.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN (Sidebar) -->
        <div class="col-lg-4 dashboard-side-column">

            <!-- Feature 7: AI Usage Monitoring -->
            @include('shared.admin.dashboard-ai-usage')

            <!-- Feature 4: Interview Performance Analytics -->
            <div class="premium-card dashboard-panel dashboard-panel-side dashboard-panel-performance mb-4">
                <h6 class="fw-bold mb-4">Average Interview Performance</h6>
                
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
            <div class="premium-card dashboard-panel dashboard-panel-side dashboard-panel-health mb-4">
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
                    <span class="{{ ($aiApiOperational ?? false) ? 'text-success' : 'text-warning' }} fw-bold" style="font-size:0.85rem;">{{ ($aiApiOperational ?? false) ? 'Operational' : 'Not configured' }}</span>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Storage Usage</span><span class="text-warning fw-bold">65%</span></div>
                    <div class="progress-track" style="height:6px;"><div class="progress-fill" style="width:65%;background:#fbbf24;"></div></div>
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
    const dashboardMobileChart = window.matchMedia('(max-width: 767.98px)').matches;

    // Feature 2: User Analytics (Line Chart)
    const userCanvas = document.getElementById('userGrowthChart');
    if (!userCanvas) return;
    const userCtx = userCanvas.getContext('2d');
    let gradientLine = userCtx.createLinearGradient(0, 0, 0, 300);
    gradientLine.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
    gradientLine.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

    const userGrowthLabels = @js($userGrowthLabels);
    const userGrowthDataValues = @js($userGrowthData);

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
    const chartLabels = @js($chartLabels);
    const chartDataValues = @js($chartData);

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
                legend: {
                    position: dashboardMobileChart ? 'bottom' : 'right',
                    labels: {
                        boxWidth: dashboardMobileChart ? 8 : 10,
                        padding: dashboardMobileChart ? 8 : 10,
                        font: {size: dashboardMobileChart ? 9 : 10}
                    }
                }
            }
        }
    });

    // Feature 5: Readiness Score Analytics (Bar Chart)
    const readCanvas = document.getElementById('readinessBarChart');
    if (!readCanvas) return;
    const readCtx = readCanvas.getContext('2d');
    const readinessDataValues = @js($readinessData);

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
