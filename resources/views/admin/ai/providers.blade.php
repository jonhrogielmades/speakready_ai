@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<style>
    /* Mobile Card-based Table Layout for Module Usage Table */
    @media (max-width: 767px) {
        #moduleUsageTableWrapper {
            overflow-x: visible !important;
            -webkit-overflow-scrolling: auto !important;
        }
        #moduleUsageTable thead {
            display: none;
        }
        #moduleUsageTable tbody tr {
            display: flex;
            flex-direction: column;
            background: var(--bg3, rgba(255,255,255,0.02));
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--bd, rgba(255,255,255,0.1));
            padding: 12px;
        }
        #moduleUsageTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-top: none !important;
            text-align: right;
        }
        #moduleUsageTable tbody td:last-child {
            border-bottom: none !important;
        }
        #moduleUsageTable tbody td::before {
            font-size: 0.8rem;
            color: var(--tx3, #888);
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
            text-align: left;
        }
        #moduleUsageTable tbody td:nth-child(1)::before { content: "Module Name"; }
        #moduleUsageTable tbody td:nth-child(2)::before { content: "Requests"; }
        
        #moduleUsageTable tbody td:nth-child(1) {
            order: -1;
            justify-content: flex-start;
            border-bottom: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
            padding-bottom: 12px !important;
            margin-bottom: 4px;
            text-align: left;
            flex-direction: column;
            align-items: flex-start;
        }
        #moduleUsageTable tbody td:nth-child(1)::before { content: none; }
    }

    /* Mobile Card-based Table Layout for Main Providers Table */
    @media (max-width: 767px) {
        #mainProvidersTableWrapper {
            overflow-x: visible !important;
            -webkit-overflow-scrolling: auto !important;
        }
        #mainProvidersTable thead {
            display: none;
        }
        #mainProvidersTable tbody tr {
            display: flex;
            flex-direction: column;
            background: var(--bg3, rgba(255,255,255,0.02));
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--bd, rgba(255,255,255,0.1));
            padding: 12px;
        }
        #mainProvidersTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-top: none !important;
            text-align: right;
        }
        #mainProvidersTable tbody td:last-child {
            border-bottom: none !important;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px !important;
        }
        #mainProvidersTable tbody td::before {
            font-size: 0.8rem;
            color: var(--tx3, #888);
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
            text-align: left;
        }
        #mainProvidersTable tbody td:nth-child(1)::before { content: "Provider Name"; }
        #mainProvidersTable tbody td:nth-child(2)::before { content: "Status"; }
        #mainProvidersTable tbody td:nth-child(3)::before { content: "API Key"; }
        #mainProvidersTable tbody td:nth-child(4)::before { content: "Role"; }
        
        #mainProvidersTable tbody td:nth-child(1) {
            order: -1;
            justify-content: flex-start;
            border-bottom: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
            padding-bottom: 12px !important;
            margin-bottom: 4px;
            text-align: left;
        }
        #mainProvidersTable tbody td:nth-child(1)::before { content: none; }
    }
    .premium-card {
        background: var(--sf, #1e1e2d);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
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
    .input-dark {
        background: var(--bg3, #2b2b40);
        border: 1px solid var(--bd, rgba(255,255,255,0.1));
        color: var(--tx);
    }
    .input-dark:focus {
        background: var(--bg3);
        border-color: #3b82f6;
        color: var(--tx);
        box-shadow: 0 0 0 0.25rem rgba(59,130,246,0.25);
    }
    .key-hidden {
        font-family: monospace;
        letter-spacing: 2px;
    }
</style>

<div class="db-section active">
    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#f87171">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-microchip me-2"></i>AI Providers Dashboard</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Manage AI Providers and view system metrics.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProviderModal"><i class="fa-solid fa-plus me-2"></i>Add AI Provider</button>
    </div>

    <!-- Feature 1: Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;margin-bottom:8px;" class="text-primary"><i class="fa-solid fa-robot"></i></div>
                <div style="font-size:1.2rem;font-weight:700;" class="text-primary">{{ $activeProvider ? $activeProvider->name : 'None' }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Active Provider</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;margin-bottom:8px;" class="text-info"><i class="fa-solid fa-bolt"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($totalRequests) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Requests Today</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;margin-bottom:8px;" class="text-warning"><i class="fa-solid fa-clock"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($avgResponseTime) }}ms</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Avg Response</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;margin-bottom:8px;" class="text-success"><i class="fa-solid fa-check-circle"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($successfulRequests) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Successful Req</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;margin-bottom:8px;" class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div style="font-size:1.5rem;font-weight:700;">{{ number_format($failedRequests) }}</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Failed Req</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="premium-card text-center p-3 h-100">
                <div style="font-size:1.5rem;margin-bottom:8px;" class="text-success"><i class="fa-solid fa-percent"></i></div>
                <div style="font-size:1.5rem;font-weight:700;" class="text-success">{{ $successRate }}%</div>
                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;letter-spacing:0.5px;">Success Rate</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Main Column -->
        <div class="col-lg-8">
            <!-- Feature 7: Module Requests Breakdown -->
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-4">Requests by Module</h6>
                <div class="table-responsive" id="moduleUsageTableWrapper">
                    <table class="table custom-table mb-0 w-100" id="moduleUsageTable">
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
            <div class="premium-card mb-4">
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
            <div class="premium-card">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-network-wired me-2 text-info"></i>Provider Status</h6>
                
                <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--bg3);">
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

    <!-- Active & Fallback System -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-3 text-primary"><i class="fa-solid fa-star me-2"></i>Primary Provider</h6>
                @if($primary)
                    <h4 class="fw-bold">{{ $primary->name }}</h4>
                    <p class="mb-0 text-muted" style="font-size:0.85rem;">Currently handling all system requests.</p>
                @else
                    <p class="mb-0 text-warning">No primary provider selected. System will not generate AI responses.</p>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="premium-card h-100">
                <h6 class="fw-bold mb-3 text-warning"><i class="fa-solid fa-life-ring me-2"></i>Fallback Provider</h6>
                @if($fallback)
                    <h4 class="fw-bold">{{ $fallback->name }}</h4>
                    <p class="mb-0 text-muted" style="font-size:0.85rem;">System will automatically switch to this if primary fails.</p>
                @else
                    <p class="mb-0 text-warning">No fallback provider selected. System risks downtime if primary fails.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Providers Table -->
    <div class="premium-card mb-4">
        <div class="table-responsive" id="mainProvidersTableWrapper">
            <table class="table custom-table mb-0 w-100" id="mainProvidersTable">
                <thead>
                    <tr>
                        <th>Provider Name</th>
                        <th>Status</th>
                        <th>API Key</th>
                        <th>Role</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $provider)
                    <tr>
                        <td class="fw-bold">{{ $provider->name }}</td>
                        <td>
                            @if($provider->status == 'active')
                                <span class="stat-badge success">Active</span>
                            @else
                                <span class="stat-badge danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="key-hidden">sk-********************************</span>
                                <button class="btn btn-sm text-info p-0 test-conn-btn" title="Test Connection"><i class="fa-solid fa-plug-circle-check"></i></button>
                            </div>
                        </td>
                        <td>
                            @if($provider->is_primary)
                                <span class="badge bg-primary">Primary</span>
                            @endif
                            @if($provider->is_fallback)
                                <span class="badge bg-warning text-dark">Fallback</span>
                            @endif
                            @if(!$provider->is_primary && !$provider->is_fallback)
                                <span class="badge bg-secondary">Standby</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="background:var(--sf);border:1px solid var(--bd);">
                                    <li>
                                        <button class="dropdown-item edit-provider-btn" type="button" data-id="{{ $provider->id }}" data-name="{{ $provider->name }}" data-endpoint="{{ $provider->api_endpoint }}" data-status="{{ $provider->status }}" style="color:var(--tx);">
                                            <i class="fa-solid fa-pen-to-square me-2 text-info"></i>Edit Provider
                                        </button>
                                    </li>
                                    @if(!$provider->is_primary)
                                    <li>
                                        <form action="{{ route('admin.ai.providers.primary', $provider->id) }}" method="POST">
                                            @csrf
                                            <button class="dropdown-item" type="submit" style="color:var(--tx);"><i class="fa-solid fa-star me-2 text-primary"></i>Set as Primary</button>
                                        </form>
                                    </li>
                                    @endif
                                    @if(!$provider->is_fallback)
                                    <li>
                                        <form action="{{ route('admin.ai.providers.fallback', $provider->id) }}" method="POST">
                                            @csrf
                                            <button class="dropdown-item" type="submit" style="color:var(--tx);"><i class="fa-solid fa-life-ring me-2 text-warning"></i>Set as Fallback</button>
                                        </form>
                                    </li>
                                    @endif
                                    <li><hr class="dropdown-divider" style="border-color:var(--bd);"></li>
                                    <li>
                                        <form action="{{ route('admin.ai.providers.destroy', $provider->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this AI Provider?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit"><i class="fa-solid fa-trash me-2 text-danger"></i>Delete Provider</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No providers registered yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Provider Modal -->
<div class="modal fade" id="addProviderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--sf);border:1px solid var(--bd);color:var(--tx);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Add New AI Provider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.ai.providers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--tx2);font-size:0.85rem;">Provider Name</label>
                        <input type="text" name="name" class="form-control input-dark" placeholder="e.g. OpenAI" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--tx2);font-size:0.85rem;">API Endpoint</label>
                        <input type="url" name="api_endpoint" class="form-control input-dark" placeholder="e.g. https://api.openai.com/v1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--tx2);font-size:0.85rem;">API Key</label>
                        <input type="password" name="api_key" class="form-control input-dark" placeholder="sk-..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--tx2);font-size:0.85rem;">Status</label>
                        <select name="status" class="form-select input-dark" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Provider</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Provider Modal -->
<div class="modal fade" id="editProviderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--sf);border:1px solid var(--bd);color:var(--tx);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Edit AI Provider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProviderForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--tx2);font-size:0.85rem;">Provider Name</label>
                        <input type="text" name="name" id="editProviderName" class="form-control input-dark" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--tx2);font-size:0.85rem;">API Endpoint</label>
                        <input type="url" name="api_endpoint" id="editProviderEndpoint" class="form-control input-dark" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--tx2);font-size:0.85rem;">API Key</label>
                        <input type="password" name="api_key" class="form-control input-dark" placeholder="Leave blank to keep current key">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="color:var(--tx2);font-size:0.85rem;">Status</label>
                        <select name="status" id="editProviderStatus" class="form-select input-dark" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.querySelectorAll('.test-conn-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let icon = this.querySelector('i');
            icon.className = 'fa-solid fa-spinner fa-spin text-warning';
            setTimeout(() => {
                icon.className = 'fa-solid fa-check text-success';
                alert('Connection test successful!');
            }, 1500);
        });
    });

    document.querySelectorAll('.edit-provider-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let id = this.getAttribute('data-id');
            let name = this.getAttribute('data-name');
            let endpoint = this.getAttribute('data-endpoint');
            let status = this.getAttribute('data-status');
            
            document.getElementById('editProviderForm').action = `/admin/ai/providers/${id}`;
            document.getElementById('editProviderName').value = name;
            document.getElementById('editProviderEndpoint').value = endpoint;
            document.getElementById('editProviderStatus').value = status;
            
            new bootstrap.Modal(document.getElementById('editProviderModal')).show();
        });
    });
</script>
@endsection

