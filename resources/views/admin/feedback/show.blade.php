@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="font-weight: 700; color: var(--tx);">Philippines Interview Feedback Review</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.feedback.index') }}">PH Feedback Audit</a></li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: var(--tx3);">Audit #{{ $answer->id }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <!-- Status Form -->
            <form action="{{ route('admin.feedback.status', $answer) }}" method="POST" class="d-flex align-items-center gap-2">
                @csrf
                @method('PATCH')
                <select name="audit_status" class="form-select border-0 fw-bold" style="border-radius: 8px; width: 150px; background: var(--bg); color: var(--tx);">
                    <option value="approved" {{ $answer->audit_status == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="under_review" {{ $answer->audit_status == 'under_review' ? 'selected' : '' }}>Under Review</option>
                    <option value="flagged" {{ $answer->audit_status == 'flagged' ? 'selected' : '' }}>Flagged</option>
                    <option value="archived" {{ $answer->audit_status == 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
                <button type="submit" class="btn btn-dark" style="border-radius: 8px;">Update</button>
            </form>
        </div>
    </div>

    <!-- Alert for Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left Column: Content -->
        <div class="col-md-7">
            <!-- QA Section -->
            <div class="card boc mb-4" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd);">
                <div class="card-body p-4">
                    <h6 class="text-uppercase fw-bold mb-3" style="font-size: 0.8rem; letter-spacing: 1px; color: var(--tx3);">Philippines Interview Question</h6>
                    <p class="fs-5 fw-bold mb-4" style="color: var(--tx);">{{ $answer->question ? $answer->question->question_text : 'N/A' }}</p>

                    <h6 class="text-uppercase fw-bold mb-3" style="font-size: 0.8rem; letter-spacing: 1px; color: var(--tx3);">User Answer</h6>
                    <div class="p-3 rounded" style="border-left: 4px solid var(--danger-tx); background: var(--bg); color: var(--tx);">
                        <p class="mb-0" style="white-space: pre-line;">{{ $answer->answer_text ?? 'No answer text provided.' }}</p>
                    </div>

                    @php
                        $integrityFlags = is_array($answer->answer_integrity_flags) ? $answer->answer_integrity_flags : [];
                        $integritySignals = array_values(array_filter((array) ($integrityFlags['signals'] ?? [])));
                        $pasteEventCount = (int) ($answer->paste_event_count ?? 0);
                        $pastedCharacterCount = (int) ($answer->pasted_character_count ?? 0);
                        $aiGeneratedLikelihood = (int) ($answer->ai_generated_likelihood ?? 0);
                        $hasIntegritySignals = $pasteEventCount > 0 || $pastedCharacterCount > 0 || $aiGeneratedLikelihood >= 50 || ! empty($integritySignals);
                    @endphp
                    @if($hasIntegritySignals)
                        <div class="mt-3 p-3 rounded" style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.28);">
                            <strong class="d-block mb-2" style="color:#f59e0b;"><i class="fa-solid fa-shield-halved me-2"></i>Answer Integrity Signals</strong>
                            <p class="small mb-2" style="color:var(--tx3);">Signals are not proof of misconduct; they mark answers that may need human review.</p>
                            <div class="d-flex flex-wrap gap-2 small" style="color:var(--tx);">
                                <span class="badge bg-warning text-dark">Paste events: {{ $pasteEventCount }}</span>
                                <span class="badge bg-warning text-dark">Pasted chars: {{ $pastedCharacterCount }}</span>
                                <span class="badge bg-warning text-dark">AI-template likelihood: {{ $aiGeneratedLikelihood }}%</span>
                                @foreach($integritySignals as $signal)
                                    <span class="badge" style="background:rgba(245,158,11,.16);color:#f59e0b;border:1px solid rgba(245,158,11,.28);">{{ str_replace('_', ' ', $signal) }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- AI Feedback Section -->
            <div class="card boc" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd);">
                <div class="card-body p-4">
                    <h6 class="text-uppercase fw-bold mb-3" style="font-size: 0.8rem; letter-spacing: 1px; color: var(--tx3);">AI Generated Philippines Interview Feedback</h6>
                    
                    <div class="mb-4">
                        <strong class="d-block mb-2 text-success"><i class="fa-solid fa-thumbs-up me-2"></i>Strengths</strong>
                        <p class="mb-0" style="color: var(--tx);">{{ $answer->ai_feedback ?? 'No feedback provided.' }}</p>
                    </div>
                    
                    <hr>
                    
                    <div class="mt-4">
                        <strong class="d-block mb-2 text-primary"><i class="fa-solid fa-shield-halved me-2"></i>Fact-Grounded Revision Template</strong>
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.05); border: 1px dashed rgba(59, 130, 246, 0.3);">
                            <p class="mb-0 fst-italic" style="color: var(--tx);">{{ $answer->better_sample_answer ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Verification & Audit -->
        <div class="col-md-5">
            <!-- Score Verification Form -->
            <div class="card boc mb-4" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd);">
                <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-0" style="color: var(--tx);"><i class="fa-solid fa-shield-halved me-2 text-primary"></i> PH Interview Scoring Verification</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.feedback.verify', $answer) }}" method="POST">
                        @csrf
                        <input type="hidden" name="audit_status" value="approved">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small" style="color: var(--tx3);">Clarity Score</label>
                                <input type="number" name="clarity_score" class="form-control" style="background: var(--bg); color: var(--tx); border: 1px solid var(--bd);" value="{{ $answer->clarity_score ?? 0 }}" min="0" max="100">
                            </div>
                            <div class="col-6">
                                <label class="form-label small" style="color: var(--tx3);">Relevance Score</label>
                                <input type="number" name="relevance_score" class="form-control" style="background: var(--bg); color: var(--tx); border: 1px solid var(--bd);" value="{{ $answer->relevance_score ?? 0 }}" min="0" max="100">
                            </div>
                            <div class="col-6">
                                <label class="form-label small" style="color: var(--tx3);">Delivery Stability (non-scoring)</label>
                                <input type="number" name="delivery_stability_score" class="form-control" style="background: var(--bg); color: var(--tx); border: 1px solid var(--bd);" value="{{ $answer->delivery_stability_score ?? 0 }}" min="0" max="100">
                            </div>
                            <div class="col-6">
                                <label class="form-label small" style="color: var(--tx3);">Grammar Score</label>
                                <input type="number" name="grammar_score" class="form-control" style="background: var(--bg); color: var(--tx); border: 1px solid var(--bd);" value="{{ $answer->grammar_score ?? 0 }}" min="0" max="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small" style="color: var(--tx3);">STAR Analysis (JSON format)</label>
                            <textarea name="star_analysis" class="form-control font-monospace" rows="4" style="background: var(--bg); color: var(--tx); border: 1px solid var(--bd);">{{ json_encode($answer->star_analysis ?? []) }}</textarea>
                            <div class="form-text" style="color: var(--tx3);">Verify Situation, Task, Action, Result parameters.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small" style="color: var(--tx3);">Verification Notes</label>
                            <input type="text" name="notes" class="form-control" style="background: var(--bg); color: var(--tx); border: 1px solid var(--bd);" placeholder="Optional notes about this verification...">
                        </div>

                        <button type="submit" class="btn btn-primary w-100" style="border-radius: 8px;">
                            <i class="fa-solid fa-check me-2"></i>Verify & Approve Scores
                        </button>
                    </form>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="card boc" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd);">
                <div class="card-header bg-transparent pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--tx);"><i class="fa-solid fa-timeline me-2" style="color: var(--tx3);"></i> Audit Trail</h5>
                    <button class="btn btn-sm btn-outline-secondary" style="border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#addNoteModal">Add Note</button>
                </div>
                <div class="card-body p-4">
                    <div class="timeline" style="border-left: 2px solid var(--bd); padding-left: 20px; position: relative;">
                        @forelse($answer->auditLogs()->latest()->get() as $log)
                            <div class="mb-4 position-relative">
                                <span class="position-absolute" style="left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--danger-tx); border: 2px solid var(--sf);"></span>
                                <div class="small mb-1" style="color: var(--tx3);">{{ $log->created_at->format('M d, Y h:i A') }}</div>
                                <div class="fw-bold" style="color: var(--tx);">{{ ucwords(str_replace('_', ' ', $log->action)) }}</div>
                                <div class="small" style="color: var(--tx3);">By: {{ $log->admin ? $log->admin->name : 'System' }}</div>
                                @if($log->notes)
                                    <div class="mt-2 p-2 rounded small" style="background: var(--bg); border-left: 3px solid var(--tx3); color: var(--tx);">{{ $log->notes }}</div>
                                @endif
                                @if($log->old_status !== $log->new_status)
                                    <div class="mt-1 small">
                                        <span class="badge bg-secondary">{{ $log->old_status }}</span> <i class="fa-solid fa-arrow-right mx-1" style="color: var(--tx3);"></i> <span class="badge bg-primary">{{ $log->new_status }}</span>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-3" style="color: var(--tx3);">No audit actions recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true" style="--bs-modal-bg: var(--sf);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: 1px solid var(--bd); background: var(--sf);">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" style="color: var(--tx);">Add Revision Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <form action="{{ route('admin.feedback.notes', $answer) }}" method="POST">
                @csrf
                <div class="modal-body px-4 pb-0">
                    <p class="small mb-3" style="color: var(--tx3);">Add a note to explain why this feedback was flagged or requires changes. This will be recorded in the audit trail.</p>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Type your note here..." style="background: var(--bg); color: var(--tx); border: 1px solid var(--bd);" required></textarea>
                </div>
                <div class="modal-footer border-top-0 pb-4 px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;">Save Note</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

