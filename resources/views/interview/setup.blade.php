@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('content')
<style>
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    .setup-panel { 
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        scroll-margin-top: 120px;
        box-shadow: var(--shadow-soft, 0 10px 28px rgba(0, 0, 0, 0.14));
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .setup-panel:hover {
        transform: translateY(-1px);
        border-color: rgba(96, 165, 250, 0.34);
        box-shadow: var(--shadow-card, 0 18px 45px rgba(0, 0, 0, 0.18));
    }
    #sec-interview-setup { --setup-gap: 20px; }
    #sec-interview-setup #setupForm > .row,
    #sec-interview-setup .row.g-4 {
        --bs-gutter-x: var(--setup-gap);
        --bs-gutter-y: var(--setup-gap);
    }
    #setup-left-col {
        display: flex;
        flex-direction: column;
        gap: var(--setup-gap);
    }
    #setup-left-col > .setup-panel,
    #panel-summary {
        margin-bottom: 0;
    }
    #sec-interview-setup .setup-panel h5 {
        font-size: 1rem;
        line-height: 1.3;
        margin-bottom: 16px !important;
        letter-spacing: 0 !important;
    }
    #panel-summary h5 {
        color: var(--tx) !important;
        text-align: left !important;
    }
    #btn-start-interview { scroll-margin-top: 120px; }
    .olbl { font-weight:600;color:var(--tx);font-size:.9rem;margin-bottom:8px;display:block; letter-spacing: 0.3px; }
    .oinp { width:100%;padding:12px 16px;border:1px solid var(--bd);border-radius:12px;background:var(--bg3);color:var(--tx);font-size:.9rem;transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .oinp:focus { outline:none;border-color:var(--pur);box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15); background: var(--sf); }
    .desc-text { font-size:.75rem;color:var(--tx3);margin-top:4px; }
    
    .custom-radio { position:relative;display:flex;align-items:flex-start;padding:16px;border:1px solid var(--bd);border-radius:12px;background:var(--bg3);cursor:pointer;margin-bottom:10px;transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .custom-radio:hover { border-color:#60a5fa; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(96, 165, 250, 0.1); background: var(--sf); }
    .custom-radio input[type="radio"]:checked + div { color: #60a5fa; }
    .custom-radio:has(input[type="radio"]:checked) { border-color: #60a5fa; background: rgba(96, 165, 250, 0.05); box-shadow: 0 4px 20px rgba(96, 165, 250, 0.15); }
    .custom-radio input[type="radio"] { margin-top:4px;margin-right:12px;accent-color:var(--pur); }
    .custom-radio .r-title { font-weight:700;font-size:.95rem;color:var(--tx);display:block; }
    .custom-radio .r-desc { font-size:.8rem;color:var(--tx3);display:block; margin-top:2px; }
    
    .cbx-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
    .custom-cbx { display:flex;align-items:center;padding:12px 16px;border:1px solid var(--bd);border-radius:10px;background:var(--bg3);cursor:pointer;font-size:.9rem;font-weight:500;color:var(--tx);transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .custom-cbx:hover { border-color:#60a5fa; transform: translateY(-1px); background: var(--sf); }
    .custom-cbx:has(input[type="checkbox"]:checked) { border-color: #60a5fa; background: rgba(96, 165, 250, 0.05); }
    .custom-cbx input[type="checkbox"] { margin-right:10px;accent-color:#60a5fa; }

    .summary-row { display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--bd);font-size:.85rem; }
    .summary-row:last-child { border-bottom:none; }
    .summary-label { color:var(--tx3);font-weight:600; }
    .summary-val { color:var(--tx);font-weight:700;text-align:right; }

    /* Drag and Drop Zone */
    @keyframes dashBorder { to { background-position: 100% 0, 0 100%, 0 0, 100% 100%; } }
    .drop-zone { 
        border: 2px dashed var(--bd); 
        border-radius: 16px; 
        padding: 40px 20px; 
        text-align: center; 
        cursor: pointer; 
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
        background: var(--bg3); 
        position: relative;
    }
    .drop-zone:hover { border-color: rgba(96,165,250,0.5); background: var(--sf); transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.05); }
    .drop-zone.dragover { border-color: #60a5fa; background: rgba(96,165,250,0.1); transform: scale(1.02); }
    .drop-zone-icon { font-size: 2.5rem; color: #60a5fa; margin-bottom: 12px; transition: transform 0.4s; }
    .drop-zone:hover .drop-zone-icon { transform: scale(1.1) translateY(-5px); }
    .drop-zone-text { font-size: 0.95rem; color: var(--tx); font-weight: 600; }
    
    /* Persona Cards */
    .persona-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 12px; }
    .persona-card { 
        border: 1px solid var(--bd); 
        border-radius: 16px; 
        padding: 16px; 
        text-align: center; 
        cursor: pointer; 
        background: var(--bg3); 
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
        position: relative; 
        overflow: hidden; 
    }
    .persona-card:hover { border-color: rgba(167,139,250,0.6); transform: translateY(-4px); background: var(--sf); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
    .persona-card.selected { border-color: #8b5cf6; background: rgba(139,92,246,0.08); box-shadow: 0 8px 25px rgba(139,92,246,0.25); }
    .persona-card.selected::after { content:''; position:absolute; inset:0; border-radius:16px; box-shadow: inset 0 0 0 1px rgba(139,92,246,0.5); pointer-events:none; }
    .persona-icon { font-size: 2rem; margin-bottom: 10px; color: var(--tx); transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); display: inline-block; }
    .persona-card:hover .persona-icon { transform: scale(1.15) rotate(5deg); }
    .persona-card.selected .persona-icon { color: #8b5cf6; transform: scale(1.1); }
    .persona-title { font-weight: 700; font-size: 0.9rem; color: var(--tx); letter-spacing: 0.3px; }
    .persona-desc { font-size: 0.75rem; color: var(--tx3); margin-top: 6px; }
    .persona-check { position: absolute; top: 12px; right: 12px; color: #8b5cf6; font-size: 1rem; opacity: 0; transition: opacity 0.3s, transform 0.3s; transform: scale(0.5); }
    .persona-card.selected .persona-check { opacity: 1; transform: scale(1); }
    .setup-chip-panel {
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 14px;
        background: var(--bg3);
    }

    #sec-interview-setup .custom-radio,
    #sec-interview-setup .custom-cbx {
        margin-bottom: 0;
        min-height: 100%;
    }
    #sec-interview-setup .summary-row {
        gap: 14px;
        align-items: flex-start;
    }
    #sec-interview-setup .summary-label,
    #sec-interview-setup .summary-val {
        min-width: 0;
    }

    /* Animations */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }

    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }

    /* Driver.js Dark Theme Customization */
    .driverjs-theme-dark.driver-popover { background-color: var(--bg3); color: var(--tx); border: 1px solid var(--bd); }
    .driverjs-theme-dark .driver-popover-title { color: var(--tx); }
    .driverjs-theme-dark .driver-popover-description { color: var(--tx2); }
    .driverjs-theme-dark .driver-popover-footer button { background-color: var(--bg); color: var(--tx); border: 1px solid var(--bd); text-shadow: none; }
    .driverjs-theme-dark .driver-popover-progress-text { color: var(--tx3); }
    .driverjs-theme-dark .driver-popover-arrow::before { border-color: var(--bg3) !important; }

    @media (max-width: 767px) {
        #sec-interview-setup { --setup-gap: 16px; }
        #sec-interview-setup .setup-panel {
            padding: 14px !important;
            margin-bottom: 0;
        }
        #sec-interview-setup .setup-panel h5 {
            font-size: 0.96rem;
            margin-bottom: 14px !important;
        }
        #sec-interview-setup .custom-radio,
        #sec-interview-setup .custom-cbx {
            padding: 12px;
        }
        #sec-interview-setup .summary-row {
            padding: 9px 0;
            font-size: 0.82rem;
        }
        #panel-summary {
            position: static !important;
            top: auto !important;
        }
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>

<div class="db-section active" id="sec-interview-setup">
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h4 class="text-gradient-primary" style="font-size:1.4rem;font-weight:800;margin-bottom:4px;letter-spacing:-0.5px;text-transform:uppercase;"><i class="fa-solid fa-sliders me-2"></i>Interview Setup</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Configure your mock interview session to match your goals.</p>
        </div>
        <div>
        </div>
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
            <div class="col-lg-8" id="setup-left-col">
                
                <!-- Basic Info -->
                <div class="setup-panel animate-fade-up delay-100" id="panel-basic">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-briefcase me-2" style="color:#60a5fa"></i> Basic Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="olbl">Interview Category</label>
                            <select class="oinp setup-input" name="category_id" id="valCategory" required>
                                @if(isset($categories) && $categories->count() > 0)
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                    @endforeach
                                @else
                                    <option value="" disabled selected>No Categories Available (Contact Admin)</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Target Position</label>
                            <input class="oinp setup-input" type="text" name="target_position" id="valPosition" placeholder="Enter your target role (e.g. Software Developer)..." value="{{ old('target_position', $selectedApplication?->job_title ?? ($selectedPack?->role_family ?? '')) }}" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="olbl">Tracked Application</label>
                            <select class="oinp setup-input" name="job_application_id" id="valApplication">
                                <option value="">No tracked job</option>
                                @foreach(($applications ?? collect()) as $application)
                                    <option value="{{ $application->id }}" {{ (int) old('job_application_id', $selectedApplication?->id) === (int) $application->id ? 'selected' : '' }}>
                                        {{ $application->company_name }} - {{ $application->job_title }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="desc-text">Use your saved resume, job description, and 7-day plan context.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Interview Pack</label>
                            <select class="oinp setup-input" name="interview_pack_id" id="valPack">
                                <option value="">Custom setup</option>
                                @foreach(($packs ?? collect()) as $pack)
                                    <option value="{{ $pack->id }}" {{ (int) old('interview_pack_id', $selectedPack?->id) === (int) $pack->id ? 'selected' : '' }}>
                                        {{ $pack->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="desc-text"><a href="{{ route('user.packs.index') }}" style="color:#60a5fa;text-decoration:none;">Browse packs</a> for company and role-specific drills.</div>
                        </div>
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

                <!-- Advanced Personalization -->
                <div class="setup-panel animate-fade-up delay-200" id="panel-advanced">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-file-lines me-2" style="color:#a78bfa"></i> Advanced Personalization <span class="badge bg-primary" style="font-size:0.7rem;vertical-align:middle;margin-left:5px">New</span></h5>
                    <p style="font-size:.85rem;color:var(--tx3);margin-bottom:15px">Provide your resume and the target job description to get highly tailored, role-specific questions.</p>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="olbl">Upload Resume (PDF)</label>
                            <div class="drop-zone" id="resumeDropZone" onclick="document.getElementById('resumeFileInput').click()">
                                <i class="fa-solid fa-cloud-arrow-up drop-zone-icon"></i>
                                <div class="drop-zone-text" id="dropZoneText">Drag & Drop your PDF resume here<br><span style="font-size:0.75rem;opacity:0.7">or click to browse</span></div>
                                <input type="file" id="resumeFileInput" accept=".pdf" style="display:none;" onchange="handleResumeUpload(event)">
                            </div>
                            <textarea class="oinp setup-input mt-2" name="resume_text" id="valResume" rows="3" placeholder="Or paste your resume text manually here..." style="font-size:0.8rem;">{{ old('resume_text', $selectedApplication?->resume_text ?? '') }}</textarea>
                            <div id="pdfParsingIndicator" style="display:none; color:#60a5fa; font-size:0.8rem; margin-top:5px;"><i class="fa-solid fa-circle-notch fa-spin me-1"></i> Extracting text from PDF...</div>
                        </div>
                        <div class="col-md-12">
                            <label class="olbl">Paste Job Description (Optional)</label>
                            <textarea class="oinp setup-input" name="job_description" id="valJobDescription" rows="3" placeholder="Paste the exact job description you are applying for to tailor the questions to those specific requirements...">{{ old('job_description', $selectedApplication?->job_description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Interview Structure -->
                <div class="setup-panel animate-fade-up delay-300" id="panel-structure">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-layer-group me-2" style="color:#60a5fa"></i> Interview Structure</h5>
                    
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
                <div class="setup-panel animate-fade-up delay-400" id="panel-content">
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
                                <option value="Personal">Personal</option>
                                <option value="Salary Negotiation">Salary Negotiation (New)</option>
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

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="olbl">Interviewer Strictness</label>
                            <select class="oinp setup-input" name="interviewer_strictness" id="valStrictness">
                                <option value="friendly">Friendly Interviewer</option>
                                <option value="neutral" selected>Neutral HR Interviewer</option>
                                <option value="strict">Strict Technical Lead</option>
                                <option value="executive">Executive Panel</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Live Feedback Mode</label>
                            <select class="oinp setup-input" name="live_feedback_mode" id="valFeedbackMode">
                                <option value="coaching" selected>Coaching On</option>
                                <option value="real_interview">Real Interview Mode</option>
                            </select>
                        </div>
                    </div>

                    <div class="setup-chip-panel mb-4">
                        <label class="custom-cbx" style="margin:0;">
                            <input type="checkbox" name="pressure_mode" id="valPressureMode" value="1" class="setup-input" {{ old('pressure_mode', $selectedPack?->pressure_mode ? 1 : 0) ? 'checked' : '' }}>
                            Pressure Mode
                        </label>
                        <div class="desc-text">Uses strict interviewer behavior, real interview mode, tougher follow-ups, and timed answers by default.</div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-12">
                            <label class="olbl">Company Persona Simulator</label>
                            <p style="font-size:.75rem;color:var(--tx3);margin-top:-4px;margin-bottom:8px;">Have the AI simulate the specific interview style of top companies.</p>
                            <input type="hidden" name="company_persona" id="valPersona" value="" class="setup-input">
                            <div class="persona-grid">
                                <div class="persona-card selected" onclick="selectPersona(this, '')">
                                    <i class="fa-solid fa-circle-check persona-check"></i>
                                    <i class="fa-solid fa-building persona-icon" style="color:#60a5fa"></i>
                                    <div class="persona-title">Standard</div>
                                    <div class="persona-desc">General Industry</div>
                                </div>
                                <div class="persona-card" onclick="selectPersona(this, 'Amazon')">
                                    <i class="fa-solid fa-circle-check persona-check"></i>
                                    <i class="fa-brands fa-amazon persona-icon" style="color:#f97316"></i>
                                    <div class="persona-title">Amazon</div>
                                    <div class="persona-desc">Leadership Principles</div>
                                </div>
                                <div class="persona-card" onclick="selectPersona(this, 'Google')">
                                    <i class="fa-solid fa-circle-check persona-check"></i>
                                    <i class="fa-brands fa-google persona-icon" style="color:#ef4444"></i>
                                    <div class="persona-title">Google</div>
                                    <div class="persona-desc">Googlyness & Scaling</div>
                                </div>
                                <div class="persona-card" onclick="selectPersona(this, 'McKinsey')">
                                    <i class="fa-solid fa-circle-check persona-check"></i>
                                    <i class="fa-solid fa-chart-pie persona-icon" style="color:#3b82f6"></i>
                                    <div class="persona-title">McKinsey</div>
                                    <div class="persona-desc">Consulting & Case</div>
                                </div>
                                <div class="persona-card" onclick="selectPersona(this, 'Goldman Sachs')">
                                    <i class="fa-solid fa-circle-check persona-check"></i>
                                    <i class="fa-solid fa-vault persona-icon" style="color:#eab308"></i>
                                    <div class="persona-title">Goldman Sachs</div>
                                    <div class="persona-desc">Finance & Pressure</div>
                                </div>
                            </div>
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
                <div class="setup-panel animate-fade-up delay-400" id="panel-response">
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
            <div class="col-lg-4 animate-fade-up delay-200">
                <div style="position:sticky;top:20px;">
                    <div class="setup-panel" id="panel-summary" style="background:linear-gradient(145deg, rgba(59,130,246,0.08) 0%, rgba(59,130,246,0.02) 100%); border:1px solid rgba(59,130,246,0.25); box-shadow: 0 15px 35px rgba(59,130,246,0.1), inset 0 1px 1px rgba(255, 255, 255, 0.1); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);">
                        <h5 style="font-weight:800;margin-bottom:24px;color:var(--pur);text-align:center;letter-spacing:0.5px;"><i class="fa-solid fa-clipboard-list me-2"></i> Interview Summary</h5>
                        
                        <div class="summary-row">
                            <span class="summary-label">Category:</span>
                            <span class="summary-val" id="sumCategory">Job Interview</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Position:</span>
                            <span class="summary-val" id="sumPosition">Software Developer</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Application:</span>
                            <span class="summary-val" id="sumApplication">None</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Pack:</span>
                            <span class="summary-val" id="sumPack">Custom</span>
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
                            <span class="summary-label">Strictness:</span>
                            <span class="summary-val" id="sumStrictness">Neutral HR Interviewer</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Live Feedback:</span>
                            <span class="summary-val" id="sumFeedbackMode">Coaching On</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Pressure Mode:</span>
                            <span class="summary-val" id="sumPressureMode">Off</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Company Persona:</span>
                            <span class="summary-val" id="sumPersona">Standard</span>
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
                            <button type="submit" id="btn-start-interview" class="btn w-100 py-3 btn-shine" style="font-size:1.1rem;font-weight:700;border-radius:14px;background:var(--dash-primary, #60a5fa);color:white;border:none;box-shadow:0 8px 25px rgba(96,165,250,0.4);transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 30px rgba(96,165,250,0.6)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 8px 25px rgba(96,165,250,0.4)'">
                                Start Mock Interview <i class="fa-solid fa-play ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@php
    $applicationData = ($applications ?? collect())->mapWithKeys(fn ($application) => [
        $application->id => [
            'company_name' => $application->company_name,
            'job_title' => $application->job_title,
            'resume_text' => $application->resume_text,
            'job_description' => $application->job_description,
            'match_score' => $application->match_score,
        ],
    ])->all();
    $packData = ($packs ?? collect())->mapWithKeys(fn ($pack) => [
        $pack->id => [
            'name' => $pack->name,
            'role_family' => $pack->role_family,
            'difficulty' => $pack->difficulty,
            'interview_focus' => $pack->interview_focus,
            'company_persona' => $pack->company_persona,
            'question_types' => $pack->question_types ?? [],
            'pressure_mode' => (bool) $pack->pressure_mode,
        ],
    ])->all();
@endphp
<script>
    const applicationData = @json($applicationData);
    const packData = @json($packData);

    function updateSummary() {
        // Category text
        const catSelect = document.getElementById('valCategory');
        if(catSelect.options.length > 0) {
            document.getElementById('sumCategory').innerText = catSelect.options[catSelect.selectedIndex].text;
        }

        // Position
        const posVal = document.getElementById('valPosition').value;
        document.getElementById('sumPosition').innerText = posVal || 'Not Specified';

        const appSelect = document.getElementById('valApplication');
        if (appSelect) {
            document.getElementById('sumApplication').innerText = appSelect.value ? appSelect.options[appSelect.selectedIndex].text : 'None';
        }

        const packSelect = document.getElementById('valPack');
        if (packSelect) {
            document.getElementById('sumPack').innerText = packSelect.value ? packSelect.options[packSelect.selectedIndex].text : 'Custom';
        }

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

        const strictness = document.getElementById('valStrictness');
        if (strictness) {
            document.getElementById('sumStrictness').innerText = strictness.options[strictness.selectedIndex].text;
        }

        const feedbackMode = document.getElementById('valFeedbackMode');
        if (feedbackMode) {
            document.getElementById('sumFeedbackMode').innerText = feedbackMode.options[feedbackMode.selectedIndex].text;
        }

        const pressureMode = document.getElementById('valPressureMode');
        if (pressureMode) {
            document.getElementById('sumPressureMode').innerText = pressureMode.checked ? 'On' : 'Off';
        }

        // Persona
        const personaInput = document.getElementById('valPersona');
        if (personaInput) {
            document.getElementById('sumPersona').innerText = personaInput.value || 'Standard';
        }

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

    document.getElementById('valApplication')?.addEventListener('change', applyApplicationSelection);
    document.getElementById('valPack')?.addEventListener('change', applyPackSelection);
    document.getElementById('valPressureMode')?.addEventListener('change', applyPressureMode);

    function selectPersona(cardEl, value) {
        document.querySelectorAll('.persona-card').forEach(el => el.classList.remove('selected'));
        cardEl.classList.add('selected');
        document.getElementById('valPersona').value = value;
        updateSummary();
    }

    function setPersonaValue(value) {
        document.getElementById('valPersona').value = value || '';
        let selected = false;
        document.querySelectorAll('.persona-card').forEach(el => {
            const clickAttr = el.getAttribute('onclick') || '';
            const matches = value ? clickAttr.includes(`'${value}'`) : clickAttr.includes("''");
            el.classList.toggle('selected', matches && !selected);
            if (matches) selected = true;
        });
        if (!selected) {
            document.querySelectorAll('.persona-card').forEach(el => el.classList.remove('selected'));
        }
    }

    function setSelectValue(id, value) {
        const el = document.getElementById(id);
        if (!el || value === null || value === undefined || value === '') return;
        const option = Array.from(el.options).find(opt => opt.value === String(value));
        if (option) el.value = String(value);
    }

    function setDifficulty(value) {
        const radio = document.querySelector(`input[name="difficulty"][value="${value}"]`);
        if (radio) radio.checked = true;
    }

    function setQuestionTypes(types) {
        if (!Array.isArray(types) || types.length === 0) return;
        document.querySelectorAll('input[name="question_types[]"]').forEach(input => {
            input.checked = types.includes(input.value);
        });
    }

    function applyPressureMode() {
        const enabled = document.getElementById('valPressureMode')?.checked;
        if (enabled) {
            setSelectValue('valStrictness', 'strict');
            setSelectValue('valAssistance', 'challenge');
            setSelectValue('valFeedbackMode', 'real_interview');
            if (document.getElementById('valTimeLimit')?.value === '0') {
                setSelectValue('valTimeLimit', '2');
            }
        }
        updateSummary();
    }

    function applyApplicationSelection() {
        const selectedId = document.getElementById('valApplication')?.value;
        const app = selectedId ? applicationData[selectedId] : null;
        if (!app) {
            updateSummary();
            return;
        }

        document.getElementById('valPosition').value = app.job_title || '';
        document.getElementById('valResume').value = app.resume_text || '';
        document.getElementById('valJobDescription').value = app.job_description || '';
        updateSummary();
    }

    function applyPackSelection() {
        const selectedId = document.getElementById('valPack')?.value;
        const pack = selectedId ? packData[selectedId] : null;
        if (!pack) {
            updateSummary();
            return;
        }

        if (!document.getElementById('valPosition').value && pack.role_family) {
            document.getElementById('valPosition').value = pack.role_family;
        }
        setDifficulty(pack.difficulty);
        setSelectValue('valFocus', pack.interview_focus);
        setPersonaValue(pack.company_persona);
        setQuestionTypes(pack.question_types || []);

        const pressure = document.getElementById('valPressureMode');
        if (pressure && pack.pressure_mode) {
            pressure.checked = true;
            applyPressureMode();
        }

        updateSummary();
    }

    // PDF Drag and Drop Handling
    const dropZone = document.getElementById('resumeDropZone');
    const valResume = document.getElementById('valResume');
    const pdfIndicator = document.getElementById('pdfParsingIndicator');

    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.classList.remove('dragover'); });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            processPdfFile(e.dataTransfer.files[0]);
        }
    });

    function handleResumeUpload(e) {
        if (e.target.files && e.target.files[0]) {
            processPdfFile(e.target.files[0]);
        }
    }

    async function processPdfFile(file) {
        if (file.type !== 'application/pdf') {
            alert('Please upload a valid PDF file.');
            return;
        }
        document.getElementById('dropZoneText').innerHTML = `<i class="fa-solid fa-file-pdf me-2"></i> ${file.name}`;
        pdfIndicator.style.display = 'block';
        valResume.value = '';

        try {
            const fileReader = new FileReader();
            fileReader.onload = async function() {
                const typedarray = new Uint8Array(this.result);
                const pdf = await pdfjsLib.getDocument(typedarray).promise;
                let fullText = '';
                
                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const textContent = await page.getTextContent();
                    const pageText = textContent.items.map(item => item.str).join(' ');
                    fullText += pageText + '\n\n';
                }
                
                valResume.value = fullText.trim();
                pdfIndicator.style.display = 'none';
                updateSummary();
            };
            fileReader.readAsArrayBuffer(file);
        } catch (err) {
            console.error(err);
            pdfIndicator.innerHTML = '<i class="fa-solid fa-circle-exclamation text-danger me-1"></i> Error extracting text.';
        }
    }

    // Initial update
    window.onload = () => {
        applyApplicationSelection();
        applyPackSelection();
        applyPressureMode();
        updateSummary();
    };
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#panel-basic', popover: { title: 'Basic Information', description: 'Choose the interview category, target position, and AI provider for this session.', side: 'top', align: 'center' }},
            { element: '#panel-advanced', popover: { title: 'Personalization', description: 'Add your resume, job description, or company context for role-specific questions.', side: 'top', align: 'center' }},
            { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Set difficulty, question count, and optional response timing before you start.', side: 'top', align: 'center' }},
            { element: '#panel-content', popover: { title: 'Content Focus', description: 'Pick the interview focus, assistance level, question types, and company style.', side: 'top', align: 'center' }},
            { element: '#panel-response', popover: { title: 'Response Mode', description: 'Choose typed, voice, or hybrid answers depending on how you want to practice.', side: 'top', align: 'center' }},
            { element: '#panel-summary', popover: { title: 'Live Summary', description: 'Confirm your interview setup before generating the practice session.', side: 'top', align: 'center' }},
            { element: '#btn-start-interview', popover: { title: 'Start Interview', description: 'Generate your customized mock interview when the setup looks right.', side: 'top', align: 'center' }}
        ];

        const stepsDesktop = [
            { element: '#panel-basic', popover: { title: 'Basic Information', description: 'Choose the interview category, target position, and AI provider for this session.', side: 'top', align: 'center' }},
            { element: '#panel-advanced', popover: { title: 'Personalization', description: 'Add your resume, job description, or company context for role-specific questions.', side: 'top', align: 'center' }},
            { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Set difficulty, question count, and optional response timing before you start.', side: 'top', align: 'center' }},
            { element: '#panel-content', popover: { title: 'Content Focus', description: 'Pick the interview focus, assistance level, question types, and company style.', side: 'top', align: 'center' }},
            { element: '#panel-response', popover: { title: 'Response Mode', description: 'Choose typed, voice, or hybrid answers depending on how you want to practice.', side: 'top', align: 'center' }},
            { element: '#panel-summary', popover: { title: 'Live Summary', description: 'Confirm your interview setup before generating the practice session.', side: 'top', align: 'center' }},
            { element: '#btn-start-interview', popover: { title: 'Start Interview', description: 'Generate your customized mock interview when the setup looks right.', side: 'top', align: 'center' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_interview_setup',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
            beforeStart: () => {
                document.documentElement.style.setProperty('scroll-behavior', 'auto', 'important');
            },
            onBeforeDestroy: () => {
                document.documentElement.style.removeProperty('scroll-behavior');
            },
            onDestroyed: () => {
                document.documentElement.style.removeProperty('scroll-behavior');
            },
        });
    });
</script>
@endpush
@endsection

