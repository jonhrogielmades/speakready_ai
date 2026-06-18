<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Question;
use App\Models\LearningModule;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    // Category Store
    public function storeCategory(Request $request)
    {
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Category::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return redirect()->back();
    }

    // Question Store
    public function storeQuestion(Request $request)
    {

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'question_text' => 'required|string',
        ]);

        Question::create([
            'category_id' => $request->category_id,
            'question_text' => $request->question_text,
            'difficulty' => 'medium', // Default difficulty
        ]);

        return redirect()->back();
    }

    // Module Store
    public function storeModule(Request $request)
    {

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:article,video,exercise',
        ]);

        LearningModule::create([
            'title' => $request->title,
            'type' => $request->type,
            'description' => 'Content placeholder',
        ]);

        return redirect()->back();
    }
}
