@extends('desktop.layouts.app')
@section('title', 'Philippines Readiness Coach')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/coach.css?v=1') }}" data-page-style="user-coach">
<link rel="stylesheet" href="{{ asset('css/desktop/user/coach-2.css?v=1') }}" data-page-style="user-coach-2">
@endpush

@section('content')
@php
    $coachUser = Auth::user();
    $coachUserInitial = $coachUser ? strtoupper(substr((string) $coachUser->name, 0, 1)) : 'U';
    $coachUserPhotoUrl = null;

    if ($coachUser?->profile_photo_path) {
        $coachPhotoPath = $coachUser->profile_photo_path;
        $coachUserPhotoUrl = Str::startsWith($coachPhotoPath, ['http://', 'https://', 'data:'])
            ? $coachPhotoPath
            : asset('storage/' . $coachPhotoPath);
    }
@endphp
@include('desktop.partials.page-hero-styles')

<div class="db-section active" id="ai-coach-page" data-chat-url="{{ route('user.coach.chat') }}" data-conversation-url="{{ url('/coach/conversation') }}" data-clear-url="{{ route('user.coach.clear') }}" style="height:100%">
    <div class="sr-page-hero coach-progress-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="coach-hero-icon"><i class="fa-solid fa-headset"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a7 7 0 0 0-7 7v3a4 4 0 0 0 4 4h1v-6H7v-1a5 5 0 0 1 10 0v1h-3v6h1a4 4 0 0 0 4-4v-3a7 7 0 0 0-7-7Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 21h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Philippines Readiness Coach
                    </h4>
                    <p class="sr-page-hero-subtitle">Ask for advice, resume feedback, and focused practice guidance.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="coachPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="coachBlue" x1="62" y1="38" x2="164" y2="116"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#coachPanel)" stroke="#BFDBFE" stroke-width="3"/><rect x="64" y="53" width="92" height="56" rx="20" fill="url(#coachBlue)"/><circle cx="92" cy="79" r="7" fill="#EFF6FF"/><circle cx="128" cy="79" r="7" fill="#EFF6FF"/><path d="M92 96h36" stroke="#EFF6FF" stroke-width="6" stroke-linecap="round"/><path d="M110 53V38" stroke="#2563EB" stroke-width="6" stroke-linecap="round"/><circle cx="110" cy="34" r="8" fill="#22C55E"/><path d="M156 70h22v24h-13l-9 9V70Z" fill="#BAE6FD"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="chat-container animate-fade-up">
        
        <!-- Sidebar History -->
        <div class="chat-sidebar d-none d-md-flex" id="coach-sidebar">
            <div style="padding:20px; border-bottom:1px solid var(--bd);">
                <button class="btn btn-outline-primary w-100" style="border-radius:12px;font-weight:600" onclick="newConversation()">
                    <i class="fa-solid fa-plus me-2"></i> New Conversation
                </button>
            </div>
            <div style="overflow-y:auto; flex-grow:1" id="conversationsList">
                <div style="padding:16px 16px 8px; font-size:.75rem; font-weight:700; color:var(--tx3); text-transform:uppercase; letter-spacing:1px">Recent</div>
                @forelse($recentConversations as $conv)
                    <div class="history-item" id="conv-{{ $conv->id }}">
                        <div class="d-flex align-items-center flex-grow-1" onclick="loadConversation({{ $conv->id }})">
                            <i class="fa-regular fa-message"></i> 
                            <span class="text-truncate" style="max-width: 150px;">{{ $conv->title ?: 'New Conversation' }}</span>
                        </div>
                        <button class="btn btn-link text-danger p-0 ms-2" onclick="deleteConversation({{ $conv->id }})">
                            <i class="fa-solid fa-trash-can" style="margin:0;"></i>
                        </button>
                    </div>
                @empty
                    <div style="padding:0 16px; font-size:.8rem; color:var(--tx3);">No recent conversations</div>
                @endforelse
                
                <div style="padding:16px 16px 8px; font-size:.75rem; font-weight:700; color:var(--tx3); text-transform:uppercase; letter-spacing:1px; margin-top: 10px;">Older</div>
                @forelse($olderConversations as $conv)
                    <div class="history-item" id="conv-{{ $conv->id }}">
                        <div class="d-flex align-items-center flex-grow-1" onclick="loadConversation({{ $conv->id }})">
                            <i class="fa-regular fa-message"></i> 
                            <span class="text-truncate" style="max-width: 150px;">{{ $conv->title ?: 'New Conversation' }}</span>
                        </div>
                        <button class="btn btn-link text-danger p-0 ms-2" onclick="deleteConversation({{ $conv->id }})">
                            <i class="fa-solid fa-trash-can" style="margin:0;"></i>
                        </button>
                    </div>
                @empty
                    <div style="padding:0 16px; font-size:.8rem; color:var(--tx3);">No older conversations</div>
                @endforelse
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-main">
            <!-- Header -->
            <div class="coach-chat-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="coach-status">Online</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="coach-actions" id="coachActions">
                        <button class="coach-actions-toggle" id="coachActionsToggle" type="button" aria-label="Open conversation actions" aria-expanded="false" aria-controls="coachActionsMenu" onclick="toggleCoachActions(event)">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <div class="coach-actions-menu" id="coachActionsMenu" role="menu" aria-labelledby="coachActionsToggle">
                            <button class="coach-actions-item" type="button" role="menuitem" onclick="newConversation(); closeCoachActions();">
                                <i class="fa-solid fa-plus"></i>
                                <span>New conversation</span>
                            </button>
                            <div class="coach-actions-divider"></div>
                            <div class="coach-actions-heading">Recent history</div>
                            @forelse($recentConversations as $conv)
                                <button class="coach-actions-history" type="button" role="menuitem" onclick="loadConversation({{ $conv->id }}); closeCoachActions();">
                                    <i class="fa-regular fa-message"></i>
                                    <span>{{ $conv->title ?: 'New Conversation' }}</span>
                                </button>
                            @empty
                                <div class="coach-actions-empty">No recent conversations</div>
                            @endforelse
                            <div class="coach-actions-heading">Older history</div>
                            @forelse($olderConversations as $conv)
                                <button class="coach-actions-history" type="button" role="menuitem" onclick="loadConversation({{ $conv->id }}); closeCoachActions();">
                                    <i class="fa-regular fa-clock"></i>
                                    <span>{{ $conv->title ?: 'New Conversation' }}</span>
                                </button>
                            @empty
                                <div class="coach-actions-empty">No older conversations</div>
                            @endforelse
                            <div class="coach-actions-divider"></div>
                            <button class="coach-actions-item danger" type="button" role="menuitem" onclick="deleteCurrentConversation();">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Delete convo</span>
                            </button>
                            <button class="coach-actions-item danger" type="button" role="menuitem" onclick="clearCoachHistory();">
                                <i class="fa-solid fa-broom"></i>
                                <span>Clear all history</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="chat-messages" id="chatBox">
                <div class="text-center" style="margin-bottom:16px" id="chatDateBadge">
                    <span class="db-badge" style="background:rgba(255,255,255,0.05);color:var(--tx3)">Today</span>
                </div>

                <div class="coach-msg-row" id="welcomeMsg">
                    <div class="coach-avatar">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="chat-bubble bubble-ai">
                        Hello {{ Auth::user()->name }}! I can use your competency map and verified story index to explain scores, rehearse truthful answers, and prepare your next job-specific practice step. I will never invent experience for you.
                    </div>
                </div>
                
                <div id="typingIndicator" class="d-none coach-msg-row">
                     <div class="coach-avatar">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="chat-bubble bubble-ai" style="padding:12px 20px;display:flex;align-items:center;gap:4px">
                        <div style="width:8px;height:8px;background:rgba(255,255,255,0.5);border-radius:50%;animation:pulse 1.5s infinite"></div>
                        <div style="width:8px;height:8px;background:rgba(255,255,255,0.5);border-radius:50%;animation:pulse 1.5s infinite .2s"></div>
                        <div style="width:8px;height:8px;background:rgba(255,255,255,0.5);border-radius:50%;animation:pulse 1.5s infinite .4s"></div>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="chat-input-area" id="coach-input-area">
                <div class="chat-attachment-preview" id="chatAttachmentPreview" aria-live="polite"></div>
                <div class="chat-input-wrapper">
                    <input class="chat-file-input" id="coachFiles" type="file" multiple accept=".pdf,.doc,.docx,.txt,.rtf,.csv,.png,.jpg,.jpeg,.webp">
                    <button class="chat-attachment-btn" type="button" aria-label="Attach interview file" title="Attach resume, certificate, PDF, DOCX, or image" onclick="document.getElementById('coachFiles').click()">
                        <i class="fa-solid fa-paperclip"></i>
                    </button>
                    <textarea class="chat-textarea" id="chatMsg" rows="1" placeholder="Ask about interviews, resumes, certificates..." oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                    <button class="chat-send-btn" onclick="sendMsg()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
                <div class="coach-disclaimer">
                    <i class="fa-regular fa-circle-info" aria-hidden="true"></i>
                    The coach can make mistakes. Verify advice and keep every personal claim truthful.
                </div>
            </div>
        </div>
    </div>

    <script>
        let coachChatHistory = [];
        let currentConversationId = null;
        const initialCoachPrompt = @json((string) request('ask', ''));
        let coachSelectedFiles = [];
        let coachSending = false;
        const coachAllowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'csv', 'png', 'jpg', 'jpeg', 'webp'];
        const coachMaxFiles = 3;
        const coachMaxFileBytes = 5 * 1024 * 1024;
        const coachUserPhotoUrl = @json($coachUserPhotoUrl);
        const coachUserInitial = @json($coachUserInitial);

        function toggleCoachActions(event) {
            event.stopPropagation();
            const menu = document.getElementById('coachActionsMenu');
            const toggle = document.getElementById('coachActionsToggle');
            const willOpen = !menu.classList.contains('open');

            menu.classList.toggle('open', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        }

        function closeCoachActions() {
            const menu = document.getElementById('coachActionsMenu');
            const toggle = document.getElementById('coachActionsToggle');

            if (menu) menu.classList.remove('open');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }

        function handleCoachFiles(input) {
            const incomingFiles = Array.from(input.files || []);
            const acceptedFiles = [];

            incomingFiles.forEach(file => {
                const extension = (file.name.split('.').pop() || '').toLowerCase();

                if (!coachAllowedExtensions.includes(extension)) {
                    alert(`${file.name} is not a supported interview file type.`);
                    return;
                }

                if (file.size > coachMaxFileBytes) {
                    alert(`${file.name} is larger than the 5MB upload limit.`);
                    return;
                }

                acceptedFiles.push(file);
            });

            const availableSlots = Math.max(0, coachMaxFiles - coachSelectedFiles.length);
            coachSelectedFiles = coachSelectedFiles.concat(acceptedFiles.slice(0, availableSlots));
            if (acceptedFiles.length > availableSlots) {
                alert('You can attach up to 3 files at a time.');
            }

            input.value = '';
            renderCoachAttachments();
        }

        function renderCoachAttachments() {
            const preview = document.getElementById('chatAttachmentPreview');
            if (!preview) return;

            if (!coachSelectedFiles.length) {
                preview.classList.remove('has-files');
                preview.innerHTML = '';
                return;
            }

            preview.classList.add('has-files');
            preview.innerHTML = coachSelectedFiles.map((file, index) => `
                <div class="chat-attachment-chip">
                    <i class="fa-solid ${coachFileIcon(file.name)}" aria-hidden="true"></i>
                    <span title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span>
                    <small>${formatFileSize(file.size)}</small>
                    <button class="chat-attachment-remove" type="button" aria-label="Remove ${escapeHtml(file.name)}" onclick="removeCoachAttachment(${index})">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            `).join('');
        }

        function removeCoachAttachment(index) {
            coachSelectedFiles.splice(index, 1);
            renderCoachAttachments();
        }

        function clearCoachAttachments() {
            coachSelectedFiles = [];
            renderCoachAttachments();
        }

        function coachFileIcon(fileName) {
            const extension = (fileName.split('.').pop() || '').toLowerCase();
            if (extension === 'pdf') return 'fa-file-pdf';
            if (['doc', 'docx', 'rtf', 'txt'].includes(extension)) return 'fa-file-lines';
            if (extension === 'csv') return 'fa-file-csv';
            if (['png', 'jpg', 'jpeg', 'webp'].includes(extension)) return 'fa-file-image';
            return 'fa-file';
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return `${bytes} B`;
            if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1).replace(/\.0$/, '')} KB`;
            return `${(bytes / (1024 * 1024)).toFixed(1).replace(/\.0$/, '')} MB`;
        }

        function renderBubbleAttachments(files) {
            if (!files.length) return '';

            return `
                <div class="chat-attachment-bubble">
                    ${files.map(file => `
                        <div class="chat-attachment-bubble-item">
                            <i class="fa-solid ${coachFileIcon(file.name)}" aria-hidden="true"></i>
                            <span>${escapeHtml(file.name)}</span>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function renderCoachUserAvatar() {
            if (coachUserPhotoUrl) {
                return `<img class="coach-user-avatar-img" src="${escapeHtml(coachUserPhotoUrl)}" alt="Avatar">`;
            }

            return escapeHtml(coachUserInitial || 'U');
        }

        function coachEndpoint(datasetKey, fallback) {
            return document.getElementById('ai-coach-page')?.dataset[datasetKey] || fallback;
        }

        async function sendMsg() {
            const ta = document.getElementById('chatMsg');
            const box = document.getElementById('chatBox');
            const text = ta.value.trim();
            const files = coachSelectedFiles.slice();
            const displayText = text || (files.length ? 'Please review the attached interview file(s).' : '');
            if(!displayText || coachSending) return;
            coachSending = true;

            // Create user bubble
            const userMsgDiv = document.createElement('div');
            userMsgDiv.className = 'd-flex justify-content-end mt-3 dynamic-msg';
            userMsgDiv.style.gap = '12px';
            userMsgDiv.innerHTML = `
                    <div class="chat-bubble bubble-user">
                        ${escapeHtml(displayText).replace(/\n/g, '<br>')}
                        ${renderBubbleAttachments(files)}
                    </div>
                    <div class="coach-user-avatar">
                        ${renderCoachUserAvatar()}
                    </div>
            `;
            box.insertBefore(userMsgDiv, document.getElementById('typingIndicator'));
            
            ta.value = '';
            ta.style.height = '';
            clearCoachAttachments();
            box.scrollTop = box.scrollHeight;
            
            // Show typing
            const typing = document.getElementById('typingIndicator');
            typing.classList.remove('d-none');
            typing.classList.add('d-flex');
            box.scrollTop = box.scrollHeight;

            try {
                const formData = new FormData();
                formData.append('message', displayText);
                formData.append('history', JSON.stringify(coachChatHistory));
                if (currentConversationId) {
                    formData.append('conversation_id', currentConversationId);
                }
                files.forEach(file => formData.append('coach_attachments[]', file));

                // Call AI Backend
                const response = await fetch(coachEndpoint('chatUrl', @json(route('user.coach.chat'))), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                if (!response.ok) {
                    const errorPayload = await response.json().catch(() => null);
                    throw new Error(errorPayload?.message || 'Network response was not ok');
                }

                const data = await response.json();
                const aiResponse = data.response;
                
                if (data.conversation_id && !currentConversationId) {
                    currentConversationId = data.conversation_id;
                    // Add to sidebar if we just created it
                    const recentDiv = document.querySelector('#conversationsList > div:nth-child(2)'); // The first 'No recent conversations' or item
                    if (recentDiv && recentDiv.textContent.includes('No recent')) {
                        recentDiv.outerHTML = ''; // Remove empty message
                    }
                    
                    const newItem = document.createElement('div');
                    newItem.className = 'history-item active';
                    newItem.id = 'conv-' + data.conversation_id;
                    newItem.innerHTML = `
                        <div class="d-flex align-items-center flex-grow-1" onclick="loadConversation(${data.conversation_id})">
                            <i class="fa-regular fa-message"></i> 
                            <span class="text-truncate" style="max-width: 150px;">${escapeHtml(data.title || 'New Conversation')}</span>
                        </div>
                        <button class="btn btn-link text-danger p-0 ms-2" onclick="deleteConversation(${data.conversation_id})">
                            <i class="fa-solid fa-trash-can" style="margin:0;"></i>
                        </button>
                    `;
                    document.getElementById('conversationsList').insertBefore(newItem, document.querySelector('#conversationsList > div:nth-child(1)').nextSibling);
                }

                // Update History
                const historyContent = files.length
                    ? `${displayText}\n\nAttached interview file(s):\n${files.map(file => `- ${file.name}`).join('\n')}`
                    : displayText;
                coachChatHistory.push({ role: 'user', content: historyContent });
                coachChatHistory.push({ role: 'ai', content: aiResponse });

                // Remove typing indicator
                typing.classList.remove('d-flex');
                typing.classList.add('d-none');

                // Add AI Message
                const aiMsgDiv = document.createElement('div');
                aiMsgDiv.className = 'coach-msg-row mt-3 dynamic-msg';
                aiMsgDiv.innerHTML = `
                        <div class="coach-avatar">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div class="chat-bubble bubble-ai">
                            ${formatMarkdown(aiResponse)}
                        </div>
                `;
                box.insertBefore(aiMsgDiv, typing);
                box.scrollTop = box.scrollHeight;
            } catch (error) {
                console.error('Error:', error);
                typing.classList.remove('d-flex');
                typing.classList.add('d-none');
                
                const errorMsgDiv = document.createElement('div');
                errorMsgDiv.className = 'coach-msg-row mt-3 dynamic-msg';
                errorMsgDiv.innerHTML = `
                        <div class="coach-avatar">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="chat-bubble bubble-ai" style="color:#ef4444; border-color:#ef4444">
                            ${escapeHtml(error.message || 'Sorry, I encountered an error communicating with the AI. Please try again later.')}
                        </div>
                `;
                box.insertBefore(errorMsgDiv, typing);
                box.scrollTop = box.scrollHeight;
            } finally {
                coachSending = false;
            }
        }
        
        function escapeHtml(unsafe) {
            return String(unsafe || '')
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        function formatInlineMarkdown(text) {
            return escapeHtml(text)
                .replace(/`([^`]+)`/g, '<code>$1</code>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>');
        }

        function flushList(listItems, ordered, parts) {
            if (!listItems.length) return;

            const tag = ordered ? 'ol' : 'ul';
            parts.push(`<${tag}>${listItems.map(item => `<li>${formatInlineMarkdown(item)}</li>`).join('')}</${tag}>`);
            listItems.length = 0;
        }

        function formatMarkdown(text) {
            const normalized = String(text || '')
                .replace(/\r\n/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .trim();

            if (!normalized) return '<div class="ai-response"><p>No response yet.</p></div>';

            const parts = [];
            const listItems = [];
            let listOrdered = false;

            normalized.split('\n').forEach(rawLine => {
                const line = rawLine.trim();

                if (!line) {
                    flushList(listItems, listOrdered, parts);
                    return;
                }

                const bullet = line.match(/^[-*]\s+(.+)$/);
                const numbered = line.match(/^\d+[.)]\s+(.+)$/);

                if (bullet || numbered) {
                    const ordered = Boolean(numbered);

                    if (listItems.length && ordered !== listOrdered) {
                        flushList(listItems, listOrdered, parts);
                    }

                    listOrdered = ordered;
                    listItems.push((bullet || numbered)[1]);
                    return;
                }

                flushList(listItems, listOrdered, parts);

                const plainHeading = line.match(/^\*\*([^*]{2,60})\*\*:?\s*$/);
                const colonHeading = line.match(/^([A-Za-z][A-Za-z\s/&-]{2,48}):$/);

                if (plainHeading || colonHeading) {
                    parts.push(`<span class="ai-section-title">${formatInlineMarkdown((plainHeading || colonHeading)[1])}</span>`);
                    return;
                }

                parts.push(`<p>${formatInlineMarkdown(line)}</p>`);
            });

            flushList(listItems, listOrdered, parts);

            return `<div class="ai-response">${parts.join('')}</div>`;
        }
        
        function newConversation() {
            // Reset state
            coachChatHistory = [];
            currentConversationId = null;
            
            // Remove active classes from sidebar
            document.querySelectorAll('.history-item').forEach(el => el.classList.remove('active'));

            // Remove all dynamic messages
            document.querySelectorAll('.dynamic-msg').forEach(e => e.remove());
            clearCoachAttachments();
            
            // Focus the input
            const ta = document.getElementById('chatMsg');
            ta.value = '';
            ta.style.height = '';
            ta.focus();
        }

        async function loadConversation(id) {
            try {
                const response = await fetch(coachEndpoint('conversationUrl', @json(url('/coach/conversation'))) + '/' + encodeURIComponent(id), {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                if (!response.ok) throw new Error('Failed to load conversation');
                
                const data = await response.json();
                
                // Update State
                currentConversationId = data.conversation.id;
                coachChatHistory = [];
                
                // Update UI active state
                document.querySelectorAll('.history-item').forEach(el => el.classList.remove('active'));
                const activeItem = document.getElementById('conv-' + id);
                if (activeItem) activeItem.classList.add('active');
                
                // Clear chatbox
                document.querySelectorAll('.dynamic-msg').forEach(e => e.remove());
                
                const box = document.getElementById('chatBox');
                const typing = document.getElementById('typingIndicator');
                
                // Render messages
                data.conversation.messages.forEach(msg => {
                    coachChatHistory.push({ role: msg.role, content: msg.content });
                    
                    const msgDiv = document.createElement('div');
                    if (msg.role === 'user') {
                        msgDiv.className = 'd-flex justify-content-end mt-3 dynamic-msg';
                        msgDiv.style.gap = '12px';
                        msgDiv.innerHTML = `
                                <div class="chat-bubble bubble-user">${escapeHtml(msg.content).replace(/\n/g, '<br>')}</div>
                                <div class="coach-user-avatar">
                                    ${renderCoachUserAvatar()}
                                </div>
                        `;
                    } else {
                        msgDiv.className = 'coach-msg-row mt-3 dynamic-msg';
                        msgDiv.innerHTML = `
                                <div class="coach-avatar">
                                    <i class="fa-solid fa-robot"></i>
                                </div>
                                <div class="chat-bubble bubble-ai">
                                    ${formatMarkdown(msg.content)}
                                </div>
                        `;
                    }
                    box.insertBefore(msgDiv, typing);
                });
                box.scrollTop = box.scrollHeight;

            } catch (error) {
                console.error(error);
                alert('Could not load conversation');
            }
        }

        async function deleteConversation(id) {
            closeCoachActions();
            if (!confirm('Are you sure you want to delete this conversation?')) return;
            
            try {
                const response = await fetch(coachEndpoint('conversationUrl', @json(url('/coach/conversation'))) + '/' + encodeURIComponent(id), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                if (!response.ok) throw new Error('Failed to delete conversation');
                
                // Remove from UI
                const item = document.getElementById('conv-' + id);
                if (item) item.remove();
                
                // If it was the active conversation, start a new one
                if (currentConversationId === id) {
                    newConversation();
                }
            } catch (error) {
                console.error(error);
                alert('Could not delete conversation');
            }
        }

        function deleteCurrentConversation() {
            if (!currentConversationId) {
                closeCoachActions();
                alert('No active conversation to delete.');
                return;
            }

            deleteConversation(currentConversationId);
        }

        async function clearCoachHistory() {
            closeCoachActions();

            if (!confirm('Are you sure you want to clear all AI Coach conversation history?')) return;

            try {
                const response = await fetch(coachEndpoint('clearUrl', @json(route('user.coach.clear'))), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (!response.ok) throw new Error('Failed to clear conversations');

                document.querySelectorAll('.history-item').forEach(item => item.remove());
                const list = document.getElementById('conversationsList');
                if (list) {
                    const recentHeading = list.querySelector('div:first-child');
                    const olderHeading = Array.from(list.children).find(child => child.textContent.trim() === 'Older');
                    const recentEmpty = document.createElement('div');
                    recentEmpty.style.cssText = 'padding:0 16px; font-size:.8rem; color:var(--tx3);';
                    recentEmpty.textContent = 'No recent conversations';

                    const olderEmpty = document.createElement('div');
                    olderEmpty.style.cssText = 'padding:0 16px; font-size:.8rem; color:var(--tx3);';
                    olderEmpty.textContent = 'No older conversations';

                    if (recentHeading && !recentHeading.nextElementSibling?.textContent.includes('No recent')) {
                        recentHeading.insertAdjacentElement('afterend', recentEmpty);
                    }
                    if (olderHeading && !olderHeading.nextElementSibling?.textContent.includes('No older')) {
                        olderHeading.insertAdjacentElement('afterend', olderEmpty);
                    }
                }
                newConversation();
            } catch (error) {
                console.error(error);
                alert('Could not clear conversation history');
            }
        }

        document.getElementById('chatMsg').addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMsg();
            }
        });

        document.getElementById('coachFiles')?.addEventListener('change', function () {
            handleCoachFiles(this);
        });

        document.addEventListener('click', function (event) {
            const actions = document.getElementById('coachActions');
            if (actions && !actions.contains(event.target)) {
                closeCoachActions();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeCoachActions();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const prompt = String(initialCoachPrompt || '').trim();
            const input = document.getElementById('chatMsg');

            if (!prompt || !input) {
                return;
            }

            input.value = prompt;
            input.style.height = '';
            input.style.height = input.scrollHeight + 'px';
            window.setTimeout(sendMsg, 250);
        });
    </script>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#chatBox', popover: { title: 'Coach Messages', description: 'Your AI Coach responds here with interview advice, resume tips, and practice guidance.', side: 'bottom', align: 'center' }},
            { element: '#coach-input-area', popover: { title: 'Ask Anything', description: 'Type a question or prompt here, then press Enter to send it.', side: 'top', align: 'center' }}
        ];

        const stepsDesktop = [
            { element: '#coach-sidebar', popover: { title: 'Conversation History', description: 'Start a new chat or return to an earlier coaching conversation.', side: 'right', align: 'start' }},
            { element: '#chatBox', popover: { title: 'Coach Messages', description: 'Your AI Coach responds here with interview advice, resume tips, and practice guidance.', side: 'bottom', align: 'center' }},
            { element: '#coach-input-area', popover: { title: 'Ask Anything', description: 'Type a question or prompt here, then press Enter to send it.', side: 'top', align: 'center' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_coach',
            serverDetectedMobile: false,
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
