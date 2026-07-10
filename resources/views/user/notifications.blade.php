@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Notifications')

@section('content')
<style>
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    .premium-panel {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        overflow: hidden;
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
    
    .notification-row { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .notification-row:hover { background-color: rgba(255,255,255,0.03) !important; transform: translateX(5px); }
</style>

<div class="db-section active animate-fade-up">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-gradient-primary" style="font-weight:800;letter-spacing:-0.5px;margin-bottom:4px;"><i class="fa-regular fa-bell me-2"></i>Notifications</h4>
            <p style="color:var(--tx3)">Stay updated on your progress, system updates, and activities.</p>
        </div>
        @if(count($notifications) > 0)
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm btn-shine" style="border-radius:12px;font-weight:600;" onclick="markAllReadPage()">Mark all as read</button>
            <button class="btn btn-outline-danger btn-sm btn-shine" style="border-radius:12px;font-weight:600;" onclick="clearAllNotificationsPage()">Clear all</button>
        </div>
        @endif
    </div>

    <div class="premium-panel animate-fade-up" style="animation-delay: 0.2s;" id="notificationsPageList">
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


