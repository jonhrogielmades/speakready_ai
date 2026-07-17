@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Feedback')

@section('content')
<style>
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    .premium-panel {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
    
    .db-filter-input { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .db-filter-input:focus, .db-filter-input:focus-within { border-color: var(--pur) !important; box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15) !important; background: var(--sf) !important; }
    .input-group.db-filter-input:focus-within { border-radius: 8px; border: 1px solid var(--pur) !important; }
    #feedback-filters .feedback-search-wrap {
        overflow: hidden;
        border: 1px solid var(--bd);
        background: var(--bg);
        border-radius: 8px;
    }
    #feedback-filters .feedback-search-wrap .input-group-text,
    #feedback-filters .feedback-search-wrap .form-control {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }
    #feedback-filters .feedback-search-wrap .input-group-text,
    #feedback-filters .feedback-search-wrap .form-control {
        border-radius: 0 !important;
    }
    .feedback-empty-state {
        display: block;
        width: 100%;
        border: 1px solid var(--bd);
        border-radius: 10px;
        background: rgba(96, 165, 250, 0.08);
        color: var(--tx3);
        font-size: 0.82rem;
        line-height: 1.45;
        padding: 14px;
        text-align: left;
    }

    @media (max-width: 1199px) {
        .feedback-history-head {
            display: block !important;
        }

        .feedback-history-head h5 {
            margin-bottom: 12px !important;
        }

        #feedback-filters {
            display: grid !important;
            grid-template-columns: 1fr;
            gap: 8px !important;
            width: 100%;
            align-items: stretch;
        }

        #feedback-filters #scenarioFilter {
            grid-column: 1 / -1;
            display: block;
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            min-height: 42px;
        }

        #feedback-filters #sortDateBtn {
            grid-column: 1 / -1;
            width: 100% !important;
            min-width: 0;
            min-height: 42px;
            padding: 8px 9px;
            font-size: 0.72rem;
            white-space: nowrap;
        }

        #feedback-filters .feedback-search-wrap {
            grid-column: 1 / -1;
            width: 100% !important;
            max-width: none !important;
            min-width: 0;
            min-height: 42px;
        }

        #feedback-filters .feedback-search-wrap .input-group-text {
            padding-left: 10px;
            padding-right: 7px;
        }

        #feedback-filters #feedbackSearch {
            min-width: 0;
            font-size: 0.76rem;
            padding-left: 4px;
        }

        #feedback-filters form {
            grid-column: 1 / -1;
            width: 100%;
        }

        #feedback-filters form .btn {
            width: 100%;
            min-height: 42px;
        }
    }

    @media (max-width: 420px) {
        #feedback-filters #sortDateBtn {
            font-size: 0.68rem;
            padding-inline: 7px;
        }

        #feedback-filters #feedbackSearch {
            font-size: 0.72rem;
        }
    }
</style>
@include('partials.page-hero-styles')

<div class="db-section active animate-fade-up">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5h8M9 3h6l1 3H8l1-3Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 6H5v15h14V6h-2" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m8 14 2 2 5-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Feedback Center
                </h4>
                <p class="sr-page-hero-subtitle">Review Philippines practice interviews, readiness scores, and answer feedback.</p>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="feedbackPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="feedbackBlue" x1="72" y1="38" x2="166" y2="112"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#feedbackPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <rect x="58" y="43" width="78" height="8" rx="4" fill="#93C5FD"/><rect x="58" y="61" width="108" height="7" rx="3.5" fill="#BAE6FD"/><rect x="58" y="78" width="88" height="7" rx="3.5" fill="#C7D2FE"/>
            <circle cx="154" cy="47" r="20" fill="url(#feedbackBlue)"/><path d="m146 47 6 6 12-14" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M61 106h34m18 0h46" stroke="#60A5FA" stroke-width="8" stroke-linecap="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>

    <div class="premium-panel">
        <div class="feedback-history-head d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h5 style="color:var(--tx);margin:0;font-weight:bold;">Feedback History</h5>
            <div id="feedback-filters" class="d-flex gap-2 flex-wrap">
                <select id="scenarioFilter" class="form-select border-0 db-filter-input" style="background:var(--bg);color:var(--tx);width:220px;border-radius:8px;">
                    <option value="">All Scenarios</option>
                    @foreach($feedbackCategories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-secondary" id="sortDateBtn" style="border-radius:8px;"><i class="fa-solid fa-arrow-down-short-wide me-1"></i> Sort by Date</button>
                @if($sessions->total() > 0)
                    <form action="{{ route('user.sessions.clear') }}" method="POST" onsubmit="return confirm('Clear all completed interview sessions? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" style="border-radius:8px;font-weight:600;">
                            <i class="fa-solid fa-trash-can me-1"></i> Clear All
                        </button>
                    </form>
                @endif
                <div class="input-group db-filter-input feedback-search-wrap" style="width:250px;">
                    <span class="input-group-text border-0" style="background:transparent;color:var(--tx3);border-radius:8px 0 0 8px;"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="feedbackSearch" class="form-control border-0 db-filter-input" placeholder="Search Feedback..." style="background:transparent;color:var(--tx);border-radius:0 8px 8px 0; outline:none; box-shadow:none !important;">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table custom-table align-middle" style="color:var(--tx); background: transparent; --bs-table-bg: transparent;" id="feedbackTable">
                <thead>
                    <tr style="border-bottom: 2px solid var(--bd); color: var(--tx3);">
                        <th class="border-0">Date</th>
                        <th class="border-0">Practice Scenario</th>
                        <th class="border-0">Score</th>
                        <th class="border-0">Rating</th>
                        <th class="border-0 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $session)
                    <tr style="border-bottom: 1px solid var(--bd);" data-scenario="{{ $session->practice_scenario ?? 'General Job Interview' }}" data-date="{{ $session->created_at->timestamp }}">
                        <td class="border-0 py-3">{{ $session->created_at->format('M d, Y') }}</td>
                        <td class="border-0 py-3 fw-bold">{{ $session->practice_scenario ?? 'General Job Interview' }}</td>
                        <td class="border-0 py-3 fw-bold">
                            @if($session->score)
                                {{ $session->score->overall_readiness_score }}%
                            @else
                                <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Score pending</span>
                            @endif
                        </td>
                        <td class="border-0 py-3">
                            @php $sc = $session->score ? $session->score->overall_readiness_score : null; @endphp
                            @if($sc === null) <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Not scored</span>
                            @elseif($sc >= 90) <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">Excellent</span>
                            @elseif($sc >= 70) <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Good</span>
                            @elseif($sc >= 50) <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">Fair</span>
                            @else <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">Needs Improvement</span>
                            @endif
                        </td>
                        <td class="border-0 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-primary btn-shine" style="border-radius: 8px; font-weight:600;">View Details</a>
                                <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this interview session? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete session" style="border-radius:8px;">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($sessions->count() == 0)
                    <tr>
                        <td colspan="5" class="border-0 py-3">
                            <span class="feedback-empty-state">No feedback available yet. Complete a Philippines practice interview to generate detailed feedback.</span>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <!-- Pagination UI -->
        <div class="mt-4 d-flex justify-content-end" id="feedbackPagination">
            {{ $sessions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('feedbackSearch');
        const scenarioFilter = document.getElementById('scenarioFilter');
        const sortBtn = document.getElementById('sortDateBtn');
        const tbody = document.querySelector('#feedbackTable tbody');
        let sortDesc = true;

        function filterTable() {
            const search = searchInput.value.toLowerCase();
            const scenario = scenarioFilter.value.toLowerCase();
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const rowScenario = (row.getAttribute('data-scenario') || '').toLowerCase();
                
                const matchesSearch = text.includes(search);
                const matchesScenario = scenario === "" || rowScenario.includes(scenario);

                if (matchesSearch && matchesScenario) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if(searchInput) searchInput.addEventListener('keyup', filterTable);
        if(scenarioFilter) scenarioFilter.addEventListener('change', filterTable);

        if(sortBtn) {
            sortBtn.addEventListener('click', function() {
                sortDesc = !sortDesc;
                sortBtn.innerHTML = sortDesc ? '<i class="fa-solid fa-arrow-down-short-wide me-1"></i> Sort by Date' : '<i class="fa-solid fa-arrow-up-wide-short me-1"></i> Sort by Date';
                
                const rows = Array.from(tbody.querySelectorAll('tr'));
                rows.sort((a, b) => {
                    const d1 = parseInt(a.getAttribute('data-date') || 0);
                    const d2 = parseInt(b.getAttribute('data-date') || 0);
                    return sortDesc ? d2 - d1 : d1 - d2;
                });
                
                rows.forEach(row => tbody.appendChild(row));
            });
        }
    });
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#feedback-filters', popover: { title: 'Filters & Search', description: 'Filter by scenario or search keywords to find a specific feedback record.', side: 'bottom', align: 'start' }},
            { element: '#feedbackTable', popover: { title: 'Interview History', description: 'Review past Philippines practice interviews, scores, ratings, and available actions.', side: 'top', align: 'center' }},
            { element: '#feedbackPagination', popover: { title: 'Pagination', description: 'Move through older interview feedback records from here.', side: 'top', align: 'center' }}
        ];

        const stepsDesktop = [
            { element: '#feedback-filters', popover: { title: 'Filters & Search', description: 'Filter by scenario or search keywords to find a specific feedback record.', side: 'bottom', align: 'end' }},
            { element: '#feedbackTable', popover: { title: 'Interview History', description: 'Review past Philippines practice interviews, scores, ratings, and available actions.', side: 'top', align: 'center' }},
            { element: '#feedbackPagination', popover: { title: 'Pagination', description: 'Move through older interview feedback records from here.', side: 'top', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_feedback',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
