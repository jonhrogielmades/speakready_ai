@extends('layouts.app')

@section('content')
<style>
    /* Chat specific styles */
    .chat-container { display: flex; height: calc(100vh - 140px); background: var(--sf); border: 1px solid var(--bd); border-radius: 18px; overflow: hidden; }
    .chat-sidebar { width: 280px; border-right: 1px solid var(--bd); display: flex; flex-direction: column; }
    .chat-main { flex-grow: 1; display: flex; flex-direction: column; position: relative; }
    .chat-messages { flex-grow: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 24px; }
    
    .chat-bubble { max-width: 80%; padding: 16px 20px; border-radius: 18px; font-size: .95rem; line-height: 1.5; }
    .bubble-ai { background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.2); border-bottom-left-radius: 4px; color: var(--tx); align-self: flex-start; }
    .bubble-user { background: var(--pur); color: #fff; border-bottom-right-radius: 4px; align-self: flex-end; }
    
    .chat-input-area { padding: 20px; border-top: 1px solid var(--bd); background: rgba(0,0,0,0.2); }
    .chat-input-wrapper { display: flex; align-items: flex-end; background: var(--bg); border: 1px solid var(--bd); border-radius: 16px; padding: 8px 16px; transition: border-color 0.3s; }
    .chat-input-wrapper:focus-within { border-color: var(--pur); box-shadow: 0 0 0 3px rgba(139,92,246,0.1); }
    .chat-textarea { flex-grow: 1; background: transparent; border: none; color: var(--tx); resize: none; max-height: 150px; padding: 8px 0; outline: none; }
    .chat-send-btn { background: var(--pur); color: #fff; border: none; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-left: 12px; margin-bottom: 4px; cursor: pointer; transition: 0.2s; }
    .chat-send-btn:hover { opacity: 0.9; transform: scale(1.05); }

    .history-item { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; transition: 0.2s; color: var(--tx3); font-size: .9rem; display: flex; align-items: center; }
    .history-item:hover, .history-item.active { background: rgba(255,255,255,0.05); color: var(--tx); }
    .history-item i { margin-right: 12px; opacity: 0.7; }
</style>

<div class="db-section active p-0" style="height:100%">
    <div class="chat-container">
        
        <!-- Sidebar History -->
        <div class="chat-sidebar d-none d-md-flex">
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
                    <div style="width:40px;height:40px;background:var(--pur);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;margin-right:16px;font-size:1.2rem">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div>
                        <h6 style="color:var(--tx);margin:0;font-weight:700">SpeakReady AI Coach</h6>
                        <span style="font-size:.75rem;color:#34d399"><i class="fa-solid fa-circle text-success" style="font-size:.5rem;margin-right:4px"></i>Online</span>
                    </div>
                </div>
                <button class="btn btn-link text-muted"><i class="fa-solid fa-ellipsis-vertical"></i></button>
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
                        Hello {{ Auth::user()->name }}! I'm your dedicated SpeakReady AI Coach. I'm here to help you prepare for your interviews, refine your resume, or practice specific behavioral questions. How can I assist you today?
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
            <div class="chat-input-area">
                <div class="chat-input-wrapper">
                    <textarea class="chat-textarea" id="chatMsg" rows="1" placeholder="Ask your AI Coach anything..." oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                    <button class="chat-send-btn" onclick="sendMsg()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
                <div style="text-align:center;margin-top:12px;font-size:.7rem;color:var(--tx3)">
                    AI Coach can make mistakes. Always verify critical advice before real interviews.
                </div>
            </div>
        </div>
    </div>

    <script>
        let coachChatHistory = [];
        let currentConversationId = null;

        async function sendMsg() {
            const ta = document.getElementById('chatMsg');
            const box = document.getElementById('chatBox');
            const text = ta.value.trim();
            if(!text) return;

            // Add user message
            const initialHtml = `{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}`;
            
            // Create user bubble
            const userMsgDiv = document.createElement('div');
            userMsgDiv.className = 'd-flex justify-content-end mt-3 dynamic-msg';
            userMsgDiv.style.gap = '16px';
            userMsgDiv.innerHTML = `
                    <div class="chat-bubble bubble-user">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
                    <div style="width:36px;height:36px;background:rgba(255,255,255,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--tx);flex-shrink:0;font-weight:700">
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

        function formatMarkdown(text) {
            // Escape HTML first to prevent XSS
            let formatted = escapeHtml(text);
            
            // Very basic markdown formatting for chat bubble
            return formatted
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>');
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
                                <div style="width:36px;height:36px;background:rgba(255,255,255,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--tx);flex-shrink:0;font-weight:700">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
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

        document.getElementById('chatMsg').addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMsg();
            }
        });
    </script>
</div>
@endsection
