@extends('mobile.layouts.public-review')
@section('title', 'Interview Results')

@section('content')
<div class="db-section active">
 @php
 $feedback = $sessionRecord->feedback;
 $report = \App\Support\FeedbackReportPresenter::forSession($sessionRecord);
 $strengthItems = $report['strength_items'];
 $weaknessItems = $report['weakness_items'];
 $comparisonRows = $comparisonRows?? [];
 $mentorComments = $sessionRecord->mentorReviewComments?? collect();
 @endphp
 @if(session('success'))
 <div class="alert alert-success" style="border-radius:12px;">{{ session('success') }}</div>
 @endif
 <!-- Feature 2 & 15: Header, Report Info, Export -->
 <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
 <div>
 <h4 style="color:var(--tx);font-weight:700">Interview Results: {{ $sessionRecord->share_hide_sensitive? 'Candidate': ($sessionRecord->user->name?? 'Candidate') }}</h4>
 <div class="d-flex gap-3 mt-2 feedback-report-meta" style="font-size:0.9rem;color:var(--tx3)">
 <span><i class="fa-regular fa-calendar me-1"></i> {{ $sessionRecord->created_at->format('M d, Y') }}</span>
 <span><i class="fa-solid fa-layer-group me-1"></i> {{ $sessionRecord->category->title?? 'Job Interview' }}</span>
 <span><i class="fa-solid fa-signal me-1"></i> {{ ucfirst($sessionRecord->difficulty?? 'Intermediate') }}</span>
 <span><i class="fa-regular fa-clock me-1"></i> {{ floor(($sessionRecord->duration_seconds?? 0) / 60) }}m {{ ($sessionRecord->duration_seconds?? 0) % 60 }}s</span>
 </div>
 </div>
 <div class="text-md-end d-flex gap-4 align-items-center flex-wrap mt-3 mt-md-0 feedback-report-score-actions">
 <!-- Feature 3: Overall Performance Score & Rating -->
 <div class="text-start feedback-report-score">
 @php 
 $overall = $sessionRecord->score->overall_readiness_score?? 0;
 $rating = $sessionRecord->score->readiness_band?: ($overall >= 80? 'Ready for Simulation': ($overall >= 60? 'Nearly Ready': 'Developing'));
 $color = $overall >= 80? '#10b981': ($overall >= 60? '#3b82f6': '#f59e0b');
 @endphp
 <div class="d-flex align-items-center gap-2 d-md-block">
 <div style="font-size:2.5rem;font-weight:800;color:{{ $color }};line-height:1">{{ $overall }}<span style="font-size:1.2rem;color:var(--tx3)">%</span></div>
 <div style="font-size:0.9rem;font-weight:600;color:{{ $color }}">{{ $rating }}</div>
 </div>
 </div>
 </div>
 </div>

 <div class="alert mb-4" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);color:var(--tx);border-radius:14px;">
 <i class="fa-solid fa-shield-halved me-2 text-primary"></i>
 Private review link{{ $sessionRecord->share_expires_at? ' - ends '.$sessionRecord->share_expires_at->format('M d, Y g:i A'): '' }}.
 @if(!$sessionRecord->score_eligible) This is coached-practice feedback and is not used for the readiness score. @endif
 </div>

 @include('shared.partials.report-overview', ['report' => $report])

 @include('mobile.partials.interview-coaching-summary', ['feedback' => $feedback, 'sessionRecord' => $sessionRecord])

 <!-- Feature 5 & 6: Strengths and Areas for Improvement -->
 <div class="row g-4 mb-4">
 <div class="col-md-6">
 <div style="background:rgba(16, 185, 129, 0.05);border:1px solid rgba(16, 185, 129, 0.2);border-radius:18px;padding:24px;height:100%">
 <h5 style="color:#10b981;font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-thumbs-up me-2"></i>What You Did Well</h5>
 @include('shared.partials.report-bullet-list', ['items' => $strengthItems, 'icon' => 'fa-circle-check', 'color' => '#10b981'])
 </div>
 </div>
 <div class="col-md-6">
 <div style="background:rgba(245, 158, 11, 0.06);border:1px solid rgba(245, 158, 11, 0.22);border-radius:18px;padding:24px;height:100%">
 <h5 style="color:#b45309;font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-bullseye me-2"></i>Focus Areas</h5>
 @include('shared.partials.report-bullet-list', ['items' => $weaknessItems, 'icon' => 'fa-arrow-trend-up', 'color' => '#f59e0b'])
 </div>
 </div>
 </div>

 @include('shared.partials.report-conciseness-check', ['report' => $report])

 <!-- Feature 4, 12, 13: Skills, Breakdown, and Comparison -->
 <div class="row g-4 mb-4">
 <div class="col-lg-8">
 <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
 <h5 style="color:var(--tx);font-weight:bold;margin-bottom:24px;">Category Breakdown</h5>
 @php
 $skills = $report['category_breakdown']?? [];
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
 <h5 style="color:var(--tx);font-weight:bold;margin-bottom:24px;">Progress Since Last Score</h5>
 @if(count($comparisonRows) > 0)
 <p style="color:var(--tx3);font-size:0.85rem;margin-bottom:16px;">Compared with the last completed scored session.</p>
 <div class="feedback-comparison-table-wrap">
 <table class="table table-borderless table-sm mb-0 feedback-comparison-table" style="color:var(--tx);font-size:0.95rem;">
 <thead>
 <tr style="border-bottom: 1px solid var(--bd);color:var(--tx3);">
 <th>Skill</th>
 <th class="text-center">Last</th>
 <th class="text-center">Now</th>
 <th class="text-end">Change</th>
 </tr>
 </thead>
 <tbody>
 @foreach($comparisonRows as $row)
 <tr>
 <td>{{ $row['label'] }}</td>
 <td class="text-center">{{ $row['previous'] }}%</td>
 <td class="text-center fw-bold">{{ $row['current'] }}%</td>
 <td class="text-end {{ $row['delta'] > 0? 'text-success': ($row['delta'] < 0? 'text-danger': 'text-muted') }}">
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
 </div>
 @else
 <p style="color:var(--tx3);font-size:0.9rem;line-height:1.6;margin:0;">No earlier scored session yet.</p>
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

 <!-- Answer Breakdown -->
 <h4 class="answer-review-heading" style="color:var(--tx);font-weight:700;margin-bottom:20px;margin-top:40px;">Answer Review</h4>
 <div class="accordion" id="answersAccordion">
 @foreach($sessionRecord->answers as $index => $answer)
 @php
 $headerAlignmentStatus = trim((string) data_get($answer->coaching_feedback?? [], 'content_alignment.status', ''));
 $headerAlignmentLabel = trim((string) data_get($answer->coaching_feedback?? [], 'content_alignment.status_label', ''));
 $headerAlignmentLabel = $headerAlignmentLabel!== ''? $headerAlignmentLabel: match ($headerAlignmentStatus) {
 'directly_answered' => 'Answered directly',
 'partially_answered' => 'Answered partly',
 'low_relevance' => 'Low match',
 'insufficient_evidence' => 'Not enough detail',
 'not_evaluated' => 'Not checked',
 'skipped' => 'Skipped',
 default => '',
 };
 $headerAlignmentLabel = match (strtolower($headerAlignmentLabel)) {
 'directly answered' => 'Answered directly',
 'partially answered' => 'Answered partly',
 'low relevance' => 'Low match',
 'not enough evidence' => 'Not enough detail',
 'not evaluated' => 'Not checked',
 default => $headerAlignmentLabel,
 };
 $headerAlignmentColor = match ($headerAlignmentStatus) {
 'directly_answered' => '#10b981',
 'partially_answered', 'insufficient_evidence' => '#f59e0b',
 'low_relevance' => '#f59e0b',
 default => '#64748b',
 };
 $headerHasEvaluatedScore = in_array($headerAlignmentStatus, ['directly_answered', 'partially_answered', 'low_relevance'], true);
 @endphp
 <div class="accordion-item answer-review-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;margin-bottom:20px;overflow:hidden;box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
 <h2 class="accordion-header">
 <button class="accordion-button collapsed answer-review-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" style="background:transparent;color:var(--tx);box-shadow:none;padding:20px;">
 <div class="d-flex justify-content-between align-items-center w-100 pe-3 flex-wrap gap-3 answer-review-header">
 <span class="answer-review-title" style="font-size:1.1rem;"><strong>Answer {{ $index + 1 }}</strong></span>
 <div class="d-flex gap-2 align-items-center answer-review-score">
 @if($answer->is_skipped)
 <span class="badge" style="background:rgba(245, 158, 11, 0.12);color:#b45309;font-size:0.9rem;padding:8px 12px;">Skipped</span>
 @elseif($headerAlignmentStatus!== '')
 <span class="badge" style="background:color-mix(in srgb, {{ $headerAlignmentColor }} 12%, transparent);color:{{ $headerAlignmentColor }};border:1px solid color-mix(in srgb, {{ $headerAlignmentColor }} 28%, transparent);font-size:.82rem;padding:8px 12px;">{{ $headerAlignmentLabel }}</span>
 @if($headerHasEvaluatedScore)
 <span class="badge" style="background:rgba(59, 130, 246, 0.1);color:#3b82f6;font-size:0.9rem;padding:8px 12px;">Score: {{ $answer->score?? 0 }}</span>
 @endif
 @else
 <span class="badge" style="background:rgba(59, 130, 246, 0.1);color:#3b82f6;font-size:0.9rem;padding:8px 12px;">Score: {{ $answer->score?? 0 }}</span>
 @endif
 </div>
 </div>
 </button>
 </h2>
 <div id="collapse{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#answersAccordion">
 <div class="accordion-body answer-review-body" style="border-top:1px solid var(--bd);padding:24px;">
 @php
 $canPlayStoredVoiceAnswer = auth()->check()
 && (int) ($sessionRecord->user_id?? 0) === (int) auth()->id()
 && trim((string) ($answer->voice_recording_path?? ''))!== '';
 @endphp
 @if($canPlayStoredVoiceAnswer)
 @include('shared.partials.interview-answer-voice-recording', ['answer' => $answer])
 @endif
 
 @if($answer->is_skipped)
 <div class="alert alert-warning border-0" style="background:rgba(245, 158, 11, 0.1);color:#f59e0b;">
 <i class="fa-solid fa-forward-step me-2"></i> {{ review_feedback_without_question_text($answer->ai_feedback ?: 'You skipped this prompt. No feedback available.', $answer->question ?? $answer) }}
 </div>
 @include('mobile.partials.interview-answer-coaching', ['answer' => $answer])
 @else
 @include('mobile.partials.interview-answer-coaching', ['answer' => $answer])

 <div class="mb-4 p-4" style="background:rgba(59, 130, 246, 0.05);border:1px solid rgba(59, 130, 246, 0.2);border-radius:12px;">
 <h6 style="color:#3b82f6;font-weight:bold;margin-bottom:12px;"><i class="fa-solid fa-comment-medical me-2"></i>Feedback</h6>
 <p style="color:var(--tx);font-size:0.95rem;line-height:1.7;margin:0;">{{ review_feedback_without_question_text($answer->ai_feedback ?: 'No feedback was generated for this answer.', $answer->question ?? $answer) }}</p>
 </div>

 @php $evidenceMap = is_array($answer->evidence_map)? $answer->evidence_map: []; @endphp
 @if(!empty($evidenceMap) || $answer->rubric_level)
 <div class="mb-4 p-4" style="background:rgba(16,185,129,.05);border:1px solid rgba(16,185,129,.2);border-radius:12px;">
 <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
 <h6 style="color:#10b981;font-weight:800;margin:0;"><i class="fa-solid fa-scale-balanced me-2"></i>Why this score</h6>
 @if($answer->rubric_level)<span class="badge bg-success">{{ $answer->rubric_level }}</span>@endif
 </div>
 @if(!empty($evidenceMap['supporting_excerpts']))
 <strong style="color:var(--tx);font-size:.85rem;">Proof found</strong>
 <ul style="color:var(--tx);line-height:1.6;margin-top:8px;">
 @foreach($evidenceMap['supporting_excerpts'] as $excerpt)<li>{{ $excerpt }}</li>@endforeach
 </ul>
 @endif
 @if(!empty($evidenceMap['missing_evidence']))
 <strong style="color:var(--tx);font-size:.85rem;">Details to add</strong>
 <ul style="color:var(--tx);line-height:1.6;margin:8px 0 0;">
 @foreach($evidenceMap['missing_evidence'] as $missing)<li>{{ $missing }}</li>@endforeach
 </ul>
 @endif
 </div>
 @endif

 @php
 $starAnalysis = is_array($answer->star_analysis)? $answer->star_analysis: [];
 $starLabels = [
 'situation' => 'Situation',
 'task' => 'Task',
 'action' => 'Action',
 'result' => 'Result',
 ];
 @endphp
 @if(!empty($starAnalysis))
 <div class="mb-4 p-4" style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;">
 <h6 style="color:var(--tx);font-weight:bold;margin-bottom:16px;">STAR Check</h6>
 <div class="d-flex flex-wrap gap-4 align-items-center">
 @foreach($starLabels as $key => $label)
 @php $present = (bool) ($starAnalysis[$key]?? false); @endphp
 <div class="d-flex align-items-center gap-2">
 <span class="badge rounded-pill {{ $present? 'bg-success': 'bg-warning text-dark' }}" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;">
 <i class="fa-solid {{ $present? 'fa-check': 'fa-minus' }}"></i>
 </span>
 <span style="color:var(--tx);font-weight:600;">{{ $label }}</span>
 </div>
 @endforeach
 </div>
 @if(!empty($starAnalysis['suggestion']))
 <p style="color:var(--tx3);font-size:0.9rem;margin-top:12px;margin-bottom:0;">
 <strong style="color:#b45309;">Next practice:</strong> {{ $starAnalysis['suggestion'] }}
 </p>
 @endif
 </div>
 @elseif(($sessionRecord->score->star_method_score?? 0) > 0)
 <div class="mb-4 p-4" style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;">
 <h6 style="color:var(--tx);font-weight:bold;margin-bottom:12px;">STAR Score</h6>
 <p style="color:var(--tx3);font-size:0.9rem;margin:0;">Session STAR score: {{ $sessionRecord->score->star_method_score }}%.</p>
 </div>
 @endif

 <!-- Feature 8: Suggested Answer Improvement -->
 <div class="row g-4 mb-4">
 <div class="col-md-6">
 @php
 $originalAnswerText = trim((string) ($answer->answer_text?? ''));
 $hasOriginalVoiceEvidence = trim((string) ($answer->delivery_transcript?? ''))!== '';
 $isOriginalVoiceOnlyAnswer = strtolower((string) ($answer->response_mode?? '')) === 'voice' && $canPlayStoredVoiceAnswer;
 @endphp
 <label style="font-size:0.85rem;color:var(--tx3);font-weight:700;text-transform:uppercase;margin-bottom:8px;"><i class="fa-solid fa-user me-2"></i>Your Answer</label>
 <div style="color:var(--tx);background:rgba(255,255,255,0.03);padding:16px;border-radius:12px;border:1px solid var(--bd);height:100%;font-size:0.95rem;line-height:1.6;">
 {{ $originalAnswerText!== ''? $originalAnswerText: ($isOriginalVoiceOnlyAnswer && $hasOriginalVoiceEvidence? 'Voice answer saved. Feedback is based on the saved voice session.': ($canPlayStoredVoiceAnswer? 'Transcript unavailable. Listen to the saved voice answer above.': 'Transcript unavailable for this answer.')) }}
 </div>
 </div>
 <div class="col-md-6">
 <label style="font-size:0.85rem;color:#10b981;font-weight:700;text-transform:uppercase;margin-bottom:8px;"><i class="fa-solid fa-shield-halved me-2"></i>Better Answer Draft</label>
 <div style="color:var(--tx);background:rgba(16, 185, 129, 0.05);padding:16px;border-radius:12px;border:1px solid rgba(16, 185, 129, 0.2);height:100%;font-size:0.95rem;line-height:1.6;">
 {{ review_feedback_without_question_text($answer->better_sample_answer ?: 'No better draft was made for this response.', $answer->question ?? $answer) }}
 </div>
 <div style="color:var(--tx3);font-size:.78rem;margin-top:8px;">Built only from the candidate's answer. Any placeholder needs true facts.</div>
 </div>
 </div>

 @endif

 @php
 $retryAttempts = $answer->retryAttempts?? collect();
 @endphp
 @if($retryAttempts->count() > 0)
 <div class="mt-4 p-4" style="background:rgba(16,185,129,.05);border:1px solid rgba(16,185,129,.2);border-radius:12px;">
 <h6 style="color:#10b981;font-weight:800;margin-bottom:12px;"><i class="fa-solid fa-rotate me-2"></i>Practice Attempts</h6>
 <div class="d-flex flex-column gap-2">
 @foreach($retryAttempts as $retry)
 <div class="d-flex flex-column flex-md-row justify-content-between gap-2" style="color:var(--tx);border-bottom:1px solid var(--bd);padding-bottom:10px;">
 <div>
 <strong>Attempt {{ $retry->attempt_number }}</strong>
 <div style="color:var(--tx3);font-size:.85rem;">{{ $retry->created_at?->format('M d, Y g:i A') }}</div>
 </div>
 <div class="retry-meta d-flex gap-2 flex-wrap align-items-center">
 <span class="retry-chip" style="display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;background:rgba(59,130,246,.12);color:#3b82f6;font-size:.78rem;font-weight:700;">Score {{ $retry->score?? 0 }}%</span>
 @if(in_array(strtolower((string) $retry->response_mode), ['voice', 'hybrid', 'voice_and_text'], true) && ($retry->voice_duration?? 0) > 0 && $retry->delivery_stability_score!== null)
 <span class="retry-chip" style="display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;background:rgba(59,130,246,.12);color:#3b82f6;font-size:.78rem;font-weight:700;">Pacing {{ $retry->delivery_stability_score }}%</span>
 @endif
 </div>
 </div>
 @if($retry->ai_feedback)
 <p style="color:var(--tx2);font-size:.9rem;line-height:1.6;margin:0 0 8px;">{{ review_feedback_without_question_text($retry->ai_feedback, $retry->question ?? $answer->question ?? $retry) }}</p>
 @endif
 @include('mobile.partials.interview-answer-coaching', ['answer' => $retry])
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
