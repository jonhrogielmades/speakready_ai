<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\Category;
use App\Models\GameAnswer;
use App\Models\GameLevel;
use App\Models\GameProgress;
use App\Models\GameSession;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserSideHardeningTest extends TestCase
{
 use RefreshDatabase;

 public function test_user_profile_relation_powers_learning_state(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $profile = Profile::create([
 'user_id' => $user->id,
 'energy' => 2,
 'player_level' => 4,
 ]);
 $category = $this->category(['type' => 'game']);
 $this->gameLevel($category);

 $this->assertTrue($profile->is($user->fresh()->profile));

 $this->actingAs($user)
 ->get(route('user.learning', ['category_id' => $category->id]))
 ->assertOk();
 }

 public function test_learning_page_repairs_game_tables_and_hides_unavailable_challenge_levels(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $inactiveCategory = $this->category([
 'title' => 'Inactive Interview Games',
 'type' => 'game',
 'status' => 'inactive',
 ]);
 $coreCategory = $this->category([
 'title' => 'Core Interview Questions',
 'type' => 'core',
 ]);
 $this->gameLevel($inactiveCategory, ['title' => 'Unavailable Inactive Challenge']);
 $this->gameLevel($coreCategory, ['title' => 'Unavailable Core Challenge']);

 Schema::dropIfExists('game_answers');
 Schema::dropIfExists('game_sessions');
 Schema::dropIfExists('game_certificates');
 Schema::dropIfExists('game_progress');

 $this->actingAs($user)
 ->get(route('user.learning'))
 ->assertOk()
 ->assertSee('No challenge levels loaded yet.')
 ->assertDontSee('Unavailable Inactive Challenge')
 ->assertDontSee('Unavailable Core Challenge');

 foreach (['game_progress', 'game_sessions', 'game_answers', 'game_certificates'] as $table) {
 $this->assertTrue(Schema::hasTable($table), "Expected {$table} to be repaired.");
 }

 foreach ([
 'voice_recording_disk',
 'voice_recording_path',
 'voice_recording_mime_type',
 'voice_recording_byte_size',
 'voice_recording_original_name',
 'voice_recording_transcription_status',
 'voice_recording_uploaded_at',
 ] as $column) {
 $this->assertTrue(Schema::hasColumn('game_answers', $column), "Expected game_answers.{$column} to be repaired.");
 }
 }

 public function test_mobile_learning_page_includes_mobile_challenge_controls(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create([
 'user_id' => $user->id,
 'energy' => Profile::MAX_ENERGY,
 'player_level' => 1,
 ]);
 $category = $this->category(['type' => 'game', 'title' => 'Mobile Challenge Path']);
 $this->gameLevel($category, [
 'title' => 'Mobile Opening Challenge',
 'skill_focus' => 'Clarity',
 'learning_objective' => 'Give a concise opening answer.',
 ]);

 $this->actingAs($user)
 ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
 ->get(route('user.learning', ['category_id' => $category->id]))
 ->assertOk()
 ->assertSee('class="user-mobile-shell mobile-shell"', false)
 ->assertSee('css/mobile/user/learning.css?v=1', false)
 ->assertDontSee('id="learningSearchInput"', false)
 ->assertDontSee('Search challenges, skills, scenarios...', false)
 ->assertSee('data-search-text=', false)
 ->assertSee('learning-badge-row-active', false)
 ->assertSee('start-challenge-form', false)
 ->assertSee('Starting...', false)
 ->assertSee('autoStart: false', false)
 ->assertDontSee('mb-20px', false);
 }

 public function test_learning_position_modal_filters_challenges_by_user_position(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category(['type' => 'game', 'title' => 'Role Challenge Path']);
 $this->gameLevel($category, [
 'level_number' => 1,
 'title' => 'Data Analyst Screening',
 'target_position' => 'Data Analyst',
 ]);
 $this->gameLevel($category, [
 'level_number' => 2,
 'title' => 'Software Developer Screening',
 'target_position' => 'Software Developer',
 ]);

 $this->actingAs($user)
 ->get(route('user.learning', ['category_id' => $category->id]))
 ->assertOk()
 ->assertSee('id="challengePositionModal"', false)
 ->assertSee('data-show-on-load="true"', false)
 ->assertSee('What position are you applying for?');

 $response = $this->actingAs($user)
 ->post(route('user.learning.position'), [
 'category_id' => $category->id,
 'target_position' => 'Data Analyst',
 ]);

 $generatedCategory = Category::where('title', 'Interview Challenges - Data Analyst')
 ->where('type', 'game')
 ->firstOrFail();

 $response
 ->assertRedirect(route('user.learning', ['category_id' => $generatedCategory->id]))
 ->assertSessionHas('learning_challenge_position', 'Data Analyst')
 ->assertSessionHas('learning_challenge_category_id', $generatedCategory->id);

 $this->assertDatabaseHas('users', [
 'id' => $user->id,
 'target_position' => 'Data Analyst',
 ]);

 $generatedLevelNumbers = GameLevel::where('category_id', $generatedCategory->id)
 ->where('target_position', 'Data Analyst')
 ->orderBy('level_number')
 ->pluck('level_number')
 ->all();
 $this->assertSame(range(1, 5), $generatedLevelNumbers);

 $this->actingAs($user)
 ->withSession(['learning_challenge_position' => 'Data Analyst'])
 ->get(route('user.learning', ['category_id' => $generatedCategory->id]))
 ->assertOk()
 ->assertSee('Target Position')
 ->assertSee('Data Analyst')
 ->assertSee('Data Analyst Interview Level 5')
 ->assertDontSee('Software Developer Screening');
 }

 public function test_challenge_journey_is_capped_at_level_five(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category(['type' => 'game', 'title' => 'Five Level Challenge Path']);
 $levels = collect(range(1, 6))->map(fn (int $levelNumber): GameLevel => $this->gameLevel($category, [
 'level_number' => $levelNumber,
 'title' => "Journey Level {$levelNumber}",
 'target_position' => 'Developer',
 'required_score' => 80,
 ]));

 $this->actingAs($user)
 ->withSession(['learning_challenge_position' => 'Developer'])
 ->get(route('user.learning', ['category_id' => $category->id]))
 ->assertOk()
 ->assertSee('Journey Level 5')
 ->assertDontSee('Journey Level 6');

 $this->actingAs($user)
 ->withSession(['learning_challenge_position' => 'Developer'])
 ->post(route('user.game.start', $levels->last()))
 ->assertSessionHas('error', 'Only Levels 1-5 are available in the Challenge Journey.');

 $levels->take(5)->each(function (GameLevel $level) use ($user): void {
 GameProgress::create([
 'user_id' => $user->id,
 'game_level_id' => $level->id,
 'status' => 'completed',
 'best_score' => 85,
 ]);
 });

 $this->actingAs($user)
 ->withSession(['learning_challenge_position' => 'Developer'])
 ->get(route('user.game.certificate.download', $category))
 ->assertOk();

 $this->assertDatabaseHas('game_certificates', [
 'user_id' => $user->id,
 'category_id' => $category->id,
 'final_game_level_id' => $levels->get(4)->id,
 ]);
 }

 public function test_learning_challenge_generation_normalizes_array_ai_fields(): void
 {
 $service = new \App\Services\LearningChallengeGenerationService();
 $method = new \ReflectionMethod($service, 'normalizeGeneratedGameData');
 $method->setAccessible(true);

 $normalized = $method->invoke($service, [
 'title' => 'AI Data Analyst Screening',
 'mission_text' => [
 ['question' => 'Why are you interested in a Data Analyst role?'],
 ['question' => 'Tell me about a project where you cleaned or interpreted data.'],
 ],
 'success_criteria' => [
 ['criterion' => 'Answer directly.'],
 ['criterion' => 'Use one data-related example.'],
 ],
 'retry_hint' => ['text' => 'Use one concrete project and explain the result.'],
 ], 'Data Analyst', 'beginner', 1);

 $this->assertSame('Data Analyst', $normalized['target_position']);
 $this->assertStringContainsString('1. Why are you interested in a Data Analyst role?', $normalized['mission_text']);
 $this->assertStringContainsString('2. Tell me about a project where you cleaned or interpreted data.', $normalized['mission_text']);
 $this->assertStringContainsString('1. Answer directly.', $normalized['success_criteria']);
 $this->assertStringContainsString('2. Use one data-related example.', $normalized['success_criteria']);
 $this->assertSame('Use one concrete project and explain the result.', $normalized['retry_hint']);
 }

 public function test_game_start_uses_selected_position_path_for_previous_level_lock(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category(['type' => 'game', 'title' => 'Mixed Role Challenge Path']);
 $firstDataLevel = $this->gameLevel($category, [
 'level_number' => 1,
 'title' => 'Data Analyst Opening',
 'target_position' => 'Data Analyst',
 ]);
 $this->gameLevel($category, [
 'level_number' => 2,
 'title' => 'Software Developer Bridge',
 'target_position' => 'Software Developer',
 ]);
 $nextDataLevel = $this->gameLevel($category, [
 'level_number' => 3,
 'title' => 'Data Analyst Evidence Round',
 'target_position' => 'Data Analyst',
 ]);
 GameProgress::create([
 'user_id' => $user->id,
 'game_level_id' => $firstDataLevel->id,
 'status' => 'completed',
 'best_score' => 85,
 ]);

 $this->actingAs($user)
 ->withSession(['learning_challenge_position' => 'Data Analyst'])
 ->post(route('user.game.start', $nextDataLevel))
 ->assertRedirect(route('user.game.match'));

 $this->assertDatabaseHas('game_sessions', [
 'user_id' => $user->id,
 'game_level_id' => $nextDataLevel->id,
 'status' => 'in_progress',
 ]);
 }

 public function test_user_can_choose_language_from_profile_menu(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

 $this->actingAs($user)
 ->get(route('dashboard'))
 ->assertOk()
 ->assertSee('id="profileLanguageSelect"', false)
 ->assertSee('<option value="tl"', false)
 ->assertSee('<option value="ceb"', false);

 $this->actingAs($user)
 ->post(route('user.language.update'), [
 'preferred_language' => 'ceb',
 ])
 ->assertRedirect();

 $this->assertSame('ceb', $user->fresh()->preferred_language);

 $this->actingAs($user)
 ->get(route('dashboard'))
 ->assertOk()
 ->assertSee('lang="ceb"', false)
 ->assertSee('data-speech-locale="ceb-PH"', false);
 }

 public function test_english_translation_endpoint_returns_identity_without_ai_provider(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active', 'preferred_language' => 'en']);

 $this->actingAs($user)
 ->postJson(route('user.language.translate'), [
 'texts' => ['Account Management', 'Notifications'],
 ])
 ->assertOk()
 ->assertJsonPath('language', 'en')
 ->assertJsonPath('translations.Account Management', 'Account Management')
 ->assertJsonPath('translations.Notifications', 'Notifications');
 }

 public function test_language_update_falls_back_to_session_when_column_is_missing(): void
 {
 Schema::table('users', function ($table) {
 $table->dropColumn('preferred_language');
 });

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

 $this->actingAs($user)
 ->post(route('user.language.update'), [
 'preferred_language' => 'fil',
 ])
 ->assertRedirect()
 ->assertSessionHas('preferred_language', 'fil');

 $this->actingAs($user)
 ->get(route('dashboard'))
 ->assertOk()
 ->assertSee('lang="fil"', false)
 ->assertSee('data-speech-locale="fil-PH"', false);
 }

 public function test_perk_unlock_uses_server_catalog_cost_and_type(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create([
 'user_id' => $user->id,
 'leadership_xp' => 500,
 'technical_xp' => 999,
 ]);

 $this->actingAs($user)
 ->postJson(route('user.skills.unlock'), [
 'perk_id' => 'energy_efficiency',
 'perk_type' => 'technical',
 'cost' => 0,
 ])
 ->assertOk()
 ->assertJson(['success' => true]);

 $profile = Profile::where('user_id', $user->id)->first();

 $this->assertSame(0, $profile->leadership_xp);
 $this->assertSame(999, $profile->technical_xp);
 $this->assertTrue($profile->hasPerk('energy_efficiency'));

 $this->actingAs($user)
 ->postJson(route('user.skills.unlock'), [
 'perk_id' => 'energy_efficiency',
 ])
 ->assertStatus(400)
 ->assertJsonPath('message', 'Perk already unlocked.');

 $this->assertSame(0, Profile::where('user_id', $user->id)->first()->leadership_xp);
 }

 public function test_perk_unlock_rejects_unknown_perks(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'leadership_xp' => 9999]);

 $this->actingAs($user)
 ->postJson(route('user.skills.unlock'), ['perk_id' => 'free_everything'])
 ->assertUnprocessable();

 $this->assertFalse(Profile::where('user_id', $user->id)->first()->hasPerk('free_everything'));
 }

 public function test_perk_unlock_rejects_when_skill_xp_is_insufficient(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'leadership_xp' => 499]);

 $this->actingAs($user)
 ->postJson(route('user.skills.unlock'), ['perk_id' => 'energy_efficiency'])
 ->assertStatus(400)
 ->assertJsonPath('message', 'Not enough Skill XP.');

 $profile = Profile::where('user_id', $user->id)->first();
 $this->assertSame(499, $profile->leadership_xp);
 $this->assertFalse($profile->hasPerk('energy_efficiency'));
 }

 public function test_interview_start_requires_active_core_category(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $gameCategory = $this->category(['type' => 'game']);
 $inactiveCategory = $this->category(['title' => 'Inactive Core', 'status' => 'inactive']);

 foreach ([$gameCategory, $inactiveCategory] as $category) {
 $this->actingAs($user)
 ->from(route('interview.setup'))
 ->post(route('interview.start'), $this->interviewPayload($category))
 ->assertRedirect(route('interview.setup'))
 ->assertSessionHasErrors('category_id');
 }

 $this->assertDatabaseCount('interview_sessions', 0);
 }

 public function test_interview_start_rejects_school_program_target_for_job_interview_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'Job Interview']);

 $this->actingAs($user)
 ->from(route('interview.setup'))
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Information Technology',
 ]))
 ->assertRedirect(route('interview.setup'))
 ->assertSessionHasErrors([
 'target_position' => 'This looks like a school-related target program. Recommendation: proceed with School Admission Interviews. Job Interview accepts job-related target positions only.',
 ]);

 $this->assertDatabaseCount('interview_sessions', 0);
 }

 public function test_interview_start_rejects_non_job_target_for_job_interview_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'Job Interview']);

 $this->actingAs($user)
 ->from(route('interview.setup'))
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Better Communication',
 ]))
 ->assertRedirect(route('interview.setup'))
 ->assertSessionHasErrors([
 'target_position' => 'Job Interview accepts job-related target positions only. Enter a job role like Software Developer, Teacher, HR Assistant, or Call Center Agent, or choose School Admission Interviews for school programs like Information Technology.',
 ]);

 $this->assertDatabaseCount('interview_sessions', 0);
 }

 public function test_interview_start_recommends_specific_cleaning_jobs_for_vague_job_target(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'Job Interview']);

 $this->actingAs($user)
 ->from(route('interview.setup'))
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Clean',
 ]))
 ->assertRedirect(route('interview.setup'))
 ->assertSessionHasErrors([
 'target_position' => 'Clean is too broad for a target position. Recommendation: use a specific job target such as Cleaner, Janitor, Housekeeping Attendant, or Janitorial Services, then proceed with Job Interview.',
 ]);

 $this->assertDatabaseCount('interview_sessions', 0);
 }

 public function test_interview_start_accepts_it_job_role_for_job_interview_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'Job Interview']);

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'IT Specialist',
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $this->assertSame('IT Specialist', $session->target_position);
 $this->assertStringContainsString('Job Interview', $session->interview_focus);
 }

 public function test_interview_start_accepts_network_administrator_for_job_interview_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'Job Interview']);

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Network Administrator',
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $this->assertSame('Network Administrator', $session->target_position);
 $this->assertStringContainsString('Job Interview', $session->interview_focus);
 }

 public function test_interview_start_accepts_cleaner_for_job_interview_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'Job Interview']);

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Cleaner',
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $this->assertSame('Cleaner', $session->target_position);
 $this->assertStringContainsString('Job Interview', $session->interview_focus);
 }

 public function test_interview_start_accepts_janitorial_services_for_job_interview_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'Job Interview']);

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Janitorial Services',
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $this->assertSame('Janitorial Services', $session->target_position);
 $this->assertStringContainsString('Job Interview', $session->interview_focus);
 }

 public function test_interview_start_accepts_program_manager_for_job_interview_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'Job Interview']);

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Program Manager',
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $this->assertSame('Program Manager', $session->target_position);
 $this->assertStringContainsString('Job Interview', $session->interview_focus);
 }

 public function test_interview_start_rejects_job_role_target_for_school_admission_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'College Admission']);

 $this->actingAs($user)
 ->from(route('interview.setup'))
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Software Developer',
 ]))
 ->assertRedirect(route('interview.setup'))
 ->assertSessionHasErrors([
 'target_position' => 'This looks like a job-related target position. Recommendation: proceed with Job Interview. School Admission accepts school-related target programs only.',
 ]);

 $this->assertDatabaseCount('interview_sessions', 0);
 }

 public function test_interview_start_recommends_job_interview_for_janitorial_services_under_school_admission(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'College Admission']);

 $this->actingAs($user)
 ->from(route('interview.setup'))
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Janitorial Services',
 ]))
 ->assertRedirect(route('interview.setup'))
 ->assertSessionHasErrors([
 'target_position' => 'This looks like a job-related target position. Recommendation: proceed with Job Interview. School Admission accepts school-related target programs only.',
 ]);

 $this->assertDatabaseCount('interview_sessions', 0);
 }

 public function test_interview_start_recommends_job_interview_for_vague_cleaning_target_under_school_admission(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'College Admission']);

 $this->actingAs($user)
 ->from(route('interview.setup'))
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Clean',
 ]))
 ->assertRedirect(route('interview.setup'))
 ->assertSessionHasErrors([
 'target_position' => 'Clean looks related to cleaning work. Recommendation: proceed with Job Interview using a specific target position such as Cleaner, Janitor, Housekeeping Attendant, or Janitorial Services.',
 ]);

 $this->assertDatabaseCount('interview_sessions', 0);
 }

 public function test_interview_start_rejects_non_school_target_for_school_admission_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'College Admission']);

 $this->actingAs($user)
 ->from(route('interview.setup'))
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Better Communication',
 ]))
 ->assertRedirect(route('interview.setup'))
 ->assertSessionHasErrors('target_position');

 $this->assertDatabaseCount('interview_sessions', 0);
 }

 public function test_interview_start_accepts_school_program_target_for_school_admission_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'College Admission']);

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Information Technology',
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $this->assertSame('Information Technology', $session->target_position);
 $this->assertStringContainsString('School Admission', $session->interview_focus);
 }

 public function test_interview_start_accepts_engineering_program_for_school_admission_scenario(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'College Admission']);

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Software Engineering',
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $this->assertSame('Software Engineering', $session->target_position);
 $this->assertStringContainsString('School Admission', $session->interview_focus);
 }

 public function test_interview_target_suggestions_follow_user_input_and_selected_scenario_with_fallback(): void
 {
 Http::fake();

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $jobCategory = $this->category(['title' => 'Job Interview']);
 $schoolCategory = $this->category(['title' => 'College Admission']);

 $this->actingAs($user)
 ->postJson(route('interview.targetSuggestions'), [
 'category_id' => $jobCategory->id,
 'query' => 'Clean',
 ])
 ->assertOk()
 ->assertJsonPath('scenario_kind', 'job')
 ->assertJsonFragment(['value' => 'Cleaner'])
 ->assertJsonFragment(['value' => 'Janitor'])
 ->assertJsonMissing(['value' => 'BS Information Technology']);

 foreach (['c', 'cl', 'cle', 'clean'] as $query) {
 $this->actingAs($user)
 ->postJson(route('interview.targetSuggestions'), [
 'category_id' => $jobCategory->id,
 'query' => $query,
 ])
 ->assertOk()
 ->assertJsonPath('scenario_kind', 'job')
 ->assertJsonFragment(['value' => 'Cleaner']);
 }

 foreach (['n', 'ne', 'net', 'network', 'network admin', 'network administrator'] as $query) {
 $this->actingAs($user)
 ->postJson(route('interview.targetSuggestions'), [
 'category_id' => $jobCategory->id,
 'query' => $query,
 ])
 ->assertOk()
 ->assertJsonPath('scenario_kind', 'job')
 ->assertJsonFragment(['value' => 'Network Administrator'])
 ->assertJsonMissing(['value' => 'BS Information Technology']);
 }

 $this->actingAs($user)
 ->postJson(route('interview.targetSuggestions'), [
 'category_id' => $schoolCategory->id,
 'query' => 'bsit',
 ])
 ->assertOk()
 ->assertJsonPath('scenario_kind', 'school')
 ->assertJsonFragment(['value' => 'BS Information Technology'])
 ->assertJsonMissing(['value' => 'Cleaner']);

 foreach (['b', 'bs', 'bsi', 'bsit'] as $query) {
 $this->actingAs($user)
 ->postJson(route('interview.targetSuggestions'), [
 'category_id' => $schoolCategory->id,
 'query' => $query,
 ])
 ->assertOk()
 ->assertJsonPath('scenario_kind', 'school')
 ->assertJsonFragment(['value' => 'BS Information Technology']);
 }

 foreach (['d', 'da', 'data', 'bs data'] as $query) {
 $this->actingAs($user)
 ->postJson(route('interview.targetSuggestions'), [
 'category_id' => $schoolCategory->id,
 'query' => $query,
 ])
 ->assertOk()
 ->assertJsonPath('scenario_kind', 'school')
 ->assertJsonFragment(['value' => 'BS Data Science'])
 ->assertJsonMissing(['value' => 'Network Administrator']);
 }
 }

 public function test_interview_target_suggestions_use_ai_provider_results_and_filter_wrong_scenario(): void
 {
 Cache::flush();

 AiProvider::create([
 'name' => 'OpenAI',
 'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
 'api_key' => Crypt::encryptString('test-openai-key'),
 'status' => 'active',
 ]);

 $capturedPrompt = '';
 Http::fake([
 'api.openai.com/*' => function ($request) use (&$capturedPrompt) {
 $capturedPrompt = (string) data_get($request->data(), 'messages.1.content');

 return Http::response([
 'choices' => [[
 'finish_reason' => 'stop',
 'message' => [
 'content' => json_encode([
 'suggestions' => [
 ['value' => 'Facilities Cleaner', 'kind' => 'job', 'confidence' => 0.94],
 ['value' => 'BS Information Technology', 'kind' => 'school', 'confidence' => 0.92],
 ],
 ]),
 ],
 ]],
 ], 200);
 },
 ]);

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'Job Interview']);

 $this->actingAs($user)
 ->postJson(route('interview.targetSuggestions'), [
 'category_id' => $category->id,
 'query' => 'clean',
 ])
 ->assertOk()
 ->assertJsonPath('source', 'ai')
 ->assertJsonPath('provider', 'openai')
 ->assertJsonFragment(['value' => 'Facilities Cleaner'])
 ->assertJsonMissing(['value' => 'BS Information Technology']);

 $this->assertStringContainsString('Typed input: "clean"', $capturedPrompt);
 $this->assertStringContainsString('Job Interview', $capturedPrompt);
 }

 public function test_interview_setup_only_uses_job_and_school_admission_categories(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $jobCategory = $this->category([
 'title' => 'Job Interview',
 'description' => 'Job interview practice',
 'sort_order' => 1,
 ]);
 $admissionCategory = $this->category([
 'title' => 'College Admission',
 'description' => 'School admission practice',
 'sort_order' => 2,
 ]);
 $this->category(['title' => 'BPO / Customer Support', 'sort_order' => 3]);
 $this->category(['title' => 'IT/Programming', 'sort_order' => 4]);
 $this->category(['title' => 'Scholarship Interview', 'sort_order' => 5]);
 $this->category(['title' => 'Game Category', 'type' => 'game']);

 $this->actingAs($user)
 ->get(route('interview.setup'))
 ->assertOk()
 ->assertSee('name="category_id"', false)
 ->assertSee('value="'.$jobCategory->id.'"', false)
 ->assertSee('value="'.$admissionCategory->id.'"', false)
 ->assertSee('Job Interviews')
 ->assertSee('School Admission Interviews')
 ->assertDontSee('BPO / Customer Support Interview')
 ->assertDontSee('IT / Programming Interview')
 ->assertDontSee('Scholarship Interview')
 ->assertDontSee('Game Category');
 }

 public function test_interview_setup_renames_target_field_for_school_admission_on_desktop_and_mobile(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $this->category(['title' => 'Job Interview', 'sort_order' => 1]);
 $admissionCategory = $this->category([
 'title' => 'College Admission',
 'description' => 'School admission practice',
 'sort_order' => 2,
 ]);
 $oldInput = [
 'category_id' => $admissionCategory->id,
 ];
 $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

 $desktopResponse = $this->actingAs($user)
 ->withSession(['_old_input' => $oldInput])
 ->get(route('interview.setup'))
 ->assertOk();
 $mobileResponse = $this->actingAs($user)
 ->withSession(['_old_input' => $oldInput])
 ->withHeader('User-Agent', $mobileUserAgent)
 ->get(route('interview.setup'))
 ->assertOk();

 foreach ([$desktopResponse, $mobileResponse] as $response) {
 $response
 ->assertSee('Target Program')
 ->assertSee('Program:')
 ->assertSee('e.g. BS Information Technology, Computer Science, Nursing')
 ->assertSee('Enter the target program before continuing.');
 }
 }

 public function test_interview_setup_includes_scenario_recommendation_alert_copy_on_desktop_and_mobile(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $this->category(['title' => 'Job Interview', 'sort_order' => 1]);
 $this->category(['title' => 'College Admission', 'sort_order' => 2]);
 $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

 $desktopResponse = $this->actingAs($user)
 ->get(route('interview.setup'))
 ->assertOk();
 $mobileResponse = $this->actingAs($user)
 ->withHeader('User-Agent', $mobileUserAgent)
 ->get(route('interview.setup'))
 ->assertOk();

 foreach ([$desktopResponse, $mobileResponse] as $response) {
 $response
 ->assertSee('Proceed with Job Interview')
 ->assertSee('Recommendation: proceed with Job Interview.')
 ->assertSee('Clean is too broad for a target position. Recommendation: use a specific job target such as Cleaner, Janitor, Housekeeping Attendant, or Janitorial Services, then proceed with Job Interview.')
 ->assertSee('Clean looks related to cleaning work. Recommendation: proceed with Job Interview using a specific target position such as Cleaner, Janitor, Housekeeping Attendant, or Janitorial Services.')
 ->assertSee('Proceed with School Admission')
 ->assertSee('Recommendation: proceed with School Admission Interviews.');
 }
 }

 public function test_interview_setup_includes_target_autocomplete_suggestions_on_desktop_and_mobile(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $this->category(['title' => 'Job Interview', 'sort_order' => 1]);
 $this->category(['title' => 'College Admission', 'sort_order' => 2]);
 $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

 $desktopResponse = $this->actingAs($user)
 ->get(route('interview.setup'))
 ->assertOk();
 $mobileResponse = $this->actingAs($user)
 ->withHeader('User-Agent', $mobileUserAgent)
 ->get(route('interview.setup'))
 ->assertOk();

 foreach ([$desktopResponse, $mobileResponse] as $response) {
 $response
 ->assertSee('aria-autocomplete="list"', false)
 ->assertSee('aria-controls="targetSuggestionList"', false)
 ->assertSee('id="targetSuggestionList"', false)
 ->assertSee('data-target-autocomplete-input', false)
 ->assertSee('setupTargetSuggestionEndpoint', false)
 ->assertSee('target-suggestions')
 ->assertSee('requestAiTargetSuggestions', false)
 ->assertSee('fetch(setupTargetSuggestionEndpoint', false)
 ->assertSee('setupTargetSuggestions', false)
 ->assertSee('renderSetupTargetSuggestionLabel', false)
 ->assertSee('targetPositionInput.addEventListener(\'input\'', false)
 ->assertSee('setupTargetSuggestionScore', false)
 ->assertSee('setupTargetSuggestionAcronym', false)
 ->assertSee('Network Administrator')
 ->assertSee('network admin')
 ->assertSee('Data Analyst')
 ->assertSee('BS Data Science')
 ->assertSee('bsit')
 ->assertSee('Cleaner')
 ->assertSee('Janitor')
 ->assertSee('Housekeeping Attendant')
 ->assertSee('BS Information Technology')
 ->assertSee('BS Computer Science');
 }
 }

 public function test_interview_setup_shows_added_question_count_options_on_desktop_and_mobile(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $this->category();
 $expectedOptions = [
 '<option value="1"',
 '<option value="3"',
 '<option value="25"',
 '<option value="30"',
 ];
 $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

 $desktopResponse = $this->actingAs($user)->get(route('interview.setup'))->assertOk();
 $mobileResponse = $this->actingAs($user)
 ->withHeader('User-Agent', $mobileUserAgent)
 ->get(route('interview.setup'))
 ->assertOk();

 foreach ($expectedOptions as $option) {
 $desktopResponse->assertSee($option, false);
 $mobileResponse->assertSee($option, false);
 }
 }

 public function test_interview_setup_does_not_expose_interview_format_on_desktop_or_mobile(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $this->category();
 $removedMarkup = [
 'name="interview_format"',
 'id="valInterviewFormat"',
 '<option value="hr_screen"',
 '<option value="hiring_manager"',
 '<option value="panel"',
 '<option value="phone"',
 '<option value="asynchronous"',
 '<option value="technical"',
 '<option value="case"',
 '<option value="presentation"',
 'id="sumFormat"',
 'Interview Format',
 ];
 $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

 $desktopResponse = $this->actingAs($user)->get(route('interview.setup'))->assertOk();
 $mobileResponse = $this->actingAs($user)
 ->withHeader('User-Agent', $mobileUserAgent)
 ->get(route('interview.setup'))
 ->assertOk();

 foreach ($removedMarkup as $markup) {
 $desktopResponse->assertDontSee($markup, false);
 $mobileResponse->assertDontSee($markup, false);
 }
 }

 public function test_interview_setup_exposes_content_assistance_controls_on_desktop_and_mobile(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $this->category();
 $expectedMarkup = [
 'name="ai_assistance_level"',
 'id="valAssistance"',
 '<option value="beginner"',
 '<option value="standard"',
 '<option value="challenge"',
 'name="live_feedback_mode"',
 'id="valFeedbackMode"',
 '<option value="coaching"',
 '<option value="real_interview"',
 'name="question_types[]"',
 'value="Behavioral"',
 'value="Situational"',
 'value="Technical"',
 'value="Personal"',
 'id="sumAssistance"',
 'id="sumQuestionTypes"',
 'id="sumFeedbackMode"',
 ];
 $removedMarkup = [
 'name="interviewer_strictness"',
 'id="valStrictness"',
 '<option value="friendly"',
 '<option value="strict"',
 '<option value="executive"',
 'name="company_persona"',
 'id="valPersona"',
 'maxlength="120"',
 'id="sumStrictness"',
 'id="sumPersona"',
 'Interviewer Style',
 'Hiring Context',
 ];
 $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

 $desktopResponse = $this->actingAs($user)->get(route('interview.setup'))->assertOk();
 $mobileResponse = $this->actingAs($user)
 ->withHeader('User-Agent', $mobileUserAgent)
 ->get(route('interview.setup'))
 ->assertOk();

 foreach ($expectedMarkup as $markup) {
 $desktopResponse->assertSee($markup, false);
 $mobileResponse->assertSee($markup, false);
 }

 foreach ($removedMarkup as $markup) {
 $desktopResponse->assertDontSee($markup, false);
 $mobileResponse->assertDontSee($markup, false);
 }
 }

 public function test_interview_setup_repairs_missing_runtime_tables(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category([
 'title' => 'Job Interview',
 'sort_order' => 1,
 ]);

 $this->dropInterviewRuntimeTables();

 $this->actingAs($user)
 ->get(route('interview.setup'))
 ->assertOk()
 ->assertSee('name="category_id"', false)
 ->assertSee('value="'.$category->id.'"', false)
 ->assertSee('id="scenarioHelp"', false)
 ->assertSee('Start Interview');

 $this->assertInterviewRuntimeTablesReady();
 }

 public function test_interview_start_repairs_missing_runtime_tables_and_opens_session(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();

 $this->dropInterviewRuntimeTables();

 $this->actingAs($user)
 ->post(route('interview.start'), $this->interviewPayload($category))
 ->assertRedirect(route('interview.session'))
 ->assertSessionHas('active_interview_id')
 ->assertSessionHas('active_interview_context', 'interview');

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $this->assertDatabaseHas('interview_sessions', [
 'id' => $session->id,
 'category_id' => $category->id,
 'target_position' => 'Developer',
 'status' => 'in_progress',
 ]);
 $this->assertGreaterThanOrEqual(2, Question::where('interview_session_id', $session->id)->count());

 $this->actingAs($user)
 ->withSession(['active_interview_id' => $session->id])
 ->get(route('interview.session'))
 ->assertOk()
 ->assertSee('Developer');

 $this->assertInterviewRuntimeTablesReady();
 }

 public function test_interview_session_repairs_missing_runtime_tables_and_clears_stale_session(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $this->category();

 $this->dropInterviewRuntimeTables();

 $this->actingAs($user)
 ->withSession([
 'active_interview_id' => 99999,
 'active_interview_provider' => 'openai',
 'active_interview_context' => 'interview',
 ])
 ->get(route('interview.session'))
 ->assertRedirect(route('interview.setup'))
 ->assertSessionHas('message', 'Your interview session is no longer active.')
 ->assertSessionMissing('active_interview_id')
 ->assertSessionMissing('active_interview_provider')
 ->assertSessionMissing('active_interview_context');

 $this->assertInterviewRuntimeTablesReady();
 }

 public function test_interview_start_accepts_active_core_category(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $gameCategory = $this->category(['title' => 'Game Category', 'type' => 'game']);
 $level = $this->gameLevel($gameCategory);

 $this->actingAs($user)
 ->withSession([
 'game_level_id' => $level->id,
 'active_interview_context' => 'learning_game',
 ])
 ->post(route('interview.start'), $this->interviewPayload($category))
 ->assertRedirect(route('interview.session'))
 ->assertSessionMissing('game_level_id')
 ->assertSessionHas('active_interview_context', 'interview');

 $this->assertDatabaseHas('interview_sessions', [
 'user_id' => $user->id,
 'category_id' => $category->id,
 'difficulty' => 'medium',
 'game_level_id' => null,
 'status' => 'in_progress',
 ]);
 }

 public function test_interview_start_accepts_and_normalizes_all_response_modes(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();

 foreach ([
 'text' => 'text',
 'voice' => 'voice',
 'hybrid' => 'hybrid',
 'voice_and_text' => 'hybrid',
 ] as $submittedMode => $expectedMode) {
 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'response_mode' => $submittedMode,
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->latest('id')->firstOrFail();

 $this->assertSame($expectedMode, $session->response_mode);
 }
 }

 public function test_interview_start_stores_camera_detection_setting(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'camera_detection' => '1',
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $this->assertTrue((bool) data_get($session->accommodation_profile, 'camera_detection'));
 $this->assertTrue((bool) data_get($session->accommodation_profile, 'camera_coaching'));
 }

 public function test_interview_start_rejects_unsupported_core_categories(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'BPO / Customer Support']);

 $this->actingAs($user)
 ->from(route('interview.setup'))
 ->post(route('interview.start'), $this->interviewPayload($category))
 ->assertRedirect(route('interview.setup'))
 ->assertSessionHasErrors('category_id');

 $this->assertDatabaseCount('interview_sessions', 0);
 }

 public function test_interview_start_accepts_added_question_counts(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();

 foreach ([1, 3, 25, 30] as $count) {
 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'num_questions' => $count,
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->latest('id')->firstOrFail();

 $this->assertSame($count, $session->num_questions);
 }
 }

 public function test_interview_start_stores_complete_interview_structure_settings(): void
 {
 Http::fake();

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'difficulty' => 'hard',
 'num_questions' => 3,
 'time_limit' => 2,
 'interview_format' => 'panel',
 'question_types' => ['Behavioral', 'Technical'],
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->latest('id')->firstOrFail();

 $this->assertSame('hard', $session->difficulty);
 $this->assertSame(3, $session->num_questions);
 $this->assertSame(2, $session->time_limit);
 $this->assertArrayNotHasKey('interview_format', $session->getAttributes());
 $this->assertSame(['Behavioral', 'Technical'], json_decode($session->question_types, true));
 }

 public function test_interview_start_stores_complete_content_assistance_settings(): void
 {
 Http::fake();

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'num_questions' => 3,
 'ai_assistance_level' => 'challenge',
 'interviewer_strictness' => 'executive',
 'live_feedback_mode' => 'real_interview',
 'company_persona' => 'enterprise hiring panel',
 'question_types' => ['Technical', 'Personal'],
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->latest('id')->firstOrFail();

 $this->assertSame('challenge', $session->ai_assistance_level);
 $this->assertSame('real_interview', $session->live_feedback_mode);
 $this->assertSame('assessment', $session->assessment_mode);
 $this->assertTrue((bool) $session->score_eligible);
 $this->assertArrayNotHasKey('interviewer_strictness', $session->getAttributes());
 $this->assertArrayNotHasKey('company_persona', $session->getAttributes());
 $this->assertSame(['Technical', 'Personal'], json_decode($session->question_types, true));
 }

 public function test_ai_assistance_level_prompt_rules_drive_question_generation_and_live_chat(): void
 {
 AiProvider::create([
 'name' => 'OpenAI',
 'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
 'api_key' => Crypt::encryptString('test-openai-key'),
 'status' => 'active',
 ]);
 $capturedMessages = [];
 $providerReplies = [
 json_encode(['questions' => ['What evidence proves your Developer fit under pressure?']]),
 'Thanks. What personal action most changed the outcome for the Developer role?',
 'That result is useful. What tradeoff did you manage as a Developer?',
 'That is a start. What evidence proves the decision worked for the Developer role?',
 ];

 Http::fake([
 'api.openai.com/*' => function ($request) use (&$capturedMessages, &$providerReplies) {
 $capturedMessages[] = data_get($request->data(), 'messages', []);

 return Http::response([
 'choices' => [[
 'finish_reason' => 'stop',
 'message' => [
 'content' => array_shift($providerReplies),
 ],
 ]],
 ], 200);
 },
 ]);

 $questions = AIService::generateQuestions(
 1,
 'Developer',
 'medium',
 'Job Interview',
 'openai',
 null,
 null,
 ['Behavioral'],
 'challenge'
 );
 $this->assertSame(['What evidence proves your Developer fit under pressure?'], $questions);

 $sessionBase = [
 'target_position' => 'Developer',
 'difficulty' => 'medium',
 'question_types' => json_encode(['Behavioral']),
 'interview_focus' => 'Job Interview',
 'live_feedback_mode' => 'coaching',
 'accommodation_profile' => [],
 ];
 $answer = 'I coordinated QA with the deployment team and improved release confidence.';

 $beginnerReply = AIService::generateChatReply((object) array_merge($sessionBase, [
 'ai_assistance_level' => 'beginner',
 ]), [], $answer, 'openai');
 $standardReply = AIService::generateChatReply((object) array_merge($sessionBase, [
 'ai_assistance_level' => 'standard',
 ]), [], $answer, 'openai');
 $challengeReply = AIService::generateChatReply((object) array_merge($sessionBase, [
 'ai_assistance_level' => 'challenge',
 ]), [], $answer, 'openai');

 $this->assertSame('Thanks. What personal action most changed the outcome for the Developer role?', $beginnerReply);
 $this->assertSame('That result is useful. What tradeoff did you manage as a Developer?', $standardReply);
 $this->assertSame('That is a start. What evidence proves the decision worked for the Developer role?', $challengeReply);
 $this->assertCount(4, $capturedMessages);

 $questionGenerationPrompt = (string) data_get($capturedMessages, '0.1.content');
 $beginnerPrompt = (string) data_get($capturedMessages, '1.1.content');
 $standardPrompt = (string) data_get($capturedMessages, '2.1.content');
 $challengePrompt = (string) data_get($capturedMessages, '3.1.content');

 $this->assertStringContainsString('Challenge assistance is enabled', $questionGenerationPrompt);
 $this->assertStringContainsString('without giving hints', $questionGenerationPrompt);
 $this->assertStringContainsString('Beginner assistance is enabled', $beginnerPrompt);
 $this->assertStringContainsString('clearer, shorter questions', $beginnerPrompt);
 $this->assertStringContainsString('Standard assistance is enabled', $standardPrompt);
 $this->assertStringContainsString('balanced professional interview style', $standardPrompt);
 $this->assertStringContainsString('Challenge assistance is enabled', $challengePrompt);
 $this->assertStringContainsString('tougher, more specific follow-ups', $challengePrompt);
 }

 public function test_live_feedback_modes_send_distinct_interviewer_prompt_rules(): void
 {
 AiProvider::create([
 'name' => 'OpenAI',
 'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
 'api_key' => Crypt::encryptString('test-openai-key'),
 'status' => 'active',
 ]);
 $capturedMessages = [];
 $providerReplies = [
 'To strengthen the answer, what measurable result came from coordinating QA for the Developer role?',
 'What measurable result proves that coordinating QA mattered for the Developer role?',
 ];

 Http::fake([
 'api.openai.com/*' => function ($request) use (&$capturedMessages, &$providerReplies) {
 $capturedMessages[] = data_get($request->data(), 'messages', []);

 return Http::response([
 'choices' => [[
 'message' => [
 'content' => array_shift($providerReplies),
 ],
 ]],
 ], 200);
 },
 ]);

 $sessionBase = [
 'target_position' => 'Developer',
 'difficulty' => 'medium',
 'ai_assistance_level' => 'standard',
 'question_types' => json_encode(['Behavioral']),
 'interview_focus' => 'Job Interview',
 'accommodation_profile' => [],
 ];
 $answer = 'I coordinated QA with the deployment team before launch.';

 $coachedReply = AIService::generateChatReply((object) array_merge($sessionBase, [
 'live_feedback_mode' => 'coaching',
 ]), [], $answer, 'openai');
 $realReply = AIService::generateChatReply((object) array_merge($sessionBase, [
 'live_feedback_mode' => 'real_interview',
 ]), [], $answer, 'openai');

 $this->assertSame('To strengthen the answer, what measurable result came from coordinating QA for the Developer role?', $coachedReply);
 $this->assertSame('What measurable result proves that coordinating QA mattered for the Developer role?', $realReply);
 $this->assertCount(2, $capturedMessages);

 $coachedSystem = (string) data_get($capturedMessages, '0.0.content');
 $coachedPrompt = (string) data_get($capturedMessages, '0.1.content');
 $realSystem = (string) data_get($capturedMessages, '1.0.content');
 $realPrompt = (string) data_get($capturedMessages, '1.1.content');

 $this->assertStringContainsString('Coached practice mode is enabled', $coachedPrompt);
 $this->assertStringContainsString('Final coaching and feedback are saved for the report', $coachedPrompt);
 $this->assertStringContainsString('one brief practice cue', $coachedSystem);
 $this->assertStringNotContainsString('Real interview mode is enabled', $coachedPrompt);
 $this->assertStringContainsString('Real interview mode is enabled', $realPrompt);
 $this->assertStringContainsString('do not reassure or teach', $realPrompt);
 $this->assertStringContainsString('prefer sharper follow-ups', $realPrompt);
 $this->assertStringContainsString('do not coach, reassure, teach', $realSystem);
 $this->assertStringNotContainsString('Coached practice mode is enabled', $realPrompt);
 }

 public function test_local_follow_up_fallback_respects_ai_assistance_level(): void
 {
 $sessionBase = [
 'target_position' => 'Developer',
 'live_feedback_mode' => 'coaching',
 ];
 $shortAnswer = 'I helped.';

 $beginnerReply = AIService::fallbackInterviewReply((object) array_merge($sessionBase, [
 'ai_assistance_level' => 'beginner',
 ]), [], $shortAnswer);
 $standardReply = AIService::fallbackInterviewReply((object) array_merge($sessionBase, [
 'ai_assistance_level' => 'standard',
 ]), [], $shortAnswer);
 $challengeReply = AIService::fallbackInterviewReply((object) array_merge($sessionBase, [
 'ai_assistance_level' => 'challenge',
 ]), [], $shortAnswer);

 $this->assertStringContainsString("Let's make this easier to answer", $beginnerReply);
 $this->assertStringContainsString("Let's turn that into a stronger practice answer", $standardReply);
 $this->assertStringContainsString('That is not enough evidence yet', $challengeReply);
 }

 public function test_local_follow_up_fallback_respects_live_feedback_mode(): void
 {
 $sessionBase = [
 'target_position' => 'Developer',
 ];
 $shortAnswer = 'I helped.';

 $coachedReply = AIService::fallbackInterviewReply((object) array_merge($sessionBase, [
 'live_feedback_mode' => 'coaching',
 ]), [], $shortAnswer);
 $realReply = AIService::fallbackInterviewReply((object) array_merge($sessionBase, [
 'live_feedback_mode' => 'real_interview',
 ]), [], $shortAnswer);

 $this->assertStringContainsString("Let's turn that into a stronger practice answer", $coachedReply);
 $this->assertStringContainsString('I need a complete example to assess fit', $realReply);
 $this->assertStringNotContainsString('stronger practice answer', $realReply);
 }

 public function test_interview_session_marks_live_coaching_aids_as_coaching_only_on_desktop_and_mobile(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $session = $this->sessionFor($user, $category, [
 'live_feedback_mode' => 'coaching',
 'ai_assistance_level' => 'challenge',
 'assessment_mode' => 'coached',
 'score_eligible' => false,
 ]);
 $this->question($category, [
 'interview_session_id' => $session->id,
 'question_text' => 'Tell me about a project you owned.',
 ]);
 $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';
 $expectedMarkup = [
 'css/desktop/interview/session.css?v=23',
 'id="coachingTip"',
 'session-live-coaching coaching-only',
 'interview-confidence-control coaching-only',
 'Coaching On',
 'Challenge Assistance',
 "liveFeedbackMode!== 'real_interview'",
 "classList.toggle('real-interview-mode', liveFeedbackMode === 'real_interview')",
 'const assistanceLevel = "challenge";',
 'const liveFeedbackMode = "coaching";',
 ];
 $removedSourceMarkup = [
 'id="questionSourcePanel"',
 'id="aiQuestionSource"',
 'AI-adapted from:',
 'Source will appear when the question starts.',
 'function updateQuestionSource',
 ];

 $desktopResponse = $this->actingAs($user)
 ->withSession(['active_interview_id' => $session->id])
 ->get(route('interview.session'))
 ->assertOk();

 foreach ($expectedMarkup as $markup) {
 $desktopResponse->assertSee($markup, false);
 }
 foreach ($removedSourceMarkup as $markup) {
 $desktopResponse->assertDontSee($markup, false);
 }

 $mobileResponse = $this->actingAs($user)
 ->withHeader('User-Agent', $mobileUserAgent)
 ->withSession(['active_interview_id' => $session->id])
 ->get(route('interview.session'))
 ->assertOk();

 foreach (array_merge(array_diff($expectedMarkup, ['css/desktop/interview/session.css?v=23']), [
 'css/mobile/interview/session.css?v=9',
 ]) as $markup) {
 $mobileResponse->assertSee($markup, false);
 }
 foreach ($removedSourceMarkup as $markup) {
 $mobileResponse->assertDontSee($markup, false);
 }
 }

 public function test_interview_start_uses_category_source_dataset(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category(['title' => 'College Admission']);

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'target_position' => 'Information Technology',
 'question_types' => ['Situational'],
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $sourceTypes = Question::where('interview_session_id', $session->id)
 ->where('category_id', $category->id)
 ->where('source_type', '!=', 'real_interview_opening')
 ->pluck('source_type');

 $this->assertNotContains('competency_source', $sourceTypes->all());
 $this->assertTrue($sourceTypes->contains(fn ($type) => in_array($type, [
 'official_admission_source',
 'speakready_reliable_question_bank',
 ], true)));
 }

 public function test_interview_start_creates_questions_when_bank_is_empty(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();

 $this->actingAs($user)
 ->post(route('interview.start'), array_merge($this->interviewPayload($category), [
 'num_questions' => 5,
 'question_types' => ['Technical'],
 ]))
 ->assertRedirect(route('interview.session'));

 $session = InterviewSession::where('user_id', $user->id)->firstOrFail();

 $this->assertDatabaseCount('questions', 2);
 $this->assertDatabaseHas('questions', [
 'interview_session_id' => $session->id,
 'category_id' => $category->id,
 'difficulty' => 'medium',
 'type' => 'Personal',
 'status' => 'active',
 'source_type' => 'real_interview_opening',
 ]);
 $this->assertDatabaseHas('questions', [
 'interview_session_id' => $session->id,
 'category_id' => $category->id,
 'difficulty' => 'medium',
 'type' => 'Technical',
 'status' => 'active',
 ]);
 $this->assertTrue(
 Question::where('interview_session_id', $session->id)
 ->where('source_type', 'real_interview_opening')
 ->pluck('question_text')
 ->every(fn (string $questionText) => str_contains($questionText, 'introduce yourself'))
 );
 $this->assertTrue(
 Question::where('interview_session_id', $session->id)->get()
 ->every(fn (Question $question): bool => filled($question->expected_guide) &&! empty($question->mapped_skills))
 );
 }

 public function test_public_shared_review_accepts_mentor_comment(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $session = $this->sessionFor($user, $category);
 $session->update([
 'is_public' => true,
 'share_token' => 'public-session-token',
 ]);

 $this->post(route('shared.mentor-comments.store', $session->share_token), [
 'reviewer_name' => 'Mentor One',
 'reviewer_email' => 'mentor@example.com',
 'rating' => 5,
 'comment' => 'Strong structure and clear examples. Keep tightening the measurable results.',
 ])
 ->assertRedirect(route('shared.review', $session->share_token));

 $this->assertDatabaseHas('mentor_review_comments', [
 'interview_session_id' => $session->id,
 'reviewer_name' => 'Mentor One',
 'rating' => 5,
 ]);
 }

 public function test_interview_answer_recomputes_delivery_metrics_from_server_evidence(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $session = $this->sessionFor($user, $category);
 $question = $this->question($category, ['interview_session_id' => $session->id]);

 $this->actingAs($user)
 ->withSession(['active_interview_id' => $session->id])
 ->postJson(route('interview.answer'), [
 'question_id' => $question->id,
 'answer_text' => 'Typed notes said um um, then I did it.',
 'speech_transcript' => 'I did it.',
 'response_mode' => 'voice',
 'voice_duration' => 30,
 'wpm' => 100,
 'filler_words_count' => 0,
 'pause_count' => 0,
 'confidence_score' => 100,
 'eye_contact_score' => 90,
 'posture_score' => 90,
 ])
 ->assertOk();

 $this->assertDatabaseHas('interview_answers', [
 'interview_session_id' => $session->id,
 'question_id' => $question->id,
 'wpm' => 6,
 'filler_words_count' => 0,
 'eye_contact_score' => 0,
 'posture_score' => 0,
 'confidence_score' => 0,
 ]);

 $savedAnswer = InterviewAnswer::where('interview_session_id', $session->id)
 ->where('question_id', $question->id)
 ->firstOrFail();

 $this->assertSame('measured', data_get($savedAnswer->observation_data, 'delivery.status'));
 $this->assertSame(6, data_get($savedAnswer->observation_data, 'delivery.wpm'));
 $this->assertSame('I did it.', $savedAnswer->delivery_transcript);
 $this->assertSame('not_measured', data_get($savedAnswer->observation_data, 'camera.status'));
 $this->assertNotEmpty($savedAnswer->coaching_feedback);
 }

 public function test_interview_answer_respects_text_voice_hybrid_and_legacy_response_modes(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();

 foreach ([
 'text' => ['stored' => 'text', 'expects_voice_delivery' => false],
 'voice' => ['stored' => 'voice', 'expects_voice_delivery' => true],
 'hybrid' => ['stored' => 'hybrid', 'expects_voice_delivery' => true],
 'voice_and_text' => ['stored' => 'hybrid', 'expects_voice_delivery' => true],
 ] as $submittedMode => $expectation) {
 $session = $this->sessionFor($user, $category, [
 'response_mode' => $submittedMode,
 ]);
 $question = $this->question($category, [
 'interview_session_id' => $session->id,
 'question_text' => "Describe a {$submittedMode} response.",
 ]);
 $spokenAnswer = 'I handled a support escalation and documented the result.';

 $this->actingAs($user)
 ->withSession(['active_interview_id' => $session->id])
 ->postJson(route('interview.answer'), [
 'question_id' => $question->id,
 'answer_text' => $submittedMode === 'text'? 'I typed a separate answer with my own project evidence.': $spokenAnswer,
 'speech_transcript' => $spokenAnswer,
 'response_mode' => $submittedMode,
 'voice_duration' => 30,
 'wpm' => 400,
 'filler_words_count' => 99,
 'pause_count' => 2,
 ])
 ->assertOk();

 $answer = InterviewAnswer::where('interview_session_id', $session->id)
 ->where('question_id', $question->id)
 ->firstOrFail();

 $this->assertSame($expectation['stored'], $answer->response_mode);

 if ($expectation['expects_voice_delivery']) {
 $this->assertSame($spokenAnswer, $answer->delivery_transcript);
 $this->assertSame(30, $answer->voice_duration);
 $this->assertGreaterThan(0, $answer->wpm);
 $this->assertNotSame(400, $answer->wpm);
 $this->assertSame(0, $answer->filler_words_count);
 $this->assertSame('measured', data_get($answer->observation_data, 'delivery.status'));
 } else {
 $this->assertNull($answer->delivery_transcript);
 $this->assertSame(0, $answer->voice_duration);
 $this->assertSame(0, $answer->wpm);
 $this->assertSame(0, $answer->filler_words_count);
 $this->assertSame('not_measured', data_get($answer->observation_data, 'delivery.status'));
 }
 }
 }

 public function test_voice_answer_upload_submits_without_transcript_and_plays_back_in_review(): void
 {
 Storage::fake('local');
 config(['services.ai_transcription.provider_priority' => '']);

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $session = $this->sessionFor($user, $category, ['response_mode' => 'voice']);
 $question = $this->question($category, [
 'interview_session_id' => $session->id,
 'question_text' => 'Tell me about a time you helped a customer.',
 ]);

 $this->actingAs($user)
 ->withSession(['active_interview_id' => $session->id])
 ->post(route('interview.answer'), [
 'session_id' => $session->id,
 'question_id' => $question->id,
 'answer_text' => '',
 'speech_transcript' => '',
 'response_mode' => 'voice',
 'voice_duration' => 14,
 'voice_recording_duration_seconds' => 14,
 'voice_recording_transcription_status' => 'failed',
 'voice_audio' => UploadedFile::fake()
 ->createWithContent('answer.webm', str_repeat('A', 4096))
 ->mimeType('audio/webm'),
 ])
 ->assertOk()
 ->assertJson(['success' => true]);

 $answer = InterviewAnswer::where('interview_session_id', $session->id)
 ->where('question_id', $question->id)
 ->firstOrFail();

 $this->assertSame('', $answer->answer_text);
 $this->assertFalse((bool) $answer->is_skipped);
 $this->assertSame('voice', $answer->response_mode);
 $this->assertSame(14, $answer->voice_duration);
 $this->assertSame('local', $answer->voice_recording_disk);
 $this->assertSame('audio/webm', $answer->voice_recording_mime_type);
 $this->assertSame('failed', $answer->voice_recording_transcription_status);
 $this->assertGreaterThan(0, $answer->voice_recording_byte_size);
 $this->assertNotEmpty($answer->voice_recording_path);
 Storage::disk('local')->assertExists($answer->voice_recording_path);

 $this->actingAs($user)
 ->get(route('interview.answer.voiceRecording', $answer))
 ->assertOk()
 ->assertHeader('Content-Type', 'audio/webm')
 ->assertHeader('Accept-Ranges', 'bytes');

 $this->actingAs($user)
 ->withHeader('Range', 'bytes=0-15')
 ->get(route('interview.answer.voiceRecording', $answer))
 ->assertStatus(206)
 ->assertHeader('Content-Range', 'bytes 0-15/'.$answer->voice_recording_byte_size);

 $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $this->actingAs($otherUser)
 ->get(route('interview.answer.voiceRecording', $answer))
 ->assertForbidden();

 $session->update([
 'status' => 'ended',
 'action_plan' => ['ended_early' => true],
 ]);

 $this->actingAs($user)
 ->get(route('user.review', $session->id))
 ->assertOk()
 ->assertSee('Voice Answer')
 ->assertSee(route('interview.answer.voiceRecording', $answer), false)
 ->assertSee('Transcript unavailable. Your voice recording was still saved for playback and review.')
 ->assertSee('Transcript unavailable. Listen to the saved voice answer above.');
 }

 public function test_voice_answer_upload_adds_internal_transcript_for_ai_feedback(): void
 {
 Storage::fake('local');
 config([
 'services.ai_transcription.provider_priority' => 'openai',
 'services.local_speech.enabled' => false,
 'services.openai.transcription_model' => 'gpt-transcribe',
 ]);

 Http::fake([
 'https://api.openai.com/v1/audio/transcriptions' => Http::response([
 'text' => 'I listened to the customer, fixed the billing issue, and followed up the next day.',
 ], 200),
 ]);

 AiProvider::create([
 'name' => 'OpenAI',
 'api_endpoint' => 'https://api.openai.com/v1',
 'api_key' => Crypt::encryptString('test-key'),
 'status' => 'active',
 ]);

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $session = $this->sessionFor($user, $category, ['response_mode' => 'voice']);
 $question = $this->question($category, [
 'interview_session_id' => $session->id,
 'question_text' => 'Tell me about a time you helped a customer.',
 ]);

 $this->actingAs($user)
 ->withSession(['active_interview_id' => $session->id])
 ->post(route('interview.answer'), [
 'session_id' => $session->id,
 'question_id' => $question->id,
 'answer_text' => '',
 'speech_transcript' => '',
 'response_mode' => 'voice',
 'voice_duration' => 18,
 'voice_recording_duration_seconds' => 18,
 'voice_recording_transcription_status' => 'failed',
 'voice_audio' => UploadedFile::fake()
 ->createWithContent('answer.webm', str_repeat('A', 4096))
 ->mimeType('audio/webm'),
 ])
 ->assertOk()
 ->assertJson(['success' => true]);

 $answer = InterviewAnswer::where('interview_session_id', $session->id)
 ->where('question_id', $question->id)
 ->firstOrFail();

 $this->assertSame('', $answer->answer_text);
 $this->assertSame('I listened to the customer, fixed the billing issue, and followed up the next day.', $answer->delivery_transcript);
 $this->assertSame('transcribed', $answer->voice_recording_transcription_status);
 $this->assertGreaterThan(0, $answer->wpm);

 $answer->forceFill([
 'ai_feedback' => 'Based on your voice answer, you gave a clear customer support example.',
 'better_sample_answer' => 'I listened to the customer and fixed the billing issue.',
 'score' => 82,
 ])->save();
 $session->forceFill(['status' => 'completed'])->save();

 $this->actingAs($user)
 ->get(route('user.feedback'))
 ->assertOk()
 ->assertSee('Voice answer recorded for feedback.')
 ->assertSee('Voice answer session')
 ->assertSee('Feedback is based on this voice answer.')
 ->assertSee(route('interview.answer.voiceRecording', $answer), false);
 }

 public function test_interview_finish_recovers_stored_voice_answer_for_ai_feedback(): void
 {
 Storage::fake('local');
 config([
 'services.ai_transcription.provider_priority' => 'openai',
 'services.local_speech.enabled' => false,
 'services.openai.transcription_model' => 'gpt-transcribe',
 ]);

 $voiceTranscript = 'I listened to the customer, fixed the billing issue, and followed up the next day.';
 $feedbackPrompt = '';
 $answerId = null;

 Http::fake(function ($request) use (&$feedbackPrompt, &$answerId, $voiceTranscript) {
 $url = (string) $request->url();

 if (str_contains($url, '/audio/transcriptions')) {
 return Http::response(['text' => $voiceTranscript], 200);
 }

 $feedbackPrompt = (string) collect(data_get($request->data(), 'messages', []))
 ->pluck('content')
 ->filter()
 ->implode("\n");

 return Http::response([
 'choices' => [[
 'finish_reason' => 'stop',
 'message' => [
 'content' => json_encode([
 'per_question_feedback' => [[
 'id' => $answerId,
 'score' => 82,
 'clarity_score' => 82,
 'relevance_score' => 82,
 'grammar_score' => 82,
 'professionalism_score' => 82,
 'star_applicable' => false,
 'star_method_score' => 0,
 'evidence_quotes' => [$voiceTranscript],
 'question_focus' => 'Tell me about a time you helped a customer.',
 'answer_alignment' => 'directly_addressed',
 'missing_criteria' => [],
 'ai_feedback' => 'For "Tell me about a time you helped a customer.", you stated "'.$voiceTranscript.'", which directly supports the customer help example.',
 'better_sample_answer' => 'I listened to the customer, fixed the billing issue, and followed up the next day.',
 'follow_up_question' => 'What result did the customer or team see after your follow-up?',
 'coaching' => [
 'keep' => 'Keep the billing issue and follow-up detail for this customer question.',
 'improve' => 'Add the result the customer or team saw after your action.',
 'next_try' => 'Answer the customer question by adding one true result after the follow-up.',
 'next_attempt_steps' => [
 'Start with the customer billing issue.',
 'State your action and follow-up.',
 'Add one true result.',
 ],
 'success_check' => 'The retry connects the billing help to a clear result.',
 ],
 ]],
 'session_feedback' => [
 'strengths' => 'The voice answer gave a clear customer support example.',
 'weaknesses' => 'The answer could add the final customer result.',
 'improvement_suggestions' => 'Keep the action clear and add one true result.',
 ],
 ]),
 ],
 ]],
 ], 200);
 });

 AiProvider::create([
 'name' => 'OpenAI',
 'api_endpoint' => 'https://api.openai.com/v1',
 'api_key' => Crypt::encryptString('test-key'),
 'status' => 'active',
 ]);

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $session = $this->sessionFor($user, $category, [
 'response_mode' => 'voice',
 'num_questions' => 1,
 ]);
 $question = $this->question($category, [
 'interview_session_id' => $session->id,
 'question_text' => 'Tell me about a time you helped a customer.',
 'type' => 'General',
 ]);
 $voicePath = 'interview_voice_answers/user_'.$user->id.'/session_'.$session->id.'/answer.webm';
 Storage::disk('local')->put($voicePath, str_repeat('A', 4096));
 $answer = InterviewAnswer::create([
 'interview_session_id' => $session->id,
 'question_id' => $question->id,
 'answer_text' => '',
 'delivery_transcript' => null,
 'response_mode' => 'voice',
 'voice_duration' => 18,
 'voice_recording_disk' => 'local',
 'voice_recording_path' => $voicePath,
 'voice_recording_mime_type' => 'audio/webm',
 'voice_recording_byte_size' => 4096,
 'voice_recording_transcription_status' => 'failed',
 ]);
 $answerId = $answer->id;

 $this->actingAs($user)
 ->withSession([
 'active_interview_id' => $session->id,
 'active_interview_provider' => 'openai',
 'active_interview_feedback_provider' => 'openai',
 ])
 ->postJson(route('interview.finish'), ['session_id' => $session->id])
 ->assertOk()
 ->assertJsonPath('redirect_url', route('user.review', $session));

 $savedAnswer = $answer->fresh();
 $this->assertSame('', $savedAnswer->answer_text);
 $this->assertSame($voiceTranscript, $savedAnswer->delivery_transcript);
 $this->assertSame('transcribed', $savedAnswer->voice_recording_transcription_status);
 $this->assertStringContainsString($voiceTranscript, $feedbackPrompt);
 $this->assertStringContainsString($voiceTranscript, (string) $savedAnswer->ai_feedback);
 $this->assertDatabaseHas('interview_sessions', ['id' => $session->id, 'status' => 'completed']);
 $this->assertDatabaseHas('scores', ['interview_session_id' => $session->id]);
 $this->assertDatabaseHas('feedback', ['interview_session_id' => $session->id]);
 }

 public function test_interview_answer_rejects_out_of_range_delivery_metrics(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $session = $this->sessionFor($user, $category);
 $question = $this->question($category, ['interview_session_id' => $session->id]);

 $this->actingAs($user)
 ->withSession(['active_interview_id' => $session->id])
 ->postJson(route('interview.answer'), [
 'question_id' => $question->id,
 'answer_text' => 'This should not be accepted.',
 'wpm' => 999,
 ])
 ->assertUnprocessable();

 $this->assertDatabaseMissing('interview_answers', [
 'interview_session_id' => $session->id,
 'question_id' => $question->id,
 ]);
 }

 public function test_interview_answer_rejects_unrelated_speech_transcript_as_delivery_evidence(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $session = $this->sessionFor($user, $category);
 $question = $this->question($category, ['interview_session_id' => $session->id]);

 $this->actingAs($user)
 ->withSession(['active_interview_id' => $session->id])
 ->postJson(route('interview.answer'), [
 'question_id' => $question->id,
 'answer_text' => 'This answer was typed and contains different content.',
 'speech_transcript' => 'Um I claimed an unrelated spoken response.',
 'response_mode' => 'voice',
 'voice_duration' => 30,
 ])
 ->assertOk();

 $savedAnswer = InterviewAnswer::where('interview_session_id', $session->id)
 ->where('question_id', $question->id)
 ->firstOrFail();

 $this->assertNull($savedAnswer->delivery_transcript);
 $this->assertNull($savedAnswer->delivery_stability_score);
 $this->assertSame(0, $savedAnswer->wpm);
 $this->assertSame(0, $savedAnswer->filler_words_count);
 $this->assertSame('not_measured', data_get($savedAnswer->observation_data, 'delivery.status'));
 }

 public function test_interview_answer_cleans_adjacent_transcript_duplicates(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $session = $this->sessionFor($user, $category);
 $question = $this->question($category, ['interview_session_id' => $session->id]);

 $this->actingAs($user)
 ->withSession(['active_interview_id' => $session->id])
 ->postJson(route('interview.answer'), [
 'question_id' => $question->id,
 'answer_text' => 'I led a migration I led a migration and reduced downtime downtime.',
 'response_mode' => 'voice',
 ])
 ->assertOk();

 $this->assertDatabaseHas('interview_answers', [
 'interview_session_id' => $session->id,
 'question_id' => $question->id,
 'answer_text' => 'I led a migration and reduced downtime',
 ]);
 }

 public function test_interview_answer_records_copy_paste_and_ai_integrity_signals(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $session = $this->sessionFor($user, $category);
 $question = $this->question($category, ['interview_session_id' => $session->id]);

 $this->actingAs($user)
 ->withSession(['active_interview_id' => $session->id])
 ->postJson(route('interview.answer'), [
 'question_id' => $question->id,
 'answer_text' => 'As an AI language model, I would leverage best practices to streamline a robust and comprehensive process for stakeholders while ensuring measurable outcomes and continuous improvement.',
 'paste_event_count' => 1,
 'pasted_character_count' => 180,
 'elapsed_seconds' => 6,
 'transcript_timeline' => json_encode([
 ['at' => 5, 'event' => 'large_paste', 'words' => 24, 'chars' => 180, 'pasted_chars' => 180],
 ]),
 ])
 ->assertOk();

 $answer = InterviewAnswer::where('interview_session_id', $session->id)
 ->where('question_id', $question->id)
 ->firstOrFail();

 $this->assertSame(1, $answer->paste_event_count);
 $this->assertSame(180, $answer->pasted_character_count);
 $this->assertGreaterThanOrEqual(70, $answer->ai_generated_likelihood);
 $this->assertTrue($answer->answer_integrity_flags['copy_paste_detected']);
 $this->assertTrue($answer->answer_integrity_flags['possible_ai_generated_answer']);
 $this->assertContains('large_paste_volume', $answer->answer_integrity_flags['signals']);
 $this->assertSame('flagged', $answer->audit_status);
 $this->assertStringContainsString('copy/paste activity detected', $answer->flagged_reason);
 $this->assertStringContainsString('possible AI-generated answer pattern detected', $answer->flagged_reason);
 }

 public function test_game_start_stops_when_energy_is_empty_after_today_refill(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create([
 'user_id' => $user->id,
 'energy' => 0,
 'energy_last_refilled_at' => now(),
 ]);
 $category = $this->category(['type' => 'game']);
 $level = $this->gameLevel($category);

 $this->actingAs($user)
 ->from(route('user.learning', ['category_id' => $category->id]))
 ->post(route('user.game.start', $level))
 ->assertRedirect(route('user.learning', ['category_id' => $category->id]))
 ->assertSessionHas('error');

 $this->assertDatabaseMissing('interview_sessions', [
 'user_id' => $user->id,
 'category_id' => $category->id,
 ]);
 $this->assertSame(0, Profile::where('user_id', $user->id)->first()->energy);
 }

 public function test_game_start_refills_daily_energy_and_consumes_cost(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create([
 'user_id' => $user->id,
 'energy' => 0,
 'energy_last_refilled_at' => now()->subDay(),
 ]);
 $category = $this->category(['type' => 'game']);
 $level = $this->gameLevel($category);

 $this->actingAs($user)
 ->post(route('user.game.start', $level))
 ->assertRedirect(route('user.game.match'));

 $this->assertSame(Profile::MAX_ENERGY - 1, Profile::where('user_id', $user->id)->first()->energy);
 $this->assertDatabaseHas('game_sessions', [
 'user_id' => $user->id,
 'game_level_id' => $level->id,
 'status' => 'in_progress',
 ]);
 $this->assertDatabaseCount('interview_sessions', 0);
 }

 public function test_learning_caps_existing_energy_to_three_lives(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create([
 'user_id' => $user->id,
 'energy' => 20,
 'energy_last_refilled_at' => now(),
 ]);
 $category = $this->category(['type' => 'game']);
 $this->gameLevel($category);

 $this->actingAs($user)
 ->get(route('user.learning', ['category_id' => $category->id]))
 ->assertOk()
 ->assertSee('3 / 3 Lives');

 $this->assertSame(3, Profile::where('user_id', $user->id)->first()->energy);
 }

 public function test_game_start_repairs_missing_game_session_tables_before_insert(): void
 {
 Schema::dropIfExists('game_answers');
 Schema::dropIfExists('game_sessions');
 Schema::dropIfExists('game_certificates');
 Schema::dropIfExists('game_progress');

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category(['type' => 'game']);
 $level = $this->gameLevel($category);

 $this->actingAs($user)
 ->post(route('user.game.start', $level))
 ->assertRedirect(route('user.game.match'));

 $this->assertTrue(Schema::hasTable('game_sessions'));
 $this->assertTrue(Schema::hasTable('game_answers'));
 $this->assertTrue(Schema::hasTable('game_progress'));
 $this->assertTrue(Schema::hasTable('game_certificates'));
 $this->assertDatabaseHas('game_sessions', [
 'user_id' => $user->id,
 'game_level_id' => $level->id,
 'status' => 'in_progress',
 ]);
 }

 public function test_game_save_state_rejects_question_index_outside_session_questions(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category(['type' => 'game']);
 $level = $this->gameLevel($category);
 $session = GameSession::create([
 'user_id' => $user->id,
 'game_level_id' => $level->id,
 'status' => 'in_progress',
 'num_questions' => 2,
 'questions' => ['First question?', 'Second question?'],
 'response_mode' => 'hybrid',
 'current_question_index' => 0,
 ]);

 $this->actingAs($user)
 ->postJson(route('user.game.saveState'), [
 'game_session_id' => $session->id,
 'current_question_index' => 2,
 'duration_seconds' => 90,
 ])
 ->assertStatus(422)
 ->assertJsonPath('error', 'Saved question position is outside this Learning Game session.');

 $session->refresh();
 $this->assertSame(0, $session->current_question_index);
 $this->assertNull($session->duration_seconds);
 }

 public function test_game_answer_and_finish_use_separate_game_session_flow(): void
 {
 Storage::fake('local');

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category(['type' => 'game']);
 $level = $this->gameLevel($category, [
 'required_score' => 0,
 'xp_reward' => 125,
 ]);

 $this->actingAs($user)
 ->post(route('user.game.start', $level))
 ->assertRedirect(route('user.game.match'));

 $session = GameSession::where('user_id', $user->id)->firstOrFail();
 $this->assertSame('voice', $session->response_mode);

 $this->actingAs($user)
 ->post(route('user.game.answer'), [
 'game_session_id' => $session->id,
 'question_index' => 0,
 'answer_text' => 'I explained the situation task action and result with enough detail for this learning game.',
 'response_mode' => 'voice',
 'voice_duration' => 18,
 'voice_recording_duration_seconds' => 18,
 'voice_recording_transcription_status' => 'transcribed',
 'voice_audio' => UploadedFile::fake()
 ->createWithContent('challenge-answer.webm', str_repeat('A', 4096))
 ->mimeType('audio/webm'),
 ])
 ->assertOk()
 ->assertJson(['success' => true]);

 $this->actingAs($user)
 ->post(route('user.game.finish'), [
 'game_session_id' => $session->id,
 'duration_seconds' => 45,
 ])
 ->assertRedirect(route('user.learning', ['category_id' => $category->id]))
 ->assertSessionHas('success')
 ->assertSessionHas('game_result', function (array $result) use ($level, $session): bool {
 return $result['game_session_id'] === $session->id
 && $result['level_id'] === $level->id
 && $result['status'] === 'passed'
 && $result['required_score'] === 0
 && $result['energy_spent'] === 1
 && $result['energy_remaining'] === Profile::MAX_ENERGY - 1
 && $result['xp_earned'] === 125
 &&! empty($result['certificate']['download_url']);
 });

 $this->assertDatabaseHas('game_progress', [
 'user_id' => $user->id,
 'game_level_id' => $level->id,
 'status' => 'completed',
 ]);
 $this->assertGreaterThan(0, (int) GameProgress::where('user_id', $user->id)
 ->where('game_level_id', $level->id)
 ->value('best_score'));

 $this->assertDatabaseHas('game_sessions', [
 'id' => $session->id,
 'status' => 'completed',
 'duration_seconds' => 45,
 ]);
 $this->assertDatabaseHas('game_answers', [
 'game_session_id' => $session->id,
 'question_index' => 0,
 ]);

 $gameAnswer = GameAnswer::where('game_session_id', $session->id)->firstOrFail();
 $this->assertSame('voice', $gameAnswer->response_mode);
 $this->assertNotEmpty($gameAnswer->voice_recording_path);
 Storage::disk('local')->assertExists($gameAnswer->voice_recording_path);

 $this->assertDatabaseCount('interview_sessions', 0);
 $this->assertDatabaseCount('interview_answers', 0);

 $certificateResponse = $this->actingAs($user)
 ->get(route('user.game.certificate.download', $category));
 $certificateResponse->assertOk();
 $certificateResponse->assertHeader('content-type', 'application/pdf');
 $this->assertStringStartsWith('%PDF-1.4', $certificateResponse->getContent());
 $this->assertStringContainsString('/Type /Catalog', $certificateResponse->getContent());
 $this->assertDatabaseHas('game_certificates', [
 'user_id' => $user->id,
 'category_id' => $category->id,
 'final_game_level_id' => $level->id,
 ]);

 $profile = Profile::where('user_id', $user->id)->firstOrFail();
 $scoreCount = Score::count();
 $progressUpdatedAt = GameProgress::where('user_id', $user->id)
 ->where('game_level_id', $level->id)
 ->firstOrFail()
 ->updated_at;

 $this->actingAs($user)
 ->post(route('user.game.finish'), ['game_session_id' => $session->id])
 ->assertRedirect(route('user.learning', ['category_id' => $category->id]))
 ->assertSessionHas('success')
 ->assertSessionHas('game_result');

 $this->assertSame($scoreCount, Score::count());
 $this->assertSame($profile->experience_points, Profile::where('user_id', $user->id)->firstOrFail()->experience_points);
 $this->assertTrue($progressUpdatedAt->equalTo(
 GameProgress::where('user_id', $user->id)
 ->where('game_level_id', $level->id)
 ->firstOrFail()
 ->updated_at
 ));
 }

 public function test_game_certificate_download_requires_completed_path(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category(['type' => 'game']);
 $this->gameLevel($category);

 $this->actingAs($user)
 ->get(route('user.game.certificate.download', $category))
 ->assertForbidden();

 $this->assertDatabaseCount('game_certificates', 0);
 }

 public function test_regular_interview_finish_ignores_stale_game_level_session_key(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category();
 $gameCategory = $this->category(['title' => 'Game Path', 'type' => 'game']);
 $level = $this->gameLevel($gameCategory);
 $session = $this->sessionFor($user, $category);
 $question = $this->question($category, ['interview_session_id' => $session->id]);

 $answer = InterviewAnswer::create([
 'interview_session_id' => $session->id,
 'question_id' => $question->id,
 'answer_text' => 'I built a deployment checklist and improved release quality with clearer ownership.',
 'response_mode' => 'text',
 ]);
 Http::fake([
 'api.openai.com/*' => Http::response([
 'choices' => [[
 'finish_reason' => 'stop',
 'message' => [
 'content' => json_encode([
 'per_question_feedback' => [[
 'id' => $answer->id,
 'score' => 82,
 'clarity_score' => 82,
 'relevance_score' => 82,
 'grammar_score' => 82,
 'professionalism_score' => 82,
 'star_applicable' => true,
 'star_method_score' => 75,
 'evidence_quotes' => ['I built a deployment checklist and improved release quality with clearer ownership'],
 'question_focus' => 'Describe a difficult project.',
 'answer_alignment' => 'directly_addressed',
 'missing_criteria' => [],
 'ai_feedback' => 'For "Describe a difficult project.", you stated "I built a deployment checklist and improved release quality with clearer ownership", which gives project detail. The review is tied to deployment and checklist from this answer.',
 'better_sample_answer' => 'I would answer: I built a deployment checklist and improved release quality with clearer ownership.',
 'follow_up_question' => 'What final result or detail from this project would make it stronger?',
 'coaching' => [
 'keep' => 'Keep the deployment checklist detail for "Describe a difficult project.".',
 'improve' => 'Add the final project result for "Describe a difficult project.".',
 'next_try' => 'Answer "Describe a difficult project." by connecting the checklist work to the project result.',
 'next_attempt_steps' => [
 'Start with the difficult project context.',
 'Use "I built a deployment checklist and improved release quality with clearer ownership" as support.',
 ],
 'success_check' => 'The retry links the deployment checklist to a clear project result.',
 ],
 ]],
 'session_feedback' => [
 'strengths' => 'The AI review used saved project details to identify what worked.',
 'weaknesses' => 'The answer could add the final result from the same project.',
 'improvement_suggestions' => 'Keep the project action and add the outcome only if it is true.',
 ],
 ]),
 ],
 ]],
 ], 200),
 ]);

 $this->actingAs($user)
 ->withSession([
 'active_interview_id' => $session->id,
 'active_interview_provider' => 'openai',
 'game_level_id' => $level->id,
 'active_interview_context' => 'learning_game',
 ])
 ->post(route('interview.finish'), [
 'session_id' => $session->id,
 'duration_seconds' => 60,
 ])
 ->assertRedirect(route('user.review', $session->id))
 ->assertSessionMissing('game_result');

 $this->assertDatabaseMissing('game_progress', [
 'user_id' => $user->id,
 'game_level_id' => $level->id,
 ]);
 }

 public function test_voice_game_answers_are_cleaned_before_save(): void
 {
 Storage::fake('local');

 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category(['type' => 'game']);
 $level = $this->gameLevel($category);

 $this->actingAs($user)
 ->post(route('user.game.start', $level))
 ->assertRedirect(route('user.game.match'));

 $session = GameSession::where('user_id', $user->id)->firstOrFail();

 $this->actingAs($user)
 ->post(route('user.game.answer'), [
 'game_session_id' => $session->id,
 'question_index' => 0,
 'answer_text' => 'I handled customer concern I handled customer concern and solved it solved it',
 'response_mode' => 'voice',
 'voice_duration' => 18,
 'voice_recording_duration_seconds' => 18,
 'voice_recording_transcription_status' => 'transcribed',
 'voice_audio' => UploadedFile::fake()
 ->createWithContent('challenge-answer.webm', str_repeat('A', 4096))
 ->mimeType('audio/webm'),
 ])
 ->assertOk()
 ->assertJson(['success' => true]);

 $answer = GameAnswer::where('game_session_id', $session->id)->firstOrFail();

 $this->assertSame('I handled customer concern and solved it', $answer->answer_text);
 $this->assertSame('voice', $answer->response_mode);
 $this->assertSame('local', $answer->voice_recording_disk);
 $this->assertSame('audio/webm', $answer->voice_recording_mime_type);
 $this->assertSame('transcribed', $answer->voice_recording_transcription_status);
 Storage::disk('local')->assertExists($answer->voice_recording_path);

 $this->actingAs($user)
 ->get(route('user.game.answer.voiceRecording', $answer))
 ->assertOk()
 ->assertHeader('Content-Type', 'audio/webm')
 ->assertHeader('Accept-Ranges', 'bytes');

 $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $this->actingAs($otherUser)
 ->get(route('user.game.answer.voiceRecording', $answer))
 ->assertForbidden();
 }

 public function test_game_answer_requires_recorded_voice_for_challenge_session(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category(['type' => 'game']);
 $level = $this->gameLevel($category);

 $this->actingAs($user)
 ->post(route('user.game.start', $level))
 ->assertRedirect(route('user.game.match'));

 $session = GameSession::where('user_id', $user->id)->firstOrFail();
 $this->assertSame('voice', $session->response_mode);

 $this->actingAs($user)
 ->postJson(route('user.game.answer'), [
 'game_session_id' => $session->id,
 'question_index' => 0,
 'answer_text' => 'This is typed only and should not count for voice mode.',
 'response_mode' => 'text',
 ])
 ->assertStatus(422)
 ->assertJsonValidationErrors('voice_audio')
 ->assertJsonPath('message', 'Please record your voice answer before submitting this challenge response.');

 $this->assertDatabaseCount('game_answers', 0);
 }

 public function test_game_match_rejects_regular_interview_even_with_stale_game_key(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category();
 $gameCategory = $this->category(['title' => 'Game Route', 'type' => 'game']);
 $level = $this->gameLevel($gameCategory);
 $session = $this->sessionFor($user, $category);

 $this->actingAs($user)
 ->withSession([
 'active_interview_id' => $session->id,
 'game_level_id' => $level->id,
 'active_interview_context' => 'learning_game',
 ])
 ->get(route('user.game.match'))
 ->assertRedirect(route('user.learning'))
 ->assertSessionHas('error', 'No active Learning Game found.');
 }

 public function test_learning_page_renders_game_result_modal_actions(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => 2]);
 $category = $this->category(['type' => 'game']);
 $level = $this->gameLevel($category, [
 'success_criteria' => "1. Give context.\n2. Explain action.\n3. State result.",
 'retry_hint' => 'Add a stronger measurable result.',
 ]);
 $session = GameSession::create([
 'user_id' => $user->id,
 'game_level_id' => $level->id,
 'status' => 'completed',
 'questions' => ['Describe a goal answer.'],
 'num_questions' => 1,
 ]);

 $this->actingAs($user)
 ->withSession([
 'game_result' => [
 'game_session_id' => $session->id,
 'level_id' => $level->id,
 'level_number' => $level->level_number,
 'level_title' => $level->title,
 'skill_focus' => 'STAR Method',
 'learning_objective' => 'Use a complete STAR answer.',
 'success_criteria' => ['Give context.', 'Explain action.', 'State result.'],
 'status' => 'failed',
 'message' => 'You scored 65% and need 80% to clear this level.',
 'score' => 65,
 'required_score' => 80,
 'points_to_goal' => 15,
 'best_score' => 65,
 'is_new_best' => true,
 'xp_earned' => 100,
 'energy_spent' => 1,
 'energy_remaining' => 2,
 'retry_hint' => 'Add a stronger measurable result.',
 'retry_energy_cost' => 1,
 'can_retry' => true,
 'next_level' => null,
 'goal_breakdown' => [
 'averages' => [
 'goal_coverage' => 60,
 'clarity' => 70,
 'confidence' => 62,
 ],
 'ai_feedback_scorecard' => [
 'title' => 'AI Feedback Scorecard',
 'summary' => 'Scored 65%. Focus first on goal coverage, then retry with a clearer result.',
 'metrics' => [
 'clarity' => [
 'label' => 'Clarity',
 'score' => 70,
 'level' => 'Competent',
 'feedback' => 'Usable signal. Add one sharper proof point to lift it.',
 ],
 'confidence' => [
 'label' => 'Confidence',
 'score' => 62,
 'level' => 'Needs Work',
 'feedback' => 'Use first-person ownership and name the action or decision you took.',
 ],
 ],
 'priority_actions' => [
 'Add a measurable result before retrying.',
 ],
 'question_feedback' => [
 [
 'question_index' => 0,
 'score' => 65,
 'feedback' => 'Tie the answer to a result the interviewer can verify.',
 ],
 ],
 'reliability_score' => 82,
 'reliability_band' => 'Moderate',
 'evidence_policy' => 'Based only on submitted challenge answers; camera estimates are excluded.',
 'guidance_note' => 'Use this as coaching guidance, not a guarantee of real hiring performance.',
 'body_language_included' => false,
 ],
 ],
 ],
 ])
 ->get(route('user.learning', ['category_id' => $category->id]))
 ->assertOk()
 ->assertSee('id="gameResultModal"', false)
 ->assertSee('Needs Retry')
 ->assertSee('15 more points needed')
 ->assertSee('Retry Level')
 ->assertSee('Goal Score Breakdown')
 ->assertSee('AI Feedback Scorecard')
 ->assertSee('Reliability')
 ->assertSee('Confidence')
 ->assertSee('Based only on submitted challenge answers')
 ->assertSee('Tie the answer to a result the interviewer can verify.')
 ->assertDontSee('View Feedback')
 ->assertSee('Add a stronger measurable result.');
 }

 public function test_learning_game_session_renders_game_only_finish_modal(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
 $category = $this->category(['type' => 'game']);
 $level = $this->gameLevel($category);
 $session = GameSession::create([
 'user_id' => $user->id,
 'game_level_id' => $level->id,
 'status' => 'in_progress',
 'num_questions' => 2,
 'questions' => ['Describe a goal answer.', 'Close with your strongest proof.'],
 'response_mode' => 'hybrid',
 'current_question_index' => 1,
 ]);
 GameAnswer::create([
 'game_session_id' => $session->id,
 'question_index' => 1,
 'question_text' => 'Close with your strongest proof.',
 'answer_text' => 'Saved answer about a customer escalation result.',
 'response_mode' => 'text',
 'elapsed_seconds' => 12,
 ]);

 $this->actingAs($user)
 ->withSession([
 'active_game_session_id' => $session->id,
 'game_level_id' => $level->id,
 ])
 ->get(route('user.game.match'))
 ->assertOk()
 ->assertSee('Finish Challenge')
 ->assertSee('let currentQIdx = 1;', false)
 ->assertSee('Saved answer about a customer escalation result.')
 ->assertSee('learning-game-interview-layout', false)
 ->assertSee('session-main-only', false)
 ->assertSee('desktop-session-two-column', false)
 ->assertSee('desktop-response-column', false)
 ->assertSee('id="questionCaptionText"', false)
 ->assertSee('hud-mode-badge', false)
 ->assertSee('enterGameMatchFullscreen({ auto: true })', false)
 ->assertSee('game-match-auto-fullscreen', false)
 ->assertSee('id="challengeFinishModal"', false)
 ->assertSee('Scoring Challenge')
 ->assertDontSee('Challenge Brief')
 ->assertDontSee('AI Visualizer')
 ->assertDontSee('STAR Analyzer')
 ->assertDontSee('Voice Analytics')
 ->assertDontSee('Challenge Notes')
 ->assertDontSee('Finish Interview');

 $this->actingAs($user)
 ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
 ->withSession([
 'active_game_session_id' => $session->id,
 'game_level_id' => $level->id,
 ])
 ->get(route('user.game.match'))
 ->assertOk()
 ->assertSee('class="user-mobile-shell mobile-shell"', false)
 ->assertSee('css/mobile/user/game-session.css?v=1', false)
 ->assertSee('data-page-style="user-game-session"', false)
 ->assertSee('id="gameSessionControls"', false)
 ->assertSee('learning-game-interview-layout', false)
 ->assertSee('session-main-only', false)
 ->assertSee('desktop-session-two-column', false)
 ->assertSee('hud-mode-badge', false)
 ->assertSee('enterGameMatchFullscreen({ auto: true })', false)
 ->assertSee('game-match-auto-fullscreen', false)
 ->assertSee('response-panel', false)
 ->assertDontSee('Challenge Brief')
 ->assertDontSee('AI Visualizer')
 ->assertDontSee('STAR Analyzer')
 ->assertDontSee('Voice Analytics')
 ->assertDontSee('Challenge Notes');
 }

 public function test_inactive_user_session_is_logged_out_by_user_middleware(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'inactive']);

 $this->actingAs($user)
 ->get(route('dashboard'))
 ->assertRedirect('/');

 $this->assertGuest();
 }

 private function category(array $overrides = []): Category
 {
 return Category::create(array_merge([
 'title' => 'Job Interview',
 'description' => 'Job interview questions',
 'status' => 'active',
 'type' => 'core',
 ], $overrides));
 }

 private function question(Category $category, array $overrides = []): Question
 {
 return Question::create(array_merge([
 'category_id' => $category->id,
 'question_text' => 'Describe a difficult project.',
 'difficulty' => 'medium',
 'type' => 'Behavioral',
 'status' => 'active',
 ], $overrides));
 }

 private function sessionFor(User $user, Category $category, array $overrides = []): InterviewSession
 {
 return InterviewSession::create(array_merge([
 'user_id' => $user->id,
 'category_id' => $category->id,
 'difficulty' => 'medium',
 'target_position' => 'Developer',
 'num_questions' => 1,
 'coach_focus_mode' => 'balanced',
 'response_mode' => 'text',
 'status' => 'in_progress',
 ], $overrides));
 }

 private function gameLevel(Category $category, array $overrides = []): GameLevel
 {
 return GameLevel::create(array_merge([
 'category_id' => $category->id,
 'level_number' => 1,
 'title' => 'Opening Challenge',
 'description' => 'Practice a concise response.',
 'mission_text' => 'Tell me about yourself.',
 'target_position' => 'Developer',
 'difficulty' => 'beginner',
 'required_score' => 80,
 'xp_reward' => 100,
 'energy_cost' => 1,
 'is_hidden' => false,
 ], $overrides));
 }

 private function interviewPayload(Category $category): array
 {
 return [
 'category_id' => $category->id,
 'difficulty' => 'medium',
 'target_position' => 'Developer',
 'num_questions' => 5,
 'response_mode' => 'text',
 'time_limit' => 0,
 'ai_provider' => 'local',
 ];
 }

 private function dropInterviewRuntimeTables(): void
 {
 Schema::disableForeignKeyConstraints();

 try {
 foreach ([
 'feedback_audit_logs',
 'feedback_complaints',
 'mentor_review_comments',
 'feedback',
 'scores',
 'interview_answers',
 'questions',
 'interview_sessions',
 ] as $table) {
 Schema::dropIfExists($table);
 }
 } finally {
 Schema::enableForeignKeyConstraints();
 }
 }

 private function assertInterviewRuntimeTablesReady(): void
 {
 foreach ([
 'interview_sessions',
 'questions',
 'interview_answers',
 'scores',
 ] as $table) {
 $this->assertTrue(Schema::hasTable($table), "Expected {$table} to be repaired.");
 }

 foreach ([
 'user_id',
 'game_level_id',
 'category_id',
 'difficulty',
 'target_position',
 'resume_text',
 'job_description',
 'num_questions',
 'coach_focus_mode',
 'response_mode',
 'interview_focus',
 'time_limit',
 'question_types',
 'ai_assistance_level',
 'live_feedback_mode',
 'pressure_mode',
 'assessment_mode',
 'accommodation_profile',
 'score_eligible',
 'status',
 'notes',
 'duration_seconds',
 'current_question_index',
 'session_state',
 'action_plan',
 'is_archived',
 'flag_reason',
 'share_token',
 'share_expires_at',
 'share_password_hash',
 'share_permissions',
 'share_hide_sensitive',
 'is_public',
 'created_at',
 'updated_at',
 ] as $column) {
 $this->assertTrue(Schema::hasColumn('interview_sessions', $column), "Expected interview_sessions.{$column} to be repaired.");
 }
 }
}
