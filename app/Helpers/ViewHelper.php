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

        $questionText = review_question_text($questionSource);

        if ($questionText !== '') {
            $quotedQuestion = preg_quote($questionText, '/');
            $clean = preg_replace('/(^|[\s(\[])(?:For|Regarding|About|On|In response to)\s+[\'"\x{201C}\x{201D}\x{2018}\x{2019}]?' . $quotedQuestion . '[\'"\x{201C}\x{201D}\x{2018}\x{2019}]?\s*,?\s*/iu', '$1', $clean) ?? $clean;
            $clean = preg_replace('/[\'"\x{201C}\x{201D}\x{2018}\x{2019}]\s*' . $quotedQuestion . '\s*[\'"\x{201C}\x{201D}\x{2018}\x{2019}]/iu', '', $clean) ?? $clean;
            $clean = preg_replace('/' . $quotedQuestion . '/iu', '', $clean) ?? $clean;
        }

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

if (! function_exists('review_better_answer_fallback')) {
    function review_better_answer_fallback(mixed $answerSource = null, mixed $questionSource = null): string
    {
        $answerText = review_answer_text($answerSource);
        if ($answerText === '' || review_text_looks_like_question($answerText, $questionSource)) {
            return 'No better answer draft is available yet.';
        }

        $answerText = trim(preg_replace('/\s+/u', ' ', $answerText) ?? $answerText);
        $answerText = mb_strlen($answerText) > 420
            ? rtrim(mb_substr($answerText, 0, 417), " \t\n\r\0\x0B.,;:") . '...'
            : $answerText;

        if ($answerText !== '' && preg_match('/[.!?]$/u', $answerText) !== 1) {
            $answerText .= '.';
        }

        $draft = preg_match('/^\s*(?:I|we|my|our)\b/iu', $answerText) === 1
            ? $answerText
            : 'I would answer: ' . $answerText;

        if (preg_match('/\b(?:as a result|result(?:ed)?|outcome|resolved|improved|learned|lesson|led to|\d+(?:\.\d+)?%?)\b/iu', $draft) !== 1) {
            $draft .= ' I would close with [true result, effect, or lesson].';
        }

        return $draft;
    }
}

if (! function_exists('review_better_answer_text')) {
    function review_better_answer_text(?string $text, mixed $answerSource = null, mixed $questionSource = null): string
    {
        $questionSource ??= $answerSource;
        $clean = review_feedback_without_question_text((string) $text, $questionSource);
        $clean = preg_replace('/^\s*(?:(?:suggested|sample|better)\s+)?(?:better\s+)?(?:answer|response|example|draft)\s*[:\-]\s*/iu', '', $clean) ?? $clean;
        $clean = trim($clean);

        if ($clean !== ''
            && review_text_word_count($clean) >= 5
            && ! review_text_looks_like_question($clean, $questionSource)
        ) {
            return $clean;
        }

        return review_better_answer_fallback($answerSource, $questionSource);
    }
}
