<section class="ai-provider-status-usage" aria-labelledby="provider-status-usage-title">
    <div class="ai-provider-stats-heading">
        <div>
            <h5 id="provider-status-usage-title" class="fw-bold mb-1">
                <i class="fa-solid fa-chart-pie me-2 text-primary" aria-hidden="true"></i>Providers Status &amp; Usage
            </h5>
            <p class="mb-0">Runtime readiness and request evidence recorded today. Updated {{ now()->format('M d, Y h:i A') }}.</p>
        </div>
        <a href="{{ route('admin.ai.providers') }}" class="btn btn-outline-primary ai-provider-usage-refresh">
            <i class="fa-solid fa-rotate" aria-hidden="true"></i>
            <span>Refresh Usage</span>
        </a>
    </div>

    <div class="row g-4 mb-4 ai-provider-stats-grid" data-provider-usage-grid aria-live="polite">
        @foreach($providerStats as $provider)
            <div class="col-md-6 col-lg-4">
                <article class="premium-card h-100 position-relative ai-provider-usage-card" data-provider-key="{{ $provider->key }}">
                    @if($provider->is_primary)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">Primary</span>
                    @elseif($provider->is_fallback)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">Fallback</span>
                    @endif

                    <div class="d-flex justify-content-between align-items-center mb-3 ai-provider-usage-card-header">
                        <h5 class="fw-bold mb-0">
                            @switch($provider->key)
                                @case('openai')
                                    <i class="fa-solid fa-robot text-success me-2" aria-hidden="true"></i>
                                    @break
                                @case('gemini')
                                    <i class="fa-solid fa-star-of-life text-primary me-2" aria-hidden="true"></i>
                                    @break
                                @case('groq')
                                    <i class="fa-solid fa-bolt text-danger me-2" aria-hidden="true"></i>
                                    @break
                                @case('cohere')
                                    <i class="fa-solid fa-leaf text-success me-2" aria-hidden="true"></i>
                                    @break
                                @default
                                    <i class="fa-solid fa-microchip text-secondary me-2" aria-hidden="true"></i>
                            @endswitch
                            {{ $provider->name }}
                        </h5>

                        @switch($provider->status)
                            @case('active')
                                <span class="stat-badge success"><i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Active</span>
                                @break
                            @case('inactive')
                                <span class="stat-badge danger"><i class="fa-solid fa-circle-pause me-1" aria-hidden="true"></i>Inactive</span>
                                @break
                            @case('misconfigured')
                                <span class="stat-badge warning"><i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>Needs Attention</span>
                                @break
                            @default
                                <span class="stat-badge ai-provider-not-setup"><i class="fa-solid fa-circle-xmark me-1" aria-hidden="true"></i>Not Setup</span>
                        @endswitch
                    </div>

                    <dl class="ai-provider-metrics mb-0">
                        <div>
                            <dt>Runtime Model</dt>
                            <dd class="text-break">{{ $provider->model }}</dd>
                        </div>
                        <div>
                            <dt>Requests Today</dt>
                            <dd id="provider-{{ $provider->key }}-requests-today">{{ number_format($provider->requests_today) }}</dd>
                        </div>
                        <div>
                            <dt>Successful Today</dt>
                            <dd class="text-success" id="provider-{{ $provider->key }}-successful-today">{{ number_format($provider->successful_requests) }}</dd>
                        </div>
                        <div>
                            <dt>Failed Today</dt>
                            <dd @class(['text-danger' => $provider->failed_requests > 0]) id="provider-{{ $provider->key }}-failed-today">{{ number_format($provider->failed_requests) }}</dd>
                        </div>
                        <div>
                            <dt>Average Latency</dt>
                            <dd @class(['text-muted' => $provider->avg_response_time === null])>
                                {{ $provider->avg_response_time === null ? 'No data' : number_format($provider->avg_response_time).'ms' }}
                            </dd>
                        </div>
                        <div>
                            <dt>Success Rate</dt>
                            <dd @class([
                                'text-muted' => $provider->success_rate === null,
                                'text-success' => $provider->success_rate !== null && $provider->success_rate >= 95,
                                'text-warning' => $provider->success_rate !== null && $provider->success_rate >= 80 && $provider->success_rate < 95,
                                'text-danger' => $provider->success_rate !== null && $provider->success_rate < 80,
                            ]) id="provider-{{ $provider->key }}-success-rate">
                                {{ $provider->success_rate === null ? 'No data' : number_format($provider->success_rate, 2).'%' }}
                            </dd>
                        </div>
                        <div>
                            <dt>Last Activity</dt>
                            <dd @class(['text-muted' => $provider->last_activity_at === null]) title="{{ $provider->last_activity_at?->format('M d, Y h:i:s A') }}">
                                {{ $provider->last_activity_at?->format('h:i A') ?? 'No activity today' }}
                            </dd>
                        </div>
                    </dl>

                    @if($provider->status === 'misconfigured')
                        <p class="ai-provider-configuration-note warning mb-0">
                            The saved provider is active but its key, endpoint, or runtime model is not usable.
                        </p>
                    @elseif($provider->status === 'unconfigured')
                        <p class="ai-provider-configuration-note mb-0">
                            Add this provider to begin recording live usage.
                        </p>
                    @endif
                </article>
            </div>
        @endforeach
    </div>
</section>
