@extends('layouts.app')

@section('content')
<style>
    /* Premium Dashboard Styles */
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
    .glass-effect {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .score-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.9rem;
    }
    .score-high { background: rgba(52, 211, 153, 0.15); color: #34d399; }
    .score-med { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .score-low { background: rgba(248, 113, 113, 0.15); color: #f87171; }
    
    .quick-action-btn {
        background: var(--bg3, #2b2b40);
        border: 1px solid var(--bd, rgba(255,255,255,0.1));
        color: var(--tx, #e0e0e0);
        border-radius: 12px;
        padding: 12px 20px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .quick-action-btn:hover {
        background: var(--pur, #3b82f6);
        border-color: var(--pur, #3b82f6);
        color: #fff;
    }
    
    .progress-track {
        background: var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 10px;
        height: 8px;
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-fill {
        height: 100%;
        border-radius: 10px;
        background: linear-gradient(90deg, #2563eb, #60a5fa);
    }
    
    .badge-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: var(--bg3, rgba(255,255,255,0.05));
        border: 1px solid var(--bd, rgba(255,255,255,0.1));
        color: #fbbf24;
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
    }
    .custom-table tr:last-child td {
        border-bottom: none;
    }
    
    .nav-pills .nav-link {
        color: var(--tx3, #808090);
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 0.85rem;
    }
    .nav-pills .nav-link.active {
        background-color: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
    }
    
    .notif-item {
        display: flex;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--bd);
    }
    .notif-item:last-child { border-bottom: none; }
</style>

<div class="db-section active" id="sec-overview">

    <!-- Feature 1: Welcome Section -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3">
            <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg, #2563eb, #60a5fa);display:flex;align-items:center;justify-content:center;color:white;font-size:1.5rem;font-weight:700;border:2px solid #fff;">
                {{ substr(Auth::user()->name ?? 'User', 0, 1) }}
            </div>
            <div>
                <h4 class="fw-bold mb-1" style="font-size:1.6rem;">Welcome back, <span id="greetName">{{ Auth::user()->name ?? 'User' }}</span>!</h4>
                <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Your interview readiness score is <strong style="color:#34d399">{{ $profile->readiness_score ?? 82 }}%</strong>. Keep practicing to reach Expert Level.</p>
            </div>
        </div>
    </div>



    <div class="row g-4">
        <!-- LEFT COLUMN (Main Content) -->
        <div class="col-lg-8">
            
            <!-- Feature 2: Readiness Score Card -->
            <div class="premium-card mb-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(52,211,153,0.1) 100%);">
                <div style="position:absolute;top:-50px;right:-50px;width:150px;height:150px;background:rgba(52,211,153,0.1);border-radius:50%;filter:blur(30px);"></div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold m-0"><i class="fa-solid fa-star me-2" style="color:#fbbf24"></i> Overall Readiness</h5>
                    <span class="score-badge score-high">Highly Acceptable</span>
                </div>
                <div class="d-flex align-items-end gap-3 mb-2">
                    <div style="font-size:3.5rem;font-weight:800;line-height:1;color:var(--tx);">{{ $profile->readiness_score ?? 82 }}<span style="font-size:1.5rem;color:var(--tx3)">%</span></div>
                    <div style="margin-bottom:8px;font-size:1rem;color:var(--tx2);">Rating: <strong>4.5/5</strong></div>
                </div>
                <div class="progress-track mt-3" style="height:12px;background:var(--bd);">
                    <div class="progress-fill" style="width: {{ $profile->readiness_score ?? 82 }}%;background:linear-gradient(90deg, #34d399, #10b981);"></div>
                </div>
            </div>

            <!-- Feature 3: Quick Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="premium-card text-center p-3">
                        <div style="font-size:1.5rem;color:#60a5fa;margin-bottom:8px;"><i class="fa-solid fa-microphone"></i></div>
                        <div style="font-size:1.5rem;font-weight:700;">{{ $profile->total_sessions ?? 35 }}</div>
                        <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:1px;">Total Sessions</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="premium-card text-center p-3">
                        <div style="font-size:1.5rem;color:#34d399;margin-bottom:8px;"><i class="fa-solid fa-chart-simple"></i></div>
                        <div style="font-size:1.5rem;font-weight:700;">4.2<span style="font-size:0.9rem;color:var(--tx3)">/5</span></div>
                        <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:1px;">Avg Score</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="premium-card text-center p-3">
                        <div style="font-size:1.5rem;color:#60a5fa;margin-bottom:8px;"><i class="fa-solid fa-book-open"></i></div>
                        <div style="font-size:1.5rem;font-weight:700;">12</div>
                        <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:1px;">Modules Done</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="premium-card text-center p-3">
                        <div style="font-size:1.5rem;color:#fbbf24;margin-bottom:8px;"><i class="fa-solid fa-fire"></i></div>
                        <div style="font-size:1.5rem;font-weight:700;">7 <span style="font-size:0.9rem;color:var(--tx3)">Days</span></div>
                        <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:1px;">Practice Streak</div>
                    </div>
                </div>
            </div>

            <!-- Feature 4: Interview Progress Chart -->
            <div class="premium-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0"><i class="fa-solid fa-chart-line me-2" style="color:#60a5fa"></i> Interview Progress</h5>
                    <ul class="nav nav-pills" id="chartTabs">
                        <li class="nav-item"><a class="nav-link active" href="#" data-period="daily">Daily</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-period="weekly">Weekly</a></li>
                        <li class="nav-item"><a class="nav-link" href="#" data-period="monthly">Monthly</a></li>
                    </ul>
                </div>
                <div style="height: 250px;">
                    <canvas id="progressChart"></canvas>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Feature 5: Category Performance -->
                <div class="col-md-6">
                    <div class="premium-card h-100">
                        <h6 class="fw-bold mb-4">Category Performance</h6>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Job Interview</span><span class="text-success fw-bold">90%</span></div>
                            <div class="progress-track" style="height:6px;"><div class="progress-fill" style="width:90%;background:#34d399;"></div></div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Scholarship</span><span class="text-primary fw-bold">85%</span></div>
                            <div class="progress-track" style="height:6px;"><div class="progress-fill" style="width:85%;background:#60a5fa;"></div></div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>IT Interview</span><span class="text-info fw-bold">88%</span></div>
                            <div class="progress-track" style="height:6px;"><div class="progress-fill" style="width:88%;background:#38bdf8;"></div></div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>College Admission</span><span class="text-warning fw-bold">75%</span></div>
                            <div class="progress-track" style="height:6px;"><div class="progress-fill" style="width:75%;background:#fbbf24;"></div></div>
                        </div>
                    </div>
                </div>

                <!-- Feature 10: Learning Lab Progress -->
                <div class="col-md-6">
                    <div class="premium-card h-100">
                        <h6 class="fw-bold mb-4">Learning Lab Progress</h6>
                        
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(59,130,246,0.15);display:flex;align-items:center;justify-content:center;color:#3b82f6;"><i class="fa-solid fa-comments"></i></div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Communication Skills</span><span>80%</span></div>
                                <div class="progress-track" style="height:4px;"><div class="progress-fill" style="width:80%;background:#3b82f6;"></div></div>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(52,211,153,0.15);display:flex;align-items:center;justify-content:center;color:#34d399;"><i class="fa-solid fa-star"></i></div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>STAR Method</span><span class="text-success">100%</span></div>
                                <div class="progress-track" style="height:4px;"><div class="progress-fill" style="width:100%;background:#34d399;"></div></div>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(96,165,250,0.15);display:flex;align-items:center;justify-content:center;color:#60a5fa;"><i class="fa-solid fa-code"></i></div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;"><span>Technical Interview</span><span>65%</span></div>
                                <div class="progress-track" style="height:4px;"><div class="progress-fill" style="width:65%;background:#60a5fa;"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Feature 7: AI Feedback Summary -->
                <div class="col-md-6">
                    <div class="premium-card h-100">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-wand-magic-sparkles me-2" style="color:#60a5fa"></i> AI Feedback Summary</h6>
                        <div class="p-3 mb-3" style="background:rgba(52,211,153,0.05);border-radius:12px;border:1px solid rgba(52,211,153,0.2);">
                            <h6 style="color:#34d399;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Top Strengths</h6>
                            <ul style="margin:0;padding-left:20px;font-size:0.9rem;color:var(--tx2);">
                                <li>Clear Communication</li>
                                <li>Professional Tone</li>
                                <li>Strong Technical Knowledge</li>
                            </ul>
                        </div>
                        <div class="p-3" style="background:rgba(248,113,113,0.05);border-radius:12px;border:1px solid rgba(248,113,113,0.2);">
                            <h6 style="color:#f87171;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Areas for Improvement</h6>
                            <ul style="margin:0;padding-left:20px;font-size:0.9rem;color:var(--tx2);">
                                <li>Confidence during long answers</li>
                                <li>Conciseness (avoid rambling)</li>
                                <li>Minor Grammar tweaks</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 8: AI Recommendations -->
                <div class="col-md-6">
                    <div class="premium-card h-100" style="background:linear-gradient(180deg, var(--sf) 0%, rgba(59,130,246,0.05) 100%);">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-lightbulb me-2" style="color:#fbbf24"></i> AI Recommendations</h6>
                        <p style="font-size:0.85rem;color:var(--tx3);margin-bottom:15px;">Based on your recent performance, AI suggests:</p>
                        
                        <div class="d-flex align-items-center gap-3 mb-3 p-2 rounded hover-bg" style="background:var(--bg3);">
                            <div style="color:#60a5fa;"><i class="fa-solid fa-bullseye"></i></div>
                            <div style="font-size:0.9rem;">Practice Behavioral Questions</div>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-3 p-2 rounded hover-bg" style="background:var(--bg3);">
                            <div style="color:#34d399;"><i class="fa-solid fa-star"></i></div>
                            <div style="font-size:0.9rem;">Improve STAR Responses</div>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-3 p-2 rounded hover-bg" style="background:var(--bg3);">
                            <div style="color:#60a5fa;"><i class="fa-solid fa-book"></i></div>
                            <div style="font-size:0.9rem;">Review Scholarship Module</div>
                        </div>
                        <div class="d-flex align-items-center gap-3 p-2 rounded hover-bg" style="background:var(--bg3);">
                            <div style="color:#fbbf24;"><i class="fa-solid fa-microphone"></i></div>
                            <div style="font-size:0.9rem;">Complete Voice Rehearsal</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature 6: Recent Interview Sessions -->
            <div class="premium-card mb-4">
                <h5 class="fw-bold mb-4">Recent Interview Sessions</h5>
                <div class="table-responsive">
                    <table class="table custom-table mb-0 w-100">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Score</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSessions ?? [] as $session)
                            <tr>
                                <td style="color:var(--tx2);font-size:0.9rem;">{{ $session->created_at ? $session->created_at->format('M d') : 'June 18' }}</td>
                                <td><span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;">{{ $session->category ? $session->category->name : 'Job Interview' }}</span></td>
                                <td><span style="color:#34d399;font-weight:600;">{{ $session->score ? $session->score->overall_readiness_score . '%' : '92%' }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-outline-primary" style="font-size:0.8rem;border-radius:8px;">View</a>
                                </td>
                            </tr>
                            @empty
                            <!-- Mock data if no sessions exist -->
                            <tr>
                                <td style="color:var(--tx2);font-size:0.9rem;">June 18</td>
                                <td><span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;">Job Interview</span></td>
                                <td><span style="color:#34d399;font-weight:600;">92%</span></td>
                                <td class="text-end"><button class="btn btn-sm btn-outline-primary" style="font-size:0.8rem;border-radius:8px;">View</button></td>
                            </tr>
                            <tr>
                                <td style="color:var(--tx2);font-size:0.9rem;">June 15</td>
                                <td><span class="badge" style="background:rgba(96,165,250,0.15);color:#60a5fa;">Technical</span></td>
                                <td><span style="color:#34d399;font-weight:600;">85%</span></td>
                                <td class="text-end"><button class="btn btn-sm btn-outline-primary" style="font-size:0.8rem;border-radius:8px;">View</button></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN (Sidebar) -->
        <div class="col-lg-4">
            
            <!-- Feature 16: Interview Readiness Radar Chart -->
            <div class="premium-card mb-4 text-center">
                <h6 class="fw-bold mb-3">Readiness Radar</h6>
                <div style="position:relative; width:100%; height:250px; margin:0 auto;">
                    <canvas id="radarChart"></canvas>
                </div>
            </div>

            <!-- Feature 9: Daily Practice Challenge -->
            <div class="premium-card mb-4" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(59,130,246,0.1) 100%); border: 1px solid rgba(59,130,246,0.3);">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-calendar-day" style="color:#60a5fa;font-size:1.2rem;"></i>
                    <h6 class="fw-bold m-0" style="color:#60a5fa;">Today's Challenge</h6>
                </div>
                <h5 class="fw-bold mb-2">Answer 3 Behavioral Questions</h5>
                <p style="font-size:0.85rem;color:var(--tx2);margin-bottom:15px;">Earn extra XP and maintain your streak!</p>
                <div class="d-flex gap-2 mb-3">
                    <span class="badge" style="background:rgba(251,191,36,0.2);color:#fbbf24;">+50 XP</span>
                    <span class="badge" style="background:rgba(52,211,153,0.2);color:#34d399;">+1 Streak</span>
                </div>
                <button class="btn w-100" style="background:#3b82f6;color:white;font-weight:600;border-radius:10px;">Start Challenge</button>
            </div>

            <!-- Feature 13: Upcoming Goals -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-3">Upcoming Goals</h6>
                <div class="p-3 mb-3" style="background:var(--bg3);border-radius:12px;border:1px solid var(--bd);">
                    <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;margin-bottom:5px;">Current Goal</div>
                    <div class="fw-bold" style="color:#34d399;">Reach 90% Readiness</div>
                    <div class="progress-track mt-2"><div class="progress-fill" style="width:82%;background:#34d399;"></div></div>
                </div>
                <div style="font-size:0.85rem;color:var(--tx2);">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-regular fa-circle"></i> Complete 2 Mock Interviews
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-regular fa-circle"></i> Practice Voice Rehearsal
                    </div>
                </div>
            </div>

            <!-- Feature 14: Achievement & Badges System -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-3">Achievements</h6>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <div class="text-center" title="First Interview Completed">
                        <div class="badge-icon mx-auto mb-1" style="background:rgba(251,191,36,0.15);border-color:rgba(251,191,36,0.3);"><i class="fa-solid fa-medal"></i></div>
                        <div style="font-size:0.65rem;color:var(--tx3);">First<br>Interview</div>
                    </div>
                    <div class="text-center" title="7-Day Practice Streak">
                        <div class="badge-icon mx-auto mb-1" style="background:rgba(248,113,113,0.15);border-color:rgba(248,113,113,0.3);color:#f87171;"><i class="fa-solid fa-fire"></i></div>
                        <div style="font-size:0.65rem;color:var(--tx3);">7-Day<br>Streak</div>
                    </div>
                    <div class="text-center" title="STAR Master">
                        <div class="badge-icon mx-auto mb-1" style="background:rgba(59,130,246,0.15);border-color:rgba(59,130,246,0.3);color:#60a5fa;"><i class="fa-solid fa-star"></i></div>
                        <div style="font-size:0.65rem;color:var(--tx3);">STAR<br>Master</div>
                    </div>
                    <div class="text-center" title="Excellent Communicator">
                        <div class="badge-icon mx-auto mb-1" style="background:rgba(52,211,153,0.15);border-color:rgba(52,211,153,0.3);color:#34d399;"><i class="fa-solid fa-bullhorn"></i></div>
                        <div style="font-size:0.65rem;color:var(--tx3);">Top<br>Comm</div>
                    </div>
                    <div class="text-center" style="opacity:0.5" title="Interview Champion (Locked)">
                        <div class="badge-icon mx-auto mb-1" style="background:var(--bg3);border-color:var(--bd);color:var(--tx3);"><i class="fa-solid fa-trophy"></i></div>
                        <div style="font-size:0.65rem;color:var(--tx3);">Champion</div>
                    </div>
                </div>
            </div>

            <!-- Feature 15: Notifications Panel -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-3">Recent Notifications</h6>
                <div class="notif-item">
                    <div style="width:8px;height:8px;border-radius:50%;background:#34d399;margin-top:6px;"></div>
                    <div>
                        <div style="font-size:0.85rem;color:var(--tx);">New Feedback Available</div>
                        <div style="font-size:0.75rem;color:var(--tx3);">Your session "Job Interview" has been reviewed.</div>
                    </div>
                </div>
                <div class="notif-item">
                    <div style="width:8px;height:8px;border-radius:50%;background:#60a5fa;margin-top:6px;"></div>
                    <div>
                        <div style="font-size:0.85rem;color:var(--tx);">New Learning Module</div>
                        <div style="font-size:0.75rem;color:var(--tx3);">"Mastering the STAR Method" is now available.</div>
                    </div>
                </div>
                <div class="notif-item">
                    <div style="width:8px;height:8px;border-radius:50%;background:#fbbf24;margin-top:6px;"></div>
                    <div>
                        <div style="font-size:0.85rem;color:var(--tx);">Achievement Unlocked!</div>
                        <div style="font-size:0.75rem;color:var(--tx3);">You earned the "7-Day Streak" badge.</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- Scripts for Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Shared Chart.js options for dark mode
    Chart.defaults.color = '#808090';
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Feature 4: Progress Line Chart
    const progressCtx = document.getElementById('progressChart').getContext('2d');
    
    // Mock data for Daily, Weekly, Monthly
    const chartDataObj = {
        daily: { labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], data: [65, 70, 68, 75, 80, 78, 82] },
        weekly: { labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'], data: [55, 65, 75, 82] },
        monthly: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], data: [40, 50, 60, 70, 75, 82] }
    };

    let gradientLine = progressCtx.createLinearGradient(0, 0, 0, 300);
    gradientLine.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
    gradientLine.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    let progressChart = new Chart(progressCtx, {
        type: 'line',
        data: {
            labels: chartDataObj.daily.labels,
            datasets: [{
                label: 'Readiness Score',
                data: chartDataObj.daily.data,
                borderColor: '#3b82f6',
                backgroundColor: gradientLine,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#1e1e2d',
                pointBorderColor: '#3b82f6',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
            scales: {
                y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false } },
                x: { grid: { display: false, drawBorder: false } }
            }
        }
    });

    // Handle Tab Clicks for Line Chart
    document.querySelectorAll('#chartTabs .nav-link').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('#chartTabs .nav-link').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            let period = this.getAttribute('data-period');
            progressChart.data.labels = chartDataObj[period].labels;
            progressChart.data.datasets[0].data = chartDataObj[period].data;
            progressChart.update();
        });
    });

    // Feature 16: Readiness Radar Chart
    const radarCtx = document.getElementById('radarChart').getContext('2d');
    
    // Mock values based on the score in DB (or mock)
    let clarity = {{ $score->clarity_score ?? 85 }};
    let relevance = {{ $score->relevance_score ?? 80 }};
    let grammar = {{ $score->grammar_score ?? 75 }};
    let professionalism = {{ $score->professionalism_score ?? 90 }};
    let confidence = 88; // Mock extra metric

    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: ['Clarity', 'Relevance', 'Grammar', 'Professionalism', 'Confidence'],
            datasets: [{
                label: 'Current Level',
                data: [clarity, relevance, grammar, professionalism, confidence],
                backgroundColor: 'rgba(52, 211, 153, 0.2)',
                borderColor: '#34d399',
                pointBackgroundColor: '#34d399',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#34d399',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                r: {
                    angleLines: { color: 'rgba(255,255,255,0.1)' },
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    pointLabels: { color: '#a0a0b0', font: { size: 11 } },
                    ticks: { display: false, min: 0, max: 100, stepSize: 20 }
                }
            }
        }
    });
});
</script>

@endsection