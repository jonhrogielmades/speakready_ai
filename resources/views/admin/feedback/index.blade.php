@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<style>
    .feedback-page-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--sr-page-title-accent, #ffffff) !important;
        -webkit-text-fill-color: var(--sr-page-title-accent, #ffffff) !important;
        text-transform: uppercase;
        text-shadow: 0 2px 12px rgba(15, 23, 42, 0.18);
    }
    .feedback-page-title i {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.14);
        color: var(--sr-page-title-accent, #ffffff);
        flex: 0 0 auto;
    }
    .feedback-export-btn,
    .feedback-filter-btn,
    .feedback-review-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
    }

    /* Mobile Card-based Table Layout for Main Feedback Table */
    @media (max-width: 767px) {
        .feedback-audit-page {
            padding: 0 !important;
        }
        .feedback-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 12px;
            margin-bottom: 14px !important;
        }
        .feedback-page-title {
            justify-content: center;
            font-size: clamp(1.04rem, 5vw, 1.14rem) !important;
            line-height: 1.14;
            margin-bottom: 6px !important;
            max-width: 19rem;
            text-wrap: balance;
        }
        .feedback-page-title i {
            width: 30px;
            height: 30px;
            border-radius: 11px;
            font-size: 0.84rem;
        }
        .feedback-page-subtitle {
            font-size: 0.78rem !important;
            line-height: 1.45;
        }
        .feedback-header-actions {
            width: 100%;
        }
        .feedback-export-btn {
            width: 100%;
            min-height: 40px;
            border-radius: 11px !important;
            padding: 8px 10px !important;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .feedback-export-btn i {
            margin-right: 0 !important;
        }
        .feedback-stat-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            margin-bottom: 14px !important;
        }
        .feedback-stat-grid > [class*="col-"] {
            width: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .feedback-stat-card {
            border-radius: 16px !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08) !important;
        }
        .feedback-stat-card .card-body {
            min-height: 118px;
            padding: 12px !important;
        }
        .feedback-stat-card .d-flex {
            align-items: center !important;
            gap: 10px;
        }
        .feedback-stat-card h6 {
            font-size: 0.62rem;
            line-height: 1.2;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px !important;
        }
        .feedback-stat-card h3 {
            font-size: 1.22rem;
            line-height: 1.1;
        }
        .feedback-stat-icon {
            width: 38px !important;
            height: 38px !important;
            border-radius: 13px !important;
            font-size: 0.95rem;
            flex: 0 0 38px;
        }
        .feedback-content-card {
            border-radius: 14px !important;
        }
        .feedback-content-card .card-body {
            padding: 14px !important;
        }
        .feedback-content-card h6 {
            font-size: 0.9rem;
            line-height: 1.3;
            margin-bottom: 12px !important;
        }
        .feedback-chart-box {
            height: 188px !important;
        }
        .feedback-filter-form {
            margin-bottom: 14px !important;
        }
        .feedback-filter-form .form-control,
        .feedback-filter-form .form-select {
            min-height: 42px;
            border-radius: 11px !important;
            font-size: 0.82rem;
            background: var(--bg3);
            border: 1px solid var(--bd);
            color: var(--tx);
        }
        .feedback-filter-btn {
            min-height: 42px;
            border-radius: 11px !important;
            font-size: 0.82rem;
            font-weight: 700;
        }
        #mainFeedbackTableWrapper {
            overflow-x: visible !important;
            -webkit-overflow-scrolling: auto !important;
        }
        #mainFeedbackTable thead {
            display: none;
        }
        #mainFeedbackTable tbody tr {
            display: flex;
            flex-direction: column;
            background: var(--bg3, rgba(255,255,255,0.02));
            border-radius: 12px;
            margin-bottom: 10px;
            border: 1px solid var(--bd, rgba(255,255,255,0.1));
            padding: 11px;
        }
        #mainFeedbackTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 7px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-top: none !important;
            text-align: right;
        }
        #mainFeedbackTable tbody td:last-child {
            border-bottom: none !important;
            justify-content: flex-end;
            gap: 8px;
            padding-top: 12px !important;
        }
        #mainFeedbackTable tbody td::before {
            font-size: 0.68rem;
            color: var(--tx3, #888);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-align: left;
        }
        #mainFeedbackTable tbody td:nth-child(1)::before { content: "Audit ID"; }
        #mainFeedbackTable tbody td:nth-child(3)::before { content: "Score"; }
        #mainFeedbackTable tbody td:nth-child(4)::before { content: "Generated Date"; }
        #mainFeedbackTable tbody td:nth-child(5)::before { content: "Status"; }
        
        #mainFeedbackTable tbody td:nth-child(2) {
            order: -1;
            justify-content: flex-start;
            border-bottom: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
            padding-bottom: 12px !important;
            margin-bottom: 4px;
        }
        #mainFeedbackTable tbody td:nth-child(2)::before { content: none; }
        #mainFeedbackTable tbody td:nth-child(2) .text-truncate {
            max-width: 100% !important;
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
            line-height: 1.35;
            font-size: 0.82rem;
        }
        #mainFeedbackTable .badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            border-radius: 9px;
            padding: 5px 8px;
            font-size: 0.68rem;
            line-height: 1.15;
            white-space: normal;
        }
        .feedback-review-btn {
            min-height: 34px;
            border-radius: 10px !important;
            padding: 6px 11px !important;
            font-size: 0.74rem !important;
            font-weight: 700;
        }
        .pagination {
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
        }
        .page-link {
            min-width: 34px;
            min-height: 34px;
            border-radius: 9px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
        }
    }

    @media (max-width: 380px) {
        .feedback-stat-grid {
            gap: 8px !important;
        }
        .feedback-stat-card .card-body {
            min-height: 108px;
            padding: 10px !important;
        }
        .feedback-stat-card h3 {
            font-size: 1.08rem;
        }
    }
</style>
<div class="container-fluid py-4 feedback-audit-page">
    <!-- Header -->
    <div class="feedback-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="feedback-page-title mb-1" style="font-weight: 700; color: var(--tx);"><i class="fa-solid fa-clipboard-check"></i> Philippines Interview Feedback Audit</h2>
            <p class="feedback-page-subtitle mb-0" style="color: var(--tx3);">Monitor, review, and evaluate AI feedback for Philippine interview practice.</p>
        </div>
        <div class="feedback-header-actions">
            <a href="{{ route('admin.feedback.export', request()->query()) }}" class="btn feedback-export-btn" style="border-radius: 10px; background-color: var(--danger-bg); color: var(--danger-tx); border: 1px solid var(--danger-tx);">
                <i class="fa-solid fa-download me-2"></i>Export Report
            </a>
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

    <!-- Analytics Charts -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card boc feedback-content-card" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color: var(--tx);">Average Scores</h6>
                    <div class="feedback-chart-box" style="position: relative; height: 250px; width: 100%;">
                        <canvas id="scoresChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card boc feedback-content-card" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color: var(--tx);">Philippines Interview Feedback Audit List</h6>
                    
                    <!-- Filters -->
                    <form action="{{ route('admin.feedback.index') }}" method="GET" class="feedback-filter-form row g-2 mb-3">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Search by PH interview question..." value="{{ request('search') }}" style="border-radius: 8px;">
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
                                    <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Audit ID</th>
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
                                    <td style="border-bottom: 1px solid var(--bd);">#{{ $fb->id }}</td>
                                    <td style="border-bottom: 1px solid var(--bd);">
                                        <div class="text-truncate" style="max-width: 250px;" title="{{ $fb->question ? $fb->question->question_text : 'N/A' }}">
                                            {{ $fb->question ? $fb->question->question_text : 'N/A' }}
                                        </div>
                                    </td>
                                    <td style="border-bottom: 1px solid var(--bd);">
                                        <span class="badge" style="{{ $fb->score >= 80 ? 'background: rgba(16, 185, 129, 0.1); color: #10b981;' : ($fb->score >= 50 ? 'background: rgba(245, 158, 11, 0.1); color: #f59e0b;' : 'background: var(--danger-bg); color: var(--danger-tx);') }}">
                                            {{ $fb->score ?? 'N/A' }}%
                                        </span>
                                    </td>
                                    <td style="border-bottom: 1px solid var(--bd);">{{ $fb->created_at->format('M d, Y') }}</td>
                                    <td style="border-bottom: 1px solid var(--bd);">
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
                                    <td style="border-bottom: 1px solid var(--bd);">
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
                    
                    <div class="mt-3">
                        {{ $feedbacks->links('pagination::bootstrap-5') }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.Chart === 'undefined') return;

    const scoresCanvas = document.getElementById('scoresChart');
    if (!scoresCanvas) return;

    const ctx = scoresCanvas.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Overall', 'Clarity', 'Relevance'],
            datasets: [{
                label: 'Average Score (%)',
                data: [
                    {{ round($stats['avg_score']) }}, 
                    {{ round($stats['avg_clarity']) }}, 
                    {{ round($stats['avg_relevance']) }}
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.6)',
                    'rgba(16, 185, 129, 0.6)',
                    'rgba(245, 158, 11, 0.6)'
                ],
                borderColor: [
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)'
                ],
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: { color: '#888' },
                    grid: { color: 'rgba(128, 128, 128, 0.2)' }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { color: '#888' },
                    grid: { color: 'rgba(128, 128, 128, 0.2)' }
                }
            },
            plugins: {
                legend: {
                    labels: { color: '#888' }
                }
            }
        }
    });
});
</script>
@endsection
