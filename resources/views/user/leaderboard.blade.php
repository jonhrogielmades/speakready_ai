@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4">
        <h4 style="color:var(--tx);font-weight:700">Global Leaderboard</h4>
        <p style="color:var(--tx3)">See how you stack up against the community! Keep practicing to climb the ranks.</p>
    </div>

    <div class="premium-card">
        <div class="table-responsive">
            <table class="table table-borderless" style="color:var(--tx); background: transparent; --bs-table-bg: transparent; --bs-table-color: var(--tx);">
                <thead style="border-bottom: 2px solid var(--bd);">
                    <tr>
                        <th class="py-3" style="color:var(--tx2);font-weight:600;">Rank</th>
                        <th class="py-3 text-center" style="color:var(--tx2);font-weight:600;">Candidate</th>
                        <th class="py-3 text-center" style="color:var(--tx2);font-weight:600;">Total XP</th>
                        <th class="py-3 text-center" style="color:var(--tx2);font-weight:600;">Current Streak</th>
                        <th class="py-3 text-end" style="color:var(--tx2);font-weight:600;">Badges Earned</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topUsers as $index => $profile)
                    <tr style="border-bottom: 1px solid var(--bd); background-color: {{ Auth::id() == $profile->user_id ? 'rgba(59, 130, 246, 0.05)' : 'transparent' }}">
                        <td class="py-4 align-middle">
                            @if($index == 0)
                                <div style="width:35px;height:35px;background:#f59e0b;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;box-shadow:0 0 10px rgba(245, 158, 11, 0.5)"><i class="fa-solid fa-trophy"></i></div>
                            @elseif($index == 1)
                                <div style="width:35px;height:35px;background:#94a3b8;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;"><i class="fa-solid fa-medal"></i></div>
                            @elseif($index == 2)
                                <div style="width:35px;height:35px;background:#b45309;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;"><i class="fa-solid fa-medal"></i></div>
                            @else
                                <div style="width:35px;height:35px;background:var(--sf);border:1px solid var(--bd);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;color:var(--tx2)">{{ $index + 1 }}</div>
                            @endif
                        </td>
                        <td class="py-4 align-middle">
                            <div class="d-flex align-items-center justify-content-center">
                                <div class="text-center">
                                    <div style="font-weight:600;color:var(--tx)">{{ $profile->user->name ?? 'Anonymous User' }}</div>
                                    @if(Auth::id() == $profile->user_id)
                                    <div style="font-size:0.75rem;color:var(--dash-primary);font-weight:600;">You</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-4 align-middle text-center" style="font-weight:700;color:var(--dash-info);font-size:1.1rem;">
                            {{ $profile->experience_points }} XP
                        </td>
                        <td class="py-4 align-middle text-center">
                            <span class="badge" style="background:rgba(248,113,113,0.15);color:var(--dash-danger);font-size:0.85rem;"><i class="fa-solid fa-fire me-1"></i> {{ $profile->current_streak }} Days</span>
                        </td>
                        <td class="py-4 align-middle text-end">
                            @php
                                $badges = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
                                $badgeCount = count($badges);
                            @endphp
                            <div class="d-flex justify-content-end align-items-center gap-1">
                                @if($badgeCount > 0)
                                    <div style="color:var(--dash-warning);font-size:1.2rem;"><i class="fa-solid fa-medal"></i></div>
                                    <span style="font-weight:600;color:var(--tx2)">x{{ $badgeCount }}</span>
                                @else
                                    <span style="color:var(--tx3);font-size:0.85rem;">None yet</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5" style="color:var(--tx3)">No users have earned XP yet. Be the first!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
