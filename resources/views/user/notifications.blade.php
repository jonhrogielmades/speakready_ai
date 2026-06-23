@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="color:var(--tx);font-weight:700">Notifications</h4>
            <p style="color:var(--tx3)">Stay updated on your progress, system updates, and activities.</p>
        </div>
        @if(count($notifications) > 0)
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" style="border-radius:8px" onclick="markAllReadPage()">Mark all as read</button>
            <button class="btn btn-outline-danger btn-sm" style="border-radius:8px" onclick="clearAllNotificationsPage()">Clear all</button>
        </div>
        @endif
    </div>

    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;overflow:hidden" id="notificationsPageList">
        @forelse($notifications as $notification)
        @php
            $isRead = !is_null($notification->read_at);
            $bg = $isRead ? 'transparent' : 'rgba(59,130,246,0.03)';
            $icon = $notification->data['icon'] ?? 'fa-bell';
            $typeClass = $notification->data['type'] ?? 'info';
            // Map types to colors
            $colors = [
                'info' => ['bg' => 'rgba(59,130,246,.15)', 'text' => '#60a5fa'],
                'success' => ['bg' => 'rgba(52,211,153,.15)', 'text' => '#34d399'],
                'warning' => ['bg' => 'rgba(245,158,11,.15)', 'text' => '#fbbf24'],
                'error' => ['bg' => 'rgba(248,113,113,.15)', 'text' => '#f87171'],
            ];
            $colorSettings = $colors[$typeClass] ?? $colors['info'];
        @endphp
        <div class="notification-row p-3 p-md-4" style="border-bottom:1px solid var(--bd);background:{{ $bg }};display:flex;align-items:flex-start;position:relative" id="notif-{{ $notification->id }}">
            <div style="width:48px;height:48px;background:{{ $colorSettings['bg'] }};color:{{ $colorSettings['text'] }};border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-right:16px;flex-shrink:0">
                <i class="fa-solid {{ $icon }}"></i>
            </div>
            <div style="flex-grow:1;min-width:0;">
                <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center mb-1 gap-1">
                    <h6 style="color:var(--tx);margin:0;font-weight:{{ $isRead ? '600' : '700' }};word-wrap:break-word;white-space:normal;">
                        {{ $notification->data['title'] ?? 'Notification' }}
                        @if(!$isRead)
                        <span class="badge bg-primary ms-2" style="font-size:.65rem">NEW</span>
                        @endif
                    </h6>
                    <span style="font-size:.75rem;color:var(--tx3)">{{ $notification->created_at->diffForHumans() }}</span>
                </div>
                <p style="color:var(--tx3);font-size:.9rem;margin-bottom:8px;word-wrap:break-word;white-space:normal;">{{ $notification->data['message'] ?? '' }}</p>
                <div class="d-flex gap-3">
                    @if(!$isRead)
                    <button class="btn btn-sm btn-link text-decoration-none p-0" onclick="markRead('{{ $notification->id }}')" style="font-size:0.8rem;color:var(--pur)">Mark as read</button>
                    @endif
                    <button class="btn btn-sm btn-link text-decoration-none text-danger p-0" onclick="deleteNotification('{{ $notification->id }}')" style="font-size:0.8rem;">Delete</button>
                </div>
            </div>
        </div>
        @empty
        <div style="padding:40px; text-align:center; color:var(--tx3)">
            <i class="fa-regular fa-bell-slash mb-3" style="font-size:2rem; opacity:0.5"></i>
            <p>You have no notifications at the moment.</p>
        </div>
        @endforelse
    </div>
    
    <div class="mt-4 d-flex justify-content-center">
        {{ $notifications->links() }}
    </div>
</div>

@push('scripts')
<script>
function reloadNotificationsPage() {
    window.location.reload();
}

function markAllReadPage() {
    fetch('/notifications/read-all', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            window.location.reload();
        }
    });
}

function clearAllNotificationsPage() {
    if(confirm('Are you sure you want to clear all notifications?')) {
        fetch('/notifications/clear-all', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                window.location.reload();
            }
        });
    }
}

function markRead(id) {
    fetch('/notifications/' + id + '/read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            window.location.reload(); // simple reload for consistency
        }
    });
}

function deleteNotification(id) {
    if(confirm('Are you sure you want to delete this notification?')) {
        fetch('/notifications/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                window.location.reload();
            }
        });
    }
}
</script>
@endpush
@endsection
