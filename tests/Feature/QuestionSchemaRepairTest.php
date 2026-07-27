<?php

namespace Tests\Feature;

use App\Support\QuestionSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuestionSchemaRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_question_schema_repair_adds_missing_metadata_columns(): void
    {
        Schema::dropIfExists('questions');

        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->text('question_text');
            $table->string('difficulty')->default('medium');
            $table->timestamps();
        });

        QuestionSchema::ensure(force: true, createIfMissing: true);

        foreach ([
            'interview_session_id',
            'type',
            'status',
            'expected_guide',
            'mapped_skills',
            'source_name',
            'source_url',
            'source_type',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('questions', $column),
                "Expected questions.{$column} to exist after schema repair."
            );
        }
    }
}
