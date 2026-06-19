@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="font-weight: 700; color: var(--tx1);">Feedback Details Review</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.feedback.index') }}">Feedback Audit</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Audit #{{ $answer->id }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <!-- Status Form -->
            <form action="{{ route('admin.feedback.status', $answer) }}" method="POST" class="d-flex align-items-center gap-2">
                @csrf
                @method('PATCH')
                <select name="audit_status" class="form-select border-0 bg-light fw-bold" style="border-radius: 8px; width: 150px;">
                    <option value="approved" {{ $answer->audit_status == 'approved' ? 'selected' : '' }}>🟢 Approved</option>
                    <option value="under_review" {{ $answer->audit_status == 'under_review' ? 'selected' : '' }}>🟡 Under Review</option>
                    <option value="flagged" {{ $answer->audit_status == 'flagged' ? 'selected' : '' }}>🔴 Flagged</option>
                    <option value="archived" {{ $answer->audit_status == 'archived' ? 'selected' : '' }}>⚫ Archived</option>
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
            <div class="card boc mb-4" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem; letter-spacing: 1px;">Interview Question</h6>
                    <p class="fs-5 fw-bold mb-4" style="color: var(--tx1);">{{ $answer->question ? $answer->question->question_text : 'N/A' }}</p>

                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem; letter-spacing: 1px;">User Answer</h6>
                    <div class="p-3 bg-light rounded" style="border-left: 4px solid #f87171;">
                        <p class="mb-0" style="white-space: pre-line;">{{ $answer->answer_text ?? 'No answer text provided.' }}</p>
                    </div>
                </div>
            </div>

            <!-- AI Feedback Section -->
            <div class="card boc" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 0.8rem; letter-spacing: 1px;">AI Generated Feedback</h6>
                    
                    <div class="mb-4">
                        <strong class="d-block mb-2 text-success"><i class="fa-solid fa-thumbs-up me-2"></i>Strengths</strong>
                        <p class="mb-0">{{ $answer->ai_feedback ?? 'No feedback provided.' }}</p>
                    </div>
                    
                    <hr>
                    
                    <div class="mt-4">
                        <strong class="d-block mb-2 text-primary"><i class="fa-solid fa-lightbulb me-2"></i>Better Sample Answer</strong>
                        <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.05); border: 1px dashed rgba(59, 130, 246, 0.3);">
                            <p class="mb-0 fst-italic">{{ $answer->better_sample_answer ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Verification & Audit -->
        <div class="col-md-5">
            <!-- Score Verification Form -->
            <div class="card boc mb-4" style="border-radius: 16px;">
                <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-shield-halved me-2 text-primary"></i> AI Scoring Verification</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.feedback.verify', $answer) }}" method="POST">
                        @csrf
                        <input type="hidden" name="audit_status" value="approved">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted small">Clarity Score</label>
                                <input type="number" name="clarity_score" class="form-control" value="{{ $answer->clarity_score ?? 0 }}" min="0" max="100">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small">Relevance Score</label>
                                <input type="number" name="relevance_score" class="form-control" value="{{ $answer->relevance_score ?? 0 }}" min="0" max="100">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small">Confidence Score</label>
                                <input type="number" name="confidence_score" class="form-control" value="{{ $answer->confidence_score ?? 0 }}" min="0" max="100">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small">Grammar Score</label>
                                <input type="number" name="grammar_score" class="form-control" value="{{ $answer->grammar_score ?? 0 }}" min="0" max="100">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">STAR Analysis (JSON format)</label>
                            <textarea name="star_analysis" class="form-control font-monospace" rows="4">{{ json_encode($answer->star_analysis) ?? '{}' }}</textarea>
                            <div class="form-text">Verify Situation, Task, Action, Result parameters.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Verification Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Optional notes about this verification...">
                        </div>

                        <button type="submit" class="btn btn-primary w-100" style="border-radius: 8px;">
                            <i class="fa-solid fa-check me-2"></i>Verify & Approve Scores
                        </button>
                    </form>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="card boc" style="border-radius: 16px;">
                <div class="card-header bg-transparent pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-timeline me-2 text-muted"></i> Audit Trail</h5>
                    <button class="btn btn-sm btn-outline-secondary" style="border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#addNoteModal">Add Note</button>
                </div>
                <div class="card-body p-4">
                    <div class="timeline" style="border-left: 2px solid #e9ecef; padding-left: 20px; position: relative;">
                        @forelse($answer->auditLogs()->latest()->get() as $log)
                            <div class="mb-4 position-relative">
                                <span class="position-absolute" style="left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #f87171; border: 2px solid #fff;"></span>
                                <div class="text-muted small mb-1">{{ $log->created_at->format('M d, Y h:i A') }}</div>
                                <div class="fw-bold">{{ ucwords(str_replace('_', ' ', $log->action)) }}</div>
                                <div class="text-muted small">By: {{ $log->admin ? $log->admin->name : 'System' }}</div>
                                @if($log->notes)
                                    <div class="mt-2 p-2 bg-light rounded small" style="border-left: 3px solid #ccc;">{{ $log->notes }}</div>
                                @endif
                                @if($log->old_status !== $log->new_status)
                                    <div class="mt-1 small">
                                        <span class="badge bg-secondary">{{ $log->old_status }}</span> <i class="fa-solid fa-arrow-right mx-1 text-muted"></i> <span class="badge bg-primary">{{ $log->new_status }}</span>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted text-center py-3">No audit actions recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Add Revision Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.feedback.notes', $answer) }}" method="POST">
                @csrf
                <div class="modal-body px-4 pb-0">
                    <p class="text-muted small mb-3">Add a note to explain why this feedback was flagged or requires changes. This will be recorded in the audit trail.</p>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Type your note here..." required></textarea>
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
