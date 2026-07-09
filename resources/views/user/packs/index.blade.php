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
    .pack-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:rgba(59,130,246,.12); color:#60a5fa; font-weight:800; font-size:.75rem; margin:0 6px 8px 0; }
    .sample-list { color:var(--tx2); font-size:.86rem; line-height:1.55; padding-left:18px; }
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

    @foreach($packs as $group => $items)
        <h5 style="color:var(--tx);font-weight:900;margin:22px 0 12px;">{{ $group }}</h5>
        <div class="pack-grid">
            @foreach($items as $pack)
                <article class="pack-card">
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
    @endforeach
</div>
@endsection
