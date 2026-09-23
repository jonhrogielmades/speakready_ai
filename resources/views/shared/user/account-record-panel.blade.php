@php
    $accountUser = Auth::user();
    $accountCreatedAt = $accountUser?->created_at;
    $accountLastLoginAt = $lastLoginAt ?? null;
    $accountEmailVerifiedAt = $accountUser?->email_verified_at;

    $accountCreatedDate = $accountCreatedAt?->format('M d, Y') ?? 'Not recorded';
    $accountCreatedTime = $accountCreatedAt?->format('h:i A') ?? 'Awaiting record';
    $accountLastLoginDate = $accountLastLoginAt?->format('M d, Y') ?? 'No login recorded';
    $accountLastLoginTime = $accountLastLoginAt?->format('h:i A') ?? 'Sign in to update this record';
    $accountEmailStatus = $accountEmailVerifiedAt ? 'Verified' : 'Pending';
    $accountEmailStatusMeta = $accountEmailVerifiedAt
        ? 'Verified '.$accountEmailVerifiedAt->format('M d, Y')
        : 'Email verification not completed';
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

        <div class="account-record-item">
            <span class="account-record-icon"><i class="fa-regular fa-envelope"></i></span>
            <span class="account-record-copy">
                <span class="account-record-label">Email Status</span>
                <strong><span class="account-record-status {{ $accountEmailVerifiedAt ? 'is-verified' : 'is-pending' }}">{{ $accountEmailStatus }}</span></strong>
                <small>{{ $accountEmailStatusMeta }}</small>
            </span>
        </div>
    </div>
</div>
