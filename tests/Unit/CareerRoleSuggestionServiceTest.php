<?php

namespace Tests\Unit;

use App\Services\CareerRoleSuggestionService;
use Tests\TestCase;

class CareerRoleSuggestionServiceTest extends TestCase
{
    public function test_catalog_places_seafaring_and_florist_positions_in_relevant_categories(): void
    {
        $groups = config('speakready_scope.job_positions');

        $this->assertContains('Seaman', $groups['Maritime and Seafaring']);
        $this->assertContains('Able Seaman', $groups['Maritime and Seafaring']);
        $this->assertContains('Deck Cadet', $groups['Maritime and Seafaring']);
        $this->assertContains('Florist', $groups['Retail and Personal Services']);

        $positions = collect($groups)->flatten()->map(fn (string $title): string => mb_strtolower($title));
        $this->assertSame($positions->count(), $positions->unique()->count());
    }

    public function test_suggestions_match_maritime_and_floristry_backgrounds(): void
    {
        $suggestions = app(CareerRoleSuggestionService::class);

        $maritime = $suggestions->suggest([
            'skills' => ['seaman', 'deck watchkeeping'],
            'interests' => ['shipboard work'],
            'qualifications' => '',
            'experience_level' => 'entry',
        ]);
        $floristry = $suggestions->suggest([
            'skills' => ['floral arrangements', 'flower care'],
            'interests' => ['flowers'],
            'qualifications' => '',
            'experience_level' => 'entry',
        ]);

        $this->assertContains('Maritime and Seafaring', array_column($maritime['roles'], 'category'));
        $this->assertContains('Retail and Personal Services', array_column($floristry['roles'], 'category'));
    }
}
