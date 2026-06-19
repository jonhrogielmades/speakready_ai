@extends('layouts.app')

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
    
    .quiz-container {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 20px;
        padding: 40px;
        max-width: 800px;
        margin: 0 auto;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }
    .quiz-progress-bar {
        width: 100%;
        height: 8px;
        background: var(--bd);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 30px;
    }
    .quiz-progress-fill {
        height: 100%;
        border-radius: 4px;
        background: linear-gradient(90deg, var(--pur) 0%, #34d399 100%);
        width: 40%; /* Mock progress */
    }
    .option-card {
        background: var(--bg);
        border: 2px solid var(--bd);
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: 0.2s;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .option-card:hover {
        background: rgba(59,130,246,0.05);
        border-color: rgba(59,130,246,0.5);
    }
    .option-card.selected {
        background: rgba(59,130,246,0.1);
        border-color: var(--pur);
    }
    .option-letter {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: var(--bd);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: var(--tx2);
    }
    .option-card.selected .option-letter {
        background: var(--pur);
        color: #fff;
    }
    .option-text {
        color: var(--tx);
        font-size: 1.05rem;
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
        <a href="{{ route('user.learning.library') }}" class="ll-nav-pill"><i class="fa-solid fa-book-bookmark"></i> Answer Library</a>
        <a href="{{ route('user.learning.quiz') }}" class="ll-nav-pill active"><i class="fa-solid fa-brain"></i> Mini Quizzes</a>
    </div>

    <div class="quiz-container">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="color:var(--tx3);font-size:0.9rem;font-weight:600;text-transform:uppercase">Question 2 of 5</span>
            <span style="color:var(--pur);font-size:0.9rem;font-weight:700">Topic: Body Language</span>
        </div>
        
        <div class="quiz-progress-bar">
            <div class="quiz-progress-fill"></div>
        </div>

        <h4 style="color:var(--tx);font-weight:700;margin-bottom:30px;line-height:1.4">
            During an in-person interview, what is the best approach regarding eye contact with a panel of three interviewers?
        </h4>

        <div class="options-container mb-5">
            <div class="option-card" onclick="selectOption(this)">
                <div class="option-letter">A</div>
                <div class="option-text">Stare continuously at the person who asked the question.</div>
            </div>
            
            <div class="option-card selected" onclick="selectOption(this)">
                <div class="option-letter">B</div>
                <div class="option-text">Start by looking at the person who asked the question, then periodically sweep your eyes to the other panelists.</div>
            </div>
            
            <div class="option-card" onclick="selectOption(this)">
                <div class="option-letter">C</div>
                <div class="option-text">Look slightly above their heads so you don't get intimidated.</div>
            </div>
            
            <div class="option-card" onclick="selectOption(this)">
                <div class="option-letter">D</div>
                <div class="option-text">Only look at the most senior person in the room to show respect.</div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <button class="btn btn-outline-secondary px-4" style="border-radius:10px">Previous</button>
            <button class="btn bgrd px-5" style="border-radius:10px">Next Question <i class="fa-solid fa-arrow-right ms-2"></i></button>
        </div>
    </div>
</div>

<a href="{{ route('user.learning.assistant') }}" class="ll-ai-fab" title="Chat with AI Learning Assistant">
    <i class="fa-solid fa-robot"></i>
</a>

<script>
    function selectOption(element) {
        // Remove selected class from all options
        const options = document.querySelectorAll('.option-card');
        options.forEach(opt => opt.classList.remove('selected'));
        
        // Add selected class to clicked option
        element.classList.add('selected');
    }
</script>

@endsection
