@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/' . (($isMobile ?? false) ? 'mobile' : 'desktop') . '/admin/sessions/review.css?v=1') }}" data-page-style="admin-sessions-review">
@endpush

@section('content')

<div class="db-section active">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.sessions.show', $session->id) }}" class="text-decoration-none" style="color:var(--tx2);font-size:0.9rem;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Details
            </a>
            <h4 class="fw-bold mb-1 mt-2">Philippines Interview Q&A Review & AI Feedback</h4>
            <p style="font-size:0.9rem;color:var(--tx2);margin:0;">Audit Philippine interview quality, evaluate user responses, and monitor AI feedback.</p>
        </div>
        <span class="stat-badge primary">Session #{{ $session->id }}</span>
    </div>

    <!-- AI Feedback Monitoring -->
    @if($session->feedback)
    <div class="premium-card mb-4" style="border-left: 4px solid #3b82f6;">
        <h6 class="fw-bold mb-3"><i class="fa-solid fa-robot me-2 text-primary"></i>AI Overall Philippines Interview Feedback</h6>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="mb-3">
                    <label style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;">Overall Evaluation</label>
                    <div class="p-3 mt-1 rounded" style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);font-size:0.95rem;line-height:1.6;">
                        {{ $session->feedback->overall_feedback ?? 'No overall feedback provided.' }}
                    </div>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;">Strengths</label>
                    <div class="p-3 mt-1 rounded text-success" style="background:rgba(52,211,153,0.05);border:1px solid rgba(52,211,153,0.2);font-size:0.95rem;">
                        {!! nl2br(e($session->feedback->strengths ?? 'Not specified')) !!}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;">Areas for Improvement</label>
                    <div class="p-3 mt-1 rounded text-warning" style="background:rgba(251,191,36,0.05);border:1px solid rgba(251,191,36,0.2);font-size:0.95rem;">
                        {!! nl2br(e($session->feedback->weaknesses ?? 'Not specified')) !!}
                    </div>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;">AI Recommendations</label>
                    <div class="p-3 mt-1 rounded text-info" style="background:rgba(6,182,212,0.05);border:1px solid rgba(6,182,212,0.2);font-size:0.95rem;">
                        {!! nl2br(e($session->feedback->recommendations ?? 'No specific recommendations.')) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-warning" style="background:rgba(251,191,36,0.1);color:#fbbf24;border:1px solid rgba(251,191,36,0.3);">
        <i class="fa-solid fa-circle-exclamation me-2"></i> Overall AI feedback has not been generated for this Philippines interview session.
    </div>
    @endif

    <h5 class="fw-bold mb-3 mt-5">Question & Answer Logs</h5>
    
    @forelse($session->answers as $index => $answer)
        <div class="qa-box {{ $index === 0 ? 'open' : '' }}">
            <div class="qa-header" onclick="this.parentElement.classList.toggle('open')">
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-bold" style="color:var(--tx2);">Q{{ $index + 1 }}</span>
                    <h6 class="m-0 fw-bold" style="color:var(--tx);">{{ $answer->question->title ?? 'Deleted Question' }}</h6>
                    @if($answer->is_skipped)
                        <span class="badge bg-danger">Skipped</span>
                    @endif
                </div>
                <i class="fa-solid fa-chevron-down qa-toggle-icon"></i>
            </div>
            <div class="qa-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <label style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;">PH Interview Question Text</label>
                        <p style="color:var(--tx2);font-size:0.95rem;">{{ $answer->question->content ?? 'Content unavailable.' }}</p>

                        <label style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-top:15px;">User's Answer</label>
                        @if($answer->is_skipped)
                            <div class="p-3 rounded text-danger" style="background:rgba(248,113,113,0.1);border:1px dashed rgba(248,113,113,0.3);">
                                User skipped this question.
                            </div>
                        @else
                            <div class="p-3 rounded" style="background:var(--sf);border:1px solid var(--bd);color:var(--tx);line-height:1.6;">
                                {{ $answer->answer_text ?? 'No transcript available.' }}
                            </div>
                        @endif

                        <!-- Voice Response Monitoring -->
                        @if($answer->response_mode == 'voice' && !$answer->is_skipped)
                            <div class="mt-4">
                                <label style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;">Voice Metrics</label>
                                <div class="voice-metrics">
                                    <div class="voice-metric-box">
                                        <div style="font-size:0.75rem;color:var(--tx3);">Duration</div>
                                        <div class="fw-bold fs-5 text-primary">{{ gmdate("i:s", $answer->voice_duration ?? 0) }}</div>
                                    </div>
                                    <div class="voice-metric-box">
                                        <div style="font-size:0.75rem;color:var(--tx3);">Pace (WPM)</div>
                                        <div class="fw-bold fs-5 text-success">{{ $answer->wpm ?? 0 }}</div>
                                    </div>
                                    <div class="voice-metric-box">
                                        <div style="font-size:0.75rem;color:var(--tx3);">Filler Words</div>
                                        <div class="fw-bold fs-5 {{ ($answer->filler_words_count ?? 0) > 5 ? 'text-warning' : 'text-success' }}">{{ $answer->filler_words_count ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <div class="col-lg-6">
                        <!-- AI Question Feedback -->
                        <label style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;">AI Analysis</label>
                        
                        @if($answer->feedback)
                            <div class="feedback-section">
                                <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-comment-medical me-2"></i>Specific Feedback</h6>
                                <p style="font-size:0.95rem;color:var(--tx);margin-bottom:15px;">{{ $answer->feedback }}</p>
                                
                                @if(isset($answer->suggested_answer))
                                    <h6 class="fw-bold text-success mb-2 mt-3"><i class="fa-solid fa-lightbulb me-2"></i>Suggested Better Answer</h6>
                                    <p style="font-size:0.95rem;color:var(--tx);background:rgba(255,255,255,0.05);padding:10px;border-radius:6px;">{{ $answer->suggested_answer }}</p>
                                @endif
                            </div>
                        @else
                            <div class="p-3 text-center rounded text-muted mt-2" style="background:var(--sf);border:1px solid var(--bd);">
                                No individual AI feedback generated for this answer.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="premium-card text-center text-muted">
            <p class="m-0">No answers recorded for this session.</p>
        </div>
    @endforelse

</div>
@endsection

