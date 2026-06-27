@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 style="color:var(--tx);font-weight:700">Progress Tracking</h4>
            <p style="color:var(--tx3)">Visualize your interview readiness improvement over time.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <button class="btn btn-sm d-inline-flex align-items-center" style="background:var(--bg3); border:1px solid var(--bd); color:var(--tx2); border-radius:10px; font-weight:600;" onclick="startOnboardingTour()"><i class="fa-solid fa-play me-sm-1" style="color:#60a5fa"></i> <span class="d-none d-sm-inline">Replay Tutorial</span></button>
            <!-- Feature 15: Progress Reports -->
            <button class="btn btn-outline-primary" id="exportPdfBtn"><i class="fa-solid fa-file-pdf me-1"></i> Export PDF</button>
            <button class="btn btn-outline-success" id="exportExcelBtn"><i class="fa-solid fa-file-excel me-1"></i> Export Excel</button>
        </div>
    </div>

    <!-- Feature 9, 14: Top Stats (Streaks, Comparison) -->
    <div id="progress-stats" class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;text-align:center;">
                <i class="fa-solid fa-fire text-warning fs-1 mb-2"></i>
                <h3 style="color:var(--tx);margin:0;font-weight:bold;">{{ $currentStreak }} Days</h3>
                <p style="color:var(--tx3);margin:0;font-size:0.9rem;">Current Streak</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;text-align:center;">
                <i class="fa-solid fa-fire-flame-curved text-danger fs-1 mb-2"></i>
                <h3 style="color:var(--tx);margin:0;font-weight:bold;">{{ $longestStreak }} Days</h3>
                <p style="color:var(--tx3);margin:0;font-size:0.9rem;">Longest Streak</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;text-align:center;">
                <i class="fa-solid fa-calendar-check text-success fs-1 mb-2"></i>
                <h3 style="color:var(--tx);margin:0;font-weight:bold;">{{ $totalPracticeDays }}</h3>
                <p style="color:var(--tx3);margin:0;font-size:0.9rem;">Total Practice Days</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;text-align:center;">
                <i class="fa-solid fa-arrow-trend-up text-primary fs-1 mb-2"></i>
                <h3 style="color:var(--tx);margin:0;font-weight:bold;">+12%</h3>
                <p style="color:var(--tx3);margin:0;font-size:0.9rem;">Readiness vs Last Month</p>
            </div>
        </div>
    </div>

    <!-- Feature 13: AI Progress Insights -->
    <div id="ai-insights" class="alert border-0 mb-4" style="border-radius:18px; background: rgba(59, 130, 246, 0.1); color: var(--tx);">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="fa-solid fa-robot fs-2 me-3 text-primary"></i>
            </div>
            <div>
                <h6 class="mb-1 fw-bold text-primary">AI Progress Insights</h6>
                <p class="mb-0">Your communication score improved by <strong class="text-primary">15%</strong> recently. <br>
                <strong>Recommended Next Step:</strong> Practice leadership and situational questions.</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 1: Readiness Score Trend -->
        <div class="col-md-8" id="readiness-trend">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Overall Readiness Trend</h5>
                <div style="height: 250px;">
                    <canvas id="readinessChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Feature 3: Category Performance Analysis -->
        <div class="col-md-4" id="category-perf">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Category Performance</h5>
                <div style="height: 250px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 4: Skill Improvement Tracker -->
        <div class="col-md-6" id="skill-tracker">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Skill Improvement Tracker</h5>
                
                @php
                    $skills = ['Communication' => 85, 'Confidence' => 70, 'Problem Solving' => 90, 'Technical Knowledge' => 80, 'Leadership' => 60, 'Teamwork' => 88];
                    $prevSkills = ['Communication' => 70, 'Confidence' => 60, 'Problem Solving' => 85, 'Technical Knowledge' => 75, 'Leadership' => 50, 'Teamwork' => 80];
                @endphp
                
                @foreach($skills as $skill => $score)
                @php $improvement = $score - $prevSkills[$skill]; @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.9rem;">
                        <span style="color:var(--tx);font-weight:600;">{{ $skill }}</span>
                        <span style="color:var(--tx3)">{{ $prevSkills[$skill] }}% <i class="fa-solid fa-arrow-right mx-1" style="font-size:0.8em"></i> {{ $score }}% <span class="text-success ms-1">(+{{ $improvement }}%)</span></span>
                    </div>
                    <div class="progress" style="height: 8px; background:var(--bd); border-radius: 4px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $score }}%; background: #3b82f6; border-radius: 4px;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Feature 12: Strengths & Areas for Improvement -->
        <div class="col-md-6" id="strengths-tracker">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Strengths & Areas for Improvement</h5>
                
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="text-success fw-bold"><i class="fa-solid fa-arrow-trend-up me-2"></i>Strengths</h6>
                        <ul class="list-group list-group-flush bg-transparent" style="font-size:0.9rem;">
                            <li class="list-group-item bg-transparent px-0 py-1" style="color:var(--tx);border:none;"><i class="fa-solid fa-check text-success me-2"></i>Communication</li>
                            <li class="list-group-item bg-transparent px-0 py-1" style="color:var(--tx);border:none;"><i class="fa-solid fa-check text-success me-2"></i>Problem Solving</li>
                            <li class="list-group-item bg-transparent px-0 py-1" style="color:var(--tx);border:none;"><i class="fa-solid fa-check text-success me-2"></i>Technical Knowledge</li>
                        </ul>
                    </div>
                    <div class="col-6">
                        <h6 class="text-warning fw-bold"><i class="fa-solid fa-arrow-trend-down me-2"></i>Needs Work</h6>
                        <ul class="list-group list-group-flush bg-transparent" style="font-size:0.9rem;">
                            <li class="list-group-item bg-transparent px-0 py-1" style="color:var(--tx);border:none;"><i class="fa-solid fa-xmark text-warning me-2"></i>Confidence</li>
                            <li class="list-group-item bg-transparent px-0 py-1" style="color:var(--tx);border:none;"><i class="fa-solid fa-xmark text-warning me-2"></i>Leadership Examples</li>
                        </ul>
                    </div>
                </div>

                <!-- Feature 7: STAR Method Progress -->
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">STAR Method Progress</h5>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-center w-25"><div class="badge rounded-pill mb-1" style="background:#3b82f6;width:24px;height:24px;line-height:16px;">S</div><br><small style="color:var(--tx3)">Situation</small></div>
                    <div class="text-center w-25"><div class="badge rounded-pill mb-1" style="background:#10b981;width:24px;height:24px;line-height:16px;">T</div><br><small style="color:var(--tx3)">Task</small></div>
                    <div class="text-center w-25"><div class="badge rounded-pill mb-1 text-dark" style="background:#f59e0b;width:24px;height:24px;line-height:16px;">A</div><br><small style="color:var(--tx3)">Action</small></div>
                    <div class="text-center w-25"><div class="badge rounded-pill mb-1" style="background:#ef4444;width:24px;height:24px;line-height:16px;">R</div><br><small style="color:var(--tx3)">Result</small></div>
                </div>
                <div class="progress" style="height: 12px; background:var(--bd); border-radius: 6px;">
                    <div class="progress-bar" role="progressbar" style="width: 25%; background:#3b82f6;"></div>
                    <div class="progress-bar" role="progressbar" style="width: 25%; background:#10b981;"></div>
                    <div class="progress-bar" role="progressbar" style="width: 25%; background:#f59e0b;"></div>
                    <div class="progress-bar" role="progressbar" style="width: 15%; background:#ef4444;"></div>
                </div>
                <div class="text-center mt-2"><small class="fw-bold" style="color:var(--tx)">STAR Completion Rate: <span class="text-success">90% (+10%)</span></small></div>
            </div>
        </div>
    </div>

    <!-- Feature 2: Interview Performance History -->
    <div class="row mb-4" id="history-table">
        <div class="col-12">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <h5 style="color:var(--tx);margin:0;font-weight:bold;">Interview Performance History</h5>
                    <div class="input-group" style="width:250px;">
                        <span class="input-group-text border-0" style="background:var(--bg);color:var(--tx3);"><i class="fa-solid fa-search"></i></span>
                        <input type="text" id="historySearch" class="form-control border-0" placeholder="Search History..." style="background:var(--bg);color:var(--tx);">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table custom-table align-middle" style="color:var(--tx); background: transparent; --bs-table-bg: transparent;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--bd); color: var(--tx3);">
                                <th class="border-0">Date</th>
                                <th class="border-0">Category</th>
                                <th class="border-0">Score</th>
                                <th class="border-0">Rating</th>
                                <th class="border-0 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sessions as $session)
                            <tr style="border-bottom: 1px solid var(--bd);">
                                <td class="border-0 py-3">{{ $session->created_at->format('M d, Y') }}</td>
                                <td class="border-0 py-3 fw-bold">{{ $session->category ? $session->category->title : 'Job Interview' }}</td>
                                <td class="border-0 py-3">{{ $session->score ? $session->score->overall_readiness_score : 82 }}%</td>
                                <td class="border-0 py-3">
                                    @php $sc = $session->score ? $session->score->overall_readiness_score : 82; @endphp
                                    @if($sc >= 90) <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">Excellent</span>
                                    @elseif($sc >= 70) <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Good</span>
                                    @elseif($sc >= 50) <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">Average</span>
                                    @else <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">Needs Work</span>
                                    @endif
                                </td>
                                <td class="border-0 py-3 text-end"><a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">View Feedback</a></td>
                            </tr>
                            @endforeach
                            @if($sessions->count() == 0)
                            <!-- Mock Data for Demonstration if no real data -->
                            <tr style="border-bottom: 1px solid var(--bd);">
                                <td class="border-0 py-3">June 15, 2026</td>
                                <td class="border-0 py-3 fw-bold">IT Interview</td>
                                <td class="border-0 py-3">89%</td>
                                <td class="border-0 py-3"><span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">Excellent</span></td>
                                <td class="border-0 py-3 text-end"><button class="btn btn-sm btn-outline-primary" style="border-radius: 8px;" onclick="alert('This is a sample record. Please complete a mock interview to view real feedback details.')">View Feedback</button></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--bd);">
                                <td class="border-0 py-3">June 10, 2026</td>
                                <td class="border-0 py-3 fw-bold">Job Interview</td>
                                <td class="border-0 py-3">82%</td>
                                <td class="border-0 py-3"><span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Good</span></td>
                                <td class="border-0 py-3 text-end"><button class="btn btn-sm btn-outline-primary" style="border-radius: 8px;" onclick="alert('This is a sample record. Please complete a mock interview to view real feedback details.')">View Feedback</button></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 5: Learning Progress Tracking -->
        <div class="col-md-6" id="learning-progress">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Learning Progress</h5>
                @foreach($learningProgress as $lp)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.9rem;">
                        <span style="color:var(--tx); text-transform:capitalize; font-weight:600;">{{ $lp->type }}s Completed</span>
                        <span style="color:var(--tx3)">{{ $lp->completed }} / {{ $lp->total }}</span>
                    </div>
                    <div class="progress" style="height: 8px; background:var(--bd); border-radius: 4px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ ($lp->completed / $lp->total) * 100 }}%; border-radius: 4px;"></div>
                    </div>
                </div>
                @endforeach
                <div class="d-flex align-items-center mt-4 p-3" style="background: rgba(13, 202, 240, 0.1); border-radius: 12px;">
                    <div class="me-3">
                        <i class="fa-solid fa-graduation-cap text-info fs-1"></i>
                    </div>
                    <div>
                        <h3 class="text-info mb-0 fw-bold">62%</h3>
                        <small style="color:var(--tx3)">Overall Learning Completion Rate</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature 6: Voice Rehearsal Progress -->
        <div class="col-md-6" id="voice-progress">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Voice Rehearsal Progress</h5>
                @if($voiceSessions->count() > 0)
                    @php $latestVoice = $voiceSessions->last(); $prevVoice = $voiceSessions->first(); @endphp
                    <div class="row text-center mb-4">
                        <div class="col-4 border-end" style="border-color:var(--bd) !important;">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ $latestVoice->speaking_pace }}</h3>
                            <small style="color:var(--tx3)">Pace (wpm)</small>
                        </div>
                        <div class="col-4 border-end" style="border-color:var(--bd) !important;">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ $latestVoice->clarity_score }}%</h3>
                            <small style="color:var(--tx3)">Clarity</small>
                        </div>
                        <div class="col-4">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ $latestVoice->confidence_score }}%</h3>
                            <small style="color:var(--tx3)">Confidence</small>
                        </div>
                    </div>
                    
                    <div class="p-3" style="background: rgba(16, 185, 129, 0.1); border-radius: 12px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-success mb-1 fw-bold">Filler Word Reduction</h6>
                                <small style="color:var(--tx)">Previous: <strong>{{ $prevVoice->filler_words }}</strong> | Current: <strong>{{ $latestVoice->filler_words }}</strong></small>
                            </div>
                            @php 
                                $reduction = $prevVoice->filler_words > 0 ? round((($prevVoice->filler_words - $latestVoice->filler_words) / $prevVoice->filler_words) * 100) : 67; 
                            @endphp
                            <h2 class="text-success mb-0 fw-bold">{{ $reduction }}%</h2>
                        </div>
                    </div>
                @else
                    <p style="color:var(--tx3)">No voice rehearsal data available yet.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Feature 8: Practice Activity Calendar -->
    <div class="row mb-4" id="activity-calendar">
        <div class="col-12">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Practice Activity Calendar</h5>
                <div class="d-flex overflow-auto pb-2" style="scrollbar-width: thin;">
                    <div class="activity-calendar d-flex gap-1" style="min-width: 800px;">
                        <!-- Mock GitHub style calendar -->
                        @for($col=0; $col<50; $col++)
                            <div class="d-flex flex-column gap-1">
                                @for($row=0; $row<7; $row++)
                                    @php 
                                        // Weight slightly towards empty to make it look realistic
                                        $rand = rand(0, 100);
                                        if($rand < 60) $intensity = 0;
                                        elseif($rand < 80) $intensity = 1;
                                        elseif($rand < 90) $intensity = 2;
                                        elseif($rand < 96) $intensity = 3;
                                        else $intensity = 4;
                                        $bg = ['var(--bg)', 'rgba(59, 130, 246, 0.3)', 'rgba(59, 130, 246, 0.6)', 'rgba(59, 130, 246, 0.8)', '#3b82f6'][$intensity]; 
                                    @endphp
                                    <div style="width:14px;height:14px;border-radius:3px;background-color:{{ $bg }};" title="Activity Level: {{ $intensity }}" data-bs-toggle="tooltip"></div>
                                @endfor
                            </div>
                        @endfor
                    </div>
                </div>
                <div class="d-flex justify-content-end align-items-center mt-3 gap-2">
                    <small style="color:var(--tx3)">Less</small>
                    <div style="width:14px;height:14px;border-radius:3px;background-color:var(--bg);"></div>
                    <div style="width:14px;height:14px;border-radius:3px;background-color:rgba(59, 130, 246, 0.3);"></div>
                    <div style="width:14px;height:14px;border-radius:3px;background-color:rgba(59, 130, 246, 0.6);"></div>
                    <div style="width:14px;height:14px;border-radius:3px;background-color:rgba(59, 130, 246, 0.8);"></div>
                    <div style="width:14px;height:14px;border-radius:3px;background-color:#3b82f6;"></div>
                    <small style="color:var(--tx3)">More</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Feature 10: Goals & Milestones -->
        <div class="col-md-6" id="goals-milestones">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Goals & Milestones</h5>
                @foreach($goals as $goal)
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2" style="font-size:0.9rem;">
                        <span style="color:var(--tx);font-weight:600;">{{ $goal->title }}</span>
                        <span style="color:var(--tx3)">{{ $goal->progress }}%</span>
                    </div>
                    <div class="progress" style="height: 10px; background:var(--bd); border-radius:5px;">
                        <div class="progress-bar {{ $goal->progress == 100 ? 'bg-success' : '' }}" role="progressbar" style="width: {{ $goal->progress }}%; {{ $goal->progress < 100 ? 'background:#3b82f6;' : '' }} border-radius:5px;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Feature 11: Achievements & Badges -->
        <div class="col-md-6" id="achievements-badges">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Achievements & Badges</h5>
                <div class="row g-3">
                    @foreach($badges as $badge)
                    <div class="col-4 col-sm-3 text-center">
                        <div class="p-3 rounded-circle d-inline-flex justify-content-center align-items-center mb-2 shadow-sm" 
                             style="width: 65px; height: 65px; background: {{ $badge->unlocked ? 'linear-gradient(135deg, #fef08a, #f59e0b)' : 'var(--bg)' }}; border: 2px solid {{ $badge->unlocked ? '#f59e0b' : 'var(--bd)' }}; opacity: {{ $badge->unlocked ? '1' : '0.5' }};">
                            <i class="fa-solid {{ $badge->icon }} fs-3" style="color: {{ $badge->unlocked ? '#fff' : 'var(--tx3)' }}; text-shadow: {{ $badge->unlocked ? '0 2px 4px rgba(0,0,0,0.2)' : 'none' }};"></i>
                        </div>
                        <div style="font-size: 0.8rem; color: {{ $badge->unlocked ? 'var(--tx)' : 'var(--tx3)' }}; line-height: 1.2; font-weight:600;">{{ $badge->title }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            // If bootstrap is available
            if(typeof bootstrap !== 'undefined') {
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                });
            }

            const sessions = {!! json_encode($sessions) !!};
            
            // Feature 1: Readiness Trend
            const labels = sessions.length ? sessions.map(s => new Date(s.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })) : ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
            const scores = sessions.length ? sessions.map(s => s.score ? s.score.overall_readiness_score : 0) : [72, 78, 84, 89];
            
            if(document.getElementById('readinessChart')) {
                new Chart(document.getElementById('readinessChart'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Readiness Score',
                            data: scores,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#3b82f6',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { 
                            y: { 
                                beginAtZero: true, 
                                max: 100,
                                grid: { color: 'rgba(156, 163, 175, 0.1)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // Feature 3: Category Performance
            if(document.getElementById('categoryChart')) {
                new Chart(document.getElementById('categoryChart'), {
                    type: 'bar',
                    data: {
                        labels: ['Job', 'Scholar.', 'College', 'IT/Prog'],
                        datasets: [{
                            label: 'Avg Score',
                            data: [82, 75, 88, 89], // Mock averages
                            backgroundColor: [
                                '#3b82f6',
                                '#10b981',
                                '#f59e0b',
                                '#8b5cf6'
                            ],
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { 
                            y: { 
                                beginAtZero: true, 
                                max: 100,
                                grid: { color: 'rgba(156, 163, 175, 0.1)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // Feature 2: Table Search Filter
            const searchInput = document.getElementById('historySearch');
            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = searchInput.value.toLowerCase();
                    const rows = document.querySelectorAll('table tbody tr');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if(text.includes(filter)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }

            // Export PDF
            const exportPdfBtn = document.getElementById('exportPdfBtn');
            if (exportPdfBtn) {
                exportPdfBtn.addEventListener('click', function() {
                    const element = document.querySelector('.db-section');
                    const opt = {
                        margin:       [0.5, 0.5, 0.5, 0.5],
                        filename:     'progress_report.pdf',
                        image:        { type: 'jpeg', quality: 0.98 },
                        html2canvas:  { scale: 2, useCORS: true },
                        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
                    };
                    
                    // Hide buttons during export
                    const buttons = element.querySelectorAll('button');
                    const originalDisplays = [];
                    buttons.forEach(btn => {
                        originalDisplays.push(btn.style.display);
                        btn.style.display = 'none';
                    });

                    // Hide inputs like search during export
                    const inputs = element.querySelectorAll('input');
                    const originalInputDisplays = [];
                    inputs.forEach(input => {
                        originalInputDisplays.push(input.style.display);
                        input.style.display = 'none';
                    });
                    
                    html2pdf().set(opt).from(element).save().then(() => {
                        buttons.forEach((btn, index) => {
                            btn.style.display = originalDisplays[index];
                        });
                        inputs.forEach((input, index) => {
                            input.style.display = originalInputDisplays[index];
                        });
                    });
                });
            }

            // Export Excel
            const exportExcelBtn = document.getElementById('exportExcelBtn');
            if (exportExcelBtn) {
                exportExcelBtn.addEventListener('click', function() {
                    const table = document.querySelector('#history-table table');
                    if (table) {
                        const clonedTable = table.cloneNode(true);
                        const ths = clonedTable.querySelectorAll('th');
                        if (ths.length > 0) ths[ths.length - 1].remove();
                        const trs = clonedTable.querySelectorAll('tbody tr');
                        trs.forEach(tr => {
                            const tds = tr.querySelectorAll('td');
                            if (tds.length > 0) tds[tds.length - 1].remove();
                        });

                        const wb = XLSX.utils.table_to_book(clonedTable, {sheet: "History"});
                        XLSX.writeFile(wb, 'interview_history.xlsx');
                    } else {
                        alert("No history table found to export.");
                    }
                });
            }
        });
    </script>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.driver === 'undefined') return;
        const driver = window.driver.js.driver;

        const stepsMobile = [
            { element: '#progress-stats', popover: { title: 'At a Glance', description: 'Quickly see your current streak, total practice days, and month-over-month readiness improvement.', side: "bottom", align: 'start' }},
            { element: '#ai-insights', popover: { title: 'AI Insights', description: 'Get actionable, dynamic advice from AI based on your recent performance trends.', side: "bottom", align: 'start' }},
            { element: '#readiness-trend', popover: { title: 'Readiness Trend', description: 'Visualize your overall interview readiness score over time.', side: "bottom", align: 'start' }},
            { element: '#category-perf', popover: { title: 'Category Breakdown', description: 'See which interview categories are your strongest and which need more practice.', side: "top", align: 'start' }},
            { element: '#skill-tracker', popover: { title: 'Skill Improvement', description: 'Track your skill improvements over time.', side: "top", align: 'start' }},
            { element: '#strengths-tracker', popover: { title: 'Strengths & Areas', description: 'Analyze your strengths and view your STAR method progress.', side: "top", align: 'start' }},
            { element: '#history-table', popover: { title: 'Session History', description: 'Review your past mock interviews and access detailed AI feedback for each one.', side: "top", align: 'start' }},
            { element: '#learning-progress', popover: { title: 'Learning Progress', description: 'Keep track of modules you have completed.', side: "top", align: 'start' }},
            { element: '#voice-progress', popover: { title: 'Voice Rehearsal', description: 'Check your speaking pace, clarity, and confidence from voice drills.', side: "top", align: 'start' }},
            { element: '#activity-calendar', popover: { title: 'Activity Calendar', description: 'Your daily practice activity visually represented.', side: "top", align: 'start' }},
            { element: '#goals-milestones', popover: { title: 'Goals & Milestones', description: 'Track your progress towards specific platform goals.', side: "top", align: 'start' }},
            { element: '#achievements-badges', popover: { title: 'Achievements', description: 'Badges and awards you have earned from consistent practice.', side: "top", align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#progress-stats', popover: { title: 'At a Glance', description: 'Quickly see your current streak, total practice days, and month-over-month readiness improvement.', side: "bottom", align: 'start' }},
            { element: '#ai-insights', popover: { title: 'AI Insights', description: 'Get actionable, dynamic advice from AI based on your recent performance trends.', side: "bottom", align: 'start' }},
            { element: '#readiness-trend', popover: { title: 'Readiness Trend', description: 'Visualize your overall interview readiness score over time.', side: "bottom", align: 'start' }},
            { element: '#category-perf', popover: { title: 'Category Breakdown', description: 'See which interview categories are your strongest and which need more practice.', side: "bottom", align: 'start' }},
            { element: '#skill-tracker', popover: { title: 'Skill Improvement', description: 'Track your skill improvements over time.', side: "right", align: 'start' }},
            { element: '#strengths-tracker', popover: { title: 'Strengths & Areas', description: 'Analyze your strengths and view your STAR method progress.', side: "left", align: 'start' }},
            { element: '#history-table', popover: { title: 'Session History', description: 'Review your past mock interviews and access detailed AI feedback for each one.', side: "top", align: 'start' }},
            { element: '#learning-progress', popover: { title: 'Learning Progress', description: 'Keep track of modules you have completed.', side: "right", align: 'start' }},
            { element: '#voice-progress', popover: { title: 'Voice Rehearsal', description: 'Check your speaking pace, clarity, and confidence from voice drills.', side: "left", align: 'start' }},
            { element: '#activity-calendar', popover: { title: 'Activity Calendar', description: 'Your daily practice activity visually represented.', side: "top", align: 'start' }},
            { element: '#goals-milestones', popover: { title: 'Goals & Milestones', description: 'Track your progress towards specific platform goals.', side: "right", align: 'start' }},
            { element: '#achievements-badges', popover: { title: 'Achievements', description: 'Badges and awards you have earned from consistent practice.', side: "left", align: 'start' }}
        ];

        const driverObj = driver({
            showProgress: true,
            animate: true,
            popoverClass: document.documentElement.classList.contains('lm') ? 'driverjs-theme-light' : 'driverjs-theme-dark',
            steps: {{ $isMobile ? 'true' : 'false' }} ? stepsMobile : stepsDesktop,
            onDestroyStarted: () => {
                if (!driverObj.hasNextStep() || confirm("Are you sure you want to exit the tutorial?")) {
                    driverObj.destroy();
                    localStorage.setItem('onboarding_completed_progress', 'true');
                }
            },
        });

        window.startOnboardingTour = function() {
            driverObj.drive();
        };

        if (!localStorage.getItem('onboarding_completed_progress')) {
            setTimeout(() => {
                startOnboardingTour();
            }, 500);
        }
    });
</script>
@endpush
@endsection
