@extends('layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('user.feedback') }}" class="btn btn-link text-decoration-none p-0 mb-2" style="color:var(--pur)"><i class="fa-solid fa-arrow-left me-2"></i>Back to Feedback Center</a>
            <h4 style="color:var(--tx);font-weight:700">Session Review</h4>
            <p style="color:var(--tx3)">Detailed breakdown of your interview on {{ $sessionRecord->created_at->format('M d, Y') }}</p>
        </div>
        <div class="text-end d-flex gap-3 align-items-center">
            <div class="text-start">
                <div style="font-size:2rem;font-weight:700;color:#34d399;line-height:1">{{ $sessionRecord->score->overall_readiness_score ?? 0 }}<span style="font-size:1rem;color:var(--tx3)">/100</span></div>
                <div style="font-size:.8rem;color:var(--tx3)">Overall Readiness</div>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-color:var(--bd);color:var(--tx)">
                    <i class="fa-solid fa-download me-2"></i>Export Report
                </button>
                <ul class="dropdown-menu" style="background:var(--sf);border-color:var(--bd)">
                    <li><a class="dropdown-item" href="#" style="color:var(--tx)"><i class="fa-solid fa-file-pdf text-danger me-2"></i> Download PDF</a></li>
                    <li><a class="dropdown-item" href="#" style="color:var(--tx)"><i class="fa-solid fa-file-excel text-success me-2"></i> Download Excel</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;padding:16px;text-align:center">
                <div style="font-size:1.5rem;font-weight:700;color:var(--tx)">
                    {{ floor(($sessionRecord->duration_seconds ?? 0) / 60) }}m {{ ($sessionRecord->duration_seconds ?? 0) % 60 }}s
                </div>
                <div style="font-size:.8rem;color:var(--tx3)">Total Duration</div>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;padding:16px;text-align:center">
                <div style="font-size:1.5rem;font-weight:700;color:var(--tx)">
                    {{ $sessionRecord->answers->where('is_skipped', false)->count() }} / {{ $sessionRecord->num_questions ?? $sessionRecord->answers->count() }}
                </div>
                <div style="font-size:.8rem;color:var(--tx3)">Questions Answered</div>
            </div>
        </div>
        <div class="col-md-6">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;padding:16px">
                <div class="d-flex justify-content-between text-center">
                    <div>
                        <div style="font-size:1.2rem;font-weight:700;color:var(--tx)">{{ $sessionRecord->score->clarity_score ?? 0 }}%</div>
                        <div style="font-size:.7rem;color:var(--tx3)">Clarity</div>
                    </div>
                    <div>
                        <div style="font-size:1.2rem;font-weight:700;color:var(--tx)">{{ $sessionRecord->score->relevance_score ?? 0 }}%</div>
                        <div style="font-size:.7rem;color:var(--tx3)">Relevance</div>
                    </div>
                    <div>
                        <div style="font-size:1.2rem;font-weight:700;color:var(--tx)">{{ $sessionRecord->score->grammar_score ?? 0 }}%</div>
                        <div style="font-size:.7rem;color:var(--tx3)">Grammar</div>
                    </div>
                    <div>
                        <div style="font-size:1.2rem;font-weight:700;color:var(--tx)">{{ $sessionRecord->score->professionalism_score ?? 0 }}%</div>
                        <div style="font-size:.7rem;color:var(--tx3)">Professionalism</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overall Feedback -->
    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;margin-bottom:24px">
        <h5 style="color:var(--tx);margin-bottom:16px;">AI Coach Summary</h5>
        <div class="row g-4">
            <div class="col-md-4">
                <div style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.2);padding:16px;border-radius:12px;height:100%">
                    <h6 style="color:#34d399"><i class="fa-solid fa-thumbs-up me-2"></i>Strengths</h6>
                    <p style="font-size:.9rem;color:var(--tx);margin-bottom:0">{{ $sessionRecord->feedback->strengths ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.2);padding:16px;border-radius:12px;height:100%">
                    <h6 style="color:#f87171"><i class="fa-solid fa-triangle-exclamation me-2"></i>Areas to Improve</h6>
                    <p style="font-size:.9rem;color:var(--tx);margin-bottom:0">{{ $sessionRecord->feedback->weaknesses ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.2);padding:16px;border-radius:12px;height:100%">
                    <h6 style="color:#a78bfa"><i class="fa-solid fa-lightbulb me-2"></i>Suggestions</h6>
                    <p style="font-size:.9rem;color:var(--tx);margin-bottom:0">{{ $sessionRecord->feedback->improvement_suggestions ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Breakdown -->
    <h5 style="color:var(--tx);margin-bottom:16px;margin-top:32px">Question Breakdown</h5>
    <div class="accordion" id="answersAccordion">
        @foreach($sessionRecord->answers as $index => $answer)
        <div class="accordion-item" style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;margin-bottom:16px;overflow:hidden">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" style="background:transparent;color:var(--tx);box-shadow:none">
                    <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                        <span><strong>Q{{ $index + 1 }}:</strong> {{ $answer->question->question_text ?? 'Unknown Question' }}</span>
                        <span class="db-badge" style="background:rgba(52,211,153,.1);color:#34d399">Score: {{ $answer->score ?? 'N/A' }}</span>
                    </div>
                </button>
            </h2>
            <div id="collapse{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#answersAccordion">
                <div class="accordion-body" style="border-top:1px solid var(--bd)">
                    @if($answer->is_skipped)
                        <div class="mb-4">
                            <span class="db-badge" style="background:rgba(248,113,113,.1);color:#f87171"><i class="fa-solid fa-forward-step me-1"></i> Question Skipped</span>
                        </div>
                    @else
                        <div class="mb-4">
                            <label style="font-size:.8rem;color:var(--tx3);font-weight:600;text-transform:uppercase">Your Answer</label>
                            <p style="color:var(--tx);background:rgba(255,255,255,.05);padding:12px;border-radius:8px;margin-top:4px">{{ $answer->answer_text }}</p>
                            @if($answer->filler_words_count > 0 || $answer->wpm > 0)
                            <div class="d-flex gap-3" style="font-size:.8rem;color:var(--tx3);margin-top:8px">
                                <span><i class="fa-solid fa-wave-square me-1"></i> {{ $answer->wpm }} WPM</span>
                                <span><i class="fa-solid fa-microphone-lines me-1"></i> {{ $answer->voice_duration }}s Voice Duration</span>
                                <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $answer->filler_words_count }} Filler Words</span>
                            </div>
                            @endif
                        </div>
                    @endif
                    
                    <div class="mb-4">
                        <label style="font-size:.8rem;color:#a78bfa;font-weight:600;text-transform:uppercase"><i class="fa-solid fa-robot me-1"></i> AI Feedback</label>
                        <p style="color:var(--tx);margin-top:4px">{{ $answer->ai_feedback ?? 'No specific feedback generated.' }}</p>
                    </div>

                    <div class="mb-4">
                        <label style="font-size:.8rem;color:#34d399;font-weight:600;text-transform:uppercase"><i class="fa-solid fa-star me-1"></i> Better Sample Answer</label>
                        <p style="color:var(--tx);background:rgba(52,211,153,.05);border-left:3px solid #34d399;padding:12px;border-radius:4px;margin-top:4px">{{ $answer->better_sample_answer ?? 'No sample provided.' }}</p>
                    </div>

                    @if($answer->follow_up_question)
                    <div>
                        <label style="font-size:.8rem;color:#f87171;font-weight:600;text-transform:uppercase"><i class="fa-solid fa-clipboard-question me-1"></i> Follow-up Question to consider</label>
                        <p style="color:var(--tx);margin-top:4px;font-style:italic">"{{ $answer->follow_up_question }}"</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection
