@extends('desktop.layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/admin/contacts.css?v=1') }}" data-page-style="admin-contacts">
@endpush

@section('content')
<div class="db-section active admin-contacts-shell" id="sec-admin-contacts">
    <div class="admin-contacts-header">
        <div>
            <h4 class="admin-contacts-title"><i class="fa-solid fa-envelope" aria-hidden="true"></i>Contact Messages</h4>
            <p class="admin-contacts-subtitle">View, find, reply to, and manage messages sent from the Contact Us form.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success admin-contact-alert" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger admin-contact-alert" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="admin-contacts-filter-card">
        @include('shared.admin.contact-filters')
    </div>

    <div class="card admin-contacts-card">
        <div class="table-responsive" id="mainContactsTableWrapper">
            <table class="table mb-0" id="mainContactsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                        <tr @class(['contact-message-unread' => $contact->status === 'unread'])>
                            <td data-label="Date">{{ $contact->created_at->format('M d, Y h:i A') }}</td>
                            <td data-label="Name" @class(['contact-name-cell', 'contact-message-emphasis' => $contact->status === 'unread'])>
                                {{ $contact->name }}
                            </td>
                            <td data-label="Email">{{ $contact->email }}</td>
                            <td data-label="Subject" @class(['contact-message-emphasis' => $contact->status === 'unread'])>
                                {{ \Illuminate\Support\Str::limit($contact->subject, 42) }}
                            </td>
                            <td data-label="Status">
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
                            </td>
                            <td data-label="Actions" class="contact-action-cell">
                                <div class="contact-actions">
                                    <a href="{{ route('admin.contacts.show', $contact) }}" class="btn btn-sm admin-contact-view-button">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                        <span>View</span>
                                    </a>
                                    <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" class="contact-row-action-form" onsubmit="return window.SpeakReadyAdminConfirm(event, this, 'Are you sure you want to delete this message?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="contacts-empty-cell">
                                <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                                <p>{{ $filters['search'] !== '' || $filters['status'] !== '' ? 'No messages match the selected filters.' : 'No contact messages found.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contacts->hasPages())
            <div class="card-footer admin-contacts-pagination">
                {{ $contacts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
