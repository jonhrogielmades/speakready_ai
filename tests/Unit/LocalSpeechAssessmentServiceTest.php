<?php

namespace Tests\Unit;

use App\Services\LocalSpeechAssessmentService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LocalSpeechAssessmentServiceTest extends TestCase
{
    public function test_disabled_local_speech_pipeline_returns_not_measured(): void
    {
        config(['services.local_speech.enabled' => false]);

        $assessment = app(LocalSpeechAssessmentService::class)->assessUploadedAudio(
            UploadedFile::fake()->create('speech.webm', 4, 'audio/webm'),
            'I resolved the customer issue.'
        );

        $this->assertSame('not_measured', $assessment['status']);
        $this->assertSame('local_speech_disabled', $assessment['reason']);
        $this->assertNull(app(LocalSpeechAssessmentService::class)->transcriptFrom($assessment));
        $this->assertNull(app(LocalSpeechAssessmentService::class)->scoreFrom($assessment));
    }

    public function test_it_normalizes_local_speech_payload_and_extracts_score(): void
    {
        $service = app(LocalSpeechAssessmentService::class);

        $assessment = $service->normalizeAssessment([
            'status' => 'partial',
            'asr' => [
                'status' => 'measured',
                'provider' => 'whisper',
                'transcript' => 'I improved the process.',
            ],
            'pronunciation' => [
                'status' => 'measured',
                'score' => 130,
            ],
            'gop' => [
                'status' => 'measured',
                'score' => 72,
            ],
            'reliability' => [
                'score' => 88,
                'measured_components' => ['asr', 'gop'],
            ],
        ]);

        $this->assertSame(LocalSpeechAssessmentService::VERSION, $assessment['version']);
        $this->assertSame('partial', $assessment['status']);
        $this->assertSame(100, $assessment['pronunciation']['score']);
        $this->assertSame(72, $service->scoreFrom($assessment));
        $this->assertSame('I improved the process.', $service->transcriptFrom($assessment));
        $this->assertSame('High', $assessment['reliability']['band']);
    }

    public function test_reliability_score_is_not_treated_as_pronunciation_score(): void
    {
        $service = app(LocalSpeechAssessmentService::class);

        $assessment = $service->normalizeAssessment([
            'status' => 'partial',
            'asr' => ['status' => 'measured', 'transcript' => 'Clear answer.'],
            'pronunciation' => ['status' => 'not_measured'],
            'gop' => ['status' => 'not_measured'],
            'reliability' => [
                'score' => 82,
                'measured_components' => ['asr'],
            ],
        ]);

        $this->assertNull($service->scoreFrom($assessment));
        $this->assertSame(82, $assessment['reliability']['score']);
    }
}
