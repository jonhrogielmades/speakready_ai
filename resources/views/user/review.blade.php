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
</style>

<div class="db-section active animate-fade-up">
    @php
        $feedback = $sessionRecord->feedback;
        $strengths = trim($feedback->strengths ?? '');
        $weaknesses = trim($feedback->weaknesses ?? '');
        $suggestions = trim($feedback->improvement_suggestions ?? '');
        $feedbackSummary = $suggestions !== '' ? $suggestions : ($weaknesses !== '' ? $weaknesses : ($strengths !== '' ? $strengths : 'AI feedback was unavailable for this session.'));
        $comparisonRows = $comparisonRows ?? [];
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
                    if($overall >= 90) { $rating = 'Excellent'; $color = '#10b981'; }
                    elseif($overall >= 70) { $rating = 'Good'; $color = '#3b82f6'; }
                    elseif($overall >= 50) { $rating = 'Fair'; $color = '#f59e0b'; }
                    else { $rating = 'Needs Improvement'; $color = '#ef4444'; }
                @endphp
                <div class="d-flex align-items-center gap-2 d-md-block">
                    <div style="font-size:2.5rem;font-weight:800;color:{{ $color }};line-height:1">{{ $overall }}<span style="font-size:1.2rem;color:var(--tx3)">%</span></div>
                    <div style="font-size:0.9rem;font-weight:600;color:{{ $color }}">{{ $rating }}</div>
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
                    <li><a class="dropdown-item" href="#" style="color:var(--tx)" onclick="window.print()"><i class="fa-solid fa-file-pdf text-danger me-2"></i> PDF Format</a></li>
                    <li><a class="dropdown-item" href="#" style="color:var(--tx)"><i class="fa-solid fa-file-excel text-success me-2"></i> Excel Format</a></li>
                </ul>
            </div>
        </div>
    </div>

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
                        ['name' => 'Confidence', 'score' => $sessionRecord->score->confidence_score ?? 0, 'color' => '#ef4444'],
                        ['name' => 'Body Language', 'score' => $sessionRecord->score->body_language_score ?? 0, 'color' => '#ec4899']
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
                            $hasBodyLanguageMetrics = ($answer->eye_contact_score ?? 0) > 0
                                || ($answer->posture_score ?? 0) > 0;
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
                                    <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:4px;">Confidence</div>
                                    <div style="color:{{ ($answer->confidence_score ?? 0) >= 80 ? '#10b981' : '#f59e0b' }};font-weight:bold;font-size:1.1rem;">{{ $answer->confidence_score ?? 0 }}%</div>
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

                        <!-- Feature 16: Body Language Breakdown -->
                        @if($hasBodyLanguageMetrics)
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

                        <div class="mb-4 p-4" style="background:rgba(59, 130, 246, 0.05);border:1px solid rgba(59, 130, 246, 0.2);border-radius:12px;">
                            <h6 style="color:#3b82f6;font-weight:bold;margin-bottom:12px;"><i class="fa-solid fa-comment-medical me-2"></i>AI Feedback</h6>
                            <p style="color:var(--tx);font-size:0.95rem;line-height:1.7;margin:0;">{{ $answer->ai_feedback ?: 'No AI feedback was generated for this answer.' }}</p>
                        </div>

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
                                <label style="font-size:0.85rem;color:#10b981;font-weight:700;text-transform:uppercase;margin-bottom:8px;"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Improved Answer</label>
                                <div style="color:var(--tx);background:rgba(16, 185, 129, 0.05);padding:16px;border-radius:12px;border:1px solid rgba(16, 185, 129, 0.2);height:100%;font-size:0.95rem;line-height:1.6;">
                                    {{ $answer->better_sample_answer ?: 'No improved answer was generated for this response.' }}
                                </div>
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
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>

<script>
function toggleShare() {
    fetch('{{ route('interview.toggleShare', $sessionRecord->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (data.is_public) {
                prompt('Your session is now public! Copy this link to share:', data.share_url);
                document.getElementById('btnShareSession').innerHTML = '<i class="fa-solid fa-share-nodes me-2"></i>Shared Link';
            } else {
                alert('Session is now private. The previous link is disabled.');
                document.getElementById('btnShareSession').innerHTML = '<i class="fa-solid fa-share-nodes me-2"></i>Share Session';
            }
        }
    });
}
</script>
@endsection


