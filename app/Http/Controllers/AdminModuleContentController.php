<?php

namespace App\Http\Controllers;

use App\Models\LearningModule;
use App\Models\ModuleQuiz;
use App\Models\ModuleQuizQuestion;
use App\Models\ModulePracticeActivity;
use App\Models\ModuleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminModuleContentController extends Controller
{
    public function storePractice(Request $request, LearningModule $module)
    {
        $module->activities()->create($this->validatedPractice($request));

        return back()->with('success', 'Practice activity added.')->with('active_module_tab', '#practice');
    }

    public function updatePractice(Request $request, LearningModule $module, ModulePracticeActivity $activity)
    {
        abort_unless((int) $activity->learning_module_id === (int) $module->id, 404);
        $activity->update($this->validatedPractice($request));

        return back()->with('success', 'Practice activity updated.')->with('active_module_tab', '#practice');
    }

    public function destroyPractice(LearningModule $module, ModulePracticeActivity $activity)
    {
        abort_unless((int) $activity->learning_module_id === (int) $module->id, 404);
        $activity->delete();

        return back()->with('success', 'Practice activity deleted.')->with('active_module_tab', '#practice');
    }

    public function storeResource(Request $request, LearningModule $module)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,txt,png,jpg,jpeg'],
        ]);
        $path = $data['file']->store('module-resources', 'public');
        $module->resources()->create([
            'title' => trim($data['title']),
            'file_path' => $path,
            'file_type' => strtolower($data['file']->getClientOriginalExtension()),
        ]);

        return back()->with('success', 'Resource added.')->with('active_module_tab', '#resources');
    }

    public function updateResource(Request $request, LearningModule $module, ModuleResource $resource)
    {
        $this->ownsResource($module, $resource);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,txt,png,jpg,jpeg'],
        ]);
        $oldPath = $resource->file_path;
        $values = ['title' => trim($data['title'])];
        if (isset($data['file'])) {
            $values['file_path'] = $data['file']->store('module-resources', 'public');
            $values['file_type'] = strtolower($data['file']->getClientOriginalExtension());
        }
        $resource->update($values);
        if (isset($data['file'])) Storage::disk('public')->delete($oldPath);

        return back()->with('success', 'Resource updated.')->with('active_module_tab', '#resources');
    }

    public function destroyResource(LearningModule $module, ModuleResource $resource)
    {
        $this->ownsResource($module, $resource);
        $path = $resource->file_path;
        $resource->delete();
        Storage::disk('public')->delete($path);

        return back()->with('success', 'Resource deleted.')->with('active_module_tab', '#resources');
    }

    public function storeQuiz(Request $request, LearningModule $module)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['required', 'integer', 'between:1,100'],
        ]);
        $question = $this->validatedQuestion($request);

        DB::transaction(function () use ($module, $data, $question): void {
            $quiz = $module->quizzes()->create([
                'title' => trim($data['title']),
                'passing_score' => $data['passing_score'],
            ]);
            $quiz->questions()->create($question);
        });

        return back()->with('success', 'Quiz added with its first question.')->with('active_module_tab', '#quizzes');
    }

    public function updateQuiz(Request $request, LearningModule $module, ModuleQuiz $quiz)
    {
        $this->ownsQuiz($module, $quiz);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['required', 'integer', 'between:1,100'],
        ]);
        $quiz->update(['title' => trim($data['title']), 'passing_score' => $data['passing_score']]);

        return back()->with('success', 'Quiz updated. Learners must submit it again to use the new passing score.')->with('active_module_tab', '#quizzes');
    }

    public function destroyQuiz(LearningModule $module, ModuleQuiz $quiz)
    {
        $this->ownsQuiz($module, $quiz);
        $quiz->delete();

        return back()->with('success', 'Quiz deleted.')->with('active_module_tab', '#quizzes');
    }

    public function storeQuestion(Request $request, LearningModule $module, ModuleQuiz $quiz)
    {
        $this->ownsQuiz($module, $quiz);
        $quiz->questions()->create($this->validatedQuestion($request));

        return back()->with('success', 'Question added.')->with('active_module_tab', '#quizzes');
    }

    public function updateQuestion(Request $request, LearningModule $module, ModuleQuiz $quiz, ModuleQuizQuestion $question)
    {
        $this->ownsQuestion($module, $quiz, $question);
        $question->update($this->validatedQuestion($request));

        return back()->with('success', 'Question updated.')->with('active_module_tab', '#quizzes');
    }

    public function destroyQuestion(LearningModule $module, ModuleQuiz $quiz, ModuleQuizQuestion $question)
    {
        $this->ownsQuestion($module, $quiz, $question);
        if ($module->status === 'published' && $quiz->questions()->count() <= 1) {
            throw ValidationException::withMessages(['question' => 'A published quiz must keep at least one question. Delete the quiz or return the module to draft first.']);
        }
        $question->delete();

        return back()->with('success', 'Question deleted.')->with('active_module_tab', '#quizzes');
    }

    private function validatedQuestion(Request $request): array
    {
        $data = $request->validate([
            'question_type' => ['required', Rule::in(['multiple_choice', 'true_false', 'short_answer'])],
            'question_text' => ['required', 'string', 'max:2000'],
            'options' => ['nullable', 'string', 'max:5000'],
            'correct_answer' => ['required', 'string', 'max:255'],
        ]);
        $type = $data['question_type'];
        $answer = trim($data['correct_answer']);
        $options = null;
        if ($type === 'multiple_choice') {
            $options = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['options'] ?? '') ?: []))));
            if (count($options) < 2 || count($options) > 6 || ! in_array($answer, $options, true)) {
                throw ValidationException::withMessages(['options' => 'Enter 2 to 6 options on separate lines and use one exact option as the correct answer.']);
            }
        } elseif ($type === 'true_false') {
            $options = ['True', 'False'];
            if (! in_array(strtolower($answer), ['true', 'false'], true)) {
                throw ValidationException::withMessages(['correct_answer' => 'The answer must be True or False.']);
            }
            $answer = ucfirst(strtolower($answer));
        }

        return [
            'type' => $type,
            'question_text' => trim($data['question_text']),
            'options' => $options,
            'correct_answer' => $answer,
        ];
    }

    private function validatedPractice(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        return ['title' => trim($data['title']), 'type' => $data['type'], 'description' => trim($data['description'])];
    }

    private function ownsResource(LearningModule $module, ModuleResource $resource): void
    {
        abort_unless((int) $resource->learning_module_id === (int) $module->id, 404);
    }

    private function ownsQuiz(LearningModule $module, ModuleQuiz $quiz): void
    {
        abort_unless((int) $quiz->learning_module_id === (int) $module->id, 404);
    }

    private function ownsQuestion(LearningModule $module, ModuleQuiz $quiz, ModuleQuizQuestion $question): void
    {
        $this->ownsQuiz($module, $quiz);
        abort_unless((int) $question->module_quiz_id === (int) $quiz->id, 404);
    }
}
