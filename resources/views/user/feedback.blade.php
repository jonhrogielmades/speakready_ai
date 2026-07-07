@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

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
</style>

<div class="db-section active animate-fade-up">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="text-gradient-primary" style="font-size:1.4rem;font-weight:800;margin-bottom:4px;letter-spacing:-0.5px;text-transform:uppercase;">
<i class="fa-solid fa-clipboard-check me-2"></i>Feedback Center</h4>
            <p style="color:var(--tx3)">Review your past interviews and AI-generated insights.</p>
        </div>
        <div>
        </div>
    </div>

    <div class="premium-panel">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h5 style="color:var(--tx);margin:0;font-weight:bold;">Feedback History</h5>
            <div id="feedback-filters" class="d-flex gap-2 flex-wrap">
                <select id="categoryFilter" class="form-select border-0 db-filter-input" style="background:var(--bg);color:var(--tx);width:200px;border-radius:8px;">
                    <option value="">All Categories</option>
                    <option value="Job Interview">Job Interview</option>
                    <option value="Scholarship Interview">Scholarship Interview</option>
                    <option value="IT Interview">IT Interview</option>
                    <option value="College Admission">College Admission</option>
                </select>
                <button class="btn btn-outline-secondary" id="sortDateBtn" style="border-radius:8px;"><i class="fa-solid fa-arrow-down-short-wide me-1"></i> Sort by Date</button>
                <div class="input-group db-filter-input" style="width:250px; background:var(--bg); border-radius:8px;">
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
                        <th class="border-0">Interview Type</th>
                        <th class="border-0">Score</th>
                        <th class="border-0">Rating</th>
                        <th class="border-0 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $session)
                    <tr style="border-bottom: 1px solid var(--bd);" data-category="{{ $session->category ? $session->category->title : 'Job Interview' }}" data-date="{{ $session->created_at->timestamp }}">
                        <td class="border-0 py-3">{{ $session->created_at->format('M d, Y') }}</td>
                        <td class="border-0 py-3 fw-bold">{{ $session->category ? $session->category->title : 'Job Interview' }}</td>
                        <td class="border-0 py-3 fw-bold">{{ $session->score ? $session->score->overall_readiness_score : 0 }}%</td>
                        <td class="border-0 py-3">
                            @php $sc = $session->score ? $session->score->overall_readiness_score : 0; @endphp
                            @if($sc >= 90) <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">Excellent</span>
                            @elseif($sc >= 70) <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Good</span>
                            @elseif($sc >= 50) <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">Fair</span>
                            @else <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">Needs Improvement</span>
                            @endif
                        </td>
                        <td class="border-0 py-3 text-end"><a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-primary btn-shine" style="border-radius: 8px; font-weight:600;">View Details</a></td>
                    </tr>
                    @endforeach
                    @if($sessions->count() == 0)
                    <tr>
                        <td colspan="5" class="text-center py-4" style="color:var(--tx3);font-style:italic;">No feedback available yet. Complete a mock interview to generate detailed feedback!</td>
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
        const categoryFilter = document.getElementById('categoryFilter');
        const sortBtn = document.getElementById('sortDateBtn');
        const tbody = document.querySelector('#feedbackTable tbody');
        let sortDesc = true;

        function filterTable() {
            const search = searchInput.value.toLowerCase();
            const cat = categoryFilter.value.toLowerCase();
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const rowCat = row.getAttribute('data-category').toLowerCase();
                
                const matchesSearch = text.includes(search);
                const matchesCat = cat === "" || rowCat.includes(cat);

                if (matchesSearch && matchesCat) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if(searchInput) searchInput.addEventListener('keyup', filterTable);
        if(categoryFilter) categoryFilter.addEventListener('change', filterTable);

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
        if (typeof window.driver === 'undefined') return;
        const driver = window.driver.js.driver;

        const stepsMobile = [
            { element: '#feedback-filters', popover: { title: 'Filters & Search', description: 'Quickly find past feedback by filtering by category or searching for specific keywords.', side: "bottom", align: 'start' }},
            { element: '#feedbackTable', popover: { title: 'Interview History', description: 'Review your past mock interviews, scores, and overall ratings.', side: "top", align: 'center' }},
            { element: '#feedbackPagination', popover: { title: 'Pagination', description: 'Navigate through your older interview records here.', side: "top", align: 'center' }}
        ];

        const stepsDesktop = [
            { element: '#feedback-filters', popover: { title: 'Filters & Search', description: 'Quickly find past feedback by filtering by category or searching for specific keywords.', side: "bottom", align: 'end' }},
            { element: '#feedbackTable', popover: { title: 'Interview History', description: 'Review your past mock interviews, scores, and overall ratings.', side: "top", align: 'center' }},
            { element: '#feedbackPagination', popover: { title: 'Pagination', description: 'Navigate through your older interview records here.', side: "top", align: 'end' }}
        ];

        const driverObj = driver({
            showProgress: true,
            animate: true,
            popoverClass: document.documentElement.classList.contains('lm') ? 'driverjs-theme-light' : 'driverjs-theme-dark',
            steps: ({{ $isMobile ? 'true' : 'false' }} ? stepsMobile : stepsDesktop).filter(step => step.element ? document.querySelector(step.element) : true),
            onDestroyStarted: () => {
                if (!driverObj.hasNextStep() || confirm("Are you sure you want to exit the tutorial?")) {
                    driverObj.destroy();
                    localStorage.setItem('onboarding_completed_feedback', 'true');
                }
            },
        });

        window.startOnboardingTour = function() {
            driverObj.drive();
        };

        if (!localStorage.getItem('onboarding_completed_feedback')) {
            setTimeout(() => {
                startOnboardingTour();
            }, 500);
        }
    });
</script>
@endpush
@endsection


