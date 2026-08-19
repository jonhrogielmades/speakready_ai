@extends('desktop.layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/admin/dashboard.css?v=1') }}" data-page-style="admin-dashboard">
@endpush

@section('content')

<div class="db-section active" id="sec-overview">
    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="admin-dashboard-title fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-chart-pie"></i> Philippines Interview Admin Dashboard</h4>
            <p class="admin-dashboard-subtitle" style="font-size:0.95rem;color:var(--tx2);margin:0;">Overview for Philippine interview practice, user analytics, and platform health.</p>
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
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">PH Mock Interviews</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card overview-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#fbbf24;margin-bottom:8px;"><i class="fa-solid fa-robot"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($aiFeedbacksCount) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">PH AI Feedbacks</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card overview-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#f472b6;margin-bottom:8px;"><i class="fa-solid fa-graduation-cap"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($modulesCompletedCount) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">PH Modules Completed</div>
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
                <h6 class="fw-bold mb-4">Philippines Interview Categories</h6>
                <div class="chart-container" style="height: 200px;">
                    <canvas id="categoryDonutChart"></canvas>
                </div>
                <div class="text-center mt-3" style="font-size: 0.8rem; color:var(--tx2);">Philippines job interview practice is the most active.</div>
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
                    <h6 class="fw-bold m-0">Recent Philippines Interview Sessions</h6>
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
                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-scale-balanced me-2 text-warning"></i>Assessment Quality</h6>
                        <p class="small mb-3" style="color:var(--tx3);">Anonymous readiness-band distribution from score-eligible and legacy assessments.</p>
                        @forelse($readinessBandSummary as $band)
                        <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded" style="background:var(--bg3);border:1px solid var(--bd);">
                            <div>
                                <span style="font-size:0.9rem;font-weight:700;">{{ $band->band }}</span>
                                <div style="font-size:.72rem;color:var(--tx3);">Average score confidence {{ $band->scoring_confidence }}%</div>
                            </div>
                            <span class="fw-bold text-primary">{{ $band->count }} assessments</span>
                        </div>
                        @empty
                        <div class="text-center text-muted mt-4">No eligible assessments available yet.</div>
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
                <h6 class="fw-bold mb-4">Average PH Interview Performance</h6>
                
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
                <h6 class="fw-bold mb-3">PH Question Bank Stats</h6>
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
                <h6 class="fw-bold mb-3">PH Interview Learning Stats</h6>
                <div class="p-2 mb-2 rounded" style="background:var(--bg3);border:1px solid var(--bd);">
                    <div style="font-size:0.75rem;color:var(--tx3);">Most Viewed & Completed</div>
                    <div class="fw-bold" style="color:var(--tx);font-size:0.9rem;">PH Interview Communication</div>
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
                    <option>Philippines Interview Reports</option>
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
