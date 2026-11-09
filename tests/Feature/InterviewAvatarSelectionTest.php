<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use App\Services\AIService;
use App\Support\InterviewerAvatar;
use App\Support\InterviewSessionSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class InterviewAvatarSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_setup_layouts_offer_a_compact_interviewer_picker_and_modal_choices(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $this->category();
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
        ];

        foreach ($userAgents as $userAgent) {
            $response = $this->actingAs($user)
                ->withHeader('User-Agent', $userAgent)
                ->get(route('interview.setup'));

            $response->assertOk()
                ->assertSeeInOrder(['id="interviewerAvatarTrigger"', 'id="interviewerAvatarDialog"', 'id="interviewerAvatarGroup"'], false)
                ->assertSee('id="interviewerAvatarCancel"', false)
                ->assertSee('id="interviewerAvatarConfirm"', false)
                ->assertDontSee('id="interviewerAvatarClose"', false)
                ->assertSee('js/interview-setup-avatar-modal.js?v=5', false);

            foreach (InterviewerAvatar::keys() as $key) {
                $response->assertSee('name="interviewer_avatar" form="setupForm" value="'.$key.'"', false);
            }

            preg_match_all('/<input[^>]*name="interviewer_avatar"[^>]*>/s', $response->getContent(), $avatarInputs);
            $this->assertCount(6, $avatarInputs[0]);
            foreach ($avatarInputs[0] as $avatarInput) {
                $this->assertDoesNotMatchRegularExpression('/\schecked(?:\s|>)/', $avatarInput);
            }
            $response->assertSee('id="interviewerAvatarSelectedName">Choose an AI interviewer</span>', false);
        }
    }

    public function test_every_interviewer_has_distinct_existing_portrait_and_room_images(): void
    {
        $portraits = [];
        $backgrounds = [];

        foreach (InterviewerAvatar::keys() as $avatar) {
            $portrait = InterviewerAvatar::image($avatar);
            $background = InterviewerAvatar::background($avatar);

            $this->assertFileExists(public_path($portrait));
            $this->assertFileExists(public_path(str_replace('.webp', '.png', $portrait)));
            $this->assertFileExists(public_path($background));
            $portraits[] = $portrait;
            $backgrounds[] = $background;
        }

        $this->assertCount(6, array_unique($portraits));
        $this->assertCount(6, array_unique($backgrounds));
        $this->assertCount(6, array_unique(array_map(fn (string $path): string => sha1_file(public_path($path)), $portraits)));
        $this->assertCount(6, array_unique(array_map(fn (string $path): string => sha1_file(public_path($path)), $backgrounds)));
    }

    public function test_setup_ignores_stale_old_avatar_without_validation_errors_but_keeps_it_after_an_error(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $this->category();
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
        ];

        foreach ($userAgents as $userAgent) {
            $fresh = $this->actingAs($user)
                ->withSession([
                    '_old_input' => ['interviewer_avatar' => 'role_specialist'],
                    'errors' => new ViewErrorBag,
                ])
                ->withHeader('User-Agent', $userAgent)
                ->get(route('interview.setup'));

            $fresh->assertOk()
                ->assertSee('id="interviewerAvatarSelectedName">Choose an AI interviewer</span>', false);
            preg_match('/<input[^>]*name="interviewer_avatar"[^>]*value="role_specialist"[^>]*>/', $fresh->getContent(), $freshInput);
            $this->assertNotEmpty($freshInput);
            $this->assertDoesNotMatchRegularExpression('/\schecked(?:\s|>)/', $freshInput[0]);

            $errors = new ViewErrorBag;
            $errors->put('default', new MessageBag(['target_position' => ['Target position is required.']]));
            $retry = $this->actingAs($user)
                ->withSession([
                    '_old_input' => ['interviewer_avatar' => 'role_specialist'],
                    'errors' => $errors,
                ])
                ->withHeader('User-Agent', $userAgent)
                ->get(route('interview.setup'));

            $retry->assertOk()
                ->assertSee('id="interviewerAvatarSelectedName">Role Specialist</span>', false);
            preg_match('/<input[^>]*name="interviewer_avatar"[^>]*value="role_specialist"[^>]*>/', $retry->getContent(), $retryInput);
            $this->assertNotEmpty($retryInput);
            $this->assertMatchesRegularExpression('/\schecked(?:\s|>)/', $retryInput[0]);
        }
    }

    public function test_desktop_dashboard_mock_form_requires_an_explicit_interviewer_choice(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $this->category();
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $response = $this->actingAs($user)
            ->withHeader('User-Agent', $desktopUserAgent)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('name="interviewer_avatar" id="dashboardMockInterviewerAvatar" required', false)
            ->assertSee('<option value="" disabled selected>Choose an AI interviewer</option>', false);

        foreach (InterviewerAvatar::keys() as $avatar) {
            $response->assertSee('<option value="'.$avatar.'"', false);
        }

        $this->withSession(['_old_input' => ['interviewer_avatar' => 'role_specialist']])
            ->withHeader('User-Agent', $desktopUserAgent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<option value="role_specialist" selected>Role Specialist</option>', false);
    }

    public function test_each_interviewer_selection_is_persisted_and_used_for_the_local_opening(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $openingCues = [
            'friendly_recruiter' => 'No need to be perfect',
            'structured_hr' => 'begin with your background',
            'hiring_manager' => 'work you would bring',
            'role_specialist' => 'experience behind your approach',
            'senior_leader' => 'shaped your judgment',
            'panel_interviewer' => 'support your answers',
        ];

        foreach ($openingCues as $avatar => $openingCue) {
            $this->actingAs($user)
                ->post(route('interview.start'), $this->payload($category, ['interviewer_avatar' => $avatar]))
                ->assertRedirect(route('interview.session'));

            $session = InterviewSession::where('user_id', $user->id)->latest('id')->firstOrFail();
            $opening = Question::where('interview_session_id', $session->id)
                ->where('source_type', 'real_interview_opening')
                ->firstOrFail();

            $this->assertSame($avatar, $session->interviewer_avatar);
            $this->assertStringContainsString($openingCue, $opening->question_text);
            $this->assertStringContainsString('Tell me about yourself', $opening->question_text);
        }
    }

    public function test_start_requires_an_explicit_valid_interviewer_selection(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        $this->actingAs($user)
            ->from(route('interview.setup'))
            ->post(route('interview.start'), $this->payload($category))
            ->assertRedirect(route('interview.setup'))
            ->assertSessionHasErrors('interviewer_avatar');

        $this->actingAs($user)
            ->from(route('interview.setup'))
            ->post(route('interview.start'), $this->payload($category, ['interviewer_avatar' => '']))
            ->assertRedirect(route('interview.setup'))
            ->assertSessionHasErrors('interviewer_avatar');

        $this->actingAs($user)
            ->post(route('interview.start'), $this->payload($category, ['interviewer_avatar' => 'unsupported']))
            ->assertSessionHasErrors('interviewer_avatar');

        $this->assertSame(0, InterviewSession::where('user_id', $user->id)->count());
        $this->assertSame(InterviewerAvatar::DEFAULT, (new InterviewSession)->interviewer_avatar);

        $this->actingAs($user)
            ->from(route('interview.setup'))
            ->post(route('interview.start'), $this->payload($category, ['interviewer_avatar' => ['unsupported']]))
            ->assertRedirect(route('interview.setup'))
            ->assertSessionHasErrors('interviewer_avatar');

        $this->actingAs($user)->get(route('interview.setup'))->assertOk();
    }

    public function test_persona_changes_local_follow_up_focus_while_preserving_a_single_role_question(): void
    {
        $answer = 'I redesigned the approval queue, tested the change with finance, and reduced handoff time for our internal team during a busy month.';
        $session = (object) [
            'target_position' => 'Developer',
            'live_feedback_mode' => 'real_interview',
        ];
        $questions = [];
        $replies = [];

        foreach (InterviewerAvatar::keys() as $avatar) {
            $session->interviewer_avatar = $avatar;
            $reply = AIService::fallbackInterviewReply($session, [], $answer);
            $this->assertSame(1, substr_count($reply, '?'));
            $this->assertStringContainsString('approval queue', $reply);
            $questions[] = preg_replace('/^.*[”]\.\s*/u', '', $reply);
            $replies[] = $reply;
        }

        $this->assertCount(6, array_unique($questions));
        $this->assertCount(6, array_unique($replies));
    }

    public function test_interviewer_style_keeps_the_requested_assistance_level_in_local_follow_ups(): void
    {
        $session = (object) [
            'target_position' => 'Developer',
            'interviewer_avatar' => 'senior_leader',
            'live_feedback_mode' => 'coaching',
            'ai_assistance_level' => 'beginner',
        ];
        $answer = 'I coordinated the handoff with my team, improved approval speed, and delivered a clear result for the customer.';

        $beginnerReply = AIService::fallbackInterviewReply($session, [], $answer);
        $this->assertStringContainsString('What was your main reason for that choice?', $beginnerReply);

        $session->ai_assistance_level = 'challenge';
        $challengeReply = AIService::fallbackInterviewReply($session, [], $answer);
        $this->assertStringContainsString('What evidence did you use to rule out another approach?', $challengeReply);
        $this->assertSame(1, substr_count($beginnerReply, '?'));
        $this->assertSame(1, substr_count($challengeReply, '?'));
    }

    public function test_provider_prompts_apply_selected_style_without_changing_difficulty_or_question_type(): void
    {
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);
        $messages = [];
        $replies = [
            'Hi, nice to meet you. I will be your AI interviewer for the Developer interview. Tell me about yourself.',
            'Thank you for that example. What tradeoff shaped the decision you made as a Developer?',
        ];

        Http::fake([
            'api.openai.com/*' => function ($request) use (&$messages, &$replies) {
                $messages[] = data_get($request->data(), 'messages', []);

                return Http::response(['choices' => [['message' => ['content' => array_shift($replies)]]]], 200);
            },
        ]);

        $session = (object) [
            'target_position' => 'Developer',
            'difficulty' => 'easy',
            'interview_focus' => 'Job Interview',
            'interviewer_avatar' => 'senior_leader',
            'question_types' => json_encode(['Behavioral']),
            'ai_assistance_level' => 'standard',
            'live_feedback_mode' => 'real_interview',
            'accommodation_profile' => [],
        ];

        $opening = AIService::generateInterviewOpeningIntro($session, 'openai');
        $followUp = AIService::generateChatReply($session, [], 'I led the rollout and improved delivery for our team.', 'openai', false, null, [], null, 'Behavioral');

        $this->assertSame('openai', $opening['provider']);
        $this->assertStringContainsString('Tell me about yourself', $opening['text']);
        $this->assertStringContainsString('tradeoff', $followUp);
        $this->assertCount(2, $messages);
        $this->assertStringContainsString('Senior Leader', (string) data_get($messages, '0.1.content'));
        $this->assertStringContainsString('Senior Leader', (string) data_get($messages, '1.1.content'));
        $this->assertStringContainsString("difficulty is 'easy'", (string) data_get($messages, '1.1.content'));
        $this->assertStringContainsString('must be Behavioral', (string) data_get($messages, '1.1.content'));
    }

    public function test_schema_repair_includes_avatar_column_and_legacy_values_have_safe_default(): void
    {
        $this->assertTrue(Schema::hasColumn('interview_sessions', 'interviewer_avatar'));
        $this->assertTrue(InterviewSessionSchema::hasRequiredColumns());
        $this->assertSame(InterviewerAvatar::DEFAULT, InterviewerAvatar::normalize('unknown'));
        $this->assertSame(InterviewerAvatar::DEFAULT, InterviewerAvatar::normalize(null));
        $this->assertSame(InterviewerAvatar::DEFAULT, InterviewerAvatar::normalize(['unknown']));

        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->dropColumn('interviewer_avatar');
        });
        InterviewSession::flushColumnCache();
        $this->assertFalse(InterviewSession::hasColumn('interviewer_avatar'));

        InterviewSessionSchema::ensure(force: true);
        $this->assertTrue(InterviewSession::hasColumn('interviewer_avatar'));
        $this->assertTrue(InterviewSessionSchema::hasRequiredColumns());
    }

    private function category(): Category
    {
        return Category::create([
            'title' => 'Job Interview',
            'description' => 'Interview practice',
            'status' => 'active',
            'type' => 'core',
        ]);
    }

    private function payload(Category $category, array $extra = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 5,
            'response_mode' => 'text',
            'time_limit' => 0,
            'ai_provider' => 'local',
        ], $extra);
    }
}
