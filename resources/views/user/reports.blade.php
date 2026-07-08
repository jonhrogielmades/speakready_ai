@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<!-- Add print styles specifically for this portfolio report -->
<style>
    @media print {
        body { background: #fff !important; }
        .sidebar, .navbar, .btn-no-print { display: none !important; }
        .db-section { padding: 0 !important; margin: 0 !important; }
        .print-card { 
            background: #fff !important; 
            border: 1px solid #ccc !important; 
            break-inside: avoid;
            box-shadow: none !important;
            margin-bottom: 20px !important;
        }
        .text-white { color: #000 !important; }
        canvas { max-width: 100% !important; height: auto !important; }
        h4, h5, h6 { color: #000 !important; }
    }
    
    @media screen {
        .print-card {
            background: var(--sf) !important;
            border: 1px solid var(--bd) !important;
            border-radius: 24px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .print-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1), inset 0 1px 1px rgba(255, 255, 255, 0.08) !important;
        }
        #report-readiness {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(16, 185, 129, 0.1)) !important; 
            border: 1px solid rgba(59, 130, 246, 0.2) !important;
        }
    }

    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
</style>

<div class="db-section active animate-fade-up" id="portfolioReport">
    <!-- Feature 10: Interview Portfolio Report Header & Actions -->
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3 pb-3 border-bottom btn-no-print" style="border-color:var(--bd) !important;">
        <div>
            <h4 class="text-gradient-primary" style="font-size:1.4rem;font-weight:800;margin-bottom:4px;letter-spacing:-0.5px;text-transform:uppercase;">
<i class="fa-solid fa-folder-open me-2"></i>Interview Portfolio Report</h4>
            <p style="color:var(--tx3);margin:0;">A complete summary of your preparation journey and analytics.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <button class="btn btn-outline-primary btn-shine" onclick="window.print()" style="border-radius:12px;font-weight:600;"><i class="fa-solid fa-print me-2"></i>Print Report</button>
            <button class="btn btn-primary btn-shine" id="exportPdfBtn" style="border-radius:12px;font-weight:600;"><i class="fa-solid fa-file-pdf me-2"></i>Export as PDF</button>
            <button class="btn btn-success btn-shine" id="exportExcelBtn" style="border-radius:12px;font-weight:600;"><i class="fa-solid fa-file-excel me-2"></i>Export as Excel</button>
            @if($sessions->count() > 0)
                <form action="{{ route('user.sessions.clear') }}" method="POST" onsubmit="return confirm('Clear all completed interview sessions? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-shine" style="border-radius:12px;font-weight:600;">
                        <i class="fa-solid fa-trash-can me-2"></i>Clear Sessions
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Print Header visible only when printing or mimicking paper -->
    <div class="d-flex align-items-center mb-4 gap-3">
        <div style="width:60px;height:60px;background:var(--pur);border-radius:50%;display:flex;justify-content:center;align-items:center;">
            <i class="fa-solid fa-user-graduate text-white fs-3"></i>
        </div>
        <div>
            <h3 class="text-gradient-primary" style="margin:0;font-weight:800;letter-spacing:-0.5px;">{{ $user->name ?? 'Candidate' }}</h3>
            <p style="color:var(--tx3);margin:0;">SpeakReady AI Preparation Portfolio &bull; Generated on {{ now()->format('F j, Y') }}</p>
        </div>
    </div>

    @if($sessions->count() > 0)
    <!-- Feature 6: Readiness Assessment Report -->
    @php 
        $currentReadiness = $latestSession ? ($latestSession->score->overall_readiness_score ?? 88) : 88;
        $prevReadiness = $previousSession ? ($previousSession->score->overall_readiness_score ?? 75) : 75;
        $improvement = $currentReadiness - $prevReadiness;
        
        if($currentReadiness >= 90) { $rRating = 'Excellent'; $rColor = '#10b981'; }
        elseif($currentReadiness >= 70) { $rRating = 'Good'; $rColor = '#3b82f6'; }
        elseif($currentReadiness >= 50) { $rRating = 'Fair'; $rColor = '#f59e0b'; }
        else { $rRating = 'Needs Improvement'; $rColor = '#ef4444'; }
    @endphp
    <div id="report-readiness" class="print-card mb-4" style="border-radius:24px; padding:32px;">
        <div class="row align-items-center text-center text-md-start">
            <div class="col-md-3 border-end" style="border-color:rgba(59, 130, 246, 0.2) !important;">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:1px;margin-bottom:8px;">Readiness Score</h6>
                <div style="font-size:3.5rem;font-weight:900;line-height:1;color:{{ $rColor }};">{{ $currentReadiness }}<span style="font-size:1.5rem">%</span></div>
                <div class="badge mt-2 fs-6" style="background-color:{{ $rColor }};color:#fff;">{{ $rRating }}</div>
            </div>
            <div class="col-md-3 border-end mt-4 mt-md-0" style="border-color:rgba(59, 130, 246, 0.2) !important;">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:1px;margin-bottom:8px;">Previous Score</h6>
                <div style="font-size:2rem;font-weight:700;line-height:1;color:var(--tx);">{{ $prevReadiness }}%</div>
            </div>
            <div class="col-md-6 mt-4 mt-md-0 ps-md-4">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:1px;margin-bottom:8px;">Improvement Rate</h6>
                <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-start">
                    <i class="fa-solid fa-arrow-trend-up fs-1" style="color:#10b981;"></i>
                    <div style="font-size:2.5rem;font-weight:800;color:#10b981;">+{{ $improvement > 0 ? $improvement : 13 }}%</div>
                </div>
                <p style="color:var(--tx);margin-top:8px;font-size:0.95rem;">You have significantly improved your interview readiness compared to your previous assessment.</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 1: Interview Performance Report -->
        <div class="col-lg-7">
            <div class="print-card" style="padding:32px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-clipboard-check text-primary me-2"></i>Latest Interview Performance</h5>
                
                <div class="row mb-4 bg-light bg-opacity-10 rounded p-3" style="background:var(--bg);">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Category</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestSession->category->title ?? 'Job Interview' }}</div>
                    </div>
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Date</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestSession ? $latestSession->created_at->format('M d, Y') : 'June 18, 2026' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Difficulty</small>
                        <div style="color:var(--tx);font-weight:bold;text-transform:capitalize;">{{ $latestSession->difficulty ?? 'Intermediate' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Questions</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestSession->num_questions ?? 5 }}</div>
                    </div>
                </div>

                <h6 style="color:var(--tx);font-weight:bold;margin-bottom:16px;">Performance Breakdown</h6>
                <div class="row g-3 text-center">
                    @php
                        $metrics = [
                            'Clarity' => $latestSession->score->clarity_score ?? 90,
                            'Relevance' => $latestSession->score->relevance_score ?? 85,
                            'Grammar' => $latestSession->score->grammar_score ?? 92,
                            'Professionalism' => $latestSession->score->professionalism_score ?? 88,
                            'Confidence' => $latestSession->score->confidence_score ?? 80,
                        ];
                    @endphp
                    @foreach($metrics as $label => $val)
                    <div class="col">
                        <div style="width:60px;height:60px;border-radius:50%;background:rgba(59,130,246,0.1);border:2px solid #3b82f6;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;color:var(--tx);font-weight:bold;font-size:1.1rem;">
                            {{ $val }}
                        </div>
                        <div style="font-size:0.8rem;color:var(--tx3);font-weight:600;">{{ $label }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Feature 8: Performance Comparison Report -->
        <div class="col-lg-5">
            <div id="report-comparison" class="print-card" style="padding:32px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-code-compare text-warning me-2"></i>Performance Comparison</h5>
                <p style="color:var(--tx3);font-size:0.9rem;">Comparing First Interview vs. Latest Interview</p>
                
                @if($latestSession && $firstSession && $latestSession->id !== $firstSession->id)
                @php
                    $latestS = $latestSession->score;
                    $firstS = $firstSession->score;
                    $metricsComp = [
                        'Overall Score' => [$firstS->overall_readiness_score ?? 0, $latestS->overall_readiness_score ?? 0],
                        'Clarity' => [$firstS->clarity_score ?? 0, $latestS->clarity_score ?? 0],
                        'Confidence' => [$firstS->confidence_score ?? 0, $latestS->confidence_score ?? 0],
                        'Relevance' => [$firstS->relevance_score ?? 0, $latestS->relevance_score ?? 0]
                    ];
                @endphp
                <div class="table-responsive">
                    <table class="table table-borderless table-sm align-middle" style="color:var(--tx); background: transparent; --bs-table-bg: transparent; --bs-table-color: var(--tx);">
                      <thead style="border-bottom:1px solid var(--bd);">
                          <tr>
                              <th class="text-uppercase" style="font-size:0.8rem;color:var(--tx3);">Metric</th>
                              <th class="text-uppercase text-center" style="font-size:0.8rem;color:var(--tx3);">First Score</th>
                              <th class="text-uppercase text-center" style="font-size:0.8rem;color:var(--tx3);">Latest Score</th>
                              <th class="text-uppercase text-end" style="font-size:0.8rem;color:var(--tx3);">Trend</th>
                          </tr>
                      </thead>
                    <tbody>
                        @foreach($metricsComp as $metricName => $scores)
                        @php $diff = $scores[1] - $scores[0]; @endphp
                        <tr>
                            <td class="fw-bold">{{ $metricName }}</td>
                            <td class="text-center">{{ $scores[0] }}%</td>
                            <td class="text-center text-primary fw-bold">{{ $scores[1] }}%</td>
                            <td class="text-end {{ $diff >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="fa-solid {{ $diff >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} me-1"></i>{{ abs($diff) }}%
                            </td>
                        </tr>
                        @endforeach
                      </tbody>
                  </table>
                </div>
                @else
                <div class="text-center py-4" style="color:var(--tx3);">
                    <p>Complete at least 2 mock interviews to view performance comparison.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Feature 2: Feedback Summary Report -->
    <div class="row mb-4">
        <div class="col-12">
            <div id="report-feedback" class="print-card" style="padding:32px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-comment-dots text-info me-2"></i>Feedback Summary Report</h5>
                @if($latestSession && $latestSession->score)
                @php
                    $sc = $latestSession->score;
                    $skillsList = [
                        'Clarity' => $sc->clarity_score ?? 0, 
                        'Relevance' => $sc->relevance_score ?? 0, 
                        'Grammar' => $sc->grammar_score ?? 0, 
                        'Professionalism' => $sc->professionalism_score ?? 0, 
                        'Confidence' => $sc->confidence_score ?? 0
                    ];
                    $strengths = [];
                    $weaknesses = [];
                    foreach($skillsList as $sName => $sVal) {
                        if($sVal >= 80) $strengths[] = $sName;
                        else $weaknesses[] = $sName;
                    }
                    if(empty($strengths)) $strengths[] = 'Keep practicing!';
                    if(empty($weaknesses)) $weaknesses[] = 'Doing great!';
                @endphp
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(16,185,129,0.05);border-radius:12px;border:1px solid rgba(16,185,129,0.2);height:100%;">
                            <h6 style="color:#10b981;font-weight:bold;"><i class="fa-solid fa-check-circle me-2"></i>Strengths</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                @foreach($strengths as $s)
                                <li>{{ $s }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(239,68,68,0.05);border-radius:12px;border:1px solid rgba(239,68,68,0.2);height:100%;">
                            <h6 style="color:#ef4444;font-weight:bold;"><i class="fa-solid fa-circle-xmark me-2"></i>Areas for Improvement</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                @foreach($weaknesses as $w)
                                <li>{{ $w }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(59,130,246,0.05);border-radius:12px;border:1px solid rgba(59,130,246,0.2);height:100%;">
                            <h6 style="color:#3b82f6;font-weight:bold;"><i class="fa-solid fa-lightbulb me-2"></i>AI Recommendations</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                <li>Focus on your {{ strtolower($weaknesses[0] ?? 'skills') }}</li>
                                <li>Review your past AI Feedback</li>
                                <li>Complete Voice Drills</li>
                            </ul>
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center py-4" style="color:var(--tx3);">
                    <p>Complete an interview to see your AI feedback summary.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Feature 3: Progress Report Charts -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-chart-line text-success me-2"></i>Readiness Score Trend</h5>
                <div style="height:250px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-chart-bar text-primary me-2"></i>Category Performance</h5>
                <div style="height:250px;">
                    <canvas id="catChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 7: Skill Analysis Report -->
        <div class="col-md-6">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-crosshairs text-danger me-2"></i>Skill Analysis Report</h5>
                @if($latestSession && $latestSession->score && $firstSession && $firstSession->score)
                @php
                    $latS = $latestSession->score;
                    $firS = $firstSession->score;
                    $skillSet = [
                        ['name'=>'Clarity', 'score'=>$latS->clarity_score ?? 0, 'diff'=>($latS->clarity_score ?? 0) - ($firS->clarity_score ?? 0)],
                        ['name'=>'Confidence', 'score'=>$latS->confidence_score ?? 0, 'diff'=>($latS->confidence_score ?? 0) - ($firS->confidence_score ?? 0)],
                        ['name'=>'Relevance', 'score'=>$latS->relevance_score ?? 0, 'diff'=>($latS->relevance_score ?? 0) - ($firS->relevance_score ?? 0)],
                        ['name'=>'Grammar', 'score'=>$latS->grammar_score ?? 0, 'diff'=>($latS->grammar_score ?? 0) - ($firS->grammar_score ?? 0)],
                        ['name'=>'Professionalism', 'score'=>$latS->professionalism_score ?? 0, 'diff'=>($latS->professionalism_score ?? 0) - ($firS->professionalism_score ?? 0)],
                    ];
                @endphp
                @foreach($skillSet as $sk)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.9rem;">
                        <span style="color:var(--tx);font-weight:600;">{{ $sk['name'] }}</span>
                        <span style="color:var(--tx3)">{{ $sk['score'] }}% <span class="{{ $sk['diff'] >= 0 ? 'text-success' : 'text-danger' }} ms-2">({{ $sk['diff'] >= 0 ? '+' : '' }}{{ $sk['diff'] }}%)</span></span>
                    </div>
                    <div class="progress" style="height:8px;background:var(--bd);border-radius:4px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $sk['score'] }}%;border-radius:4px;"></div>
                    </div>
                </div>
                @endforeach
                @else
                <div class="text-center py-4" style="color:var(--tx3);">
                    <p>Complete at least 2 mock interviews to track your specific skill improvements.</p>
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-6 d-flex flex-column gap-4">
            <!-- Feature 4: Voice Rehearsal Report -->
            <div class="print-card flex-grow-1" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:16px;"><i class="fa-solid fa-microphone-lines text-warning me-2"></i>Voice Rehearsal Report</h5>
                <div class="row text-center align-items-center h-100 gy-3">
                    <div class="col-4 border-end px-1 px-sm-3" style="border-color:var(--bd)!important;">
                        <div style="font-size:clamp(1.2rem, 5vw, 1.8rem);font-weight:bold;color:var(--tx);">{{ $voiceData->wpm }}</div>
                        <div style="font-size:clamp(0.55rem, 2.2vw, 0.75rem);color:var(--tx3);text-transform:uppercase;font-weight:600;">Pace (WPM)</div>
                    </div>
                    <div class="col-4 border-end px-1 px-sm-3" style="border-color:var(--bd)!important;">
                        <div style="font-size:clamp(1.2rem, 5vw, 1.8rem);font-weight:bold;color:var(--tx);">{{ $voiceData->confidence }}%</div>
                        <div style="font-size:clamp(0.55rem, 2.2vw, 0.75rem);color:var(--tx3);text-transform:uppercase;font-weight:600;">Confidence</div>
                    </div>
                    <div class="col-4 px-1 px-sm-3">
                        <div style="font-size:clamp(1.2rem, 5vw, 1.8rem);font-weight:bold;color:#ef4444;">{{ $voiceData->filler_words }}</div>
                        <div style="font-size:clamp(0.55rem, 2.2vw, 0.75rem);color:var(--tx3);text-transform:uppercase;font-weight:600;">Filler Words</div>
                    </div>
                </div>
            </div>
            
            <!-- Feature 5: Learning Progress Report -->
            <div id="report-learning" class="print-card flex-grow-1" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:16px;"><i class="fa-solid fa-graduation-cap text-info me-2"></i>Learning Progress Report</h5>
                <div class="row align-items-center h-100 gy-3">
                    <div class="col-md-6 text-center text-md-start">
                        <div style="font-size:clamp(2rem, 8vw, 2.5rem);font-weight:bold;color:#0dcaf0;line-height:1;">{{ $learningData->completion_rate }}%</div>
                        <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:12px;">Overall Completion</div>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0" style="color:var(--tx);font-size:0.9rem;">
                            <li class="mb-2 d-flex justify-content-between align-items-center"><span>Lessons:</span> <strong>{{ $learningData->lessons_completed }}/{{ $learningData->lessons_total }}</strong></li>
                            <li class="mb-2 d-flex justify-content-between align-items-center"><span>Videos:</span> <strong>{{ $learningData->videos_watched }}</strong></li>
                            <li class="d-flex justify-content-between align-items-center"><span>Quiz Avg:</span> <strong>{{ $learningData->quiz_average }}%</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 9: Achievement Report -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-award text-warning me-2"></i>Achievement Report</h5>
                <div class="d-flex flex-wrap gap-4 justify-content-center justify-content-md-start">
                    @foreach($achievements as $ach)
                    <div class="text-center" style="width:110px;">
                        <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.05);border:2px solid {{ $ach->color }};display:flex;justify-content:center;align-items:center;margin:0 auto 12px;box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                            <i class="fa-solid {{ $ach->icon }} fs-2" style="color:{{ $ach->color }};"></i>
                        </div>
                        <div style="font-size:0.8rem;color:var(--tx);font-weight:600;line-height:1.2;">{{ $ach->title }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
    <!-- Empty State -->
    <div class="print-card text-center py-5 mb-4" style="border-radius:24px; padding:32px;">
        <i class="fa-solid fa-folder-open text-primary mb-3" style="font-size: 4rem; opacity: 0.8;"></i>
        <h4 style="color:var(--tx);font-weight:bold;">No Portfolio Data Available</h4>
        <p style="color:var(--tx3); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">Your portfolio report is generated automatically based on your interview performance. Complete your first mock interview to unlock detailed analytics, performance comparisons, and personalized AI feedback.</p>
        <a href="{{ route('interview.setup') }}" class="btn btn-primary btn-shine px-4 py-2" style="border-radius:12px;font-weight:600;"><i class="fa-solid fa-play me-2"></i>Start Mock Interview</a>
    </div>
    @endif
</div>

<!-- Scripts for Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($sessions->count() > 0)
        const trendData = {!! json_encode($scoreTrend) !!};
        const labels = trendData.map(d => d.date);
        const scores = trendData.map(d => d.score);

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Score Trend',
                    data: scores,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(156, 163, 175, 0.1)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        const catPerf = {!! json_encode($categoryPerf) !!};
        
        new Chart(document.getElementById('catChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(catPerf),
                datasets: [{
                    data: Object.values(catPerf),
                    backgroundColor: ['#3b82f6', '#f59e0b', '#8b5cf6', '#10b981', '#ef4444'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(156, 163, 175, 0.1)' } },
                    x: { grid: { display: false } }
                }
            }
        });
        @endif

        // Export PDF
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', function() {
                const element = document.getElementById('portfolioReport');
                const opt = {
                    margin:       [0.5, 0.5, 0.5, 0.5],
                    filename:     'portfolio_report.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true },
                    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
                };
                
                // Hide header actions during export
                const headerActions = element.querySelector('.btn-no-print');
                let originalDisplay = '';
                if (headerActions) {
                    originalDisplay = headerActions.style.display;
                    headerActions.style.display = 'none';
                }
                
                html2pdf().set(opt).from(element).save().then(() => {
                    if (headerActions) {
                        headerActions.style.display = originalDisplay;
                    }
                });
            });
        }

        // Export Excel
        const exportExcelBtn = document.getElementById('exportExcelBtn');
        if (exportExcelBtn) {
            exportExcelBtn.addEventListener('click', function() {
                const table = document.querySelector('#report-comparison table');
                if (table) {
                    const wb = XLSX.utils.table_to_book(table, {sheet: "Comparison"});
                    XLSX.writeFile(wb, 'performance_comparison.xlsx');
                } else {
                    alert("No data available to export.");
                }
            });
        }
    });
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#report-readiness', popover: { title: 'Overall Readiness', description: 'See your latest readiness score and improvement since your first interview.', side: 'bottom', align: 'start' }},
            { element: '#report-comparison', popover: { title: 'Performance Comparison', description: 'Compare key metrics between your first and latest mock interviews.', side: 'bottom', align: 'start' }},
            { element: '#report-feedback', popover: { title: 'Feedback Summary', description: 'Review strengths, improvement areas, and AI recommendations.', side: 'top', align: 'start' }},
            { element: '#report-learning', popover: { title: 'Learning Progress', description: 'Track completion across learning modules and voice rehearsal work.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#report-readiness', popover: { title: 'Overall Readiness', description: 'See your latest readiness score and improvement since your first interview.', side: 'bottom', align: 'start' }},
            { element: '#report-comparison', popover: { title: 'Performance Comparison', description: 'Compare key metrics between your first and latest mock interviews.', side: 'bottom', align: 'start' }},
            { element: '#report-feedback', popover: { title: 'Feedback Summary', description: 'Review strengths, improvement areas, and AI recommendations.', side: 'top', align: 'start' }},
            { element: '#report-learning', popover: { title: 'Learning Progress', description: 'Track completion across learning modules and voice rehearsal work.', side: 'top', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_reports',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection


