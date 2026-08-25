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
        $registeredUsersCount = \App\Models\User::count();
        $onlineTodayCount = $this->onlineUserIds()->count();
        $mockInterviewsCount = \App\Models\InterviewSession::count();
        $aiFeedbacksCount = \App\Models\Feedback::count();
        $modulesCompletedCount = \App\Models\LearningProgress::where('status', 'completed')->count();
        $userUpdatesCount = \App\Models\ActivityLog::count();
        
        $recentSessions = \App\Models\InterviewSession::with(['user', 'category', 'score'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        $eligibleScoresQuery = \App\Models\Score::readinessEligible();
        $readinessBandSummary = (clone $eligibleScoresQuery)->get()
            ->groupBy(fn ($score) => $score->readiness_band ?: 'Legacy')
            ->map(fn ($scores, $band) => (object) [
                'band' => $band,
                'count' => $scores->count(),
                'scoring_confidence' => (int) round($scores->avg('scoring_confidence') ?? 0),
            ])->values();

        // Users needing support (< 60 score)
        $usersNeedingSupport = \App\Models\Score::select('scores.*', 'users.name as user_name', 'users.id as user_id')
            ->join('interview_sessions', 'scores.interview_session_id', '=', 'interview_sessions.id')
            ->join('users', 'interview_sessions.user_id', '=', 'users.id')
            ->where('scores.overall_readiness_score', '<', 60)
            ->readinessEligible()
            ->orderBy('scores.overall_readiness_score', 'asc')
            ->take(3)
            ->get();

        // Avg performance metrics
        $avgClarity = round((clone $eligibleScoresQuery)->avg('clarity_score') ?? 0);
        $avgRelevance = round((clone $eligibleScoresQuery)->avg('relevance_score') ?? 0);
        $avgGrammar = round((clone $eligibleScoresQuery)->avg('grammar_score') ?? 0);
        $avgProfessionalism = round((clone $eligibleScoresQuery)->avg('professionalism_score') ?? 0);

        $recentActivities = \App\Models\ActivityLog::orderBy('created_at', 'desc')->take(15)->get()->map(function($activity) {
            return [
                'text' => $activity->description ?: $activity->action,
                'time' => $activity->created_at->diffForHumans(),
            ];
        });

        // Analytics for charts
        $categoriesDonut = \App\Models\InterviewSession::join('categories', 'interview_sessions.category_id', '=', 'categories.id')
            ->selectRaw('categories.title as label, count(*) as count')
            ->groupBy('categories.title')
            ->get();

        $chartLabels = $categoriesDonut->pluck('label');
        $chartData = $categoriesDonut->pluck('count');

        // Readiness Distribution
        $highlyAcc = \App\Models\Score::where('overall_readiness_score', '>=', 90)->count();
        $acceptable = \App\Models\Score::whereBetween('overall_readiness_score', [70, 89])->count();
        $needsImp = \App\Models\Score::whereBetween('overall_readiness_score', [50, 69])->count();
        $poor = \App\Models\Score::where('overall_readiness_score', '<', 50)->count();
        $readinessData = [$highlyAcc, $acceptable, $needsImp, $poor];

        // User Growth (Last 6 months)
        $userGrowthLabels = [];
        $userGrowthData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $userGrowthLabels[] = $date->format('M');
            $userGrowthData[] = \App\Models\User::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }

        return $this->mobileView('admin.dashboard', compact(
            'registeredUsersCount',
            'onlineTodayCount',
            'mockInterviewsCount',
            'aiFeedbacksCount',
            'modulesCompletedCount',
            'userUpdatesCount',
            'recentSessions',
            'readinessBandSummary',
            'usersNeedingSupport',
            'avgClarity',
            'avgRelevance',
            'avgGrammar',
            'avgProfessionalism',
            'chartLabels',
            'chartData',
            'readinessData',
            'userGrowthLabels',
            'userGrowthData',
            'recentActivities'
        ));
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

        if (config('session.driver') !== 'file') {
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

                return collect($matches[1] ?? [])->map(fn ($id) => (int) $id);
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
            'status' => $request->status ?? 'active',
            'is_featured' => $request->is_featured ? true : false,
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
            'is_featured' => $request->is_featured ? true : false,
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
        $category->status = $category->status === 'active' ? 'inactive' : 'active';
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
                ->avg('scores.overall_readiness_score') ?? 0
        );

        $allInterviewCount = InterviewSession::count();
        $popularity = $allInterviewCount > 0
            ? max(1, min(10, (int) ceil(($totalInterviews / $allInterviewCount) * 10)))
            : 0;

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
            ->groupBy(fn ($question) => $question->type ?: 'Unspecified')
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

        $skills = $request->mapped_skills ? array_map('trim', explode(',', $request->mapped_skills)) : null;

        Question::create([
            'category_id' => $request->category_id,
            'question_text' => $request->question_text,
            'difficulty' => $request->difficulty,
            'type' => $request->type,
            'status' => $request->status ?? 'active',
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

        $skills = $request->mapped_skills ? array_map('trim', explode(',', $request->mapped_skills)) : null;

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
        $question->status = $question->status === 'active' ? 'inactive' : 'active';
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
            'average_score' => $avgScore === null ? 0 : (int) round($avgScore),
            'has_score_data' => $avgScore !== null,
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
        $provider = $this->normalizeQuestionProvider(
            $request->input('ai_provider', $this->defaultQuestionProvider())
        );
        $dataset = QuestionDatasetProvider::find($request->input('dataset'))
            ?? QuestionDatasetProvider::forCategory($category);
        $sourceMetadata = QuestionDatasetProvider::sourceMetadata($dataset);
        $fallbackQuestion = QuestionDatasetProvider::fallbackQuestion($dataset, $category, $position, $difficulty);
        $categoryFocus = 'Philippines ' . trim($category->title) . ' interview';

        $questionText = null;
        $source = 'fallback';

        if ($this->providerCanGenerateQuestions($provider)) {
            try {
                $generated = AIService::generateQuestions(
                    1,
                    $position,
                    $difficulty,
                    $categoryFocus,
                    $provider,
                    null,
                    null,
                    'Philippine hiring panel',
                    [],
                    'standard',
                    'neutral',
                    $dataset
                );

                $questionText = collect($generated)
                    ->first(fn ($question) => is_string($question) && trim($question) !== '');

                if ($questionText) {
                    $source = 'ai_dataset';
                }
            } catch (\Throwable $e) {
                Log::warning('Admin AI question generation failed; using deterministic fallback.', [
                    'provider' => $provider,
                    'category_id' => $category->id,
                    'dataset' => $dataset['key'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $questionText = $questionText ?: (
            $provider === 'local'
                ? $this->legacyFallbackInterviewQuestion($category, $position, $difficulty)
                : ($fallbackQuestion['question_text'] ?? $this->fallbackInterviewQuestion($category, $position, $difficulty))
        );

        return response()->json([
            'question_text' => trim($questionText),
            'source' => $source,
            'expected_guide' => $fallbackQuestion['expected_guide'] ?? null,
            'mapped_skills' => $fallbackQuestion['mapped_skills'] ?? ($dataset['default_skills'] ?? []),
            'source_name' => $sourceMetadata['source_name'] ?? null,
            'source_url' => $sourceMetadata['source_url'] ?? null,
            'source_type' => $sourceMetadata['source_type'] ?? null,
            'dataset_name' => $dataset['name'] ?? null,
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

        $datasetPacks = QuestionDatasetProvider::all();
        $aiProviderOptions = $this->questionProviderOptions();

        return $this->mobileView('admin.questions', compact(
            'questions',
            'categories',
            'totalQuestions',
            'activeQuestions',
            'totalCategories',
            'mostUsedQuestions',
            'datasetPacks',
            'aiProviderOptions'
        ));
    }

    private function providerCanGenerateQuestions(string $provider): bool
    {
        return AIService::providerIsConfigured($provider);
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
            'anthropic', 'claude' => 'claude',
            'groq' => 'groq',
            'openrouter' => 'openrouter',
            'hf', 'huggingface', 'huggingfacehub' => 'huggingface',
            'wisdomgate' => 'wisdomgate',
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

    private function fallbackInterviewQuestion(Category $category, string $position, string $difficulty): string
    {
        $categoryTitle = trim($category->title) ?: 'this skill area';
        $targetPosition = $position !== '' ? $position : 'your target role';

        $difficultyPrompt = match (strtolower($difficulty)) {
            'easy' => 'foundational',
            'hard' => 'complex or high-pressure',
            default => 'realistic',
        };

        return "For a {$targetPosition} role in the Philippines, describe a {$difficultyPrompt} school, internship, BPO, freelance, or workplace situation where you used {$categoryTitle}. What was your responsibility, what actions did you take, and what result would help a local HR interviewer judge your readiness?";
    }

    private function legacyFallbackInterviewQuestion(Category $category, string $position, string $difficulty): string
    {
        $categoryTitle = trim($category->title) ?: 'this skill area';
        $targetPosition = $position !== '' ? $position : 'your target role';

        $difficultyPrompt = match (strtolower($difficulty)) {
            'easy' => 'foundational',
            'hard' => 'complex or high-pressure',
            default => 'realistic',
        };

        return "For a {$targetPosition} role, describe a {$difficultyPrompt} situation where you used {$categoryTitle}. What was your responsibility, what actions did you take, and what measurable result followed?";
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

        while (($row = fgetcsv($fileHandle)) !== false) {
            if (count($row) >= 4) {
                Question::create([
                    'question_text' => $row[0],
                    'type' => $row[1],
                    'difficulty' => $row[2],
                    'category_id' => $row[3],
                    'source_name' => $row[4] ?? null,
                    'source_url' => $row[5] ?? null,
                    'source_type' => $row[6] ?? null,
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
                ['title' => $q['category'] ?? 'Philippines Source Packs', 'type' => 'core'],
                ['description' => 'Imported from reliable Philippines source packs', 'status' => 'active']
            );

            $question = Question::firstOrCreate(
                ['question_text' => $q['question_text'], 'category_id' => $category->id],
                [
                    'type' => $q['type'],
                    'difficulty' => $q['difficulty'],
                    'expected_guide' => $q['expected_guide'] ?? null,
                    'mapped_skills' => $q['mapped_skills'] ?? null,
                    'source_name' => $q['source_name'] ?? null,
                    'source_url' => $q['source_url'] ?? null,
                    'source_type' => $q['source_type'] ?? null,
                    'status' => 'active',
                ]
            );

            if ($question->wasRecentlyCreated) {
                $imported++;
            }
        }

        return redirect()->back()->with('success', $imported . ' new questions imported from ' . $dataset['name'] . '.');
    }

    public function exportQuestions()
    {
        $questions = Question::with('category')
            ->whereNull('interview_session_id')
            ->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=questions.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Category', 'Question', 'Type', 'Difficulty', 'Source Name', 'Source URL', 'Source Type'];

        $callback = function() use($questions, $columns) {
            $file = fopen('php://output', 'w');
            CsvExportService::writeRow($file, $columns);

            foreach ($questions as $question) {
                $row['ID']  = $question->id;
                $row['Category']    = $question->category->title ?? 'N/A';
                $row['Question']  = $question->question_text;
                $row['Type']  = $question->type;
                $row['Difficulty']  = $question->difficulty;
                $row['Source Name'] = $question->source_name;
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
            'status' => $request->status ?? 'draft',
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
        $categoryInstruction = $categories ? "Choose exactly one of these categories: $categories" : "General";

        $prompt = "Create an action-focused Philippines interview preparation learning module about: " . $request->prompt . ".
        Focus only on what the learner needs to do before and during the interview: what to prepare, what to write, what to rehearse, what to revise, and what to check before marking the module complete.
        Keep every action grounded in Philippine hiring and education interview practice: local HR screening, BPO/customer support, IT roles, fresh graduate applications, scholarship or college admission interviews, professional communication, salary expectations, and availability/work-setup questions when relevant.
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
            
            if (!$data || !isset($data['title'])) {
                $data = $this->fallbackModuleData($request->prompt);
            }

            $module = LearningModule::create([
                'title' => $data['title'],
                'category' => $data['category'] ?? 'General',
                'difficulty' => $data['difficulty'] ?? 'Beginner',
                'description' => $data['description'] ?? '',
                'status' => 'draft',
                'type' => 'article',
                'is_featured' => false,
            ]);

            if (isset($data['chapters']) && is_array($data['chapters'])) {
                foreach ($data['chapters'] as $index => $chapterData) {
                    $module->chapters()->create([
                        'title' => $chapterData['title'] ?? 'Chapter ' . ($index + 1),
                        'content' => $chapterData['content'] ?? '',
                        'order' => $index + 1,
                    ]);
                }
            }

            $this->ensureLearningCategory($module->category);

            return redirect()->route('admin.modules.edit', $module->id)->with('success', 'AI Module generated successfully! You can now review and publish it.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Module Generation Error: ' . $e->getMessage());

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
        $prompt = "Create action-focused Philippines interview preparation content for an educational learning module titled: '" . $module->title . "'.
        The category is '" . $module->category . "' and difficulty is '" . $module->difficulty . "'.
        Focus only on what the learner needs to do before and during the interview: what to prepare, what to write, what to rehearse, what to revise, and what to check before marking the module complete.
        Ground every task in Philippine hiring and education interview practice, including local HR screening, BPO/customer support, IT roles, fresh graduate applications, scholarship or college admission interviews, communication clarity, salary expectations, and availability/work-setup questions when relevant.
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
                    'description' => $data['description'] ?? $module->description,
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
            \Illuminate\Support\Facades\Log::error('AI Module Autofill Error: ' . $e->getMessage());

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
        $totalResources = \App\Models\ModuleResource::count();
        $mostViewedModule = LearningModule::orderBy('views', 'desc')->first();
        
        $categories = $this->learningCategoryNames();

        return $this->mobileView('admin.modules', compact('modules', 'totalModules', 'publishedModules', 'draftModules', 'totalResources', 'mostViewedModule', 'categories'));
    }

    public function editModule(LearningModule $module)
    {
        $module->load(['chapters', 'resources', 'quizzes.questions', 'activities', 'gameLevels']);
        $allGameLevels = \App\Models\GameLevel::orderBy('level_number', 'asc')->get();
        $categories = $this->learningCategoryNames();
        return $this->mobileView('admin.module_edit', compact('module', 'allGameLevels', 'categories'));
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
            'status' => $request->status ?? 'draft',
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
        
        $prompt = "Generate exactly 1 new Philippines-focused interview preparation chapter for a learning module.
        Module Title: {$module->title}
        Module Description: {$module->description}
        Existing Chapters: " . ($existingChapters ?: "None yet.") . "
        Generate the next logical chapter using Philippine hiring or school-interview examples when relevant.
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
            \Illuminate\Support\Facades\Log::warning('AI Chapter Generation Error: ' . $e->getMessage());
            $data = null;
        }

        if (!$data || !isset($data['title'])) {
            $data = $this->fallbackModuleChapterData($module);
        }

        $module->chapters()->create([
            'title' => $data['title'],
            'content' => $data['content'] ?? '',
            'order' => ((int) $module->chapters()->max('order')) + 1,
        ]);

        return redirect()->back()->with('success', 'AI Chapter generated successfully!');
    }

    public function destroyModuleChapter(\App\Models\ModuleChapter $chapter)
    {
        $chapter->delete();
        return redirect()->back()->with('success', 'Chapter deleted successfully');
    }

    // Quizzes
    public function generateModuleQuiz(Request $request, LearningModule $module)
    {
        $prompt = "Create a 5-question multiple choice quiz based on the following Philippines-focused interview preparation learning module content.\n";
        $prompt .= "Module Title: " . $module->title . "\n";
        $prompt .= "Module Description: " . $module->description . "\n";
        foreach($module->chapters as $chapter) {
            $prompt .= "Chapter '" . $chapter->title . "' Content: " . strip_tags($chapter->content) . "\n";
        }
        $prompt .= "Keep every question aligned with Philippine interview preparation and local hiring or education interview expectations.\n";
        
        $prompt .= <<<EOT
Return ONLY a valid JSON object strictly matching this format. Do not include markdown.
{
  "title": "Module Assessment Quiz",
  "passing_score": 80,
  "questions": [
    {
      "question_text": "What is the main topic?",
      "options": "Option A, Option B, Option C, Option D",
      "correct_answer": "Option A"
    }
  ]
}
EOT;

        try {
            $jsonResponse = \App\Services\AIService::generateJson($prompt);
            $data = json_decode($jsonResponse, true);
            
            if (!$data || !isset($data['questions']) || !is_array($data['questions'])) {
                $data = $this->fallbackModuleQuizData($module);
            }

            $quiz = $module->quizzes()->create([
                'title' => $data['title'] ?? 'AI Generated Quiz',
                'passing_score' => $data['passing_score'] ?? 75,
            ]);

            foreach ($data['questions'] as $q) {
                $quiz->questions()->create([
                    'type' => 'multiple_choice',
                    'question_text' => $q['question_text'] ?? 'Review the module content and select the best answer.',
                    'options' => $this->normalizeQuizOptions($q['options'] ?? []),
                    'correct_answer' => $q['correct_answer'] ?? 'Review the module',
                ]);
            }

            return redirect()->back()->with('success', 'AI generated the quiz successfully!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Quiz Gen Error: ' . $e->getMessage());

            $data = $this->fallbackModuleQuizData($module);
            $quiz = $module->quizzes()->create([
                'title' => $data['title'],
                'passing_score' => $data['passing_score'],
            ]);

            foreach ($data['questions'] as $q) {
                $quiz->questions()->create([
                    'type' => 'multiple_choice',
                    'question_text' => $q['question_text'],
                    'options' => $this->normalizeQuizOptions($q['options']),
                    'correct_answer' => $q['correct_answer'],
                ]);
            }

            return redirect()->back()->with('success', 'Quiz generated with reliable fallback content.');
        }
    }

    public function storeModuleQuiz(Request $request, LearningModule $module)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'passing_score' => 'required|integer|min:0|max:100',
        ]);

        $module->quizzes()->create([
            'title' => $request->title,
            'passing_score' => $request->passing_score,
        ]);

        return redirect()->back()->with('success', 'Quiz created successfully');
    }

    public function destroyModuleQuiz(\App\Models\ModuleQuiz $quiz)
    {
        $quiz->delete();
        return redirect()->back()->with('success', 'Quiz deleted successfully');
    }

    public function storeModuleQuizQuestion(Request $request, \App\Models\ModuleQuiz $quiz)
    {
        $request->validate([
            'type' => 'required|string',
            'question_text' => 'required|string',
            'correct_answer' => 'required|string',
        ]);

        $options = $request->options ? array_map('trim', explode(',', $request->options)) : null;

        $quiz->questions()->create([
            'type' => $request->type,
            'question_text' => $request->question_text,
            'options' => $options,
            'correct_answer' => $request->correct_answer,
        ]);

        return redirect()->back()->with('success', 'Question added to quiz');
    }

    public function destroyModuleQuizQuestion(\App\Models\ModuleQuizQuestion $question)
    {
        $question->delete();
        return redirect()->back()->with('success', 'Question deleted successfully');
    }

    // Resources
    public function storeModuleResource(Request $request, LearningModule $module)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,docx,pptx|max:10240',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('module_resources', 'public');
            
            $module->resources()->create([
                'title' => $request->title,
                'file_path' => $path,
                'file_type' => $request->file('file')->getClientOriginalExtension(),
            ]);
        }

        return redirect()->back()->with('success', 'Resource uploaded successfully');
    }

    public function destroyModuleResource(\App\Models\ModuleResource $resource)
    {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($resource->file_path);
        $resource->delete();
        return redirect()->back()->with('success', 'Resource deleted successfully');
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
                    'body' => $activity->description ?: ($activity->user ? $activity->user->name : 'A user activity was recorded.'),
                    'url' => route('admin.dashboard'),
                ];
            });

        $html = '';
        if ($latestActivities->isEmpty()) {
            $html = '<div class="p-3 text-center text-muted" style="font-size:0.85rem;">No recent activities.</div>';
        } else {
            foreach ($latestActivities as $activity) {
                $time = $activity->created_at->diffForHumans();
                $userName = htmlspecialchars($activity->user ? $activity->user->name : 'System', ENT_QUOTES, 'UTF-8');
                $time = htmlspecialchars($time, ENT_QUOTES, 'UTF-8');
                $isNew = is_null($activity->read_at);
                $description = htmlspecialchars($activity->description ?: $activity->action, ENT_QUOTES, 'UTF-8');
                
                $html .= '
                <div class="admin-activity-item '.($isNew ? 'is-unread' : '').'" data-id="'.$activity->id.'">
                    <div class="admin-activity-ico"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div class="admin-activity-copy">
                        <div class="admin-activity-row-head">
                            <strong>'.$userName.'</strong>
                            <small>'.$time.'</small>
                        </div>
                        <span>'.$description.'</span>
                        <div class="admin-activity-row-actions">
                            '.($isNew ? '<button class="admin-activity-link-btn" type="button" onclick="markActivityRead('.$activity->id.', event)">Mark as read</button>' : '').'
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

    public function attachGameLevel(Request $request, LearningModule $module)
    {
        $request->validate([
            'game_level_id' => 'required|exists:game_levels,id',
        ]);

        if (!$module->gameLevels->contains($request->game_level_id)) {
            $module->gameLevels()->attach($request->game_level_id);
            return redirect()->back()->with('success', 'Philippines interview learning game attached successfully.');
        }

        return redirect()->back()->with('warning', 'Philippines interview learning game is already attached.');
    }

    public function detachGameLevel(LearningModule $module, \App\Models\GameLevel $gameLevel)
    {
        $module->gameLevels()->detach($gameLevel->id);
        return redirect()->back()->with('success', 'Philippines interview learning game detached successfully.');
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
            ['description' => 'Philippines interview learning module category', 'status' => 'active']
        );
    }

    private function fallbackModuleData(string $topic): array
    {
        $topic = $this->cleanFallbackText($topic, 'Philippines Interview Readiness');

        return [
            'title' => $this->cleanFallbackText($topic . ' Essentials', 'Philippines Interview Readiness Essentials'),
            'category' => 'General',
            'difficulty' => 'Beginner',
            'description' => "A practical Philippines interview module that tells learners what to prepare, write, rehearse, revise, and check for {$topic}.",
            'chapters' => [
                [
                    'title' => 'Prepare the Proof',
                    'content' => "<h3>Prepare the Proof</h3><p>Write one target role, one Philippine interview situation where this topic matters, and one result a local HR, school, or hiring panel should hear.</p><ul><li>Pick a real school, internship, BPO, freelance, or workplace example.</li><li>Name your responsibility in one sentence.</li><li>List the evidence you can honestly explain, such as a result, lesson, metric, or customer impact.</li></ul>",
                ],
                [
                    'title' => 'Rehearse and Check',
                    'content' => "<h3>Rehearse and Check</h3><p>Draft a short answer using context, action, result, and reflection, then rehearse it aloud until it sounds natural.</p><ul><li>Keep the answer role-relevant for Philippine employers or admissions panels.</li><li>Revise vague phrases into specific actions you personally took.</li><li>Mark the module complete only after the answer includes a clear action, honest evidence, and a confident closing line.</li></ul>",
                ],
            ],
        ];
    }

    private function fallbackModuleAutofillData(LearningModule $module): array
    {
        $title = $this->cleanFallbackText($module->title, 'Philippines Interview Readiness');

        return [
            'description' => "A focused Philippines interview module that turns {$title} into preparation tasks, practice answers, revision steps, and completion checks.",
            'chapters' => [
                [
                    'title' => 'Prepare Your Local Example',
                    'content' => "<h3>Prepare Your Local Example</h3><p>Choose one honest example for {$title} that fits a Philippine HR, school, BPO, IT, or fresh graduate interview.</p><ul><li>Write the situation in one line.</li><li>Write what you personally did.</li><li>Write the result, lesson, or proof that shows readiness.</li></ul>",
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
            'title' => "Chapter {$next}: Philippine Interview Practice Checkpoint",
            'content' => "<h3>Philippine Interview Practice Checkpoint</h3><p>Review the key idea from {$title}, then write a short answer for a Philippine HR, school, BPO, IT, or fresh graduate interview that explains the situation, your action, and the result.</p><ul><li>Use one concrete local example.</li><li>Name your personal contribution.</li><li>End with a lesson, measurable result, or reason you are ready for the role.</li></ul>",
        ];
    }

    private function fallbackModuleQuizData(LearningModule $module): array
    {
        $title = $this->cleanFallbackText($module->title, 'the module');

        return [
            'title' => 'Philippines Interview Module Assessment Quiz',
            'passing_score' => 80,
            'questions' => [
                [
                    'question_text' => "What should a strong Philippines interview answer about {$title} include?",
                    'options' => ['A specific example', 'Only a job title', 'A memorized slogan', 'No result or reflection'],
                    'correct_answer' => 'A specific example',
                ],
                [
                    'question_text' => 'Which structure best supports a local interview answer?',
                    'options' => ['Context, action, result', 'Greeting only', 'A list of unrelated skills', 'A long apology'],
                    'correct_answer' => 'Context, action, result',
                ],
                [
                    'question_text' => 'Why should an answer include measurable impact when available?',
                    'options' => ['It makes evidence clearer', 'It makes the answer longer', 'It avoids the question', 'It replaces preparation'],
                    'correct_answer' => 'It makes evidence clearer',
                ],
            ],
        ];
    }

    private function normalizeQuizOptions($options): array
    {
        if (is_string($options)) {
            $options = explode(',', $options);
        }

        if (!is_array($options)) {
            $options = [];
        }

        $options = array_values(array_filter(array_map(
            fn ($option) => trim((string) $option),
            $options
        )));

        return $options ?: ['Review the module', 'Skip the lesson', 'Ignore examples', 'Avoid structure'];
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
