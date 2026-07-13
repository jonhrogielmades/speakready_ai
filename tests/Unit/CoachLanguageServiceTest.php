<?php

namespace Tests\Unit;

use App\Services\CoachLanguageService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CoachLanguageServiceTest extends TestCase
{
    private CoachLanguageService $languages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languages = new CoachLanguageService;
    }

    #[DataProvider('messageLanguageProvider')]
    public function test_it_detects_the_language_used_by_the_latest_message(string $message, string $expected): void
    {
        $this->assertSame($expected, $this->languages->detect($message));
    }

    public static function messageLanguageProvider(): array
    {
        return [
            'English interview question' => [
                'How can I improve my answer to this interview question?',
                CoachLanguageService::ENGLISH,
            ],
            'English developer question' => [
                'Who are the developers of this system?',
                CoachLanguageService::ENGLISH,
            ],
            'Filipino interview question' => [
                'Paano ko mapapaganda ang sagot ko sa interview?',
                CoachLanguageService::FILIPINO,
            ],
            'Filipino developer question' => [
                'Sino ang mga developer ng sistemang ito?',
                CoachLanguageService::FILIPINO,
            ],
            'Cebuano interview question' => [
                'Unsaon nako pagpaayo sa akong tubag sa interview?',
                CoachLanguageService::CEBUANO,
            ],
            'Cebuano developer question' => [
                'Kinsa ang mga developer ani nga sistema?',
                CoachLanguageService::CEBUANO,
            ],
            'Taglish interview question' => [
                'How ko mapapaganda ang answer ko sa interview?',
                CoachLanguageService::TAGLISH,
            ],
            'Taglish developer question' => [
                'Who ang developers ng system na ito?',
                CoachLanguageService::TAGLISH,
            ],
            'Polite Taglish question' => [
                'Can you help me po with my interview answer?',
                CoachLanguageService::TAGLISH,
            ],
            'Taglish with colloquial marker' => [
                'I need to improve yung answer ko.',
                CoachLanguageService::TAGLISH,
            ],
            'Taglish with hyphenated English verb' => [
                'Paano ko i-improve ang answer ko?',
                CoachLanguageService::TAGLISH,
            ],
            'Cebuano with common conversational words' => [
                'Pwede ba ko mangayo og tabang para mahimong andam?',
                CoachLanguageService::CEBUANO,
            ],
            'Short Filipino message' => [
                'Salamat po.',
                CoachLanguageService::FILIPINO,
            ],
            'Short Cebuano message' => [
                'Daghang salamat.',
                CoachLanguageService::CEBUANO,
            ],
        ];
    }

    #[DataProvider('explicitLanguageProvider')]
    public function test_an_explicit_language_request_takes_priority(string $message, string $expected): void
    {
        $this->assertSame($expected, $this->languages->detect($message, [], 'en'));
    }

    public static function explicitLanguageProvider(): array
    {
        return [
            ['Please answer in Bisaya: how should I introduce myself?', CoachLanguageService::CEBUANO],
            ['Pakisagot sa Filipino: how should I introduce myself?', CoachLanguageService::FILIPINO],
            ['Reply in Taglish: how should I introduce myself?', CoachLanguageService::TAGLISH],
            ['Please use Bislish for the reply.', CoachLanguageService::CEBUANO],
            ['Please reply in English: Unsaon nako pagpaila sa akong kaugalingon?', CoachLanguageService::ENGLISH],
            ['Translate this from Bisaya to English.', CoachLanguageService::ENGLISH],
        ];
    }

    public function test_it_uses_recent_user_language_then_profile_for_ambiguous_messages(): void
    {
        $history = [
            ['role' => 'user', 'content' => 'Paano ako maghahanda sa interview?'],
            ['role' => 'ai', 'content' => 'Narito ang ilang paraan.'],
        ];

        $this->assertSame(
            CoachLanguageService::FILIPINO,
            $this->languages->detect('Okay.', $history, 'ceb')
        );
        $this->assertSame(
            CoachLanguageService::CEBUANO,
            $this->languages->detect('Okay.', [], 'ceb')
        );
        $this->assertSame(
            CoachLanguageService::FILIPINO,
            $this->languages->detect('Okay.', [], 'tl')
        );
    }

    #[DataProvider('promptLanguageProvider')]
    public function test_each_language_has_a_strict_provider_instruction(string $language, string $expectedText): void
    {
        $instruction = $this->languages->promptInstruction($language);

        $this->assertStringContainsString('Strict response-language requirement', $instruction);
        $this->assertStringContainsString($expectedText, $instruction);
        $this->assertStringContainsString('latest user message takes priority', $instruction);
    }

    public static function promptLanguageProvider(): array
    {
        return [
            [CoachLanguageService::ENGLISH, 'natural English'],
            [CoachLanguageService::FILIPINO, 'natural Filipino (Tagalog)'],
            [CoachLanguageService::CEBUANO, 'natural Cebuano (Binisaya)'],
            [CoachLanguageService::TAGLISH, 'natural Philippine Taglish'],
        ];
    }
}
