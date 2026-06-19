@extends('layouts.admin')

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
    .stat-badge.danger { background: rgba(248, 113, 113, 0.15); color: #f87171; }
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
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-chart-pie me-2" style="color:#3b82f6;"></i>Admin Dashboard</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">System overview, user analytics, and platform health.</p>
        </div>
        <!-- Feature 16: Quick Actions Panel -->
        <div class="d-flex flex-wrap gap-2">
            <button class="quick-action-btn"><i class="fa-solid fa-plus text-primary"></i> Add Question</button>
            <button class="quick-action-btn"><i class="fa-solid fa-plus text-success"></i> Add Module</button>
            <button class="quick-action-btn"><i class="fa-solid fa-bullhorn text-warning"></i> Create Announcement</button>
            <button class="quick-action-btn"><i class="fa-solid fa-users text-info"></i> Manage Users</button>
        </div>
    </div>

    <!-- Feature 1: Dashboard Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#3b82f6;margin-bottom:8px;"><i class="fa-solid fa-users"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">1,250</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Registered Users</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#34d399;margin-bottom:8px;"><i class="fa-solid fa-user-check"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">215</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Active Today</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#60a5fa;margin-bottom:8px;"><i class="fa-solid fa-microphone"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">4,820</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Mock Interviews</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#fbbf24;margin-bottom:8px;"><i class="fa-solid fa-robot"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">12,430</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">AI Feedbacks</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#f472b6;margin-bottom:8px;"><i class="fa-solid fa-graduation-cap"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">3,150</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Modules Completed</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(52,211,153,0.1) 100%); border-color: rgba(52,211,153,0.3);">
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
                    <button class="btn btn-sm" style="border-radius:8px;border:1px solid var(--bd);color:var(--tx2);background:var(--bg3);">View All</button>
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
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:32px;height:32px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:0.8rem;">JD</div>
                                        <span>Juan Dela Cruz</span>
                                    </div>
                                </td>
                                <td><span class="stat-badge primary">Job Interview</span></td>
                                <td><span class="fw-bold text-success">88%</span></td>
                                <td style="color:var(--tx2);">Today, 10:30 AM</td>
                                <td class="text-end">
                                    <button class="btn btn-sm" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);" title="View Session"><i class="fa-solid fa-eye"></i></button>
                                    <button class="btn btn-sm" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);" title="Export Report"><i class="fa-solid fa-download"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:32px;height:32px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:0.8rem;">MS</div>
                                        <span>Maria Santos</span>
                                    </div>
                                </td>
                                <td><span class="stat-badge warning">Scholarship</span></td>
                                <td><span class="fw-bold text-success">92%</span></td>
                                <td style="color:var(--tx2);">Yesterday</td>
                                <td class="text-end">
                                    <button class="btn btn-sm" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);" title="View Session"><i class="fa-solid fa-eye"></i></button>
                                    <button class="btn btn-sm" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);" title="Export Report"><i class="fa-solid fa-download"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:32px;height:32px;border-radius:50%;background:#10b981;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:0.8rem;">MG</div>
                                        <span>Mark Garcia</span>
                                    </div>
                                </td>
                                <td><span class="stat-badge" style="background:rgba(168,85,247,0.15);color:#a855f7;">IT Interview</span></td>
                                <td><span class="fw-bold text-warning">65%</span></td>
                                <td style="color:var(--tx2);">Oct 12, 2026</td>
                                <td class="text-end">
                                    <button class="btn btn-sm" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);" title="View Session"><i class="fa-solid fa-eye"></i></button>
                                    <button class="btn btn-sm" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);" title="Export Report"><i class="fa-solid fa-download"></i></button>
                                </td>
                            </tr>
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
                            <div class="activity-item">
                                <div style="font-size:0.85rem;color:var(--tx);"><strong>Admin</strong> added new Question.</div>
                                <div style="font-size:0.75rem;color:var(--tx3);">10 mins ago</div>
                            </div>
                            <div class="activity-item">
                                <div style="font-size:0.85rem;color:var(--tx);"><strong>Juan Dela Cruz</strong> completed Mock Interview.</div>
                                <div style="font-size:0.75rem;color:var(--tx3);">1 hour ago</div>
                            </div>
                            <div class="activity-item">
                                <div style="font-size:0.85rem;color:var(--tx);"><strong>Maria Santos</strong> registered.</div>
                                <div style="font-size:0.75rem;color:var(--tx3);">3 hours ago</div>
                            </div>
                            <div class="activity-item">
                                <div style="font-size:0.85rem;color:var(--tx);"><strong>Admin</strong> updated Settings.</div>
                                <div style="font-size:0.75rem;color:var(--tx3);">Yesterday</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="premium-card h-100">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-trophy me-2 text-warning"></i>Leaderboard</h6>
                        <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded" style="background:rgba(251,191,36,0.1);border:1px solid rgba(251,191,36,0.3);">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold" style="color:#fbbf24;width:20px;">1</span>
                                <div style="width:30px;height:30px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;">JO</div>
                                <span style="font-size:0.9rem;">John</span>
                            </div>
                            <span class="fw-bold text-success">96%</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded" style="background:var(--bg3);">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold" style="color:var(--tx3);width:20px;">2</span>
                                <div style="width:30px;height:30px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;">MA</div>
                                <span style="font-size:0.9rem;">Maria</span>
                            </div>
                            <span class="fw-bold text-success">94%</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background:var(--bg3);">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold" style="color:var(--tx3);width:20px;">3</span>
                                <div style="width:30px;height:30px;border-radius:50%;background:#10b981;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;">MK</div>
                                <span style="font-size:0.9rem;">Mark</span>
                            </div>
                            <span class="fw-bold text-success">93%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature 11: Users Needing Improvement -->
            <div class="premium-card mb-4" style="border-left: 4px solid #f87171;">
                <h6 class="fw-bold mb-3 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Users Needing Support</h6>
                <div class="table-responsive">
                    <table class="table custom-table mb-0 w-100">
                        <tbody>
                            <tr>
                                <td>Peter Parker</td>
                                <td><span class="stat-badge danger">Low Readiness (45%)</span></td>
                                <td>Incomplete Modules</td>
                                <td class="text-end"><button class="btn btn-sm" style="border-radius:8px;border:1px solid #f87171;color:#f87171;background:rgba(248,113,113,0.1);">Message</button></td>
                            </tr>
                            <tr>
                                <td>Bruce Wayne</td>
                                <td><span class="stat-badge warning">Low Activity</span></td>
                                <td>No sessions in 30 days</td>
                                <td class="text-end"><button class="btn btn-sm" style="border-radius:8px;border:1px solid #f87171;color:#f87171;background:rgba(248,113,113,0.1);">Message</button></td>
                            </tr>
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
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Clarity</span><span class="fw-bold">85%</span></div>
                    <div class="progress-track"><div class="progress-fill" style="width:85%;background:#34d399;"></div></div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Relevance</span><span class="fw-bold">82%</span></div>
                    <div class="progress-track"><div class="progress-fill" style="width:82%;background:#60a5fa;"></div></div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Grammar</span><span class="fw-bold">90%</span></div>
                    <div class="progress-track"><div class="progress-fill" style="width:90%;background:#3b82f6;"></div></div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Professionalism</span><span class="fw-bold">80%</span></div>
                    <div class="progress-track"><div class="progress-fill" style="width:80%;background:#fbbf24;"></div></div>
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
    Chart.defaults.color = '#808090';
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Feature 2: User Analytics (Line Chart)
    const userCtx = document.getElementById('userGrowthChart').getContext('2d');
    let gradientLine = userCtx.createLinearGradient(0, 0, 0, 300);
    gradientLine.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
    gradientLine.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

    new Chart(userCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'New Registrations',
                data: [120, 190, 300, 450, 600, 850],
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
    const catCtx = document.getElementById('categoryDonutChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: ['Job', 'Scholarship', 'College', 'IT'],
            datasets: [{
                data: [1250, 820, 670, 2080],
                backgroundColor: ['#3b82f6', '#34d399', '#fbbf24', '#60a5fa'],
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
    const readCtx = document.getElementById('readinessBarChart').getContext('2d');
    new Chart(readCtx, {
        type: 'bar',
        data: {
            labels: ['Highly Acc.', 'Acceptable', 'Needs Imp.', 'Poor'],
            datasets: [{
                label: 'Users',
                data: [450, 520, 180, 80],
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
