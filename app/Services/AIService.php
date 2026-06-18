<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    public static function generateQuestions($num, $position, $difficulty, $focus, $provider)
    {
        $prompt = "Generate $num mock interview questions for a '$position' role. The difficulty level should be '$difficulty'. The interview focus is '$focus'. Return ONLY a valid JSON array of strings containing the questions. Do not include any markdown formatting, headers, or explanations.";

        try {
            switch ($provider) {
                case 'gemini':
                    return self::callGemini($prompt);
                case 'cohere':
                    return self::callCohere($prompt);
                case 'groq':
                    return self::callGroq($prompt);
                case 'openrouter':
                    return self::callOpenRouter($prompt);
                case 'claude':
                    return self::callClaude($prompt);
                case 'wisdomgate':
                    return self::callWisdomGate($prompt);
                default:
                    return self::callGemini($prompt);
            }
        } catch (\Exception $e) {
            Log::error('AI Generation Error: ' . $e->getMessage());
            return [];
        }
    }

    public static function chatMessage($message, $history = [], $provider = 'gemini')
    {
        try {
            switch ($provider) {
                case 'gemini':
                    return self::chatGemini($message, $history);
                case 'cohere':
                    return self::chatCohere($message, $history);
                case 'groq':
                    return self::chatGroq($message, $history);
                case 'openrouter':
                    return self::chatOpenRouter($message, $history);
                case 'claude':
                    return self::chatClaude($message, $history);
                case 'wisdomgate':
                    return self::chatWisdomGate($message, $history);
                default:
                    return self::chatGemini($message, $history);
            }
        } catch (\Exception $e) {
            Log::error('AI Chat Error: ' . $e->getMessage());
            return "I'm sorry, I encountered an error processing your request.";
        }
    }

    private static function parseJsonResponse($content)
    {
        // Strip markdown backticks if present
        $content = preg_replace('/^```json\s*|```\s*$/i', '', trim($content));
        $content = preg_replace('/^```\s*|```\s*$/i', '', trim($content));
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function callGemini($prompt)
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = env('GEMINI_MODEL', 'gemini-2.5-flash-lite');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->successful()) {
            $content = $response->json('candidates.0.content.parts.0.text');
            return self::parseJsonResponse($content);
        }
        Log::error('Gemini Error: ' . $response->body());
        return [];
    }

    private static function callCohere($prompt)
    {
        $apiKey = env('COHERE_API_KEY');
        $model = env('COHERE_MODEL', 'command-r7b-12-2024');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.cohere.ai/v1/generate', [
            'model' => $model,
            'prompt' => $prompt,
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            $content = $response->json('generations.0.text');
            return self::parseJsonResponse($content);
        }
        Log::error('Cohere Error: ' . $response->body());
        return [];
    }

    private static function callGroq($prompt)
    {
        $apiKey = env('GROQ_API_KEY');
        $model = env('GROQ_MODEL', 'llama3-8b-8192');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');
            return self::parseJsonResponse($content);
        }
        Log::error('Groq Error: ' . $response->body());
        return [];
    }

    private static function callOpenRouter($prompt)
    {
        $apiKey = env('OPENROUTER_API_KEY');
        $model = env('OPENROUTER_MODEL', 'openrouter/free');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');
            return self::parseJsonResponse($content);
        }
        Log::error('OpenRouter Error: ' . $response->body());
        return [];
    }

    private static function callClaude($prompt)
    {
        $apiKey = env('ANTHROPIC_API_KEY');
        $model = env('ANTHROPIC_MODEL', 'claude-3-haiku-20240307');
        $version = env('ANTHROPIC_VERSION', '2023-06-01');

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => $version,
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 1000,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if ($response->successful()) {
            $content = $response->json('content.0.text');
            return self::parseJsonResponse($content);
        }
        Log::error('Claude Error: ' . $response->body());
        return [];
    }

    private static function callWisdomGate($prompt)
    {
        $apiKey = env('WISDOMGATE_API_KEY');
        $model = env('WISDOMGATE_MODEL', 'gpt-5-nano');

        // Assuming WisdomGate is an OpenAI-compatible endpoint
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.wisdomgate.ai/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');
            return self::parseJsonResponse($content);
        }
        Log::error('WisdomGate Error: ' . $response->body());
        return [];
    }

    private static function formatHistoryForGemini($message, $history) {
        $contents = [];
        foreach ($history as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'ai' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]]
            ];
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];
        return $contents;
    }

    private static function chatGemini($message, $history)
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = env('GEMINI_MODEL', 'gemini-2.5-flash-lite');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::post($url, [
            'contents' => self::formatHistoryForGemini($message, $history),
            'systemInstruction' => [
                'parts' => [['text' => 'You are a dedicated AI Interview Coach for SpeakReady AI. Your goal is to help users prepare for interviews, refine their resumes, and answer behavioral questions. Provide concise, helpful, and encouraging responses.']]
            ]
        ]);

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text');
        }
        Log::error('Gemini Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function formatHistoryForStandard($message, $history) {
        $messages = [
            ['role' => 'system', 'content' => 'You are a dedicated AI Interview Coach for SpeakReady AI. Your goal is to help users prepare for interviews, refine their resumes, and answer behavioral questions. Provide concise, helpful, and encouraging responses.']
        ];
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'ai' ? 'assistant' : 'user',
                'content' => $msg['content']
            ];
        }
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];
        return $messages;
    }

    private static function chatCohere($message, $history)
    {
        $apiKey = env('COHERE_API_KEY');
        $model = env('COHERE_MODEL', 'command-r7b-12-2024');
        
        $chatHistory = [];
        foreach ($history as $msg) {
            $chatHistory[] = [
                'role' => $msg['role'] === 'ai' ? 'CHATBOT' : 'USER',
                'message' => $msg['content']
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.cohere.ai/v1/chat', [
            'model' => $model,
            'message' => $message,
            'chat_history' => $chatHistory,
            'preamble' => 'You are a dedicated AI Interview Coach for SpeakReady AI. Provide concise, helpful, and encouraging responses.',
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            return $response->json('text');
        }
        Log::error('Cohere Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function chatGroq($message, $history)
    {
        $apiKey = env('GROQ_API_KEY');
        $model = env('GROQ_MODEL', 'llama3-8b-8192');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $model,
            'messages' => self::formatHistoryForStandard($message, $history)
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }
        Log::error('Groq Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function chatOpenRouter($message, $history)
    {
        $apiKey = env('OPENROUTER_API_KEY');
        $model = env('OPENROUTER_MODEL', 'openrouter/free');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => $model,
            'messages' => self::formatHistoryForStandard($message, $history)
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }
        Log::error('OpenRouter Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function chatClaude($message, $history)
    {
        $apiKey = env('ANTHROPIC_API_KEY');
        $model = env('ANTHROPIC_MODEL', 'claude-3-haiku-20240307');
        $version = env('ANTHROPIC_VERSION', '2023-06-01');

        $messages = [];
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'ai' ? 'assistant' : 'user',
                'content' => $msg['content']
            ];
        }
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => $version,
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'system' => 'You are a dedicated AI Interview Coach for SpeakReady AI. Your goal is to help users prepare for interviews, refine their resumes, and answer behavioral questions. Provide concise, helpful, and encouraging responses.',
            'max_tokens' => 1000,
            'messages' => $messages
        ]);

        if ($response->successful()) {
            return $response->json('content.0.text');
        }
        Log::error('Claude Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function chatWisdomGate($message, $history)
    {
        $apiKey = env('WISDOMGATE_API_KEY');
        $model = env('WISDOMGATE_MODEL', 'gpt-5-nano');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.wisdomgate.ai/v1/chat/completions', [
            'model' => $model,
            'messages' => self::formatHistoryForStandard($message, $history)
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }
        Log::error('WisdomGate Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }
}
