<?php

namespace Tests\Unit;

use App\Services\VoiceTranscriptionProofService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use Tests\TestCase;

class VoiceTranscriptionProofServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_issues_and_verifies_a_proof_bound_to_the_audio_and_clean_transcript(): void
    {
        Carbon::setTestNow('2026-10-18 12:00:00');
        $audio = UploadedFile::fake()->createWithContent('answer.webm', 'exact-audio-bytes');
        $service = new VoiceTranscriptionProofService;

        $proof = $service->issue(12, 'session-34', 56, $audio, '  teh API answer  ', ' OpenAI / Speech ');
        $payload = $service->verify($proof, '12', 'session-34', '56', $audio, 'the API answer');

        $this->assertNotNull($payload);
        $this->assertSame(1, $payload['v']);
        $this->assertSame(Carbon::now()->getTimestamp(), $payload['issued_at']);
        $this->assertSame('12', $payload['user_id']);
        $this->assertSame('session-34', $payload['session_id']);
        $this->assertSame('56', $payload['question_id']);
        $this->assertSame(hash('sha256', 'exact-audio-bytes'), $payload['audio_sha256']);
        $this->assertSame(hash('sha256', 'the API answer'), $payload['transcript_sha256']);
        $this->assertSame('openai-speech', $payload['provider']);
    }

    public function test_it_rejects_tampering_and_malformed_encrypted_payloads(): void
    {
        $audio = UploadedFile::fake()->createWithContent('answer.webm', 'audio');
        $service = new VoiceTranscriptionProofService;
        $proof = $service->issue(1, 2, 3, $audio, 'My answer', 'openai');

        $lastCharacter = substr($proof, -1);
        $tamperedProof = substr($proof, 0, -1).($lastCharacter === 'a' ? 'b' : 'a');

        $this->assertNull($service->verify($tamperedProof, 1, 2, 3, $audio, 'My answer'));
        $this->assertNull($service->verify('not-an-encrypted-token', 1, 2, 3, $audio, 'My answer'));
        $this->assertNull($service->verify(Crypt::encryptString('{bad json'), 1, 2, 3, $audio, 'My answer'));
        $this->assertNull($service->verify(Crypt::encryptString(json_encode(['v' => 1])), 1, 2, 3, $audio, 'My answer'));
    }

    public function test_it_rejects_replay_for_another_user_session_or_question(): void
    {
        $audio = UploadedFile::fake()->createWithContent('answer.webm', 'audio');
        $service = new VoiceTranscriptionProofService;
        $proof = $service->issue(1, 'session-a', 'question-a', $audio, 'My answer', 'openai');

        $this->assertNull($service->verify($proof, 2, 'session-a', 'question-a', $audio, 'My answer'));
        $this->assertNull($service->verify($proof, 1, 'session-b', 'question-a', $audio, 'My answer'));
        $this->assertNull($service->verify($proof, 1, 'session-a', 'question-b', $audio, 'My answer'));
    }

    public function test_it_rejects_changed_audio_or_transcript(): void
    {
        $audio = UploadedFile::fake()->createWithContent('answer.webm', 'original-audio');
        $changedAudio = UploadedFile::fake()->createWithContent('answer.webm', 'changed-audio');
        $service = new VoiceTranscriptionProofService;
        $proof = $service->issue(1, 2, 3, $audio, 'Original answer', 'openai');

        $this->assertNull($service->verify($proof, 1, 2, 3, $changedAudio, 'Original answer'));
        $this->assertNull($service->verify($proof, 1, 2, 3, $audio, 'Changed answer'));
    }

    public function test_it_enforces_the_configured_ttl_and_future_clock_skew_limit(): void
    {
        config()->set('services.ai_transcription.proof_ttl_seconds', 120);
        $audio = UploadedFile::fake()->createWithContent('answer.webm', 'audio');
        $service = new VoiceTranscriptionProofService;

        Carbon::setTestNow('2026-10-18 12:00:00');
        $proof = $service->issue(1, 2, 3, $audio, 'My answer', 'openai');

        Carbon::setTestNow('2026-10-18 12:02:00');
        $this->assertNotNull($service->verify($proof, 1, 2, 3, $audio, 'My answer'));

        Carbon::setTestNow('2026-10-18 12:02:01');
        $this->assertNull($service->verify($proof, 1, 2, 3, $audio, 'My answer'));

        Carbon::setTestNow('2026-10-18 12:10:00');
        $futureProof = $service->issue(1, 2, 3, $audio, 'My answer', 'openai');

        Carbon::setTestNow('2026-10-18 12:09:00');
        $this->assertNotNull($service->verify($futureProof, 1, 2, 3, $audio, 'My answer'));

        Carbon::setTestNow('2026-10-18 12:08:59');
        $this->assertNull($service->verify($futureProof, 1, 2, 3, $audio, 'My answer'));
    }

    public function test_it_rejects_blank_transcripts_when_issuing_or_verifying(): void
    {
        $audio = UploadedFile::fake()->createWithContent('answer.webm', 'audio');
        $service = new VoiceTranscriptionProofService;

        try {
            $service->issue(1, 2, 3, $audio, " \n\t ", 'openai');
            $this->fail('Issuing a proof for a blank transcript should fail.');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $proof = $service->issue(1, 2, 3, $audio, 'My answer', 'openai');

        $this->assertNull($service->verify($proof, 1, 2, 3, $audio, ''));
    }

    public function test_it_rejects_an_unreadable_audio_file_when_issuing_or_verifying(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'voice-proof-');
        $this->assertIsString($path);
        file_put_contents($path, 'audio');
        $audio = new UploadedFile($path, 'answer.webm', 'audio/webm', UPLOAD_ERR_OK, true);
        $service = new VoiceTranscriptionProofService;
        $proof = $service->issue(1, 2, 3, $audio, 'My answer', 'openai');

        unlink($path);

        $this->assertNull($service->verify($proof, 1, 2, 3, $audio, 'My answer'));

        $this->expectException(InvalidArgumentException::class);
        $service->issue(1, 2, 3, $audio, 'My answer', 'openai');
    }
}
