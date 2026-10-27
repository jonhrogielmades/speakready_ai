@extends('desktop.layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/admin/feedback/index.css?v=7') }}" data-page-style="admin-feedback-index">
@endpush

@section('content')
<div class="container-fluid py-4 feedback-audit-page">
    <!-- Header -->
    <div class="feedback-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="feedback-page-title mb-1" style="font-weight: 700; color: var(--tx);"><i class="fa-solid fa-clipboard-check"></i> Interview Feedback Audit</h2>
            <p class="feedback-page-subtitle mb-0" style="color: var(--tx3);">Monitor, review, and evaluate AI feedback for interview practice.</p>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row g-4 mb-4 feedback-stat-grid">
        <div class="col-md-3">
            <div class="card boc feedback-stat-card" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd); box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2" style="color: var(--tx3);">Total Feedback</h6>
                            <h3 class="mb-0 fw-bold" style="color: var(--tx);">{{ number_format($stats['total']) }}</h3>
                        </div>
                        <div class="feedback-stat-icon d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            <i class="fa-solid fa-comment-dots fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card boc feedback-stat-card" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd); box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2" style="color: var(--tx3);">Reviewed</h6>
                            <h3 class="mb-0 fw-bold" style="color: var(--tx);">{{ number_format($stats['reviewed']) }}</h3>
                        </div>
                        <div class="feedback-stat-icon d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="fa-solid fa-check-double fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card boc feedback-stat-card" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd); box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2" style="color: var(--tx3);">Pending Review</h6>
                            <h3 class="mb-0 fw-bold" style="color: var(--tx);">{{ number_format($stats['pending']) }}</h3>
                        </div>
                        <div class="feedback-stat-icon d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                            <i class="fa-solid fa-hourglass-half fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card boc feedback-stat-card" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd); box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2" style="color: var(--tx3);">Flagged</h6>
                            <h3 class="mb-0 fw-bold" style="color: var(--tx);">{{ number_format($stats['flagged']) }}</h3>
                        </div>
                        <div class="feedback-stat-icon d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: var(--danger-bg); color: var(--danger-tx);">
                            <i class="fa-solid fa-flag fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Audit List -->
    <div class="row g-4 mb-4 feedback-main-grid">
        <div class="col-12">
            <div class="card boc feedback-content-card" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color: var(--tx);">Interview Feedback Audit List</h6>
                    
                    <!-- Filters -->
                    <form action="{{ route('admin.feedback.index') }}" method="GET" class="feedback-filter-form row g-2 mb-3">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Search by interview question..." value="{{ request('search') }}" style="border-radius: 8px;">
                        </div>
                        <div class="col-md-4">
                            <select name="status" class="form-select" style="border-radius: 8px;">
                                <option value="">All Statuses</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                                <option value="flagged" {{ request('status') == 'flagged' ? 'selected' : '' }}>Flagged</option>
                                <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-dark feedback-filter-btn w-100" style="border-radius: 8px;"><i class="fa-solid fa-filter"></i>Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive" id="mainFeedbackTableWrapper">
                        <table class="table align-middle" id="mainFeedbackTable" style="color: var(--tx); --bs-table-bg: transparent; --bs-table-color: var(--tx);">
                            <thead style="background: transparent;">
                                <tr>
                                    <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">User</th>
                                    <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Question</th>
                                    <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Score</th>
                                    <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Generated Date</th>
                                    <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Status</th>
                                    <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($feedbacks as $fb)
                                <tr>
                                    <td data-label="User" class="feedback-user-cell" style="border-bottom: 1px solid var(--bd);">
                                        <div class="fw-semibold feedback-user-name">{{ $fb->interviewSession?->user?->name ?? 'Unknown User' }}</div>
                                        @if($fb->interviewSession?->user?->email)
                                            <div class="feedback-user-email">{{ $fb->interviewSession->user->email }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Question" class="feedback-question-cell" style="border-bottom: 1px solid var(--bd);">
                                        <div class="text-truncate" style="max-width: 250px;" title="{{ $fb->question ? $fb->question->question_text : 'N/A' }}">
                                            {{ $fb->question ? $fb->question->question_text : 'N/A' }}
                                        </div>
                                    </td>
                                    <td data-label="Score" style="border-bottom: 1px solid var(--bd);">
                                        <span class="badge" style="{{ $fb->score >= 80 ? 'background: rgba(16, 185, 129, 0.1); color: #10b981;' : ($fb->score >= 50 ? 'background: rgba(245, 158, 11, 0.1); color: #f59e0b;' : 'background: var(--danger-bg); color: var(--danger-tx);') }}">
                                            {{ $fb->score ?? 'N/A' }}%
                                        </span>
                                    </td>
                                    <td data-label="Generated Date" style="border-bottom: 1px solid var(--bd);">{{ $fb->created_at->format('M d, Y') }}</td>
                                    <td data-label="Status" style="border-bottom: 1px solid var(--bd);">
                                        @if($fb->audit_status == 'approved')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="fa-solid fa-check-circle me-1"></i> Approved</span>
                                        @elseif($fb->audit_status == 'under_review')
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25"><i class="fa-solid fa-clock me-1"></i> Under Review</span>
                                        @elseif($fb->audit_status == 'flagged')
                                            <span class="badge" style="background: var(--danger-bg); color: var(--danger-tx); border: 1px solid var(--danger-tx);"><i class="fa-solid fa-flag me-1"></i> Flagged</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25"><i class="fa-solid fa-archive me-1"></i> Archived</span>
                                        @endif
                                    </td>
                                    <td data-label="Action" style="border-bottom: 1px solid var(--bd);">
                                        <a href="{{ route('admin.feedback.show', $fb) }}" class="btn btn-sm btn-outline-primary feedback-review-btn" style="border-radius: 6px;"><i class="fa-solid fa-eye"></i>Review</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4" style="color: var(--tx3); border-bottom: 1px solid var(--bd);">No feedback records found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="admin-feedback-pagination" aria-label="Feedback audit pagination">
                        <div class="admin-feedback-page-count">
                            @if($feedbacks->total())
                                Showing {{ $feedbacks->firstItem() }}-{{ $feedbacks->lastItem() }} of {{ $feedbacks->total() }}
                            @else
                                Showing 0 of 0
                            @endif
                        </div>
                        <div class="admin-feedback-page-actions">
                            @if($feedbacks->onFirstPage())
                                <span class="admin-feedback-page-btn disabled" aria-disabled="true">
                                    <i class="fa-solid fa-chevron-left"></i> Previous
                                </span>
                            @else
                                <a href="{{ $feedbacks->previousPageUrl() }}" class="admin-feedback-page-btn">
                                    <i class="fa-solid fa-chevron-left"></i> Previous
                                </a>
                            @endif

                            @if($feedbacks->hasMorePages())
                                <a href="{{ $feedbacks->nextPageUrl() }}" class="admin-feedback-page-btn">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            @else
                                <span class="admin-feedback-page-btn disabled" aria-disabled="true">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </span>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection
