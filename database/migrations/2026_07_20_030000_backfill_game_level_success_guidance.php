<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = $this->levelsTable();

        if (! $tableName) {
            return;
        }

        $columns = array_flip(Schema::getColumnListing($tableName));
        $selectColumns = array_values(array_intersect([
            'id',
            'title',
            'difficulty',
            'target_position',
            'skill_focus',
            'learning_objective',
            'success_criteria',
            'retry_hint',
        ], array_keys($columns)));

        DB::table($tableName)
            ->orderBy('id')
            ->get($selectColumns)
            ->each(function ($level) use ($tableName, $columns): void {
                $guidance = $this->guidanceFor(
                    (string) ($level->title ?? ''),
                    (string) ($level->skill_focus ?? ''),
                    (string) ($level->difficulty ?? '')
                );

                $updates = [];
                foreach ($guidance as $column => $value) {
                    if (isset($columns[$column]) && $this->isBlank($level->{$column} ?? null)) {
                        $updates[$column] = $value;
                    }
                }

                if ($updates !== []) {
                    DB::table($tableName)->where('id', $level->id)->update($updates);
                }
            });
    }

    public function down(): void
    {
        // Keep repaired production guidance in place on rollback.
    }

    private function levelsTable(): ?string
    {
        if (Schema::hasTable('game_levels')) {
            return 'game_levels';
        }

        if (Schema::hasTable('arena_levels')) {
            return 'arena_levels';
        }

        return null;
    }

    private function guidanceFor(string $title, string $skillFocus, string $difficulty): array
    {
        $context = strtolower($title.' '.$skillFocus.' '.$difficulty);
        $skillFocus = $skillFocus ?: $this->skillFocusFor($context);

        return [
            'skill_focus' => $skillFocus,
            'learning_objective' => $this->learningObjectiveFor($context, $skillFocus),
            'success_criteria' => $this->successCriteriaFor($context),
            'retry_hint' => $this->retryHintFor($context),
        ];
    }

    private function skillFocusFor(string $context): string
    {
        if (str_contains($context, 'star') || str_contains($context, 'behavior') || str_contains($context, 'conflict')) {
            return 'STAR Method';
        }

        if (str_contains($context, 'weakness') || str_contains($context, 'curveball') || str_contains($context, 'hr')) {
            return 'Professionalism';
        }

        if (str_contains($context, 'final') || str_contains($context, 'mock') || str_contains($context, 'readiness')) {
            return 'Philippine Interview Readiness';
        }

        if (str_contains($context, 'about yourself') || str_contains($context, 'introduction')) {
            return 'Clarity';
        }

        return 'Interview Communication';
    }

    private function learningObjectiveFor(string $context, string $skillFocus): string
    {
        if ($skillFocus === 'STAR Method') {
            return 'Practice a behavioral answer with clear Situation, Task, Action, and Result evidence.';
        }

        if ($skillFocus === 'Professionalism') {
            return 'Answer difficult interview questions honestly while protecting credibility and role readiness.';
        }

        if ($skillFocus === 'Philippine Interview Readiness') {
            return 'Combine clarity, relevance, structured evidence, and professional delivery across the challenge path.';
        }

        if ($skillFocus === 'Clarity') {
            return 'Build a concise interview answer that connects background, strengths, and the target opportunity.';
        }

        return 'Practice a realistic Philippine interview answer with clear structure, specific evidence, and professional tone.';
    }

    private function successCriteriaFor(string $context): string
    {
        if (str_contains($context, 'star') || str_contains($context, 'behavior') || str_contains($context, 'conflict')) {
            return "1. Set the situation briefly.\n2. Explain your responsibility or goal.\n3. Describe the specific action you took.\n4. End with a result, impact, or lesson.";
        }

        if (str_contains($context, 'weakness') || str_contains($context, 'curveball') || str_contains($context, 'hr')) {
            return "1. Answer the question directly and honestly.\n2. Keep the tone respectful and accountable.\n3. Explain what you learned or changed.\n4. Connect the answer back to readiness for the role.";
        }

        if (str_contains($context, 'about yourself') || str_contains($context, 'introduction')) {
            return "1. Open with your current role, course, training, or background.\n2. Mention one or two strengths relevant to the opportunity.\n3. Connect your experience to the role or panel question.\n4. Keep the answer focused, respectful, and professional.";
        }

        if (str_contains($context, 'readiness') || str_contains($context, 'final') || str_contains($context, 'mock')) {
            return "1. Answer each question directly.\n2. Use specific evidence from school, work, internship, freelance, or project experience.\n3. Include a result, lesson, or next step when relevant.\n4. Keep pacing steady and stay professional from start to finish.";
        }

        return "1. Answer the interview question directly.\n2. Use one concrete example or proof point.\n3. Explain your action or decision clearly.\n4. Include a result, lesson, or next step.\n5. Keep the tone professional and appropriate for Philippine interviews.";
    }

    private function retryHintFor(string $context): string
    {
        if (str_contains($context, 'star') || str_contains($context, 'behavior') || str_contains($context, 'conflict')) {
            return 'On the next attempt, make the Action and Result parts more specific.';
        }

        if (str_contains($context, 'weakness') || str_contains($context, 'curveball') || str_contains($context, 'hr')) {
            return 'On the next attempt, keep the problem brief and spend more time on what changed.';
        }

        return 'On the next attempt, choose one real example first, then answer with context, action, and result.';
    }

    private function isBlank(mixed $value): bool
    {
        return trim((string) $value) === '';
    }
};
