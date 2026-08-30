@extends('desktop.layouts.app')
@section('title', 'Skill Trees')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/skills.css?v=1') }}" data-page-style="user-skills">
<link rel="stylesheet" href="{{ asset('css/desktop/user/skills-2.css?v=2') }}" data-page-style="user-skills-2">
@endpush

@section('content')
@include('desktop.partials.page-hero-styles')

<div class="db-section active animate-fade-up" id="skill-trees-page">
    <div class="sr-page-hero skill-tree-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="skill-hero-icon"><i class="fa-solid fa-code-branch"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 4v5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M7 14h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M7 14v4M17 14v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="12" cy="4" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="7" cy="20" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="17" cy="20" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="11" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        Skill Trees
                    </h4>
                    <p class="sr-page-hero-subtitle">Unlock perks by earning Skill XP in PH Challenges.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs>
                <linearGradient id="skillPanel" x1="36" y1="18" x2="176" y2="128" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE"/>
                    <stop offset="1" stop-color="#ECFEFF"/>
                </linearGradient>
                <linearGradient id="skillBlue" x1="64" y1="34" x2="166" y2="118" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#3B82F6"/>
                    <stop offset="1" stop-color="#06B6D4"/>
                </linearGradient>
            </defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#skillPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <path d="M110 52v30M78 104h64M78 104v15M142 104v15" stroke="#60A5FA" stroke-width="7" stroke-linecap="round"/>
            <circle cx="110" cy="45" r="20" fill="url(#skillBlue)"/>
            <circle cx="110" cy="88" r="17" fill="#38BDF8"/>
            <circle cx="78" cy="119" r="16" fill="#22C55E"/>
            <circle cx="142" cy="119" r="16" fill="#F59E0B"/>
            <path d="m101 45 6 6 13-15" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M70 69h25M126 69h25" stroke="#93C5FD" stroke-width="6" stroke-linecap="round" opacity=".8"/>
            <path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="sr-page-actions skill-tree-actions">
        <span class="badge bg-primary skill-level-pill d-inline-flex align-items-center justify-content-center" style="font-size:14px;padding:10px 15px;border-radius:12px;">Level {{ $profile->player_level ?? 1 }}</span>
        <a href="{{ route('user.learning') }}" class="btn btn-sm skill-back-link d-inline-flex align-items-center justify-content-center" style="background:var(--bg3); border:1px solid var(--bd); color:var(--tx2); border-radius:10px; font-weight:600; white-space:nowrap;">
            <i class="fa-solid fa-arrow-left me-1"></i> <span>PH Challenges</span>
        </a>
    </div>

    <!-- Skill XP Overview -->
    <div class="row g-4 mb-4 skill-xp-overview">
        <div class="col-6 col-md-3 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="card stat-card premium-panel text-center" style="border:none;padding:24px;">
                <div style="width:50px;height:50px;background:rgba(59,130,246,0.1);color:#3b82f6;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Leadership</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->leadership_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 animate-fade-up" style="animation-delay: 0.2s;">
            <div class="card stat-card premium-panel text-center" style="border:none;padding:24px;">
                <div style="width:50px;height:50px;background:rgba(16,185,129,0.1);color:#10b981;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Communication</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->communication_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 animate-fade-up" style="animation-delay: 0.3s;">
            <div class="card stat-card premium-panel text-center" style="border:none;padding:24px;">
                <div style="width:50px;height:50px;background:rgba(139,92,246,0.1);color:#8b5cf6;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-laptop-code"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Technical</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->technical_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 animate-fade-up" style="animation-delay: 0.4s;">
            <div class="card stat-card premium-panel text-center" style="border:none;padding:24px;">
                <div style="width:50px;height:50px;background:rgba(245,158,11,0.1);color:#f59e0b;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-lightbulb"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Problem Solving</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->problem_solving_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
    </div>

    <section class="perks-panel">
        <h5 class="perks-title">Available Perks</h5>

        <div class="row g-0 perk-list">
            @foreach($perks as $id => $perk)
                @php
                    $isUnlocked = $profile->hasPerk($id);
                    $colName = $perk['type'] . '_xp';
                    $userXP = $profile->$colName ?? 0;
                    $canAfford = $userXP >= $perk['cost'];

                    $color = '#3b82f6';
                    if ($perk['type'] == 'communication') $color = '#10b981';
                    if ($perk['type'] == 'technical') $color = '#8b5cf6';
                    if ($perk['type'] == 'problem_solving') $color = '#f59e0b';
                @endphp

                <div class="col-12 animate-fade-up" style="animation-delay: {{ 0.4 + ($loop->index * 0.1) }}s;">
                    <div class="card perk-card h-100 premium-panel {{ $isUnlocked ? 'is-unlocked' : '' }}" style="--perk-color: {{ $color }}; border:1px solid {{ $isUnlocked ? $color : 'rgba(0,0,0,0.05)' }} !important; padding:24px; position:relative; overflow:hidden;">
                        @if($isUnlocked)
                            <div style="position:absolute;top:-10px;right:-10px;background:{{ $color }};color:#fff;font-size:12px;font-weight:bold;padding:15px 20px 5px 15px;transform:rotate(45deg);z-index:2;">
                                <i class="fa-solid fa-check" style="transform:rotate(-45deg)"></i>
                            </div>
                        @endif

                        <div class="perk-icon" style="width:60px;height:60px;background:{{ $isUnlocked ? $color : 'var(--bg1)' }};color:{{ $isUnlocked ? '#fff' : $color }};border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:15px;transition:0.3s all;">
                            <i class="fa-solid {{ $perk['icon'] }}"></i>
                        </div>

                        <div class="perk-content">
                            <h5 style="color:var(--tx);font-weight:700;margin-bottom:10px;">{{ $perk['name'] }}</h5>
                            <p style="color:var(--tx3);font-size:14px;min-height:45px;">{{ $perk['description'] }}</p>
                        </div>

                        <div class="perk-purchase mt-auto pt-3 border-top" style="border-color:var(--border-color) !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span style="font-size:12px;color:var(--tx3);text-transform:uppercase;letter-spacing:1px;display:block;">Cost</span>
                                    <strong style="color:{{ $color }}">{{ $perk['cost'] }} XP</strong>
                                </div>
                                <div>
                                    @if($isUnlocked)
                                        <button class="btn" style="background:var(--bg1);color:var(--tx3);border-radius:12px;font-weight:600;" disabled>Unlocked</button>
                                    @else
                                        <button class="btn btn-unlock btn-shine" data-id="{{ $id }}" style="background:{{ $canAfford ? $color : 'var(--bg1)' }};color:{{ $canAfford ? '#fff' : 'var(--tx3)' }};border-radius:12px;font-weight:600;" {{ $canAfford ? '' : 'disabled' }}>
                                            <i class="fa-solid {{ $canAfford ? 'fa-unlock' : 'fa-lock' }} me-2"></i>{{ $canAfford ? 'Unlock' : 'Locked' }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const unlockBtns = document.querySelectorAll('.btn-unlock');
    const skillTheme = () => ({
        background: document.documentElement.classList.contains('lm') ? '#fff' : '#1e1e1e',
        color: document.documentElement.classList.contains('lm') ? '#000' : '#fff'
    });
    const skillAlert = (options) => {
        const payload = { ...skillTheme(), ...options };
        if (window.Swal && typeof Swal.fire === 'function') {
            return Swal.fire(payload);
        }

        console[payload.icon === 'error' ? 'error' : 'log'](`${payload.title}: ${payload.text}`);
        if (typeof window.alert === 'function') {
            window.alert(`${payload.title}: ${payload.text}`);
        }

        return Promise.resolve();
    };
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const skillJson = async (response) => {
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'We could not unlock this perk. Please refresh and try again.');
        }

        return data;
    };
    
    unlockBtns.forEach(btn => {
        btn.addEventListener('click', async function() {
            const perkId = btn.dataset.id;
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            btn.disabled = true;
            
            try {
                const token = csrfToken();
                if (!token) {
                    throw new Error('Your secure session token is missing. Please refresh the page and try again.');
                }

                const data = await fetch("{{ route('user.skills.unlock') }}", {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": token
                    },
                    body: JSON.stringify({ perk_id: perkId })
                }).then(skillJson);

                await skillAlert({
                    icon: 'success',
                    title: 'Perk Unlocked!',
                    text: data.message
                });
                window.location.reload();
            } catch (error) {
                console.error(error);
                await skillAlert({
                    icon: 'error',
                    title: 'Unlock Failed',
                    text: error.message || 'We could not unlock this perk. Please refresh and try again.'
                });
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    });
});
</script>
@endpush
