<?php

if (! function_exists('mobile_view')) {
    /**
     * Resolve and return the correct desktop or mobile Blade view.
     *
     * This is the procedural equivalent of Controller::mobileView() for use
     * in route closures and other non-controller contexts.
     *
     * Resolution order:
     *   1. `desktop.<view>` when the request is desktop
     *   2. `mobile.<view>`  when the request is mobile
     *   3. Falls back to the original `<view>` if the prefixed variant doesn't exist.
     *
     * @param  string  $view   Dot-notation view name, e.g. 'interview.setup'
     * @param  array   $data   Data to pass to the view
     * @return \Illuminate\View\View
     */
    function mobile_view(string $view, array $data = []): \Illuminate\View\View
    {
        $isMobile = (bool) request()->attributes->get('is_mobile', false);
        $prefix   = $isMobile ? 'mobile' : 'desktop';
        $resolved = "{$prefix}.{$view}";

        if (! \Illuminate\Support\Facades\View::exists($resolved)) {
            $resolved = $view;
        }

        return view($resolved, array_merge($data, ['isMobile' => $isMobile]));
    }
}

if (! function_exists('admin_without_restricted_country_text')) {
    function admin_without_restricted_country_text(?string $text): string
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        $clean = preg_replace('/\bphilippines?\b/i', '', $text) ?? $text;
        $clean = preg_replace('/[ \t]{2,}/', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s+([,.;:!?])/', '$1', $clean) ?? $clean;
        $clean = preg_replace('/([({\[])\s+/', '$1', $clean) ?? $clean;
        $clean = preg_replace('/\s+([)}\]])/', '$1', $clean) ?? $clean;

        return trim($clean);
    }
}

if (! function_exists('review_question_text')) {
    function review_question_text(mixed $source): string
    {
        if (is_string($source)) {
            return trim($source);
        }

        foreach (['question.question_text', 'question_text', 'question'] as $key) {
            $value = data_get($source, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }
}

if (! function_exists('review_feedback_without_question_text')) {
    function review_feedback_without_question_text(?string $text, mixed $questionSource = null): string
    {
        $clean = trim((string) $text);

        if ($clean === '') {
            return '';
        }

        $clean = review_remove_prompt_quote_references($clean);
        $questionText = review_question_text($questionSource);

        if ($questionText !== '') {
            $quotedQuestion = preg_quote($questionText, '/');
            $clean = preg_replace('/(^|[\s(\[])(?:For|Regarding|About|On|In response to)\s+[\'"\x{201C}\x{201D}\x{2018}\x{2019}]?' . $quotedQuestion . '[\'"\x{201C}\x{201D}\x{2018}\x{2019}]?\s*,?\s*/iu', '$1', $clean) ?? $clean;
            $clean = preg_replace('/[\'"\x{201C}\x{201D}\x{2018}\x{2019}]\s*' . $quotedQuestion . '\s*[\'"\x{201C}\x{201D}\x{2018}\x{2019}]/iu', '', $clean) ?? $clean;
            $clean = preg_replace('/' . $quotedQuestion . '/iu', '', $clean) ?? $clean;
        }

        $clean = review_remove_prompt_quote_references($clean);
        $clean = preg_replace('/\bQuestion focus\b/u', 'Answer focus', $clean) ?? $clean;
        $clean = preg_replace('/\bquestion focus\b/u', 'answer focus', $clean) ?? $clean;
        $clean = preg_replace('/\bQuestion results\b/u', 'Answer results', $clean) ?? $clean;
        $clean = preg_replace('/\bquestion results\b/u', 'answer results', $clean) ?? $clean;
        $clean = preg_replace('/\bquestion match\b/iu', 'answer match', $clean) ?? $clean;
        $clean = preg_replace('/\bquestion notes?\b/iu', 'answer notes', $clean) ?? $clean;
        $clean = preg_replace('/\bquestion guide\b/iu', 'answer guide', $clean) ?? $clean;
        $clean = preg_replace('/\banswer each question\b/iu', 'complete each answer', $clean) ?? $clean;
        $clean = preg_replace('/\banswer the exact question\b/iu', 'answer directly', $clean) ?? $clean;
        $clean = preg_replace('/\banswers? the exact question\b/iu', 'answers directly', $clean) ?? $clean;
        $clean = preg_replace('/\bexact question\b/iu', 'answer focus', $clean) ?? $clean;
        $clean = preg_replace('/\beach question\b/iu', 'each answer', $clean) ?? $clean;
        $clean = preg_replace('/\bwhen the question allows\b/iu', 'when the answer supports it', $clean) ?? $clean;
        $clean = preg_replace('/\bper-question\b/iu', 'per-answer', $clean) ?? $clean;
        $clean = preg_replace('/\b(\d+)\s+of\s+(\d+)\s+questions?\s+need\b/iu', '$1 of $2 answers need', $clean) ?? $clean;
        $clean = preg_replace('/\bskipped questions?\b/iu', 'skipped answers', $clean) ?? $clean;
        $clean = preg_replace('/\bmarked questions?\b/iu', 'marked answers', $clean) ?? $clean;
        $clean = preg_replace('/\bthis question\b/iu', 'this answer', $clean) ?? $clean;
        $clean = preg_replace('/\bthe question\b/iu', 'the answer', $clean) ?? $clean;
        $clean = preg_replace('/\bquestions\b/iu', 'answers', $clean) ?? $clean;
        $clean = preg_replace('/\s+([,.;:!?])/u', '$1', $clean) ?? $clean;
        $clean = preg_replace('/([({\[])\s+/u', '$1', $clean) ?? $clean;
        $clean = preg_replace('/\s+([)}\]])/u', '$1', $clean) ?? $clean;
        $clean = preg_replace('/\s{2,}/u', ' ', $clean) ?? $clean;
        $clean = trim($clean, " \t\n\r\0\x0B,;-:");

        if ($clean !== '' && preg_match('/^\p{Ll}/u', $clean) === 1) {
            $clean = mb_strtoupper(mb_substr($clean, 0, 1)) . mb_substr($clean, 1);
        }

        return $clean;
    }
}

if (! function_exists('review_remove_prompt_quote_references')) {
    function review_remove_prompt_quote_references(string $text): string
    {
        $quotedText = '(?:"[^"]{12,}"|\x{201C}[^\x{201D}]{12,}\x{201D}|\'[^\']{12,}\'|\x{2018}[^\x{2019}]{12,}\x{2019})';
        $anyQuotedText = '(?:"[^"]*"|\x{201C}[^\x{201D}]*\x{201D}|\'[^\']*\'|\x{2018}[^\x{2019}]*\x{2019})';

        $clean = preg_replace('/\b(?:the\s+)?(?:answer|response|reply)\s+text\s+was\s+' . $anyQuotedText . '\s*,?\s+but\s+it\s+/iu', 'The answer ', $text) ?? $text;
        $clean = preg_replace('/\b(?:the\s+)?(?:answer|response|reply)\s+text\s+was\s+' . $anyQuotedText . '\s*,?\s+but\s+/iu', 'The answer ', $clean) ?? $clean;
        $clean = preg_replace('/\b(?:the\s+)?(?:answer|response|reply)\s+text\s+was\s+' . $anyQuotedText . '\s*[.;:]?\s*/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/\s+to\s+show\s+how\s+well\s+it\s+answered\s+the\s+question\b/iu', '', $clean) ?? $clean;

        $clean = preg_replace_callback(
            '/(^|[.!?:]\s+)\s*(?:For|Regarding|About|On|In response to)\s+(?:the\s+)?(?:answer|response|reply|question|prompt|interview\s+question|interview\s+prompt)\s+(' . $quotedText . ')\s*[:,\-]?\s*/iu',
            static function (array $matches): string {
                $quoted = review_unquote_feedback_text($matches[2] ?? '');

                return review_quoted_text_looks_like_prompt($quoted) ? ($matches[1] ?? '') : ($matches[0] ?? '');
            },
            $clean
        ) ?? $clean;

        $clean = preg_replace_callback(
            '/(^|[.!?:]\s+)\s*(?:For|Regarding|About|On|In response to)\s+(' . $quotedText . ')\s*[:,\-]?\s*/iu',
            static function (array $matches): string {
                $quoted = review_unquote_feedback_text($matches[2] ?? '');

                return review_quoted_text_looks_like_prompt($quoted) ? ($matches[1] ?? '') : ($matches[0] ?? '');
            },
            $clean
        ) ?? $clean;

        $clean = preg_replace_callback(
            '/\b(weak\s+link|connection|match|link|tie)\s+to\s+(' . $quotedText . ')/iu',
            static function (array $matches): string {
                $quoted = review_unquote_feedback_text($matches[2] ?? '');

                return review_quoted_text_looks_like_prompt($quoted)
                    ? trim((string) ($matches[1] ?? 'link')) . ' in this answer'
                    : ($matches[0] ?? '');
            },
            $clean
        ) ?? $clean;

        $clean = preg_replace_callback(
            '/\b((?:answer\s+)?draft\s+based\s+on\s+your\s+facts|based\s+on\s+your\s+facts)\s+for\s+(' . $quotedText . ')/iu',
            static function (array $matches): string {
                $quoted = review_unquote_feedback_text($matches[2] ?? '');

                return review_quoted_text_looks_like_prompt($quoted)
                    ? trim((string) ($matches[1] ?? ''))
                    : ($matches[0] ?? '');
            },
            $clean
        ) ?? $clean;

        return $clean;
    }
}

if (! function_exists('review_unquote_feedback_text')) {
    function review_unquote_feedback_text(string $text): string
    {
        $clean = trim($text);
        $clean = preg_replace('/^(?:[\'"]|\x{201C}|\x{2018})|(?:[\'"]|\x{201D}|\x{2019})$/u', '', $clean) ?? $clean;

        return trim($clean);
    }
}

if (! function_exists('review_quoted_text_looks_like_prompt')) {
    function review_quoted_text_looks_like_prompt(string $text): bool
    {
        $clean = trim($text);

        if ($clean === '') {
            return false;
        }

        if (review_text_looks_like_question($clean)) {
            return true;
        }

        return preg_match('/\b(?:good\s+(?:morning|afternoon|evening)|interview\s+(?:question|prompt)|question|prompt|tell\s+me|describe|explain|walk\s+me\s+through|introduce\s+yourself|please\s+share|what\s+(?:is|are|would|did|do|does|can|could)|how\s+(?:do|did|would|can|could)|why\s+(?:do|did|are|would)|when\s+(?:did|would|can|could)|where\s+(?:did|would|can|could))\b/iu', $clean) === 1;
    }
}

if (! function_exists('review_answer_text')) {
    function review_answer_text(mixed $source): string
    {
        if (is_string($source)) {
            return trim($source);
        }

        foreach (['answer_text', 'answer', 'delivery_transcript', 'transcript'] as $key) {
            $value = data_get($source, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }
}

if (! function_exists('review_text_word_count')) {
    function review_text_word_count(string $text): int
    {
        preg_match_all('/\b[\pL\pN][\pL\pN\'-]*\b/u', $text, $matches);

        return count($matches[0] ?? []);
    }
}

if (! function_exists('review_normalized_text_key')) {
    function review_normalized_text_key(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\pL\pN]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}

if (! function_exists('review_meaningful_words')) {
    function review_meaningful_words(string $text): array
    {
        preg_match_all('/[\pL][\pL\pN\'-]{2,}/u', mb_strtolower($text), $matches);
        $stopWords = [
            'about', 'answer', 'because', 'before', 'candidate', 'could', 'describe',
            'does', 'during', 'explain', 'from', 'have', 'interview', 'question',
            'prompt', 'that', 'the', 'this', 'what', 'when', 'where', 'which', 'while',
            'with', 'would', 'you', 'your',
        ];

        return array_values(array_unique(array_diff($matches[0] ?? [], $stopWords)));
    }
}

if (! function_exists('review_text_looks_like_question')) {
    function review_text_looks_like_question(string $text, mixed $questionSource = null): bool
    {
        $clean = trim($text);
        if ($clean === '') {
            return true;
        }

        if (preg_match('/^\s*(?:question|prompt|interview\s+prompt|interview\s+question)\s*[:\-]/iu', $clean) === 1) {
            return true;
        }

        $questionText = review_question_text($questionSource);
        if ($questionText !== '') {
            $textKey = review_normalized_text_key($clean);
            $questionKey = review_normalized_text_key($questionText);

            if ($textKey !== '' && $questionKey !== '') {
                if ($textKey === $questionKey) {
                    return true;
                }

                $textWords = review_meaningful_words($clean);
                $questionWords = review_meaningful_words($questionText);
                $overlap = count(array_intersect($textWords, $questionWords));
                if ($textWords !== []
                    && $questionWords !== []
                    && $overlap / max(1, count($textWords)) >= 0.75
                    && review_text_word_count($clean) <= review_text_word_count($questionText) + 4
                ) {
                    return true;
                }
            }
        }

        return preg_match('/\?\s*$/u', $clean) === 1
            && preg_match('/\b(?:I|we|my|our)\b/iu', $clean) !== 1;
    }
}

if (! function_exists('review_sentence_text')) {
    function review_sentence_text(string $text): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($clean === '') {
            return '';
        }

        if (preg_match('/[.!?]$/u', $clean) !== 1) {
            $clean .= '.';
        }

        return $clean;
    }
}

if (! function_exists('review_answer_is_usable_for_better_answer')) {
    function review_answer_is_usable_for_better_answer(string $answerText, mixed $questionSource = null): bool
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $answerText) ?? $answerText);

        return $clean !== ''
            && review_text_word_count($clean) >= 4
            && preg_match('/^(?:ok|okay|yes|no|none|n\/a|na)$/iu', $clean) !== 1
            && ! review_text_looks_like_question($clean, $questionSource);
    }
}

if (! function_exists('review_question_based_better_answer')) {
    function review_question_based_better_answer(string $questionText, string $answerText = ''): string
    {
        $question = trim(preg_replace('/\s+/u', ' ', $questionText) ?? $questionText);
        $lowerQuestion = mb_strtolower($question, 'UTF-8');
        $baseAnswer = review_sentence_text($answerText);
        if ($baseAnswer !== '' && preg_match('/^\s*(?:I|we|my|our)\b/iu', $baseAnswer) !== 1) {
            $baseAnswer = 'I would answer: ' . $baseAnswer;
        }

        if (preg_match('/\b(?:introduce yourself|tell me about yourself|background)\b/iu', $question) === 1) {
            return $baseAnswer !== ''
                ? $baseAnswer . ' My background connects to this role through [relevant experience or skill], and I can contribute by [specific value you can offer].'
                : 'Hi, I am [your name], currently based in [your location]. My background is in [your relevant experience], and I am interested in this role because [skill or strength connected to the job].';
        }

        if (preg_match('/\b(?:irate|angry|upset|customer|client|complaint|concern)\b/iu', $question) === 1) {
            return $baseAnswer !== ''
                ? $baseAnswer . ' I stayed calm, confirmed the concern, took [specific action], and followed up with [result or lesson].'
                : 'In a customer situation, I would first listen carefully, confirm the main concern, and stay calm. Then I would take [specific action], explain the next step clearly, and follow up with [result or lesson].';
        }

        if (preg_match('/\b(?:tell me about a time|describe a time|give an example|example of|challenge|conflict|handled|helped|solved|worked under pressure|difficult)\b/iu', $question) === 1) {
            return $baseAnswer !== ''
                ? $baseAnswer . ' The situation was [specific context], my action was [specific action], and the result was [clear result or lesson].'
                : 'In a previous situation, [specific context happened]. My task was to [responsibility], so I [specific action you personally took]. As a result, [clear outcome or lesson].';
        }

        if (preg_match('/\b(?:weakness|improving|improve)\b/iu', $lowerQuestion) === 1) {
            return $baseAnswer !== ''
                ? $baseAnswer . ' I am improving it by [specific action], and I can show progress through [result or habit].'
                : 'One weakness I am improving is [real weakness]. I noticed it when [specific context], so I now [specific improvement action]. I can see progress because [result or habit].';
        }

        if (preg_match('/\b(?:strength|strongest|good at|best skill)\b/iu', $lowerQuestion) === 1) {
            return $baseAnswer !== ''
                ? $baseAnswer . ' A strong proof point is [specific example], where I used this strength to [result].'
                : 'One of my strongest skills is [strength]. I used it when [specific example], where I [specific action] and helped achieve [result].';
        }

        if (preg_match('/\b(?:why do you want|why are you interested|why this role|why our company|motivation)\b/iu', $lowerQuestion) === 1) {
            return $baseAnswer !== ''
                ? $baseAnswer . ' This role interests me because [specific reason], and I can contribute through [relevant skill or experience].'
                : 'I want this role because [specific reason connected to the company or work]. My experience in [relevant skill] fits the role, and I can contribute by [specific contribution].';
        }

        if (preg_match('/\b(?:why should we hire|hire you|best candidate)\b/iu', $lowerQuestion) === 1) {
            return $baseAnswer !== ''
                ? $baseAnswer . ' The strongest reason to hire me is [specific proof], which shows I can [role-related result].'
                : 'You should hire me because I bring [relevant skill], [experience], and [work habit]. For example, [brief proof], and I would use those strengths to [role-related result].';
        }

        if (preg_match('/\b(?:how would you|how do you|diagnose|troubleshoot|process|approach|steps?)\b/iu', $lowerQuestion) === 1) {
            return $baseAnswer !== ''
                ? $baseAnswer . ' I would start with [first step], take [specific action], and check the result by [validation step].'
                : 'I would start by [first step], then [specific action or analysis], and finally [how you would check the result]. This approach helps me solve the problem clearly and avoid guessing.';
        }

        return $baseAnswer !== ''
            ? $baseAnswer . ' For example, [specific example], I took [specific action], and the result was [clear result or lesson].'
            : 'My direct answer is [clear answer to the question]. For example, [specific example], I took [specific action], and the result was [clear result or lesson].';
    }
}

if (! function_exists('review_better_answer_fallback')) {
    function review_better_answer_fallback(mixed $answerSource = null, mixed $questionSource = null): string
    {
        $questionText = review_question_text($questionSource);
        $answerText = review_answer_text($answerSource);
        if (! review_answer_is_usable_for_better_answer($answerText, $questionSource)) {
            if ($questionText !== '') {
                return review_question_based_better_answer($questionText);
            }

            return 'No AI-enhanced answer was generated yet. Submit a complete answer so the Better Answer can be based on your real details for this question.';
        }

        $answerText = trim(preg_replace('/\s+/u', ' ', $answerText) ?? $answerText);

        if ($questionText !== '') {
            return review_question_based_better_answer($questionText, $answerText);
        }

        return review_question_based_better_answer('', $answerText);
    }
}

if (! function_exists('review_better_answer_text')) {
    function review_better_answer_text(?string $text, mixed $answerSource = null, mixed $questionSource = null): string
    {
        $questionSource ??= $answerSource;
        $clean = review_feedback_without_question_text((string) $text, $questionSource);
        $clean = preg_replace('/^\s*(?:(?:suggested|sample|better)\s+)?(?:better\s+)?(?:answer|response|example|draft)\s*[:\-]\s*/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/^\s*(?:I\s+would\s+answer|I\s+would\s+say)\s*[:\-]\s*/iu', '', $clean) ?? $clean;
        $clean = trim($clean);
        $looksLikeAdvice = preg_match('/^\s*(?:a\s+stronger\s+answer\s+would|the\s+answer\s+should|you\s+should|try\s+to|make\s+sure|add|include|use|practice)\b/iu', $clean) === 1
            && preg_match('/\b(?:I|we|my|our)\b/iu', $clean) !== 1;

        if ($clean !== ''
            && review_text_word_count($clean) >= 5
            && ! review_text_looks_like_question($clean, $questionSource)
            && ! $looksLikeAdvice
        ) {
            return $clean;
        }

        return review_better_answer_fallback($answerSource, $questionSource);
    }
}
