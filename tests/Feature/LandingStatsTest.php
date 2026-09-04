<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Score;
use App\Models\User;
use App\Services\LandingStatsService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use PDOException;
use Tests\TestCase;

class LandingStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_landing_stats_are_rendered_from_database(): void
    {
        $users = User::factory()->count(4)->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $category = Category::create([
            'title' => 'Behavioral',
            'description' => 'Behavioral interview practice',
            'status' => 'active',
            'type' => 'core',
        ]);

        $activeQuestion = $this->question($category, 'Describe a time you solved a hard problem.');
        $this->question($category, 'How do you handle changing priorities?');
        $this->question($category, 'Inactive practice prompt.', ['status' => 'inactive']);

        $scores = [80, 76, 74, 60, 92];
        $sessions = [];

        foreach ($scores as $score) {
            $session = InterviewSession::create([
                'user_id' => $users->first()->id,
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'target_position' => 'Developer',
                'num_questions' => 1,
                'coach_focus_mode' => 'balanced',
                'response_mode' => 'text',
                'status' => 'completed',
            ]);

            Score::create([
                'interview_session_id' => $session->id,
                'clarity_score' => $score,
                'relevance_score' => $score,
                'grammar_score' => $score,
                'professionalism_score' => $score,
                'overall_readiness_score' => $score,
            ]);

            $sessions[] = $session;
        }

        Feedback::create([
            'interview_session_id' => $sessions[0]->id,
            'strengths' => 'Clear structure.',
            'weaknesses' => 'Needs more evidence.',
            'improvement_suggestions' => 'Add measurable results.',
        ]);

        foreach (array_slice($sessions, 0, 3) as $session) {
            InterviewAnswer::create([
                'interview_session_id' => $session->id,
                'question_id' => $activeQuestion->id,
                'answer_text' => 'I improved the process.',
                'response_mode' => 'text',
                'ai_feedback' => 'Useful feedback.',
                'score' => 80,
            ]);
        }

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertLandingStat($content, 'registered-users', '4');
        $this->assertLandingStat($content, 'interview-sessions', '5');
        $this->assertLandingStat($content, 'questions-available', '2');
        $this->assertLandingStat($content, 'feedback-generated', '4');
        $this->assertLandingStat($content, 'success-rate', '60');
    }

    public function test_landing_stats_fall_back_to_zero_when_database_is_unavailable(): void
    {
        Schema::shouldReceive('hasTable')
            ->with('users')
            ->once()
            ->andThrow(new QueryException(
                'mysql',
                'select * from information_schema.tables',
                [],
                new PDOException('SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it')
            ));

        $stats = app(LandingStatsService::class)->summary();

        $this->assertSame('0', $stats['registered_users']['display']);
        $this->assertSame('0', $stats['interview_sessions']['display']);
        $this->assertSame('0', $stats['questions_available']['display']);
        $this->assertSame('0', $stats['feedback_generated']['display']);
        $this->assertSame('0', $stats['success_rate']['display']);
    }

    public function test_guest_landing_page_renders_when_auth_database_check_fails(): void
    {
        Auth::shouldReceive('check')
            ->once()
            ->ordered()
            ->andThrow(new QueryException(
                'mysql',
                'select * from `users` where `id` = ? limit 1',
                [1],
                new PDOException('SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it')
            ));
        Auth::shouldReceive('logout')->once()->ordered();
        Auth::shouldReceive('check')->andReturnFalse();

        $this->get('/')->assertOk();
    }

    public function test_guest_landing_page_renders_when_language_view_composer_database_check_fails(): void
    {
        app()->instance(LandingStatsService::class, new class extends LandingStatsService {
            public function summary(): array
            {
                return [
                    'registered_users' => ['value' => 0, 'display' => '0'],
                    'interview_sessions' => ['value' => 0, 'display' => '0'],
                    'questions_available' => ['value' => 0, 'display' => '0'],
                    'feedback_generated' => ['value' => 0, 'display' => '0'],
                    'success_rate' => ['value' => 0, 'display' => '0'],
                ];
            }
        });

        Schema::shouldReceive('hasTable')
            ->with('settings')
            ->once()
            ->andThrow(new QueryException(
                'mysql',
                'select * from information_schema.tables',
                [],
                new PDOException('SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it')
            ));

        $this->get('/')->assertOk();
    }

    private function question(Category $category, string $text, array $overrides = []): Question
    {
        return Question::create(array_merge([
            'category_id' => $category->id,
            'question_text' => $text,
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ], $overrides));
    }

    private function assertLandingStat(string $content, string $key, string $value): void
    {
        $this->assertMatchesRegularExpression(
            '/data-landing-stat="'.$key.'".*?class="[^"]*pnum[^"]*".*?>\s*(?:<span class="counter">)?'.preg_quote($value, '/').'(?:<\/span>)?\s*(?:%)?\s*<\/div>/s',
            $content
        );
    }
}
