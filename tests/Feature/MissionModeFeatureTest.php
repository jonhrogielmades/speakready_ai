<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\MissionAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
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
            ->assertSee('Showing the built-in practice set')
            ->assertDontSee('First Impression Sprint')
            ->assertSee('missionGenerateUrl', false)
            ->assertSee('missionSubmitUrl', false)
            ->assertDontSee('id="voiceMissionLink"', false);
    }

    public function test_authenticated_user_can_generate_personalized_missions(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $response = $this->actingAs($user)
            ->postJson(route('user.missions.generate'), [
                'goal' => 'Practice an IT support interview about debugging a network issue',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('persisted', true)
            ->assertJsonCount(4, 'missions')
            ->assertJsonStructure([
                'success',
                'source',
                'provider',
                'message',
                'persisted',
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
                        'attempt_id',
                    ],
                ],
            ]);

        $this->assertContains($response->json('source'), ['built_in', 'built_in_fallback']);
        $this->assertStringContainsString(
            'debugging a network issue',
            (string) $response->json('missions.0.prompt')
        );
        $this->assertDatabaseCount('mission_attempts', 4);
        $this->assertDatabaseHas('mission_attempts', [
            'user_id' => $user->id,
            'goal' => 'Practice an IT support interview about debugging a network issue',
            'mission_key' => $response->json('missions.0.id'),
            'status' => 'generated',
        ]);
        $this->assertNotNull($response->json('missions.0.attempt_id'));

        $this->actingAs($user)
            ->get(route('user.missions'))
            ->assertOk()
            ->assertSee((string) $response->json('missions.0.prompt'))
            ->assertSee('saved to your account');
    }

    public function test_configured_provider_generates_validated_missions_and_reports_its_source(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('mission-mode-test-key'),
            'status' => 'active',
            'is_primary' => true,
            'is_fallback' => false,
        ]);

        $missions = collect(range(1, 4))->map(fn (int $number): array => [
            'title' => "Provider Mission {$number}",
            'category' => 'Technical Interview',
            'difficulty' => $number === 1 ? 'Easy' : 'Medium',
            'duration' => 60,
            'intent' => 'Clear',
            'prompt' => "Explain debugging step {$number} using a true example.",
            'success_criteria' => [
                'State the issue clearly.',
                'Explain your own action.',
                'Give the true result or lesson.',
            ],
            'coach_tip' => 'Keep the technical sequence easy to follow.',
        ])->all();

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => [
                        'content' => json_encode(['missions' => $missions]),
                    ],
                ]],
            ]),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('user.missions.generate'), [
                'goal' => 'Practice a network debugging interview',
            ])
            ->assertOk()
            ->assertJsonPath('source', 'ai_provider')
            ->assertJsonPath('provider', 'openai')
            ->assertJsonPath('missions.0.title', 'Provider Mission 1')
            ->assertJsonCount(4, 'missions');

        $this->assertDatabaseHas('mission_attempts', [
            'user_id' => $user->id,
            'mission_key' => $response->json('missions.0.id'),
            'source' => 'ai_provider',
            'provider' => 'openai',
        ]);

        Http::assertSent(function ($request): bool {
            $prompt = (string) data_get($request->data(), 'messages.1.content', '');

            return str_contains($request->url(), 'api.openai.com')
                && str_contains($prompt, 'Practice a network debugging interview')
                && data_get($request->data(), 'response_format.type') === 'json_schema';
        });
    }

    public function test_server_scores_and_persists_an_authoritative_mission_result(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $generated = $this->actingAs($user)
            ->postJson(route('user.missions.generate'), ['goal' => 'Practice a support interview'])
            ->assertOk();
        $mission = $generated->json('missions.0');
        $answer = 'During a school project, I handled a difficult support issue because the client needed help. I improved the process, explained the result clearly, and I will apply that experience to support the team.';

        $response = $this->postJson(route('user.missions.submit'), [
            'attempt_id' => $mission['attempt_id'],
            'mission_id' => $mission['id'],
            'answer' => $answer,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.authoritative', true)
            ->assertJsonPath('result.persisted', true)
            ->assertJsonPath('result.score_version', 'mission_rules_v1')
            ->assertJsonPath('result.status', 'Mission Ready')
            ->assertJsonStructure([
                'attempt_id',
                'attempt_ids',
                'result' => ['score', 'status', 'summary', 'word_count', 'feedback', 'breakdown', 'score_version'],
            ]);

        $attempt = MissionAttempt::findOrFail($mission['attempt_id']);
        $this->assertSame($user->id, $attempt->user_id);
        $this->assertSame('submitted', $attempt->status);
        $this->assertSame($answer, $attempt->answer_text);
        $this->assertSame($response->json('result.score'), $attempt->score);
        $this->assertSame('mission_rules_v1', $attempt->score_version);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertSame($response->json('result.breakdown'), data_get($attempt->scoring_breakdown, 'criteria'));

        $this->get(route('user.missions'))
            ->assertOk()
            ->assertSee($answer)
            ->assertSee('Saved to your account');
    }

    public function test_user_cannot_submit_another_users_mission_attempt(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $owner = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $mission = $this->actingAs($owner)
            ->postJson(route('user.missions.generate'), ['goal' => 'Practice my interview'])
            ->assertOk()
            ->json('missions.0');

        $this->actingAs($otherUser)
            ->postJson(route('user.missions.submit'), [
                'attempt_id' => $mission['attempt_id'],
                'mission_id' => $mission['id'],
                'answer' => 'Because of a school project, I learned a useful lesson and I will apply it next time.',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('mission_attempts', [
            'id' => $mission['attempt_id'],
            'user_id' => $owner->id,
            'status' => 'generated',
            'answer_text' => null,
        ]);
    }

    public function test_submission_rejects_mismatched_mission_and_client_authored_scores(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $mission = $this->actingAs($user)
            ->postJson(route('user.missions.generate'), ['goal' => 'Practice an interview'])
            ->assertOk()
            ->json('missions.0');

        $payload = [
            'attempt_id' => $mission['attempt_id'],
            'mission_id' => 'a-different-mission',
            'answer' => 'During work I solved a real problem, learned from the result, and I will improve next time.',
        ];
        $this->postJson(route('user.missions.submit'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mission_id');

        $payload['mission_id'] = $mission['id'];
        $payload['score'] = 100;
        $this->postJson(route('user.missions.submit'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('score');

        $this->assertDatabaseHas('mission_attempts', [
            'id' => $mission['attempt_id'],
            'status' => 'generated',
            'score' => null,
        ]);
    }

    public function test_first_builtin_submission_snapshots_the_known_set_and_rejects_unknown_missions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $this->actingAs($user)
            ->postJson(route('user.missions.submit'), [
                'mission_id' => 'client-invented-mission',
                'answer' => 'During work I solved a real problem, learned from the result, and I will improve next time.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mission_id');
        $this->assertDatabaseCount('mission_attempts', 0);

        $response = $this->postJson(route('user.missions.submit'), [
            'mission_id' => 'direct-answer',
            'answer' => 'During work I solved a real problem, learned from the result, and I will improve next time.',
        ])->assertOk()
            ->assertJsonPath('result.authoritative', true)
            ->assertJsonCount(4, 'attempt_ids');

        $this->assertDatabaseCount('mission_attempts', 4);
        $this->assertDatabaseHas('mission_attempts', [
            'id' => $response->json('attempt_id'),
            'user_id' => $user->id,
            'mission_key' => 'direct-answer',
            'source' => 'built_in',
            'status' => 'submitted',
        ]);
    }
}
