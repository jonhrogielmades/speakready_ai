@extends('layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="color:var(--tx);font-weight:700">Notifications</h4>
            <p style="color:var(--tx3)">Stay updated on your progress, system updates, and new courses.</p>
        </div>
        <button class="btn btn-outline-primary btn-sm" style="border-radius:8px">Mark all as read</button>
    </div>

    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;overflow:hidden">
        
        <!-- Unread Notification -->
        <div style="padding:20px 24px;border-bottom:1px solid var(--bd);background:rgba(139,92,246,0.03);display:flex;align-items:flex-start">
            <div style="width:48px;height:48px;background:rgba(139,92,246,.15);color:#a78bfa;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-right:16px;flex-shrink:0">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>
            <div style="flex-grow:1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 style="color:var(--tx);margin:0;font-weight:700">Detailed Feedback is Ready! <span class="badge bg-primary ms-2" style="font-size:.65rem">NEW</span></h6>
                    <span style="font-size:.75rem;color:var(--tx3)">2 hours ago</span>
                </div>
                <p style="color:var(--tx3);font-size:.9rem;margin-bottom:8px">Your AI Coach has finished analyzing your last Mock Interview. Click here to review your per-question score breakdown and sample answers.</p>
                <a href="{{ route('user.feedback') }}" style="font-size:.85rem;color:var(--pur);text-decoration:none;font-weight:600">View Session Review <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
        </div>

        <!-- Read Notification -->
        <div style="padding:20px 24px;border-bottom:1px solid var(--bd);display:flex;align-items:flex-start">
            <div style="width:48px;height:48px;background:rgba(52,211,153,.15);color:#34d399;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-right:16px;flex-shrink:0">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div style="flex-grow:1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 style="color:var(--tx);margin:0;font-weight:600">New Module: Salary Negotiation</h6>
                    <span style="font-size:.75rem;color:var(--tx3)">1 day ago</span>
                </div>
                <p style="color:var(--tx3);font-size:.9rem;margin-bottom:0">We just added a new comprehensive video course to the Learning Lab. Check it out to boost your earning potential.</p>
            </div>
        </div>

        <!-- System Notification -->
        <div style="padding:20px 24px;display:flex;align-items:flex-start">
            <div style="width:48px;height:48px;background:rgba(245,158,11,.15);color:#f59e0b;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-right:16px;flex-shrink:0">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div style="flex-grow:1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 style="color:var(--tx);margin:0;font-weight:600">Security Alert: New Login</h6>
                    <span style="font-size:.75rem;color:var(--tx3)">3 days ago</span>
                </div>
                <p style="color:var(--tx3);font-size:.9rem;margin-bottom:0">We noticed a new login from a device in your region. If this was you, no action is needed.</p>
            </div>
        </div>

    </div>
    
    <div class="text-center mt-4">
        <button class="btn btn-link text-muted text-decoration-none">Load Older Notifications</button>
    </div>
</div>
@endsection
