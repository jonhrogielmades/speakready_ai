@php
    $dashboardAiUsage = $aiUsageMetrics ?? ['total_requests' => 0, 'today_requests' => 0, 'average_response_time_ms' => null];
    $dashboardAiProviders = $aiProviderStatuses ?? collect();
    $averageResponseTimeMs = $dashboardAiUsage['average_response_time_ms'] ?? null;
    $averageResponseTimeLabel = $averageResponseTimeMs === null
        ? 'No data'
        : ($averageResponseTimeMs >= 1000
            ? number_format($averageResponseTimeMs / 1000, 2).'s'
            : number_format($averageResponseTimeMs).'ms');
@endphp

<div class="premium-card dashboard-panel dashboard-panel-featured mb-4" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(59,130,246,0.05) 100%);">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-4">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-microchip me-2 text-primary"></i>AI Usage Monitoring</h6>
        <a href="{{ route('admin.ai.providers') }}" class="btn btn-sm admin-dashboard-icon-action" title="View AI providers" aria-label="View AI providers" style="background:var(--bg3);color:var(--tx2);border:1px solid var(--bd);">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </a>
    </div>

    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem;">
        <span style="color:var(--tx2);">Total API Requests</span>
        <span class="fw-bold">{{ number_format($dashboardAiUsage['total_requests'] ?? 0) }}</span>
    </div>
    <div class="d-flex justify-content-between mb-3" style="font-size:0.85rem;">
        <span style="color:var(--tx2);">Today's Requests</span>
        <span class="fw-bold text-primary">{{ number_format($dashboardAiUsage['today_requests'] ?? 0) }}</span>
    </div>

    <div class="p-3 mb-3" style="background:var(--bg3);border-radius:12px;border:1px solid var(--bd);">
        <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Today's Avg Response Time</div>
        <div class="fw-bold" style="font-size:1.5rem;color:#34d399;">{{ $averageResponseTimeLabel }}</div>
    </div>

    <h6 style="font-size:0.85rem;color:var(--tx2);margin-bottom:10px;">Provider Status</h6>
    @forelse($dashboardAiProviders as $provider)
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div style="font-size:0.85rem;">
                <i class="fa-solid fa-robot me-2"></i>{{ $provider['name'] }}
                @if($provider['is_primary'])
                    <small style="color:var(--tx3);">(Primary)</small>
                @elseif($provider['is_fallback'])
                    <small style="color:var(--tx3);">(Fallback)</small>
                @endif
            </div>
            <span class="stat-badge {{ $provider['status'] === 'active' ? 'success' : ($provider['status'] === 'inactive' ? 'danger' : 'warning') }}">
                {{ $provider['status_label'] }}
            </span>
        </div>
    @empty
        <div style="font-size:0.85rem;color:var(--tx3);">No supported AI providers are available.</div>
    @endforelse
</div>
