@extends('layouts.admin')

@section('content')
<style>
    .premium-card {
        background: var(--sf, #1e1e2d);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .premium-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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
    .chart-container {
        position: relative;
        height: 250px;
        width: 100%;
    }
</style>

<div class="db-section active">
    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-microchip me-2" style="color:#3b82f6;"></i>AI Providers Dashboard</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Overview of AI metrics, costs, and provider status.</p>
        </div>
    </div>

    <!-- Feature 1: Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(59,130,246,0.1) 100%); border-color: rgba(59,130,246,0.3);">
                <div style="font-size:1.5rem;color:#3b82f6;margin-bottom:8px;"><i class="fa-solid fa-robot"></i></div>
                <div style="font-size:1.2rem;font-weight:700;color:#3b82f6;">{{ $activeProvider ? $activeProvider->name : 'None' }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Active Provider</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#60a5fa;margin-bottom:8px;"><i class="fa-solid fa-bolt"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($totalRequests) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Requests Today</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#fbbf24;margin-bottom:8px;"><i class="fa-solid fa-clock"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($avgResponseTime) }}ms</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Avg Response</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#34d399;margin-bottom:8px;"><i class="fa-solid fa-check-circle"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($successfulRequests) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Successful Req</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;color:#f87171;margin-bottom:8px;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($failedRequests) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Failed Req</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(52,211,153,0.1) 100%); border-color: rgba(52,211,153,0.3);">
                <div style="font-size:1.5rem;color:#34d399;margin-bottom:8px;"><i class="fa-solid fa-percent"></i></div>
                <div style="font-size:1.5rem;font-weight:700;color:#34d399;">{{ $successRate }}%</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Success Rate</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Column -->
        <div class="col-lg-8">
            <!-- Feature 7: Module Requests Breakdown -->
            <div class="premium-card h-100 mb-4">
                <h6 class="fw-bold mb-4">Requests by Module</h6>
                <div class="table-responsive">
                    <table class="table custom-table mb-0 w-100">
                        <thead>
                            <tr>
                                <th>Module Name</th>
                                <th class="text-end">Requests</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($moduleUsage as $usage)
                            <tr>
                                <td><span class="stat-badge primary">{{ ucfirst(str_replace('_', ' ', $usage->module)) }}</span></td>
                                <td class="text-end fw-bold">{{ number_format($usage->count) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No usage data available today.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Column -->
        <div class="col-lg-4">
            <!-- Feature 8: Cost Monitoring -->
            <div class="premium-card mb-4" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(251,191,36,0.05) 100%);">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-coins me-2 text-warning"></i>Cost Monitoring</h6>
                
                <div class="d-flex justify-content-between mb-3 align-items-center">
                    <span style="color:var(--tx2);">Monthly Cost</span>
                    <span class="fw-bold fs-3 text-warning">${{ number_format($monthlyCost, 2) }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;">
                    <span style="color:var(--tx2);">Daily Cost (Avg)</span>
                    <span class="fw-bold">${{ number_format($monthlyCost / max(now()->day, 1), 2) }}</span>
                </div>
            </div>
            
            <!-- Feature 6: Provider Status Monitoring -->
            <div class="premium-card mb-4">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-network-wired me-2 text-info"></i>Provider Status</h6>
                
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded" style="background:var(--bg3);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-robot text-primary"></i>
                        <span>{{ $activeProvider ? $activeProvider->name : 'N/A' }}</span>
                    </div>
                    @if($successRate > 95)
                        <span class="stat-badge success"><i class="fa-solid fa-circle fa-2xs me-1"></i> Online</span>
                    @elseif($successRate > 80)
                        <span class="stat-badge warning"><i class="fa-solid fa-circle fa-2xs me-1"></i> Slow</span>
                    @else
                        <span class="stat-badge danger"><i class="fa-solid fa-circle fa-2xs me-1"></i> Offline</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
