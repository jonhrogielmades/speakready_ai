<div id="logoutTransitionOverlay" class="sr-generic-loader" role="status" aria-live="polite" aria-atomic="true" aria-hidden="true">
    <div class="sr-generic-loader-card">
        <div class="sr-generic-loader-mark" aria-hidden="true">
            <img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt="">
        </div>
        <h4 class="sr-generic-loader-title">Signing you out...</h4>
        <p class="sr-generic-loader-copy">Ending your session securely.</p>
    </div>
</div>

<script>
    (function () {
        function showLogoutTransition(form) {
            var overlay = document.getElementById('logoutTransitionOverlay');
            if (!overlay || form?.dataset?.srLogoutTransitionShown === 'true') return;
            if (form) form.dataset.srLogoutTransitionShown = 'true';
            overlay.classList.add('active', 'is-active');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('sr-generic-loader-active');
        }

        function bindLogoutForms() {
            document.querySelectorAll('form[action="{{ route('logout') }}"]').forEach(function (form) {
                if (form.dataset.srLogoutTransitionBound === 'true') return;
                form.dataset.srLogoutTransitionBound = 'true';
                form.addEventListener('submit', function () { showLogoutTransition(form); });
            });
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindLogoutForms, { once: true });
        else bindLogoutForms();
    }());
</script>
