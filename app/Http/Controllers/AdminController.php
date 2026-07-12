<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Score;
use App\Models\LearningModule;
use App\Models\AiProvider;
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
        
        $recentSessions = \App\Models\InterviewSession::with(['user', 'category', 'score'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        // Top users based on highest score
        $leaderboard = \App\Models\Score::select('scores.*', 'users.name as user_name', 'users.id as user_id', 'users.email')
            ->join('interview_sessions', 'scores.interview_session_id', '=', 'interview_sessions.id')
            ->join('users', 'interview_sessions.user_id', '=', 'users.id')
            ->orderBy('scores.overall_readiness_score', 'desc')
            ->take(3)
            ->get();

        // Users needing support (< 60 score)
        $usersNeedingSupport = \App\Models\Score::select('scores.*', 'users.name as user_name', 'users.id as user_id')
            ->join('interview_sessions', 'scores.interview_session_id', '=', 'interview_sessions.id')
            ->join('users', 'interview_sessions.user_id', '=', 'users.id')
            ->where('scores.overall_readiness_score', '<', 60)
            ->orderBy('scores.overall_readiness_score', 'asc')
            ->take(3)
            ->get();

        // Avg performance metrics
        $avgClarity = round(\App\Models\Score::avg('clarity_score') ?? 0);
        $avgRelevance = round(\App\Models\Score::avg('relevance_score') ?? 0);
        $avgGrammar = round(\App\Models\Score::avg('grammar_score') ?? 0);
        $avgProfessionalism = round(\App\Models\Score::avg('professionalism_score') ?? 0);

        $recentActivities = \App\Models\ActivityLog::orderBy('created_at', 'desc')->take(4)->get()->map(function($activity) {
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

        return view('admin.dashboard', compact(
            'registeredUsersCount',
            'onlineTodayCount',
            'mockInterviewsCount',
            'aiFeedbacksCount',
            'modulesCompletedCount',
            'recentSessions',
            'leaderboard',
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

        return view('admin.category_details', compact(
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

        $questionText = null;
        $source = 'fallback';

        if ($this->providerCanGenerateQuestions($provider)) {
            try {
                $generated = AIService::generateQuestions(
                    1,
                    $position,
                    $difficulty,
                    $category->title,
                    $provider,
                    null,
                    null,
                    null,
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
                ? $this->fallbackInterviewQuestion($category, $position, $difficulty)
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

        return view('admin.questions', compact(
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
        $providerKeys = [
            'openai' => 'OPENAI_API_KEY',
            'gemini' => 'GEMINI_API_KEY',
            'cohere' => 'COHERE_API_KEY',
            'groq' => 'GROQ_API_KEY',
            'openrouter' => 'OPENROUTER_API_KEY',
            'claude' => 'ANTHROPIC_API_KEY',
            'wisdomgate' => 'WISDOMGATE_API_KEY',
        ];

        if (!isset($providerKeys[$provider])) {
            return false;
        }

        if ($provider === 'openai' && AiProvider::where('name', 'like', '%OpenAI%')->where('status', 'active')->whereNotNull('api_key')->exists()) {
            return true;
        }

        return filled(env($providerKeys[$provider]));
    }

    private function defaultQuestionProvider(): string
    {
        $primary = AiProvider::where('is_primary', true)->where('status', 'active')->first();
        $provider = $primary?->name ?: env('AI_PROVIDER', 'gemini');

        return $this->normalizeQuestionProvider($provider);
    }

    private function normalizeQuestionProvider(?string $provider): string
    {
        $provider = strtolower(trim((string) $provider));
        $provider = str_replace([' ', '_'], '', $provider);

        return match ($provider) {
            'local' => 'local',
            'openai', 'chatgpt', 'gpt' => 'openai',
            'google', 'googlegemini', 'gemini' => 'gemini',
            'anthropic', 'claude' => 'claude',
            'groq' => 'groq',
            'openrouter' => 'openrouter',
            'wisdomgate' => 'wisdomgate',
            'cohere' => 'cohere',
            default => 'gemini',
        };
    }

    private function questionProviderOptions(): array
    {
        $providers = [
            'openai' => 'OpenAI',
            'gemini' => 'Gemini',
            'groq' => 'Groq',
            'claude' => 'Claude',
            'openrouter' => 'OpenRouter',
            'wisdomgate' => 'WisdomGate',
            'cohere' => 'Cohere',
        ];

        return collect($providers)
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'enabled' => $this->providerCanGenerateQuestions($key),
                'is_default' => $key === $this->defaultQuestionProvider(),
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
                ['title' => $q['category'] ?? 'Community Datasets'],
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

        $prompt = "Create a comprehensive educational learning module about: " . $request->prompt . ". 
        Return ONLY a JSON object with the following structure:
        {
            \"title\": \"Module Title\",
            \"description\": \"Short summary of the module\",
            \"difficulty\": \"Beginner\",
            \"category\": \"$categoryInstruction\",
            \"chapters\": [
                {
                    \"title\": \"Chapter 1: Intro\",
                    \"content\": \"Detailed reading material for chapter 1 (at least 3 paragraphs)\"
                },
                {
                    \"title\": \"Chapter 2: Deep Dive\",
                    \"content\": \"Detailed reading material for chapter 2...\"
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
        $prompt = "Create comprehensive content for an educational learning module titled: '" . $module->title . "'. 
        The category is '" . $module->category . "' and difficulty is '" . $module->difficulty . "'.
        Return ONLY a JSON object with the following structure:
        {
            \"description\": \"A professional, detailed summary of the module (3-4 sentences)\",
            \"chapters\": [
                {
                    \"title\": \"Chapter 1: ...\",
                    \"content\": \"Detailed reading material for this chapter (at least 3 paragraphs)\"
                },
                {
                    \"title\": \"Chapter 2: ...\",
                    \"content\": \"Detailed reading material for this chapter...\"
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

        return view('admin.modules', compact('modules', 'totalModules', 'publishedModules', 'draftModules', 'totalResources', 'mostViewedModule', 'categories'));
    }

    public function editModule(LearningModule $module)
    {
        $module->load(['chapters', 'resources', 'quizzes.questions', 'activities', 'gameLevels']);
        $allGameLevels = \App\Models\GameLevel::orderBy('level_number', 'asc')->get();
        $categories = $this->learningCategoryNames();
        return view('admin.module_edit', compact('module', 'allGameLevels', 'categories'));
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
        
        $prompt = "Generate exactly 1 new chapter for a learning module.
        Module Title: {$module->title}
        Module Description: {$module->description}
        Existing Chapters: " . ($existingChapters ?: "None yet.") . "
        Generate the next logical chapter. Provide a JSON response with the following exact keys:
        {
            \"title\": \"Chapter Title\",
            \"content\": \"Comprehensive HTML formatted lesson content (use h3, p, ul, li). Be detailed.\"
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
        $prompt = "Create a 5-question multiple choice quiz based on the following learning module content.\n";
        $prompt .= "Module Title: " . $module->title . "\n";
        $prompt .= "Module Description: " . $module->description . "\n";
        foreach($module->chapters as $chapter) {
            $prompt .= "Chapter '" . $chapter->title . "' Content: " . strip_tags($chapter->content) . "\n";
        }
        
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

        $html = '';
        if ($latestActivities->isEmpty()) {
            $html = '<div class="p-3 text-center text-muted" style="font-size:0.85rem;">No recent activities.</div>';
        } else {
            foreach ($latestActivities as $activity) {
                $time = $activity->created_at->diffForHumans();
                $userName = htmlspecialchars($activity->user ? $activity->user->name : 'System', ENT_QUOTES, 'UTF-8');
                $time = htmlspecialchars($time, ENT_QUOTES, 'UTF-8');
                $isNew = is_null($activity->read_at);
                $bgClass = $isNew ? 'rgba(59,130,246,0.1)' : 'transparent';
                
                $html .= '
                <div class="dropdown-item d-flex flex-column gap-1 border-bottom admin-activity-item" data-id="'.$activity->id.'" style="background: '.$bgClass.'; padding: 12px 16px; border-color: var(--bd) !important; white-space: normal;">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <span class="fw-bold text-truncate" style="font-size: 0.85rem; color: var(--tx);">'.$userName.'</span>
                        <div class="d-flex gap-2 align-items-center flex-shrink-0">
                            <span style="font-size: 0.7rem; color: var(--tx3);">'.$time.'</span>
                            '.($isNew ? '<button class="btn btn-sm p-0 m-0 act-mark-read text-primary" onclick="markActivityRead('.$activity->id.', event)" title="Mark as read"><i class="fa-solid fa-circle text-primary" style="font-size:8px;"></i></button>' : '').'
                            <button class="btn btn-sm p-0 m-0 act-delete text-danger" onclick="deleteActivity('.$activity->id.', event)" title="Delete"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--tx2);">'.htmlspecialchars($activity->description ?: $activity->action).'</div>
                </div>';
            }
        }

        return response()->json([
            'html' => $html,
            'new_count' => $newCount,
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
            return redirect()->back()->with('success', 'Learning Game attached successfully.');
        }

        return redirect()->back()->with('warning', 'Learning Game is already attached.');
    }

    public function detachGameLevel(LearningModule $module, \App\Models\GameLevel $gameLevel)
    {
        $module->gameLevels()->detach($gameLevel->id);
        return redirect()->back()->with('success', 'Learning Game detached successfully.');
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
            ['description' => 'Learning module category', 'status' => 'active']
        );
    }

    private function fallbackModuleData(string $topic): array
    {
        $topic = $this->cleanFallbackText($topic, 'Interview Readiness');

        return [
            'title' => $this->cleanFallbackText($topic . ' Essentials', 'Interview Readiness Essentials'),
            'category' => 'General',
            'difficulty' => 'Beginner',
            'description' => "A practical learning module for building confidence and structure around {$topic}.",
            'chapters' => [
                [
                    'title' => 'Foundations',
                    'content' => "<h3>Foundations</h3><p>Start by defining the skill, the situation where it matters, and the outcome a strong candidate should produce.</p><p>Focus on clear examples, concise wording, and evidence that shows ownership.</p>",
                ],
                [
                    'title' => 'Practice Framework',
                    'content' => "<h3>Practice Framework</h3><p>Prepare answers with a simple structure: context, action, result, and reflection.</p><ul><li>Use specific details.</li><li>Keep the answer role-relevant.</li><li>Close with measurable impact when possible.</li></ul>",
                ],
            ],
        ];
    }

    private function fallbackModuleAutofillData(LearningModule $module): array
    {
        $title = $this->cleanFallbackText($module->title, 'Interview Readiness');

        return [
            'description' => "A focused module for practicing {$title} with structured lessons, examples, and review checkpoints.",
            'chapters' => [
                [
                    'title' => 'Core Concepts',
                    'content' => "<h3>Core Concepts</h3><p>Clarify the main ideas behind {$title} and connect them to real interview expectations.</p><p>Use examples that show judgment, communication, and measurable impact.</p>",
                ],
                [
                    'title' => 'Applied Practice',
                    'content' => "<h3>Applied Practice</h3><p>Turn the concepts into repeatable practice prompts. Draft one answer, review it for clarity, then revise it to include stronger evidence.</p>",
                ],
            ],
        ];
    }

    private function fallbackModuleChapterData(LearningModule $module): array
    {
        $next = $module->chapters()->count() + 1;
        $title = $this->cleanFallbackText($module->title, 'this module');

        return [
            'title' => "Chapter {$next}: Practice Checkpoint",
            'content' => "<h3>Practice Checkpoint</h3><p>Review the key idea from {$title}, then write a short answer that explains the situation, your action, and the result.</p><ul><li>Use one concrete example.</li><li>Name your personal contribution.</li><li>End with a lesson or measurable result.</li></ul>",
        ];
    }

    private function fallbackModuleQuizData(LearningModule $module): array
    {
        $title = $this->cleanFallbackText($module->title, 'the module');

        return [
            'title' => 'Module Assessment Quiz',
            'passing_score' => 80,
            'questions' => [
                [
                    'question_text' => "What should a strong answer about {$title} include?",
                    'options' => ['A specific example', 'Only a job title', 'A memorized slogan', 'No result or reflection'],
                    'correct_answer' => 'A specific example',
                ],
                [
                    'question_text' => 'Which structure best supports an interview answer?',
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
