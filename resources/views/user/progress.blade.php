@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<style>
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    .premium-panel {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .premium-panel:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1), inset 0 1px 1px rgba(255, 255, 255, 0.08);
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
</style>

<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="text-gradient-primary" style="font-size:1.4rem;font-weight:800;margin-bottom:4px;letter-spacing:-0.5px;text-transform:uppercase;">
<i class="fa-solid fa-chart-line me-2"></i>Progress Tracking</h4>
            <p style="color:var(--tx3)">Visualize your interview readiness improvement over time.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <!-- Feature 15: Progress Reports -->
            <button class="btn btn-primary btn-shine" id="exportPdfBtn" style="border-radius:12px;font-weight:600;"><i class="fa-solid fa-file-pdf me-1"></i> Export PDF</button>
            <button class="btn btn-success btn-shine" id="exportExcelBtn" style="border-radius:12px;font-weight:600;"><i class="fa-solid fa-file-excel me-1"></i> Export Excel</button>
        </div>
    </div>

    <!-- Feature 9, 14: Top Stats (Streaks, Comparison) -->
    <div id="progress-stats" class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="premium-panel text-center">
                <i class="fa-solid fa-fire text-warning fs-1 mb-2"></i>
                <h3 style="color:var(--tx);margin:0;font-weight:bold;">{{ $currentStreak }} Days</h3>
                <p style="color:var(--tx3);margin:0;font-size:0.9rem;">Current Streak</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.2s;">
            <div class="premium-panel text-center">
                <i class="fa-solid fa-fire-flame-curved text-danger fs-1 mb-2"></i>
                <h3 style="color:var(--tx);margin:0;font-weight:bold;">{{ $longestStreak }} Days</h3>
                <p style="color:var(--tx3);margin:0;font-size:0.9rem;">Longest Streak</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.3s;">
            <div class="premium-panel text-center">
                <i class="fa-solid fa-calendar-check text-success fs-1 mb-2"></i>
                <h3 style="color:var(--tx);margin:0;font-weight:bold;">{{ $totalPracticeDays }}</h3>
                <p style="color:var(--tx3);margin:0;font-size:0.9rem;">Total Practice Days</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.4s;">
            <div class="premium-panel text-center">
                <i class="fa-solid fa-arrow-trend-up text-primary fs-1 mb-2"></i>
                <h3 style="color:var(--tx);margin:0;font-weight:bold;">{{ $readinessMovement?->label ?? 'N/A' }}</h3>
                <p style="color:var(--tx3);margin:0;font-size:0.9rem;">Readiness vs Last</p>
            </div>
        </div>
    </div>

    <!-- Feature 13: AI Progress Insights -->
    @if($readinessMovement)
        <div id="ai-insights" class="alert border-0 mb-4 animate-fade-up" style="animation-delay: 0.5s; border-radius:24px; background: rgba(59, 130, 246, 0.1); color: var(--tx); box-shadow: inset 0 2px 10px rgba(255,255,255,0.05); padding: 20px;">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-robot fs-2 me-3 text-primary" style="background: var(--bg); border-radius: 12px; padding: 12px; box-shadow: 0 4px 15px rgba(59,130,246,0.1);"></i>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold text-primary">AI Progress Insights</h6>
                    <p class="mb-0">Your overall readiness score {!! $readinessMovement->trend_html !!} recently. <br>
                    <strong>Recommended Next Step:</strong> Review your recent feedback to identify specific improvement areas.</p>
                </div>
            </div>
        </div>
    @elseif($scoredSessions->count() === 1)
        <div id="ai-insights" class="alert border-0 mb-4 animate-fade-up" style="animation-delay: 0.5s; border-radius:24px; background: rgba(59, 130, 246, 0.1); color: var(--tx); box-shadow: inset 0 2px 10px rgba(255,255,255,0.05); padding: 20px;">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-robot fs-2 me-3 text-primary" style="background: var(--bg); border-radius: 12px; padding: 12px; box-shadow: 0 4px 15px rgba(59,130,246,0.1);"></i>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold text-primary">AI Progress Insights</h6>
                    <p class="mb-0">Complete one more scored mock interview to compare readiness movement accurately.</p>
                </div>
            </div>
        </div>
    @else
        <div id="ai-insights" class="alert border-0 mb-4 animate-fade-up" style="animation-delay: 0.5s; border-radius:24px; background: rgba(59, 130, 246, 0.1); color: var(--tx); box-shadow: inset 0 2px 10px rgba(255,255,255,0.05); padding: 20px;">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-robot fs-2 me-3 text-primary" style="background: var(--bg); border-radius: 12px; padding: 12px; box-shadow: 0 4px 15px rgba(59,130,246,0.1);"></i>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold text-primary">AI Progress Insights</h6>
                    <p class="mb-0">Complete at least 2 mock interviews to generate personalized AI progress insights.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <!-- Feature 1: Readiness Score Trend -->
        <div class="col-md-8 animate-fade-up" id="readiness-trend" style="animation-delay: 0.6s;">
            <div class="premium-panel" style="height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Overall Readiness Trend</h5>
                <div style="height: 250px;">
                    <canvas id="readinessChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Feature 3: Category Performance Analysis -->
        <div class="col-md-4 animate-fade-up" id="category-perf" style="animation-delay: 0.7s;">
            <div class="premium-panel" style="height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Category Performance</h5>
                <div style="height: 250px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 4: Skill Improvement Tracker -->
        <div class="col-md-6 animate-fade-up" id="skill-tracker" style="animation-delay: 0.8s;">
            <div class="premium-panel" style="height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Skill Improvement Tracker</h5>
                
                @if(count($skillComparison) > 0)
                @foreach($skillComparison as $metric)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.9rem;">
                        <span style="color:var(--tx);font-weight:600;">{{ $metric['label'] }}</span>
                        <span style="color:var(--tx3)">{{ $metric['previous'] }}% <i class="fa-solid fa-arrow-right mx-1" style="font-size:0.8em"></i> {{ $metric['current'] }}%
                        @if($metric['delta'] >= 0)
                            <span class="text-success ms-1">(+{{ $metric['delta'] }}%)</span>
                        @else
                            <span class="text-danger ms-1">({{ $metric['delta'] }}%)</span>
                        @endif
                        </span>
                    </div>
                    <div class="progress" style="height: 8px; background:var(--bd); border-radius: 4px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $metric['bar'] }}%; background: #3b82f6; border-radius: 4px;"></div>
                    </div>
                </div>
                @endforeach
                @else
                    <div class="text-center py-5" style="color:var(--tx3);">
                        <i class="fa-solid fa-chart-bar fs-2 mb-3" style="color:var(--bd);"></i>
                        <p>Complete multiple mock interviews to track your specific skill improvements.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Feature 12: Strengths & Areas for Improvement -->
        <div class="col-md-6 animate-fade-up" id="strengths-tracker" style="animation-delay: 0.9s;">
            <div class="premium-panel" style="height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Strengths & Areas for Improvement</h5>
                @php
                    $strengths = $latestSkillSummary->strengths ?: ['None identified yet'];
                    $weaknesses = $latestSkillSummary->weaknesses ?: ['None identified yet'];
                @endphp
                @if($latestSkillSummary->has_data)
                <div class="row mb-4">
                    <div class="col-6">
                        <h6 class="text-success fw-bold"><i class="fa-solid fa-arrow-trend-up me-2"></i>Strengths</h6>
                        <ul class="list-group list-group-flush bg-transparent" style="font-size:0.9rem;">
                            @foreach(array_slice($strengths, 0, 3) as $str)
                            <li class="list-group-item bg-transparent px-0 py-1" style="color:var(--tx);border:none;"><i class="fa-solid fa-check text-success me-2"></i>{{ $str }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-6">
                        <h6 class="text-warning fw-bold"><i class="fa-solid fa-arrow-trend-down me-2"></i>Needs Work</h6>
                        <ul class="list-group list-group-flush bg-transparent" style="font-size:0.9rem;">
                            @foreach(array_slice($weaknesses, 0, 3) as $wk)
                            <li class="list-group-item bg-transparent px-0 py-1" style="color:var(--tx);border:none;"><i class="fa-solid fa-xmark text-warning me-2"></i>{{ $wk }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @else
                <div class="text-center py-4 mb-4" style="color:var(--tx3);">
                    <p>Complete an interview to see strengths and areas for improvement.</p>
                </div>
                @endif

                <!-- Feature 7: STAR Method Progress -->
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">STAR Method Progress</h5>
                <div class="text-center py-4" style="color:var(--tx3); font-style:italic;">
                    <p>Insufficient data to analyze your STAR Method usage. Keep practicing behavioral questions!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2: Interview Performance History -->
    <div class="row mb-4 animate-fade-up" id="history-table" style="animation-delay: 1s;">
        <div class="col-12">
            <div class="premium-panel">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                    <h5 style="color:var(--tx);margin:0;font-weight:bold;">Interview Performance History</h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if($sessions->count() > 0)
                            <form action="{{ route('user.sessions.clear') }}" method="POST" onsubmit="return confirm('Clear all completed interview sessions? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" style="border-radius:8px;font-weight:600;">
                                    <i class="fa-solid fa-trash-can me-1"></i> Clear All
                                </button>
                            </form>
                        @endif
                        <div class="input-group" style="width:250px;">
                            <span class="input-group-text border-0" style="background:var(--bg);color:var(--tx3);"><i class="fa-solid fa-search"></i></span>
                            <input type="text" id="historySearch" class="form-control border-0" placeholder="Search History..." style="background:var(--bg);color:var(--tx);">
                        </div>
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
                                <td class="border-0 py-3">
                                    @if($session->score)
                                        {{ $session->score->overall_readiness_score }}%
                                    @else
                                        <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Score pending</span>
                                    @endif
                                </td>
                                <td class="border-0 py-3">
                                    @php $sc = $session->score ? $session->score->overall_readiness_score : null; @endphp
                                    @if($sc === null) <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Not scored</span>
                                    @elseif($sc >= 90) <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">Excellent</span>
                                    @elseif($sc >= 70) <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Good</span>
                                    @elseif($sc >= 50) <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">Average</span>
                                    @else <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">Needs Work</span>
                                    @endif
                                </td>
                                <td class="border-0 py-3 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">View Feedback</a>
                                        <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this interview session? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete session" style="border-radius:8px;">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            @if($sessions->count() == 0)
                            <tr>
                                <td colspan="5" class="text-center py-4" style="color:var(--tx3);font-style:italic;">No interview records found. Start a mock interview to track your progress!</td>
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
                @forelse($learningProgress as $lp)
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.9rem;">
                        <span style="color:var(--tx); text-transform:capitalize; font-weight:600;">{{ $lp->learningModule ? $lp->learningModule->title : 'Module' }}</span>
                        <span style="color:var(--tx3)">{{ $lp->progress_percentage }}%</span>
                    </div>
                    <div class="progress" style="height: 8px; background:var(--bd); border-radius: 4px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $lp->progress_percentage }}%; border-radius: 4px;"></div>
                    </div>
                </div>
                @empty
                    <div class="text-center py-5" style="color:var(--tx3);">
                        <i class="fa-solid fa-graduation-cap fs-2 mb-3" style="color:var(--bd);"></i>
                        <p>No learning progress recorded yet.</p>
                    </div>
                @endforelse
                
                @if($learningProgress->count() > 0)
                <div class="d-flex align-items-center mt-4 p-3" style="background: rgba(13, 202, 240, 0.1); border-radius: 12px;">
                    <div class="me-3">
                        <i class="fa-solid fa-graduation-cap text-info fs-1"></i>
                    </div>
                    <div>
                        <h3 class="text-info mb-0 fw-bold">{{ round($learningProgress->avg('progress_percentage') ?? 0) }}%</h3>
                        <small style="color:var(--tx3)">Overall Learning Completion Rate</small>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Feature 6: Voice Rehearsal Progress -->
        <div class="col-md-6" id="voice-progress">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Voice Rehearsal Progress</h5>
                @if($voiceSummary->latest)
                    @php $latestVoice = $voiceSummary->latest; $prevVoice = $voiceSummary->previous; @endphp
                    <div class="row text-center mb-4">
                        <div class="col-4 border-end" style="border-color:var(--bd) !important;">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ $latestVoice->speaking_pace ?? $latestVoice->wpm ?? 'N/A' }}</h3>
                            <small style="color:var(--tx3)">Pace (wpm)</small>
                        </div>
                        <div class="col-4 border-end" style="border-color:var(--bd) !important;">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ is_numeric($latestVoice->clarity_score) ? $latestVoice->clarity_score . '%' : 'N/A' }}</h3>
                            <small style="color:var(--tx3)">Clarity</small>
                        </div>
                        <div class="col-4">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ is_numeric($latestVoice->confidence_score) ? $latestVoice->confidence_score . '%' : 'N/A' }}</h3>
                            <small style="color:var(--tx3)">Confidence</small>
                        </div>
                    </div>
                    
                    <div class="p-3" style="background: rgba(16, 185, 129, 0.1); border-radius: 12px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-success mb-1 fw-bold">Filler Word Change</h6>
                                @if($prevVoice)
                                    <small style="color:var(--tx)">Previous: <strong>{{ $prevVoice->filler_words ?? 0 }}</strong> | Current: <strong>{{ $latestVoice->filler_words ?? 0 }}</strong></small>
                                @else
                                    <small style="color:var(--tx)">Complete another voice rehearsal to compare filler word movement.</small>
                                @endif
                            </div>
                            <h2 class="text-success mb-0 fw-bold">{{ $voiceSummary->filler_reduction === null ? 'N/A' : (($voiceSummary->filler_reduction > 0 ? '+' : '') . $voiceSummary->filler_reduction . '%') }}</h2>
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
                <div class="text-center py-5" style="color:var(--tx3);">
                    <i class="fa-solid fa-calendar-days fs-2 mb-3" style="color:var(--bd);"></i>
                    <p>Complete your first mock interview to start tracking your daily practice activity!</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Feature 10: Goals & Milestones -->
        <div class="col-md-6" id="goals-milestones">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Goals & Milestones</h5>
                @forelse($goals as $goal)
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2" style="font-size:0.9rem;">
                        <span style="color:var(--tx);font-weight:600;">{{ $goal->title }}</span>
                        <span style="color:var(--tx3)">{{ $goal->progress }}%</span>
                    </div>
                    <div class="progress" style="height: 10px; background:var(--bd); border-radius:5px;">
                        <div class="progress-bar {{ $goal->progress == 100 ? 'bg-success' : '' }}" role="progressbar" style="width: {{ $goal->progress }}%; {{ $goal->progress < 100 ? 'background:#3b82f6;' : '' }} border-radius:5px;"></div>
                    </div>
                </div>
                @empty
                    <div class="text-center py-5" style="color:var(--tx3);">
                        <i class="fa-solid fa-bullseye fs-2 mb-3" style="color:var(--bd);"></i>
                        <p>No goals defined yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Feature 11: Achievements & Badges -->
        <div class="col-md-6" id="achievements-badges">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;font-weight:bold;">Achievements & Badges</h5>
                <div class="row g-3">
                    @forelse($badges as $badge)
                    <div class="col-4 col-sm-3 text-center">
                        <div class="p-3 rounded-circle d-inline-flex justify-content-center align-items-center mb-2 shadow-sm" 
                             style="width: 65px; height: 65px; background: {{ $badge->unlocked ? 'linear-gradient(135deg, #fef08a, #f59e0b)' : 'var(--bg)' }}; border: 2px solid {{ $badge->unlocked ? '#f59e0b' : 'var(--bd)' }}; opacity: {{ $badge->unlocked ? '1' : '0.5' }};">
                            <i class="fa-solid {{ $badge->icon }} fs-3" style="color: {{ $badge->unlocked ? '#fff' : 'var(--tx3)' }}; text-shadow: {{ $badge->unlocked ? '0 2px 4px rgba(0,0,0,0.2)' : 'none' }};"></i>
                        </div>
                        <div style="font-size: 0.8rem; color: {{ $badge->unlocked ? 'var(--tx)' : 'var(--tx3)' }}; line-height: 1.2; font-weight:600;">{{ $badge->title }}</div>
                    </div>
                    @empty
                    <div class="col-12 text-center py-4" style="color:var(--tx3);">
                        <i class="fa-solid fa-trophy fs-2 mb-3" style="color:var(--bd);"></i>
                        <p>No achievements earned yet. Keep practicing!</p>
                    </div>
                    @endforelse
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

            const trendData = @json($scoreTrend);
            const categoryPerformance = @json($categoryPerf);
            
            // Feature 1: Readiness Trend
            const labels = trendData.map(s => s.date);
            const scores = trendData.map(s => s.score);
            
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
                const categoryLabels = Object.keys(categoryPerformance);
                const categoryData = Object.values(categoryPerformance);

                new Chart(document.getElementById('categoryChart'), {
                    type: 'bar',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            label: 'Avg Score',
                            data: categoryData,
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
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#progress-stats', popover: { title: 'At A Glance', description: 'Review streak, practice days, and readiness movement without opening a report.', side: 'bottom', align: 'start' }},
            { element: '#ai-insights', popover: { title: 'AI Insights', description: 'Use trend-based coaching notes to decide what to practice next.', side: 'bottom', align: 'start' }},
            { element: '#readiness-trend', popover: { title: 'Readiness Trend', description: 'Track how your overall readiness score changes over time.', side: 'bottom', align: 'start' }},
            { element: '#category-perf', popover: { title: 'Category Breakdown', description: 'Compare interview categories to find strengths and weak spots.', side: 'top', align: 'start' }},
            { element: '#skill-tracker', popover: { title: 'Skill Improvement', description: 'Watch the core interview skills that are improving across sessions.', side: 'top', align: 'start' }},
            { element: '#strengths-tracker', popover: { title: 'Strengths & STAR', description: 'Review strengths, areas to improve, and STAR method progress.', side: 'top', align: 'start' }},
            { element: '#history-table', popover: { title: 'Session History', description: 'Open previous interviews and detailed AI feedback from one place.', side: 'top', align: 'start' }},
            { element: '#learning-progress', popover: { title: 'Learning Progress', description: 'See how much of your module work is complete.', side: 'top', align: 'start' }},
            { element: '#voice-progress', popover: { title: 'Voice Rehearsal', description: 'Check speaking pace, clarity, and confidence from voice drills.', side: 'top', align: 'start' }},
            { element: '#activity-calendar', popover: { title: 'Activity Calendar', description: 'Use the calendar to spot consistent practice days and gaps.', side: 'top', align: 'start' }},
            { element: '#goals-milestones', popover: { title: 'Goals & Milestones', description: 'Track progress toward platform goals and target outcomes.', side: 'top', align: 'start' }},
            { element: '#achievements-badges', popover: { title: 'Achievements', description: 'Badges and awards appear here as your practice history grows.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#progress-stats', popover: { title: 'At A Glance', description: 'Review streak, practice days, and readiness movement without opening a report.', side: 'bottom', align: 'start' }},
            { element: '#ai-insights', popover: { title: 'AI Insights', description: 'Use trend-based coaching notes to decide what to practice next.', side: 'bottom', align: 'start' }},
            { element: '#readiness-trend', popover: { title: 'Readiness Trend', description: 'Track how your overall readiness score changes over time.', side: 'bottom', align: 'start' }},
            { element: '#category-perf', popover: { title: 'Category Breakdown', description: 'Compare interview categories to find strengths and weak spots.', side: 'bottom', align: 'start' }},
            { element: '#skill-tracker', popover: { title: 'Skill Improvement', description: 'Watch the core interview skills that are improving across sessions.', side: 'right', align: 'start' }},
            { element: '#strengths-tracker', popover: { title: 'Strengths & STAR', description: 'Review strengths, areas to improve, and STAR method progress.', side: 'left', align: 'start' }},
            { element: '#history-table', popover: { title: 'Session History', description: 'Open previous interviews and detailed AI feedback from one place.', side: 'top', align: 'start' }},
            { element: '#learning-progress', popover: { title: 'Learning Progress', description: 'See how much of your module work is complete.', side: 'right', align: 'start' }},
            { element: '#voice-progress', popover: { title: 'Voice Rehearsal', description: 'Check speaking pace, clarity, and confidence from voice drills.', side: 'left', align: 'start' }},
            { element: '#activity-calendar', popover: { title: 'Activity Calendar', description: 'Use the calendar to spot consistent practice days and gaps.', side: 'top', align: 'start' }},
            { element: '#goals-milestones', popover: { title: 'Goals & Milestones', description: 'Track progress toward platform goals and target outcomes.', side: 'right', align: 'start' }},
            { element: '#achievements-badges', popover: { title: 'Achievements', description: 'Badges and awards appear here as your practice history grows.', side: 'left', align: 'start' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_progress',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection


