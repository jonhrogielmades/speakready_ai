<style>
    #pageTransitionOverlay {
        position: fixed;
        inset: 0;
        z-index: 999998;
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: max(24px, env(safe-area-inset-top, 0px)) 20px max(24px, env(safe-area-inset-bottom, 0px));
        background: var(--bg, #ffffff);
        color: var(--tx, #0f172a);
        text-align: center;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.18s ease, visibility 0.18s ease;
    }

    #pageTransitionOverlay.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    body.sr-page-transition-active {
        cursor: progress;
        overflow: hidden !important;
        touch-action: none;
    }

    .sr-page-loading-wrapper {
        position: relative;
        width: 112px;
        height: 112px;
        flex: 0 0 112px;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 28px;
        background: linear-gradient(180deg, #ffffff, #eff6ff);
        border: 1px solid rgba(96, 165, 250, 0.26);
        isolation: isolate;
        overflow: hidden;
        box-shadow:
            0 0 0 4px rgba(255, 255, 255, 0.62),
            0 18px 36px rgba(37, 99, 235, 0.16);
    }

    .sr-page-loading-ring {
        position: absolute;
        inset: 0;
        border-radius: 28px;
        border: 4px solid var(--bd, #e2e8f0);
        border-top-color: var(--pur, #7c3aed);
        border-right-color: rgba(14, 165, 233, 0.78);
        animation: srPageTransitionSpin 0.95s linear infinite;
    }

    .sr-page-loading-wrapper img {
        width: 74px;
        height: 74px;
        object-fit: contain;
        border-radius: 20px;
        filter: drop-shadow(0 0 1px rgba(255, 255, 255, 0.9));
        animation: srPageTransitionPulse 1.45s ease-in-out infinite;
    }

    #pageTransitionOverlay h4 {
        margin: 0;
        color: var(--tx, #0f172a);
        font-weight: 700;
        font-size: 1.05rem;
        line-height: 1.25;
        letter-spacing: 0;
        max-width: min(100%, 360px);
        overflow-wrap: anywhere;
    }

    #pageTransitionOverlay p {
        margin: 8px 0 0;
        color: var(--tx3, #64748b);
        font-size: 0.86rem;
        line-height: 1.45;
        max-width: min(100%, 360px);
        overflow-wrap: anywhere;
    }

    @media (max-width: 575px) {
        .sr-page-loading-wrapper {
            width: 98px;
            height: 98px;
            flex-basis: 98px;
            border-radius: 25px;
            margin-bottom: 16px;
        }

        .sr-page-loading-ring {
            border-width: 3px;
            border-radius: 25px;
        }

        .sr-page-loading-wrapper img {
            width: 63px;
            height: 63px;
            border-radius: 18px;
        }

        #pageTransitionOverlay h4 {
            font-size: 0.98rem;
        }

        #pageTransitionOverlay p {
            font-size: 0.8rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .sr-page-loading-ring,
        .sr-page-loading-wrapper img {
            animation: none !important;
        }
    }

    @keyframes srPageTransitionSpin {
        to { transform: rotate(360deg); }
    }

    @keyframes srPageTransitionPulse {
        0%, 100% { transform: scale(0.94); opacity: 0.84; }
        50% { transform: scale(1.04); opacity: 1; }
    }
</style>

<div id="pageTransitionOverlay" role="status" aria-live="polite" aria-atomic="true" aria-hidden="true">
    <div class="sr-page-loading-wrapper">
        <div class="sr-page-loading-ring"></div>
        <img src="{{ asset('img/logo.png') }}" alt="Loading page">
    </div>
    <h4 id="pageTransitionTitle">Opening page...</h4>
    <p id="pageTransitionCopy">Please wait while SpeakReady AI loads.</p>
</div>

<script>
    (function() {
        var overlayId = 'pageTransitionOverlay';
        var activeClass = 'sr-page-transition-active';
        var showTimer = null;

        function getOverlay() {
            return document.getElementById(overlayId);
        }

        function setText(title, copy) {
            var titleEl = document.getElementById('pageTransitionTitle');
            var copyEl = document.getElementById('pageTransitionCopy');
            if (titleEl && title) titleEl.textContent = title;
            if (copyEl && copy) copyEl.textContent = copy;
        }

        function showPageTransition(options) {
            window.clearTimeout(showTimer);
            showTimer = window.setTimeout(function() {
                if (options && options.event && options.event.defaultPrevented) return;

                var overlay = getOverlay();
                if (!overlay) return;

                options = options || {};
                setText(options.title || 'Opening page...', options.copy || 'Please wait while SpeakReady AI loads.');
                overlay.classList.add('active');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add(activeClass);
            }, 50);
        }

        function hidePageTransition() {
            window.clearTimeout(showTimer);
            var overlay = getOverlay();
            if (overlay) {
                overlay.classList.remove('active');
                overlay.setAttribute('aria-hidden', 'true');
            }
            document.body.classList.remove(activeClass);
        }

        function hasModifierKey(event) {
            return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
        }

        function isBootstrapToggle(link) {
            return Boolean(
                link.closest('[data-bs-toggle]') ||
                link.closest('[data-bs-dismiss]') ||
                link.closest('[data-bs-target]') ||
                link.classList.contains('dropdown-toggle')
            );
        }

        function isFileOrDownloadPath(pathname) {
            return /\.(?:7z|csv|docx?|gif|jpe?g|json|pdf|png|svg|webp|xlsx?|zip)$/i.test(pathname)
                || /\/(?:download|export|storage)\b/i.test(pathname);
        }

        function isEligibleLink(link, event) {
            if (!link || event.defaultPrevented || event.button !== 0 || hasModifierKey(event)) return false;
            if (link.dataset.srNoTransition === 'true' || link.dataset.srTransition === 'off') return false;
            if (link.hasAttribute('download') || isBootstrapToggle(link)) return false;

            var target = (link.getAttribute('target') || '').toLowerCase();
            if (target && target !== '_self') return false;

            var href = link.getAttribute('href');
            if (!href || href === '#' || href.charAt(0) === '#') return false;
            if (/^(?:javascript:|mailto:|tel:|sms:)/i.test(href)) return false;

            var url;
            try {
                url = new URL(link.href, window.location.href);
            } catch (error) {
                return false;
            }

            if (url.origin !== window.location.origin) return false;
            if (url.pathname === window.location.pathname && url.search === window.location.search) return false;
            if (url.pathname === '/auth/google' || url.pathname === '/auth/google/callback') return false;
            if (isFileOrDownloadPath(url.pathname)) return false;

            return true;
        }

        function isEligibleForm(form) {
            if (!form || form.dataset.srNoTransition === 'true' || form.dataset.srTransition === 'off') return false;
            if (form.dataset.srAjax === 'true' || form.hasAttribute('data-ajax')) return false;

            var target = (form.getAttribute('target') || '').toLowerCase();
            if (target && target !== '_self') return false;

            var method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method === 'dialog') return false;

            var action;
            try {
                action = new URL(form.action || window.location.href, window.location.href);
            } catch (error) {
                return false;
            }

            if (action.origin !== window.location.origin) return false;
            if (action.pathname === '/logout') return false;
            if (isFileOrDownloadPath(action.pathname)) return false;
            if (typeof form.checkValidity === 'function' && !form.checkValidity()) return false;

            return true;
        }

        document.addEventListener('click', function(event) {
            var rawTarget = event.target;
            var target = rawTarget && rawTarget.nodeType === 1 ? rawTarget : (rawTarget ? rawTarget.parentElement : null);
            var link = target && target.closest ? target.closest('a[href]') : null;
            if (!isEligibleLink(link, event)) return;

            showPageTransition({
                title: 'Opening page...',
                copy: 'Please wait while SpeakReady AI loads.',
                event: event
            });
        });

        document.addEventListener('submit', function(event) {
            var form = event.target;
            window.setTimeout(function() {
                if (event.defaultPrevented || !isEligibleForm(form)) return;

                showPageTransition({
                    title: 'Processing...',
                    copy: 'Please wait while SpeakReady AI saves your request.'
                });
            }, 0);
        });

        window.addEventListener('pageshow', hidePageTransition);
        window.addEventListener('pagehide', function() {
            window.clearTimeout(showTimer);
        });

        window.SpeakReadyPageTransition = {
            show: showPageTransition,
            hide: hidePageTransition
        };
    })();
</script>
