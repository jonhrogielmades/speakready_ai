@extends('layouts.app')
@section('content')
<style>
    .setup-panel { background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;margin-bottom:20px; }
    .olbl { font-weight:600;color:var(--tx);font-size:.9rem;margin-bottom:8px;display:block; }
    .oinp { width:100%;padding:10px 14px;border:1px solid var(--bd);border-radius:10px;background:var(--bg3);color:var(--tx);font-size:.9rem;transition:border-color 0.2s; }
    .oinp:focus { outline:none;border-color:var(--pur); }
    .desc-text { font-size:.75rem;color:var(--tx3);margin-top:4px; }
    
    .custom-radio { position:relative;display:flex;align-items:flex-start;padding:12px;border:1px solid var(--bd);border-radius:10px;background:var(--bg3);cursor:pointer;margin-bottom:10px;transition:all 0.2s; }
    .custom-radio:hover { border-color:#a78bfa; }
    .custom-radio input[type="radio"] { margin-top:4px;margin-right:12px;accent-color:var(--pur); }
    .custom-radio .r-title { font-weight:600;font-size:.9rem;color:var(--tx);display:block; }
    .custom-radio .r-desc { font-size:.75rem;color:var(--tx3);display:block; }
    
    .cbx-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
    .custom-cbx { display:flex;align-items:center;padding:10px;border:1px solid var(--bd);border-radius:8px;background:var(--bg3);cursor:pointer;font-size:.85rem;color:var(--tx);transition:all 0.2s; }
    .custom-cbx:hover { border-color:#60a5fa; }
    .custom-cbx input[type="checkbox"] { margin-right:10px;accent-color:#60a5fa; }

    .summary-row { display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--bd);font-size:.85rem; }
    .summary-row:last-child { border-bottom:none; }
    .summary-label { color:var(--tx3);font-weight:600; }
    .summary-val { color:var(--tx);font-weight:700;text-align:right; }
</style>

<div class="db-section active" id="sec-interview-setup">
    <div class="mb-4">
        <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Interview Setup</h4>
        <p style="font-size:.875rem;color:var(--tx3);margin:0">Configure your mock interview session to match your goals.</p>
    </div>

    @if($errors->any())
       <div class="alert alert-danger" style="background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#f87171;padding:10px;border-radius:10px;margin-bottom:15px;font-size:.85rem">
          <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $errors->first() }}
       </div>
    @endif

    <form action="{{ route('interview.start') }}" method="POST" id="setupForm">
        @csrf
        <div class="row g-4">
            <!-- Left Column: Form Settings -->
            <div class="col-lg-8">
                
                <!-- Basic Info -->
                <div class="setup-panel">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-briefcase me-2" style="color:#60a5fa"></i> Basic Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="olbl">Interview Category</label>
                            <select class="oinp setup-input" name="category_name" id="valCategory" required>
                                <option value="Job Interview">Job Interview</option>
                                <option value="Scholarship Interview">Scholarship Interview</option>
                                <option value="College Admission Interview">College Admission Interview</option>
                                <option value="IT/Programming Interview">IT/Programming Interview</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Target Position</label>
                            <input class="oinp setup-input" type="text" name="target_position" id="valPosition" placeholder="Enter your target role (e.g. Software Developer)..." required>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-12">
                            <label class="olbl">Questions & AI Provider</label>
                            <select class="oinp setup-input" name="ai_provider" id="valProvider">
                                <option value="local">Local</option>
                                <option value="gemini" selected>Gemini</option>
                                <option value="cohere">Cohere</option>
                                <option value="groq">Groq</option>
                                <option value="openrouter">OpenRouter</option>
                                <option value="claude">Claude (Anthropic)</option>
                                <option value="wisdomgate">WisdomGate</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Interview Structure -->
                <div class="setup-panel">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-layer-group me-2" style="color:#a78bfa"></i> Interview Structure</h5>
                    
                    <label class="olbl mb-3">Difficulty Level</label>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="difficulty" value="easy" class="setup-input">
                                <div>
                                    <span class="r-title">Easy</span>
                                    <span class="r-desc">Basic and introductory questions</span>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="difficulty" value="medium" checked class="setup-input">
                                <div>
                                    <span class="r-title">Medium</span>
                                    <span class="r-desc">Common interview questions</span>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="difficulty" value="hard" class="setup-input">
                                <div>
                                    <span class="r-title">Hard</span>
                                    <span class="r-desc">Advanced and situational questions</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="olbl">Number of Questions</label>
                            <select class="oinp setup-input" name="num_questions" id="valNumQuestions">
                                <option value="5">5 Questions</option>
                                <option value="10" selected>10 Questions</option>
                                <option value="15">15 Questions</option>
                                <option value="20">20 Questions</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Time Limit</label>
                            <select class="oinp setup-input" name="time_limit" id="valTimeLimit">
                                <option value="0" selected>No Limit</option>
                                <option value="1">1 Minute per Question</option>
                                <option value="2">2 Minutes per Question</option>
                                <option value="3">3 Minutes per Question</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Content & Assistance -->
                <div class="setup-panel">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-brain me-2" style="color:#f87171"></i> Content & Assistance</h5>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="olbl">Interview Focus</label>
                            <select class="oinp setup-input" name="interview_focus" id="valFocus">
                                <option value="General Practice" selected>General Practice</option>
                                <option value="Communication Skills">Communication Skills</option>
                                <option value="Technical Knowledge">Technical Knowledge</option>
                                <option value="Problem Solving">Problem Solving</option>
                                <option value="Leadership">Leadership</option>
                                <option value="Teamwork">Teamwork</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">AI Assistance Level</label>
                            <select class="oinp setup-input" name="ai_assistance_level" id="valAssistance">
                                <option value="beginner">Beginner Mode (More hints & feedback)</option>
                                <option value="standard" selected>Standard Mode (Balanced experience)</option>
                                <option value="challenge">Challenge Mode (No hints, harder follow-ups)</option>
                            </select>
                        </div>
                    </div>

                    <label class="olbl mb-2">Question Types</label>
                    <div class="cbx-grid">
                        <label class="custom-cbx"><input type="checkbox" name="question_types[]" value="Behavioral" checked> Behavioral Questions</label>
                        <label class="custom-cbx"><input type="checkbox" name="question_types[]" value="Situational" checked> Situational Questions</label>
                        <label class="custom-cbx"><input type="checkbox" name="question_types[]" value="Technical"> Technical Questions</label>
                        <label class="custom-cbx"><input type="checkbox" name="question_types[]" value="Personal"> Personal Questions</label>
                    </div>
                </div>

                <!-- Response Mode -->
                <div class="setup-panel">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-microphone me-2" style="color:#34d399"></i> Response Mode</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="response_mode" value="text" class="setup-input">
                                <div>
                                    <span class="r-title">Text Mode</span>
                                    <span class="r-desc">Type your answers manually</span>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="response_mode" value="voice" checked class="setup-input">
                                <div>
                                    <span class="r-title">Voice Mode</span>
                                    <span class="r-desc">Speak through your microphone</span>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="response_mode" value="hybrid" class="setup-input">
                                <div>
                                    <span class="r-title">Hybrid Mode</span>
                                    <span class="r-desc">Voice-to-text with manual editing</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Live Summary -->
            <div class="col-lg-4">
                <div style="position:sticky;top:20px;">
                    <div class="setup-panel" style="background:linear-gradient(145deg, rgba(139,92,246,0.05) 0%, rgba(59,130,246,0.05) 100%); border:1px solid rgba(139,92,246,0.2);">
                        <h5 style="font-weight:700;margin-bottom:20px;color:var(--pur);text-align:center"><i class="fa-solid fa-clipboard-list me-2"></i> Interview Summary</h5>
                        
                        <div class="summary-row">
                            <span class="summary-label">Category:</span>
                            <span class="summary-val" id="sumCategory">Job Interview</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Position:</span>
                            <span class="summary-val" id="sumPosition">Software Developer</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Difficulty:</span>
                            <span class="summary-val" id="sumDifficulty">Medium</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Questions:</span>
                            <span class="summary-val" id="sumQuestions">10</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Response Mode:</span>
                            <span class="summary-val" id="sumResponse">Voice</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Focus:</span>
                            <span class="summary-val" id="sumFocus">General Practice</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">AI Provider:</span>
                            <span class="summary-val" id="sumProvider">Gemini</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Est. Duration:</span>
                            <span class="summary-val text-success" id="sumDuration">15 Minutes</span>
                        </div>
                        
                        <div style="margin-top:30px;">
                            <button type="submit" class="bgrd btn w-100 py-3" style="font-size:1.1rem;font-weight:700;border-radius:12px;box-shadow:0 4px 15px rgba(139,92,246,0.3)">
                                Start Mock Interview <i class="fa-solid fa-play ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>

    function updateSummary() {
        // Category text
        const catSelect = document.getElementById('valCategory');
        if(catSelect.options.length > 0) {
            document.getElementById('sumCategory').innerText = catSelect.options[catSelect.selectedIndex].text;
        }

        // Position
        const posVal = document.getElementById('valPosition').value;
        document.getElementById('sumPosition').innerText = posVal || 'Not Specified';

        // Difficulty
        const diff = document.querySelector('input[name="difficulty"]:checked');
        if(diff) document.getElementById('sumDifficulty').innerText = diff.value.charAt(0).toUpperCase() + diff.value.slice(1);

        // Questions
        const numQ = document.getElementById('valNumQuestions').value;
        document.getElementById('sumQuestions').innerText = numQ;

        // Response Mode
        const resp = document.querySelector('input[name="response_mode"]:checked');
        if(resp) document.getElementById('sumResponse').innerText = resp.value.charAt(0).toUpperCase() + resp.value.slice(1);

        // Focus
        const focus = document.getElementById('valFocus').value;
        document.getElementById('sumFocus').innerText = focus;

        // Provider
        const provider = document.getElementById('valProvider');
        if (provider) {
            document.getElementById('sumProvider').innerText = provider.options[provider.selectedIndex].text;
        }

        // Estimated Duration
        const timeLimit = parseInt(document.getElementById('valTimeLimit').value);
        let durationStr = "Self-paced";
        if(timeLimit > 0) {
            durationStr = (numQ * timeLimit) + " Minutes";
        } else {
            // Rough estimate based on questions (e.g. 1.5 mins per question)
            durationStr = Math.round(numQ * 1.5) + " Minutes";
        }
        document.getElementById('sumDuration').innerText = durationStr;
    }

    // Attach listeners
    document.querySelectorAll('.setup-input').forEach(el => {
        el.addEventListener('change', updateSummary);
        el.addEventListener('keyup', updateSummary);
    });

    // Initial update
    window.onload = () => {
        updateSummary();
    };
</script>
@endsection