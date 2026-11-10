<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameLevel;
use App\Models\Score;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use App\Jobs\RefreshDetailedFeedbackReview;
use App\Services\AIService;
use App\Services\DetailedFeedbackReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DetailedFeedbackStrictProviderValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_provider_gate_requires_provider_narratives_without_local_repairs(): void
    {
        $answer = $this->answer();
        $feedback = $this->validProviderFeedback($answer);

        $verified = $this->validateProviderFeedback($answer, $feedback);

        $this->assertSame('verified', $verified['status'], json_encode($verified['reasons']));
        $this->assertTrue($verified['checks']['strict_provider_validation_passed']);
        $this->assertTrue($verified['checks']['provider_repair_safe']);

        $missingNarrativeProvenance = $feedback;
        unset($missingNarrativeProvenance['_provider_narratives_verified']);
        $missingNarrativeValidation = $this->validateProviderFeedback($answer, $missingNarrativeProvenance);
        $this->assertSame('rejected', $missingNarrativeValidation['status']);
        $this->assertFalse($missingNarrativeValidation['checks']['provider_narratives_verified']);

        $localAnswerNarrative = $feedback;
        $localAnswerNarrative['per_question_feedback'][0]['answer_review_source'] = 'local_evidence';
        $localNarrativeValidation = $this->validateProviderFeedback($answer, $localAnswerNarrative);
        $this->assertSame('rejected', $localNarrativeValidation['status']);
        $this->assertFalse($localNarrativeValidation['answer_checks'][(string) $answer->id]['provider_narrative_source']);

        $missingStrictMetadata = $feedback;
        unset($missingStrictMetadata['_strict_validation_passed']);

        $missingStrictValidation = $this->validateProviderFeedback($answer, $missingStrictMetadata);

        $this->assertSame('rejected', $missingStrictValidation['status']);
        $this->assertFalse($missingStrictValidation['checks']['strict_provider_validation_passed']);
        $this->assertContains('strict_provider_validation_passed', $missingStrictValidation['reasons']);

        $repairedFeedback = $feedback;
        $repairedFeedback['_strict_validation_passed'] = false;
        $repairedFeedback['_repair_applied'] = true;

        $repairedValidation = $this->validateProviderFeedback($answer, $repairedFeedback);

        $this->assertSame('rejected', $repairedValidation['status']);
        $this->assertFalse($repairedValidation['checks']['strict_provider_validation_passed']);
        $this->assertFalse($repairedValidation['checks']['provider_repair_safe']);
        $this->assertContains('strict_provider_validation_passed', $repairedValidation['reasons']);
        $this->assertContains('provider_repair_safe', $repairedValidation['reasons']);

        $safeCoachingRepair = $feedback;
        $safeCoachingRepair['_repair_applied'] = true;
        $safeCoachingRepair['_repair_strategy'] = 'provider_coaching_safety_repair';
        $safeCoachingRepair['_repair_scope'] = 'coaching_only';
        $safeCoachingRepair['_coaching_repaired_answer_ids'] = [(string) $answer->id];
        $safeCoachingRepair['_session_feedback_repaired_fields'] = [];
        $safeCoachingRepair['review_context']['repair_strategy'] = 'provider_coaching_safety_repair';
        $safeCoachingRepair['review_context']['repair_scope'] = 'coaching_only';
        $safeCoachingRepair['review_context']['coaching_repaired_answer_ids'] = [(string) $answer->id];
        $safeCoachingRepair['review_context']['session_feedback_repaired_fields'] = [];
        $safeCoachingRepair['review_context']['session_feedback_source'] = 'ai_provider';

        $safeRepairValidation = $this->validateProviderFeedback($answer, $safeCoachingRepair);
        $this->assertSame('rejected', $safeRepairValidation['status']);
        $this->assertFalse($safeRepairValidation['checks']['provider_repair_safe']);

        $unmatchedRepair = $safeCoachingRepair;
        $unmatchedRepair['_coaching_repaired_answer_ids'] = ['999999'];
        $unmatchedValidation = $this->validateProviderFeedback($answer, $unmatchedRepair);
        $this->assertSame('rejected', $unmatchedValidation['status']);
        $this->assertFalse($unmatchedValidation['checks']['provider_repair_safe']);

        $safeSummaryRepair = $feedback;
        $safeSummaryRepair['_repair_applied'] = true;
        $safeSummaryRepair['_repair_strategy'] = 'provider_session_summary_safety_repair';
        $safeSummaryRepair['_repair_scope'] = 'session_feedback_only';
        $safeSummaryRepair['_coaching_repaired_answer_ids'] = [];
        $safeSummaryRepair['_session_feedback_repaired_fields'] = ['overall_summary'];
        $safeSummaryRepair['review_context']['repair_strategy'] = 'provider_session_summary_safety_repair';
        $safeSummaryRepair['review_context']['repair_scope'] = 'session_feedback_only';
        $safeSummaryRepair['review_context']['coaching_repaired_answer_ids'] = [];
        $safeSummaryRepair['review_context']['session_feedback_repaired_fields'] = ['overall_summary'];
        $safeSummaryRepair['review_context']['session_feedback_source'] = 'mixed_provider_and_local_evidence';
        $summaryRepairValidation = $this->validateProviderFeedback($answer, $safeSummaryRepair);
        $this->assertSame('rejected', $summaryRepairValidation['status']);
        $this->assertFalse($summaryRepairValidation['checks']['provider_repair_safe']);

        $safeCombinedRepair = $safeSummaryRepair;
        $safeCombinedRepair['_repair_strategy'] = 'provider_coaching_and_session_summary_safety_repair';
        $safeCombinedRepair['_repair_scope'] = 'coaching_and_session_feedback';
        $safeCombinedRepair['_coaching_repaired_answer_ids'] = [(string) $answer->id];
        $safeCombinedRepair['review_context']['repair_strategy'] = $safeCombinedRepair['_repair_strategy'];
        $safeCombinedRepair['review_context']['repair_scope'] = $safeCombinedRepair['_repair_scope'];
        $safeCombinedRepair['review_context']['coaching_repaired_answer_ids'] = [(string) $answer->id];
        $combinedValidation = $this->validateProviderFeedback($answer, $safeCombinedRepair);
        $this->assertSame('rejected', $combinedValidation['status']);
        $this->assertFalse($combinedValidation['checks']['provider_repair_safe']);

    }

    public function test_calibrated_provider_review_with_limited_confidence_is_saved_without_an_endless_refresh(): void
    {
        $answer = $this->answer();
        $session = InterviewSession::findOrFail($answer->interview_session_id);
        $service = $this->app->make(DetailedFeedbackReviewService::class);
        $normalizeQuality = new ReflectionMethod(AIService::class, 'normalizedFeedbackQuality');

        foreach ([
            ['checked' => true, 'coverage_flags' => ['limited_question_coverage']],
            ['checked' => true, 'adjustment_applied' => true],
            ['checked' => false],
        ] as $calibration) {
            $feedback = $this->validProviderFeedback($answer);
            $item = $feedback['per_question_feedback'][0];
            $item['score_calibration'] = array_replace(['source' => 'deterministic_evidence_cross_check'], $calibration);
            $quality = $normalizeQuality->invoke(null, $item, $answer->answer_text, $answer->question->question_text);
            $this->assertSame('verified', $quality['status']);
            $this->assertLessThan(95, $quality['reliability_percent']);
            $item['feedback_quality'] = $quality;
            $feedback['per_question_feedback'][0] = $item;
            $feedback['feedback_quality'] = $quality;
            $feedback['review_context']['feedback_quality'] = $quality;

            $validation = $this->validateProviderFeedback($answer, $feedback);

            $this->assertSame('verified', $validation['status'], json_encode($validation['reasons']));
            $this->assertFalse($validation['scoreable_reliability_threshold_met']);
            $this->assertSame([], $validation['reasons']);
            $this->assertSame($quality['reliability_percent'], $validation['minimum_scoreable_reliability_percent']);
            $this->assertTrue($validation['answer_checks'][(string) $answer->id]['all_safeguards_passed']);
        }

        (new ReflectionMethod(DetailedFeedbackReviewService::class, 'persistProviderFeedback'))->invoke(
            $service, $session, collect([$answer]), $feedback, 'openai', $validation
        );
        $savedSession = $session->fresh(['answers.question', 'feedback', 'score']);
        $savedAnswer = $answer->fresh('question');
        $this->assertSame($item['ai_feedback'], $savedAnswer->ai_feedback);
        $this->assertSame($item['better_sample_answer'], $savedAnswer->better_sample_answer);
        $this->assertSame($quality['reliability_percent'], data_get($savedSession->feedback->coaching_summary,
            'review_context.detailed_review_validation.minimum_scoreable_reliability_percent'));
        $this->assertFalse(data_get($savedSession->feedback->coaching_summary,
            'review_context.detailed_review_validation.scoreable_reliability_threshold_met'));
        $this->assertFalse($service->shouldRefreshWithProvider($savedSession));
        $this->assertTrue($service->answerReviewProviderState($savedAnswer)['feedback_ready']);
        $this->assertTrue($service->answerReviewProviderState($savedAnswer)['possible_answer_ready']);
    }

    public function test_valid_provider_feedback_wins_over_a_stale_local_fallback_marker(): void
    {
        $answer = $this->answer();
        $session = InterviewSession::findOrFail($answer->interview_session_id);
        $service = $this->app->make(DetailedFeedbackReviewService::class);
        $feedback = $this->validProviderFeedback($answer);
        $validation = $this->validateProviderFeedback($answer, $feedback);

        (new ReflectionMethod(DetailedFeedbackReviewService::class, 'persistProviderFeedback'))->invoke(
            $service, $session, collect([$answer]), $feedback, 'openai', $validation
        );

        $savedAnswer = $answer->fresh('question');
        $savedAnswer->forceFill([
            'coaching_feedback' => array_replace(
                $savedAnswer->coaching_feedback ?? [],
                ['local_fallback' => true, 'local_fallback_reason' => 'old_provider_failure']
            ),
        ])->save();

        $state = $service->answerReviewProviderState($savedAnswer->fresh('question'));

        $this->assertTrue($state['feedback_ready']);
        $this->assertSame($feedback['per_question_feedback'][0]['ai_feedback'], $state['feedback']);
        $this->assertSame('openai', $savedAnswer->fresh()->ai_provider);
    }

    public function test_final_gate_still_rejects_invalid_reliability_metadata_and_failed_safeguards(): void
    {
        $answer = $this->answer();
        foreach ([null, 'unknown', -1, 101, INF, NAN] as $invalidReliability) {
            $feedback = $this->validProviderFeedback($answer);
            data_set($feedback, 'per_question_feedback.0.feedback_quality.reliability_percent', $invalidReliability);
            data_set($feedback, 'feedback_quality.reliability_percent', $invalidReliability);
            data_set($feedback, 'review_context.feedback_quality.reliability_percent', $invalidReliability);

            $validation = $this->validateProviderFeedback($answer, $feedback);

            $this->assertSame('rejected', $validation['status']);
            $this->assertFalse($validation['answer_checks'][(string) $answer->id]['score_handling_safe']);

            $invalidAggregate = $this->validProviderFeedback($answer);
            data_set($invalidAggregate, 'feedback_quality.reliability_percent', $invalidReliability);
            data_set($invalidAggregate, 'review_context.feedback_quality.reliability_percent', $invalidReliability);
            $validation = $this->validateProviderFeedback($answer, $invalidAggregate);
            $this->assertSame('rejected', $validation['status']);
            $this->assertFalse($validation['checks']['aggregate_safeguards_passed']);
            $this->assertFalse($validation['checks']['review_context_quality_matches']);
        }

        $feedback = $this->validProviderFeedback($answer);
        data_set($feedback, 'per_question_feedback.0.feedback_quality.reliability_percent', 89);
        data_set($feedback, 'feedback_quality.reliability_percent', 89);
        data_set($feedback, 'review_context.feedback_quality.reliability_percent', 89);
        data_set($feedback, 'per_question_feedback.0.feedback_quality.checks.answer_evidence_linked', false);
        $validation = $this->validateProviderFeedback($answer, $feedback);
        $this->assertSame('rejected', $validation['status']);
        $this->assertFalse($validation['answer_checks'][(string) $answer->id]['all_safeguards_passed']);
    }

    public function test_review_refresh_preserves_voice_and_unedited_hybrid_asr_formatting_provenance(): void
    {
        $answer = $this->answer();
        $service = $this->app->make(DetailedFeedbackReviewService::class);
        $method = new ReflectionMethod(DetailedFeedbackReviewService::class, 'answerData');
        $method->setAccessible(true);

        $answer->forceFill([
            'response_mode' => 'voice',
            'answer_text' => 'Voice transcript with provider formatting.',
            'delivery_transcript' => 'voice transcript with provider formatting',
            'voice_duration' => 18,
        ]);
        $voice = $method->invoke($service, $answer);

        $this->assertSame('voice', $voice['response_mode']);
        $this->assertSame(18, $voice['voice_duration']);
        $this->assertTrue($voice['uses_asr_transcript']);

        $answer->forceFill([
            'response_mode' => 'hybrid',
            'answer_text' => 'unedited hybrid transcript',
            'delivery_transcript' => 'unedited hybrid transcript',
        ]);
        $uneditedHybrid = $method->invoke($service, $answer);
        $this->assertTrue($uneditedHybrid['uses_asr_transcript']);

        $answer->forceFill([
            'answer_text' => 'I edited the hybrid transcript and added my own punctuation.',
            'delivery_transcript' => 'i edited the hybrid transcript',
        ]);
        $editedHybrid = $method->invoke($service, $answer);
        $this->assertFalse($editedHybrid['uses_asr_transcript']);

        $answer->forceFill([
            'response_mode' => 'text',
            'answer_text' => 'Typed answer.',
            'delivery_transcript' => 'Typed answer.',
        ]);
        $typed = $method->invoke($service, $answer);
        $this->assertFalse($typed['uses_asr_transcript']);
    }

    public function test_final_gate_rechecks_source_evidence_even_when_all_quality_flags_claim_success(): void
    {
        $answer = $this->answer();
        $feedback = $this->validProviderFeedback($answer);

        $fabricatedQuote = $feedback;
        $fabricatedQuote['per_question_feedback'][0]['evidence_quotes'] = ['I improved customer satisfaction by 35%.'];
        $validation = $this->validateProviderFeedback($answer, $fabricatedQuote);
        $this->assertSame('rejected', $validation['status']);
        $this->assertFalse($validation['answer_checks'][(string) $answer->id]['evidence_quotes_verbatim']);

        $incorrectlyUnscored = $feedback;
        $incorrectlyUnscored['per_question_feedback'][0]['is_too_short'] = true;
        $incorrectlyUnscored['per_question_feedback'][0]['insufficient_data'] = true;
        $incorrectlyUnscored['per_question_feedback'][0]['score'] = 5;
        $incorrectlyUnscored['per_question_feedback'][0]['scoring_confidence'] = 10;
        $validation = $this->validateProviderFeedback($answer, $incorrectlyUnscored);
        $this->assertSame('rejected', $validation['status']);
        $this->assertFalse($validation['answer_checks'][(string) $answer->id]['eligibility_matches_saved_answer']);

        $unboundedScore = $feedback;
        $unboundedScore['per_question_feedback'][0]['grammar_score'] = 150;
        $validation = $this->validateProviderFeedback($answer, $unboundedScore);
        $this->assertSame('rejected', $validation['status']);
        $this->assertFalse($validation['answer_checks'][(string) $answer->id]['numeric_scores_safe']);

        $falseCheckCounts = $feedback;
        $falseCheckCounts['per_question_feedback'][0]['feedback_quality']['checks'] = ['pretend_check' => true];
        $validation = $this->validateProviderFeedback($answer, $falseCheckCounts);
        $this->assertSame('rejected', $validation['status']);
        $this->assertFalse($validation['answer_checks'][(string) $answer->id]['all_safeguards_passed']);
    }

    public function test_final_gate_accepts_verbatim_evidence_with_transcript_whitespace_normalization(): void
    {
        $answer = $this->answer();
        $feedback = $this->validProviderFeedback($answer);
        $answer->answer_text = str_replace(', ', ",\n\t", $answer->answer_text);

        $validation = $this->validateProviderFeedback($answer, $feedback);

        $this->assertSame('verified', $validation['status'], json_encode($validation['answer_checks']));
        $this->assertTrue($validation['answer_checks'][(string) $answer->id]['evidence_quotes_verbatim']);
    }

    public function test_a_slow_provider_response_cannot_overwrite_feedback_after_the_answer_changes(): void
    {
        $answer = $this->answer();
        $session = InterviewSession::findOrFail($answer->interview_session_id);
        $feedback = $this->validProviderFeedback($answer);
        $validation = $this->validateProviderFeedback($answer, $feedback);
        $this->assertSame('verified', $validation['status']);

        InterviewAnswer::whereKey($answer->id)->update([
            'answer_text' => 'I have not yet handled this support handoff.',
            'ai_feedback' => 'Newer saved feedback must be preserved.',
        ]);

        $persist = new ReflectionMethod(DetailedFeedbackReviewService::class, 'persistProviderFeedback');
        try {
            $persist->invoke($this->app->make(DetailedFeedbackReviewService::class), $session, collect([$answer]), $feedback, 'openai', $validation);
            $this->fail('Stale provider feedback must be rejected.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('inputs changed', $error->getMessage());
        }

        $this->assertSame('Newer saved feedback must be preserved.', $answer->fresh()->ai_feedback);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $session->id]);
    }

    public function test_a_changed_question_invalidates_a_verified_review_snapshot(): void
    {
        $answer = $this->answer();
        $session = InterviewSession::findOrFail($answer->interview_session_id);
        $feedback = $this->validProviderFeedback($answer);
        $validation = $this->validateProviderFeedback($answer, $feedback);
        $service = $this->app->make(DetailedFeedbackReviewService::class);
        $persist = new ReflectionMethod(DetailedFeedbackReviewService::class, 'persistProviderFeedback');
        $persist->invoke($service, $session, collect([$answer]), $feedback, 'openai', $validation);

        $saved = $session->fresh(['answers.question', 'feedback', 'score']);
        $this->assertNotEmpty(data_get($saved->feedback->coaching_summary, 'review_context.detailed_review_validation.input_fingerprint'));
        $this->assertTrue($service->reviewInputsMatchSavedValidation($saved));
        $this->assertFalse($service->shouldRefreshWithProvider($saved));

        $answer->question->update(['expected_guide' => 'Explain a disagreement and how you resolved it.']);
        $this->assertFalse($service->reviewInputsMatchSavedValidation($session->fresh(['answers.question', 'feedback', 'score'])));
        $this->assertTrue($service->shouldRefreshWithProvider($session->fresh(['answers.question', 'feedback', 'score'])));
    }

    public function test_final_gate_rejects_unsupported_achievement_embedded_in_advice(): void
    {
        $answer = $this->answer();
        $feedback = $this->validProviderFeedback($answer);
        $feedback['per_question_feedback'][0]['ai_feedback'] .= ' Keep the handoff detail because you earned a promotion.';
        $validation = $this->validateProviderFeedback($answer, $feedback);
        $this->assertSame('rejected', $validation['status']);
    }

    public function test_queued_provider_failure_retries_current_inputs_without_local_repair(): void
    {
        config()->set('queue.default', 'database');
        $answer = $this->answer();
        $session = InterviewSession::findOrFail($answer->interview_session_id);
        $replacement = 'I checked the current handoff log and escalated the unresolved approval.';
        $service = \Mockery::mock(DetailedFeedbackReviewService::class);
        $service->shouldReceive('refreshWithProvidersIfNeeded')->once()->andReturnUsing(
            function (InterviewSession $candidate) use ($answer, $replacement): bool {
                $candidate->load('answers.question');
                InterviewAnswer::whereKey($answer->id)->update(['answer_text' => $replacement]);

                return false;
            }
        );
        $service->shouldReceive('hasConfiguredProvider')->once()->andReturn(true);
        $service->shouldReceive('shouldRefreshWithProvider')->once()->withArgs(
            fn (InterviewSession $candidate): bool => $candidate->answers->first()->answer_text === $replacement
        )->andReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AI detailed review is still pending');
        (new RefreshDetailedFeedbackReview((int) $session->id, (int) $session->user_id))->handle($service);
    }

    public function test_mixed_local_summary_is_rejected_before_persistence(): void
    {
        $answer = $this->answer();
        $session = InterviewSession::findOrFail($answer->interview_session_id);
        $feedback = $this->validProviderFeedback($answer);
        $feedback['_repair_applied'] = true;
        $feedback['_repair_strategy'] = 'provider_session_summary_safety_repair';
        $feedback['_repair_scope'] = 'session_feedback_only';
        $feedback['_coaching_repaired_answer_ids'] = [];
        $feedback['_session_feedback_repaired_fields'] = ['overall_summary'];
        $feedback['review_context']['repair_strategy'] = $feedback['_repair_strategy'];
        $feedback['review_context']['repair_scope'] = $feedback['_repair_scope'];
        $feedback['review_context']['coaching_repaired_answer_ids'] = [];
        $feedback['review_context']['session_feedback_repaired_fields'] = ['overall_summary'];
        $feedback['review_context']['session_feedback_source'] = 'mixed_provider_and_local_evidence';
        $validation = $this->validateProviderFeedback($answer, $feedback);
        $this->assertSame('rejected', $validation['status']);
        $this->assertFalse($validation['checks']['provider_repair_safe']);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $session->id]);
    }

    public function test_desktop_and_mobile_show_pending_provider_review_instead_of_local_replacement(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        $answer = $this->answer();
        $session = InterviewSession::findOrFail($answer->interview_session_id);
        $user = User::findOrFail($session->user_id);
        $user->forceFill(['is_admin' => false])->save();
        $answer->update([
            'ai_feedback' => 'Keep your support handoff detail because you earned an international award.',
            'ai_provider' => 'openai',
            'better_sample_answer' => 'I coordinated the checklist update. I earned an international award. I increased revenue by 90%.',
            'coaching_feedback' => ['content_alignment' => [
                'possible_answer_source' => 'ai_provider',
                'possible_answer_provider' => 'openai',
            ]],
        ]);

        foreach (['Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148'] as $agent) {
            $this->actingAs($user)->withHeader('User-Agent', $agent)
                ->get(route('user.review', $session))
                ->assertOk()
                ->assertSee('Overall Review')
                ->assertSee('Possible Answer')
                ->assertSee('AI feedback is not ready for this question yet.')
                ->assertSee('An AI-generated possible answer is not available for this question yet.')
                ->assertSee('documented missed approvals')
                ->assertDontSee('international award')
                ->assertDontSee('90%');
        }
        \Illuminate\Support\Facades\Http::assertNothingSent();
        $this->assertStringContainsString('international award', $answer->fresh()->ai_feedback);
    }

    public function test_changed_answers_show_pending_provider_review_and_score_until_refresh(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $answer = $this->answer();
        $session = InterviewSession::findOrFail($answer->interview_session_id);
        $user = User::findOrFail($session->user_id);
        $user->forceFill(['is_admin' => false])->save();
        $feedback = $this->validProviderFeedback($answer);
        $validation = $this->validateProviderFeedback($answer, $feedback);
        (new ReflectionMethod(DetailedFeedbackReviewService::class, 'persistProviderFeedback'))->invoke(
            $this->app->make(DetailedFeedbackReviewService::class), $session, collect([$answer]), $feedback, 'openai', $validation
        );
        $answer->update(['answer_text' => 'I have not handled a support handoff. I only observed the team and do not know the result.']);

        foreach (['desktop', 'mobile'] as $layout) {
            $this->actingAs($user)->get(route('user.review', $session).'?sr_layout='.$layout)
                ->assertOk()
                ->assertSee('<strong>Pending</strong>', false)
                ->assertSee('I only observed the team')
                ->assertSee('AI feedback is not ready for this question yet.')
                ->assertDontSee('I mapped the support handoff issue')
                ->assertDontSee('<strong>84%</strong>', false);
        }
        $this->assertStringContainsString('I mapped the support handoff issue', $answer->fresh()->ai_feedback);
    }

    public function test_game_provider_refresh_updates_narrative_without_overwriting_game_scores(): void
    {
        $answer = $this->answer();
        $session = InterviewSession::findOrFail($answer->interview_session_id);
        $level = GameLevel::create([
            'category_id' => $session->category_id,
            'level_number' => 1,
            'title' => 'Support Handoff',
            'description' => 'Practice describing a support handoff.',
            'mission_text' => 'Describe how you improved a support handoff.',
            'difficulty' => 'intermediate',
            'required_score' => 80,
            'xp_reward' => 500,
            'energy_cost' => 1,
        ]);
        $session->update(['game_level_id' => $level->id, 'action_plan' => ['headline' => 'Original game plan']]);
        $answer->update(['score' => 42, 'clarity_score' => 48, 'relevance_score' => 39, 'grammar_score' => 52]);
        $score = Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => 48,
            'relevance_score' => 39,
            'grammar_score' => 52,
            'professionalism_score' => 61,
            'overall_readiness_score' => 42,
        ]);
        $savedScore = $score->fresh()->getAttributes();
        $feedback = $this->validProviderFeedback($answer);
        $validation = $this->validateProviderFeedback($answer, $feedback);
        $this->assertSame('verified', $validation['status']);

        (new ReflectionMethod(DetailedFeedbackReviewService::class, 'persistProviderFeedback'))->invoke(
            $this->app->make(DetailedFeedbackReviewService::class), $session, collect([$answer]), $feedback, 'openai', $validation
        );

        $this->assertSame($savedScore, $score->fresh()->getAttributes());
        $this->assertSame(42, (int) $answer->fresh()->score);
        $this->assertSame(48, (int) $answer->fresh()->clarity_score);
        $this->assertSame('Original game plan', data_get($session->fresh()->action_plan, 'headline'));
        $this->assertSame($feedback['per_question_feedback'][0]['ai_feedback'], $answer->fresh()->ai_feedback);
        $state = $this->app->make(DetailedFeedbackReviewService::class)->answerReviewProviderState($answer->fresh('question'));
        $this->assertTrue($state['feedback_ready']);
        $this->assertTrue($state['possible_answer_ready']);
    }

    private function validateProviderFeedback(InterviewAnswer $answer, array $feedback): array
    {
        $method = new ReflectionMethod(DetailedFeedbackReviewService::class, 'providerFeedbackValidation');
        $method->setAccessible(true);

        return $method->invoke(
            $this->app->make(DetailedFeedbackReviewService::class),
            collect([$answer]),
            $feedback,
            'openai'
        );
    }

    private function answer(): InterviewAnswer
    {
        $user = User::factory()->create(['status' => 'active']);
        $category = Category::create(['title' => 'Behavioral']);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Support Specialist',
            'status' => 'completed',
        ]);
        $question = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Describe how you improved a support handoff.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'expected_guide' => 'Explain the handoff issue, your action, and the final result.',
            'mapped_skills' => ['Communication', 'Process Improvement'],
        ]);

        return InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I mapped the support handoff issue, documented missed approvals, coordinated the checklist update, and confirmed the final handoff result with my supervisor.',
            'response_mode' => 'text',
        ])->load('question');
    }

    private function validProviderFeedback(InterviewAnswer $answer): array
    {
        $qualityChecks = [
            'question_linked' => true,
            'answer_evidence_linked' => true,
            'scores_bounded_and_recomputed' => true,
            'score_calibration_recorded' => true,
            'alignment_cross_checked' => true,
            'uncertainty_reported' => true,
            'next_attempt_actionable' => true,
            'impact_explained_in_plain_words' => true,
            'personal_trait_inference_excluded' => true,
            'perfect_accuracy_not_claimed' => true,
        ];
        $quality = [
            'status' => 'verified',
            'checks_passed' => count($qualityChecks),
            'checks_total' => count($qualityChecks),
            'completeness_percent' => 100,
            'reliability_percent' => 98,
            'reliability_band' => 'High',
            'checks' => $qualityChecks,
        ];
        $question = (string) $answer->question->question_text;
        $quote = 'I mapped the support handoff issue, documented missed approvals, coordinated the checklist update, and confirmed the final handoff result with my supervisor.';

        return [
            '_provider_key' => 'openai',
            '_providers_attempted' => ['openai'],
            '_strict_validation_passed' => true,
            '_provider_narratives_verified' => true,
            '_repair_applied' => false,
            'per_question_feedback' => [[
                'id' => $answer->id,
                'score' => 84,
                'clarity_score' => 84,
                'relevance_score' => 88,
                'grammar_score' => 82,
                'professionalism_score' => 84,
                'star_applicable' => true,
                'star_method_score' => 75,
                'scoring_confidence' => 92,
                'insufficient_data' => false,
                'ai_feedback' => 'You said "'.$quote.'" This directly answers the support handoff question with your action and final check. It helps the interviewer judge your handoff approach. Next, add the true effect on the team or customer.',
                'answer_review_source' => 'ai_provider',
                'better_sample_answer' => 'I mapped the support handoff issue. I documented missed approvals and coordinated the checklist update. I confirmed the final handoff result with my supervisor.',
                'better_sample_answer_source' => 'ai_provider',
                'follow_up_question' => 'What true effect did the checklist update have on the support handoff?',
                'evidence_quotes' => [$quote],
                'question_focus' => $question,
                'missing_criteria' => ['the final result'],
                'evaluation_source' => 'ai_evidence_validated',
                'answer_alignment' => 'directly_addressed',
                'is_skipped' => false,
                'is_too_short' => false,
                'feedback_quality' => $quality,
            ]],
            'session_feedback' => [
                'overall_summary' => 'The support handoff answer explains the checklist update and final supervisor check. The next focus is the true effect of that process change.',
                'strengths' => 'The answer describes documenting missed approvals and coordinating the checklist update.',
                'weaknesses' => 'The support handoff result does not yet show the effect on the team or customer.',
                'improvement_suggestions' => 'Add the true effect of the checklist update on the support handoff.',
            ],
            'review_context' => [
                'schema_version' => 'interview_feedback_v9',
                'review_policy_version' => AIService::REVIEW_POLICY_VERSION,
                'provider_key' => 'openai',
                'provider_source' => 'ai_provider',
                'coverage' => [
                    'answer_count' => 1,
                    'provider_validated_count' => 1,
                ],
                'feedback_quality' => $quality,
            ],
            'feedback_quality' => $quality,
        ];
    }
}
