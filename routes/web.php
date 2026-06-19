<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\AdminSessionController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminSettingController;

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

    // System Settings
    Route::get('/admin/settings', [AdminSettingController::class, 'index'])->name('admin.settings.index');
    Route::post('/admin/settings', [AdminSettingController::class, 'update'])->name('admin.settings.update');

    Route::get('/admin/users', function () {
        return view('admin.users', ['users' => \App\Models\User::all()]);
    })->name('admin.users');
    
    Route::get('/admin/categories', function () {
        return view('admin.categories', ['categories' => \App\Models\Category::all()]);
    })->name('admin.categories');
    
    Route::get('/admin/questions', [AdminController::class, 'questionsDashboard'])->name('admin.questions');
    
    Route::get('/admin/modules', [AdminController::class, 'modulesDashboard'])->name('admin.modules');
    
    // Admin Session Monitoring
    Route::get('/admin/sessions', [AdminSessionController::class, 'index'])->name('admin.sessions.index');
    Route::get('/admin/sessions/archive', [AdminSessionController::class, 'archiveIndex'])->name('admin.sessions.archive');
    Route::get('/admin/sessions/{session}', [AdminSessionController::class, 'show'])->name('admin.sessions.show');
    Route::get('/admin/sessions/{session}/review', [AdminSessionController::class, 'review'])->name('admin.sessions.review');
    Route::post('/admin/sessions/{session}/flag', [AdminSessionController::class, 'flag'])->name('admin.sessions.flag');
    Route::post('/admin/sessions/{session}/archive', [AdminSessionController::class, 'archive'])->name('admin.sessions.doArchive');
    Route::post('/admin/sessions/{session}/restore', [AdminSessionController::class, 'restore'])->name('admin.sessions.restore');
    Route::get('/admin/reports/sessions/export', [AdminSessionController::class, 'export'])->name('admin.sessions.export');

    // Admin Feedback Audit Features
    Route::get('/admin/feedback', [App\Http\Controllers\AdminFeedbackController::class, 'index'])->name('admin.feedback.index');
    Route::get('/admin/feedback/complaints', [App\Http\Controllers\AdminFeedbackController::class, 'complaints'])->name('admin.feedback.complaints');
    Route::get('/admin/feedback/{answer}', [App\Http\Controllers\AdminFeedbackController::class, 'show'])->name('admin.feedback.show');
    Route::post('/admin/feedback/{answer}/verify', [App\Http\Controllers\AdminFeedbackController::class, 'verify'])->name('admin.feedback.verify');
    Route::patch('/admin/feedback/{answer}/status', [App\Http\Controllers\AdminFeedbackController::class, 'updateStatus'])->name('admin.feedback.status');
    Route::post('/admin/feedback/{answer}/notes', [App\Http\Controllers\AdminFeedbackController::class, 'addNote'])->name('admin.feedback.notes');
    // Admin AI Providers Features
    Route::prefix('admin/ai')->name('admin.ai.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\AdminAiController::class, 'dashboard'])->name('dashboard');
        
        Route::get('/providers', [\App\Http\Controllers\AdminAiController::class, 'providers'])->name('providers');
        Route::post('/providers', [\App\Http\Controllers\AdminAiController::class, 'storeProvider'])->name('providers.store');
        Route::put('/providers/{provider}', [\App\Http\Controllers\AdminAiController::class, 'updateProvider'])->name('providers.update');
        Route::post('/providers/{provider}/primary', [\App\Http\Controllers\AdminAiController::class, 'setPrimaryProvider'])->name('providers.primary');
        Route::post('/providers/{provider}/fallback', [\App\Http\Controllers\AdminAiController::class, 'setFallbackProvider'])->name('providers.fallback');
        
        Route::get('/prompts', [\App\Http\Controllers\AdminAiController::class, 'prompts'])->name('prompts');
        Route::post('/prompts', [\App\Http\Controllers\AdminAiController::class, 'storePrompt'])->name('prompts.store');
        
        Route::get('/settings', [\App\Http\Controllers\AdminAiController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\AdminAiController::class, 'storeSettings'])->name('settings.store');
        
        Route::get('/testing', [\App\Http\Controllers\AdminAiController::class, 'testing'])->name('testing');
        Route::post('/testing/generate', [\App\Http\Controllers\AdminAiController::class, 'testAiResponse'])->name('testing.generate');
        
        Route::get('/logs', [\App\Http\Controllers\AdminAiController::class, 'logs'])->name('logs');
    });
});

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Admin Routes - Categories
Route::post('/admin/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
Route::put('/admin/categories/{category}', [AdminController::class, 'updateCategory'])->name('admin.categories.update');
Route::delete('/admin/categories/{category}', [AdminController::class, 'destroyCategory'])->name('admin.categories.destroy');
Route::patch('/admin/categories/{category}/status', [AdminController::class, 'toggleCategoryStatus'])->name('admin.categories.status');
Route::get('/admin/categories/{category}/details', [AdminController::class, 'categoryDetails'])->name('admin.categories.details');

// Admin Routes - Questions
Route::post('/admin/questions', [AdminController::class, 'storeQuestion'])->name('admin.questions.store');
Route::put('/admin/questions/{question}', [AdminController::class, 'updateQuestion'])->name('admin.questions.update');
Route::delete('/admin/questions/{question}', [AdminController::class, 'destroyQuestion'])->name('admin.questions.destroy');
Route::patch('/admin/questions/{question}/status', [AdminController::class, 'toggleQuestionStatus'])->name('admin.questions.status');
Route::get('/admin/questions/{question}/analytics', [AdminController::class, 'questionAnalytics'])->name('admin.questions.analytics');
Route::post('/admin/questions/ai-generate', [AdminController::class, 'generateAiQuestion'])->name('admin.questions.ai-generate');
Route::post('/admin/questions/import', [AdminController::class, 'importQuestions'])->name('admin.questions.import');
Route::get('/admin/questions/export', [AdminController::class, 'exportQuestions'])->name('admin.questions.export');

// Admin Routes - Modules
Route::get('/admin/modules/{module}/edit', [AdminController::class, 'editModule'])->name('admin.modules.edit');
Route::post('/admin/modules', [AdminController::class, 'storeModule'])->name('admin.modules.store');
Route::put('/admin/modules/{module}', [AdminController::class, 'updateModule'])->name('admin.modules.update');
Route::delete('/admin/modules/{module}', [AdminController::class, 'destroyModule'])->name('admin.modules.destroy');

// Admin Routes - Module Chapters
Route::post('/admin/modules/{module}/chapters', [AdminController::class, 'storeModuleChapter'])->name('admin.modules.chapters.store');
Route::put('/admin/modules/chapters/{chapter}', [AdminController::class, 'updateModuleChapter'])->name('admin.modules.chapters.update');
Route::delete('/admin/modules/chapters/{chapter}', [AdminController::class, 'destroyModuleChapter'])->name('admin.modules.chapters.destroy');

// Admin Routes - Module Resources
Route::post('/admin/modules/{module}/resources', [AdminController::class, 'storeModuleResource'])->name('admin.modules.resources.store');
Route::delete('/admin/modules/resources/{resource}', [AdminController::class, 'destroyModuleResource'])->name('admin.modules.resources.destroy');

// Admin Routes - Module Quizzes
Route::post('/admin/modules/{module}/quizzes', [AdminController::class, 'storeModuleQuiz'])->name('admin.modules.quizzes.store');
Route::delete('/admin/modules/quizzes/{quiz}', [AdminController::class, 'destroyModuleQuiz'])->name('admin.modules.quizzes.destroy');

// Admin Routes - Module Quiz Questions
Route::post('/admin/modules/quizzes/{quiz}/questions', [AdminController::class, 'storeModuleQuizQuestion'])->name('admin.modules.quizzes.questions.store');
Route::delete('/admin/modules/quizzes/questions/{question}', [AdminController::class, 'destroyModuleQuizQuestion'])->name('admin.modules.quizzes.questions.destroy');

Route::post('/interview/start', [InterviewController::class, 'start'])->name('interview.start');
Route::post('/interview/answer', [InterviewController::class, 'answer'])->name('interview.answer');
Route::post('/interview/save-state', [InterviewController::class, 'saveSessionState'])->name('interview.saveState');
Route::post('/interview/finish', [InterviewController::class, 'finish'])->name('interview.finish');
