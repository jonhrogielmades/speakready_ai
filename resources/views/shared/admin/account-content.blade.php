@php
    $admin = $admin ?? Auth::user();
    $photoPath = $admin?->profile_photo_path;
    $photoUrl = null;

    if ($photoPath) {
        $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:'))
            ? $photoPath
            : asset('storage/' . $photoPath);
    }
@endphp

<div class="db-section active animate-fade-up" id="account-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="account-hero-icon"><i class="fa-solid fa-user-shield"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M16 11.5v3a4 4 0 0 1-8 0v-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Admin Account
                    </h4>
                    <p class="sr-page-hero-subtitle">Update administrator profile and security settings.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="adminAccountPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="adminAccountBlue" x1="66" y1="36" x2="166" y2="118"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#adminAccountPanel)" stroke="#BFDBFE" stroke-width="3"/><circle cx="103" cy="63" r="23" fill="url(#adminAccountBlue)"/><path d="M64 114a40 40 0 0 1 78 0" fill="#BAE6FD"/><circle cx="158" cy="53" r="20" fill="#2563EB"/><path d="M158 43v20M148 53h20" stroke="#fff" stroke-width="5" stroke-linecap="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>

    @if(session('success') || session('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:12px">
            {{ session('success') ?? session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:12px">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4 account-grid">
        <div class="col-lg-7 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="premium-panel account-card" style="padding:32px;margin-bottom:24px">
                <h5 class="account-card-title"><span class="account-title-icon"><i class="fa-solid fa-user-shield"></i></span>Profile Details</h5>

                <form action="{{ route('admin.account.profile') }}" method="POST" enctype="multipart/form-data" id="adminAccountProfileForm">
                    @csrf

                    <div class="d-flex align-items-center mb-4 account-photo-row">
                        <div class="account-photo-avatar" style="width:80px;height:80px;border-radius:24px;overflow:hidden;margin-right:24px;border:1px solid var(--bd);background:#f87171;color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;">
                            @if($photoUrl)
                                <img id="profilePhotoPreview" src="{{ $photoUrl }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;">
                                <span id="profilePhotoInitial" style="display:none;">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                            @else
                                <img id="profilePhotoPreview" src="" alt="Profile Photo preview" style="width:100%;height:100%;object-fit:cover;display:none;">
                                <span id="profilePhotoInitial">{{ strtoupper(substr($admin->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="account-photo-actions">
                            <input type="file" name="profile_photo" id="profile_photo" class="d-none" accept="image/png, image/jpeg, image/gif">
                            <button type="button" class="btn upload-picture-btn" onclick="document.getElementById('profile_photo').click()"><i class="fa-solid fa-cloud-arrow-up"></i> Upload New Picture</button>
                            <div class="upload-hint" id="photo-filename">JPG, GIF or PNG. Max size of 2MB.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="account-field-label" for="adminAccountName"><span class="account-label-icon"><i class="fa-regular fa-user"></i></span>Full Name</label>
                            <input type="text" class="oinp" name="name" id="adminAccountName" value="{{ old('name', $admin->name) }}" autocomplete="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="account-field-label" for="adminAccountEmail"><span class="account-label-icon"><i class="fa-regular fa-envelope"></i></span>Email Address</label>
                            <input type="email" class="oinp" name="email" id="adminAccountEmail" value="{{ old('email', $admin->email) }}" autocomplete="email" required>
                        </div>
                    </div>
                    <div class="account-field">
                        <label class="account-field-label" for="adminAccountFocus"><span class="account-label-icon"><i class="fa-solid fa-id-badge"></i></span>Admin Role / Focus</label>
                        <input type="text" class="oinp" name="target_position" id="adminAccountFocus" value="{{ old('target_position', $admin->target_position) }}" placeholder="e.g., System Administrator" autocomplete="organization-title">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn account-submit-btn btn-shine"><i class="fa-regular fa-floppy-disk"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5 animate-fade-up admin-account-side-stack" style="animation-delay: 0.2s;">
            <div class="premium-panel account-card" style="padding:32px;margin-bottom:24px">
                <h5 class="account-card-title"><span class="account-title-icon"><i class="fa-solid fa-lock"></i></span>Security & Password</h5>
                <form action="{{ route('admin.account.password') }}" method="POST" id="adminAccountPasswordForm">
                    @csrf
                    <div class="account-field">
                        <label class="account-field-label" for="currentPassword">Current Password</label>
                        <div class="password-field">
                            <span class="password-prefix-icon"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="oinp" name="current_password" id="currentPassword" placeholder="********" autocomplete="current-password" required>
                            <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('currentPassword', this)" aria-label="Show password">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="account-field">
                        <label class="account-field-label" for="newPassword">New Password</label>
                        <div class="password-field">
                            <span class="password-prefix-icon"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="oinp" name="new_password" id="newPassword" placeholder="********" autocomplete="new-password" required minlength="8">
                            <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('newPassword', this)" aria-label="Show password">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="account-field">
                        <label class="account-field-label" for="confirmPassword">Confirm New Password</label>
                        <div class="password-field">
                            <span class="password-prefix-icon"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="oinp" name="confirm_password" id="confirmPassword" placeholder="********" autocomplete="new-password" required minlength="8">
                            <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('confirmPassword', this)" aria-label="Show password">
                                <i class="fa-solid fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn account-submit-btn btn-shine"><i class="fa-solid fa-shield-halved"></i>Update Password</button>
                </form>
            </div>

            <div class="premium-panel account-card admin-account-status-card" style="padding:32px">
                <h5 class="account-card-title"><span class="account-title-icon"><i class="fa-solid fa-circle-check"></i></span>Admin Access</h5>
                <div class="admin-account-meta-list">
                    <div class="admin-account-meta-row"><span>Role</span><strong>Administrator</strong></div>
                    <div class="admin-account-meta-row"><span>Status</span><strong>{{ ucfirst($admin->status ?? 'active') }}</strong></div>
                    <div class="admin-account-meta-row"><span>Joined</span><strong>{{ optional($admin->created_at)->format('M d, Y') }}</strong></div>
                </div>
                <a href="{{ route('admin.settings.index') }}" class="btn account-submit-btn admin-settings-shortcut"><i class="fa-solid fa-gear"></i>Open System Settings</a>
            </div>
        </div>
    </div>
</div>

<script>
    const profileInput = document.getElementById('profile_photo');
    const profilePreview = document.getElementById('profilePhotoPreview');
    const profileInitial = document.getElementById('profilePhotoInitial');
    const photoFilename = document.getElementById('photo-filename');

    if (profileInput) {
        profileInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) return;

            if (photoFilename) {
                photoFilename.textContent = file.name || 'Selected profile photo';
            }

            if (!file.type || !file.type.startsWith('image/') || !profilePreview) return;

            const previewUrl = URL.createObjectURL(file);
            profilePreview.src = previewUrl;
            profilePreview.style.display = 'block';

            if (profileInitial) {
                profileInitial.style.display = 'none';
            }
        });
    }

    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (!input || !icon) return;

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            btn.setAttribute('aria-label', 'Hide password');
            return;
        }

        input.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        btn.setAttribute('aria-label', 'Show password');
    }
</script>
