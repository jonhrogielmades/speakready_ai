<section class="admin-contact-reply-panel" aria-labelledby="contact-reply-heading">
    <div class="admin-contact-reply-heading-row">
        <div>
            <h5 id="contact-reply-heading">
                <i class="fa-solid fa-reply" aria-hidden="true"></i>
                Reply via Email
            </h5>
            <p>Send a response directly to <strong>{{ $contact->email }}</strong>.</p>
        </div>

        @if($contact->status === 'replied')
            <span class="admin-contact-replied-indicator">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                Replied
            </span>
        @endif
    </div>

    <form
        method="POST"
        action="{{ route('admin.contacts.reply', $contact) }}"
        class="admin-contact-reply-form"
        data-contact-reply-form
    >
        @csrf

        <div class="admin-contact-form-field">
            <label for="reply-subject">Subject</label>
            <input
                type="text"
                id="reply-subject"
                name="reply_subject"
                value="{{ old('reply_subject', $replySubject) }}"
                maxlength="255"
                autocomplete="off"
                required
                @class(['is-invalid' => $errors->contactReply->has('reply_subject')])
                @if($errors->contactReply->has('reply_subject'))
                    aria-invalid="true"
                    aria-describedby="reply-subject-error"
                @endif
            >
            @error('reply_subject', 'contactReply')
                <div class="admin-contact-field-error" id="reply-subject-error" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="admin-contact-form-field">
            <label for="reply-message">Message</label>
            <textarea
                id="reply-message"
                name="reply_message"
                rows="8"
                maxlength="10000"
                placeholder="Write your response here..."
                required
                @class(['is-invalid' => $errors->contactReply->has('reply_message')])
                @if($errors->contactReply->has('reply_message'))
                    aria-invalid="true"
                    aria-describedby="reply-message-error"
                @endif
            >{{ old('reply_message') }}</textarea>
            @error('reply_message', 'contactReply')
                <div class="admin-contact-field-error" id="reply-message-error" role="alert">{{ $message }}</div>
            @enderror
        </div>

        <div class="admin-contact-reply-actions">
            <button type="submit" class="btn btn-primary" data-contact-reply-submit>
                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                <span data-contact-reply-submit-label>Send Reply via Email</span>
            </button>
            <a href="mailto:{{ $contact->email }}?subject={{ rawurlencode(old('reply_subject', $replySubject)) }}" class="btn admin-contact-email-app-link">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                <span>Open Email App</span>
            </a>
        </div>
    </form>
</section>
