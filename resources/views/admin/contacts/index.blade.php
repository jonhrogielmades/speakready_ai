@extends(isset($isMobile) && $isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<div class="db-section active" id="sec-admin-contacts">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 style="color:var(--tx);font-weight:700"><i class="fa-solid fa-envelope me-2"></i>Contact Messages</h4>
            <p style="color:var(--tx3)">View and manage messages sent from the Contact Us form.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="border-radius:12px; font-weight:600;">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="card" style="border:none;background:var(--bg2);border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,0.05);">
        <div class="table-responsive">
            <table class="table mb-0" style="color:var(--tx);">
                <thead>
                    <tr style="border-bottom: 2px solid var(--bd);">
                        <th class="d-none d-md-table-cell" style="padding:15px; border-bottom:none; color:var(--tx2);">Date</th>
                        <th style="padding:15px; border-bottom:none; color:var(--tx2);">Name</th>
                        <th class="d-none d-md-table-cell" style="padding:15px; border-bottom:none; color:var(--tx2);">Email</th>
                        <th class="d-none d-sm-table-cell" style="padding:15px; border-bottom:none; color:var(--tx2);">Subject</th>
                        <th style="padding:15px; border-bottom:none; color:var(--tx2);">Status</th>
                        <th style="padding:15px; border-bottom:none; color:var(--tx2); text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                        <tr style="border-bottom: 1px solid var(--bd); {{ $contact->status === 'unread' ? 'background:rgba(59,130,246,0.05);' : '' }}">
                            <td class="d-none d-md-table-cell" style="padding:15px; border:none; vertical-align:middle;">
                                {{ $contact->created_at->format('M d, Y h:i A') }}
                            </td>
                            <td style="padding:15px; border:none; vertical-align:middle; font-weight:{{ $contact->status === 'unread' ? 'bold' : 'normal' }};">
                                {{ $contact->name }}
                            </td>
                            <td class="d-none d-md-table-cell" style="padding:15px; border:none; vertical-align:middle;">
                                {{ $contact->email }}
                            </td>
                            <td class="d-none d-sm-table-cell" style="padding:15px; border:none; vertical-align:middle; font-weight:{{ $contact->status === 'unread' ? 'bold' : 'normal' }};">
                                {{ \Illuminate\Support\Str::limit($contact->subject, 30) }}
                            </td>
                            <td style="padding:15px; border:none; vertical-align:middle;">
                                @if($contact->status === 'unread')
                                    <span class="badge bg-primary">Unread</span>
                                @else
                                    <span class="badge bg-secondary">Read</span>
                                @endif
                            </td>
                            <td style="padding:15px; border:none; vertical-align:middle; text-align:right;">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.contacts.show', $contact->id) }}" class="btn btn-sm" style="background:var(--bg3); color:var(--tx2); border:1px solid var(--bd);">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                    <form action="{{ route('admin.contacts.destroy', $contact->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4" style="color:var(--tx3);">
                                <i class="fa-solid fa-inbox fa-3x mb-3" style="color:var(--bd);"></i>
                                <p>No contact messages found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($contacts->hasPages())
            <div class="card-footer" style="background:transparent; border-top:1px solid var(--bd); padding:15px;">
                {{ $contacts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
