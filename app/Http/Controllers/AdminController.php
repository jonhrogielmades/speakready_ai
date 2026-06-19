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
        return view('admin.dashboard');
    }

    // Category CRUD
    public function storeCategory(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'status' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
        ]);

        Category::create([
            'title' => $request->title,
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
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'status' => 'required|string',
            'is_featured' => 'nullable|boolean',
        ]);

        $category->update([
            'title' => $request->title,
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
        $categories = Category::all();
        
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

    public function modulesDashboard()
    {
        $modules = LearningModule::all();
        $totalModules = $modules->count();
        $publishedModules = $modules->where('status', 'published')->count();
        $draftModules = $modules->where('status', 'draft')->count();
        $totalResources = \App\Models\ModuleResource::count();
        $mostViewedModule = LearningModule::orderBy('views', 'desc')->first();

        return view('admin.modules', compact('modules', 'totalModules', 'publishedModules', 'draftModules', 'totalResources', 'mostViewedModule'));
    }

    public function editModule(LearningModule $module)
    {
        $module->load(['chapters', 'resources', 'quizzes.questions', 'activities']);
        return view('admin.module_edit', compact('module'));
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
}
