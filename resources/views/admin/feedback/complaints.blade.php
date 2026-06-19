@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="font-weight: 700; color: var(--tx1);">User Feedback Complaints</h2>
            <p class="text-muted mb-0">Manage and investigate feedback reported by users.</p>
        </div>
    </div>

    <!-- Alert for Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card boc" style="border-radius: 16px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Complaint ID</th>
                            <th>User</th>
                            <th>Reason</th>
                            <th>Notes</th>
                            <th>Reported Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($complaints as $complaint)
                        <tr>
                            <td>#{{ $complaint->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        {{ strtoupper(substr($complaint->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.9rem;">{{ $complaint->user->name ?? 'Unknown User' }}</div>
                                        <div class="text-muted small">{{ $complaint->user->email ?? 'No email' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">{{ $complaint->reason }}</span>
                            </td>
                            <td>
                                <div class="text-truncate text-muted" style="max-width: 200px;" title="{{ $complaint->notes }}">
                                    {{ $complaint->notes ?? 'N/A' }}
                                </div>
                            </td>
                            <td>{{ $complaint->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($complaint->status == 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($complaint->status == 'investigated')
                                    <span class="badge bg-info text-white">Investigating</span>
                                @else
                                    <span class="badge bg-success">Resolved</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.feedback.show', $complaint->interview_answer_id) }}" class="btn btn-sm btn-dark" style="border-radius: 6px;">Investigate</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-regular fa-face-smile-beam fa-3x mb-3 text-success opacity-50"></i>
                                <h5>No Complaints Found</h5>
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
</div>
@endsection
