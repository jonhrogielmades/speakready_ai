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
    .qa-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 20px;
        transition: 0.3s;
    }
    .qa-card:hover {
        border-color: rgba(59,130,246,0.3);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .answer-box {
        background: rgba(59,130,246,0.05);
        border-left: 4px solid var(--pur);
        padding: 20px;
        border-radius: 0 12px 12px 0;
        margin-top: 15px;
        display: none; /* hidden by default */
    }
    .answer-box.show {
        display: block;
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
            <h3 style="font-weight:700;color:var(--tx);margin:0">Learning Lab <i class="fa-solid fa-flask" style="color:var(--pur);font-size:1.2rem"></i></h3>
            <p style="color:var(--tx3);margin-top:5px;">Master your interview skills with structured, AI-powered learning.</p>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div class="mb-4 pb-2" style="overflow-x:auto;white-space:nowrap;">
        <a href="{{ route('user.learning') }}" class="ll-nav-pill"><i class="fa-solid fa-border-all"></i> Dashboard</a>
        <a href="{{ route('user.learning.star') }}" class="ll-nav-pill"><i class="fa-solid fa-star"></i> STAR Method Training</a>
        <a href="{{ route('user.learning.library') }}" class="ll-nav-pill active"><i class="fa-solid fa-book-bookmark"></i> Answer Library</a>
        <a href="{{ route('user.learning.quiz') }}" class="ll-nav-pill"><i class="fa-solid fa-brain"></i> Mini Quizzes</a>
    </div>

    <div class="row g-4">
        <!-- Filter Sidebar -->
        <div class="col-lg-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:16px;padding:20px;">
                <h6 style="font-weight:700;color:var(--tx);margin-bottom:15px;text-transform:uppercase;font-size:0.85rem;letter-spacing:1px">Filters</h6>
                
                <div class="mb-4">
                    <label style="color:var(--tx2);font-size:0.9rem;margin-bottom:8px;display:block">Search</label>
                    <input type="text" class="form-control" placeholder="Search questions..." style="background:transparent;border:1px solid var(--bd);color:var(--tx)">
                </div>
                
                <div class="mb-4">
                    <label style="color:var(--tx2);font-size:0.9rem;margin-bottom:8px;display:block">Category</label>
                    <select class="form-select" style="background:transparent;border:1px solid var(--bd);color:var(--tx)">
                        <option>All Categories</option>
                        <option>Behavioral</option>
                        <option>Situational</option>
                        <option>Technical</option>
                        <option>Personal</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label style="color:var(--tx2);font-size:0.9rem;margin-bottom:8px;display:block">Difficulty</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" checked>
                        <label class="form-check-label" style="color:var(--tx2);font-size:0.9rem">Beginner</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" checked>
                        <label class="form-check-label" style="color:var(--tx2);font-size:0.9rem">Intermediate</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" checked>
                        <label class="form-check-label" style="color:var(--tx2);font-size:0.9rem">Advanced</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- QA List -->
        <div class="col-lg-9">
            
            <!-- Question 1 -->
            <div class="qa-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-primary mb-2">Behavioral</span>
                        <span class="badge bg-success mb-2 ms-1">Beginner</span>
                        <h5 style="color:var(--tx);font-weight:700">Tell me about yourself.</h5>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="this.parentElement.nextElementSibling.classList.toggle('show')">
                        <i class="fa-solid fa-eye me-1"></i> View Sample Answer
                    </button>
                </div>
                
                <div class="answer-box">
                    <h6 style="color:var(--tx);font-weight:700;margin-bottom:10px">Model Answer:</h6>
                    <p style="color:var(--tx2);line-height:1.6;margin-bottom:15px">
                        "I am a software engineer with over 5 years of experience specializing in full-stack development. Most recently, I led a team of three at XYZ Corp to migrate our legacy application to a modern React-based stack, which improved load times by 40%. I’m particularly passionate about creating intuitive user experiences and writing clean, scalable code. I’m excited about this opportunity because your company's focus on innovative AI solutions aligns perfectly with my background and career goals."
                    </p>
                    <div style="background:var(--bg);padding:10px;border-radius:8px">
                        <strong style="color:var(--pur);font-size:0.85rem">Why it works:</strong>
                        <p style="color:var(--tx3);font-size:0.85rem;margin:0;margin-top:5px">It follows the Present-Past-Future formula. It highlights relevant achievements without rambling, and ties back to why the candidate is interested in the specific role.</p>
                    </div>
                </div>
            </div>

            <!-- Question 2 -->
            <div class="qa-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-info mb-2">Situational</span>
                        <span class="badge bg-warning text-dark mb-2 ms-1">Intermediate</span>
                        <h5 style="color:var(--tx);font-weight:700">What is your greatest weakness?</h5>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="this.parentElement.nextElementSibling.classList.toggle('show')">
                        <i class="fa-solid fa-eye me-1"></i> View Sample Answer
                    </button>
                </div>
                
                <div class="answer-box">
                    <h6 style="color:var(--tx);font-weight:700;margin-bottom:10px">Model Answer:</h6>
                    <p style="color:var(--tx2);line-height:1.6;margin-bottom:15px">
                        "Sometimes, I can be a bit too critical of my own work, which has occasionally led me to spend too much time double-checking simple tasks. However, I’ve learned to manage this by setting strict time-boxes for myself and asking for peer reviews earlier in the process. This has actually improved my collaboration skills and helped me deliver projects more efficiently."
                    </p>
                    <div style="background:var(--bg);padding:10px;border-radius:8px">
                        <strong style="color:var(--pur);font-size:0.85rem">Why it works:</strong>
                        <p style="color:var(--tx3);font-size:0.85rem;margin:0;margin-top:5px">It provides a real, relatable weakness (not a humblebrag like 'I work too hard') and immediately pivots to actionable steps the candidate has taken to improve.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<a href="{{ route('user.learning.assistant') }}" class="ll-ai-fab" title="Chat with AI Learning Assistant">
    <i class="fa-solid fa-robot"></i>
</a>

@endsection
