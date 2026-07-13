<?php

namespace App\Services;

final class CoachLanguageService
{
    public const ENGLISH = 'en';

    public const FILIPINO = 'fil';

    public const CEBUANO = 'ceb';

    public const TAGLISH = 'taglish';

    private const CEBUANO_MARKERS = [
        'unsa' => 4,
        'unsaon' => 4,
        'kinsa' => 4,
        'asa' => 3,
        'kanus-a' => 4,
        'ngano' => 4,
        'giunsa' => 4,
        'dili' => 3,
        'naa' => 3,
        'aduna' => 3,
        'ug' => 3,
        'og' => 3,
        'nga' => 3,
        'nimo' => 3,
        'nako' => 2,
        'imong' => 3,
        'ani' => 3,
        'kini' => 2,
        'pud' => 3,
        'gyud' => 3,
        'jud' => 2,
        'kaayo' => 3,
        'palihog' => 4,
        'tubaga' => 4,
        'tubag' => 2,
        'tubagon' => 3,
        'pangutana' => 3,
        'maayong' => 3,
        'daghang' => 3,
        'tabangi' => 4,
        'mangayo' => 3,
        'mangutana' => 3,
        'mahimo' => 2,
        'mahimong' => 3,
        'maayo' => 3,
        'kinahanglan' => 2,
        'tabangan' => 3,
        'hatagi' => 3,
        'kasinatian' => 3,
        'kahanas' => 3,
        'gikan' => 2,
        'unta' => 1,
    ];

    private const FILIPINO_MARKERS = [
        'ano' => 3,
        'sino' => 4,
        'saan' => 4,
        'kailan' => 4,
        'bakit' => 4,
        'paano' => 4,
        'kamusta' => 3,
        'kumusta' => 3,
        'salamat' => 3,
        'pakisagot' => 4,
        'sagutin' => 3,
        'sasagutin' => 3,
        'sumagot' => 3,
        'ipaliwanag' => 4,
        'tulungan' => 3,
        'maaari' => 3,
        'kailangan' => 3,
        'panayam' => 4,
        'tanong' => 3,
        'sagot' => 3,
        'magandang' => 3,
        'maayos' => 2,
        'tungkol' => 3,
        'kapag' => 3,
        'dahil' => 2,
        'ngunit' => 3,
        'huwag' => 4,
        'hindi' => 3,
        'mayroon' => 3,
        'meron' => 2,
        'puwede' => 2,
        'pwede' => 2,
        'gusto' => 2,
        'handa' => 2,
        'bigyan' => 3,
        'dapat' => 2,
        'gawin' => 2,
        'maging' => 2,
        'kumpanya' => 2,
        'karanasan' => 3,
        'kasanayan' => 3,
        'sistemang' => 3,
        'aplikasyong' => 3,
        'ang' => 1,
        'ng' => 2,
        'mga' => 1,
        'sa' => 1,
        'ay' => 1,
        'ako' => 1,
        'ikaw' => 2,
        'ko' => 1,
        'mo' => 1,
        'natin' => 2,
        'namin' => 2,
        'ito' => 2,
        'iyan' => 2,
        'iyon' => 2,
        'aking' => 2,
        'iyong' => 2,
        'kanilang' => 2,
        'kong' => 2,
        'isang' => 1,
        'na' => 1,
        'at' => 2,
        'para' => 1,
        'mas' => 1,
        'yung' => 2,
        'yong' => 2,
        'po' => 2,
        'opo' => 3,
        'ba' => 1,
        'lang' => 1,
    ];

    private const ENGLISH_MARKERS = [
        'how' => 3,
        'what' => 3,
        'who' => 3,
        'where' => 3,
        'when' => 3,
        'why' => 3,
        'which' => 2,
        'can' => 2,
        'could' => 2,
        'should' => 2,
        'would' => 2,
        'please' => 2,
        'help' => 2,
        'improve' => 2,
        'explain' => 2,
        'prepare' => 2,
        'practice' => 2,
        'give' => 2,
        'tips' => 2,
        'want' => 2,
        'better' => 2,
        'introduce' => 2,
        'ready' => 2,
        'hello' => 3,
        'thanks' => 3,
        'thank' => 2,
        'yes' => 2,
        'answer' => 1,
        'ask' => 2,
        'tell' => 1,
        'yourself' => 2,
        'the' => 1,
        'a' => 1,
        'an' => 1,
        'is' => 1,
        'are' => 1,
        'am' => 1,
        'was' => 1,
        'were' => 1,
        'do' => 1,
        'does' => 1,
        'did' => 1,
        'this' => 1,
        'that' => 1,
        'these' => 1,
        'those' => 1,
        'my' => 1,
        'your' => 1,
        'our' => 1,
        'their' => 1,
        'me' => 1,
        'you' => 1,
        'we' => 1,
        'they' => 1,
        'and' => 1,
        'or' => 1,
        'but' => 1,
        'if' => 1,
        'because' => 1,
        'for' => 1,
        'from' => 1,
        'with' => 1,
        'to' => 1,
        'of' => 1,
        'in' => 1,
        'on' => 1,
        'about' => 1,
        'need' => 2,
        'i' => 1,
    ];

    public function detect(string $message, array $history = [], ?string $preferredLanguage = null): string
    {
        $explicitLanguage = $this->detectExplicitLanguageRequest($message);
        if ($explicitLanguage !== null) {
            return $explicitLanguage;
        }

        $messageLanguage = $this->detectFromText($message);
        if ($messageLanguage !== null) {
            return $messageLanguage;
        }

        foreach (array_reverse($history) as $entry) {
            if (! is_array($entry) || ($entry['role'] ?? null) !== 'user' || ! is_string($entry['content'] ?? null)) {
                continue;
            }

            $historyLanguage = $this->detectExplicitLanguageRequest($entry['content'])
                ?? $this->detectFromText($entry['content']);

            if ($historyLanguage !== null) {
                return $historyLanguage;
            }
        }

        return $this->normalizePreferredLanguage($preferredLanguage);
    }

    public function promptInstruction(string $language): string
    {
        $instruction = match ($language) {
            self::FILIPINO => 'Write the entire response in natural Filipino (Tagalog). Common English technical terms may remain only when that is natural, but keep all explanations in Filipino. Do not drift into English, Taglish, or Cebuano/Binisaya.',
            self::CEBUANO => 'Write the entire response in natural Cebuano (Binisaya). Common English technical terms may remain only when that is natural, but keep all explanations in Cebuano. Do not drift into Filipino/Tagalog, Taglish, or English.',
            self::TAGLISH => 'Write the response in natural Philippine Taglish, using a clear Filipino-English mix that mirrors the approximate balance and tone of the user\'s latest message. Do not make the response purely English, purely Filipino, or Cebuano/Binisaya.',
            default => 'Write the entire response in natural English. Do not switch to Filipino/Tagalog, Taglish, or Cebuano/Binisaya.',
        };

        return 'Strict response-language requirement for this turn: '.$instruction
            .' The latest user message takes priority over older conversation messages and profile settings. Keep proper names, official role titles, company names, acronyms, and code unchanged.';
    }

    private function detectExplicitLanguageRequest(string $message): ?string
    {
        $text = $this->normalize($message);

        if (preg_match('/\btaglish\b/u', $text) === 1
            || preg_match('/\b(?:tagalog|filipino)\s*(?:and|with|\/|\+)\s*english\b/u', $text) === 1
            || preg_match('/\benglish\s*(?:and|with|\/|\+)\s*(?:tagalog|filipino)\b/u', $text) === 1) {
            return self::TAGLISH;
        }

        if (preg_match('/\bbislish\b/u', $text) === 1) {
            return self::CEBUANO;
        }

        $targets = [
            self::CEBUANO => '(?:cebuano|bisaya|binisaya)',
            self::FILIPINO => '(?:filipino|tagalog|pilipino)',
            self::ENGLISH => '(?:english|ingles)',
        ];

        foreach ($targets as $language => $target) {
            if (preg_match('/\b(?:to|into)\s+(?:the\s+)?'.$target.'\b/u', $text) === 1) {
                return $language;
            }
        }

        foreach ($targets as $language => $target) {
            if (preg_match('/\b(?:in|using|use|sa)\s+(?:the\s+)?'.$target.'\b/u', $text) === 1
                || preg_match('/\b'.$target.'\b.{0,18}\b(?:please|lang|lamang|only|reply|response|answer|tubag)\b/u', $text) === 1
                || preg_match('/\b(?:reply|respond|answer|write|speak|say|explain|translate|sumagot|sagutin|ipaliwanag|tubag|tubaga|isulti|ipasabot)\b.{0,35}\b'.$target.'\b/u', $text) === 1) {
                return $language;
            }
        }

        return null;
    }

    private function detectFromText(string $message): ?string
    {
        $text = $this->normalize($message);
        $tokens = preg_split('/[^\p{L}\p{N}\'-]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return null;
        }

        $cebuanoScore = $this->scoreTokens($tokens, self::CEBUANO_MARKERS);
        $filipinoScore = $this->scoreTokens($tokens, self::FILIPINO_MARKERS);
        $englishScore = $this->scoreTokens($tokens, self::ENGLISH_MARKERS);

        if (preg_match('/\b(?:maayong\s+(?:adlaw|buntag|hapon|gabii)|unsaon\s+nako)\b/u', $text) === 1) {
            $cebuanoScore += 4;
        }

        if (preg_match('/\b(?:akong|imong|among|inyong)\s+(?:interview|resume|trabaho|tubag|tubagon|pangutana|kasinatian|kahanas|aplikasyon)\b/u', $text) === 1) {
            $cebuanoScore += 4;
        }

        if (preg_match('/\b(?:magandang\s+(?:araw|umaga|hapon|gabi)|paano\s+ko|sistemang\s+ito|aplikasyong\s+ito)\b/u', $text) === 1) {
            $filipinoScore += 3;
        }

        if (preg_match('/\b(?:help|prepare|practice)\b.{0,15}\bsa\s+(?:aking|interview|resume|trabaho)\b/u', $text) === 1) {
            $filipinoScore += 2;
        }

        if (preg_match('/\b(?:i|ma|mag|nag|ipa)-(?:answer|explain|improve|practice|prepare)\b/u', $text) === 1) {
            $englishScore += 3;
        }

        // Distinctive Cebuano grammar wins even when common English interview terms are mixed in.
        if ($cebuanoScore >= 3) {
            return self::CEBUANO;
        }

        if (($englishScore >= 3 && $filipinoScore >= 2)
            || ($englishScore >= 2 && $filipinoScore >= 3)) {
            return self::TAGLISH;
        }

        if ($filipinoScore >= 3) {
            return self::FILIPINO;
        }

        if ($englishScore >= 2) {
            return self::ENGLISH;
        }

        return null;
    }

    private function scoreTokens(array $tokens, array $markers): int
    {
        $score = 0;

        foreach ($tokens as $token) {
            $score += $markers[$token] ?? 0;
        }

        return $score;
    }

    private function normalizePreferredLanguage(?string $language): string
    {
        return match ($language) {
            'fil', 'tl' => self::FILIPINO,
            'ceb' => self::CEBUANO,
            default => self::ENGLISH,
        };
    }

    private function normalize(string $text): string
    {
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
