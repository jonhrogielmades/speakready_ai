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
    .star-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 25px;
        height: 100%;
        transition: 0.3s;
        position: relative;
        overflow: hidden;
    }
    .star-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .star-icon-wrap {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 15px;
        color: #fff;
    }
    .bg-s { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    .bg-t { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .bg-a { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .bg-r { background: linear-gradient(135deg, #3b82f6 0%, #6d28d9 100%); }
    
    .practice-box {
        background: var(--bg);
        border: 1px dashed var(--bd);
        border-radius: 12px;
        padding: 20px;
    }
    .ll-ai-fab {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--pur) 0%, #34d399 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 10px 25px rgba(59,130,246,0.4);
        cursor: pointer;
        transition: 0.3s;
        z-index: 100;
        text-decoration: none;
    }
    .ll-ai-fab:hover {
        transform: scale(1.1);
        box-shadow: 0 15px 35px rgba(59,130,246,0.5);
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
        <a href="{{ route('user.learning.star') }}" class="ll-nav-pill active"><i class="fa-solid fa-star"></i> STAR Method Training</a>
        <a href="{{ route('user.learning.library') }}" class="ll-nav-pill"><i class="fa-solid fa-book-bookmark"></i> Answer Library</a>
        <a href="{{ route('user.learning.quiz') }}" class="ll-nav-pill"><i class="fa-solid fa-brain"></i> Mini Quizzes</a>
    </div>

    <!-- STAR Header -->
    <div class="mb-5 text-center">
        <h4 style="color:var(--tx);font-weight:700">The STAR Method Framework</h4>
        <p style="color:var(--tx3);max-width:600px;margin:0 auto">A structured manner of responding to behavioral interview questions by discussing the specific situation, task, action, and result of the situation you are describing.</p>
    </div>

    <!-- STAR Breakdown -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="star-card" style="border-top: 4px solid #3b82f6">
                <div class="star-icon-wrap bg-s"><i class="fa-solid fa-map-location-dot"></i></div>
                <h5 style="color:var(--tx);font-weight:700">Situation</h5>
                <p style="color:var(--tx3);font-size:0.9rem">Describe the context within which you performed a job or faced a challenge. Provide relevant background details.</p>
                <div style="font-size:0.8rem;color:#3b82f6;font-weight:600;margin-top:10px">10-15% of your answer</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="star-card" style="border-top: 4px solid #f59e0b">
                <div class="star-icon-wrap bg-t"><i class="fa-solid fa-bullseye"></i></div>
                <h5 style="color:var(--tx);font-weight:700">Task</h5>
                <p style="color:var(--tx3);font-size:0.9rem">Describe your responsibility in that situation. What was your goal? What did you need to accomplish?</p>
                <div style="font-size:0.8rem;color:#f59e0b;font-weight:600;margin-top:10px">10-15% of your answer</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="star-card" style="border-top: 4px solid #10b981">
                <div class="star-icon-wrap bg-a"><i class="fa-solid fa-person-running"></i></div>
                <h5 style="color:var(--tx);font-weight:700">Action</h5>
                <p style="color:var(--tx3);font-size:0.9rem">Describe the specific actions you took to address the situation. Focus on your individual contribution.</p>
                <div style="font-size:0.8rem;color:#10b981;font-weight:600;margin-top:10px">60-70% of your answer</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="star-card" style="border-top: 4px solid #3b82f6">
                <div class="star-icon-wrap bg-r"><i class="fa-solid fa-chart-line"></i></div>
                <h5 style="color:var(--tx);font-weight:700">Result</h5>
                <p style="color:var(--tx3);font-size:0.9rem">Describe the outcome of your actions. Take credit for your accomplishments and quantify when possible.</p>
                <div style="font-size:0.8rem;color:#3b82f6;font-weight:600;margin-top:10px">10-15% of your answer</div>
            </div>
        </div>
    </div>

    <!-- Interactive Practice -->
    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:20px;padding:30px">
        <h4 style="color:var(--tx);font-weight:700;margin-bottom:20px"><i class="fa-solid fa-pen-nib me-2 text-primary"></i> Practice Exercise</h4>
        <p style="color:var(--tx3);margin-bottom:30px">Try building a STAR response for this common question: <br><strong style="color:var(--pur)">"Tell me about a time you had to deal with a difficult team member."</strong></p>

        <form>
            <div class="mb-4 practice-box">
                <label style="color:var(--tx);font-weight:600;margin-bottom:10px">S - Situation</label>
                <textarea class="form-control" rows="2" placeholder="e.g., During my final year project, I was grouped with a member who consistently missed deadlines..." style="background:transparent;border:1px solid var(--bd);color:var(--tx)"></textarea>
            </div>
            
            <div class="mb-4 practice-box">
                <label style="color:var(--tx);font-weight:600;margin-bottom:10px">T - Task</label>
                <textarea class="form-control" rows="2" placeholder="e.g., As the team lead, I needed to ensure the project was submitted on time without compromising quality..." style="background:transparent;border:1px solid var(--bd);color:var(--tx)"></textarea>
            </div>
            
            <div class="mb-4 practice-box">
                <label style="color:var(--tx);font-weight:600;margin-bottom:10px">A - Action</label>
                <textarea class="form-control" rows="4" placeholder="e.g., I set up a private 1-on-1 meeting to understand their challenges. We discovered they were struggling with a specific software tool. I paired them with another member for technical support and reorganized our timeline..." style="background:transparent;border:1px solid var(--bd);color:var(--tx)"></textarea>
            </div>
            
            <div class="mb-4 practice-box">
                <label style="color:var(--tx);font-weight:600;margin-bottom:10px">R - Result</label>
                <textarea class="form-control" rows="2" placeholder="e.g., They caught up on their tasks, the team morale improved, and we delivered the project two days before the deadline, scoring an A grade." style="background:transparent;border:1px solid var(--bd);color:var(--tx)"></textarea>
            </div>

            <div class="text-end">
                <button type="button" class="btn btn-outline-secondary me-2 px-4" style="border-radius:10px">Save Draft</button>
                <button type="button" class="btn bgrd px-4" style="border-radius:10px"><i class="fa-solid fa-robot me-2"></i> Get AI Feedback</button>
            </div>
        </form>
    </div>
</div>

<a href="{{ route('user.learning.assistant') }}" class="ll-ai-fab" title="Chat with AI Learning Assistant">
    <i class="fa-solid fa-robot"></i>
</a>

@endsection

