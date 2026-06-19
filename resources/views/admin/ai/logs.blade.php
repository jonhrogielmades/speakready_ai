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
        padding: 4px 10px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.75rem;
    }
    .stat-badge.success { background: rgba(52, 211, 153, 0.15); color: #34d399; }
    .stat-badge.danger { background: rgba(248, 113, 113, 0.15); color: #f87171; }
    .stat-badge.warning { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .custom-table th {
        color: var(--tx3, #808090);
        font-size: 0.8rem;
        text-transform: uppercase;
        border-bottom: 1px solid var(--bd);
        padding: 12px 16px;
    }
    .custom-table td {
        padding: 16px;
        border-bottom: 1px solid var(--bd);
        color: var(--tx, #e0e0e0);
        vertical-align: middle;
        font-size: 0.85rem;
    }
    .custom-table tr:hover td {
        background: rgba(255,255,255,0.02);
    }
</style>

<div class="db-section active">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-clipboard-list me-2" style="color:#60a5fa;"></i>AI Error & Usage Logs</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Track failed requests, timeout errors, and API usage.</p>
        </div>
    </div>

    <div class="premium-card mb-4">
        <div class="table-responsive">
            <table class="table custom-table mb-0 w-100">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Module</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Time (ms)</th>
                        <th>Tokens</th>
                        <th>Cost</th>
                        <th>Error Msg</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap" style="color:var(--tx2);">{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                        <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $log->module)) }}</span></td>
                        <td>{{ $log->provider_id }}</td>
                        <td>
                            @if($log->status == 'success')
                                <span class="stat-badge success">Success</span>
                            @elseif($log->status == 'timeout')
                                <span class="stat-badge warning">Timeout</span>
                            @else
                                <span class="stat-badge danger">Failed</span>
                            @endif
                        </td>
                        <td>{{ $log->response_time_ms ?? '-' }}</td>
                        <td>{{ $log->tokens_used ?? '-' }}</td>
                        <td>{{ $log->cost ? '$'.number_format($log->cost, 4) : '-' }}</td>
                        <td class="text-danger" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->error_message }}">
                            {{ $log->error_message ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No AI logs available yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
