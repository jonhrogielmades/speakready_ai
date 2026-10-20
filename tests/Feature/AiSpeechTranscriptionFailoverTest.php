<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\AiProviderLog;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSpeechTranscriptionFailoverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai_transcription.provider_priority' => 'openai,gemini',
            'services.openai.transcription_model' => 'gpt-transcribe',
            'services.gemini.transcription_model' => 'gemini-3.6-flash',
        ]);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);
        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-gemini-key'),
            'status' => 'active',
        ]);
    }

    public function test_empty_primary_transcription_falls_back_to_the_next_provider(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'api.openai.com/v1/audio/transcriptions')) {
                return Http::response(['text' => ''], 200);
            }

            return Http::response($this->geminiResponse('Secondary provider transcript.'), 200);
        });

        $result = AIService::transcribeSpeechResult($this->audioFile());

        $this->assertSame('Secondary provider transcript.', $result['transcript']);
        $this->assertSame('gemini', $result['provider']);
        $this->assertSame('transcribed', $result['status']);
        $this->assertSame(['openai', 'gemini'], $result['attempted_providers']);
        Http::assertSentCount(2);
        $this->assertDatabaseHas('ai_provider_logs', [
            'module' => 'speech_transcription',
            'endpoint' => 'openai',
            'status' => 'empty',
        ]);
        $this->assertDatabaseHas('ai_provider_logs', [
            'module' => 'speech_transcription',
            'endpoint' => 'gemini',
            'status' => 'success',
        ]);
    }

    public function test_all_successful_empty_transcriptions_return_an_empty_status(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'api.openai.com/v1/audio/transcriptions')) {
                return Http::response(['text' => ''], 200);
            }

            return Http::response($this->geminiResponse(''), 200);
        });

        $result = AIService::transcribeSpeechResult($this->audioFile());

        $this->assertSame('', $result['transcript']);
        $this->assertSame('openai', $result['provider']);
        $this->assertSame('empty', $result['status']);
        $this->assertSame(['openai', 'gemini'], $result['attempted_providers']);
        Http::assertSentCount(2);
        $this->assertDatabaseCount('ai_provider_logs', 2);
        $this->assertSame(
            2,
            AiProviderLog::where('module', 'speech_transcription')->where('status', 'empty')->count()
        );
    }

    public function test_all_failed_transcription_providers_return_a_failed_status(): void
    {
        Http::fake(fn () => Http::response([
            'error' => ['message' => 'Provider rejected the request.'],
        ], 401));

        $result = AIService::transcribeSpeechResult($this->audioFile());

        $this->assertSame('', $result['transcript']);
        $this->assertSame('gemini', $result['provider']);
        $this->assertSame('failed', $result['status']);
        $this->assertSame('failed', $result['error_code']);
        $this->assertSame(401, $result['error_status']);
        $this->assertSame(['openai', 'gemini'], $result['attempted_providers']);
        Http::assertSentCount(2);
    }

    public function test_malformed_gemini_output_is_not_accepted_as_a_transcript(): void
    {
        config(['services.ai_transcription.provider_priority' => 'gemini']);
        Http::fake(fn () => Http::response([
            'candidates' => [[
                'finishReason' => 'STOP',
                'content' => [
                    'parts' => [[
                        'text' => 'Here is a polished answer that was not valid transcription JSON.',
                    ]],
                ],
            ]],
        ], 200));

        $result = AIService::transcribeSpeechResult($this->audioFile());

        $this->assertSame('', $result['transcript']);
        $this->assertSame('gemini', $result['provider']);
        $this->assertSame('failed', $result['status']);
        $this->assertSame('failed', $result['error_code']);
        $this->assertDatabaseHas('ai_provider_logs', [
            'module' => 'speech_transcription',
            'endpoint' => 'gemini',
            'status' => 'failed',
        ]);
    }

    public function test_openai_success_status_with_non_json_body_is_not_accepted_as_a_transcript(): void
    {
        config(['services.ai_transcription.provider_priority' => 'openai']);
        Http::fake(fn () => Http::response(
            '<html><body>Upstream proxy error</body></html>',
            200,
            ['Content-Type' => 'text/html']
        ));

        $result = AIService::transcribeSpeechResult($this->audioFile());

        $this->assertSame('', $result['transcript']);
        $this->assertSame('openai', $result['provider']);
        $this->assertSame('failed', $result['status']);
        $this->assertDatabaseHas('ai_provider_logs', [
            'module' => 'speech_transcription',
            'endpoint' => 'openai',
            'status' => 'failed',
        ]);
    }

    public function test_truncated_gemini_transcription_is_rejected_even_when_its_json_is_valid(): void
    {
        config(['services.ai_transcription.provider_priority' => 'gemini']);
        Http::fake(fn () => Http::response([
            'candidates' => [[
                'finishReason' => 'MAX_TOKENS',
                'content' => ['parts' => [[
                    'text' => json_encode(['transcript' => 'Partial but syntactically valid transcript.']),
                ]]],
            ]],
        ], 200));

        $result = AIService::transcribeSpeechResult($this->audioFile());

        $this->assertSame('', $result['transcript']);
        $this->assertSame('gemini', $result['provider']);
        $this->assertSame('failed', $result['status']);
    }

    public function test_clean_gemini_transcription_concatenates_all_text_parts(): void
    {
        config(['services.ai_transcription.provider_priority' => 'gemini']);
        Http::fake(fn () => Http::response([
            'candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['parts' => [
                    ['text' => '{"transcript":"Complete multipart'],
                    ['text' => ' transcription."}'],
                ]],
            ]],
        ], 200));

        $result = AIService::transcribeSpeechResult($this->audioFile());

        $this->assertSame('Complete multipart transcription.', $result['transcript']);
        $this->assertSame('gemini', $result['provider']);
        $this->assertSame('transcribed', $result['status']);
    }

    public function test_openai_retry_reopens_the_audio_stream_after_a_transient_failure(): void
    {
        config([
            'services.ai_transcription.provider_priority' => 'openai',
            'services.ai_transcription.http_attempts' => 2,
        ]);
        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::sequence()
                ->push(['error' => ['message' => 'temporary outage']], 503)
                ->push(['text' => 'Recovered transcription.'], 200),
        ]);

        $result = AIService::transcribeSpeechResult($this->audioFile());

        $this->assertSame('Recovered transcription.', $result['transcript']);
        $this->assertSame('openai', $result['provider']);
        $this->assertSame('transcribed', $result['status']);
        Http::assertSentCount(2);

        foreach (Http::recorded() as [$request]) {
            $filePart = collect($request->data())
                ->first(fn ($part): bool => is_array($part) && ($part['name'] ?? null) === 'file');

            $this->assertIsArray($filePart);
            $this->assertSame('speech.webm', $filePart['filename'] ?? null);
        }
    }

    public function test_gemini_retry_recovers_within_the_shared_transcription_budget(): void
    {
        config([
            'services.ai_transcription.provider_priority' => 'gemini',
            'services.ai_transcription.http_attempts' => 2,
            'services.ai_transcription.total_timeout_seconds' => 30,
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'temporary outage']], 503)
                ->push($this->geminiResponse('Recovered Gemini transcription.'), 200),
        ]);

        $result = AIService::transcribeSpeechResult($this->audioFile());

        $this->assertSame('Recovered Gemini transcription.', $result['transcript']);
        $this->assertSame('gemini', $result['provider']);
        $this->assertSame('transcribed', $result['status']);
        Http::assertSentCount(2);
    }

    public function test_an_earlier_caller_deadline_is_not_replaced_by_the_service_default(): void
    {
        config([
            'services.ai_transcription.provider_priority' => 'openai',
            'services.ai_transcription.total_timeout_seconds' => 105,
        ]);
        Http::fake(fn () => Http::response(['text' => 'Too late to accept.'], 200));

        $result = AIService::transcribeSpeechResult($this->audioFile(), null, [
            'final_recording' => true,
            '_transcription_deadline' => microtime(true) - 0.1,
        ]);

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_server_deadline_stays_below_the_client_timeout_and_reserves_local_fallback_time(): void
    {
        config([
            'services.ai_transcription.total_timeout_seconds' => 105,
            'services.ai_transcription.full_request_timeout_ms' => 30000,
            'services.ai_transcription.local_fallback_reserve_seconds' => 20,
            'services.local_speech.enabled' => true,
        ]);

        $startedAt = microtime(true);
        $sharedDeadline = AIService::speechTranscriptionDeadlineAt(true);
        $cloudDeadline = AIService::cloudSpeechTranscriptionDeadlineAt($sharedDeadline);

        $this->assertGreaterThanOrEqual(24.0, $sharedDeadline - $startedAt);
        $this->assertLessThanOrEqual(25.1, $sharedDeadline - $startedAt);
        // One third of this short 25-second budget (8 seconds) is reserved,
        // so cloud remains first while local ASR still gets a real window.
        $this->assertEqualsWithDelta(8.0, $sharedDeadline - $cloudDeadline, 0.2);
        $this->assertGreaterThan($cloudDeadline, $sharedDeadline);
    }

    public function test_each_cloud_provider_receives_only_its_fair_share_of_the_remaining_deadline(): void
    {
        $method = new \ReflectionMethod(AIService::class, 'fairWorkDeadlineAt');
        $method->setAccessible(true);

        $startedAt = microtime(true);
        $deadlineAt = $startedAt + 20.0;
        $firstProviderDeadline = $method->invoke(null, $deadlineAt, 2);

        $this->assertEqualsWithDelta(10.0, $firstProviderDeadline - $startedAt, 0.1);
        $this->assertLessThan($deadlineAt, $firstProviderDeadline);
    }

    public function test_cloud_failover_gives_each_first_request_its_provider_slice(): void
    {
        config([
            'services.ai_transcription.http_attempts' => 2,
            'services.ai_transcription.total_timeout_seconds' => 30,
            'services.ai_transcription.request_timeout_ms' => 30000,
        ]);
        $timeouts = [];
        Http::fake(function (Request $request, array $options) use (&$timeouts) {
            $provider = str_contains($request->url(), 'api.openai.com') ? 'openai' : 'gemini';
            $timeouts[$provider] = (float) ($options['timeout'] ?? 0);

            return $provider === 'openai'
                ? Http::response(['text' => ''], 200)
                : Http::response($this->geminiResponse('Fair-slice fallback transcript.'), 200);
        });

        $result = AIService::transcribeSpeechResult($this->audioFile());

        $this->assertSame('gemini', $result['provider']);
        $this->assertSame(['openai', 'gemini'], $result['attempted_providers']);
        $this->assertGreaterThanOrEqual(11.0, $timeouts['openai']);
        $this->assertLessThanOrEqual(13.0, $timeouts['openai']);
        $this->assertGreaterThan($timeouts['openai'], $timeouts['gemini']);
        $this->assertLessThanOrEqual(25.0, $timeouts['gemini']);
    }

    public function test_full_recording_first_request_uses_the_full_default_provider_slice(): void
    {
        config([
            'services.ai_transcription.http_attempts' => 3,
            'services.ai_transcription.total_timeout_seconds' => 105,
            'services.ai_transcription.full_request_timeout_ms' => 120000,
        ]);
        $timeouts = [];
        Http::fake(function (Request $request, array $options) use (&$timeouts) {
            $provider = str_contains($request->url(), 'api.openai.com') ? 'openai' : 'gemini';
            $timeouts[$provider] = (float) ($options['timeout'] ?? 0);

            return $provider === 'openai'
                ? Http::response(['text' => ''], 200)
                : Http::response($this->geminiResponse('Full recording fallback transcript.'), 200);
        });

        $result = AIService::transcribeSpeechResult($this->audioFile(), null, [
            'final_recording' => true,
        ]);

        $this->assertSame('gemini', $result['provider']);
        $this->assertGreaterThanOrEqual(51.0, $timeouts['openai']);
        $this->assertLessThanOrEqual(53.0, $timeouts['openai']);
        $this->assertGreaterThan($timeouts['openai'], $timeouts['gemini']);
        $this->assertLessThanOrEqual(90.0, $timeouts['gemini']);
    }

    public function test_transcription_retry_delay_keeps_a_meaningful_window_for_the_next_attempt(): void
    {
        $method = new \ReflectionMethod(AIService::class, 'boundedTranscriptionRetryDelayMilliseconds');
        $method->setAccessible(true);

        $this->assertSame(2500, $method->invoke(null, 30000, 7500));
        $this->assertSame(500, $method->invoke(null, 500, 7500));
        $this->assertSame(0, $method->invoke(null, 30000, 3500));
        $this->assertSame(30000, $method->invoke(null, 30000, null));
    }

    private function audioFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('speech.webm', str_repeat('0', 2048));
    }

    private function geminiResponse(string $transcript): array
    {
        return [
            'candidates' => [
                [
                    'finishReason' => 'STOP',
                    'content' => [
                        'parts' => [
                            ['text' => json_encode(['transcript' => $transcript])],
                        ],
                    ],
                ],
            ],
        ];
    }
}
