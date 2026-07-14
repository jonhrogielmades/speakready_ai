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
    .notification-icon-box {
        width:48px;
        height:48px;
        border-radius:12px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:1.2rem;
        margin-right:16px;
        flex-shrink:0;
    }
    .notification-meta {
        font-size:.75rem;
        color:var(--tx3);
        white-space:nowrap;
    }
    .notification-status-badge {
        font-size:.65rem;
        letter-spacing:0;
        border-radius:999px;
        padding:4px 8px;
    }
    .notification-actions {
        display:flex;
        gap:12px;
        flex-wrap:wrap;
    }
    .notification-action-btn {
        border:0;
        background:transparent;
        padding:0;
        min-height:30px;
        display:inline-flex;
        align-items:center;
        gap:6px;
        font-size:0.8rem;
        font-weight:700;
        line-height:1;
    }
    .notification-action-btn.read {
        color:var(--pur);
    }
    .notification-action-btn.delete {
        color:#f87171;
    }
    .notifications-empty-state {
        padding:42px 22px;
        text-align:center;
        color:var(--tx3);
    }
    .notifications-empty-icon {
        width:64px;
        height:64px;
        margin:0 auto 14px;
        border-radius:20px;
        display:flex;
        align-items:center;
        justify-content:center;
        background:rgba(96,165,250,0.12);
        color:#60a5fa;
        font-size:1.55rem;
    }
    #notifications-page .notifications-pagination {
        margin-top: 14px !important;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    #notifications-page .notifications-pagination nav {
        width: auto;
        max-width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    #notifications-page .notifications-pagination .pagination {
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
        align-items: center;
    }
    #notifications-page .notifications-pagination .page-item {
        margin: 0 !important;
    }
    #notifications-page .notifications-pagination .page-link,
    #notifications-page .notifications-pagination span.page-link {
        width: auto !important;
        min-width: 38px !important;
        height: 38px !important;
        min-height: 38px !important;
        padding: 0 12px !important;
        border-radius: 10px !important;
        border: 1px solid var(--bd) !important;
        background: var(--sf) !important;
        color: var(--tx) !important;
        font-size: 0.84rem !important;
        line-height: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: none !important;
    }
    #notifications-page .notifications-pagination .page-item.active .page-link {
        background: var(--pur) !important;
        border-color: var(--pur) !important;
        color: #fff !important;
    }
    #notifications-page .notifications-pagination .page-item.disabled .page-link {
        opacity: 0.55;
    }
    #notifications-page .notifications-pagination svg,
    #notifications-page .notifications-pagination .page-link svg {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }
    #notifications-page .notifications-pagination p,
    #notifications-page .notifications-pagination .text-sm {
        width: 100%;
        margin: 0 !important;
        text-align: center;
        color: var(--tx3) !important;
        font-size: 0.78rem !important;
        line-height: 1.35 !important;
        position: static !important;
    }
    @media (max-width: 767px) {
        #notifications-page {
            --notif-mobile-radius: 16px;
        }
        #notifications-page .sr-page-actions {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 10px !important;
            margin-bottom: 14px !important;
        }
        #notifications-page .sr-page-actions .btn {
            width: 100%;
            min-height: 44px;
            border-radius: 13px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 11px;
            font-size: 0.78rem;
            line-height: 1.15;
            white-space: normal;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }
        #notifications-page .premium-panel {
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            display: grid;
            gap: 10px;
            overflow: visible;
            padding: 0 !important;
        }
        #notifications-page .notification-row {
            padding: 13px !important;
            border: 1px solid var(--bd) !important;
            border-radius: var(--notif-mobile-radius);
            background: var(--sf) !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            align-items: stretch !important;
            gap: 11px;
            overflow: hidden;
        }
        #notifications-page .notification-row:hover {
            transform: none;
        }
        #notifications-page .notification-row.is-unread {
            border-color: rgba(96,165,250,0.32) !important;
            background: linear-gradient(135deg, rgba(96,165,250,0.13), rgba(255,255,255,0.03)) !important;
        }
        #notifications-page .notification-icon-box {
            width: 42px !important;
            height: 42px !important;
            border-radius: 13px !important;
            font-size: 1rem !important;
            margin-right: 0 !important;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
        }
        #notifications-page .notification-row h6 {
            font-size: 0.9rem;
            line-height: 1.3;
            display: flex;
            align-items: flex-start;
            gap: 6px;
            flex-wrap: wrap;
        }
        #notifications-page .notification-status-badge {
            margin-left: 0 !important;
            padding: 4px 7px;
            font-size: 0.62rem;
        }
        #notifications-page .notification-row p {
            font-size: 0.79rem !important;
            line-height: 1.48;
            margin-bottom: 10px !important;
        }
        #notifications-page .notification-meta {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            width: fit-content;
            max-width: 100%;
            margin-top: 4px;
            padding: 4px 8px;
            border: 1px solid var(--bd);
            border-radius: 999px;
            font-size: 0.66rem !important;
            white-space: normal;
            line-height: 1.25;
            background: rgba(255,255,255,0.035);
        }
        #notifications-page .notification-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px !important;
            margin-top: 8px;
        }
        #notifications-page .notification-actions > :only-child {
            grid-column: 1 / -1;
        }
        #notifications-page .notification-action-btn {
            width: 100%;
            min-height: 38px;
            justify-content: center;
            border: 1px solid var(--bd2);
            border-radius: 12px;
            background: rgba(255,255,255,0.035);
            padding: 9px 10px;
            font-size: 0.75rem;
        }
        #notifications-page .notification-action-btn.read {
            border-color: rgba(96,165,250,0.28);
            background: rgba(96,165,250,0.1);
            color:#60a5fa;
        }
        #notifications-page .notification-action-btn.delete {
            border-color: rgba(248,113,113,0.28);
            background: rgba(248,113,113,0.08);
        }
        #notifications-page .notifications-empty-state {
            border: 1px solid var(--bd);
            border-radius: var(--notif-mobile-radius);
            background: var(--sf);
            padding: 34px 18px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        #notifications-page .pagination {
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
        }
        #notifications-page .page-link {
            min-width: 38px;
            min-height: 38px;
            border-radius: 11px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    }
</style>
@include('partials.page-hero-styles')

<div class="db-section active animate-fade-up" id="notifications-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M10 21h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Notifications
                </h4>
                <p class="sr-page-hero-subtitle">Stay updated on your progress, system updates, and activities.</p>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="notifPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="notifBlue" x1="66" y1="36" x2="166" y2="118"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#notifPanel)" stroke="#BFDBFE" stroke-width="3"/><path d="M110 40a30 30 0 0 0-30 30c0 29-15 27-15 39h90c0-12-15-10-15-39a30 30 0 0 0-30-30Z" fill="url(#notifBlue)"/><path d="M96 113a14 14 0 0 0 28 0" fill="none" stroke="#2563EB" stroke-width="7" stroke-linecap="round"/><circle cx="155" cy="48" r="18" fill="#EF4444"/><path d="M155 40v11" stroke="#fff" stroke-width="5" stroke-linecap="round"/><circle cx="155" cy="58" r="2.5" fill="#fff"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    @if(count($notifications) > 0)
    <div class="sr-page-actions">
        <button class="btn btn-outline-primary btn-sm btn-shine" style="border-radius:12px;font-weight:600;" onclick="markAllReadPage()"><i class="fa-solid fa-check-double"></i>Mark all as read</button>
        <button class="btn btn-outline-danger btn-sm btn-shine" style="border-radius:12px;font-weight:600;" onclick="clearAllNotificationsPage()"><i class="fa-solid fa-trash-can"></i>Clear all</button>
    </div>
    @endif

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
        <div class="notification-row {{ !$isRead ? 'is-unread' : '' }} p-3 p-md-4" style="border-bottom:1px solid var(--bd);background:{{ $bg }};display:flex;align-items:flex-start;position:relative" id="notif-{{ $notification->id }}">
            <div class="notification-icon-box" style="background:{{ $colorSettings['bg'] }};color:{{ $colorSettings['text'] }};">
                <i class="fa-solid {{ $icon }}"></i>
            </div>
            <div style="flex-grow:1;min-width:0;">
                <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center mb-1 gap-1">
                    <h6 style="color:var(--tx);margin:0;font-weight:{{ $isRead ? '600' : '700' }};word-wrap:break-word;white-space:normal;">
                        {{ $notification->data['title'] ?? 'Notification' }}
                        @if(!$isRead)
                        <span class="badge bg-primary ms-2 notification-status-badge">NEW</span>
                        @endif
                    </h6>
                    <span class="notification-meta"><i class="fa-regular fa-clock"></i>{{ $notification->created_at->diffForHumans() }}</span>
                </div>
                <p style="color:var(--tx3);font-size:.9rem;margin-bottom:8px;word-wrap:break-word;white-space:normal;">{{ $notification->data['message'] ?? '' }}</p>
                <div class="notification-actions">
                    @if(!$isRead)
                    <button class="notification-action-btn read" onclick="markRead('{{ $notification->id }}')"><i class="fa-solid fa-check"></i>Mark as read</button>
                    @endif
                    <button class="notification-action-btn delete" onclick="deleteNotification('{{ $notification->id }}')"><i class="fa-solid fa-trash"></i>Delete</button>
                </div>
            </div>
        </div>
        @empty
        <div class="notifications-empty-state">
            <div class="notifications-empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
            <p class="mb-0">You have no notifications at the moment.</p>
        </div>
        @endforelse
    </div>
    
    <div class="notifications-pagination mt-4 d-flex justify-content-center">
        {{ $notifications->links('pagination::bootstrap-5') }}
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


