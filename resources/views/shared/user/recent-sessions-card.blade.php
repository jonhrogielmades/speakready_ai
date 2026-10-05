@php
    $recentSessionsForCard = $recentSessions ?? collect();
@endphp

<section id="card-recent-sessions" class="print-card report-sessions-card btn-no-print" style="--report-session-accent:#06b6d4">
    <div class="sr-polished-header report-sessions-header">
        <div class="sr-polished-icon report-sessions-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="min-w-0 flex-grow-1">
            <h5 class="sr-polished-title report-sessions-title">Recent Sessions</h5>
            <p class="sr-polished-subtitle report-sessions-subtitle">Review the latest completed local mock interviews.</p>
        </div>
    </div>

    @if($recentSessionsForCard->count() > 0)
        <div class="sr-section-actions report-session-actions">
            <form action="{{ route('user.sessions.clear') }}" method="POST" data-sr-confirm-form data-sr-confirm-title="Clear all sessions?" data-sr-confirm-message="This will permanently clear all completed interview sessions. This cannot be undone." data-sr-confirm-action="Clear All" data-sr-confirm-variant="danger">
                @csrf
                @method('DELETE')
                <button type="submit" class="sr-btn sr-section-action danger">
                    <i class="fa-solid fa-trash-can"></i> Clear All
                </button>
            </form>
        </div>
    @endif

    <div class="table-responsive sr-sessions-table">
        <table class="table custom-table mb-0 w-100">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Score</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentSessionsForCard as $session)
                    @php
                        $sessionScore = $session->score ? (int) $session->score->overall_readiness_score : null;
                        $sessionScoreLabel = $sessionScore === null ? 'No score' : $sessionScore.'%';
                        $sessionColor = $sessionScore === null ? '#64748b' : ($sessionScore >= 80 ? '#22c55e' : ($sessionScore >= 60 ? '#f59e0b' : '#ef4444'));
                    @endphp
                    <tr style="--session-score-color: {{ $sessionColor }};">
                        <td>{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }}</td>
                        <td><span class="report-session-category-chip">{{ $session->category ? $session->category->title : 'Interview' }}</span></td>
                        <td><span class="report-session-score-value">{{ $sessionScoreLabel }}</span></td>
                        <td class="text-end sr-session-action-cell">
                            <div class="sr-session-row-actions">
                                <a href="{{ route('user.review', $session->id) }}" class="sr-btn sr-btn-primary sr-session-review-table">Review</a>
                                <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" data-sr-confirm-form data-sr-confirm-title="Delete this session?" data-sr-confirm-message="This interview session and its saved feedback will be permanently deleted." data-sr-confirm-action="Delete Session" data-sr-confirm-variant="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sr-btn sr-session-delete-table" title="Delete session" aria-label="Delete session from {{ $session->created_at ? $session->created_at->format('M d, Y') : 'recent sessions' }}">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4" style="color:var(--tx3)">No recent sessions found. Start interview practice when you are ready.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sr-sessions-mobile sr-session-list">
        @forelse($recentSessionsForCard as $session)
            @php
                $sessionScore = $session->score ? (int) $session->score->overall_readiness_score : null;
                $sessionScoreBarValue = $sessionScore === null ? '0' : $sessionScore.'%';
                $sessionScoreLabel = $sessionScore === null ? 'No score' : $sessionScore.'%';
                $sessionColor = $sessionScore === null ? '#64748b' : ($sessionScore >= 80 ? '#22c55e' : ($sessionScore >= 60 ? '#f59e0b' : '#ef4444'));
            @endphp
            <div class="sr-session-card-polished">
                <div class="sr-session-icon"><i class="fa-solid fa-briefcase"></i></div>
                <div class="sr-session-meta">
                    <div class="sr-session-title">{{ $session->category ? $session->category->title : 'Interview' }}</div>
                    <div class="sr-session-date">{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }}</div>
                </div>
                <div class="sr-session-score-stack" style="--score-color: {{ $sessionColor }}">
                    <span class="sr-session-score-pill">{{ $sessionScoreLabel }}</span>
                    <div class="sr-session-score-bar"><span style="--score-value: {{ $sessionScoreBarValue }}"></span></div>
                </div>
                <a href="{{ route('user.review', $session->id) }}" class="sr-btn sr-btn-primary sr-session-review-btn">Review</a>
                <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" data-sr-confirm-form data-sr-confirm-title="Delete this session?" data-sr-confirm-message="This interview session and its saved feedback will be permanently deleted." data-sr-confirm-action="Delete Session" data-sr-confirm-variant="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="sr-btn sr-session-delete-btn" title="Delete session" aria-label="Delete session from {{ $session->created_at ? $session->created_at->format('M d, Y') : 'recent sessions' }}">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </form>
            </div>
        @empty
            <div class="sr-polished-empty">
                <div class="sr-polished-empty-inner">
                    <div class="sr-empty-visual"><i class="fa-solid fa-calendar-plus"></i></div>
                    <p class="sr-polished-empty-text">No recent sessions found. Start interview practice when you are ready.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if(method_exists($recentSessionsForCard, 'hasPages') && $recentSessionsForCard->hasPages())
        <div class="sr-recent-session-pager" aria-label="Recent sessions pagination">
            <a
                href="{{ $recentSessionsForCard->previousPageUrl() ?: '#' }}"
                class="sr-recent-page-btn {{ $recentSessionsForCard->onFirstPage() ? 'disabled' : '' }}"
                aria-disabled="{{ $recentSessionsForCard->onFirstPage() ? 'true' : 'false' }}"
                @if($recentSessionsForCard->onFirstPage()) tabindex="-1" @endif
            >
                <i class="fa-solid fa-arrow-left"></i>
                Previous
            </a>
            <span class="sr-recent-page-status">
                Page {{ $recentSessionsForCard->currentPage() }} of {{ $recentSessionsForCard->lastPage() }}
            </span>
            <a
                href="{{ $recentSessionsForCard->nextPageUrl() ?: '#' }}"
                class="sr-recent-page-btn {{ $recentSessionsForCard->hasMorePages() ? '' : 'disabled' }}"
                aria-disabled="{{ $recentSessionsForCard->hasMorePages() ? 'false' : 'true' }}"
                @unless($recentSessionsForCard->hasMorePages()) tabindex="-1" @endunless
            >
                Next
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    @endif
</section>
