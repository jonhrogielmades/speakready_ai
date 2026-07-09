@extends(isset($isMobile) && $isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<style>
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    .pack-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px; }
    .pack-card { background:var(--sf); border:1px solid var(--bd); border-radius:18px; padding:20px; min-height:100%; box-shadow:var(--shadow-soft, 0 10px 28px rgba(0,0,0,.12)); }
    .pack-card[hidden] { display:none !important; }
    .pack-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:rgba(59,130,246,.12); color:#60a5fa; font-weight:800; font-size:.75rem; margin:0 6px 8px 0; }
    .sample-list { color:var(--tx2); font-size:.86rem; line-height:1.55; padding-left:18px; }
    .pack-toolbar { display:grid; grid-template-columns:minmax(0,1fr) minmax(180px,240px); gap:12px; margin-bottom:20px; }
    .pack-field { width:100%; border:1px solid var(--bd); border-radius:12px; background:var(--bg3); color:var(--tx); padding:11px 13px; font-size:.9rem; }
    .pack-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:20px; }
    .pack-stat { background:var(--sf); border:1px solid var(--bd); border-radius:16px; padding:15px; }
    .pack-stat-label { color:var(--tx3); font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.02em; }
    .pack-stat-value { color:var(--tx); font-size:1.45rem; line-height:1.1; font-weight:900; margin-top:7px; }
    @media (max-width: 767px) {
        .pack-toolbar,
        .pack-summary { grid-template-columns:1fr; }
        .pack-card { border-radius:14px; padding:15px; }
    }
</style>

<div class="db-section active">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
        <div>
            <h4 class="text-gradient-primary" style="font-size:1.4rem;font-weight:800;margin-bottom:4px;text-transform:uppercase;">
                <i class="fa-solid fa-layer-group me-2"></i>Interview Packs
            </h4>
            <p style="color:var(--tx3);margin:0;">Start with ready-made company and role simulations.</p>
        </div>
        <a href="{{ route('user.applications.index') }}" class="btn btn-outline-primary align-self-start" style="border-radius:12px;font-weight:700;">
            <i class="fa-solid fa-briefcase me-1"></i>Job Tracker
        </a>
    </div>

    @php
        $flatPacks = $packs->flatten(1);
        $totalPacks = $flatPacks->count();
        $pressurePacks = $flatPacks->where('pressure_mode', true)->count();
        $roleFamilies = $flatPacks->pluck('role_family')->filter()->unique()->count();
    @endphp

    <div class="pack-summary" id="pack-summary">
        <div class="pack-stat">
            <div class="pack-stat-label">Active Packs</div>
            <div class="pack-stat-value">{{ $totalPacks }}</div>
        </div>
        <div class="pack-stat">
            <div class="pack-stat-label">Role Families</div>
            <div class="pack-stat-value">{{ $roleFamilies }}</div>
        </div>
        <div class="pack-stat">
            <div class="pack-stat-label">Pressure Packs</div>
            <div class="pack-stat-value">{{ $pressurePacks }}</div>
        </div>
    </div>

    <div class="pack-toolbar" id="pack-browser">
        <input id="packSearch" class="pack-field" type="search" placeholder="Search company, role, focus, or question...">
        <select id="packDifficulty" class="pack-field">
            <option value="">All difficulties</option>
            <option value="easy">Easy</option>
            <option value="medium">Medium</option>
            <option value="hard">Hard</option>
        </select>
    </div>

    @forelse($packs as $group => $items)
        <section class="pack-group">
        <h5 style="color:var(--tx);font-weight:900;margin:22px 0 12px;">{{ $group }}</h5>
        <div class="pack-grid">
            @foreach($items as $pack)
                @php
                    $searchText = strtolower(implode(' ', array_filter([
                        $pack->name,
                        $pack->company,
                        $pack->role_family,
                        $pack->difficulty,
                        $pack->interview_focus,
                        $pack->company_persona,
                        $pack->description,
                        implode(' ', $pack->sample_questions ?? []),
                    ])));
                @endphp
                <article class="pack-card" data-pack-card data-search="{{ $searchText }}" data-difficulty="{{ strtolower($pack->difficulty) }}">
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <div>
                            <h5 style="color:var(--tx);font-weight:900;margin:0;">{{ $pack->name }}</h5>
                            <div style="color:var(--tx3);font-size:.85rem;margin-top:4px;">{{ $pack->role_family ?: 'General' }}</div>
                        </div>
                        @if($pack->pressure_mode)
                            <span class="pack-chip" style="background:rgba(239,68,68,.12);color:#ef4444;"><i class="fa-solid fa-bolt"></i>Pressure</span>
                        @endif
                    </div>
                    <p style="color:var(--tx2);font-size:.9rem;line-height:1.6;">{{ $pack->description }}</p>
                    <div class="mb-2">
                        <span class="pack-chip">{{ ucfirst($pack->difficulty) }}</span>
                        <span class="pack-chip">{{ $pack->interview_focus }}</span>
                        @if($pack->company_persona)
                            <span class="pack-chip">{{ $pack->company_persona }}</span>
                        @endif
                    </div>
                    @if(!empty($pack->sample_questions))
                        <ul class="sample-list">
                            @foreach(array_slice($pack->sample_questions, 0, 3) as $question)
                                <li>{{ $question }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <a href="{{ route('user.packs.practice', $pack) }}" class="btn btn-primary w-100 mt-2" style="border-radius:12px;font-weight:800;">
                        <i class="fa-solid fa-play me-1"></i>Use This Pack
                    </a>
                </article>
            @endforeach
        </div>
        </section>
    @empty
        <section class="pack-card text-center py-5">
            <i class="fa-solid fa-layer-group" style="font-size:3rem;color:#60a5fa;"></i>
            <h5 style="color:var(--tx);font-weight:900;margin-top:16px;">No active packs yet</h5>
            <p style="color:var(--tx3);max-width:520px;margin:8px auto 0;">When interview packs are published, they will appear here as quick-start simulations.</p>
        </section>
    @endforelse
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('packSearch');
    const difficultyInput = document.getElementById('packDifficulty');
    const cards = Array.from(document.querySelectorAll('[data-pack-card]'));

    function filterPacks() {
        const term = (searchInput?.value || '').trim().toLowerCase();
        const difficulty = (difficultyInput?.value || '').trim().toLowerCase();

        cards.forEach(card => {
            const matchesTerm = !term || (card.dataset.search || '').includes(term);
            const matchesDifficulty = !difficulty || card.dataset.difficulty === difficulty;
            card.hidden = !(matchesTerm && matchesDifficulty);
        });

        document.querySelectorAll('.pack-group').forEach(group => {
            group.hidden = !group.querySelector('[data-pack-card]:not([hidden])');
        });
    }

    searchInput?.addEventListener('input', filterPacks);
    difficultyInput?.addEventListener('change', filterPacks);
});
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#pack-summary', popover: { title: 'Pack Library', description: 'Scan how many packs, role families, and pressure-mode simulations are ready.', side: 'bottom', align: 'start' }},
            { element: '#pack-browser', popover: { title: 'Find A Pack', description: 'Search by company, role, focus, or sample question and filter by difficulty.', side: 'bottom', align: 'start' }},
            { element: '.pack-card', popover: { title: 'Pack Details', description: 'Review difficulty, focus, persona, pressure mode, and sample questions before starting.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#pack-summary', popover: { title: 'Pack Library', description: 'Scan how many packs, role families, and pressure-mode simulations are ready.', side: 'bottom', align: 'start' }},
            { element: '#pack-browser', popover: { title: 'Find A Pack', description: 'Search by company, role, focus, or sample question and filter by difficulty.', side: 'bottom', align: 'start' }},
            { element: '.pack-card', popover: { title: 'Pack Details', description: 'Review difficulty, focus, persona, pressure mode, and sample questions before starting.', side: 'top', align: 'start' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_interview_packs',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
