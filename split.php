<?php

$content = file_get_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\welcome.blade.php');

$head = substr($content, 0, strpos($content, '<body>') + 6);
$scripts = substr($content, strpos($content, '<!-- ======================== SCRIPTS ======================== -->'));

$landing_start = strpos($content, '<!-- ======================== LANDING PAGE ======================== -->');
$dashboard_start = strpos($content, '<!-- ======================== DASHBOARD ======================== -->');

$landing_content = substr($content, $landing_start, $dashboard_start - $landing_start);
$landing_content = str_replace('<div id="landing" @auth style="display:none" @endauth>', '<div id="landing">', $landing_content);

$guest_blade = $head . "\n" . $landing_content . "\n" . $scripts;
file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\layouts\\guest.blade.php', $guest_blade);

// Dashboard App Layout
$dashboard_content = substr($content, $dashboard_start, strpos($content, '<!-- ======================== SCRIPTS ======================== -->') - $dashboard_start);
$dashboard_content = str_replace('<div id="dashboard" @guest style="display:none" @endguest>', '<div id="dashboard">', $dashboard_content);

// We need to split the sidebar and the content area
// Look for <!-- /db-sidebar --> or the start of <div class="db-main">
$db_main_start = strpos($dashboard_content, '<div class="db-main">');
$db_sidebar = substr($dashboard_content, 0, $db_main_start);

// The navbar inside db-main
$navbar_start = strpos($dashboard_content, '<div class="db-top">', $db_main_start);
$db_content_start = strpos($dashboard_content, '<div class="db-content">', $navbar_start);
$navbar = substr($dashboard_content, $navbar_start, $db_content_start - $navbar_start);

$app_blade = $head . "\n" . $db_sidebar . "\n<div class=\"db-main\">\n" . $navbar . "\n<div class=\"db-content\">\n    @yield('content')\n</div>\n</div>\n</div>\n" . $scripts;
file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\layouts\\app.blade.php', $app_blade);

echo "Layouts created.\n";

// Now extract the individual sections
function extract_section($html, $id) {
    $start = strpos($html, '<div class="db-section" id="' . $id . '">');
    if ($start === false) {
        $start = strpos($html, '<div class="db-section active" id="' . $id . '">');
    }
    if ($start === false) {
        // try with other classes
        $start = preg_match('/<div class="db-section[^"]*" id="' . $id . '">/', $html, $matches, PREG_OFFSET_CAPTURE);
        if ($start) {
            $start = $matches[0][1];
        } else {
            return "Section $id not found\n";
        }
    }
    
    // Find the next <!-- -- ... -- --> comment or <!-- /db-content -->
    $next_comment = strpos($html, '<!-- -- ', $start + 1);
    $end_content = strpos($html, '<!-- /db-content -->', $start + 1);
    
    $end = min($next_comment ?: 9999999, $end_content ?: 9999999);
    
    return trim(substr($html, $start, $end - $start));
}

$overview = extract_section($dashboard_content, 'sec-overview');
$overview = preg_replace('/class="db-section[^"]*" id="sec-overview"/', 'class="db-section active" id="sec-overview"', $overview);
file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\dashboard.blade.php', "@extends('layouts.app')\n@section('content')\n" . $overview . "\n@endsection");

$interview_setup = extract_section($dashboard_content, 'sec-interview-setup');
$interview_setup = preg_replace('/class="db-section[^"]*" id="sec-interview-setup"/', 'class="db-section active" id="sec-interview-setup"', $interview_setup);
file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\interview\\setup.blade.php', "@extends('layouts.app')\n@section('content')\n" . $interview_setup . "\n@endsection");

$interview_session = extract_section($dashboard_content, 'sec-interview-session');
$interview_session = preg_replace('/class="db-section[^"]*" id="sec-interview-session"/', 'class="db-section active" id="sec-interview-session"', $interview_session);
file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\interview\\session.blade.php', "@extends('layouts.app')\n@section('content')\n" . $interview_session . "\n@endsection");

$admin_users = extract_section($dashboard_content, 'sec-admin-users');
$admin_users = preg_replace('/class="db-section[^"]*" id="sec-admin-users"/', 'class="db-section active" id="sec-admin-users"', $admin_users);
file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\admin\\users.blade.php', "@extends('layouts.app')\n@section('content')\n" . $admin_users . "\n@endsection");

$admin_categories = extract_section($dashboard_content, 'sec-admin-categories');
$admin_categories = preg_replace('/class="db-section[^"]*" id="sec-admin-categories"/', 'class="db-section active" id="sec-admin-categories"', $admin_categories);
file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\admin\\categories.blade.php', "@extends('layouts.app')\n@section('content')\n" . $admin_categories . "\n@endsection");

$admin_questions = extract_section($dashboard_content, 'sec-admin-questions');
$admin_questions = preg_replace('/class="db-section[^"]*" id="sec-admin-questions"/', 'class="db-section active" id="sec-admin-questions"', $admin_questions);
file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\admin\\questions.blade.php', "@extends('layouts.app')\n@section('content')\n" . $admin_questions . "\n@endsection");

$admin_modules = extract_section($dashboard_content, 'sec-admin-modules');
$admin_modules = preg_replace('/class="db-section[^"]*" id="sec-admin-modules"/', 'class="db-section active" id="sec-admin-modules"', $admin_modules);

// Wait, the modals are at the bottom of the db-main. I need to get the modals too.
$modals_start = strpos($dashboard_content, '<!-- Admin Modals -->');
if ($modals_start !== false) {
    $modals_end = strpos($dashboard_content, '<!-- /dashboard -->', $modals_start);
    $modals = substr($dashboard_content, $modals_start, $modals_end - $modals_start);
    
    // Distribute modals to their respective views
    $cat_modal = substr($modals, strpos($modals, '<div class="modal fade" id="addCategoryModal"'), strpos($modals, '<div class="modal fade" id="addQuestionModal"') - strpos($modals, '<div class="modal fade" id="addCategoryModal"'));
    $q_modal = substr($modals, strpos($modals, '<div class="modal fade" id="addQuestionModal"'), strpos($modals, '<div class="modal fade" id="addModuleModal"') - strpos($modals, '<div class="modal fade" id="addQuestionModal"'));
    $m_modal = substr($modals, strpos($modals, '<div class="modal fade" id="addModuleModal"'), strpos($modals, '@endif') - strpos($modals, '<div class="modal fade" id="addModuleModal"'));
    
    $admin_categories .= "\n" . $cat_modal;
    file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\admin\\categories.blade.php', "@extends('layouts.app')\n@section('content')\n" . $admin_categories . "\n@endsection");

    $admin_questions .= "\n" . $q_modal;
    file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\admin\\questions.blade.php', "@extends('layouts.app')\n@section('content')\n" . $admin_questions . "\n@endsection");

    $admin_modules .= "\n" . $m_modal;
    file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\admin\\modules.blade.php', "@extends('layouts.app')\n@section('content')\n" . $admin_modules . "\n@endsection");
}

// Replace welcome.blade.php to just extend layouts.guest
file_put_contents('c:\\laragon\\www\\nexusai-1.0.0\\resources\\views\\welcome.blade.php', "@extends('layouts.guest')");

echo "Views extracted successfully.\n";

