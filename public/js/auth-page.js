(function () {
   'use strict';

   function initializeAuthPage() {
      var page = document.getElementById('authPage');
      if (!page) return;

      var overlay = document.getElementById('authTransitionOverlay');
      var transitionTitle = document.getElementById('authTransitionTitle');
      var transitionCopy = document.getElementById('authTransitionCopy');
      var themeToggle = document.getElementById('authPageThemeToggle');

      var transitionMessages = {
         login: ['Logging you in...', 'Opening your interview workspace securely.'],
         register: ['Creating your account...', 'Setting up your interview practice workspace.'],
         'google-login': ['Connecting to Google...', 'Opening secure Google sign-in.'],
         'google-register': ['Signing up with Google...', 'Opening secure Google registration.']
      };

      function showAuthTransition(mode) {
         if (!overlay) return;

         var message = transitionMessages[mode] || transitionMessages.login;
         if (transitionTitle) transitionTitle.textContent = message[0];
         if (transitionCopy) transitionCopy.textContent = message[1];
         overlay.hidden = false;
         window.requestAnimationFrame(function () {
            overlay.classList.add('is-active');
         });
      }

      function hideAuthTransition() {
         if (!overlay) return;
         overlay.classList.remove('is-active');
         overlay.hidden = true;
      }

      function syncThemeToggle() {
         if (!themeToggle) return;

         var isDark = window.SpeakReadyTheme
            ? window.SpeakReadyTheme.get() === 'dark'
            : document.documentElement.dataset.theme === 'dark';
         var sunIcon = themeToggle.querySelector('[data-auth-theme-icon="sun"]');
         var moonIcon = themeToggle.querySelector('[data-auth-theme-icon="moon"]');

         if (sunIcon) sunIcon.hidden = !isDark;
         if (moonIcon) moonIcon.hidden = isDark;
         themeToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
         themeToggle.setAttribute('aria-label', isDark ? 'Switch to light theme' : 'Switch to dark theme');
         themeToggle.title = isDark ? 'Switch to light theme' : 'Switch to dark theme';
      }

      if (themeToggle) {
         syncThemeToggle();
         themeToggle.addEventListener('click', function () {
            if (window.SpeakReadyTheme && typeof window.SpeakReadyTheme.toggle === 'function') {
               window.SpeakReadyTheme.toggle();
            }
            syncThemeToggle();
         });
      }

      window.showAuthTransition = showAuthTransition;
      window.showLoginTransition = showAuthTransition;

      document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
         button.addEventListener('click', function () {
            var inputId = button.getAttribute('aria-controls');
            var input = inputId ? document.getElementById(inputId) : null;
            var icon = button.querySelector('i');
            if (!input) return;

            var shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
            if (icon) {
               icon.classList.toggle('fa-eye', !shouldShow);
               icon.classList.toggle('fa-eye-slash', shouldShow);
            }
            input.focus({ preventScroll: true });
         });
      });

      var passwordInput = document.getElementById('signupPass');
      var confirmInput = document.getElementById('signupPassConfirm');
      var validStatus = document.getElementById('signupPasswordValid');
      var invalidStatus = document.getElementById('signupPasswordInvalid');
      var confirmStatus = document.querySelector('[data-confirm-password-status]');

      function passwordRules(value) {
         return {
            length: Array.from(value).length >= 8,
            lower: /\p{Ll}/u.test(value),
            upper: /\p{Lu}/u.test(value),
            number: /\p{N}/u.test(value),
            symbol: /[\p{Z}\p{S}\p{P}]/u.test(value)
         };
      }

      function updatePasswordFeedback() {
         if (!passwordInput) return;

         var value = passwordInput.value || '';
         var rules = passwordRules(value);
         var strongPasswordRequired = passwordInput.dataset.strongPassword === 'true';
         var passwordIsValid = rules.length && (!strongPasswordRequired || (rules.lower && rules.upper && rules.number && rules.symbol));

         document.querySelectorAll('[data-password-rule]').forEach(function (item) {
            var ruleName = item.dataset.passwordRule;
            var met = Boolean(rules[ruleName]);
            item.classList.toggle('is-met', met);
            var icon = item.querySelector('i');
            if (icon) {
               icon.classList.toggle('fa-circle', !met);
               icon.classList.toggle('fa-circle-check', met);
            }
         });

         if (validStatus) validStatus.hidden = !passwordIsValid;
         if (invalidStatus) invalidStatus.hidden = value === '' || passwordIsValid;

         if (value === '' || passwordIsValid) {
            passwordInput.setCustomValidity('');
            if (value !== '') passwordInput.setAttribute('aria-invalid', 'false');
         } else {
            passwordInput.setCustomValidity(strongPasswordRequired
               ? 'Use at least 8 characters with uppercase, lowercase, a number, and a symbol.'
               : 'Use at least 8 characters.');
            passwordInput.setAttribute('aria-invalid', 'true');
         }

         updateConfirmPasswordFeedback();
      }

      function updateConfirmPasswordFeedback() {
         if (!passwordInput || !confirmInput || !confirmStatus) return;

         var confirmation = confirmInput.value || '';
         var matches = confirmation !== '' && confirmation === passwordInput.value;
         if (confirmation === '') {
            confirmInput.setCustomValidity('');
            confirmInput.removeAttribute('aria-invalid');
            confirmStatus.textContent = 'Re-enter your password to confirm it.';
            confirmStatus.classList.remove('is-valid', 'is-invalid');
         } else if (matches) {
            confirmInput.setCustomValidity('');
            confirmInput.setAttribute('aria-invalid', 'false');
            confirmStatus.textContent = 'Passwords match.';
            confirmStatus.classList.add('is-valid');
            confirmStatus.classList.remove('is-invalid');
         } else {
            confirmInput.setCustomValidity('Passwords do not match.');
            confirmInput.setAttribute('aria-invalid', 'true');
            confirmStatus.textContent = 'Passwords do not match yet.';
            confirmStatus.classList.add('is-invalid');
            confirmStatus.classList.remove('is-valid');
         }
      }

      if (passwordInput) {
         passwordInput.addEventListener('input', updatePasswordFeedback);
         passwordInput.addEventListener('change', updatePasswordFeedback);
         updatePasswordFeedback();
      }
      if (confirmInput) {
         confirmInput.addEventListener('input', updateConfirmPasswordFeedback);
         confirmInput.addEventListener('change', updateConfirmPasswordFeedback);
         updateConfirmPasswordFeedback();
      }

      document.querySelectorAll('[data-auth-submit]').forEach(function (form) {
         form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === 'true') {
               event.preventDefault();
               return;
            }
            if (!form.checkValidity()) return;

            form.dataset.submitting = 'true';
            var submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
               submitButton.disabled = true;
               submitButton.setAttribute('aria-disabled', 'true');
            }
            showAuthTransition(form.dataset.authSubmit || 'login');
         });
      });

      document.querySelectorAll('[data-auth-transition]').forEach(function (link) {
         link.addEventListener('click', function (event) {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            link.classList.add('is-loading');
            link.setAttribute('aria-disabled', 'true');
            showAuthTransition(link.dataset.authTransition || 'google-login');
         });
      });

      window.addEventListener('pageshow', function () {
         hideAuthTransition();
         syncThemeToggle();
         document.querySelectorAll('[data-auth-submit]').forEach(function (form) {
            form.dataset.submitting = 'false';
            var submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
               submitButton.disabled = false;
               submitButton.removeAttribute('aria-disabled');
            }
         });
         document.querySelectorAll('[data-auth-transition]').forEach(function (link) {
            link.classList.remove('is-loading');
            link.removeAttribute('aria-disabled');
         });
      });

      var errorSummary = document.querySelector('[data-auth-error-summary]');
      if (errorSummary) {
         window.setTimeout(function () { errorSummary.focus({ preventScroll: true }); }, 0);
      }
   }

   if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initializeAuthPage, { once: true });
   } else {
      initializeAuthPage();
   }
})();
