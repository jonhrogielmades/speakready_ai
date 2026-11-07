<?php

namespace Tests\Unit;

use App\Services\JobPositionCategoryService;
use App\Services\QuestionDatasetProvider;
use Tests\TestCase;

class JobPositionCategoryServiceTest extends TestCase
{
    public function test_catalog_and_recognizable_custom_titles_map_to_their_occupational_categories(): void
    {
        $categories = app(JobPositionCategoryService::class);

        $this->assertSame('Maritime and Seafaring', $categories->categoryFor('Seaman'));
        $this->assertSame('Maritime and Seafaring', $categories->categoryFor('Deck Cadet'));
        $this->assertSame('Maritime and Seafaring', $categories->categoryFor('Maritime Safety Officer'));
        $this->assertSame('Maritime and Seafaring', $categories->categoryFor('Marine Safety Officer'));
        $this->assertSame('Retail and Personal Services', $categories->categoryFor('Florist'));
        $this->assertSame('Retail and Personal Services', $categories->categoryFor('Retail Clerk'));
        $this->assertSame('IT and Digital', $categories->categoryFor('AI Engineer'));
        $this->assertSame('IT and Digital', $categories->categoryFor('IT Specialist'));
        $this->assertSame('Healthcare', $categories->categoryFor('Registered Nurse'));
        $this->assertNull($categories->categoryFor('Program Manager'));
    }

    public function test_dataset_roles_keep_a_matching_category_instead_of_being_put_in_other(): void
    {
        config()->set('speakready_scope.job_positions', [
            'Healthcare' => ['Medical Assistant'],
        ]);

        $groups = QuestionDatasetProvider::targetPositionOptionGroups([
            'job' => [
                'questions' => [[
                    'question_text' => 'How do you care for a patient?',
                    'archive_roles' => ['Nurse'],
                ]],
            ],
        ]);

        $this->assertContains('Nurse', $groups['Healthcare']);
        $this->assertNotContains('Nurse', $groups[JobPositionCategoryService::OTHER_CATEGORY] ?? []);
    }
}
