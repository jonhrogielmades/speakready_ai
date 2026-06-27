@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<!-- Add print styles specifically for this portfolio report -->
<style>
    @media print {
        body { background: #fff !important; }
        .sidebar, .navbar, .btn-no-print { display: none !important; }
        .db-section { padding: 0 !important; margin: 0 !important; }
        .print-card { 
            background: #fff !important; 
            border: 1px solid #ccc !important; 
            break-inside: avoid;
            box-shadow: none !important;
            margin-bottom: 20px !important;
        }
        .text-white { color: #000 !important; }
        canvas { max-width: 100% !important; height: auto !important; }
        h4, h5, h6 { color: #000 !important; }
    }
</style>

<div class="db-section active" id="portfolioReport">
    <!-- Feature 10: Interview Portfolio Report Header & Actions -->
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3 pb-3 border-bottom btn-no-print" style="border-color:var(--bd) !important;">
        <div>
            <h4 style="color:var(--tx);font-weight:800;margin-bottom:4px;">Interview Portfolio Report</h4>
            <p style="color:var(--tx3);margin:0;">A complete summary of your preparation journey and analytics.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <button class="btn btn-sm d-inline-flex align-items-center" style="background:var(--bg3); border:1px solid var(--bd); color:var(--tx2); border-radius:10px; font-weight:600;" onclick="startOnboardingTour()"><i class="fa-solid fa-play me-sm-1" style="color:#60a5fa"></i> <span class="d-none d-sm-inline">Replay Tutorial</span></button>
            <button class="btn btn-outline-primary" onclick="window.print()"><i class="fa-solid fa-print me-2"></i>Print Report</button>
            <button class="btn btn-primary" id="exportPdfBtn"><i class="fa-solid fa-file-pdf me-2"></i>Export as PDF</button>
            <button class="btn btn-success" id="exportExcelBtn"><i class="fa-solid fa-file-excel me-2"></i>Export as Excel</button>
        </div>
    </div>

    <!-- Print Header visible only when printing or mimicking paper -->
    <div class="d-flex align-items-center mb-4 gap-3">
        <div style="width:60px;height:60px;background:var(--pur);border-radius:50%;display:flex;justify-content:center;align-items:center;">
            <i class="fa-solid fa-user-graduate text-white fs-3"></i>
        </div>
        <div>
            <h3 style="color:var(--tx);margin:0;font-weight:bold;">{{ $user->name ?? 'Candidate' }}</h3>
            <p style="color:var(--tx3);margin:0;">SpeakReady AI Preparation Portfolio &bull; Generated on {{ now()->format('F j, Y') }}</p>
        </div>
    </div>

    <!-- Feature 6: Readiness Assessment Report -->
    @php 
        $currentReadiness = $latestSession ? ($latestSession->score->overall_readiness_score ?? 88) : 88;
        $prevReadiness = $previousSession ? ($previousSession->score->overall_readiness_score ?? 75) : 75;
        $improvement = $currentReadiness - $prevReadiness;
        
        if($currentReadiness >= 90) { $rRating = 'Excellent'; $rColor = '#10b981'; }
        elseif($currentReadiness >= 70) { $rRating = 'Good'; $rColor = '#3b82f6'; }
        elseif($currentReadiness >= 50) { $rRating = 'Fair'; $rColor = '#f59e0b'; }
        else { $rRating = 'Needs Improvement'; $rColor = '#ef4444'; }
    @endphp
    <div id="report-readiness" class="print-card mb-4" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(16, 185, 129, 0.1)); border:1px solid rgba(59, 130, 246, 0.2); border-radius:18px; padding:32px;">
        <div class="row align-items-center text-center text-md-start">
            <div class="col-md-3 border-end" style="border-color:rgba(59, 130, 246, 0.2) !important;">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:1px;margin-bottom:8px;">Readiness Score</h6>
                <div style="font-size:3.5rem;font-weight:900;line-height:1;color:{{ $rColor }};">{{ $currentReadiness }}<span style="font-size:1.5rem">%</span></div>
                <div class="badge mt-2 fs-6" style="background-color:{{ $rColor }};color:#fff;">{{ $rRating }}</div>
            </div>
            <div class="col-md-3 border-end mt-4 mt-md-0" style="border-color:rgba(59, 130, 246, 0.2) !important;">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:1px;margin-bottom:8px;">Previous Score</h6>
                <div style="font-size:2rem;font-weight:700;line-height:1;color:var(--tx);">{{ $prevReadiness }}%</div>
            </div>
            <div class="col-md-6 mt-4 mt-md-0 ps-md-4">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:1px;margin-bottom:8px;">Improvement Rate</h6>
                <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-start">
                    <i class="fa-solid fa-arrow-trend-up fs-1" style="color:#10b981;"></i>
                    <div style="font-size:2.5rem;font-weight:800;color:#10b981;">+{{ $improvement > 0 ? $improvement : 13 }}%</div>
                </div>
                <p style="color:var(--tx);margin-top:8px;font-size:0.95rem;">You have significantly improved your interview readiness compared to your previous assessment.</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 1: Interview Performance Report -->
        <div class="col-lg-7">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-clipboard-check text-primary me-2"></i>Latest Interview Performance</h5>
                
                <div class="row mb-4 bg-light bg-opacity-10 rounded p-3" style="background:var(--bg);">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Category</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestSession->category->title ?? 'Job Interview' }}</div>
                    </div>
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Date</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestSession ? $latestSession->created_at->format('M d, Y') : 'June 18, 2026' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Difficulty</small>
                        <div style="color:var(--tx);font-weight:bold;text-transform:capitalize;">{{ $latestSession->difficulty ?? 'Intermediate' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Questions</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestSession->num_questions ?? 5 }}</div>
                    </div>
                </div>

                <h6 style="color:var(--tx);font-weight:bold;margin-bottom:16px;">Performance Breakdown</h6>
                <div class="row g-3 text-center">
                    @php
                        $metrics = [
                            'Clarity' => $latestSession->score->clarity_score ?? 90,
                            'Relevance' => $latestSession->score->relevance_score ?? 85,
                            'Grammar' => $latestSession->score->grammar_score ?? 92,
                            'Professionalism' => $latestSession->score->professionalism_score ?? 88,
                            'Confidence' => $latestSession->score->confidence_score ?? 80,
                        ];
                    @endphp
                    @foreach($metrics as $label => $val)
                    <div class="col">
                        <div style="width:60px;height:60px;border-radius:50%;background:rgba(59,130,246,0.1);border:2px solid #3b82f6;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;color:var(--tx);font-weight:bold;font-size:1.1rem;">
                            {{ $val }}
                        </div>
                        <div style="font-size:0.8rem;color:var(--tx3);font-weight:600;">{{ $label }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Feature 8: Performance Comparison Report -->
        <div class="col-lg-5">
            <div id="report-comparison" class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-code-compare text-warning me-2"></i>Performance Comparison</h5>
                <p style="color:var(--tx3);font-size:0.9rem;">Comparing First Interview vs. Latest Interview</p>
                
                <table class="table table-borderless table-sm align-middle" style="color:var(--tx); background: transparent; --bs-table-bg: transparent; --bs-table-color: var(--tx);">
                    <thead style="border-bottom:1px solid var(--bd);">
                        <tr>
                            <th class="text-uppercase" style="font-size:0.8rem;color:var(--tx3);">Metric</th>
                            <th class="text-center text-uppercase" style="font-size:0.8rem;color:var(--tx3);">First</th>
                            <th class="text-center text-uppercase" style="font-size:0.8rem;color:var(--tx3);">Latest</th>
                            <th class="text-end text-uppercase" style="font-size:0.8rem;color:var(--tx3);">Diff</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold">Overall Score</td>
                            <td class="text-center">65%</td>
                            <td class="text-center text-primary fw-bold">{{ $currentReadiness }}%</td>
                            <td class="text-end text-success"><i class="fa-solid fa-arrow-up me-1"></i>23%</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Clarity</td>
                            <td class="text-center">70%</td>
                            <td class="text-center text-primary fw-bold">{{ $metrics['Clarity'] }}%</td>
                            <td class="text-end text-success"><i class="fa-solid fa-arrow-up me-1"></i>20%</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Confidence</td>
                            <td class="text-center">50%</td>
                            <td class="text-center text-primary fw-bold">{{ $metrics['Confidence'] }}%</td>
                            <td class="text-end text-success"><i class="fa-solid fa-arrow-up me-1"></i>30%</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Relevance</td>
                            <td class="text-center">80%</td>
                            <td class="text-center text-primary fw-bold">{{ $metrics['Relevance'] }}%</td>
                            <td class="text-end text-success"><i class="fa-solid fa-arrow-up me-1"></i>5%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Feature 2: Feedback Summary Report -->
    <div class="row mb-4">
        <div class="col-12">
            <div id="report-feedback" class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-comment-dots text-info me-2"></i>Feedback Summary Report</h5>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(16,185,129,0.05);border-radius:12px;border:1px solid rgba(16,185,129,0.2);height:100%;">
                            <h6 style="color:#10b981;font-weight:bold;"><i class="fa-solid fa-check-circle me-2"></i>Strengths</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                <li>Clear Communication</li>
                                <li>Strong Technical Knowledge</li>
                                <li>Professional Vocabulary</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(239,68,68,0.05);border-radius:12px;border:1px solid rgba(239,68,68,0.2);height:100%;">
                            <h6 style="color:#ef4444;font-weight:bold;"><i class="fa-solid fa-circle-xmark me-2"></i>Areas for Improvement</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                <li>Confidence in delivery</li>
                                <li>Leadership Examples</li>
                                <li>Using the STAR method fully</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(59,130,246,0.05);border-radius:12px;border:1px solid rgba(59,130,246,0.2);height:100%;">
                            <h6 style="color:#3b82f6;font-weight:bold;"><i class="fa-solid fa-lightbulb me-2"></i>AI Recommendations</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                <li>Practice Behavioral Questions</li>
                                <li>Improve STAR Responses</li>
                                <li>Try Voice Rehearsal Drills</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 3: Progress Report Charts -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-chart-line text-success me-2"></i>Readiness Score Trend</h5>
                <div style="height:250px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-chart-bar text-primary me-2"></i>Category Performance</h5>
                <div style="height:250px;">
                    <canvas id="catChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 7: Skill Analysis Report -->
        <div class="col-md-6">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-crosshairs text-danger me-2"></i>Skill Analysis Report</h5>
                @php
                    $skillSet = [
                        ['name'=>'Communication', 'score'=>88, 'rate'=>'+15%'],
                        ['name'=>'Confidence', 'score'=>80, 'rate'=>'+10%'],
                        ['name'=>'Leadership', 'score'=>75, 'rate'=>'+5%'],
                        ['name'=>'Teamwork', 'score'=>90, 'rate'=>'+12%'],
                        ['name'=>'Problem Solving', 'score'=>85, 'rate'=>'+8%'],
                        ['name'=>'Technical Knowledge', 'score'=>95, 'rate'=>'+20%'],
                    ];
                @endphp
                @foreach($skillSet as $sk)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.9rem;">
                        <span style="color:var(--tx);font-weight:600;">{{ $sk['name'] }}</span>
                        <span style="color:var(--tx3)">{{ $sk['score'] }}% <span class="text-success ms-2">({{ $sk['rate'] }})</span></span>
                    </div>
                    <div class="progress" style="height:8px;background:var(--bd);border-radius:4px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $sk['score'] }}%;border-radius:4px;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="col-md-6 d-flex flex-column gap-4">
            <!-- Feature 4: Voice Rehearsal Report -->
            <div class="print-card flex-grow-1" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:16px;"><i class="fa-solid fa-microphone-lines text-warning me-2"></i>Voice Rehearsal Report</h5>
                <div class="row text-center align-items-center h-100">
                    <div class="col-4 border-end" style="border-color:var(--bd)!important;">
                        <div style="font-size:1.8rem;font-weight:bold;color:var(--tx);">{{ $voiceData->wpm }}</div>
                        <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;font-weight:600;">Pace (WPM)</div>
                    </div>
                    <div class="col-4 border-end" style="border-color:var(--bd)!important;">
                        <div style="font-size:1.8rem;font-weight:bold;color:var(--tx);">{{ $voiceData->confidence }}%</div>
                        <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;font-weight:600;">Confidence</div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:1.8rem;font-weight:bold;color:#ef4444;">{{ $voiceData->filler_words }}</div>
                        <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;font-weight:600;">Filler Words</div>
                    </div>
                </div>
            </div>
            
            <!-- Feature 5: Learning Progress Report -->
            <div id="report-learning" class="print-card flex-grow-1" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:16px;"><i class="fa-solid fa-graduation-cap text-info me-2"></i>Learning Progress Report</h5>
                <div class="row align-items-center h-100">
                    <div class="col-md-6">
                        <div style="font-size:2.5rem;font-weight:bold;color:#0dcaf0;line-height:1;">{{ $learningData->completion_rate }}%</div>
                        <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:12px;">Overall Completion</div>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0" style="color:var(--tx);font-size:0.9rem;">
                            <li class="mb-2 d-flex justify-content-between"><span>Lessons:</span> <strong>{{ $learningData->lessons_completed }}/{{ $learningData->lessons_total }}</strong></li>
                            <li class="mb-2 d-flex justify-content-between"><span>Videos:</span> <strong>{{ $learningData->videos_watched }}</strong></li>
                            <li class="d-flex justify-content-between"><span>Quiz Avg:</span> <strong>{{ $learningData->quiz_average }}%</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 9: Achievement Report -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-award text-warning me-2"></i>Achievement Report</h5>
                <div class="d-flex flex-wrap gap-4 justify-content-center justify-content-md-start">
                    @foreach($achievements as $ach)
                    <div class="text-center" style="width:110px;">
                        <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.05);border:2px solid {{ $ach->color }};display:flex;justify-content:center;align-items:center;margin:0 auto 12px;box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                            <i class="fa-solid {{ $ach->icon }} fs-2" style="color:{{ $ach->color }};"></i>
                        </div>
                        <div style="font-size:0.8rem;color:var(--tx);font-weight:600;line-height:1.2;">{{ $ach->title }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Scripts for Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = ['Month 1', 'Month 2', 'Month 3', 'Current'];
        const scores = [65, 72, 80, {{ $currentReadiness }}];

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Score Trend',
                    data: scores,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(156, 163, 175, 0.1)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        new Chart(document.getElementById('catChart'), {
            type: 'bar',
            data: {
                labels: ['Job', 'Schol.', 'Tech'],
                datasets: [{
                    data: [88, 75, 92],
                    backgroundColor: ['#3b82f6', '#f59e0b', '#8b5cf6'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(156, 163, 175, 0.1)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Export PDF
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', function() {
                const element = document.getElementById('portfolioReport');
                const opt = {
                    margin:       [0.5, 0.5, 0.5, 0.5],
                    filename:     'portfolio_report.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true },
                    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
                };
                
                // Hide header actions during export
                const headerActions = element.querySelector('.btn-no-print');
                let originalDisplay = '';
                if (headerActions) {
                    originalDisplay = headerActions.style.display;
                    headerActions.style.display = 'none';
                }
                
                html2pdf().set(opt).from(element).save().then(() => {
                    if (headerActions) {
                        headerActions.style.display = originalDisplay;
                    }
                });
            });
        }

        // Export Excel
        const exportExcelBtn = document.getElementById('exportExcelBtn');
        if (exportExcelBtn) {
            exportExcelBtn.addEventListener('click', function() {
                const table = document.querySelector('#report-comparison table');
                if (table) {
                    const wb = XLSX.utils.table_to_book(table, {sheet: "Comparison"});
                    XLSX.writeFile(wb, 'performance_comparison.xlsx');
                } else {
                    alert("No data available to export.");
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
            { element: '#report-readiness', popover: { title: 'Overall Readiness', description: 'See your current readiness score and how much you have improved since your first interview.', side: "bottom", align: 'start' }},
            { element: '#report-comparison', popover: { title: 'Performance Comparison', description: 'Track specific metric improvements between your first and latest mock interviews.', side: "bottom", align: 'start' }},
            { element: '#report-feedback', popover: { title: 'Feedback Summary', description: 'Review your core strengths, areas for improvement, and AI recommendations.', side: "top", align: 'start' }},
            { element: '#report-learning', popover: { title: 'Learning Progress', description: 'Track your completion rate across the Learning Lab and Voice Rehearsal modules.', side: "top", align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#report-readiness', popover: { title: 'Overall Readiness', description: 'See your current readiness score and how much you have improved since your first interview.', side: "bottom", align: 'start' }},
            { element: '#report-comparison', popover: { title: 'Performance Comparison', description: 'Track specific metric improvements between your first and latest mock interviews.', side: "bottom", align: 'start' }},
            { element: '#report-feedback', popover: { title: 'Feedback Summary', description: 'Review your core strengths, areas for improvement, and AI recommendations.', side: "top", align: 'start' }},
            { element: '#report-learning', popover: { title: 'Learning Progress', description: 'Track your completion rate across the Learning Lab and Voice Rehearsal modules.', side: "top", align: 'end' }}
        ];

        const driverObj = driver({
            showProgress: true,
            animate: true,
            popoverClass: document.documentElement.classList.contains('lm') ? 'driverjs-theme-light' : 'driverjs-theme-dark',
            steps: {{ $isMobile ? 'true' : 'false' }} ? stepsMobile : stepsDesktop,
            onDestroyStarted: () => {
                if (!driverObj.hasNextStep() || confirm("Are you sure you want to exit the tutorial?")) {
                    driverObj.destroy();
                    localStorage.setItem('onboarding_completed_reports', 'true');
                }
            },
        });

        window.startOnboardingTour = function() {
            driverObj.drive();
        };

        if (!localStorage.getItem('onboarding_completed_reports')) {
            setTimeout(() => {
                startOnboardingTour();
            }, 500);
        }
    });
</script>
@endpush
@endsection

