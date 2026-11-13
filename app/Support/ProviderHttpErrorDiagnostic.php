<?php

namespace App\Support;

/**
 * Extract only fixed, non-content categories from an AI provider HTTP error.
 * Provider messages may echo interview answers, prompts, and credentials.
 */
final class ProviderHttpErrorDiagnostic
{
    private const TYPES = [
        'invalid_request_error', 'invalid_request', 'validation_error',
        'bad_request', 'invalid_argument',
    ];

    private const CODES = [
        'invalid_json_schema', 'invalid_schema', 'invalid_request_error',
        'invalid_request', 'invalid_parameter', 'unsupported_parameter',
        'unsupported_value', 'unsupported_response_format',
        'invalid_response_format', 'invalid_model', 'model_not_found',
        'model_not_available', 'unsupported_model', 'invalid_argument',
        'context_length_exceeded', 'too_many_tokens', 'bad_request',
    ];

    private const HINTS = [
        'schema_or_format', 'model', 'token_limit', 'reasoning_effort',
        'temperature', 'safety_mode', 'messages', 'endpoint', 'access',
    ];

    /** @return array{status?: int, type?: string, code?: string, param?: string, hint?: string} */
    public static function fromResponse($response): array
    {
        if (! $response || ! method_exists($response, 'body')) {
            return [];
        }

        $status = method_exists($response, 'status') ? (int) $response->status() : 0;
        $body = (string) $response->body();
        $diagnostic = ['status' => $status];
        if (strlen($body) > 32768) {
            return self::sanitize($diagnostic);
        }

        $decoded = json_decode($body, true);
        $error = is_array($decoded) ? ($decoded['error'] ?? $decoded) : [];
        if (! is_array($error)) {
            $error = ['message' => is_string($error) ? $error : ''];
        }

        foreach (['type', 'code', 'param'] as $field) {
            if (isset($error[$field]) && is_string($error[$field])) {
                $diagnostic[$field] = $error[$field];
            }
        }

        // Classify message-only errors without retaining any provider prose.
        $message = $error['message'] ?? (is_array($decoded) ? ($decoded['message'] ?? '') : $body);
        if (is_string($message)) {
            $diagnostic['hint'] = self::hintFor($message);
        }

        $safe = self::sanitize($diagnostic);
        if (! isset($safe['param']) && isset($safe['hint'])) {
            $safe['param'] = match ($safe['hint']) {
                'schema_or_format' => 'response_format',
                'model' => 'model',
                'token_limit' => 'max_tokens',
                'reasoning_effort' => 'reasoning_effort',
                'temperature' => 'temperature',
                'safety_mode' => 'safety_mode',
                'messages' => 'messages',
                default => null,
            };
            if ($safe['param'] === null) {
                unset($safe['param']);
            }
        }

        return $safe;
    }

    /** @return array{status?: int, type?: string, code?: string, param?: string, hint?: string} */
    public static function sanitize(array $diagnostic): array
    {
        $safe = [];
        $status = (int) ($diagnostic['status'] ?? 0);
        if ($status >= 100 && $status <= 599) {
            $safe['status'] = $status;
        }
        foreach (['type' => self::TYPES, 'code' => self::CODES, 'hint' => self::HINTS] as $field => $allowed) {
            $value = $diagnostic[$field] ?? null;
            if (is_string($value) && in_array($value, $allowed, true)) {
                $safe[$field] = $value;
            }
        }
        $param = $diagnostic['param'] ?? null;
        if (is_string($param)) {
            $safeParam = self::paramFor($param);
            if ($safeParam !== null) {
                $safe['param'] = $safeParam;
            }
        }

        return $safe;
    }

    public static function describe(array $diagnostic): string
    {
        $safe = self::sanitize($diagnostic);
        $parts = [];
        foreach (['status', 'type', 'code', 'param', 'hint'] as $field) {
            if (isset($safe[$field])) {
                $parts[] = $field.'='.$safe[$field];
            }
        }

        return implode('; ', $parts);
    }

    private static function paramFor(string $value): ?string
    {
        $value = strtolower($value);
        foreach ([
            'response_format' => '/response[_ -]?format|json[_ -]?schema|\bschema\b/',
            'max_completion_tokens' => '/max[_ -]?completion[_ -]?tokens/',
            'max_output_tokens' => '/max[_ -]?output[_ -]?tokens/',
            'max_tokens' => '/max[_ -]?tokens|token[_ -]?limit/',
            'reasoning_effort' => '/reasoning[_ -]?effort/',
            'temperature' => '/\btemperature\b/',
            'safety_mode' => '/safety[_ -]?mode/',
            'messages' => '/\bmessages?\b/',
            'model' => '/\bmodel\b/',
        ] as $param => $pattern) {
            if (preg_match($pattern, $value)) {
                return $param;
            }
        }

        return null;
    }

    private static function hintFor(string $message): ?string
    {
        $message = strtolower($message);
        foreach ([
            'schema_or_format' => '/response[_ -]?format|json[_ -]?schema|structured[_ -]?output|\bschema\b/',
            'model' => '/\bmodel\b/',
            'token_limit' => '/max[_ -]?(?:completion[_ -]?|output[_ -]?)?tokens|token[_ -]?limit|context[_ -]?length/',
            'reasoning_effort' => '/reasoning[_ -]?effort/',
            'temperature' => '/\btemperature\b/',
            'safety_mode' => '/safety[_ -]?mode/',
            'messages' => '/\bmessages?\b/',
            'endpoint' => '/\bendpoint\b|\broute\b|\burl\b/',
            'access' => '/\bpermission\b|\bauthoriz(?:ed|ation)\b|\baccess\b/',
        ] as $hint => $pattern) {
            if (preg_match($pattern, $message)) {
                return $hint;
            }
        }

        return null;
    }
}
