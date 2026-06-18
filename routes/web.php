<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InterviewController;

use App\Http\Controllers\UserController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');

    Route::get('/interview/setup', function () {
        return view('interview.setup');
    })->name('interview.setup');

    Route::get('/interview/session', function () {
        return view('interview.session');
    })->name('interview.session');

    // User Side Features
    Route::get('/account', [UserController::class, 'account'])->name('user.account');
    Route::get('/notifications', [UserController::class, 'notifications'])->name('user.notifications');
    Route::get('/feedback', [UserController::class, 'feedback'])->name('user.feedback');
    Route::get('/coach', [UserController::class, 'coach'])->name('user.coach');
    Route::post('/coach/chat', [UserController::class, 'coachChat'])->name('user.coach.chat');
    Route::get('/coach/conversation/{id}', [UserController::class, 'loadCoachConversation'])->name('user.coach.load');
    Route::delete('/coach/conversation/{id}', [UserController::class, 'deleteCoachConversation'])->name('user.coach.delete');
    Route::get('/learning', [UserController::class, 'learning'])->name('user.learning');
    Route::get('/learning/module/{id}', [UserController::class, 'learningModule'])->name('user.learning.module');
    Route::get('/learning/star-method', [UserController::class, 'learningStar'])->name('user.learning.star');
    Route::get('/learning/library', [UserController::class, 'learningLibrary'])->name('user.learning.library');
    Route::get('/learning/quiz', [UserController::class, 'learningQuiz'])->name('user.learning.quiz');
    Route::get('/learning/assistant', [UserController::class, 'learningAssistant'])->name('user.learning.assistant');
    Route::get('/drills/voice', [UserController::class, 'voiceRehearsal'])->name('user.drills.voice');
    Route::get('/progress', [UserController::class, 'progress'])->name('user.progress');
    Route::get('/session/{id}/review', [UserController::class, 'review'])->name('user.review');
    Route::get('/reports', [UserController::class, 'reports'])->name('user.reports');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/admin/users', function () {
        return view('admin.users', ['users' => \App\Models\User::all()]);
    })->name('admin.users');
    
    Route::get('/admin/categories', function () {
        return view('admin.categories', ['categories' => \App\Models\Category::all()]);
    })->name('admin.categories');
    
    Route::get('/admin/questions', function () {
        return view('admin.questions', ['questions' => \App\Models\Question::all(), 'categories' => \App\Models\Category::all()]);
    })->name('admin.questions');
    
    Route::get('/admin/modules', function () {
        return view('admin.modules', ['modules' => \App\Models\LearningModule::all()]);
    })->name('admin.modules');
});

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::post('/admin/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
Route::post('/admin/questions', [AdminController::class, 'storeQuestion'])->name('admin.questions.store');
Route::post('/admin/modules', [AdminController::class, 'storeModule'])->name('admin.modules.store');

Route::post('/interview/start', [InterviewController::class, 'start'])->name('interview.start');
Route::post('/interview/answer', [InterviewController::class, 'answer'])->name('interview.answer');
Route::post('/interview/save-state', [InterviewController::class, 'saveSessionState'])->name('interview.saveState');
Route::post('/interview/finish', [InterviewController::class, 'finish'])->name('interview.finish');
