<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use App\Services\DetailedFeedbackReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DetailedFeedbackStrictProviderValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_provider_gate_requires_strict_unrepaired_feedback_metadata(): void
    {
        $answer = $this->answer();
        $feedback = $this->validProviderFeedback($answer);

        $verified = $this->validateProviderFeedback($answer, $feedback);

        $this->assertSame('verified', $verified['status']);
        $this->assertTrue($verified['checks']['strict_provider_validation_passed']);
        $this->assertTrue($verified['checks']['provider_repair_not_applied']);

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
        $this->assertFalse($repairedValidation['checks']['provider_repair_not_applied']);
        $this->assertContains('strict_provider_validation_passed', $repairedValidation['reasons']);
        $this->assertContains('provider_repair_not_applied', $repairedValidation['reasons']);

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
