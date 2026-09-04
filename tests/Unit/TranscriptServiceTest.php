<?php

namespace Tests\Unit;

use App\Services\TranscriptService;
use PHPUnit\Framework\TestCase;

class TranscriptServiceTest extends TestCase
{
    public function test_it_cleans_restart_duplicates_and_spacing(): void
    {
        $transcript = '  I led a migration   I led a migration and reduced downtime downtime.  ';

        $this->assertSame(
            'I led a migration and reduced downtime',
            TranscriptService::clean($transcript)
        );
    }

    public function test_it_normalizes_unicode_transcript_duplicates(): void
    {
        $transcript = 'Pinangunahan ko ang proyekto Pinangunahan ko ang proyekto at nakamit ang layunin.';

        $this->assertSame(
            'Pinangunahan ko ang proyekto at nakamit ang layunin.',
            TranscriptService::clean($transcript)
        );
        $this->assertSame(8, TranscriptService::wordCount(TranscriptService::clean($transcript)));
    }

    public function test_it_collapses_browser_restart_phrases_and_word_doubles(): void
    {
        $transcript = 'I can handle pressure because I can handle pressure because I stayed calm calm during calls calls';

        $this->assertSame(
            'I can handle pressure because I stayed calm during calls',
            TranscriptService::clean($transcript)
        );
    }

    public function test_it_collapses_long_repeated_transcription_chunks(): void
    {
        $phrase = 'I reviewed the customer issue with the team and documented the root cause before deployment';
        $transcript = "{$phrase} {$phrase} so the release was safer.";

        $this->assertSame(
            "{$phrase} so the release was safer.",
            TranscriptService::clean($transcript)
        );
    }

    public function test_it_auto_corrects_common_transcription_word_errors(): void
    {
        $transcript = 'teh api improovement helped alot because im responsable for qa and didnt miss teh sla.';

        $this->assertSame(
            "the API improvement helped a lot because I'm responsible for QA and didn't miss the SLA.",
            TranscriptService::clean($transcript)
        );
    }

    public function test_it_preserves_fillers_and_local_words_during_auto_correction(): void
    {
        $transcript = 'Um um opo po trabaho tabang maayo.';

        $this->assertSame(
            'Um um opo po trabaho tabang maayo.',
            TranscriptService::clean($transcript)
        );
    }

    public function test_it_counts_common_fillers_as_phrases(): void
    {
        $this->assertSame(
            5,
            TranscriptService::countFillerWords('Um, I mean, you know, I basically used, uh, monitoring.')
        );
    }
}
