<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Score;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedbackModelTrainingPipelineTest extends TestCase
{
 use RefreshDatabase;

 private array $originalEnvValues = [];

 protected function tearDown(): void
 {
 foreach ($this->originalEnvValues as $key => $value) {
 if ($value === false) {
 putenv($key);
 unset($_ENV[$key], $_SERVER[$key]);
 } else {
 putenv("{$key}={$value}");
 $_ENV[$key] = $value;
 $_SERVER[$key] = $value;
 }
 }

 parent::tearDown();
 }

 public function test_reviewed_answers_export_as_feedback_training_jsonl(): void
 {
 $category = Category::create(['title' => 'Job Interview']);
 $question = Question::create([
 'category_id' => $category->id,
 'question_text' => 'Tell me about a time you solved a customer issue.',
 'difficulty' => 'Medium',
 'type' => 'Behavioral',
 'expected_guide' => 'Use STAR and explain the final result.',
 'mapped_skills' => ['Customer Service', 'STAR Method'],
 ]);
 $session = InterviewSession::create([
 'user_id' => User::factory()->create()->id,
 'category_id' => $category->id,
 'difficulty' => 'Medium',
 'target_position' => 'Customer Service Representative',
 'status' => 'completed',
 ]);
 Score::create([
 'interview_session_id' => $session->id,
 'clarity_score' => 82,
 'relevance_score' => 86,
 'grammar_score' => 80,
 'professionalism_score' => 88,
 'overall_readiness_score' => 84,
 'star_method_score' => 75,
 ]);
 InterviewAnswer::create([
 'interview_session_id' => $session->id,
 'question_id' => $question->id,
 'answer_text' => 'I listened to the customer, checked the account, explained the delay, and resolved the issue before the end of the call.',
 'response_mode' => 'text',
 'ai_feedback' => 'The answer gave a clear action and result.',
 'score' => 84,
 'clarity_score' => 82,
 'relevance_score' => 86,
 'grammar_score' => 80,
 'audit_status' => 'approved',
 'star_analysis' => ['situation' => 'clear', 'task' => 'clear', 'action' => 'clear', 'result' => 'clear'],
 ]);

 $path = 'normalized/training/phpunit_feedback_train.jsonl';
 Storage::disk('datasets')->delete($path);
 Storage::disk('datasets')->delete('normalized/training/phpunit_feedback_train_manifest.json');

 $this->artisan('ai:export-feedback-training', ['--output' => $path])
 ->expectsOutput('Exported 1 feedback training rows to datasets:'.$path)
 ->assertSuccessful();

 $row = json_decode(trim(Storage::disk('datasets')->get($path)), true);

 $this->assertSame('Tell me about a time you solved a customer issue.', data_get($row, 'input.question'));
 $this->assertSame('Customer Service Representative', data_get($row, 'input.target_position'));
 $this->assertSame(84, data_get($row, 'output.score'));
 $this->assertSame(88, data_get($row, 'output.professionalism_score'));
 $this->assertSame(100, data_get($row, 'output.star_method_score'));
 }

 public function test_trained_local_model_is_not_used_for_ai_only_feedback_provider(): void
 {
 foreach ([
 'AI_FEEDBACK_PROVIDER_PRIORITY' => 'localmodel,openai',
 'OPENAI_API_KEY' => '',
 'GEMINI_API_KEY' => '',
 'GROQ_API_KEY' => '',
 'COHERE_API_KEY' => '',
 ] as $key => $value) {
 $this->setEnvValue($key, $value);
 }

 $modelPath = storage_path('framework/testing/local-feedback-model.json');
 if (! is_dir(dirname($modelPath))) {
 mkdir(dirname($modelPath), 0775, true);
 }
 file_put_contents($modelPath, json_encode([
 'schema_version' => 1,
 'models' => ['score' => ['weights' => ['bias' => 0.8], 'fallback' => 80]],
 ]));

 config([
 'services.local_feedback_model.enabled' => true,
 'services.local_feedback_model.model_path' => $modelPath,
 'services.local_feedback_model.predict_script' => 'scripts/predict_feedback.py',
 ]);

 $method = new \ReflectionMethod(AIService::class, 'feedbackProviderPriority');
 $method->setAccessible(true);

 $providers = $method->invoke(null, 'openai');

 $this->assertSame('openai', $providers[0]?? null);
 $this->assertNotContains('localmodel', $providers);
 }

 public function test_auto_train_command_exports_and_detects_pending_training(): void
 {
 $category = Category::create(['title' => 'Job Interview']);
 $question = Question::create([
 'category_id' => $category->id,
 'question_text' => 'Describe a time you improved a team process.',
 'difficulty' => 'Medium',
 'type' => 'Behavioral',
 'expected_guide' => 'Explain your action and the final result.',
 'mapped_skills' => ['Process Improvement'],
 ]);
 $session = InterviewSession::create([
 'user_id' => User::factory()->create()->id,
 'category_id' => $category->id,
 'difficulty' => 'Medium',
 'target_position' => 'Team Lead',
 'status' => 'completed',
 ]);
 Score::create([
 'interview_session_id' => $session->id,
 'clarity_score' => 78,
 'relevance_score' => 80,
 'grammar_score' => 82,
 'professionalism_score' => 84,
 'overall_readiness_score' => 80,
 'star_method_score' => 75,
 ]);
 InterviewAnswer::create([
 'interview_session_id' => $session->id,
 'question_id' => $question->id,
 'answer_text' => 'I reviewed the handoff notes, found repeated missing details, made a checklist, and the team finished reviews faster.',
 'response_mode' => 'text',
 'ai_feedback' => 'The answer showed action and a result.',
 'score' => 80,
 'clarity_score' => 78,
 'relevance_score' => 80,
 'grammar_score' => 82,
 'audit_status' => 'approved',
 ]);

 $path = 'normalized/training/phpunit_auto_feedback_train.jsonl';
 Storage::disk('datasets')->delete($path);
 Storage::disk('datasets')->delete('normalized/training/phpunit_auto_feedback_train_manifest.json');

 $this->artisan('ai:auto-train-feedback-model', [
 '--dataset' => $path,
 '--output' => storage_path('framework/testing/phpunit-auto-feedback-model.json'),
 '--min-examples' => 1,
 '--dry-run' => true,
 '--force' => true,
 ])
 ->expectsOutput('Dry run complete: training would run now.')
 ->assertSuccessful();

 $this->assertTrue(Storage::disk('datasets')->exists($path));
 }

 public function test_train_command_writes_stronger_v2_model_artifact(): void
 {
 $path = 'normalized/training/phpunit_stronger_feedback_train.jsonl';
 Storage::disk('datasets')->put($path, json_encode([
 'schema_version' => 1,
 'source' => 'interview_answers',
 'input' => [
 'question' => 'Tell me about a time you solved a customer issue.',
 'question_type' => 'Behavioral',
 'expected_guide' => 'Use STAR and explain the final result.',
 'mapped_skills' => ['Customer Service', 'STAR Method'],
 'category' => 'Job Interview',
 'target_position' => 'Customer Service Representative',
 'difficulty' => 'Medium',
 'answer' => 'I listened to the customer, checked the account, explained the delay, and resolved the issue before the end of the call.',
 'response_mode' => 'text',
 'voice_duration' => 0,
 'wpm' => 0,
 'filler_words_count' => 0,
 'pause_count' => 0,
 ],
 'output' => [
 'score' => 84,
 'clarity_score' => 82,
 'relevance_score' => 86,
 'grammar_score' => 80,
 'professionalism_score' => 88,
 'star_method_score' => 100,
 ],
 ], JSON_UNESCAPED_SLASHES)."\n");

 $modelPath = storage_path('framework/testing/phpunit-stronger-feedback-model.json');
 if (is_file($modelPath)) {
 unlink($modelPath);
 }

 $this->artisan('ai:train-feedback-model', [
 '--dataset' => $path,
 '--output' => $modelPath,
 '--min-examples' => 1,
 '--epochs' => 5,
 '--force' => true,
 ])->assertSuccessful();

 $model = json_decode((string) file_get_contents($modelPath), true);

 $this->assertSame(2, $model['schema_version']);
 $this->assertSame('hashed_linear_feedback_scorer_v2', $model['model_type']);
 $this->assertSame('scaled_bagged_elastic_net_sgd', data_get($model, 'models.score.algorithm'));
 $this->assertSame('sparse_rms_no_centering', data_get($model, 'feature_pipeline.scaling'));
 $this->assertGreaterThan(0, data_get($model, 'models.score.feature_count'));
 $this->assertNotEmpty(data_get($model, 'models.score.members'));
 }

 private function setEnvValue(string $key, string $value): void
 {
 if (! array_key_exists($key, $this->originalEnvValues)) {
 $this->originalEnvValues[$key] = getenv($key);
 }

 putenv("{$key}={$value}");
 $_ENV[$key] = $value;
 $_SERVER[$key] = $value;
 }
}
