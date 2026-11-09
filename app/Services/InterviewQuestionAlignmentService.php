<?php

namespace App\Services;

class InterviewQuestionAlignmentService
{
    /**
     * Only flag tasks that clearly belong to another occupation. These
     * checks run on the question clause after removing the target title, so
     * adding "for the Nurse role" cannot validate a software task.
     */
    private const SPECIALIZED_TASK_DOMAINS = [
        'software' => [
            'task' => '/\b(?:debug\w*|deploy\w*|cod(?:e|ing)|program\w*|troubleshoot\w*|architect\w*)\b.*\b(?:cloud|software|code|api|database|website|web app|application|kubernetes|aws|azure)\b|\b(?:cloud|software|code|api|database|website|web app|application|kubernetes|aws|azure)\b.*\b(?:deploy\w*|debug\w*|cod(?:e|ing)|program\w*|troubleshoot\w*|architecture)\b/i',
            'role' => '/\b(?:developer|programmer|software|web|cloud|devops|database|systems? engineer|data engineer|qa engineer|it engineer|it specialist|information technology|technical support|application support|support engineer|system administrator|network engineer|site reliability|cybersecurity)\b/i',
        ],
        'clinical' => [
            'task' => '/\b(?:care for|treat|diagnos\w*|triag\w*|assess\w*|monitor\w*)\b.*\bpatients?\b(?!\s+(?:data|records|information|portal|app|software))|\b(?:administer\w* medication|monitor\w* vital signs|assess\w* vital signs|clinical care|patient care)\b(?!\s+(?:data|records|information|portal|app|software|system))|\bpatients?\b(?!\s+(?:data|records|information|portal|app|software)).*\b(?:care|treat\w*|diagnos\w*|triag\w*|medication|assessment)\b/i',
            'role' => '/\b(?:nurse|nursing|doctor|physician|medical|medic|clinical|patient care|therapist|pharmacist|healthcare|health care|caregiver|paramedic|dentist|dental|midwife)\b/i',
        ],
        'maritime' => [
            'task' => '/\b(?:lifeboat|life raft|mooring|berthing|bridge watch|deck watch|cargo lashing|helm|shipboard safety|vessel deck|anchoring operation)\b/i',
            'role' => '/\b(?:seaman|seafarer|sailor|deck|bosun|boatswain|mate|maritime|marine|shipboard|ship crew|port operations)\b/i',
        ],
        'accounting' => [
            'task' => '/\b(?:reconcil\w*|audit\w*|process\w*|prepare\w*)\b.*\b(?:invoice|payroll|ledger|accounts payable|accounts receivable|financial statement)\b|\b(?:invoice|payroll|ledger|accounts payable|accounts receivable|financial statement)\b.*\b(?:reconcil\w*|audit\w*|process\w*|prepare\w*)\b/i',
            'role' => '/\b(?:accountant|accounting|bookkeeper|billing|finance|financial|payroll|auditor|accounts payable|accounts receivable|controller)\b/i',
        ],
        'automotive' => [
            'task' => '/\b(?:repair\w*|diagnos\w*|servic\w*|replac\w*|rebuild\w*|fix\w*)\b.{0,80}\b(?:car|automobile|vehicle|truck)\s+(?:engines?|brakes?|transmissions?|gearboxes?|motors?)\b|\b(?:car|automobile|vehicle|truck)\s+(?:engines?|brakes?|transmissions?|gearboxes?|motors?)\b.{0,80}\b(?:repair\w*|diagnos\w*|servic\w*|replac\w*|rebuild\w*|fix\w*)\b/i',
            'role' => '/\b(?:mechanic|automotive|auto technician|vehicle technician|fleet mechanic|car repair|auto repair)\b/i',
        ],
    ];

    private const ANSWER_STOP_WORDS = [
        'about', 'after', 'again', 'also', 'answer', 'asked', 'because', 'been',
        'before', 'being', 'candidate', 'could', 'detail', 'example', 'experience',
        'first', 'from', 'have', 'interview', 'interviewer', 'into', 'mentioned',
        'more', 'next', 'question', 'result', 'role', 'said', 'shared', 'should', 'specific',
        'that', 'their', 'them', 'then', 'there', 'these', 'they', 'this', 'those',
        'through', 'time', 'what', 'when', 'where', 'which', 'while', 'with', 'work',
        'would', 'your', 'yourself',
    ];

    /**
     * Keep the maritime guard narrow. A generic position should continue
     * through the existing interview question selection pipeline.
     */
    public function isRoleRelevant(string $questionText, string $targetPosition, ?string $jobDescription = null): bool
    {
        $reply = $this->normalize($questionText);
        $isMaritimeDeckRole = $this->isMaritimeDeckRole($targetPosition);
        $question = $this->lastQuestionClause($reply);
        if ($question === '' && ! $isMaritimeDeckRole) {
            // Existing question banks also contain imperative interview prompts
            // such as "Describe a time ..." without a question mark.
            $clauses = preg_split('/[.!;]\s+/u', $reply) ?: [];
            $question = trim((string) end($clauses));
        }
        if ($question === '') {
            return false;
        }

        $role = $this->normalize($targetPosition);
        $questionWithoutRole = $role === '' ? $question : trim(str_replace($role, '', $question));
        if ($this->hasUnrelatedSpecializedTask($questionWithoutRole, $role, $this->normalize((string) $jobDescription))) {
            return false;
        }

        if (! $isMaritimeDeckRole) {
            return true;
        }

        // A job-title prefix must not turn a question for another occupation
        // into a seafarer question.
        if (preg_match('/\b(?:aws|azure|kubernetes|cloud|software|deploy(?:ed|ing|ment)?\s+code|coding|programming|database|api|web\s*(?:site|application|app)|frontend|backend|debug(?:ging)?\s+code|billing|invoice|reconciliation|payroll|call center|customer service|customer complaint|retail sales|warehouse inventory|hospital patient)\b/i', $question)) {
            return false;
        }

        if ($this->hasMaritimeTask($questionWithoutRole)) {
            return true;
        }

        $acknowledgement = substr($reply, 0, max(0, strlen($reply) - strlen($question)));
        if ($role !== '') {
            $acknowledgement = str_replace($role, '', $acknowledgement);
        }
        if ($this->hasMaritimeTask($acknowledgement) && $this->isGenericEvidenceProbe($questionWithoutRole)) {
            return true;
        }

        // Motivation and strengths are valid personal interview questions
        // when they explicitly identify the chosen maritime role.
        if ($role !== ''
            && str_contains($question, $role)
            && preg_match('/\b(?:motivat\w*|interest\w*|strength\w*|weakness\w*|career goal\w*|work style|fit|hire|contribut\w*|why (?:do|did|would|are) you)\b/i', $question)) {
            return true;
        }

        // A supplied description can name a narrower deck task absent from
        // our vocabulary, but one incidental shared word is not sufficient.
        $description = $this->normalize((string) $jobDescription);
        if ($description !== '' && $this->hasMaritimeTask($description)) {
            $questionTokens = $this->contentTokens($questionWithoutRole, $targetPosition);
            $descriptionTokens = $this->contentTokens($description, $targetPosition);

            return count(array_intersect($questionTokens, $descriptionTokens)) >= 2;
        }

        return false;
    }

    /**
     * Only a question that asserts continuity with an available, substantive
     * answer needs an answer anchor. Inspect the actual question clause so a
     * relevant acknowledgement cannot hide an unrelated next question.
     */
    public function isAnswerGrounded(string $questionText, string $latestAnswer, string $targetPosition): bool
    {
        $answer = $this->normalize($latestAnswer);
        if ($this->answerUnavailable($answer) || count(preg_split('/\s+/u', $answer) ?: []) < 8) {
            return true;
        }

        $reply = $this->normalize($questionText);
        $question = $this->lastQuestionClause($reply);
        if ($this->isMaritimeDeckRole($targetPosition)
            && preg_match('/\b(?:never|have not|has not|had not|did not|not yet|no experience|haven.t|hasn.t|hadn.t|didn.t)\b/i', $answer)
            && $this->hasMaritimeTask($question)
            && preg_match('/\b(?:how|what|when|where|why)\s+did\s+you\b|\b(?:describe|tell me about)\s+(?:a|the|one|your)\s+(?:specific\s+)?time\s+you\b|\bwhat\s+did\s+you\s+learn\s+from\b/i', $question)) {
            return false;
        }

        if (! preg_match('/\b(?:you (?:mentioned|said|shared|described|explained|handled|used|discussed)|your (?:answer|example|experience|story)|what you (?:said|shared|described)|thinking about what you shared|earlier you)\b/i', $reply)) {
            return true;
        }

        if ($question === '') {
            return false;
        }

        $answerTokens = $this->contentTokens($answer, $targetPosition);
        $questionTokens = $this->contentTokens($question, $targetPosition);
        if (count(array_intersect($answerTokens, $questionTokens)) > 0) {
            return true;
        }

        // A short probe such as "What happened next?" can refer back to a
        // concrete acknowledgement without repeating the same noun.
        $acknowledgement = substr($reply, 0, max(0, strlen($reply) - strlen($question)));
        $acknowledgementHasAnchor = count(array_intersect(
            $answerTokens,
            $this->contentTokens($acknowledgement, $targetPosition)
        )) > 0;

        $questionWithoutRole = str_replace($this->normalize($targetPosition), '', $question);

        return $acknowledgementHasAnchor && $this->isGenericEvidenceProbe($questionWithoutRole);
    }

    public function fallbackQuestion(string $targetPosition, ?string $requiredType, string $latestAnswer, bool $isFinal = false): ?string
    {
        if (! $this->isMaritimeDeckRole($targetPosition)) {
            return null;
        }

        $position = trim($targetPosition);
        $topic = $this->answerTopic($latestAnswer);
        $type = ucfirst(strtolower(trim((string) $requiredType)));

        $question = match ($type) {
            'Behavioral' => $topic === null
                ? "Can you describe any experience following a safety procedure in work or training, or explain how you would learn one for vessel duties as a {$position}?"
                : "Can you describe any experience or training you have had with {$topic['task']}, or explain how you would learn that task as a {$position}?",
            'Situational' => $topic === null
                ? "If you noticed an unsafe condition during a deck task on board a vessel in the {$position} role, what would you do first?"
                : "If you noticed a safety concern while {$topic['activity']} in the {$position} role, what would you do first to protect the crew and vessel?",
            'Technical' => $topic === null
                ? "What procedure would you follow in the {$position} role to prepare for a deck task on board a vessel and check for hazards?"
                : "What procedure or equipment checks would you use for {$topic['task']} in the {$position} role to keep the vessel and crew safe?",
            'Personal' => $topic === null
                ? "Which strength would help you work safely with a vessel's deck crew as a {$position}?"
                : "Which of your strengths would help you carry out {$topic['task']} safely as a {$position}?",
            default => $topic === null
                ? "What experience or training, if any, best shows your readiness for safe deck work as a {$position}?"
                : "What have you learned, if anything, about {$topic['task']}, and what would you still need to learn before doing it as a {$position}?",
        };

        return $isFinal ? 'For my final question, '.lcfirst($question) : $question;
    }

    public function roleGuidance(string $targetPosition): string
    {
        if (! $this->isMaritimeDeckRole($targetPosition)) {
            return '';
        }

        return 'For this seaman or deck seafarer interview, use realistic vessel safety, watch or lookout, mooring and berthing, deck maintenance, cargo, emergency drill, and crew communication situations when relevant to the stated position. Match the exact rank and job description. Do not imply prior sea service, certifications, licenses, vessel type, or duties the candidate has not provided.';
    }

    private function isMaritimeDeckRole(string $position): bool
    {
        return preg_match('/\b(?:seaman|seafarer|sailor|deck\s*(?:hand|crew|cadet|officer|rating)|able\s+(?:bodied\s+)?seaman|ordinary\s+seaman|bosun|boatswain|(?:chief|first|second|third)\s+mate|ship(?:board)?\s+crew)\b/i', $position) === 1;
    }

    private function hasUnrelatedSpecializedTask(string $question, string $role, string $jobDescription): bool
    {
        foreach (self::SPECIALIZED_TASK_DOMAINS as $domain) {
            if (! preg_match($domain['task'], $question)) {
                continue;
            }

            if (preg_match($domain['role'], $role) || ($jobDescription !== '' && preg_match($domain['task'], $jobDescription))) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function hasMaritimeTask(string $text): bool
    {
        return preg_match('/\b(?:seamanship|maritime|ship|shipboard|vessel|onboard|on board|aboard|deck|bridge|watchkeeping|watch\s*(?:duty|handover|keeping)|lookout|helm|steering|navigat\w*|chart|compass|cargo|mooring|moor|berth\w*|anchor\w*|port\s+(?:arrival|departure|operation)|harbo(?:u)?r|rope|rigging|line handling|lifeboat|life raft|muster|fire drill|safety drill|emergency drill|safety equipment|personal protective equipment|ppe|crew safety|captain|deck maintenance|deck inspection)\b/i', $text) === 1;
    }

    private function answerTopic(string $answer): ?array
    {
        $answer = $this->normalize($answer);
        if ($this->answerUnavailable($answer)
            || preg_match('/\b(?:never|have not|has not|had not|did not|not yet|no experience|without experience|haven.t|hasn.t|hadn.t|didn.t)\b/i', $answer)) {
            return null;
        }

        $topics = [
            '/\b(?:lifeboat|life raft|flares|safety equipment)\b/' => ['task' => 'a lifeboat or safety equipment inspection', 'activity' => 'inspecting lifeboat or safety equipment'],
            '/\b(?:moor\w*|berth\w*|line handling)\b/' => ['task' => 'a mooring or berthing operation', 'activity' => 'handling mooring lines'],
            '/\b(?:watchkeeping|lookout|bridge watch|helm|navigation|navigating)\b/' => ['task' => 'a bridge or lookout watch', 'activity' => 'standing a bridge or lookout watch'],
            '/\b(?:cargo|loading|unloading|lashing)\b/' => ['task' => 'cargo handling', 'activity' => 'handling cargo'],
            '/\b(?:anchor\w*)\b/' => ['task' => 'an anchoring operation', 'activity' => 'preparing for anchoring'],
            '/\b(?:emergency|muster|fire drill|lifeboat|life raft)\b/' => ['task' => 'an emergency drill', 'activity' => 'responding to an emergency drill'],
            '/\b(?:maintenance|inspection|inspected|equipment check|checked equipment)\b/' => ['task' => 'deck equipment inspection or maintenance', 'activity' => 'inspecting deck equipment'],
            '/\b(?:weather|storm|rough seas)\b/' => ['task' => 'work during changing weather at sea', 'activity' => 'working in changing weather at sea'],
            '/\b(?:crew|teamwork|communication|communicated)\b/' => ['task' => 'communicating with the vessel crew', 'activity' => 'coordinating with the vessel crew'],
        ];

        foreach ($topics as $pattern => $topic) {
            if (preg_match($pattern, $answer)) {
                return $topic;
            }
        }

        return null;
    }

    private function answerUnavailable(string $answer): bool
    {
        return $answer === ''
            || str_starts_with($answer, '(skipped or no answer)')
            || str_starts_with($answer, '(no answer before the time limit)')
            || str_starts_with($answer, '[voice answer saved for playback.');
    }

    private function lastQuestionClause(string $reply): string
    {
        if (preg_match('/(?:^|[.!;]\s+)([^.!;?]*\?)\s*$/u', $reply, $matches)) {
            return trim($matches[1]);
        }

        return str_contains($reply, '?') ? trim($reply) : '';
    }

    private function isGenericEvidenceProbe(string $question): bool
    {
        $tokens = $this->contentTokens($question, '');
        $generic = [
            'action', 'apply', 'approach', 'captain', 'challenge', 'check', 'choice', 'claim',
            'concern', 'confirm', 'crew', 'decision', 'describe', 'different', 'ensure',
            'evidence', 'explain', 'final', 'fit', 'follow', 'followed', 'handle', 'handled', 'happen',
            'happened', 'impact', 'improve', 'improved', 'issue', 'learn', 'learned',
            'lesson', 'measure', 'measured', 'outcome', 'personally', 'prepare',
            'prepared', 'process', 'proof', 'prove', 'remember', 'report', 'resolve',
            'responsibility', 'safety', 'show', 'step', 'steps', 'strongest',
            'support', 'tell', 'tradeoff', 'verify', 'want', 'weigh',
            'choose', 'consider', 'difference', 'difficult', 'first', 'most', 'next', 'other', 'part', 'whether',
            'method', 'investigate', 'problem', 'test', 'task', 'similar', 'situation', 'came', 'work', 'respond',
            'strength', 'background', 'help', 'helped', 'interests', 'kind', 'skill', 'develop', 'related',
            'school', 'training', 'everyday', 'life', 'draw', 'taking', 'responsibility', 'started', 'prepare',
            'approaches', 'compare', 'choosing', 'prioritize', 'decide', 'against', 'main', 'reason', 'rule',
            'another', 'failed', 'troubleshooting', 'guide', 'adapt', 'procedure', 'larger', 'someone', 'disagreed',
        ];

        return count(array_diff($tokens, $this->contentTokens(implode(' ', $generic), ''))) === 0;
    }

    private function contentTokens(string $text, string $position): array
    {
        preg_match_all('/\b[a-z][a-z0-9]{3,}\b/u', $this->normalize($text), $matches);
        $positionTokens = preg_split('/[^a-z0-9]+/u', $this->normalize($position)) ?: [];

        $tokens = array_diff($matches[0], self::ANSWER_STOP_WORDS, $positionTokens);

        return array_values(array_unique(array_map(function (string $token): string {
            $token = preg_replace('/(?:ing|ed|es|s)$/u', '', $token) ?? $token;

            return strlen($token) > 4 ? rtrim($token, 'e') : $token;
        }, $tokens)));
    }

    private function normalize(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? $text));
    }
}
