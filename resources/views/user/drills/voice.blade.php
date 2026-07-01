@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<style>
    /* Premium Dashboard Styles inherited/adapted */
    .premium-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .premium-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 45px rgba(0, 0, 0, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.08);
    }
    /* Wave animation */
    @keyframes wave { 0%, 100% { height: 20px; } 50% { height: 80px; } }
    .wave-bar {
        width: 6px; background: #3b82f6; border-radius: 4px;
        animation: wave 1s infinite ease-in-out;
    }
    /* Filler word highlight */
    .filler-word {
        background: rgba(248, 113, 113, 0.2);
        color: #f87171;
        padding: 0 4px;
        border-radius: 4px;
        font-weight: 600;
    }
    .keyword-highlight {
        background: rgba(52, 211, 153, 0.2);
        color: #34d399;
        padding: 0 4px;
        border-radius: 4px;
        font-weight: 600;
    }
    .mispronounced {
        text-decoration: underline wavy #fbbf24;
    }
    .stat-box {
        background: var(--bg3);
        border: 1px solid var(--bd);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }
    .stat-val { font-size: 1.5rem; font-weight: 700; color: var(--tx); }
    .stat-lbl { font-size: 0.8rem; color: var(--tx3); text-transform: uppercase; letter-spacing: 1px; }

    .tab-pane { display: none; }
    .tab-pane.active { display: block; animation: fadeIn 0.4s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

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
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }

    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
</style>

<div class="db-section active">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <div>
<h4 class="text-gradient-primary" style="font-size:1.4rem;font-weight:800;margin-bottom:4px;letter-spacing:-0.5px;text-transform:uppercase;">
 <i class="fa-solid fa-ear-listen me-2"></i>Voice Rehearsal Studio</h4>
            <p style="color:var(--tx2);margin-top:4px;margin-bottom:0;font-size:0.95rem;font-weight:500;">Master your delivery, pacing, and tone with AI analysis.</p>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <ul class="nav nav-pills" id="moduleTabs" style="margin-bottom:0;">
                <li class="nav-item"><a class="nav-link active" href="#" data-target="tab-practice">Practice</a></li>
                <li class="nav-item"><a class="nav-link" href="#" data-target="tab-analytics">History & Analytics</a></li>
            </ul>
        </div>
    </div>

    <!-- TAB: PRACTICE -->
    <div id="tab-practice" class="tab-pane active">
        <div class="row g-4">
            <!-- Left: Controls & Recording -->
            <div class="col-lg-8 animate-fade-up delay-100">
                <div class="premium-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <select id="categorySelect" class="form-select w-auto" style="background:var(--bg3);color:var(--tx);border-color:var(--bd);border-radius:10px;">
                            <option value="Tell Me About Yourself">Tell Me About Yourself</option>
                            <option value="Strengths and Weaknesses">Strengths & Weaknesses</option>
                            <option value="Leadership">Leadership Questions</option>
                            <option value="Problem Solving">Problem Solving</option>
                            <option value="Technical">Technical Questions</option>
                            <option value="Scholarship">Scholarship Questions</option>
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" onclick="randomizePrompt()" style="border-radius:8px;"><i class="fa-solid fa-shuffle"></i> Randomize</button>
                    </div>

                    <div class="text-center mb-5">
                        <h5 style="color:#60a5fa;font-size:0.85rem;font-weight:700;letter-spacing:1px;margin-bottom:12px;">PROMPT</h5>
                        <h3 id="promptText" style="color:var(--tx);font-weight:600;line-height:1.4;">"Tell me about a time you showed leadership."</h3>
                    </div>

                    <!-- Mic Visualization -->
                    <div style="margin-bottom:40px;position:relative;height:160px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(139,92,246,0.05) 100%);border-radius:24px;border:1px solid rgba(139,92,246,0.2);box-shadow:inset 0 4px 20px rgba(0,0,0,0.1), 0 10px 30px rgba(59,130,246,0.1);">
                        <!-- Idle State -->
                        <div id="micIdle" class="text-center">
                            <div style="width:64px;height:64px;border-radius:50%;background:rgba(59,130,246,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto;color:#60a5fa;font-size:1.8rem;transition:all 0.3s;">
                                <i class="fa-solid fa-microphone"></i>
                            </div>
                            <div id="statusText" style="color:var(--tx3);margin-top:12px;font-size:0.9rem;">Ready to record</div>
                        </div>

                        <!-- Active State -->
                        <div id="micActive" style="display:none;align-items:center;gap:6px;height:100%;">
                            <div class="wave-bar" style="height:30px;animation-delay:0.1s"></div>
                            <div class="wave-bar" style="height:60px;animation-delay:0.2s"></div>
                            <div class="wave-bar" style="height:80px;animation-delay:0.3s"></div>
                            <div class="wave-bar" style="height:50px;animation-delay:0.4s"></div>
                            <div class="wave-bar" style="height:70px;animation-delay:0.5s"></div>
                            <div class="wave-bar" style="height:40px;animation-delay:0.6s"></div>
                            <div class="wave-bar" style="height:90px;animation-delay:0.7s"></div>
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="d-flex justify-content-center gap-3 mb-4">
                        <button id="btnStart" class="btn btn-shine" style="background:var(--dash-primary, #60a5fa);color:#fff;border-radius:12px;padding:12px 24px;font-weight:600;border:none;box-shadow:0 4px 15px rgba(96,165,250,0.4);" onclick="startRec()"><i class="fa-solid fa-play me-2"></i> Start</button>
                        <button id="btnPause" class="btn btn-warning" style="display:none;color:#fff;border-radius:12px;padding:12px 24px;font-weight:600;" onclick="pauseRec()"><i class="fa-solid fa-pause me-2"></i> Pause</button>
                        <button id="btnResume" class="btn btn-info" style="display:none;color:#fff;border-radius:12px;padding:12px 24px;font-weight:600;" onclick="resumeRec()"><i class="fa-solid fa-play me-2"></i> Resume</button>
                        <button id="btnStop" class="btn btn-danger" style="display:none;color:#fff;border-radius:12px;padding:12px 24px;font-weight:600;" onclick="stopRec()"><i class="fa-solid fa-stop me-2"></i> Stop & Analyze</button>
                        <button id="btnRerecord" class="btn btn-outline-secondary" style="display:none;border-radius:12px;padding:12px 24px;font-weight:600;" onclick="resetRec()"><i class="fa-solid fa-rotate-left me-2"></i> Retry</button>
                    </div>

                    <!-- Live Stats -->
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-val" id="timeDisp">0:00</div>
                                <div class="stat-lbl">Duration</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-val" id="wpmDisp">0</div>
                                <div class="stat-lbl">WPM</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-val" id="fillerDisp" style="color:#f87171;">0</div>
                                <div class="stat-lbl">Fillers</div>
                            </div>
                        </div>
                    </div>

                    <!-- Transcript Box -->
                    <div>
                        <div class="d-flex justify-content-between mb-2">
                            <label style="font-size:0.85rem;color:var(--tx3);font-weight:600;text-transform:uppercase;">Live Transcript</label>
                            <span id="transStatus" style="font-size:0.8rem;color:#34d399;display:none;"><i class="fa-solid fa-circle-dot fa-fade me-1"></i> Transcribing</span>
                        </div>
                        <div id="transcriptView" style="background:var(--bg3);border:1px solid var(--bd);border-radius:12px;padding:16px;min-height:120px;color:var(--tx);font-size:1.05rem;line-height:1.6;white-space:pre-wrap;" contenteditable="false">Your speech will appear here...</div>
                        <p class="mt-2" style="font-size:0.8rem;color:var(--tx3);display:none;" id="editHint"><i class="fa-solid fa-pencil me-1"></i> You can edit the transcript above manually before saving.</p>
                    </div>
                </div>
            </div>

            <!-- Right: Post-Analysis Dashboard -->
            <div class="col-lg-4 animate-fade-up delay-200">
                <div id="analysisPanel" style="opacity:0.5;pointer-events:none;transition:opacity 0.4s;">
                    <div class="premium-card mb-4" style="background: linear-gradient(180deg, var(--sf) 0%, rgba(59,130,246,0.05) 100%);">
                        <h6 class="fw-bold mb-4"><i class="fa-solid fa-chart-pie me-2" style="color:#60a5fa;"></i> AI Assessment</h6>
                        
                        <!-- Clarity & Confidence -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="text-center w-50">
                                <div style="font-size:2rem;font-weight:800;color:#34d399;" id="resClarity">--%</div>
                                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Clarity Score</div>
                            </div>
                            <div style="width:1px;height:40px;background:var(--bd);"></div>
                            <div class="text-center w-50">
                                <div style="font-size:1.2rem;font-weight:700;color:#60a5fa;" id="resConfidence">--</div>
                                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Confidence</div>
                            </div>
                        </div>

                        <!-- Pace Rating -->
                        <div class="mb-4 p-3" style="background:var(--bg3);border-radius:10px;border:1px solid var(--bd);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="font-size:0.85rem;color:var(--tx2);">Speaking Pace</span>
                                <span style="font-weight:700;" id="resPaceRating">--</span>
                            </div>
                            <div class="progress-track" style="height:6px;">
                                <div id="paceBar" class="progress-fill" style="width:0%;background:#3b82f6;"></div>
                            </div>
                            <div style="font-size:0.75rem;color:var(--tx3);margin-top:6px;text-align:right;" id="resPaceDesc">Optimal: 100-150 WPM</div>
                        </div>

                        <!-- Pronunciation & Keywords -->
                        <div class="mb-4">
                            <h6 style="font-size:0.85rem;color:var(--tx3);text-transform:uppercase;margin-bottom:8px;">Detected Keywords</h6>
                            <div id="resKeywords" class="d-flex flex-wrap gap-2">
                                <span style="color:var(--tx3);font-size:0.85rem;">Waiting for analysis...</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 style="font-size:0.85rem;color:var(--tx3);text-transform:uppercase;margin-bottom:8px;">AI Feedback</h6>
                            <div class="p-2 mb-2" style="background:rgba(52,211,153,0.05);border:1px solid rgba(52,211,153,0.2);border-radius:8px;">
                                <strong style="color:#34d399;font-size:0.8rem;"><i class="fa-solid fa-check"></i> Strengths</strong>
                                <div id="resStrengths" style="font-size:0.85rem;color:var(--tx2);margin-top:4px;">--</div>
                            </div>
                            <div class="p-2" style="background:rgba(248,113,113,0.05);border:1px solid rgba(248,113,113,0.2);border-radius:8px;">
                                <strong style="color:#f87171;font-size:0.8rem;"><i class="fa-solid fa-arrow-trend-up"></i> Needs Work</strong>
                                <div id="resWeak" style="font-size:0.85rem;color:var(--tx2);margin-top:4px;">--</div>
                            </div>
                        </div>

                        <!-- Audio Playback -->
                        <div class="mb-4">
                            <h6 style="font-size:0.85rem;color:var(--tx3);text-transform:uppercase;margin-bottom:8px;">Playback</h6>
                            <audio id="audioPlayback" controls style="width:100%;height:40px;outline:none;" class="mb-2"></audio>
                        </div>

                        <button id="btnSave" class="btn w-100 btn-shine" style="background:#34d399;color:#fff;font-weight:600;border-radius:12px;border:none;box-shadow:0 4px 15px rgba(52,211,153,0.4);" onclick="saveSession()"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Save Session</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sample Answer Comparison (Feature 12) -->
        <div id="comparisonPanel" class="premium-card mt-4" style="display:none;">
            <h5 class="fw-bold mb-4"><i class="fa-solid fa-code-compare me-2" style="color:#60a5fa;"></i> Sample Answer Comparison</h5>
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 style="color:var(--tx2);font-size:0.9rem;">Your Answer</h6>
                    <div id="compUser" class="p-3" style="background:var(--bg3);border:1px solid var(--bd);border-radius:12px;font-size:0.95rem;color:var(--tx);min-height:100px;"></div>
                </div>
                <div class="col-md-6">
                    <h6 style="color:#60a5fa;font-size:0.9rem;">AI Improved Version</h6>
                    <div id="compAI" class="p-3" style="background:rgba(96,165,250,0.05);border:1px solid rgba(96,165,250,0.2);border-radius:12px;font-size:0.95rem;color:var(--tx);min-height:100px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: HISTORY & ANALYTICS -->
    <div id="tab-analytics" class="tab-pane">
        <div class="row g-4">
            <div class="col-lg-8 animate-fade-up delay-100">
                <!-- Progress Charts -->
                <div class="premium-card mb-4">
                    <h5 class="fw-bold mb-4">Progress Analytics</h5>
                    <div style="height:250px;">
                        <canvas id="voiceProgressChart"></canvas>
                    </div>
                </div>

                <!-- Rehearsal History -->
                <div class="premium-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0">Rehearsal History</h5>
                        <button class="btn btn-sm btn-outline-primary" style="border-radius:8px;" onclick="downloadReport()"><i class="fa-solid fa-download me-1"></i> Download Report (PDF)</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Clarity</th>
                                    <th>WPM</th>
                                    <th>Fillers</th>
                                </tr>
                            </thead>
                            <tbody id="historyTable">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 animate-fade-up delay-200">
                <!-- AI Practice Suggestions -->
                <div class="premium-card" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(251,191,36,0.05) 100%);">
                    <h6 class="fw-bold mb-4"><i class="fa-solid fa-lightbulb me-2" style="color:#fbbf24;"></i> AI Practice Suggestions</h6>
                    
                    <div class="p-3 mb-3" style="background:var(--bg3);border-radius:12px;border:1px solid var(--bd);">
                        <div class="d-flex gap-3">
                            <i class="fa-solid fa-gauge-high" style="color:#60a5fa;margin-top:4px;"></i>
                            <div>
                                <div style="font-weight:600;font-size:0.9rem;">Pace Yourself</div>
                                <div style="font-size:0.8rem;color:var(--tx2);">Your average pace is 165 WPM. Try taking slight pauses between sentences.</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 mb-3" style="background:var(--bg3);border-radius:12px;border:1px solid var(--bd);">
                        <div class="d-flex gap-3">
                            <i class="fa-solid fa-comment-slash" style="color:#f87171;margin-top:4px;"></i>
                            <div>
                                <div style="font-weight:600;font-size:0.9rem;">Reduce Fillers</div>
                                <div style="font-size:0.8rem;color:var(--tx2);">You used "Um" 12 times last session. Try silent pauses instead of filler words.</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3" style="background:var(--bg3);border-radius:12px;border:1px solid var(--bd);">
                        <div class="d-flex gap-3">
                            <i class="fa-solid fa-briefcase" style="color:#34d399;margin-top:4px;"></i>
                            <div>
                                <div style="font-weight:600;font-size:0.9rem;">Focus on Technical</div>
                                <div style="font-size:0.8rem;color:var(--tx2);">Your clarity drops on technical questions. Practice the 'Technical' category next.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Tab Switching
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        e.target.classList.add('active');
        document.getElementById(e.target.getAttribute('data-target')).classList.add('active');
    });
});

// Mock Prompts
const prompts = {
    "Tell Me About Yourself": ["Walk me through your resume.", "How would you describe yourself in three words?", "What is your biggest professional achievement?"],
    "Strengths and Weaknesses": ["What is your greatest weakness?", "What are your top three strengths?", "Tell me about a time you failed."],
    "Leadership": ["Tell me about a time you showed leadership.", "How do you handle conflict in a team?", "Describe a situation where you had to motivate others."],
    "Problem Solving": ["Tell me about a complex problem you solved.", "How do you handle working under tight deadlines?", "Describe a time you had to make a quick decision."],
    "Technical": ["Explain a complex technical concept to a non-technical person.", "What is your process for debugging code?", "Describe the architecture of your last project."],
    "Scholarship": ["Why do you deserve this scholarship?", "How will this scholarship help you achieve your goals?", "Describe a community service project you led."]
};

function randomizePrompt() {
    const cat = document.getElementById('categorySelect').value;
    const list = prompts[cat] || prompts["Tell Me About Yourself"];
    const rand = list[Math.floor(Math.random() * list.length)];
    document.getElementById('promptText').innerText = `"${rand}"`;
}
document.getElementById('categorySelect').addEventListener('change', randomizePrompt);

// Recording Logic
let recognition = null;
let isRec = false;
let isPaused = false;
let transcript = "";
let timer = null;
let seconds = 0;
let mediaRecorder = null;
let audioChunks = [];

const fillerWordsList = ['um', 'uh', 'like', 'basically', 'you know', 'actually', 'literally'];
let fillerCount = 0;
let wordCount = 0;

if ('webkitSpeechRecognition' in window) {
    recognition = new webkitSpeechRecognition();
    recognition.continuous = true;
    recognition.interimResults = true;
    
    recognition.onresult = (event) => {
        let interim = '';
        let final = '';
        for (let i = event.resultIndex; i < event.results.length; ++i) {
            if (event.results[i].isFinal) {
                final += event.results[i][0].transcript;
            } else {
                interim += event.results[i][0].transcript;
            }
        }
        
        if (final) {
            transcript += final;
            processTranscript(transcript + interim);
        } else {
            processTranscript(transcript + interim);
        }
    };
    
    recognition.onend = () => {
        if (isRec && !isPaused) {
            recognition.start(); // Keep alive if not manually stopped
        }
    };
}

function processTranscript(text) {
    const box = document.getElementById('transcriptView');
    
    // Count words
    const words = text.trim().split(/\s+/).filter(w => w.length > 0);
    wordCount = words.length;
    
    // Detect Fillers and Highlights
    fillerCount = 0;
    let formattedHtml = text;
    
    fillerWordsList.forEach(filler => {
        const regex = new RegExp(`\\b${filler}\\b`, 'gi');
        const matches = text.match(regex);
        if (matches) fillerCount += matches.length;
        
        formattedHtml = formattedHtml.replace(regex, `<span class="filler-word">$&</span>`);
    });
    
    // Dummy keyword highlighting
    ['leadership', 'team', 'success', 'problem', 'solved', 'agile', 'communication', 'manager'].forEach(kw => {
        const regex = new RegExp(`\\b${kw}\\b`, 'gi');
        formattedHtml = formattedHtml.replace(regex, `<span class="keyword-highlight">$&</span>`);
    });
    
    // Pronunciation mock highlight
    const mispronounced = ['specifically', 'phenomenon', 'statistics'];
    mispronounced.forEach(kw => {
        const regex = new RegExp(`\\b${kw}\\b`, 'gi');
        formattedHtml = formattedHtml.replace(regex, `<span class="mispronounced" title="Possible mispronunciation">$&</span>`);
    });

    box.innerHTML = formattedHtml || "Listening...";
    document.getElementById('fillerDisp').innerText = fillerCount;
    updateWPM();
}

function updateTimer() {
    seconds++;
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    document.getElementById('timeDisp').innerText = `${m}:${s.toString().padStart(2, '0')}`;
    updateWPM();
}

function updateWPM() {
    if (seconds > 0) {
        const mins = seconds / 60;
        const wpm = Math.round(wordCount / mins);
        document.getElementById('wpmDisp').innerText = wpm;
    }
}

function initAudioRec() {
    navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
        mediaRecorder = new MediaRecorder(stream);
        mediaRecorder.ondataavailable = e => { audioChunks.push(e.data); };
        mediaRecorder.onstop = () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const audioUrl = URL.createObjectURL(audioBlob);
            document.getElementById('audioPlayback').src = audioUrl;
        };
        mediaRecorder.start();
    }).catch(e => console.error("Audio rec error:", e));
}

function startRec() {
    if (!recognition) return alert("Chrome required for speech recognition.");
    isRec = true;
    isPaused = false;
    transcript = "";
    seconds = 0;
    wordCount = 0;
    fillerCount = 0;
    audioChunks = [];
    
    document.getElementById('transcriptView').innerHTML = "";
    document.getElementById('transcriptView').setAttribute('contenteditable', 'false');
    document.getElementById('editHint').style.display = 'none';
    document.getElementById('audioPlayback').src = "";
    
    recognition.start();
    initAudioRec();
    
    timer = setInterval(updateTimer, 1000);
    updateUIState();
}

function pauseRec() {
    isPaused = true;
    recognition.stop();
    if(mediaRecorder && mediaRecorder.state === "recording") mediaRecorder.pause();
    clearInterval(timer);
    updateUIState();
}

function resumeRec() {
    isPaused = false;
    recognition.start();
    if(mediaRecorder && mediaRecorder.state === "paused") mediaRecorder.resume();
    timer = setInterval(updateTimer, 1000);
    updateUIState();
}

function stopRec() {
    isRec = false;
    isPaused = false;
    recognition.stop();
    if(mediaRecorder && mediaRecorder.state !== "inactive") mediaRecorder.stop();
    clearInterval(timer);
    updateUIState();
    
    document.getElementById('transcriptView').setAttribute('contenteditable', 'true');
    document.getElementById('editHint').style.display = 'block';
    
    generateAnalysis();
}

function resetRec() {
    stopRec();
    document.getElementById('timeDisp').innerText = "0:00";
    document.getElementById('wpmDisp').innerText = "0";
    document.getElementById('fillerDisp').innerText = "0";
    document.getElementById('transcriptView').innerHTML = "Your speech will appear here...";
    document.getElementById('analysisPanel').style.opacity = '0.5';
    document.getElementById('analysisPanel').style.pointerEvents = 'none';
    document.getElementById('comparisonPanel').style.display = 'none';
    startRec();
}

function updateUIState() {
    const stBtn = document.getElementById('btnStart');
    const paBtn = document.getElementById('btnPause');
    const reBtn = document.getElementById('btnResume');
    const spBtn = document.getElementById('btnStop');
    const rrBtn = document.getElementById('btnRerecord');
    
    const idle = document.getElementById('micIdle');
    const active = document.getElementById('micActive');
    const tStat = document.getElementById('transStatus');

    if (!isRec) {
        stBtn.style.display = 'block'; paBtn.style.display = 'none'; reBtn.style.display = 'none';
        spBtn.style.display = 'none'; rrBtn.style.display = 'none';
        idle.style.display = 'block'; active.style.display = 'none';
        tStat.style.display = 'none';
    } else if (isPaused) {
        stBtn.style.display = 'none'; paBtn.style.display = 'none'; reBtn.style.display = 'block';
        spBtn.style.display = 'block'; rrBtn.style.display = 'block';
        idle.style.display = 'block'; active.style.display = 'none';
        tStat.style.display = 'none';
        document.getElementById('statusText').innerText = "Paused";
    } else {
        stBtn.style.display = 'none'; paBtn.style.display = 'block'; reBtn.style.display = 'none';
        spBtn.style.display = 'block'; rrBtn.style.display = 'block';
        idle.style.display = 'none'; active.style.display = 'flex';
        tStat.style.display = 'inline-block';
    }
}

function generateAnalysis() {
    // Unlock analysis panel
    const panel = document.getElementById('analysisPanel');
    panel.style.opacity = '1';
    panel.style.pointerEvents = 'auto';
    
    // Calculate metrics
    const wpm = parseInt(document.getElementById('wpmDisp').innerText) || 0;
    
    let paceRating = "Too Slow";
    let paceCol = "#f87171";
    let pacePct = 30;
    if (wpm >= 100 && wpm <= 150) { paceRating = "Good Pace"; paceCol = "#34d399"; pacePct = 100; }
    else if (wpm > 150) { paceRating = "Too Fast"; paceCol = "#fbbf24"; pacePct = 80; }
    
    document.getElementById('resPaceRating').innerText = paceRating;
    document.getElementById('resPaceRating').style.color = paceCol;
    document.getElementById('paceBar').style.width = pacePct + '%';
    document.getElementById('paceBar').style.background = paceCol;
    
    // Mock Clarity & Confidence
    const clarity = Math.max(20, 100 - (fillerCount * 5));
    document.getElementById('resClarity').innerText = clarity + "%";
    
    let conf = "High";
    if (fillerCount > 5) conf = "Medium";
    if (fillerCount > 10) conf = "Low";
    document.getElementById('resConfidence').innerText = conf;
    
    // Keywords
    const kws = ['Leadership', 'Communication', 'Agile', 'Teamwork'];
    document.getElementById('resKeywords').innerHTML = kws.map(k => `<span class="badge" style="background:rgba(52,211,153,0.15);color:#34d399;font-weight:600;">${k}</span>`).join('');
    
    // Feedback
    document.getElementById('resStrengths').innerText = "Good articulation and solid use of action verbs.";
    document.getElementById('resWeak').innerText = fillerCount > 3 ? `Try to reduce the use of filler words (used ${fillerCount} times).` : "Elaborate more on specific examples with STAR method.";

    // Sample Compare
    document.getElementById('comparisonPanel').style.display = 'block';
    document.getElementById('compUser').innerHTML = document.getElementById('transcriptView').innerHTML || "<em>No speech detected.</em>";
    document.getElementById('compAI').innerHTML = "<em>(AI Improved Version)</em><br><br>Here is a more professional way to frame your answer:<br><br>'I led a cross-functional team to deliver the project on time, resulting in a 20% increase in efficiency. I communicated regularly with stakeholders to ensure alignment.'";
}

function saveSession() {
    alert("Session saved successfully to your History!");
    
    // Append to history table
    const cat = document.getElementById('categorySelect').value;
    const cl = document.getElementById('resClarity').innerText;
    const w = document.getElementById('wpmDisp').innerText;
    const f = document.getElementById('fillerDisp').innerText;
    const d = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="color:var(--tx2);font-size:0.9rem;">${d}</td>
        <td><span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;">${cat}</span></td>
        <td style="color:#34d399;font-weight:600;">${cl}</td>
        <td>${w}</td>
        <td style="color:#f87171;">${f}</td>
    `;
    document.getElementById('historyTable').prepend(tr);
}

function downloadReport() {
    alert("Downloading PDF Report... (Mock Action)");
}

// Chart.js & History Init
function loadHistory() {
    const hist = [
        { d: 'Jun 17', c: 'Job Interview', cl: '88%', w: 120, f: 3 },
        { d: 'Jun 18', c: 'Leadership', cl: '92%', w: 135, f: 1 }
    ];
    let html = '';
    hist.reverse().forEach(h => {
        html += `<tr>
            <td style="color:var(--tx2);font-size:0.9rem;">${h.d}</td>
            <td><span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;">${h.c}</span></td>
            <td style="color:#34d399;font-weight:600;">${h.cl}</td>
            <td>${h.w}</td>
            <td style="color:#f87171;">${h.f}</td>
        </tr>`;
    });
    document.getElementById('historyTable').innerHTML = html;
}

document.addEventListener("DOMContentLoaded", function() {
    loadHistory();
    randomizePrompt();
    
    if(typeof Chart !== 'undefined') {
        Chart.defaults.color = '#808090';
        Chart.defaults.font.family = "'Inter', sans-serif";
        
        const ctx = document.getElementById('voiceProgressChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Session 1', 'Session 2', 'Session 3', 'Session 4', 'Session 5'],
                datasets: [{
                    label: 'Clarity Score',
                    data: [70, 75, 82, 85, 92],
                    borderColor: '#34d399',
                    backgroundColor: 'rgba(52,211,153,0.1)',
                    borderWidth: 3, tension: 0.4, fill: true
                }, {
                    label: 'Fillers Used',
                    data: [15, 12, 8, 5, 2],
                    borderColor: '#f87171',
                    backgroundColor: 'transparent',
                    borderWidth: 2, tension: 0.4, fill: false
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.driver === 'undefined') return;
        const driver = window.driver.js.driver;

        const stepsMobile = [
            { element: '#categorySelect', popover: { title: 'Select Category', description: 'Choose a question category to generate a random prompt for your practice.', side: "bottom", align: 'start' }},
            { element: '#btnStart', popover: { title: 'Start Recording', description: 'Click here to start speaking. The system will track your WPM, filler words, and generate a transcript.', side: "bottom", align: 'center' }},
            { element: '#transcriptView', popover: { title: 'Live Transcript', description: 'Your speech will appear here in real-time. Filler words and keywords will be highlighted automatically.', side: "top", align: 'start' }},
            { element: '#analysisPanel', popover: { title: 'AI Assessment', description: 'Once you stop recording, AI will analyze your pacing, clarity, and provide instant actionable feedback.', side: "top", align: 'start' }},
            { element: '#moduleTabs', popover: { title: 'History & Analytics', description: 'Switch to this tab later to review your past sessions, charts, and long-term improvement.', side: "bottom", align: 'end' }}
        ];

        const stepsDesktop = [
            { element: '#categorySelect', popover: { title: 'Select Category', description: 'Choose a question category to generate a random prompt for your practice.', side: "bottom", align: 'start' }},
            { element: '#btnStart', popover: { title: 'Start Recording', description: 'Click here to start speaking. The system will track your WPM, filler words, and generate a transcript.', side: "bottom", align: 'center' }},
            { element: '#transcriptView', popover: { title: 'Live Transcript', description: 'Your speech will appear here in real-time. Filler words and keywords will be highlighted automatically.', side: "top", align: 'start' }},
            { element: '#analysisPanel', popover: { title: 'AI Assessment', description: 'Once you stop recording, AI will analyze your pacing, clarity, and provide instant actionable feedback.', side: "bottom", align: 'start' }},
            { element: '#moduleTabs', popover: { title: 'History & Analytics', description: 'Switch to this tab later to review your past sessions, charts, and long-term improvement.', side: "bottom", align: 'end' }}
        ];

        const driverObj = driver({
            showProgress: true,
            animate: true,
            popoverClass: document.documentElement.classList.contains('lm') ? 'driverjs-theme-light' : 'driverjs-theme-dark',
            steps: {{ $isMobile ? 'true' : 'false' }} ? stepsMobile : stepsDesktop,
            onDestroyStarted: () => {
                if (!driverObj.hasNextStep() || confirm("Are you sure you want to exit the tutorial?")) {
                    driverObj.destroy();
                    localStorage.setItem('onboarding_completed_drills_voice', 'true');
                }
            },
        });

        window.startOnboardingTour = function() {
            // Make sure we are on the Practice tab for the tour
            const practiceTab = document.querySelector('a[data-target="tab-practice"]');
            if(practiceTab) practiceTab.click();
            
            setTimeout(() => {
                driverObj.drive();
            }, 300);
        };

        if (!localStorage.getItem('onboarding_completed_drills_voice')) {
            setTimeout(() => {
                startOnboardingTour();
            }, 500);
        }
    });
</script>
@endpush
@endsection


