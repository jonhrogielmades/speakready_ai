<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readiness_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_application_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('target_role');
            $table->json('competency_map')->nullable();
            $table->json('mastery_snapshot')->nullable();
            $table->json('future_skills')->nullable();
            $table->json('next_actions')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('calibrated_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'job_application_id']);
        });

        Schema::create('experience_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('context_type')->default('work');
            $table->text('situation')->nullable();
            $table->text('task')->nullable();
            $table->text('action')->nullable();
            $table->text('result')->nullable();
            $table->json('verified_facts')->nullable();
            $table->json('metrics')->nullable();
            $table->json('competency_tags')->nullable();
            $table->boolean('facts_confirmed')->default(false);
            $table->string('visibility')->default('private');
            $table->timestamps();
            $table->index(['user_id', 'facts_confirmed']);
        });

        Schema::create('interview_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('interview_session_id')->nullable()->constrained('interview_sessions')->nullOnDelete();
            $table->date('interview_date')->nullable();
            $table->string('interview_format')->default('live');
            $table->string('stage')->nullable();
            $table->string('result')->default('pending');
            $table->json('questions_asked')->nullable();
            $table->json('surprise_topics')->nullable();
            $table->json('useful_story_ids')->nullable();
            $table->text('recruiter_feedback')->nullable();
            $table->text('reflection')->nullable();
            $table->unsignedTinyInteger('confidence_before')->nullable();
            $table->unsignedTinyInteger('confidence_after')->nullable();
            $table->boolean('allow_anonymous_learning')->default(false);
            $table->timestamps();
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->json('inclusive_preferences')->nullable()->after('personal_information');
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->json('competency_map')->nullable()->after('job_description');
            $table->unsignedTinyInteger('evidence_match_score')->default(0)->after('match_score');
            $table->json('evidence_matches')->nullable()->after('matched_keywords');
            $table->json('evidence_gaps')->nullable()->after('missing_keywords');
            $table->json('future_skills')->nullable()->after('evidence_gaps');
        });

        Schema::table('interview_sessions', function (Blueprint $table) {
            // Legacy is the safe default for records created outside the versioned interview flow.
            // The current flow always writes coached or assessment explicitly.
            $table->string('assessment_mode')->default('legacy')->after('live_feedback_mode');
            $table->string('interview_format')->default('standard')->after('assessment_mode');
            $table->json('accommodation_profile')->nullable()->after('interview_format');
            $table->boolean('score_eligible')->default(false)->after('accommodation_profile');
            $table->timestamp('share_expires_at')->nullable()->after('share_token');
            $table->string('share_password_hash')->nullable()->after('share_expires_at');
            $table->json('share_permissions')->nullable()->after('share_password_hash');
            $table->boolean('share_hide_sensitive')->default(true)->after('share_permissions');
        });

        Schema::table('interview_answers', function (Blueprint $table) {
            $table->unsignedTinyInteger('delivery_stability_score')->nullable()->after('confidence_score');
            $table->unsignedTinyInteger('self_reported_confidence')->nullable()->after('delivery_stability_score');
            $table->unsignedTinyInteger('scoring_confidence')->nullable()->after('self_reported_confidence');
            $table->json('evidence_map')->nullable()->after('recommendation_text');
            $table->string('rubric_level')->nullable()->after('evidence_map');
            $table->string('improved_answer_source')->default('illustrative')->after('rubric_level');
        });

        Schema::table('scores', function (Blueprint $table) {
            $table->unsignedInteger('score_version')->default(1)->after('interview_session_id');
            $table->string('assessment_mode')->default('legacy')->after('score_version');
            $table->string('readiness_band')->default('Developing')->after('overall_readiness_score');
            $table->unsignedTinyInteger('scoring_confidence')->default(0)->after('readiness_band');
            $table->unsignedTinyInteger('delivery_stability_score')->default(0)->after('confidence_score');
            $table->unsignedTinyInteger('job_evidence_match_score')->default(0)->after('ats_match_score');
            $table->json('evidence_map')->nullable()->after('star_method_score');
            $table->json('rubric')->nullable()->after('evidence_map');
            $table->boolean('body_language_included')->default(false)->after('rubric');
        });

        DB::table('scores')->update([
            'score_version' => 1,
            'assessment_mode' => 'legacy',
            'readiness_band' => DB::raw("CASE WHEN overall_readiness_score >= 80 THEN 'Ready for Simulation' WHEN overall_readiness_score >= 60 THEN 'Nearly Ready' ELSE 'Developing' END"),
            'body_language_included' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->dropColumn([
                'score_version', 'assessment_mode', 'readiness_band', 'scoring_confidence',
                'delivery_stability_score', 'job_evidence_match_score', 'evidence_map', 'rubric',
                'body_language_included',
            ]);
        });

        Schema::table('interview_answers', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_stability_score', 'self_reported_confidence', 'scoring_confidence',
                'evidence_map', 'rubric_level', 'improved_answer_source',
            ]);
        });

        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'assessment_mode', 'interview_format', 'accommodation_profile', 'score_eligible',
                'share_expires_at', 'share_password_hash', 'share_permissions', 'share_hide_sensitive',
            ]);
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['competency_map', 'evidence_match_score', 'evidence_matches', 'evidence_gaps', 'future_skills']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['inclusive_preferences']);
        });

        Schema::dropIfExists('interview_outcomes');
        Schema::dropIfExists('experience_stories');
        Schema::dropIfExists('readiness_profiles');
    }
};
