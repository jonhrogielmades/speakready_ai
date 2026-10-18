<div class="premium-card dashboard-panel mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold m-0"><i class="fa-solid fa-arrows-rotate me-2 text-success"></i>Recent User Updates</h6>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm mobile-action-btn admin-dashboard-mini-action" style="border-radius:8px;border:1px solid var(--bd);color:var(--tx2);background:var(--bg3);">
            <i class="fa-solid fa-users"></i>Manage Users
        </a>
    </div>

    <div class="d-flex flex-column gap-3">
        @forelse(($recentUserUpdates ?? collect()) as $activity)
            <div class="d-flex justify-content-between align-items-start gap-3 p-3" style="background:var(--bg3);border:1px solid var(--bd);border-radius:12px;">
                <div class="d-flex align-items-start gap-3 min-w-0">
                    <div style="width:36px;height:36px;flex:0 0 36px;border-radius:50%;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem;">
                        {{ strtoupper(substr($activity->user?->name ?? 'U', 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                            <span class="fw-semibold">{{ $activity->user?->name ?? 'Deleted user' }}</span>
                            <span class="stat-badge primary">{{ \Illuminate\Support\Str::headline($activity->action) }}</span>
                        </div>
                        <p class="mb-0" style="font-size:0.86rem;color:var(--tx2);">{{ $activity->description ?: \Illuminate\Support\Str::headline($activity->action) }}</p>
                    </div>
                </div>
                <div class="text-end" style="flex:0 0 auto;">
                    <div style="font-size:0.75rem;color:var(--tx3);">{{ $activity->created_at?->diffForHumans() ?? 'Unknown time' }}</div>
                    @if($activity->user && ! $activity->user->trashed())
                        <a href="{{ route('admin.users.show', $activity->user) }}" class="btn btn-sm mt-2 admin-dashboard-icon-action" title="View user" aria-label="View {{ $activity->user->name }}" style="background:var(--sf);color:var(--tx2);border:1px solid var(--bd);">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-3" style="color:var(--tx3);">No recent user updates found.</div>
        @endforelse
    </div>
</div>
