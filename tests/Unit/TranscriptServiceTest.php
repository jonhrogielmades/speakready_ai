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

    public function test_it_counts_common_fillers_as_phrases(): void
    {
        $this->assertSame(
            5,
            TranscriptService::countFillerWords('Um, I mean, you know, I basically used, uh, monitoring.')
        );
    }
}
