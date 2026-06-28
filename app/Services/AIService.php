<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    public static function generateQuestions($num, $position, $difficulty, $focus, $provider, $resumeText = null, $jobDescription = null, $companyPersona = null)
    {
        $jobDescription = self::truncateText($jobDescription);
        $resumeText = self::truncateText($resumeText);

        $prompt = "Generate $num mock interview questions for a '$position' role. The difficulty level should be '$difficulty'. The interview focus is '$focus'. ";
        
        if ($focus === 'Salary Negotiation') {
            $prompt .= "This is a Salary Negotiation simulation. Generate questions and statements that a hiring manager or recruiter would use during a compensation negotiation, including budget constraints, asking for expected salary, and presenting counter-offers. ";
        }
        
        if (!empty($companyPersona)) {
            $prompt .= "You must act as an interviewer from '$companyPersona'. Structure your questions according to their specific interview culture (e.g., if Amazon, use Leadership Principles and STAR method focus; if Google, focus on Googlyness and open-ended technical scaling; if McKinsey, use consulting case-like framing). ";
        }
        
        if (!empty($jobDescription)) {
            $prompt .= "The questions must be highly tailored to the following Job Description: \"$jobDescription\". ";
        }
        if (!empty($resumeText)) {
            $prompt .= "The candidate has provided their resume. Create behavioral and experience-based questions that specifically ask about details, projects, or experiences mentioned in this Resume: \"$resumeText\". ";
        }
        
        $prompt .= "Return ONLY a valid JSON array of strings containing the questions. Do not include any markdown formatting, headers, or explanations.\n";
        $prompt .= "EXAMPLE OUTPUT FORMAT:\n";
        $prompt .= "[\n  \"Can you describe a time when you had to overcome a significant technical challenge?\",\n  \"How do you prioritize your tasks when facing multiple tight deadlines?\"\n]";

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $response = [];
                switch ($provider) {
                    case 'gemini':
                        $response = self::callGemini($prompt);
                        break;
                    case 'cohere':
                        $response = self::callCohere($prompt);
                        break;
                    case 'groq':
                        $response = self::callGroq($prompt);
                        break;
                    case 'openrouter':
                        $response = self::callOpenRouter($prompt);
                        break;
                    case 'claude':
                        $response = self::callClaude($prompt);
                        break;
                    case 'wisdomgate':
                        $response = self::callWisdomGate($prompt);
                        break;
                    default:
                        $response = self::callGemini($prompt);
                        break;
                }
                if (!empty($response)) {
                    return $response;
                }
            } catch (\Exception $e) {
                Log::error("AI Generation Error (Attempt " . ($attempt + 1) . "): " . $e->getMessage());
            }

            $attempt++;
            if ($attempt < $maxRetries) {
                sleep(1);
            }
        }
        
        Log::error("AI Generation Failed after {$maxRetries} attempts.");
        return [];
    }

    public static function generateFeedback($sessionData, $answersData, $provider)
    {
        $prompt = "You are an expert Interview Coach evaluating a candidate's interview session. Evaluate the following interview answers and provide highly accurate feedback and scores.\n";
        $prompt .= "Target Position: " . ($sessionData['target_position'] ?? 'General') . "\n";
        $prompt .= "Difficulty: " . ($sessionData['difficulty'] ?? 'Medium') . "\n\n";

        if (isset($sessionData['banned_words']) && !empty($sessionData['banned_words'])) {
            $prompt .= "CRITICAL MODIFIER - BANNED WORDS: The user was strictly forbidden from using the following words or phrases: " . $sessionData['banned_words'] . ". If you detect ANY of these words in their answers, you MUST heavily penalize their professionalism_score and mention it explicitly in their ai_feedback.\n";
        }
        
        if (isset($sessionData['target_tone']) && !empty($sessionData['target_tone'])) {
            $prompt .= "CRITICAL MODIFIER - TARGET TONE: The user was instructed to answer with a '" . $sessionData['target_tone'] . "' tone. Evaluate if they achieved this tone. If they did not, lower their score and advise them in the feedback.\n";
        }

        $prompt .= "\nHere is the transcript:\n";
        
        foreach ($answersData as $index => $ans) {
            $prompt .= "Index ID: " . $ans['id'] . "\n";
            $prompt .= "Question: " . $ans['question'] . "\n";
            $prompt .= "Candidate Answer: " . ($ans['answer'] ?? '(Skipped or no answer)') . "\n\n";
        }

        $prompt .= <<<EOT
Provide your evaluation STRICTLY as a JSON object with the following structure. Do not include any markdown formatting or explanations outside the JSON object.

CRITICAL INSTRUCTION FOR SKIPPED ANSWERS:
If the Candidate Answer is '(Skipped or no answer)', you MUST set score, clarity_score, relevance_score, grammar_score, and professionalism_score strictly to 0. The ai_feedback should advise them not to skip questions.

EXAMPLE OUTPUT FORMAT:
{
  "per_question_feedback": [
    {
      "id": 1,
      "score": 85,
      "clarity_score": 90,
      "relevance_score": 80,
      "grammar_score": 95,
      "professionalism_score": 90,
      "ai_feedback": "You clearly explained your role, but could have focused more on the specific outcome.",
      "better_sample_answer": "In my previous role, I led a team of 5 to redesign the checkout flow. Using the STAR method, the situation was...",
      "follow_up_question": "What metrics did you use to measure the success of that redesign?"
    },
    {
      "id": 2,
      "score": 0,
      "clarity_score": 0,
      "relevance_score": 0,
      "grammar_score": 0,
      "professionalism_score": 0,
      "ai_feedback": "You skipped this question. In a real interview, skipping a question can be detrimental. Always try to provide at least a partial answer.",
      "better_sample_answer": "Even if you haven't faced this exact scenario, you could say: 'While I haven't directly encountered X, in a similar situation Y, I did Z...'",
      "follow_up_question": "Can you think of any parallel experience you could draw from to answer this?"
    }
  ],
  "session_feedback": {
    "overall_readiness_score": 75,
    "strengths": "Strong communication and clear articulation of past technical achievements.",
    "weaknesses": "Tendency to skip behavioral questions or provide brief answers without the STAR method.",
    "improvement_suggestions": "Practice using the STAR method (Situation, Task, Action, Result) to structure your behavioral answers more effectively."
  }
}

Now, provide the JSON evaluation for the transcript provided above using this exact schema.
EOT;

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $response = [];
                switch ($provider) {
                    case 'gemini':
                        $response = self::callGemini($prompt);
                        break;
                    case 'cohere':
                        $response = self::callCohere($prompt);
                        break;
                    case 'groq':
                        $response = self::callGroq($prompt);
                        break;
                    case 'openrouter':
                        $response = self::callOpenRouter($prompt);
                        break;
                    case 'claude':
                        $response = self::callClaude($prompt);
                        break;
                    case 'wisdomgate':
                        $response = self::callWisdomGate($prompt);
                        break;
                    default:
                        $response = self::callGemini($prompt);
                        break;
                }
                if (!empty($response)) {
                    return $response;
                }
            } catch (\Exception $e) {
                Log::error("AI Feedback Generation Error (Attempt " . ($attempt + 1) . "): " . $e->getMessage());
            }

            $attempt++;
            if ($attempt < $maxRetries) {
                sleep(1);
            }
        }
        
        Log::error("AI Feedback Generation Failed after {$maxRetries} attempts.");
        return [];
    }

    public static function generateGame($topic, $provider = 'gemini')
    {
        $prompt = "You are an expert Gamification and Interview Design AI. Create a highly engaging, gamified Interview Learning Game based on the topic: '$topic'.\n";
        $prompt .= <<<EOT
Return ONLY a valid JSON object describing the level. Do not include markdown formatting or explanations.
The JSON structure MUST be exactly like this:
{
  "title": "String, a catchy gamified title",
  "description": "String, 1-2 sentences setting the scene",
  "mission_text": "String, 5-10 specific questions the user needs to answer in this challenge. Format them as a numbered list. DO NOT write this as a mission, just list the questions.",
  "target_position": "String, the personal improvement goal e.g., 'Better Communication', 'Public Speaking'",
  "difficulty": "String, either 'beginner', 'intermediate', or 'advanced'",
  "required_score": 80, // Integer between 50 and 100
  "xp_reward": 500, // Integer
  "energy_cost": 1, // Integer, usually 1 or 2
  "ai_persona": "String, the persona of the interviewer (e.g., 'Strict Technical Lead')",
  "ai_custom_prompt": "String, hidden prompt instructions for the AI on how to act",
  "time_limit_seconds": 120, // Integer, e.g., 60, 120, or null
  "banned_words": "String, comma separated words user shouldn't say, e.g., 'um, like, basically', or null",
  "target_tone": "String, desired tone e.g., 'Confident', 'Empathetic', or null",
  "custom_badge_name": "String, a badge name e.g., 'Master Negotiator', or null",
  "skill_xp_type": "String, e.g., 'Leadership', 'Technical', 'Communication', or null",
  "skill_xp_amount": 50 // Integer, e.g., 50
}
EOT;

        $priorityString = env('INTERVIEW_CHATBOT_PROVIDER_PRIORITY', 'gemini,groq,claude,openrouter,wisdomgate,cohere');
        $providers = array_filter(array_map('trim', explode(',', $priorityString)));
        if (empty($providers)) {
            $providers = [$provider, 'gemini', 'groq', 'claude', 'openrouter', 'wisdomgate', 'cohere'];
        }

        foreach ($providers as $currentProvider) {
            try {
                $response = [];
                switch ($currentProvider) {
                    case 'gemini': $response = self::callGemini($prompt); break;
                    case 'cohere': $response = self::callCohere($prompt); break;
                    case 'groq': $response = self::callGroq($prompt); break;
                    case 'openrouter': $response = self::callOpenRouter($prompt); break;
                    case 'claude': $response = self::callClaude($prompt); break;
                    case 'wisdomgate': $response = self::callWisdomGate($prompt); break;
                    default: continue 2;
                }
                
                if (is_string($response)) {
                    $cleanResponse = trim(str_replace(['```json', '```'], '', $response));
                    $decoded = json_decode($cleanResponse, true);
                    if ($decoded && isset($decoded['title'])) return $decoded;
                } elseif (is_array($response)) {
                    if (isset($response['title'])) return $response;
                }
            } catch (\Exception $e) {
                Log::error("Learning Game Generation Error ($currentProvider): " . $e->getMessage());
            }
        }
        
        return null;
    }

    public static function chatMessage($message, $history = [], $provider = 'gemini')
    {
        $priorityString = env('INTERVIEW_CHATBOT_PROVIDER_PRIORITY', $provider);
        $providers = array_filter(array_map('trim', explode(',', $priorityString)));
        if (empty($providers)) {
            $providers = [$provider];
        }

        $fallbackError = "Sorry, I am having trouble connecting to my brain right now.";

        foreach ($providers as $currentProvider) {
            try {
                $response = null;
                switch ($currentProvider) {
                    case 'gemini': $response = self::chatGemini($message, $history); break;
                    case 'cohere': $response = self::chatCohere($message, $history); break;
                    case 'groq': $response = self::chatGroq($message, $history); break;
                    case 'openrouter': $response = self::chatOpenRouter($message, $history); break;
                    case 'claude': $response = self::chatClaude($message, $history); break;
                    case 'wisdomgate': $response = self::chatWisdomGate($message, $history); break;
                }

                if ($response !== null && $response !== $fallbackError && $response !== "I'm sorry, I encountered an error processing your request.") {
                    return $response;
                }
            } catch (\Exception $e) {
                Log::error("AI Chat Error ({$currentProvider}): " . $e->getMessage());
            }
        }

        return $fallbackError;
    }

    private static function truncateText($text, $maxWords = 800)
    {
        if (empty($text)) return $text;
        $words = explode(' ', $text);
        if (count($words) > $maxWords) {
            return implode(' ', array_slice($words, 0, $maxWords)) . '... [Truncated for length]';
        }
        return $text;
    }

    private static function parseJsonResponse($content)
    {
        // Strip markdown backticks if present
        $content = preg_replace('/^```json\s*|```\s*$/i', '', trim($content));
        $content = preg_replace('/^```\s*|```\s*$/i', '', trim($content));
        
        // Extract JSON using substring if there is any trailing/leading text
        $firstChar = strpos($content, '{');
        $firstBracket = strpos($content, '[');
        
        $startPos = false;
        if ($firstChar !== false && $firstBracket !== false) {
            $startPos = min($firstChar, $firstBracket);
        } elseif ($firstChar !== false) {
            $startPos = $firstChar;
        } elseif ($firstBracket !== false) {
            $startPos = $firstBracket;
        }

        if ($startPos !== false) {
            $endChar = strrpos($content, '}');
            $endBracket = strrpos($content, ']');
            
            $endPos = false;
            if ($endChar !== false && $endBracket !== false) {
                $endPos = max($endChar, $endBracket);
            } elseif ($endChar !== false) {
                $endPos = $endChar;
            } elseif ($endBracket !== false) {
                $endPos = $endBracket;
            }

            if ($endPos !== false && $endPos > $startPos) {
                $content = substr($content, $startPos, $endPos - $startPos + 1);
            }
        }

        $decoded = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Parsing Error: ' . json_last_error_msg() . ' Content: ' . $content);
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private static function callGemini($prompt)
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = env('GEMINI_MODEL', 'gemini-2.5-flash-lite');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(45)->post($url, [
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

        $response = Http::timeout(45)->withHeaders([
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

        $response = Http::timeout(45)->withHeaders([
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

        $response = Http::timeout(45)->withHeaders([
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

        $response = Http::timeout(45)->withHeaders([
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
        $response = Http::timeout(45)->withHeaders([
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

        $response = Http::timeout(45)->post($url, [
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

        $response = Http::timeout(45)->withHeaders([
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

        $response = Http::timeout(45)->withHeaders([
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

        $response = Http::timeout(45)->withHeaders([
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

        $response = Http::timeout(45)->withHeaders([
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

        $response = Http::timeout(45)->withHeaders([
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
