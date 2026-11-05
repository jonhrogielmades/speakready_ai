(function initializeSpeakReadyPwaInstall(global) {
    'use strict';

    const CONTROLLER_VERSION = '2';
    const SERVICE_WORKER_URL = '/sw.js?v=48';
    const AUTO_PROMPT_COOLDOWN_MS = 7 * 24 * 60 * 60 * 1000;
    const AUTO_PROMPT_DELAY_MS = 900;
    const INSTALLED_MARKER_MAX_AGE_MS = 365 * 24 * 60 * 60 * 1000;
    const STORAGE_KEYS = Object.freeze({
        dismissedAt: 'speakready.pwa.install.dismissedAt',
        autoShownAt: 'speakready.pwa.install.autoShownAt',
        installedAt: 'speakready.pwa.install.installedAt',
        legacyDismissed: 'pwa_prompt_dismissed',
        legacyAutoShown: 'pwa_prompt_auto_shown',
        legacyLastShown: 'pwa_prompt_last_shown_at',
        legacyInstalled: 'pwa_app_installed',
    });

    function detectPlatform(navigatorRef = {}) {
        const userAgent = String(navigatorRef.userAgent || '');
        const platformName = String(navigatorRef.platform || '');
        const maxTouchPoints = Number(navigatorRef.maxTouchPoints || 0);
        const ipadDesktopMode = (platformName === 'MacIntel' || /Macintosh/i.test(userAgent)) && maxTouchPoints > 1;
        const iosMatch = /iPad|iPhone|iPod/i.exec(userAgent);
        const iosToken = iosMatch && iosMatch[0] ? iosMatch[0].toLowerCase() : '';
        const isIos = Boolean(iosToken || ipadDesktopMode);
        const isAndroid = !isIos && /Android/i.test(userAgent);
        const isMac = !isIos && (/Macintosh|Mac OS X/i.test(userAgent) || platformName === 'MacIntel');
        const isWindows = !isIos && /Windows/i.test(userAgent + ' ' + platformName);
        const isSamsung = /SamsungBrowser/i.test(userAgent);
        const isSafari = /Safari/i.test(userAgent) && !/Chrome|Chromium|CriOS|Edg|EdgiOS|OPR|Opera|FxiOS|SamsungBrowser/i.test(userAgent);
        const isChromium = /Chrome|Chromium|CriOS|Edg|EdgiOS|OPR|SamsungBrowser/i.test(userAgent);
        const isWebView = isAndroid && (/;\s*wv\)/i.test(userAgent) || /\bFBAN\b|\bFBAV\b|Instagram/i.test(userAgent));

        let family = 'other';
        let device = 'desktop';

        if (isIos) {
            family = 'ios';
            device = ipadDesktopMode || iosToken === 'ipad' ? 'ipad' : (iosToken === 'ipod' ? 'ipod' : 'iphone');
        } else if (isAndroid) {
            family = 'android';
            device = /Mobile/i.test(userAgent) ? 'phone' : 'tablet';
        } else if (isMac) {
            family = 'macos';
        } else if (isWindows) {
            family = 'windows';
        } else if (/Linux|CrOS/i.test(userAgent + ' ' + platformName)) {
            family = 'linux';
        }

        return Object.freeze({
            family,
            device,
            browser: isSamsung ? 'samsung' : (isSafari ? 'safari' : (isChromium ? 'chromium' : 'other')),
            isIos,
            isAndroid,
            isSafari,
            isSamsung,
            isChromium,
            isWebView,
        });
    }

    function fallbackCopy(platform, options = {}) {
        const secureContext = options.secureContext !== false;

        if (!secureContext) {
            return {
                kind: 'unavailable',
                title: 'Open the secure site to install',
                message: 'App installation requires HTTPS. Open the secure SpeakReady AI site in Safari, Chrome, Edge, or Samsung Internet, then try again.',
            };
        }

        if (platform.family === 'ios') {
            const deviceName = platform.device === 'ipad' ? 'iPad' : (platform.device === 'ipod' ? 'iPod touch' : 'iPhone');
            return {
                kind: 'manual',
                title: `Install on ${deviceName}`,
                message: 'Tap Share, choose Add to Home Screen, turn on Open as Web App if shown, then tap Add. If Add to Home Screen is missing, open this page in Safari.',
            };
        }

        if (platform.family === 'android' && platform.isWebView) {
            return {
                kind: 'manual',
                title: 'Open in a browser to install',
                message: 'This in-app browser cannot install the app. Open its menu, choose Open in browser, then use Chrome or Samsung Internet and select Install app or Add to Home screen.',
            };
        }

        if (platform.family === 'android' && platform.isSamsung) {
            return {
                kind: 'manual',
                title: 'Install on this Android device',
                message: 'Tap the Install icon in Samsung Internet, or open the browser menu and choose Add page to, then Home screen. If the option is missing, update Samsung Internet or open this page in Chrome.',
            };
        }

        if (platform.family === 'android') {
            return {
                kind: 'manual',
                title: 'Install on this Android device',
                message: 'Open the browser menu (⋮), then choose Install app, Add to phone, or Add to Home screen. If no install option appears, open this page in Chrome or Samsung Internet.',
            };
        }

        if (platform.family === 'macos' && platform.isSafari) {
            return {
                kind: 'manual',
                title: 'Add SpeakReady AI to your Mac',
                message: 'In Safari on macOS Sonoma or later, choose File, then Add to Dock, and confirm Add.',
            };
        }

        if (platform.isChromium) {
            return {
                kind: 'manual',
                title: 'Install from your browser',
                message: 'Open the browser menu, then choose Install SpeakReady AI or Apps > Install this site as an app. You may also see an Install icon in the address bar.',
            };
        }

        return {
            kind: 'manual',
            title: 'Add SpeakReady AI to this device',
            message: 'Open your browser menu and look for Install app or Add to Home screen. If that option is unavailable, open this site in a supported browser such as Chrome, Edge, Safari, or Samsung Internet.',
        };
    }

    function isStandalone(windowRef = {}, navigatorRef = {}) {
        const displayModes = ['standalone', 'minimal-ui', 'fullscreen', 'window-controls-overlay'];
        let installedDisplayMode = false;

        try {
            installedDisplayMode = displayModes.some(mode => (
                typeof windowRef.matchMedia === 'function' && windowRef.matchMedia(`(display-mode: ${mode})`).matches
            ));
        } catch (error) {
            installedDisplayMode = false;
        }

        return installedDisplayMode || navigatorRef.standalone === true;
    }

    function createController(options = {}) {
        const windowRef = options.windowRef || global.window;
        const navigatorRef = options.navigatorRef || (windowRef && windowRef.navigator) || global.navigator || {};
        const documentRef = options.documentRef || (windowRef && windowRef.document) || global.document;
        const storageRef = options.storageRef || (() => {
            try {
                return (windowRef && windowRef.localStorage) || null;
            } catch (error) {
                return null;
            }
        })();
        const now = typeof options.now === 'function' ? options.now : () => Date.now();
        const platform = detectPlatform(navigatorRef);
        const state = {
            initialized: false,
            domBound: false,
            deferredPrompt: null,
            installedThisSession: false,
            registrationPromise: null,
            autoPromptTimer: null,
            lastFocusedElement: null,
            isolatedElements: [],
        };

        function storageGet(key) {
            try {
                return storageRef && typeof storageRef.getItem === 'function' ? storageRef.getItem(key) : null;
            } catch (error) {
                return null;
            }
        }

        function storageSet(key, value) {
            try {
                if (storageRef && typeof storageRef.setItem === 'function') storageRef.setItem(key, String(value));
            } catch (error) {
                // Storage can be unavailable in private, embedded, or managed contexts.
            }
        }

        function storageRemove(key) {
            try {
                if (storageRef && typeof storageRef.removeItem === 'function') storageRef.removeItem(key);
            } catch (error) {
                // Storage is only a prompt-frequency hint.
            }
        }

        function secureInstallContext() {
            const locationRef = windowRef && windowRef.location ? windowRef.location : {};
            const hostname = String(locationRef.hostname || '').toLowerCase();
            const localHost = hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '[::1]';
            const protocol = String(locationRef.protocol || '');

            if (windowRef && typeof windowRef.isSecureContext === 'boolean') return windowRef.isSecureContext || localHost;
            return protocol === 'https:' || localHost;
        }

        function runningStandalone() {
            return isStandalone(windowRef, navigatorRef);
        }

        function dialogParts() {
            const getById = documentRef && typeof documentRef.getElementById === 'function'
                ? id => documentRef.getElementById(id)
                : () => null;
            return {
                root: getById('pwa-install-prompt'),
                dialog: documentRef && typeof documentRef.querySelector === 'function'
                    ? documentRef.querySelector('#pwa-install-prompt .pwa-install-dialog')
                    : null,
                title: getById('pwaPromptTitle'),
                copy: getById('pwaPromptCopy'),
                install: getById('pwa-btn-yes'),
                dismiss: getById('pwa-btn-no'),
            };
        }

        function emit(name, detail = {}) {
            if (!windowRef || typeof windowRef.dispatchEvent !== 'function' || typeof windowRef.CustomEvent !== 'function') return;
            windowRef.dispatchEvent(new windowRef.CustomEvent(name, { detail }));
        }

        function installActions() {
            if (!documentRef || typeof documentRef.querySelectorAll !== 'function') return [];
            return Array.from(documentRef.querySelectorAll('#heroInstallBtn, [data-pwa-install-action]'));
        }

        function markInstalled() {
            state.installedThisSession = true;
            storageSet(STORAGE_KEYS.installedAt, now());
            storageRemove(STORAGE_KEYS.legacyInstalled);
        }

        function rememberedInstalled() {
            const installedAt = Number(storageGet(STORAGE_KEYS.installedAt) || 0);
            if (!installedAt) return false;
            if (now() - installedAt <= INSTALLED_MARKER_MAX_AGE_MS) return true;
            storageRemove(STORAGE_KEYS.installedAt);
            return false;
        }

        function updateInstallActions() {
            const installed = runningStandalone() || state.installedThisSession;
            installActions().forEach(action => {
                action.hidden = installed;
                action.disabled = installed;
                if (typeof action.setAttribute === 'function') {
                    action.setAttribute('aria-hidden', installed ? 'true' : 'false');
                    action.setAttribute('aria-label', installed ? 'SpeakReady AI is installed' : 'Install SpeakReady AI');
                }
            });
        }

        function reconcileInstalledState() {
            if (runningStandalone()) {
                markInstalled();
                updateInstallActions();
                return Promise.resolve(true);
            }

            const remembered = rememberedInstalled();
            state.installedThisSession = remembered;
            updateInstallActions();
            if (typeof navigatorRef.getInstalledRelatedApps !== 'function') return Promise.resolve(remembered);

            return Promise.resolve(navigatorRef.getInstalledRelatedApps())
                .then(apps => {
                    const installed = Array.isArray(apps) && apps.length > 0;
                    if (installed) markInstalled();
                    state.installedThisSession = installed || remembered;
                    updateInstallActions();
                    return state.installedThisSession;
                })
                .catch(() => remembered);
        }

        function setAttribute(element, name, value) {
            if (element && typeof element.setAttribute === 'function') element.setAttribute(name, value);
        }

        function isolatePage(root) {
            const body = documentRef && documentRef.body;
            if (!body || !body.children || state.isolatedElements.length) return;

            Array.from(body.children).forEach(element => {
                const containsDialog = element === root || (typeof element.contains === 'function' && element.contains(root));
                if (containsDialog) return;

                const supportsInert = 'inert' in element;
                const hadAriaHidden = typeof element.hasAttribute === 'function' && element.hasAttribute('aria-hidden');
                state.isolatedElements.push({
                    element,
                    supportsInert,
                    previousInert: supportsInert ? element.inert : false,
                    hadAriaHidden,
                    previousAriaHidden: typeof element.getAttribute === 'function' ? element.getAttribute('aria-hidden') : null,
                });
                if (supportsInert) element.inert = true;
                else setAttribute(element, 'aria-hidden', 'true');
            });
        }

        function restorePage() {
            state.isolatedElements.forEach(record => {
                const element = record.element;
                if (!element) return;
                if (record.supportsInert) element.inert = record.previousInert;
                else if (record.hadAriaHidden) setAttribute(element, 'aria-hidden', record.previousAriaHidden);
                else if (typeof element.removeAttribute === 'function') element.removeAttribute('aria-hidden');
            });
            state.isolatedElements = [];
        }

        function showMessage(details, options = {}) {
            const parts = dialogParts();
            if (!parts.root) return details;

            const wasClosed = parts.root.hidden || (typeof parts.root.getAttribute === 'function' && parts.root.getAttribute('aria-hidden') !== 'false');
            if (wasClosed && !state.lastFocusedElement) {
                state.lastFocusedElement = documentRef && documentRef.activeElement ? documentRef.activeElement : null;
            }

            parts.root.hidden = false;
            if (parts.root.style && typeof parts.root.style.setProperty === 'function') parts.root.style.setProperty('display', 'grid');
            setAttribute(parts.root, 'aria-hidden', 'false');
            parts.root.dataset.mode = details.kind || 'manual';
            parts.root.dataset.automatic = options.automatic ? 'true' : 'false';
            if (parts.title) parts.title.textContent = details.title;
            if (parts.copy) parts.copy.textContent = details.message;
            if (parts.install) {
                parts.install.hidden = details.kind !== 'native';
                parts.install.disabled = details.kind !== 'native';
                parts.install.textContent = 'Install';
            }
            if (parts.dismiss) parts.dismiss.textContent = details.kind === 'native' ? 'Not now' : 'Got it';

            const body = documentRef && documentRef.body;
            if (body && body.classList && typeof body.classList.add === 'function') body.classList.add('sr-pwa-dialog-open');
            isolatePage(parts.root);

            const focusTarget = details.kind === 'native' ? parts.install : parts.dismiss;
            const focusDialog = () => {
                const target = focusTarget || parts.dialog;
                if (target && typeof target.focus === 'function') target.focus();
            };
            if (windowRef && typeof windowRef.requestAnimationFrame === 'function') windowRef.requestAnimationFrame(focusDialog);
            else focusDialog();
            emit('speakready:pwa-message', details);
            return details;
        }

        function hideMessage(rememberDismissal = false) {
            const parts = dialogParts();
            if (rememberDismissal) storageSet(STORAGE_KEYS.dismissedAt, now());
            if (parts.root) {
                parts.root.hidden = true;
                if (parts.root.style && typeof parts.root.style.removeProperty === 'function') parts.root.style.removeProperty('display');
                setAttribute(parts.root, 'aria-hidden', 'true');
                parts.root.dataset.automatic = 'false';
            }

            const body = documentRef && documentRef.body;
            if (body && body.classList && typeof body.classList.remove === 'function') body.classList.remove('sr-pwa-dialog-open');
            restorePage();

            if (state.lastFocusedElement && state.lastFocusedElement.isConnected !== false && typeof state.lastFocusedElement.focus === 'function') {
                state.lastFocusedElement.focus();
            }
            state.lastFocusedElement = null;
        }

        function manualInstallCopy() {
            return fallbackCopy(platform, { secureContext: secureInstallContext() });
        }

        function closestMenu(element) {
            if (!element) return null;
            if (typeof element.closest === 'function') return element.closest('.db-dropdown, .mob-profile-dropdown');
            let current = element.parentElement;
            while (current) {
                if (typeof current.matches === 'function' && current.matches('.db-dropdown, .mob-profile-dropdown')) return current;
                current = current.parentElement;
            }
            return null;
        }

        function closeOwningMenu(action) {
            const menu = closestMenu(action);
            if (!menu) return;

            let triggerId = typeof menu.getAttribute === 'function' ? menu.getAttribute('aria-labelledby') : '';
            if (!triggerId && menu.id === 'profileDropdown') triggerId = 'userPill';
            if (!triggerId && menu.id === 'mobProfileDropdown') triggerId = 'mobProfileBtn';
            if (!triggerId && menu.id === 'mobMoreDropdown') triggerId = 'mobMoreBtn';
            const trigger = triggerId && documentRef && typeof documentRef.getElementById === 'function'
                ? documentRef.getElementById(triggerId)
                : null;
            state.lastFocusedElement = trigger || action;

            if (menu.classList && typeof menu.classList.remove === 'function') {
                menu.classList.remove('open');
                menu.classList.remove('show');
            }
            setAttribute(menu, 'aria-hidden', 'true');
            setAttribute(trigger, 'aria-expanded', 'false');

            if (menu.id === 'mobProfileDropdown' && windowRef && typeof windowRef.closeMobileProfile === 'function') {
                windowRef.closeMobileProfile();
            } else if (menu.id === 'profileDropdown' && windowRef && typeof windowRef.closeUserHeaderDropdowns === 'function') {
                windowRef.closeUserHeaderDropdowns();
            }

            const chevron = documentRef && typeof documentRef.getElementById === 'function'
                ? documentRef.getElementById('profileChevron')
                : null;
            if (chevron && chevron.style) chevron.style.transform = 'rotate(0deg)';
        }

        async function requestInstall() {
            if (runningStandalone() || state.installedThisSession) {
                return showMessage({
                    kind: 'installed',
                    title: 'Already installed',
                    message: 'SpeakReady AI is already installed on this device.',
                });
            }
            if (!secureInstallContext()) return showMessage(manualInstallCopy());

            if (state.deferredPrompt) {
                const promptEvent = state.deferredPrompt;
                state.deferredPrompt = null;
                hideMessage(false);

                try {
                    await promptEvent.prompt();
                    const choice = await promptEvent.userChoice;
                    const outcome = choice && choice.outcome ? choice.outcome : 'dismissed';
                    if (outcome === 'accepted') markInstalled();
                    emit('speakready:pwa-install-outcome', { outcome });
                    return { kind: 'native', outcome };
                } catch (error) {
                    const details = manualInstallCopy();
                    showMessage(details);
                    return Object.assign({}, details, { error });
                } finally {
                    updateInstallActions();
                }
            }
            return showMessage(manualInstallCopy());
        }

        function dismissedRecently() {
            let dismissedAt = Number(storageGet(STORAGE_KEYS.dismissedAt) || 0);
            if (!dismissedAt && storageGet(STORAGE_KEYS.legacyDismissed)) {
                dismissedAt = now();
                storageSet(STORAGE_KEYS.dismissedAt, dismissedAt);
                storageRemove(STORAGE_KEYS.legacyDismissed);
            }
            return dismissedAt > 0 && now() - dismissedAt < AUTO_PROMPT_COOLDOWN_MS;
        }

        function autoShownRecently() {
            let shownAt = Number(storageGet(STORAGE_KEYS.autoShownAt) || 0);
            if (!shownAt) shownAt = Number(storageGet(STORAGE_KEYS.legacyLastShown) || 0);
            if (!shownAt && storageGet(STORAGE_KEYS.legacyAutoShown)) shownAt = now();
            if (shownAt) storageSet(STORAGE_KEYS.autoShownAt, shownAt);
            storageRemove(STORAGE_KEYS.legacyAutoShown);
            storageRemove(STORAGE_KEYS.legacyLastShown);
            return shownAt > 0 && now() - shownAt < AUTO_PROMPT_COOLDOWN_MS;
        }

        function autoPromptAllowed() {
            const bodyDataset = documentRef && documentRef.body && documentRef.body.dataset
                ? documentRef.body.dataset
                : {};
            return bodyDataset.pwaAutoInstall === 'true' && bodyDataset.pwaSuppressAutoInstall !== 'true';
        }

        function autoPromptBlocked() {
            if (!autoPromptAllowed()) return true;
            if (!documentRef || typeof documentRef.querySelector !== 'function') return false;
            return Boolean(documentRef.querySelector(
                '.sr-native-tour-overlay, .sr-native-tour-popover, .driver-popover, .modal.show, .dropdown-menu.show, .db-dropdown.open, .mob-profile-dropdown.open, [aria-modal="true"]:not(#pwa-install-prompt)[aria-hidden="false"]'
            ));
        }

        function scheduleAutoOffer(attempt = 0) {
            if (windowRef && typeof windowRef.clearTimeout === 'function') windowRef.clearTimeout(state.autoPromptTimer);
            const parts = dialogParts();
            const dialogAlreadyOpen = parts.root && !parts.root.hidden;
            if (!autoPromptAllowed() || dialogAlreadyOpen || runningStandalone() || state.installedThisSession || dismissedRecently() || autoShownRecently()) return;
            if (!windowRef || typeof windowRef.setTimeout !== 'function') return;

            state.autoPromptTimer = windowRef.setTimeout(() => {
                if (autoPromptBlocked()) {
                    if (attempt < 24) scheduleAutoOffer(attempt + 1);
                    return;
                }

                let details = null;
                if (state.deferredPrompt) {
                    details = {
                        kind: 'native',
                        title: 'Install SpeakReady AI',
                        message: 'Install the app for faster access and a focused, full-screen experience.',
                    };
                } else if (platform.family === 'ios') {
                    details = manualInstallCopy();
                }

                if (!details || !dialogParts().root) return;
                storageSet(STORAGE_KEYS.autoShownAt, now());
                showMessage(details, { automatic: true });
            }, AUTO_PROMPT_DELAY_MS);
        }

        function focusableDialogElements(dialog) {
            if (!dialog || typeof dialog.querySelectorAll !== 'function') return [];
            const selector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
            return Array.from(dialog.querySelectorAll(selector)).filter(element => (
                !element.hidden && element.disabled !== true && (!element.getAttribute || element.getAttribute('aria-hidden') !== 'true')
            ));
        }

        function handleDialogKeydown(event, parts) {
            if (!parts.root || parts.root.hidden) return;
            if (event.key === 'Escape') {
                hideMessage(parts.root.dataset.automatic === 'true');
                return;
            }
            if (event.key !== 'Tab') return;

            const focusable = focusableDialogElements(parts.dialog);
            if (!focusable.length) {
                if (typeof event.preventDefault === 'function') event.preventDefault();
                if (parts.dialog && typeof parts.dialog.focus === 'function') parts.dialog.focus();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            const activeElement = documentRef.activeElement;
            const containsActive = parts.dialog && typeof parts.dialog.contains === 'function' && parts.dialog.contains(activeElement);
            if (event.shiftKey && (activeElement === first || !containsActive)) {
                if (typeof event.preventDefault === 'function') event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && (activeElement === last || !containsActive)) {
                if (typeof event.preventDefault === 'function') event.preventDefault();
                first.focus();
            }
        }

        function bindDom() {
            if (state.domBound) return;
            state.domBound = true;

            installActions().forEach(action => {
                if (action.dataset.srPwaInstallBound === 'true') return;
                action.dataset.srPwaInstallBound = 'true';
                if (typeof action.addEventListener === 'function') {
                    action.addEventListener('click', () => {
                        closeOwningMenu(action);
                        return requestInstall();
                    });
                }
            });

            const parts = dialogParts();
            if (parts.install && typeof parts.install.addEventListener === 'function') parts.install.addEventListener('click', requestInstall);
            if (parts.dismiss && typeof parts.dismiss.addEventListener === 'function') {
                parts.dismiss.addEventListener('click', () => {
                    const rememberDismissal = parts.root && (parts.root.dataset.automatic === 'true' || parts.root.dataset.mode === 'native');
                    hideMessage(rememberDismissal);
                });
            }
            if (parts.root && typeof parts.root.addEventListener === 'function') {
                parts.root.addEventListener('click', event => {
                    if (event.target === parts.root) hideMessage(parts.root.dataset.automatic === 'true');
                });
            }
            if (documentRef && typeof documentRef.addEventListener === 'function') {
                documentRef.addEventListener('keydown', event => handleDialogKeydown(event, parts));
            }

            reconcileInstalledState();
            if (platform.family === 'ios' || state.deferredPrompt) scheduleAutoOffer();
        }

        function registerServiceWorker() {
            if (state.registrationPromise) return state.registrationPromise;
            if (!navigatorRef.serviceWorker || typeof navigatorRef.serviceWorker.register !== 'function') return Promise.resolve(null);

            state.registrationPromise = navigatorRef.serviceWorker.register(SERVICE_WORKER_URL, { scope: '/' })
                .then(registration => {
                    if (registration && windowRef) windowRef.SpeakReadyServiceWorkerRegistration = registration;
                    emit('speakready:pwa-service-worker-ready', { registration });
                    return registration;
                })
                .catch(error => {
                    console.warn('SpeakReady AI service worker registration failed.', error);
                    return null;
                });
            return state.registrationPromise;
        }

        function handleBeforeInstallPrompt(event) {
            if (event && typeof event.preventDefault === 'function') event.preventDefault();
            state.deferredPrompt = event;
            state.installedThisSession = false;
            storageRemove(STORAGE_KEYS.installedAt);
            storageRemove(STORAGE_KEYS.legacyInstalled);
            updateInstallActions();
            emit('speakready:pwa-install-available', { platform });
            scheduleAutoOffer();
        }

        function handleAppInstalled() {
            state.deferredPrompt = null;
            markInstalled();
            hideMessage(false);
            updateInstallActions();
            emit('speakready:pwa-installed', { platform });
        }

        function handleVisibilityChange() {
            if (!documentRef.visibilityState || documentRef.visibilityState === 'visible') reconcileInstalledState();
        }

        function init() {
            if (state.initialized || !windowRef || !documentRef) return controller;
            state.initialized = true;

            if (typeof windowRef.addEventListener === 'function') {
                windowRef.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
                windowRef.addEventListener('appinstalled', handleAppInstalled);
                windowRef.addEventListener('pageshow', reconcileInstalledState);
            }
            if (typeof documentRef.addEventListener === 'function') {
                documentRef.addEventListener('visibilitychange', handleVisibilityChange);
            }

            if (documentRef.readyState === 'loading') documentRef.addEventListener('DOMContentLoaded', bindDom, { once: true });
            else bindDom();
            registerServiceWorker();
            return controller;
        }

        const controller = Object.freeze({
            version: CONTROLLER_VERSION,
            init,
            bindDom,
            requestInstall,
            registerServiceWorker,
            hideMessage,
            showMessage,
            platform,
            isStandalone: runningStandalone,
            getState() {
                return Object.freeze({
                    initialized: state.initialized,
                    available: Boolean(state.deferredPrompt),
                    installed: runningStandalone() || state.installedThisSession,
                });
            },
        });
        return controller;
    }

    const api = Object.freeze({
        version: CONTROLLER_VERSION,
        detectPlatform,
        fallbackCopy,
        isStandalone,
        createController,
    });

    global.SpeakReadyPwaInstall = api;
    if (global.window && global.document) {
        const controller = createController();
        global.SpeakReadyPwa = controller;
        controller.init();
    }
})(typeof globalThis !== 'undefined' ? globalThis : this);
