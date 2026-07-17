@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Readiness Coach')

@section('content')
<style>
    /* Chat specific styles */
    .chat-container {
        display: flex;
        height: calc(100vh - 274px);
        min-height: 420px;
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .chat-sidebar { width: 280px; border-right: 1px solid var(--bd); display: flex; flex-direction: column; background: linear-gradient(180deg, rgba(255,255,255,0.02) 0%, transparent 100%); }
    .chat-main { flex-grow: 1; display: flex; flex-direction: column; position: relative; min-height: 0; }
    .chat-messages { flex-grow: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 24px; }

    .chat-bubble { max-width: 80%; padding: 16px 20px; border-radius: 20px; font-size: .95rem; line-height: 1.5; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .bubble-ai { background: linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(139,92,246,0.05) 100%); border: 1px solid rgba(139,92,246,0.2); border-bottom-left-radius: 4px; color: var(--tx); align-self: flex-start; box-shadow: inset 0 2px 10px rgba(255,255,255,0.05); }
    .bubble-user { background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%); color: #fff; border-bottom-right-radius: 4px; align-self: flex-end; border: none; }
    .ai-response { display: grid; gap: 10px; line-height: 1.62; }
    .ai-response p { margin: 0; }
    .ai-response strong { color: var(--tx); font-weight: 800; }
    .ai-response em { color: var(--tx2); font-style: italic; }
    .ai-response .ai-section-title {
        display: block;
        margin-top: 2px;
        color: #60a5fa;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    .ai-response ul, .ai-response ol {
        margin: 0;
        padding-left: 1.15rem;
        display: grid;
        gap: 7px;
    }
    .ai-response li { padding-left: 2px; }
    .ai-response code {
        padding: 1px 5px;
        border-radius: 6px;
        background: rgba(255,255,255,0.08);
        color: var(--tx);
        font-size: .88em;
    }

    .chat-input-area { padding: 20px; border-top: 1px solid var(--bd); background: rgba(255,255,255,0.02); flex-shrink: 0; }
    .chat-input-wrapper { display: flex; align-items: flex-end; background: var(--bg3); border: 1px solid var(--bd); border-radius: 16px; padding: 8px 16px; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .chat-input-wrapper:focus-within { border-color: var(--pur) !important; box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15); background: var(--sf); }
    .chat-textarea { flex-grow: 1; background: transparent; border: none; color: var(--tx); resize: none; max-height: 120px; padding: 8px 0; outline: none; font-family: "Space Grotesk", sans-serif; font-size: 0.95rem; }
    .chat-send-btn { background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%); color: #fff; border: none; width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-left: 12px; margin-bottom: 4px; cursor: pointer; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); flex-shrink: 0; box-shadow: 0 4px 15px rgba(139,92,246,0.3); }
    .chat-send-btn:hover { transform: scale(1.05) translateY(-2px); box-shadow: 0 6px 20px rgba(139,92,246,0.5); }

    .history-item { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; transition: 0.2s; color: var(--tx3); font-size: .9rem; display: flex; align-items: center; }
    .history-item:hover, .history-item.active { background: rgba(255,255,255,0.05); color: var(--tx); }
    .history-item i { margin-right: 12px; opacity: 0.7; }
    .coach-actions { position: relative; }
    .coach-actions-toggle {
        width: 36px;
        height: 36px;
        border: 1px solid transparent;
        border-radius: 10px;
        background: transparent;
        color: var(--tx3);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
    }
    .coach-actions-toggle:hover, .coach-actions-toggle[aria-expanded="true"] {
        background: rgba(255,255,255,0.06);
        border-color: var(--bd);
        color: var(--tx);
    }
    .coach-actions-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        z-index: 30;
        width: min(320px, calc(100vw - 28px));
        max-height: min(70dvh, 520px);
        padding: 6px;
        border: 1px solid var(--bd);
        border-radius: 12px;
        background: var(--sf);
        box-shadow: 0 18px 45px rgba(0,0,0,0.22);
        display: none;
        overflow-y: auto;
    }
    .coach-actions-menu.open { display: block; }
    .coach-actions-item {
        width: 100%;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--tx);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        font-size: .88rem;
        text-align: left;
        transition: 0.2s;
    }
    .coach-actions-item:hover { background: rgba(255,255,255,0.06); }
    .coach-actions-item i {
        width: 16px;
        text-align: center;
        color: var(--tx3);
    }
    .coach-actions-item.danger { color: #ef4444; }
    .coach-actions-item.danger i { color: #ef4444; }
    .coach-actions-divider {
        height: 1px;
        background: var(--bd);
        margin: 6px;
    }
    .coach-actions-heading {
        padding: 8px 10px 5px;
        color: var(--tx3);
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    .coach-actions-history {
        width: 100%;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--tx2);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        text-align: left;
        transition: 0.2s;
    }
    .coach-actions-history:hover {
        background: rgba(255,255,255,0.06);
        color: var(--tx);
    }
    .coach-actions-history i {
        width: 16px;
        color: var(--tx3);
        text-align: center;
        flex: 0 0 16px;
    }
    .coach-actions-history span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.82rem;
    }
    .coach-actions-empty {
        padding: 8px 10px 10px;
        color: var(--tx3);
        font-size: 0.78rem;
    }

    /* Mobile-specific: full height chat within the mobile layout */
    @media (max-width: 767px) {
        #ai-coach-page {
            height: auto !important;
        }
        .chat-container {
            height: calc(100dvh - var(--mob-top-h, 56px) - var(--mob-nav-h, 78px) - 132px) !important;
            min-height: 380px;
            border-radius: 14px !important;
            flex-direction: column !important;
        }
        .chat-sidebar { display: none !important; }
        .chat-main > div:first-child {
            padding: 11px 12px !important;
        }
        .chat-main > div:first-child [style*="width:40px"] {
            width: 34px !important;
            height: 34px !important;
            margin-right: 10px !important;
            border-radius: 10px !important;
        }
        .chat-messages { padding: 12px; gap: 10px; }
        .chat-input-area { padding: 10px 12px; }
        .chat-bubble { max-width: 92%; padding: 10px 13px; font-size: 0.84rem; line-height: 1.45; }
        .ai-response { gap: 8px; line-height: 1.55; }
        .chat-input-wrapper {
            padding: 7px 8px 7px 12px;
            border-radius: 14px;
        }
        .chat-send-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            margin-left: 8px;
        }
    }
    
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    
    /* Animations */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
</style>
@include('partials.page-hero-styles')

<div class="db-section active" id="ai-coach-page" style="height:100%">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a7 7 0 0 0-7 7v3a4 4 0 0 0 4 4h1v-6H7v-1a5 5 0 0 1 10 0v1h-3v6h1a4 4 0 0 0 4-4v-3a7 7 0 0 0-7-7Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 21h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Philippines Readiness Coach
                </h4>
                <p class="sr-page-hero-subtitle">Ask for interview advice, resume feedback, and focused practice guidance.</p>
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
            <div style="padding:16px 24px; border-bottom:1px solid var(--bd); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.02)">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="text-gradient-primary" style="margin:0;font-weight:800;letter-spacing:-0.5px;">SpeakReady Philippines Readiness Coach</h6>
                        <span style="font-size:.75rem;color:#34d399"><i class="fa-solid fa-circle text-success" style="font-size:.5rem;margin-right:4px"></i>Online</span>
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

                <div class="d-flex" style="gap:16px" id="welcomeMsg">
                    <div style="width:36px;height:36px;background:var(--pur);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="chat-bubble bubble-ai">
                        Hello {{ Auth::user()->name }}! I can use your competency map and verified story index to explain scores, rehearse truthful answers, and prepare your next job-specific practice step. I will never invent experience for you.
                    </div>
                </div>
                
                <div id="typingIndicator" class="d-none" style="gap:16px">
                     <div style="width:36px;height:36px;background:var(--pur);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0">
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
                <div class="chat-input-wrapper">
                    <textarea class="chat-textarea" id="chatMsg" rows="1" placeholder="Ask about a Philippine HR screen, BPO answer, salary expectation, score, story, or target role..." oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                    <button class="chat-send-btn" onclick="sendMsg()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
                <div style="text-align:center;margin-top:12px;font-size:.7rem;color:var(--tx3)">
                    The coach can make mistakes. Verify advice and keep every personal claim truthful.
                </div>
            </div>
        </div>
    </div>

    <script>
        let coachChatHistory = [];
        let currentConversationId = null;

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

        async function sendMsg() {
            const ta = document.getElementById('chatMsg');
            const box = document.getElementById('chatBox');
            const text = ta.value.trim();
            if(!text) return;

            // Add user message
            const initialHtml = `
                @if(Auth::check() && Auth::user()->profile_photo_path)
                    @if(Str::startsWith(Auth::user()->profile_photo_path, ['http://', 'https://', 'data:']))
                        <img src="{{ Auth::user()->profile_photo_path }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                    @else
                        <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                    @endif
                @else
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                @endif
            `;
            
            // Create user bubble
            const userMsgDiv = document.createElement('div');
            userMsgDiv.className = 'd-flex justify-content-end mt-3 dynamic-msg';
            userMsgDiv.style.gap = '16px';
            userMsgDiv.innerHTML = `
                    <div class="chat-bubble bubble-user">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
                    <div style="width:36px;height:36px;background:rgba(255,255,255,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--tx);flex-shrink:0;font-weight:700;padding:0;overflow:hidden;border:1px solid var(--bd);">
                        ${initialHtml}
                    </div>
            `;
            box.insertBefore(userMsgDiv, document.getElementById('typingIndicator'));
            
            ta.value = '';
            ta.style.height = '';
            box.scrollTop = box.scrollHeight;
            
            // Show typing
            const typing = document.getElementById('typingIndicator');
            typing.classList.remove('d-none');
            typing.classList.add('d-flex');
            box.scrollTop = box.scrollHeight;

            try {
                // Call AI Backend
                const response = await fetch('{{ route("user.coach.chat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        message: text,
                        history: coachChatHistory,
                        conversation_id: currentConversationId
                    })
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
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
                coachChatHistory.push({ role: 'user', content: text });
                coachChatHistory.push({ role: 'ai', content: aiResponse });

                // Remove typing indicator
                typing.classList.remove('d-flex');
                typing.classList.add('d-none');

                // Add AI Message
                const aiMsgDiv = document.createElement('div');
                aiMsgDiv.className = 'd-flex mt-3 dynamic-msg';
                aiMsgDiv.style.gap = '16px';
                aiMsgDiv.innerHTML = `
                        <div style="width:36px;height:36px;background:var(--pur);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0">
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
                errorMsgDiv.className = 'd-flex mt-3 dynamic-msg';
                errorMsgDiv.style.gap = '16px';
                errorMsgDiv.innerHTML = `
                        <div style="width:36px;height:36px;background:var(--pur);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="chat-bubble bubble-ai" style="color:#ef4444; border-color:#ef4444">
                            Sorry, I encountered an error communicating with the AI. Please try again later.
                        </div>
                `;
                box.insertBefore(errorMsgDiv, typing);
                box.scrollTop = box.scrollHeight;
            }
        }
        
        function escapeHtml(unsafe) {
            return unsafe
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
            
            // Focus the input
            const ta = document.getElementById('chatMsg');
            ta.value = '';
            ta.style.height = '';
            ta.focus();
        }

        async function loadConversation(id) {
            try {
                const response = await fetch(`/coach/conversation/${id}`, {
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
                        msgDiv.style.gap = '16px';
                        msgDiv.innerHTML = `
                                <div class="chat-bubble bubble-user">${escapeHtml(msg.content).replace(/\n/g, '<br>')}</div>
                                <div style="width:36px;height:36px;background:rgba(255,255,255,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--tx);flex-shrink:0;font-weight:700;padding:0;overflow:hidden;border:1px solid var(--bd);">
                                    @if(Auth::check() && Auth::user()->profile_photo_path)
                                        @if(Str::startsWith(Auth::user()->profile_photo_path, ['http://', 'https://', 'data:']))
                                            <img src="{{ Auth::user()->profile_photo_path }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                                        @else
                                            <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                                        @endif
                                    @else
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    @endif
                                </div>
                        `;
                    } else {
                        msgDiv.className = 'd-flex mt-3 dynamic-msg';
                        msgDiv.style.gap = '16px';
                        msgDiv.innerHTML = `
                                <div style="width:36px;height:36px;background:var(--pur);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0">
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
                const response = await fetch(`/coach/conversation/${id}`, {
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
                const response = await fetch('{{ route("user.coach.clear") }}', {
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
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection



