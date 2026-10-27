@extends('desktop.layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/admin/contacts.css?v=1') }}" data-page-style="admin-contacts">
@endpush

@section('content')
<div class="db-section active admin-contact-detail-shell" id="sec-admin-contact-detail">
    <div class="admin-contact-detail-header">
        <h4 class="admin-contact-detail-title">
            <a href="{{ route('admin.contacts.index') }}" class="admin-contact-detail-back-link" aria-label="Back to contact messages">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            </a>
            Message Details
        </h4>
    </div>

    @if(session('success'))
        <div class="alert alert-success admin-contact-alert" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger admin-contact-alert" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="card admin-contact-detail-card">
        <div class="admin-contact-detail-grid">
            <div class="admin-contact-detail-field">
                <p class="admin-contact-detail-label">Sender Name</p>
                <h5 class="admin-contact-detail-value">{{ $contact->name }}</h5>
            </div>
            <div class="admin-contact-detail-field">
                <p class="admin-contact-detail-label">Sender Email</p>
                <h5 class="admin-contact-detail-value">
                    <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                </h5>
            </div>
            <div class="admin-contact-detail-field">
                <p class="admin-contact-detail-label">Date Received</p>
                <h5 class="admin-contact-detail-value">{{ $contact->created_at->format('M d, Y h:i A') }}</h5>
            </div>
            <div class="admin-contact-detail-field">
                <p class="admin-contact-detail-label">Status</p>
                @switch($contact->status)
                    @case('unread')
                        <span class="badge admin-contact-status admin-contact-status-unread">Unread</span>
                        @break
                    @case('replied')
                        <span class="badge admin-contact-status admin-contact-status-replied">Replied</span>
                        @break
                    @default
                        <span class="badge admin-contact-status admin-contact-status-read">Read</span>
                @endswitch
            </div>
        </div>

        <div class="admin-contact-message-section">
            <p class="admin-contact-detail-label">Subject</p>
            <h4 class="admin-contact-detail-subject">{{ $contact->subject }}</h4>

            <p class="admin-contact-detail-label">Message</p>
            <div class="admin-contact-message-body">{{ $contact->message }}</div>
        </div>

        @include('shared.admin.contact-reply-form')

        <div class="admin-contact-detail-actions">
            <a href="{{ route('admin.contacts.index') }}" class="btn admin-contact-back-button">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Back to Messages</span>
            </a>

            <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" class="admin-contact-detail-delete-form" onsubmit="return window.SpeakReadyAdminConfirm(event, this, 'Are you sure you want to delete this message?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    <span>Delete Message</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-contacts.js?v=1') }}" defer></script>
@endpush
