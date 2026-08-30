<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\User;
use App\Models\VoiceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class MissionModeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_mission_mode(): void
    {
        $this->get(route('user.missions'))
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_open_mission_mode(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        VoiceSession::create([
            'user_id' => $user->id,
            'category' => 'Tell Me About Yourself',
            'prompt' => 'Introduce yourself to a Philippine recruiter.',
            'transcript' => 'I connected my background to the role.',
            'speaking_pace' => 132,
            'clarity_score' => 84,
            'confidence_score' => 80,
            'filler_words' => 1,
            'duration_seconds' => 42,
            'wpm' => 132,
        ]);

        $this->actingAs($user)
            ->get(route('user.missions'))
            ->assertOk()
            ->assertSee('Real-Life Mission Mode')
            ->assertSee('1 saved session')
            ->assertSee('Generate Task')
            ->assertSee('Tell AI what you want to practice to generate your mission tasks.', false)
            ->assertSee('First Impression Sprint')
            ->assertSee('Introduce yourself to a Philippine recruiter in 60 seconds', false)
            ->assertSee('&middot;', false)
            ->assertSee('missionVoiceModal', false)
            ->assertSee('AI Speak Mission')
            ->assertSee('function missionElement', false)
            ->assertSee('missionResponseJson', false)
            ->assertSee('missionGenerateUrl', false)
            ->assertDontSee('id="voiceMissionLink"', false)
            ->assertDontSee("alert('Add an answer", false);
    }

    public function test_mobile_user_can_open_mission_mode_with_starter_missions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
            ->get(route('user.missions'))
            ->assertOk()
            ->assertSee('class="user-mobile-shell mobile-shell"', false)
            ->assertSee('css/mobile/user/missions.css?v=1', false)
            ->assertSee('First Impression Sprint')
            ->assertSee('function bindMissionElement', false)
            ->assertSee('setMissionVoiceButtonStates', false)
            ->assertSee('onboarding_completed_mobile_user_missions', false)
            ->assertSee('missionVoiceModal', false);
    }

    public function test_mission_mode_repairs_missing_voice_session_schema(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        Schema::dropIfExists('voice_sessions');

        $this->actingAs($user)
            ->get(route('user.missions'))
            ->assertOk()
            ->assertSee('Real-Life Mission Mode')
            ->assertSee('0 saved sessions');

        $this->assertTrue(Schema::hasTable('voice_sessions'));
        $this->assertTrue(Schema::hasColumn('voice_sessions', 'user_id'));
        $this->assertTrue(Schema::hasColumn('voice_sessions', 'pronunciation_score'));
    }

    public function test_authenticated_user_can_generate_personalized_missions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->postJson(route('user.missions.generate'), [
                'goal' => 'Practice an IT support interview about debugging a network issue',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'missions')
            ->assertJsonStructure([
                'success',
                'missions' => [
                    '*' => [
                        'id',
                        'title',
                        'category',
                        'difficulty',
                        'duration',
                        'intent',
                        'icon',
                        'color',
                        'prompt',
                        'success_criteria',
                        'coach_tip',
                    ],
                ],
            ]);
    }

    public function test_mission_generation_rejects_unrelated_goals(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->postJson(route('user.missions.generate'), [
                'goal' => 'cook pasta at home',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Mission Mode can generate tasks for interview, school admission, career, or Philippine workplace communication practice.');
    }

    public function test_mission_payload_normalization_handles_malformed_generated_data(): void
    {
        $controller = app(UserController::class);
        $normalize = new ReflectionMethod(UserController::class, 'normalizeMission');
        $normalize->setAccessible(true);
        $uniqueMissions = new ReflectionMethod(UserController::class, 'uniqueMissions');
        $uniqueMissions->setAccessible(true);

        $goal = 'Practice an IT support interview about debugging a network issue';
        $payload = [
            'id' => 'Shared Mission<script>',
            'title' => 'Debug Support Scenario With A Very Long Title That Must Fit',
            'category' => 'cooking',
            'difficulty' => 'challenge',
            'duration' => 999,
            'intent' => 'calm',
            'icon' => 'fa-"><script>',
            'color' => 'blue',
            'prompt' => 'Debug a network issue for a BPO support interview and explain one result to the recruiter.',
            'success_criteria' => [
                'good answer',
                'Names the customer issue and action taken',
                'Shows the result in the final sentence',
                'Keeps the answer tied to the role',
            ],
            'coach_tip' => ['bad' => 'shape'],
        ];

        $first = $normalize->invoke($controller, $payload, 0, $goal);
        $second = $normalize->invoke($controller, $payload, 1, $goal);
        $missions = $uniqueMissions->invoke($controller, collect([$first, $second]));

        $this->assertSame('Job Interviews', $first->category);
        $this->assertSame('Challenge', $first->difficulty);
        $this->assertSame('Calm', $first->intent);
        $this->assertSame(120, $first->duration);
        $this->assertSame('fa-handshake-angle', $first->icon);
        $this->assertSame('#2563eb', $first->color);
        $this->assertLessThanOrEqual(42, mb_strlen($first->title));
        $this->assertNotContains('good answer', $first->success_criteria);
        $this->assertCount(3, $first->success_criteria);
        $this->assertStringContainsString('answering directly', $first->coach_tip);
        $this->assertCount(2, $missions);
        $this->assertNotSame($missions[0]->id, $missions[1]->id);
        $this->assertLessThanOrEqual(64, mb_strlen($missions[0]->id));
        $this->assertLessThanOrEqual(64, mb_strlen($missions[1]->id));
    }

    public function test_voice_rehearsal_supports_mission_preset_and_intention_coach(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $prompt = 'Explain why this school or program fits your goals and readiness.';

        $this->actingAs($user)
            ->get(route('user.drills.voice', [
                'mission' => 'polite-problem',
                'category' => 'School Admission',
                'intent' => 'Calm',
                'prompt' => $prompt,
            ]))
            ->assertOk()
            ->assertSee('<meta name="csrf-token"', false)
            ->assertSee('Emotion & Intention Coach', false)
            ->assertSee('<option value="School Admission">School Admission Interviews</option>', false)
            ->assertSee('voiceMissionPreset', false)
            ->assertSee($prompt);
    }
}
