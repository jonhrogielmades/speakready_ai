<div class="db-section active" id="sec-progress-tracking">
 <div class="progress-hero" id="progressModulesLikeHero">
 <div class="progress-hero-inner">
 <div class="progress-hero-copy">
 <div class="progress-hero-icon"><i class="fa-solid fa-chart-line"></i></div>
 <div>
 <h4 class="progress-hero-title text-gradient-primary">Interview Progress</h4>
 <p class="progress-hero-subtitle">Track readiness growth across your practice scenarios.</p>
 </div>
 </div>
 </div>
 <svg class="progress-hero-art progress-hero-motion-art" viewBox="0 0 220 150" aria-hidden="true" role="img">
 <defs>
 <linearGradient id="progressArtPanel" x1="36" y1="18" x2="176" y2="128" gradientUnits="userSpaceOnUse">
 <stop stop-color="#DBEAFE"/>
 <stop offset="1" stop-color="#ECFEFF"/>
 </linearGradient>
 <linearGradient id="progressArtBlue" x1="54" y1="34" x2="168" y2="112" gradientUnits="userSpaceOnUse">
 <stop stop-color="#3B82F6"/>
 <stop offset="1" stop-color="#06B6D4"/>
 </linearGradient>
 </defs>
 <rect class="progress-art-panel" x="31" y="21" width="158" height="108" rx="18" fill="url(#progressArtPanel)" stroke="#BFDBFE" stroke-width="3"/>
 <path class="progress-art-axis" d="M54 105V52" stroke="#93C5FD" stroke-width="5" stroke-linecap="round"/>
 <path class="progress-art-axis" d="M54 105h113" stroke="#93C5FD" stroke-width="5" stroke-linecap="round"/>
 <path class="progress-art-trend" d="M65 92l25-28 27 16 38-43" fill="none" stroke="url(#progressArtBlue)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
 <circle class="progress-art-runner" r="5" fill="#EFF6FF" stroke="#1D4ED8" stroke-width="2">
 <animateMotion dur="3.8s" repeatCount="indefinite" path="M65 92 L90 64 L117 80 L155 37"/>
 <animate attributeName="opacity" values=".35;1;.35" dur="3.8s" repeatCount="indefinite"/>
 </circle>
 <circle class="progress-art-node progress-art-node-1" cx="90" cy="64" r="9" fill="#2563EB" stroke="#EFF6FF" stroke-width="4"/>
 <circle class="progress-art-node progress-art-node-2" cx="117" cy="80" r="9" fill="#0EA5E9" stroke="#EFF6FF" stroke-width="4"/>
 <circle class="progress-art-node progress-art-node-3" cx="155" cy="37" r="11" fill="#22C55E" stroke="#EFF6FF" stroke-width="4"/>
 <rect class="progress-art-bar progress-art-bar-1" x="67" y="101" width="13" height="16" rx="5" fill="#60A5FA" opacity=".65"/>
 <rect class="progress-art-bar progress-art-bar-2" x="93" y="91" width="13" height="26" rx="5" fill="#38BDF8" opacity=".75"/>
 <rect class="progress-art-bar progress-art-bar-3" x="119" y="97" width="13" height="20" rx="5" fill="#818CF8" opacity=".65"/>
 <rect class="progress-art-bar progress-art-bar-4" x="145" y="75" width="13" height="42" rx="5" fill="#22C55E" opacity=".75"/>
 <path class="progress-art-wave" d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
 <path class="progress-art-sparks" d="M194 28l10-10m-6 30l14-2M24 59l-11-7m18 55l-14 3" stroke="#38BDF8" stroke-width="5" stroke-linecap="round" opacity=".55"/>
 </svg>
 </div>
 <div class="progress-summary-strip">
 <!-- Readiness-first summary -->
 <div id="progress-stats" class="row g-4">
 <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.1s;">
 <div class="premium-panel progress-stat-card readiness-primary" style="--stat-accent:{{ $readinessSummary?->color?? '#64748b' }}">
 <div class="progress-stat-icon"><i class="fa-solid fa-gauge-high"></i></div>
 <div class="progress-stat-value">{{ $readinessSummary?->current === null? 'N/A': $readinessSummary->current.'%' }}</div>
 <div class="progress-stat-label">Current Readiness</div>
 </div>
 </div>
 <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.2s;">
 <div class="premium-panel progress-stat-card" style="--stat-accent:#2563eb">
 <div class="progress-stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
 <div class="progress-stat-value">{{ $readinessMovement?->label?? 'N/A' }}</div>
 <div class="progress-stat-label">VS Last</div>
 </div>
 </div>
 <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.3s;">
 <div class="premium-panel progress-stat-card" style="--stat-accent:#f59e0b">
 <div class="progress-stat-icon"><i class="fa-solid fa-fire"></i></div>
 <div class="progress-stat-value">{{ $currentStreak }} {{ $currentStreak == 1? 'Day': 'Days' }}</div>
 <div class="progress-stat-label">Current Streak</div>
 </div>
 </div>
 <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.4s;">
 <div class="premium-panel progress-stat-card" style="--stat-accent:#16a34a">
 <div class="progress-stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
 <div class="progress-stat-value">{{ $totalPracticeDays }} {{ $totalPracticeDays == 1? 'Day': 'Days' }}</div>
 <div class="progress-stat-label">Total Practice</div>
 </div>
 </div>
 </div>

 </div>

 @include('shared.partials.progress-live-sections')

 <div class="row g-4 mb-4 progress-overview-grid">
 <!-- Feature 1: Readiness Score Trend -->
 <div class="col-12 animate-fade-up" id="readiness-trend" style="animation-delay: 0.6s;">
 <div class="premium-panel progress-chart-panel" style="height:100%; --panel-accent:#2563eb;">
 <div class="progress-panel-heading">
 <div class="progress-panel-icon"><i class="fa-solid fa-chart-line"></i></div>
 <div>
 <h5 class="progress-panel-title">Overall Readiness Trend</h5>
 <p class="progress-panel-subtitle">Track your overall interview readiness over time.</p>
 </div>
 </div>
 <div class="progress-chart-frame">
 @if($scoreTrend->isNotEmpty())
 <canvas id="readinessChart"></canvas>
 @else
 <div class="progress-chart-empty">
 <i class="fa-solid fa-chart-line"></i>
 <h6>No readiness trend yet</h6>
 <p>Complete a scored practice interview to start charting your growth.</p>
 </div>
 @endif
 </div>
 </div>
 </div>
 <!-- Feature 3: Scenario Performance Analysis -->
 <div class="col-12 animate-fade-up" id="category-perf" style="animation-delay: 0.7s;">
 <div class="premium-panel progress-chart-panel" style="height:100%; --panel-accent:#10b981;">
 <div class="progress-panel-heading">
 <div class="progress-panel-icon"><i class="fa-solid fa-crosshairs"></i></div>
 <div>
 <h5 class="progress-panel-title">Scenario Performance</h5>
 <p class="progress-panel-subtitle">Your average scores across job interview scenarios.</p>
 </div>
 </div>
 <div class="progress-chart-frame scenario">
 @if(count($categoryPerf) > 0)
 <canvas id="categoryChart"></canvas>
 @else
 <div class="progress-chart-empty">
 <i class="fa-solid fa-crosshairs"></i>
 <h6>No scenario performance yet</h6>
 <p>Your scored scenario averages appear here after completed practice sessions.</p>
 </div>
 @endif
 </div>
 </div>
 </div>
 </div>

 <div class="row g-4 mb-4">
 <!-- Feature 12: Strengths & Areas for Improvement -->
 <div class="col-12 animate-fade-up" id="strengths-tracker" style="animation-delay: 0.9s;">
 <div class="premium-panel strengths-star-panel" style="height:100%; --panel-accent:#7c3aed;">
 @php
 $strengths = $latestSkillSummary->strengths?: ['None identified yet'];
 $weaknesses = $latestSkillSummary->weaknesses?: ['None identified yet'];
 @endphp
 <div class="strengths-overview">
 <div class="strengths-icon"><i class="fa-solid fa-star"></i></div>
 <div>
 <h5 class="strengths-title">Strengths & Areas for Improvement</h5>
 @if($latestSkillSummary->has_data)
 <div class="strengths-lists">
 <div class="strengths-list-card">
 <h6 class="text-success"><i class="fa-solid fa-arrow-trend-up me-2"></i>Strengths</h6>
 <ul>
 @foreach(array_slice($strengths, 0, 3) as $str)
 <li><i class="fa-solid fa-check text-success me-2"></i>{{ $str }}</li>
 @endforeach
 </ul>
 </div>
 <div class="strengths-list-card">
 <h6 class="text-warning"><i class="fa-solid fa-arrow-trend-down me-2"></i>Needs Work</h6>
 <ul>
 @foreach(array_slice($weaknesses, 0, 3) as $wk)
 <li><i class="fa-solid fa-xmark text-warning me-2"></i>{{ $wk }}</li>
 @endforeach
 </ul>
 </div>
 </div>
 @else
 <p class="strengths-text">Complete an interview to see strengths and areas for improvement.</p>
 @endif
 </div>
 </div>

 <!-- Feature 7: STAR Method Progress -->
 <div class="star-overview">
 <div class="star-icon"><i class="fa-solid fa-bullseye"></i></div>
 <div>
 <h5 class="star-title">STAR Method Progress</h5>
 @if($starProgress->has_data)
 <p class="star-text">{{ $starProgress->message }}</p>
 <div class="star-progress-summary">
 <div class="star-progress-score" style="--star-overall: {{ $starProgress->overall_percent?? 0 }}%;">
 <span>{{ $starProgress->overall_percent }}%</span>
 <small>STAR coverage</small>
 </div>
 <div class="star-part-list">
 @foreach($starProgress->parts as $part)
 <div class="star-part-row">
 <div class="star-part-top">
 <span>{{ $part->label }}</span>
 <span>{{ $part->percent === null? 'N/A': $part->percent. '%' }}</span>
 </div>
 <div class="star-part-track">
 <div class="star-part-fill" style="--star-progress: {{ $part->percent?? 0 }}%;"></div>
 </div>
 </div>
 @endforeach
 </div>
 </div>
 @else
 <p class="star-text">Insufficient data to analyze your STAR Method usage. Keep practicing behavioral questions!</p>
 @endif
 </div>
 </div>
 <div class="star-note">
 <div class="star-note-icon"><i class="fa-regular fa-lightbulb"></i></div>
 <div>
 <h6 class="star-note-title">{{ $starProgress->has_data? 'Next STAR Step': 'What is STAR Method?' }}</h6>
 <p class="star-note-text">{{ $starProgress->has_data? $starProgress->suggestion: 'STAR stands for Situation, Task, Action, Result. It helps you structure strong and impactful answers.' }}</p>
 </div>
 </div>
 </div>
 </div>
 <!-- Feature 2: Interview Performance History -->
 <div class="col-12 animate-fade-up" id="history-table" style="animation-delay: 1s;">
 <div class="premium-panel history-panel" style="--panel-accent:#4f46e5;">
 <div class="history-top">
 <div class="history-heading">
 <div class="history-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
 <h5 class="history-title">Recent Interview History</h5>
 </div>
 </div>
 <label class="history-search" for="historySearch">
 <i class="fa-solid fa-magnifying-glass"></i>
 <input type="text" id="historySearch" placeholder="Search history...">
 </label>
 <div class="history-list">
 @foreach($historySessions as $session)
 @php $sc = $session->score? $session->score->overall_readiness_score: null; @endphp
 <article class="history-card" data-history-record>
 <div class="history-date"><i class="fa-regular fa-calendar-days"></i>{{ $session->created_at->format('M d, Y') }}</div>
 <h6 class="history-scenario">{{ $session->practice_scenario?? 'General Job Interview' }}</h6>
 <div class="history-meta">
 <div>Score:
 @if($session->score)
 <span class="history-score-value">{{ $session->score->overall_readiness_score }}%</span>
 @else
 <span class="history-rating-badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Score pending</span>
 @endif
 </div>
 <div>Rating:
 @if($sc === null) <span class="history-rating-badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Not scored</span>
 @elseif($sc >= 90) <span class="history-rating-badge" style="background: rgba(16, 185, 129, 0.16); color: #059669;">Excellent</span>
 @elseif($sc >= 70) <span class="history-rating-badge" style="background: rgba(59, 130, 246, 0.16); color: #2563eb;">Good</span>
 @elseif($sc >= 50) <span class="history-rating-badge" style="background: rgba(245, 158, 11, 0.18); color: #d97706;">Average</span>
 @else <span class="history-rating-badge" style="background: rgba(239, 68, 68, 0.16); color: #ef334e;">Needs Work</span>
 @endif
 </div>
 </div>
 <div class="history-card-actions">
 <a href="{{ route('user.review', $session->id) }}" class="btn btn-outline-primary history-feedback-btn"><i class="fa-regular fa-message"></i> View Feedback</a>
 </div>
 </article>
 @endforeach
 @if($historySessions->count() == 0)
 <div class="skill-empty-state">
 <p class="skill-empty-text">No interview records found. Start a practice interview to track your progress.</p>
 </div>
 @endif
 </div>
 @if($sessions->count() > $historySessions->count())
 <div class="history-footer-action">
 <a href="{{ route('user.feedback') }}" class="btn btn-outline-primary history-feedback-btn"><i class="fa-solid fa-clock-rotate-left"></i> View Full History</a>
 </div>
 @endif
 <div class="skill-empty-state history-no-results" id="historyNoResults" hidden>
 <div>
 <div class="skill-empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
 <p class="skill-empty-text">No history records match your search.</p>
 </div>
 </div>
 </div>
 </div>
 </div>

 <div class="row g-4 progress-goals-badges-grid">
 <!-- Feature 10: Goals & Milestones -->
 <div class="col-12" id="goals-milestones">
 <div class="goals-panel" style="--panel-accent:#10b981;">
 <div class="goals-heading">
 <div class="goals-heading-icon"><i class="fa-solid fa-bullseye"></i></div>
 <div>
 <h5 class="goals-title">Goals & Milestones</h5>
 <p class="goals-subtitle">Track your progress and reach your interview goals.</p>
 </div>
 </div>
 @forelse($goals as $goal)
 <div class="goal-row">
 <div class="goal-top">
 <span class="goal-title">{{ $goal->title }}</span>
 <span class="goal-percent">{{ $goal->progress }}%</span>
 </div>
 <div class="goal-track">
 <div class="goal-fill" style="--goal-progress: {{ max(0, min(100, (int) $goal->progress)) }}%;"></div>
 </div>
 </div>
 @empty
 <div class="goal-row">
 <div class="goal-top">
 <span class="goal-title">Complete your first scored interview</span>
 <span class="goal-percent">0%</span>
 </div>
 <div class="goal-track">
 <div class="goal-fill" style="--goal-progress: 0%;"></div>
 </div>
 </div>
 @endforelse
 <div class="goal-note">
 <div class="goal-note-icon"><i class="fa-solid {{ $goalNote->icon }}"></i></div>
 <div>
 <div class="goal-note-title">{{ $goalNote->title }}</div>
 <div class="goal-note-text">{{ $goalNote->text }}</div>
 </div>
 </div>
 </div>
 </div>

 <!-- Feature 11: Achievements & Badges -->
 <div class="col-12" id="achievements-badges">
 <div class="badges-panel" style="--panel-accent:#f59e0b;">
 <div class="badges-heading">
 <div class="badges-heading-icon"><i class="fa-solid fa-trophy"></i></div>
 <div>
 <h5 class="badges-title">Achievements & Badges</h5>
 <p class="badges-subtitle">Celebrate your progress and stay motivated.</p>
 </div>
 </div>
 <div class="badge-grid">
 @forelse($badges as $badge)
 <div class="badge-item {{ $badge->unlocked? '': 'locked' }}">
 <div class="badge-medal">
 <i class="fa-solid {{ $badge->icon }}"></i>
 </div>
 <div class="badge-title">{{ $badge->title }}</div>
 <div class="badge-desc">{{ $badge->description?? ($badge->unlocked? 'Unlocked': 'Keep practicing') }}</div>
 </div>
 @empty
 <div class="badge-item">
 <div class="badge-medal"><i class="fa-solid fa-medal"></i></div>
 <div class="badge-title">First Interview</div>
 <div class="badge-desc">Complete 1 interview</div>
 </div>
 <div class="badge-item">
 <div class="badge-medal"><i class="fa-solid fa-fire"></i></div>
 <div class="badge-title">3-Day Streak</div>
 <div class="badge-desc">Practice 3 days in a row</div>
 </div>
 <div class="badge-item">
 <div class="badge-medal"><i class="fa-solid fa-star"></i></div>
 <div class="badge-title">STAR Master</div>
 <div class="badge-desc">Use STAR effectively</div>
 </div>
 <div class="badge-item locked">
 <div class="badge-medal"><i class="fa-solid fa-list-check"></i></div>
 <div class="badge-title">5 Interviews</div>
 <div class="badge-desc">Complete 5 interviews</div>
 </div>
 @endforelse
 </div>
 </div>
 </div>
 </div>

 <!-- Scripts -->
 <script src="{{ asset('js/chart.umd.min.js') }}"></script>
 <script>
 document.addEventListener('DOMContentLoaded', function() {
 // Enable tooltips
 var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
 // If bootstrap is available
 if(typeof bootstrap!== 'undefined') {
 var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
 return new bootstrap.Tooltip(tooltipTriggerEl)
 });
 }

 const trendData = @json($scoreTrend);
 const scenarioPerformance = @json($categoryPerf);
 const progressCharts = [];
 const previousChartColorUpdater = window.updateChartColors;
 const progressThemeColors = () => {
 const isLight = document.documentElement.dataset.theme === 'light' || document.documentElement.classList.contains('lm');
 return {
 tick: isLight? '#1e2f50': '#cbd5e1',
 grid: isLight? 'rgba(148, 163, 184, 0.24)': 'rgba(148, 163, 184, 0.18)',
 border: isLight? 'rgba(148, 163, 184, 0.35)': 'rgba(148, 163, 184, 0.22)'
 };
 };
 const applyProgressChartTheme = (chart) => {
 if (!chart) return;
 const colors = progressThemeColors();
 if (chart.options.scales?.y) {
 chart.options.scales.y.ticks.color = colors.tick;
 chart.options.scales.y.grid.color = colors.grid;
 }
 if (chart.options.scales?.x) {
 chart.options.scales.x.ticks.color = colors.tick;
 chart.options.scales.x.border.color = colors.border;
 }
 chart.update('none');
 };
 const showProgressChartFallback = (canvas, message) => {
 if (!canvas) return;

 const fallback = document.createElement('div');
 fallback.className = 'progress-chart-empty progress-chart-runtime-empty';
 fallback.innerHTML = `
 <div>
 <i class="fa-solid fa-chart-line"></i>
 <h6>Chart unavailable</h6>
 <p>${message}</p>
 </div>
 `;
 canvas.replaceWith(fallback);
 };
 
 // Feature 1: Readiness Trend
 const labels = trendData.map(s => s.date);
 const scores = trendData.map(s => s.score);
 
 const readinessCanvas = document.getElementById('readinessChart');
 if (readinessCanvas) {
 if (window.Chart && document.getElementById('readinessChart')) {
 try {
 const readinessGradient = readinessCanvas.getContext('2d').createLinearGradient(0, 0, 0, 340);
 readinessGradient.addColorStop(0, 'rgba(37, 99, 235, 0.18)');
 readinessGradient.addColorStop(1, 'rgba(37, 99, 235, 0.02)');

 const readinessChart = new Chart(readinessCanvas, {
 type: 'line',
 data: {
 labels: labels,
 datasets: [{
 label: 'Readiness Score',
 data: scores,
 borderColor: '#2563eb',
 backgroundColor: readinessGradient,
 borderWidth: 3,
 tension: 0.34,
 fill: true,
 pointBackgroundColor: '#2563eb',
 pointBorderColor: '#ffffff',
 pointBorderWidth: 2,
 pointRadius: 5,
 pointHoverRadius: 7
 }]
 },
 options: {
 responsive: true,
 maintainAspectRatio: false,
 plugins: { legend: { display: false } },
 elements: { line: { capBezierPoints: true } },
 scales: {
 y: {
 beginAtZero: true,
 max: 100,
 ticks: { color: progressThemeColors().tick, stepSize: 10, padding: 12 },
 border: { display: false },
 grid: { color: progressThemeColors().grid, borderDash: [4, 5], drawTicks: false }
 },
 x: {
 ticks: { color: progressThemeColors().tick, maxRotation: 0, autoSkipPadding: 16 },
 border: { color: progressThemeColors().border },
 grid: { display: false }
 }
 }
 }
 });
 progressCharts.push(readinessChart);
 } catch (error) {
 console.error(error);
 showProgressChartFallback(readinessCanvas, 'Readiness trend data is available, but the chart could not be rendered.');
 }
 } else {
 showProgressChartFallback(readinessCanvas, 'Readiness trend data is available, but the chart library did not load.');
 }
 }

 // Feature 3: Scenario Performance
 const categoryCanvas = document.getElementById('categoryChart');
 if (categoryCanvas) {
 if (window.Chart && document.getElementById('categoryChart')) {
 try {
 const scenarioLabels = Object.keys(scenarioPerformance);
 const scenarioData = Object.values(scenarioPerformance);

 const categoryChart = new Chart(categoryCanvas, {
 type: 'bar',
 data: {
 labels: scenarioLabels,
 datasets: [{
 label: 'Avg Score',
 data: scenarioData,
 backgroundColor: [
 '#3b82f6',
 '#10b981',
 '#8b5cf6',
 '#fb923c'
 ],
 borderRadius: 4,
 maxBarThickness: 96
 }]
 },
 options: {
 responsive: true,
 maintainAspectRatio: false,
 plugins: { legend: { display: false } },
 scales: {
 y: {
 beginAtZero: true,
 max: 100,
 ticks: { color: progressThemeColors().tick, stepSize: 10, padding: 12 },
 border: { display: false },
 grid: { color: progressThemeColors().grid, borderDash: [4, 5], drawTicks: false }
 },
 x: {
 ticks: { color: progressThemeColors().tick, maxRotation: 0, font: { weight: 500 } },
 border: { color: progressThemeColors().border },
 grid: { display: false }
 }
 }
 }
 });
 progressCharts.push(categoryChart);
 } catch (error) {
 console.error(error);
 showProgressChartFallback(categoryCanvas, 'Scenario scores are available, but the chart could not be rendered.');
 }
 } else {
 showProgressChartFallback(categoryCanvas, 'Scenario scores are available, but the chart library did not load.');
 }
 }
 window.updateChartColors = function() {
 if (typeof previousChartColorUpdater === 'function') {
 previousChartColorUpdater();
 }
 progressCharts.forEach(applyProgressChartTheme);
 };

 // Feature 2: History Search Filter
 const searchInput = document.getElementById('historySearch');
 if(searchInput) {
 searchInput.addEventListener('input', function() {
 const filter = searchInput.value.toLowerCase();
 const cards = document.querySelectorAll('#history-table [data-history-record]');
 const noResults = document.getElementById('historyNoResults');
 let visibleCards = 0;

 cards.forEach(card => {
 const text = card.textContent.toLowerCase();
 const isVisible = text.includes(filter);
 card.style.display = isVisible? '': 'none';
 if (isVisible) {
 visibleCards++;
 }
 });
 if (noResults) {
 noResults.hidden = filter.length === 0 || cards.length === 0 || visibleCards > 0;
 }
 });
 }
 });
 </script>
</div>

@push('scripts')
<script>
 document.addEventListener("DOMContentLoaded", function() {
 if (typeof window.createSpeakReadyTour!== 'function') return;

 const stepsMobile = [
 { element: '#progressModulesLikeHero', popover: { title: 'Interview Progress', description: 'This page brings your practice scores, learning progress, history, goals, and achievements into one review hub.', side: 'bottom', align: 'start' }},
 { element: '#progress-stats', popover: { title: 'Readiness Snapshot', description: 'Review current readiness, movement from the last scored interview, your streak, and total practice days.', side: 'bottom', align: 'start' }},
 { element: '#skill-tracker', popover: { title: 'Skill Improvement', description: 'Watch core interview skills move from earlier scores to your latest session results.', side: 'top', align: 'start' }},
 { element: '#category-performance-summary', popover: { title: 'Category Summary', description: 'Check which scoring categories are currently strongest before drilling into the full chart.', side: 'top', align: 'start' }},
 { element: '#readiness-trend', popover: { title: 'Readiness Trend', description: 'Track how your overall readiness score changes over time as you complete more scored sessions.', side: 'bottom', align: 'start' }},
 { element: '#category-perf', popover: { title: 'Scenario Breakdown', description: 'Compare practice scenarios to find strengths, weak spots, and where your next session should focus.', side: 'top', align: 'start' }},
 { element: '#strengths-tracker', popover: { title: 'Strengths & STAR', description: 'Review strengths, areas to improve, STAR method coverage, and the next coaching suggestion.', side: 'top', align: 'start' }},
 { element: '#historySearch', popover: { title: 'Search History', description: 'Filter your interview history when you want to revisit a scenario, date, rating, or score quickly.', side: 'top', align: 'start' }},
 { element: '#history-table', popover: { title: 'Session History', description: 'Open previous interviews and detailed AI feedback from one place.', side: 'top', align: 'start' }},
 { element: '#goals-milestones', popover: { title: 'Goals & Milestones', description: 'Track practice goals and see the next milestone that will move your preparation forward.', side: 'top', align: 'start' }},
 { element: '#achievements-badges', popover: { title: 'Achievements', description: 'Badges and awards appear here as your practice history grows.', side: 'top', align: 'start' }}
 ];

 const stepsDesktop = [
 { element: '#progressModulesLikeHero', popover: { title: 'Interview Progress', description: 'This page brings your practice scores, learning progress, history, goals, and achievements into one review hub.', side: 'bottom', align: 'start' }},
 { element: '#progress-stats', popover: { title: 'Readiness Snapshot', description: 'Review current readiness, movement from the last scored interview, your streak, and total practice days.', side: 'bottom', align: 'start' }},
 { element: '#skill-tracker', popover: { title: 'Skill Improvement', description: 'Watch core interview skills move from earlier scores to your latest session results.', side: 'top', align: 'start' }},
 { element: '#category-performance-summary', popover: { title: 'Category Summary', description: 'Check which scoring categories are currently strongest before drilling into the full chart.', side: 'top', align: 'start' }},
 { element: '#readiness-trend', popover: { title: 'Readiness Trend', description: 'Track how your overall readiness score changes over time as you complete more scored sessions.', side: 'bottom', align: 'start' }},
 { element: '#category-perf', popover: { title: 'Scenario Breakdown', description: 'Compare practice scenarios to find strengths, weak spots, and where your next session should focus.', side: 'bottom', align: 'start' }},
 { element: '#strengths-tracker', popover: { title: 'Strengths & STAR', description: 'Review strengths, areas to improve, STAR method coverage, and the next coaching suggestion.', side: 'left', align: 'start' }},
 { element: '#historySearch', popover: { title: 'Search History', description: 'Filter your interview history when you want to revisit a scenario, date, rating, or score quickly.', side: 'top', align: 'start' }},
 { element: '#history-table', popover: { title: 'Session History', description: 'Open previous interviews and detailed AI feedback from one place.', side: 'top', align: 'start' }},
 { element: '#goals-milestones', popover: { title: 'Goals & Milestones', description: 'Track practice goals and see the next milestone that will move your preparation forward.', side: 'right', align: 'start' }},
 { element: '#achievements-badges', popover: { title: 'Achievements', description: 'Badges and awards appear here as your practice history grows.', side: 'left', align: 'start' }}
 ];

 window.createSpeakReadyTour({
 completionKey: 'onboarding_completed_progress',
 serverDetectedMobile: {{ ($serverDetectedMobile?? false)? 'true': 'false' }},
 stepsMobile,
 stepsDesktop,
 autoStartDelay: 500,
 });
 });
</script>
@endpush
