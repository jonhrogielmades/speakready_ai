@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<style>
    .premium-card {
        background: var(--sf, #1e1e2d);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    /* Mobile Card-based Table Layout for Main Archive Table */
    @media (max-width: 767px) {
        #mainArchiveTableWrapper {
            overflow-x: visible !important;
            -webkit-overflow-scrolling: auto !important;
        }
        #mainArchiveTable thead {
            display: none;
        }
        #mainArchiveTable tbody tr {
            display: flex;
            flex-direction: column;
            background: var(--bg3, rgba(255,255,255,0.02));
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--bd, rgba(255,255,255,0.1));
            padding: 12px;
        }
        #mainArchiveTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-top: none !important;
            text-align: right;
        }
        #mainArchiveTable tbody td:last-child {
            border-bottom: none !important;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px !important;
        }
        #mainArchiveTable tbody td::before {
            font-size: 0.8rem;
            color: var(--tx3, #888);
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
            text-align: left;
        }
        #mainArchiveTable tbody td:nth-child(1)::before { content: "ID"; }
        #mainArchiveTable tbody td:nth-child(3)::before { content: "Category"; }
        #mainArchiveTable tbody td:nth-child(4)::before { content: "Archived Date"; }
        
        #mainArchiveTable tbody td:nth-child(2) {
            order: -1;
            justify-content: flex-start;
            border-bottom: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
            padding-bottom: 12px !important;
            margin-bottom: 4px;
            text-align: left;
            flex-direction: column;
            align-items: flex-start;
        }
        #mainArchiveTable tbody td:nth-child(2)::before { content: none; }
    }
</style>

<div class="db-section active">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.sessions.index') }}" class="text-decoration-none" style="color:var(--tx2);font-size:0.9rem;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <h4 class="fw-bold mb-1 mt-2"><i class="fa-solid fa-box-archive text-warning me-2"></i>Archived Sessions</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Historical records of interview sessions.</p>
        </div>
    </div>

    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="premium-card mb-4">
        <form method="GET" action="{{ route('admin.sessions.archive') }}" class="row g-2 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search user or Session ID..." value="{{ request('search') }}" style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-warning w-100" style="border-radius:8px;">Search Archive</button>
            </div>
        </form>

        <div class="table-responsive" id="mainArchiveTableWrapper">
            <table class="table custom-table mb-0 w-100" id="mainArchiveTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Category</th>
                        <th>Archived Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    <tr>
                        <td>#{{ $session->id }}</td>
                        <td>{{ $session->user ? $session->user->name : 'Deleted User' }}</td>
                        <td>{{ $session->category ? $session->category->title : 'N/A' }}</td>
                        <td style="color:var(--tx2);">{{ $session->updated_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            <form action="{{ route('admin.sessions.restore', $session->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to restore this session?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success" style="border-radius:8px;" title="Restore">
                                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Restore
                                </button>
                            </form>
                            <form action="{{ route('admin.sessions.destroy', $session->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this archived session? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:8px;" title="Delete">
                                    <i class="fa-solid fa-trash-can me-1"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No archived sessions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $sessions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

