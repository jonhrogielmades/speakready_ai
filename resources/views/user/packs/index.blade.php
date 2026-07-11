@extends(isset($isMobile) && $isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Interview Packs')

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
        #interview-packs-page .sr-page-actions {
            margin-bottom: 12px !important;
        }
        #interview-packs-page .pack-summary {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }
        #interview-packs-page .pack-stat {
            border-radius: 14px;
            padding: 11px 8px;
            min-height: 86px;
            text-align: center;
        }
        #interview-packs-page .pack-stat-label {
            font-size: 0.58rem;
            line-height: 1.18;
            letter-spacing: 0;
        }
        #interview-packs-page .pack-stat-value {
            font-size: 1.14rem;
            margin-top: 8px;
        }
        #interview-packs-page .pack-toolbar {
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        #interview-packs-page .pack-field {
            min-height: 44px;
            border-radius: 11px;
            font-size: 0.84rem;
        }
        #interview-packs-page .pack-group h5 {
            margin: 16px 0 10px !important;
            font-size: 0.98rem;
        }
        #interview-packs-page .pack-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        #interview-packs-page .pack-card {
            border-radius:14px;
            padding:14px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        #interview-packs-page .pack-card > .d-flex:first-child {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr);
            gap: 8px !important;
        }
        #interview-packs-page .pack-card h5 {
            font-size: 0.98rem;
            line-height: 1.25;
        }
        #interview-packs-page .pack-card p {
            font-size: 0.8rem !important;
            line-height: 1.45 !important;
            margin-bottom: 10px;
        }
        #interview-packs-page .pack-chip {
            padding: 6px 9px;
            font-size: 0.68rem;
            line-height: 1.15;
            margin: 0 4px 6px 0;
        }
        #interview-packs-page .sample-list {
            font-size: 0.78rem;
            line-height: 1.45;
            margin-bottom: 10px;
        }
        #interview-packs-page .pack-card .btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    }
</style>
@include('partials.page-hero-styles')

<div class="db-section active" id="interview-packs-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Interview Packs
                </h4>
                <p class="sr-page-hero-subtitle">Start with ready-made company and role simulations.</p>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="packPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="packBlue" x1="66" y1="36" x2="166" y2="118"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#packPanel)" stroke="#BFDBFE" stroke-width="3"/><path d="m111 42 54 29-54 29-54-29 54-29Z" fill="url(#packBlue)"/><path d="m57 83 54 29 54-29M57 98l54 29 54-29" fill="none" stroke="#60A5FA" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/><path d="M95 70h32" stroke="#EFF6FF" stroke-width="7" stroke-linecap="round"/><circle cx="164" cy="44" r="17" fill="#22C55E"/><path d="M157 44l5 5 10-12" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
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
