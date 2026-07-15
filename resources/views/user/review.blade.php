@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Detailed Feedback Report')

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
        background: var(--sf) !important;
        border: 1px solid var(--bd) !important;
        border-radius: 24px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .premium-panel:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1), inset 0 1px 1px rgba(255, 255, 255, 0.08) !important;
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
    .action-plan-grid { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px; }
    .action-plan-item { background:var(--bg);border:1px solid var(--bd);border-radius:12px;padding:16px; }
    .retry-panel { display:none;background:var(--bg);border:1px solid var(--bd);border-radius:14px;padding:16px;margin-top:18px; }
    .retry-panel.active { display:block; }
    .retry-meta { display:flex;gap:10px;flex-wrap:wrap;align-items:center; }
    .retry-chip { display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;background:rgba(59,130,246,.12);color:#3b82f6;font-size:.78rem;font-weight:700; }
    .timeline-row { display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--bd);padding:8px 0;font-size:.86rem;color:var(--tx2); }
    .timeline-row:last-child { border-bottom:0; }
    @media (max-width: 768px) {
        .action-plan-grid { grid-template-columns:1fr; }
        .premium-panel { border-radius:18px !important; }
        .retry-panel .btn { width:100%; }
        .retry-meta { flex-direction:column;align-items:stretch; }
        .retry-chip { justify-content:center; }
    }
</style>

<div class="db-section active animate-fade-up">
    @php
        $feedback = $sessionRecord->feedback;
        $strengths = trim($feedback->strengths ?? '');
        $weaknesses = trim($feedback->weaknesses ?? '');
        $suggestions = trim($feedback->improvement_suggestions ?? '');
        $feedbackSummary = $suggestions !== '' ? $suggestions : ($weaknesses !== '' ? $weaknesses : ($strengths !== '' ? $strengths : 'AI feedback was unavailable for this session.'));
        $comparisonRows = $comparisonRows ?? [];
        $actionPlan = is_array($sessionRecord->action_plan ?? null) ? $sessionRecord->action_plan : [];
        $actionPriorities = $actionPlan['priorities'] ?? [];
        $recommendedPaths = $actionPlan['recommended_paths'] ?? [];
        $mentorComments = $sessionRecord->mentorReviewComments ?? collect();
    @endphp
    <!-- Feature 2 & 15: Header, Report Info, Export -->
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <a href="{{ route('user.feedback') }}" class="btn btn-link text-decoration-none p-0 mb-2" style="color:#3b82f6;"><i class="fa-solid fa-arrow-left me-2"></i>Back to Feedback Center</a>
            <h4 class="text-gradient-primary" style="font-size:1.4rem;font-weight:800;margin-bottom:4px;letter-spacing:-0.5px;text-transform:uppercase;">
<i class="fa-solid fa-file-invoice me-2"></i>Detailed Feedback Report</h4>
            <div class="d-flex gap-3 mt-2" style="font-size:0.9rem;color:var(--tx3)">
                <span><i class="fa-regular fa-calendar me-1"></i> {{ $sessionRecord->created_at->format('M d, Y') }}</span>
                <span><i class="fa-solid fa-layer-group me-1"></i> {{ $sessionRecord->category->title ?? 'Job Interview' }}</span>
                <span><i class="fa-solid fa-signal me-1"></i> {{ ucfirst($sessionRecord->difficulty ?? 'Intermediate') }}</span>
                <span><i class="fa-regular fa-clock me-1"></i> {{ floor(($sessionRecord->duration_seconds ?? 0) / 60) }}m {{ ($sessionRecord->duration_seconds ?? 0) % 60 }}s</span>
            </div>
        </div>
        <div class="text-md-end d-flex gap-4 align-items-center flex-wrap mt-3 mt-md-0">
            <!-- Feature 3: Overall Performance Score & Rating -->
            <div class="text-start">
                @php 
                    $overall = $sessionRecord->score->overall_readiness_score ?? 0;
                    $rating = $sessionRecord->score->readiness_band
                        ?: ($overall >= 80 ? 'Ready for Simulation' : ($overall >= 60 ? 'Nearly Ready' : 'Developing'));
                    $color = $overall >= 80 ? '#10b981' : ($overall >= 60 ? '#3b82f6' : '#f59e0b');
                @endphp
                <div class="d-flex align-items-center gap-2 d-md-block">
                    <div style="font-size:2.5rem;font-weight:800;color:{{ $color }};line-height:1">{{ $overall }}<span style="font-size:1.2rem;color:var(--tx3)">%</span></div>
                    <div style="font-size:0.9rem;font-weight:600;color:{{ $color }}">{{ $rating }}</div>
                    @if(($sessionRecord->score->score_version ?? 1) >= 2)
                        <div style="font-size:.72rem;color:var(--tx3);margin-top:4px;">Rubric v{{ $sessionRecord->score->score_version }} · score confidence {{ $sessionRecord->score->scoring_confidence ?? 0 }}%</div>
                    @endif
                </div>
            </div>
            <div class="dropdown mt-2 mt-md-0 d-flex w-100 w-md-auto">
                <button class="btn btn-outline-primary me-2 flex-grow-1 flex-md-grow-0 btn-shine" id="btnShareSession" type="button" style="border-radius:12px;font-weight:600;" onclick="toggleShare()">
                    <i class="fa-solid fa-share-nodes me-2"></i>{{ $sessionRecord->is_public ? 'Shared Link' : 'Share Session' }}
                </button>
                <button class="btn btn-outline-secondary dropdown-toggle flex-grow-1 flex-md-grow-0 btn-shine" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-color:var(--bd);color:var(--tx);border-radius:12px;font-weight:600;">
                    <i class="fa-solid fa-download me-2"></i>Export
                </button>
                <ul class="dropdown-menu shadow-sm" style="background:var(--sf);border-color:var(--bd)">
                    <li><a class="dropdown-item" href="#" style="color:var(--tx)" onclick="event.preventDefault(); window.print();"><i class="fa-solid fa-file-pdf text-danger me-2"></i> PDF Format</a></li>
                    <li><a class="dropdown-item" href="{{ route('user.sessions.export', $sessionRecord) }}" style="color:var(--tx)"><i class="fa-solid fa-file-excel text-success me-2"></i> Excel CSV</a></li>
                </ul>
            </div>
        </div>
    </div>

    @if(!$sessionRecord->score_eligible)
        <div class="alert mb-4" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);color:var(--tx);border-radius:14px;">
            <i class="fa-solid fa-circle-info me-2 text-primary"></i>
            This was a coached practice session. Its feedback remains available, but it is not used as readiness evidence. Complete an assessment-mode session without live coaching for a calibrated score.
        </div>
    @endif

    <!-- Feature 7 & 14: AI Personalized Feedback & Recommendations -->
    <div class="row mb-4">
        <div class="col-12 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="premium-panel" style="background: linear-gradient(145deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1)) !important; border:1px solid rgba(59, 130, 246, 0.2) !important; padding:32px;">
                <div class="d-flex align-items-start gap-4">
                    <div style="background:#3b82f6; width:60px; height:60px; border-radius:50%; display:flex; justify-content:center; align-items:center; flex-shrink:0;">
                        <i class="fa-solid fa-robot text-white fs-3"></i>
                    </div>
                    <div class="row w-100">
                        <div class="col-md-7">
                            <h5 style="color:var(--tx);font-weight:bold;margin-bottom:12px;">AI Personalized Feedback</h5>
                            <p style="color:var(--tx);font-size:1rem;line-height:1.6;">
                                {!! nl2br(e($feedbackSummary)) !!}
                            </p>
                        </div>
                        <div class="col-md-5" style="border-left: 1px solid rgba(59, 130, 246, 0.2);">
                            <h5 style="color:var(--tx);font-weight:bold;margin-bottom:12px;"><i class="fa-solid fa-location-arrow me-2 text-primary"></i>Recommended Actions</h5>
                            <p style="color:var(--tx);font-size:0.95rem;line-height:1.8;margin:0;">{!! nl2br(e($suggestions ?: 'No recommendations were generated for this session.')) !!}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($actionPlan))
    <div class="row mb-4">
        <div class="col-12 animate-fade-up" style="animation-delay: 0.15s;">
            <div class="premium-panel" style="padding:24px;border:1px solid rgba(16,185,129,.22) !important;background:rgba(16,185,129,.04) !important;">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                    <div>
                        <h5 style="color:var(--tx);font-weight:800;margin-bottom:6px;"><i class="fa-solid fa-route me-2" style="color:#10b981"></i>Post-Session Action Plan</h5>
                        <p style="color:var(--tx3);margin:0;font-size:.92rem;">{{ $actionPlan['headline'] ?? 'Targeted practice plan' }}</p>
                    </div>
                    <div class="text-md-end">
                        <div style="font-size:.8rem;color:var(--tx3);font-weight:700;text-transform:uppercase;">Next Target</div>
                        <div style="font-size:1.8rem;font-weight:800;color:#10b981;line-height:1;">{{ $actionPlan['target_score'] ?? 70 }}%</div>
                    </div>
                </div>

                @if(!empty($actionPriorities))
                    <div class="action-plan-grid mb-3">
                        @foreach($actionPriorities as $priority)
                            <div class="action-plan-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong style="color:var(--tx);">{{ $priority['skill'] ?? 'Skill' }}</strong>
                                    <span style="color:#f59e0b;font-weight:800;">{{ $priority['score'] ?? 0 }}%</span>
                                </div>
                                <p style="color:var(--tx2);font-size:.9rem;line-height:1.55;margin:0;">{{ $priority['task'] ?? 'Retry this area with a more specific answer.' }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @foreach($recommendedPaths as $path)
                        <a class="btn btn-sm btn-outline-primary" style="border-radius:999px;font-weight:700;" href="{{ $path['url'] ?? route('interview.setup') }}">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>{{ $path['label'] ?? 'Practice' }}
                        </a>
                    @endforeach
                    @if(!empty($actionPlan['next_session']))
                        <span class="retry-chip"><i class="fa-solid fa-sliders"></i>{{ ucfirst($actionPlan['next_session']['difficulty'] ?? 'medium') }} next</span>
                        <span class="retry-chip"><i class="fa-solid fa-user-tie"></i>{{ ucfirst($actionPlan['next_session']['strictness'] ?? 'neutral') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row mb-4">
        <div class="col-12 animate-fade-up" style="animation-delay: 0.18s;">
            <div class="premium-panel" style="padding:24px;">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                    <div>
                        <h5 style="color:var(--tx);font-weight:800;margin-bottom:6px;"><i class="fa-solid fa-user-pen me-2 text-primary"></i>Mentor / Peer Reviews</h5>
                        <p style="color:var(--tx3);margin:0;font-size:.9rem;">Share this report and collect comments from mentors, teachers, or peers.</p>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" style="border-radius:999px;font-weight:800;" onclick="toggleShare()">
                        <i class="fa-solid fa-share-nodes me-1"></i>Get Share Link
                    </button>
                </div>
                @forelse($mentorComments as $comment)
                    <div style="border-top:1px solid var(--bd);padding:13px 0;">
                        <div class="d-flex justify-content-between gap-3">
                            <strong style="color:var(--tx);">{{ $comment->reviewer_name }}</strong>
                            <div style="color:var(--tx3);font-size:.82rem;">
                                @if($comment->rating)
                                    <span style="color:#f59e0b;font-weight:900;">{{ $comment->rating }}/5</span>
                                @endif
                                <span class="ms-2">{{ $comment->created_at?->format('M d, Y') }}</span>
                            </div>
                        </div>
                        <p style="color:var(--tx2);font-size:.92rem;line-height:1.6;margin:6px 0 0;">{{ $comment->comment }}</p>
                    </div>
                @empty
                    <div style="border:1px dashed var(--bd);border-radius:12px;padding:18px;color:var(--tx3);">No mentor comments yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Feature 5 & 6: Strengths and Areas for Improvement -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 animate-fade-up" style="animation-delay: 0.2s;">
            <div class="premium-panel" style="background:rgba(16, 185, 129, 0.05) !important;border:1px solid rgba(16, 185, 129, 0.2) !important;padding:24px;height:100%">
                <h5 style="color:#10b981;font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-thumbs-up me-2"></i>Strengths</h5>
                <p style="color:var(--tx);line-height:1.8;margin:0;">{!! nl2br(e($strengths ?: 'No strengths were generated for this session.')) !!}</p>
            </div>
        </div>
        <div class="col-md-6 animate-fade-up" style="animation-delay: 0.3s;">
            <div class="premium-panel" style="background:rgba(239, 68, 68, 0.05) !important;border:1px solid rgba(239, 68, 68, 0.2) !important;padding:24px;height:100%">
                <h5 style="color:#ef4444;font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-triangle-exclamation me-2"></i>Needs Improvement</h5>
                <p style="color:var(--tx);line-height:1.8;margin:0;">{!! nl2br(e($weaknesses ?: 'No weaknesses were generated for this session.')) !!}</p>
                {{-- <ul class="list-unstyled" style="color:var(--tx);line-height:2;">
                    <li><span class="text-danger me-3" style="font-size:1.2rem;line-height:0;">•</span>Add more real-world examples</li>
                    <li><span class="text-danger me-3" style="font-size:1.2rem;line-height:0;">•</span>Improve confidence in delivery</li>
                    <li><span class="text-danger me-3" style="font-size:1.2rem;line-height:0;">•</span>Use the STAR Method more effectively</li>
                </ul> --}}
            </div>
        </div>
    </div>

    <!-- Feature 4, 12, 13: Skills, Breakdown, and Comparison -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8 animate-fade-up" style="animation-delay: 0.4s;">
            <div class="premium-panel" style="padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:24px;">Skill Performance Summary</h5>
                @php
                    $skills = [
                        ['name' => 'Clarity', 'score' => $sessionRecord->score->clarity_score ?? 0, 'color' => '#3b82f6'],
                        ['name' => 'Relevance', 'score' => $sessionRecord->score->relevance_score ?? 0, 'color' => '#10b981'],
                        ['name' => 'Grammar', 'score' => $sessionRecord->score->grammar_score ?? 0, 'color' => '#8b5cf6'],
                        ['name' => 'Professionalism', 'score' => $sessionRecord->score->professionalism_score ?? 0, 'color' => '#f59e0b'],
                        ['name' => 'Delivery Stability', 'score' => $sessionRecord->score->delivery_stability_score ?? 0, 'color' => '#ef4444'],
                        ['name' => 'Job Evidence Match', 'score' => $sessionRecord->score->job_evidence_match_score ?? 0, 'color' => '#ec4899']
                    ];
                @endphp
                <div class="row g-4">
                    @foreach($skills as $skill)
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color:var(--tx);font-weight:600;">{{ $skill['name'] }}</span>
                            <span style="color:var(--tx)">{{ $skill['score'] }}%</span>
                        </div>
                        <div class="progress" style="height: 10px; background:var(--bd); border-radius:5px;">
                            <div class="progress-bar" role="progressbar" style="width: {{ $skill['score'] }}%; background: {{ $skill['color'] }}; border-radius:5px;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-4 animate-fade-up" style="animation-delay: 0.5s;">
            <div class="premium-panel" style="padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:24px;">Feedback Comparison</h5>
                @if(count($comparisonRows) > 0)
                    <p style="color:var(--tx3);font-size:0.85rem;margin-bottom:16px;">Comparing to your previous completed scored session.</p>
                    <table class="table table-borderless table-sm mb-0" style="color:var(--tx);font-size:0.95rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--bd);color:var(--tx3);">
                                <th>Metric</th>
                                <th class="text-center">Prev</th>
                                <th class="text-center">Cur</th>
                                <th class="text-end">Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comparisonRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-center">{{ $row['previous'] }}%</td>
                                    <td class="text-center fw-bold">{{ $row['current'] }}%</td>
                                    <td class="text-end {{ $row['delta'] > 0 ? 'text-success' : ($row['delta'] < 0 ? 'text-danger' : 'text-muted') }}">
                                        @if($row['delta'] > 0)
                                            <i class="fa-solid fa-arrow-up"></i>
                                        @elseif($row['delta'] < 0)
                                            <i class="fa-solid fa-arrow-down"></i>
                                        @else
                                            <i class="fa-solid fa-minus"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color:var(--tx3);font-size:0.9rem;line-height:1.6;margin:0;">No previous scored session is available for comparison yet.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Question Breakdown -->
    <h4 style="color:var(--tx);font-weight:700;margin-bottom:20px;margin-top:40px;">Detailed Answers Review</h4>
    <div class="accordion" id="answersAccordion">
        @foreach($sessionRecord->answers as $index => $answer)
        <div class="accordion-item premium-panel animate-fade-up" style="margin-bottom:20px;overflow:hidden; animation-delay: {{ 0.5 + ($loop->index * 0.1) }}s; transform: none; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" style="background:transparent;color:var(--tx);box-shadow:none;padding:20px;">
                    <div class="d-flex justify-content-between align-items-center w-100 pe-3 flex-wrap gap-3">
                        <span style="font-size:1.1rem;"><strong>Q{{ $index + 1 }}:</strong> {{ $answer->question->question_text ?? 'Describe a time you faced a difficult challenge.' }}</span>
                        <div class="d-flex gap-2 align-items-center">
                            @if($answer->is_skipped)
                                <span class="badge" style="background:rgba(239, 68, 68, 0.1);color:#ef4444;font-size:0.9rem;padding:8px 12px;">Skipped</span>
                            @else
                                <span class="badge" style="background:rgba(59, 130, 246, 0.1);color:#3b82f6;font-size:0.9rem;padding:8px 12px;">Score: {{ $answer->score ?? 0 }}</span>
                            @endif
                        </div>
                    </div>
                </button>
            </h2>
            <div id="collapse{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#answersAccordion">
                <div class="accordion-body" style="border-top:1px solid var(--bd);padding:24px;">
                    
                    @if($answer->is_skipped)
                        <div class="alert alert-warning border-0" style="background:rgba(245, 158, 11, 0.1);color:#f59e0b;">
                            <i class="fa-solid fa-forward-step me-2"></i> {{ $answer->ai_feedback ?: 'You skipped this question. No feedback available.' }}
                        </div>
                    @else
                        
                        @php
                            $hasDeliveryMetrics = ($answer->wpm ?? 0) > 0
                                || ($answer->voice_duration ?? 0) > 0
                                || ($answer->filler_words_count ?? 0) > 0
                                || ($answer->confidence_score ?? 0) > 0;
                            $hasLegacyBodyLanguageMetrics = ($sessionRecord->score->body_language_included ?? false)
                                && (($answer->eye_contact_score ?? 0) > 0 || ($answer->posture_score ?? 0) > 0);
                        @endphp

                        <!-- Feature 11: Voice Rehearsal Feedback -->
                        @if($hasDeliveryMetrics)
                        <div class="row g-3 mb-4">
                            <div class="col-md-3 col-6">
                                <div class="p-3 text-center" style="background:var(--bg);border-radius:12px;border:1px solid var(--bd);">
                                    <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Speaking Pace</div>
                                    <div style="color:var(--tx);font-weight:bold;font-size:1.1rem;">{{ $answer->wpm ?? 0 }} WPM</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-3 text-center" style="background:var(--bg);border-radius:12px;border:1px solid var(--bd);">
                                    <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Duration</div>
                                    <div style="color:var(--tx);font-weight:bold;font-size:1.1rem;">{{ $answer->voice_duration ?? 0 }}s</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-3 text-center" style="background:var(--bg);border-radius:12px;border:1px solid var(--bd);">
                                    <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Delivery Stability</div>
                                    <div style="color:{{ ($answer->delivery_stability_score ?? 0) >= 80 ? '#10b981' : '#f59e0b' }};font-weight:bold;font-size:1.1rem;">{{ $answer->delivery_stability_score ?? 0 }}%</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-3 text-center" style="background:var(--bg);border-radius:12px;border:1px solid var(--bd);">
                                    <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Filler Words</div>
                                    <div style="color:#ef4444;font-weight:bold;font-size:1.1rem;">{{ $answer->filler_words_count ?? 0 }}</div>
                                </div>
                            </div>
                        </div>

                        @endif

                        {{-- Legacy-only visual estimates are shown for historical transparency, never as readiness evidence. --}}
                        @if($hasLegacyBodyLanguageMetrics)
                        <div class="mb-2" style="color:var(--tx3);font-size:.8rem;">Legacy camera estimates — excluded from the current readiness score.</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 d-flex justify-content-between align-items-center" style="background:rgba(236,72,153,0.05);border-radius:12px;border:1px solid rgba(236,72,153,0.2);">
                                    <div>
                                        <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:2px;"><i class="fa-solid fa-eye me-2" style="color:#ec4899"></i>Eye Contact</div>
                                    </div>
                                    <div style="color:var(--tx);font-weight:bold;font-size:1.1rem;">{{ $answer->eye_contact_score ?? 0 }}%</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 d-flex justify-content-between align-items-center" style="background:rgba(236,72,153,0.05);border-radius:12px;border:1px solid rgba(236,72,153,0.2);">
                                    <div>
                                        <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:2px;"><i class="fa-solid fa-person me-2" style="color:#ec4899"></i>Posture</div>
                                    </div>
                                    <div style="color:var(--tx);font-weight:bold;font-size:1.1rem;">{{ $answer->posture_score ?? 0 }}%</div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(($answer->self_reported_confidence ?? 0) > 0)
                            <div class="mb-4" style="color:var(--tx3);font-size:.88rem;">
                                <i class="fa-regular fa-face-smile me-1"></i>Self-reported preparedness after answering: <strong style="color:var(--tx);">{{ $answer->self_reported_confidence }}/100</strong>. This reflection is kept separate from automated scoring.
                            </div>
                        @endif

                        @php
                            $timeline = is_array($answer->transcript_timeline) ? array_slice($answer->transcript_timeline, -6) : [];
                        @endphp
                        @if(!empty($timeline))
                            <div class="mb-4 p-4" style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;">
                                <h6 style="color:var(--tx);font-weight:bold;margin-bottom:12px;"><i class="fa-solid fa-wave-square me-2"></i>Transcript Timeline</h6>
                                @foreach($timeline as $point)
                                    <div class="timeline-row">
                                        <span>{{ ucfirst(str_replace('_', ' ', $point['event'] ?? 'progress')) }}</span>
                                        <span>{{ $point['at'] ?? 0 }}s / {{ $point['words'] ?? 0 }} words</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mb-4 p-4" style="background:rgba(59, 130, 246, 0.05);border:1px solid rgba(59, 130, 246, 0.2);border-radius:12px;">
                            <h6 style="color:#3b82f6;font-weight:bold;margin-bottom:12px;"><i class="fa-solid fa-comment-medical me-2"></i>AI Feedback</h6>
                            <p style="color:var(--tx);font-size:0.95rem;line-height:1.7;margin:0;">{{ $answer->ai_feedback ?: 'No AI feedback was generated for this answer.' }}</p>
                        </div>

                        @php $evidenceMap = is_array($answer->evidence_map) ? $answer->evidence_map : []; @endphp
                        @if(!empty($evidenceMap) || $answer->rubric_level)
                            <div class="mb-4 p-4" style="background:rgba(16,185,129,.05);border:1px solid rgba(16,185,129,.2);border-radius:12px;">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                                    <h6 style="color:#10b981;font-weight:800;margin:0;"><i class="fa-solid fa-scale-balanced me-2"></i>Why this score</h6>
                                    @if($answer->rubric_level)<span class="retry-chip">{{ $answer->rubric_level }}</span>@endif
                                </div>
                                @if(!empty($evidenceMap['supporting_excerpts']))
                                    <div style="color:var(--tx3);font-size:.8rem;font-weight:800;text-transform:uppercase;">Evidence found</div>
                                    <ul style="color:var(--tx);line-height:1.6;margin-top:8px;">
                                        @foreach($evidenceMap['supporting_excerpts'] as $excerpt)<li>“{{ $excerpt }}”</li>@endforeach
                                    </ul>
                                @endif
                                @if(!empty($evidenceMap['missing_evidence']))
                                    <div style="color:var(--tx3);font-size:.8rem;font-weight:800;text-transform:uppercase;">Evidence to add</div>
                                    <ul style="color:var(--tx);line-height:1.6;margin:8px 0 0;">
                                        @foreach($evidenceMap['missing_evidence'] as $missing)<li>{{ $missing }}</li>@endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif

                        @php
                            $starAnalysis = is_array($answer->star_analysis) ? $answer->star_analysis : [];
                            $starLabels = [
                                'situation' => 'Situation',
                                'task' => 'Task',
                                'action' => 'Action',
                                'result' => 'Result',
                            ];
                        @endphp
                        @if(!empty($starAnalysis))
                            <div class="mb-4 p-4" style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;">
                                <h6 style="color:var(--tx);font-weight:bold;margin-bottom:16px;">STAR Framework Analysis</h6>
                                <div class="d-flex flex-wrap gap-4 align-items-center">
                                    @foreach($starLabels as $key => $label)
                                        @php $present = (bool) ($starAnalysis[$key] ?? false); @endphp
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill {{ $present ? 'bg-success' : 'bg-danger' }}" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;">
                                                <i class="fa-solid {{ $present ? 'fa-check' : 'fa-xmark' }}"></i>
                                            </span>
                                            <span style="color:var(--tx);font-weight:600;">{{ $label }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                @if(!empty($starAnalysis['suggestion']))
                                    <p style="color:var(--tx3);font-size:0.9rem;margin-top:12px;margin-bottom:0;">
                                        <strong class="text-danger">Suggestion:</strong> {{ $starAnalysis['suggestion'] }}
                                    </p>
                                @endif
                            </div>
                        @elseif(($sessionRecord->score->star_method_score ?? 0) > 0)
                            <div class="mb-4 p-4" style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;">
                                <h6 style="color:var(--tx);font-weight:bold;margin-bottom:12px;">STAR Method Score</h6>
                                <p style="color:var(--tx3);font-size:0.9rem;margin:0;">Session-level STAR score: {{ $sessionRecord->score->star_method_score }}%.</p>
                            </div>
                        @endif

                        <!-- Feature 8: Suggested Answer Improvement -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label style="font-size:0.85rem;color:var(--tx3);font-weight:700;text-transform:uppercase;margin-bottom:8px;"><i class="fa-solid fa-user me-2"></i>Original Answer</label>
                                <div style="color:var(--tx);background:rgba(255,255,255,0.03);padding:16px;border-radius:12px;border:1px solid var(--bd);height:100%;font-size:0.95rem;line-height:1.6;">
                                    {{ $answer->answer_text }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label style="font-size:0.85rem;color:#10b981;font-weight:700;text-transform:uppercase;margin-bottom:8px;"><i class="fa-solid fa-shield-halved me-2"></i>Fact-Grounded Revision Template</label>
                                <div style="color:var(--tx);background:rgba(16, 185, 129, 0.05);padding:16px;border-radius:12px;border:1px solid rgba(16, 185, 129, 0.2);height:100%;font-size:0.95rem;line-height:1.6;">
                                    {{ $answer->better_sample_answer ?: 'No improved answer was generated for this response.' }}
                                </div>
                                <div style="color:var(--tx3);font-size:.78rem;margin-top:8px;">Uses your answer as the source. Replace placeholders only with facts you can verify.</div>
                            </div>
                        </div>

                        <!-- Feature 10: Follow-Up Questions -->
                        <div class="mt-4 p-4" style="background:rgba(59, 130, 246, 0.05);border:1px solid rgba(59, 130, 246, 0.2);border-radius:12px;">
                            <label style="font-size:0.9rem;color:#3b82f6;font-weight:700;text-transform:uppercase;margin-bottom:12px;"><i class="fa-solid fa-clipboard-question me-2"></i>Follow-Up Questions to Consider</label>
                            <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:12px;">Think about these questions to encourage deeper practice:</p>
                            <ul class="mb-0" style="color:var(--tx);line-height:1.8;">
                                @if($answer->follow_up_question)
                                    <li>{{ $answer->follow_up_question }}</li>
                                @else
                                    <li>No follow-up question was generated for this answer.</li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    @php
                        $retryAttempts = $answer->retryAttempts ?? collect();
                    @endphp
                    @if($retryAttempts->count() > 0)
                        <div class="mt-4 p-4" style="background:rgba(16,185,129,.05);border:1px solid rgba(16,185,129,.2);border-radius:12px;">
                            <h6 style="color:#10b981;font-weight:800;margin-bottom:12px;"><i class="fa-solid fa-rotate me-2"></i>Retry Attempts</h6>
                            <div class="d-flex flex-column gap-2">
                                @foreach($retryAttempts as $retry)
                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2" style="color:var(--tx);border-bottom:1px solid var(--bd);padding-bottom:10px;">
                                        <div>
                                            <strong>Attempt {{ $retry->attempt_number }}</strong>
                                            <div style="color:var(--tx3);font-size:.85rem;">{{ $retry->created_at?->format('M d, Y g:i A') }}</div>
                                        </div>
                                        <div class="retry-meta">
                                            <span class="retry-chip">Score {{ $retry->score ?? 0 }}%</span>
                                            <span class="retry-chip">Delivery Stability {{ $retry->delivery_stability_score ?? 0 }}%</span>
                                        </div>
                                    </div>
                                    @if($retry->ai_feedback)
                                        <p style="color:var(--tx2);font-size:.9rem;line-height:1.6;margin:0 0 8px;">{{ $retry->ai_feedback }}</p>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mt-4">
                        <button type="button" class="btn btn-outline-primary btn-sm" style="border-radius:999px;font-weight:700;" onclick="toggleRetryPanel({{ $answer->id }})">
                            <i class="fa-solid fa-rotate-right me-1"></i>Retry This Answer
                        </button>
                        <div class="retry-panel" id="retry-panel-{{ $answer->id }}" data-url="{{ route('interview.answer.retry', $answer->id) }}">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                <div>
                                    <strong style="color:var(--tx);">Practice Attempt</strong>
                                    <div style="color:var(--tx3);font-size:.85rem;">This saves as a retry and does not change the original session score.</div>
                                </div>
                                <div class="retry-meta">
                                    <span class="retry-chip" id="retry-timer-{{ $answer->id }}"><i class="fa-regular fa-clock"></i>00:00</span>
                                    <span class="retry-chip" id="retry-words-{{ $answer->id }}">0 words</span>
                                </div>
                            </div>
                            <textarea class="oinp retry-textarea" id="retry-text-{{ $answer->id }}" rows="5" style="font-size:.95rem;" placeholder="Record or type your improved answer here..." onfocus="startRetryTimer({{ $answer->id }})" oninput="updateRetryWordCount({{ $answer->id }})"></textarea>
                            <div class="d-flex flex-column flex-md-row gap-2 mt-3">
                                <button type="button" class="btn btn-outline-secondary" style="border-radius:12px;font-weight:700;" onclick="prefillRetry({{ $answer->id }}, @js($answer->better_sample_answer ?: ''))">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Use Improved Draft
                                </button>
                                <button type="button" class="btn btn-primary" style="border-radius:12px;font-weight:700;" onclick="submitRetry({{ $answer->id }})">
                                    <i class="fa-solid fa-paper-plane me-1"></i>Submit Retry
                                </button>
                            </div>
                            <div class="mt-3" id="retry-result-{{ $answer->id }}" style="display:none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>

<div class="modal fade" id="secureShareModal" tabindex="-1" aria-labelledby="secureShareLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--sf);border:1px solid var(--bd);color:var(--tx);border-radius:18px;">
            <div class="modal-header" style="border-color:var(--bd);">
                <div>
                    <h5 class="modal-title" id="secureShareLabel" style="font-weight:800;"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Secure Review Link</h5>
                    <div style="color:var(--tx3);font-size:.82rem;">Set an expiry, access password, and reviewer permissions.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-bold">Link expires after</label>
                <select class="form-select mb-3" id="shareExpiry" style="background:var(--bg);border-color:var(--bd);color:var(--tx);">
                    <option value="1">1 day</option>
                    <option value="7" selected>7 days</option>
                    <option value="30">30 days</option>
                </select>
                <label class="form-label fw-bold">Password <span style="color:var(--tx3);font-weight:400;">(optional, at least 6 characters)</span></label>
                <input class="form-control mb-3" id="sharePassword" type="password" minlength="6" autocomplete="new-password" placeholder="Leave blank for no password" style="background:var(--bg);border-color:var(--bd);color:var(--tx);">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="shareComments" checked>
                    <label class="form-check-label" for="shareComments">Allow mentor or peer comments</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="shareHideSensitive" checked>
                    <label class="form-check-label" for="shareHideSensitive">Hide identity and sensitive application context</label>
                </div>
                <div class="alert alert-danger mt-3 mb-0" id="shareError" style="display:none;"></div>
            </div>
            <div class="modal-footer" style="border-color:var(--bd);">
                @if($sessionRecord->is_public)
                    <button class="btn btn-outline-danger me-auto" type="button" onclick="saveShare(false)">Disable current link</button>
                @endif
                <button class="btn btn-primary" type="button" id="saveShareButton" onclick="saveShare(true)"><i class="fa-solid fa-link me-1"></i>Create / Update Link</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleShare() {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('secureShareModal')).show();
}

function saveShare(enabled) {
    const errorBox = document.getElementById('shareError');
    const button = document.getElementById('saveShareButton');
    errorBox.style.display = 'none';
    if (enabled) {
        const password = document.getElementById('sharePassword').value;
        if (password && password.length < 6) {
            errorBox.textContent = 'The optional password must contain at least 6 characters.';
            errorBox.style.display = 'block';
            return;
        }
    }
    button.disabled = true;
    fetch('{{ route('interview.toggleShare', $sessionRecord->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            enabled,
            expires_in_days: Number(document.getElementById('shareExpiry').value),
            password: document.getElementById('sharePassword').value || null,
            allow_comments: document.getElementById('shareComments').checked,
            hide_sensitive: document.getElementById('shareHideSensitive').checked
        })
    })
    .then(async res => {
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Unable to update the link.');
        return data;
    })
    .then(data => {
        if (data.success) {
            if (data.is_public) {
                const expiry = data.expires_at ? new Date(data.expires_at).toLocaleString() : 'the selected time';
                if (navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText(data.share_url).then(() => alert(`Secure link copied. It expires ${expiry}.`));
                } else {
                    prompt(`Copy this secure link. It expires ${expiry}:`, data.share_url);
                }
                document.getElementById('btnShareSession').innerHTML = '<i class="fa-solid fa-share-nodes me-2"></i>Shared Link';
            } else {
                alert('Session is now private. The previous link is disabled.');
                document.getElementById('btnShareSession').innerHTML = '<i class="fa-solid fa-share-nodes me-2"></i>Share Session';
            }
            bootstrap.Modal.getInstance(document.getElementById('secureShareModal'))?.hide();
        }
    })
    .catch(error => {
        errorBox.textContent = error.message || 'Unable to update the secure link.';
        errorBox.style.display = 'block';
    })
    .finally(() => { button.disabled = false; });
}

const retryTimers = {};

function retryEscape(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatRetrySeconds(total) {
    const safe = Math.max(0, Math.round(total || 0));
    const m = Math.floor(safe / 60).toString().padStart(2, '0');
    const s = (safe % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

function toggleRetryPanel(answerId) {
    const panel = document.getElementById(`retry-panel-${answerId}`);
    if (!panel) return;
    panel.classList.toggle('active');
    if (panel.classList.contains('active')) {
        document.getElementById(`retry-text-${answerId}`)?.focus();
    }
}

function startRetryTimer(answerId) {
    if (retryTimers[answerId]) return;
    retryTimers[answerId] = {
        startedAt: Date.now(),
        interval: setInterval(() => {
            const elapsed = retryElapsed(answerId);
            const timer = document.getElementById(`retry-timer-${answerId}`);
            if (timer) timer.innerHTML = `<i class="fa-regular fa-clock"></i>${formatRetrySeconds(elapsed)}`;
        }, 1000)
    };
}

function retryElapsed(answerId) {
    if (!retryTimers[answerId]) return 0;
    return Math.max(1, Math.round((Date.now() - retryTimers[answerId].startedAt) / 1000));
}

function updateRetryWordCount(answerId) {
    startRetryTimer(answerId);
    const text = document.getElementById(`retry-text-${answerId}`)?.value || '';
    const words = text.trim().split(/\s+/).filter(Boolean).length;
    const target = document.getElementById(`retry-words-${answerId}`);
    if (target) target.innerText = `${words} words`;
}

function prefillRetry(answerId, draft) {
    const textarea = document.getElementById(`retry-text-${answerId}`);
    if (!textarea || !draft) return;
    textarea.value = draft;
    updateRetryWordCount(answerId);
    textarea.focus();
}

function submitRetry(answerId) {
    const panel = document.getElementById(`retry-panel-${answerId}`);
    const textarea = document.getElementById(`retry-text-${answerId}`);
    const result = document.getElementById(`retry-result-${answerId}`);
    if (!panel || !textarea || !result) return;

    const text = textarea.value.trim();
    if (!text) {
        result.style.display = 'block';
        result.innerHTML = '<div class="alert alert-warning mb-0">Please enter your improved answer first.</div>';
        return;
    }

    const elapsed = retryElapsed(answerId);
    const words = text.split(/\s+/).filter(Boolean).length;
    const wpm = Math.round((words / Math.max(1, elapsed)) * 60);
    const fillers = (text.match(/\b(um|uh|like|you know|basically|i mean|sort of|kind of)\b/gi) || []).length;
    const confidence = Math.max(0, Math.min(100, 92 - (fillers * 3) - (wpm < 90 || wpm > 190 ? 10 : 0)));

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('answer_text', text);
    formData.append('response_mode', 'text');
    formData.append('elapsed_seconds', elapsed);
    formData.append('voice_duration', elapsed);
    formData.append('wpm', wpm);
    formData.append('filler_words_count', fillers);
    formData.append('pause_count', 0);
    formData.append('confidence_score', confidence);
    formData.append('eye_contact_score', 0);
    formData.append('posture_score', 0);
    formData.append('transcript_timeline', JSON.stringify([
        { at: 0, event: 'retry_started', words: 0, chars: 0 },
        { at: elapsed, event: 'retry_submitted', words, chars: text.length }
    ]));

    result.style.display = 'block';
    result.innerHTML = '<div class="alert alert-info mb-0"><i class="fa-solid fa-circle-notch fa-spin me-1"></i>Scoring retry...</div>';

    fetch(panel.dataset.url, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) throw new Error(data.error || 'Retry failed');
        result.innerHTML = `
            <div class="p-3" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);border-radius:12px;color:var(--tx);">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="retry-chip">Attempt ${retryEscape(data.attempt_number)}</span>
                    <span class="retry-chip">Score ${retryEscape(data.score)}%</span>
                    <span class="retry-chip">Delivery Stability ${retryEscape(data.delivery_stability_score ?? 0)}%</span>
                </div>
                <p style="margin:0;color:var(--tx2);line-height:1.6;">${retryEscape(data.ai_feedback)}</p>
            </div>
        `;
    })
    .catch(error => {
        result.innerHTML = `<div class="alert alert-danger mb-0">${retryEscape(error.message || 'Retry failed.')}</div>`;
    });
}
</script>
@endsection


