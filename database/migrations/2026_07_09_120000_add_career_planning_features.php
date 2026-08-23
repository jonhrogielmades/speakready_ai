<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_applications')) {
            Schema::create('job_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('company_name');
                $table->string('job_title');
                $table->string('status')->default('tracking');
                $table->string('interview_stage')->nullable();
                $table->date('interview_date')->nullable();
                $table->string('source_url')->nullable();
                $table->longText('resume_text')->nullable();
                $table->longText('job_description')->nullable();
                $table->integer('match_score')->default(0);
                $table->json('matched_keywords')->nullable();
                $table->json('missing_keywords')->nullable();
                $table->json('smart_plan')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('practice_plan_items')) {
            Schema::create('practice_plan_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('job_application_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('interview_session_id')->nullable()->constrained('interview_sessions')->nullOnDelete();
                $table->integer('day_number')->default(1);
                $table->date('due_date')->nullable();
                $table->string('type')->default('practice');
                $table->string('title');
                $table->text('task');
                $table->json('metadata')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('interview_packs')) {
            Schema::create('interview_packs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('company')->nullable();
                $table->string('role_family')->nullable();
                $table->string('difficulty')->default('medium');
                $table->string('interview_focus')->default('General Practice');
                $table->string('company_persona')->nullable();
                $table->json('question_types')->nullable();
                $table->json('sample_questions')->nullable();
                $table->text('description')->nullable();
                $table->boolean('pressure_mode')->default(false);
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mentor_review_comments')) {
            Schema::create('mentor_review_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('interview_session_id')->constrained('interview_sessions')->cascadeOnDelete();
                $table->string('reviewer_name');
                $table->string('reviewer_email')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->text('comment');
                $table->string('visibility')->default('owner');
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        Schema::table('interview_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('interview_sessions', 'job_application_id')) {
                $table->foreignId('job_application_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('job_applications')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('interview_sessions', 'interview_pack_id')) {
                $table->foreignId('interview_pack_id')
                    ->nullable()
                    ->after('job_application_id')
                    ->constrained('interview_packs')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('interview_sessions', 'pressure_mode')) {
                $table->boolean('pressure_mode')->default(false)->after('live_feedback_mode');
            }
        });

        DB::table('interview_packs')->upsert([
            [
                'name' => 'Amazon Leadership Principles',
                'slug' => 'amazon-leadership-principles',
                'company' => 'Amazon',
                'role_family' => 'Behavioral',
                'difficulty' => 'hard',
                'interview_focus' => 'Leadership',
                'company_persona' => 'Amazon',
                'question_types' => json_encode(['Behavioral', 'Situational']),
                'sample_questions' => json_encode([
                    'Tell me about a time you showed ownership under pressure.',
                    'Describe a decision where you had incomplete data.',
                    'Give an example of when you disagreed and committed.',
                ]),
                'description' => 'Behavioral practice focused on ownership, customer obsession, and measurable impact.',
                'pressure_mode' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Google Product/Technical Screen',
                'slug' => 'google-product-technical-screen',
                'company' => 'Google',
                'role_family' => 'Technical',
                'difficulty' => 'hard',
                'interview_focus' => 'Problem Solving',
                'company_persona' => 'Google',
                'question_types' => json_encode(['Technical', 'Situational']),
                'sample_questions' => json_encode([
                    'Walk me through how you would debug a scaling issue.',
                    'Tell me about a technical tradeoff you made.',
                    'How would you explain a complex system to a non-technical stakeholder?',
                ]),
                'description' => 'Technical and product-thinking practice with follow-ups on tradeoffs and clarity.',
                'pressure_mode' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'BPO Customer Service Interview',
                'slug' => 'bpo-customer-service-interview',
                'company' => null,
                'role_family' => 'Customer Service',
                'difficulty' => 'medium',
                'interview_focus' => 'Communication Skills',
                'company_persona' => 'Customer Service Panel',
                'question_types' => json_encode(['Behavioral', 'Situational', 'Personal']),
                'sample_questions' => json_encode([
                    'How would you handle an angry customer?',
                    'Tell me about a time you stayed calm under pressure.',
                    'How do you manage repetitive work while keeping quality high?',
                ]),
                'description' => 'Communication, empathy, and pressure-handling practice for customer-facing roles.',
                'pressure_mode' => false,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Scholarship Interview',
                'slug' => 'scholarship-interview',
                'company' => null,
                'role_family' => 'Scholarship',
                'difficulty' => 'medium',
                'interview_focus' => 'Personal',
                'company_persona' => 'Scholarship Committee',
                'question_types' => json_encode(['Personal', 'Behavioral']),
                'sample_questions' => json_encode([
                    'Why do you deserve this scholarship?',
                    'Describe your leadership contribution to your community.',
                    'How will this opportunity support your long-term goals?',
                ]),
                'description' => 'Motivation, goals, leadership, and values-based interview practice.',
                'pressure_mode' => false,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['slug'], [
            'name',
            'company',
            'role_family',
            'difficulty',
            'interview_focus',
            'company_persona',
            'question_types',
            'sample_questions',
            'description',
            'pressure_mode',
            'status',
            'updated_at',
        ]);
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('interview_sessions', 'job_application_id')) {
                $table->dropConstrainedForeignId('job_application_id');
            }

            if (Schema::hasColumn('interview_sessions', 'interview_pack_id')) {
                $table->dropConstrainedForeignId('interview_pack_id');
            }

            if (Schema::hasColumn('interview_sessions', 'pressure_mode')) {
                $table->dropColumn('pressure_mode');
            }
        });

        Schema::dropIfExists('mentor_review_comments');
        Schema::dropIfExists('practice_plan_items');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('interview_packs');
    }
};
