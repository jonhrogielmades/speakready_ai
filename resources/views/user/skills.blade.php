@extends(isset($isMobile) && $isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 style="color:var(--tx);font-weight:700"><i class="fa-solid fa-tree me-2"></i>Skill Trees</h4>
            <p style="color:var(--tx3)">Unlock powerful perks by earning Skill XP in Learning Games.</p>
        </div>
        <div>
            <span class="badge bg-primary" style="font-size:14px;padding:10px 15px;border-radius:12px;">Level {{ $profile->player_level ?? 1 }}</span>
        </div>
    </div>

    <!-- Skill XP Overview -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card" style="border:none;background:var(--bg2);border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,0.05);padding:20px;text-align:center;">
                <div style="width:50px;height:50px;background:rgba(59,130,246,0.1);color:#3b82f6;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Leadership</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->leadership_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card" style="border:none;background:var(--bg2);border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,0.05);padding:20px;text-align:center;">
                <div style="width:50px;height:50px;background:rgba(16,185,129,0.1);color:#10b981;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Communication</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->communication_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card" style="border:none;background:var(--bg2);border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,0.05);padding:20px;text-align:center;">
                <div style="width:50px;height:50px;background:rgba(139,92,246,0.1);color:#8b5cf6;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-laptop-code"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Technical</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->technical_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card" style="border:none;background:var(--bg2);border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,0.05);padding:20px;text-align:center;">
                <div style="width:50px;height:50px;background:rgba(245,158,11,0.1);color:#f59e0b;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-lightbulb"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Problem Solving</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->problem_solving_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
    </div>

    <h5 style="color:var(--tx);font-weight:700;margin-bottom:20px;">Available Perks</h5>
    
    <div class="row g-4">
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
            
            <div class="col-md-6 col-lg-3">
                <div class="card perk-card h-100" style="border:1px solid {{ $isUnlocked ? $color : 'rgba(0,0,0,0.05)' }};background:var(--bg2);border-radius:16px;padding:25px;position:relative;overflow:hidden;">
                    @if($isUnlocked)
                        <div style="position:absolute;top:-10px;right:-10px;background:{{ $color }};color:#fff;font-size:12px;font-weight:bold;padding:15px 20px 5px 15px;transform:rotate(45deg);z-index:2;">
                            <i class="fa-solid fa-check" style="transform:rotate(-45deg)"></i>
                        </div>
                    @endif
                    
                    <div style="width:60px;height:60px;background:{{ $isUnlocked ? $color : 'var(--bg1)' }};color:{{ $isUnlocked ? '#fff' : $color }};border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:15px;transition:0.3s all;">
                        <i class="fa-solid {{ $perk['icon'] }}"></i>
                    </div>
                    
                    <h5 style="color:var(--tx);font-weight:700;margin-bottom:10px;">{{ $perk['name'] }}</h5>
                    <p style="color:var(--tx3);font-size:14px;min-height:45px;">{{ $perk['description'] }}</p>
                    
                    <div class="mt-auto pt-3 border-top" style="border-color:var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span style="font-size:12px;color:var(--tx3);text-transform:uppercase;letter-spacing:1px;display:block;">Cost</span>
                                <strong style="color:{{ $color }}">{{ $perk['cost'] }} XP</strong>
                            </div>
                            <div>
                                @if($isUnlocked)
                                    <button class="btn" style="background:var(--bg1);color:var(--tx3);border-radius:12px;font-weight:600;" disabled>Unlocked</button>
                                @else
                                    <button class="btn btn-unlock" data-id="{{ $id }}" data-type="{{ $perk['type'] }}" data-cost="{{ $perk['cost'] }}" style="background:{{ $canAfford ? $color : 'var(--bg1)' }};color:{{ $canAfford ? '#fff' : 'var(--tx3)' }};border-radius:12px;font-weight:600;" {{ $canAfford ? '' : 'disabled' }}>
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
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const unlockBtns = document.querySelectorAll('.btn-unlock');
    
    unlockBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const perkId = this.dataset.id;
            const perkType = this.dataset.type;
            const cost = this.dataset.cost;
            const originalText = this.innerHTML;
            
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            fetch("{{ route('user.skills.unlock') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    perk_id: perkId,
                    perk_type: perkType,
                    cost: cost
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Perk Unlocked!',
                        text: data.message,
                        background: document.documentElement.classList.contains('lm') ? '#fff' : '#1e1e1e',
                        color: document.documentElement.classList.contains('lm') ? '#000' : '#fff'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message,
                        background: document.documentElement.classList.contains('lm') ? '#fff' : '#1e1e1e',
                        color: document.documentElement.classList.contains('lm') ? '#000' : '#fff'
                    });
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    });
});
</script>
@endpush
