(function () {
    'use strict';

    var userApp = window.SpeakReadyUserApp || {};
    if (!window.SpeakReadyUserApp) {
        window.SpeakReadyUserApp = userApp;
    }

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function getFocusableElements(container) {
        if (!container) return [];

        return Array.from(container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )).filter(function (element) {
            return !element.hidden && element.getAttribute('aria-hidden') !== 'true' && element.getClientRects().length > 0;
        });
    }

    function trapFocus(event, container) {
        if (event.key !== 'Tab') return;

        var focusable = getFocusableElements(container);
        if (!focusable.length) {
            event.preventDefault();
            container.focus({ preventScroll: true });
            return;
        }

        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus({ preventScroll: true });
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus({ preventScroll: true });
        }
    }

    function setupMovableLauncher(launcher) {
        if (!launcher) return null;

        var mobileViewport = window.matchMedia('(max-width: 991.98px)');
        var storageKey = launcher.dataset.ucpStorageKey || 'speakready.ucp-launcher-position.v1';
        var pointerId = null;
        var startPointerX = 0;
        var startPointerY = 0;
        var startLeft = 0;
        var startTop = 0;
        var moved = false;
        var suppressNextClick = false;
        var resizeFrame = 0;
        var positionStatus = document.getElementById('ucpMobileLauncherStatus');

        function clamp(value, minimum, maximum) {
            return Math.min(Math.max(value, minimum), maximum);
        }

        function isVisible(element) {
            return Boolean(element && element.getClientRects().length && window.getComputedStyle(element).display !== 'none');
        }

        function getSafeBounds() {
            var visualViewport = window.visualViewport;
            var viewportLeft = visualViewport ? visualViewport.offsetLeft : 0;
            var viewportTop = visualViewport ? visualViewport.offsetTop : 0;
            var viewportWidth = visualViewport ? visualViewport.width : window.innerWidth;
            var viewportHeight = visualViewport ? visualViewport.height : window.innerHeight;
            var launcherRect = launcher.getBoundingClientRect();
            var launcherWidth = launcherRect.width || launcher.offsetWidth || 42;
            var launcherHeight = launcherRect.height || launcher.offsetHeight || 42;
            var launcherStyles = window.getComputedStyle(launcher);
            var safeInsetTop = parseFloat(launcherStyles.getPropertyValue('--ucp-safe-top')) || 0;
            var safeInsetRight = parseFloat(launcherStyles.getPropertyValue('--ucp-safe-right')) || 0;
            var safeInsetBottom = parseFloat(launcherStyles.getPropertyValue('--ucp-safe-bottom')) || 0;
            var safeInsetLeft = parseFloat(launcherStyles.getPropertyValue('--ucp-safe-left')) || 0;
            var edgeGap = 10;
            var minimumLeft = viewportLeft + safeInsetLeft + edgeGap;
            var maximumLeft = viewportLeft + viewportWidth - launcherWidth - safeInsetRight - edgeGap;
            var minimumTop = viewportTop + safeInsetTop + edgeGap;
            var maximumTop = viewportTop + viewportHeight - launcherHeight - safeInsetBottom - edgeGap;
            var mobileHeader = document.getElementById('mob-header') || document.getElementById('nbar');
            var mobileNavigation = document.getElementById('mob-bottom-nav');

            if (isVisible(mobileHeader)) {
                minimumTop = Math.max(minimumTop, mobileHeader.getBoundingClientRect().bottom + edgeGap);
            }

            if (isVisible(mobileNavigation)) {
                maximumTop = Math.min(maximumTop, mobileNavigation.getBoundingClientRect().top - launcherHeight - edgeGap);
            }

            maximumLeft = Math.max(minimumLeft, maximumLeft);
            maximumTop = Math.max(minimumTop, maximumTop);

            return {
                minX: minimumLeft,
                maxX: maximumLeft,
                minY: minimumTop,
                maxY: maximumTop
            };
        }

        function positionLauncher(left, top, restoring) {
            launcher.classList.add('is-positioned');
            launcher.classList.toggle('is-restoring', Boolean(restoring));
            launcher.style.right = 'auto';
            launcher.style.bottom = 'auto';
            launcher.style.left = Math.round(left) + 'px';
            launcher.style.top = Math.round(top) + 'px';

            if (restoring) {
                window.requestAnimationFrame(function () {
                    launcher.classList.remove('is-restoring');
                });
            }
        }

        function readSavedPosition() {
            try {
                var saved = JSON.parse(window.localStorage.getItem(storageKey) || 'null');
                if (!saved || ['left', 'right', 'top', 'bottom'].indexOf(saved.edge) === -1) return null;
                saved.offset = clamp(Number(saved.offset) || 0, 0, 1);
                return saved;
            } catch (error) {
                return null;
            }
        }

        function savePosition(edge, offset) {
            try {
                window.localStorage.setItem(storageKey, JSON.stringify({
                    edge: edge,
                    offset: clamp(offset, 0, 1)
                }));
            } catch (error) {
                // The launcher remains movable when storage is unavailable.
            }
        }

        function restorePosition() {
            if (!mobileViewport.matches || pointerId !== null) return;

            var saved = readSavedPosition();
            if (!saved) {
                if (!launcher.classList.contains('is-positioned')) return;

                var currentBounds = getSafeBounds();
                var currentRect = launcher.getBoundingClientRect();
                positionLauncher(
                    clamp(currentRect.left, currentBounds.minX, currentBounds.maxX),
                    clamp(currentRect.top, currentBounds.minY, currentBounds.maxY),
                    true
                );
                return;
            }

            var bounds = getSafeBounds();
            var horizontalRange = bounds.maxX - bounds.minX;
            var verticalRange = bounds.maxY - bounds.minY;
            var left = bounds.minX;
            var top = bounds.minY;

            if (saved.edge === 'left' || saved.edge === 'right') {
                left = saved.edge === 'left' ? bounds.minX : bounds.maxX;
                top = bounds.minY + (verticalRange * saved.offset);
            } else {
                left = bounds.minX + (horizontalRange * saved.offset);
                top = saved.edge === 'top' ? bounds.minY : bounds.maxY;
            }

            positionLauncher(left, top, true);
        }

        function announcePosition(edge) {
            if (!positionStatus) return;

            positionStatus.textContent = '';
            window.requestAnimationFrame(function () {
                positionStatus.textContent = 'Quick navigation button moved to the ' + edge + ' edge.';
            });
        }

        function moveToEdge(edge) {
            var bounds = getSafeBounds();
            var currentRect = launcher.getBoundingClientRect();
            var safeLeft = clamp(currentRect.left, bounds.minX, bounds.maxX);
            var safeTop = clamp(currentRect.top, bounds.minY, bounds.maxY);
            var horizontalRange = Math.max(1, bounds.maxX - bounds.minX);
            var verticalRange = Math.max(1, bounds.maxY - bounds.minY);
            var offset;

            if (edge === 'left' || edge === 'right') {
                safeLeft = edge === 'left' ? bounds.minX : bounds.maxX;
                offset = (safeTop - bounds.minY) / verticalRange;
            } else {
                safeTop = edge === 'top' ? bounds.minY : bounds.maxY;
                offset = (safeLeft - bounds.minX) / horizontalRange;
            }

            positionLauncher(safeLeft, safeTop, false);
            savePosition(edge, offset);
            announcePosition(edge);
        }

        function snapToNearestEdge(left, top) {
            var bounds = getSafeBounds();
            var safeLeft = clamp(left, bounds.minX, bounds.maxX);
            var safeTop = clamp(top, bounds.minY, bounds.maxY);
            var distances = [
                { edge: 'left', distance: Math.abs(safeLeft - bounds.minX) },
                { edge: 'right', distance: Math.abs(bounds.maxX - safeLeft) },
                { edge: 'top', distance: Math.abs(safeTop - bounds.minY) },
                { edge: 'bottom', distance: Math.abs(bounds.maxY - safeTop) }
            ];
            var nearest = distances.sort(function (first, second) {
                return first.distance - second.distance;
            })[0].edge;
            var horizontalRange = Math.max(1, bounds.maxX - bounds.minX);
            var verticalRange = Math.max(1, bounds.maxY - bounds.minY);
            var offset;

            if (nearest === 'left' || nearest === 'right') {
                safeLeft = nearest === 'left' ? bounds.minX : bounds.maxX;
                offset = (safeTop - bounds.minY) / verticalRange;
            } else {
                safeTop = nearest === 'top' ? bounds.minY : bounds.maxY;
                offset = (safeLeft - bounds.minX) / horizontalRange;
            }

            positionLauncher(safeLeft, safeTop, false);
            savePosition(nearest, offset);
            announcePosition(nearest);
        }

        function finishDrag(event, cancelled) {
            if (pointerId === null || event.pointerId !== pointerId) return;

            if (launcher.hasPointerCapture && launcher.hasPointerCapture(pointerId)) {
                launcher.releasePointerCapture(pointerId);
            }

            launcher.classList.remove('is-dragging');

            if (moved) {
                event.preventDefault();
                snapToNearestEdge(parseFloat(launcher.style.left) || startLeft, parseFloat(launcher.style.top) || startTop);

                if (!cancelled) {
                    suppressNextClick = true;
                    window.setTimeout(function () { suppressNextClick = false; }, 450);
                }
            }

            pointerId = null;
            moved = false;
        }

        if (typeof window.PointerEvent !== 'undefined') {
            launcher.addEventListener('pointerdown', function (event) {
                if (!mobileViewport.matches || event.button !== 0 || pointerId !== null) return;

                var rect = launcher.getBoundingClientRect();
                suppressNextClick = false;
                pointerId = event.pointerId;
                startPointerX = event.clientX;
                startPointerY = event.clientY;
                startLeft = rect.left;
                startTop = rect.top;
                moved = false;

                if (launcher.setPointerCapture) launcher.setPointerCapture(pointerId);
            });

            launcher.addEventListener('pointermove', function (event) {
                if (pointerId === null || event.pointerId !== pointerId) return;

                var deltaX = event.clientX - startPointerX;
                var deltaY = event.clientY - startPointerY;

                if (!moved && Math.hypot(deltaX, deltaY) < 6) return;

                moved = true;
                event.preventDefault();
                launcher.classList.add('is-dragging');

                var bounds = getSafeBounds();
                positionLauncher(
                    clamp(startLeft + deltaX, bounds.minX, bounds.maxX),
                    clamp(startTop + deltaY, bounds.minY, bounds.maxY),
                    false
                );
            });

            launcher.addEventListener('pointerup', function (event) { finishDrag(event, false); });
            launcher.addEventListener('pointercancel', function (event) { finishDrag(event, true); });
        }
        launcher.addEventListener('dragstart', function (event) { event.preventDefault(); });
        launcher.addEventListener('keydown', function (event) {
            if (!mobileViewport.matches || !event.shiftKey) return;

            var edgeByKey = {
                ArrowLeft: 'left',
                ArrowRight: 'right',
                ArrowUp: 'top',
                ArrowDown: 'bottom'
            };
            var requestedEdge = edgeByKey[event.key];

            if (!requestedEdge) return;

            event.preventDefault();
            moveToEdge(requestedEdge);
        });

        function scheduleRestore() {
            if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
            resizeFrame = window.requestAnimationFrame(restorePosition);
        }

        window.addEventListener('resize', scheduleRestore, { passive: true });
        window.addEventListener('orientationchange', scheduleRestore, { passive: true });
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', scheduleRestore, { passive: true });
            window.visualViewport.addEventListener('scroll', scheduleRestore, { passive: true });
        }

        restorePosition();

        return {
            consumeClick: function () {
                if (!suppressNextClick) return false;
                suppressNextClick = false;
                return true;
            }
        };
    }

    onReady(function () {
        var palette = document.getElementById('userCommandPalette');
        if (!palette || palette.dataset.ucpInitialized === 'true') return;

        palette.dataset.ucpInitialized = 'true';

        var dialog = palette.querySelector('.ucp-dialog');
        var results = Array.from(palette.querySelectorAll('[data-ucp-item]'));
        var openTriggers = Array.from(document.querySelectorAll('[data-ucp-open]'));
        var lastFocusedElement = null;
        var previousBodyOverflow = '';
        var activeItem = null;
        var paletteOpen = false;
        var mobileLauncher = document.querySelector('.ucp-mobile-launcher');
        var movableLauncher = setupMovableLauncher(mobileLauncher);

        function setTriggerExpanded(expanded) {
            openTriggers.forEach(function (trigger) {
                trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        }

        function setActive(item, shouldScroll, shouldFocus) {
            results.forEach(function (result) {
                var selected = result === item;
                result.classList.toggle('is-active', selected);
            });

            activeItem = item || null;

            if (activeItem && shouldScroll) {
                activeItem.scrollIntoView({ block: 'nearest' });
            }

            if (activeItem && shouldFocus) {
                activeItem.focus({ preventScroll: true });
            }
        }

        function openPalette(trigger) {
            if (paletteOpen) {
                (activeItem || results[0] || dialog).focus({ preventScroll: true });
                return;
            }

            lastFocusedElement = trigger instanceof HTMLElement ? trigger : document.activeElement;
            previousBodyOverflow = document.body.style.overflow;
            paletteOpen = true;
            palette.hidden = false;
            palette.setAttribute('aria-hidden', 'false');
            setTriggerExpanded(true);
            document.body.style.overflow = 'hidden';
            setActive(results[0] || null, false, false);

            window.requestAnimationFrame(function () {
                (activeItem || dialog).focus({ preventScroll: true });
            });
        }

        function closePalette(options) {
            if (!paletteOpen) return;

            var restoreFocus = !options || options.restoreFocus !== false;
            paletteOpen = false;
            palette.hidden = true;
            palette.setAttribute('aria-hidden', 'true');
            setTriggerExpanded(false);
            document.body.style.overflow = previousBodyOverflow;
            setActive(null, false);

            if (restoreFocus && lastFocusedElement && lastFocusedElement.isConnected && typeof lastFocusedElement.focus === 'function') {
                window.requestAnimationFrame(function () {
                    lastFocusedElement.focus({ preventScroll: true });
                });
            }
        }

        function moveActive(direction) {
            if (!results.length) return;

            var index = results.indexOf(activeItem);
            if (index === -1) index = direction > 0 ? -1 : 0;
            index = (index + direction + results.length) % results.length;
            setActive(results[index], true, true);
        }

        window.openUserCommandPalette = function (trigger) {
            openPalette(trigger);
        };

        window.closeUserCommandPalette = function () {
            closePalette();
        };

        setTriggerExpanded(false);

        openTriggers.forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                if (trigger === mobileLauncher && movableLauncher && movableLauncher.consumeClick()) {
                    event.preventDefault();
                    return;
                }

                openPalette(trigger);
            });
        });

        palette.querySelectorAll('[data-ucp-close]').forEach(function (button) {
            button.addEventListener('click', function () { closePalette(); });
        });

        palette.addEventListener('mousedown', function (event) {
            if (event.target === palette) closePalette();
        });

        results.forEach(function (item) {
            item.addEventListener('mousemove', function () { setActive(item, false); });
            item.addEventListener('focus', function () { setActive(item, false); });
            item.addEventListener('click', function () {
                var href = item.getAttribute('href') || '';
                var destination = href.startsWith('#') && href.length > 1
                    ? document.getElementById(href.slice(1))
                    : null;

                closePalette({ restoreFocus: false });

                if (!destination) return;

                var hadTabIndex = destination.hasAttribute('tabindex');
                if (!hadTabIndex) destination.setAttribute('tabindex', '-1');

                window.requestAnimationFrame(function () {
                    destination.focus({ preventScroll: true });

                    if (!hadTabIndex) {
                        destination.addEventListener('blur', function () {
                            destination.removeAttribute('tabindex');
                        }, { once: true });
                    }
                });
            });
        });

        palette.querySelectorAll('[data-ucp-action]').forEach(function (action) {
            action.addEventListener('click', function () {
                var focusReturn = lastFocusedElement;
                var targetSelector = action.getAttribute('data-bs-target') || '';
                var actionTarget = targetSelector.startsWith('#')
                    ? document.getElementById(targetSelector.slice(1))
                    : null;

                if (actionTarget && focusReturn && focusReturn.isConnected) {
                    var restoreActionFocus = function () {
                        window.requestAnimationFrame(function () {
                            if (focusReturn.isConnected && typeof focusReturn.focus === 'function') {
                                focusReturn.focus({ preventScroll: true });
                            }
                        });
                    };

                    if (action.getAttribute('data-bs-toggle') === 'modal') {
                        actionTarget.addEventListener('hidden.bs.modal', restoreActionFocus, { once: true });
                    } else {
                        actionTarget.addEventListener('hidden.bs.offcanvas', restoreActionFocus, { once: true });
                    }
                }

                closePalette({ restoreFocus: !actionTarget });
            });
        });

        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && !event.altKey && event.key.toLocaleLowerCase() === 'k') {
                event.preventDefault();
                if (paletteOpen) closePalette();
                else openPalette();
                return;
            }

            if (!paletteOpen) return;

            if (event.key === 'Escape') {
                event.preventDefault();
                closePalette();
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                moveActive(1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                moveActive(-1);
                return;
            }

            trapFocus(event, dialog);
        });

        enhanceMobileDrawer(function () { return paletteOpen; });
    });

    onReady(function () {
        setupUserFullscreen();
        setupUserPartialNavigation();
    });

    function fullscreenElement() {
        return document.fullscreenElement || null;
    }

    function setupUserFullscreen() {
        if (userApp.fullscreenInitialized) {
            updateFullscreenButtons();
            return;
        }

        userApp.fullscreenInitialized = true;
        userApp.fullscreenBusy = false;

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-user-fullscreen-toggle], #dbFullscreenBtn, #mobFullscreenBtn');
            if (!button) return;

            event.preventDefault();
            event.stopPropagation();
            toggleUserFullscreen(button);
        });

        document.addEventListener('fullscreenchange', updateFullscreenButtons);
        updateFullscreenButtons();
    }

    async function toggleUserFullscreen(button) {
        if (userApp.fullscreenBusy) return;

        var root = document.documentElement;
        var canEnter = typeof root.requestFullscreen === 'function';
        var canExit = typeof document.exitFullscreen === 'function';

        if (!fullscreenElement() && !canEnter) {
            console.warn('Fullscreen API is not supported in this browser.');
            showSafeNavigationStatus('Fullscreen is not supported in this browser.');
            updateFullscreenButtons();
            return;
        }

        userApp.fullscreenBusy = true;
        button.setAttribute('aria-busy', 'true');

        try {
            if (fullscreenElement()) {
                if (canExit) {
                    await document.exitFullscreen();
                }
                rememberFullscreenPreference(false);
            } else {
                await root.requestFullscreen();
                rememberFullscreenPreference(true);
            }
        } catch (error) {
            console.warn('Fullscreen toggle failed:', error);
            showSafeNavigationStatus('Fullscreen could not be changed. Try again from the button.');
        } finally {
            userApp.fullscreenBusy = false;
            button.removeAttribute('aria-busy');
            updateFullscreenButtons();
        }
    }

    function rememberFullscreenPreference(value) {
        try {
            window.localStorage.setItem('speakready.user.fullscreenPreferred', value ? '1' : '0');
        } catch (error) {
            console.warn('Unable to save fullscreen preference:', error);
        }
    }

    function updateFullscreenButtons() {
        var isFullscreen = Boolean(fullscreenElement());
        document.body.classList.toggle('user-app-fullscreen', isFullscreen);

        document.querySelectorAll('[data-user-fullscreen-toggle], #dbFullscreenBtn, #mobFullscreenBtn').forEach(function (button) {
            button.setAttribute('aria-label', isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen');
            button.setAttribute('title', isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen');
        });

        ['dbFullscreenIcon', 'mobFullscreenIcon'].forEach(function (id) {
            var icon = document.getElementById(id);
            if (!icon) return;
            icon.classList.toggle('fa-expand', !isFullscreen);
            icon.classList.toggle('fa-compress', isFullscreen);
        });
    }

    function setupUserPartialNavigation() {
        var content = document.querySelector('[data-user-ajax-content]');
        if (!content || userApp.navigationInitialized) return;

        userApp.navigationInitialized = true;
        userApp.navigationController = null;
        userApp.navigationToken = 0;

        window.history.replaceState(makeHistoryState(window.location.href), document.title, window.location.href);

        document.addEventListener('click', function (event) {
            var anchor = event.target.closest('a[href]');
            if (!anchor || !shouldHandleLink(anchor, event)) return;

            event.preventDefault();
            closeOpenUserMenus();
            navigateUserApp(anchor.href, { push: true });
        });

        window.addEventListener('popstate', function () {
            navigateUserApp(window.location.href, { push: false });
        });
    }

    function makeHistoryState(url) {
        return {
            speakReadyUserShell: true,
            url: new URL(url, window.location.href).href
        };
    }

    function shouldHandleLink(anchor, event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
        if (anchor.target && anchor.target !== '_self') return false;
        if (anchor.hasAttribute('download')) return false;
        if (anchor.dataset.fullReload === 'true' || anchor.dataset.noAjax === 'true') return false;
        if (anchor.closest('form')) return false;

        var href = anchor.getAttribute('href') || '';
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return false;

        var url;
        try {
            url = new URL(anchor.href, window.location.href);
        } catch (error) {
            return false;
        }

        if (url.origin !== window.location.origin) return false;
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return false;

        var reloadPrefixes = ['/logout', '/login', '/register', '/auth/', '/shared/', '/admin'];
        if (reloadPrefixes.some(function (prefix) { return url.pathname === prefix || url.pathname.startsWith(prefix); })) return false;

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
            '/modules',
            '/game/match',
            '/missions',
            '/drills/voice',
            '/progress',
            '/session/',
            '/reports',
            '/personal-mastery',
            '/community/leaderboard'
        ];

        return userPrefixes.some(function (prefix) {
            return url.pathname === prefix || url.pathname.startsWith(prefix);
        });
    }

    async function navigateUserApp(url, options) {
        var content = document.querySelector('[data-user-ajax-content]');
        if (!content) {
            window.location.assign(url);
            return;
        }

        var token = ++userApp.navigationToken;
        if (userApp.navigationController) {
            userApp.navigationController.abort();
        }

        var controller = new AbortController();
        userApp.navigationController = controller;
        content.setAttribute('aria-busy', 'true');
        showSafeNavigationStatus('Loading page...');

        try {
            var response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                signal: controller.signal,
                headers: {
                    'Accept': 'text/html, application/xhtml+xml',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-SpeakReady-Partial-Navigation': '1'
                }
            });

            if (token !== userApp.navigationToken) return;
            if (!response.ok || response.redirected && new URL(response.url).pathname !== new URL(url, window.location.href).pathname) {
                throw new Error('Navigation response was not usable: ' + response.status);
            }

            var html = await response.text();
            if (token !== userApp.navigationToken) return;

            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var nextContent = doc.querySelector('[data-user-ajax-content]') || doc.querySelector('.db-content');
            if (!nextContent) {
                throw new Error('Fetched page did not include a User Side content container.');
            }

            var contentScripts = Array.from(nextContent.querySelectorAll('script'));
            replaceUserContent(content, nextContent);
            updateDocumentMetadata(doc, nextContent, url, options);
            runPageScripts(html, contentScripts);
            refreshCommonEnhancements();
            window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
            hideSafeNavigationStatus();
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('User Side AJAX navigation failed, falling back to normal navigation:', error);
            showSafeNavigationStatus('This page needs a normal reload. Redirecting...');
            window.location.assign(url);
        } finally {
            if (token === userApp.navigationToken) {
                content.removeAttribute('aria-busy');
                userApp.navigationController = null;
            }
        }
    }

    function replaceUserContent(currentContent, nextContent) {
        currentContent.innerHTML = nextContent.innerHTML;
        Array.from(nextContent.attributes).forEach(function (attribute) {
            if (attribute.name === 'id') return;
            currentContent.setAttribute(attribute.name, attribute.value);
        });
    }

    function updateDocumentMetadata(doc, nextContent, url, options) {
        var nextTitle = (doc.querySelector('title') || {}).textContent || document.title;
        document.title = nextTitle;

        var contextTitle = nextContent.getAttribute('data-page-title') || nextTitle.replace(/\s*-\s*SpeakReady AI.*$/i, '');
        var desktopContext = document.querySelector('.db-page-context strong');
        if (desktopContext) desktopContext.textContent = contextTitle || 'Overview';

        if (options && options.push) {
            window.history.pushState(makeHistoryState(url), nextTitle, url);
        }

        updateActiveUserNavigation(new URL(url, window.location.href));
    }

    function updateActiveUserNavigation(url) {
        document.querySelectorAll('.db-nav a[href], .profile-menu-item[href], .mob-nav-item[href], .mob-profile-link[href], .ucp-result[href]').forEach(function (anchor) {
            var anchorUrl;
            try {
                anchorUrl = new URL(anchor.href, window.location.href);
            } catch (error) {
                return;
            }

            var active = anchorUrl.pathname === url.pathname || (
                anchorUrl.pathname !== '/' && url.pathname.startsWith(anchorUrl.pathname + '/')
            );
            anchor.classList.toggle('active', active);
        });
    }

    function extractPageScriptHtml(html) {
        var start = html.indexOf('<!-- USER_PAGE_SCRIPTS_START -->');
        var end = html.indexOf('<!-- USER_PAGE_SCRIPTS_END -->');
        if (start === -1 || end === -1 || end <= start) return '';
        return html.slice(start, end);
    }

    function runPageScripts(html, contentScripts) {
        document.querySelectorAll('script[data-user-page-script-runtime]').forEach(function (script) {
            script.remove();
        });

        var stackTemplate = document.createElement('template');
        stackTemplate.innerHTML = extractPageScriptHtml(html);
        var scripts = []
            .concat(contentScripts || [])
            .concat(Array.from(stackTemplate.content.querySelectorAll('script')));

        scripts.forEach(function (script) {
            if (!script.src && !script.textContent.trim()) return;

            if (!script.src && (script.type || '').toLowerCase() !== 'module') {
                executeInlinePageScript(script.textContent);
                return;
            }

            var replacement = document.createElement('script');
            Array.from(script.attributes).forEach(function (attribute) {
                replacement.setAttribute(attribute.name, attribute.value);
            });
            replacement.dataset.userPageScriptRuntime = 'true';
            if (!replacement.src) replacement.text = script.textContent;
            document.body.appendChild(replacement);
        });
    }

    function executeInlinePageScript(source) {
        var exportedNames = [];
        var functionPattern = /(?:^|\n)\s*(?:async\s+)?function\s+([A-Za-z_$][\w$]*)\s*\(/g;
        var match;

        while ((match = functionPattern.exec(source)) !== null) {
            if (exportedNames.indexOf(match[1]) === -1) exportedNames.push(match[1]);
        }

        var exportSource = exportedNames.map(function (name) {
            return 'try { if (typeof ' + name + ' === "function") window["' + name + '"] = ' + name + '; } catch (exportError) { console.warn("Unable to export page function ' + name + ':", exportError); }';
        }).join('\n');

        try {
            new Function('"use strict";\n' + source + '\n' + exportSource + '\n//# sourceURL=speakready-user-page-inline.js')();
        } catch (error) {
            console.error('User Side page script failed:', error);
            showSafeNavigationStatus('Some page behavior could not initialize. Reloading...');
            window.setTimeout(function () {
                window.location.reload();
            }, 250);
        }
    }

    function refreshCommonEnhancements() {
        if (window.AOS && typeof window.AOS.refreshHard === 'function') {
            window.AOS.refreshHard();
        } else if (window.AOS && typeof window.AOS.refresh === 'function') {
            window.AOS.refresh();
        }

        if (typeof window.closeUserCommandPalette === 'function') {
            window.closeUserCommandPalette();
        }

        closeOpenUserMenus();
        updateFullscreenButtons();
    }

    function closeOpenUserMenus() {
        if (typeof window.closeDashboardSidebar === 'function') window.closeDashboardSidebar();
        if (typeof window.closeMobileNotif === 'function') window.closeMobileNotif();
        if (typeof window.closeMobileProfile === 'function') window.closeMobileProfile();

        document.getElementById('notifDropdown')?.classList.remove('open');
        document.getElementById('profileDropdown')?.classList.remove('open');
    }

    function showSafeNavigationStatus(message) {
        var status = document.getElementById('userAjaxNavigationStatus');
        if (!status) {
            status = document.createElement('div');
            status.id = 'userAjaxNavigationStatus';
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            status.style.cssText = 'position:fixed;left:50%;bottom:18px;z-index:13000;transform:translateX(-50%);padding:8px 12px;border-radius:10px;background:rgba(15,23,42,.92);color:#fff;font:600 12px Poppins,Arial,sans-serif;box-shadow:0 10px 30px rgba(0,0,0,.25);display:none;';
            document.body.appendChild(status);
        }

        status.textContent = message;
        status.style.display = 'block';
    }

    function hideSafeNavigationStatus() {
        var status = document.getElementById('userAjaxNavigationStatus');
        if (status) status.style.display = 'none';
    }

    function enhanceMobileDrawer(isPaletteOpen) {
        var drawer = document.getElementById('mob-drawer');
        var overlay = document.getElementById('mob-drawer-overlay');
        if (!drawer || drawer.dataset.focusEnhanced === 'true') return;

        drawer.dataset.focusEnhanced = 'true';
        drawer.tabIndex = -1;

        var triggers = Array.from(document.querySelectorAll('#mobnav-more, .mob-avatar'));
        var returnFocus = null;
        var wasOpen = drawer.classList.contains('open');

        triggers.forEach(function (trigger) {
            trigger.setAttribute('aria-haspopup', 'dialog');
            trigger.setAttribute('aria-controls', 'mob-drawer');
            trigger.setAttribute('aria-expanded', wasOpen ? 'true' : 'false');

            if (!/^(BUTTON|A)$/.test(trigger.tagName)) {
                trigger.setAttribute('role', 'button');
                trigger.tabIndex = 0;
                trigger.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        if (typeof window.openMobDrawer === 'function') window.openMobDrawer();
                    }
                });
            }
        });

        function setDrawerState(open, focusDrawer) {
            wasOpen = open;
            drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
            drawer.toggleAttribute('inert', !open);
            if (overlay) overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
            triggers.forEach(function (trigger) {
                trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            if (open && focusDrawer) {
                window.requestAnimationFrame(function () {
                    var focusable = getFocusableElements(drawer);
                    (focusable[0] || drawer).focus({ preventScroll: true });
                });
            }
        }

        var originalOpen = typeof window.openMobDrawer === 'function' ? window.openMobDrawer : null;
        var originalClose = typeof window.closeMobDrawer === 'function' ? window.closeMobDrawer : null;

        window.openMobDrawer = function (options) {
            var shouldFocusDrawer = Boolean(options && options.focusDrawer);
            returnFocus = document.activeElement;

            if (originalOpen) originalOpen.apply(this, arguments);
            else {
                drawer.classList.add('open');
                if (overlay) overlay.classList.add('open');
                document.body.style.overflow = 'hidden';
            }

            setDrawerState(true, shouldFocusDrawer);
        };

        window.closeMobDrawer = function () {
            if (originalClose) originalClose.apply(this, arguments);
            else {
                drawer.classList.remove('open');
                if (overlay) overlay.classList.remove('open');
                document.body.style.overflow = '';
            }

            setDrawerState(false, false);

            if (!isPaletteOpen() && returnFocus && returnFocus.isConnected && typeof returnFocus.focus === 'function') {
                var focusTarget = returnFocus;
                window.requestAnimationFrame(function () {
                    focusTarget.focus({ preventScroll: true });
                });
            }
        };

        setDrawerState(wasOpen, false);

        var observer = new MutationObserver(function () {
            var open = drawer.classList.contains('open');
            if (open !== wasOpen) setDrawerState(open, false);
        });
        observer.observe(drawer, { attributes: true, attributeFilter: ['class'] });

        document.addEventListener('keydown', function (event) {
            if (isPaletteOpen() || !drawer.classList.contains('open')) return;

            if (event.key === 'Escape') {
                event.preventDefault();
                window.closeMobDrawer();
                return;
            }

            trapFocus(event, drawer);
        });
    }
})();
