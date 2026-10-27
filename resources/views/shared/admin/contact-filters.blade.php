<form method="GET" action="{{ route('admin.contacts.index') }}" class="admin-contact-filter-form" role="search" aria-label="Filter contact messages">
    <div class="admin-contact-filter-search">
        <label for="contact-message-search">Search messages</label>
        <div class="admin-contact-search-control">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input
                type="search"
                id="contact-message-search"
                name="search"
                value="{{ $filters['search'] }}"
                maxlength="255"
                placeholder="Name, email, subject, or message"
            >
        </div>
    </div>

    <div class="admin-contact-filter-status">
        <label for="contact-message-status">Status</label>
        <select id="contact-message-status" name="status">
            <option value="">All statuses</option>
            <option value="unread" @selected($filters['status'] === 'unread')>Unread</option>
            <option value="read" @selected($filters['status'] === 'read')>Read</option>
            <option value="replied" @selected($filters['status'] === 'replied')>Replied</option>
        </select>
    </div>

    <div class="admin-contact-filter-actions">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-filter" aria-hidden="true"></i>
            <span>Apply Filters</span>
        </button>

        @if($filters['search'] !== '' || $filters['status'] !== '')
            <a href="{{ route('admin.contacts.index') }}" class="btn admin-contact-clear-filter">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                <span>Clear</span>
            </a>
        @endif
    </div>
</form>
