<?php

namespace App\Services;

/**
 * A conservative topic check for AI Coach replies. This catches clear
 * in-domain drift; it is not a semantic or factual-accuracy score.
 */
final class CoachRequestAlignmentService
{
    private const REQUEST_PATTERNS = [
        'score' => '/\b(?:readiness|score|rating|marka|puntos|iskor)\b/iu',
        'salary' => '/\b(?:salary|pay|compensation|wage|sahod|sweldo|suweldo)\b/iu',
        'career_gap' => '/\b(?:career|employment|resume|work)\s+gap\b|\bgap\s+in\s+(?:employment|my\s+resume|work)\b|\btime\s+(?:away|off)\s+from\s+work\b|\b(?:pahinga|pahulay)\s+(?:sa|gikan\s+sa)\s+trabaho\b/iu',
        'introduction' => '/\b(?:tell me about yourself|introduce yourself|self.introduction|pakilala|ipakilala)\b/iu',
        'weakness' => '/\b(?:weakness(?:es)?|kahinaan|kaluyahon)\b/iu',
        'strength' => '/\b(?:strengths?|lakas|kalig.on)\b/iu',
        'result' => '/\b(?:result|outcome|impact|lesson learned|naging resulta|resulta)\b/iu',
        'customer' => '/\b(?:customer|client|complaint|customer service|customer support|kustomer|kostumer|kliyente|liyente)\b/iu',
        'resume' => '/\b(?:resume|résumé|curriculum vitae|\bcv\b)\b/iu',
        'review' => '/\b(?:review|feedback)\s+(?:my|this|the)\s+(?:saved\s+)?(?:interview\s+)?answer\b|\b(?:my|this|the|saved|previous)\s+(?:[\pL\'-]+\s+){0,3}answer\b|\bwhat\s+(?:worked|should\s+i\s+improve)\b/iu',
    ];

    private const RESPONSE_PATTERNS = [
        'score' => '/\b(?:readiness|score|rating|criterion|criteria|marka|puntos|iskor|kahandaan|kaandam)\b/iu',
        'salary' => '/\b(?:salary|pay|compensation|wage|range|sahod|sweldo|suweldo)\b/iu',
        'career_gap' => '/\b(?:gap|break|time\s+(?:away|off)|period\s+without\s+work|pahinga|pahulay|walay\s+trabaho)\b/iu',
        'introduction' => '/\b(?:yourself|introduction|introduce|background|current\s+(?:role|training)|pakilala|sarili|kaugalingon|kasalukuyan|kasamtangan)\b/iu',
        'weakness' => '/\b(?:weakness|growth\s+area|area\s+(?:to|for)\s+improve|improv\w*|develop\w*|kahinaan|kaluyahon|pagbutihin|pauswagon)\b/iu',
        'strength' => '/\b(?:strength|skill|ability|competenc\w*|what\s+you\s+do\s+well|kasanayan|kahanas|lakas|kalig.on)\b/iu',
        'result' => '/\b(?:result|outcome|impact|lesson|effect|nangyari|resulta|bunga|kinahinatnan|natutuhan|nakat.onan)\b/iu',
        'customer' => '/\b(?:customer|client|complaint|support|service|kustomer|kostumer|kliyente|liyente|serbisyo)\b/iu',
        'resume' => '/\b(?:resume|résumé|cv|work\s+history|experience|employment|internship|document)\b/iu',
        'review' => '/\b(?:answer|action|outcome|result|evidence|feedback|practice|question|example|clarity|sagot|tubag|aksiyon|resulta|ginawa|gibuhat|halimbawa|pananglitan|tanong|pangutana)\b/iu',
    ];

    /** @return array<int, string> */
    public function focusAreas(string $message, bool $reviewContext = false): array
    {
        // A legacy Detailed Review handoff includes the full interview question
        // and answer. Those are evidence, not additional user requests.
        if (preg_match('/\bInterview question:\s*.*\bMy answer:/isu', $message) === 1) {
            $prefix = preg_split('/\bInterview question:\s*/iu', $message, 2)[0] ?? '';
            $suffix = preg_match('/\bPlease tell me\b.*$/isu', $message, $match) === 1
                ? $match[0]
                : '';
            $message = $prefix.' '.$suffix;
        }

        $areas = [];
        foreach (self::REQUEST_PATTERNS as $area => $pattern) {
            if (preg_match($pattern, $message) === 1) {
                $areas[] = $area;
            }
        }

        if (in_array('career_gap', $areas, true)) {
            $areas = array_values(array_diff($areas, ['resume']));
        }

        if ($areas === [] && $reviewContext) {
            $areas[] = 'review';
        }

        return $areas;
    }

    /** @param array<int, string> $focusAreas */
    public function addressesRequest(string $response, array $focusAreas): bool
    {
        // "My saved answer" identifies the context; a more specific topic in
        // the same question is the actual answer requirement.
        if (count($focusAreas) > 1) {
            $focusAreas = array_values(array_diff($focusAreas, ['review']));
        }

        foreach ($focusAreas as $area) {
            $pattern = self::RESPONSE_PATTERNS[$area] ?? null;
            if ($pattern !== null && preg_match($pattern, $response) !== 1) {
                return false;
            }
        }

        return true;
    }

    /** @param array<int, string> $focusAreas */
    public function switchesAwayFromReview(array $focusAreas): bool
    {
        if (in_array('review', $focusAreas, true)) {
            return false;
        }

        return array_intersect($focusAreas, [
            'score', 'salary', 'career_gap', 'introduction', 'weakness',
            'strength', 'customer', 'resume',
        ]) !== [];
    }

    /**
     * Keep only the current topic's earlier turns when leaving a saved-answer
     * review. A topic-free follow-up belongs to the last matching topic.
     *
     * @param array<int, array{role: string, content: string}> $history
     * @param array<int, string> $focusAreas
     * @return array<int, array{role: string, content: string}>
     */
    public function historyForFocus(array $history, array $focusAreas): array
    {
        $filtered = [];
        $matchingTopic = false;

        foreach ($history as $item) {
            if ($item['role'] === 'user') {
                $earlierFocus = $this->focusAreas($item['content']);
                if ($earlierFocus !== []) {
                    $matchingTopic = array_intersect($earlierFocus, $focusAreas) !== [];
                }
            }

            if ($matchingTopic) {
                $filtered[] = $item;
            }
        }

        return $filtered;
    }
}
