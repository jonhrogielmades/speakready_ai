<div class="db-section active animate-fade-up">
 <a href="{{ route('user.feedback') }}" class="btn btn-link text-decoration-none p-0 mb-3"><i class="fa-solid fa-arrow-left me-2"></i>Back to Feedback Center</a>
 <h4 class="mb-3">Detailed Review</h4>
 <div class="alert alert-warning" role="status">
 <strong>AI review pending</strong>
 <p class="mb-3">Your answers are saved. The AI provider has not returned a validated review yet, so scores and coaching are unavailable.</p>
 <a class="btn btn-outline-dark" href="{{ route('user.review', $sessionRecord->id) }}"><i class="fa-solid fa-rotate me-2"></i>Retry AI Review</a>
 </div>
 <h5 class="mt-4 mb-3">Saved answers</h5>
 @forelse($sessionRecord->answers as $index => $answer)
 <section class="mb-3">
 <strong>Question {{ $index + 1 }}: {{ $answer->question?->question_text ?: 'Question text unavailable.' }}</strong>
 <p class="mt-2 mb-0">{{ trim((string) $answer->answer_text) ?: (trim((string) $answer->voice_recording_path) !== '' ? 'Voice answer saved; transcript unavailable.' : 'No answer text was saved.') }}</p>
 @include('shared.partials.interview-answer-voice-recording', ['answer' => $answer])
 </section>
 @empty
 <p>No answers were saved for this session.</p>
 @endforelse
</div>
