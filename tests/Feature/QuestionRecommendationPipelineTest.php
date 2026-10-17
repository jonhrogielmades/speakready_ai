<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use App\Services\QuestionRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionRecommendationPipelineTest extends TestCase
{
 use RefreshDatabase;

 public function test_hr_question_pipeline_builds_index_and_recommends_matching_question(): void
 {
 Storage::fake('datasets');

 $input = 'normalized/questions/phpunit/speakready_reliable_questions.jsonl';
 $output = 'embeddings/questions/phpunit/question_embeddings.json';
 Storage::disk('datasets')->put($input, implode("\n", [
 json_encode([
 'id' => 'q1',
 'dataset_key' => 'ph_job_interview',
 'category' => 'Job Interview',
 'country' => 'General',
 'question_text' => 'Tell me about a time you resolved a conflict between employees.',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'expected_guide' => 'Use STAR and explain the conflict, action, communication, and outcome.',
 'mapped_skills' => ['Conflict Resolution', 'Employee Relations', 'Communication'],
 'archive_roles' => ['HR Specialist'],
 'source_keys' => ['phpunit_hr_dataset'],
 ], JSON_UNESCAPED_SLASHES),
 json_encode([
 'id' => 'q2',
 'dataset_key' => 'ph_job_interview',
 'category' => 'Job Interview',
 'country' => 'General',
 'question_text' => 'What is your greatest personal strength?',
 'type' => 'Personal',
 'difficulty' => 'Easy',
 'expected_guide' => 'Name one strength and connect it to the target role.',
 'mapped_skills' => ['Self Awareness'],
 'archive_roles' => ['Marketing Associate'],
 'source_keys' => ['phpunit_hr_dataset'],
 ], JSON_UNESCAPED_SLASHES),
 ])."\n");

 $this->artisan('ai:build-question-embedding-index', [
 '--input' => $input,
 '--output' => $output,
 '--backend' => 'lexical_hash',
 ])->assertSuccessful();

 $category = Category::create([
 'title' => 'Job Interview',
 'type' => 'core',
 'status' => 'active',
 ]);
 $session = InterviewSession::create([
 'user_id' => User::factory()->create()->id,
 'category_id' => $category->id,
 'difficulty' => 'medium',
 'target_position' => 'HR Specialist',
 'job_description' => 'Handle employee relations, workplace conflict, policy communication, and onboarding.',
 'interview_focus' => 'HR interview',
 'status' => 'in_progress',
 ]);

 config([
 'services.question_recommender.enabled' => true,
 'services.question_recommender.index_path' => Storage::disk('datasets')->path($output),
 ]);

 $matches = app(QuestionRecommendationService::class)->recommend(
 $session,
 ['key' => 'ph_job_interview', 'category' => 'Job Interview', 'country' => 'General'],
 ['Behavioral'],
 1
 );

 $this->assertCount(1, $matches);
 $this->assertSame('Tell me about a time you resolved a conflict between employees.', $matches[0]['question_text']);
 $this->assertSame('Behavioral', $matches[0]['type']);
 $this->assertSame('speakready_reliable_question_bank', $matches[0]['source_type']);
 $this->assertSame('lexical_similarity', $matches[0]['selection_pipeline']);
 $this->assertTrue(app(QuestionRecommendationService::class)->indexQuality()['production_ready']);
 $this->assertFalse($matches[0]['accuracy_guaranteed']);
 $this->assertTrue($matches[0]['decision_support_only']);
 }

 public function test_testing_environment_skips_sentence_bert_recommender_index(): void
 {
 Storage::fake('datasets');
 Log::spy();

 $index = 'embeddings/questions/phpunit/sentence_bert_question_embeddings.json';
 Storage::disk('datasets')->put($index, json_encode([
 'schema_version' => 1,
 'database_type' => 'question_embedding_database',
 'embedding_backend' => 'sentence-bert',
 'embedding_model' => 'sentence-transformers/all-MiniLM-L6-v2',
 'embedding_dimensions' => 384,
 'records' => [[
 'id' => 'q1',
 'dataset_key' => 'ph_job_interview',
 'question_text' => 'Tell me about a time you resolved a conflict.',
 'source_keys' => ['phpunit_hr_dataset'],
 'embedding' => array_merge([1.0], array_fill(0, 383, 0.0)),
 ]],
 ], JSON_UNESCAPED_SLASHES));
 $indexPath = Storage::disk('datasets')->path($index);
 Storage::disk('datasets')->put($index.'.quality.json', json_encode([
 'schema_version' => 1,
 'index_schema_version' => 1,
 'database_type' => 'question_embedding_database',
 'embedding_backend' => 'sentence-bert',
 'embedding_model' => 'sentence-transformers/all-MiniLM-L6-v2',
 'embedding_dimensions' => 384,
 'record_count' => 1,
 'valid_record_percent' => 100,
 'source_backed_percent' => 100,
 'unique_question_percent' => 100,
 'index_size_bytes' => filesize($indexPath),
 'index_sha256' => hash_file('sha256', $indexPath),
 'production_ready' => true,
 'accuracy_guaranteed' => false,
 ], JSON_UNESCAPED_SLASHES));

 $category = Category::create([
 'title' => 'Job Interview',
 'type' => 'core',
 'status' => 'active',
 ]);
 $session = InterviewSession::create([
 'user_id' => User::factory()->create()->id,
 'category_id' => $category->id,
 'difficulty' => 'medium',
 'target_position' => 'HR Specialist',
 'status' => 'in_progress',
 ]);

 config([
 'services.question_recommender.enabled' => true,
 'services.question_recommender.index_path' => Storage::disk('datasets')->path($index),
 ]);

 $matches = app(QuestionRecommendationService::class)->recommend(
 $session,
 ['key' => 'ph_job_interview', 'category' => 'Job Interview', 'country' => 'General'],
 ['Behavioral'],
 1
 );

 $this->assertSame([], $matches);
 Log::shouldHaveReceived('debug')
 ->once()
 ->with('Skipping Sentence-BERT question recommender during tests. Use a lexical_hash test index to exercise recommendations.');
 }

 public function test_question_embedding_index_fails_closed_after_artifact_tampering(): void
 {
 Storage::fake('datasets');

 $input = 'normalized/questions/phpunit/tamper_source.jsonl';
 $output = 'embeddings/questions/phpunit/tamper_index.json';
 Storage::disk('datasets')->put($input, json_encode([
 'id' => 'tamper-q1',
 'dataset_key' => 'ph_job_interview',
 'category' => 'Job Interview',
 'country' => 'General',
 'question_text' => 'Describe a time you improved an onboarding process.',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'expected_guide' => 'Use verified evidence and explain the result.',
 'mapped_skills' => ['Onboarding', 'Process Improvement'],
 'source_keys' => ['phpunit_hr_dataset'],
 ], JSON_UNESCAPED_SLASHES)."\n");

 $this->artisan('ai:build-question-embedding-index', [
 '--input' => $input,
 '--output' => $output,
 '--backend' => 'lexical_hash',
 ])->assertSuccessful();

 $indexPath = Storage::disk('datasets')->path($output);
 $service = app(QuestionRecommendationService::class);
 $this->assertTrue($service->indexQuality($indexPath)['production_ready']);

 file_put_contents($indexPath, " ", FILE_APPEND);

 $quality = $service->indexQuality($indexPath);
 $this->assertFalse($quality['production_ready']);
 $this->assertContains('embedding_index_hash_mismatch', $quality['reasons']);
 $this->assertContains('embedding_index_size_mismatch', $quality['reasons']);
 }

 public function test_question_embedding_build_command_rejects_an_index_below_the_quality_gate(): void
 {
 Storage::fake('datasets');

 $input = 'normalized/questions/phpunit/unverified_source.jsonl';
 $output = 'embeddings/questions/phpunit/unverified_index.json';
 Storage::disk('datasets')->put($input, json_encode([
 'id' => 'unverified-q1',
 'dataset_key' => 'ph_job_interview',
 'question_text' => 'What makes this interview question reliable?',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'source_keys' => [],
 ], JSON_UNESCAPED_SLASHES)."\n");

 $this->artisan('ai:build-question-embedding-index', [
 '--input' => $input,
 '--output' => $output,
 '--backend' => 'lexical_hash',
 ])->assertFailed();

 $quality = app(QuestionRecommendationService::class)
 ->indexQuality(Storage::disk('datasets')->path($output));
 $this->assertFalse($quality['production_ready']);
 $this->assertContains('embedding_index_quality_gate_failed', $quality['reasons']);
 }

 public function test_mock_interview_setup_defers_source_backed_questions_until_after_opening(): void
 {
 Storage::fake('datasets');

 $input = 'normalized/questions/phpunit/speakready_reliable_questions.jsonl';
 $output = 'embeddings/questions/phpunit/question_embeddings.json';
 Storage::disk('datasets')->put($input, json_encode([
 'id' => 'q1',
 'dataset_key' => 'ph_job_interview',
 'category' => 'Job Interview',
 'country' => 'General',
 'question_text' => 'Tell me about a time you resolved a conflict between employees.',
 'type' => 'Behavioral',
 'difficulty' => 'Medium',
 'expected_guide' => 'Use STAR and explain the conflict, action, communication, and outcome.',
 'mapped_skills' => ['Conflict Resolution', 'Employee Relations', 'Communication'],
 'archive_roles' => ['HR Specialist'],
 'source_keys' => ['phpunit_hr_dataset'],
 ], JSON_UNESCAPED_SLASHES)."\n");

 $this->artisan('ai:build-question-embedding-index', [
 '--input' => $input,
 '--output' => $output,
 '--backend' => 'lexical_hash',
 ])->assertSuccessful();

 config([
 'services.question_recommender.enabled' => true,
 'services.question_recommender.index_path' => Storage::disk('datasets')->path($output),
 ]);

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = Category::create([
 'title' => 'Job Interview',
 'type' => 'core',
 'description' => 'Job interview practice.',
 'status' => 'active',
 ]);

 $this->actingAs($user)
 ->post(route('interview.start'), [
 'category_id' => $category->id,
 'difficulty' => 'medium',
 'target_position' => 'HR Specialist',
 'job_description' => 'Handle employee relations, workplace conflict, policy communication, and onboarding.',
 'num_questions' => 3,
 'response_mode' => 'text',
 'question_types' => ['Behavioral'],
 'time_limit' => 0,
 ])
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();
 $questions = Question::where('interview_session_id', $session->id)
 ->orderBy('id')
 ->get();

 $this->assertCount(1, $questions);
 $this->assertSame('real_interview_opening', $questions->first()->source_type);
 $this->assertSame('Personal', $questions->first()->type);
 }
}
