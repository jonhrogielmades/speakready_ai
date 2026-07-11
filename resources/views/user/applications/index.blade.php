@extends(isset($isMobile) && $isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Job Application Tracker')

@section('content')
<style>
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    .tracker-grid { display:grid; grid-template-columns: minmax(0, 390px) minmax(0, 1fr); gap:20px; align-items:start; }
    .tracker-panel { background:var(--sf); border:1px solid var(--bd); border-radius:18px; padding:20px; box-shadow:var(--shadow-soft, 0 10px 28px rgba(0,0,0,.12)); }
    .tracker-card { background:var(--sf); border:1px solid var(--bd); border-radius:18px; padding:18px; margin-bottom:16px; }
    .tracker-field { width:100%; padding:11px 13px; border:1px solid var(--bd); border-radius:12px; background:var(--bg3); color:var(--tx); font-size:.9rem; }
    .tracker-label { display:block; color:var(--tx); font-weight:700; font-size:.82rem; margin-bottom:7px; }
    .match-ring { width:70px; height:70px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:conic-gradient(#10b981 var(--score), var(--bg3) 0); }
    .match-ring span { width:54px; height:54px; border-radius:50%; background:var(--sf); display:flex; align-items:center; justify-content:center; color:var(--tx); font-weight:900; font-size:.95rem; }
    .keyword-chip { display:inline-flex; border-radius:999px; padding:5px 9px; font-size:.75rem; font-weight:700; margin:0 5px 6px 0; }
    .keyword-chip.good { background:rgba(16,185,129,.12); color:#10b981; }
    .keyword-chip.miss { background:rgba(245,158,11,.12); color:#f59e0b; }
    .plan-item { display:grid; grid-template-columns:auto minmax(0,1fr) auto; gap:10px; align-items:start; padding:12px; border:1px solid var(--bd); border-radius:12px; background:var(--bg3); margin-bottom:9px; }
    .plan-item.done { opacity:.62; }
    .plan-item.done .plan-title, .plan-item.done .plan-task { text-decoration:line-through; }
    .plan-item.is-saving { opacity:.72; pointer-events:none; }
    .plan-title { color:var(--tx); font-weight:800; font-size:.88rem; }
    .plan-task { color:var(--tx2); font-size:.82rem; line-height:1.45; margin-top:2px; }
    .status-pill { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:6px 10px; background:rgba(59,130,246,.12); color:#60a5fa; font-weight:800; font-size:.75rem; }
    .tracker-stats { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; margin-bottom:20px; }
    .tracker-stat { background:var(--sf); border:1px solid var(--bd); border-radius:16px; padding:15px; min-width:0; }
    .tracker-stat-label { color:var(--tx3); font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.02em; }
    .tracker-stat-value { color:var(--tx); font-size:1.45rem; line-height:1.1; font-weight:900; margin-top:7px; }
    .tracker-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    @media (max-width: 991px) {
        .tracker-grid,
        .tracker-stats { grid-template-columns:1fr; }
        .tracker-panel,
        .tracker-card { border-radius:14px; padding:15px; }
    }
    @media (max-width: 767px) {
        #job-tracker-page .sr-page-actions {
            margin-bottom: 12px !important;
        }
        #job-tracker-page .tracker-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }
        #job-tracker-page .tracker-stat {
            min-height: 86px;
            padding: 11px;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        #job-tracker-page .tracker-stat-label {
            font-size: 0.62rem;
            line-height: 1.2;
            letter-spacing: 0;
        }
        #job-tracker-page .tracker-stat-value {
            font-size: 1.18rem;
            margin-top: 8px;
        }
        #job-tracker-page .tracker-grid {
            gap: 12px;
        }
        #job-tracker-page .tracker-panel,
        #job-tracker-page .tracker-card {
            padding: 14px;
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        #job-tracker-page .tracker-panel h5,
        #job-tracker-page .tracker-card h5 {
            font-size: 0.98rem;
            line-height: 1.25;
        }
        #job-tracker-page .tracker-label {
            font-size: 0.74rem;
            margin-bottom: 6px;
        }
        #job-tracker-page .tracker-field {
            min-height: 44px;
            padding: 10px 12px;
            border-radius: 11px;
            font-size: 0.84rem;
        }
        #job-tracker-page textarea.tracker-field {
            min-height: 94px;
            line-height: 1.45;
        }
        #job-tracker-page #job-tracker-form .row.g-3 {
            --bs-gutter-x: 8px;
            --bs-gutter-y: 10px;
        }
        #job-tracker-page #job-tracker-form .btn,
        #job-tracker-page details .btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        #job-tracker-page .tracker-application-card > .d-flex:first-child {
            gap: 12px !important;
        }
        #job-tracker-page .tracker-application-card .status-pill {
            padding: 5px 8px;
            font-size: 0.66rem;
            line-height: 1.15;
        }
        #job-tracker-page .tracker-actions {
            display: grid;
            grid-template-columns: 68px minmax(0, 1fr);
            width: 100%;
            gap: 10px;
            align-items: center;
        }
        #job-tracker-page .match-ring {
            width: 62px;
            height: 62px;
        }
        #job-tracker-page .match-ring span {
            width: 48px;
            height: 48px;
            font-size: 0.82rem;
        }
        #job-tracker-page .tracker-actions .d-flex {
            width: 100%;
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) 44px;
            gap: 8px !important;
        }
        #job-tracker-page .tracker-actions .btn {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        #job-tracker-page .tracker-actions form,
        #job-tracker-page .tracker-actions form .btn {
            width: 100%;
        }
        #job-tracker-page .keyword-chip {
            padding: 5px 8px;
            font-size: 0.68rem;
            line-height: 1.15;
            margin: 0 4px 6px 0;
        }
        #job-tracker-page .plan-item {
            grid-template-columns: auto minmax(0, 1fr);
            gap: 9px;
            padding: 11px;
            border-radius: 12px;
        }
        #job-tracker-page .plan-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        #job-tracker-page .plan-title {
            font-size: 0.8rem;
            line-height: 1.35;
        }
        #job-tracker-page .plan-task {
            font-size: 0.76rem;
            line-height: 1.4;
        }
        #job-tracker-page .plan-item > span:last-child {
            grid-column: 2;
            font-size: 0.7rem !important;
            white-space: normal !important;
        }
        #job-tracker-page details summary {
            min-height: 40px;
            display: flex;
            align-items: center;
        }
        #job-tracker-page details .row.g-3 {
            --bs-gutter-x: 8px;
            --bs-gutter-y: 8px;
        }
    }
    @media (max-width: 380px) {
        #job-tracker-page .tracker-stats {
            grid-template-columns: 1fr;
        }
    }
</style>
@include('partials.page-hero-styles')

<div class="db-section active" id="job-tracker-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4 7h16v12H4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M4 12h16M10 12v2h4v-2" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                    Job Tracker
                </h4>
                <p class="sr-page-hero-subtitle">Track target roles, measure resume fit, and follow a smart 7-day practice plan.</p>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="jobPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="jobBlue" x1="58" y1="38" x2="168" y2="118"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="32" y="24" width="156" height="104" rx="18" fill="url(#jobPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <rect x="58" y="52" width="104" height="58" rx="12" fill="url(#jobBlue)"/><path d="M90 52v-6a10 10 0 0 1 20 0v6" fill="none" stroke="#2563EB" stroke-width="6" stroke-linecap="round"/><path d="M58 75h104" stroke="#EFF6FF" stroke-width="5" opacity=".8"/><rect x="99" y="72" width="22" height="13" rx="4" fill="#EFF6FF"/>
            <circle cx="164" cy="43" r="17" fill="#22C55E"/><path d="m157 43 5 5 10-12" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    @php
        $totalApplications = $applications->count();
        $activeApplications = $applications->whereNotIn('status', ['rejected', 'archived'])->count();
        $upcomingInterviews = $applications->filter(fn ($application) => $application->interview_date && $application->interview_date->isFuture())->count();
        $averageMatch = $totalApplications ? round($applications->avg('match_score')) : 0;
        $totalPlanItems = $applications->flatMap->planItems->count();
        $completedPlanItems = $applications->flatMap->planItems->whereNotNull('completed_at')->count();
        $overallPlanProgress = $totalPlanItems ? round(($completedPlanItems / $totalPlanItems) * 100) : 0;
    @endphp

    <div class="tracker-stats" id="job-tracker-summary">
        <div class="tracker-stat">
            <div class="tracker-stat-label">Tracked Jobs</div>
            <div class="tracker-stat-value">{{ $totalApplications }}</div>
        </div>
        <div class="tracker-stat">
            <div class="tracker-stat-label">Active Pipeline</div>
            <div class="tracker-stat-value">{{ $activeApplications }}</div>
        </div>
        <div class="tracker-stat">
            <div class="tracker-stat-label">Upcoming</div>
            <div class="tracker-stat-value">{{ $upcomingInterviews }}</div>
        </div>
        <div class="tracker-stat">
            <div class="tracker-stat-label">Avg Match</div>
            <div class="tracker-stat-value">{{ $averageMatch }}%</div>
        </div>
        <div class="tracker-stat">
            <div class="tracker-stat-label">Plan Progress</div>
            <div class="tracker-stat-value">{{ $overallPlanProgress }}%</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="border-radius:12px;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" style="border-radius:12px;">{{ $errors->first() }}</div>
    @endif

    <div class="tracker-grid">
        <aside class="tracker-panel" id="job-tracker-form">
            <h5 style="color:var(--tx);font-weight:800;margin-bottom:14px;">Add Target Job</h5>
            <form action="{{ route('user.applications.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="tracker-label">Company</label>
                    <input class="tracker-field" name="company_name" placeholder="e.g. Amazon" required>
                </div>
                <div class="mb-3">
                    <label class="tracker-label">Job Title</label>
                    <input class="tracker-field" name="job_title" placeholder="e.g. Software Developer" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="tracker-label">Stage</label>
                        <input class="tracker-field" name="interview_stage" placeholder="Screening">
                    </div>
                    <div class="col-6">
                        <label class="tracker-label">Interview Date</label>
                        <input class="tracker-field" type="date" name="interview_date">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="tracker-label">Source URL</label>
                    <input class="tracker-field" type="url" name="source_url" placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label class="tracker-label">Resume Text</label>
                    <textarea class="tracker-field" name="resume_text" rows="4" placeholder="Paste the resume version you will use."></textarea>
                </div>
                <div class="mb-3">
                    <label class="tracker-label">Job Description</label>
                    <textarea class="tracker-field" name="job_description" rows="5" placeholder="Paste the full job description for match scoring."></textarea>
                </div>
                <div class="mb-3">
                    <label class="tracker-label">Notes</label>
                    <textarea class="tracker-field" name="notes" rows="3" placeholder="Recruiter name, schedule notes, or reminders."></textarea>
                </div>
                <button class="btn btn-primary w-100" style="border-radius:12px;font-weight:800;">
                    <i class="fa-solid fa-plus me-1"></i>Add & Generate Plan
                </button>
            </form>
        </aside>

        <main id="job-tracker-list">
            @forelse($applications as $application)
                @php
                    $completed = $application->planItems->whereNotNull('completed_at')->count();
                    $total = max(1, $application->planItems->count());
                    $progress = round(($completed / $total) * 100);
                    $latestSession = $application->sessions->sortByDesc('created_at')->first();
                @endphp
                <section class="tracker-card tracker-application-card">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <h5 style="color:var(--tx);font-weight:900;margin:0;">{{ $application->company_name }}</h5>
                                <span class="status-pill">{{ ucfirst($application->status) }}</span>
                                @if($application->interview_stage)
                                    <span class="status-pill" style="background:rgba(245,158,11,.12);color:#f59e0b;">{{ $application->interview_stage }}</span>
                                @endif
                            </div>
                            <div style="color:var(--tx2);font-weight:700;">{{ $application->job_title }}</div>
                            <div style="color:var(--tx3);font-size:.84rem;margin-top:4px;">
                                @if($application->interview_date)
                                    <i class="fa-regular fa-calendar me-1"></i>{{ $application->interview_date->format('M d, Y') }}
                                @else
                                    <i class="fa-regular fa-calendar me-1"></i>No interview date yet
                                @endif
                                @if($latestSession?->score)
                                    <span class="ms-2"><i class="fa-solid fa-chart-line me-1"></i>Last mock {{ $latestSession->score->overall_readiness_score }}%</span>
                                @endif
                            </div>
                        </div>
                        <div class="tracker-actions">
                            <div class="match-ring" style="--score: {{ $application->match_score }}%;">
                                <span>{{ $application->match_score }}%</span>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a href="{{ route('user.applications.practice', $application) }}" class="btn btn-primary btn-sm" style="border-radius:10px;font-weight:800;">
                                    <i class="fa-solid fa-play me-1"></i>Practice
                                </a>
                                <form action="{{ route('user.applications.destroy', $application) }}" method="POST" onsubmit="return confirm('Remove this tracked application?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm w-100" style="border-radius:10px;font-weight:800;">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div style="color:var(--tx);font-weight:800;font-size:.86rem;margin-bottom:7px;">Matched Keywords</div>
                            @forelse(array_slice($application->matched_keywords ?? [], 0, 8) as $keyword)
                                <span class="keyword-chip good">{{ $keyword }}</span>
                            @empty
                                <span style="color:var(--tx3);font-size:.84rem;">No matches yet. Add resume and job description text.</span>
                            @endforelse
                        </div>
                        <div class="col-md-6">
                            <div style="color:var(--tx);font-weight:800;font-size:.86rem;margin-bottom:7px;">Missing Keywords</div>
                            @forelse(array_slice($application->missing_keywords ?? [], 0, 8) as $keyword)
                                <span class="keyword-chip miss">{{ $keyword }}</span>
                            @empty
                                <span style="color:var(--tx3);font-size:.84rem;">No missing keywords detected.</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <strong style="color:var(--tx);">7-Day Smart Practice Plan</strong>
                            <span style="color:#10b981;font-weight:900;">{{ $progress }}%</span>
                        </div>
                        <div class="progress" style="height:8px;background:var(--bd);border-radius:999px;">
                            <div class="progress-bar bg-success" style="width:{{ $progress }}%;border-radius:999px;"></div>
                        </div>
                    </div>

                    @foreach($application->planItems->sortBy('due_date')->take(10) as $item)
                        <div class="plan-item {{ $item->completed_at ? 'done' : '' }}" id="plan-item-{{ $item->id }}">
                            <input type="checkbox" class="form-check-input mt-1" {{ $item->completed_at ? 'checked' : '' }} onchange="togglePlanItem({{ $item->id }}, this)">
                            <div>
                                <div class="plan-title">Day {{ $item->day_number }}: {{ $item->title }}</div>
                                <div class="plan-task">{{ $item->task }}</div>
                            </div>
                            <span style="color:var(--tx3);font-size:.78rem;white-space:nowrap;">{{ $item->due_date?->format('M d') }}</span>
                        </div>
                    @endforeach

                    <details class="mt-3">
                        <summary style="color:#60a5fa;font-weight:800;cursor:pointer;">Edit Application</summary>
                        <form action="{{ route('user.applications.update', $application) }}" method="POST" class="mt-3">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-6"><input class="tracker-field" name="company_name" value="{{ $application->company_name }}" required></div>
                                <div class="col-md-6"><input class="tracker-field" name="job_title" value="{{ $application->job_title }}" required></div>
                                <div class="col-md-4">
                                    <select class="tracker-field" name="status">
                                        @foreach(['tracking','applied','screening','interviewing','offer','rejected','archived'] as $status)
                                            <option value="{{ $status }}" {{ $application->status === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4"><input class="tracker-field" name="interview_stage" value="{{ $application->interview_stage }}" placeholder="Stage"></div>
                                <div class="col-md-4"><input class="tracker-field" type="date" name="interview_date" value="{{ $application->interview_date?->format('Y-m-d') }}"></div>
                                <div class="col-md-12"><input class="tracker-field" type="url" name="source_url" value="{{ $application->source_url }}" placeholder="Source URL"></div>
                                <div class="col-md-6"><textarea class="tracker-field" name="resume_text" rows="4">{{ $application->resume_text }}</textarea></div>
                                <div class="col-md-6"><textarea class="tracker-field" name="job_description" rows="4">{{ $application->job_description }}</textarea></div>
                                <div class="col-md-12"><textarea class="tracker-field" name="notes" rows="2">{{ $application->notes }}</textarea></div>
                            </div>
                            <button class="btn btn-outline-primary mt-3" style="border-radius:10px;font-weight:800;">Update Tracker</button>
                        </form>
                    </details>
                </section>
            @empty
                <section class="tracker-panel text-center py-5">
                    <i class="fa-solid fa-briefcase" style="font-size:3rem;color:#60a5fa;"></i>
                    <h5 style="color:var(--tx);font-weight:900;margin-top:16px;">No tracked applications yet</h5>
                    <p style="color:var(--tx3);max-width:520px;margin:8px auto 0;">Add your first target job to unlock resume match scoring and a job-specific practice plan.</p>
                </section>
            @endforelse
        </main>
    </div>
</div>

<script>
function togglePlanItem(itemId, checkbox) {
    const itemEl = document.getElementById(`plan-item-${itemId}`);
    const previousState = checkbox ? !checkbox.checked : null;

    if (itemEl) itemEl.classList.add('is-saving');
    if (checkbox) checkbox.disabled = true;

    fetch(`{{ url('/practice-plan') }}/${itemId}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(res => {
        if (!res.ok) throw new Error('Request failed');
        return res.json();
    })
    .then(data => {
        if (!data.success) throw new Error('Update failed');
        if (itemEl) itemEl.classList.toggle('done', data.completed);
    })
    .catch(() => {
        if (checkbox && previousState !== null) checkbox.checked = previousState;
        alert('Could not update this practice task. Please try again.');
    })
    .finally(() => {
        if (itemEl) itemEl.classList.remove('is-saving');
        if (checkbox) checkbox.disabled = false;
    });
}
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#job-tracker-summary', popover: { title: 'Tracker Snapshot', description: 'See tracked jobs, active applications, upcoming interviews, and plan progress.', side: 'bottom', align: 'start' }},
            { element: '#job-tracker-form', popover: { title: 'Add Target Job', description: 'Paste the job, resume, and job description to generate a match score and plan.', side: 'top', align: 'start' }},
            { element: '.tracker-application-card', popover: { title: 'Application Card', description: 'Review stage, match score, missing keywords, and the smart practice plan.', side: 'top', align: 'start' }},
            { element: '.plan-item', popover: { title: 'Practice Tasks', description: 'Check off each task as you work through the role-specific preparation plan.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#job-tracker-summary', popover: { title: 'Tracker Snapshot', description: 'See tracked jobs, active applications, upcoming interviews, and plan progress.', side: 'bottom', align: 'start' }},
            { element: '#job-tracker-form', popover: { title: 'Add Target Job', description: 'Paste the job, resume, and job description to generate a match score and plan.', side: 'right', align: 'start' }},
            { element: '.tracker-application-card', popover: { title: 'Application Card', description: 'Review stage, match score, missing keywords, and the smart practice plan.', side: 'left', align: 'start' }},
            { element: '.plan-item', popover: { title: 'Practice Tasks', description: 'Check off each task as you work through the role-specific preparation plan.', side: 'top', align: 'start' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_job_tracker',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush

@endsection
