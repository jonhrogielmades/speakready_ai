<?php

namespace Tests\Unit;

use App\Models\GameLevel;
use App\Services\LearningGameScoringService;
use Tests\TestCase;

class LearningGameScoringServiceTest extends TestCase
{
    public function test_it_returns_ai_feedback_scorecard_with_confidence_and_reliability(): void
    {
        $level = new GameLevel([
            'title' => 'STAR Evidence Sprint',
            'skill_focus' => 'STAR Method',
            'learning_objective' => 'Give a complete answer with action and result.',
            'success_criteria' => "1. Give context.\n2. Explain action.\n3. State result.",
            'required_score' => 80,
        ]);

        $result = (new LearningGameScoringService)->scoreSession($level, [[
            'id' => 10,
            'question_index' => 0,
            'question' => 'Tell me about a time you improved a process.',
            'answer' => 'During my internship, I owned the daily support checklist. I organized repeated issues, coordinated with my supervisor, and improved the handoff process so the team resolved common requests faster.',
            'is_skipped' => false,
            'wpm' => 135,
            'voice_duration' => 70,
            'filler_words_count' => 1,
            'pause_count' => 1,
        ]]);

        $this->assertArrayHasKey('ai_feedback_scorecard', $result);
        $this->assertArrayHasKey('confidence', $result['averages']);
        $this->assertArrayHasKey('confidence_score', $result['per_question'][0]);
        $this->assertSame(3, $result['scorecard_version']);

        $scorecard = $result['ai_feedback_scorecard'];

        $this->assertSame('Evidence-Based Feedback Scorecard', $scorecard['title']);
        $this->assertArrayHasKey('clarity', $scorecard['metrics']);
        $this->assertArrayHasKey('relevance', $scorecard['metrics']);
        $this->assertArrayHasKey('confidence', $scorecard['metrics']);
        $this->assertArrayHasKey('grammar', $scorecard['metrics']);
        $this->assertArrayHasKey('professionalism', $scorecard['metrics']);
        $this->assertGreaterThan(0, $scorecard['reliability_score']);
        $this->assertNotEmpty($scorecard['question_feedback']);
        $this->assertFalse($scorecard['body_language_included']);
        $this->assertStringContainsString('submitted challenge answers', $scorecard['evidence_policy']);
        $this->assertTrue($scorecard['quality_gate']['passed']);
        $this->assertTrue($result['score_eligible']);
        $this->assertFalse($result['accuracy_guaranteed']);
    }

    public function test_skipped_answers_have_limited_scorecard_reliability(): void
    {
        $level = new GameLevel([
            'title' => 'Opening Challenge',
            'success_criteria' => '1. Answer directly.',
            'required_score' => 80,
        ]);

        $result = (new LearningGameScoringService)->scoreSession($level, [[
            'question_index' => 0,
            'question' => 'Tell me about yourself.',
            'answer' => '(Skipped or no answer)',
            'is_skipped' => true,
        ]]);

        $this->assertSame(0, $result['averages']['confidence']);
        $this->assertSame('Limited', $result['ai_feedback_scorecard']['reliability_band']);
        $this->assertFalse($result['quality_gate']['passed']);
        $this->assertFalse($result['score_eligible']);
        $this->assertSame('Submit a complete answer before relying on this challenge score.', $result['per_question'][0]['scorecard_feedback']);
    }

    public function test_non_behavioral_game_answer_never_receives_star_points(): void
    {
        $level = new GameLevel([
            'title' => 'Technical Explanation',
            'skill_focus' => 'Clarity',
            'learning_objective' => 'Explain a technical decision directly.',
            'success_criteria' => "1. Name the issue.\n2. Explain the checks.",
            'required_score' => 80,
        ]);

        $result = (new LearningGameScoringService)->scoreSession($level, [[
            'id' => 11,
            'question_index' => 0,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow database query?',
            'answer' => 'The situation was a slow query. My task was diagnosis, so I checked the query plan and indexes. My action was to compare row estimates, and the result was a verified bottleneck.',
            'is_skipped' => false,
        ]]);

        $answer = $result['per_question'][0];

        $this->assertFalse($answer['star_applicable']);
        $this->assertSame(0, $answer['star_method_score']);
        $this->assertNotSame('star_method', $answer['weakest_metric']);
        $this->assertStringNotContainsString('STAR', $answer['scorecard_feedback']);
        $this->assertArrayNotHasKey('star_method', $result['ai_feedback_scorecard']['metrics']);
        $this->assertStringNotContainsString('STAR', implode(' ', $result['ai_feedback_scorecard']['priority_actions']));
    }

    public function test_descriptive_technical_prompt_is_not_forced_into_star_while_past_example_remains_behavioral(): void
    {
        $level = new GameLevel([
            'title' => 'Mixed Explanation Challenge',
            'skill_focus' => 'STAR Method',
            'learning_objective' => 'Use STAR when the prompt asks for a past example.',
            'success_criteria' => "1. Explain the answer clearly.\n2. Use STAR for behavioral prompts.",
            'required_score' => 70,
        ]);
        $scorer = new LearningGameScoringService;

        $technical = $scorer->scoreAnswer([
            'question_index' => 0,
            'question' => 'Describe how DNS resolution works during an outage.',
            'answer' => 'A resolver checks its cache, asks the configured DNS hierarchy, and returns the available record or a lookup error.',
            'is_skipped' => false,
        ], $level);
        $behavioral = $scorer->scoreAnswer([
            'question_index' => 1,
            'question' => 'Describe a time you diagnosed a DNS outage.',
            'answer' => 'During an outage, I checked resolver logs, isolated a stale record, updated it, and restored successful lookups.',
            'is_skipped' => false,
        ], $level);

        $this->assertFalse($technical['star_applicable']);
        $this->assertSame(0, $technical['star_method_score']);
        $this->assertTrue($behavioral['star_applicable']);
        $this->assertGreaterThan(0, $behavioral['star_method_score']);
    }

    public function test_typed_answers_earn_formatting_credit_while_voice_and_hybrid_asr_formatting_is_neutral(): void
    {
        $level = new GameLevel([
            'title' => 'Clear Support Reply',
            'skill_focus' => 'Clarity',
            'learning_objective' => 'Explain the support response clearly.',
            'success_criteria' => '1. Explain the action.',
            'required_score' => 70,
        ]);
        $base = [
            'question_index' => 0,
            'question_type' => 'Technical',
            'question' => 'How would you respond to the support request?',
            'is_skipped' => false,
        ];
        $scorer = new LearningGameScoringService;
        $formattedTyped = $scorer->scoreAnswer($base + [
            'answer' => 'I checked the request details and explained the next action to the customer.',
            'response_mode' => 'text',
        ], $level);
        $unformattedTyped = $scorer->scoreAnswer($base + [
            'answer' => 'i checked the request details and explained the next action to the customer',
            'response_mode' => 'text',
        ], $level);
        $formattedVoice = $scorer->scoreAnswer($base + [
            'answer' => 'I checked the request details and explained the next action to the customer.',
            'response_mode' => 'voice',
            'voice_duration' => 12,
        ], $level);
        $unformattedVoice = $scorer->scoreAnswer($base + [
            'answer' => 'i checked the request details and explained the next action to the customer',
            'response_mode' => 'voice',
            'voice_duration' => 12,
        ], $level);
        $formattedHybridAsr = $scorer->scoreAnswer($base + [
            'answer' => 'I checked the request details and explained the next action to the customer.',
            'response_mode' => 'hybrid',
            'voice_duration' => 12,
            'uses_asr_transcript' => true,
        ], $level);
        $unformattedHybridAsr = $scorer->scoreAnswer($base + [
            'answer' => 'i checked the request details and explained the next action to the customer',
            'response_mode' => 'hybrid',
            'voice_duration' => 12,
            'uses_asr_transcript' => true,
        ], $level);
        $typedHybrid = $scorer->scoreAnswer($base + [
            'answer' => 'I checked the request details and explained the next action to the customer.',
            'response_mode' => 'hybrid',
            'voice_duration' => 12,
            'uses_asr_transcript' => false,
        ], $level);

        $this->assertSame(16, $formattedTyped['grammar_score'] - $unformattedTyped['grammar_score']);
        $this->assertSame($formattedVoice['grammar_score'], $unformattedVoice['grammar_score']);
        $this->assertSame($formattedHybridAsr['grammar_score'], $unformattedHybridAsr['grammar_score']);
        $this->assertSame($unformattedTyped['grammar_score'] + 8, $formattedVoice['grammar_score']);
        $this->assertSame(
            (int) (($formattedTyped['grammar_score'] + $unformattedTyped['grammar_score']) / 2),
            $formattedVoice['grammar_score']
        );
        $this->assertSame($formattedTyped['grammar_score'], $typedHybrid['grammar_score']);
    }
}
