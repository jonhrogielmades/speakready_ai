@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Readiness Twin')
@section('content')
@php
    $twin = $selectedApplication?->readinessProfile;
    $competencies = collect($twin?->competency_map ?? []);
    $preferences = $profile->inclusive_preferences ?? [];
@endphp
<style>
    .rt-hero,.rt-card{background:var(--sf);border:1px solid var(--bd);border-radius:18px;box-shadow:0 12px 35px rgba(15,23,42,.08)}
    .rt-hero{padding:24px;background:linear-gradient(120deg,rgba(59,130,246,.13),rgba(139,92,246,.08)),var(--sf)}
    .rt-card{padding:22px;height:100%}.rt-title{font-weight:850;color:var(--tx);margin:0}.rt-muted{color:var(--tx3)}
    .rt-chip{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:.76rem;font-weight:750;background:rgba(59,130,246,.11);color:#60a5fa}
    .rt-meter{height:9px;border-radius:999px;background:var(--bg3);overflow:hidden}.rt-meter span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#3b82f6,#8b5cf6)}
    .rt-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(245px,1fr));gap:14px}.rt-competency{padding:16px;border:1px solid var(--bd);border-radius:14px;background:var(--bg3)}
    .rt-form label{font-size:.8rem;font-weight:750;color:var(--tx);margin-bottom:6px}.rt-form .form-control,.rt-form .form-select{background:var(--bg3);border-color:var(--bd);color:var(--tx);border-radius:11px}
    .rt-list{display:flex;flex-direction:column;gap:11px}.rt-list-item{padding:15px;border:1px solid var(--bd);border-radius:13px;background:var(--bg3)}
    .rt-empty{padding:28px;text-align:center;color:var(--tx3);border:1px dashed var(--bd);border-radius:14px}
    .rt-tabs{display:flex;gap:8px;overflow:auto;padding-bottom:4px}.rt-tabs a{white-space:nowrap;text-decoration:none;padding:9px 13px;border-radius:10px;background:var(--bg3);color:var(--tx3);font-weight:700}.rt-tabs a.active{background:#3b82f6;color:white}
    details.rt-details summary{cursor:pointer;color:#60a5fa;font-weight:750}
</style>

<div class="db-section active">
    <div class="rt-hero mb-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <span class="rt-chip mb-2"><i class="fa-solid fa-shield-heart"></i> Outcome-validated preparation</span>
                <h3 class="rt-title">Interview Readiness Twin</h3>
                <p class="rt-muted mb-0 mt-2">Build verified evidence, master job-specific competencies, use inclusive assessment settings, and learn from real interviews.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-primary" href="{{ route('user.coach') }}"><i class="fa-solid fa-robot me-2"></i>Readiness Coach</a>
                <a class="btn btn-primary" href="{{ route('interview.setup', $selectedApplication ? ['application' => $selectedApplication->id] : []) }}"><i class="fa-solid fa-play me-2"></i>Practice</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><strong>Please review the form:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if($applications->isNotEmpty())
        <div class="rt-tabs mb-3">
            @foreach($applications as $application)
                <a class="{{ $selectedApplication?->id === $application->id ? 'active' : '' }}" href="{{ route('user.readiness.index', ['application' => $application->id]) }}">
                    {{ $application->company_name }} · {{ $application->job_title }}
                </a>
            @endforeach
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="rt-card">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h5 class="rt-title">{{ $selectedApplication->job_title }} competency map</h5>
                            <p class="rt-muted mb-0 mt-1">{{ $selectedApplication->company_name }} · calibrated {{ optional($twin?->calibrated_at)->diffForHumans() ?? 'when refreshed' }}</p>
                        </div>
                        <form method="POST" action="{{ route('user.readiness.refresh', $selectedApplication) }}">@csrf
                            <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-rotate me-1"></i>Recalibrate</button>
                        </form>
                    </div>
                    <div class="rt-grid">
                        @forelse($competencies as $competency)
                            <div class="rt-competency">
                                <div class="d-flex justify-content-between gap-2 mb-2">
                                    <strong style="color:var(--tx)">{{ $competency['name'] }}</strong>
                                    <span class="rt-chip">{{ $competency['mastery'] ?? 0 }}%</span>
                                </div>
                                <div class="rt-meter mb-2"><span style="width:{{ $competency['mastery'] ?? 0 }}%"></span></div>
                                <div class="small rt-muted mb-2">{{ $competency['readiness_band'] ?? 'Developing' }} · {{ count($competency['story_ids'] ?? []) }} verified stories</div>
                                <div class="small" style="color:var(--tx)">{{ $competency['next_drill'] ?? 'Complete a targeted practice answer.' }}</div>
                            </div>
                        @empty
                            <div class="rt-empty">Recalibrate this application to create its competency map.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="rt-card">
                    <h5 class="rt-title mb-3">Readiness snapshot</h5>
                    <div class="display-5 fw-bold text-primary">{{ data_get($twin?->mastery_snapshot, 'average', 0) }}%</div>
                    <div class="rt-muted mb-3">Competency mastery—not a promise of hiring success.</div>
                    <div class="rt-list">
                        <div class="rt-list-item"><small class="rt-muted">Job evidence match</small><div class="fw-bold" style="color:var(--tx)">{{ $selectedApplication->evidence_match_score }}%</div></div>
                        <div class="rt-list-item"><small class="rt-muted">Strongest</small><div class="fw-bold text-success">{{ data_get($twin?->mastery_snapshot, 'strongest', 'Not enough data') }}</div></div>
                        <div class="rt-list-item"><small class="rt-muted">Next focus</small><div class="fw-bold text-warning">{{ data_get($twin?->mastery_snapshot, 'weakest', 'Add evidence') }}</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="rt-card">
                    <h5 class="rt-title mb-3">Adaptive practice plan</h5>
                    <div class="rt-list">
                        @forelse($selectedApplication->planItems->take(10) as $item)
                            <div class="rt-list-item d-flex align-items-start gap-3">
                                <span class="rt-chip">{{ $item->due_date?->format('M j') ?? 'Next' }}</span>
                                <div><strong style="color:var(--tx)">{{ $item->title }}</strong><div class="small rt-muted mt-1">{{ $item->task }}</div></div>
                            </div>
                        @empty
                            <div class="rt-empty">Refresh the twin to generate an adaptive practice plan.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="rt-card">
                    <h5 class="rt-title mb-3">Future-skill radar</h5>
                    <p class="rt-muted">Transferable capabilities worth demonstrating as the role changes.</p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(($twin?->future_skills ?? $selectedApplication->future_skills ?? []) as $skill)
                            <span class="rt-chip"><i class="fa-solid fa-arrow-trend-up"></i>{{ $skill }}</span>
                        @endforeach
                    </div>
                    <hr style="border-color:var(--bd)">
                    <div class="small rt-muted">These are coaching priorities, not claims about a specific employer's selection process.</div>
                </div>
            </div>
        </div>
    @else
        <div class="rt-card mb-4"><div class="rt-empty"><h5>No target job yet</h5><p>Add a job description so the system can build a role-specific competency map.</p><a class="btn btn-primary" href="{{ route('user.applications.index') }}">Add target job</a></div></div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="rt-card">
                <h5 class="rt-title mb-2">Verified STAR Story Vault</h5>
                <p class="rt-muted small">The coach may organize these facts, but it must not invent achievements or results.</p>
                <form class="rt-form" method="POST" action="{{ route('user.readiness.stories.store') }}">@csrf
                    <div class="row g-2">
                        <div class="col-md-7"><label>Story title</label><input class="form-control" name="title" required value="{{ old('title') }}"></div>
                        <div class="col-md-5"><label>Context</label><select class="form-select" name="context_type">@foreach(['work','internship','education','volunteer','freelance','personal_project','community'] as $type)<option value="{{ $type }}">{{ ucfirst(str_replace('_',' ',$type)) }}</option>@endforeach</select></div>
                        <div class="col-12"><label>Situation</label><textarea class="form-control" name="situation" rows="2">{{ old('situation') }}</textarea></div>
                        <div class="col-12"><label>Task</label><textarea class="form-control" name="task" rows="2">{{ old('task') }}</textarea></div>
                        <div class="col-12"><label>Your action *</label><textarea class="form-control" name="action" rows="3" required>{{ old('action') }}</textarea></div>
                        <div class="col-12"><label>Result</label><textarea class="form-control" name="result" rows="2">{{ old('result') }}</textarea></div>
                        <div class="col-12"><label>Verified facts (one per line)</label><textarea class="form-control" name="verified_facts_text" rows="2">{{ old('verified_facts_text') }}</textarea></div>
                        <div class="col-12"><label>Metrics (one per line)</label><textarea class="form-control" name="metrics_text" rows="2">{{ old('metrics_text') }}</textarea></div>
                        <div class="col-12"><label>Competency tags</label><input class="form-control" name="competency_tags_text" placeholder="Leadership, Communication, Problem Solving" value="{{ old('competency_tags_text') }}"></div>
                        <div class="col-12"><label class="d-flex gap-2 align-items-start"><input type="checkbox" name="facts_confirmed" value="1" required><span>I confirm these are truthful facts from my own experience.</span></label></div>
                        <div class="col-12"><button class="btn btn-primary w-100">Add verified story</button></div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="rt-card">
                <h5 class="rt-title mb-3">Private evidence library</h5>
                <div class="rt-list">
                    @forelse($stories as $story)
                        <div class="rt-list-item">
                            <div class="d-flex justify-content-between gap-3">
                                <div><strong style="color:var(--tx)">{{ $story->title }}</strong><div class="small rt-muted">{{ ucfirst(str_replace('_',' ',$story->context_type)) }} · {{ implode(', ', $story->competency_tags ?? []) ?: 'Untagged' }}</div></div>
                                <form method="POST" action="{{ route('user.readiness.stories.destroy', $story) }}" onsubmit="return confirm('Remove this private story?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
                            </div>
                            <details class="rt-details mt-2"><summary>Review and edit verified facts</summary>
                                <form class="rt-form mt-3" method="POST" action="{{ route('user.readiness.stories.update', $story) }}">@csrf @method('PUT')
                                    <input type="hidden" name="context_type" value="{{ $story->context_type }}">
                                    <div class="row g-2"><div class="col-12"><label>Title</label><input class="form-control" name="title" value="{{ $story->title }}" required></div>
                                    @foreach(['situation','task','action','result'] as $part)<div class="col-md-6"><label>{{ ucfirst($part) }}</label><textarea class="form-control" name="{{ $part }}" rows="3" {{ $part === 'action' ? 'required' : '' }}>{{ $story->{$part} }}</textarea></div>@endforeach
                                    <div class="col-12"><label>Facts</label><textarea class="form-control" name="verified_facts_text">{{ implode("\n", $story->verified_facts ?? []) }}</textarea></div>
                                    <div class="col-12"><label>Metrics</label><textarea class="form-control" name="metrics_text">{{ implode("\n", $story->metrics ?? []) }}</textarea></div>
                                    <div class="col-12"><label>Tags</label><input class="form-control" name="competency_tags_text" value="{{ implode(', ', $story->competency_tags ?? []) }}"></div>
                                    <input type="hidden" name="facts_confirmed" value="1"><div class="col-12"><button class="btn btn-sm btn-outline-primary">Save changes</button></div></div>
                                </form>
                            </details>
                        </div>
                    @empty
                        <div class="rt-empty">Add your first truthful experience. School, volunteer and personal-project evidence all count.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="rt-card">
                <h5 class="rt-title mb-2">Real interview outcome loop</h5>
                <p class="small rt-muted">Record what happened so readiness can be calibrated against real transfer—not confidence alone.</p>
                <form class="rt-form" method="POST" action="{{ route('user.readiness.outcomes.store') }}">@csrf
                    <div class="row g-2">
                        <div class="col-md-7"><label>Application</label><select class="form-select" name="job_application_id"><option value="">General interview</option>@foreach($applications as $application)<option value="{{ $application->id }}" {{ $selectedApplication?->id === $application->id ? 'selected' : '' }}>{{ $application->company_name }} · {{ $application->job_title }}</option>@endforeach</select></div>
                        <div class="col-md-5"><label>Date</label><input class="form-control" type="date" name="interview_date" value="{{ now()->toDateString() }}"></div>
                        <div class="col-md-4"><label>Format</label><select class="form-select" name="interview_format">@foreach(['live','phone','video','panel','asynchronous','technical','case','presentation'] as $format)<option>{{ $format }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label>Stage</label><input class="form-control" name="stage" placeholder="HR screen"></div>
                        <div class="col-md-4"><label>Result</label><select class="form-select" name="result">@foreach(['pending','advanced','offer','rejected','withdrawn'] as $result)<option>{{ $result }}</option>@endforeach</select></div>
                        <div class="col-12"><label>Questions asked (one per line)</label><textarea class="form-control" name="questions_asked_text" rows="3"></textarea></div>
                        <div class="col-12"><label>Surprise topics or missing preparation</label><textarea class="form-control" name="surprise_topics_text" rows="2"></textarea></div>
                        <div class="col-12"><label>Recruiter feedback</label><textarea class="form-control" name="recruiter_feedback" rows="2"></textarea></div>
                        <div class="col-12"><label>Reflection</label><textarea class="form-control" name="reflection" rows="2"></textarea></div>
                        @if($stories->isNotEmpty())
                            <div class="col-12"><label>Which verified stories were useful?</label><div class="rt-list-item d-flex flex-wrap gap-3">@foreach($stories as $story)<label class="d-flex gap-2 align-items-center"><input type="checkbox" name="useful_story_ids[]" value="{{ $story->id }}"><span>{{ $story->title }}</span></label>@endforeach</div></div>
                        @endif
                        <div class="col-6"><label>Self-reported preparedness before</label><input class="form-control" type="number" min="0" max="100" name="confidence_before"></div>
                        <div class="col-6"><label>Self-reported preparedness after</label><input class="form-control" type="number" min="0" max="100" name="confidence_after"></div>
                        <div class="col-12"><label class="d-flex gap-2"><input type="checkbox" name="allow_anonymous_learning" value="1"><span>Allow de-identified outcome statistics to improve calibration.</span></label></div>
                        <div class="col-12"><button class="btn btn-primary w-100">Record outcome and recalibrate</button></div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="rt-card">
                <h5 class="rt-title mb-3">Practice-to-reality history</h5>
                <div class="rt-list">
                    @forelse($outcomes as $outcome)
                        <div class="rt-list-item d-flex justify-content-between gap-3">
                            <div><strong style="color:var(--tx)">{{ $outcome->jobApplication?->company_name ?? 'General interview' }} · {{ ucfirst($outcome->result) }}</strong><div class="small rt-muted">{{ $outcome->interview_date?->format('M j, Y') }} · {{ ucfirst($outcome->interview_format) }} · {{ $outcome->stage ?: 'Stage not specified' }}</div><div class="small mt-2" style="color:var(--tx)">{{ count($outcome->questions_asked ?? []) }} questions captured · {{ count($outcome->surprise_topics ?? []) }} surprise gaps</div></div>
                            <form method="POST" action="{{ route('user.readiness.outcomes.destroy', $outcome) }}" onsubmit="return confirm('Remove this outcome?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button></form>
                        </div>
                    @empty
                        <div class="rt-empty">No real interview outcomes recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="rt-card mb-4">
        <h5 class="rt-title mb-2">Inclusive practice profile</h5>
        <p class="small rt-muted">These options change practice conditions. Camera behavior is optional and is never included in readiness scoring.</p>
        <form class="rt-form" method="POST" action="{{ route('user.readiness.preferences') }}">@csrf
            <div class="row g-3">
                @foreach([
                    'camera_coaching' => 'Optional camera framing feedback',
                    'separate_language_scoring' => 'Separate language mechanics from job readiness',
                    'extended_time' => 'Extended response time',
                    'captions' => 'Captions and transcript-first controls',
                    'reduced_distraction' => 'Reduced-distraction workspace',
                    'simplified_questions' => 'Clearer, simplified question wording',
                ] as $key => $label)
                    <div class="col-md-6 col-lg-4"><label class="d-flex gap-2 align-items-start rt-list-item"><input type="checkbox" name="{{ $key }}" value="1" {{ data_get($preferences, $key) ? 'checked' : '' }}><span>{{ $label }}</span></label></div>
                @endforeach
                <div class="col-md-6"><label>Preferred response mode</label><select class="form-select" name="preferred_response_mode">@foreach(['text','voice','hybrid'] as $mode)<option value="{{ $mode }}" {{ data_get($preferences, 'preferred_response_mode', 'voice') === $mode ? 'selected' : '' }}>{{ ucfirst($mode) }}</option>@endforeach</select></div>
                <div class="col-md-6 d-flex align-items-end"><button class="btn btn-primary w-100">Save inclusive preferences</button></div>
            </div>
        </form>
    </div>
</div>
@endsection
