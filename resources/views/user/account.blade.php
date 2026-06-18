@extends('layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4">
        <h4 style="color:var(--tx);font-weight:700">Account Management</h4>
        <p style="color:var(--tx3)">Update your personal information and security settings.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:32px;margin-bottom:24px">
                <h5 style="color:var(--tx);margin-bottom:24px">Profile Details</h5>
                
                <div class="d-flex align-items-center mb-4">
                    <div style="width:80px;height:80px;background:var(--pur);border-radius:24px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:700;margin-right:24px">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <button class="btn btn-outline-primary btn-sm mb-2" style="border-radius:8px">Upload New Picture</button>
                        <div style="font-size:.8rem;color:var(--tx3)">JPG, GIF or PNG. Max size of 2MB.</div>
                    </div>
                </div>

                <form action="#" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="olbl">Full Name</label>
                            <input type="text" class="oinp" name="name" value="{{ Auth::user()->name }}">
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Email Address</label>
                            <input type="email" class="oinp" name="email" value="{{ Auth::user()->email }}">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="olbl">Target Job Position</label>
                        <input type="text" class="oinp" name="target_position" value="Software Engineer" placeholder="e.g., Data Analyst">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn bgrd px-4 py-2" onclick="event.preventDefault(); alert('Profile updated (Demo)')">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:32px">
                <h5 style="color:var(--tx);margin-bottom:24px">Security & Password</h5>
                <form action="#" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="olbl">Current Password</label>
                        <input type="password" class="oinp" name="current_password" placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <label class="olbl">New Password</label>
                        <input type="password" class="oinp" name="new_password" placeholder="••••••••">
                    </div>
                    <div class="mb-4">
                        <label class="olbl">Confirm New Password</label>
                        <input type="password" class="oinp" name="confirm_password" placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100 py-2" onclick="event.preventDefault(); alert('Password updated (Demo)')">Update Password</button>
                </form>
            </div>
            
            <div style="background:rgba(248,113,113,.05);border:1px solid rgba(248,113,113,.2);border-radius:18px;padding:24px;margin-top:24px">
                <h6 style="color:#f87171;margin-bottom:12px">Danger Zone</h6>
                <p style="font-size:.85rem;color:var(--tx3);margin-bottom:16px">Once you delete your account, there is no going back. Please be certain.</p>
                <button class="btn btn-sm btn-outline-danger" onclick="confirm('Are you sure you want to delete your account?')">Delete Account</button>
            </div>
        </div>
    </div>
</div>
@endsection
