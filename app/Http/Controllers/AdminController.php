<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Score;
use App\Models\LearningModule;
use App\Services\AIService;
use App\Services\CsvExportService;
use App\Services\QuestionDatasetProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
 public function dashboard()
 {
 $registeredUsersCount = $this->dashboardValue('registered_users', fn () => Schema::hasTable('users') ? \App\Models\User::count() : 0, 0);
 $onlineTodayCount = $this->dashboardValue('online_today', fn () => $this->onlineUserIds()->count(), 0);
 $mockInterviewsCount = $this->dashboardValue('mock_interviews', fn () => Schema::hasTable('interview_sessions') ? \App\Models\InterviewSession::count() : 0, 0);
 $aiFeedbacksCount = $this->dashboardValue('ai_feedbacks', fn () => Schema::hasTable('feedback') ? \App\Models\Feedback::count() : 0, 0);
 $modulesCompletedCount = $this->dashboardValue('modules_completed', function () {
 if (! $this->hasTableColumns('learning_progress', ['status'])) {
 return 0;
 }

 return \App\Models\LearningProgress::where('status', 'completed')->count();
 }, 0);
 $userUpdatesCount = $this->dashboardValue('user_updates', fn () => Schema::hasTable('activity_logs') ? \App\Models\ActivityLog::count() : 0, 0);
 $recentUserUpdates = $this->dashboardValue('recent_user_updates', function () {
 if (! Schema::hasTable('activity_logs')) {
 return collect();
 }

 return \App\Models\ActivityLog::with(Schema::hasTable('users') ? 'user' : [])
 ->latest('id')
 ->take(5)
 ->get();
 }, collect());

 $recentSessions = $this->dashboardValue('recent_sessions', function () {
 if (
 ! $this->hasTableColumns('interview_sessions', ['id', 'user_id', 'category_id'])
 || ! Schema::hasTable('users')
 || ! Schema::hasTable('categories')
 || ! Schema::hasTable('scores')
 ) {
 return collect();
 }

 $query = \App\Models\InterviewSession::with(['user', 'category', 'score']);
 $orderColumn = Schema::hasColumn('interview_sessions', 'created_at') ? 'created_at' : 'id';

 return $query
 ->orderBy($orderColumn, 'desc')
 ->take(5)
 ->get();
 }, collect());

 $usersNeedingSupport = $this->dashboardValue('users_needing_support', function () {
 if (
 ! $this->hasTableColumns('scores', ['interview_session_id', 'overall_readiness_score'])
 || ! $this->hasTableColumns('interview_sessions', ['id', 'user_id'])
 || ! $this->hasTableColumns('users', ['id', 'name'])
 ) {
 return collect();
 }

 return \App\Models\Score::select('scores.*', 'users.name as user_name', 'users.id as user_id')
 ->join('interview_sessions', 'scores.interview_session_id', '=', 'interview_sessions.id')
 ->join('users', 'interview_sessions.user_id', '=', 'users.id')
 ->where('scores.overall_readiness_score', '<', 60)
 ->readinessEligible()
 ->orderBy('scores.overall_readiness_score', 'asc')
 ->take(3)
 ->get();
 }, collect());

 $averageMetrics = $this->dashboardValue('average_metrics', function () {
 if (! $this->hasTableColumns('scores', ['clarity_score', 'relevance_score', 'grammar_score', 'professionalism_score'])) {
 return null;
 }

 return \App\Models\Score::readinessEligible()
 ->selectRaw('AVG(clarity_score) as clarity_score, AVG(relevance_score) as relevance_score, AVG(grammar_score) as grammar_score, AVG(professionalism_score) as professionalism_score')
 ->first();
 }, null);
 $avgClarity = round($averageMetrics->clarity_score?? 0);
 $avgRelevance = round($averageMetrics->relevance_score?? 0);
 $avgGrammar = round($averageMetrics->grammar_score?? 0);
 $avgProfessionalism = round($averageMetrics->professionalism_score?? 0);

 $categoriesDonut = $this->dashboardValue('categories_donut', function () {
 if (
 ! $this->hasTableColumns('interview_sessions', ['category_id'])
 || ! $this->hasTableColumns('categories', ['id', 'title'])
 ) {
 return collect();
 }

 return \App\Models\InterviewSession::join('categories', 'interview_sessions.category_id', '=', 'categories.id')
 ->selectRaw('categories.title as label, count(*) as count')
 ->groupBy('categories.title')
 ->get();
 }, collect());

 $chartLabels = $categoriesDonut->pluck('label');
 $chartData = $categoriesDonut->pluck('count');

 $readinessData = $this->dashboardValue('readiness_distribution', function () {
 if (! $this->hasTableColumns('scores', ['overall_readiness_score'])) {
 return [0, 0, 0, 0];
 }

 $readinessDistribution = \App\Models\Score::query()
 ->selectRaw('SUM(CASE WHEN overall_readiness_score >= 90 THEN 1 ELSE 0 END) as highly_accurate, SUM(CASE WHEN overall_readiness_score BETWEEN 70 AND 89 THEN 1 ELSE 0 END) as acceptable, SUM(CASE WHEN overall_readiness_score BETWEEN 50 AND 69 THEN 1 ELSE 0 END) as needs_improvement, SUM(CASE WHEN overall_readiness_score < 50 THEN 1 ELSE 0 END) as poor')
 ->first();

 return [
 (int) ($readinessDistribution->highly_accurate?? 0),
 (int) ($readinessDistribution->acceptable?? 0),
 (int) ($readinessDistribution->needs_improvement?? 0),
 (int) ($readinessDistribution->poor?? 0),
 ];
 }, [0, 0, 0, 0]);

 // User Growth (Last 6 months)
 $userGrowthLabels = [];
 $userGrowthData = [];
 $growthStart = now()->subMonths(5)->startOfMonth();
 $growthCounts = $this->dashboardValue('user_growth', function () use ($growthStart) {
 if (! $this->hasTableColumns('users', ['created_at'])) {
 return collect();
 }

 return \App\Models\User::where('created_at', '>=', $growthStart)
 ->get(['created_at'])
 ->groupBy(fn ($user) => $user->created_at?->format('Y-m'))
 ->map->count();
 }, collect());
 for ($i = 5; $i >= 0; $i--) {
 $date = now()->subMonths($i);
 $userGrowthLabels[] = $date->format('M');
 $userGrowthData[] = (int) ($growthCounts[$date->format('Y-m')]?? 0);
 }

 return $this->mobileView('admin.dashboard', compact(
 'registeredUsersCount',
 'onlineTodayCount',
 'mockInterviewsCount',
 'aiFeedbacksCount',
 'modulesCompletedCount',
 'userUpdatesCount',
 'recentUserUpdates',
 'recentSessions',
 'usersNeedingSupport',
 'avgClarity',
 'avgRelevance',
 'avgGrammar',
 'avgProfessionalism',
 'chartLabels',
 'chartData',
 'readinessData',
 'userGrowthLabels',
 'userGrowthData'
 ));
 }

 private function dashboardValue(string $section, callable $callback, mixed $fallback): mixed
 {
 try {
 return $callback();
 } catch (\Throwable $e) {
 Log::warning('Admin dashboard data unavailable.', [
 'section' => $section,
 'error' => $e->getMessage(),
 ]);

 return $fallback;
 }
 }

 private function hasTableColumns(string $table, array $columns): bool
 {
 if (! Schema::hasTable($table)) {
 return false;
 }

 foreach ($columns as $column) {
 if (! Schema::hasColumn($table, $column)) {
 return false;
 }
 }

 return true;
 }

 private function onlineUserIds(): \Illuminate\Support\Collection
 {
 $cutoff = now()->subMinutes(5);

 if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
 return DB::table(config('session.table', 'sessions'))
 ->whereNotNull('user_id')
 ->where('last_activity', '>=', $cutoff->timestamp)
 ->distinct()
 ->pluck('user_id')
 ->map(fn ($id) => (int) $id)
 ->values();
 }

 if (config('session.driver')!== 'file') {
 return collect();
 }

 $sessionPath = config('session.files');

 if (!is_dir($sessionPath)) {
 return collect();
 }

 return collect(File::files($sessionPath))
 ->filter(fn ($file) => $file->getMTime() >= $cutoff->timestamp)
 ->flatMap(function ($file) {
 $contents = File::get($file->getPathname());
 preg_match_all('/login_web_[^";|]*(?:\";i:|\|i:)(\d+)/', $contents, $matches);

 return collect($matches[1]?? [])->map(fn ($id) => (int) $id);
 })
 ->unique()
 ->values();
 }

 // Category CRUD
 public function storeCategory(Request $request)
 {
 $request->validate([
 'title' => 'required|string|max:255',
 'type' => 'required|in:core,game,learning',
 'description' => 'nullable|string',
 'icon' => 'nullable|string',
 'status' => 'nullable|string',
 'is_featured' => 'nullable|boolean',
 ]);

 Category::create([
 'title' => $request->title,
 'type' => strtolower($request->type),
 'description' => $request->description,
 'icon' => $request->icon,
 'status' => $request->status?? 'active',
 'is_featured' => $request->is_featured? true: false,
 'sort_order' => Category::max('sort_order') + 1,
 ]);

 return redirect()->back()->with('success', 'Category created successfully');
 }

 public function updateCategory(Request $request, Category $category)
 {
 $request->validate([
 'title' => 'required|string|max:255',
 'type' => 'required|in:core,game,learning',
 'description' => 'nullable|string',
 'icon' => 'nullable|string',
 'status' => 'required|string',
 'is_featured' => 'nullable|boolean',
 ]);

 $category->update([
 'title' => $request->title,
 'type' => strtolower($request->type),
 'description' => $request->description,
 'icon' => $request->icon,
 'status' => $request->status,
 'is_featured' => $request->is_featured? true: false,
 ]);

 return redirect()->back()->with('success', 'Category updated successfully');
 }

 public function destroyCategory(Category $category)
 {
 if ($category->questions()->count() > 0) {
 return redirect()->back()->with('error', 'Cannot delete category with active questions.');
 }

 $category->delete();
 return redirect()->back()->with('success', 'Category deleted successfully');
 }

 public function toggleCategoryStatus(Category $category)
 {
 $category->status = $category->status === 'active'? 'inactive': 'active';
 $category->save();

 return redirect()->back()->with('success', 'Category status updated');
 }

 public function categoryDetails(Category $category)
 {
 $category->load('questions');

 $totalQuestions = $category->questions->count();
 $totalInterviews = InterviewSession::where('category_id', $category->id)->count();
 $averageScore = (int) round(
 Score::join('interview_sessions', 'scores.interview_session_id', '=', 'interview_sessions.id')
 ->where('interview_sessions.category_id', $category->id)
 ->avg('scores.overall_readiness_score')?? 0
 );

 $allInterviewCount = InterviewSession::count();
 $popularity = $allInterviewCount > 0? max(1, min(10, (int) ceil(($totalInterviews / $allInterviewCount) * 10))): 0;

 $categoryMonthlyLabels = [];
 $categoryMonthlyData = [];
 for ($i = 5; $i >= 0; $i--) {
 $date = now()->subMonths($i);
 $categoryMonthlyLabels[] = $date->format('M');
 $categoryMonthlyData[] = InterviewSession::where('category_id', $category->id)
 ->whereYear('created_at', $date->year)
 ->whereMonth('created_at', $date->month)
 ->count();
 }

 $questionTypeCounts = $category->questions
 ->groupBy(fn ($question) => $question->type?: 'Unspecified')
 ->map(fn ($questions) => $questions->count())
 ->toArray();

 return $this->mobileView('admin.category_details', compact(
 'category',
 'totalQuestions',
 'totalInterviews',
 'averageScore',
 'popularity',
 'categoryMonthlyLabels',
 'categoryMonthlyData',
 'questionTypeCounts'
 ));
 }

 // Question CRUD
 public function storeQuestion(Request $request)
 {
 $request->validate([
 'category_id' => 'required|exists:categories,id',
 'question_text' => 'required|string',
 'difficulty' => 'required|string',
 'type' => 'required|string',
 'status' => 'nullable|string',
 'expected_guide' => 'nullable|string',
 'mapped_skills' => 'nullable|string',
 'source_name' => 'nullable|string|max:255',
 'source_url' => 'nullable|url',
 'source_type' => 'nullable|string|max:255',
 ]);

 $skills = $request->mapped_skills? array_map('trim', explode(',', $request->mapped_skills)): null;

 Question::create([
 'category_id' => $request->category_id,
 'question_text' => $request->question_text,
 'difficulty' => $request->difficulty,
 'type' => $request->type,
 'status' => $request->status?? 'active',
 'expected_guide' => $request->expected_guide,
 'mapped_skills' => $skills,
 'source_name' => $request->source_name,
 'source_url' => $request->source_url,
 'source_type' => $request->source_type,
 ]);

 return redirect()->back()->with('success', 'Question added successfully');
 }

 public function updateQuestion(Request $request, Question $question)
 {
 $request->validate([
 'category_id' => 'required|exists:categories,id',
 'question_text' => 'required|string',
 'difficulty' => 'required|string',
 'type' => 'required|string',
 'status' => 'required|string',
 'expected_guide' => 'nullable|string',
 'mapped_skills' => 'nullable|string',
 'source_name' => 'nullable|string|max:255',
 'source_url' => 'nullable|url',
 'source_type' => 'nullable|string|max:255',
 ]);

 $skills = $request->mapped_skills? array_map('trim', explode(',', $request->mapped_skills)): null;

 $question->update([
 'category_id' => $request->category_id,
 'question_text' => $request->question_text,
 'difficulty' => $request->difficulty,
 'type' => $request->type,
 'status' => $request->status,
 'expected_guide' => $request->expected_guide,
 'mapped_skills' => $skills,
 'source_name' => $request->source_name,
 'source_url' => $request->source_url,
 'source_type' => $request->source_type,
 ]);

 return redirect()->back()->with('success', 'Question updated successfully');
 }

 public function destroyQuestion(Question $question)
 {
 $question->delete();
 return redirect()->back()->with('success', 'Question deleted successfully');
 }

 public function bulkDestroyQuestions(Request $request)
 {
 $request->validate([
 'question_ids' => 'required|array',
 'question_ids.*' => 'exists:questions,id'
 ]);

 Question::whereIn('id', $request->question_ids)->delete();

 return redirect()->back()->with('success', 'Selected questions deleted successfully');
 }

 public function toggleQuestionStatus(Question $question)
 {
 $question->status = $question->status === 'active'? 'inactive': 'active';
 $question->save();
 return redirect()->back()->with('success', 'Question status updated');
 }

 public function questionAnalytics(Question $question)
 {
 $questionIds = Question::where('category_id', $question->category_id)
 ->where('question_text', $question->question_text)
 ->pluck('id');
 $answers = InterviewAnswer::whereIn('question_id', $questionIds);
 $used = (clone $answers)->count();
 $avgScore = (clone $answers)->whereNotNull('score')->avg('score');

 return response()->json([
 'used_count' => $used,
 'average_score' => $avgScore === null? 0: (int) round($avgScore),
 'has_score_data' => $avgScore!== null,
 ]);
 }

 public function generateAiQuestion(Request $request)
 {
 $request->validate([
 'category_id' => 'required|exists:categories,id',
 'position' => 'required|string|max:255',
 'difficulty' => 'required|string|max:50',
 'ai_provider' => 'nullable|string|max:50',
 'dataset' => 'nullable|string|max:80',
 ]);

 $category = Category::findOrFail($request->category_id);
 $position = trim($request->position);
 $difficulty = trim($request->difficulty);
 $dataset = QuestionDatasetProvider::find($request->input('dataset'))?? QuestionDatasetProvider::forCategory($category);
 $fallbackQuestion = QuestionDatasetProvider::fallbackQuestion($dataset, $category, $position, $difficulty);
 $rankedQuestion = collect(QuestionDatasetProvider::rankedQuestions($dataset, $position, $difficulty, [], 1))
 ->first();
 $questionRecord = is_array($rankedQuestion)? $rankedQuestion: $fallbackQuestion;
 $source = is_array($rankedQuestion)? 'dataset': 'fallback';
 $questionText = $this->roleAlignedQuestionText(
 (string) ($questionRecord['question_text']?? $this->fallbackInterviewQuestion($category, $position, $difficulty)),
 $position
 );

 return response()->json([
 'question_text' => trim($questionText),
 'source' => $source,
 'expected_guide' => $questionRecord['expected_guide']?? null,
 'mapped_skills' => $questionRecord['mapped_skills']?? ($dataset['default_skills']?? []),
 'source_name' => $questionRecord['source_name']?? null,
 'source_url' => $questionRecord['source_url']?? null,
 'source_type' => $questionRecord['source_type']?? null,
 'dataset_name' => $dataset['name']?? null,
 ]);
 }

 public function questionsDashboard()
 {
 $questions = Question::with('category')
 ->whereNull('interview_session_id')
 ->withCount('answers')
 ->latest()
 ->get();
 $categories = Category::withCount([
 'questions as questions_count' => fn ($query) => $query->whereNull('interview_session_id'),
 ])->get();

 $totalQuestions = $questions->count();
 $activeQuestions = $questions->where('status', 'active')->count();
 $totalCategories = $categories->count();
 $mostUsedQuestions = Question::with('category')
 ->whereNull('interview_session_id')
 ->withCount('answers')
 ->orderByDesc('answers_count')
 ->take(3)
 ->get();

 $questionDatasets = QuestionDatasetProvider::all();
 $aiProviderOptions = $this->questionProviderOptions();

 return $this->mobileView('admin.questions', compact(
 'questions',
 'categories',
 'totalQuestions',
 'activeQuestions',
 'totalCategories',
 'mostUsedQuestions',
 'questionDatasets',
 'aiProviderOptions'
 ));
 }

 private function defaultQuestionProvider(): string
 {
 $provider = AIService::defaultProviderKey();

 return $this->normalizeQuestionProvider($provider);
 }

 private function normalizeQuestionProvider(?string $provider): string
 {
 $provider = strtolower(trim((string) $provider));
 $provider = str_replace([' ', '_', '-'], '', $provider);

 return match ($provider) {
 'local' => 'local',
 'openai', 'chatgpt', 'gpt' => 'openai',
 'google', 'googlegemini', 'gemini' => 'gemini',
 'groq' => 'groq',
 'cohere' => 'cohere',
 default => AIService::defaultProviderKey(),
 };
 }

 private function questionProviderOptions(): array
 {
 $defaultProvider = $this->defaultQuestionProvider();

 return collect(AIService::supportedProviderOptions())
 ->map(fn (array $provider) => [
 'key' => $provider['key'],
 'label' => $provider['label'],
 'enabled' => $provider['enabled'],
 'is_default' => $provider['key'] === $defaultProvider,
 ])
 ->values()
 ->all();
 }

 private function roleAlignedQuestionText(string $questionText, string $position): string
 {
 $questionText = trim($questionText);
 $position = trim($position);

 if ($questionText === '' || $position === '') {
 return $questionText;
 }

 $rolePhrase = "the {$position} role";
 $replacements = [
 '/\bfor this role\b/i' => "for {$rolePhrase}",
 '/\bfor the role\b/i' => "for {$rolePhrase}",
 '/\bfor your target role\b/i' => "for {$rolePhrase}",
 '/\bthis role\b/i' => $rolePhrase,
 '/\bthe target role\b/i' => $rolePhrase,
 '/\byour target role\b/i' => $rolePhrase,
 ];

 foreach ($replacements as $pattern => $replacement) {
 $questionText = preg_replace($pattern, $replacement, $questionText)?? $questionText;
 }

 if (str_contains(mb_strtolower($questionText), mb_strtolower($position))) {
 return $questionText;
 }

 if (preg_match('/^(.+[.!])\s+([^.!?]+\?)$/s', $questionText, $matches)) {
 $finalQuestion = rtrim(trim($matches[2]), '?');

 return trim($matches[1]).' '.$finalQuestion.' for '.$rolePhrase.'?';
 }

 return 'For your target position of '.$position.', '.lcfirst($questionText);
 }

 private function fallbackInterviewQuestion(Category $category, string $position, string $difficulty): string
 {
 $categoryTitle = trim($category->title)?: 'this skill area';
 $targetPosition = $position!== ''? $position: 'your target role';

 $difficultyPrompt = match (strtolower($difficulty)) {
 'easy' => 'foundational',
 'hard' => 'complex or high-pressure',
 default => 'realistic',
 };

 return "For a {$targetPosition} role in your target context, describe a {$difficultyPrompt} school, internship, BPO, freelance, or workplace situation where you used {$categoryTitle}. What was your responsibility, what actions did you take, and what result would help a local HR interviewer judge your readiness?";
 }

 public function importQuestions(Request $request)
 {
 $request->validate([
 'file' => 'required|mimes:csv,txt'
 ]);

 $file = $request->file('file');
 $fileHandle = fopen($file->getPathname(), 'r');

 // Skip header
 fgetcsv($fileHandle);

 while (($row = fgetcsv($fileHandle))!== false) {
 if (count($row) >= 4) {
 Question::create([
 'question_text' => $row[0],
 'type' => $row[1],
 'difficulty' => $row[2],
 'category_id' => $row[3],
 'source_name' => $row[4]?? null,
 'source_url' => $row[5]?? null,
 'source_type' => $row[6]?? null,
 ]);
 }
 }

 fclose($fileHandle);
 return redirect()->back()->with('success', 'Questions imported successfully');
 }

 public function importDataset(Request $request)
 {
 $request->validate([
 'dataset' => 'required|string'
 ]);

 $dataset = QuestionDatasetProvider::find($request->dataset);
 if (!$dataset) {
 return redirect()->back()->with('error', 'Selected dataset is not available.');
 }

 $questionsToImport = QuestionDatasetProvider::preparedQuestions($request->dataset);
 $imported = 0;

 foreach ($questionsToImport as $q) {
 $category = Category::firstOrCreate(
 ['title' => $q['category']?? 'Question Sources', 'type' => 'core'],
 ['description' => 'Imported from reliable question sources', 'status' => 'active']
 );

 $question = Question::firstOrCreate(
 ['question_text' => $q['question_text'], 'category_id' => $category->id],
 [
 'type' => $q['type'],
 'difficulty' => $q['difficulty'],
 'expected_guide' => $q['expected_guide']?? null,
 'mapped_skills' => $q['mapped_skills']?? null,
 'source_name' => $q['source_name']?? null,
 'source_url' => $q['source_url']?? null,
 'source_type' => $q['source_type']?? null,
 'status' => 'active',
 ]
 );

 if ($question->wasRecentlyCreated) {
 $imported++;
 }
 }

 return redirect()->back()->with('success', $imported. ' new questions imported from '. $dataset['name']. '.');
 }

 public function exportQuestions()
 {
 $questions = Question::with('category')
 ->whereNull('interview_session_id')
 ->get();

 $headers = [
 "Content-type" => "text/csv",
 "Content-Disposition" => "attachment; filename=questions.csv",
 "Pragma" => "no-cache",
 "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
 "Expires" => "0"
 ];

 $columns = ['ID', 'Category', 'Question', 'Type', 'Difficulty', 'Source Name', 'Source URL', 'Source Type'];

 $callback = function() use($questions, $columns) {
 $file = fopen('php://output', 'w');
 CsvExportService::writeRow($file, $columns);

 foreach ($questions as $question) {
 $row['ID'] = $question->id;
 $row['Category'] = admin_without_restricted_country_text($question->category->title?? 'N/A');
 $row['Question'] = admin_without_restricted_country_text($question->question_text);
 $row['Type'] = $question->type;
 $row['Difficulty'] = $question->difficulty;
 $row['Source Name'] = admin_without_restricted_country_text($question->source_name);
 $row['Source URL'] = $question->source_url;
 $row['Source Type'] = $question->source_type;

 CsvExportService::writeRow($file, array(
 $row['ID'],
 $row['Category'],
 $row['Question'],
 $row['Type'],
 $row['Difficulty'],
 $row['Source Name'],
 $row['Source URL'],
 $row['Source Type'],
 ));
 }

 fclose($file);
 };

 return response()->stream($callback, 200, $headers);
 }

 // Module Store
 public function storeModule(Request $request)
 {
 $request->validate([
 'title' => 'required|string|max:255',
 'category' => 'nullable|string|max:255',
 'difficulty' => 'nullable|string',
 'description' => 'nullable|string',
 'status' => 'nullable|string',
 'is_featured' => 'nullable|boolean',
 ]);

 $this->ensureLearningCategory($request->category);

 LearningModule::create([
 'title' => $request->title,
 'category' => $request->category,
 'difficulty' => $request->difficulty,
 'description' => $request->description,
 'status' => $request->status?? 'draft',
 'is_featured' => $request->has('is_featured'),
 ]);

 return redirect()->back()->with('success', 'Module created successfully');
 }

 public function generateModule(Request $request)
 {
 $request->validate([
 'prompt' => 'required|string',
 ]);

 $categories = $this->learningCategoryNames()->implode(', ');
 $categoryInstruction = $categories? "Choose exactly one of these categories: $categories": "General";

 $prompt = "Create an action-focused interview preparation learning module about: ". $request->prompt. ".
 Focus only on what the learner needs to do before and during the interview: what to prepare, what to write, what to rehearse, what to revise, and what to check before marking the module complete.
 Keep every action grounded in job interview practice: local HR screening, BPO/customer support, IT roles, fresh graduate interviews, professional communication, salary expectations, and availability/work-setup questions when relevant.
 Avoid broad lectures, history, trivia, generic motivation, feature promotion, or content that does not tell the user a concrete interview-preparation action.
 Return ONLY a JSON object with the following structure:
 {
 \"title\": \"Module Title\",
 \"description\": \"Short action-focused summary of what the learner will do in this module\",
 \"difficulty\": \"Beginner\",
 \"category\": \"$categoryInstruction\",
 \"chapters\": [
 {
 \"title\": \"Chapter 1: Action Step\",
 \"content\": \"HTML content using h3, p, ul, and li. Include concrete tasks, a short answer pattern, and a completion check.\"
 },
 {
 \"title\": \"Chapter 2: Practice Step\",
 \"content\": \"HTML content using h3, p, ul, and li. Include only what the learner must do, rehearse, revise, or verify.\"
 }
 ]
 }";

 try {
 $jsonResponse = \App\Services\AIService::generateJson($prompt);
 $data = json_decode($jsonResponse, true);

 if (!$data ||!isset($data['title'])) {
 $data = $this->fallbackModuleData($request->prompt);
 }

 $module = LearningModule::create([
 'title' => $data['title'],
 'category' => $data['category']?? 'General',
 'difficulty' => $data['difficulty']?? 'Beginner',
 'description' => $data['description']?? '',
 'status' => 'draft',
 'type' => 'article',
 'is_featured' => false,
 ]);

 if (isset($data['chapters']) && is_array($data['chapters'])) {
 foreach ($data['chapters'] as $index => $chapterData) {
 $module->chapters()->create([
 'title' => $chapterData['title']?? 'Chapter '. ($index + 1),
 'content' => $chapterData['content']?? '',
 'order' => $index + 1,
 ]);
 }
 }

 $this->ensureLearningCategory($module->category);

 return redirect()->route('admin.modules.edit', $module->id)->with('success', 'AI Module generated successfully! You can now review and publish it.');

 } catch (\Exception $e) {
 \Illuminate\Support\Facades\Log::error('AI Module Generation Error: '. $e->getMessage());

 $data = $this->fallbackModuleData($request->prompt);
 $module = LearningModule::create([
 'title' => $data['title'],
 'category' => $data['category'],
 'difficulty' => $data['difficulty'],
 'description' => $data['description'],
 'status' => 'draft',
 'type' => 'article',
 'is_featured' => false,
 ]);

 foreach ($data['chapters'] as $index => $chapterData) {
 $module->chapters()->create([
 'title' => $chapterData['title'],
 'content' => $chapterData['content'],
 'order' => $index + 1,
 ]);
 }

 $this->ensureLearningCategory($module->category);

 return redirect()->route('admin.modules.edit', $module->id)->with('success', 'Module generated with reliable fallback content. You can now review and publish it.');
 }
 }

 public function autofillModule(LearningModule $module)
 {
 $prompt = "Create action-focused interview preparation content for an educational learning module titled: '". $module->title. "'.
 The category is '". $module->category. "' and difficulty is '". $module->difficulty. "'.
 Focus only on what the learner needs to do before and during the interview: what to prepare, what to write, what to rehearse, what to revise, and what to check before marking the module complete.
 Ground every task in job interview practice, including local HR screening, BPO/customer support, IT roles, fresh graduate interviews, communication clarity, salary expectations, and availability/work-setup questions when relevant.
 Avoid broad lectures, history, trivia, generic motivation, feature promotion, or content that does not tell the user a concrete interview-preparation action.
 Return ONLY a JSON object with the following structure:
 {
 \"description\": \"A professional action-focused summary of what the learner will do in the module (2-3 sentences)\",
 \"chapters\": [
 {
 \"title\": \"Chapter 1: Action Step\",
 \"content\": \"HTML content using h3, p, ul, and li. Include concrete tasks, a short answer pattern, and a completion check.\"
 },
 {
 \"title\": \"Chapter 2: Practice Step\",
 \"content\": \"HTML content using h3, p, ul, and li. Include only what the learner must do, rehearse, revise, or verify.\"
 }
 ]
 }";

 try {
 $jsonResponse = \App\Services\AIService::generateJson($prompt);
 $data = json_decode($jsonResponse, true);

 if (!$data) {
 $data = $this->fallbackModuleAutofillData($module);
 }

 // Update description if it's currently empty or short
 if (empty($module->description) || strlen($module->description) < 20) {
 $module->update([
 'description' => $data['description']?? $module->description,
 ]);
 }

 if (isset($data['chapters']) && is_array($data['chapters'])) {
 // Delete existing chapters to prevent duplication, or just append? Append is safer, but autofill implies filling it out. Let's just append.
 $startOrder = $module->chapters()->count();
 foreach ($data['chapters'] as $index => $chapterData) {
 $module->chapters()->create([
 'title' => $chapterData['title'],
 'content' => $chapterData['content'],
 'order' => $startOrder + $index + 1,
 ]);
 }
 }

 return redirect()->back()->with('success', 'Module successfully autofilled by AI!');

 } catch (\Exception $e) {
 \Illuminate\Support\Facades\Log::error('AI Module Autofill Error: '. $e->getMessage());

 $data = $this->fallbackModuleAutofillData($module);
 if (empty($module->description) || strlen($module->description) < 20) {
 $module->update([
 'description' => $data['description'],
 ]);
 }

 $startOrder = $module->chapters()->count();
 foreach ($data['chapters'] as $index => $chapterData) {
 $module->chapters()->create([
 'title' => $chapterData['title'],
 'content' => $chapterData['content'],
 'order' => $startOrder + $index + 1,
 ]);
 }

 return redirect()->back()->with('success', 'Module autofilled with reliable fallback content.');
 }
 }

 public function modulesDashboard()
 {
 $modules = LearningModule::all();
 $totalModules = $modules->count();
 $publishedModules = $modules->where('status', 'published')->count();
 $draftModules = $modules->where('status', 'draft')->count();
 $mostViewedModule = LearningModule::orderBy('views', 'desc')->first();

 $categories = $this->learningCategoryNames();

 return $this->mobileView('admin.modules', compact('modules', 'totalModules', 'publishedModules', 'draftModules', 'mostViewedModule', 'categories'));
 }

 public function editModule(LearningModule $module)
 {
 $module->load(['chapters', 'activities']);
 $categories = $this->learningCategoryNames();
 return $this->mobileView('admin.module_edit', compact('module', 'categories'));
 }

 public function updateModule(Request $request, LearningModule $module)
 {
 $request->validate([
 'title' => 'required|string|max:255',
 'category' => 'nullable|string|max:255',
 'difficulty' => 'nullable|string',
 'description' => 'nullable|string',
 'status' => 'nullable|string',
 ]);

 $this->ensureLearningCategory($request->category);

 $module->update([
 'title' => $request->title,
 'category' => $request->category,
 'difficulty' => $request->difficulty,
 'description' => $request->description,
 'status' => $request->status?? 'draft',
 'is_featured' => $request->has('is_featured'),
 ]);

 return redirect()->route('admin.modules')->with('success', 'Module updated successfully');
 }

 public function destroyModule(LearningModule $module)
 {
 $module->delete();
 return redirect()->back()->with('success', 'Module deleted successfully');
 }

 // Chapters
 public function storeModuleChapter(Request $request, LearningModule $module)
 {
 $request->validate([
 'title' => 'required|string|max:255',
 'content' => 'nullable|string',
 'video_url' => 'nullable|string',
 ]);

 $module->chapters()->create([
 'title' => $request->title,
 'content' => $request->content,
 'video_url' => $request->video_url,
 'order' => $module->chapters()->count() + 1,
 ]);

 return redirect()->back()->with('success', 'Chapter added successfully');
 }

 public function updateModuleChapter(Request $request, \App\Models\ModuleChapter $chapter)
 {
 $request->validate([
 'title' => 'required|string|max:255',
 'content' => 'nullable|string',
 'video_url' => 'nullable|string',
 ]);

 $chapter->update([
 'title' => $request->title,
 'content' => $request->content,
 'video_url' => $request->video_url,
 ]);

 return redirect()->back()->with('success', 'Chapter updated successfully');
 }

 public function generateModuleChapter(Request $request, LearningModule $module)
 {
 $existingChapters = $module->chapters()->orderBy('order')->pluck('title')->implode(', ');

 $prompt = "Generate exactly 1 new role-focused interview preparation chapter for a learning module.
 Module Title: {$module->title}
 Module Description: {$module->description}
 Existing Chapters: ". ($existingChapters?: "None yet."). "
 Generate the next logical chapter using hiring or school-interview examples when relevant.
 Focus only on concrete user actions: what to prepare, write, rehearse, revise, or check. Avoid broad lectures, trivia, and generic motivation.
 Provide a JSON response with the following exact keys:
 {
 \"title\": \"Chapter Title\",
 \"content\": \"Action-focused HTML content (use h3, p, ul, li). Include concrete tasks and a completion check.\"
 }";

 try {
 $jsonResponse = \App\Services\AIService::generateJson($prompt);
 $data = json_decode($jsonResponse, true);
 } catch (\Throwable $e) {
 \Illuminate\Support\Facades\Log::warning('AI Chapter Generation Error: '. $e->getMessage());
 $data = null;
 }

 if (!$data ||!isset($data['title'])) {
 $data = $this->fallbackModuleChapterData($module);
 }

 $module->chapters()->create([
 'title' => $data['title'],
 'content' => $data['content']?? '',
 'order' => ((int) $module->chapters()->max('order')) + 1,
 ]);

 return redirect()->back()->with('success', 'AI Chapter generated successfully!');
 }

 public function destroyModuleChapter(\App\Models\ModuleChapter $chapter)
 {
 $chapter->delete();
 return redirect()->back()->with('success', 'Chapter deleted successfully');
 }

 public function fetchLatestActivities(Request $request)
 {
 $activitiesQuery = \App\Models\ActivityLog::with('user')->orderBy('id', 'desc');
 $latestActivities = $activitiesQuery->take(15)->get();
 $newCount = \App\Models\ActivityLog::whereNull('read_at')->count();
 $authActivities = $latestActivities
 ->whereNull('read_at')
 ->values()
 ->map(function ($activity) {
 return [
 'id' => $activity->id,
 'title' => ucwords(str_replace('_', ' ', $activity->action)),
 'body' => $activity->description?: ($activity->user? $activity->user->name: 'A user activity was recorded.'),
 'url' => route('admin.dashboard'),
 ];
 });

 $html = '';
 if ($latestActivities->isEmpty()) {
 $html = '<div class="p-3 text-center text-muted" style="font-size:0.85rem;">No recent activities.</div>';
 } else {
 foreach ($latestActivities as $activity) {
 $time = $activity->created_at->diffForHumans();
 $userName = htmlspecialchars($activity->user? $activity->user->name: 'System', ENT_QUOTES, 'UTF-8');
 $time = htmlspecialchars($time, ENT_QUOTES, 'UTF-8');
 $isNew = is_null($activity->read_at);
 $description = htmlspecialchars($activity->description?: $activity->action, ENT_QUOTES, 'UTF-8');

 $html.= '
 <div class="admin-activity-item '.($isNew? 'is-unread': '').'" data-id="'.$activity->id.'">
 <div class="admin-activity-ico"><i class="fa-solid fa-clock-rotate-left"></i></div>
 <div class="admin-activity-copy">
 <div class="admin-activity-row-head">
 <strong>'.$userName.'</strong>
 <small>'.$time.'</small>
 </div>
 <span>'.$description.'</span>
 <div class="admin-activity-row-actions">
 '.($isNew? '<button class="admin-activity-link-btn" type="button" onclick="markActivityRead('.$activity->id.', event)">Mark as read</button>': '').'
 <button class="admin-activity-link-btn danger" type="button" onclick="deleteActivity('.$activity->id.', event)">Delete</button>
 </div>
 </div>
 </div>';
 }
 }

 return response()->json([
 'html' => $html,
 'new_count' => $newCount,
 'auth_activities' => $authActivities,
 ]);
 }

 public function markAllActivitiesRead()
 {
 \App\Models\ActivityLog::whereNull('read_at')->update(['read_at' => now()]);
 return response()->json(['success' => true]);
 }

 public function clearAllActivities()
 {
 \App\Models\ActivityLog::truncate();
 return response()->json(['success' => true]);
 }

 public function markActivityRead($id)
 {
 $log = \App\Models\ActivityLog::find($id);
 if ($log) {
 $log->update(['read_at' => now()]);
 }
 return response()->json(['success' => true]);
 }

 public function deleteActivity($id)
 {
 $log = \App\Models\ActivityLog::find($id);
 if ($log) {
 $log->delete();
 }
 return response()->json(['success' => true]);
 }

 private function learningCategoryNames()
 {
 return Category::where('type', 'learning')
 ->pluck('title')
 ->merge(LearningModule::whereNotNull('category')->pluck('category'))
 ->map(fn ($name) => trim((string) $name))
 ->filter()
 ->unique()
 ->values();
 }

 private function ensureLearningCategory(?string $title): void
 {
 $title = trim((string) $title);
 if ($title === '') {
 return;
 }

 Category::firstOrCreate(
 ['title' => $title, 'type' => 'learning'],
 ['description' => 'interview learning module category', 'status' => 'active']
 );
 }

 private function fallbackModuleData(string $topic): array
 {
 $topic = $this->cleanFallbackText($topic, 'Interview Readiness');

 return [
 'title' => $this->cleanFallbackText($topic. ' Essentials', 'Interview Readiness Essentials'),
 'category' => 'General',
 'difficulty' => 'Beginner',
 'description' => "A practical local interview module that tells learners what to prepare, write, rehearse, revise, and check for {$topic}.",
 'chapters' => [
 [
 'title' => 'Prepare the Proof',
 'content' => "<h3>Prepare the Proof</h3><p>Write one target role, one interview situation where this topic matters, and one result a local HR or hiring panel should hear.</p><ul><li>Pick a real internship, BPO, freelance, or workplace example.</li><li>Name your responsibility in one sentence.</li><li>List the evidence you can honestly explain, such as a result, lesson, metric, or customer impact.</li></ul>",
 ],
 [
 'title' => 'Rehearse and Check',
 'content' => "<h3>Rehearse and Check</h3><p>Draft a short answer using context, action, result, and reflection, then rehearse it aloud until it sounds natural.</p><ul><li>Keep the answer role-relevant for employers and hiring panels.</li><li>Revise vague phrases into specific actions you personally took.</li><li>Mark the module complete only after the answer includes a clear action, honest evidence, and a confident closing line.</li></ul>",
 ],
 ],
 ];
 }

 private function fallbackModuleAutofillData(LearningModule $module): array
 {
 $title = $this->cleanFallbackText($module->title, 'Interview Readiness');

 return [
 'description' => "A focused interview module that turns {$title} into preparation tasks, practice answers, revision steps, and completion checks.",
 'chapters' => [
 [
 'title' => 'Prepare Your Local Example',
 'content' => "<h3>Prepare Your Local Example</h3><p>Choose one honest example for {$title} that fits a HR, school, BPO, IT, or fresh graduate interview.</p><ul><li>Write the situation in one line.</li><li>Write what you personally did.</li><li>Write the result, lesson, or proof that shows readiness.</li></ul>",
 ],
 [
 'title' => 'Practice, Revise, Complete',
 'content' => "<h3>Practice, Revise, Complete</h3><p>Turn your example into a complete interview answer, then rehearse it aloud and revise it once for clarity and local relevance.</p><ul><li>Remove filler or generic claims.</li><li>Add one concrete detail that a local interviewer can verify.</li><li>Complete the module when the answer is concise, truthful, and role-relevant.</li></ul>",
 ],
 ],
 ];
 }

 private function fallbackModuleChapterData(LearningModule $module): array
 {
 $next = $module->chapters()->count() + 1;
 $title = $this->cleanFallbackText($module->title, 'this module');

 return [
 'title' => "Chapter {$next}: local Interview Practice Checkpoint",
 'content' => "<h3>local Interview Practice Checkpoint</h3><p>Review the key idea from {$title}, then write a short answer for a HR, school, BPO, IT, or fresh graduate interview that explains the situation, your action, and the result.</p><ul><li>Use one concrete local example.</li><li>Name your personal contribution.</li><li>End with a lesson, measurable result, or reason you are ready for the role.</li></ul>",
 ];
 }

 private function cleanFallbackText(?string $value, string $fallback): string
 {
 $cleaned = trim(preg_replace('/\s+/', ' ', (string) $value));
 if ($cleaned === '') {
 $cleaned = $fallback;
 }

 return mb_substr($cleaned, 0, 180);
 }
}
