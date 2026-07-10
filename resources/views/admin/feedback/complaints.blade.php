@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<style>
    #sec-admin-complaints {
        max-width: 100%;
    }
    #sec-admin-complaints .complaints-panel {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 8px;
        box-shadow: 0 14px 36px rgba(2, 6, 23, 0.12);
        padding: 20px;
    }
    #mainComplaintsTable {
        table-layout: fixed;
        width: 100%;
        min-width: 0;
        font-size: 0.86rem;
    }
    #mainComplaintsTable th,
    #mainComplaintsTable td {
        vertical-align: middle;
    }
    #mainComplaintsTable th:nth-child(1),
    #mainComplaintsTable td:nth-child(1) { width: 10%; }
    #mainComplaintsTable th:nth-child(2),
    #mainComplaintsTable td:nth-child(2) { width: 23%; }
    #mainComplaintsTable th:nth-child(3),
    #mainComplaintsTable td:nth-child(3) { width: 14%; }
    #mainComplaintsTable th:nth-child(4),
    #mainComplaintsTable td:nth-child(4) {
        width: 24%;
        white-space: normal !important;
    }
    #mainComplaintsTable th:nth-child(5),
    #mainComplaintsTable td:nth-child(5) { width: 13%; }
    #mainComplaintsTable th:nth-child(6),
    #mainComplaintsTable td:nth-child(6) { width: 10%; }
    #mainComplaintsTable th:nth-child(7),
    #mainComplaintsTable td:nth-child(7) { width: 10%; }
    #mainComplaintsTableWrapper {
        overflow-x: hidden !important;
    }
    #mainComplaintsTable .complaint-notes {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal !important;
        overflow-wrap: anywhere;
        color: var(--tx3);
    }
    #mainComplaintsTable .complaints-empty {
        white-space: normal !important;
        overflow: hidden;
    }
    #mainComplaintsTable .complaints-empty p {
        max-width: 620px;
        margin: 0 auto;
        white-space: normal !important;
        overflow-wrap: anywhere;
    }
    @media (max-width: 1199.98px) {
        #mainComplaintsTable {
            font-size: 0.78rem;
        }
        #mainComplaintsTable th,
        #mainComplaintsTable td {
            padding-left: 6px !important;
            padding-right: 6px !important;
        }
        #mainComplaintsTable .btn-sm {
            min-height: 30px;
            padding: 0.3rem 0.52rem;
            font-size: 0.7rem;
        }
        #mainComplaintsTable .badge {
            font-size: 0.68rem;
            padding-inline: 0.45rem;
        }
    }
    /* Mobile Card-based Table Layout for Main Complaints Table */
    @media (max-width: 767px) {
        #mainComplaintsTableWrapper {
            overflow-x: visible !important;
            -webkit-overflow-scrolling: auto !important;
        }
        #mainComplaintsTable thead {
            display: none;
        }
        #mainComplaintsTable tbody tr {
            display: flex;
            flex-direction: column;
            background: var(--bg3, rgba(255,255,255,0.02));
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--bd, rgba(255,255,255,0.1));
            padding: 12px;
        }
        #mainComplaintsTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-top: none !important;
            text-align: right;
        }
        #mainComplaintsTable tbody td:last-child {
            border-bottom: none !important;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px !important;
        }
        #mainComplaintsTable tbody td::before {
            font-size: 0.8rem;
            color: var(--tx3, #888);
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
            text-align: left;
        }
        #mainComplaintsTable tbody td:nth-child(1)::before { content: "Complaint ID"; }
        #mainComplaintsTable tbody td:nth-child(3)::before { content: "Reason"; }
        #mainComplaintsTable tbody td:nth-child(4)::before { content: "Notes"; }
        #mainComplaintsTable tbody td:nth-child(5)::before { content: "Reported Date"; }
        #mainComplaintsTable tbody td:nth-child(6)::before { content: "Status"; }
        
        #mainComplaintsTable tbody td:nth-child(2) {
            order: -1;
            justify-content: flex-start;
            border-bottom: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
            padding-bottom: 12px !important;
            margin-bottom: 4px;
            text-align: left;
        }
        #mainComplaintsTable tbody td:nth-child(2)::before { content: none; }
        
        #mainComplaintsTable tbody td:nth-child(4) {
            flex-direction: column;
            align-items: flex-start;
        }
        #mainComplaintsTable tbody td:nth-child(4) .text-truncate {
            max-width: 100% !important;
            white-space: normal;
            text-align: left;
        }
        #mainComplaintsTable {
            table-layout: auto;
        }
        #mainComplaintsTable th,
        #mainComplaintsTable td {
            width: auto !important;
        }
    }
</style>
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

