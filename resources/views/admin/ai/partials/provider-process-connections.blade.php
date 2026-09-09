@php
    $openAiProcessConnections = collect($openAiProcessConnections ?? []);
    $liveProcessCount = $openAiProcessConnections->where('status', 'live')->count();
    $mappedProcessCount = $openAiProcessConnections->where('status', 'mapped')->count();
    $loggedProcessCount = $openAiProcessConnections
        ->filter(fn ($process) => (int) ($process['request_count'] ?? 0) > 0)
        ->count();
@endphp

<div class="premium-card mb-4 ai-process-connection-card">
    <div class="ai-process-panel-title">
        <div>
            <h6 class="fw-bold mb-1"><i class="fa-solid fa-plug-circle-check me-2 text-success"></i>OpenAI Process Connections</h6>
            <p class="mb-0">Live means OpenAI is called directly. Safeguarded means the workflow is connected to OpenAI evidence, while final scoring or review logic stays local.</p>
        </div>
        <span class="stat-badge primary">{{ $openAiProcessConnections->count() }} processes</span>
    </div>

    <div class="ai-process-summary">
        <div>
            <strong>{{ $liveProcessCount }}</strong>
            <span>Live API paths</span>
        </div>
        <div>
            <strong>{{ $mappedProcessCount }}</strong>
            <span>Safeguarded processes</span>
        </div>
        <div>
            <strong>{{ $loggedProcessCount }}</strong>
            <span>With logs</span>
        </div>
    </div>

    <div class="table-responsive" id="openAiProcessTableWrapper">
        <table class="table custom-table mb-0 w-100" id="openAiProcessTable">
            <thead>
                <tr>
                    <th>Process</th>
                    <th>Area</th>
                    <th>Connection</th>
                    <th>Status</th>
                    <th class="text-end">Logs</th>
                </tr>
            </thead>
            <tbody>
                @foreach($openAiProcessConnections as $process)
                    @php
                        $status = $process['status'] ?? 'mapped';
                        $statusClass = match ($status) {
                            'live' => 'success',
                            'mapped' => 'primary',
                            default => 'secondary',
                        };
                        $statusLabel = match ($status) {
                            'live' => 'Live',
                            'mapped' => 'Safeguarded',
                            default => ucfirst($status),
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="ai-process-name">
                                <i class="{{ $process['icon'] ?? 'fa-solid fa-microchip' }}"></i>
                                <span>{{ $process['label'] ?? $process['key'] ?? 'OpenAI Process' }}</span>
                            </div>
                            <small>{{ $process['description'] ?? '' }}</small>
                        </td>
                        <td>{{ $process['group'] ?? 'OpenAI' }}</td>
                        <td>{{ $process['connection'] ?? 'OpenAI' }}</td>
                        <td><span class="stat-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        <td class="text-end fw-bold">{{ number_format((int) ($process['request_count'] ?? 0)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
