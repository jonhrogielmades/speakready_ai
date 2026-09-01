<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\AdminSettingController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\InterviewPackController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\MentorReviewController;
use App\Http\Controllers\UserApplicationController;
use App\Http\Controllers\UserController;
use App\Services\LandingStatsService;
use App\Support\CareerPlanningSchema;
use App\Support\InterviewAnswerSchema;
use App\Support\InterviewSessionSchema;
use App\Support\QuestionSchema;
use App\Support\ScoreSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function (LandingStatsService $landingStatsService) {
    if (Auth::check()) {
        if (Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('dashboard');
    }

    return mobile_view('welcome', [
        'landingStats' => $landingStatsService->summary(),
    ]);
});

// Auth Routes
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('/login', function () {
    return redirect('/');
});
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/request-reactivation', [AuthController::class, 'requestReactivation'])->name('request.reactivation');
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('guest')->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest')->name('password.update');

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/login', [AuthController::class, 'redirectToGoogle'])->name('auth.google.login');
Route::get('/auth/google/register', [AuthController::class, 'redirectToGoogleRegister'])->name('auth.google.register');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Public Shared Session Route
Route::get('/shared/{token}', [InterviewController::class, 'sharedReview'])->name('shared.review');
Route::post('/shared/{token}/unlock', [InterviewController::class, 'unlockSharedReview'])->name('shared.unlock');
Route::post('/shared/{token}/mentor-comments', [MentorReviewController::class, 'store'])->name('shared.mentor-comments.store');

// Contact Form Route
Route::post('/contact/send', [ContactController::class, 'send'])->name('contact.send');
Route::post('/newsletter/subscribe', [ContactController::class, 'subscribe'])->name('newsletter.subscribe');

// Public Legal Routes
Route::get('/privacy-policy', [LegalPageController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms-of-service', [LegalPageController::class, 'terms'])->name('legal.terms');
Route::get('/security', [LegalPageController::class, 'security'])->name('legal.security');
Route::get('/cookie-preferences', [LegalPageController::class, 'cookies'])->name('legal.cookies');

// User Routes
Route::middleware(['auth', 'user'])->group(function () {
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');

    Route::get('/interview/setup', function () {
        CareerPlanningSchema::ensure();
        InterviewSessionSchema::ensure();
        QuestionSchema::ensure();
        InterviewAnswerSchema::ensure();
        ScoreSchema::ensure();

        $isSupportedInterviewCategory = function ($category): bool {
            $title = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('/', ' / ', (string) $category->title)) ?? ''));

            if (
                str_contains($title, 'bpo')
                || str_contains($title, 'customer')
                || str_contains($title, 'programming')
                || str_contains($title, 'technical')
                || str_contains($title, 'scholar')
                || preg_match('/\bit\b/', $title)
            ) {
                return false;
            }

            return str_contains($title, 'job interview')
                || str_contains($title, 'general job')
                || str_contains($title, 'school admission')
                || str_contains($title, 'college admission')
                || str_contains($title, 'admission interview');
        };

        $categories = Schema::hasTable('categories')
            ? \App\Models\Category::where('status', 'active')
                ->where('type', 'core')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get()
                ->filter($isSupportedInterviewCategory)
                ->values()
            : collect();
        $sourcePacks = \App\Services\QuestionDatasetProvider::all();
        $selectedApplication = null;
        $selectedPack = null;

        if (request()->filled('application')) {
            $selectedApplication = \App\Models\JobApplication::where('user_id', Auth::id())
                ->findOrFail(request()->integer('application'));
        }

        if (request()->filled('pack')) {
            $selectedPack = \App\Models\InterviewPack::where('status', 'active')
                ->findOrFail(request()->integer('pack'));
        }

        return mobile_view('interview.setup', compact('categories', 'sourcePacks', 'selectedApplication', 'selectedPack'));
    })->name('interview.setup');

    Route::get('/interview/session', function () {
        CareerPlanningSchema::ensure();
        InterviewSessionSchema::ensure();
        QuestionSchema::ensure();
        InterviewAnswerSchema::ensure();
        ScoreSchema::ensure();

        $activeInterviewId = session('active_interview_id');

        if (! $activeInterviewId) {
            return redirect()
                ->route('interview.setup')
                ->with('message', 'Start an interview session first.');
        }

        $activeSession = \App\Models\InterviewSession::where('user_id', Auth::id())
            ->where('status', 'in_progress')
            ->find($activeInterviewId);

        if (! $activeSession) {
            session()->forget(['active_interview_id', 'active_interview_provider', 'active_interview_context']);

            return redirect()
                ->route('interview.setup')
                ->with('message', 'Your interview session is no longer active.');
        }

        return mobile_view('interview.session');
    })->name('interview.session');

    Route::post('/interview/start', [InterviewController::class, 'start'])->name('interview.start');
    Route::post('/interview/answer', [InterviewController::class, 'answer'])->name('interview.answer');
    Route::post('/interview/chat-reply', [InterviewController::class, 'chatReply'])->name('interview.chatReply');
    Route::post('/interview/speech', [InterviewController::class, 'speech'])->name('interview.speech');
    Route::post('/interview/transcribe', [InterviewController::class, 'transcribe'])->name('interview.transcribe');
    Route::post('/interview/save-state', [InterviewController::class, 'saveSessionState'])->name('interview.saveState');
    Route::post('/interview/finish', [InterviewController::class, 'finish'])->name('interview.finish');
    Route::post('/interview/abort', [InterviewController::class, 'abortSession'])->name('interview.abort');
    Route::get('/interview/{id}/review', [InterviewController::class, 'review'])->name('interview.review');
    Route::post('/interview/answers/{answer}/retry', [InterviewController::class, 'retryAnswer'])->name('interview.answer.retry');
    Route::post('/session/{id}/share', [InterviewController::class, 'toggleShare'])->name('interview.toggleShare');

    // User Side Features
    Route::get('/account', [UserController::class, 'account'])->name('user.account');
    Route::post('/language', [UserController::class, 'updateLanguage'])->name('user.language.update');
    Route::post('/language/translate', [UserController::class, 'translateLanguage'])->name('user.language.translate');
    Route::post('/account/profile', [UserController::class, 'updateProfile'])->name('user.account.profile');
    Route::post('/account/password', [UserController::class, 'updatePassword'])->name('user.account.password');
    Route::post('/account/delete', [UserController::class, 'deleteAccount'])->name('user.account.delete');

    Route::get('/notifications', [UserController::class, 'notifications'])->name('user.notifications');
    Route::get('/notifications/fetch', [UserController::class, 'fetchNotifications'])->name('user.notifications.fetch');
    Route::post('/notifications/read-all', [UserController::class, 'markAllNotificationsAsRead'])->name('user.notifications.readAll');
    Route::delete('/notifications/clear-all', [UserController::class, 'clearAllNotifications'])->name('user.notifications.clearAll');
    Route::delete('/notifications/activities/clear-all', [UserController::class, 'clearAllActivities'])->name('user.activities.clearAll');
    Route::delete('/notifications/activities/{id}', [UserController::class, 'deleteActivity'])->name('user.activities.delete');
    Route::post('/notifications/{id}/read', [UserController::class, 'markNotificationAsRead'])->name('user.notifications.read');
    Route::delete('/notifications/{id}', [UserController::class, 'deleteNotification'])->name('user.notifications.delete');
    Route::get('/feedback', [UserController::class, 'feedback'])->name('user.feedback');
    Route::get('/coach', [UserController::class, 'coach'])->name('user.coach');
    Route::post('/coach/chat', [UserController::class, 'coachChat'])->name('user.coach.chat');
    Route::get('/coach/conversation/{id}', [UserController::class, 'loadCoachConversation'])->name('user.coach.load');
    Route::delete('/coach/conversation/{id}', [UserController::class, 'deleteCoachConversation'])->name('user.coach.delete');
    Route::delete('/coach/conversations', [UserController::class, 'clearCoachConversations'])->name('user.coach.clear');
    Route::get('/learning', [UserController::class, 'learning'])->name('user.learning');
    Route::get('/skills', [UserController::class, 'skills'])->name('user.skills');
    Route::post('/skills/unlock', [UserController::class, 'unlockPerk'])->name('user.skills.unlock');

    Route::get('/applications', [UserApplicationController::class, 'index'])->name('user.applications.index');
    Route::post('/applications', [UserApplicationController::class, 'store'])->name('user.applications.store');
    Route::put('/applications/{application}', [UserApplicationController::class, 'update'])->name('user.applications.update');
    Route::delete('/applications/{application}', [UserApplicationController::class, 'destroy'])->name('user.applications.destroy');
    Route::get('/applications/{application}/practice', [UserApplicationController::class, 'practice'])->name('user.applications.practice');
    Route::post('/practice-plan/{item}/toggle', [UserApplicationController::class, 'togglePlanItem'])->name('user.practice-plan.toggle');

    Route::get('/packs', [InterviewPackController::class, 'index'])->name('user.packs.index');
    Route::get('/packs/{pack}/practice', [InterviewPackController::class, 'practice'])->name('user.packs.practice');

    // User Learning Modules
    Route::get('/modules', [UserController::class, 'modules'])->name('user.modules.index');
    Route::post('/modules/{id}/progress', [UserController::class, 'updateModuleProgress'])->name('user.modules.progress');
    Route::get('/modules/{id}', [UserController::class, 'moduleShow'])->name('user.modules.show');

    // Arena Gamification Routes
    Route::post('/game/level/{id}/start', [\App\Http\Controllers\GameController::class, 'startLevel'])->name('user.game.start');
    Route::get('/game/match', [\App\Http\Controllers\GameController::class, 'arenaSession'])->name('user.game.match');
    Route::post('/game/answer', [\App\Http\Controllers\GameController::class, 'answer'])->name('user.game.answer');
    Route::post('/game/save-state', [\App\Http\Controllers\GameController::class, 'saveState'])->name('user.game.saveState');
    Route::post('/game/finish', [\App\Http\Controllers\GameController::class, 'finish'])->name('user.game.finish');
    Route::get('/game/certificates/{category}/download', [\App\Http\Controllers\GameController::class, 'downloadCertificate'])->name('user.game.certificate.download');

    Route::get('/learning/assistant', [UserController::class, 'learningAssistant'])->name('user.learning.assistant');
    Route::get('/missions', [UserController::class, 'missions'])->name('user.missions');
    Route::post('/missions/generate', [UserController::class, 'generateMissionTask'])->name('user.missions.generate');
    Route::get('/drills/voice', [UserController::class, 'voiceRehearsal'])->name('user.drills.voice');
    Route::post('/drills/voice/prompt', [UserController::class, 'generateVoicePrompt'])->name('user.drills.voice.prompt');
    Route::post('/drills/voice/analyze', [UserController::class, 'analyzeVoiceSession'])->name('user.drills.voice.analyze');
    Route::post('/drills/voice/save', [UserController::class, 'saveVoiceSession'])->name('user.drills.voice.save');
    Route::delete('/drills/voice/sessions', [UserController::class, 'clearVoiceSessions'])->name('user.drills.voice.clear');
    Route::get('/progress', [UserController::class, 'progress'])->name('user.progress');
    Route::get('/session/{id}/review', [UserController::class, 'review'])->name('user.review');
    Route::get('/session/{session}/export', [UserController::class, 'exportSession'])->name('user.sessions.export');
    Route::delete('/session/{id}', [UserController::class, 'destroySession'])->name('user.sessions.destroy');
    Route::delete('/sessions/clear', [UserController::class, 'clearSessions'])->name('user.sessions.clear');
    Route::get('/reports', [UserController::class, 'reports'])->name('user.reports');
    Route::get('/personal-mastery', [UserController::class, 'personalMastery'])->name('user.leaderboard');
    Route::post('/personal-mastery/stories', [UserController::class, 'storeMasteryStory'])->name('user.mastery.stories.store');
    Route::delete('/personal-mastery/stories/{item}', [UserController::class, 'destroyMasteryStory'])->name('user.mastery.stories.destroy');
    Route::post('/personal-mastery/checklist/{item}/toggle', [UserController::class, 'toggleMasteryChecklist'])->name('user.mastery.checklist.toggle');
    Route::redirect('/community/leaderboard', '/personal-mastery', 301);
});

// Admin Routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    Route::get('/admin/account', [AdminAccountController::class, 'edit'])->name('admin.account');
    Route::post('/admin/account/profile', [AdminAccountController::class, 'updateProfile'])->name('admin.account.profile');
    Route::post('/admin/account/password', [AdminAccountController::class, 'updatePassword'])->name('admin.account.password');

    // System Settings
    Route::get('/admin/settings', [AdminSettingController::class, 'index'])->name('admin.settings.index');
    Route::post('/admin/settings', [AdminSettingController::class, 'update'])->name('admin.settings.update');

    Route::prefix('admin/users')->name('admin.users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/export', [AdminUserController::class, 'export'])->name('export');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
        Route::post('/{user}/approve-reactivation', [AdminUserController::class, 'approveReactivation'])->name('approve-reactivation');
    });

    Route::get('/admin/categories', function () {
        return mobile_view('admin.categories', ['categories' => \App\Models\Category::all()]);
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
    Route::post('/admin/modules/generate', [AdminController::class, 'generateModule'])->name('admin.modules.generate');
    Route::post('/admin/modules/{module}/ai-fill', [AdminController::class, 'autofillModule'])->name('admin.modules.autofill');
    Route::post('/admin/modules', [AdminController::class, 'storeModule'])->name('admin.modules.store');
    Route::put('/admin/modules/{module}', [AdminController::class, 'updateModule'])->name('admin.modules.update');
    Route::delete('/admin/modules/{module}', [AdminController::class, 'destroyModule'])->name('admin.modules.destroy');
    Route::post('/admin/modules/{module}/arena-levels', [AdminController::class, 'attachGameLevel'])->name('admin.modules.arena-levels.store');
    Route::delete('/admin/modules/{module}/arena-levels/{gameLevel}', [AdminController::class, 'detachGameLevel'])->name('admin.modules.arena-levels.destroy');

    // Admin Routes - Learning Games
    Route::get('/admin/game', [\App\Http\Controllers\AdminGameController::class, 'index'])->name('admin.game');
    Route::post('/admin/game', [\App\Http\Controllers\AdminGameController::class, 'store'])->name('admin.game.store');
    Route::post('/admin/game/generate', [\App\Http\Controllers\AdminGameController::class, 'generate'])->name('admin.game.generate');
    Route::put('/admin/game/{arena_level}', [\App\Http\Controllers\AdminGameController::class, 'update'])->name('admin.game.update');
    Route::delete('/admin/game/{arena_level}', [\App\Http\Controllers\AdminGameController::class, 'destroy'])->name('admin.game.destroy');

    // Admin Routes - Module Chapters
    Route::post('/admin/modules/{module}/chapters/generate', [AdminController::class, 'generateModuleChapter'])->name('admin.modules.chapters.generate');
    Route::post('/admin/modules/{module}/chapters', [AdminController::class, 'storeModuleChapter'])->name('admin.modules.chapters.store');
    Route::put('/admin/modules/chapters/{chapter}', [AdminController::class, 'updateModuleChapter'])->name('admin.modules.chapters.update');
    Route::delete('/admin/modules/chapters/{chapter}', [AdminController::class, 'destroyModuleChapter'])->name('admin.modules.chapters.destroy');

    // Admin Routes - Module Resources
    Route::post('/admin/modules/{module}/resources', [AdminController::class, 'storeModuleResource'])->name('admin.modules.resources.store');
    Route::delete('/admin/modules/resources/{resource}', [AdminController::class, 'destroyModuleResource'])->name('admin.modules.resources.destroy');

    // Admin Routes - Module Quizzes
    Route::post('/admin/modules/{module}/quizzes/generate', [AdminController::class, 'generateModuleQuiz'])->name('admin.modules.quizzes.generate');
    Route::post('/admin/modules/{module}/quizzes', [AdminController::class, 'storeModuleQuiz'])->name('admin.modules.quizzes.store');
    Route::delete('/admin/modules/quizzes/{quiz}', [AdminController::class, 'destroyModuleQuiz'])->name('admin.modules.quizzes.destroy');

    // Admin Routes - Module Quiz Questions
    Route::post('/admin/modules/quizzes/{quiz}/questions', [AdminController::class, 'storeModuleQuizQuestion'])->name('admin.modules.quizzes.questions.store');
    Route::delete('/admin/modules/quizzes/questions/{question}', [AdminController::class, 'destroyModuleQuizQuestion'])->name('admin.modules.quizzes.questions.destroy');

    // Admin Session Monitoring
    Route::get('/admin/sessions', [AdminSessionController::class, 'index'])->name('admin.sessions.index');
    Route::get('/admin/sessions/archive', [AdminSessionController::class, 'archiveIndex'])->name('admin.sessions.archive');
    Route::delete('/admin/sessions/clear', [AdminSessionController::class, 'clear'])->name('admin.sessions.clear');
    Route::get('/admin/sessions/{session}', [AdminSessionController::class, 'show'])->name('admin.sessions.show');
    Route::get('/admin/sessions/{session}/review', [AdminSessionController::class, 'review'])->name('admin.sessions.review');
    Route::post('/admin/sessions/{session}/flag', [AdminSessionController::class, 'flag'])->name('admin.sessions.flag');
    Route::post('/admin/sessions/{session}/archive', [AdminSessionController::class, 'archive'])->name('admin.sessions.doArchive');
    Route::post('/admin/sessions/{session}/restore', [AdminSessionController::class, 'restore'])->name('admin.sessions.restore');
    Route::delete('/admin/sessions/{session}', [AdminSessionController::class, 'destroy'])->name('admin.sessions.destroy');
    Route::get('/admin/reports/sessions/export', [AdminSessionController::class, 'export'])->name('admin.sessions.export');

    // Admin Contacts
    Route::get('/admin/contacts', [App\Http\Controllers\AdminContactController::class, 'index'])->name('admin.contacts.index');
    Route::get('/admin/contacts/{contact}', [App\Http\Controllers\AdminContactController::class, 'show'])->name('admin.contacts.show');
    Route::delete('/admin/contacts/{contact}', [App\Http\Controllers\AdminContactController::class, 'destroy'])->name('admin.contacts.destroy');

    // Admin Feedback Audit Features
    Route::get('/admin/feedback', [App\Http\Controllers\AdminFeedbackController::class, 'index'])->name('admin.feedback.index');
    Route::get('/admin/feedback/complaints', [App\Http\Controllers\AdminFeedbackController::class, 'complaints'])->name('admin.feedback.complaints');
    Route::get('/admin/feedback/export', [App\Http\Controllers\AdminFeedbackController::class, 'export'])->name('admin.feedback.export');
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
