@extends('layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="color:var(--tx);font-weight:700">Progress Tracking</h4>
            <p style="color:var(--tx3)">Visualize your interview readiness improvement over time.</p>
        </div>
    </div>

    @if($sessions->count() > 0)
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;">Overall Readiness Trend</h5>
                <canvas id="readinessChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%">
                <h5 style="color:var(--tx);margin-bottom:20px;">Skill Breakdown</h5>
                <canvas id="skillsChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sessions = {!! json_encode($sessions) !!};
            
            // Prepare Data for Readiness Trend
            const labels = sessions.map(s => {
                const d = new Date(s.created_at);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            const scores = sessions.map(s => s.score ? s.score.overall_readiness_score : 0);
            
            new Chart(document.getElementById('readinessChart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Readiness Score',
                        data: scores,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });

            // Prepare Data for Average Skills (Radar or Bar)
            if(sessions.length > 0) {
                const latest = sessions[sessions.length - 1].score;
                if(latest) {
                    new Chart(document.getElementById('skillsChart'), {
                        type: 'radar',
                        data: {
                            labels: ['Clarity', 'Relevance', 'Grammar', 'Professionalism'],
                            datasets: [{
                                label: 'Latest Skill Levels',
                                data: [latest.clarity_score, latest.relevance_score, latest.grammar_score, latest.professionalism_score],
                                borderColor: '#34d399',
                                backgroundColor: 'rgba(52, 211, 153, 0.2)',
                                borderWidth: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                r: { min: 0, max: 100 }
                            }
                        }
                    });
                }
            }
        });
    </script>
    @else
    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:48px;text-align:center">
        <i class="fa-solid fa-chart-line" style="font-size:3rem;color:var(--tx3);margin-bottom:16px;"></i>
        <h5 style="color:var(--tx)">No Data Available Yet</h5>
        <p style="color:var(--tx3)">Complete your first mock interview to start tracking your progress.</p>
        <a href="{{ route('interview.setup') }}" class="btn bgrd px-4 py-2 mt-3">Start Practice</a>
    </div>
    @endif
</div>
@endsection
