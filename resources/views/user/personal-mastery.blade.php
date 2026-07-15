@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Personal Mastery')
@section('content')
<style>
    .text-gradient-primary {
        background: linear-gradient(135deg, #f59e0b 0%, #3b82f6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    #personal-mastery-page .sr-page-hero {
        border-color: rgba(245, 158, 11, 0.3);
        background:
            radial-gradient(circle at 91% 31%, rgba(245, 158, 11, 0.2), transparent 27%),
            linear-gradient(110deg, rgba(245, 158, 11, 0.13), rgba(59, 130, 246, 0.055)),
            var(--sf);
    }
    #personal-mastery-page .sr-page-hero-title svg {
        color: #f59e0b;
    }
    #personal-mastery-page .sr-page-hero-subtitle {
        max-width: 760px;
    }
    #personal-mastery-page .mastery-stat-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 24px;
        height: 100%;
    }
</style>
@include('partials.page-hero-styles')

<div class="db-section active" id="personal-mastery-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M7 6H4v2a4 4 0 0 0 4 4M17 6h3v2a4 4 0 0 1-4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Personal Mastery
                </h4>
                <p class="sr-page-hero-subtitle">Private progress has replaced global ranking because scores from different roles, languages, difficulties, and practice conditions are not fairly comparable.</p>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs>
                <linearGradient id="masteryPanel" x1="34" y1="18" x2="178" y2="130" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#FEF3C7"/>
                    <stop offset="1" stop-color="#DBEAFE"/>
                </linearGradient>
                <linearGradient id="masteryGold" x1="76" y1="28" x2="158" y2="118" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#F59E0B"/>
                    <stop offset="1" stop-color="#3B82F6"/>
                </linearGradient>
            </defs>
            <rect x="34" y="22" width="152" height="106" rx="19" fill="url(#masteryPanel)" stroke="#FDE68A" stroke-width="3"/>
            <path d="M94 44h52v28c0 19-13 36-26 42-13-6-26-23-26-42V44Z" fill="url(#masteryGold)"/>
            <path d="M104 57h32v16c0 11-8 21-16 25-8-4-16-14-16-25V57Z" fill="#fff" opacity=".88"/>
            <path d="m111 76 7 7 14-18" fill="none" stroke="#F59E0B" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M58 111h94" stroke="#93C5FD" stroke-width="7" stroke-linecap="round" opacity=".8"/>
            <rect x="64" y="82" width="14" height="29" rx="7" fill="#60A5FA"/>
            <rect x="85" y="70" width="14" height="41" rx="7" fill="#38BDF8"/>
            <rect x="156" y="61" width="14" height="50" rx="7" fill="#22C55E"/>
            <circle cx="62" cy="48" r="13" fill="#F59E0B"/>
            <circle cx="169" cy="39" r="10" fill="#3B82F6"/>
            <path d="M30 135c32-10 72-10 108 0s58 8 78-4" fill="none" stroke="#FBBF24" stroke-width="5" stroke-linecap="round" opacity=".55"/>
        </svg>
    </div>
    <div class="row g-4">
        @foreach([
            ['Personal best', $personalBest.'%', 'fa-trophy', '#f59e0b'],
            ['Latest assessed', $latest.'%', 'fa-bullseye', '#3b82f6'],
            ['Growth from baseline', (($latest-$baseline) >= 0 ? '+' : '').($latest-$baseline).' pts', 'fa-arrow-trend-up', '#10b981'],
            ['Practice streak', ($profile->current_streak ?? 0).' days', 'fa-fire', '#ef4444'],
        ] as [$label,$value,$icon,$color])
            <div class="col-md-6 col-xl-3"><div class="mastery-stat-card"><i class="fa-solid {{ $icon }} mb-3" style="font-size:1.5rem;color:{{ $color }}"></i><div class="h3 fw-bold" style="color:var(--tx)">{{ $value }}</div><div style="color:var(--tx3)">{{ $label }}</div></div></div>
        @endforeach
    </div>
    <div class="p-4 mt-4" style="background:var(--sf);border:1px solid var(--bd);border-radius:16px">
        <h5 class="fw-bold" style="color:var(--tx)">What counts here?</h5>
        <p style="color:var(--tx3)">Only uncoached, score-eligible assessments and clearly labelled legacy sessions contribute. Coached practice remains visible in your history but does not change this mastery baseline.</p>
        <a class="btn btn-primary" href="{{ route('user.readiness.index') }}">Open Readiness Twin</a>
    </div>
</div>
@endsection
