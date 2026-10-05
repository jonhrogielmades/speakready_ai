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

if (! function_exists('review_question_sample_answer')) {
    function review_question_sample_answer(mixed $questionSource = null): string
    {
        $question = trim(preg_replace('/\s+/u', ' ', review_question_text($questionSource)) ?? review_question_text($questionSource));
        if ($question === '') {
            return '';
        }

        $lowerQuestion = mb_strtolower($question, 'UTF-8');
        $questionType = mb_strtolower(trim((string) data_get($questionSource, 'question.type', data_get($questionSource, 'type', ''))), 'UTF-8');
        $expectedGuide = trim(preg_replace(
            '/\s+/u',
            ' ',
            (string) data_get($questionSource, 'question.expected_guide', data_get($questionSource, 'expected_guide', ''))
        ) ?? '');

        $guideTail = $expectedGuide !== ''
            ? ' I would keep the answer focused on '.mb_strtolower(rtrim($expectedGuide, ". \t\n\r\0\x0B"), 'UTF-8').'.'
            : '';

        if (preg_match('/\b(?:introduce yourself|tell me about yourself|background)\b/iu', $question) === 1) {
            return review_better_answer_limit_sentences(
                'I am [name], and my background is in [field or experience]. I have worked on [relevant task], where I built strengths in [skill]. I am interested in this role because it connects to [role goal], and I can contribute by [specific contribution].'.$guideTail,
                5
            );
        }

        if (preg_match('/\b(?:irate|angry|upset|customer|client|complaint|concern)\b/iu', $question) === 1) {
            return review_better_answer_limit_sentences(
                'When a customer needed help with [issue], I first listened and confirmed the problem. I explained the next step clearly, followed the correct process, and kept the customer updated. The result was [honest outcome], and I learned to stay calm while solving the real concern.'.$guideTail,
                5
            );
        }

        if (str_contains($questionType, 'behavioral')
            || str_contains($questionType, 'situational')
            || preg_match('/\b(?:tell me about a time|describe a time|give an example|example of|challenge|conflict|handled|helped|solved|worked under pressure|difficult)\b/iu', $question) === 1
        ) {
            return review_better_answer_limit_sentences(
                'In my previous experience, [situation] created a challenge for [team or customer]. My task was to [responsibility]. I [specific action] and communicated the next step clearly. As a result, [honest result or lesson].'.$guideTail,
                5
            );
        }

        if (preg_match('/\b(?:weakness|improving|improve)\b/iu', $lowerQuestion) === 1) {
            return review_better_answer_limit_sentences(
                'One area I am improving is [skill]. I noticed this when [brief example], so I started [specific action]. I now track my progress by [method], and it has helped me [improvement].'.$guideTail,
                5
            );
        }

        if (preg_match('/\b(?:strength|strongest|good at|best skill)\b/iu', $lowerQuestion) === 1) {
            return review_better_answer_limit_sentences(
                'One of my strongest skills is [skill]. For example, I used it when [situation]. I [specific action], which helped [team, customer, or result]. I would bring that same strength to this role.'.$guideTail,
                5
            );
        }

        if (preg_match('/\b(?:why do you want|why are you interested|why this role|why our company|motivation)\b/iu', $lowerQuestion) === 1) {
            return review_better_answer_limit_sentences(
                'I am interested in this role because it connects with my experience in [relevant work] and my goal to grow in [area]. I like that the role requires [responsibility]. I can contribute by using [specific skill or example] to support the team.'.$guideTail,
                5
            );
        }

        if (preg_match('/\b(?:why should we hire|hire you|best candidate)\b/iu', $lowerQuestion) === 1) {
            return review_better_answer_limit_sentences(
                'You should consider hiring me because I bring [skill], [experience], and a steady approach to learning. In [example], I [action and result]. I can use that same approach to support the team and handle the responsibilities of this role.'.$guideTail,
                5
            );
        }

        if (preg_match('/\b(?:how would you|how do you|diagnose|troubleshoot|process|approach|steps?)\b/iu', $lowerQuestion) === 1) {
            return review_better_answer_limit_sentences(
                'I would start by clarifying [goal or issue], then gather the important facts. Next, I would [step one], [step two], and check the result with [verification]. I would communicate updates clearly so the team or customer knows what happens next.'.$guideTail,
                5
            );
        }

        return review_better_answer_limit_sentences(
            'I would answer directly by saying [main point]. Then I would support it with one real example from [experience]. I would explain my action, the result, and why it matters for this role.'.$guideTail,
            5
        );
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
            $draft = $baseAnswer !== ''
                ? $baseAnswer . ' This background gives the interviewer a clear view of my experience. I would connect it to the role using only details I can explain truthfully.'
                : 'A response-based possible answer is unavailable because no usable answer text was saved.';

            return review_better_answer_limit_sentences($draft);
        }

        if (preg_match('/\b(?:irate|angry|upset|customer|client|complaint|concern)\b/iu', $question) === 1) {
            $draft = $baseAnswer !== ''
                ? $baseAnswer . ' I would present this as a calm, organized response. This helps the customer understand the next step without adding details I cannot support.'
                : 'A response-based possible answer is unavailable because no usable answer text was saved.';

            return review_better_answer_limit_sentences($draft);
        }

        if (preg_match('/\b(?:tell me about a time|describe a time|give an example|example of|challenge|conflict|handled|helped|solved|worked under pressure|difficult)\b/iu', $question) === 1) {
            $draft = $baseAnswer !== ''
                ? $baseAnswer . ' I would keep the story in STAR order. I would make the situation, task, action, and honest result clear in one connected answer.'
                : 'A response-based possible answer is unavailable because no usable answer text was saved.';

            return review_better_answer_limit_sentences($draft);
        }

        if (preg_match('/\b(?:weakness|improving|improve)\b/iu', $lowerQuestion) === 1) {
            $draft = $baseAnswer !== ''
                ? $baseAnswer . ' I would explain this honestly. I would connect it to the progress I am actively working on.'
                : 'A response-based possible answer is unavailable because no usable answer text was saved.';

            return review_better_answer_limit_sentences($draft);
        }

        if (preg_match('/\b(?:strength|strongest|good at|best skill)\b/iu', $lowerQuestion) === 1) {
            $draft = $baseAnswer !== ''
                ? $baseAnswer . ' This gives a clear proof point for the strength I named. I would connect that strength to how I can support the team and handle the role responsibilities.'
                : 'A response-based possible answer is unavailable because no usable answer text was saved.';

            return review_better_answer_limit_sentences($draft);
        }

        if (preg_match('/\b(?:why do you want|why are you interested|why this role|why our company|motivation)\b/iu', $lowerQuestion) === 1) {
            $draft = $baseAnswer !== ''
                ? $baseAnswer . ' I would make the reason clear. I would connect it to the experience or skill I already shared.'
                : 'A response-based possible answer is unavailable because no usable answer text was saved.';

            return review_better_answer_limit_sentences($draft);
        }

        if (preg_match('/\b(?:why should we hire|hire you|best candidate)\b/iu', $lowerQuestion) === 1) {
            $draft = $baseAnswer !== ''
                ? $baseAnswer . ' This keeps the answer focused on the experience I already shared. I would close by showing how that experience can help me contribute to the role.'
                : 'A response-based possible answer is unavailable because no usable answer text was saved.';

            return review_better_answer_limit_sentences($draft);
        }

        if (preg_match('/\b(?:how would you|how do you|diagnose|troubleshoot|process|approach|steps?)\b/iu', $lowerQuestion) === 1) {
            $draft = $baseAnswer !== ''
                ? $baseAnswer . ' I would explain why each step matters. I would also explain how I would check that the issue is resolved.'
                : 'A response-based possible answer is unavailable because no usable answer text was saved.';

            return review_better_answer_limit_sentences($draft);
        }

        $draft = $baseAnswer !== ''
            ? $baseAnswer . ' This keeps the main point clear and easy to follow. I would keep the answer focused, direct, and connected to the role.'
            : 'A response-based possible answer is unavailable because no usable answer text was saved.';

        return review_better_answer_limit_sentences($draft);
    }
}

if (! function_exists('review_better_answer_fallback')) {
    function review_better_answer_fallback(mixed $answerSource = null, mixed $questionSource = null): string
    {
        $questionText = review_question_text($questionSource);
        $answerText = review_answer_text($answerSource);
        if (! review_answer_is_usable_for_better_answer($answerText, $questionSource)) {
            if (trim($answerText) === '') {
                return 'A response-based possible answer is unavailable because no usable answer text was saved. Submit an answer to get a draft based on your details.';
            }

            return 'Your saved answer is too short or does not contain enough response detail to build a reliable draft. Add true details, then try again.';
        }

        $answerText = trim(preg_replace('/\s+/u', ' ', $answerText) ?? $answerText);

        $assessment = app(\App\Services\TrustworthyAssessmentService::class);
        $evidence = $assessment->answerEvidence($answerText, null, $questionSource);
        $draft = $assessment->groundedRevisionTemplate($answerText, $evidence);

        if ($draft !== '') {
            return $draft;
        }

        if ($questionText !== '') {
            return review_question_based_better_answer($questionText, $answerText);
        }

        return review_question_based_better_answer('', $answerText);
    }
}

if (! function_exists('review_better_answer_contains_placeholder')) {
    function review_better_answer_contains_placeholder(string $text): bool
    {
        return preg_match('/\[[^\]]+\]/u', $text) === 1;
    }
}

if (! function_exists('review_better_answer_sentence_count')) {
    function review_better_answer_sentence_count(string $text): int
    {
        $text = trim($text);
        if ($text === '') {
            return 0;
        }

        preg_match_all('/[^.!?]+[.!?]+|[^.!?]+$/u', $text, $matches);

        return count(array_filter(array_map(
            fn (string $sentence): string => trim($sentence),
            $matches[0] ?? []
        )));
    }
}

if (! function_exists('review_better_answer_has_required_sentence_count')) {
    function review_better_answer_has_required_sentence_count(string $text): bool
    {
        $count = review_better_answer_sentence_count($text);

        return $count >= 3 && $count <= 6;
    }
}

if (! function_exists('review_better_answer_limit_sentences')) {
    function review_better_answer_limit_sentences(string $text, int $maxSentences = 6): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        preg_match_all('/[^.!?]+[.!?]+|[^.!?]+$/u', $text, $matches);
        $sentences = array_slice(array_values(array_filter(array_map(
            fn (string $sentence): string => trim($sentence),
            $matches[0] ?? []
        ))), 0, max(1, $maxSentences));

        return trim(implode(' ', array_map('review_sentence_text', $sentences)));
    }
}

if (! function_exists('review_feedback_sentence_count')) {
    function review_feedback_sentence_count(string $text): int
    {
        $text = trim($text);
        if ($text === '') {
            return 0;
        }

        preg_match_all('/[^.!?]+[.!?]+|[^.!?]+$/u', $text, $matches);

        return count(array_filter(array_map(
            fn (string $sentence): string => trim($sentence),
            $matches[0] ?? []
        )));
    }
}

if (! function_exists('review_feedback_limit_sentences')) {
    function review_feedback_limit_sentences(string $text, int $maxSentences = 6): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        preg_match_all('/[^.!?]+[.!?]+|[^.!?]+$/u', $text, $matches);
        $sentences = array_slice(array_values(array_filter(array_map(
            fn (string $sentence): string => trim($sentence),
            $matches[0] ?? []
        ))), 0, max(1, $maxSentences));

        return trim(implode(' ', array_map('review_sentence_text', $sentences)));
    }
}

if (! function_exists('review_feedback_with_sentence_range')) {
    function review_feedback_with_sentence_range(string $text, string $kind = 'feedback'): string
    {
        $text = review_feedback_limit_sentences($text, 6);
        if ($text === '') {
            return '';
        }

        $supplements = match ($kind) {
            'worked' => [
                'This point is based on the saved answer for this question.',
                'Keep the same true detail and add only facts you can confirm.',
            ],
            'improve' => [
                'Use only true details from your own experience when you retry.',
                'A stronger answer should add a specific result, example, or role connection only when you can confirm it.',
            ],
            'impact' => [
                'This matters because the interviewer can only judge what the answer clearly states.',
                'Avoid adding numbers, outcomes, or achievements unless they are true.',
            ],
            'success' => [
                'Use this as a quick check before retrying the answer.',
                'The retry should stay grounded in the same real answer details.',
            ],
            'limitation' => [
                'Treat the note as guidance based on the saved response.',
                'Confirm any missing detail before using it in an interview.',
            ],
            default => [
                'This is based on the saved answer for this question.',
                'Add only details that are true and can be confirmed.',
            ],
        };

        foreach ($supplements as $sentence) {
            if (review_feedback_sentence_count($text) >= 3) {
                break;
            }

            if (! str_contains(mb_strtolower($text, 'UTF-8'), mb_strtolower($sentence, 'UTF-8'))) {
                $text .= ' '.review_sentence_text($sentence);
            }
        }

        return review_feedback_limit_sentences($text, 6);
    }
}

if (! function_exists('review_better_answer_uses_star_labels')) {
    function review_better_answer_uses_star_labels(string $text): bool
    {
        return preg_match('/^[ \t]*(?:Situation|Task|Action|Result)[ \t]*:/imu', trim($text)) === 1;
    }
}

if (! function_exists('review_better_answer_has_complete_star_structure')) {
    function review_better_answer_has_complete_star_structure(string $text): bool
    {
        preg_match_all('/^[ \t]*(Situation|Task|Action|Result)[ \t]*:[ \t]*([^\r\n]+)[ \t]*$/imu', trim($text), $matches, PREG_SET_ORDER);
        $sections = [];

        foreach ($matches as $match) {
            if (trim((string) ($match[2] ?? '')) === '') {
                return false;
            }

            $sections[] = ucfirst(strtolower((string) $match[1]));
        }

        return $sections === ['Situation', 'Task', 'Action', 'Result'];
    }
}

if (! function_exists('review_better_answer_star_to_paragraph')) {
    function review_better_answer_star_to_paragraph(string $text): string
    {
        preg_match_all('/^[ \t]*(Situation|Task|Action|Result)[ \t]*:[ \t]*([^\r\n]+)[ \t]*$/imu', trim($text), $matches, PREG_SET_ORDER);
        if (count($matches) !== 4) {
            return trim($text);
        }

        $sentences = [];
        foreach ($matches as $match) {
            $sentence = trim((string) ($match[2] ?? ''));
            if ($sentence === '' || review_better_answer_contains_placeholder($sentence)) {
                return trim($text);
            }

            $sentences[] = review_sentence_text($sentence);
        }

        return trim(implode(' ', $sentences));
    }
}

if (! function_exists('review_better_answer_text')) {
    function review_better_answer_text(?string $text, mixed $answerSource = null, mixed $questionSource = null): string
    {
        $questionSource ??= $answerSource;
        if (! review_answer_is_usable_for_better_answer(review_answer_text($answerSource), $questionSource)) {
            return review_better_answer_fallback($answerSource, $questionSource);
        }

        $lineBreakMarker = "\u{E000}reviewline\u{E001}";
        $clean = review_feedback_without_question_text(
            str_replace(["\r\n", "\r", "\n"], $lineBreakMarker, (string) $text),
            $questionSource
        );
        $clean = str_replace($lineBreakMarker, "\n", $clean);
        $clean = preg_replace('/^\s*(?:(?:suggested|sample|better)\s+)?(?:better\s+)?(?:answer|response|example|draft)\s*[:\-]\s*/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/^\s*(?:I\s+would\s+answer|I\s+would\s+say)\s*[:\-]\s*/iu', '', $clean) ?? $clean;
        $clean = trim($clean);
        $starApplicable = \App\Services\QuestionIntentService::starApplicable($questionSource);
        $containsPlaceholder = review_better_answer_contains_placeholder($clean);
        if (! $containsPlaceholder && $starApplicable && review_better_answer_has_complete_star_structure($clean)) {
            $clean = review_better_answer_star_to_paragraph($clean);
        }
        $looksLikeAdvice = preg_match('/^\s*(?:a\s+stronger\s+answer\s+would|the\s+answer\s+should|you\s+should|try\s+to|make\s+sure|add|include|use|practice)\b/iu', $clean) === 1
            && preg_match('/\b(?:I|we|my|our)\b/iu', $clean) !== 1;
        $usesIncompleteStarLabels = $starApplicable
            && review_better_answer_uses_star_labels($clean)
            && ! review_better_answer_has_complete_star_structure($clean);
        $thinStarParagraph = $starApplicable
            && ! review_better_answer_uses_star_labels($clean)
            && review_text_word_count($clean) < 12;
        $hasRequiredSentenceCount = review_better_answer_has_required_sentence_count($clean);

        if ($clean !== ''
            && review_text_word_count($clean) >= 5
            && ! $containsPlaceholder
            && ! $usesIncompleteStarLabels
            && ! $thinStarParagraph
            && $hasRequiredSentenceCount
            && ! review_text_looks_like_question($clean, $questionSource)
            && ! $looksLikeAdvice
        ) {
            return $clean;
        }

        return review_better_answer_fallback($answerSource, $questionSource);
    }
}
