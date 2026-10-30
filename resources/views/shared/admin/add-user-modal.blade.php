@php
    $addUserErrors = $errors->getBag('addUser');
    $addUserHadErrors = $addUserErrors->any();
    $addUserName = $addUserHadErrors ? old('name', '') : '';
    $addUserUsername = $addUserHadErrors ? old('username', '') : '';
    $addUserEmail = $addUserHadErrors ? old('email', '') : '';
    $addUserRole = $addUserHadErrors ? old('role', 'user') : 'user';
    $addUserStatus = $addUserHadErrors ? old('status', 'active') : 'active';
    $strongPasswordsRequired = \App\Support\SystemSettings::enabled('sec_strong_pass', true);
@endphp

<div
    class="modal fade custom-modal"
    id="addUserModal"
    tabindex="-1"
    aria-labelledby="addUserModalLabel"
    aria-hidden="true"
    data-has-errors="{{ $addUserHadErrors ? 'true' : 'false' }}"
>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addUserForm" action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" id="addUserModalLabel">Add New User</h5>
                        <div class="text-muted mt-1" style="font-size:0.82rem;">Create login credentials and choose the account access level.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1);"></button>
                </div>

                <div class="modal-body">
                    @if($addUserHadErrors)
                        <div class="alert alert-danger" role="alert" data-add-user-error-summary>
                            <div class="fw-bold mb-1">The account was not created.</div>
                            <div>Please correct the highlighted fields and submit again.</div>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted" for="createUserName">Full Name</label>
                            <input
                                type="text"
                                name="name"
                                id="createUserName"
                                class="form-control {{ $addUserErrors->has('name') ? 'is-invalid' : '' }}"
                                value="{{ $addUserName }}"
                                maxlength="255"
                                autocomplete="name"
                                required
                            >
                            @if($addUserErrors->has('name'))
                                <div class="invalid-feedback">{{ $addUserErrors->first('name') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted" for="createUserUsername">Username</label>
                            <input
                                type="text"
                                name="username"
                                id="createUserUsername"
                                class="form-control {{ $addUserErrors->has('username') ? 'is-invalid' : '' }}"
                                value="{{ $addUserUsername }}"
                                minlength="3"
                                maxlength="64"
                                pattern="[a-z0-9._-]+"
                                autocomplete="off"
                                autocapitalize="none"
                                spellcheck="false"
                                aria-describedby="createUserUsernameHelp"
                                required
                            >
                            <div class="form-text text-muted" id="createUserUsernameHelp">3-64 lowercase letters, numbers, dots, underscores, or hyphens.</div>
                            @if($addUserErrors->has('username'))
                                <div class="invalid-feedback d-block">{{ $addUserErrors->first('username') }}</div>
                            @endif
                        </div>

                        <div class="col-12">
                            <label class="form-label text-muted" for="createUserEmail">Recovery Email</label>
                            <input
                                type="email"
                                name="email"
                                id="createUserEmail"
                                class="form-control {{ $addUserErrors->has('email') ? 'is-invalid' : '' }}"
                                value="{{ $addUserEmail }}"
                                maxlength="255"
                                autocomplete="email"
                                required
                            >
                            <div class="form-text text-muted">Used for account recovery and notifications.</div>
                            @if($addUserErrors->has('email'))
                                <div class="invalid-feedback d-block">{{ $addUserErrors->first('email') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted" for="createUserPassword">Temporary Password</label>
                            <div class="password-field">
                                <input
                                    type="password"
                                    name="password"
                                    id="createUserPassword"
                                    class="form-control {{ $addUserErrors->has('password') ? 'is-invalid' : '' }}"
                                    placeholder="{{ $strongPasswordsRequired ? '8+ chars: upper, lower, number & symbol' : 'At least 8 characters' }}"
                                    minlength="8"
                                    autocomplete="new-password"
                                    aria-describedby="createUserPasswordHelp"
                                    required
                                >
                                <button class="password-toggle toggle-password" type="button" onclick="togglePasswordVisibility('createUserPassword', this)" aria-label="Show password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-muted" id="createUserPasswordHelp">{{ $strongPasswordsRequired ? 'Use at least 8 characters with uppercase, lowercase, a number, and a symbol.' : 'Use at least 8 characters.' }}</div>
                            @if($addUserErrors->has('password'))
                                <div class="invalid-feedback d-block">{{ $addUserErrors->first('password') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted" for="createUserPasswordConfirmation">Confirm Password</label>
                            <div class="password-field">
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    id="createUserPasswordConfirmation"
                                    class="form-control {{ $addUserErrors->has('password_confirmation') ? 'is-invalid' : '' }}"
                                    placeholder="Confirm the temporary password"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                >
                                <button class="password-toggle toggle-password" type="button" onclick="togglePasswordVisibility('createUserPasswordConfirmation', this)" aria-label="Show password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            @if($addUserErrors->has('password_confirmation'))
                                <div class="invalid-feedback d-block">{{ $addUserErrors->first('password_confirmation') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted" for="createUserRole">Role</label>
                            <select name="role" id="createUserRole" class="form-select {{ $addUserErrors->has('role') ? 'is-invalid' : '' }}" required>
                                <option value="user" {{ $addUserRole === 'user' ? 'selected' : '' }}>Candidate/User</option>
                                <option value="admin" {{ $addUserRole === 'admin' ? 'selected' : '' }}>Administrator</option>
                            </select>
                            @if($addUserErrors->has('role'))
                                <div class="invalid-feedback">{{ $addUserErrors->first('role') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted" for="createUserStatus">Status</label>
                            <select name="status" id="createUserStatus" class="form-select {{ $addUserErrors->has('status') ? 'is-invalid' : '' }}" required>
                                <option value="active" {{ $addUserStatus === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $addUserStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="suspended" {{ $addUserStatus === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                            <div class="form-text text-muted">Only active accounts can sign in.</div>
                            @if($addUserErrors->has('status'))
                                <div class="invalid-feedback d-block">{{ $addUserErrors->first('status') }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createUserSubmit" style="background:#3b82f6;border-color:#3b82f6;">
                        <span class="spinner-border spinner-border-sm me-2 d-none text-white" aria-hidden="true"></span>
                        <span class="text-white" data-submit-label>Create User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('addUserModal');
        const form = document.getElementById('addUserForm');

        if (!modalElement || !form || modalElement.dataset.initialized === 'true') {
            return;
        }

        modalElement.dataset.initialized = 'true';

        const nameInput = document.getElementById('createUserName');
        const usernameInput = document.getElementById('createUserUsername');
        const passwordInput = document.getElementById('createUserPassword');
        const confirmationInput = document.getElementById('createUserPasswordConfirmation');
        const submitButton = document.getElementById('createUserSubmit');

        const validatePasswordMatch = function () {
            if (!confirmationInput) {
                return;
            }

            confirmationInput.setCustomValidity(
                confirmationInput.value !== '' && confirmationInput.value !== passwordInput?.value
                    ? 'Passwords do not match.'
                    : ''
            );
        };

        usernameInput?.addEventListener('input', function () {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toLowerCase().replace(/\s+/g, '-');
            this.setSelectionRange(start, end);
        });

        passwordInput?.addEventListener('input', validatePasswordMatch);
        confirmationInput?.addEventListener('input', validatePasswordMatch);

        modalElement.addEventListener('shown.bs.modal', function () {
            (form.querySelector('.is-invalid') || nameInput)?.focus();
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            form.reset();
            modalElement.dataset.hasErrors = 'false';
            form.querySelectorAll('.is-invalid').forEach(function (field) {
                field.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(function (feedback) {
                feedback.remove();
            });
            modalElement.querySelector('[data-add-user-error-summary]')?.remove();
            passwordInput?.setAttribute('type', 'password');
            confirmationInput?.setAttribute('type', 'password');
            confirmationInput?.setCustomValidity('');
            form.querySelectorAll('.password-toggle').forEach(function (button) {
                button.setAttribute('aria-label', 'Show password');
                const icon = button.querySelector('i');
                icon?.classList.remove('fa-eye-slash');
                icon?.classList.add('fa-eye');
            });
        });

        form.addEventListener('submit', function (event) {
            validatePasswordMatch();

            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            if (!submitButton) {
                return;
            }

            submitButton.disabled = true;
            submitButton.querySelector('.spinner-border')?.classList.remove('d-none');
            const label = submitButton.querySelector('[data-submit-label]');
            if (label) {
                label.textContent = 'Creating...';
            }
        });

        if (modalElement.dataset.hasErrors === 'true' && window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }
    });
</script>
@endpush
