@php
    $accountUsesGoogle = (bool) $accountUser?->isGoogleAccount();
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-account-delete-close aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="account-delete-modal-copy">Your profile, interview history, reports, learning progress, notifications, and sign-in data will be permanently removed. This cannot be undone.</p>

                @error('account_delete')
                    <div class="account-delete-modal-error" role="alert" tabindex="-1">{{ $message }}</div>
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

                    <form action="{{ route('user.account.delete.google', sr_layout_query(request())) }}" method="POST" class="account-delete-confirm-form" data-account-delete-form data-account-delete-kind="google" data-account-delete-busy-status="Opening Google securely. Please wait.">
                        @csrf
                        <button type="button" class="btn account-delete-cancel-btn" data-bs-dismiss="modal" data-account-delete-cancel>Keep Account</button>
                        <button type="submit" class="btn account-delete-google-btn" data-account-delete-submit data-busy-label="Opening Google…">
                            @include('shared.google-auth-mark')
                            <span data-account-delete-submit-label>Continue with Google</span>
                        </button>
                        <p class="account-delete-status" data-account-delete-status role="status" aria-live="polite"></p>
                    </form>
                @else
                    <form action="{{ route('user.account.delete', sr_layout_query(request())) }}" method="POST" class="account-delete-password-form" data-account-delete-form data-account-delete-kind="local" data-account-delete-busy-status="Securely deleting your account. Please wait.">
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
                                data-account-delete-password
                                required
                            >
                            <button type="button" class="password-toggle toggle-password" data-account-delete-password-toggle onclick="togglePasswordVisibility('deleteAccountPassword', this)" aria-label="Show password">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                        @error('delete_password')
                            <div class="account-delete-field-error" role="alert">{{ $message }}</div>
                        @enderror
                        <p class="account-delete-confirmation-help">Enter your current password to confirm that you own this account.</p>

                        <div class="account-delete-confirm-form">
                            <button type="button" class="btn account-delete-cancel-btn" data-bs-dismiss="modal" data-account-delete-cancel>Keep Account</button>
                            <button type="submit" class="btn account-delete-confirm-btn" data-account-delete-submit data-busy-label="Deleting account…"><i class="fa-regular fa-trash-can"></i><span data-account-delete-submit-label>Permanently Delete</span></button>
                        </div>
                        <p class="account-delete-status" data-account-delete-status role="status" aria-live="polite"></p>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@include('shared.user.account-delete-script')
