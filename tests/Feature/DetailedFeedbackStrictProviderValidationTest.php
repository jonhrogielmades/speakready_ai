<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use App\Services\AIService;
use App\Services\DetailedFeedbackReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DetailedFeedbackStrictProviderValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_provider_gate_requires_strict_feedback_or_safe_coaching_repair(): void
    {
        $answer = $this->answer();
        $feedback = $this->validProviderFeedback($answer);

        $verified = $this->validateProviderFeedback($answer, $feedback);

        $this->assertSame('verified', $verified['status'], json_encode($verified['reasons']));
        $this->assertTrue($verified['checks']['strict_provider_validation_passed']);
        $this->assertTrue($verified['checks']['provider_repair_safe']);

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
        $this->assertSame('verified', $safeRepairValidation['status']);
        $this->assertTrue($safeRepairValidation['checks']['provider_repair_safe']);

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
        $this->assertSame('verified', $summaryRepairValidation['status']);
        $this->assertTrue($summaryRepairValidation['checks']['provider_repair_safe']);

        $safeCombinedRepair = $safeSummaryRepair;
        $safeCombinedRepair['_repair_strategy'] = 'provider_coaching_and_session_summary_safety_repair';
        $safeCombinedRepair['_repair_scope'] = 'coaching_and_session_feedback';
        $safeCombinedRepair['_coaching_repaired_answer_ids'] = [(string) $answer->id];
        $safeCombinedRepair['review_context']['repair_strategy'] = $safeCombinedRepair['_repair_strategy'];
        $safeCombinedRepair['review_context']['repair_scope'] = $safeCombinedRepair['_repair_scope'];
        $safeCombinedRepair['review_context']['coaching_repaired_answer_ids'] = [(string) $answer->id];
        $combinedValidation = $this->validateProviderFeedback($answer, $safeCombinedRepair);
        $this->assertSame('verified', $combinedValidation['status']);
        $this->assertTrue($combinedValidation['checks']['provider_repair_safe']);

        $scoreDisagreement = $feedback;
        data_set($scoreDisagreement, 'per_question_feedback.0.scoring_confidence', 80);
        data_set($scoreDisagreement, 'per_question_feedback.0.feedback_quality.reliability_percent', 94);
        data_set($scoreDisagreement, 'feedback_quality.reliability_percent', 94);
        data_set($scoreDisagreement, 'review_context.feedback_quality.reliability_percent', 94);

        $scoreDisagreementValidation = $this->validateProviderFeedback($answer, $scoreDisagreement);

        $this->assertSame('rejected', $scoreDisagreementValidation['status']);
        $this->assertFalse($scoreDisagreementValidation['checks']['scoreable_reliability_threshold_met']);
        $this->assertContains('scoreable_reliability_threshold_met', $scoreDisagreementValidation['reasons']);
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

    public function test_verified_mixed_summary_persists_its_source_and_does_not_refresh_again(): void
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
        $this->assertSame('verified', $validation['status']);

        $service = $this->app->make(DetailedFeedbackReviewService::class);
        $persist = new ReflectionMethod(DetailedFeedbackReviewService::class, 'persistProviderFeedback');
        $persist->setAccessible(true);
        $persist->invoke($service, $session, collect([$answer]), $feedback, 'openai', $validation);

        $session = $session->fresh(['answers.question', 'feedback', 'score']);
        $summary = $session->feedback->coaching_summary;
        $this->assertSame('local_evidence_validated', data_get($summary, 'overall_summary_source'));
        $this->assertSame('mixed_provider_and_local_evidence', data_get($summary, 'review_context.session_feedback_source'));
        $this->assertSame(['overall_summary'], data_get($summary, 'review_context.session_feedback_repaired_fields'));
        $this->assertFalse($service->shouldRefreshWithProvider($session));
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
                'better_sample_answer' => 'I mapped the support handoff issue. I documented missed approvals and coordinated the checklist update. I confirmed the final handoff result with my supervisor.',
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
