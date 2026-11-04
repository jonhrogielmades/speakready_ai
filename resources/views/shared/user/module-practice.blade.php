@php $submission = $practiceSubmissions->get($activity->id); @endphp
<div class="col-12 col-md-6 mb-3">
    <div class="chapter-card h-100 d-flex flex-column">
        <h5 style="color:var(--tx); font-weight:700; margin-bottom:10px;"><i class="fa-solid fa-dumbbell text-success me-2"></i>{{ $activity->title }}</h5>
        <p style="color:var(--tx3); font-size:0.9rem;">{{ $activity->description }}</p>
        @if($submission)
            <p class="text-success fw-semibold mb-2"><i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Practice saved</p>
        @endif
        <form action="{{ route('user.modules.activities.submit', [$module, $activity]) }}" method="POST" class="mt-auto">
            @csrf
            <input type="hidden" name="practice_activity_id" value="{{ $activity->id }}">
            <label class="form-label fw-semibold" for="practiceResponse{{ $activity->id }}">Your practice response or reflection</label>
            <textarea class="form-control" id="practiceResponse{{ $activity->id }}" name="response_text" rows="5" minlength="20" maxlength="5000" required placeholder="Write your answer, then revise it after practice.">{{ old('practice_activity_id') == $activity->id ? old('response_text') : $submission?->response_text }}</textarea>
            <button type="submit" class="btn btn-outline-success w-100 mt-3">{{ $submission ? 'Update Practice' : 'Save Practice' }}</button>
        </form>
    </div>
</div>
