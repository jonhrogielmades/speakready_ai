@php
    $recentSessionsForCard = $recentSessions ?? collect();
    $recentSessionsItems = $recentSessionsForCard instanceof \Illuminate\Pagination\AbstractPaginator
        ? $recentSessionsForCard->getCollection()
        : collect($recentSessionsForCard);
    $recentSessionsCardId = $recentSessionsCardId ?? 'card-recent-sessions';
    $recentSessionsTitle = $recentSessionsTitle ?? 'Recent Sessions';
    $recentSessionsSubtitle = $recentSessionsSubtitle ?? 'Review the latest completed local mock interviews.';
    $recentSessionsEmptyText = $recentSessionsEmptyText ?? 'No recent sessions found. Start interview practice when you are ready.';
    $recentSessionsActionLabel = $recentSessionsActionLabel ?? 'Review';
    $recentSessionsClientPager = (bool) ($recentSessionsClientPager ?? false);
    $recentSessionsPageSizeDesktop = max(1, (int) ($recentSessionsPageSizeDesktop ?? 6));
    $recentSessionsPageSizeMobile = max(1, (int) ($recentSessionsPageSizeMobile ?? 3));
    $recentSessionsInitialPageSize = ($serverDetectedMobile ?? $isMobile ?? false)
        ? $recentSessionsPageSizeMobile
        : $recentSessionsPageSizeDesktop;
    $recentSessionsInitialPages = max(1, (int) ceil($recentSessionsItems->count() / $recentSessionsInitialPageSize));
@endphp

<section
    id="{{ $recentSessionsCardId }}"
    class="print-card report-sessions-card btn-no-print"
    style="--report-session-accent:#06b6d4"
    @if($recentSessionsClientPager)
        data-recent-session-card
        data-client-pager="true"
        data-page-size-desktop="{{ $recentSessionsPageSizeDesktop }}"
        data-page-size-mobile="{{ $recentSessionsPageSizeMobile }}"
    @endif
>
    <div class="sr-polished-header report-sessions-header">
        <div class="sr-polished-icon report-sessions-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="min-w-0 flex-grow-1">
            <h5 class="sr-polished-title report-sessions-title">{{ $recentSessionsTitle }}</h5>
            <p class="sr-polished-subtitle report-sessions-subtitle">{{ $recentSessionsSubtitle }}</p>
        </div>
    </div>

    @if($recentSessionsItems->count() > 0)
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
                @forelse($recentSessionsItems as $session)
                    @php
                        $sessionScore = $session->score ? (int) $session->score->overall_readiness_score : null;
                        $sessionScoreLabel = $sessionScore === null ? 'No score' : $sessionScore.'%';
                        $sessionColor = $sessionScore === null ? '#64748b' : ($sessionScore >= 80 ? '#22c55e' : ($sessionScore >= 60 ? '#f59e0b' : '#ef4444'));
                        $sessionCategoryLabel = $session->category ? $session->category->title : ($session->practice_scenario ?? 'Interview');
                    @endphp
                    <tr class="sr-session-table-row" data-recent-session-entry="desktop" @if($recentSessionsClientPager && $loop->index >= $recentSessionsInitialPageSize) hidden @endif style="--session-score-color: {{ $sessionColor }};">
                        <td>{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }}</td>
                        <td><span class="report-session-category-chip">{{ $sessionCategoryLabel }}</span></td>
                        <td><span class="report-session-score-value">{{ $sessionScoreLabel }}</span></td>
                        <td class="text-end sr-session-action-cell">
                            <div class="sr-session-row-actions">
                                <a href="{{ route('user.review', $session->id) }}" class="sr-btn sr-btn-primary sr-session-review-table">{{ $recentSessionsActionLabel }}</a>
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
                        <td colspan="4" class="text-center py-4" style="color:var(--tx3)">{{ $recentSessionsEmptyText }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sr-sessions-mobile sr-session-list">
        @forelse($recentSessionsItems as $session)
            @php
                $sessionScore = $session->score ? (int) $session->score->overall_readiness_score : null;
                $sessionScoreBarValue = $sessionScore === null ? '0' : $sessionScore.'%';
                $sessionScoreLabel = $sessionScore === null ? 'No score' : $sessionScore.'%';
                $sessionColor = $sessionScore === null ? '#64748b' : ($sessionScore >= 80 ? '#22c55e' : ($sessionScore >= 60 ? '#f59e0b' : '#ef4444'));
                $sessionCategoryLabel = $session->category ? $session->category->title : ($session->practice_scenario ?? 'Interview');
            @endphp
            <div class="sr-session-card-polished" data-recent-session-entry="mobile" @if($recentSessionsClientPager && $loop->index >= $recentSessionsInitialPageSize) hidden @endif>
                <div class="sr-session-icon"><i class="fa-solid fa-briefcase"></i></div>
                <div class="sr-session-meta">
                    <div class="sr-session-title">{{ $sessionCategoryLabel }}</div>
                    <div class="sr-session-date">{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }}</div>
                </div>
                <div class="sr-session-score-stack" style="--score-color: {{ $sessionColor }}">
                    <span class="sr-session-score-pill">{{ $sessionScoreLabel }}</span>
                    <div class="sr-session-score-bar"><span style="--score-value: {{ $sessionScoreBarValue }}"></span></div>
                </div>
                <a href="{{ route('user.review', $session->id) }}" class="sr-btn sr-btn-primary sr-session-review-btn">{{ $recentSessionsActionLabel }}</a>
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
                    <p class="sr-polished-empty-text">{{ $recentSessionsEmptyText }}</p>
                </div>
            </div>
        @endforelse
    </div>

    @if($recentSessionsClientPager && $recentSessionsItems->count() > 0)
        <div class="sr-recent-session-pager" data-recent-session-pager aria-label="Recent sessions pagination">
            <button
                type="button"
                class="sr-recent-page-btn disabled"
                data-recent-page-prev
                aria-disabled="true"
                disabled
            >
                <i class="fa-solid fa-arrow-left"></i>
                Previous
            </button>
            <span class="sr-recent-page-status" data-recent-page-status aria-live="polite">
                Page 1 of {{ $recentSessionsInitialPages }}
            </span>
            <button
                type="button"
                class="sr-recent-page-btn {{ $recentSessionsInitialPages > 1 ? '' : 'disabled' }}"
                data-recent-page-next
                aria-disabled="{{ $recentSessionsInitialPages > 1 ? 'false' : 'true' }}"
                @if($recentSessionsInitialPages <= 1) disabled @endif
            >
                Next
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    @elseif(method_exists($recentSessionsForCard, 'hasPages') && $recentSessionsForCard->hasPages())
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
    @if($recentSessionsClientPager)
        <script>
        (function() {
            const initRecentSessionPager = function() {
                document.querySelectorAll('[data-recent-session-card][data-client-pager="true"]').forEach(function(card) {
                    if (card.dataset.recentPagerReady === '1') return;
                    card.dataset.recentPagerReady = '1';

                    const desktopEntries = Array.from(card.querySelectorAll('[data-recent-session-entry="desktop"]'));
                    const mobileEntries = Array.from(card.querySelectorAll('[data-recent-session-entry="mobile"]'));
                    const pager = card.querySelector('[data-recent-session-pager]');
                    const prevBtn = card.querySelector('[data-recent-page-prev]');
                    const nextBtn = card.querySelector('[data-recent-page-next]');
                    const status = card.querySelector('[data-recent-page-status]');
                    const pageSizeDesktop = Number.parseInt(card.dataset.pageSizeDesktop || '6', 10) || 6;
                    const pageSizeMobile = Number.parseInt(card.dataset.pageSizeMobile || '3', 10) || 3;
                    const mobileMedia = window.matchMedia ? window.matchMedia('(max-width: 767.98px)') : null;
                    let currentPage = 1;

                    const getPageSize = function() {
                        return document.body.classList.contains('user-mobile-shell') || (mobileMedia && mobileMedia.matches)
                            ? pageSizeMobile
                            : pageSizeDesktop;
                    };

                    const updateButton = function(button, disabled) {
                        if (!button) return;
                        button.disabled = disabled;
                        button.classList.toggle('disabled', disabled);
                        button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
                    };

                    const renderPage = function() {
                        const pageSize = getPageSize();
                        const totalItems = Math.max(desktopEntries.length, mobileEntries.length);
                        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
                        currentPage = Math.min(Math.max(currentPage, 1), totalPages);
                        const start = (currentPage - 1) * pageSize;
                        const end = start + pageSize;

                        [desktopEntries, mobileEntries].forEach(function(entries) {
                            entries.forEach(function(entry, index) {
                                entry.hidden = index < start || index >= end;
                            });
                        });

                        if (pager) {
                            pager.hidden = totalItems === 0;
                        }
                        if (status) {
                            status.textContent = `Page ${currentPage} of ${totalPages}`;
                        }
                        updateButton(prevBtn, currentPage <= 1);
                        updateButton(nextBtn, currentPage >= totalPages);
                    };

                    if (prevBtn) {
                        prevBtn.addEventListener('click', function(event) {
                            event.preventDefault();
                            if (currentPage > 1) {
                                currentPage -= 1;
                                renderPage();
                            }
                        });
                    }

                    if (nextBtn) {
                        nextBtn.addEventListener('click', function(event) {
                            event.preventDefault();
                            const totalPages = Math.max(1, Math.ceil(Math.max(desktopEntries.length, mobileEntries.length) / getPageSize()));
                            if (currentPage < totalPages) {
                                currentPage += 1;
                                renderPage();
                            }
                        });
                    }

                    if (mobileMedia?.addEventListener) {
                        mobileMedia.addEventListener('change', function() {
                            currentPage = 1;
                            renderPage();
                        });
                    } else if (mobileMedia?.addListener) {
                        mobileMedia.addListener(function() {
                            currentPage = 1;
                            renderPage();
                        });
                    }

                    renderPage();
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initRecentSessionPager, { once: true });
            } else {
                initRecentSessionPager();
            }
        })();
        </script>
    @endif
</section>
