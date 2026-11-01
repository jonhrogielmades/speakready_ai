<div id="pageTransitionOverlay" class="sr-generic-loader" role="status" aria-live="polite" aria-atomic="true" aria-hidden="true">
    <div class="sr-generic-loader-card">
        <div class="sr-generic-loader-mark" aria-hidden="true">
            <img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt="">
        </div>
        <h4 id="pageTransitionTitle" class="sr-generic-loader-title">Opening page...</h4>
        <p id="pageTransitionCopy" class="sr-generic-loader-copy">Please wait while SpeakReady AI loads.</p>
        <button type="button" id="pageTransitionReload" class="sr-generic-loader-reload" hidden>Reload page</button>
    </div>
</div>

<script>
    (function () {
        var overlayId = 'pageTransitionOverlay';
        var activeClass = 'sr-page-transition-active';
        var showTimer = null;
        var longLoadTimer = null;
        var defaultDelayMs = 0;
        var formDelayMs = 0;
        var longLoadMs = 8000;

        function getOverlay() {
            return document.getElementById(overlayId);
        }

        function cleanLabel(value) {
            return String(value || '').replace(/\s+/g, ' ').trim().replace(/[.…]+$/, '');
        }

        function titleCase(value) {
            return cleanLabel(value).replace(/[-_]+/g, ' ').replace(/\b\w/g, function (character) {
                return character.toUpperCase();
            });
        }

        function setText(title, copy) {
            var titleEl = document.getElementById('pageTransitionTitle');
            var copyEl = document.getElementById('pageTransitionCopy');
            if (titleEl && title) titleEl.textContent = title;
            if (copyEl && copy) copyEl.textContent = copy;
        }

        function explicitCopy(source) {
            if (!source) return null;

            var title = source.dataset.srLoadingTitle || source.dataset.loadingTitle;
            var copy = source.dataset.srLoadingCopy || source.dataset.loadingCopy;
            if (!title && !copy) return null;

            return {
                title: title || 'Working...',
                copy: copy || 'Please wait while SpeakReady AI completes this request.'
            };
        }

        function linkCopy(link) {
            var explicit = explicitCopy(link);
            if (explicit) return explicit;

            var label = cleanLabel(link.getAttribute('aria-label') || link.title || link.textContent);
            var href = link.getAttribute('href') || '';
            if (!label || label.length > 72) {
                try {
                    var url = new URL(href, window.location.href);
                    label = titleCase(url.pathname.split('/').filter(Boolean).pop() || 'page');
                } catch (error) {
                    label = 'page';
                }
            }

            return {
                title: 'Opening ' + label + '...',
                copy: 'Please wait while we load ' + label.toLowerCase() + '.'
            };
        }

        function formCopy(form) {
            var explicit = explicitCopy(form);
            if (explicit) return explicit;

            var action = (form.getAttribute('action') || '').toLowerCase();
            var method = (form.getAttribute('method') || 'get').toLowerCase();
            var label = cleanLabel(form.dataset.loadingLabel || form.querySelector('button[type="submit"]')?.textContent);
            var verb = 'Processing';
            var copy = 'Please wait while SpeakReady AI completes your request.';

            if (/logout|signout|sign-out/.test(action)) {
                return { title: 'Signing you out...', copy: 'Ending your session securely.' };
            }
            if (/delete|destroy|remove|clear/.test(action) || form.dataset.loadingTone === 'danger') {
                verb = 'Deleting';
                copy = 'Removing the selected item securely.';
            } else if (/update|edit|save|settings|account|profile/.test(action)) {
                verb = 'Saving';
                copy = 'Saving your changes securely.';
            } else if (/create|add|store|register|signup/.test(action)) {
                verb = 'Creating';
                copy = 'Adding this information to your workspace.';
            } else if (/login|signin|sign-in/.test(action)) {
                verb = 'Signing you in';
                copy = 'Opening your secure workspace.';
            } else if (method === 'get') {
                verb = 'Loading';
                copy = 'Preparing the next view for you.';
            }

            if (label && label.length < 44 && !/^(submit|continue|save|delete|confirm)$/i.test(label)) {
                copy = copy.replace(/\.$/, '') + ' ' + label + '.';
            }

            return { title: verb + '...', copy: copy };
        }

        function showPageTransition(options) {
            window.clearTimeout(showTimer);
            window.clearTimeout(longLoadTimer);

            var activate = function () {
                if (options && options.event && options.event.defaultPrevented) return;

                var overlay = getOverlay();
                if (!overlay) return;

                options = options || {};
                setText(options.title || 'Opening page...', options.copy || 'Please wait while SpeakReady AI loads.');
                var reloadButton = document.getElementById('pageTransitionReload');
                if (reloadButton) reloadButton.hidden = true;
                overlay.classList.add('active');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add(activeClass, 'sr-generic-loader-active');
                longLoadTimer = window.setTimeout(function () {
                    var activeOverlay = getOverlay();
                    if (!activeOverlay || !activeOverlay.classList.contains('active')) return;

                    setText('Still working...', 'This is taking longer than usual. You can keep waiting or reload the page.');
                    var delayedReloadButton = document.getElementById('pageTransitionReload');
                    if (delayedReloadButton) delayedReloadButton.hidden = false;
                }, Math.max(1000, Number(options.longLoadMs) || longLoadMs));
            };

            var delayMs = Math.max(0, Number(options && options.delayMs) || defaultDelayMs);
            if (delayMs === 0) activate();
            else showTimer = window.setTimeout(activate, delayMs);
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
            document.body.classList.remove(activeClass, 'sr-generic-loader-active');
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

        function isPrepFlow(element) {
            return Boolean(element && (
                element.matches?.('.finish-transition-overlay, .interview-prep-overlay, [data-related-prep-loader]') ||
                element.closest?.('.finish-transition-overlay, .interview-prep-overlay, [data-related-prep-loader]') ||
                element.matches?.('[data-sr-related-loader-form], [data-sr-related-loader-link]')
            ));
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
            try { url = new URL(link.href, window.location.href); } catch (error) { return false; }
            if (url.origin !== window.location.origin) return false;
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return false;
            if (isFileOrDownloadPath(url.pathname)) return false;

            var reloadPrefixes = ['/logout', '/login', '/register', '/auth/', '/admin'];
            if (reloadPrefixes.some(function (prefix) { return url.pathname === prefix || url.pathname.startsWith(prefix); })) return false;

            var userPrefixes = ['/dashboard', '/interview/setup', '/interview/session', '/interview/', '/account', '/notifications', '/feedback', '/coach', '/learning', '/skills', '/missions', '/modules', '/game/match', '/progress', '/practice-activity-calendar', '/session/', '/reports'];
            return userPrefixes.some(function (prefix) { return url.pathname === prefix || url.pathname.startsWith(prefix); });
        }

        function isEligibleLink(link, event) {
            if (!link || event.defaultPrevented || event.button !== 0 || hasModifierKey(event)) return false;
            if (isPrepFlow(link)) return false;
            if (link.dataset.srNoTransition === 'true' || link.dataset.srTransition === 'off') return false;
            if (link.hasAttribute('download') || isBootstrapToggle(link)) return false;
            if (isUserShellPartialNavigationCandidate(link)) return false;

            var target = (link.getAttribute('target') || '').toLowerCase();
            if (target && target !== '_self') return false;
            var href = link.getAttribute('href');
            if (!href || href === '#' || href.charAt(0) === '#') return false;
            if (/^(?:javascript:|mailto:|tel:|sms:)/i.test(href)) return false;

            var url;
            try { url = new URL(link.href, window.location.href); } catch (error) { return false; }
            if (url.origin !== window.location.origin) return false;
            if (url.pathname === window.location.pathname && url.search === window.location.search) return false;
            if (url.pathname === '/auth/google' || url.pathname === '/auth/google/login' || url.pathname === '/auth/google/register' || url.pathname === '/auth/google/callback') return false;
            return !isFileOrDownloadPath(url.pathname);
        }

        function isEligibleForm(form) {
            if (!form || isPrepFlow(form)) return false;
            if (form.dataset.srNoTransition === 'true' || form.dataset.srTransition === 'off') return false;
            if (form.dataset.srAjax === 'true' || form.hasAttribute('data-ajax')) return false;
            var target = (form.getAttribute('target') || '').toLowerCase();
            if (target && target !== '_self') return false;
            var method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method === 'dialog') return false;

            var action;
            try { action = new URL(form.getAttribute('action') || window.location.href, window.location.href); } catch (error) { return false; }
            if (action.origin !== window.location.origin || action.pathname === '/logout' || isFileOrDownloadPath(action.pathname)) return false;
            return !(typeof form.checkValidity === 'function' && !form.checkValidity());
        }

        var reloadButton = document.getElementById('pageTransitionReload');
        if (reloadButton) reloadButton.addEventListener('click', function () { window.location.reload(); });

        document.addEventListener('click', function (event) {
            var rawTarget = event.target;
            var target = rawTarget && rawTarget.nodeType === 1 ? rawTarget : (rawTarget ? rawTarget.parentElement : null);
            var link = target && target.closest ? target.closest('a[href]') : null;
            if (!isEligibleLink(link, event)) return;
            var copy = linkCopy(link);
            showPageTransition({ title: copy.title, copy: copy.copy, event: event, delayMs: defaultDelayMs });
        });

        document.addEventListener('submit', function (event) {
            var form = event.target;
            window.setTimeout(function () {
                if (event.defaultPrevented || !isEligibleForm(form)) return;
                var copy = formCopy(form);
                showPageTransition({ title: copy.title, copy: copy.copy, delayMs: formDelayMs });
            }, 0);
        });

        window.addEventListener('pageshow', hidePageTransition);
        window.addEventListener('pagehide', function () { window.clearTimeout(showTimer); });

        window.SpeakReadyPageTransition = { show: showPageTransition, hide: hidePageTransition };
    }());
</script>
