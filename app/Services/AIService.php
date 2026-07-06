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

    public static function generateChatReply($session, $history, $latestAnswer, $provider = 'openai')
    {
        $prompt = "You are an expert Interviewer conducting a mock interview for a '" . ($session->target_position ?? 'General') . "' role. ";
        $prompt .= "The difficulty is '" . ($session->difficulty ?? 'Medium') . "'. ";
        if (!empty($session->company_persona)) {
            $prompt .= "You must act as an interviewer from '" . $session->company_persona . "'. ";
        }
        $prompt .= "\nHere is the conversation so far:\n";
        
        foreach ($history as $idx => $interaction) {
            $prompt .= "Interviewer: " . $interaction['question'] . "\n";
            $prompt .= "Candidate: " . $interaction['answer'] . "\n";
        }
        
        $prompt .= "\nYour task: Briefly acknowledge the candidate's latest answer (1-2 sentences), and then ask exactly ONE relevant follow-up question. Do not include markdown formatting or labels like 'Interviewer:'. Just output the spoken text.";

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $systemPrompt = 'You are an expert interviewer. Respond concisely and professionally without markdown.';
                
                // Rely on chatMessage for robust failover
                $response = self::chatMessage($prompt, [], $provider, $systemPrompt);

                if (!empty($response) && $response !== "Sorry, I am having trouble connecting to my brain right now.") {
                    return $response;
                }
            } catch (\Exception $e) {
                Log::error("AI Chat Reply Error (Attempt " . ($attempt + 1) . "): " . $e->getMessage());
            }

            $attempt++;
            if ($attempt < $maxRetries) {
                sleep(1);
            }
        }
        return "Thank you for sharing that. Let's move on to the next question. Can you tell me more about your background?";
    }

    private static function callOpenAI($prompt, $systemPrompt = null)
    {
        // Try fetching key from DB first, then .env
        $dbProvider = \App\Models\AiProvider::where('name', 'like', '%OpenAI%')->where('status', 'active')->first();
        if ($dbProvider && !empty($dbProvider->api_key)) {
            $apiKey = \Illuminate\Support\Facades\Crypt::decryptString($dbProvider->api_key);
            $endpoint = $dbProvider->api_endpoint ?? 'https://api.openai.com/v1/chat/completions';
        } else {
            $apiKey = env('OPENAI_API_KEY');
            $endpoint = 'https://api.openai.com/v1/chat/completions';
        }

        $model = env('OPENAI_MODEL', 'gpt-4o-mini');
        $sysMsg = $systemPrompt ?? 'You are an expert interviewer. Respond concisely and professionally without markdown.';

        $response = Http::timeout(45)->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $sysMsg],
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if ($response->successful()) {
            return trim($response->json('choices.0.message.content'));
        }
        Log::error('OpenAI Error: ' . $response->body());
        return "";
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
Provide your evaluation STRICTLY as a valid JSON object only.

DO NOT include:

* Markdown
* Code blocks
* Explanations outside JSON
* Introductory or concluding text

ACCURACY REQUIREMENTS (HIGHEST PRIORITY):
You MUST evaluate ONLY the Candidate Answer provided.
You MUST NOT invent information, assumptions, achievements, skills, experiences, results, or intentions that were not explicitly stated by the candidate.

Every feedback statement MUST be supported by evidence found in the candidate's exact answer.

If the candidate did not mention something, explicitly state that it was missing instead of assuming it existed.

FORBIDDEN GENERIC FEEDBACK:
Do NOT use generic comments such as:

* "Good answer."
* "Well explained."
* "Could provide more details."
* "Try to be more specific."

Instead, reference the candidate's exact response and explain:

* What was mentioned
* What was missing
* Why it affected the score

EXAMPLE:
Bad:
"You should provide more details."

Good:
"You mentioned collaborating with your team to complete the project, but you did not explain your specific responsibilities, challenges encountered, or measurable outcomes."

STRICT SCORING RULES:

Score every category independently from 0-100.

1. score
   Overall answer quality.

2. clarity_score
   Measures:

* Organization
* Understandability
* Logical flow

3. relevance_score
   Measures:

* How directly the answer addresses the question

4. grammar_score
   Measures:

* Grammar
* Sentence structure
* Word usage

5. professionalism_score
   Measures:

* Professional tone
* Interview appropriateness
* Confidence

SCORING CALIBRATION:

95-100:
Exceptional answer with clear evidence, strong relevance, professional communication, and measurable outcomes.

85-94:
Strong answer with minor weaknesses.

70-84:
Good answer but missing some supporting details or examples.

50-69:
Partially answers the question but lacks depth, examples, or clarity.

20-49:
Weak answer with significant gaps.

1-19:
Very poor answer with minimal useful information.

0:
Skipped, blank, irrelevant, nonsense, or unable to evaluate.

SHORT ANSWER DETECTION:

If Candidate Answer:

* Contains fewer than 10 meaningful words
* Is a single phrase
* Is only "yes", "no", "okay", "maybe", "I don't know", "not sure", etc.

Then:

Set:

* score = 0-10
* clarity_score = 0-10
* relevance_score = 0-10
* grammar_score = 0-10
* professionalism_score = 0-10

Feedback MUST explicitly state:

"The answer was too short to properly evaluate communication skills, knowledge, and interview readiness."

SKIPPED ANSWER RULE:

If answer equals:
"(Skipped or no answer)"
or is empty

Then set:

{
"score": 0,
"clarity_score": 0,
"relevance_score": 0,
"grammar_score": 0,
"professionalism_score": 0
}

Feedback MUST explain why skipping interview questions is harmful.

STAR METHOD VALIDATION (BEHAVIORAL QUESTIONS):

For behavioral and situational questions:

Explicitly evaluate:

Situation:
Was context provided?

Task:
Was responsibility or objective explained?

Action:
Were specific actions described?

Result:
Was outcome clearly stated?

STAR SCORING:

0:
No STAR structure.

25:
Only one STAR component present.

50:
Two STAR components present.

75:
Three STAR components present.

100:
All four components clearly present.

RESULT REQUIREMENT:

If the candidate does NOT provide:

* Outcome
* Achievement
* Impact
* Metrics
* Lessons learned
* Final result

Then deduct at least 20 points from:

* score
* star_method_score

Feedback MUST explicitly state:

"The answer described actions taken but did not explain the final result or impact."

FACTUAL EVIDENCE REQUIREMENT:

For each feedback item:

Reference specific evidence from the answer.

Examples:

Good:
"You stated that you resolved customer complaints by communicating with stakeholders, which demonstrates problem-solving skills."

Bad:
"You appear to have strong problem-solving skills."

BETTER SAMPLE ANSWER REQUIREMENTS:

The better_sample_answer MUST:

* Directly answer the same question
* Demonstrate an ideal response
* Be realistic
* Be professional
* Use STAR format when applicable
* Include measurable outcomes whenever possible

FOLLOW-UP QUESTION REQUIREMENTS:

Generate a relevant interviewer follow-up question that explores:

* Missing details
* Missing results
* Missing technical depth
* Missing decision-making process

SESSION ANALYSIS RULES:

overall_readiness_score:
Must be calculated from the actual answer quality across all questions.

star_method_score:
Must reflect STAR usage across all behavioral questions.

strengths:
Must only include strengths actually demonstrated.

weaknesses:
Must only include weaknesses actually observed.

improvement_suggestions:
Must be personalized based on observed deficiencies.

OUTPUT SCHEMA:

{
"per_question_feedback": [
{
"id": 1,
"score": 0,
"clarity_score": 0,
"relevance_score": 0,
"grammar_score": 0,
"professionalism_score": 0,
"ai_feedback": "",
"better_sample_answer": "",
"follow_up_question": ""
}
],
"session_feedback": {
"overall_readiness_score": 0,
"star_method_score": 0,
"strengths": "",
"weaknesses": "",
"improvement_suggestions": ""
}
}

Return ONLY the JSON object.
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

    public static function analyzeVoiceRehearsal($questionPrompt, $transcript, $provider = 'gemini')
    {
        $prompt = "You are an expert Speech and Interview Coach evaluating a candidate's verbal response to an interview question.\n";
        $prompt .= "Question Prompt: \"$questionPrompt\"\n";
        $prompt .= "Candidate Transcript: \"$transcript\"\n\n";
        
        $prompt .= <<<EOT
Provide your evaluation STRICTLY as a valid JSON object only. Do not include Markdown, code blocks, or explanations outside JSON.

OUTPUT SCHEMA:
{
  "strengths": "String. 1-2 sentences highlighting what the candidate did well in their speech (e.g., clear structure, relevant examples). If the answer is too short to judge, say 'The answer was too brief to evaluate strengths.'",
  "weaknesses": "String. 1-2 sentences suggesting actionable improvements (e.g., 'Elaborate more on specific examples with STAR method', 'Reduce use of filler words like um and ah'). If the answer is too short, say 'Provide a more detailed and structured response.'",
  "improved_answer": "String. A rewritten, professional version of their answer that directly addresses the prompt using the STAR method where appropriate. Keep it concise but impactful."
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
                    if ($decoded && isset($decoded['strengths'])) return $decoded;
                } elseif (is_array($response)) {
                    if (isset($response['strengths'])) return $response;
                }
            } catch (\Exception $e) {
                Log::error("Voice Rehearsal Analysis Error ($currentProvider): " . $e->getMessage());
            }
        }
        
        return [
            'strengths' => 'Could not generate strengths due to a service error.',
            'weaknesses' => 'Could not generate weaknesses due to a service error.',
            'improved_answer' => 'Service error occurred while trying to generate an improved answer.'
        ];
    }


    public static function chatMessage($message, $history = [], $provider = 'gemini', $systemPrompt = null)
    {
        $priorityString = env('INTERVIEW_CHATBOT_PROVIDER_PRIORITY', 'gemini,groq,claude,openrouter,wisdomgate,cohere,openai');
        $fallbackProviders = array_filter(array_map('trim', explode(',', $priorityString)));
        
        // Put the requested provider first, then the fallback ones
        $providers = array_values(array_unique(array_merge([$provider], $fallbackProviders)));
        
        if (empty($providers)) {
            $providers = [$provider, 'gemini', 'groq', 'claude'];
        }

        $fallbackError = "Sorry, I am having trouble connecting to my brain right now.";

        foreach ($providers as $currentProvider) {
            try {
                $response = null;
                switch ($currentProvider) {
                    case 'openai': $response = self::chatOpenAI($message, $history, $systemPrompt); break;
                    case 'gemini': $response = self::chatGemini($message, $history, $systemPrompt); break;
                    case 'cohere': $response = self::chatCohere($message, $history, $systemPrompt); break;
                    case 'groq': $response = self::chatGroq($message, $history, $systemPrompt); break;
                    case 'openrouter': $response = self::chatOpenRouter($message, $history, $systemPrompt); break;
                    case 'claude': $response = self::chatClaude($message, $history, $systemPrompt); break;
                    case 'wisdomgate': $response = self::chatWisdomGate($message, $history, $systemPrompt); break;
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

    private static function chatGemini($message, $history, $systemPrompt = null)
    {
        $apiKey = env('GEMINI_API_KEY');
        $model = env('GEMINI_MODEL', 'gemini-2.5-flash-lite');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        
        $sysMsg = $systemPrompt ?? 'You are a dedicated AI Interview Coach for SpeakReady AI. Your goal is to help users prepare for interviews, refine their resumes, and answer behavioral questions. Provide concise, helpful, and encouraging responses.';

        $response = Http::timeout(45)->post($url, [
            'contents' => self::formatHistoryForGemini($message, $history),
            'systemInstruction' => [
                'parts' => [['text' => $sysMsg]]
            ]
        ]);

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text');
        }
        Log::error('Gemini Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function formatHistoryForStandard($message, $history, $systemPrompt = null) {
        $sysMsg = $systemPrompt ?? 'You are a dedicated AI Interview Coach for SpeakReady AI. Your goal is to help users prepare for interviews, refine their resumes, and answer behavioral questions. Provide concise, helpful, and encouraging responses.';
        $messages = [
            ['role' => 'system', 'content' => $sysMsg]
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

    private static function chatOpenAI($message, $history, $systemPrompt = null)
    {
        $dbProvider = \App\Models\AiProvider::where('name', 'like', '%OpenAI%')->where('status', 'active')->first();
        if ($dbProvider && !empty($dbProvider->api_key)) {
            $apiKey = \Illuminate\Support\Facades\Crypt::decryptString($dbProvider->api_key);
            $endpoint = $dbProvider->api_endpoint ?? 'https://api.openai.com/v1/chat/completions';
        } else {
            $apiKey = env('OPENAI_API_KEY');
            $endpoint = 'https://api.openai.com/v1/chat/completions';
        }

        $model = env('OPENAI_MODEL', 'gpt-4o-mini');

        $response = Http::timeout(45)->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'model' => $model,
            'messages' => self::formatHistoryForStandard($message, $history, $systemPrompt)
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }
        Log::error('OpenAI Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function chatCohere($message, $history, $systemPrompt = null)
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

        $sysMsg = $systemPrompt ?? 'You are a dedicated AI Interview Coach for SpeakReady AI. Provide concise, helpful, and encouraging responses.';

        $response = Http::timeout(45)->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.cohere.ai/v1/chat', [
            'model' => $model,
            'message' => $message,
            'chat_history' => $chatHistory,
            'preamble' => $sysMsg,
            'temperature' => 0.7,
        ]);

        if ($response->successful()) {
            return $response->json('text');
        }
        Log::error('Cohere Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function chatGroq($message, $history, $systemPrompt = null)
    {
        $apiKey = env('GROQ_API_KEY');
        $model = env('GROQ_MODEL', 'llama3-8b-8192');

        $response = Http::timeout(45)->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $model,
            'messages' => self::formatHistoryForStandard($message, $history, $systemPrompt)
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }
        Log::error('Groq Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function chatOpenRouter($message, $history, $systemPrompt = null)
    {
        $apiKey = env('OPENROUTER_API_KEY');
        $model = env('OPENROUTER_MODEL', 'openrouter/free');

        $response = Http::timeout(45)->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => $model,
            'messages' => self::formatHistoryForStandard($message, $history, $systemPrompt)
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }
        Log::error('OpenRouter Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function chatClaude($message, $history, $systemPrompt = null)
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

        $sysMsg = $systemPrompt ?? 'You are a dedicated AI Interview Coach for SpeakReady AI. Your goal is to help users prepare for interviews, refine their resumes, and answer behavioral questions. Provide concise, helpful, and encouraging responses.';

        $response = Http::timeout(45)->withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => $version,
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'system' => $sysMsg,
            'max_tokens' => 1000,
            'messages' => $messages
        ]);

        if ($response->successful()) {
            return $response->json('content.0.text');
        }
        Log::error('Claude Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    private static function chatWisdomGate($message, $history, $systemPrompt = null)
    {
        $apiKey = env('WISDOMGATE_API_KEY');
        $model = env('WISDOMGATE_MODEL', 'gpt-5-nano');

        $response = Http::timeout(45)->withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.wisdomgate.ai/v1/chat/completions', [
            'model' => $model,
            'messages' => self::formatHistoryForStandard($message, $history, $systemPrompt)
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }
        Log::error('WisdomGate Chat Error: ' . $response->body());
        return "Sorry, I am having trouble connecting to my brain right now.";
    }

    public static function generateJson($prompt, $provider = 'gemini')
    {
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
                
                if (!empty($response)) {
                    return json_encode($response);
                }
            } catch (\Exception $e) {
                Log::error("AI JSON Generation Error ($currentProvider): " . $e->getMessage());
            }
        }
        
        Log::error("AI JSON Generation Failed on all providers.");
        return json_encode([]);
    }
}
