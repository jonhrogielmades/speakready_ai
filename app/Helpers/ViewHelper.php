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
        $clean = preg_replace('/\bper-question\b/iu', 'per-answer', $clean) ?? $clean;
        $clean = preg_replace('/\bthis question\b/iu', 'this prompt', $clean) ?? $clean;
        $clean = preg_replace('/\bthe question\b/iu', 'the prompt', $clean) ?? $clean;
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
