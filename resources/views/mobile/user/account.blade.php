@extends('mobile.layouts.app')
@section('title', 'Account Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/account.css?v=2') }}" data-page-style="user-account">
<link rel="stylesheet" href="{{ asset('css/mobile/user/account-2.css?v=3') }}" data-page-style="user-account-2">
@endpush

@section('content')
@include('mobile.partials.page-hero-styles')

<div class="db-section active animate-fade-up" id="account-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="account-hero-icon"><i class="fa-regular fa-user"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 8v4M17 10h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Account Management
                    </h4>
                    <p class="sr-page-hero-subtitle">Update profile and security settings.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="accountPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="accountBlue" x1="66" y1="36" x2="166" y2="118"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#accountPanel)" stroke="#BFDBFE" stroke-width="3"/><circle cx="103" cy="63" r="23" fill="url(#accountBlue)"/><path d="M64 114a40 40 0 0 1 78 0" fill="#BAE6FD"/><circle cx="158" cy="53" r="20" fill="#22C55E"/><path d="M158 43v20M148 53h20" stroke="#fff" stroke-width="5" stroke-linecap="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:12px">
            {{ session('success') }}
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
                <h5 class="account-card-title"><span class="account-title-icon"><i class="fa-regular fa-user"></i></span>Profile Details</h5>
                
                <form action="{{ route('user.account.profile') }}" method="POST" enctype="multipart/form-data" id="accountProfileForm">
                    @csrf
                    
                    <div class="d-flex align-items-center mb-4 account-photo-row">
                        @if(Auth::user()->profile_photo_path)
                            <div class="account-photo-avatar" style="width:80px;height:80px;border-radius:24px;overflow:hidden;margin-right:24px;border:1px solid var(--bd)">
                                @if(Str::startsWith(Auth::user()->profile_photo_path, ['http://', 'https://', 'data:']))
                                    <img id="profilePhotoPreview" src="{{ Auth::user()->profile_photo_path }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;">
                                @else
                                    <img id="profilePhotoPreview" src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;">
                                @endif
                            </div>
                        @else
                            <div class="account-photo-avatar" style="width:80px;height:80px;background:var(--pur);border-radius:24px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:700;margin-right:24px;overflow:hidden;">
                                <img id="profilePhotoPreview" src="" alt="Profile Photo preview" style="width:100%;height:100%;object-fit:cover;display:none;">
                                <span id="profilePhotoInitial">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="account-photo-actions">
                            <input type="file" name="profile_photo" id="profile_photo" class="d-none" accept="image/png, image/jpeg, image/gif">
                            <button type="button" class="btn upload-picture-btn" onclick="document.getElementById('profile_photo').click()"><i class="fa-solid fa-cloud-arrow-up"></i> Upload New Picture</button>
                            <div class="upload-hint" id="photo-filename">JPG, GIF or PNG. Max size of 2MB.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="account-field-label" for="accountName"><span class="account-label-icon"><i class="fa-regular fa-user"></i></span>Full Name</label>
                            <input type="text" class="oinp" name="name" id="accountName" value="{{ old('name', Auth::user()->name) }}" autocomplete="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="account-field-label" for="accountEmail"><span class="account-label-icon"><i class="fa-regular fa-envelope"></i></span>Email Address</label>
                            <input type="email" class="oinp" name="email" id="accountEmail" value="{{ old('email', Auth::user()->email) }}" autocomplete="email" required>
                        </div>
                    </div>
                    <div class="account-field">
                        <label class="account-field-label" for="accountTargetPosition"><span class="account-label-icon"><i class="fa-solid fa-briefcase"></i></span>Target Job Position</label>
                        <input type="text" class="oinp" name="target_position" id="accountTargetPosition" value="{{ old('target_position', Auth::user()->target_position) }}" placeholder="e.g., Data Analyst" autocomplete="organization-title">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn account-submit-btn btn-shine"><i class="fa-regular fa-floppy-disk"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5 animate-fade-up" style="animation-delay: 0.2s;">
            <div class="premium-panel account-card" style="padding:32px">
                <h5 class="account-card-title"><span class="account-title-icon"><i class="fa-solid fa-lock"></i></span>Security & Password</h5>
                <form action="{{ route('user.account.password') }}" method="POST" id="accountPasswordForm">
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

                <div class="account-danger-section">
                    <h6 class="danger-title"><span class="danger-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>Danger Zone</h6>
                    <p class="danger-copy">Once you delete your account, there is no going back. Please be certain.</p>
                    <form action="{{ route('user.account.delete') }}" method="POST" id="accountDeleteForm" data-sr-confirm-form data-sr-confirm-title="Delete account" data-sr-confirm-message="This will permanently disable access to your account. This cannot be undone." data-sr-confirm-action="Delete Account" data-sr-confirm-variant="danger">
                        @csrf
                        <button type="submit" class="btn delete-account-btn"><i class="fa-regular fa-trash-can"></i>Delete Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="profile-crop-modal" id="profileCropModal" aria-hidden="true" hidden>
    <div class="profile-crop-dialog" role="dialog" aria-modal="true" aria-labelledby="profileCropTitle">
        <h6 id="profileCropTitle" style="color:var(--tx);font-weight:800;margin:0;">Crop Profile Picture</h6>
        <div class="profile-crop-frame">
            <canvas id="profileCropCanvas" width="320" height="320"></canvas>
        </div>
        <div class="profile-crop-controls">
            <label class="olbl" for="profileCropZoom" style="margin:0;">Zoom</label>
            <input type="range" id="profileCropZoom" min="1" max="3" step="0.01" value="1" class="form-range">
        </div>
        <div class="profile-crop-actions">
            <button type="button" class="btn btn-outline-secondary" onclick="cancelProfileCrop()">Cancel</button>
            <button type="button" class="btn bgrd" onclick="applyProfileCrop()">Use Crop</button>
        </div>
    </div>
</div>

<script>
    const profileInput = document.getElementById('profile_photo');
    const profilePreview = document.getElementById('profilePhotoPreview');
    const cropModal = document.getElementById('profileCropModal');
    const cropCanvas = document.getElementById('profileCropCanvas');
    const cropZoom = document.getElementById('profileCropZoom');
    const cropCtx = cropCanvas ? cropCanvas.getContext('2d') : null;
    const photoFilename = document.getElementById('photo-filename');
    let cropImage = null;
    let cropSourceName = 'profile-photo.jpg';
    let cropScale = 1;
    let cropOffsetX = 0;
    let cropOffsetY = 0;
    let cropDragging = false;
    let cropLastX = 0;
    let cropLastY = 0;

    function drawProfileCrop() {
        if (!cropCtx || !cropImage) return;
        const size = cropCanvas.width;
        cropCtx.clearRect(0, 0, size, size);
        cropCtx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--bg3') || '#111827';
        cropCtx.fillRect(0, 0, size, size);

        const baseScale = Math.max(size / cropImage.width, size / cropImage.height);
        const scale = baseScale * cropScale;
        const width = cropImage.width * scale;
        const height = cropImage.height * scale;
        const minX = size - width;
        const minY = size - height;
        cropOffsetX = Math.min(0, Math.max(minX, cropOffsetX));
        cropOffsetY = Math.min(0, Math.max(minY, cropOffsetY));
        cropCtx.drawImage(cropImage, cropOffsetX, cropOffsetY, width, height);
    }

    function openProfileCrop(file) {
        if (!file || !cropModal || !cropCanvas || !cropZoom) return;
        cropSourceName = file.name || 'profile-photo.jpg';
        if (photoFilename) photoFilename.textContent = cropSourceName;
        const reader = new FileReader();
        reader.onload = event => {
            cropImage = new Image();
            cropImage.onload = () => {
                cropScale = 1;
                cropZoom.value = '1';
                const size = cropCanvas.width;
                const baseScale = Math.max(size / cropImage.width, size / cropImage.height);
                cropOffsetX = (size - cropImage.width * baseScale) / 2;
                cropOffsetY = (size - cropImage.height * baseScale) / 2;
                cropModal.hidden = false;
                cropModal.setAttribute('aria-hidden', 'false');
                cropModal.classList.add('open');
                drawProfileCrop();
            };
            cropImage.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }

    function cancelProfileCrop() {
        if (cropModal) {
            cropModal.classList.remove('open');
            cropModal.setAttribute('aria-hidden', 'true');
            cropModal.hidden = true;
        }
        if (profileInput) profileInput.value = '';
        if (photoFilename) photoFilename.textContent = 'JPG, GIF or PNG. Max size of 2MB.';
    }

    function applyProfileCrop() {
        if (!cropCanvas || !profileInput || !cropModal) return;
        cropCanvas.toBlob(blob => {
            if (!blob) return;
            const file = new File([blob], cropSourceName.replace(/\.[^.]+$/, '') + '-cropped.jpg', { type: 'image/jpeg' });
            const transfer = new DataTransfer();
            transfer.items.add(file);
            profileInput.files = transfer.files;

            const previewUrl = URL.createObjectURL(file);
            if (profilePreview) {
                profilePreview.src = previewUrl;
                profilePreview.style.display = 'block';
                const initial = document.getElementById('profilePhotoInitial');
                if (initial) initial.style.display = 'none';
            }
            if (photoFilename) photoFilename.textContent = file.name;
            cropModal.classList.remove('open');
            cropModal.setAttribute('aria-hidden', 'true');
            cropModal.hidden = true;
        }, 'image/jpeg', 0.9);
    }

    if (profileInput) {
        profileInput.addEventListener('change', function () {
            openProfileCrop(this.files && this.files[0]);
        });
    }

    if (cropZoom) {
        cropZoom.addEventListener('input', function () {
            cropScale = parseFloat(this.value) || 1;
            drawProfileCrop();
        });
    }

    if (cropCanvas) {
        const pointerPosition = event => {
            const rect = cropCanvas.getBoundingClientRect();
            return {
                x: (event.clientX - rect.left) * (cropCanvas.width / rect.width),
                y: (event.clientY - rect.top) * (cropCanvas.height / rect.height)
            };
        };
        cropCanvas.addEventListener('pointerdown', event => {
            cropDragging = true;
            const pos = pointerPosition(event);
            cropLastX = pos.x;
            cropLastY = pos.y;
            cropCanvas.setPointerCapture(event.pointerId);
        });
        cropCanvas.addEventListener('pointermove', event => {
            if (!cropDragging) return;
            const pos = pointerPosition(event);
            cropOffsetX += pos.x - cropLastX;
            cropOffsetY += pos.y - cropLastY;
            cropLastX = pos.x;
            cropLastY = pos.y;
            drawProfileCrop();
        });
        cropCanvas.addEventListener('pointerup', () => { cropDragging = false; });
        cropCanvas.addEventListener('pointercancel', () => { cropDragging = false; });
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
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            btn.setAttribute('aria-label', 'Show password');
        }
    }
</script>
@endsection
