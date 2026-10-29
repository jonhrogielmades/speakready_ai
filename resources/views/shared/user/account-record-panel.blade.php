@php
    $accountUser = Auth::user();
    $accountTimezone = 'Asia/Manila';
    $accountTimezoneLabel = 'PHT';
    $accountInPhilippineTime = static fn ($timestamp) => $timestamp ? $timestamp->copy()->timezone($accountTimezone) : null;

    $accountCreatedAt = $accountInPhilippineTime($accountUser?->created_at);
    $accountLastLoginAt = $accountInPhilippineTime($lastLoginAt ?? null);
    $accountEmailVerifiedAt = $accountInPhilippineTime($accountUser?->email_verified_at);
    $accountHasPendingRecoveryEmail = $accountUser?->isLocalAccount() && filled($accountUser?->pending_recovery_email);

    $accountCreatedDate = $accountCreatedAt?->format('M d, Y') ?? 'Not recorded';
    $accountCreatedTime = $accountCreatedAt ? $accountCreatedAt->format('h:i A').' '.$accountTimezoneLabel : 'Awaiting record';
    $accountLastLoginDate = $accountLastLoginAt?->format('M d, Y') ?? 'No login recorded';
    $accountLastLoginTime = $accountLastLoginAt ? $accountLastLoginAt->format('h:i A').' '.$accountTimezoneLabel : 'Sign in to update this record';
    $accountEmailStatus = $accountHasPendingRecoveryEmail ? 'Change pending' : ($accountEmailVerifiedAt ? 'Verified' : 'Pending');
    $accountEmailStatusMeta = $accountHasPendingRecoveryEmail
        ? 'Current address stays active until the replacement is verified'
        : ($accountEmailVerifiedAt
            ? 'Verified '.$accountEmailVerifiedAt->format('M d, Y')
            : 'Email verification not completed');
@endphp

<div class="premium-panel account-card account-record-card" style="padding:32px;margin-bottom:24px">
    <h5 class="account-card-title"><span class="account-title-icon"><i class="fa-solid fa-user-shield"></i></span>Account &amp; Privacy</h5>

    <div class="account-record-list" aria-label="Account record">
        <div class="account-record-item">
            <span class="account-record-icon"><i class="fa-regular fa-calendar-plus"></i></span>
            <span class="account-record-copy">
                <span class="account-record-label">Account Created</span>
                <strong>{{ $accountCreatedDate }}</strong>
                <small>{{ $accountCreatedTime }}</small>
            </span>
        </div>

        <div class="account-record-item">
            <span class="account-record-icon"><i class="fa-solid fa-right-to-bracket"></i></span>
            <span class="account-record-copy">
                <span class="account-record-label">Last Login</span>
                <strong>{{ $accountLastLoginDate }}</strong>
                <small>{{ $accountLastLoginTime }}</small>
            </span>
        </div>

        <div class="account-record-item{{ $accountHasPendingRecoveryEmail || (! $accountEmailVerifiedAt && filled($accountUser?->email)) ? ' account-record-action-item' : '' }}">
            <span class="account-record-icon"><i class="fa-regular fa-envelope"></i></span>
            <span class="account-record-copy">
                <span class="account-record-label">Email Status</span>
                <strong><span class="account-record-status {{ $accountEmailVerifiedAt && ! $accountHasPendingRecoveryEmail ? 'is-verified' : 'is-pending' }}">{{ $accountEmailStatus }}</span></strong>
                <small>{{ $accountEmailStatusMeta }}</small>
            </span>
            @if($accountHasPendingRecoveryEmail || (! $accountEmailVerifiedAt && filled($accountUser?->email)))
                <form method="POST" action="{{ route('verification.send') }}" class="account-record-action">
                    @csrf
                    <button type="submit" class="account-notification-setup-btn" aria-label="Resend recovery email verification">
                        <i class="fa-regular fa-paper-plane"></i>
                        <span>Verify</span>
                    </button>
                </form>
            @endif
        </div>

        <div class="account-record-item account-record-notification-item account-record-action-item">
            <span class="account-record-icon"><i class="fa-regular fa-bell"></i></span>
            <span class="account-record-copy">
                <span class="account-record-label">Device Notifications</span>
                <strong><span class="account-record-status is-pending" data-account-push-status>Off</span></strong>
                <small data-account-push-meta>PWA alerts</small>
            </span>
            <button type="button" class="account-notification-setup-btn account-record-action" data-account-open-notification-setup aria-label="Manage device notifications">
                <i class="fa-solid fa-sliders"></i>
                <span>Manage</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        function initAccountNotificationSetupButton() {
            const button = document.querySelector('[data-account-open-notification-setup]');
            const modalElement = document.getElementById('dashboardSetupToolsModal');
            const statusElement = document.querySelector('[data-account-push-status]');
            const metaElement = document.querySelector('[data-account-push-meta]');

            if (!button || !modalElement || button.dataset.bound === 'true') return;

            button.dataset.bound = 'true';

            const setAccountPushState = (label, state, meta) => {
                if (statusElement) {
                    statusElement.textContent = label;
                    statusElement.classList.toggle('is-verified', state === 'ready');
                    statusElement.classList.toggle('is-pending', state !== 'ready');
                }

                if (metaElement && meta) {
                    metaElement.textContent = meta;
                }
            };

            const syncNotificationStatus = () => {
                if (!window.SpeakReadyPush || typeof window.SpeakReadyPush.status !== 'function') {
                    setAccountPushState('Off', 'pending', 'PWA alerts');
                    return;
                }

                window.SpeakReadyPush.status()
                    .then((result) => {
                        if (!result.supported) {
                            setAccountPushState('Unsupported', 'pending', 'Unavailable');
                            return;
                        }

                        if (result.permission === 'denied') {
                            setAccountPushState('Blocked', 'pending', 'Site settings');
                            return;
                        }

                        if (result.configured && result.subscribed && result.permission === 'granted') {
                            setAccountPushState('Ready', 'ready', 'Device alerts on');
                            return;
                        }

                        setAccountPushState(result.configured ? 'Off' : 'Setup', 'pending', result.configured ? 'PWA alerts' : 'Server setup');
                    })
                    .catch(() => setAccountPushState('Off', 'pending', 'PWA alerts'));
            };

            button.addEventListener('click', () => {
                modalElement.querySelectorAll('[data-sr-setup-tool]').forEach((input) => {
                    input.checked = input.dataset.srSetupTool === 'notifications';
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });

                if (window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modalElement, {
                        backdrop: 'static',
                        keyboard: true,
                    }).show();
                }
            });

            modalElement.addEventListener('hidden.bs.modal', syncNotificationStatus);
            window.setTimeout(syncNotificationStatus, 700);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAccountNotificationSetupButton);
        } else {
            initAccountNotificationSetupButton();
        }
    })();
</script>
@endpush
