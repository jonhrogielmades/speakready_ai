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

    private function audioFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('speech.webm', str_repeat('0', 2048));
    }

    private function geminiResponse(string $transcript): array
    {
        return [
            'candidates' => [
                [
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
