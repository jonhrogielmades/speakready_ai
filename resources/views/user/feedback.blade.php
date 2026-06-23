@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 style="color:var(--tx);font-weight:700">Feedback Center</h4>
            <p style="color:var(--tx3)">Review your past interviews and AI-generated insights.</p>
        </div>
    </div>

    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h5 style="color:var(--tx);margin:0;font-weight:bold;">Feedback History</h5>
            <div class="d-flex gap-2 flex-wrap">
                <select id="categoryFilter" class="form-select border-0" style="background:var(--bg);color:var(--tx);width:200px;border-radius:8px;">
                    <option value="">All Categories</option>
                    <option value="Job Interview">Job Interview</option>
                    <option value="Scholarship Interview">Scholarship Interview</option>
                    <option value="IT Interview">IT Interview</option>
                    <option value="College Admission">College Admission</option>
                </select>
                <button class="btn btn-outline-secondary" id="sortDateBtn" style="border-radius:8px;"><i class="fa-solid fa-arrow-down-short-wide me-1"></i> Sort by Date</button>
                <div class="input-group" style="width:250px;">
                    <span class="input-group-text border-0" style="background:var(--bg);color:var(--tx3);border-radius:8px 0 0 8px;"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="feedbackSearch" class="form-control border-0" placeholder="Search Feedback..." style="background:var(--bg);color:var(--tx);border-radius:0 8px 8px 0;">
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
                        <td class="border-0 py-3 text-end"><a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-primary" style="border-radius: 8px;">View Details</a></td>
                    </tr>
                    @endforeach
                    @if($sessions->count() == 0)
                    <!-- Mock data for demonstration if DB is empty -->
                    <tr style="border-bottom: 1px solid var(--bd);" data-category="Job Interview" data-date="1718668800">
                        <td class="border-0 py-3">June 18, 2026</td>
                        <td class="border-0 py-3 fw-bold">Job Interview</td>
                        <td class="border-0 py-3 fw-bold">88%</td>
                        <td class="border-0 py-3"><span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Good</span></td>
                        <td class="border-0 py-3 text-end"><button class="btn btn-sm btn-primary" style="border-radius: 8px;">View Details</button></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <!-- Pagination UI -->
        <nav aria-label="Feedback pagination" class="mt-4">
            <ul class="pagination justify-content-end mb-0" id="feedbackPagination">
                <li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" style="background:var(--bg);color:var(--tx3);border-color:var(--bd);">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#" style="background:#3b82f6;border-color:#3b82f6;color:#fff;">1</a></li>
                <li class="page-item"><a class="page-link" href="#" style="background:var(--sf);color:var(--tx);border-color:var(--bd);">2</a></li>
                <li class="page-item"><a class="page-link" href="#" style="background:var(--sf);color:var(--tx);border-color:var(--bd);">Next</a></li>
            </ul>
        </nav>
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
@endsection
