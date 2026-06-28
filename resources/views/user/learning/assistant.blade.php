@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<style>
    .ll-nav-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 30px;
        background: var(--sf);
        color: var(--tx2);
        border: 1px solid var(--bd);
        text-decoration: none;
        font-weight: 500;
        transition: 0.3s;
        margin-right: 10px;
        margin-bottom: 10px;
    }
    .ll-nav-pill:hover, .ll-nav-pill.active {
        background: var(--pur);
        color: #fff;
        border-color: var(--pur);
        box-shadow: 0 4px 15px rgba(59,130,246,0.3);
    }

    .chat-container {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        height: 70vh;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    .chat-header {
        padding: 20px 25px;
        border-bottom: 1px solid var(--bd);
        display: flex;
        align-items: center;
        gap: 15px;
        background: rgba(59,130,246,0.05);
    }
    .chat-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--pur) 0%, #34d399 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.2rem;
    }
    .chat-body {
        flex-grow: 1;
        padding: 25px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .chat-bubble {
        max-width: 75%;
        padding: 15px 20px;
        border-radius: 18px;
        line-height: 1.5;
        font-size: 0.95rem;
    }
    .chat-bubble.ai {
        background: var(--bg);
        border: 1px solid var(--bd);
        color: var(--tx);
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }
    .chat-bubble.user {
        background: linear-gradient(135deg, var(--pur) 0%, #6d28d9 100%);
        color: #fff;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }
    .chat-input-area {
        padding: 20px 25px;
        border-top: 1px solid var(--bd);
        background: var(--bg);
    }
    .chat-input-wrap {
        display: flex;
        gap: 15px;
        background: var(--bg);
        border: 1px solid var(--bd);
        border-radius: 30px;
        padding: 5px 5px 5px 20px;
        align-items: center;
    }
    .chat-input {
        flex-grow: 1;
        background: transparent;
        border: none;
        color: var(--tx);
        outline: none;
    }
    .chat-send-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--pur);
        color: #fff;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
    }
    .chat-send-btn:hover {
        background: #7c3aed;
        transform: scale(1.05);
    }
    .suggestion-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }
    .suggestion-chip {
        background: transparent;
        border: 1px solid var(--pur);
        color: var(--pur);
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: 0.2s;
    }
    .suggestion-chip:hover {
        background: rgba(59,130,246,0.1);
    }
</style>

<div class="db-section active">
    <!-- Header & Navigation -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h3 style="font-weight:700;color:var(--tx);margin:0">Learning Lab</h3>
            <p style="color:var(--tx3);margin-top:5px;">Master your interview skills with structured, AI-powered learning.</p>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div class="mb-4 pb-2" style="overflow-x:auto;white-space:nowrap;">
        <a href="{{ route('user.learning') }}" class="ll-nav-pill"><i class="fa-solid fa-border-all"></i> Dashboard</a>
        <a href="{{ route('user.learning.star') }}" class="ll-nav-pill"><i class="fa-solid fa-star"></i> STAR Method Training</a>
        <a href="{{ route('user.learning.library') }}" class="ll-nav-pill"><i class="fa-solid fa-book-bookmark"></i> Answer Library</a>
        <a href="{{ route('user.learning.quiz') }}" class="ll-nav-pill"><i class="fa-solid fa-brain"></i> Mini Quizzes</a>
    </div>

    <div class="chat-container">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-avatar">
                <i class="fa-solid fa-robot"></i>
            </div>
            <div>
                <h5 style="color:var(--tx);margin:0;font-weight:700">SpeakReady Learning Assistant</h5>
                <div style="color:#34d399;font-size:0.8rem;font-weight:600"><i class="fa-solid fa-circle" style="font-size:0.5rem;vertical-align:middle"></i> Online</div>
            </div>
        </div>
        
        <!-- Chat Body -->
        <div class="chat-body" id="chatBody">
            <div class="chat-bubble ai">
                Hello! I am your personal AI Learning Assistant. I'm here to help you master interview concepts, provide answer suggestions, and explain difficult techniques. How can I assist you today?
            </div>
            
            <!-- User Message Example -->
            <div class="chat-bubble user">
                Can you explain the STAR method to me briefly?
            </div>
            
            <!-- AI Response Example -->
            <div class="chat-bubble ai">
                Absolutely! The <strong>STAR method</strong> is a technique used to answer behavioral interview questions in a structured and compelling way.<br><br>
                It stands for:<br>
                • <strong>Situation:</strong> Set the scene and give the necessary details of your example.<br>
                • <strong>Task:</strong> Describe what your responsibility was in that situation.<br>
                • <strong>Action:</strong> Explain exactly what steps you took to address it.<br>
                • <strong>Result:</strong> Share what outcomes your actions achieved.<br><br>
                Would you like me to give you an example of a STAR answer?
            </div>
        </div>
        
        <!-- Chat Input -->
        <div class="chat-input-area">
            <div class="suggestion-chips mb-3">
                <div class="suggestion-chip">Give me an example</div>
                <div class="suggestion-chip">How do I answer "Why should we hire you?"</div>
                <div class="suggestion-chip">Give me vocabulary tips</div>
            </div>
            <div class="chat-input-wrap">
                <input type="text" class="chat-input" placeholder="Ask about interview techniques, request examples...">
                <button class="chat-send-btn">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Scroll to bottom of chat
    const chatBody = document.getElementById('chatBody');
    chatBody.scrollTop = chatBody.scrollHeight;
</script>

@endsection

