<?php

namespace App\Services;

/**
 * Deterministic, fail-closed safeguards for free-form AI Coach responses.
 *
 * This gate does not claim that generated prose is statistically 95% accurate.
 * It requires at least 95% of the declared safeguards to pass and rejects every
 * detected unsupported personal fact, score, guarantee, or file-content claim.
 */
final class CoachReliabilityService
{
    public const VERSION = 'coach_reliability_v2';

    private const DEFAULT_MINIMUM_RELIABILITY_PERCENT = 95;

    private const MAX_RESPONSE_CHARACTERS = 12000;

    /**
     * @param  array<int, array<string, mixed>>  $attachmentContexts
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function evaluate(
        string $response,
        string $userMessage,
        array $attachmentContexts = [],
        array $context = []
    ): array {
        $response = trim($response);
        $normalizedResponse = $this->normalize($response);
        $evidence = $this->buildEvidence($userMessage, $attachmentContexts, $context);

        $checks = [
            'response_present' => $response !== '',
            'response_within_safe_bounds' => mb_strlen($response) <= self::MAX_RESPONSE_CHARACTERS,
            'text_integrity' => $this->hasSafeTextIntegrity($response),
            'no_hiring_or_accuracy_guarantee' => ! $this->containsUnsupportedGuarantee($normalizedResponse),
            'score_claims_grounded' => ! $this->containsUnverifiableScore($response, $evidence),
            'personal_claims_grounded' => ! $this->containsUnsupportedPersonalClaim($response, $evidence),
            'personal_numbers_grounded' => ! $this->containsUnsupportedPersonalNumber($response, $evidence),
            'high_risk_numbers_grounded' => ! $this->containsUnsupportedHighRiskNumber($response, $evidence),
            'external_source_claims_grounded' => ! $this->containsUnsupportedExternalSourceClaim($response),
            'response_scope_preserved' => ! $this->containsOutOfScopeContent($response),
            'attachment_claims_grounded' => ! $this->containsUnsupportedAttachmentClaim($response, $attachmentContexts),
            'attachment_access_truthful' => ! $this->containsIncorrectAttachmentAccessClaim($response, $attachmentContexts),
            'prompt_boundaries_preserved' => ! $this->containsPromptBoundaryLeak($response),
        ];

        $riskMap = [
            'response_present' => 'empty_response',
            'response_within_safe_bounds' => 'response_too_long',
            'text_integrity' => 'unsafe_response_text',
            'no_hiring_or_accuracy_guarantee' => 'unsupported_hiring_or_accuracy_guarantee',
            'score_claims_grounded' => 'unsupported_score_claim',
            'personal_claims_grounded' => 'unsupported_personal_fact',
            'personal_numbers_grounded' => 'unsupported_numeric_claim',
            'high_risk_numbers_grounded' => 'unsupported_high_risk_numeric_claim',
            'external_source_claims_grounded' => 'unsupported_external_source_claim',
            'response_scope_preserved' => 'out_of_scope_response',
            'attachment_claims_grounded' => 'unsupported_attachment_claim',
            'attachment_access_truthful' => 'incorrect_attachment_access_claim',
            'prompt_boundaries_preserved' => 'prompt_boundary_violation',
        ];

        $riskFlags = [];
        foreach ($checks as $check => $passed) {
            if (! $passed) {
                $riskFlags[] = $riskMap[$check];
            }
        }

        $checksPassed = count(array_filter($checks));
        $checksTotal = count($checks);
        $reliabilityPercent = $checksTotal > 0
            ? (int) round(($checksPassed / $checksTotal) * 100)
            : 0;
        $minimumReliabilityPercent = $this->minimumReliabilityPercent();
        $accepted = $riskFlags === [] && $reliabilityPercent >= $minimumReliabilityPercent;

        return [
            'version' => self::VERSION,
            'status' => $accepted ? 'passed' : 'blocked',
            'requires_local_fallback' => ! $accepted,
            'verification_required' => true,
            'accuracy_guaranteed' => false,
            'reliability_percent' => $reliabilityPercent,
            'minimum_reliability_percent' => $minimumReliabilityPercent,
            'checks_passed' => $checksPassed,
            'checks_total' => $checksTotal,
            'checks' => $checks,
            'risk_flags' => array_values(array_unique($riskFlags)),
            'score_basis' => 'deterministic_safeguards_not_statistical_accuracy',
            'evidence_summary' => $evidence['summary'],
        ];
    }

    private function minimumReliabilityPercent(): int
    {
        return max(95, min(100, (int) config(
            'services.ai_coach.min_reliability_percent',
            self::DEFAULT_MINIMUM_RELIABILITY_PERCENT
        )));
    }

    /**
     * Only user-supplied sources can ground personal claims. Previous AI
     * replies are deliberately excluded so an earlier hallucination cannot
     * become evidence for a later response.
     *
     * @param  array<int, array<string, mixed>>  $attachmentContexts
     * @param  array<string, mixed>  $context
     * @return array{normalized: string, summary: array<string, int>}
     */
    private function buildEvidence(string $userMessage, array $attachmentContexts, array $context): array
    {
        $parts = [];
        $currentEvidence = $this->affirmativeEvidence($userMessage);
        if ($currentEvidence !== '') {
            $parts[] = $currentEvidence;
        }
        $userHistoryCount = 0;

        foreach ((array) ($context['history'] ?? []) as $item) {
            if (! is_array($item) || strtolower(trim((string) ($item['role'] ?? ''))) !== 'user') {
                continue;
            }

            $content = trim((string) ($item['content'] ?? ''));
            $affirmativeContent = $this->affirmativeEvidence($content);
            if ($affirmativeContent !== '') {
                $parts[] = $affirmativeContent;
                $userHistoryCount++;
            }
        }

        // A target role is a preference, not proof of work history. Likewise,
        // an interviewer question is not evidence that its premise is true.
        $reviewAnswer = $this->affirmativeEvidence((string) ($context['review_answer'] ?? ''));
        if ($reviewAnswer !== '') {
            $parts[] = $reviewAnswer;
        }

        $readableAttachmentCount = 0;
        foreach ($attachmentContexts as $file) {
            if (! is_array($file)) {
                continue;
            }

            $readableText = trim((string) ($file['readable_text'] ?? ''));
            if ($readableText !== '') {
                $parts[] = $this->affirmativeEvidence($readableText);
                $readableAttachmentCount++;
            }
        }

        return [
            'normalized' => $this->normalize(implode("\n", $parts)),
            'summary' => [
                'current_user_messages' => $currentEvidence === '' ? 0 : 1,
                'prior_user_messages' => $userHistoryCount,
                'readable_attachments' => $readableAttachmentCount,
                'total_attachments' => count($attachmentContexts),
            ],
        ];
    }

    private function containsUnsupportedGuarantee(string $response): bool
    {
        if ($response === '') {
            return false;
        }

        $patterns = [
            '/\b(?:i\s+)?(?:guarantee|promise)\s+(?:that\s+)?(?:you|this|your)\b/iu',
            '/\b(?:you|your answer)\s+(?:are|is)\s+guaranteed\b/iu',
            '/\b(?:you(?:\'|’)?re|your answer\s+is)\s+guaranteed\b/iu',
            '/\byou(?:\s+will|(?:\'|’)?ll)\s+(?:definitely\s+)?(?:get\s+hired|be\s+hired|pass|succeed)\b/iu',
            '/\bdefinitely\s+(?:get\s+hired|be\s+hired|pass|succeed)\b/iu',
            '/\b\d{1,3}\s*%\s*(?:accurate|correct|certain|guaranteed|chance\s+(?:of|to)|probability\s+(?:of|to))\b/iu',
            '/\b100\s*%\s*(?:ready|to\s+(?:pass|succeed|get\s+hired))\b/iu',
            '/\b(?:completely|perfectly|fully)\s+accurate\b/iu',
            '/\b(?:you|your answer)\s+(?:are|is)\s+(?:certain|sure)\s+to\s+(?:pass|succeed|get\s+hired|be\s+hired)\b/iu',
            '/\b(?:this|your)\s+(?:answer|preparation)\s+(?:will|is\s+certain\s+to)\s+ensure\s+(?:that\s+)?you\s+(?:pass|succeed|get\s+hired)\b/iu',
            '/\b(?:garantisado|siguradong|tiyak\s+na)\s+(?:matatanggap|papasa|makukuha|madawat|makapasar)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $response, $matches, PREG_OFFSET_CAPTURE) === false) {
                continue;
            }

            foreach ($matches[0] ?? [] as $match) {
                $offset = (int) ($match[1] ?? 0);
                $prefix = mb_strcut($response, max(0, $offset - 24), min(24, $offset));
                if (! preg_match('/\b(?:no|not|never|cannot|can\'t|do\s+not|don\'t|nobody|no\s+one|without|hindi|walang|dili|walay)\b/iu', $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param array{normalized: string, summary: array<string, int>} $evidence */
    private function containsUnverifiableScore(string $response, array $evidence): bool
    {
        $patterns = [
            '/\b(?:(?:your|my|the|this)\s+)?(?<label>readiness|interview|answer|resume|communication|overall)\s+(?:score|rating)\s*(?:is|was|of|at|:|-)?\s*(?<value>\d{1,3})(?:\s*%|\s*\/\s*100)?\b/iu',
            '/\b(?:(?:your|my|the|this)\s+)?(?<label>score|rating)\s*(?:is|was|of|at|:|-)\s*(?<value>\d{1,3})(?:\s*%|\s*\/\s*100)?\b/iu',
            '/\b(?:you|your\s+answer)\s+(?:scored|rated)\s*(?<value>\d{1,3})(?:\s*%|\s*\/\s*100)?\b/iu',
            '/\b(?:i\s+)?(?:rate|score)\s+(?:you|your\s+answer)\s*(?:at|as|:|-)?\s*(?<value>\d{1,3})(?:\s*%|\s*\/\s*100)?\b/iu',
            '/\b(?:your|the)\s+(?<label>readiness|interview|answer|communication)\s*(?:is|:|-)\s*(?<value>\d{1,3})(?:\s*%|\s*\/\s*100)?\b/iu',
        ];

        foreach ($this->sentences($response) as $sentence) {
            if ($this->isClearlyHypothetical($sentence)) {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $sentence, $matches, PREG_SET_ORDER) === false) {
                    continue;
                }

                foreach ($matches as $match) {
                    $value = (string) ($match['value'] ?? '');
                    $label = $this->normalize((string) ($match['label'] ?? 'score'));
                    if ($value !== '' && ! $this->evidenceContainsScore($evidence['normalized'], $label, $value)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function evidenceContainsScore(string $evidence, string $label, string $value): bool
    {
        $quotedValue = preg_quote($value, '/');
        $labelPattern = in_array($label, ['score', 'rating'], true)
            ? '(?:score|rating)'
            : preg_quote($label, '/').'\s+(?:score|rating)';

        return preg_match('/\b'.$labelPattern.'\b.{0,30}\b'.$quotedValue.'(?:\s*%|\s*\/\s*100)?\b/iu', $evidence) === 1
            || preg_match('/\b'.$quotedValue.'(?:\s*%|\s*\/\s*100)?\b.{0,30}\b'.$labelPattern.'\b/iu', $evidence) === 1;
    }

    /** @param array{normalized: string, summary: array<string, int>} $evidence */
    private function containsUnsupportedPersonalClaim(string $response, array $evidence): bool
    {
        foreach ($this->sentences($response) as $sentence) {
            if (! $this->isPersonalFactSentence($sentence) || $this->isClearlyHypothetical($sentence)) {
                continue;
            }

            if (! $this->claimHasEvidenceOverlap($sentence, $evidence['normalized'])) {
                return true;
            }
        }

        return false;
    }

    /** @param array{normalized: string, summary: array<string, int>} $evidence */
    private function containsUnsupportedPersonalNumber(string $response, array $evidence): bool
    {
        foreach ($this->sentences($response) as $sentence) {
            if (! $this->isPersonalFactSentence($sentence) || $this->isClearlyHypothetical($sentence)) {
                continue;
            }

            preg_match_all('/(?<![\pL\pN])(?:[$₱£€]\s*)?\d+(?:[.,]\d+)*(?:\s*%|\s*(?:years?|months?|days?|hours?|minutes?|customers?|clients?|tickets?|calls?|tasks?|people|members?))?/iu', $sentence, $numbers);
            foreach ($numbers[0] ?? [] as $number) {
                if (! $this->evidenceContainsNumber($evidence['normalized'], (string) $number)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param array{normalized: string, summary: array<string, int>} $evidence */
    private function containsUnsupportedHighRiskNumber(string $response, array $evidence): bool
    {
        preg_match_all('/(?:[$₱£€]\s*\d+(?:[.,]\d+)*(?:\s*[kKmM])?|\b\d+(?:[.,]\d+)*\s*(?:%(?![\pL\pN])|percent\b)|\b(?:19|20)\d{2}\b)/iu', $response, $matches);

        foreach ($matches[0] ?? [] as $number) {
            if (! $this->evidenceContainsHighRiskNumber($evidence['normalized'], (string) $number)) {
                return true;
            }
        }

        return false;
    }

    private function containsUnsupportedExternalSourceClaim(string $response): bool
    {
        return preg_match('/\b(?:research|studies|statistics|industry\s+data|experts)\s+(?:shows?|proves?|confirms?|indicates?|suggests?)\b|\baccording\s+to\s+(?!your\b|the\s+(?:provided|uploaded|job\s+description|resume|cv|file|attachment)\b)|https?:\/\/|\bwww\./iu', $response) === 1;
    }

    private function containsOutOfScopeContent(string $response): bool
    {
        $scannable = [];
        foreach ($this->sentences($response) as $sentence) {
            if (preg_match('/\b(?:i|we)\s+(?:cannot|can\'t|will\s+not|won\'t|do\s+not)\s+(?:help|provide|assist)\b/iu', $sentence) !== 1) {
                $scannable[] = $sentence;

                continue;
            }

            $contrast = preg_split('/\b(?:but|however)\b/iu', $sentence, 2);
            if (count($contrast) === 2) {
                $scannable[] = $contrast[1];
            }
        }

        $normalized = $this->normalize(implode(' ', $scannable));

        preg_match_all('/\b(?:ingredients?|recipe|preheat|oven|bake|tablespoons?|teaspoons?|cups?\s+of)\b/iu', $normalized, $recipeSignals);
        $recipeDrift = count(array_unique($recipeSignals[0] ?? [])) >= 2;
        $medicalDrift = preg_match('/\b(?:diagnos(?:e|is)|prescription|dosage|symptoms?)\b.{0,80}\b(?:medicine|medication|\d+\s*mg|treatment|disease)\b|\b(?:medicine|medication|\d+\s*mg|treatment|disease)\b.{0,80}\b(?:diagnos(?:e|is)|prescription|dosage|symptoms?)\b/iu', $normalized) === 1;
        $financialDrift = preg_match('/\b(?:buy|sell|invest\s+in|trade)\b.{0,40}\b(?:stocks?|crypto|bitcoin|forex|securities)\b/iu', $normalized) === 1;
        $legalDrift = preg_match('/\b(?:legal\s+advice|file\s+(?:a\s+)?lawsuit|sue\s+(?:them|your|the)|evade\s+the\s+law)\b/iu', $normalized) === 1;
        $politicalDrift = preg_match('/\b(?:vote\s+for|campaign\s+for|support\s+the\s+(?:political\s+)?candidate)\b/iu', $normalized) === 1;
        $relationshipDrift = preg_match('/\b(?:dating\s+advice|win\s+(?:him|her|them)\s+back|make\s+(?:him|her|them)\s+jealous)\b/iu', $normalized) === 1;
        $malwareDrift = preg_match('/\b(?:ransomware|keylogger|steal\s+(?:passwords?|credentials)|bypass\s+antivirus|deploy\s+malware)\b/iu', $normalized) === 1;

        return $recipeDrift
            || $medicalDrift
            || $financialDrift
            || $legalDrift
            || $politicalDrift
            || $relationshipDrift
            || $malwareDrift;
    }

    private function isPersonalFactSentence(string $sentence): bool
    {
        $patterns = [
            '/\b(?:you(?:(?:\'|’)?ve)?|i|we|the\s+candidate|the\s+applicant)\s+(?:have\s+|has\s+)?(?:work|worked|earned|hold|completed|graduated|achieved|increased|reduced|managed|led|supervised|handled|served|resolved|created|built|delivered|received|won|studied|trained)\b/iu',
            '/\b(?:you|you(?:\'|’)?re|the\s+candidate|the\s+applicant)\s+(?:are\s+|were\s+|is\s+)?(?:certified|licensed|employed|a\s+graduate|an?\s+employee)\b/iu',
            '/\byou\s+have\s+(?:an?\s+|the\s+)?(?:[\pL\pN+#.-]+\s+){0,4}(?:years?\b|degree\b|certificate\b|certification\b|license\b|experience\b|background\b)/iu',
            '/\byou\s+(?:possess|hold)\s+(?:an?\s+|the\s+)?(?:[\pL\pN+#.-]+\s+){0,4}(?:degree|certificate|certification|license)\b/iu',
            '/\byour\s+(?:employer|company|school|degree|certificate|certification|license|experience|salary|tenure|achievement|result)\s+(?:is|was|shows|proves|confirms|includes)\b/iu',
            '/\baccording\s+to\s+your\s+(?:profile|resume|cv|certificate|answer)\b/iu',
            '/\b(?:nagtrabaho|nakatrabaho)\s+ka\b|\b(?:mayroon|meron)\s+kang?\b.{0,45}\b(?:karanasan|sertipiko|lisensya)\b/iu',
            '/\b(?:nagtrabaho\s+ka|aduna\s+kay|naa\s+kay|nakab-ot\s+nimo)\b/iu',
            '/\b[\pL][\pL\'-]*(?:\s+[\pL][\pL\'-]*){0,3}\s+(?:worked\s+(?:at|for)|earned\s+(?:an?\s+)?(?:degree|certificate|certification)|graduated\s+from|achieved\s+(?:a|an|the)\b)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sentence) === 1) {
                return true;
            }
        }

        return false;
    }

    private function claimHasEvidenceOverlap(string $claim, string $evidence): bool
    {
        $tokens = $this->meaningfulTokens($claim);
        if ($tokens === []) {
            return false;
        }

        $evidenceTokens = array_fill_keys($this->meaningfulTokens($evidence), true);
        $matched = count(array_filter($tokens, fn (string $token): bool => isset($evidenceTokens[$token])));
        $required = count($tokens) === 1
            ? 1
            : min(3, max(2, (int) ceil(count($tokens) * 0.6)));

        return $matched >= $required;
    }

    /** @param array<int, array<string, mixed>> $attachmentContexts */
    private function containsUnsupportedAttachmentClaim(string $response, array $attachmentContexts): bool
    {
        $readableEvidence = $this->readableAttachmentEvidence($attachmentContexts);

        foreach ($this->sentences($response) as $sentence) {
            if (preg_match('/\b(?:resume|cv|certificate|file|attachment|document|portfolio)\b.{0,45}\b(?:shows|proves|confirms|states|contains|demonstrates|lists|mentions|indicates|says)\b|\b(?:shows|proves|confirms|states|contains|demonstrates|lists|mentions|indicates)\b.{0,45}\b(?:resume|cv|certificate|file|attachment|document|portfolio)\b/iu', $sentence) !== 1) {
                continue;
            }

            if ($readableEvidence === '' || ! $this->claimHasEvidenceOverlap($sentence, $readableEvidence)) {
                return true;
            }

            preg_match_all('/(?<![\pL\pN])\d+(?:[.,]\d+)*(?:\s*%)?/u', $sentence, $numbers);
            foreach ($numbers[0] ?? [] as $number) {
                if (! $this->evidenceContainsNumber($readableEvidence, (string) $number)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param array<int, array<string, mixed>> $attachmentContexts */
    private function containsIncorrectAttachmentAccessClaim(string $response, array $attachmentContexts): bool
    {
        if ($attachmentContexts === []) {
            return false;
        }

        $hasReadableText = $this->hasReadableAttachmentText($attachmentContexts);

        if (! $hasReadableText && preg_match('/\b(?:i|we)\s+(?:reviewed|read|verified|confirmed|opened|examined|can\s+see|can\s+read|can\s+access)\s+(?:your|the|this)?\s*(?:resume|cv|certificate|file|attachment|document|portfolio)\b/iu', $response) === 1) {
            return true;
        }

        return $hasReadableText
            && preg_match('/\b(?:i|we)\s+(?:cannot|can\'t|could\s+not|couldn\'t|am\s+unable\s+to|are\s+unable\s+to)\s+(?:view|see|read|open|access)\s+(?:your|the|this)?\s*(?:resume|cv|certificate|file|attachment|document|portfolio)\b/iu', $response) === 1;
    }

    private function containsPromptBoundaryLeak(string $response): bool
    {
        return preg_match('/\b(?:ignore|disregard)\s+(?:all\s+)?(?:previous|earlier|system|developer)\s+(?:instructions|messages)\b|\b(?:system prompt|developer message|jailbreak)\b|SPEAKREADY\s+(?:USER\s+PROFILE|ANSWER\s+REVIEW)\s+(?:CONTEXT|HANDOFF)\s+JSON|UPLOADED\s+INTERVIEW-RELATED\s+FILE\s+CONTEXT\s+JSON|Reliability contract:/iu', $response) === 1;
    }

    private function hasSafeTextIntegrity(string $response): bool
    {
        if ($response === '') {
            return true;
        }

        return mb_check_encoding($response, 'UTF-8')
            && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $response) !== 1;
    }

    /** @return array<int, string> */
    private function sentences(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+|\R+/u', trim($text)) ?: [];

        return array_values(array_filter(array_map('trim', $sentences), fn (string $sentence): bool => $sentence !== ''));
    }

    private function isClearlyHypothetical(string $sentence): bool
    {
        return preg_match('/\b(?:if|suppose|imagine|hypothetical|for\s+example|example|sample|template|placeholder|replace\s+this|only\s+if\s+true)\b/iu', $sentence) === 1
            || str_contains($sentence, '[')
            || str_contains($sentence, '{');
    }

    /**
     * Questions, negated statements, and uncertainty are not affirmative proof
     * of a personal fact. A factual first-person clause remains usable when it
     * is followed by a question (for example, "I worked at Acme; how do I say it?").
     */
    private function affirmativeEvidence(string $text): string
    {
        $parts = [];

        foreach ($this->sentences($text) as $sentence) {
            if (preg_match('/\b(?:not|never|no\s+(?:work\s+)?experience|without|didn\'t|did\s+not|don\'t|do\s+not|haven\'t|have\s+not|wasn\'t|was\s+not|isn\'t|is\s+not|unsure|uncertain|maybe|possibly)\b/iu', $sentence) === 1) {
                continue;
            }

            $isQuestion = str_contains($sentence, '?')
                || preg_match('/^\s*(?:did|do|does|am|are|was|were|have|has|can|could|would|should|is)\b/iu', $sentence) === 1;
            $hasScoreValue = preg_match('/\bmy\s+(?:readiness|interview|answer|resume|communication|overall)?\s*(?:score|rating)\s+(?:is|was|of|at|:)\s*\d{1,3}\b/iu', $sentence) === 1;
            $asksWhetherScoreIsTrue = preg_match('/^\s*(?:is|was|did|do|does|am|are|have|has)\b/iu', $sentence) === 1;
            $hasExplicitAssertion = preg_match('/(?:^|[,;:]\s*)\s*(?:i|we)\s+(?:am|was|were|work|worked|have|had|earned|completed|achieved|managed|led|handled|served|created|built|received|studied|trained)\b/iu', $sentence) === 1
                || ($hasScoreValue && ! $asksWhetherScoreIsTrue);

            if ($isQuestion && ! $hasExplicitAssertion) {
                continue;
            }

            $parts[] = $sentence;
        }

        return trim(implode(' ', $parts));
    }

    /** @return array<int, string> */
    private function meaningfulTokens(string $text): array
    {
        preg_match_all('/[\pL\pN][\pL\pN\'-]{2,}/u', $this->normalize($text), $matches);
        $stopWords = array_fill_keys([
            'the', 'and', 'that', 'this', 'with', 'from', 'your', 'you', 'have', 'has', 'had', 'were', 'was', 'are', 'for', 'into', 'according',
            'resume', 'certificate', 'certification', 'file', 'attachment', 'document', 'portfolio', 'profile', 'answer', 'shows', 'show', 'proves',
            'confirms', 'states', 'contains', 'demonstrates', 'lists', 'mentions', 'indicates', 'says', 'worked', 'earned', 'completed', 'achieved',
            'managed', 'handled', 'served', 'created', 'built', 'delivered', 'received', 'trained', 'experience', 'background', 'years', 'year',
            'strong', 'good', 'great', 'reliable', 'clearly', 'also', 'their', 'there', 'about', 'only', 'what', 'when', 'where', 'which', 'who',
            'employer', 'company', 'school', 'degree', 'license', 'salary', 'tenure', 'achievement', 'result',
        ], true);

        return array_values(array_unique(array_filter(
            $matches[0] ?? [],
            fn (string $token): bool => ! isset($stopWords[$token]) && ! ctype_digit($token)
        )));
    }

    /** @param array<int, array<string, mixed>> $attachmentContexts */
    private function readableAttachmentEvidence(array $attachmentContexts): string
    {
        $parts = [];
        foreach ($attachmentContexts as $file) {
            if (! is_array($file)) {
                continue;
            }

            $readableText = trim((string) ($file['readable_text'] ?? ''));
            if ($readableText !== '') {
                $parts[] = $this->affirmativeEvidence($readableText);
            }
        }

        return $this->normalize(implode("\n", $parts));
    }

    /** @param array<int, array<string, mixed>> $attachmentContexts */
    private function hasReadableAttachmentText(array $attachmentContexts): bool
    {
        foreach ($attachmentContexts as $file) {
            if (is_array($file) && trim((string) ($file['readable_text'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(strip_tags($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function normalizeNumber(string $value): string
    {
        return preg_replace('/[^\pN%.,$₱£€]/u', '', mb_strtolower($value)) ?? '';
    }

    private function evidenceContainsHighRiskNumber(string $evidence, string $value): bool
    {
        $evidence = $this->normalize($evidence);

        if (preg_match('/(?<amount>\d+(?:[.,]\d+)*)\s*(?:%(?![\pL\pN])|percent\b)/iu', $value, $match) === 1) {
            $amount = preg_quote((string) $match['amount'], '/');

            return preg_match('/(?<!\pN)'.$amount.'\s*(?:%|percent)(?![\pL\pN])/iu', $evidence) === 1;
        }

        if (preg_match('/(?<currency>[$₱£€])\s*(?<amount>\d+(?:[.,]\d+)*)(?<scale>\s*[kKmM])?/u', $value, $match) === 1) {
            $currency = preg_quote((string) $match['currency'], '/');
            $amount = preg_quote((string) $match['amount'], '/');
            $scale = trim((string) ($match['scale'] ?? ''));
            $scalePattern = $scale === '' ? '' : '\s*'.preg_quote($scale, '/');

            return preg_match('/'.$currency.'\s*'.$amount.$scalePattern.'(?!\pN)/iu', $evidence) === 1;
        }

        return $this->evidenceContainsNumber($evidence, $value);
    }

    private function evidenceContainsNumber(string $evidence, string $number): bool
    {
        $needle = $this->normalizeNumber($number);
        if ($needle === '') {
            return true;
        }

        $normalizedEvidence = preg_replace('/([$₱£€])\s+(?=\d)/u', '$1', $this->normalize($evidence)) ?? $evidence;

        return preg_match('/(?<!\pN)'.preg_quote($needle, '/').'(?!\pN)/u', $normalizedEvidence) === 1;
    }
}
