@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<style>
    /* Admin Premium Styles */
    .premium-card {
        background: var(--sf, #1e1e2d);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .premium-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    .stat-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.8rem;
    }
    .stat-badge.success { background: rgba(52, 211, 153, 0.15); color: #34d399; }
    .stat-badge.warning { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .stat-badge.danger { background: rgba(248, 113, 113, 0.15); color: #f87171; }
    .stat-badge.info { background: rgba(96, 165, 250, 0.15); color: #60a5fa; }
    
    .form-control, .form-select {
        background: var(--bg3, #2b2b40);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        color: var(--tx, #e0e0e0);
        border-radius: 12px;
        padding: 12px 16px;
    }
    .form-control:focus, .form-select:focus {
        background: var(--bg3, #2b2b40);
        border-color: #3b82f6;
        color: var(--tx, #e0e0e0);
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
    }
    .btn-premium {
        background: #3b82f6;
        color: #fff;
        border: none;
        border-radius: 12px;
        padding: 10px 20px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-premium:hover {
        background: #2563eb;
        transform: translateY(-2px);
    }
    
    /* Mobile Responsive Tables */
    @media (max-width: 767.98px) {
    }
</style>

<div class="db-section active" id="sec-admin-notifications">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif
    
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#f87171">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-bullhorn me-2" style="color:#3b82f6;"></i>Notifications</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Broadcast announcements and send alerts to users.</p>
        </div>
        <div>
            <button type="button" class="btn-premium" data-bs-toggle="modal" data-bs-target="#sendNotificationModal">
                <i class="fa-solid fa-paper-plane me-2"></i>Send Notification
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="premium-card text-center p-4 h-100">
                <div style="font-size:1.5rem;color:#3b82f6;margin-bottom:8px;"><i class="fa-solid fa-tower-broadcast"></i></div>
                <div style="font-size:2rem;font-weight:700;">{{ $announcements->where('target', 'all')->count() }}</div>
                <div style="font-size:0.85rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Global Broadcasts</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="premium-card text-center p-4 h-100">
                <div style="font-size:1.5rem;color:#34d399;margin-bottom:8px;"><i class="fa-solid fa-user-tag"></i></div>
                <div style="font-size:2rem;font-weight:700;">{{ $announcements->where('target', 'specific')->count() }}</div>
                <div style="font-size:0.85rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Direct Alerts</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="premium-card text-center p-4 h-100">
                <div style="font-size:1.5rem;color:#f472b6;margin-bottom:8px;"><i class="fa-solid fa-envelope-open-text"></i></div>
                <div style="font-size:2rem;font-weight:700;">{{ $announcements->count() }}</div>
                <div style="font-size:0.85rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Total Sent</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-4">Broadcast History</h6>
                <div class="table-responsive">
                    <table class="table custom-table mb-0 w-100">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Target</th>
                                <th>Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($announcements as $announcement)
                            <tr>
                                <td data-label="Title"><span class="fw-bold">{{ $announcement->title }}</span></td>
                                <td data-label="Message" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $announcement->message }}
                                </td>
                                <td data-label="Type">
                                    <span class="stat-badge {{ $announcement->type == 'info' ? 'info' : ($announcement->type == 'success' ? 'success' : ($announcement->type == 'warning' ? 'warning' : 'danger')) }}">
                                        {{ ucfirst($announcement->type) }}
                                    </span>
                                </td>
                                <td data-label="Target">
                                    @if($announcement->target === 'all')
                                        <span class="stat-badge info"><i class="fa-solid fa-globe me-1"></i>All Users</span>
                                    @else
                                        <span class="stat-badge" style="background:rgba(139,92,246,0.15);color:#8b5cf6;"><i class="fa-solid fa-user me-1"></i>{{ $announcement->user->name ?? 'Unknown User' }}</span>
                                    @endif
                                </td>
                                <td data-label="Date" style="color:var(--tx2);">{{ $announcement->created_at->format('M d, Y h:i A') }}</td>
                                <td data-label="Actions" class="text-end">
                                    <form action="{{ route('admin.notifications.destroy', $announcement->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this record? Note: This will not delete the actual notifications already delivered to users.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm" style="background:rgba(248,113,113,0.1);color:#f87171;border:1px solid rgba(248,113,113,0.2);" title="Delete Record"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No notifications have been sent yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 d-flex justify-content-center">
                    {{ $announcements->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Notification Modal -->
<div class="modal fade" id="sendNotificationModal" tabindex="-1" aria-labelledby="sendNotificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--sf, #1e1e2d); border: 1px solid var(--bd, rgba(255,255,255,0.1)); border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="sendNotificationModalLabel">New Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <form action="{{ route('admin.notifications.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--tx2);font-size:0.9rem;">Notification Title</label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g. System Maintenance Update">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--tx2);font-size:0.9rem;">Message Content</label>
                        <textarea class="form-control" name="message" rows="4" required placeholder="Write your announcement here..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" style="color:var(--tx2);font-size:0.9rem;">Alert Type</label>
                            <select class="form-select" name="type" required>
                                <option value="info">Info (Blue)</option>
                                <option value="success">Success (Green)</option>
                                <option value="warning">Warning (Yellow)</option>
                                <option value="danger">Danger (Red)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label" style="color:var(--tx2);font-size:0.9rem;">Target Audience</label>
                            <select class="form-select" name="target" id="targetSelect" required onchange="toggleUserSelect()">
                                <option value="all">All Users (Broadcast)</option>
                                <option value="specific">Specific User</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="userSelectWrapper" style="display:none;">
                        <label class="form-label" style="color:var(--tx2);font-size:0.9rem;">Select User</label>
                        <select class="form-select" name="user_id" id="userSelect">
                            <option value="">-- Choose User --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn" data-bs-dismiss="modal" style="background:var(--bg3);color:var(--tx);border-radius:12px;">Cancel</button>
                    <button type="submit" class="btn-premium"><i class="fa-solid fa-paper-plane me-2"></i>Send Now</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleUserSelect() {
    const targetSelect = document.getElementById('targetSelect');
    const userSelectWrapper = document.getElementById('userSelectWrapper');
    const userSelect = document.getElementById('userSelect');
    
    if (targetSelect.value === 'specific') {
        userSelectWrapper.style.display = 'block';
        userSelect.required = true;
    } else {
        userSelectWrapper.style.display = 'none';
        userSelect.required = false;
        userSelect.value = '';
    }
}
</script>
@endsection

