<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\ContactController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminSettingController;

Route::get('/', function () {
    if (Auth::check()) {
        if (Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

// Auth Routes
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('/login', function () {
    return redirect('/');
});
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/request-reactivation', [AuthController::class, 'requestReactivation'])->name('request.reactivation');

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Public Shared Session Route
Route::get('/shared/{token}', [InterviewController::class, 'sharedReview'])->name('shared.review');

// Contact Form Route
Route::post('/contact/send', [ContactController::class, 'send'])->name('contact.send');

// User Routes
Route::middleware(['auth', 'user'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');

    Route::get('/interview/setup', function () {
        $categories = \App\Models\Category::where('status', 'active')->get();
        return view('interview.setup', compact('categories'));
    })->name('interview.setup');

    Route::get('/interview/session', function () {
        return view('interview.session');
    })->name('interview.session');

    Route::post('/interview/start', [InterviewController::class, 'start'])->name('interview.start');
    Route::post('/interview/answer', [InterviewController::class, 'answer'])->name('interview.answer');
    Route::post('/interview/save-state', [InterviewController::class, 'saveSessionState'])->name('interview.saveState');
    Route::post('/interview/finish', [InterviewController::class, 'finish'])->name('interview.finish');
    Route::get('/interview/{id}/review', [InterviewController::class, 'review'])->name('interview.review');
    Route::post('/session/{id}/share', [InterviewController::class, 'toggleShare'])->name('interview.toggleShare');

    // User Side Features
    Route::get('/account', [UserController::class, 'account'])->name('user.account');
    Route::post('/account/profile', [UserController::class, 'updateProfile'])->name('user.account.profile');
    Route::post('/account/password', [UserController::class, 'updatePassword'])->name('user.account.password');
    Route::post('/account/delete', [UserController::class, 'deleteAccount'])->name('user.account.delete');
    
    Route::get('/notifications', [UserController::class, 'notifications'])->name('user.notifications');
    Route::get('/notifications/fetch', [UserController::class, 'fetchNotifications'])->name('user.notifications.fetch');
    Route::post('/notifications/read-all', [UserController::class, 'markAllNotificationsAsRead'])->name('user.notifications.readAll');
    Route::delete('/notifications/clear-all', [UserController::class, 'clearAllNotifications'])->name('user.notifications.clearAll');
    Route::post('/notifications/{id}/read', [UserController::class, 'markNotificationAsRead'])->name('user.notifications.read');
    Route::delete('/notifications/{id}', [UserController::class, 'deleteNotification'])->name('user.notifications.delete');
    Route::get('/feedback', [UserController::class, 'feedback'])->name('user.feedback');
    Route::get('/coach', [UserController::class, 'coach'])->name('user.coach');
    Route::post('/coach/chat', [UserController::class, 'coachChat'])->name('user.coach.chat');
    Route::get('/coach/conversation/{id}', [UserController::class, 'loadCoachConversation'])->name('user.coach.load');
    Route::delete('/coach/conversation/{id}', [UserController::class, 'deleteCoachConversation'])->name('user.coach.delete');
    Route::get('/learning', [UserController::class, 'learning'])->name('user.learning');
    
    // Arena Gamification Routes
    Route::post('/arena/level/{id}/start', [\App\Http\Controllers\ArenaController::class, 'startLevel'])->name('user.arena.start');
    
    Route::get('/learning/assistant', [UserController::class, 'learningAssistant'])->name('user.learning.assistant');
    Route::get('/drills/voice', [UserController::class, 'voiceRehearsal'])->name('user.drills.voice');
    Route::get('/progress', [UserController::class, 'progress'])->name('user.progress');
    Route::get('/session/{id}/review', [UserController::class, 'review'])->name('user.review');
    Route::get('/reports', [UserController::class, 'reports'])->name('user.reports');
    Route::get('/community/leaderboard', [UserController::class, 'leaderboard'])->name('user.leaderboard');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // System Settings
    Route::get('/admin/settings', [AdminSettingController::class, 'index'])->name('admin.settings.index');
    Route::post('/admin/settings', [AdminSettingController::class, 'update'])->name('admin.settings.update');

    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
        Route::post('/{user}/approve-reactivation', [AdminUserController::class, 'approveReactivation'])->name('approve-reactivation');
    });
    
    Route::get('/admin/categories', function () {
        return view('admin.categories', ['categories' => \App\Models\Category::all()]);
    })->name('admin.categories');
    
    // Admin Routes - Categories
    Route::post('/admin/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
    Route::put('/admin/categories/{category}', [AdminController::class, 'updateCategory'])->name('admin.categories.update');
    Route::delete('/admin/categories/{category}', [AdminController::class, 'destroyCategory'])->name('admin.categories.destroy');
    Route::patch('/admin/categories/{category}/status', [AdminController::class, 'toggleCategoryStatus'])->name('admin.categories.status');
    Route::get('/admin/categories/{category}/details', [AdminController::class, 'categoryDetails'])->name('admin.categories.details');

    Route::get('/admin/questions', [AdminController::class, 'questionsDashboard'])->name('admin.questions');
    
    // Admin Routes - Questions
    Route::post('/admin/questions', [AdminController::class, 'storeQuestion'])->name('admin.questions.store');
    Route::put('/admin/questions/{question}', [AdminController::class, 'updateQuestion'])->name('admin.questions.update');
    Route::delete('/admin/questions/{question}', [AdminController::class, 'destroyQuestion'])->name('admin.questions.destroy');
    Route::post('/admin/questions/bulk-delete', [AdminController::class, 'bulkDestroyQuestions'])->name('admin.questions.bulk-delete');
    Route::patch('/admin/questions/{question}/status', [AdminController::class, 'toggleQuestionStatus'])->name('admin.questions.status');
    Route::get('/admin/questions/{question}/analytics', [AdminController::class, 'questionAnalytics'])->name('admin.questions.analytics');
    Route::post('/admin/questions/ai-generate', [AdminController::class, 'generateAiQuestion'])->name('admin.questions.ai-generate');
    Route::post('/admin/questions/import', [AdminController::class, 'importQuestions'])->name('admin.questions.import');
    Route::post('/admin/questions/import-dataset', [AdminController::class, 'importDataset'])->name('admin.questions.import-dataset');
    Route::get('/admin/questions/export', [AdminController::class, 'exportQuestions'])->name('admin.questions.export');

    Route::get('/admin/modules', [AdminController::class, 'modulesDashboard'])->name('admin.modules');
    
    // Admin Routes - Modules
    Route::get('/admin/modules/{module}/edit', [AdminController::class, 'editModule'])->name('admin.modules.edit');
    Route::post('/admin/modules', [AdminController::class, 'storeModule'])->name('admin.modules.store');
    Route::put('/admin/modules/{module}', [AdminController::class, 'updateModule'])->name('admin.modules.update');
    Route::delete('/admin/modules/{module}', [AdminController::class, 'destroyModule'])->name('admin.modules.destroy');
    Route::post('/admin/modules/{module}/arena-levels', [AdminController::class, 'attachArenaLevel'])->name('admin.modules.arena-levels.store');
    Route::delete('/admin/modules/{module}/arena-levels/{arenaLevel}', [AdminController::class, 'detachArenaLevel'])->name('admin.modules.arena-levels.destroy');

    // Admin Routes - Arena Games
    Route::get('/admin/arena', [\App\Http\Controllers\AdminArenaController::class, 'index'])->name('admin.arena');
    Route::post('/admin/arena', [\App\Http\Controllers\AdminArenaController::class, 'store'])->name('admin.arena.store');
    Route::post('/admin/arena/generate', [\App\Http\Controllers\AdminArenaController::class, 'generate'])->name('admin.arena.generate');
    Route::put('/admin/arena/{arena_level}', [\App\Http\Controllers\AdminArenaController::class, 'update'])->name('admin.arena.update');
    Route::delete('/admin/arena/{arena_level}', [\App\Http\Controllers\AdminArenaController::class, 'destroy'])->name('admin.arena.destroy');

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
        Route::get('/providers', [\App\Http\Controllers\AdminAiController::class, 'providers'])->name('providers');
        Route::post('/providers', [\App\Http\Controllers\AdminAiController::class, 'storeProvider'])->name('providers.store');
        Route::put('/providers/{provider}', [\App\Http\Controllers\AdminAiController::class, 'updateProvider'])->name('providers.update');
        Route::delete('/providers/{provider}', [\App\Http\Controllers\AdminAiController::class, 'destroyProvider'])->name('providers.destroy');
        Route::post('/providers/{provider}/primary', [\App\Http\Controllers\AdminAiController::class, 'setPrimaryProvider'])->name('providers.primary');
        Route::post('/providers/{provider}/fallback', [\App\Http\Controllers\AdminAiController::class, 'setFallbackProvider'])->name('providers.fallback');
    });

    // Admin Notifications & Activities
    Route::get('/admin/api/latest-activities', [\App\Http\Controllers\AdminController::class, 'fetchLatestActivities'])->name('admin.api.latest-activities');
    Route::post('/admin/api/activities/mark-all-read', [\App\Http\Controllers\AdminController::class, 'markAllActivitiesRead']);
    Route::delete('/admin/api/activities/clear-all', [\App\Http\Controllers\AdminController::class, 'clearAllActivities']);
    Route::post('/admin/api/activities/{id}/mark-read', [\App\Http\Controllers\AdminController::class, 'markActivityRead']);
    Route::delete('/admin/api/activities/{id}', [\App\Http\Controllers\AdminController::class, 'deleteActivity']);
    
    Route::get('/admin/notifications', [\App\Http\Controllers\AdminNotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('/admin/notifications/send', [\App\Http\Controllers\AdminNotificationController::class, 'store'])->name('admin.notifications.store');
    Route::delete('/admin/notifications/{id}', [\App\Http\Controllers\AdminNotificationController::class, 'destroy'])->name('admin.notifications.destroy');
});

Route::get('/setup-db', function() {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'ArenaLevelSeeder', '--force' => true]);
    return 'Database Migrated and Seeded Successfully!';
});
