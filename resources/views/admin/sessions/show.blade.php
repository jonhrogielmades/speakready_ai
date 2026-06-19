@extends('layouts.admin')

@section('content')
<style>
    .premium-card {
        background: var(--sf, #1e1e2d);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .stat-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.8rem;
    }
    .stat-badge.success { background: rgba(52, 211, 153, 0.15); color: #34d399; }
    .stat-badge.warning { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .stat-badge.danger { background: rgba(248, 113, 113, 0.15); color: #f87171; }
    .stat-badge.primary { background: rgba(96, 165, 250, 0.15); color: #60a5fa; }
    .stat-badge.secondary { background: rgba(156, 163, 175, 0.15); color: #9ca3af; }

    .progress-track {
        background: var(--bd, rgba(255,255,255,0.1));
        border-radius: 10px;
        height: 8px;
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-fill {
        height: 100%;
        border-radius: 10px;
    }
    
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 11px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--bd);
    }
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    .timeline-item:last-child { margin-bottom: 0; }
    .timeline-icon {
        position: absolute;
        left: -30px;
        top: 0;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        color: #fff;
        border: 2px solid var(--sf);
    }
    .bg-primary { background: #3b82f6 !important; }
    .bg-success { background: #10b981 !important; }
    .bg-warning { background: #f59e0b !important; }
    .bg-danger { background: #ef4444 !important; }
    .bg-info { background: #06b6d4 !important; }
</style>

<div class="db-section active">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.sessions.index') }}" class="text-decoration-none" style="color:var(--tx2);font-size:0.9rem;">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Sessions
            </a>
            <h4 class="fw-bold mb-1 mt-2">Session #{{ $session->id }} Details</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sessions.review', $session->id) }}" class="btn btn-primary" style="border-radius:12px;">
                <i class="fa-solid fa-magnifying-glass-chart me-2"></i>Review Q&A
            </a>
            <button class="btn btn-danger" style="border-radius:12px;" data-bs-toggle="modal" data-bs-target="#flagModal">
                <i class="fa-solid fa-flag me-2"></i>Flag Session
            </button>
        </div>
    </div>

    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    @if($session->flag_reason)
    <div class="alert alert-danger d-flex align-items-center mb-4" style="background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.3);color:#f87171;border-radius:12px;">
        <i class="fa-solid fa-triangle-exclamation fa-lg me-3"></i>
        <div>
            <strong>Session Flagged:</strong> {{ $session->flag_reason }}
        </div>
    </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="premium-card h-100">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-circle-info text-primary me-2"></i>Interview Information</h6>
                        <table class="table table-borderless mb-0" style="color:var(--tx);">
                            <tbody>
                                <tr><th style="color:var(--tx3);width:40%;">Session ID</th><td class="fw-bold">#{{ $session->id }}</td></tr>
                                <tr>
                                    <th style="color:var(--tx3);">User Name</th>
                                    <td>
                                        @if($session->user)
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width:24px;height:24px;border-radius:50%;background:#3b82f6;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:0.6rem;">
                                                    {{ strtoupper(substr($session->user->name, 0, 2)) }}
                                                </div>
                                                {{ $session->user->name }}
                                            </div>
                                        @else
                                            <span class="text-muted">Deleted User</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr><th style="color:var(--tx3);">Category</th><td><span class="stat-badge primary">{{ $session->category ? $session->category->title : 'N/A' }}</span></td></tr>
                                <tr><th style="color:var(--tx3);">Difficulty Level</th><td>{{ ucfirst($session->difficulty) }}</td></tr>
                                <tr><th style="color:var(--tx3);">Date & Time</th><td>{{ $session->created_at->format('M d, Y h:i A') }}</td></tr>
                                <tr><th style="color:var(--tx3);">Duration</th><td>{{ gmdate("i:s", $session->duration_seconds) }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="premium-card h-100">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-square-poll-vertical text-success me-2"></i>Session Results</h6>
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded" style="background:var(--bg3);border:1px solid var(--bd);">
                            <div>
                                <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;">Overall Score</div>
                                <h3 class="fw-bold m-0 {{ $performance && $performance->overall_readiness_score >= 80 ? 'text-success' : ($performance && $performance->overall_readiness_score >= 60 ? 'text-warning' : 'text-danger') }}">
                                    {{ $performance ? $performance->overall_readiness_score : 0 }}%
                                </h3>
                            </div>
                            <div class="text-end">
                                <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;">Readiness Rating</div>
                                <h5 class="fw-bold m-0 text-primary">
                                    @if($performance)
                                        @if($performance->overall_readiness_score >= 90) Excellent
                                        @elseif($performance->overall_readiness_score >= 80) Good
                                        @elseif($performance->overall_readiness_score >= 70) Fair
                                        @else Needs Improvement
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </h5>
                            </div>
                        </div>
                        <div class="row text-center mt-2">
                            <div class="col-6">
                                <div class="fw-bold" style="font-size:1.2rem;">{{ $questionsAnswered }}</div>
                                <div style="font-size:0.8rem;color:var(--tx2);">Questions Answered</div>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold text-danger" style="font-size:1.2rem;">{{ $questionsSkipped }}</div>
                                <div style="font-size:0.8rem;color:var(--tx2);">Questions Skipped</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Breakdown -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-4"><i class="fa-solid fa-chart-radar text-warning me-2"></i>Performance Breakdown</h6>
                
                @if($performance)
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;">
                                    <span><i class="fa-solid fa-eye me-2" style="color:#60a5fa;"></i>Clarity</span>
                                    <span class="fw-bold">{{ $performance->clarity_score }}%</span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" style="width:{{ $performance->clarity_score }}%;background:#60a5fa;"></div></div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;">
                                    <span><i class="fa-solid fa-bullseye me-2" style="color:#34d399;"></i>Relevance</span>
                                    <span class="fw-bold">{{ $performance->relevance_score }}%</span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" style="width:{{ $performance->relevance_score }}%;background:#34d399;"></div></div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;">
                                    <span><i class="fa-solid fa-spell-check me-2" style="color:#f472b6;"></i>Grammar</span>
                                    <span class="fw-bold">{{ $performance->grammar_score }}%</span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" style="width:{{ $performance->grammar_score }}%;background:#f472b6;"></div></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;">
                                    <span><i class="fa-solid fa-user-tie me-2" style="color:#fbbf24;"></i>Professionalism</span>
                                    <span class="fw-bold">{{ $performance->professionalism_score }}%</span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" style="width:{{ $performance->professionalism_score }}%;background:#fbbf24;"></div></div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;">
                                    <span><i class="fa-solid fa-face-smile-beam me-2" style="color:#a855f7;"></i>Confidence</span>
                                    <span class="fw-bold">{{ $performance->confidence_score }}%</span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" style="width:{{ $performance->confidence_score }}%;background:#a855f7;"></div></div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center p-4 text-muted border rounded" style="background:var(--bg3);border-color:var(--bd) !important;">
                        <i class="fa-solid fa-chart-bar fa-2x mb-2"></i>
                        <p class="m-0">No performance data generated for this session.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Session Status Tracking -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-3">Session Status Tracking</h6>
                <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background:var(--bg3);border:1px solid var(--bd);">
                    <div style="font-size:0.9rem;">Current Status:</div>
                    @if($session->status == 'completed')
                        <span class="stat-badge success"><i class="fa-solid fa-check-circle me-1"></i>Completed</span>
                    @elseif($session->status == 'pending')
                        <span class="stat-badge warning"><i class="fa-solid fa-spinner fa-spin me-1"></i>In Progress</span>
                    @elseif($session->status == 'abandoned')
                        <span class="stat-badge danger"><i class="fa-solid fa-xmark me-1"></i>Abandoned</span>
                    @else
                        <span class="stat-badge secondary">{{ ucfirst($session->status) }}</span>
                    @endif
                </div>
                @if($session->status == 'abandoned')
                <div class="mt-3 p-3 rounded alert-danger" style="background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.3);font-size:0.85rem;">
                    This session was not properly completed and was marked as abandoned. This could affect the user's completion rate metrics.
                </div>
                @endif
            </div>

            <!-- Session Timeline -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-4"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i>Session Timeline</h6>
                <div class="timeline">
                    @forelse($timeline as $item)
                        <div class="timeline-item">
                            <div class="timeline-icon bg-{{ $item['color'] }}">
                                <i class="fa-solid {{ $item['icon'] }}"></i>
                            </div>
                            <div style="font-size:0.9rem;color:var(--tx);font-weight:600;">{{ $item['event'] }}</div>
                            <div style="font-size:0.75rem;color:var(--tx3);">{{ $item['time']->format('h:i:s A - M d, Y') }}</div>
                        </div>
                    @empty
                        <div class="text-muted text-center">No timeline events recorded.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Flag Modal -->
<div class="modal fade" id="flagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--bg3);color:var(--tx);border:1px solid var(--bd);border-radius:16px;">
            <div class="modal-header" style="border-bottom:1px solid var(--bd);">
                <h5 class="modal-title fw-bold">Flag Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1)"></button>
            </div>
            <form action="{{ route('admin.sessions.flag', $session->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p style="font-size:0.9rem;color:var(--tx2);">Flag this session if you detect unusual activity, AI processing errors, or to manually review extremely low scores.</p>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:0.85rem;color:var(--tx3);">Reason for Flagging</label>
                        <select name="flag_reason" class="form-select" style="background:var(--sf);border:1px solid var(--bd);color:var(--tx);" required>
                            <option value="">Select a reason...</option>
                            <option value="Extremely Low Score">Extremely Low Score</option>
                            <option value="Incomplete Interview Activity">Incomplete Interview Activity</option>
                            <option value="Unusual Activity Detected">Unusual Activity Detected</option>
                            <option value="AI Processing Error">AI Processing Error</option>
                            <option value="Inappropriate Content">Inappropriate Content</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Cancel</button>
                    <button type="submit" class="btn btn-danger" style="border-radius:8px;">Flag Session</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
