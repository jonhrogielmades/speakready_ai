<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        $existingColumns = array_flip(Schema::getColumnListing($tableName));

        Schema::table($tableName, function (Blueprint $table) use ($existingColumns) {
            if (! isset($existingColumns['skill_focus'])) {
                $table->string('skill_focus')->nullable();
            }

            if (! isset($existingColumns['learning_objective'])) {
                $table->text('learning_objective')->nullable();
            }

            if (! isset($existingColumns['success_criteria'])) {
                $table->text('success_criteria')->nullable();
            }

            if (! isset($existingColumns['retry_hint'])) {
                $table->text('retry_hint')->nullable();
            }

            if (! isset($existingColumns['ai_persona'])) {
                $table->string('ai_persona')->nullable();
            }

            if (! isset($existingColumns['ai_custom_prompt'])) {
                $table->text('ai_custom_prompt')->nullable();
            }

            if (! isset($existingColumns['time_limit_seconds'])) {
                $table->integer('time_limit_seconds')->nullable();
            }

            if (! isset($existingColumns['banned_words'])) {
                $table->string('banned_words')->nullable();
            }

            if (! isset($existingColumns['target_tone'])) {
                $table->string('target_tone')->nullable();
            }

            if (! isset($existingColumns['custom_badge_name'])) {
                $table->string('custom_badge_name')->nullable();
            }

            if (! isset($existingColumns['skill_xp_type'])) {
                $table->string('skill_xp_type')->nullable();
            }

            if (! isset($existingColumns['skill_xp_amount'])) {
                $table->integer('skill_xp_amount')->default(0);
            }

            if (! isset($existingColumns['prerequisite_level_id'])) {
                $table->unsignedBigInteger('prerequisite_level_id')->nullable();
            }

            if (! isset($existingColumns['is_hidden'])) {
                $table->boolean('is_hidden')->default(false);
            }
        });

        $this->backfillDefaultGuidance($tableName);

        foreach (['skill_xp_amount' => 0, 'is_hidden' => false] as $column => $value) {
            if (Schema::hasColumn($tableName, $column)) {
                DB::table($tableName)->whereNull($column)->update([$column => $value]);
            }
        }
    }

    public function down(): void
    {
        // Repair migration: keep production game data and schema in place.
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

    private function backfillDefaultGuidance(string $tableName): void
    {
        if (! Schema::hasColumn($tableName, 'skill_focus')) {
            return;
        }

        $defaults = [
            'The Basics: "Tell Me About Yourself"' => [
                'skill_focus' => 'Clarity',
                'learning_objective' => 'Build a concise interview introduction that connects your background, strengths, and target role.',
                'success_criteria' => "1. Open with your current role or background.\n2. Mention one or two relevant strengths.\n3. Connect your experience to the opportunity.\n4. Keep the answer focused and professional.",
                'retry_hint' => 'Trim unrelated details and use a simple present-past-future structure.',
            ],
            'Behavioral Mastery: STAR Method' => [
                'skill_focus' => 'STAR Method',
                'learning_objective' => 'Practice a behavioral answer with clear Situation, Task, Action, and Result evidence.',
                'success_criteria' => "1. Set the situation briefly.\n2. Explain your responsibility.\n3. Describe specific actions you took.\n4. End with a result, impact, or lesson.",
                'retry_hint' => 'Make the Action and Result parts more specific before retrying.',
            ],
            'The Curveballs' => [
                'skill_focus' => 'Professionalism',
                'learning_objective' => 'Answer a difficult personal question honestly while protecting your credibility.',
                'success_criteria' => "1. Name a real but manageable weakness.\n2. Avoid blaming others.\n3. Explain what you are doing to improve.\n4. Keep the tone confident and accountable.",
                'retry_hint' => 'Choose a weakness that is not central to the role, then show your improvement plan.',
            ],
            'Final Boss: 15-Min Mock Interview' => [
                'skill_focus' => 'Interview Readiness',
                'learning_objective' => 'Combine clarity, relevance, STAR structure, and professional delivery across a longer interview flow.',
                'success_criteria' => "1. Answer each question directly.\n2. Use evidence from real experience.\n3. Include results for behavioral answers.\n4. Keep pacing steady under time pressure.\n5. Stay professional from start to finish.",
                'retry_hint' => 'Review your lowest scoring answer first, then repeat the full round with shorter, more specific responses.',
            ],
        ];

        foreach ($defaults as $title => $guidance) {
            DB::table($tableName)
                ->where('title', $title)
                ->whereNull('skill_focus')
                ->update($guidance);
        }
    }
};
