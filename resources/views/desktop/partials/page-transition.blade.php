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
        background: transparent;
        border: 0;
        isolation: isolate;
        overflow: visible;
        box-shadow: none;
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
        width: 90px;
        height: 90px;
        object-fit: contain;
        border-radius: 20px;
        filter: drop-shadow(0 12px 18px rgba(37, 99, 235, 0.2));
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

    .sr-page-reload {
        margin-top: 14px;
        border: 1px solid var(--bd, #e2e8f0);
        background: var(--bg2, #f8fafc);
        color: var(--tx, #0f172a);
        border-radius: 10px;
        padding: 8px 12px;
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
    }

    .sr-page-reload[hidden] {
        display: none !important;
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
            width: 78px;
            height: 78px;
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
    <button type="button" id="pageTransitionReload" class="sr-page-reload" hidden>Reload page</button>
</div>

<script>
    (function() {
        var overlayId = 'pageTransitionOverlay';
        var activeClass = 'sr-page-transition-active';
        var showTimer = null;
        var longLoadTimer = null;
        var defaultDelayMs = 1100;
        var formDelayMs = 900;
        var longLoadMs = 12000;

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
            window.clearTimeout(longLoadTimer);
            showTimer = window.setTimeout(function() {
                if (options && options.event && options.event.defaultPrevented) return;

                var overlay = getOverlay();
                if (!overlay) return;

                options = options || {};
                setText(options.title || 'Opening page...', options.copy || 'Please wait while SpeakReady AI loads.');
                var reloadButton = document.getElementById('pageTransitionReload');
                if (reloadButton) reloadButton.hidden = true;
                overlay.classList.add('active');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add(activeClass);
                longLoadTimer = window.setTimeout(function() {
                    var activeOverlay = getOverlay();
                    if (!activeOverlay || !activeOverlay.classList.contains('active')) return;

                    setText('Still working...', 'This is taking longer than usual. You can keep waiting or reload the page.');
                    var delayedReloadButton = document.getElementById('pageTransitionReload');
                    if (delayedReloadButton) delayedReloadButton.hidden = false;
                }, Math.max(1000, Number(options.longLoadMs) || longLoadMs));
            }, Math.max(0, Number(options && options.delayMs) || defaultDelayMs));
        }

        function hidePageTransition() {
            window.clearTimeout(showTimer);
            window.clearTimeout(longLoadTimer);
            var overlay = getOverlay();
            if (overlay) {
                overlay.classList.remove('active');
                overlay.setAttribute('aria-hidden', 'true');
            }
            var reloadButton = document.getElementById('pageTransitionReload');
            if (reloadButton) reloadButton.hidden = true;
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
            return /\.(?:7z|csv|docx?|gif|jpe?g|json|m4a|mp3|mp4|pdf|png|svg|wav|webm|webp|xlsx?|zip)$/i.test(pathname)
                || /\/(?:download|export|storage)\b/i.test(pathname)
                || /^\/interview\/answers\/[^/]+\/voice-recording$/i.test(pathname)
                || /^\/game\/answers\/[^/]+\/voice-recording$/i.test(pathname)
                || /^\/game\/certificates\/[^/]+\/download$/i.test(pathname);
        }

        function isUserShellPartialNavigationCandidate(link) {
            if (!link || document.body?.dataset?.appSurface !== 'user') return false;
            if (!document.querySelector('[data-user-ajax-content]')) return false;
            if (link.dataset.fullReload === 'true' || link.dataset.noAjax === 'true') return false;

            var target = (link.getAttribute('target') || '').toLowerCase();
            if (target && target !== '_self') return false;

            var href = link.getAttribute('href') || '';
            if (!href || href === '#' || href.charAt(0) === '#') return false;
            if (/^(?:javascript:|mailto:|tel:|sms:)/i.test(href)) return false;

            var url;
            try {
                url = new URL(link.href, window.location.href);
            } catch (error) {
                return false;
            }

            if (url.origin !== window.location.origin) return false;
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return false;
            if (isFileOrDownloadPath(url.pathname)) return false;

            var reloadPrefixes = ['/logout', '/login', '/register', '/auth/', '/shared/', '/admin'];
            if (reloadPrefixes.some(function(prefix) { return url.pathname === prefix || url.pathname.startsWith(prefix); })) return false;

            var userPrefixes = [
                '/dashboard',
                '/interview/setup',
                '/interview/session',
                '/interview/',
                '/account',
                '/notifications',
                '/feedback',
                '/coach',
                '/learning',
                '/skills',
                '/missions',
                '/drills/voice',
                '/personal-mastery',
                '/modules',
                '/game/match',
                '/progress',
                '/practice-plan',
                '/practice-activity-calendar',
                '/session/',
                '/reports'
            ];

            return userPrefixes.some(function(prefix) {
                return url.pathname === prefix || url.pathname.startsWith(prefix);
            });
        }

        function isEligibleLink(link, event) {
            if (!link || event.defaultPrevented || event.button !== 0 || hasModifierKey(event)) return false;
            if (link.dataset.srNoTransition === 'true' || link.dataset.srTransition === 'off') return false;
            if (link.hasAttribute('download') || isBootstrapToggle(link)) return false;
            if (isUserShellPartialNavigationCandidate(link)) return false;

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
            if (url.pathname === '/auth/google' ||
                url.pathname === '/auth/google/login' ||
                url.pathname === '/auth/google/register' ||
                url.pathname === '/auth/google/callback') return false;
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

        var reloadButton = document.getElementById('pageTransitionReload');
        if (reloadButton) {
            reloadButton.addEventListener('click', function() {
                window.location.reload();
            });
        }

        document.addEventListener('click', function(event) {
            var rawTarget = event.target;
            var target = rawTarget && rawTarget.nodeType === 1 ? rawTarget : (rawTarget ? rawTarget.parentElement : null);
            var link = target && target.closest ? target.closest('a[href]') : null;
            if (!isEligibleLink(link, event)) return;

            showPageTransition({
                title: 'Opening page...',
                copy: 'Please wait while SpeakReady AI loads.',
                event: event,
                delayMs: defaultDelayMs
            });
        });

        document.addEventListener('submit', function(event) {
            var form = event.target;
            window.setTimeout(function() {
                if (event.defaultPrevented || !isEligibleForm(form)) return;

                showPageTransition({
                    title: 'Processing...',
                    copy: 'Please wait while SpeakReady AI saves your request.',
                    delayMs: formDelayMs
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
