<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Question;
use App\Models\LearningModule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class AdminController extends Controller
{
    public function dashboard()
    {
        $registeredUsersCount = \App\Models\User::count();
        $activeTodayCount = \App\Models\InterviewSession::whereDate('created_at', today())->distinct('user_id')->count() 
            ?: \App\Models\User::whereDate('updated_at', '>=', now()->subDay())->count();
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

        // Activity Logs Mock based on recent users
        $recentActivities = \App\Models\User::orderBy('created_at', 'desc')->take(4)->get()->map(function($user) {
            return [
                'text' => 'User registered: ' . $user->name,
                'time' => $user->created_at->diffForHumans()
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
            'activeTodayCount',
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

    // Category CRUD
    public function storeCategory(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:core,game',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'status' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
        ]);

        Category::create([
            'title' => $request->title,
            'type' => $request->type,
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
            'type' => 'required|in:core,game',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'status' => 'required|string',
            'is_featured' => 'nullable|boolean',
        ]);

        $category->update([
            'title' => $request->title,
            'type' => $request->type,
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
        $totalQuestions = $category->questions()->count();
        // Mock data for analytics
        $totalInterviews = rand(10, 100); 
        $averageScore = rand(60, 95);
        $popularity = rand(1, 10);

        return view('admin.category_details', compact('category', 'totalQuestions', 'totalInterviews', 'averageScore', 'popularity'));
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
        // Mock data
        $used = rand(500, 3000);
        $avgScore = rand(60, 98);
        return response()->json([
            'used_count' => $used,
            'average_score' => $avgScore
        ]);
    }

    public function generateAiQuestion(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'position' => 'required|string',
            'difficulty' => 'required|string',
        ]);

        $category = Category::find($request->category_id);
        
        // Mock AI Generation
        $mockQuestions = [
            "Explain the concept of {$category->title} in the context of a {$request->position} role.",
            "Can you describe a time when you applied your {$category->title} skills as a {$request->position}?",
            "What is the most challenging {$request->difficulty} level problem you've solved related to {$category->title}?"
        ];
        
        return response()->json([
            'question_text' => $mockQuestions[array_rand($mockQuestions)]
        ]);
    }

    public function questionsDashboard()
    {
        $questions = Question::all();
        $categories = Category::withCount('questions')->get();
        
        $totalQuestions = $questions->count();
        $activeQuestions = $questions->where('status', 'active')->count();
        $totalCategories = $categories->count();
        // Mock most used
        $mostUsedQuestions = $questions->take(3); // In a real app, query by usage

        return view('admin.questions', compact('questions', 'categories', 'totalQuestions', 'activeQuestions', 'totalCategories', 'mostUsedQuestions'));
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

        // Get a default category for the imported questions, or create one
        $category = Category::firstOrCreate(
            ['title' => 'Community Datasets'],
            ['description' => 'Imported from external datasets', 'status' => 'active']
        );

        $questionsToImport = [];

        if ($request->dataset === 'web_dev') {
            $questionsToImport = [
                ['question_text' => 'Explain the concept of closures in JavaScript.', 'type' => 'Technical', 'difficulty' => 'Medium'],
                ['question_text' => 'What are the main differences between React and Vue?', 'type' => 'Technical', 'difficulty' => 'Medium'],
                ['question_text' => 'Describe a time you optimized the performance of a web application.', 'type' => 'Behavioral', 'difficulty' => 'Hard']
            ];
        } elseif ($request->dataset === 'sales') {
            $questionsToImport = [
                ['question_text' => 'How do you handle objections from a potential client?', 'type' => 'Situational', 'difficulty' => 'Medium'],
                ['question_text' => 'Describe your most successful sale. What made it successful?', 'type' => 'Behavioral', 'difficulty' => 'Hard'],
                ['question_text' => 'What CRM tools are you most familiar with?', 'type' => 'Technical', 'difficulty' => 'Easy']
            ];
        } elseif ($request->dataset === 'leadership') {
            $questionsToImport = [
                ['question_text' => 'Tell me about a time you had to resolve a conflict within your team.', 'type' => 'Behavioral', 'difficulty' => 'Medium'],
                ['question_text' => 'How do you motivate a team member who is underperforming?', 'type' => 'Situational', 'difficulty' => 'Hard'],
                ['question_text' => 'Describe your leadership style.', 'type' => 'Personal', 'difficulty' => 'Medium']
            ];
        }

        foreach ($questionsToImport as $q) {
            Question::create([
                'question_text' => $q['question_text'],
                'type' => $q['type'],
                'difficulty' => $q['difficulty'],
                'category_id' => $category->id,
                'status' => 'active'
            ]);
        }

        return redirect()->back()->with('success', count($questionsToImport) . ' questions imported from ' . $request->dataset . ' dataset successfully!');
    }

    public function exportQuestions()
    {
        $questions = Question::with('category')->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=questions.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Category', 'Question', 'Type', 'Difficulty'];

        $callback = function() use($questions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($questions as $question) {
                $row['ID']  = $question->id;
                $row['Category']    = $question->category->title ?? 'N/A';
                $row['Question']  = $question->question_text;
                $row['Type']  = $question->type;
                $row['Difficulty']  = $question->difficulty;

                fputcsv($file, array($row['ID'], $row['Category'], $row['Question'], $row['Type'], $row['Difficulty']));
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

        if ($request->filled('category')) {
            \App\Models\Category::firstOrCreate(
                ['title' => $request->category],
                ['type' => 'Learning', 'status' => 'active']
            );
        }

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

        $categories = \App\Models\Category::pluck('title')->implode(', ');
        $categoryInstruction = $categories ? "Choose one of: $categories" : "General";

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
                return redirect()->back()->with('error', 'AI failed to generate a valid module format.');
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
                        'title' => $chapterData['title'],
                        'content' => $chapterData['content'],
                        'order' => $index + 1,
                    ]);
                }
            }

            return redirect()->route('admin.modules.edit', $module->id)->with('success', 'AI Module generated successfully! You can now review and publish it.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Module Generation Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate module: ' . $e->getMessage());
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
                return redirect()->back()->with('error', 'AI failed to generate valid content.');
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
            return redirect()->back()->with('error', 'Failed to autofill module: ' . $e->getMessage());
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
        
        $categories = \App\Models\Category::pluck('title')->filter()->unique()->values();

        return view('admin.modules', compact('modules', 'totalModules', 'publishedModules', 'draftModules', 'totalResources', 'mostViewedModule', 'categories'));
    }

    public function editModule(LearningModule $module)
    {
        $module->load(['chapters', 'resources', 'quizzes.questions', 'activities', 'gameLevels']);
        $allGameLevels = \App\Models\GameLevel::orderBy('level_number', 'asc')->get();
        $categories = \App\Models\Category::pluck('title')->filter()->unique()->values();
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

        if ($request->filled('category')) {
            \App\Models\Category::firstOrCreate(
                ['title' => $request->category],
                ['type' => 'Learning', 'status' => 'active']
            );
        }

        $module->update([
            'title' => $request->title,
            'category' => $request->category,
            'difficulty' => $request->difficulty,
            'description' => $request->description,
            'status' => $request->status ?? 'draft',
            'is_featured' => $request->has('is_featured'),
        ]);

        return redirect()->back()->with('success', 'Module updated successfully');
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
            
            if (!$data || !isset($data['questions'])) {
                return redirect()->back()->with('error', 'AI failed to generate a valid quiz.');
            }

            $quiz = $module->quizzes()->create([
                'title' => $data['title'] ?? 'AI Generated Quiz',
                'passing_score' => $data['passing_score'] ?? 75,
            ]);

            foreach ($data['questions'] as $q) {
                $quiz->questions()->create([
                    'type' => 'multiple_choice',
                    'question_text' => $q['question_text'],
                    'options' => $q['options'] ?? '',
                    'correct_answer' => $q['correct_answer'],
                ]);
            }

            return redirect()->back()->with('success', 'AI generated the quiz successfully!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Quiz Gen Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate quiz: ' . $e->getMessage());
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

        $options = $request->options ? explode(',', $request->options) : null;

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
                $userName = $activity->user ? $activity->user->name : 'System';
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
}
