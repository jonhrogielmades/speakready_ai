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
                'confidence' => 90,
            ],
            'pronunciation' => [
                'status' => 'measured',
                'score' => 130,
            ],
            'gop' => [
                'status' => 'measured',
                'score' => 72,
                'method' => 'custom_acoustic_gop',
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

    public function test_it_rejects_proxy_pronunciation_scores_even_when_the_payload_claims_high_reliability(): void
    {
        config([
            'services.local_speech.require_true_gop_for_score' => true,
            'services.local_speech.min_scoring_reliability' => 80,
        ]);
        $service = app(LocalSpeechAssessmentService::class);

        $assessment = $service->normalizeAssessment([
            'status' => 'measured',
            'asr' => ['status' => 'measured', 'transcript' => 'I improved the process.'],
            'pronunciation' => ['status' => 'measured', 'score' => 98, 'method' => 'local_ctc_transcript_agreement'],
            'gop' => [
                'status' => 'measured',
                'score' => 98,
                'method' => 'ctc_pronunciation_proxy_not_clinical_gop',
            ],
            'reliability' => ['score' => 95, 'band' => 'Perfect'],
        ]);

        $this->assertNull($service->scoreFrom($assessment));
        $this->assertSame('High', $assessment['reliability']['band']);
    }

    public function test_it_rejects_real_gop_score_when_overall_evidence_is_below_threshold(): void
    {
        config([
            'services.local_speech.require_true_gop_for_score' => true,
            'services.local_speech.min_scoring_reliability' => 80,
        ]);
        $service = app(LocalSpeechAssessmentService::class);

        $assessment = $service->normalizeAssessment([
            'status' => 'partial',
            'gop' => ['status' => 'measured', 'score' => 91, 'method' => 'custom_acoustic_gop'],
            'reliability' => ['score' => 60],
        ]);

        $this->assertNull($service->scoreFrom($assessment));
    }

    public function test_it_rejects_low_confidence_local_transcript(): void
    {
        config(['services.local_speech.min_transcript_confidence' => 60]);
        $service = app(LocalSpeechAssessmentService::class);

        $assessment = $service->normalizeAssessment([
            'status' => 'measured',
            'asr' => [
                'status' => 'measured',
                'transcript' => 'An uncertain local transcript.',
                'confidence' => 42,
            ],
        ]);

        $this->assertNull($service->transcriptFrom($assessment));
    }
}
