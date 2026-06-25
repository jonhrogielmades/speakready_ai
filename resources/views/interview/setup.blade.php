@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('content')
<style>
    .setup-panel { background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;margin-bottom:20px; }
    .olbl { font-weight:600;color:var(--tx);font-size:.9rem;margin-bottom:8px;display:block; }
    .oinp { width:100%;padding:10px 14px;border:1px solid var(--bd);border-radius:10px;background:var(--bg3);color:var(--tx);font-size:.9rem;transition:border-color 0.2s; }
    .oinp:focus { outline:none;border-color:var(--pur); }
    .desc-text { font-size:.75rem;color:var(--tx3);margin-top:4px; }
    
    .custom-radio { position:relative;display:flex;align-items:flex-start;padding:12px;border:1px solid var(--bd);border-radius:10px;background:var(--bg3);cursor:pointer;margin-bottom:10px;transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s, transform 0.2s; }
    .custom-radio:hover { border-color:#60a5fa; }
    .custom-radio input[type="radio"] { margin-top:4px;margin-right:12px;accent-color:var(--pur); }
    .custom-radio .r-title { font-weight:600;font-size:.9rem;color:var(--tx);display:block; }
    .custom-radio .r-desc { font-size:.75rem;color:var(--tx3);display:block; }
    
    .cbx-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
    .custom-cbx { display:flex;align-items:center;padding:10px;border:1px solid var(--bd);border-radius:8px;background:var(--bg3);cursor:pointer;font-size:.85rem;color:var(--tx);transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s, transform 0.2s; }
    .custom-cbx:hover { border-color:#60a5fa; }
    .custom-cbx input[type="checkbox"] { margin-right:10px;accent-color:#60a5fa; }

    .summary-row { display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--bd);font-size:.85rem; }
    .summary-row:last-child { border-bottom:none; }
    .summary-label { color:var(--tx3);font-weight:600; }
    .summary-val { color:var(--tx);font-weight:700;text-align:right; }

    /* Drag and Drop Zone */
    .drop-zone { border: 2px dashed var(--bd); border-radius: 12px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s; background: var(--bg3); }
    .drop-zone.dragover { border-color: #60a5fa; background: rgba(96,165,250,0.1); }
    .drop-zone-icon { font-size: 2rem; color: #60a5fa; margin-bottom: 10px; }
    .drop-zone-text { font-size: 0.9rem; color: var(--tx2); font-weight: 500; }
    
    /* Persona Cards */
    .persona-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-top: 10px; }
    .persona-card { border: 1px solid var(--bd); border-radius: 12px; padding: 15px; text-align: center; cursor: pointer; background: var(--bg3); transition: all 0.2s; position: relative; overflow: hidden; }
    .persona-card:hover { border-color: #a78bfa; transform: translateY(-2px); }
    .persona-card.selected { border-color: #8b5cf6; background: rgba(139,92,246,0.1); box-shadow: 0 4px 15px rgba(139,92,246,0.2); }
    .persona-icon { font-size: 1.8rem; margin-bottom: 8px; color: var(--tx); }
    .persona-card.selected .persona-icon { color: #8b5cf6; }
    .persona-title { font-weight: 700; font-size: 0.85rem; color: var(--tx); }
    .persona-desc { font-size: 0.7rem; color: var(--tx3); margin-top: 4px; }
    .persona-check { position: absolute; top: 8px; right: 8px; color: #8b5cf6; font-size: 0.9rem; opacity: 0; transition: opacity 0.2s; }
    .persona-card.selected .persona-check { opacity: 1; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';</script>

<div class="db-section active" id="sec-interview-setup">
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Interview Setup</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Configure your mock interview session to match your goals.</p>
        </div>
        <div>
            <button class="btn btn-sm d-inline-flex align-items-center" style="background:var(--bg3); border:1px solid var(--bd); color:var(--tx2); border-radius:10px; font-weight:600;" onclick="startOnboardingTour()"><i class="fa-solid fa-play me-sm-1" style="color:#60a5fa"></i> <span class="d-none d-sm-inline">Replay Tutorial</span></button>
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
                <div class="setup-panel" id="panel-basic">
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

                <!-- Advanced Personalization -->
                <div class="setup-panel" id="panel-advanced">
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
                            <textarea class="oinp setup-input mt-2" name="resume_text" id="valResume" rows="3" placeholder="Or paste your resume text manually here..." style="font-size:0.8rem;"></textarea>
                            <div id="pdfParsingIndicator" style="display:none; color:#60a5fa; font-size:0.8rem; margin-top:5px;"><i class="fa-solid fa-circle-notch fa-spin me-1"></i> Extracting text from PDF...</div>
                        </div>
                        <div class="col-md-12">
                            <label class="olbl">Paste Job Description (Optional)</label>
                            <textarea class="oinp setup-input" name="job_description" rows="3" placeholder="Paste the exact job description you are applying for to tailor the questions to those specific requirements..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Interview Structure -->
                <div class="setup-panel" id="panel-structure">
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
                <div class="setup-panel" id="panel-content">
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
                <div class="setup-panel" id="panel-response">
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
                    <div class="setup-panel" id="panel-summary" style="background:linear-gradient(145deg, rgba(59,130,246,0.05) 0%, rgba(59,130,246,0.05) 100%); border:1px solid rgba(59,130,246,0.2);">
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
                            <button type="submit" id="btn-start-interview" class="bgrd btn w-100 py-3" style="font-size:1.1rem;font-weight:700;border-radius:12px;box-shadow:0 4px 15px rgba(59,130,246,0.3)">
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

    function selectPersona(cardEl, value) {
        document.querySelectorAll('.persona-card').forEach(el => el.classList.remove('selected'));
        cardEl.classList.add('selected');
        document.getElementById('valPersona').value = value;
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
        updateSummary();
    };
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.driver === 'undefined') return;
        const driver = window.driver.js.driver;

        const stepsMobile = [
            { element: '#setup-left-col', popover: { title: 'Interview Setup', description: 'Here you can customize every aspect of your mock interview before you begin.', side: "bottom", align: 'start' }},
            { element: '#panel-basic', popover: { title: 'Basic Information', description: 'Set the target role, experience level, and industry to tailor the questions.', side: "bottom", align: 'start' }},
            { element: '#panel-advanced', popover: { title: 'Advanced Personalization', description: 'Provide a job description or your resume to generate highly specific questions.', side: "bottom", align: 'start' }},
            { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Choose the difficulty, number of questions, and the specific categories you want to be tested on.', side: "top", align: 'start' }},
            { element: '#panel-content', popover: { title: 'Content Assistance', description: 'Enable hints, language checking, or strict timing depending on how much support you want.', side: "top", align: 'start' }},
            { element: '#panel-response', popover: { title: 'Response Mode', description: 'Choose between standard Voice responses or Video tracking to analyze your expressions and confidence.', side: "top", align: 'start' }},
            { element: '#btn-start-interview', popover: { title: 'Ready to Begin', description: 'Click here to generate your customized mock interview and start practicing!', side: "top", align: 'center' }}
        ];

        const stepsDesktop = [
            { element: '#setup-left-col', popover: { title: 'Interview Setup', description: 'Here you can customize every aspect of your mock interview before you begin.', side: "bottom", align: 'start' }},
            { element: '#panel-basic', popover: { title: 'Basic Information', description: 'Set the target role, experience level, and industry to tailor the questions.', side: "bottom", align: 'start' }},
            { element: '#panel-advanced', popover: { title: 'Advanced Personalization', description: 'Provide a job description or your resume to generate highly specific questions.', side: "bottom", align: 'start' }},
            { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Choose the difficulty, number of questions, and the specific categories you want to be tested on.', side: "top", align: 'start' }},
            { element: '#panel-content', popover: { title: 'Content Assistance', description: 'Enable hints, language checking, or strict timing depending on how much support you want.', side: "top", align: 'start' }},
            { element: '#panel-response', popover: { title: 'Response Mode', description: 'Choose between standard Voice responses or Video tracking to analyze your expressions and confidence.', side: "top", align: 'start' }},
            { element: '#btn-start-interview', popover: { title: 'Ready to Begin', description: 'Click here to generate your customized mock interview and start practicing!', side: "top", align: 'center' }}
        ];

        const driverObj = driver({
            showProgress: true,
            animate: true,
            popoverClass: document.documentElement.classList.contains('lm') ? 'driverjs-theme-light' : 'driverjs-theme-dark',
            steps: {{ $isMobile ? 'true' : 'false' }} ? stepsMobile : stepsDesktop,
            onDestroyStarted: () => {
                if (!driverObj.hasNextStep() || confirm("Are you sure you want to exit the tutorial?")) {
                    driverObj.destroy();
                    localStorage.setItem('onboarding_completed_interview_setup', 'true');
                }
            },
        });

        window.startOnboardingTour = function() {
            driverObj.drive();
        };

        if (!localStorage.getItem('onboarding_completed_interview_setup')) {
            setTimeout(() => {
                startOnboardingTour();
            }, 500);
        }
    });
</script>
@endpush
@endsection