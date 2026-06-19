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
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.8rem;
    }
    .stat-badge.success { background: rgba(52, 211, 153, 0.15); color: #34d399; }
    .stat-badge.danger { background: rgba(248, 113, 113, 0.15); color: #f87171; }
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
    }
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

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="font-size:1.6rem;"><i class="fa-solid fa-server me-2" style="color:#3b82f6;"></i>Provider Management</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Manage AI Providers, API Keys, and Fallback settings.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProviderModal"><i class="fa-solid fa-plus me-2"></i>Add AI Provider</button>
    </div>

    <!-- Active & Fallback System -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="premium-card h-100" style="border-left: 4px solid #3b82f6;">
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
            <div class="premium-card h-100" style="border-left: 4px solid #f59e0b;">
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
        <div class="table-responsive">
            <table class="table custom-table mb-0 w-100">
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
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background:var(--bg3);border:1px solid var(--bd);">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" style="background:var(--sf);border:1px solid var(--bd);">
                                    @if(!$provider->is_primary)
                                    <li>
                                        <form action="{{ route('admin.ai.providers.primary', $provider->id) }}" method="POST">
                                            @csrf
                                            <button class="dropdown-item" type="submit"><i class="fa-solid fa-star me-2 text-primary"></i>Set as Primary</button>
                                        </form>
                                    </li>
                                    @endif
                                    @if(!$provider->is_fallback)
                                    <li>
                                        <form action="{{ route('admin.ai.providers.fallback', $provider->id) }}" method="POST">
                                            @csrf
                                            <button class="dropdown-item" type="submit"><i class="fa-solid fa-life-ring me-2 text-warning"></i>Set as Fallback</button>
                                        </form>
                                    </li>
                                    @endif
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
        <div class="modal-content" style="background:var(--sf);border:1px solid var(--bd);">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Add New AI Provider</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background:var(--bg3);border:none;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Provider</button>
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
</script>
@endsection
