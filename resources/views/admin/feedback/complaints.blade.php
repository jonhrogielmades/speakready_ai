@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/' . (($isMobile ?? false) ? 'mobile' : 'desktop') . '/admin/feedback/complaints.css?v=1') }}" data-page-style="admin-feedback-complaints">
<div class="db-section active" id="sec-admin-complaints">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1" style="font-size:1.6rem;font-weight: 700; color: var(--tx);">User Feedback Complaints</h4>
            <p class="mb-0" style="color: var(--tx3);">Manage and investigate feedback reported by users.</p>
        </div>
    </div>

    <!-- Alert for Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="complaints-panel">
            <div class="table-responsive" id="mainComplaintsTableWrapper">
                <table class="table align-middle" id="mainComplaintsTable" style="color: var(--tx); --bs-table-bg: transparent; --bs-table-color: var(--tx);">
                    <thead style="background: transparent;">
                        <tr>
                            <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Complaint ID</th>
                            <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">User</th>
                            <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Reason</th>
                            <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Notes</th>
                            <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Reported Date</th>
                            <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Status</th>
                            <th style="color: var(--tx3); border-bottom: 1px solid var(--bd);">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($complaints as $complaint)
                        <tr>
                            <td style="border-bottom: 1px solid var(--bd);">#{{ $complaint->id }}</td>
                            <td style="border-bottom: 1px solid var(--bd);">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        {{ strtoupper(substr($complaint->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.9rem; color: var(--tx);">{{ $complaint->user->name ?? 'Unknown User' }}</div>
                                        <div class="small" style="color: var(--tx3);">{{ $complaint->user->email ?? 'No email' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="border-bottom: 1px solid var(--bd);">
                                <span class="badge" style="background: var(--danger-bg); color: var(--danger-tx); border: 1px solid var(--danger-tx);">{{ $complaint->reason }}</span>
                            </td>
                            <td style="border-bottom: 1px solid var(--bd);">
                                <div class="complaint-notes" title="{{ $complaint->notes }}">
                                    {{ $complaint->notes ?? 'N/A' }}
                                </div>
                            </td>
                            <td style="border-bottom: 1px solid var(--bd);">{{ $complaint->created_at->format('M d, Y') }}</td>
                            <td style="border-bottom: 1px solid var(--bd);">
                                @if($complaint->status == 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($complaint->status == 'investigated')
                                    <span class="badge bg-info text-white">Investigating</span>
                                @else
                                    <span class="badge bg-success">Resolved</span>
                                @endif
                            </td>
                            <td style="border-bottom: 1px solid var(--bd);">
                                <a href="{{ route('admin.feedback.show', $complaint->interview_answer_id) }}" class="btn btn-sm btn-dark" style="border-radius: 6px;">Investigate</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 complaints-empty" style="color: var(--tx3); border-bottom: 1px solid var(--bd);">
                                <i class="fa-regular fa-face-smile-beam fa-3x mb-3 text-success opacity-50"></i>
                                <h5 style="color: var(--tx);">No Complaints Found</h5>
                                <p>Great job! There are no user complaints regarding AI feedback at the moment.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $complaints->links('pagination::bootstrap-5') }}
            </div>
    </div>
</div>
@endsection

