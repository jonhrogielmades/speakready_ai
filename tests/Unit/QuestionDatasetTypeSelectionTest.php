<?php

namespace Tests\Unit;

use App\Services\QuestionDatasetProvider;
use Tests\TestCase;

class QuestionDatasetTypeSelectionTest extends TestCase
{
    public function test_each_selected_question_type_returns_only_that_type(): void
    {
        $dataset = $this->fourTypeDataset();

        foreach (['Behavioral', 'Situational', 'Technical', 'Personal'] as $type) {
            $matches = QuestionDatasetProvider::rankedQuestions($dataset, 'QA Engineer', 'Medium', [$type], 10);

            $this->assertCount(1, $matches, $type.' should have one matching question.');
            $this->assertSame($type, $matches[0]['type']);
        }
    }

    public function test_missing_selected_type_never_falls_back_to_another_type(): void
    {
        $dataset = $this->fourTypeDataset();
        $dataset['questions'] = array_values(array_filter(
            $dataset['questions'],
            fn (array $question): bool => $question['type'] === 'Behavioral'
        ));

        $this->assertSame([], QuestionDatasetProvider::rankedQuestions(
            $dataset,
            'QA Engineer',
            'Medium',
            ['Personal'],
            5
        ));
    }

    public function test_mixed_selection_keeps_only_requested_types_and_normalizes_case(): void
    {
        $dataset = $this->fourTypeDataset();
        $dataset['questions'][2]['type'] = 'technical';

        $matches = QuestionDatasetProvider::rankedQuestions(
            $dataset,
            'QA Engineer',
            'Medium',
            ['BEHAVIORAL', 'Technical'],
            10
        );

        $this->assertCount(2, $matches);
        $this->assertEqualsCanonicalizing(['Behavioral', 'Technical'], array_column($matches, 'type'));
    }

    private function fourTypeDataset(): array
    {
        return [
            'name' => 'Interview type test bank',
            'source_type' => 'test_bank',
            'questions' => [
                ['question_text' => 'Tell me about a time you found a software defect.', 'type' => 'Behavioral', 'difficulty' => 'Medium'],
                ['question_text' => 'What would you do if a release failed its final check?', 'type' => 'Situational', 'difficulty' => 'Medium'],
                ['question_text' => 'How would you isolate a failing API request?', 'type' => 'Technical', 'difficulty' => 'Medium'],
                ['question_text' => 'Why are you interested in this QA role?', 'type' => 'Personal', 'difficulty' => 'Medium'],
            ],
        ];
    }
}
