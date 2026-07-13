<?php

namespace Tests\Feature;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiCoachDeveloperCreditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_clear_only_their_ai_coach_history(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $otherUser = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $conversation = ChatbotConversation::create(['user_id' => $user->id, 'title' => 'My coach chat']);
        ChatbotMessage::create([
            'chatbot_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Help me prepare.',
        ]);

        $otherConversation = ChatbotConversation::create(['user_id' => $otherUser->id, 'title' => 'Other coach chat']);

        $this->actingAs($user)
            ->deleteJson(route('user.coach.clear'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('chatbot_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('chatbot_messages', ['chatbot_conversation_id' => $conversation->id]);
        $this->assertDatabaseHas('chatbot_conversations', ['id' => $otherConversation->id]);
    }

    public function test_ai_coach_answers_developer_credit_questions_from_official_team(): void
    {
        Http::fake();

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson(route('user.coach.chat'), [
            'message' => 'Who are the Developers of this system?',
            'history' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('language', 'en');

        $answer = $response->json('response');

        $this->assertStringContainsString('**Jonh Rogiel M. Tumanda**', $answer);
        $this->assertStringContainsString('Lead Programmer', $answer);
        $this->assertStringContainsString('Core Code, Databases, and APIs.', $answer);
        $this->assertStringContainsString('**Karyl G. Gesto**', $answer);
        $this->assertStringContainsString('Manuscript Editor', $answer);
        $this->assertStringContainsString('Technical Writing, Documentation, and Compliance.', $answer);
        $this->assertStringContainsString('**Eva Mae C. Cabilic**', $answer);
        $this->assertStringContainsString('QA Tester', $answer);
        $this->assertStringContainsString('Bug Hunting, Test Cases, and UX Stability.', $answer);

        $this->assertSame(2, ChatbotMessage::count());
        Http::assertNothingSent();
    }

    public function test_developer_credits_follow_filipino_cebuano_and_taglish_questions(): void
    {
        Http::fake();

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'preferred_language' => 'en',
        ]);

        $cases = [
            [
                'message' => 'Sino ang mga developer ng sistemang ito?',
                'language' => 'fil',
                'expected' => ['Ang mga developer', '**Tungkulin:**', 'at mga API.'],
            ],
            [
                'message' => 'Kinsa ang mga developer ani nga sistema?',
                'language' => 'ceb',
                'expected' => ['Mao kini ang mga developer', '**Papel:**', 'ug mga API.'],
            ],
            [
                'message' => 'Who ang developers ng system na ito?',
                'language' => 'taglish',
                'expected' => ['Ito ang developer team', '**Role:**', 'databases, at APIs.'],
            ],
        ];

        foreach ($cases as $case) {
            $response = $this->actingAs($user)->postJson(route('user.coach.chat'), [
                'message' => $case['message'],
                'history' => [],
            ]);

            $response->assertOk()->assertJsonPath('language', $case['language']);
            $answer = $response->json('response');

            foreach ($case['expected'] as $expected) {
                $this->assertStringContainsString($expected, $answer);
            }

            $this->assertStringContainsString('**Jonh Rogiel M. Tumanda**', $answer);
            $this->assertStringContainsString('Lead Programmer', $answer);
            $this->assertStringContainsString('**Karyl G. Gesto**', $answer);
            $this->assertStringContainsString('Manuscript Editor', $answer);
            $this->assertStringContainsString('**Eva Mae C. Cabilic**', $answer);
            $this->assertStringContainsString('QA Tester', $answer);
        }

        $this->assertSame(6, ChatbotMessage::count());
        Http::assertNothingSent();
    }

    public function test_detected_language_is_sent_to_the_ai_provider_as_a_strict_instruction(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Narito ang isang mas malinaw na sagot.'],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'preferred_language' => 'en',
        ]);

        $response = $this->actingAs($user)->postJson(route('user.coach.chat'), [
            'message' => 'Paano ko mapapaganda ang sagot ko sa interview?',
            'history' => [],
            'provider' => 'gemini',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('language', 'fil')
            ->assertJsonPath('response', 'Narito ang isang mas malinaw na sagot.');

        Http::assertSent(function ($request) {
            $instruction = $request->data()['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($instruction, 'Strict response-language requirement')
                && str_contains($instruction, 'natural Filipino (Tagalog)')
                && str_contains($instruction, 'latest user message takes priority');
        });
    }
}
