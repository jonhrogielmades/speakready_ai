@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Personal Mastery')
@section('content')
<div class="db-section active">
    <div class="p-4 mb-4" style="background:linear-gradient(120deg,rgba(245,158,11,.14),rgba(59,130,246,.08)),var(--sf);border:1px solid var(--bd);border-radius:18px">
        <span class="badge text-bg-warning mb-2">Private progress</span>
        <h3 class="fw-bold" style="color:var(--tx)">Personal Mastery</h3>
        <p class="mb-0" style="color:var(--tx3)">Global ranking has been retired because scores from different roles, languages, difficulties, and practice conditions are not fairly comparable.</p>
    </div>
    <div class="row g-4">
        @foreach([
            ['Personal best', $personalBest.'%', 'fa-trophy', '#f59e0b'],
            ['Latest assessed', $latest.'%', 'fa-bullseye', '#3b82f6'],
            ['Growth from baseline', (($latest-$baseline) >= 0 ? '+' : '').($latest-$baseline).' pts', 'fa-arrow-trend-up', '#10b981'],
            ['Practice streak', ($profile->current_streak ?? 0).' days', 'fa-fire', '#ef4444'],
        ] as [$label,$value,$icon,$color])
            <div class="col-md-6 col-xl-3"><div class="p-4 h-100" style="background:var(--sf);border:1px solid var(--bd);border-radius:16px"><i class="fa-solid {{ $icon }} mb-3" style="font-size:1.5rem;color:{{ $color }}"></i><div class="h3 fw-bold" style="color:var(--tx)">{{ $value }}</div><div style="color:var(--tx3)">{{ $label }}</div></div></div>
        @endforeach
    </div>
    <div class="p-4 mt-4" style="background:var(--sf);border:1px solid var(--bd);border-radius:16px">
        <h5 class="fw-bold" style="color:var(--tx)">What counts here?</h5>
        <p style="color:var(--tx3)">Only uncoached, score-eligible assessments and clearly labelled legacy sessions contribute. Coached practice remains visible in your history but does not change this mastery baseline.</p>
        <a class="btn btn-primary" href="{{ route('user.readiness.index') }}">Open Readiness Twin</a>
    </div>
</div>
@endsection
