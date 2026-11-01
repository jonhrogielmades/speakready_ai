<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MobileAuthModalInteractionTest extends TestCase
{
    public function test_auth_modal_keeps_its_centered_dialog_contract(): void
    {
        $markup = $this->readProjectFile('resources/views/shared/auth-modal.blade.php');

        $this->assertStringContainsString(
            'class="modal-dialog modal-dialog-centered auth-modal-dialog"',
            $markup
        );
    }

    public function test_registration_password_shows_live_valid_and_invalid_feedback(): void
    {
        $markup = $this->readProjectFile('resources/views/shared/auth-modal.blade.php');

        $this->assertStringContainsString('placeholder="Password"', $markup);
        $this->assertStringContainsString('data-strong-password=', $markup);
        $this->assertStringContainsString('id="signupPasswordValid"', $markup);
        $this->assertStringContainsString('Password is valid.', $markup);
        $this->assertStringContainsString('id="signupPasswordInvalid"', $markup);
        $this->assertStringContainsString('class="text-danger fw-semibold ms-1"', $markup);
        $this->assertStringContainsString('fa-circle-xmark', $markup);
        $this->assertStringContainsString('role="status" hidden', $markup);
        $this->assertStringContainsString('Password is invalid.', $markup);
        $this->assertStringContainsString("input.addEventListener('input', updateSignupPasswordFeedback)", $markup);
        $this->assertStringContainsString('Array.from(value).length >= 8', $markup);
        $this->assertStringContainsString('/\p{Ll}/u.test(value)', $markup);
        $this->assertStringContainsString('/\p{Lu}/u.test(value)', $markup);
        $this->assertStringContainsString('/\p{N}/u.test(value)', $markup);
        $this->assertStringContainsString('/[\p{Z}\p{S}\p{P}]/u.test(value)', $markup);
        $this->assertStringContainsString('validStatus.hidden = !passwordIsValid', $markup);
        $this->assertStringContainsString("invalidStatus.hidden = value === '' || passwordIsValid", $markup);
        $this->assertStringContainsString('input.setCustomValidity(requirementMessage)', $markup);
    }

    public function test_checkbox_focus_does_not_enable_mobile_keyboard_layout(): void
    {
        $script = $this->authInteractionScript();

        $this->assertStringNotContainsString(
            "const authModalInputSelector = 'input, textarea, select';",
            $script,
            'Checkboxes must not be treated as text-entry fields because their retained focus shifts the mobile dialog.'
        );

        $excludesCheckboxes = str_contains($script, ':not([type="checkbox"])')
            || str_contains($script, ":not([type='checkbox'])")
            || preg_match('/(?:type|inputType)\s*!={1,2}\s*[\'\"]checkbox[\'\"]/i', $script) === 1
            || preg_match('/[\'\"]checkbox[\'\"]\s*[,\]]/i', $script) === 1;

        $this->assertTrue(
            $excludesCheckboxes,
            'The mobile auth focus handler must explicitly exclude checkbox inputs.'
        );
    }

    public function test_focused_mobile_fields_do_not_top_align_the_dialog(): void
    {
        $css = $this->readProjectFile('public/css/mobile/style.css');
        $focusRuleStart = strpos($css, 'body.auth-modal-field-focused #lofc.auth-modal .modal-dialog');

        $this->assertNotFalse($focusRuleStart, 'The focused mobile auth dialog rule is missing.');

        $focusRuleEnd = strpos($css, '}', $focusRuleStart);
        $this->assertNotFalse($focusRuleEnd, 'The focused mobile auth dialog rule is malformed.');

        $focusRule = substr($css, $focusRuleStart, $focusRuleEnd - $focusRuleStart + 1);

        $this->assertStringNotContainsString(
            'align-items: flex-start',
            $focusRule,
            'Typing or tapping a checkbox must not pin the auth dialog to the top of the viewport.'
        );
        $this->assertStringContainsString('align-items: center', $focusRule);
    }

    public function test_keyboard_scrolling_is_bounded_to_the_modal_body(): void
    {
        $script = $this->authInteractionScript();

        $this->assertStringNotContainsString(
            'scrollIntoView(',
            $script,
            'Field visibility adjustments must not scroll the page or outer modal.'
        );
        $this->assertStringContainsString('modalBody.scrollTop += scrollDelta', $script);
        $this->assertStringNotContainsString(
            "window.visualViewport.addEventListener('scroll', syncFocusedAuthModalViewport",
            $script,
            'Visual viewport scrolling must not create a scroll/reflow feedback loop.'
        );
        $this->assertStringNotContainsString(
            '--sr-js-vh',
            $script,
            'Auth interactions must reuse the shared viewport metric instead of mutating a global page-height variable.'
        );
    }

    public function test_mobile_auth_inputs_use_a_zoom_safe_font_size(): void
    {
        $css = $this->readProjectFile('public/css/mobile/style.css');

        $this->assertMatchesRegularExpression(
            '/#lofc\.auth-modal \.auth-input-wrap \.oinp\s*\{\s*font-size:\s*1rem\s*!important;\s*\}/',
            $css,
            'Mobile auth controls must use at least 16px text to prevent automatic iOS input zoom.'
        );
    }

    private function authInteractionScript(): string
    {
        $script = $this->readProjectFile('public/js/main.js');
        $start = strpos($script, 'const guestAuthPanel');
        $end = strpos($script, 'const guestSectionIds', $start ?: 0);

        $this->assertNotFalse($start, 'The auth modal interaction block is missing.');
        $this->assertNotFalse($end, 'The auth modal interaction block boundary is missing.');

        return substr($script, $start, $end - $start);
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
