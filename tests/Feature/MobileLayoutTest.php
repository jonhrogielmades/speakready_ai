<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\ActivityLog;
use App\Models\AiProvider;
use App\Models\AiProviderEvaluationResult;
use App\Models\AiProviderEvaluationRun;
use App\Models\AiProviderLog;
use App\Models\Announcement;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class MobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_landing_uses_new_mobile_guest_layout_for_mobile_user_agents(): void
    {
        $mobileUserAgents = [
            'iPhone 13 Safari' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
            'Android Chrome' => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36',
        ];

        foreach ($mobileUserAgents as $label => $userAgent) {
            $this->withHeader('User-Agent', $userAgent)
                ->get('/')
                ->assertOk()
                ->assertSee('guest-shell guest-mobile-shell', false)
                ->assertSee('data-layout-shell="mobile"', false)
                ->assertSee('data-guest-layout="mobile"', false)
                ->assertSee('css/mobile/style.css?v=30', false)
                ->assertSee('mobilePreviewSwiper', false)
                ->assertSee('mobile-preview-image-swiper', false)
                ->assertSee('id="mbtog"', false)
                ->assertSee('aria-controls="userCommandPalette"', false)
                ->assertSee('id="userCommandPalette"', false)
                ->assertSee('data-ucp-open', false)
                ->assertSee('id="guestHeaderClock"', false)
                ->assertDontSee('aria-controls="mbmenu"', false)
                ->assertDontSee('id="mbmenu"', false)
                ->assertDontSee('id="barIcon"', false)
                ->assertDontSee('id="xIcon"', false)
                ->assertDontSee('css/desktop/style.css?v=7', false);
        }
    }

    public function test_guest_landing_uses_mobile_guest_layout_from_viewport_cookie(): void
    {
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->withHeader('User-Agent', $desktopUserAgent)
            ->withUnencryptedCookie('sr_is_mobile', '1')
            ->withUnencryptedCookie('sr_viewport_width', '390')
            ->get('/')
            ->assertOk()
            ->assertSee('guest-shell guest-mobile-shell', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('css/mobile/style.css?v=30', false)
            ->assertSee('mobilePreviewSwiper', false)
            ->assertDontSee('css/desktop/style.css?v=7', false);
    }

    public function test_guest_landing_uses_mobile_guest_layout_from_viewport_query_hint(): void
    {
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->withHeader('User-Agent', $desktopUserAgent)
            ->get('/?sr_layout=mobile&sr_viewport_width=390')
            ->assertOk()
            ->assertSee('guest-shell guest-mobile-shell', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('css/mobile/style.css?v=30', false)
            ->assertSee('mobilePreviewSwiper', false)
            ->assertDontSee('css/desktop/style.css?v=7', false);
    }

    public function test_guest_landing_desktop_query_hint_can_override_stale_mobile_cookie(): void
    {
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->withHeader('User-Agent', $desktopUserAgent)
            ->withUnencryptedCookie('sr_is_mobile', '1')
            ->withUnencryptedCookie('sr_viewport_width', '390')
            ->get('/?sr_layout=desktop&sr_viewport_width=1280')
            ->assertOk()
            ->assertSee('guest-shell guest-desktop-shell', false)
            ->assertSee('data-layout-shell="desktop"', false)
            ->assertSee('css/desktop/style.css?v=7', false)
            ->assertDontSee('css/mobile/style.css?v=30', false);
    }

    public function test_guest_landing_exposes_shell_marker_for_viewport_repair(): void
    {
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->withHeader('User-Agent', $desktopUserAgent)
            ->get('/')
            ->assertOk()
            ->assertSee('guest-shell guest-desktop-shell', false)
            ->assertSee('data-layout-shell="desktop"', false)
            ->assertSee("body.getAttribute('data-layout-shell')", false)
            ->assertSee("bodyClasses.contains('guest-desktop-shell')", false)
            ->assertSee("url.searchParams.set('sr_layout', targetLayout)", false)
            ->assertSee('window.location.replace(url.toString())', false)
            ->assertSee('css/desktop/style.css?v=7', false);
    }

    public function test_user_dashboard_uses_mobile_shell_for_mobile_user_agent(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($user)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="user-mobile-shell mobile-shell"', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('id="mob-content"', false)
            ->assertSee('--mob-card-gap: 12px', false)
            ->assertSee('--sr-visual-vh', false)
            ->assertSee('.tracker-panel', false)
            ->assertSee('id="mob-bottom-nav"', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_dashboard_uses_mobile_shell_for_mobile_user_agent(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('id="mob-content"', false)
            ->assertSee('--sr-visual-vh', false)
            ->assertSee('id="mob-bottom-nav"', false)
            ->assertSee('class="admin-dashboard-title-text"', false)
            ->assertSee('admin-dashboard-shell', false)
            ->assertSee('dashboard-work-grid', false)
            ->assertSee('css/mobile/admin/dashboard.css?v=5', false)
            ->assertSee('id="mobFullscreenBtn"', false)
            ->assertSee('data-user-fullscreen-toggle', false)
            ->assertSee('js/user-ui.js?v=21', false)
            ->assertSee('<span>SpeakReady AI</span>', false)
            ->assertDontSee('<span>SpeakReady AI Admin</span>', false)
            ->assertSee('width: 36px; height: 36px; min-width: 36px; min-height: 36px; border-radius: 10px;', false)
            ->assertSee('border-radius: 999px', false)
            ->assertSee('body.admin-mobile-shell.user-app-fullscreen #mob-content', false)
            ->assertSee('grid-template-columns: repeat(5, minmax(0, 1fr));', false)
            ->assertSee('max-width: min(45vw, 13rem);', false)
            ->assertSee('data-admin-mobile-topbar-drawer-fix', false)
            ->assertSee('id="mobNotificationBtn"', false)
            ->assertSee('data-bs-auto-close="outside"', false)
            ->assertSee('data-admin-notif-close', false)
            ->assertSee('mob-profile-account-head', false)
            ->assertSee('mob-profile-pages-head', false)
            ->assertSee('id="mobMoreDropdown"', false)
            ->assertSee('aria-controls="mobMoreDropdown"', false)
            ->assertSee('mob-profile-account-dropdown', false)
            ->assertSee('mob-more-dropdown mob-profile-is-pages', false)
            ->assertSee('data-mobile-more-link="categories"', false)
            ->assertSee('data-mobile-more-link="archive"', false)
            ->assertSee('data-mobile-more-link="contacts"', false)
            ->assertDontSee('data-mobile-more-link="users"', false)
            ->assertDontSee('data-mobile-more-link="sessions"', false)
            ->assertDontSee('data-mobile-more-link="feedback"', false)
            ->assertSee('--admin-drawer-text', false)
            ->assertSee('--admin-notif-text', false)
            ->assertSee('body.admin-mobile-shell #mobProfileDropdown[data-origin="top"]', false)
            ->assertSee('#mobMoreDropdown', false)
            ->assertSee('mob-profile-is-account', false)
            ->assertSee('.mob-profile-account-head', false)
            ->assertSee('function getMobileProfileDropdown(mode = \'pages\')', false)
            ->assertSee('function syncMobileProfileMode(', false)
            ->assertSee('pagesMenu.hidden = isAccountMode;', false)
            ->assertSee('accountMenu.style.display', false)
            ->assertSee('function hideMobileNotificationDropdown()', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_dashboard_keeps_desktop_shell_for_desktop_user_agent(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $desktopUserAgent)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('class="admin-shell desktop-shell"', false)
            ->assertSee('data-layout-shell="desktop"', false)
            ->assertSee('class="admin-dashboard-title-text"', false)
            ->assertSee('admin-dashboard-shell', false)
            ->assertSee('dashboard-work-grid', false)
            ->assertSee('css/desktop/admin/dashboard.css?v=3', false)
            ->assertSee('class="db-sidebar"', false)
            ->assertDontSee('id="mob-content"', false);
    }

    public function test_admin_categories_page_uses_mobile_category_management_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $category = Category::create([
            'title' => 'Mobile Interview Readiness',
            'description' => 'Questions used to inspect responsive interview flows.',
            'type' => 'core',
            'status' => 'active',
            'icon' => 'fa-solid fa-mobile-screen',
            'is_featured' => true,
        ]);

        Question::create([
            'category_id' => $category->id,
            'question_text' => 'How do you validate a mobile dashboard layout?',
            'difficulty' => 'Medium',
            'type' => 'Technical',
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.categories'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-categories"', false)
            ->assertSee('css/mobile/admin/categories.css?v=4', false)
            ->assertSee('admin-categories-header', false)
            ->assertSee('admin-categories-add-btn', false)
            ->assertSee('admin-categories-table-card', false)
            ->assertSee('id="mainCategoriesTable"', false)
            ->assertSee('data-label="Category"', false)
            ->assertSee('data-label="Description"', false)
            ->assertSee('data-label="Type"', false)
            ->assertSee('data-label="Questions"', false)
            ->assertSee('data-label="Status"', false)
            ->assertSee('data-label="Actions"', false)
            ->assertSee('admin-category-action-cell', false)
            ->assertSee('Mobile Interview Readiness', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_questions_page_uses_mobile_question_bank_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $category = Category::create([
            'title' => 'Mobile Question Bank',
            'description' => 'Question bank records for mobile admin testing.',
            'type' => 'core',
            'status' => 'active',
        ]);

        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Describe how you would fix a narrow admin question table.',
            'difficulty' => 'Medium',
            'type' => 'Technical',
            'status' => 'active',
            'mapped_skills' => ['Responsive UI', 'Admin QA'],
            'source_name' => 'Mobile Fixture Source',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.questions'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-questions"', false)
            ->assertSee('css/mobile/admin/questions.css?v=7', false)
            ->assertSee('admin-questions-header-actions', false)
            ->assertSee('question-table-controls', false)
            ->assertSee('admin-questions-table-card', false)
            ->assertSee('id="mainTable"', false)
            ->assertSee('data-label="Select"', false)
            ->assertSee('data-label="Question"', false)
            ->assertSee('data-label="Category"', false)
            ->assertSee('data-label="Type / Diff"', false)
            ->assertSee('data-label="Status"', false)
            ->assertSee('data-label="Actions"', false)
            ->assertSee('question-action-cell', false)
            ->assertSee('question-row-action-form', false)
            ->assertSee((string) $question->id, false)
            ->assertSee('Describe how you would fix a narrow admin question table.', false)
            ->assertSee('Mobile Question Bank', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_modules_page_uses_mobile_learning_modules_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $module = LearningModule::create([
            'title' => 'Mobile Interview Learning Module',
            'description' => 'Practice mobile-safe interview module management.',
            'type' => 'article',
            'category' => 'Interview Readiness',
            'difficulty' => 'Beginner',
            'status' => 'published',
            'views' => 12,
            'is_featured' => true,
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.modules'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-modules"', false)
            ->assertSee('css/mobile/admin/modules.css?v=4', false)
            ->assertSee('admin-modules-header-actions', false)
            ->assertSee('modules-stats-row', false)
            ->assertSee('modules-panel', false)
            ->assertSee('id="modulesTable"', false)
            ->assertSee('data-label="Module"', false)
            ->assertSee('data-label="Category"', false)
            ->assertSee('data-label="Difficulty"', false)
            ->assertSee('data-label="Status"', false)
            ->assertSee('data-label="Views"', false)
            ->assertSee('data-label="Actions"', false)
            ->assertSee('admin-module-action-cell', false)
            ->assertSee('admin-module-row-action-form', false)
            ->assertSee('Mobile Interview Learning Module', false)
            ->assertSee(route('admin.modules.edit', $module->id), false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_module_edit_page_uses_mobile_learning_module_builder_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $module = LearningModule::create([
            'title' => 'Mobile Builder Module',
            'description' => 'Build and edit interview lesson chapters on mobile.',
            'type' => 'article',
            'category' => 'Interview Readiness',
            'difficulty' => 'Intermediate',
            'status' => 'draft',
            'views' => 4,
            'is_featured' => false,
        ]);

        $chapter = $module->chapters()->create([
            'title' => 'Prepare a concise answer',
            'content' => '<p>Write, rehearse, revise, and check the answer.</p>',
            'video_url' => 'https://www.youtube.com/embed/example',
            'order' => 1,
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.modules.edit', $module->id))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-module-edit"', false)
            ->assertSee('css/mobile/admin/module_edit.css?v=2', false)
            ->assertSee('css/mobile/admin/module_edit-2.css?v=2', false)
            ->assertSee('admin-module-edit-header', false)
            ->assertSee('admin-module-edit-back-btn', false)
            ->assertSee('id="moduleEditTabs"', false)
            ->assertSee('admin-module-basic-card', false)
            ->assertSee('admin-module-chapter-toolbar', false)
            ->assertSee('admin-module-chapter-item', false)
            ->assertSee('admin-module-chapter-actions', false)
            ->assertSee('id="addChapterModal"', false)
            ->assertSee('id="editor-container"', false)
            ->assertSee('id="editChapterModal'.$chapter->id.'"', false)
            ->assertSee('id="edit-editor-container-'.$chapter->id.'"', false)
            ->assertSee('Mobile Builder Module', false)
            ->assertSee('Prepare a concise answer', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_archived_sessions_page_uses_mobile_archive_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'name' => 'Mobile Archived Candidate',
        ]);

        $category = Category::create([
            'title' => 'Archived Interview Category',
            'type' => 'core',
            'status' => 'active',
        ]);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'completed',
            'duration_seconds' => 360,
            'is_archived' => true,
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.sessions.archive'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-archive"', false)
            ->assertSee('css/mobile/admin/sessions/archive.css?v=2', false)
            ->assertSee('admin-archive-header', false)
            ->assertSee('archive-filter-form', false)
            ->assertSee('id="mainArchiveTable"', false)
            ->assertSee('data-label="ID"', false)
            ->assertSee('data-label="User"', false)
            ->assertSee('data-label="Category"', false)
            ->assertSee('data-label="Archived"', false)
            ->assertSee('data-label="Actions"', false)
            ->assertSee('archive-action-cell', false)
            ->assertSee('#'.$session->id, false)
            ->assertSee('Mobile Archived Candidate', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_contacts_page_uses_mobile_contact_messages_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $contact = Contact::create([
            'name' => 'Mobile Contact Sender',
            'email' => 'mobile-contact@example.com',
            'subject' => 'Mobile admin contact table issue',
            'message' => 'Please make the contact messages page fit on mobile.',
            'status' => 'unread',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.contacts.index'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-contacts"', false)
            ->assertSee('css/mobile/admin/contacts.css?v=1', false)
            ->assertSee('admin-contacts-header', false)
            ->assertSee('admin-contacts-card', false)
            ->assertSee('id="mainContactsTable"', false)
            ->assertSee('data-label="Date"', false)
            ->assertSee('data-label="Name"', false)
            ->assertSee('data-label="Email"', false)
            ->assertSee('data-label="Subject"', false)
            ->assertSee('data-label="Status"', false)
            ->assertSee('data-label="Actions"', false)
            ->assertSee('contact-action-cell', false)
            ->assertSee('Mobile Contact Sender', false)
            ->assertSee($contact->email, false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_contact_detail_page_uses_mobile_message_detail_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $contact = Contact::create([
            'name' => 'Mobile Detail Sender',
            'email' => 'mobile-detail@example.com',
            'subject' => 'Mobile contact detail issue',
            'message' => 'This long contact detail message should wrap inside the mobile detail card.',
            'status' => 'unread',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.contacts.show', $contact->id))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-contact-detail"', false)
            ->assertSee('css/mobile/admin/contacts.css?v=1', false)
            ->assertSee('admin-contact-detail-header', false)
            ->assertSee('admin-contact-detail-card', false)
            ->assertSee('admin-contact-detail-grid', false)
            ->assertSee('admin-contact-message-body', false)
            ->assertSee('admin-contact-detail-actions', false)
            ->assertSee('Mobile Detail Sender', false)
            ->assertSee($contact->email, false)
            ->assertSee('Mobile contact detail issue', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_users_page_uses_mobile_management_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'name' => 'Mobile Managed Candidate',
            'email' => 'mobile-managed@example.com',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-users"', false)
            ->assertSee('css/mobile/admin/users.css?v=3', false)
            ->assertSee('admin-users-header-actions', false)
            ->assertSee('admin-users-filter-form', false)
            ->assertSee('admin-users-pagination-controls', false)
            ->assertSee('id="mainUsersTable"', false)
            ->assertSee('data-label="User"', false)
            ->assertSee('data-label="Email"', false)
            ->assertSee('data-label="Actions"', false)
            ->assertSee('Mobile Managed Candidate', false)
            ->assertSee($user->email, false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_sessions_page_uses_mobile_monitoring_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'name' => 'Mobile Session Candidate',
        ]);

        $category = Category::create([
            'title' => 'General Job Interview',
            'type' => 'core',
            'status' => 'active',
        ]);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'completed',
            'duration_seconds' => 485,
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.sessions.index'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-sessions"', false)
            ->assertSee('css/mobile/admin/sessions/index.css?v=3', false)
            ->assertSee('session-stat-grid', false)
            ->assertSee('session-analytics-grid', false)
            ->assertSee('session-filter-form', false)
            ->assertSee('id="mainSessionsTable"', false)
            ->assertSee('data-label="ID"', false)
            ->assertSee('data-label="User"', false)
            ->assertSee('data-label="Actions"', false)
            ->assertSee('session-action-cell', false)
            ->assertSee('#'.$session->id, false)
            ->assertSee('Mobile Session Candidate', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_feedback_page_uses_mobile_audit_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'name' => 'Mobile Feedback Candidate',
        ]);

        $category = Category::create([
            'title' => 'Behavioral Interview',
            'type' => 'core',
            'status' => 'active',
        ]);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'completed',
            'duration_seconds' => 520,
        ]);

        $question = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Describe a time you improved a mobile workflow.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);

        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I simplified the workflow and measured completion time.',
            'response_mode' => 'text',
            'ai_feedback' => 'The answer gives a clear action and measurable outcome.',
            'score' => 84,
            'clarity_score' => 82,
            'relevance_score' => 88,
            'grammar_score' => 81,
            'delivery_stability_score' => 79,
            'audit_status' => 'under_review',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.feedback.index'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-feedback"', false)
            ->assertSee('css/mobile/admin/feedback/index.css?v=8', false)
            ->assertSee('feedback-stat-grid', false)
            ->assertSee('feedback-main-grid', false)
            ->assertSee('feedback-filter-form', false)
            ->assertSee('feedback-audit-list-card', false)
            ->assertSee('id="mainFeedbackTable"', false)
            ->assertSee('data-label="Audit ID"', false)
            ->assertSee('data-label="Question"', false)
            ->assertSee('data-label="Score"', false)
            ->assertSee('data-label="Generated"', false)
            ->assertSee('data-label="Status"', false)
            ->assertSee('data-label="Action"', false)
            ->assertSee('feedback-action-cell', false)
            ->assertSee('#'.$answer->id, false)
            ->assertSee('Describe a time you improved a mobile workflow.', false)
            ->assertDontSee('class="db-sidebar"', false);

        $feedbackMobileCss = file_get_contents(public_path('css/mobile/admin/feedback/index.css'));
        $this->assertStringContainsString('--feedback-audit-title-color', $feedbackMobileCss);
        $this->assertStringContainsString('--sr-page-title-accent: var(--feedback-audit-title-color);', $feedbackMobileCss);
        $this->assertStringContainsString('color: var(--feedback-audit-title-color, var(--tx, #0f172a)) !important;', $feedbackMobileCss);
    }

    public function test_admin_notifications_page_uses_mobile_notifications_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'name' => 'Mobile Notification Candidate',
            'email' => 'mobile-notification@example.com',
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'mobile_layout_reviewed',
            'description' => 'Reviewed notification cards on a narrow mobile viewport.',
            'ip_address' => '127.0.0.1',
        ]);

        Announcement::create([
            'title' => 'Mobile Admin Notice',
            'message' => 'Responsive notification layout has been verified.',
            'type' => 'info',
            'target' => 'all',
            'sent_by' => $admin->id,
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-notifications"', false)
            ->assertSee('admin-notifications-shell', false)
            ->assertSee('css/mobile/admin/notifications.css?v=3', false)
            ->assertSee('admin-notifications-header', false)
            ->assertSee('admin-notifications-stats-row', false)
            ->assertSee('id="adminActivityTable"', false)
            ->assertSee('id="adminBroadcastTable"', false)
            ->assertSee('data-label="User"', false)
            ->assertSee('data-label="Activity"', false)
            ->assertSee('data-label="Message"', false)
            ->assertSee('data-label="Actions"', false)
            ->assertSee('admin-notification-action-cell', false)
            ->assertSee('admin-notification-row-action-form', false)
            ->assertSee('admin-notification-modal-footer', false)
            ->assertSee('Mobile Notification Candidate', false)
            ->assertSee('Mobile Admin Notice', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_ai_providers_page_uses_mobile_provider_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('mobile_provider_key'),
            'status' => 'active',
            'is_primary' => true,
        ]);

        AiProviderLog::create([
            'provider_id' => $provider->id,
            'module' => 'feedback_generation',
            'endpoint' => 'openai',
            'response_time_ms' => 820,
            'status' => 'success',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.ai.providers'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-ai-providers"', false)
            ->assertSee('admin-ai-providers-shell', false)
            ->assertSee('css/mobile/admin/ai/providers.css?v=7', false)
            ->assertSee('ai-providers-heading', false)
            ->assertSee('ai-overview-card', false)
            ->assertSee('OpenAI Process Connections', false)
            ->assertSee('id="openAiProcessTable"', false)
            ->assertSee('id="moduleUsageTable"', false)
            ->assertSee('id="mainProvidersTable"', false)
            ->assertSee('data-label="Process"', false)
            ->assertSee('data-label="Module"', false)
            ->assertSee('data-label="Requests"', false)
            ->assertSee('data-label="Provider Name"', false)
            ->assertSee('data-label="API Key"', false)
            ->assertSee('data-label="Actions"', false)
            ->assertSee('ai-provider-action-cell', false)
            ->assertSee('OpenAI', false)
            ->assertSee('Feedback generation', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_ai_evaluation_page_uses_mobile_evaluation_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('mobile_evaluation_key'),
            'status' => 'active',
            'is_primary' => true,
        ]);

        $run = AiProviderEvaluationRun::create([
            'benchmark_version' => 'mobile-evaluation-v1',
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'provider_count' => 1,
            'case_count' => 1,
            'summary' => ['best_provider' => 'OpenAI'],
            'created_by' => $admin->id,
        ]);

        AiProviderEvaluationResult::create([
            'run_id' => $run->id,
            'provider_id' => $provider->id,
            'provider_key' => 'openai',
            'provider_name' => 'OpenAI',
            'task_type' => 'feedback_generation',
            'case_key' => 'mobile_feedback_case',
            'status' => 'success',
            'response_time_ms' => 920,
            'quality_score' => 88,
            'reliability_score' => 100,
            'schema_score' => 96,
            'accuracy_score' => 86,
            'safety_score' => 100,
            'output_excerpt' => '{}',
            'evidence' => ['warnings' => []],
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.ai.evaluation', ['run' => $run->id]))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-ai-evaluation"', false)
            ->assertSee('admin-ai-evaluation-shell', false)
            ->assertSee('css/mobile/admin/ai/evaluation.css?v=3', false)
            ->assertSee('ai-eval-detail-grid', false)
            ->assertSee('Provider Evidence Matrix', false)
            ->assertSee('Provider Comparison Evidence', false)
            ->assertSee('data-label="Provider"', false)
            ->assertSee('data-label="Overall"', false)
            ->assertSee('data-label="Reliability"', false)
            ->assertSee('data-label="Output Evidence"', false)
            ->assertSee('OpenAI', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_ai_evaluation_report_uses_mobile_report_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('mobile_report_key'),
            'status' => 'active',
            'is_primary' => true,
        ]);

        $run = AiProviderEvaluationRun::create([
            'benchmark_version' => 'mobile-report-v1',
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'provider_count' => 1,
            'case_count' => 1,
            'summary' => ['best_provider' => 'OpenAI'],
            'created_by' => $admin->id,
        ]);

        AiProviderEvaluationResult::create([
            'run_id' => $run->id,
            'provider_id' => $provider->id,
            'provider_key' => 'openai',
            'provider_name' => 'OpenAI',
            'task_type' => 'question_generation',
            'case_key' => 'mobile_question_case',
            'status' => 'success',
            'response_time_ms' => 840,
            'quality_score' => 90,
            'reliability_score' => 100,
            'schema_score' => 94,
            'accuracy_score' => 87,
            'safety_score' => 100,
            'output_excerpt' => '{}',
            'evidence' => ['warnings' => []],
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.ai.evaluation.report', ['run' => $run->id]))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('ai-evaluation-report admin-ai-evaluation-shell', false)
            ->assertSee('css/mobile/admin/ai/evaluation.css?v=3', false)
            ->assertSee('SpeakReady AI Provider Evaluation Evidence', false)
            ->assertSee('ai-report-mobile-table', false)
            ->assertSee('data-label="Provider"', false)
            ->assertSee('data-label="Reliability"', false)
            ->assertSee('data-label="Latency"', false)
            ->assertSee('OpenAI', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_system_settings_page_uses_mobile_settings_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
            'name' => 'Mobile Settings Admin',
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'admin_settings_mobile_reviewed',
            'description' => 'Reviewed system settings from mobile layout tests.',
            'ip_address' => '127.0.0.1',
            'read_at' => now(),
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="sec-admin-settings"', false)
            ->assertSee('admin-settings-shell', false)
            ->assertSee('css/mobile/admin/settings.css?v=3', false)
            ->assertSee('admin-settings-header', false)
            ->assertSee('admin-settings-jump-grid', false)
            ->assertSee('id="systemSettingsForm"', false)
            ->assertSee('admin-settings-form', false)
            ->assertSee('settings-panel', false)
            ->assertSee('settings-save-top admin-settings-save-top', false)
            ->assertSee('admin-settings-backup-actions', false)
            ->assertSee('admin-settings-save-bar', false)
            ->assertSee('id="mainAuditLogsTable"', false)
            ->assertSee('data-label="Date"', false)
            ->assertSee('data-label="Action"', false)
            ->assertSee('data-label="User"', false)
            ->assertSee('admin-settings-info-list', false)
            ->assertSee('Reviewed system settings from mobile layout tests.', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_account_page_uses_mobile_account_layout(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
            'name' => 'Mobile Account Admin',
            'email' => 'mobile-admin-account@example.com',
            'target_position' => 'System Administrator',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.account'))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('id="account-page"', false)
            ->assertSee('admin-account-page-shell', false)
            ->assertSee('css/mobile/admin/account.css?v=3', false)
            ->assertSee('Admin Account', false)
            ->assertSee('admin-account-grid', false)
            ->assertSee('admin-account-profile-card', false)
            ->assertSee('id="adminAccountProfileForm"', false)
            ->assertSee('admin-account-photo-row', false)
            ->assertSee('admin-account-field-grid', false)
            ->assertSee('admin-account-profile-actions', false)
            ->assertSee('admin-account-security-card', false)
            ->assertSee('id="adminAccountPasswordForm"', false)
            ->assertSee('admin-account-status-card', false)
            ->assertSee('admin-account-meta-list', false)
            ->assertSee('admin-settings-shortcut', false)
            ->assertSee('Mobile Account Admin', false)
            ->assertSee('mobile-admin-account@example.com', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_admin_write_requests_are_accessible_from_mobile_user_agent(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->post(route('admin.settings.update'), [])
            ->assertRedirect();
    }

    public function test_user_dashboard_keeps_desktop_shell_for_desktop_user_agent(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->actingAs($user)
            ->withHeader('User-Agent', $desktopUserAgent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('user-desktop-shell desktop-shell', false)
            ->assertSee('data-layout-shell="desktop"', false)
            ->assertSee('class="db-sidebar"', false)
            ->assertDontSee('id="mob-bottom-nav"', false)
            ->assertDontSee('html body #progressModulesLikeHero.progress-hero', false);
    }

    public function test_user_dashboard_uses_mobile_shell_from_viewport_cookie(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withUnencryptedCookie('sr_is_mobile', '1')
            ->withUnencryptedCookie('sr_viewport_width', '390')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="user-mobile-shell mobile-shell"', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('id="mob-content"', false)
            ->assertSee('id="mob-bottom-nav"', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_user_mobile_bottom_nav_centers_interview_action(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $response = $this->actingAs($user)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('class="mob-nav-primary-icon"', false)
            ->assertSee('aria-label="Start interview practice"', false)
            ->assertSee('<span>Interview</span>', false)
            ->assertSee('<span>More</span>', false)
            ->assertSee('class="fa-solid fa-chart-simple"', false)
            ->assertSee('class="fa-regular fa-clipboard-list"', false)
            ->assertSee('class="fa-solid fa-grid"', false);

        $content = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/id="mobnav-home".*id="mobnav-progress".*id="mobnav-interview".*id="mobnav-feedback".*id="mobnav-more"/s',
            $content
        );

        $this->assertStringContainsString('class="mob-nav-item mob-nav-primary ', $content);
        $this->assertStringContainsString('--mob-nav-h: 112px;', $content);
        $this->assertStringContainsString('--mob-nav-overlap: 0px;', $content);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 78px minmax(0, 1fr) minmax(0, 1fr) !important;', $content);
        $this->assertStringContainsString('padding-bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 8px);', $content);
        $this->assertStringNotContainsString('padding-bottom: calc(var(--mob-nav-h) + var(--mob-nav-overlap) + var(--mob-safe-bottom) + 8px);', $content);
        $this->assertStringNotContainsString('height: calc(var(--mob-nav-h) + var(--mob-nav-overlap) + var(--mob-safe-bottom)) !important;', $content);
        $this->assertStringContainsString('--mob-dock-bg: transparent;', $content);
        $this->assertStringContainsString('--mob-dock-surface: #ffffff;', $content);
        $this->assertStringContainsString('--mob-dock-text: #64748b;', $content);
        $this->assertStringContainsString('--mob-dock-active: #076dff;', $content);
        $this->assertStringContainsString('.lm #mob-bottom-nav', $content);
        $this->assertStringContainsString('background: transparent !important;', $content);
        $this->assertStringContainsString('border-top: 0 !important;', $content);
        $this->assertStringContainsString('box-shadow: none !important;', $content);
        $this->assertStringContainsString('backdrop-filter: none !important;', $content);
        $this->assertStringContainsString('-webkit-backdrop-filter: none !important;', $content);
        $this->assertStringContainsString('left: max(10px, env(safe-area-inset-left, 0px)) !important;', $content);
        $this->assertStringContainsString('right: max(10px, env(safe-area-inset-right, 0px)) !important;', $content);
        $this->assertStringContainsString('bottom: calc(16px + var(--mob-safe-bottom)) !important;', $content);
        $this->assertStringContainsString('--mob-dock-height: 98px;', $content);
        $this->assertStringContainsString('--mob-dock-bar-top: 34px;', $content);
        $this->assertStringContainsString('--mob-dock-radius: 34px;', $content);
        $this->assertStringContainsString('--mob-dock-fab-size: 72px;', $content);
        $this->assertStringContainsString('height: var(--mob-dock-height) !important;', $content);
        $this->assertStringContainsString('max-width: 680px !important;', $content);
        $this->assertStringContainsString('background: transparent !important;', $content);
        $this->assertStringNotContainsString('linear-gradient(180deg, var(--bg) 0 var(--mob-nav-overlap), transparent var(--mob-nav-overlap)) !important;', $content);
        $this->assertStringNotContainsString('--mob-dock-cutout', $content);
        $this->assertStringNotContainsString('--mob-dock-fab-cutout', $content);
        $this->assertStringNotContainsString('.mob-nav-primary::before', $content);
        $this->assertStringContainsString('border-radius: 0 !important;', $content);
        $this->assertStringContainsString('top: 2px !important;', $content);
        $this->assertStringContainsString('width: calc(var(--mob-dock-fab-size) + 10px) !important;', $content);
        $this->assertStringContainsString('--mob-dock-fab-ring: rgba(255, 255, 255, 0.96);', $content);
        $this->assertStringContainsString('pointer-events: none !important;', $content);
        $this->assertStringNotContainsString('0 10px 22px rgba(37, 99, 235, 0.18)', $content);
        $this->assertStringNotContainsString('0 0 18px rgba(14, 165, 233, 0.14)', $content);
        $this->assertStringContainsString('top: var(--mob-dock-bar-top) !important;', $content);
        $this->assertStringContainsString('background: var(--mob-dock-surface) !important;', $content);
        $this->assertStringContainsString('border: 1px solid var(--mob-dock-border) !important;', $content);
        $this->assertStringContainsString('border-radius: var(--mob-dock-radius) !important;', $content);
        $this->assertStringContainsString('0 18px 20px rgba(14, 165, 233, 0.1)', $content);
        $this->assertStringNotContainsString('backdrop-filter: blur(18px) saturate(120%) !important;', $content);
        $this->assertStringNotContainsString('-webkit-backdrop-filter: blur(18px) saturate(120%) !important;', $content);
        $this->assertStringNotContainsString('box-shadow: 0 -14px 34px rgba(2, 6, 23, 0.3) !important;', $content);
        $this->assertStringNotContainsString('border-top: 1px solid var(--mob-dock-border) !important;', $content);
        $this->assertStringNotContainsString('calc(50% - 48px)', $content);
        $this->assertStringNotContainsString('calc(50% + 48px)', $content);
        $this->assertStringContainsString('padding: 0 14px 12px !important;', $content);
        $this->assertStringContainsString('pointer-events: auto !important;', $content);
        $this->assertStringContainsString('.mob-nav-item:not(.mob-nav-primary)', $content);
        $this->assertStringContainsString('.mob-nav-item.active {', $content);
        $this->assertStringNotContainsString('.mob-nav-item.active:not(.mob-nav-primary) {', $content);
        $this->assertStringContainsString('margin-top: 0 !important;', $content);
        $this->assertStringContainsString('justify-content: center !important;', $content);
        $this->assertStringContainsString('align-self: end !important;', $content);
        $this->assertStringContainsString('transform: translateY(0) !important;', $content);
        $this->assertStringContainsString('height: 98px !important;', $content);
        $this->assertStringContainsString('min-height: 98px !important;', $content);
        $this->assertStringContainsString('padding: 0 0 7px !important;', $content);
        $this->assertStringContainsString('.mob-nav-primary > span:last-child', $content);
        $this->assertStringContainsString('font-size: clamp(0.66rem, 2.45vw, 0.78rem) !important;', $content);
        $this->assertStringContainsString('font-weight: 900 !important;', $content);
        $this->assertStringContainsString('width: var(--mob-dock-fab-size) !important;', $content);
        $this->assertStringContainsString('height: var(--mob-dock-fab-size) !important;', $content);
        $this->assertStringContainsString('border: 6px solid var(--mob-dock-fab-ring) !important;', $content);
        $this->assertStringNotContainsString('border: 5px solid var(--mob-dock-bg) !important;', $content);
        $this->assertStringContainsString('0 0 0 1px rgba(147, 197, 253, 0.72)', $content);
        $this->assertStringNotContainsString('0 12px 20px rgba(37, 99, 235, 0.3)', $content);
        $this->assertStringNotContainsString('0 0 18px rgba(59, 130, 246, 0.24)', $content);
        $this->assertStringNotContainsString('0 12px 20px rgba(37, 99, 235, 0.34)', $content);
        $this->assertStringNotContainsString('0 0 22px rgba(59, 130, 246, 0.28)', $content);
        $this->assertStringContainsString('border-radius: 50% !important;', $content);
        $this->assertStringContainsString('font-size: clamp(1.42rem, 5.7vw, 1.92rem) !important;', $content);
        $this->assertStringContainsString('font-size: clamp(0.68rem, 2.45vw, 0.8rem) !important;', $content);
        $this->assertStringContainsString('text-decoration: none !important;', $content);
        $this->assertStringContainsString('text-shadow: none !important;', $content);
        $this->assertStringContainsString('position: relative !important;', $content);
        $this->assertStringNotContainsString('top: calc(var(--mob-nav-overlap) * -1) !important;', $content);
        $this->assertStringNotContainsString('translate: -50% 0 !important;', $content);
    }

    public function test_user_mobile_shell_includes_fullscreen_viewport_hardening(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($user)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="mobFullscreenBtn"', false)
            ->assertSee('data-user-fullscreen-toggle', false)
            ->assertSee('body.mobile-shell.user-app-fullscreen #mob-content', false)
            ->assertSee('height: var(--sr-visual-vh) !important', false)
            ->assertSee('css/mobile/style.css?v=33', false)
            ->assertSee('js/main.js?v=7', false)
            ->assertSee('js/user-ui.js?v=21', false)
            ->assertSee('body.user-mobile-shell #mob-header #mobFullscreenBtn', false)
            ->assertSee('display: inline-flex !important;', false)
            ->assertDontSee('body.user-mobile-shell #mob-header #mobFullscreenBtn {
               display: none !important;', false);

        $fullscreenScript = file_get_contents(public_path('js/user-ui.js'));

        $this->assertStringContainsString('userApp.fullscreenFallbackActive', $fullscreenScript);
        $this->assertStringContainsString("root.requestFullscreen({ navigationUI: 'hide' })", $fullscreenScript);
        $this->assertStringContainsString("button.setAttribute('aria-pressed'", $fullscreenScript);
    }

    public function test_user_mobile_shell_does_not_render_quick_navigation_launcher(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $response = $this->actingAs($user)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee('class="ucp-mobile-launcher"', false)
            ->assertDontSee('data-ucp-context="user"', false)
            ->assertDontSee('id="userCommandPalette"', false)
            ->assertDontSee('id="userCommandList"', false)
            ->assertDontSee('id="ucp-destination-dashboard"', false)
            ->assertDontSee('id="ucp-destination-account"', false)
            ->assertDontSee('14 destinations')
            ->assertDontSee('data-ucp-search', false);
    }

    public function test_desktop_interview_session_hides_camera_detection_panel_when_enabled(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $category = Category::create([
            'title' => 'Behavioral',
            'description' => 'Behavioral questions',
            'status' => 'active',
            'type' => 'core',
        ]);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'response_mode' => 'text',
            'accommodation_profile' => ['camera_detection' => true],
            'status' => 'in_progress',
        ]);
        Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Describe a time you solved a difficult issue.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);

        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->withHeader('User-Agent', $desktopUserAgent)
            ->get(route('interview.session'));

        $response->assertOk()
            ->assertSee('css/desktop/interview/session.css?v=43', false)
            ->assertSee('const cameraDetectionEnabled = true;', false)
            ->assertSee('interview-session-browser-fullscreen', false)
            ->assertSee('interview-ready-fullscreen', false)
            ->assertSee('Camera ON', false)
            ->assertDontSee('<i class="fa-regular fa-clock"></i>Self-paced', false)
            ->assertSee('function handleInterviewEscapeKey(event)', false)
            ->assertSee('exitReadyFullscreenShell();', false)
            ->assertSee('has-desktop-camera-pip', false)
            ->assertSee('class="desktop-camera-pip d-none d-lg-flex"', false)
            ->assertSee('id="userCamera"', false)
            ->assertSee('alt="AI Interviewer"', false)
            ->assertSee("document.getElementById('userCamera') || document.getElementById('userCameraMobile')", false)
            ->assertDontSee('id="questionTimerChip"', false)
            ->assertDontSee('id="cameraPanel"', false)
            ->assertDontSee('id="cameraDetectionStatus"', false);

        $desktopSessionCss = file_get_contents(public_path('css/desktop/interview/session.css'));
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen #sec-interview-session', $desktopSessionCss);
        $this->assertStringContainsString('height: calc(100dvh - clamp(32px, 4vw, 60px)) !important;', $desktopSessionCss);
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen #workspaceRow > [class*="col-"]', $desktopSessionCss);
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen .desktop-session-two-column', $desktopSessionCss);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.80fr) !important;', $desktopSessionCss);
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen .desktop-interview-panel .ai-avatar-panel', $desktopSessionCss);
        $this->assertStringContainsString('width: clamp(320px, 21vw, 440px)', $desktopSessionCss);
        $this->assertStringContainsString('--spectrum-radius: clamp(178px, 11.6vw, 240px);', $desktopSessionCss);
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen .desktop-response-column .response-panel', $desktopSessionCss);
        $this->assertStringContainsString('body.user-desktop-shell.interview-session-shell.interview-session-browser-fullscreen .desktop-response-column #answerTextarea', $desktopSessionCss);
        $this->assertStringContainsString('max-height: none !important;', $desktopSessionCss);
    }

    public function test_voice_and_hybrid_interview_sessions_use_inline_transcript_field_without_manual_transcript_button(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $category = Category::create([
            'title' => 'Behavioral',
            'description' => 'Behavioral questions',
            'status' => 'active',
            'type' => 'core',
        ]);
        $desktopUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';
        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        foreach (['voice', 'hybrid'] as $responseMode) {
            $session = InterviewSession::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'target_position' => 'Developer',
                'num_questions' => 1,
                'response_mode' => $responseMode,
                'status' => 'in_progress',
            ]);
            Question::create([
                'category_id' => $category->id,
                'interview_session_id' => $session->id,
                'question_text' => "Describe a {$responseMode} response.",
                'difficulty' => 'medium',
                'type' => 'Behavioral',
                'status' => 'active',
            ]);

            foreach ([$desktopUserAgent, $iphoneUserAgent] as $userAgent) {
                $response = $this->actingAs($user)
                    ->withSession(['active_interview_id' => $session->id])
                    ->withHeader('User-Agent', $userAgent)
                    ->get(route('interview.session'));

                $response->assertOk()
                    ->assertSee('id="answerTranscriptControls"', false)
                    ->assertSee('id="recordingTimer"', false)
                    ->assertSee('id="voiceControls"', false)
                    ->assertSee('const displayRealtimeTranscriptInTextarea = true;', false)
                    ->assertSee('function fullVoiceTranscriptionUnavailableMessage()', false)
                    ->assertSee('let activeTranscriptionEngine = isHybridTranscriptionMode() && displayRealtimeTranscriptInTextarea?', false)
                    ->assertSee('function renderSpeechTranscript()', false)
                    ->assertSee('function commitSpeechSegment(segment)', false)
                    ->assertSee('function currentRecordingTimerSeconds()', false)
                    ->assertSee('function scheduleRecordingTimerSideEffects(previousSeconds)', false)
                    ->assertSee('setInterval(() => syncRecordingTimerDisplay(), 250)', false)
                    ->assertSee('setTimeout(() => {', false)
                    ->assertSee('let voiceSessionRecordingStartedAt = 0;', false)
                    ->assertSee('voiceSessionRecordingStartedAt = recordingTimerNow();', false)
                    ->assertSee('startRecordingTimer(voiceSessionRecordingStartedAt);', false)
                    ->assertSee('pauseRecordingTimer();', false)
                    ->assertDontSee('recTimerSeconds++;', false)
                    ->assertSee('Recording ready - live transcript will appear in the answer box', false)
                    ->assertSee('Recording stopped - transcript is ready to edit', false)
                    ->assertSee('Full voice transcript added', false)
                    ->assertSee('await stopVoiceSessionRecorder();', false)
                    ->assertSee('async function transcribeVoiceSessionRecording', false)
                    ->assertSee('requestFullVoiceSessionTranscript(recording, question, previousTranscript)', false)
                    ->assertSee('replaceAnswerText: options.replaceAnswerText === true', false)
                    ->assertDontSee('async function flushCurrentRealtimeTranscriptForStop', false)
                    ->assertDontSee("const finalizedTranscript = await flushCurrentRealtimeTranscriptForStop('stop');", false)
                    ->assertDontSee("setTranscriptionStatus(finalizedTranscript? 'Transcript ready': '')", false)
                    ->assertDontSee('id="voiceSessionTranscript"', false)
                    ->assertDontSee('<span>Transcript</span>', false)
                    ->assertDontSee('updates every few seconds', false)
                    ->assertDontSee('Finalizing transcription', false)
                    ->assertSee('function autoCorrectTranscriptText', false)
                    ->assertSee('function appendVoiceSessionRecordingUpload', false)
                    ->assertSee("formData.append('voice_audio'", false)
                    ->assertSee('Voice answer saved for feedback. Transcript is unavailable, but playback will be available in your AI feedback review.', false)
                    ->assertSee('transcriptWordCorrections', false)
                    ->assertSee('/interview/transcribe', false)
                    ->assertDontSee('mobile-response-end-session-action', false)
                    ->assertDontSee('mobile-response-end-session-btn', false)
                    ->assertDontSee('id="voiceSessionDownload"', false);

                if ($responseMode === 'voice') {
                    $response->assertDontSee('id="answerTextarea"', false)
                        ->assertDontSee('class="answer-transcript-stage"', false)
                        ->assertDontSee('id="responseCountBar"', false)
                        ->assertSee('id="voiceSessionPanel"', false)
                        ->assertSee('<span>Download</span>', false);
                } else {
                    $response->assertSee('id="answerTextarea"', false)
                        ->assertSee('class="answer-transcript-stage"', false)
                        ->assertSee('id="responseCountBar"', false)
                        ->assertSee('Speak your answer, then edit the transcript here if needed...', false)
                        ->assertDontSee('id="voiceSessionPanel"', false)
                        ->assertDontSee('<span>Download</span>', false);
                }
            }
        }
    }

    public function test_mobile_interview_session_uses_camera_detection_gate_and_fullscreen_script(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $category = Category::create([
            'title' => 'Behavioral',
            'description' => 'Behavioral questions',
            'status' => 'active',
            'type' => 'core',
        ]);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'response_mode' => 'text',
            'status' => 'in_progress',
        ]);
        Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Describe a time you solved a difficult issue.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('interview.session'));

        $response->assertOk()
            ->assertSee('<body class="user-mobile-shell mobile-shell"', false)
            ->assertSee('css/mobile/interview/session.css?v=44', false)
            ->assertSee('const cameraDetectionEnabled = false;', false)
            ->assertSee('const cameraPreviewEnabled = cameraDetectionEnabled;', false)
            ->assertSee('Camera OFF', false)
            ->assertSee('function clearSubmittedAnswerInput()', false)
            ->assertSee("chatContainer.innerHTML = ''", false)
            ->assertSee('if (!isSubmittingAnswer)', false)
            ->assertDontSee('mobile-response-end-session-action', false)
            ->assertDontSee('mobile-response-end-session-btn', false)
            ->assertSee('id="responseFullscreenToggle"', false)
            ->assertSee('enterMobileFullscreen({ requestBrowser: false });', false)
            ->assertSee('enterMobileFullscreen();', false)
            ->assertSee('function handleInterviewEscapeKey(event)', false)
            ->assertSee('exitMobileFullscreen();', false)
            ->assertDontSee('class="mobile-camera-pip d-lg-none"', false)
            ->assertDontSee('id="userCameraMobile"', false)
            ->assertDontSee('<body class="mobile-interview-fullscreen"', false);

        $content = $response->getContent();
        $textareaPosition = strpos($content, 'id="answerTextarea"');
        $stageSearchContent = $textareaPosition === false? '': substr($content, 0, $textareaPosition);
        $stageOpenPosition = strrpos($stageSearchContent, 'class="answer-transcript-stage"');
        $counterPosition = strpos($content, 'id="responseCountBar"');
        $counterClosePosition = strpos($content, '</div>', $counterPosition);
        $stageClosePosition = strpos($content, '</div>', $counterClosePosition + 6);
        $controlsPosition = strpos($content, 'id="answerTranscriptControls"');
        $avatarPanelPosition = strpos($content, 'ai-avatar-panel');
        $aiQuestionTextPosition = strpos($content, 'id="aiQuestionText"');

        $this->assertNotFalse($stageOpenPosition);
        $this->assertNotFalse($textareaPosition);
        $this->assertNotFalse($counterPosition);
        $this->assertNotFalse($counterClosePosition);
        $this->assertNotFalse($stageClosePosition);
        $this->assertNotFalse($controlsPosition);
        $this->assertNotFalse($avatarPanelPosition);
        $this->assertNotFalse($aiQuestionTextPosition);
        $this->assertLessThan($textareaPosition, $stageOpenPosition);
        $this->assertLessThan($counterPosition, $textareaPosition);
        $this->assertLessThan($counterClosePosition, $counterPosition);
        $this->assertLessThan($stageClosePosition, $counterClosePosition);
        $this->assertLessThan($controlsPosition, $avatarPanelPosition);
        $this->assertLessThan($aiQuestionTextPosition, $controlsPosition);
        $this->assertLessThan($textareaPosition, $controlsPosition);

        $mobileSessionCss = file_get_contents(public_path('css/mobile/interview/session.css'));
        $this->assertStringContainsString('position: static;', $mobileSessionCss);
        $this->assertStringContainsString('padding: 12px 12px 36px !important;', $mobileSessionCss);
        $this->assertStringContainsString('height: clamp(148px, 32dvh, 190px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('height: clamp(174px, calc(var(--sr-visual-vh, 100dvh) - 422px), 238px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('height: clamp(162px, calc(var(--sr-visual-vh, 100dvh) - 404px), 224px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('margin-bottom: 14px !important;', $mobileSessionCss);
        $this->assertStringContainsString('overflow: hidden !important;', $mobileSessionCss);
        $this->assertStringContainsString('html body.mobile-interview-fullscreen.user-mobile-shell #sec-interview-session .response-panel', $mobileSessionCss);
        $this->assertStringNotContainsString('height: clamp(292px, 46dvh, 340px) !important;', $mobileSessionCss);
        $this->assertStringNotContainsString('height: clamp(270px, 44dvh, 318px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('padding: 10px 10px 30px !important;', $mobileSessionCss);
        $this->assertStringContainsString('html body.user-mobile-shell #sec-interview-session .response-autosave-row', $mobileSessionCss);
        $this->assertStringContainsString('inset: auto 12px 10px auto !important;', $mobileSessionCss);
        $this->assertStringContainsString('right: 12px !important;', $mobileSessionCss);
        $this->assertStringContainsString('left: auto !important;', $mobileSessionCss);
        $this->assertStringContainsString('background: transparent !important;', $mobileSessionCss);
        $this->assertStringContainsString('box-shadow: none !important;', $mobileSessionCss);
        $this->assertStringContainsString('overflow-wrap: anywhere !important;', $mobileSessionCss);
        $this->assertStringContainsString('text-overflow: clip !important;', $mobileSessionCss);
        $this->assertStringContainsString('white-space: normal !important;', $mobileSessionCss);
        $this->assertStringContainsString('--interview-avatar-lift: clamp(-34px, -4vh, -22px);', $mobileSessionCss);
        $this->assertStringContainsString('height: clamp(390px, calc(var(--sr-visual-vh, 100dvh) * 0.55), 520px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('width: clamp(152px, 42vw, 180px) !important;', $mobileSessionCss);
        $this->assertStringContainsString('--spectrum-radius: clamp(88px, 23vw, 96px);', $mobileSessionCss);
        $this->assertStringContainsString('transform: translateY(var(--interview-avatar-lift)) !important;', $mobileSessionCss);
        $this->assertStringContainsString('inset: auto 16px 22px 16px !important;', $mobileSessionCss);

        $session->update([
            'accommodation_profile' => ['camera_detection' => true],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_interview_id' => $session->id])
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('interview.session'));

        $response->assertOk()
            ->assertSee('const cameraDetectionEnabled = true;', false)
            ->assertSee('Camera ON', false)
            ->assertSee('class="mobile-camera-pip d-lg-none"', false)
            ->assertSee('id="userCameraMobile"', false)
            ->assertDontSee('id="cameraPanel"', false)
            ->assertDontSee('id="cameraDetectionStatus"', false);
    }
}
