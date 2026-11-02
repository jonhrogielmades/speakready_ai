<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MobileAuthModalInteractionTest extends TestCase
{
    public function test_login_and_registration_use_a_dedicated_full_page_contract(): void
    {
        $markup = $this->readProjectFile('resources/views/shared/auth-page.blade.php');
        $mobileLayout = $this->readProjectFile('resources/views/mobile/layouts/auth.blade.php');
        $script = $this->readProjectFile('public/js/auth-page.js');
        $css = $this->readProjectFile('public/css/shared/auth-page.css');

        $this->assertStringContainsString('id="authPage" class="auth-page-shell"', $markup);
        $this->assertStringContainsString('class="auth-page-card"', $markup);
        $this->assertStringContainsString('id="authFormPanel"', $markup);
        $this->assertStringContainsString("action=\"{{ route('login.submit') }}\"", $markup);
        $this->assertStringContainsString("action=\"{{ route('register.submit') }}\"", $markup);
        $this->assertStringContainsString('data-auth-submit="login"', $markup);
        $this->assertStringContainsString('data-auth-submit="register"', $markup);
        $forgotLinkPosition = strpos($markup, 'class="auth-page-forgot-link"');
        $passwordInputPosition = strpos($markup, 'id="loginPass"');
        $this->assertNotFalse($forgotLinkPosition);
        $this->assertNotFalse($passwordInputPosition);
        $this->assertGreaterThan($passwordInputPosition, $forgotLinkPosition, 'The forgot-password link should sit below the password input.');
        $this->assertStringContainsString('.auth-page-forgot-link { display: flex; justify-content: flex-end; margin-top: 8px;', $css);
        $this->assertStringNotContainsString('class="modal', $markup);
        $this->assertStringNotContainsString('data-bs-toggle="modal"', $markup);

        $this->assertStringContainsString("document.getElementById('authPage')", $script);
        $this->assertStringContainsString('body.auth-page-body.auth-desktop-shell', $css);
        $this->assertStringContainsString('body.auth-page-body.auth-mobile-shell', $css);
        $this->assertStringContainsString('min-height: 100dvh;', $css);
        $this->assertStringContainsString('min-height: 100svh;', $css);
        $this->assertStringContainsString('overflow-anchor: none;', $css);
        $this->assertStringContainsString('class="auth-mobile-document"', $mobileLayout);
        $this->assertStringContainsString('html.auth-mobile-document { overflow-y: clip; }', $css);
        $this->assertStringContainsString('.auth-page-card { flex: 0 0 auto; height: auto; margin-top: auto; margin-bottom: auto;', $css);
        $this->assertStringContainsString('@media (max-width: 390px) and (max-height: 700px)', $css);
        $this->assertStringContainsString('var pageScrollTop = window.scrollY;', $script);
        $this->assertStringContainsString('authFormPanel.scrollTop = formScrollTop;', $script);
    }

    public function test_registration_password_has_live_strength_and_confirmation_feedback(): void
    {
        $markup = $this->readProjectFile('resources/views/shared/auth-page.blade.php');
        $script = $this->readProjectFile('public/js/auth-page.js');

        $this->assertStringContainsString('data-strong-password=', $markup);
        $this->assertStringContainsString('data-password-rule="length"', $markup);
        $this->assertStringContainsString('data-password-rule="lower"', $markup);
        $this->assertStringContainsString('data-password-rule="upper"', $markup);
        $this->assertStringContainsString('data-password-rule="number"', $markup);
        $this->assertStringContainsString('data-password-rule="symbol"', $markup);
        $this->assertStringContainsString('id="signupPasswordValid"', $markup);
        $this->assertStringContainsString('id="signupPasswordInvalid"', $markup);
        $this->assertStringContainsString('data-confirm-password-status', $markup);
        $this->assertStringContainsString('role="status" hidden', $markup);
        $this->assertStringContainsString('data-register-steps', $markup);
        $this->assertStringContainsString('data-register-step-panel="1"', $markup);
        $this->assertStringContainsString('data-register-step-panel="2"', $markup);
        $this->assertStringContainsString('data-register-step-panel="3"', $markup);
        $this->assertStringContainsString('data-register-step-check', $markup);
        $this->assertStringContainsString('data-register-step-target="2"', $markup);
        $this->assertStringContainsString('aria-disabled="true" disabled', $markup);
        $this->assertStringContainsString('data-register-next="1"', $markup);
        $this->assertStringContainsString('data-register-next="2"', $markup);
        $this->assertStringContainsString('Continue to create account', $markup);

        $this->assertStringContainsString('Array.from(value).length >= 8', $script);
        $this->assertStringContainsString('/\p{Ll}/u.test(value)', $script);
        $this->assertStringContainsString('/\p{Lu}/u.test(value)', $script);
        $this->assertStringContainsString('/\p{N}/u.test(value)', $script);
        $this->assertStringContainsString('/[\p{Z}\p{S}\p{P}]/u.test(value)', $script);
        $this->assertStringContainsString("passwordInput.addEventListener('input', updatePasswordFeedback)", $script);
        $this->assertStringContainsString("confirmInput.addEventListener('input', updateConfirmPasswordFeedback)", $script);
        $this->assertStringContainsString("passwordInput.setCustomValidity('')", $script);
        $this->assertStringContainsString("confirmInput.setCustomValidity('Passwords do not match.')", $script);
        $this->assertStringContainsString("confirmStatus.textContent = 'Passwords match.'", $script);
        $this->assertStringContainsString('validateRegisterStep', $script);
        $this->assertStringContainsString('highestUnlockedRegisterStep', $script);
        $this->assertStringContainsString('trigger.addEventListener(\'click\'', $script);
        $this->assertStringContainsString('trigger.disabled = !isUnlocked', $script);
        $this->assertStringContainsString("indicator.classList.toggle('is-complete', isComplete)", $script);
        $this->assertStringContainsString("check.hidden = !isComplete", $script);
    }

    public function test_mobile_inputs_and_password_toggles_are_zoom_and_touch_safe(): void
    {
        $markup = $this->readProjectFile('resources/views/shared/auth-page.blade.php');
        $script = $this->readProjectFile('public/js/auth-page.js');
        $css = $this->readProjectFile('public/css/shared/auth-page.css');
        $mobileCss = $this->sectionBetween(
            $css,
            '@media (max-width: 640px)',
            '@media (max-width: 380px)'
        );

        $this->assertMatchesRegularExpression(
            '/\.auth-page-input\s*\{[^}]*font-size:\s*1rem\s*;[^}]*\}/s',
            $mobileCss,
            'Mobile auth inputs must use at least 16px text to avoid automatic iOS zoom.'
        );

        $toggleRule = $this->cssRule($css, '.auth-page-password-toggle');
        $toggleWidth = $this->pixelProperty($toggleRule, 'width');
        $toggleHeight = $this->pixelProperty($toggleRule, 'height');
        $this->assertGreaterThanOrEqual(42.0, $toggleWidth, 'Password toggles must be at least 42px wide.');
        $this->assertGreaterThanOrEqual(42.0, $toggleHeight, 'Password toggles must be at least 42px tall.');

        $this->assertStringContainsString('data-password-toggle', $markup);
        $this->assertStringContainsString('aria-controls="signupPass"', $markup);
        $this->assertStringContainsString('aria-controls="loginPass"', $markup);
        $this->assertStringContainsString("button.setAttribute('aria-pressed'", $script);
        $this->assertStringContainsString("input.focus({ preventScroll: true })", $script);
    }

    public function test_mobile_auth_page_scrolls_naturally_without_modal_keyboard_workarounds(): void
    {
        $markup = $this->readProjectFile('resources/views/shared/auth-page.blade.php');
        $script = $this->readProjectFile('public/js/auth-page.js');
        $css = $this->readProjectFile('public/css/shared/auth-page.css');
        $mobileCss = $this->sectionBetween(
            $css,
            '@media (max-width: 640px)',
            '@media (max-width: 380px)'
        );

        $this->assertMatchesRegularExpression(
            '/\.auth-page-card\s*\{[^}]*overflow:\s*visible\s*;[^}]*\}/s',
            $mobileCss,
            'The mobile auth card must allow natural document scrolling.'
        );
        $this->assertMatchesRegularExpression(
            '/\.auth-page-form-side\s*\{[^}]*align-items:\s*flex-start\s*;[^}]*\}/s',
            $mobileCss
        );

        $combined = $markup."\n".$script."\n".$css;
        foreach (['#lofc', '.modal-dialog', 'auth-modal-field-focused', 'visualViewport', 'scrollIntoView(', 'modalBody.scrollTop', '--sr-js-vh'] as $modalArtifact) {
            $this->assertStringNotContainsString(
                $modalArtifact,
                $combined,
                "Dedicated auth page still contains modal keyboard artifact: {$modalArtifact}"
            );
        }
        $this->assertStringNotContainsString('overflow-y: hidden', $css);
    }

    public function test_auth_pages_isolate_theme_contrast_and_fit_the_desktop_viewport(): void
    {
        $markup = $this->readProjectFile('resources/views/shared/auth-page.blade.php');
        $script = $this->readProjectFile('public/js/auth-page.js');
        $css = $this->readProjectFile('public/css/shared/auth-page.css');
        $mobileLayout = $this->readProjectFile('resources/views/mobile/layouts/auth.blade.php');

        foreach (['--auth-canvas:', '--auth-panel:', '--auth-text:', '--auth-control:', '--auth-control-text:', '--auth-link:'] as $token) {
            $this->assertStringContainsString($token, $css);
        }

        $this->assertStringContainsString('html[data-theme="light"] body.auth-page-body', $css);
        $this->assertStringContainsString('body.auth-page-body.auth-desktop-shell { height: 100vh; height: 100dvh; overflow: hidden; }', $css);
        $this->assertStringContainsString('grid-template-rows: auto minmax(0, 1fr) auto;', $css);
        $this->assertStringContainsString('.auth-page-card { width: min(1180px, 100%); height: 100%; min-height: 0; flex: none; }', $css);
        $this->assertStringContainsString('overflow-y: auto;', $css);
        $this->assertStringContainsString('.auth-page-input:-webkit-autofill', $css);
        $this->assertStringContainsString('-webkit-text-fill-color: var(--auth-control-text) !important;', $css);
        $this->assertStringContainsString('.auth-page-input:focus-visible { outline: 0 !important;', $css);
        $this->assertStringContainsString('linear-gradient(120deg, #020617 0%, #071b2f 48%, #063044 100%)', $css);
        $this->assertStringContainsString('linear-gradient(120deg, #f8fcff 0%, #edfaff 46%, #dff5ff 100%)', $css);
        $this->assertStringContainsString('--auth-form-surface: #ffffff;', $css);
        $this->assertStringContainsString('--auth-mobile-promo-background:', $css);
        $this->assertStringContainsString('background: var(--auth-page-background);', $css);
        $this->assertStringContainsString('background: var(--auth-form-surface);', $css);
        $this->assertStringContainsString('border: 1px solid var(--auth-form-border);', $css);
        $this->assertStringContainsString('--tx: #153b63;', $css);
        $this->assertStringContainsString('.auth-page-shell[data-auth-mode="login"] .auth-page-card { height: auto; align-self: center; }', $css);
        $this->assertStringContainsString('.auth-page-shell[data-auth-mode="register"] .auth-page-form-side { align-items: center; }', $css);
        $this->assertStringContainsString('.auth-page-shell[data-auth-mode="login"] .auth-page-card,', $css);
        $this->assertStringContainsString('.auth-page-shell.auth-support-page .auth-page-card { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }', $css);
        $this->assertStringContainsString('.auth-page-shell[data-auth-mode="login"] .auth-page-card { flex: 0 0 auto; height: auto; align-self: center; margin-top: auto; margin-bottom: auto; }', $css);
        $this->assertStringContainsString('.auth-page-shell.auth-support-page .auth-page-card { height: auto; align-self: center; }', $css);
        $this->assertStringContainsString('.auth-page-shell.auth-support-page .auth-page-card { flex: 0 0 auto; height: auto; align-self: center; margin-top: auto; margin-bottom: auto; }', $css);
        $this->assertStringContainsString('.auth-page-shell.auth-support-page .auth-page-form-wrap { max-width: 610px; }', $css);
        $this->assertStringContainsString('.auth-page-topbar, .auth-page-card { width: min(680px, 100%); }', $css);
        $this->assertStringContainsString('.auth-page-form-side { min-height: 0; padding: 24px clamp(18px, 3vw, 28px);', $css);
        $this->assertStringContainsString('.auth-page-form-wrap, .auth-page-shell[data-auth-mode="login"] .auth-page-form-wrap { max-width: 560px; }', $css);
        $this->assertStringContainsString('background: var(--auth-panel-raised);', $css);
        $this->assertStringContainsString('border: 1px solid var(--auth-border);', $css);
        $this->assertStringNotContainsString('background: var(--bg2, #f7fafc);', $css);

        $this->assertStringContainsString('id="authPageThemeToggle"', $markup);
        $this->assertStringContainsString('window.SpeakReadyTheme.toggle()', $script);
        $this->assertStringContainsString("themeToggle.setAttribute('aria-label'", $script);
        $this->assertStringContainsString('body.auth-mobile-shell:not(.auth-page-body)', $mobileLayout);

        $switchPosition = strpos($markup, 'class="auth-page-switch"');
        $loginSubmitPosition = strpos($markup, 'id="loginBtn"');
        $this->assertNotFalse($switchPosition);
        $this->assertNotFalse($loginSubmitPosition);
        $this->assertGreaterThan($loginSubmitPosition, $switchPosition, 'The account switch should sit below the primary login/register action.');
    }

    public function test_submit_transition_prevents_double_submission_and_recovers_from_history(): void
    {
        $markup = $this->readProjectFile('resources/views/shared/auth-page.blade.php');
        $script = $this->readProjectFile('public/js/auth-page.js');

        $this->assertStringContainsString(
            'id="authTransitionOverlay" class="auth-page-transition" role="status" aria-live="polite" aria-busy="true" hidden',
            $markup
        );
        $this->assertStringContainsString('[data-auth-submit]', $script);
        $this->assertStringContainsString("if (form.dataset.submitting === 'true')", $script);
        $this->assertStringContainsString('event.preventDefault();', $script);
        $this->assertStringContainsString('if (!form.checkValidity()) return;', $script);
        $this->assertStringContainsString("form.dataset.submitting = 'true'", $script);
        $this->assertStringContainsString('submitButton.disabled = true;', $script);
        $this->assertStringContainsString("submitButton.setAttribute('aria-disabled', 'true')", $script);
        $this->assertStringContainsString("showAuthTransition(form.dataset.authSubmit || 'login')", $script);

        $validityCheck = strpos($script, 'if (!form.checkValidity()) return;');
        $transitionCall = strpos($script, "showAuthTransition(form.dataset.authSubmit || 'login')");
        $this->assertNotFalse($validityCheck);
        $this->assertNotFalse($transitionCall);
        $this->assertGreaterThan($validityCheck, $transitionCall, 'The transition must start only after native validation passes.');

        $this->assertStringContainsString("window.addEventListener('pageshow'", $script);
        $this->assertStringContainsString("form.dataset.submitting = 'false'", $script);
        $this->assertStringContainsString('submitButton.disabled = false;', $script);
        $this->assertStringContainsString("submitButton.removeAttribute('aria-disabled')", $script);
        $this->assertStringContainsString('[data-auth-transition]', $script);
        $this->assertStringContainsString("link.classList.add('is-loading')", $script);
        $this->assertStringContainsString("showAuthTransition(link.dataset.authTransition || 'google-login')", $script);
    }

    private function cssRule(string $css, string $selector): string
    {
        $pattern = '/'.preg_quote($selector, '/').'\s*\{(?<rule>[^}]*)\}/s';
        $matched = preg_match($pattern, $css, $matches);

        $this->assertSame(1, $matched, "Missing CSS rule for {$selector}.");

        return $matches['rule'];
    }

    private function pixelProperty(string $rule, string $property): float
    {
        $matched = preg_match('/'.preg_quote($property, '/').'\s*:\s*(?<value>\d+(?:\.\d+)?)px\s*;/', $rule, $matches);

        $this->assertSame(1, $matched, "Missing pixel value for {$property}.");

        return (float) $matches['value'];
    }

    private function sectionBetween(string $contents, string $startMarker, string $endMarker): string
    {
        $start = strpos($contents, $startMarker);
        $this->assertNotFalse($start, "Missing section marker: {$startMarker}");

        $end = strpos($contents, $endMarker, $start + strlen($startMarker));
        $this->assertNotFalse($end, "Missing section marker: {$endMarker}");

        return substr($contents, $start, $end - $start);
    }

    private function readProjectFile(string $relativePath): string
    {
        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        return $contents;
    }
}
