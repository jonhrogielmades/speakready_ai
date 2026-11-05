import assert from 'node:assert/strict';
import test from 'node:test';

await import('../../public/js/pwa-install.js');

const pwa = globalThis.SpeakReadyPwaInstall;

const userAgents = Object.freeze({
    ipadDesktopSafari: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
    iphoneSafari: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
    iphoneChrome: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/130.0.6723.90 Mobile/15E148 Safari/604.1',
    androidPhoneChrome: 'Mozilla/5.0 (Linux; Android 14; Pixel 8 Pro Build/UQ1A.240205.004) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36',
    androidTabletChrome: 'Mozilla/5.0 (Linux; Android 14; SM-X710 Build/UP1A.231005.007) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
    samsungInternet: 'Mozilla/5.0 (Linux; Android 14; SM-S928B Build/UP1A.231005.007) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/27.0 Chrome/125.0.0.0 Mobile Safari/537.36',
    windowsChrome: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
    macSafari: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15',
});

function createEventTarget(properties = {}) {
    const listeners = new Map();

    const target = {
        ...properties,
        addEventListener(type, listener, options = {}) {
            const entries = listeners.get(type) || [];
            entries.push({ listener, once: options === true || options?.once === true });
            listeners.set(type, entries);
        },
        removeEventListener(type, listener) {
            const entries = listeners.get(type) || [];
            listeners.set(type, entries.filter(entry => entry.listener !== listener));
        },
        listenerCount(type) {
            return (listeners.get(type) || []).length;
        },
        async emit(type, event = {}) {
            const entries = [...(listeners.get(type) || [])];
            const emittedEvent = event;
            emittedEvent.type ||= type;
            emittedEvent.target ||= target;

            const results = [];
            for (const entry of entries) {
                results.push(entry.listener.call(target, emittedEvent));
                if (entry.once) target.removeEventListener(type, entry.listener);
            }

            await Promise.all(results);
            return emittedEvent;
        },
        dispatchEvent(event) {
            void target.emit(event.type, event);
            return true;
        },
    };

    return target;
}

function createElement(properties = {}) {
    const attributes = new Map();
    const styleValues = new Map();
    const element = createEventTarget({
        hidden: false,
        disabled: false,
        dataset: {},
        isConnected: true,
        textContent: '',
        focusCount: 0,
        ...properties,
    });

    element.setAttribute = (name, value) => attributes.set(name, String(value));
    element.getAttribute = name => attributes.get(name) ?? null;
    element.focus = () => { element.focusCount += 1; };
    element.style = {
        setProperty: (name, value) => styleValues.set(name, value),
        removeProperty: name => styleValues.delete(name),
        getPropertyValue: name => styleValues.get(name) || '',
    };

    return element;
}

function createStorage(initial = {}) {
    const values = new Map(Object.entries(initial));

    return {
        getItem: key => values.has(key) ? values.get(key) : null,
        setItem: (key, value) => values.set(key, String(value)),
        removeItem: key => values.delete(key),
        has: key => values.has(key),
    };
}

function createHarness({
    navigator = {},
    standalone = false,
    secure = true,
    includeDialog = false,
    storage = {},
    bodyDataset = {},
} = {}) {
    const timers = [];
    const action = createElement();
    const dialogRoot = createElement({ hidden: true, dataset: {} });
    const dialog = createElement();
    const title = createElement();
    const copy = createElement();
    const install = createElement({ hidden: true });
    const dismiss = createElement();
    const storageRef = createStorage(storage);
    const serviceWorkerCalls = [];
    const registration = { scope: '/' };
    const navigatorRef = {
        userAgent: userAgents.windowsChrome,
        platform: 'Win32',
        maxTouchPoints: 0,
        ...navigator,
    };

    if (!Object.hasOwn(navigatorRef, 'serviceWorker')) {
        navigatorRef.serviceWorker = {
            register: async (...args) => {
                serviceWorkerCalls.push(args);
                return registration;
            },
        };
    }

    const documentRef = createEventTarget({
        readyState: 'complete',
        body: { dataset: bodyDataset },
        activeElement: null,
    });
    documentRef.querySelectorAll = selector => (
        selector === '#heroInstallBtn, [data-pwa-install-action]' ? [action] : []
    );
    documentRef.getElementById = id => {
        if (!includeDialog) return null;

        return {
            'pwa-install-prompt': dialogRoot,
            pwaPromptTitle: title,
            pwaPromptCopy: copy,
            'pwa-btn-yes': install,
            'pwa-btn-no': dismiss,
        }[id] || null;
    };
    documentRef.querySelector = selector => {
        if (includeDialog && selector === '#pwa-install-prompt .pwa-install-dialog') return dialog;
        return null;
    };

    class CustomEvent {
        constructor(type, options = {}) {
            this.type = type;
            this.detail = options.detail;
        }
    }

    const windowRef = createEventTarget({
        navigator: navigatorRef,
        document: documentRef,
        localStorage: storageRef,
        isSecureContext: secure,
        location: {
            protocol: secure ? 'https:' : 'http:',
            hostname: secure ? 'speakready.test' : 'example.test',
        },
        CustomEvent,
        matchMedia: query => ({
            matches: query === '(display-mode: standalone)' && standalone,
        }),
        requestAnimationFrame: callback => {
            callback();
            return 1;
        },
        setTimeout: (callback, delay) => {
            const timer = { callback, delay, id: timers.length + 1 };
            timers.push(timer);
            return timer.id;
        },
        clearTimeout: () => {},
    });

    return {
        action,
        copy,
        dialogRoot,
        dismiss,
        documentRef,
        install,
        navigatorRef,
        registration,
        serviceWorkerCalls,
        storageRef,
        timers,
        title,
        windowRef,
    };
}

test('detects iPadOS when Safari uses a desktop Macintosh user agent', () => {
    const platform = pwa.detectPlatform({
        userAgent: userAgents.ipadDesktopSafari,
        platform: 'MacIntel',
        maxTouchPoints: 5,
    });

    assert.equal(platform.family, 'ios');
    assert.equal(platform.device, 'ipad');
    assert.equal(platform.browser, 'safari');
    assert.equal(platform.isIos, true);
});

test('distinguishes Safari and Chrome on iOS while keeping both in the iOS family', () => {
    const safari = pwa.detectPlatform({
        userAgent: userAgents.iphoneSafari,
        platform: 'iPhone',
        maxTouchPoints: 5,
    });
    const chrome = pwa.detectPlatform({
        userAgent: userAgents.iphoneChrome,
        platform: 'iPhone',
        maxTouchPoints: 5,
    });

    assert.deepEqual(
        { family: safari.family, device: safari.device, browser: safari.browser },
        { family: 'ios', device: 'iphone', browser: 'safari' },
    );
    assert.deepEqual(
        { family: chrome.family, device: chrome.device, browser: chrome.browser },
        { family: 'ios', device: 'iphone', browser: 'chromium' },
    );
});

test('detects Android phones, tablets without a Mobile token, and Samsung Internet', () => {
    const phone = pwa.detectPlatform({
        userAgent: userAgents.androidPhoneChrome,
        platform: 'Linux armv8l',
        maxTouchPoints: 5,
    });
    const tablet = pwa.detectPlatform({
        userAgent: userAgents.androidTabletChrome,
        platform: 'Linux armv8l',
        maxTouchPoints: 5,
    });
    const samsung = pwa.detectPlatform({
        userAgent: userAgents.samsungInternet,
        platform: 'Linux armv8l',
        maxTouchPoints: 5,
    });

    assert.deepEqual(
        { family: phone.family, device: phone.device, browser: phone.browser },
        { family: 'android', device: 'phone', browser: 'chromium' },
    );
    assert.deepEqual(
        { family: tablet.family, device: tablet.device, browser: tablet.browser },
        { family: 'android', device: 'tablet', browser: 'chromium' },
    );
    assert.deepEqual(
        { family: samsung.family, device: samsung.device, browser: samsung.browser, isSamsung: samsung.isSamsung },
        { family: 'android', device: 'phone', browser: 'samsung', isSamsung: true },
    );
});

test('does not mistake a touch-enabled Windows computer for iPadOS', () => {
    const platform = pwa.detectPlatform({
        userAgent: userAgents.windowsChrome,
        platform: 'Win32',
        maxTouchPoints: 10,
    });

    assert.equal(platform.family, 'windows');
    assert.equal(platform.device, 'desktop');
    assert.equal(platform.isIos, false);
    assert.equal(platform.browser, 'chromium');
});

test('detects macOS Safari separately from iPadOS desktop mode', () => {
    const platform = pwa.detectPlatform({
        userAgent: userAgents.macSafari,
        platform: 'MacIntel',
        maxTouchPoints: 0,
    });

    assert.equal(platform.family, 'macos');
    assert.equal(platform.device, 'desktop');
    assert.equal(platform.browser, 'safari');
    assert.equal(platform.isIos, false);
});

test('provides actionable manual fallback copy for each installation family', () => {
    const ios = pwa.fallbackCopy(pwa.detectPlatform({
        userAgent: userAgents.ipadDesktopSafari,
        platform: 'MacIntel',
        maxTouchPoints: 5,
    }));
    assert.equal(ios.kind, 'manual');
    assert.match(ios.message, /Share/i);
    assert.match(ios.message, /Add to Home Screen/i);
    assert.match(ios.message, /Open as Web App/i);
    assert.match(ios.message, /Safari/i);
    assert.doesNotMatch(ios.message, /install unavailable/i);

    const android = pwa.fallbackCopy(pwa.detectPlatform({
        userAgent: userAgents.androidPhoneChrome,
        platform: 'Linux armv8l',
        maxTouchPoints: 5,
    }));
    assert.equal(android.kind, 'manual');
    assert.match(android.message, /browser menu/i);
    assert.match(android.message, /Install app/i);
    assert.match(android.message, /Add to phone|Add to Home screen/i);

    const samsung = pwa.fallbackCopy(pwa.detectPlatform({
        userAgent: userAgents.samsungInternet,
        platform: 'Linux armv8l',
        maxTouchPoints: 5,
    }));
    assert.equal(samsung.kind, 'manual');
    assert.match(samsung.message, /Install icon/i);
    assert.match(samsung.message, /Samsung Internet/i);
    assert.match(samsung.message, /Home screen/i);

    const mac = pwa.fallbackCopy(pwa.detectPlatform({
        userAgent: userAgents.macSafari,
        platform: 'MacIntel',
        maxTouchPoints: 0,
    }));
    assert.equal(mac.kind, 'manual');
    assert.match(mac.message, /Safari/i);
    assert.match(mac.message, /Add to Dock/i);

    const insecure = pwa.fallbackCopy({ family: 'other', isChromium: false }, { secureContext: false });
    assert.equal(insecure.kind, 'unavailable');
    assert.match(insecure.message, /HTTPS/i);
});

test('detects standalone mode through the display-mode query or legacy iOS flag', () => {
    assert.equal(pwa.isStandalone({
        matchMedia: query => ({ matches: query === '(display-mode: standalone)' }),
    }, {}), true);
    assert.equal(pwa.isStandalone({
        matchMedia: () => ({ matches: false }),
    }, { standalone: true }), true);
    assert.equal(pwa.isStandalone({
        matchMedia: () => ({ matches: false }),
    }, { standalone: false }), false);
    assert.equal(pwa.isStandalone({
        matchMedia: () => { throw new Error('media queries unavailable'); },
    }, {}), false);

    for (const displayMode of ['minimal-ui', 'fullscreen', 'window-controls-overlay']) {
        assert.equal(pwa.isStandalone({
            matchMedia: query => ({ matches: query === `(display-mode: ${displayMode})` }),
        }, {}), true, `${displayMode} should count as an installed display mode`);
    }
});

test('initializes event listeners and service-worker registration only once', async () => {
    const harness = createHarness();
    const controller = pwa.createController({
        windowRef: harness.windowRef,
        navigatorRef: harness.navigatorRef,
        documentRef: harness.documentRef,
        storageRef: harness.storageRef,
    });

    assert.equal(controller.init(), controller);
    assert.equal(controller.init(), controller);
    const firstRegistration = controller.registerServiceWorker();
    const secondRegistration = controller.registerServiceWorker();

    assert.equal(firstRegistration, secondRegistration);
    assert.equal(await firstRegistration, harness.registration);
    assert.equal(harness.windowRef.listenerCount('beforeinstallprompt'), 1);
    assert.equal(harness.windowRef.listenerCount('appinstalled'), 1);
    assert.equal(harness.windowRef.listenerCount('pageshow'), 1);
    assert.equal(harness.documentRef.listenerCount('visibilitychange'), 1);
    assert.equal(harness.action.listenerCount('click'), 1);
    assert.deepEqual(harness.serviceWorkerCalls, [[
        '/sw.js?v=47',
        { scope: '/' },
    ]]);
    assert.equal(harness.windowRef.SpeakReadyServiceWorkerRegistration, harness.registration);
});

test('captures, consumes, and clears the native install prompt lifecycle', async () => {
    const harness = createHarness({
        storage: { pwa_app_installed: 'true' },
    });
    const controller = pwa.createController({
        windowRef: harness.windowRef,
        navigatorRef: harness.navigatorRef,
        documentRef: harness.documentRef,
        storageRef: harness.storageRef,
    }).init();
    let prevented = 0;
    let prompted = 0;
    const nativePrompt = {
        preventDefault: () => { prevented += 1; },
        prompt: async () => { prompted += 1; },
        userChoice: Promise.resolve({ outcome: 'accepted' }),
    };

    await harness.windowRef.emit('beforeinstallprompt', nativePrompt);
    assert.equal(prevented, 1);
    assert.equal(controller.getState().available, true);
    assert.equal(harness.storageRef.has('pwa_app_installed'), false);

    const result = await controller.requestInstall();
    assert.deepEqual(result, { kind: 'native', outcome: 'accepted' });
    assert.equal(prompted, 1);
    assert.equal(controller.getState().available, false);

    const repeat = await controller.requestInstall();
    assert.equal(repeat.kind, 'installed');
    assert.equal(prompted, 1, 'a BeforeInstallPromptEvent can only be consumed once');
    assert.equal(controller.getState().installed, true);
    assert.equal(harness.action.hidden, true);
    assert.equal(harness.action.disabled, true);
    assert.equal(harness.storageRef.has('speakready.pwa.install.installedAt'), true);

    await harness.windowRef.emit('appinstalled', {});
    assert.equal(controller.getState().installed, true);
    assert.equal(harness.action.hidden, true);

    const installedResult = await controller.requestInstall();
    assert.equal(installedResult.kind, 'installed');
    assert.equal(prompted, 1);
});

test('an explicit install action shows iPad manual instructions when no native prompt exists', async () => {
    const harness = createHarness({
        navigator: {
            userAgent: userAgents.ipadDesktopSafari,
            platform: 'MacIntel',
            maxTouchPoints: 5,
            serviceWorker: null,
        },
        includeDialog: true,
    });
    const controller = pwa.createController({
        windowRef: harness.windowRef,
        navigatorRef: harness.navigatorRef,
        documentRef: harness.documentRef,
        storageRef: harness.storageRef,
    }).init();

    await harness.action.emit('click', {});

    assert.equal(controller.getState().available, false);
    assert.equal(harness.dialogRoot.hidden, false);
    assert.equal(harness.dialogRoot.dataset.mode, 'manual');
    assert.match(harness.title.textContent, /Install on iPad/i);
    assert.match(harness.copy.textContent, /Share/i);
    assert.match(harness.copy.textContent, /Add to Home Screen/i);
    assert.match(harness.copy.textContent, /Open as Web App/i);
    assert.match(harness.copy.textContent, /Safari/i);
    assert.equal(harness.install.hidden, true, 'manual instructions must not expose a non-working native prompt button');
    assert.equal(harness.dismiss.textContent, 'Got it');
});

test('only auto-offers installation on explicitly opted-in page shells', async () => {
    const createController = harness => pwa.createController({
        windowRef: harness.windowRef,
        navigatorRef: harness.navigatorRef,
        documentRef: harness.documentRef,
        storageRef: harness.storageRef,
    }).init();

    const promptEvent = () => ({
        preventDefault() {},
        prompt: async () => {},
        userChoice: Promise.resolve({ outcome: 'dismissed' }),
    });

    const quietHarness = createHarness({ includeDialog: true });
    createController(quietHarness);
    await quietHarness.windowRef.emit('beforeinstallprompt', promptEvent());
    assert.equal(quietHarness.timers.length, 0);
    assert.equal(quietHarness.dialogRoot.hidden, true);

    const optedInHarness = createHarness({
        includeDialog: true,
        bodyDataset: { pwaAutoInstall: 'true' },
    });
    createController(optedInHarness);
    await optedInHarness.windowRef.emit('beforeinstallprompt', promptEvent());
    assert.equal(optedInHarness.timers.length, 1);
    optedInHarness.timers[0].callback();
    assert.equal(optedInHarness.dialogRoot.hidden, false);
    assert.equal(optedInHarness.dialogRoot.dataset.mode, 'native');
});
