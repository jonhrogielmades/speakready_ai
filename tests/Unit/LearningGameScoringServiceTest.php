<?php

namespace Tests\Unit;

use App\Models\GameLevel;
use App\Services\LearningGameScoringService;
use PHPUnit\Framework\TestCase;

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

        $scorecard = $result['ai_feedback_scorecard'];

        $this->assertSame('AI Feedback Scorecard', $scorecard['title']);
        $this->assertArrayHasKey('clarity', $scorecard['metrics']);
        $this->assertArrayHasKey('relevance', $scorecard['metrics']);
        $this->assertArrayHasKey('confidence', $scorecard['metrics']);
        $this->assertArrayHasKey('grammar', $scorecard['metrics']);
        $this->assertArrayHasKey('professionalism', $scorecard['metrics']);
        $this->assertGreaterThan(0, $scorecard['reliability_score']);
        $this->assertNotEmpty($scorecard['question_feedback']);
        $this->assertFalse($scorecard['body_language_included']);
        $this->assertStringContainsString('submitted challenge answers', $scorecard['evidence_policy']);
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
        $this->assertSame('Submit a complete answer before relying on this challenge score.', $result['per_question'][0]['scorecard_feedback']);
    }
}
