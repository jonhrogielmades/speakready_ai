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

    <div class="quiz-container" id="quiz-wrapper">
        @if(count($questions) > 0)
            <div id="quiz-content">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span id="question-progress" style="color:var(--tx3);font-size:0.9rem;font-weight:600;text-transform:uppercase">Question 1 of {{ count($questions) }}</span>
                    <span id="question-topic" style="color:var(--pur);font-size:0.9rem;font-weight:700">Mini Quiz</span>
                </div>
                
                <div class="quiz-progress-bar">
                    <div id="quiz-progress-fill" class="quiz-progress-fill" style="width: 0%;"></div>
                </div>

                <h4 id="question-text" style="color:var(--tx);font-weight:700;margin-bottom:30px;line-height:1.4">
                    <!-- Question text injected here -->
                </h4>

                <div id="options-container" class="options-container mb-5">
                    <!-- Options injected here -->
                </div>

                <div class="d-flex justify-content-between">
                    <div></div> <!-- Spacer for alignment -->
                    <button class="btn bgrd px-5" style="border-radius:10px" id="next-btn" onclick="nextQuestion()">Next Question <i class="fa-solid fa-arrow-right ms-2"></i></button>
                </div>
            </div>

            <div id="quiz-results" style="display: none; text-align: center;">
                <h3 style="font-weight:700;color:var(--tx);margin-bottom:20px;">Quiz Complete! 🎉</h3>
                <div style="font-size: 3rem; font-weight: 800; color: var(--pur); margin-bottom: 20px;">
                    <span id="final-score">0</span> / <span id="total-questions">0</span>
                </div>
                <p id="score-message" style="color:var(--tx2);font-size:1.1rem;margin-bottom:30px;"></p>
                <button class="btn bgrd px-5 py-2" style="border-radius:10px; font-size:1.1rem" onclick="window.location.reload()">Take Another Quiz</button>
            </div>
        @else
            <div style="text-align: center; padding: 40px 0;">
                <i class="fa-solid fa-brain mb-3" style="font-size: 3rem; color: var(--bd);"></i>
                <h4 style="color:var(--tx);font-weight:600">No quizzes available yet.</h4>
                <p style="color:var(--tx3);">Check back later once more learning modules and questions are added!</p>
            </div>
        @endif
    </div>
</div>

<a href="{{ route('user.learning.assistant') }}" class="ll-ai-fab" title="Chat with AI Learning Assistant">
    <i class="fa-solid fa-robot"></i>
</a>

<script>
    const questions = @json($questions ?? []);
    let currentQuestionIndex = 0;
    let score = 0;
    let selectedAnswer = null;

    if (questions.length > 0) {
        loadQuestion(0);
    }

    function loadQuestion(index) {
        const question = questions[index];
        selectedAnswer = null;
        
        document.getElementById('question-progress').innerText = `Question ${index + 1} of ${questions.length}`;
        const progressPercentage = ((index) / questions.length) * 100;
        document.getElementById('quiz-progress-fill').style.width = `${progressPercentage}%`;
        
        document.getElementById('question-text').innerText = question.question_text;
        
        const optionsContainer = document.getElementById('options-container');
        optionsContainer.innerHTML = '';
        
        // Handle options (safely parse if string)
        let options = [];
        try {
            options = typeof question.options === 'string' ? JSON.parse(question.options) : question.options;
        } catch(e) {
            options = [];
        }
        
        if (!Array.isArray(options)) options = Object.values(options || {});

        const letters = ['A', 'B', 'C', 'D', 'E', 'F'];
        
        options.forEach((opt, i) => {
            const letter = letters[i] || '-';
            const optDiv = document.createElement('div');
            optDiv.className = 'option-card';
            
            // Escape single quotes for inline onclick handler
            const safeOpt = String(opt).replace(/'/g, "\\'").replace(/"/g, "&quot;");
            optDiv.setAttribute('onclick', `selectOption(this, '${safeOpt}')`);
            
            optDiv.innerHTML = `
                <div class="option-letter">${letter}</div>
                <div class="option-text">${opt}</div>
            `;
            optionsContainer.appendChild(optDiv);
        });

        // Update button text on last question
        const nextBtn = document.getElementById('next-btn');
        if (index === questions.length - 1) {
            nextBtn.innerHTML = 'Finish Quiz <i class="fa-solid fa-check ms-2"></i>';
        } else {
            nextBtn.innerHTML = 'Next Question <i class="fa-solid fa-arrow-right ms-2"></i>';
        }
    }

    function selectOption(element, answer) {
        // Remove selected class from all options
        const optionsNodes = document.querySelectorAll('.option-card');
        optionsNodes.forEach(opt => opt.classList.remove('selected'));
        
        // Add selected class to clicked option
        element.classList.add('selected');
        // Unescape the safe HTML entities to match exact string
        selectedAnswer = answer.replace(/&quot;/g, '"');
    }

    function nextQuestion() {
        if (!selectedAnswer) {
            alert('Please select an answer before proceeding.');
            return;
        }

        // Check if correct
        const currentQuestion = questions[currentQuestionIndex];
        // Exact string match check
        if (selectedAnswer === currentQuestion.correct_answer) {
            score++;
        }

        currentQuestionIndex++;

        if (currentQuestionIndex < questions.length) {
            loadQuestion(currentQuestionIndex);
        } else {
            showResults();
        }
    }

    function showResults() {
        document.getElementById('quiz-content').style.display = 'none';
        document.getElementById('quiz-results').style.display = 'block';
        
        // Final progress bar fill
        document.getElementById('quiz-progress-fill').style.width = '100%';
        
        document.getElementById('final-score').innerText = score;
        document.getElementById('total-questions').innerText = questions.length;
        
        const percentage = (score / questions.length) * 100;
        const messageEl = document.getElementById('score-message');
        
        if (percentage >= 80) {
            messageEl.innerText = "Excellent work! You've mastered this topic.";
            messageEl.style.color = "#34d399";
        } else if (percentage >= 50) {
            messageEl.innerText = "Good job! Keep practicing to get a perfect score.";
        } else {
            messageEl.innerText = "Don't give up! Review the study materials and try again.";
        }
    }
</script>

@endsection

