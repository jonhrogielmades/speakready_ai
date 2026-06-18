@extends('layouts.app')

@section('content')
<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="color:var(--tx);font-weight:700">Feedback Center</h4>
            <p style="color:var(--tx3)">Review your past interviews and AI-generated insights.</p>
        </div>
    </div>

    @if($sessions->count() > 0)
    <div class="row g-4">
        @foreach($sessions as $session)
        <div class="col-md-6 col-lg-4">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;display:flex;flex-direction:column;">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="db-badge" style="background:rgba(139,92,246,.15);color:#a78bfa">{{ ucfirst($session->difficulty) }}</span>
                    <span style="font-size:.8rem;color:var(--tx3)">{{ $session->created_at->format('M d, Y') }}</span>
                </div>
                
                <h5 style="color:var(--tx);margin-bottom:8px;">{{ $session->category->name ?? 'General Interview' }}</h5>
                <p style="color:var(--tx3);font-size:.9rem;flex-grow:1;">{{ Str::limit($session->feedback->strengths ?? 'No feedback recorded.', 80) }}</p>
                
                <div class="mt-3 pt-3" style="border-top:1px solid var(--bd);display:flex;justify-content:space-between;align-items:center;">
                    <div style="color:var(--tx)">
                        <span style="font-size:1.2rem;font-weight:700;color:#34d399">{{ $session->score->overall_readiness_score ?? 0 }}</span>
                        <span style="font-size:.8rem;color:var(--tx3)">/100</span>
                    </div>
                    <a href="{{ route('user.review', $session->id) }}" class="btn btn-outline-primary btn-sm" style="border-radius:8px">View Details</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:48px;text-align:center">
        <i class="fa-solid fa-clipboard-check" style="font-size:3rem;color:var(--tx3);margin-bottom:16px;"></i>
        <h5 style="color:var(--tx)">No Feedback Found</h5>
        <p style="color:var(--tx3)">Complete your first mock interview to get AI feedback.</p>
        <a href="{{ route('interview.setup') }}" class="btn bgrd px-4 py-2 mt-3">Start Practice</a>
    </div>
    @endif
</div>
@endsection
