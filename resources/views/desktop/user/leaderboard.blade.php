@extends('desktop.layouts.app')
@section('title', 'Global Leaderboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/leaderboard.css?v=1') }}" data-page-style="user-leaderboard">
@endpush

@section('content')
@include('desktop.partials.page-hero-styles')

<div class="db-section active animate-fade-up" id="leaderboard-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4h8v3a4 4 0 0 1-8 0V4Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M6 5H3v2a4 4 0 0 0 5 4M18 5h3v2a4 4 0 0 1-5 4M12 11v5M9 21h6M8 16h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Global Leaderboard
                </h4>
                <p class="sr-page-hero-subtitle">See how you stack up against the community. Keep practicing to climb the ranks.</p>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="leaderPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="leaderBlue" x1="66" y1="36" x2="166" y2="118"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#leaderPanel)" stroke="#BFDBFE" stroke-width="3"/><path d="M82 47h56v23a28 28 0 0 1-56 0V47Z" fill="url(#leaderBlue)"/><path d="M82 55H61v12a22 22 0 0 0 24 22M138 55h21v12a22 22 0 0 1-24 22" fill="none" stroke="#60A5FA" stroke-width="7" stroke-linecap="round"/><path d="M110 97v22M91 119h38" stroke="#2563EB" stroke-width="7" stroke-linecap="round"/><path d="m110 58 5 11 12 1-9 8 3 12-11-6-11 6 3-12-9-8 12-1 5-11Z" fill="#fff"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>

    <div class="premium-panel" id="leaderboard-container">
        <div class="table-responsive">
            <table class="table table-borderless" style="color:var(--tx); background: transparent; --bs-table-bg: transparent; --bs-table-color: var(--tx);">
                <thead style="border-bottom: 2px solid var(--bd);">
                    <tr>
                        <th class="py-3" style="color:var(--tx2);font-weight:600;">Rank</th>
                        <th class="py-3 text-center" style="color:var(--tx2);font-weight:600;">Candidate</th>
                        <th class="py-3 text-center" style="color:var(--tx2);font-weight:600;" id="col-xp">Total XP</th>
                        <th class="py-3 text-center" style="color:var(--tx2);font-weight:600;" id="col-streak">Current Streak</th>
                        <th class="py-3 text-end" style="color:var(--tx2);font-weight:600;" id="col-badges">Badges Earned</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topUsers as $index => $profile)
                    <tr class="leaderboard-row animate-fade-up" style="border-bottom: 1px solid var(--bd); background-color: {{ Auth::id() == $profile->user_id ? 'rgba(59, 130, 246, 0.05)' : 'transparent' }}; animation-delay: {{ $index * 0.1 }}s">
                        <td class="py-4 align-middle">
                            @if($index == 0)
                                <div class="leader-rank-badge leader-rank-gold"><i class="fa-solid fa-trophy"></i></div>
                            @elseif($index == 1)
                                <div class="leader-rank-badge leader-rank-silver"><i class="fa-solid fa-medal"></i></div>
                            @elseif($index == 2)
                                <div class="leader-rank-badge leader-rank-bronze"><i class="fa-solid fa-medal"></i></div>
                            @else
                                <div class="leader-rank-badge leader-rank-default">{{ $index + 1 }}</div>
                            @endif
                        </td>
                        <td class="py-4 align-middle">
                            <div class="d-flex align-items-center justify-content-center">
                                <div class="text-center leader-name">
                                    <div class="leader-name-main" style="font-weight:600;color:var(--tx)">{{ $profile->user->name ?? 'Anonymous User' }}</div>
                                    @if(Auth::id() == $profile->user_id)
                                    <div class="leader-you-badge" style="font-size:0.75rem;color:var(--dash-primary);font-weight:600;">You</div>
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

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#leaderboard-container', popover: { title: 'Global Leaderboard', description: 'Compare XP, streaks, and badges across the SpeakReady community.', side: 'bottom', align: 'start' }},
            { element: '#col-xp', popover: { title: 'Experience Points', description: 'XP grows when you complete interviews, modules, games, and voice practice.', side: 'bottom', align: 'center' }},
            { element: '#col-streak', popover: { title: 'Practice Streaks', description: 'Daily practice keeps your streak alive and helps you climb faster.', side: 'bottom', align: 'center' }},
            { element: '#col-badges', popover: { title: 'Badges Earned', description: 'Badges mark milestones like high scores and completed learning goals.', side: 'bottom', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#leaderboard-container', popover: { title: 'Global Leaderboard', description: 'Compare XP, streaks, and badges across the SpeakReady community.', side: 'top', align: 'start' }},
            { element: '#col-xp', popover: { title: 'Experience Points', description: 'XP grows when you complete interviews, modules, games, and voice practice.', side: 'bottom', align: 'center' }},
            { element: '#col-streak', popover: { title: 'Practice Streaks', description: 'Daily practice keeps your streak alive and helps you climb faster.', side: 'bottom', align: 'center' }},
            { element: '#col-badges', popover: { title: 'Badges Earned', description: 'Badges mark milestones like high scores and completed learning goals.', side: 'bottom', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_leaderboard',
            serverDetectedMobile: false,
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
