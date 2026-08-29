<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($user)
            ->get(route('user.missions'))
            ->assertOk()
            ->assertSee('Real-Life Mission Mode')
            ->assertSee('Generate Task')
            ->assertSee('Tell AI what you want to practice to generate your mission tasks.', false)
            ->assertSee('First Impression Sprint')
            ->assertSee('Introduce yourself to a Philippine recruiter in 60 seconds', false)
            ->assertSee('missionVoiceModal', false)
            ->assertSee('AI Speak Mission')
            ->assertSee('missionGenerateUrl', false)
            ->assertDontSee('id="voiceMissionLink"', false);
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
            ->assertSee('setMissionVoiceButtonStates', false)
            ->assertSee('onboarding_completed_mobile_user_missions', false)
            ->assertSee('missionVoiceModal', false);
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
