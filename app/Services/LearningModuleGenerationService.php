<?php

namespace App\Services;

use App\Models\Category;
use App\Models\LearningModule;
use App\Support\LearningModuleSchema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LearningModuleGenerationService
{
 private const TARGET_MODULE_COUNT = 5;

 private const MODULE_SPECS = [
 [
 'key' => 'intro_role_fit',
 'title' => 'Introduction and Role Fit',
 'difficulty' => 'Beginner',
 'skill_focus' => 'Clarity',
 'theme' => 'introducing yourself, explaining motivation, and connecting background to the target role',
 ],
 [
 'key' => 'star_examples',
 'title' => 'STAR Behavioral Examples',
 'difficulty' => 'Beginner',
 'skill_focus' => 'STAR Method',
 'theme' => 'building concise STAR stories for teamwork, pressure, mistakes, conflict, and achievement questions',
 ],
 [
 'key' => 'role_skills',
 'title' => 'Role Skills and Work Evidence',
 'difficulty' => 'Intermediate',
 'skill_focus' => 'Job Evidence Match',
 'theme' => 'turning responsibilities, tools, projects, school work, internships, or work history into role evidence',
 ],
 [
 'key' => 'ph_hr_questions',
 'title' => 'HR Questions',
 'difficulty' => 'Intermediate',
 'skill_focus' => 'Professionalism',
 'theme' => 'answering salary expectation, availability, work setup, weakness, pressure, and local HR screening questions',
 ],
 [
 'key' => 'final_mock_readiness',
 'title' => 'Final Mock Interview Readiness',
 'difficulty' => 'Advanced',
 'skill_focus' => 'Confidence',
 'theme' => 'rehearsing a complete interview flow with opening, evidence, questions, and closing lines',
 ],
 ];

 public function ensureAiModulesForPosition(string $position): array
 {
 @set_time_limit(300);

 LearningModuleSchema::ensure();

 $position = app(ChallengePositionService::class)->clean($position);
 if ($position === '') {
 return [
 'modules' => collect(),
 'created_count' => 0,
 'used_ai' => false,
 ];
 }

 $existingModules = $this->publishedPositionModules($position);
 if ($existingModules->count() >= self::TARGET_MODULE_COUNT) {
 return [
 'modules' => $existingModules,
 'created_count' => 0,
 'used_ai' => false,
 ];
 }

 $categoryTitle = $this->positionCategoryTitle($position);
 $this->ensureLearningCategory($categoryTitle, $position);

 $slotsToCreate = self::TARGET_MODULE_COUNT - $existingModules->count();
 $missingSpecs = $this->missingSpecs($position)
 ->take($slotsToCreate)
 ->values();

 if ($missingSpecs->isEmpty()) {
 return [
 'modules' => $this->publishedPositionModules($position),
 'created_count' => 0,
 'used_ai' => false,
 ];
 }

 $aiDrafts = [];
 $usedAi = false;

 try {
 $aiDrafts = $this->generateAiDrafts($position, $categoryTitle, $missingSpecs);
 $usedAi = $aiDrafts!== [];
 } catch (\Throwable $e) {
 Log::warning('AI learning module generation failed; using guarded fallback modules.', [
 'position' => $position,
 'error' => $e->getMessage(),
 ]);
 }

 $createdCount = 0;
 $draftsByKey = $this->keyDraftsBySpec($aiDrafts);
 $usedAi = $missingSpecs->contains(fn (array $spec): bool => isset($draftsByKey[$spec['key']]));

 foreach ($missingSpecs as $spec) {
 if ($this->generatedSpecExists($position, $spec['key'])) {
 continue;
 }

 $moduleData = $this->normalizeGeneratedModuleData(
 $draftsByKey[$spec['key']]?? null,
 $position,
 $categoryTitle,
 $spec
 );

 DB::transaction(function () use ($moduleData, $position, $categoryTitle): void {
 $module = LearningModule::create([
 'title' => $moduleData['title'],
 'description' => $moduleData['description'],
 'type' => 'article',
 'career_path' => $position,
 'category' => $categoryTitle,
 'difficulty' => $moduleData['difficulty'],
 'status' => 'published',
 'views' => 0,
 'is_featured' => false,
 'mapped_skills' => $moduleData['mapped_skills'],
 ]);

 foreach ($moduleData['chapters'] as $index => $chapter) {
 $module->chapters()->create([
 'title' => $chapter['title'],
 'content' => $chapter['content'],
 'order' => $index + 1,
 ]);
 }

 $module->activities()->create([
 'title' => $moduleData['activity']['title'],
 'type' => 'written-practice',
 'description' => $moduleData['activity']['description'],
 ]);
 });

 $createdCount++;
 }

 return [
 'modules' => $this->publishedPositionModules($position),
 'created_count' => $createdCount,
 'used_ai' => $usedAi && $createdCount > 0,
 ];
 }

 private function publishedPositionModules(string $position): Collection
 {
 $normalizedPosition = Str::of($position)->lower()->toString();
 $normalizedCategory = Str::of($this->positionCategoryTitle($position))->lower()->toString();

 return LearningModule::where('status', 'published')
 ->where(function ($query) use ($normalizedPosition, $normalizedCategory): void {
 $query
 ->whereRaw('LOWER(career_path) =?', [$normalizedPosition])
 ->orWhereRaw('LOWER(category) =?', [$normalizedCategory]);
 })
 ->orderBy('created_at', 'desc')
 ->get();
 }

 private function missingSpecs(string $position): Collection
 {
 return collect(self::MODULE_SPECS)
 ->reject(fn (array $spec): bool => $this->generatedSpecExists($position, $spec['key']))
 ->values();
 }

 private function generatedSpecExists(string $position, string $specKey): bool
 {
 $normalizedPosition = Str::of($position)->lower()->toString();
 $marker = $this->specMarker($specKey);

 return LearningModule::where('status', 'published')
 ->whereRaw('LOWER(career_path) =?', [$normalizedPosition])
 ->where('mapped_skills', 'LIKE', '%'.$marker.'%')
 ->exists();
 }

 private function generateAiDrafts(string $position, string $categoryTitle, Collection $specs): array
 {
 if (app()->environment('testing') &&! filter_var(env('AI_LIVE_TESTS', false), FILTER_VALIDATE_BOOL)) {
 return [];
 }

 $specPayload = $specs
 ->map(fn (array $spec): array => [
 'key' => $spec['key'],
 'title_theme' => $spec['title'],
 'difficulty' => $spec['difficulty'],
 'skill_focus' => $spec['skill_focus'],
 'content_theme' => $spec['theme'],
 ])
 ->values()
 ->all();

 $prompt = 'Create role-specific learning modules for SpeakReady AI interview preparation.
Target position: '.$position.'
Category to use for every module: '.$categoryTitle.'
Generate exactly one module for each item in this JSON array: '.json_encode($specPayload).'
Focus on hiring conversations, HR screening, fresh graduate and career-shifter examples, BPO or local workplace context when relevant, and action steps the learner can complete before marking the module done.
Return ONLY one JSON object with this exact shape:
{
 "modules": [
 {
 "key": "same key from the input item",
 "title": "Specific module title for the target position",
 "description": "One short action-focused summary",
 "difficulty": "Beginner, Intermediate, or Advanced",
 "mapped_skills": ["Clarity", "STAR Method", "Job Evidence Match"],
 "chapters": [
 {
 "title": "Chapter title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Include concrete preparation tasks, answer patterns, and completion checks."
 },
 {
 "title": "Chapter title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br."
 }
 ],
 "activity": {
 "title": "Short practice activity title",
 "description": "A writing or rehearsal task the learner can complete for this module"
 }
 }
 ]
}';

 $jsonResponse = AIService::generateJson($prompt, AIService::defaultProviderKey());
 $data = json_decode($jsonResponse, true);

 if (! is_array($data)) {
 return [];
 }

 $modules = $data['modules']?? [];

 return is_array($modules)? $modules: [];
 }

 private function keyDraftsBySpec(array $drafts): array
 {
 $keyed = [];

 foreach ($drafts as $draft) {
 if (! is_array($draft)) {
 continue;
 }

 $key = trim((string) ($draft['key']?? ''));
 if ($key!== '') {
 $keyed[$key] = $draft;
 }
 }

 return $keyed;
 }

 private function normalizeGeneratedModuleData(?array $moduleData, string $position, string $categoryTitle, array $spec): array
 {
 $fallback = $this->fallbackModuleData($position, $categoryTitle, $spec);
 $moduleData = is_array($moduleData)? array_merge($fallback, $moduleData): $fallback;

 $moduleData['title'] = $this->cleanText($moduleData['title']?? $fallback['title'], $fallback['title'], 255);
 $moduleData['description'] = $this->cleanText($moduleData['description']?? $fallback['description'], $fallback['description'], 1000);
 $moduleData['difficulty'] = $this->normalizeDifficulty($moduleData['difficulty']?? $spec['difficulty']);
 $moduleData['mapped_skills'] = $this->normalizeMappedSkills($moduleData['mapped_skills']?? [], $position, $spec);
 $moduleData['chapters'] = $this->normalizeChapters($moduleData['chapters']?? [], $position, $spec, $fallback['chapters']);
 $moduleData['activity'] = $this->normalizeActivity($moduleData['activity']?? [], $position, $spec, $fallback['activity']);

 return $moduleData;
 }

 private function fallbackModuleData(string $position, string $categoryTitle, array $spec): array
 {
 $title = "{$position} Interview: {$spec['title']}";
 $theme = $spec['theme'];
 $skill = $spec['skill_focus'];

 return [
 'title' => $title,
 'description' => "A role-specific interview module where {$position} applicants prepare, rehearse, and check {$theme}.",
 'difficulty' => $spec['difficulty'],
 'category' => $categoryTitle,
 'mapped_skills' => [$position, $skill],
 'chapters' => [
 [
 'title' => 'Prepare Role-Specific Proof',
 'content' => "<h3>Prepare Role-Specific Proof</h3><p>Write one honest {$position} example that fits a HR or hiring panel conversation about {$theme}.</p><ul><li>Name the situation in one sentence.</li><li>List the action you personally took.</li><li>Add a result, lesson, customer impact, school output, or work evidence you can explain clearly.</li></ul>",
 ],
 [
 'title' => 'Rehearse the Answer Pattern',
 'content' => "<h3>Rehearse the Answer Pattern</h3><p>Turn your proof into a short answer with context, action, result, and a closing line connected to the {$position} role.</p><ul><li>Keep the answer under two minutes.</li><li>Replace vague claims with concrete details.</li><li>Mark this module complete only after the answer sounds natural aloud and shows {$skill}.</li></ul>",
 ],
 ],
 'activity' => [
 'title' => "{$spec['title']} Practice",
 'description' => "Draft and rehearse one {$position} interview answer about {$theme}. Record your strongest version or write the final script before moving on.",
 ],
 ];
 }

 private function normalizeChapters(array $chapters, string $position, array $spec, array $fallbackChapters): array
 {
 $normalized = collect($chapters)
 ->filter(fn ($chapter): bool => is_array($chapter))
 ->map(function (array $chapter, int $index) use ($position, $spec): array {
 $fallbackTitle = 'Chapter '.($index + 1).': '.$spec['title'];
 $title = $this->cleanText($chapter['title']?? $fallbackTitle, $fallbackTitle, 255);
 $content = $this->cleanHtml($chapter['content']?? '');

 if ($content === '') {
 $content = "<h3>{$title}</h3><p>Prepare one concrete {$position} interview answer that demonstrates {$spec['skill_focus']} for this role.</p>";
 }

 return [
 'title' => $title,
 'content' => $content,
 ];
 })
 ->values()
 ->take(4)
 ->all();

 if (count($normalized) < 2) {
 $normalized = $fallbackChapters;
 }

 return $normalized;
 }

 private function normalizeActivity(array $activity, string $position, array $spec, array $fallbackActivity): array
 {
 $title = $this->cleanText($activity['title']?? $fallbackActivity['title'], $fallbackActivity['title'], 255);
 $description = $this->cleanText($activity['description']?? $fallbackActivity['description'], $fallbackActivity['description'], 1000);

 if ($description === '') {
 $description = "Write and rehearse one {$position} answer that shows {$spec['skill_focus']}.";
 }

 return [
 'title' => $title,
 'description' => $description,
 ];
 }

 private function normalizeMappedSkills($skills, string $position, array $spec): array
 {
 $skills = is_array($skills)? $skills: [];
 $skills[] = $position;
 $skills[] = $spec['skill_focus'];
 $skills[] = $this->specMarker($spec['key']);

 return collect($skills)
 ->filter(fn ($skill): bool => is_scalar($skill))
 ->map(fn ($skill): string => $this->cleanText((string) $skill, '', 80))
 ->filter()
 ->unique(fn (string $skill): string => Str::lower($skill))
 ->values()
 ->all();
 }

 private function normalizeDifficulty($difficulty): string
 {
 $difficulty = Str::of((string) $difficulty)->lower()->squish()->toString();

 return match ($difficulty) {
 'intermediate' => 'Intermediate',
 'advanced' => 'Advanced',
 default => 'Beginner',
 };
 }

 private function cleanText($value, string $fallback, int $maxLength = 255): string
 {
 $text = Str::of((string) $value)
 ->stripTags()
 ->replaceMatches('/\s+/', ' ')
 ->trim()
 ->limit($maxLength, '')
 ->toString();

 return $text!== ''? $text: $fallback;
 }

 private function cleanHtml($value): string
 {
 $html = (string) $value;
 $html = preg_replace('/<(script|style|iframe|object|embed|form|input|button)\b[^>]*>.*?<\/\1>/is', '', $html)?? '';
 $html = preg_replace('/\son[a-z]+\s*=\s*(["\']).*?\1/is', '', $html)?? '';
 $html = preg_replace('/\s(href|src)\s*=\s*(["\'])\s*javascript:.*?\2/is', '', $html)?? '';
 $html = strip_tags($html, '<h3><p><ul><ol><li><strong><em><br>');
 $html = trim($html);

 return Str::limit($html, 8000, '');
 }

 private function ensureLearningCategory(string $title, string $position): void
 {
 if (! Schema::hasTable('categories')) {
 return;
 }

 $attributes = ['title' => $title];
 $values = ['description' => "AI-generated interview learning modules for {$position}."];

 if (Schema::hasColumn('categories', 'type')) {
 $attributes['type'] = 'learning';
 }

 if (Schema::hasColumn('categories', 'status')) {
 $values['status'] = 'active';
 }

 Category::firstOrCreate(
 $attributes,
 $values
 );
 }

 private function positionCategoryTitle(string $position): string
 {
 $position = $this->cleanText($position, 'Target Position', 180);

 return "Interview Modules - {$position}";
 }

 private function specMarker(string $specKey): string
 {
 return "ai_module_spec:{$specKey}";
 }
}
