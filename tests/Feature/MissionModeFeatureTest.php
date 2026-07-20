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
            ->assertSee('First Impression Sprint')
            ->assertSee('Polite Problem Report')
            ->assertSee('Convince With Evidence')
            ->assertSee('Growth Without Excuses')
            ->assertSee('missionVoiceUrl', false)
            ->assertSee(route('user.drills.voice'), false);
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
