<?php

namespace Tests\Unit;

use App\Http\Controllers\InterviewController;
use App\Models\InterviewAnswer;
use ReflectionMethod;
use Tests\TestCase;

class InterviewAsrFormattingTest extends TestCase
{
    public function test_matching_verified_hybrid_transcript_is_asr_derived_but_an_edited_answer_is_not(): void
    {
        $spokenTranscript = 'i checked the queue and resolved the oldest request';
        $method = new ReflectionMethod(InterviewController::class, 'answerContentUsesAsrFormatting');
        $method->setAccessible(true);
        $controller = app(InterviewController::class);

        $matchingAnswer = $this->answer([
            'response_mode' => 'hybrid',
            'answer_text' => $spokenTranscript,
            'delivery_transcript' => $spokenTranscript,
            'voice_recording_transcription_status' => 'transcribed_verified',
        ]);
        $editedAnswer = $this->answer([
            'response_mode' => 'hybrid',
            'answer_text' => ucfirst($spokenTranscript).'.',
            'delivery_transcript' => $spokenTranscript,
            'voice_recording_transcription_status' => 'transcribed_verified',
        ]);

        $this->assertTrue($method->invoke($controller, $matchingAnswer));
        $this->assertFalse($method->invoke($controller, $editedAnswer));
    }

    private function answer(array $attributes): InterviewAnswer
    {
        $answer = new InterviewAnswer;
        $answer->setRawAttributes($attributes);

        return $answer;
    }
}
