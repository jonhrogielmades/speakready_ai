<?php

namespace Tests\Unit;

use App\Services\AIService;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use ReflectionClass;
use Tests\TestCase;

class AIServiceLogRedactionTest extends TestCase
{
    public function test_provider_request_exception_logs_only_response_metadata(): void
    {
        $sensitiveBody = 'candidate transcript and private resume details';
        $exception = new RequestException(new Response(
            new PsrResponse(422, ['Content-Type' => 'text/plain'], $sensitiveBody)
        ));
        $method = (new ReflectionClass(AIService::class))->getMethod('safeProviderErrorMessage');

        $message = $method->invoke(null, $exception);

        $this->assertStringContainsString('HTTP 422', $message);
        $this->assertStringContainsString('response_bytes='.strlen($sensitiveBody), $message);
        $this->assertStringContainsString(hash('sha256', $sensitiveBody), $message);
        $this->assertStringNotContainsString($sensitiveBody, $message);
    }
}
