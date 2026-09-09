<?php

namespace App\Services;

use App\Models\Category;
use App\Models\GameLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LearningChallengeGenerationService
{
 private const DEFAULT_GAME_GENERATION_BATCH_SIZE = 5;

 private?array $gameLevelColumns = null;

 public function ensureAiJourneyForPosition(?Category $requestedCategory, string $position): array
 {
 @set_time_limit(300);

 $challengePositions = app(ChallengePositionService::class);
 $position = $challengePositions->clean($position);

 $category = $this->resolvePositionJourneyCategory($requestedCategory, $position);
 $createdCount = $this->generateMissingJourneyLevels($category, $position);
 $levels = $this->positionJourneyLevels($category, $position);

 return [
 'category' => $category->fresh()?? $category,
 'levels' => $levels,
 'created_count' => $createdCount,
 ];
 }

 private function resolvePositionJourneyCategory(?Category $requestedCategory, string $position): Category
 {
 if ($requestedCategory && $this->canUseRequestedCategory($requestedCategory, $position)) {
 return $requestedCategory;
 }

 return $this->findOrCreatePositionCategory($position);
 }

 private function canUseRequestedCategory(Category $category, string $position): bool
 {
 if ($category->type!== 'game' || $category->status!== 'active') {
 return false;
 }

 if ($this->isPositionCategory($category, $position)) {
 return true;
 }

 $levels = $this->visibleLevels($category);
 $journeyLevels = app(ChallengePositionService::class)->journeyLevels($levels);
 if ($journeyLevels->isEmpty()) {
 return false;
 }

 $specificLevels = app(ChallengePositionService::class)->journeyLevels(
 app(ChallengePositionService::class)->specificMatchingLevels($journeyLevels, $position)
 );
 if ($specificLevels->isEmpty()) {
 return false;
 }

 return $this->conflictingJourneyLevelNumbers($journeyLevels, $position)->isEmpty();
 }

 private function findOrCreatePositionCategory(string $position): Category
 {
 $title = $this->positionCategoryTitle($position);
 $category = Category::firstOrNew([
 'title' => $title,
 'type' => 'game',
 ]);

 if (! $category->exists) {
 $category->description = "AI-generated five-level interview challenge journey for {$position}.";
 $category->sort_order = ((int) Category::where('type', 'game')->max('sort_order')) + 1;
 }

 $category->status = 'active';
 $category->save();

 return $category;
 }

 private function isPositionCategory(Category $category, string $position): bool
 {
 return Str::lower((string) $category->title) === Str::lower($this->positionCategoryTitle($position));
 }

 private function positionCategoryTitle(string $position): string
 {
 $position = $this->cleanText($position, 'Target Position', 180);

 return "Interview Challenges - {$position}";
 }

 private function generateMissingJourneyLevels(Category $category, string $position): int
 {
 $levels = $this->visibleLevels($category);
 $journeyLevels = app(ChallengePositionService::class)->journeyLevels($levels);
 $existingRelatedLevels = $this->positionJourneyLevels($category, $position, $levels);

 if ($existingRelatedLevels->count() >= ChallengePositionService::JOURNEY_MAX_LEVEL_NUMBER) {
 return 0;
 }

 $occupiedLevelNumbers = $journeyLevels
 ->pluck('level_number')
 ->mapWithKeys(fn ($levelNumber) => [(int) $levelNumber => true]);

 $slots = [];
 for ($levelNumber = 1; $levelNumber <= ChallengePositionService::JOURNEY_MAX_LEVEL_NUMBER; $levelNumber++) {
 if ($occupiedLevelNumbers->has($levelNumber)) {
 continue;
 }

 $slots[] = [
 'level_number' => $levelNumber,
 'difficulty' => $this->difficultyForJourneyLevel($levelNumber),
 'generation_number' => $levelNumber,
 'total_levels' => ChallengePositionService::JOURNEY_MAX_LEVEL_NUMBER,
 'level_theme' => $this->themeForJourneyLevel($levelNumber, $position),
 ];
 }

 if ($slots === []) {
 return 0;
 }

 $topic = $this->topicForPosition($position);
 $createdCount = 0;

 foreach (array_chunk($slots, $this->gameGenerationBatchSize()) as $slotBatch) {
 $aiDrafts = [];

 try {
 $aiDrafts = AIService::generateGames($topic, $slotBatch, AIService::defaultProviderKey());
 } catch (\Throwable $e) {
 Log::warning('AI challenge journey generation failed; using guarded fallback levels.', [
 'position' => $position,
 'category_id' => $category->id,
 'levels' => array_column($slotBatch, 'level_number'),
 'error' => $e->getMessage(),
 ]);
 }

 $draftsByGenerationNumber = $this->keyGeneratedGamesByGenerationNumber($aiDrafts);

 foreach ($slotBatch as $slot) {
 if (GameLevel::where('category_id', $category->id)
 ->where('level_number', (int) $slot['level_number'])
 ->exists()) {
 continue;
 }

 $generationNumber = (int) $slot['generation_number'];
 $difficulty = (string) $slot['difficulty'];
 $gameData = $this->normalizeGeneratedGameData(
 $draftsByGenerationNumber[$generationNumber]?? null,
 $position,
 $difficulty,
 $generationNumber
 );

 $gameData['level_number'] = (int) $slot['level_number'];
 $gameData['category_id'] = $category->id;
 $gameData['difficulty'] = $difficulty;
 $gameData['target_position'] = $position;
 $gameData['is_hidden'] = false;

 GameLevel::create($this->gameLevelDataForCurrentSchema($gameData));
 $createdCount++;
 }
 }

 return $createdCount;
 }

 private function positionJourneyLevels(Category $category, string $position,?Collection $levels = null): Collection
 {
 $levels??= $this->visibleLevels($category);
 $challengePositions = app(ChallengePositionService::class);

 return $challengePositions->journeyLevels(
 $challengePositions->relatedLevels($levels, $position)
 );
 }

 private function visibleLevels(Category $category): Collection
 {
 return GameLevel::where('category_id', $category->id)
 ->where('is_hidden', false)
 ->orderBy('level_number')
 ->orderBy('id')
 ->get();
 }

 private function conflictingJourneyLevelNumbers(Collection $journeyLevels, string $position): Collection
 {
 $challengePositions = app(ChallengePositionService::class);
 $relatedLevels = $challengePositions->specificMatchingLevels($journeyLevels, $position);
 $relatedIds = $relatedLevels->pluck('id')->mapWithKeys(fn ($id) => [(int) $id => true]);

 return $journeyLevels
 ->reject(fn (GameLevel $level): bool => $relatedIds->has((int) $level->id))
 ->pluck('level_number')
 ->values();
 }

 private function keyGeneratedGamesByGenerationNumber(array $aiDrafts): array
 {
 $levels = $aiDrafts['levels']?? $aiDrafts;

 if (! is_array($levels)) {
 return [];
 }

 $keyedDrafts = [];

 foreach ($levels as $index => $draft) {
 if (! is_array($draft)) {
 continue;
 }

 $generationNumber = (int) ($draft['generation_number']?? ($index + 1));

 if ($generationNumber > 0) {
 $keyedDrafts[$generationNumber] = $draft;
 }
 }

 return $keyedDrafts;
 }

 private function normalizeGeneratedGameData(?array $gameData, string $position, string $difficulty, int $levelNumber): array
 {
 $fallback = $this->fallbackGameData($position, $difficulty, $levelNumber);
 $gameData = is_array($gameData)? array_merge($fallback, $gameData): $fallback;

 $gameData['title'] = $this->cleanText($gameData['title']?? $fallback['title'], $fallback['title'], 255);
 $gameData['description'] = $this->cleanText($gameData['description']?? $fallback['description'], $fallback['description'], 1000);
 $gameData['mission_text'] = $this->cleanText($gameData['mission_text']?? $fallback['mission_text'], $fallback['mission_text'], 3000);
 $gameData['target_position'] = $position;
 $gameData['skill_focus'] = $this->cleanText($gameData['skill_focus']?? $fallback['skill_focus'], $fallback['skill_focus'], 255);
 $gameData['learning_objective'] = $this->cleanText($gameData['learning_objective']?? $fallback['learning_objective'], $fallback['learning_objective'], 1000);
 $gameData['success_criteria'] = $this->cleanText($gameData['success_criteria']?? $fallback['success_criteria'], $fallback['success_criteria'], 2000);
 $gameData['retry_hint'] = $this->cleanText($gameData['retry_hint']?? $fallback['retry_hint'], $fallback['retry_hint'], 1000);
 $gameData['ai_persona'] = $this->cleanText($gameData['ai_persona']?? $fallback['ai_persona'], $fallback['ai_persona'], 255);
 $gameData['ai_custom_prompt'] = $this->cleanText($gameData['ai_custom_prompt']?? $fallback['ai_custom_prompt'], $fallback['ai_custom_prompt'], 3000);
 $gameData['banned_words'] = $this->cleanText($gameData['banned_words']?? $fallback['banned_words'], $fallback['banned_words'], 255);
 $gameData['target_tone'] = $this->cleanText($gameData['target_tone']?? $fallback['target_tone'], $fallback['target_tone'], 255);
 $gameData['custom_badge_name'] = $this->cleanText($gameData['custom_badge_name']?? $fallback['custom_badge_name'], $fallback['custom_badge_name'], 255);
 $gameData['skill_xp_type'] = $this->cleanText($gameData['skill_xp_type']?? $fallback['skill_xp_type'], $fallback['skill_xp_type'], 255);

 $gameData['required_score'] = max(0, min(100, (int) ($gameData['required_score']?? $fallback['required_score'])));
 $gameData['xp_reward'] = max(0, (int) ($gameData['xp_reward']?? $fallback['xp_reward']));
 $gameData['energy_cost'] = max(0, (int) ($gameData['energy_cost']?? $fallback['energy_cost']));
 $gameData['time_limit_seconds'] = isset($gameData['time_limit_seconds'])? max(0, (int) $gameData['time_limit_seconds']): null;
 $gameData['skill_xp_amount'] = max(0, (int) ($gameData['skill_xp_amount']?? $fallback['skill_xp_amount']));

 return $gameData;
 }

 private function fallbackGameData(string $position, string $difficulty, int $levelNumber): array
 {
 $theme = $this->themeForJourneyLevel($levelNumber, $position);
 $titleDifficulty = ucfirst($difficulty);

 return [
 'title' => "{$titleDifficulty} {$position} Interview Level {$levelNumber}",
 'description' => "A role-focused {$position} interview challenge for {$theme}.",
 'mission_text' => $this->fallbackMissionText($position, $levelNumber),
 'target_position' => $position,
 'skill_focus' => $this->skillFocusForJourneyLevel($levelNumber),
 'learning_objective' => "Practice {$theme} for a realistic local {$position} interview.",
 'success_criteria' => "1. Answer the question directly.\n2. Use details relevant to {$position}.\n3. Give one concrete school, work, internship, project, or training example.\n4. Explain your action or decision clearly.\n5. End with a result, lesson, or role-fit takeaway.",
 'retry_hint' => "Choose one real {$position} example first, then answer with context, action, result, and what it proves about your readiness.",
 'difficulty' => $difficulty,
 'required_score' => $difficulty === 'advanced'? 90: ($difficulty === 'intermediate'? 78: 65),
 'xp_reward' => $difficulty === 'advanced'? 750: ($difficulty === 'intermediate'? 600: 450),
 'energy_cost' => $difficulty === 'advanced'? 2: 1,
 'ai_persona' => 'Supportive local Interview Coach',
 'ai_custom_prompt' => "Act as a interviewer for {$position}. Keep questions role-specific, practical, professional, and grounded in local hiring expectations.",
 'time_limit_seconds' => $levelNumber === ChallengePositionService::JOURNEY_MAX_LEVEL_NUMBER? 180: 120,
 'banned_words' => 'um, like, basically',
 'target_tone' => $levelNumber >= 4? 'Professional': 'Confident',
 'custom_badge_name' => "{$position} Journey Level {$levelNumber}",
 'skill_xp_type' => $this->skillXpTypeForJourneyLevel($levelNumber),
 'skill_xp_amount' => $difficulty === 'advanced'? 75: 50,
 ];
 }

 private function fallbackMissionText(string $position, int $levelNumber): string
 {
 return match ($levelNumber) {
 1 => "1. Tell me about yourself and why you are interested in the {$position} position.\n2. What skills or training make you ready for this role?\n3. What part of the role do you understand best?\n4. What kind of work environment helps you perform well?\n5. Why should we consider you for this opportunity?",
 2 => "1. Tell me about a time you handled a challenge related to {$position} work.\n2. What was your responsibility in that situation?\n3. What specific action did you take?\n4. What result or lesson came from it?\n5. How will that experience help you in this role?",
 3 => "1. What tools, processes, or responsibilities are important for a {$position}?\n2. Describe a project, task, or training where you used a relevant skill.\n3. How do you check the quality of your work?\n4. How do you handle feedback or corrections?\n5. What skill do you want to strengthen next for this role?",
 4 => "1. What are your salary expectations for this {$position} role?\n2. Are you comfortable with the schedule, location, or work setup required?\n3. Tell me about a weakness you are actively improving.\n4. How do you handle pressure from customers, classmates, supervisors, or teammates?\n5. Why are you leaving or considering your current school, work, or training path?",
 default => "1. Please introduce yourself as if this were your final {$position} interview.\n2. Share your strongest example of role readiness.\n3. Answer one behavioral question using a clear situation, action, and result.\n4. Explain how you would handle a realistic problem in this role.\n5. Close by summarizing why you are a strong fit.",
 };
 }

 private function topicForPosition(string $position): string
 {
 return "Target position: {$position}. Build a five-level Interview Challenge Journey for this exact role. Level 1: introduction and motivation. Level 2: behavioral STAR evidence. Level 3: role skills, tools, responsibilities, and problem solving. Level 4: HR curveballs including salary expectations, availability, work setup, weakness, and pressure. Level 5: final mock interview readiness.";
 }

 private function difficultyForJourneyLevel(int $levelNumber): string
 {
 return match ($levelNumber) {
 1, 2 => 'beginner',
 3, 4 => 'intermediate',
 default => 'advanced',
 };
 }

 private function themeForJourneyLevel(int $levelNumber, string $position): string
 {
 return match ($levelNumber) {
 1 => "{$position} introduction, motivation, and basic role fit",
 2 => "{$position} behavioral evidence using STAR structure",
 3 => "{$position} role skills, tools, responsibilities, and problem solving",
 4 => "{$position} HR curveballs, salary expectations, availability, and pressure",
 default => "{$position} final mock interview readiness",
 };
 }

 private function skillFocusForJourneyLevel(int $levelNumber): string
 {
 return match ($levelNumber) {
 1 => 'Clarity',
 2 => 'STAR Method',
 3 => 'Role Evidence',
 4 => 'Professionalism',
 default => 'Interview Readiness',
 };
 }

 private function skillXpTypeForJourneyLevel(int $levelNumber): string
 {
 return match ($levelNumber) {
 3 => 'Problem Solving',
 default => 'Communication',
 };
 }

 private function gameGenerationBatchSize(): int
 {
 return max(1, min(10, (int) env('AI_GAME_BATCH_SIZE', self::DEFAULT_GAME_GENERATION_BATCH_SIZE)));
 }

 private function gameLevelDataForCurrentSchema(array $data): array
 {
 $columns = $this->currentGameLevelColumns();

 if ($columns === []) {
 return $data;
 }

 return array_intersect_key($data, array_flip($columns));
 }

 private function currentGameLevelColumns(): array
 {
 if ($this->gameLevelColumns!== null) {
 return $this->gameLevelColumns;
 }

 try {
 return $this->gameLevelColumns = Schema::getColumnListing((new GameLevel())->getTable());
 } catch (\Throwable $e) {
 Log::warning('Unable to inspect game level columns before saving generated challenge data.', [
 'error' => $e->getMessage(),
 ]);

 return $this->gameLevelColumns = [];
 }
 }

 private function cleanText(mixed $value, string $fallback, int $limit = 255): string
 {
 $text = str_replace(["\r\n", "\r"], "\n", $this->textValue($value));
 $lines = array_map(
 fn (string $line): string => trim(preg_replace('/[ \t]+/', ' ', $line)?? ''),
 explode("\n", $text)
 );
 $cleaned = trim(implode("\n", array_filter($lines, fn (string $line): bool => $line!== '')));

 if ($cleaned === '') {
 $cleaned = $fallback;
 }

 return mb_substr($cleaned, 0, $limit);
 }

 private function textValue(mixed $value): string
 {
 if ($value === null) {
 return '';
 }

 if (is_array($value)) {
 return $this->arrayTextValue($value);
 }

 if (is_object($value)) {
 return method_exists($value, '__toString')? (string) $value: $this->arrayTextValue((array) $value);
 }

 if (is_bool($value)) {
 return $value? 'true': 'false';
 }

 return (string) $value;
 }

 private function arrayTextValue(array $value): string
 {
 if ($value === []) {
 return '';
 }

 foreach (['text', 'question', 'criterion', 'criteria', 'item', 'items', 'value', 'name', 'title', 'description'] as $key) {
 if (array_key_exists($key, $value)) {
 return $this->textValue($value[$key]);
 }
 }

 $items = [];
 foreach ($value as $item) {
 $text = trim($this->textValue($item));
 if ($text!== '') {
 $items[] = $text;
 }
 }

 if ($items === []) {
 return '';
 }

 if (array_is_list($value) && count($items) > 1) {
 return implode("\n", array_map(
 fn (string $item, int $index): string => preg_match('/^\d+[\.)]\s+/', $item)? $item: ($index + 1).'. '.$item,
 $items,
 array_keys($items)
 ));
 }

 return implode("\n", $items);
 }
}
