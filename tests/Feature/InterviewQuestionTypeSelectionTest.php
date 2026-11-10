<?php

namespace Tests\Feature;

use App\Http\Controllers\InterviewController;
use App\Models\AiProvider;
use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class InterviewQuestionTypeSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_types_rotate_after_the_separate_personal_opening_and_are_persisted(): void
    {
        [$session, $category] = $this->makeSession(['Behavioral', 'Situational', 'Technical', 'Personal']);
        $this->question($session, $category, 'Please introduce yourself.', 'Personal', 'real_interview_opening');

        $questions = [
            'Behavioral' => 'Can you describe a specific time you handled a work challenge?',
            'Situational' => 'If priorities changed suddenly, what would you do first?',
            'Technical' => 'What technical tools would you use to diagnose the problem?',
            'Personal' => 'Which strength from your background would help you in this role?',
        ];

        foreach (array_values($questions) as $offset => $text) {
            $type = array_keys($questions)[$offset];
            $this->assertSame($type, $this->invoke('selectedQuestionTypeForIndex', $session, $offset + 1));
            $this->assertTrue($this->invoke('questionMatchesSelectedType', $text, $type));
            $created = $this->invoke(
                'createInterviewQuestion',
                $session,
                $category,
                $text,
                'medium',
                array_keys($questions),
                $offset + 1,
                ['question_type' => 'Behavioral', 'source_type' => 'ai_adapted_source_backed']
            );
            $this->assertSame($type, $created->fresh()->type);
        }

        $this->assertTrue($this->invoke(
            'questionMatchesSelectedType',
            'If an API failed during a Developer release, what would you check first?',
            'Situational'
        ));

        $this->assertSame('Behavioral', $this->invoke('selectedQuestionTypeForIndex', $session, 5));
    }

    public function test_off_type_dataset_rows_are_not_used_for_a_selected_type(): void
    {
        $dataset = [
            'questions' => [
                ['question_text' => 'What is your greatest strength?', 'type' => 'Personal', 'difficulty' => 'Medium'],
                ['question_text' => 'If a deadline moved forward, what would you do first?', 'type' => 'situational', 'difficulty' => 'Easy'],
            ],
        ];

        $record = $this->invoke('firstLocalSourceQuestionRecord', $dataset, ['Situational'], 'medium', 'Developer', []);
        $this->assertSame('Situational', $record['type']);
        $this->assertStringContainsString('deadline', $record['question_text']);
        $this->assertNull($this->invoke('firstLocalSourceQuestionRecord', $dataset, ['Technical'], 'medium', 'Developer', []));
    }

    public function test_seaman_source_selection_skips_software_and_requires_a_maritime_match(): void
    {
        $software = [
            'question_text' => 'How would you debug a cloud software deployment for the Seaman role?',
            'type' => 'Technical',
            'difficulty' => 'Medium',
        ];
        $maritime = [
            'question_text' => 'What procedure would you use to inspect lifeboat safety equipment before vessel departure?',
            'type' => 'Technical',
            'difficulty' => 'Medium',
        ];

        $record = $this->invoke(
            'firstLocalSourceQuestionRecord',
            ['questions' => [$software, $maritime]],
            ['Technical'],
            'medium',
            'Seaman',
            []
        );

        $this->assertNotNull($record);
        $this->assertStringContainsString('lifeboat safety equipment', $record['question_text']);
        $this->assertNull($this->invoke(
            'firstLocalSourceQuestionRecord',
            ['questions' => [$software]],
            ['Technical'],
            'medium',
            'Seaman',
            []
        ));
    }

    public function test_type_specific_fallbacks_match_each_selected_type(): void
    {
        [$session] = $this->makeSession(['Behavioral', 'Situational', 'Technical', 'Personal']);

        foreach (['Behavioral', 'Situational', 'Technical', 'Personal'] as $type) {
            $text = $this->invoke('fallbackQuestionForSelectedType', $session, $type, 'I improved the approval queue.', false);
            $this->assertTrue($this->invoke('questionMatchesSelectedType', $text, $type), "Wrong wording for {$type}: {$text}");
            $this->assertStringContainsString('Developer', $text);
            $this->assertSame(1, substr_count($text, '?'));
        }
    }

    public function test_seaman_fallbacks_use_maritime_situations_and_keep_the_selected_type(): void
    {
        [$session] = $this->makeSession(['Behavioral', 'Situational', 'Technical', 'Personal']);
        $session->target_position = 'Seaman';
        $answer = 'I inspected lifeboat equipment before departure and reported expired flares to the captain.';

        foreach (['Behavioral', 'Situational', 'Technical', 'Personal'] as $type) {
            $text = $this->invoke('fallbackQuestionForSelectedType', $session, $type, $answer, false);

            $this->assertMatchesRegularExpression(
                '/\b(?:vessel|ship|deck|watchkeeping|navigation|maritime|lifeboat|cargo|bridge|port|crew|safety|emergency)\b/i',
                $text,
                "Fallback lacks a maritime situation for {$type}: {$text}"
            );
            $this->assertTrue($this->invoke('questionMatchesSelectedType', $text, $type), "Wrong wording for {$type}: {$text}");
            $this->assertSame(1, substr_count($text, '?'));
        }
    }

    public function test_duplicate_seaman_question_stays_maritime_and_keeps_its_type(): void
    {
        [$session] = $this->makeSession(['Technical']);
        $session->target_position = 'Seaman';
        $answer = 'I inspected the lifeboat equipment and reported expired flares to the captain.';
        $original = $this->invoke('fallbackQuestionForSelectedType', $session, 'Technical', $answer, false);

        $alternative = $this->invoke('uniqueQuestionTextForSession', $session, $original, [$original], 'Technical', $answer);

        $this->assertNotSame($original, $alternative);
        $this->assertMatchesRegularExpression('/\b(?:vessel|deck|crew|lifeboat)\b/i', $alternative);
        $this->assertTrue($this->invoke('questionMatchesSelectedType', $alternative, 'Technical'));

        $exhausted = $this->invoke('uniqueQuestionTextForSession', $session, $original, [
            $original,
            $alternative,
            "What procedure would you use to check deck equipment before a vessel departs?",
        ], 'Technical', $answer);
        $this->assertMatchesRegularExpression('/\b(?:vessel|deck|crew|lifeboat)\b/i', $exhausted);
        $this->assertTrue($this->invoke('questionMatchesSelectedType', $exhausted, 'Technical'));
    }

    public function test_question_intent_wins_over_incidental_type_words(): void
    {
        $personal = 'What technical strength would you bring to the Developer role?';
        $behavioral = 'Can you tell me about a time you debugged a production API issue?';
        $technical = 'If the production API timed out, how would you diagnose it?';

        $this->assertTrue($this->invoke('questionMatchesSelectedType', $personal, 'Personal'));
        $this->assertFalse($this->invoke('questionMatchesSelectedType', $personal, 'Technical'));
        $this->assertTrue($this->invoke('questionMatchesSelectedType', $behavioral, 'Behavioral'));
        $this->assertFalse($this->invoke('questionMatchesSelectedType', $behavioral, 'Technical'));
        $this->assertTrue($this->invoke('questionMatchesSelectedType', $technical, 'Technical'));
        $this->assertFalse($this->invoke('questionMatchesSelectedType', $technical, 'Situational'));
    }

    public function test_chat_reply_saves_each_single_selected_type_across_response_modes(): void
    {
        $this->fakeProviderTypeQuestions();
        Storage::fake('local');
        config([
            'services.ai_transcription.provider_priority' => '',
            'services.local_speech.enabled' => false,
        ]);

        foreach ([
            ['Behavioral', 'text'],
            ['Situational', 'voice'],
            ['Technical', 'hybrid'],
            ['Personal', 'text'],
        ] as [$type, $mode]) {
            [$session, $category, $user] = $this->makeSession([$type], $mode);
            $opening = $this->question($session, $category, 'Please introduce yourself.', 'Personal', 'real_interview_opening');
            foreach ([1, 2, 3, 4] as $index) {
                $this->assertSame($type, $this->invoke('selectedQuestionTypeForIndex', $session, $index));
            }

            $payload = [
                'session_id' => $session->id,
                'question_id' => $opening->id,
                'response_mode' => $mode,
                'answer_text' => 'I redesigned the approval queue, resolved duplicate invoices, and improved the handoff for our finance team.',
            ];
            if ($mode === 'voice') {
                $payload['voice_audio'] = UploadedFile::fake()
                    ->createWithContent('answer.webm', str_repeat('sample-audio-', 200))
                    ->mimeType('audio/webm');
            }

            $request = $this->actingAs($user)->withSession(['active_interview_id' => $session->id]);
            $response = $mode === 'voice'
                ? $request->post(route('interview.chatReply'), $payload)
                : $request->postJson(route('interview.chatReply'), $payload);
            $response->assertOk()->assertJsonPath('success', true);

            $next = Question::findOrFail($response->json('next_question_id'));
            $this->assertSame($type, $next->type);
            $this->assertTrue($this->invoke('questionMatchesSelectedType', $next->question_text, $type), "Wrong wording for {$type}: {$next->question_text}");
        }
    }

    public function test_mixed_session_follows_selected_type_order_after_each_answer(): void
    {
        $this->fakeProviderTypeQuestions();
        $types = ['Behavioral', 'Situational', 'Technical', 'Personal'];
        [$session, $category, $user] = $this->makeSession($types);
        $currentQuestion = $this->question($session, $category, 'Please introduce yourself.', 'Personal', 'real_interview_opening');

        foreach ($types as $expectedType) {
            $response = $this->actingAs($user)
                ->withSession(['active_interview_id' => $session->id])
                ->postJson(route('interview.chatReply'), [
                    'session_id' => $session->id,
                    'question_id' => $currentQuestion->id,
                    'response_mode' => 'text',
                    'answer_text' => 'I redesigned the approval queue, resolved duplicate invoices, and improved the handoff for our finance team.',
                ])
                ->assertOk()
                ->assertJsonPath('success', true);

            $currentQuestion = Question::findOrFail($response->json('next_question_id'));
            $this->assertSame($expectedType, $currentQuestion->type);
            $this->assertTrue($this->invoke('questionMatchesSelectedType', $currentQuestion->question_text, $expectedType));
        }

        $this->assertSame($types, Question::where('interview_session_id', $session->id)
            ->where('source_type', '!=', 'real_interview_opening')
            ->orderBy('id')
            ->pluck('type')
            ->all());
    }

    private function invoke(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(InterviewController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(app(InterviewController::class), ...$args);
    }

    private function fakeProviderTypeQuestions(): void
    {
        Setting::setVal('int_follow_up', true, 'interview', 'boolean');
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        $questions = [
            'Behavioral' => 'Can you describe a time you improved an approval queue as a Developer?',
            'Situational' => 'If the approval queue stalled in the Developer role, what would you do first?',
            'Technical' => 'What tools would you use to diagnose a slow approval queue for the Developer role?',
            'Personal' => 'Which strength helps you improve approval workflows in the Developer role?',
        ];
        Http::fake(['api.openai.com/*' => function ($request) use ($questions) {
            $prompt = (string) data_get($request->data(), 'messages.1.content', '');
            preg_match('/The NEXT question must be (Behavioral|Situational|Technical|Personal)/', $prompt, $match);

            return Http::response(['choices' => [['finish_reason' => 'stop', 'message' => [
                'content' => $questions[$match[1] ?? 'Behavioral'],
            ]]]]);
        }]);
    }

    private function makeSession(array $types, string $mode = 'text'): array
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = Category::create([
            'title' => 'Job Interview',
            'description' => 'Job interview practice',
            'status' => 'active',
            'type' => 'core',
        ]);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 4,
            'question_types' => json_encode($types),
            'coach_focus_mode' => 'balanced',
            'response_mode' => $mode,
            'status' => 'in_progress',
        ]);

        return [$session, $category, $user];
    }

    private function question(InterviewSession $session, Category $category, string $text, string $type, string $sourceType): Question
    {
        return Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => $text,
            'difficulty' => 'medium',
            'type' => $type,
            'source_type' => $sourceType,
            'status' => 'active',
        ]);
    }
}
