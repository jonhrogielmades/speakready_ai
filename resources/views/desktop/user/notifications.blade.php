@extends('desktop.layouts.app')
@section('title', 'Notifications')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/notifications.css?v=6') }}" data-page-style="user-notifications">
@endpush

@section('content')

<div class="db-section active animate-fade-up" id="notifications-page" data-notifications-url="{{ url('/notifications') }}">
    <div class="notif-hero">
        <div class="notif-hero-copy">
            <div class="notif-hero-icon"><i class="fa-regular fa-bell"></i></div>
            <div>
                <h4 class="notif-hero-title">Notifications</h4>
                <p class="notif-hero-subtitle">Track progress updates, alerts, and activity reminders.</p>
            </div>
        </div>
        <svg class="notif-hero-art" viewBox="0 0 180 160" aria-hidden="true">
            <defs>
                <linearGradient id="notifCard" x1="24" y1="18" x2="150" y2="138"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#F8FAFC"/></linearGradient>
                <linearGradient id="notifBell" x1="64" y1="42" x2="130" y2="118"><stop stop-color="#60A5FA"/><stop offset="1" stop-color="#1455F5"/></linearGradient>
            </defs>
            <path d="M42 24h82c22 0 36 15 32 37l-10 55c-4 20-19 31-40 29l-70-6c-20-2-31-17-27-37l10-48c4-19 15-30 23-30Z" fill="url(#notifCard)" stroke="#BFDBFE" stroke-width="4"/>
            <path d="M94 44c-17 0-31 14-31 31 0 25-13 25-13 35h87c0-10-13-10-13-35 0-17-13-31-30-31Z" fill="url(#notifBell)"/>
            <path d="M76 109c8 14 28 17 40 3" fill="none" stroke="#1D4ED8" stroke-width="8" stroke-linecap="round"/>
            <circle cx="136" cy="55" r="22" fill="#EF4444"/>
            <path d="M136 44v15" stroke="#fff" stroke-width="7" stroke-linecap="round"/>
            <circle cx="136" cy="68" r="3.4" fill="#fff"/>
            <path d="M43 38l-8-12M31 54l-13-4M44 67l-11 7" fill="none" stroke="#60A5FA" stroke-width="5" stroke-linecap="round" opacity=".7"/>
        </svg>
    </div>
    <div class="notif-page-alert" id="notificationActionStatus" role="status" aria-live="polite" hidden></div>

    @if(count($notifications) > 0)
    <div class="notif-bulk-actions">
        <button class="notif-bulk-btn" type="button" data-read-all-url="{{ route('user.notifications.readAll') }}" onclick="markAllReadPage()"><i class="fa-solid fa-check"></i><span>Mark all as read</span></button>
        <button class="notif-bulk-btn danger" type="button" data-clear-all-url="{{ route('user.notifications.clearAll') }}" onclick="clearAllNotificationsPage()"><i class="fa-solid fa-trash-can"></i><span>Clear all</span></button>
    </div>
    @endif

    <div class="premium-panel notifications-list-panel animate-fade-up" style="animation-delay: 0.2s; width: 100% !important; min-width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; box-sizing: border-box !important;" id="notificationsPageList">
        @forelse($notifications as $notification)
        @php
            $notificationData = is_array($notification->data) ? $notification->data : [];
            $isRead = !is_null($notification->read_at);
            $icon = $notificationData['icon'] ?? 'fa-bell';
            $typeClass = $notificationData['type'] ?? 'info';
            // Map types to colors
            $colors = [
                'info' => ['bg' => 'rgba(59,130,246,.15)', 'text' => '#60a5fa'],
                'success' => ['bg' => 'rgba(52,211,153,.15)', 'text' => '#34d399'],
                'warning' => ['bg' => 'rgba(245,158,11,.15)', 'text' => '#fbbf24'],
                'error' => ['bg' => 'rgba(248,113,113,.15)', 'text' => '#f87171'],
            ];
            $colorSettings = $colors[$typeClass] ?? $colors['info'];
        @endphp
        <div class="notification-row {{ !$isRead ? 'is-unread' : '' }}" id="notif-{{ $notification->id }}">
            <div class="notification-icon-box" style="background:{{ $colorSettings['bg'] }};color:{{ $colorSettings['text'] }};">
                <i class="fa-solid {{ $icon }}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-head">
                    <h6 class="notification-title" style="font-weight:{{ $isRead ? '700' : '800' }};">
                        {{ $notificationData['title'] ?? 'Notification' }}
                    </h6>
                    @if(!$isRead)
                    <span class="badge notification-status-badge">NEW</span>
                    @endif
                    <span class="notification-meta"><i class="fa-regular fa-clock"></i>{{ $notification->created_at->diffForHumans() }}</span>
                </div>
                <p class="notification-message">{{ $notificationData['message'] ?? '' }}</p>
                <div class="notification-actions">
                    @if(!$isRead)
                    <button class="notification-action-btn read" data-notification-id="{{ $notification->id }}" data-read-url="{{ route('user.notifications.read', $notification->id) }}" onclick="markRead('{{ $notification->id }}')"><i class="fa-solid fa-check"></i>Mark as read</button>
                    @endif
                    <button class="notification-action-btn delete" data-notification-id="{{ $notification->id }}" data-delete-url="{{ route('user.notifications.delete', $notification->id) }}" onclick="deleteNotification('{{ $notification->id }}')"><i class="fa-solid fa-trash"></i>Delete</button>
                </div>
            </div>
        </div>
        @empty
        <div class="notifications-empty-state notifications-empty-state-wide" style="width: 100% !important; min-width: 100% !important; max-width: none !important; margin: 0 !important; box-sizing: border-box !important;">
            <div class="notifications-empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
            <p class="mb-0">You have no notifications at the moment.</p>
        </div>
        @endforelse
    </div>
    
    <div class="notifications-pagination mt-4 d-flex justify-content-center">
        {{ $notifications->links('pagination::bootstrap-5') }}
    </div>

    <div class="activity-history-section animate-fade-up" style="animation-delay: 0.28s;">
        <div class="activity-history-header">
            <div class="activity-history-heading">
                <div class="activity-history-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div>
                    <h5 class="activity-history-title">Activity history</h5>
                    <p class="activity-history-subtitle">Every account activity recorded for you.</p>
                </div>
            </div>
            <div class="activity-history-header-meta">
                <span class="activity-history-count">{{ number_format($activityCount) }} total</span>
                @if($activityCount > 0)
                <button class="activity-history-action-btn clear" type="button" data-clear-activities-url="{{ route('user.activities.clearAll') }}" onclick="clearAllActivitiesPage()"><i class="fa-solid fa-trash-can"></i><span>Clear all</span></button>
                @endif
            </div>
        </div>

        <div class="premium-panel activity-history-list">
            @forelse($activityLogs as $activity)
            @php
                $activityTitle = ucwords(str_replace('_', ' ', $activity->action));
            @endphp
            <div class="activity-history-row" id="activity-{{ $activity->id }}">
                <div class="activity-history-row-icon"><i class="fa-solid fa-list-check"></i></div>
                <div class="activity-history-row-content">
                    <div class="activity-history-row-head">
                        <h6 class="activity-history-row-title">{{ $activityTitle }}</h6>
                        <span class="activity-history-time"><i class="fa-regular fa-clock"></i>{{ $activity->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="activity-history-message">{{ $activity->description ?: 'Activity recorded.' }}</p>
                    <div class="activity-history-row-foot">
                        <span class="activity-history-date">{{ $activity->created_at->format('M d, Y h:i A') }}</span>
                        <button class="activity-history-action-btn delete" type="button" data-activity-id="{{ $activity->id }}" data-delete-activity-url="{{ route('user.activities.delete', $activity->id) }}" onclick="deleteActivityLog('{{ $activity->id }}')"><i class="fa-solid fa-trash"></i><span>Delete</span></button>
                    </div>
                </div>
            </div>
            @empty
            <div class="notifications-empty-state notifications-empty-state-wide">
                <div class="notifications-empty-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <p class="mb-0">No account activity has been recorded yet.</p>
            </div>
            @endforelse
        </div>

        <div class="notifications-pagination mt-4 d-flex justify-content-center">
            {{ $activityLogs->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function reloadNotificationsPage() {
    window.location.reload();
}

function notificationPageActionUrl(attribute, fallback) {
    const button = document.querySelector(`[${attribute}]`);
    return button?.getAttribute(attribute) || fallback;
}

function notificationRowActionUrl(id, attribute, suffix) {
    const selector = `[data-notification-id="${String(id).replace(/"/g, '\\"')}"][${attribute}]`;
    const button = document.querySelector(selector);
    const baseUrl = document.getElementById('notifications-page')?.dataset.notificationsUrl || @json(url('/notifications'));

    return button?.getAttribute(attribute) || `${baseUrl}/${encodeURIComponent(id)}${suffix}`;
}

function activityRowActionUrl(id, attribute, fallback) {
    const selector = `[data-activity-id="${String(id).replace(/"/g, '\\"')}"][${attribute}]`;
    const button = document.querySelector(selector);

    return button?.getAttribute(attribute) || fallback;
}

function showNotificationPageError(message) {
    const status = document.getElementById('notificationActionStatus');
    if (!status) return;
    status.hidden = false;
    status.classList.add('is-error');
    status.textContent = message || 'Action failed. Please try again.';
}

function clearNotificationPageError() {
    const status = document.getElementById('notificationActionStatus');
    if (!status) return;
    status.hidden = true;
    status.classList.remove('is-error');
    status.textContent = '';
}

function notificationJsonRequest(url, method) {
    clearNotificationPageError();

    return fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(async res => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            throw new Error(data.message || 'Action failed. Please try again.');
        }

        return data;
    })
    .catch(error => {
        showNotificationPageError(error.message);
        throw error;
    });
}

function markAllReadPage() {
    notificationJsonRequest(notificationPageActionUrl('data-read-all-url', @json(route('user.notifications.readAll'))), 'POST')
    .then(data => {
        if(data.success) {
            window.location.reload();
        }
    });
}

function clearAllNotificationsPage() {
    if(confirm('Are you sure you want to clear all notifications?')) {
        notificationJsonRequest(notificationPageActionUrl('data-clear-all-url', @json(route('user.notifications.clearAll'))), 'DELETE')
        .then(data => {
            if(data.success) {
                window.location.reload();
            }
        });
    }
}

function markRead(id) {
    notificationJsonRequest(notificationRowActionUrl(id, 'data-read-url', '/read'), 'POST')
    .then(data => {
        if(data.success) {
            window.location.reload(); // simple reload for consistency
        }
    });
}

function deleteNotification(id) {
    if(confirm('Are you sure you want to delete this notification?')) {
        notificationJsonRequest(notificationRowActionUrl(id, 'data-delete-url', ''), 'DELETE')
        .then(data => {
            if(data.success) {
                window.location.reload();
            }
        });
    }
}

function clearAllActivitiesPage() {
    if(confirm('Are you sure you want to clear all activity history?')) {
        notificationJsonRequest(notificationPageActionUrl('data-clear-activities-url', @json(route('user.activities.clearAll'))), 'DELETE')
        .then(data => {
            if(data.success) {
                window.location.reload();
            }
        });
    }
}

function deleteActivityLog(id) {
    if(confirm('Are you sure you want to delete this activity?')) {
        notificationJsonRequest(activityRowActionUrl(id, 'data-delete-activity-url', @json(url('/notifications/activities')).replace(/\/$/, '') + `/${encodeURIComponent(id)}`), 'DELETE')
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
