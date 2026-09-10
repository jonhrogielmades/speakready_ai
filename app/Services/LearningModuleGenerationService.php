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
 private const TARGET_MODULE_COUNT = 4;
 private const TARGET_CHAPTER_COUNT = 10;
 private const MIN_CHAPTER_WORD_COUNT = 520;
 private const MIN_CHAPTER_PARAGRAPH_COUNT = 8;

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

 $this->retireExtraGeneratedModules($position);
 $existingGeneratedModules = $this->publishedGeneratedPositionModules($position);
 $this->enrichGeneratedModuleChapters($existingGeneratedModules, $position);
 $existingGeneratedModules = $this->publishedGeneratedPositionModules($position);

 if ($existingGeneratedModules->count() >= self::TARGET_MODULE_COUNT) {
 return [
 'modules' => $this->publishedPositionModules($position),
 'created_count' => 0,
 'used_ai' => false,
 ];
 }

 $categoryTitle = $this->positionCategoryTitle($position);
 $this->ensureLearningCategory($categoryTitle, $position);

 $slotsToCreate = self::TARGET_MODULE_COUNT - $existingGeneratedModules->count();
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

 private function publishedGeneratedPositionModules(string $position): Collection
 {
 $normalizedPosition = Str::of($position)->lower()->toString();

 return LearningModule::where('status', 'published')
 ->whereRaw('LOWER(career_path) =?', [$normalizedPosition])
 ->where('mapped_skills', 'LIKE', '%ai_module_spec:%')
 ->orderBy('created_at', 'desc')
 ->get();
 }

 private function missingSpecs(string $position): Collection
 {
 return $this->targetModuleSpecs()
 ->reject(fn (array $spec): bool => $this->generatedSpecExists($position, $spec['key']))
 ->values();
 }

 private function targetModuleSpecs(): Collection
 {
 return collect(self::MODULE_SPECS)
 ->take(self::TARGET_MODULE_COUNT)
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

 $drafts = [];

 foreach ($specs as $spec) {
 $prompt = 'Create one role-specific learning module for SpeakReady AI interview preparation.
Target position: '.$position.'
Category to use for this module: '.$categoryTitle.'
Module spec to generate: '.json_encode([
 'key' => $spec['key'],
 'title_theme' => $spec['title'],
 'difficulty' => $spec['difficulty'],
 'skill_focus' => $spec['skill_focus'],
 'content_theme' => $spec['theme'],
 ]).'
Focus on hiring conversations, HR screening, fresh graduate and career-shifter examples, BPO or local workplace context when relevant, and action steps the learner can complete before marking the module done.
Return ONLY one JSON object with this exact shape:
{
 "module": {
 "key": "same key from the input item",
 "title": "Specific module title for the target position",
 "description": "One short action-focused summary",
 "difficulty": "Beginner, Intermediate, or Advanced",
 "mapped_skills": ["Clarity", "STAR Method", "Job Evidence Match"],
 "chapters": [
 {
 "title": "Chapter 1 title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Write 650 to 900 words across at least 8 paragraphs. Include Interview context, Core lesson, Worked example, Answer framework, Practice drill, Common mistakes, Reflection, and Completion check."
 },
 {
 "title": "Chapter 2 title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Write 650 to 900 words across at least 8 paragraphs with concrete target-position context, detailed explanation, and practical exercises."
 },
 {
 "title": "Chapter 3 title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Write 650 to 900 words across at least 8 paragraphs with realistic interview dialogue, weak vs strong answer notes, and role-specific guidance."
 },
 {
 "title": "Chapter 4 title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Write 650 to 900 words across at least 8 paragraphs with rehearsal guidance, revision steps, and a completion check."
 },
 {
 "title": "Chapter 5 title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Write 650 to 900 words across at least 8 paragraphs with role-specific examples, practice prompts, and a completion check."
 },
 {
 "title": "Chapter 6 title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Write 650 to 900 words across at least 8 paragraphs with HR or screening context, answer patterns, and common mistakes."
 },
 {
 "title": "Chapter 7 title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Write 650 to 900 words across at least 8 paragraphs with pressure, weakness, or challenge examples when relevant."
 },
 {
 "title": "Chapter 8 title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Write 650 to 900 words across at least 8 paragraphs with evidence-building guidance for projects, tools, responsibilities, or school work."
 },
 {
 "title": "Chapter 9 title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Write 650 to 900 words across at least 8 paragraphs with mock-interview rehearsal guidance and follow-up question preparation."
 },
 {
 "title": "Chapter 10 title",
 "content": "Safe HTML using only h3, p, ul, ol, li, strong, em, and br. Write 650 to 900 words across at least 8 paragraphs with final review, revision checklist, and completion check."
 }
 ],
 "activity": {
 "title": "Short practice activity title",
 "description": "A writing or rehearsal task the learner can complete for this module"
 }
 }
}
This is one module in a 4-module generated set. The full set must never exceed 4 AI-generated modules for the target position. This module must have exactly 10 chapters. Every chapter must read like a real training module or short book chapter for '.$position.', not a short note. Use many paragraphs, detailed explanations, concrete examples, weak-versus-strong answer comparisons, practice exercises, and completion checks. Use fresh graduate, career-shifter, BPO, remote-work, school project, internship, or local workplace examples only when they naturally fit the target position. Keep the content practical: explain the interview situation, what the interviewer is listening for, how to structure the answer, what weak answers sound like, and how the learner knows the chapter is complete.';

 try {
 $jsonResponse = AIService::generateJson($prompt, AIService::defaultProviderKey());
 $data = json_decode($jsonResponse, true);

 if (! is_array($data)) {
 continue;
 }

 $module = $data['module']?? ($data['modules'][0]?? null);

 if (is_array($module)) {
 $module['key'] = $spec['key'];
 $drafts[] = $module;
 }
 } catch (\Throwable $e) {
 Log::warning('AI learning module draft generation failed for one module spec.', [
 'position' => $position,
 'spec_key' => $spec['key'],
 'error' => $e->getMessage(),
 ]);
 }
 }

 return $drafts;
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
 'chapters' => $this->fallbackChapters($position, $spec),
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
 $content = $this->chapterContextHtml($title, $position, $spec, $index);
 } else {
 $content = $this->ensureChapterHasEnoughContext($content, $title, $position, $spec, $index);
 }

 return [
 'title' => $title,
 'content' => $content,
 ];
 })
 ->values()
 ->take(self::TARGET_CHAPTER_COUNT)
 ->all();

 if (count($normalized) < self::TARGET_CHAPTER_COUNT) {
 $fallbackByTitle = collect($fallbackChapters)
 ->keyBy(fn (array $chapter): string => Str::lower($chapter['title']))
 ->reject(fn (array $chapter, string $title): bool => collect($normalized)->contains(fn (array $existing): bool => Str::lower($existing['title']) === $title))
 ->values();

 foreach ($fallbackByTitle as $fallbackChapter) {
 $normalized[] = $fallbackChapter;

 if (count($normalized) >= self::TARGET_CHAPTER_COUNT) {
 break;
 }
 }
 }

 return $normalized;
 }

 private function retireExtraGeneratedModules(string $position): void
 {
 $allowedSpecKeys = $this->targetModuleSpecs()
 ->pluck('key')
 ->all();
 $keptSpecKeys = [];

 foreach ($this->publishedGeneratedPositionModules($position) as $module) {
 $specKey = $this->moduleSpecKey($module);

 if ($specKey === null) {
 continue;
 }

 $shouldRetire = ! in_array($specKey, $allowedSpecKeys, true)
 || in_array($specKey, $keptSpecKeys, true)
 || count($keptSpecKeys) >= self::TARGET_MODULE_COUNT;

 if ($shouldRetire) {
 $module->update(['status' => 'draft']);
 continue;
 }

 $keptSpecKeys[] = $specKey;
 }
 }

 private function enrichGeneratedModuleChapters(Collection $modules, string $position): void
 {
 foreach ($modules as $module) {
 $spec = $this->specForModule($module);

 if ($spec === null) {
 continue;
 }

 $module->loadMissing('chapters');
 $chapters = $module->chapters->values();

 foreach ($chapters as $index => $chapter) {
 $fallbackTitle = 'Chapter '.($index + 1).': '.$spec['title'];
 $title = $this->cleanText($chapter->title?? $fallbackTitle, $fallbackTitle, 255);
 $content = $this->cleanHtml($chapter->content?? '');
 $enrichedContent = $content === ''
 ? $this->chapterContextHtml($title, $position, $spec, $index)
 : $this->ensureChapterHasEnoughContext($content, $title, $position, $spec, $index);

 if ($title!== $chapter->title || $enrichedContent!== $chapter->content) {
 $chapter->update([
 'title' => $title,
 'content' => $enrichedContent,
 ]);
 }
 }

 if ($chapters->count() >= self::TARGET_CHAPTER_COUNT) {
 continue;
 }

 $existingTitles = $chapters
 ->pluck('title')
 ->map(fn ($title): string => Str::lower((string) $title));
 $order = (int) ($chapters->max('order')?? 0);

 foreach ($this->fallbackChapters($position, $spec) as $fallbackChapter) {
 if ($existingTitles->contains(Str::lower($fallbackChapter['title']))) {
 continue;
 }

 $order++;
 $module->chapters()->create([
 'title' => $fallbackChapter['title'],
 'content' => $fallbackChapter['content'],
 'order' => $order,
 ]);
 $existingTitles->push(Str::lower($fallbackChapter['title']));

 if ($module->chapters()->count() >= self::TARGET_CHAPTER_COUNT) {
 break;
 }
 }
 }
 }

 private function fallbackChapters(string $position, array $spec): array
 {
 return [
 [
 'title' => 'Interview Context and Hiring Signals',
 'content' => $this->chapterContextHtml('Interview Context and Hiring Signals', $position, $spec, 0),
 ],
 [
 'title' => 'Target Role Research and Vocabulary',
 'content' => $this->chapterContextHtml('Target Role Research and Vocabulary', $position, $spec, 1),
 ],
 [
 'title' => 'Evidence Bank and Role Examples',
 'content' => $this->chapterContextHtml('Evidence Bank and Role Examples', $position, $spec, 2),
 ],
 [
 'title' => 'Answer Structure and Delivery',
 'content' => $this->chapterContextHtml('Answer Structure and Delivery', $position, $spec, 3),
 ],
 [
 'title' => 'STAR Stories for Behavioral Questions',
 'content' => $this->chapterContextHtml('STAR Stories for Behavioral Questions', $position, $spec, 4),
 ],
 [
 'title' => 'HR Screening and Professional Fit',
 'content' => $this->chapterContextHtml('HR Screening and Professional Fit', $position, $spec, 5),
 ],
 [
 'title' => 'Handling Pressure, Weakness, and Mistakes',
 'content' => $this->chapterContextHtml('Handling Pressure, Weakness, and Mistakes', $position, $spec, 6),
 ],
 [
 'title' => 'Technical or Role-Specific Proof',
 'content' => $this->chapterContextHtml('Technical or Role-Specific Proof', $position, $spec, 7),
 ],
 [
 'title' => 'Mock Interview Rehearsal and Follow-Ups',
 'content' => $this->chapterContextHtml('Mock Interview Rehearsal and Follow-Ups', $position, $spec, 8),
 ],
 [
 'title' => 'Final Review and Completion Check',
 'content' => $this->chapterContextHtml('Final Review and Completion Check', $position, $spec, 9),
 ],
 ];
 }

 private function ensureChapterHasEnoughContext(string $content, string $title, string $position, array $spec, int $index): string
 {
 $plainText = Str::of($content)->stripTags()->replaceMatches('/\s+/', ' ')->trim()->toString();
 $wordCount = str_word_count($plainText);
 $paragraphCount = preg_match_all('/<p\b/i', $content)?: 0;
 $lowerContent = Str::lower($plainText);
 $hasContextLabels = collect(['interview context:', 'core lesson:', 'worked example:', 'practice drill:', 'completion check:'])
 ->every(fn (string $label): bool => Str::contains($lowerContent, $label));

 if ($wordCount >= self::MIN_CHAPTER_WORD_COUNT && $paragraphCount >= self::MIN_CHAPTER_PARAGRAPH_COUNT && $hasContextLabels) {
 return $content;
 }

 return $content.$this->chapterContextAddonHtml($title, $position, $spec, $index);
 }

 private function chapterContextHtml(string $title, string $position, array $spec, int $index): string
 {
 $title = $this->htmlText($title);
 $prompt = $this->htmlText($this->samplePromptForChapter($position, $spec, $index));
 $position = $this->htmlText($position);
 $theme = $this->htmlText($spec['theme']?? 'the interview skill for this module');
 $skill = $this->htmlText($spec['skill_focus']?? 'interview readiness');

 return "<h3>{$title}</h3>".$this->chapterStudyBodyHtml($position, $theme, $skill, $prompt, $index);
 }

 private function chapterContextAddonHtml(string $title, string $position, array $spec, int $index): string
 {
 $title = $this->htmlText($title);
 $prompt = $this->htmlText($this->samplePromptForChapter($position, $spec, $index));
 $position = $this->htmlText($position);
 $theme = $this->htmlText($spec['theme']?? 'this interview skill');
 $skill = $this->htmlText($spec['skill_focus']?? 'interview readiness');

 return "<p><strong>Detailed study expansion:</strong> The original chapter notes for {$title} were brief, so this expanded lesson adds the deeper context, examples, and practice flow a learner would expect from a complete {$position} module.</p>"
 .$this->chapterStudyBodyHtml($position, $theme, $skill, $prompt, $index);
 }

 private function chapterStudyBodyHtml(string $position, string $theme, string $skill, string $prompt, int $index): string
 {
 $angle = match ($index % self::TARGET_CHAPTER_COUNT) {
 0 => "This lesson focuses on the first few minutes of the interview, when the panel is deciding whether your background, motivation, and communication style fit the work. It gives you enough context to answer opening questions with direction instead of reciting a memorized biography.",
 1 => "This lesson shows how to research the target role before writing answers. You will translate job-posting language, company expectations, and daily responsibilities into words you can use naturally during the interview.",
 2 => "This lesson helps you turn scattered experiences into a useful evidence bank. The goal is to make your school work, internship tasks, freelance jobs, customer conversations, projects, or previous employment easier to retrieve when a question suddenly asks for proof.",
 3 => "This lesson is about shaping raw experience into a clear answer. The interviewer should be able to follow your situation, understand your personal action, and hear the result without needing to rescue the story with follow-up questions.",
 4 => "This lesson gives behavioral questions a repeatable story structure. Instead of guessing what to say, you will prepare several flexible stories that can answer teamwork, pressure, mistake, conflict, and achievement prompts.",
 5 => "This lesson prepares you for HR screening moments where professionalism matters as much as content. Salary expectations, availability, work setup, schedule concerns, and motivation questions need direct answers with calm reasoning.",
 6 => "This lesson focuses on difficult questions about pressure, weakness, failure, feedback, or mistakes. The goal is to answer honestly while still showing accountability, learning, and readiness for the role.",
 7 => "This lesson turns technical, operational, or role-specific experience into proof. You will practice explaining tools, responsibilities, projects, processes, and outcomes in language a recruiter or hiring manager can understand.",
 8 => "This lesson turns preparation into mock interview rehearsal. It shows how to revise an answer, test it aloud, and build enough fluency that the final interview feels familiar even when the exact question is new.",
 9 => "This lesson closes the module with a final review routine. You will check your best answers, prepare follow-up responses, choose questions to ask the interviewer, and decide when the module is truly complete.",
 };
 $example = match ($index % self::TARGET_CHAPTER_COUNT) {
 0 => "For example, a learner might say, <em>I studied information systems and handled a capstone project where I coordinated testing and documentation. That experience made me interested in {$position} work because I enjoyed turning unclear requirements into organized action.</em> The answer works because it names a background, gives one piece of evidence, and connects motivation to the role.",
 1 => "For example, if a job post repeats words like documentation, stakeholder coordination, customer support, quality, deadlines, or reporting, the learner should not simply copy those words. A stronger approach is to prepare one honest story for each repeated expectation and explain how that experience connects to {$position}.",
 2 => "For example, a learner preparing for {$position} could list three proof points: a project where they solved a problem, a time they explained something clearly, and a moment when they handled pressure. Each proof point should include the situation, the action they owned, and the result or lesson.",
 3 => "A weak answer might say, <em>I am hardworking and I can learn fast.</em> A stronger answer would show the same quality through a short story: <em>When our project deadline changed, I split the remaining tasks, confirmed priorities with the team, and submitted the most important feature first.</em>",
 4 => "For example, one teamwork story can also answer a conflict question if the learner emphasizes listening, clarifying responsibilities, and reaching agreement. The story becomes more flexible when the learner knows which part to emphasize for each question.",
 5 => "For example, a weak salary answer might sound unsure or defensive. A stronger answer gives a range, mentions market awareness, and shows openness to the full offer. The answer is still polite, but it does not make the learner sound unprepared.",
 6 => "For example, when asked about a mistake, a learner should avoid blaming classmates, previous managers, customers, or tools. A stronger answer names the mistake briefly, explains the correction, and shows the process they now use to prevent the same issue.",
 7 => "For example, a learner might describe using a spreadsheet, ticketing tool, code repository, design file, CRM, or communication tracker. The important part is not name-dropping the tool; it is explaining the decision, workflow, and outcome the tool supported.",
 8 => "For example, after drafting an answer, the learner can record a two-minute version, listen for vague claims, then rewrite the opening line so it names the role and the evidence faster. That small revision often makes the answer sound more confident without making it sound scripted.",
 9 => "For example, the learner can prepare three closing questions: one about team expectations, one about success in the first months, and one about next steps. These questions show interest without pretending to know everything about the company.",
 };
 $practice = match ($index % self::TARGET_CHAPTER_COUNT) {
 0 => "Write a six-sentence version of your introduction. Sentence one names your current background. Sentence two names the experience most connected to {$position}. Sentence three explains what you learned. Sentence four connects that learning to {$theme}. Sentence five names what you want to contribute. Sentence six closes with confidence.",
 1 => "Find one job description for {$position} and copy five repeated expectations into your notes. Beside each expectation, write one experience that proves it. Replace any copied corporate wording with plain language you can say comfortably.",
 2 => "Create a two-column evidence bank. On the left, write common interview themes such as teamwork, pressure, conflict, learning, and achievement. On the right, write one concrete story for each theme. Keep the story titles short so you can remember them during the actual interview.",
 3 => "Take one answer and mark each sentence as context, action, result, or role connection. If the context is longer than the action, trim it. If the action does not use the word I, rewrite it so your personal contribution is clear.",
 4 => "Draft five story titles: teamwork, pressure, mistake, learning, and achievement. For each title, write three bullets only: the situation, your action, and the result. Then practice changing the emphasis to fit two different behavioral questions.",
 5 => "Write answers for salary, availability, work setup, and why this company. Keep each answer direct, professional, and short. Add one sentence of reasoning so the answer sounds prepared rather than memorized.",
 6 => "Choose one weakness or mistake and write the answer in three parts: honest issue, action taken, and current prevention habit. Remove excuses. Add one concrete behavior that proves growth.",
 7 => "Choose one project, tool, process, or responsibility connected to {$position}. Explain it once for a recruiter with no technical background and once for a hiring manager who may ask deeper follow-up questions.",
 8 => "Rehearse the answer three times with different constraints. First, give the complete two-minute version. Second, give a one-minute version. Third, answer a follow-up question that asks for more detail. This trains flexibility instead of memorization.",
 9 => "Build a final interview checklist with your introduction, three strongest examples, one weakness answer, one pressure answer, two role-specific proof points, and three questions for the interviewer. Practice the full set aloud.",
 };

 return "<p><strong>Interview context:</strong> In a {$position} interview, this chapter prepares you for questions about {$theme}. {$angle} The interviewer is listening for clear judgment, honest evidence, and a connection between what you have done before and what the role will require.</p>"
 ."<p><strong>Why this matters:</strong> Real interviews rarely reward long, general answers. They reward answers that help the interviewer picture how you think, communicate, recover from pressure, and learn from experience. When your answer has context and detail, the interviewer does not have to guess whether your claim is true.</p>"
 ."<p><strong>Core lesson:</strong> Treat every answer as a small teaching moment. Your job is not only to say that you are ready for {$position}; your job is to show the path that makes the claim believable. Start with the situation, narrow the focus to your own action, and end with a result, lesson, or decision that proves {$skill}.</p>"
 ."<p>Before writing the answer, decide what the interviewer is really testing. A question about weakness may be testing self-awareness. A question about pressure may be testing prioritization. A question about motivation may be testing role fit. Once you know the hidden test, you can choose a story that answers the real concern.</p>"
 ."<p><strong>Worked example:</strong> {$example}</p>"
 ."<p><strong>Sample interview prompt:</strong> {$prompt}</p>"
 ."<p>Build the answer like a short chapter with a beginning, middle, and ending. The beginning gives just enough context. The middle shows the decision or behavior you controlled. The ending explains the result and connects it back to {$position}. This keeps the answer detailed without becoming scattered.</p>"
 ."<ul><li><strong>Set the scene:</strong> name the situation, goal, audience, deadline, customer, class requirement, or business constraint in one clear sentence.</li><li><strong>Show your action:</strong> describe the communication, tool, process, decision, preparation, or behavior you personally used.</li><li><strong>Explain the result:</strong> include a measurable result, feedback received, quality improvement, lesson learned, or next step.</li><li><strong>Connect to the role:</strong> close with one sentence that explains how the example prepares you for {$position} responsibilities.</li></ul>"
 ."<p><strong>Answer framework:</strong> Use a simple four-part flow: context, action, result, and role connection. If the answer is behavioral, this can look like STAR. If it is an HR screening question, this can look like direct answer, brief reason, evidence, and professional close. The structure should support the answer, not make it sound robotic.</p>"
 ."<ol><li>Draft the answer in writing without trying to sound perfect.</li><li>Underline the sentence that proves your own contribution.</li><li>Remove background details that do not help the interviewer understand the result.</li><li>Add one role-specific phrase that links the story to {$position}.</li></ol>"
 ."<p><strong>Practice drill:</strong> {$practice} After writing, read it aloud and listen for two things: whether the answer sounds like something you would naturally say, and whether a hiring panel could repeat your main point after hearing it once.</p>"
 ."<p><strong>Common mistakes:</strong> Avoid giving only personality traits, hiding behind group language, apologizing for limited experience, or turning the answer into a long life story. A fresh graduate, career shifter, or first-time applicant can still give a strong answer by using honest evidence and explaining the lesson clearly.</p>"
 ."<p><strong>Reflection:</strong> Ask yourself what the interviewer would remember from this chapter of your answer. If the remembered message is only <em>I am willing to learn</em>, add proof. If the remembered message is a specific example of {$skill}, the answer is becoming stronger.</p>"
 ."<p><strong>Completion check:</strong> You are ready to move on when you can explain the idea without reading, deliver one complete answer in under two minutes, name at least one concrete detail, avoid vague claims, and clearly connect the answer to {$position}.</p>";
 }

 private function samplePromptForChapter(string $position, array $spec, int $index): string
 {
 $theme = $spec['theme']?? 'this interview skill';

 return match ($index % self::TARGET_CHAPTER_COUNT) {
 0 => "Tell me about your background and how it connects to {$position}.",
 1 => "What do you know about this {$position} role and why does it interest you?",
 2 => "Can you give an example that shows {$theme}?",
 3 => "Walk me through how you would structure an answer about {$theme}.",
 4 => "Tell me about a time you handled teamwork, pressure, conflict, or a mistake.",
 5 => "What are your salary expectations, availability, and preferred work setup?",
 6 => "What is a weakness or mistake you have worked to improve?",
 7 => "What project, tool, process, or responsibility best proves your readiness for {$position}?",
 8 => "Can we do a quick mock answer and then discuss one follow-up question?",
 default => "How would you apply this skill if you were hired as a {$position}?",
 };
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

 private function specForModule(LearningModule $module): ?array
 {
 $specKey = $this->moduleSpecKey($module);

 if ($specKey === null) {
 return null;
 }

 foreach (self::MODULE_SPECS as $spec) {
 if ($spec['key'] === $specKey) {
 return $spec;
 }
 }

 return null;
 }

 private function moduleSpecKey(LearningModule $module): ?string
 {
 $skills = is_array($module->mapped_skills)? $module->mapped_skills: [];

 foreach ($skills as $skill) {
 if (! is_string($skill) || ! Str::startsWith($skill, 'ai_module_spec:')) {
 continue;
 }

 return Str::of($skill)->after('ai_module_spec:')->toString();
 }

 return null;
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

 return Str::limit($html, 16000, '');
 }

 private function htmlText($value): string
 {
 return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
