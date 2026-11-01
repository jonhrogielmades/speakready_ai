(function () {
   'use strict';

   var authPage = document.querySelector('#lofc.auth-page');
   if (!authPage) return;

   var transitionOverlay = document.getElementById('loginTransitionOverlay');
   var transitionTitle = document.getElementById('authTransitionTitle');
   var transitionCopy = document.getElementById('authTransitionCopy');
   var textInputSelector = [
      'input:not([type]):not([disabled]):not([readonly])',
      'input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="button"]):not([type="submit"]):not([type="reset"]):not([type="file"]):not([type="range"]):not([type="color"]):not([disabled]):not([readonly])',
      'textarea:not([disabled]):not([readonly])'
   ].join(', ');
   var focusedFieldFrame = 0;

   window.togglePasswordVisibility = function (inputId, button) {
      var input = document.getElementById(inputId);
      var icon = button ? button.querySelector('i') : null;
      if (!input || !icon) return;

      var reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      icon.classList.toggle('fa-eye', reveal);
      icon.classList.toggle('fa-eye-slash', !reveal);
      button.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
   };

   window.showLoginTransition = function (mode) {
      var copy = {
         register: ['Creating your account...', 'Please wait while we set up your dashboard'],
         'google-register': ['Signing up with Google...', 'Opening secure Google registration'],
         google: ['Connecting to Google...', 'Opening secure Google sign-in'],
         'google-login': ['Connecting to Google...', 'Opening secure Google sign-in'],
         login: ['Authenticating...', 'Please wait while we log you in']
      }[mode || 'login'];

      if (transitionTitle) transitionTitle.textContent = copy[0];
      if (transitionCopy) transitionCopy.textContent = copy[1];
      if (transitionOverlay) {
         transitionOverlay.classList.add('active');
         transitionOverlay.setAttribute('aria-hidden', 'false');
      }
   };

   function queueFocusedFieldScroll() {
      if (focusedFieldFrame) return;

      focusedFieldFrame = window.requestAnimationFrame(function () {
         focusedFieldFrame = 0;
         var activeField = document.activeElement;
         if (!activeField || !authPage.contains(activeField) || !activeField.matches(textInputSelector)) return;

         var scrollingBody = activeField.closest('.auth-modal-body');
         var scrollTarget = activeField.closest('.auth-field') || activeField;
         if (!scrollingBody || scrollingBody.scrollHeight <= scrollingBody.clientHeight) return;

         var bodyRect = scrollingBody.getBoundingClientRect();
         var targetRect = scrollTarget.getBoundingClientRect();
         var topEdge = bodyRect.top + 12;
         var bottomEdge = bodyRect.bottom - 20;
         var scrollDelta = 0;

         if (targetRect.top < topEdge) {
            scrollDelta = targetRect.top - topEdge;
         } else if (targetRect.bottom > bottomEdge) {
            scrollDelta = targetRect.bottom - bottomEdge;
         }

         if (Math.abs(scrollDelta) > 1) scrollingBody.scrollTop += scrollDelta;
      });
   }

   authPage.addEventListener('focusin', function (event) {
      if (!event.target.matches(textInputSelector)) return;
      document.body.classList.add('auth-modal-field-focused');
      queueFocusedFieldScroll();
   });

   authPage.addEventListener('focusout', function () {
      window.setTimeout(function () {
         var activeField = document.activeElement;
         var stillFocused = activeField && authPage.contains(activeField) && activeField.matches(textInputSelector);
         if (!stillFocused) document.body.classList.remove('auth-modal-field-focused');
      }, 0);
   });

   window.addEventListener('resize', queueFocusedFieldScroll, { passive: true });
   if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', queueFocusedFieldScroll, { passive: true });
   }

   var loginForm = document.getElementById('loginForm');
   if (loginForm) {
      loginForm.addEventListener('submit', function () {
         if (loginForm.checkValidity()) window.showLoginTransition('login');
      });
   }

   var signupForm = document.getElementById('signupForm');
   if (signupForm) {
      signupForm.addEventListener('submit', function () {
         if (signupForm.checkValidity()) window.showLoginTransition('register');
      });
   }

   var googleAuthLinks = Array.prototype.slice.call(authPage.querySelectorAll('a[data-auth-transition^="google"]'));
   googleAuthLinks.forEach(function (link) {
      link.addEventListener('click', function (event) {
         if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

         event.preventDefault();
         window.showLoginTransition(link.dataset.authTransition || 'google-login');
         link.setAttribute('aria-disabled', 'true');
         link.style.pointerEvents = 'none';

         var googleMark = link.querySelector('.google-auth-mark');
         if (googleMark) {
            googleMark.hidden = true;
            var spinner = document.createElement('i');
            spinner.className = 'fa-solid fa-spinner fa-spin google-auth-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            link.insertBefore(spinner, googleMark);
         }

         var destination = link.href;
         if (window.SpeakReadyGoogleMobileLayout && typeof window.SpeakReadyGoogleMobileLayout.apply === 'function') {
            destination = window.SpeakReadyGoogleMobileLayout.apply(link);
         }
         window.location.href = destination;
      });
   });

   window.addEventListener('pageshow', function () {
      document.body.classList.remove('auth-modal-field-focused');
      if (transitionOverlay) {
         transitionOverlay.classList.remove('active');
         transitionOverlay.setAttribute('aria-hidden', 'true');
      }
      googleAuthLinks.forEach(function (link) {
         link.style.pointerEvents = '';
         link.removeAttribute('aria-disabled');
         var spinner = link.querySelector('.google-auth-spinner');
         if (spinner) spinner.remove();
         var googleMark = link.querySelector('.google-auth-mark');
         if (googleMark) googleMark.hidden = false;
      });
   });
})();
