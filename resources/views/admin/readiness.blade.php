@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('page-title', 'Readiness Careers')
@section('content')
<div class="db-section active" id="sec-admin-readiness">
    <style>
        #sec-admin-readiness .readiness-card,
        #sec-admin-readiness .readiness-panel {
            background: var(--sf);
            border: 1px solid var(--bd);
            border-radius: 8px;
        }
        #sec-admin-readiness .readiness-card {
            padding: 16px;
            min-height: 96px;
        }
        #sec-admin-readiness .readiness-card span {
            color: var(--tx3);
            font-size: .73rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 800;
        }
        #sec-admin-readiness .readiness-card strong {
            color: var(--tx);
            display: block;
            font-size: 1.55rem;
            margin-top: 6px;
        }
        #sec-admin-readiness .readiness-panel {
            overflow: hidden;
        }
        #sec-admin-readiness .readiness-panel-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--bd);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        #sec-admin-readiness .readiness-filters {
            display: grid;
            grid-template-columns: minmax(180px, 280px) minmax(130px, 170px) auto;
            gap: 10px;
            align-items: center;
        }
        #sec-admin-readiness .readiness-table {
            table-layout: fixed;
            width: 100%;
            min-width: 940px;
            font-size: .82rem;
        }
        #sec-admin-readiness .readiness-table th,
        #sec-admin-readiness .readiness-table td {
            vertical-align: middle;
            border-color: var(--bd) !important;
        }
        #sec-admin-readiness .item-title {
            color: var(--tx);
            font-weight: 800;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }
        #sec-admin-readiness .item-meta {
            color: var(--tx3);
            font-size: .75rem;
            margin-top: 3px;
        }
        #sec-admin-readiness .readiness-list {
            display: grid;
            gap: 10px;
            padding: 16px;
        }
        #sec-admin-readiness .readiness-list-item {
            border: 1px solid var(--bd);
            border-radius: 8px;
            padding: 12px;
            background: var(--bg3);
        }
        #sec-admin-readiness .support-form {
            display: grid;
            gap: 8px;
        }
        #sec-admin-readiness .support-form.inline {
            grid-template-columns: minmax(90px, 130px) minmax(110px, 1fr) minmax(110px, 1fr) auto;
            align-items: center;
        }
        #sec-admin-readiness .support-form .form-control,
        #sec-admin-readiness .support-form .form-select {
            min-height: 34px;
            padding: 5px 8px;
            font-size: .75rem;
        }
        #sec-admin-readiness .support-form .btn {
            min-height: 34px;
            font-size: .74rem;
            font-weight: 800;
            white-space: nowrap;
        }
        @media (max-width: 991.98px) {
            #sec-admin-readiness .readiness-panel-header {
                align-items: flex-start;
                flex-direction: column;
            }
            #sec-admin-readiness .readiness-filters {
                grid-template-columns: 1fr;
                width: 100%;
            }
            #sec-admin-readiness .support-form.inline {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="mb-4">
        <h4 style="font-size:1.4rem;font-weight:800;margin-bottom:4px">Readiness Careers</h4>
        <p style="font-size:.875rem;color:var(--tx3);margin:0">Admin visibility for job applications, readiness twins, verified stories, outcomes, and adaptive plans.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="readiness-card"><span>Applications</span><strong>{{ $stats['applications'] }}</strong></div></div>
        <div class="col-6 col-xl-3"><div class="readiness-card"><span>Readiness Twins</span><strong>{{ $stats['readiness_profiles'] }}</strong></div></div>
        <div class="col-6 col-xl-3"><div class="readiness-card"><span>Verified Stories</span><strong>{{ $stats['verified_stories'] }}</strong></div></div>
        <div class="col-6 col-xl-3"><div class="readiness-card"><span>Real Outcomes</span><strong>{{ $stats['outcomes'] }}</strong></div></div>
        <div class="col-6 col-xl-4"><div class="readiness-card"><span>Open Plan Items</span><strong>{{ $stats['open_plan_items'] }}</strong></div></div>
        <div class="col-6 col-xl-4"><div class="readiness-card"><span>Avg Match</span><strong>{{ $stats['avg_match'] }}%</strong></div></div>
        <div class="col-12 col-xl-4"><div class="readiness-card"><span>Avg Evidence</span><strong>{{ $stats['avg_evidence'] }}%</strong></div></div>
    </div>

    <div class="readiness-panel mb-4">
        <div class="readiness-panel-header">
            <div>
                <h5 style="color:var(--tx);font-weight:800;margin:0">Job Application Pipeline</h5>
                <p style="color:var(--tx3);margin:4px 0 0;font-size:.82rem">User-owned applications are visible here for support and analytics.</p>
            </div>
            <form method="GET" action="{{ route('admin.readiness.index') }}" class="readiness-filters">
                <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search user, company, role">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    @foreach(['tracking', 'interviewing', 'offer', 'rejected', 'withdrawn'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table readiness-table mb-0">
                <thead>
                    <tr>
                        <th style="width:20%">Candidate</th>
                        <th style="width:20%">Application</th>
                        <th style="width:14%">Readiness</th>
                        <th style="width:14%">Plan</th>
                        <th style="width:32%">Admin Support Controls</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $application)
                        <tr>
                            <td>
                                <div class="item-title">{{ $application->user?->name ?? 'Unknown User' }}</div>
                                <div class="item-meta">{{ $application->user?->email ?? 'No email' }}</div>
                            </td>
                            <td>
                                <div class="item-title">{{ $application->job_title }}</div>
                                <div class="item-meta">{{ $application->company_name }} · {{ ucfirst($application->status) }}</div>
                            </td>
                            <td>
                                <span class="badge bg-primary">Match {{ (int) $application->match_score }}%</span>
                                <span class="badge bg-info">Evidence {{ (int) ($application->evidence_match_score ?? $application->match_score ?? 0) }}%</span>
                                <div class="item-meta">
                                    Twin {{ $application->readinessProfile ? 'calibrated' : 'pending' }}
                                </div>
                            </td>
                            <td>
                                <div style="color:var(--tx2)">{{ $application->plan_items_count }} plan items</div>
                                <div class="item-meta">{{ $application->sessions_count }} linked sessions</div>
                            </td>
                            <td>
                                <div style="color:var(--tx2)">{{ $application->outcomes->count() }} outcomes</div>
                                <div class="item-meta">Updated {{ $application->updated_at?->diffForHumans() }}</div>
                                <form class="support-form mt-2" method="POST" action="{{ route('admin.readiness.applications.update', $application) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="support-form inline">
                                        <select class="form-select" name="status" aria-label="Application status">
                                            @foreach(['tracking', 'interviewing', 'offer', 'rejected', 'withdrawn'] as $status)
                                                <option value="{{ $status }}" @selected($application->status === $status)>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                        <input class="form-control" name="interview_stage" value="{{ $application->interview_stage }}" placeholder="Stage">
                                        <input class="form-control" type="date" name="interview_date" value="{{ optional($application->interview_date)->format('Y-m-d') }}">
                                        <button class="btn btn-outline-primary" type="submit">Update</button>
                                    </div>
                                    <input class="form-control" name="notes" value="{{ $application->notes }}" placeholder="Admin support note">
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5" style="color:var(--tx3)">No job applications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top" style="border-color:var(--bd)!important">
            {{ $applications->links('pagination::bootstrap-5') }}
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="readiness-panel h-100">
                <div class="readiness-panel-header">
                    <h5 style="color:var(--tx);font-weight:800;margin:0">Recent Verified Stories</h5>
                </div>
                <div class="readiness-list">
                    @forelse($recentStories as $story)
                        <div class="readiness-list-item">
                            <div class="item-title">{{ $story->title }}</div>
                            <form class="support-form inline mt-2 mb-2" method="POST" action="{{ route('admin.readiness.stories.update', $story) }}">
                                @csrf
                                @method('PATCH')
                                <select class="form-select" name="visibility" aria-label="Story visibility">
                                    <option value="private" @selected($story->visibility === 'private')>Private</option>
                                    <option value="support_review" @selected($story->visibility === 'support_review')>Support Review</option>
                                </select>
                                <label class="form-check d-flex align-items-center gap-2 m-0" style="color:var(--tx2);font-size:.78rem;">
                                    <input class="form-check-input m-0" type="checkbox" name="facts_confirmed" value="1" @checked($story->facts_confirmed)>
                                    Facts confirmed
                                </label>
                                <span class="item-meta">Recalibrates plans</span>
                                <button class="btn btn-outline-primary" type="submit">Save</button>
                            </form>
                            <div class="item-meta">{{ $story->user?->name ?? 'Unknown User' }} · {{ $story->context_type }} · {{ $story->created_at?->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="text-center py-4" style="color:var(--tx3)">No verified stories yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="readiness-panel h-100">
                <div class="readiness-panel-header">
                    <h5 style="color:var(--tx);font-weight:800;margin:0">Recent Real Outcomes</h5>
                </div>
                <div class="readiness-list">
                    @forelse($recentOutcomes as $outcome)
                        <div class="readiness-list-item">
                            <form class="support-form inline mb-2" method="POST" action="{{ route('admin.readiness.outcomes.update', $outcome) }}">
                                @csrf
                                @method('PATCH')
                                <select class="form-select" name="result" aria-label="Outcome result">
                                    @foreach(['pending', 'advanced', 'offer', 'rejected', 'withdrawn'] as $result)
                                        <option value="{{ $result }}" @selected($outcome->result === $result)>{{ ucfirst($result) }}</option>
                                    @endforeach
                                </select>
                                <input class="form-control" name="stage" value="{{ $outcome->stage }}" placeholder="Stage">
                                <label class="form-check d-flex align-items-center gap-2 m-0" style="color:var(--tx2);font-size:.78rem;">
                                    <input class="form-check-input m-0" type="checkbox" name="allow_anonymous_learning" value="1" @checked($outcome->allow_anonymous_learning)>
                                    Anonymous learning
                                </label>
                                <button class="btn btn-outline-primary" type="submit">Update</button>
                            </form>
                            <div class="item-title">{{ ucfirst($outcome->result) }} · {{ $outcome->stage ?: 'Interview' }}</div>
                            <div class="item-meta">{{ $outcome->user?->name ?? 'Unknown User' }} · {{ $outcome->jobApplication?->company_name ?? 'No application' }} · {{ optional($outcome->interview_date)->format('M d, Y') ?: 'No date' }}</div>
                        </div>
                    @empty
                        <div class="text-center py-4" style="color:var(--tx3)">No real outcomes yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
