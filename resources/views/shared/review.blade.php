@extends('layouts.public-review')
@section('title', 'Interview Results')

@section('content')
<div class="db-section active">
    @php
        $feedback = $sessionRecord->feedback;
        $strengths = trim($feedback->strengths ?? '');
        $weaknesses = trim($feedback->weaknesses ?? '');
        $suggestions = trim($feedback->improvement_suggestions ?? '');
        $feedbackSummaryParts = array_filter([
            $strengths !== '' ? 'Strengths: '.$strengths : null,
            $weaknesses !== '' ? 'Areas to improve: '.$weaknesses : null,
        ]);
        $feedbackSummary = ! empty($feedbackSummaryParts)
            ? implode("\n\n", $feedbackSummaryParts)
            : ($suggestions !== '' ? 'Review the recommended actions for the next practice step.' : 'AI feedback was unavailable for this session.');
        $comparisonRows = $comparisonRows ?? [];
        $mentorComments = $sessionRecord->mentorReviewComments ?? collect();
        $sessionFeedbackQuality = is_array(data_get($feedback->coaching_summary ?? [], 'feedback_quality'))
            ? data_get($feedback->coaching_summary, 'feedback_quality')
            : [];
        $sessionFeedbackQualityPercent = is_numeric($sessionFeedbackQuality['completeness_percent'] ?? null)
            ? max(0, min(100, (int) round($sessionFeedbackQuality['completeness_percent'])))
            : null;
    @endphp
    @if(session('success'))
        <div class="alert alert-success" style="border-radius:12px;">{{ session('success') }}</div>
    @endif
    <!-- Feature 2 & 15: Header, Report Info, Export -->
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h4 style="color:var(--tx);font-weight:700">Interview Results: {{ $sessionRecord->share_hide_sensitive ? 'Candidate' : ($sessionRecord->user->name ?? 'Candidate') }}</h4>
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
                    @if($sessionFeedbackQualityPercent !== null)
                        <div title="{{ $sessionFeedbackQuality['limitation'] ?? '' }}" style="font-size:.72rem;color:{{ $sessionFeedbackQualityPercent === 100 ? '#10b981' : '#f59e0b' }};margin-top:3px;">
                            <i class="fa-solid fa-shield-halved me-1"></i>Feedback checks {{ $sessionFeedbackQualityPercent }}%
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="alert mb-4" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);color:var(--tx);border-radius:14px;">
        <i class="fa-solid fa-shield-halved me-2 text-primary"></i>
        Private review link{{ $sessionRecord->share_expires_at ? ' · expires '.$sessionRecord->share_expires_at->format('M d, Y g:i A') : '' }}.
        @if(!$sessionRecord->score_eligible) This is coached-practice feedback and is not readiness evidence. @endif
    </div>

    <!-- Feature 7 & 14: AI Personalized Feedback & Recommendations -->
    <div class="row mb-4">
        <div class="col-12">
            <div style="background: linear-gradient(145deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1)); border:1px solid rgba(59, 130, 246, 0.2); border-radius:18px; padding:32px;">
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

    @include('partials.interview-coaching-summary', ['feedback' => $feedback, 'sessionRecord' => $sessionRecord])

    <!-- Feature 5 & 6: Strengths and Areas for Improvement -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div style="background:rgba(16, 185, 129, 0.05);border:1px solid rgba(16, 185, 129, 0.2);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:#10b981;font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-thumbs-up me-2"></i>Strengths</h5>
                <p style="color:var(--tx);line-height:1.8;margin:0;">{!! nl2br(e($strengths ?: 'No strengths were generated for this session.')) !!}</p>
            </div>
        </div>
        <div class="col-md-6">
            <div style="background:rgba(239, 68, 68, 0.05);border:1px solid rgba(239, 68, 68, 0.2);border-radius:18px;padding:24px;height:100%">
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
        <div class="col-lg-8">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:24px;">Skill Performance Summary</h5>
                @php
                    $skills = [
                        ['name' => 'Clarity', 'score' => $sessionRecord->score->clarity_score ?? 0, 'color' => '#3b82f6'],
                        ['name' => 'Relevance', 'score' => $sessionRecord->score->relevance_score ?? 0, 'color' => '#10b981'],
                        ['name' => 'Grammar', 'score' => $sessionRecord->score->grammar_score ?? 0, 'color' => '#8b5cf6'],
                        ['name' => 'Professionalism', 'score' => $sessionRecord->score->professionalism_score ?? 0, 'color' => '#f59e0b'],
                        ['name' => 'Job Evidence Match', 'score' => $sessionRecord->score->job_evidence_match_score ?? 0, 'color' => '#ec4899']
                    ];
                    $deliveryMeasured = (int) data_get($feedback->coaching_summary ?? [], 'coverage.delivery_measured', 0) > 0
                        || $sessionRecord->answers->contains(fn ($item) => data_get($item->coaching_feedback ?? [], 'delivery.status') === 'measured');
                    if ($deliveryMeasured) {
                        $skills[] = ['name' => 'Delivery Stability', 'score' => $sessionRecord->score->delivery_stability_score ?? 0, 'color' => '#ef4444'];
                    }
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
        <div class="col-lg-4">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:24px;">Feedback Comparison</h5>
                @if(count($comparisonRows) > 0)
                    <p style="color:var(--tx3);font-size:0.85rem;margin-bottom:16px;">Comparing to the previous completed scored session.</p>
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
                    <p style="color:var(--tx3);font-size:0.9rem;line-height:1.6;margin:0;">No previous scored session is available for comparison.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:16px;"><i class="fa-solid fa-user-pen me-2 text-primary"></i>Mentor Feedback</h5>
                @forelse($mentorComments as $comment)
                    <div style="border-bottom:1px solid var(--bd);padding:12px 0;">
                        <div class="d-flex justify-content-between gap-3">
                            <strong style="color:var(--tx);">{{ $comment->reviewer_name }}</strong>
                            @if($comment->rating)
                                <span style="color:#f59e0b;font-weight:800;">{{ $comment->rating }}/5</span>
                            @endif
                        </div>
                        <p style="color:var(--tx2);font-size:.9rem;line-height:1.6;margin:6px 0 0;">{{ $comment->comment }}</p>
                    </div>
                @empty
                    <p style="color:var(--tx3);margin:0;">No mentor feedback has been submitted yet.</p>
                @endforelse
            </div>
        </div>
        @if($sessionRecord->share_token && data_get($sessionRecord->share_permissions, 'comment', true))
        <div class="col-lg-5">
            <form action="{{ route('shared.mentor-comments.store', $sessionRecord->share_token) }}" method="POST" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                @csrf
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:16px;"><i class="fa-solid fa-comment-dots me-2 text-success"></i>Leave Review</h5>
                <div class="mb-3">
                    <label style="color:var(--tx);font-size:.82rem;font-weight:700;margin-bottom:6px;">Name</label>
                    <input name="reviewer_name" class="form-control" style="background:var(--bg3);border-color:var(--bd);color:var(--tx);border-radius:10px;" required>
                </div>
                <div class="mb-3">
                    <label style="color:var(--tx);font-size:.82rem;font-weight:700;margin-bottom:6px;">Email</label>
                    <input type="email" name="reviewer_email" class="form-control" style="background:var(--bg3);border-color:var(--bd);color:var(--tx);border-radius:10px;">
                </div>
                <div class="mb-3">
                    <label style="color:var(--tx);font-size:.82rem;font-weight:700;margin-bottom:6px;">Rating</label>
                    <select name="rating" class="form-control" style="background:var(--bg3);border-color:var(--bd);color:var(--tx);border-radius:10px;">
                        <option value="">No rating</option>
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} / 5</option>
                        @endfor
                    </select>
                </div>
                <div class="mb-3">
                    <label style="color:var(--tx);font-size:.82rem;font-weight:700;margin-bottom:6px;">Comment</label>
                    <textarea name="comment" rows="4" class="form-control" style="background:var(--bg3);border-color:var(--bd);color:var(--tx);border-radius:10px;" required></textarea>
                </div>
                <button class="btn btn-success w-100" style="border-radius:10px;font-weight:800;">Submit Mentor Feedback</button>
            </form>
        </div>
        @endif
    </div>

    <!-- Question Breakdown -->
    <h4 style="color:var(--tx);font-weight:700;margin-bottom:20px;margin-top:40px;">Detailed Answers Review</h4>
    <div class="accordion" id="answersAccordion">
        @foreach($sessionRecord->answers as $index => $answer)
        @php
            $headerAlignmentStatus = trim((string) data_get($answer->coaching_feedback ?? [], 'content_alignment.status', ''));
            $headerAlignmentLabel = trim((string) data_get($answer->coaching_feedback ?? [], 'content_alignment.status_label', ''));
            $headerAlignmentLabel = $headerAlignmentLabel !== '' ? $headerAlignmentLabel : match ($headerAlignmentStatus) {
                'directly_answered' => 'Directly answered',
                'partially_answered' => 'Partially answered',
                'low_relevance' => 'Low relevance',
                'insufficient_evidence' => 'Not enough evidence',
                'not_evaluated' => 'Not evaluated',
                'skipped' => 'Skipped',
                default => '',
            };
            $headerAlignmentColor = match ($headerAlignmentStatus) {
                'directly_answered' => '#10b981',
                'partially_answered', 'insufficient_evidence' => '#f59e0b',
                'low_relevance' => '#ef4444',
                default => '#64748b',
            };
            $headerHasEvaluatedScore = in_array($headerAlignmentStatus, ['directly_answered', 'partially_answered', 'low_relevance'], true);
        @endphp
        <div class="accordion-item" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;margin-bottom:20px;overflow:hidden;box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" style="background:transparent;color:var(--tx);box-shadow:none;padding:20px;">
                    <div class="d-flex justify-content-between align-items-center w-100 pe-3 flex-wrap gap-3">
                        <span style="font-size:1.1rem;"><strong>Q{{ $index + 1 }}:</strong> {{ $answer->question->question_text ?? 'Describe a time you faced a difficult challenge.' }}</span>
                        <div class="d-flex gap-2 align-items-center">
                            @if($answer->is_skipped)
                                <span class="badge" style="background:rgba(239, 68, 68, 0.1);color:#ef4444;font-size:0.9rem;padding:8px 12px;">Skipped</span>
                            @elseif($headerAlignmentStatus !== '')
                                <span class="badge" style="background:color-mix(in srgb, {{ $headerAlignmentColor }} 12%, transparent);color:{{ $headerAlignmentColor }};border:1px solid color-mix(in srgb, {{ $headerAlignmentColor }} 28%, transparent);font-size:.82rem;padding:8px 12px;">{{ $headerAlignmentLabel }}</span>
                                @if($headerHasEvaluatedScore)
                                    <span class="badge" style="background:rgba(59, 130, 246, 0.1);color:#3b82f6;font-size:0.9rem;padding:8px 12px;">Score: {{ $answer->score ?? 0 }}</span>
                                @endif
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
                        @include('partials.interview-answer-coaching', ['answer' => $answer])
                    @else
                        
                        @php
                            $hasStructuredAnswerCoaching = is_array($answer->coaching_feedback ?? null)
                                && ! empty($answer->coaching_feedback);
                            $responseMode = strtolower((string) ($answer->response_mode ?? ''));
                            $hasVoiceRecording = in_array($responseMode, ['voice', 'hybrid', 'voice_and_text'], true)
                                || ($responseMode === '' && ($answer->voice_duration ?? 0) > 0);
                            $hasDeliveryMetrics = ! $hasStructuredAnswerCoaching
                                && $hasVoiceRecording
                                && ($answer->voice_duration ?? 0) > 0
                                && ($answer->wpm ?? 0) > 0;
                            $hasLegacyBodyLanguageMetrics = ! $hasStructuredAnswerCoaching
                                && ($sessionRecord->score->body_language_included ?? false)
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

                        @if($hasLegacyBodyLanguageMetrics)
                        <div class="mb-2" style="color:var(--tx3);font-size:.8rem;">Legacy camera estimates — excluded from current readiness scoring.</div>
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

                        @php
                            $integrityFlags = is_array($answer->answer_integrity_flags) ? $answer->answer_integrity_flags : [];
                            $integritySignals = array_values(array_filter((array) ($integrityFlags['signals'] ?? [])));
                            $pasteEventCount = (int) ($answer->paste_event_count ?? 0);
                            $pastedCharacterCount = (int) ($answer->pasted_character_count ?? 0);
                            $aiGeneratedLikelihood = (int) ($answer->ai_generated_likelihood ?? 0);
                            $hasIntegritySignals = $pasteEventCount > 0 || $pastedCharacterCount > 0 || $aiGeneratedLikelihood >= 50 || ! empty($integritySignals);
                        @endphp
                        @if($hasIntegritySignals)
                            <div class="mb-4 p-4" style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.28);border-radius:12px;">
                                <h6 style="color:#f59e0b;font-weight:bold;margin-bottom:10px;"><i class="fa-solid fa-shield-halved me-2"></i>Answer Integrity Signals</h6>
                                <p style="color:var(--tx3);font-size:.86rem;line-height:1.55;margin-bottom:12px;">These are review signals, not proof of misconduct. Use them to decide whether the answer needs closer human review.</p>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4"><div class="p-2" style="background:var(--bg);border:1px solid var(--bd);border-radius:10px;color:var(--tx);font-size:.86rem;">Paste events: <strong>{{ $pasteEventCount }}</strong></div></div>
                                    <div class="col-md-4"><div class="p-2" style="background:var(--bg);border:1px solid var(--bd);border-radius:10px;color:var(--tx);font-size:.86rem;">Pasted chars: <strong>{{ $pastedCharacterCount }}</strong></div></div>
                                    <div class="col-md-4"><div class="p-2" style="background:var(--bg);border:1px solid var(--bd);border-radius:10px;color:var(--tx);font-size:.86rem;">AI-template likelihood: <strong>{{ $aiGeneratedLikelihood }}%</strong></div></div>
                                </div>
                                @if(!empty($integritySignals))
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        @foreach($integritySignals as $signal)
                                            <span class="badge" style="background:rgba(245,158,11,.16);color:#f59e0b;border:1px solid rgba(245,158,11,.28);">{{ str_replace('_', ' ', $signal) }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        @include('partials.interview-answer-coaching', ['answer' => $answer])

                        <div class="mb-4 p-4" style="background:rgba(59, 130, 246, 0.05);border:1px solid rgba(59, 130, 246, 0.2);border-radius:12px;">
                            <h6 style="color:#3b82f6;font-weight:bold;margin-bottom:12px;"><i class="fa-solid fa-comment-medical me-2"></i>AI Feedback</h6>
                            <p style="color:var(--tx);font-size:0.95rem;line-height:1.7;margin:0;">{{ $answer->ai_feedback ?: 'No AI feedback was generated for this answer.' }}</p>
                        </div>

                        @php $evidenceMap = is_array($answer->evidence_map) ? $answer->evidence_map : []; @endphp
                        @if(!empty($evidenceMap) || $answer->rubric_level)
                            <div class="mb-4 p-4" style="background:rgba(16,185,129,.05);border:1px solid rgba(16,185,129,.2);border-radius:12px;">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                                    <h6 style="color:#10b981;font-weight:800;margin:0;"><i class="fa-solid fa-scale-balanced me-2"></i>Why this score</h6>
                                    @if($answer->rubric_level)<span class="badge bg-success">{{ $answer->rubric_level }}</span>@endif
                                </div>
                                @if(!empty($evidenceMap['supporting_excerpts']))
                                    <strong style="color:var(--tx);font-size:.85rem;">Supporting evidence</strong>
                                    <ul style="color:var(--tx);line-height:1.6;margin-top:8px;">
                                        @foreach($evidenceMap['supporting_excerpts'] as $excerpt)<li>“{{ $excerpt }}”</li>@endforeach
                                    </ul>
                                @endif
                                @if(!empty($evidenceMap['missing_evidence']))
                                    <strong style="color:var(--tx);font-size:.85rem;">Evidence to add</strong>
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
                                <div style="color:var(--tx3);font-size:.78rem;margin-top:8px;">Built only from the candidate's supplied answer; placeholders require verified facts.</div>
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
                                        <div class="retry-meta d-flex gap-2 flex-wrap align-items-center">
                                            <span class="retry-chip" style="display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;background:rgba(59,130,246,.12);color:#3b82f6;font-size:.78rem;font-weight:700;">Score {{ $retry->score ?? 0 }}%</span>
                                            @if(in_array(strtolower((string) $retry->response_mode), ['voice', 'hybrid', 'voice_and_text'], true) && ($retry->voice_duration ?? 0) > 0 && $retry->delivery_stability_score !== null)
                                                <span class="retry-chip" style="display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;background:rgba(59,130,246,.12);color:#3b82f6;font-size:.78rem;font-weight:700;">Delivery Stability {{ $retry->delivery_stability_score }}%</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($retry->ai_feedback)
                                        <p style="color:var(--tx2);font-size:.9rem;line-height:1.6;margin:0 0 8px;">{{ $retry->ai_feedback }}</p>
                                    @endif
                                    @include('partials.interview-answer-coaching', ['answer' => $retry])
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection

