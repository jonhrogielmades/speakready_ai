@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

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
        background: var(--sf) !important;
        border: 1px solid var(--bd) !important;
        border-radius: 24px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .premium-panel:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1), inset 0 1px 1px rgba(255, 255, 255, 0.08) !important;
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
</style>

<div class="db-section active animate-fade-up">
    <div class="mb-4">
        <h4 class="text-gradient-primary" style="font-weight:800;letter-spacing:-0.5px;margin-bottom:4px;">Account Management</h4>
        <p style="color:var(--tx3)">Update your personal information and security settings.</p>
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


    <div class="row g-4">
        <div class="col-lg-7 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="premium-panel" style="padding:32px;margin-bottom:24px">
                <h5 style="color:var(--tx);margin-bottom:24px">Profile Details</h5>
                
                <form action="{{ route('user.account.profile') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="d-flex align-items-center mb-4">
                        @if(Auth::user()->profile_photo_path)
                            <div style="width:80px;height:80px;border-radius:24px;overflow:hidden;margin-right:24px;border:1px solid var(--bd)">
                                @if(Str::startsWith(Auth::user()->profile_photo_path, ['http://', 'https://', 'data:']))
                                    <img src="{{ Auth::user()->profile_photo_path }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;">
                                @else
                                    <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;">
                                @endif
                            </div>
                        @else
                            <div style="width:80px;height:80px;background:var(--pur);border-radius:24px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:700;margin-right:24px">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <input type="file" name="profile_photo" id="profile_photo" class="d-none" accept="image/png, image/jpeg, image/gif" onchange="document.getElementById('photo-filename').textContent = this.files[0].name">
                            <button type="button" class="btn btn-outline-primary btn-sm mb-2" style="border-radius:8px" onclick="document.getElementById('profile_photo').click()">Upload New Picture</button>
                            <div style="font-size:.8rem;color:var(--tx3)" id="photo-filename">JPG, GIF or PNG. Max size of 2MB.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="olbl">Full Name</label>
                            <input type="text" class="oinp" name="name" value="{{ old('name', Auth::user()->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Email Address</label>
                            <input type="email" class="oinp" name="email" value="{{ old('email', Auth::user()->email) }}" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="olbl">Target Job Position</label>
                        <input type="text" class="oinp" name="target_position" value="{{ old('target_position', Auth::user()->target_position) }}" placeholder="e.g., Data Analyst">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn bgrd px-4 py-2 btn-shine">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5 animate-fade-up" style="animation-delay: 0.2s;">
            <div class="premium-panel" style="padding:32px">
                <h5 style="color:var(--tx);margin-bottom:24px">Security & Password</h5>
                <form action="{{ route('user.account.password') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="olbl">Current Password</label>
                        <div class="position-relative">
                           <input type="password" class="oinp" name="current_password" id="currentPassword" placeholder="••••••••" required style="padding-right: 40px; margin-bottom: 0;">
                           <span class="position-absolute top-50 translate-middle-y toggle-password" onclick="togglePasswordVisibility('currentPassword', this)" style="right: 15px; cursor: pointer; color: var(--tx3); z-index: 10;">
                              <i class="fa-solid fa-eye-slash"></i>
                           </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="olbl">New Password</label>
                        <div class="position-relative">
                           <input type="password" class="oinp" name="new_password" id="newPassword" placeholder="••••••••" required minlength="8" style="padding-right: 40px; margin-bottom: 0;">
                           <span class="position-absolute top-50 translate-middle-y toggle-password" onclick="togglePasswordVisibility('newPassword', this)" style="right: 15px; cursor: pointer; color: var(--tx3); z-index: 10;">
                              <i class="fa-solid fa-eye-slash"></i>
                           </span>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="olbl">Confirm New Password</label>
                        <div class="position-relative">
                           <input type="password" class="oinp" name="confirm_password" id="confirmPassword" placeholder="••••••••" required minlength="8" style="padding-right: 40px; margin-bottom: 0;">
                           <span class="position-absolute top-50 translate-middle-y toggle-password" onclick="togglePasswordVisibility('confirmPassword', this)" style="right: 15px; cursor: pointer; color: var(--tx3); z-index: 10;">
                              <i class="fa-solid fa-eye-slash"></i>
                           </span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100 py-2 btn-shine">Update Password</button>
                </form>
            </div>
            
            <div class="premium-panel animate-fade-up" style="animation-delay: 0.3s; background:rgba(248,113,113,.05) !important;border:1px solid rgba(248,113,113,.2) !important;padding:24px;margin-top:24px">
                <h6 style="color:#f87171;margin-bottom:12px">Danger Zone</h6>
                <p style="font-size:.85rem;color:var(--tx3);margin-bottom:16px">Once you delete your account, there is no going back. Please be certain.</p>
                <form action="{{ route('user.account.delete') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }
</script>
@endsection


