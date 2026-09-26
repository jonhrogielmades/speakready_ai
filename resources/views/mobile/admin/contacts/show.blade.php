@extends('mobile.layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/admin/contacts.css?v=1') }}" data-page-style="admin-contacts">
@endpush

@section('content')
<div class="db-section active admin-contact-detail-shell" id="sec-admin-contact-detail">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3 admin-contact-detail-header">
        <div>
            <h4 class="admin-contact-detail-title" style="color:var(--tx);font-weight:700">
                <a href="{{ route('admin.contacts.index') }}" class="admin-contact-detail-back-link" style="color:var(--tx); text-decoration:none;">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                </a>
                Message Details
            </h4>
        </div>
    </div>

    <div class="card admin-contact-detail-card" style="border:none;background:var(--bg2);border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,0.05); padding:30px;">
        <div class="row mb-4 admin-contact-detail-grid">
            <div class="col-md-6 mb-3 mb-md-0 admin-contact-detail-field">
                <p class="admin-contact-detail-label" style="color:var(--tx3); font-size:14px; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Sender Name</p>
                <h5 class="admin-contact-detail-value" style="color:var(--tx); font-weight:700; margin:0;">{{ $contact->name }}</h5>
            </div>
            <div class="col-md-6 admin-contact-detail-field">
                <p class="admin-contact-detail-label" style="color:var(--tx3); font-size:14px; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Sender Email</p>
                <h5 class="admin-contact-detail-value" style="color:var(--tx); font-weight:700; margin:0;">
                    <a href="mailto:{{ $contact->email }}" style="color:#3b82f6; text-decoration:none;">{{ $contact->email }}</a>
                </h5>
            </div>
        </div>

        <div class="row mb-4 admin-contact-detail-grid">
            <div class="col-md-6 mb-3 mb-md-0 admin-contact-detail-field">
                <p class="admin-contact-detail-label" style="color:var(--tx3); font-size:14px; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Date Received</p>
                <h5 class="admin-contact-detail-value" style="color:var(--tx); font-weight:600; margin:0;">{{ $contact->created_at->format('M d, Y h:i A') }}</h5>
            </div>
            <div class="col-md-6 admin-contact-detail-field">
                <p class="admin-contact-detail-label" style="color:var(--tx3); font-size:14px; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Status</p>
                @if($contact->status === 'unread')
                    <span class="badge bg-primary">Unread</span>
                @else
                    <span class="badge bg-secondary">Read</span>
                @endif
            </div>
        </div>

        <hr style="border-color:var(--bd);">

        <div class="mt-4">
            <p class="admin-contact-detail-label" style="color:var(--tx3); font-size:14px; text-transform:uppercase; letter-spacing:1px; margin-bottom:10px;">Subject</p>
            <h4 class="admin-contact-detail-subject" style="color:var(--tx); font-weight:700; margin-bottom:20px;">{{ $contact->subject }}</h4>
            
            <p class="admin-contact-detail-label" style="color:var(--tx3); font-size:14px; text-transform:uppercase; letter-spacing:1px; margin-bottom:10px;">Message</p>
            <div class="p-4 admin-contact-message-body" style="background:var(--bg1); border-radius:12px; border:1px solid var(--bd); color:var(--tx); white-space:pre-wrap; line-height:1.6;">
{{ $contact->message }}
            </div>
        </div>
        
        <div class="mt-4 pt-3 d-flex justify-content-between align-items-center admin-contact-detail-actions" style="border-top:1px solid var(--bd);">
            <a href="mailto:{{ $contact->email }}?subject=RE: {{ rawurlencode($contact->subject) }}" class="btn btn-primary" style="border-radius:10px; font-weight:600;">
                <i class="fa-solid fa-reply me-2"></i> Reply via Email
            </a>
            
            <form action="{{ route('admin.contacts.destroy', $contact->id) }}" method="POST" class="admin-contact-detail-delete-form" onsubmit="return confirm('Are you sure you want to delete this message?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger" style="border-radius:10px; font-weight:600;">
                    <i class="fa-solid fa-trash me-2"></i> Delete Message
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
