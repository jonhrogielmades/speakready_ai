<?php

namespace App\Services;

use App\Support\InterviewerAvatar;

/** Keeps local and provider interviewer turns attached to the saved conversation. */
class InterviewConversationService
{
    public function fallbackReply($session, array $history, string $latestAnswer, bool $isFinal = false, ?string $requiredType = null): string
    {
        $position = trim((string) data_get($session, 'target_position', 'this role')) ?: 'this role';
        $answer = $this->clean($latestAnswer);
        $asked = array_map(fn ($turn) => $this->questionClause((string) ($turn['question'] ?? '')), $history);
        $lead = $isFinal ? 'For my final question, ' : '';

        if ($this->unavailable($answer)) {
            $roleQuestion = app(InterviewQuestionAlignmentService::class)->fallbackQuestion($position, $requiredType, '', $isFinal);

            return $roleQuestion ?? $lead.$this->withoutEvidenceQuestion($position, $requiredType);
        }

        $rapport = match (true) {
            preg_match('/\b(?:what(?:\'s| is) your name|who are you|may i know your name|can i ask your name)\b/i', $answer) === 1 => 'I am your AI interviewer, nice to meet you. ',
            preg_match('/\b(?:how are you|how do you do)\b/i', $answer) === 1 => 'I am doing well, thank you for asking. ',
            preg_match('/\b(?:what happens next|what(?:\'s| is) next|next step|how will this work)\b/i', $answer) === 1 => 'I will guide the conversation one question at a time. ',
            default => '',
        };
        if ($rapport !== '' && TranscriptService::wordCount($answer) < 15) {
            return $rapport.$lead.$this->withoutEvidenceQuestion($position, $requiredType);
        }

        // Quote the candidate's actual words, including uncertainty or a denial.
        // This avoids turning "I have never led a team" into an achievement.
        $anchor = $this->anchor($answer);
        if ($anchor === '') {
            return $lead.$this->withoutEvidenceQuestion($position, $requiredType);
        }
        $acknowledgement = $rapport.'You mentioned “'.$anchor.'”. ';
        $context = $this->connectedAnswerContext($answer, $history);
        if ($rapport !== '' && ! AnswerEvidenceSignals::analyze($answer)['has_personal_action']) {
            return $acknowledgement.$lead.$this->withoutEvidenceQuestion($position, $requiredType);
        }
        $deniedExperience = preg_match('/\b(?:no (?:work |professional |practical )?experience|never (?:worked|led|managed|handled|used|done)|have not (?:worked|led|managed|handled|used|done)|haven.t (?:worked|led|managed|handled|used|done))\b/i', $context) === 1;

        $questions = match ($requiredType) {
            'Personal' => [
                'Which strength from your background would help you with that?',
                'What interests you most about that kind of work?',
                'Which personal strength would you most like to develop next?',
            ],
            'Technical' => $this->technicalQuestions($context),
            'Situational' => [
                'If a similar situation came up, what would you do first?',
                'If your first approach did not work, what would you do next?',
                'If someone disagreed with your approach, how would you respond?',
            ],
            default => $this->evidenceQuestions($context, $deniedExperience, $session),
        };

        foreach ($questions as $question) {
            if (! $this->wasAsked($question, $asked)) {
                return $acknowledgement.($lead === '' ? $question : $lead.lcfirst($question));
            }
        }

        // Advance to a new example only after the useful probes were exhausted.
        return $acknowledgement.$lead.'what different experience could you share that shows another side of your work?';
    }

    public function isCoherentFollowUp(string $reply, string $latestAnswer, array $history = []): bool
    {
        $reply = $this->clean($reply);
        $answer = $this->clean($latestAnswer);
        $question = $this->questionClause($reply);
        if ($question === '' || substr_count($reply, '?') !== 1) {
            return false;
        }

        $asked = array_map(fn ($turn) => $this->questionClause((string) ($turn['question'] ?? '')), $history);
        if ($this->wasAsked($question, $asked, false)) {
            return false;
        }

        // A provider may refer to an exact quote, but may not invent one.
        preg_match_all('/[“"]([^”"]+)[”"]/u', $reply, $quotes);
        foreach ($quotes[1] as $quote) {
            if (! str_contains(mb_strtolower($answer), mb_strtolower($this->clean($quote)))) {
                return false;
            }
        }

        $acknowledgement = trim(substr($reply, 0, max(0, strlen($reply) - strlen($question))));
        $claims = [];
        if (preg_match('/\b(?:you (?:mentioned|said|shared|described|explained)|since you|after you)\s+([^.!?]+)/iu', $acknowledgement, $match)) {
            $claims[] = $match[1];
        }
        preg_match_all('/\b(?:since|after|given that) you\s+([^,;?]+)[,;]/iu', $reply, $premises);
        foreach ($premises[1] as $premise) {
            $claims[] = $premise;
        }
        foreach ($claims as $claim) {
            $claimTokens = $this->tokens($claim);
            $answerTokens = $this->tokens($answer);
            if ($claimTokens !== [] && count(array_intersect($claimTokens, $answerTokens)) / count($claimTokens) < 0.65) {
                return false;
            }
            if (FeedbackNumericEvidence::hasUnsupportedQuantity($claim, $answer)) {
                return false;
            }
            if (! preg_match('/\b(?:never|not|no|haven.t|didn.t|wasn.t)\b/i', $claim)) {
                foreach (preg_split('/[.!?;]|\bbut\b/iu', $answer) ?: [] as $clause) {
                    if (preg_match('/\b(?:never|not|no|haven.t|didn.t|wasn.t)\b/i', $clause)
                        && count($claimTokens) >= 2
                        && count(array_intersect($claimTokens, $this->tokens($clause))) / count($claimTokens) >= 0.8) {
                        return false;
                    }
                }
            }
        }

        $context = $this->connectedAnswerContext($answer, $history);
        // Ask how a reported result was measured, rather than ask for it again.
        if ($this->hasOutcome($context)
            && preg_match('/\b(?:what|which)\s+(?:(?:measurable|specific|concrete|final)\s+)?(?:result|outcome|impact)\b/i', $question)
            && ! preg_match('/\b(?:measure(?:d|s|ment|ments|ing)?|verif\w*|confirm\w*|attribut\w*|compar\w*|evidence|different|unexpected)\b/i', $question)) {
            return false;
        }

        return true;
    }

    private function evidenceQuestions(string $context, bool $deniedExperience, $session): array
    {
        if ($deniedExperience) {
            return [
                'Can you describe a related experience from school, training, or everyday life?',
                'How would you prepare before taking on that responsibility?',
                'What support would help you get started with that task?',
            ];
        }

        if (preg_match('/\bI\s+(?:would|will|could|plan to)\b/i', $context)
            && ! AnswerEvidenceSignals::analyze($context, true)['has_personal_action']) {
            return $this->technicalQuestions($context);
        }

        $questions = [];
        if (! AnswerEvidenceSignals::analyze($context)['has_personal_action']
            && ! preg_match('/\bI\s+(?:redesigned|chose|used|worked|helped|made)\b/i', $context)) {
            $questions[] = 'What part did you personally handle?';
        }
        if (! $this->hasOutcome($context)) {
            $questions[] = 'What result did you see after that?';
        }
        if (! preg_match('/\b(?:because|reason|chose .{0,60} (?:over|instead)|decided .{0,60} (?:over|instead))\b/i', $context)) {
            $questions[] = $this->reasonQuestion($session);
        }
        if ($this->hasOutcome($context) && ! preg_match('/\b(?:measured|verified|compared|confirmed|tracked|validated)\b/i', $context)) {
            $questions[] = 'How did you check whether your approach made a difference?';
        }
        if (! preg_match('/\b(?:challenge|constraint|limited|deadline|difficult|risk|tradeoff|trade-off)\b/i', $context)) {
            $questions[] = 'What was your most difficult decision in that situation?';
        }
        if (! preg_match('/\b(?:learned|learnt|lesson|next time|differently)\b/i', $context)) {
            $questions[] = 'What did you learn from that experience?';
        }
        $questions[] = 'What other approach did you consider?';
        $questions[] = 'What would you do differently in a similar situation?';

        return $questions;
    }

    private function withoutEvidenceQuestion(string $position, ?string $type): string
    {
        return match ($type) {
            'Personal' => "which strength would you bring to the {$position} role?",
            'Technical' => "what procedure would you use to check your work in the {$position} role?",
            'Situational' => "if you faced an unfamiliar task in the {$position} role, what would you do first?",
            'Behavioral' => "can you describe a specific experience from work, school, or training for the {$position} role?",
            default => "what specific experience prepared you for the {$position} role?",
        };
    }

    private function hasOutcome(string $text): bool
    {
        return AnswerEvidenceSignals::analyze($text)['has_result'];
    }

    private function technicalQuestions(string $context): array
    {
        $questions = [];
        if (! preg_match('/\b(?:verif\w*|test\w*|check\w*|measur\w*|compar\w*|validat\w*)\b/i', $context)) {
            $questions[] = 'What method would you use to verify that approach?';
        }
        if (! preg_match('/\b(?:fail\w*|rollback|recover\w*|backup|fallback)\b/i', $context)) {
            $questions[] = 'If that method failed, what troubleshooting steps would you use next?';
        }
        if (! preg_match('/\b(?:tradeoff|trade-off|alternative|instead|chose|because)\b/i', $context)) {
            $questions[] = 'What tradeoff would guide your choice of method for that task?';
        }
        $questions[] = 'How would you adapt that procedure to a larger task?';

        return $questions;
    }

    private function reasonQuestion($session): string
    {
        $assistance = data_get($session, 'ai_assistance_level', 'standard');
        if ($assistance === 'beginner') {
            return 'What was your main reason for that choice?';
        }
        if ($assistance === 'challenge') {
            return 'What evidence did you use to rule out another approach?';
        }

        return match (InterviewerAvatar::normalize(data_get($session, 'interviewer_avatar'))) {
            'structured_hr' => 'What evidence did you use to choose that approach?',
            'hiring_manager' => 'What did you prioritize when choosing that approach?',
            'role_specialist' => 'What method did you use to compare approaches?',
            'senior_leader' => 'What tradeoff did you consider when choosing that approach?',
            'panel_interviewer' => 'How did you decide against the other approaches?',
            default => 'Why did you choose that approach?',
        };
    }

    private function connectedAnswerContext(string $answer, array $history): string
    {
        $context = $answer;
        $turns = array_reverse(array_slice($history, -8));
        $currentQuestion = (string) ($turns[0]['question'] ?? '');
        foreach ($turns as $turn) {
            $previous = $this->clean((string) ($turn['answer'] ?? ''));
            if ($previous === $answer || $this->unavailable($previous)) {
                continue;
            }
            // Short answers to a probe often use "it" or "that" instead of
            // repeating the topic. Continue through those saved turns as well.
            if (preg_match('/\b(?:you mentioned|that|this|your approach|your decision|what happened next)\b/i', $currentQuestion)
                || count(array_intersect($this->tokens($context), $this->tokens($previous))) >= 2
                || (TranscriptService::wordCount($answer) < 20 && preg_match('/\b(?:it|that|this|they|them|yes|no)\b/i', $answer))) {
                $context .= ' '.$previous;
                $currentQuestion = (string) ($turn['question'] ?? '');
            } else {
                break;
            }
        }

        return $context;
    }

    private function wasAsked(string $question, array $asked, bool $matchIntent = true): bool
    {
        $tokens = $this->tokens($question);
        foreach ($asked as $previous) {
            if ($previous === '') {
                continue;
            }
            $previousTokens = $this->tokens($previous);
            $overlap = count(array_intersect($tokens, $previousTokens));
            if (mb_strtolower($question) === mb_strtolower($previous)
                || ($overlap >= 3 && $overlap / max(1, count(array_unique(array_merge($tokens, $previousTokens)))) >= 0.75)
                || ($matchIntent && $this->intent($question) !== null && $this->intent($question) === $this->intent($previous))) {
                return true;
            }
        }

        return false;
    }

    private function intent(string $question): ?string
    {
        foreach ([
            'ownership' => '/\b(?:personally|personal responsibility|your (?:role|part))\b/i',
            'outcome' => '/\b(?:result|outcome|what happened|impact)\b/i',
            'reason' => '/\b(?:why|reason|choose|chose)\b/i',
            'verification' => '/\b(?:check|verif\w*|measur\w*|test\w*)\b/i',
            'challenge' => '/\b(?:difficult|challenge|constraint)\b/i',
            'learning' => '/\b(?:learn\w*|lesson|differently)\b/i',
        ] as $intent => $pattern) {
            if (preg_match($pattern, $question)) {
                return $intent;
            }
        }

        return null;
    }

    private function anchor(string $answer): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+|;\s*/u', $answer) ?: [$answer];
        foreach ($sentences as $sentence) {
            if (str_contains($sentence, '?')) {
                continue;
            }
            $words = preg_split('/\s+/u', trim($sentence, " \t\n\r\0\x0B.\"“”")) ?: [];
            $excerpt = implode(' ', array_slice($words, 0, 16));
            if ($this->tokens($excerpt) !== []) {
                return $excerpt;
            }
        }

        return '';
    }

    private function questionClause(string $reply): string
    {
        // Ignore punctuation inside an exact quotation when finding the probe.
        $reply = preg_replace('/[“"]([^”"]+)[”"]/u', 'that', $reply) ?? $reply;
        $clauses = preg_split('/[.!;]\s+/u', trim($reply)) ?: [];

        return trim((string) end($clauses));
    }

    private function tokens(string $text): array
    {
        preg_match_all('/\b[\p{L}][\p{L}\p{N}]{2,}\b/u', mb_strtolower($text), $matches);

        return array_values(array_diff(array_unique($matches[0]), [
            'you', 'your', 'yours', 'our', 'the', 'and', 'that', 'this', 'with', 'for', 'was', 'were', 'have', 'had',
            'what', 'how', 'did', 'does', 'would', 'could', 'which', 'from', 'into', 'then', 'about', 'mentioned', 'said',
            'yes', 'okay', 'thanks', 'thank', 'very',
        ]));
    }

    private function unavailable(string $answer): bool
    {
        return $answer === '' || preg_match('/^(?:\((?:Skipped or no answer|No answer before the time limit)\)|\[Voice answer saved for playback\.)/i', $answer) === 1;
    }

    private function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
    }
}
