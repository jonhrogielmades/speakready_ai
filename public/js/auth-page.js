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
         recovery: ['Sending recovery details...', 'Opening a secure path back to your account.'],
         reset: ['Updating your password...', 'Securing your account with the new password.'],
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

      var passwordInput = document.querySelector('[data-password-strength]') || document.getElementById('signupPass');
      var confirmInput = document.querySelector('[data-password-confirm]') || document.getElementById('signupPassConfirm');
      var validStatus = document.querySelector('[data-password-valid]') || document.getElementById('signupPasswordValid');
      var invalidStatus = document.querySelector('[data-password-invalid]') || document.getElementById('signupPasswordInvalid');
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

      var registerStepsForm = document.querySelector('[data-register-steps]');
      if (registerStepsForm) {
         var registerPanels = Array.from(registerStepsForm.querySelectorAll('[data-register-step-panel]'));
         var registerIndicators = Array.from(registerStepsForm.querySelectorAll('[data-register-step-indicator]'));
         var registerTriggers = Array.from(registerStepsForm.querySelectorAll('[data-register-step-target]'));
         var registerStatus = registerStepsForm.querySelector('[data-register-step-status]');
         var registerStep = Number.parseInt(registerStepsForm.dataset.registerStep || '1', 10);
         registerStep = Math.min(3, Math.max(1, Number.isFinite(registerStep) ? registerStep : 1));

         function isRegisterStepComplete(step) {
            var panel = registerStepsForm.querySelector('[data-register-step-panel="' + step + '"]');
            if (!panel) return true;

            return Array.from(panel.querySelectorAll('input, select, textarea')).every(function (field) {
               return field.checkValidity();
            });
         }

         function highestUnlockedRegisterStep() {
            if (!isRegisterStepComplete(1)) return 1;
            if (!isRegisterStepComplete(2)) return 2;
            return 3;
         }

         function validateRegisterStep(step) {
            var panel = registerStepsForm.querySelector('[data-register-step-panel="' + step + '"]');
            if (!panel) return true;

            var fields = Array.from(panel.querySelectorAll('input, select, textarea'));
            for (var index = 0; index < fields.length; index += 1) {
               if (!fields[index].checkValidity()) {
                  fields[index].reportValidity();
                  return false;
               }
            }
            return true;
         }

         function syncRegisterStep(step, shouldFocus) {
            registerStep = Math.min(3, Math.max(1, step));
            registerStep = Math.min(registerStep, highestUnlockedRegisterStep());
            registerStepsForm.dataset.registerStep = String(registerStep);

            registerPanels.forEach(function (panel) {
               panel.classList.toggle('is-active', Number(panel.dataset.registerStepPanel) === registerStep);
            });

            registerIndicators.forEach(function (indicator) {
               var indicatorStep = Number(indicator.dataset.registerStepIndicator);
               var isComplete = indicatorStep < registerStep;
               indicator.classList.toggle('is-active', indicatorStep === registerStep);
               indicator.classList.toggle('is-complete', isComplete);
               indicator.setAttribute('aria-disabled', indicatorStep > highestUnlockedRegisterStep() ? 'true' : 'false');
               indicator.setAttribute('aria-current', indicatorStep === registerStep ? 'step' : 'false');

               var number = indicator.querySelector('[data-register-step-number]');
               var check = indicator.querySelector('[data-register-step-check]');
               if (number) number.hidden = isComplete;
               if (check) check.hidden = !isComplete;
            });

            registerTriggers.forEach(function (trigger) {
               var targetStep = Number(trigger.dataset.registerStepTarget);
               var isUnlocked = targetStep <= highestUnlockedRegisterStep();
               trigger.disabled = !isUnlocked;
               trigger.setAttribute('aria-disabled', isUnlocked ? 'false' : 'true');
               trigger.setAttribute('aria-current', targetStep === registerStep ? 'step' : 'false');
            });

            if (registerStatus) registerStatus.textContent = 'Step ' + registerStep + ' of 3';

            if (shouldFocus) {
               var activePanel = registerStepsForm.querySelector('[data-register-step-panel="' + registerStep + '"]');
               var firstField = activePanel ? activePanel.querySelector('input, select, textarea') : null;
               if (firstField) firstField.focus({ preventScroll: true });
            }
         }

         registerStepsForm.querySelectorAll('[data-register-next]').forEach(function (button) {
            button.addEventListener('click', function () {
               var nextStep = Number.parseInt(button.dataset.registerNext, 10) + 1;
               if (validateRegisterStep(registerStep)) syncRegisterStep(nextStep, true);
            });
         });

         registerTriggers.forEach(function (trigger) {
            trigger.addEventListener('click', function () {
               var targetStep = Number.parseInt(trigger.dataset.registerStepTarget, 10);
               if (targetStep <= highestUnlockedRegisterStep()) syncRegisterStep(targetStep, true);
            });
         });

         registerStepsForm.querySelectorAll('input, select, textarea').forEach(function (field) {
            field.addEventListener('input', function () { syncRegisterStep(registerStep, false); });
            field.addEventListener('change', function () { syncRegisterStep(registerStep, false); });
         });

         registerStepsForm.addEventListener('submit', function (event) {
            if (registerStep >= 3) return;
            event.preventDefault();
            if (validateRegisterStep(registerStep)) syncRegisterStep(registerStep + 1, true);
         });

         syncRegisterStep(registerStep, false);
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
