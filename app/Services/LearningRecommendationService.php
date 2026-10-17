<?php

namespace App\Services;

use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\LearningProgress;
use App\Models\Score;
use App\Support\LearningModuleSchema;
use App\Support\ScoreSchema;
use Illuminate\Support\Collection;

class LearningRecommendationService
{
 private const SKILL_RULES = [
 'clarity' => [
 'label' => 'Clarity',
 'column' => 'clarity_score',
 'icon' => 'fa-comment-dots',
 'color' => '#3b82f6',
 'action' => 'Practice clearer, shorter answers',
 'reason' => 'your clarity score is one of your lowest areas',
 'keywords' => ['clarity', 'clear', 'concise', 'structure', 'organized', 'communication', 'explain', 'answer structure'],
 ],
 'relevance' => [
 'label' => 'Relevance',
 'column' => 'relevance_score',
 'icon' => 'fa-bullseye',
 'color' => '#06b6d4',
 'action' => 'Keep answers tightly connected to the question',
 'reason' => 'your answers need stronger question alignment',
 'keywords' => ['relevance', 'relevant', 'question', 'alignment', 'focus', 'specific', 'targeted', 'answer'],
 ],
 'grammar' => [
 'label' => 'Grammar',
 'column' => 'grammar_score',
 'icon' => 'fa-spell-check',
 'color' => '#22c55e',
 'action' => 'Improve grammar and sentence control',
 'reason' => 'grammar is limiting how polished your answers sound',
 'keywords' => ['grammar', 'sentence', 'language', 'fluency', 'word choice', 'communication', 'english'],
 ],
 'professionalism' => [
 'label' => 'Professionalism',
 'column' => 'professionalism_score',
 'icon' => 'fa-user-tie',
 'color' => '#8b5cf6',
 'action' => 'Strengthen professional tone and confidence',
 'reason' => 'your professional delivery can be improved',
 'keywords' => ['professional', 'professionalism', 'tone', 'confidence', 'presence', 'behavior', 'etiquette'],
 ],
 'confidence' => [
 'label' => 'Confidence',
 'column' => 'confidence_score',
 'icon' => 'fa-microphone-lines',
 'color' => '#f59e0b',
 'action' => 'Build a steadier speaking delivery',
 'reason' => 'your confidence or delivery score needs attention',
 'keywords' => ['confidence', 'delivery', 'voice', 'speaking', 'pace', 'filler', 'rehearsal', 'presentation'],
 'optional' => true,
 ],
 'star_method' => [
 'label' => 'STAR Method',
 'column' => 'star_method_score',
 'icon' => 'fa-list-check',
 'color' => '#ef4444',
 'action' => 'Use STAR to make evidence stronger',
 'reason' => 'your examples need clearer situation, action, and result evidence',
 'keywords' => ['star', 'situation', 'task', 'action', 'result', 'behavioral', 'example', 'evidence', 'story'],
 'optional' => true,
 ],
 ];

 private const FEEDBACK_KEYWORDS = [
 'clarity' => ['unclear', 'confusing', 'long', 'wordy', 'structure', 'organized', 'concise'],
 'relevance' => ['irrelevant', 'off topic', 'not answer', 'focus', 'specific', 'question'],
 'grammar' => ['grammar', 'sentence', 'pronunciation', 'fluency', 'language'],
 'professionalism' => ['professional', 'tone', 'attitude', 'formal', 'credibility'],
 'confidence' => ['confidence', 'nervous', 'hesitation', 'filler', 'pace', 'voice', 'delivery'],
 'star_method' => ['star', 'situation', 'task', 'action', 'result', 'example', 'impact'],
 ];

 public function forUser(int $userId, int $limit = 4,?Collection $modules = null): Collection
 {
 LearningModuleSchema::ensure();
 ScoreSchema::ensure();

 $limit = max(1, $limit);
 $progressByModule = LearningProgress::where('user_id', $userId)
 ->get()
 ->keyBy('learning_module_id');

 $signals = $this->signalsForUser($userId);
 $modules??= LearningModule::where('status', 'published')->get();

 if ($modules->isEmpty()) {
 return collect();
 }

 $scored = $modules
 ->map(fn (LearningModule $module) => $this->scoreModule($module, $signals, $progressByModule))
 ->filter(fn (object $item) => $item->score > 0)
 ->sortByDesc('score')
 ->values();

 if ($scored->isEmpty()) {
 $scored = $modules
 ->map(fn (LearningModule $module) => $this->fallbackItem($module, $progressByModule))
 ->sortByDesc('score')
 ->values();
 }

 return $scored
 ->unique(fn (object $item) => $item->module->id)
 ->take($limit)
 ->values()
 ->map(fn (object $item) => $this->recommendationFromScoredItem($item));
 }

 public function learningPathsForUser(int $userId,?Collection $modules = null): Collection
 {
 LearningModuleSchema::ensure();

 $progressByModule = LearningProgress::where('user_id', $userId)
 ->get()
 ->keyBy('learning_module_id');

 $modules??= LearningModule::where('status', 'published')->get();

 return $modules
 ->groupBy(fn (LearningModule $module) => $this->pathNameFor($module))
 ->map(function (Collection $modules, string $path) use ($progressByModule) {
 $total = $modules->count();
 $category = trim((string) ($modules->first()?->category?? ''));
 $completed = $modules->filter(function (LearningModule $module) use ($progressByModule) {
 return (int) ($progressByModule->get($module->id)?->progress_percentage?? 0) >= 100;
 })->count();
 $progress = $total > 0? (int) round(($completed / $total) * 100): 0;

 return (object) [
 'title' => $path,
 'total' => $total,
 'completed' => $completed,
 'progress' => $progress,
 'url' => $category!== ''? route('user.modules.index', ['category' => $category]): route('user.modules.index'),
 ];
 })
 ->sort(function (object $a, object $b) {
 $aComplete = $a->progress >= 100;
 $bComplete = $b->progress >= 100;

 if ($aComplete!== $bComplete) {
 return $aComplete? 1: -1;
 }

 if ($a->progress!== $b->progress) {
 return $a->progress <=> $b->progress;
 }

 return $b->total <=> $a->total;
 })
 ->values();
 }

 private function signalsForUser(int $userId): Collection
 {
 $sessions = InterviewSession::where('user_id', $userId)
 ->where('interview_sessions.status', 'completed')
 ->whereHas('score')
 ->with(['score', 'feedback', 'category'])
 ->latest()
 ->take(8)
 ->get();

 if ($sessions->isEmpty()) {
 return collect([
 $this->signal('clarity', 45, 'Start with answer clarity before your first scored interview.'),
 $this->signal('star_method', 40, 'Learn STAR early so your practice answers include evidence.'),
 ]);
 }

 $sessions = $sessions
 ->filter(fn (InterviewSession $session): bool => $this->sessionSignalsQualified($session))
 ->values();

 if ($sessions->isEmpty()) {
 return collect([
 $this->signal('clarity', 45, 'Start with answer clarity until a 95% reliable scored interview is available.'),
 $this->signal('star_method', 40, 'Learn STAR early so your practice answers include verifiable evidence.'),
 ]);
 }

 $scoreSignals = $this->scoreSignals($sessions);
 $feedbackSignals = $this->feedbackSignals($sessions);
 $categorySignals = $this->categorySignals($sessions);

 return $scoreSignals
 ->merge($feedbackSignals)
 ->merge($categorySignals)
 ->groupBy('skill')
 ->map(function (Collection $group) {
 $best = $group->sortByDesc('priority')->first();

 return $best;
 })
 ->sortByDesc('priority')
 ->values()
 ->take(5);
 }

 private function scoreSignals(Collection $sessions): Collection
 {
 $signals = collect();
 $latestScore = $sessions->first()?->score;

 foreach (self::SKILL_RULES as $skill => $rule) {
 $column = $rule['column'];

 if (! Score::hasColumn($column)) {
 continue;
 }

 $values = $sessions
 ->pluck('score')
 ->map(fn (?Score $score) => $score? $this->numericScore($score->getAttribute($column)): null)
 ->filter(fn ($score) => $score!== null)
 ->values();

 if ($values->isEmpty()) {
 continue;
 }

 if (($rule['optional']?? false) && $values->every(fn (int $value) => $value === 0)) {
 continue;
 }

 $latest = $this->numericScore($latestScore?->getAttribute($column));
 $average = (int) round($values->avg());
 $basis = $latest!== null && $latest > 0? min($latest, $average): $average;

 if ($basis <= 78) {
 $priority = max(1, 90 - $basis);
 $signals->push($this->signal(
 $skill,
 $priority,
 "{$rule['label']} score is at {$basis}%, so this module is a good next step.",
 true
 ));
 }
 }

 if ($signals->isEmpty()) {
 $signals->push($this->signal(
 'star_method',
 20,
 'Your scores are strong; a STAR practice module can help make answers more evidence-based.',
 true
 ));
 }

 return $signals;
 }

 private function feedbackSignals(Collection $sessions): Collection
 {
 $text = $sessions
 ->pluck('feedback')
 ->filter()
 ->map(fn ($feedback) => trim(($feedback->weaknesses?? '').' '.($feedback->improvement_suggestions?? '')))
 ->implode(' ');

 $text = $this->normalize($text);

 if ($text === '') {
 return collect();
 }

 return collect(self::FEEDBACK_KEYWORDS)
 ->map(function (array $keywords, string $skill) use ($text) {
 $hits = collect($keywords)->filter(fn (string $keyword) => str_contains($text, $keyword))->count();

 if ($hits === 0) {
 return null;
 }

 return $this->signal($skill, 28 + ($hits * 4), 'Recent feedback mentions '.self::SKILL_RULES[$skill]['label'].'.', true);
 })
 ->filter()
 ->values();
 }

 private function categorySignals(Collection $sessions): Collection
 {
 return $sessions
 ->pluck('category.title')
 ->filter()
 ->map(fn (string $title) => $this->normalize($title))
 ->flatMap(function (string $category) {
 if (str_contains($category, 'behavior')) {
 return [$this->signal('star_method', 24, 'Your recent interview category benefits from STAR evidence.', true)];
 }

 if (str_contains($category, 'technical')) {
 return [$this->signal('relevance', 20, 'Technical interviews benefit from tighter answer-to-question alignment.', true)];
 }

 if (str_contains($category, 'communication')) {
 return [$this->signal('clarity', 20, 'Communication practice benefits from clear answer structure.', true)];
 }

 return [];
 });
 }

 private function scoreModule(LearningModule $module, Collection $signals, Collection $progressByModule): object
 {
 $text = $this->moduleSearchText($module);
 $progress = (int) ($progressByModule->get($module->id)?->progress_percentage?? 0);
 $bestSignal = null;
 $score = 0;

 foreach ($signals as $signal) {
 $rule = self::SKILL_RULES[$signal->skill]?? null;

 if (! $rule) {
 continue;
 }

 $hits = collect($rule['keywords'])
 ->filter(fn (string $keyword) => str_contains($text, $this->normalize($keyword)))
 ->count();

 $mappedSkillHit = $this->moduleHasMappedSkill($module, $signal->skill, $rule['label']);
 $signalScore = ($hits * 14) + ($mappedSkillHit? 34: 0);

 if ($signalScore <= 0) {
 continue;
 }

 $signalScore += $signal->priority;

 if ($progress >= 100) {
 $signalScore -= 22;
 } elseif ($progress > 0) {
 $signalScore += 8;
 } else {
 $signalScore += 12;
 }

 if ($module->is_featured) {
 $signalScore += 6;
 }

 $signalScore += min(8, (int) floor(((int) $module->views) / 25));
 $score = max($score, $signalScore);

 if (! $bestSignal || $signalScore > $bestSignal->score) {
 $bestSignal = (object) [
 'score' => $signalScore,
 'skill' => $signal->skill,
 'reason' => $signal->reason,
 'qualified' => (bool) ($signal->qualified?? false),
 ];
 }
 }

 return (object) [
 'module' => $module,
 'score' => $score,
 'signal' => $bestSignal,
 'progress' => $progress,
 ];
 }

 private function fallbackItem(LearningModule $module, Collection $progressByModule): object
 {
 $progress = (int) ($progressByModule->get($module->id)?->progress_percentage?? 0);
 $score = ($module->is_featured? 40: 20) + min(20, (int) floor(((int) $module->views) / 10));

 if ($progress >= 100) {
 $score -= 15;
 } elseif ($progress > 0) {
 $score += 10;
 }

 return (object) [
 'module' => $module,
 'score' => $score,
 'signal' => (object) [
 'skill' => 'clarity',
 'reason' => 'this is a useful starting module for interview preparation',
 ],
 'progress' => $progress,
 ];
 }

 private function recommendationFromScoredItem(object $item): object
 {
 $skill = $item->signal?->skill?? 'clarity';
 $rule = self::SKILL_RULES[$skill]?? self::SKILL_RULES['clarity'];
 $module = $item->module;
 $reason = trim((string) ($item->signal?->reason?? $rule['reason']));
 $reason = rtrim($reason, ". \t\n\r\0\x0B").'.';

 return (object) [
 'icon' => $rule['icon'],
 'color' => $rule['color'],
 'skill' => $rule['label'],
 'text' => "{$rule['action']}: {$module->title}",
 'reason' => ucfirst($reason),
 'module' => $module,
 'progress' => $item->progress,
 'url' => route('user.modules.show', $module->id),
 'quality_gate_passed' => (bool) ($item->signal?->qualified?? false),
 'decision_support_only' => ! (bool) ($item->signal?->qualified?? false),
 'accuracy_guaranteed' => false,
 ];
 }

 private function signal(string $skill, int $priority, string $reason, bool $qualified = false): object
 {
 return (object) [
 'skill' => $skill,
 'priority' => $priority,
 'reason' => $reason,
 'qualified' => $qualified,
 ];
 }

 private function sessionSignalsQualified(InterviewSession $session): bool
 {
 $required = app(AlgorithmReliabilityService::class)->minimumReliabilityPercent();
 $score = $session->score;
 $rubricGatePassed = data_get($score?->rubric, 'algorithm_quality.passed') === true;
 $scoreConfidence = is_numeric($score?->scoring_confidence)
 ? max(0, min(100, (int) round((float) $score->scoring_confidence)))
 : 0;

 if ($rubricGatePassed && $scoreConfidence >= $required) {
 return true;
 }

 $summary = is_array($session->feedback?->coaching_summary)
 ? $session->feedback->coaching_summary
 : [];
 $reviewPassed = data_get($summary, 'review_context.detailed_review_validation.status') === 'passed';
 $reviewReliability = data_get($summary, 'review_context.detailed_review_validation.minimum_scoreable_reliability_percent');

 return $reviewPassed
 && is_numeric($reviewReliability)
 && (int) round((float) $reviewReliability) >= $required;
 }

 private function moduleSearchText(LearningModule $module): string
 {
 $parts = [
 $module->title,
 $module->description,
 $module->type,
 $module->category,
 $module->difficulty,
 $module->career_path?? '',
 implode(' ', $this->mappedSkills($module)),
 ];

 return $this->normalize(implode(' ', array_filter($parts)));
 }

 private function moduleHasMappedSkill(LearningModule $module, string $skill, string $label): bool
 {
 $mapped = collect($this->mappedSkills($module))
 ->map(fn (string $mappedSkill) => $this->normalize($mappedSkill));

 return $mapped->contains($this->normalize($skill))
 || $mapped->contains($this->normalize($label));
 }

 private function mappedSkills(LearningModule $module): array
 {
 $skills = $module->mapped_skills?? [];

 if (is_string($skills)) {
 $decoded = json_decode($skills, true);
 $skills = is_array($decoded)? $decoded: [$skills];
 }

 if (! is_array($skills)) {
 return [];
 }

 return collect($skills)
 ->filter(fn ($skill) => is_scalar($skill))
 ->map(fn ($skill) => (string) $skill)
 ->values()
 ->all();
 }

 private function pathNameFor(LearningModule $module): string
 {
 $path = trim((string) ($module->career_path?? ''));

 if ($path!== '') {
 return $path;
 }

 $category = trim((string) $module->category);

 return $category!== ''? $category: 'Core Interview Skills';
 }

 private function numericScore($value):?int
 {
 if (! is_numeric($value)) {
 return null;
 }

 return max(0, min(100, (int) round($value)));
 }

 private function normalize(string $value): string
 {
 return mb_strtolower(trim($value));
 }
}
