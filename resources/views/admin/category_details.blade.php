@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('content')
<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">
                <a href="{{ route('admin.categories') }}" style="color:var(--tx3);text-decoration:none;"><i class="fa-solid fa-arrow-left me-2"></i></a>
                @if($category->icon)<i class="{{ $category->icon }} me-2"></i>@endif{{ $category->title }}
            </h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">{{ $category->description ?? 'Category Details and Analytics' }}</p>
        </div>
        <div>
            @if($category->status == 'active')
                <span class="badge bg-success py-2 px-3 fs-6">Active</span>
            @else
                <span class="badge bg-secondary py-2 px-3 fs-6">Inactive</span>
            @endif
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
                <h6 style="color:var(--tx3);font-size:.85rem;margin-bottom:8px">Total Questions</h6>
                <h3 style="color:var(--tx);margin:0;font-weight:700">{{ $totalQuestions }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
                <h6 style="color:var(--tx3);font-size:.85rem;margin-bottom:8px">Total Interviews Taken</h6>
                <h3 style="color:var(--tx);margin:0;font-weight:700">{{ $totalInterviews }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
                <h6 style="color:var(--tx3);font-size:.85rem;margin-bottom:8px">Average Score</h6>
                <h3 style="color:var(--tx);margin:0;font-weight:700">{{ $averageScore }}%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
                <h6 style="color:var(--tx3);font-size:.85rem;margin-bottom:8px">Popularity Rating</h6>
                <h3 style="color:var(--tx);margin:0;font-weight:700">{{ $popularity }}/10</h3>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-size:1.1rem;margin-bottom:20px;font-weight:600">Performance Over Time</h5>
                <canvas id="barChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-size:1.1rem;margin-bottom:20px;font-weight:600">Question Types</h5>
                <canvas id="pieChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Questions Table -->
    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;overflow-x:auto;">
        <div class="d-flex justify-content-between mb-3">
            <h5 style="color:var(--tx);font-size:1.1rem;margin:0;font-weight:600">Questions in this Category</h5>
            <a href="{{ route('admin.questions') }}" class="btn btn-sm btn-outline-primary">Manage Questions</a>
        </div>
        <table class="table table-dark table-hover mb-0" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--tx)">
            <thead>
                <tr>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">ID</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Question Text</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Type</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Difficulty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($category->questions as $q)
                <tr>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ $q->id }}</td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ Str::limit($q->question_text, 70) }}</td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px"><span class="badge bg-secondary">{{ $q->type }}</span></td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        @if($q->difficulty == 'Easy') <span class="badge bg-success">Easy</span>
                        @elseif($q->difficulty == 'Medium') <span class="badge bg-warning text-dark">Medium</span>
                        @else <span class="badge bg-danger">Hard</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center" style="border-bottom:none;padding:20px;color:var(--tx3)">No questions found in this category.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Bar Chart
    const ctxBar = document.getElementById('barChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Interviews Taken',
                data: [12, 19, 15, 25, 22, {{ $totalInterviews }}],
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Pie Chart
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    
    // Count question types
    const qTypes = {
        'Behavioral': {{ $category->questions->where('type', 'Behavioral')->count() }},
        'Situational': {{ $category->questions->where('type', 'Situational')->count() }},
        'Technical': {{ $category->questions->where('type', 'Technical')->count() }},
        'Personal': {{ $category->questions->where('type', 'Personal')->count() }}
    };

    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: Object.keys(qTypes),
            datasets: [{
                data: Object.values(qTypes),
                backgroundColor: [
                    '#4bc0c0',
                    '#ffcd56',
                    '#ff6384',
                    '#c9cbcf'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: '#fff' } }
            }
        }
    });
});
</script>
@endsection
