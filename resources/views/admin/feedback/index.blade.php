@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="font-weight: 700; color: var(--tx);">Feedback Audit Dashboard</h2>
            <p class="mb-0" style="color: var(--tx3);">Monitor, review, and evaluate AI-generated feedback quality.</p>
        </div>
        <div>
            <a href="#" class="btn" style="border-radius: 10px; background-color: var(--danger-bg); color: var(--danger-tx); border: 1px solid var(--danger-tx);">
                <i class="fa-solid fa-download me-2"></i>Export Report
            </a>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card boc" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd); box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2" style="color: var(--tx3);">Total Feedback</h6>
                            <h3 class="mb-0 fw-bold" style="color: var(--tx);">{{ number_format($stats['total']) }}</h3>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            <i class="fa-solid fa-comment-dots fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card boc" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd); box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2" style="color: var(--tx3);">Reviewed</h6>
                            <h3 class="mb-0 fw-bold" style="color: var(--tx);">{{ number_format($stats['reviewed']) }}</h3>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="fa-solid fa-check-double fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card boc" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd); box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2" style="color: var(--tx3);">Pending Review</h6>
                            <h3 class="mb-0 fw-bold" style="color: var(--tx);">{{ number_format($stats['pending']) }}</h3>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                            <i class="fa-solid fa-hourglass-half fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card boc" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd); box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2" style="color: var(--tx3);">Flagged</h6>
                            <h3 class="mb-0 fw-bold" style="color: var(--tx);">{{ number_format($stats['flagged']) }}</h3>
                        </div>
                        <div class="d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 12px; background: var(--danger-bg); color: var(--danger-tx);">
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
            <div class="card boc" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color: var(--tx);">Average Scores</h6>
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="scoresChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card boc" style="border-radius: 16px; background: var(--sf); border: 1px solid var(--bd);">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color: var(--tx);">Feedback Audit List</h6>
                    
                    <!-- Filters -->
                    <form action="{{ route('admin.feedback.index') }}" method="GET" class="row g-2 mb-3">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Search by question..." value="{{ request('search') }}" style="border-radius: 8px;">
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
                            <button type="submit" class="btn btn-dark w-100" style="border-radius: 8px;">Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle" style="color: var(--tx); --bs-table-bg: transparent; --bs-table-color: var(--tx);">
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
                                        <a href="{{ route('admin.feedback.show', $fb) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 6px;">Review</a>
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
    const ctx = document.getElementById('scoresChart').getContext('2d');
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
