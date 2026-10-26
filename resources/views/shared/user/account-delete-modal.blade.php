@php
    $accountUsesGoogle = filled($accountUser?->google_id);
@endphp

<div class="modal fade account-delete-modal" id="accountDeleteModal" tabindex="-1" aria-labelledby="accountDeleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="account-delete-modal-heading">
                    <span class="account-delete-modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div>
                        <span class="account-delete-modal-eyebrow">Permanent action</span>
                        <h5 class="modal-title" id="accountDeleteModalTitle">Delete your account?</h5>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="account-delete-modal-copy">Your profile, interview history, reports, learning progress, notifications, and sign-in data will be permanently removed. This cannot be undone.</p>

                @error('account_delete')
                    <div class="account-delete-modal-error" role="alert">{{ $message }}</div>
                @enderror

                @if($accountUsesGoogle)
                    <div class="account-delete-google-account">
                        @include('shared.google-auth-mark')
                        <div>
                            <span>Connected Google account</span>
                            <strong>{{ $accountUser->email }}</strong>
                        </div>
                    </div>
                    <p class="account-delete-confirmation-help">Google will open an account chooser. Select the connected account shown above to confirm the deletion.</p>

                    <form action="{{ route('user.account.delete.google', sr_layout_query(request())) }}" method="POST" class="account-delete-confirm-form">
                        @csrf
                        <button type="button" class="btn account-delete-cancel-btn" data-bs-dismiss="modal">Keep Account</button>
                        <button type="submit" class="btn account-delete-google-btn">
                            @include('shared.google-auth-mark')
                            Continue with Google
                        </button>
                    </form>
                @else
                    <form action="{{ route('user.account.delete', sr_layout_query(request())) }}" method="POST" class="account-delete-password-form">
                        @csrf
                        <label class="account-field-label" for="deleteAccountPassword">Current Password</label>
                        <div class="password-field">
                            <span class="password-prefix-icon"><i class="fa-solid fa-lock"></i></span>
                            <input
                                type="password"
                                class="oinp @error('delete_password') is-invalid @enderror"
                                name="delete_password"
                                id="deleteAccountPassword"
                                placeholder="Enter your current password"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('deleteAccountPassword', this)" aria-label="Show password">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                        @error('delete_password')
                            <div class="account-delete-field-error" role="alert">{{ $message }}</div>
                        @enderror
                        <p class="account-delete-confirmation-help">Enter your current password to confirm that you own this account.</p>

                        <div class="account-delete-confirm-form">
                            <button type="button" class="btn account-delete-cancel-btn" data-bs-dismiss="modal">Keep Account</button>
                            <button type="submit" class="btn account-delete-confirm-btn"><i class="fa-regular fa-trash-can"></i>Permanently Delete</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
