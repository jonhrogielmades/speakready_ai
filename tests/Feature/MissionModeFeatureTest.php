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
            ->assertDontSee('First Impression Sprint')
            ->assertSee('missionVoiceModal', false)
            ->assertSee('AI Speak Mission')
            ->assertSee('missionGenerateUrl', false)
            ->assertDontSee('id="voiceMissionLink"', false);
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
        $prompt = 'Explain a customer or team problem politely, acknowledge the concern, and propose the next action.';

        $this->actingAs($user)
            ->get(route('user.drills.voice', [
                'mission' => 'polite-problem',
                'category' => 'Customer Service',
                'intent' => 'Calm',
                'prompt' => $prompt,
            ]))
            ->assertOk()
            ->assertSee('<meta name="csrf-token"', false)
            ->assertSee('Emotion & Intention Coach', false)
            ->assertSee('<option value="Customer Service">Customer Service</option>', false)
            ->assertSee('voiceMissionPreset', false)
            ->assertSee($prompt);
    }
}
