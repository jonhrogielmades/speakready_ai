import assert from 'node:assert/strict';
import test from 'node:test';

await import('../../public/js/onboarding-voiceover.js');

const voiceoverFactory = globalThis.SpeakReadyOnboardingVoiceover;

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((resolvePromise, rejectPromise) => {
        resolve = resolvePromise;
        reject = rejectPromise;
    });

    return { promise, resolve, reject };
}

function response(body = 'provider-audio', ok = true, type = 'audio/mpeg') {
    return {
        ok,
        status: ok ? 200 : 503,
        blob: async () => new Blob([body], { type }),
    };
}

class MockAudio {
    static instances = [];
    static rejectPlay = false;

    constructor(src = '') {
        this.src = src;
        this.playCalls = 0;
        this.pauseCalls = 0;
        this.loadCalls = 0;
        this.listeners = new Map();
        MockAudio.instances.push(this);
    }

    addEventListener(type, listener) {
        if (!this.listeners.has(type)) this.listeners.set(type, []);
        this.listeners.get(type).push(listener);
    }

    removeEventListener(type, listener) {
        const listeners = this.listeners.get(type) || [];
        this.listeners.set(type, listeners.filter((candidate) => candidate !== listener));
    }

    emit(type) {
        (this.listeners.get(type) || []).slice().forEach((listener) => listener({ type, target: this }));
    }

    play() {
        this.playCalls += 1;
        if (MockAudio.rejectPlay) {
            const error = new Error('Playback requires a user gesture.');
            error.name = 'NotAllowedError';
            return Promise.reject(error);
        }

        queueMicrotask(() => this.emit('playing'));
        return Promise.resolve();
    }

    pause() {
        this.pauseCalls += 1;
    }

    removeAttribute(name) {
        if (name === 'src') this.src = '';
    }

    load() {
        this.loadCalls += 1;
    }
}

function harness(overrides = {}) {
    MockAudio.instances = [];
    MockAudio.rejectPlay = false;

    const fetchCalls = [];
    const fallbackCalls = [];
    const createdUrls = [];
    const revokedUrls = [];
    const fetchImpl = overrides.fetchImpl || (async (url, options) => {
        fetchCalls.push({ url, options });
        return response();
    });

    const runtime = voiceoverFactory.create({
        endpoint: '/onboarding/speech',
        csrfToken: 'csrf-token',
        fetchImpl: async (url, options) => {
            if (overrides.fetchImpl) fetchCalls.push({ url, options });
            return fetchImpl(url, options);
        },
        AudioCtor: MockAudio,
        createObjectURL: (blob) => {
            const url = `blob:tutorial-${createdUrls.length + 1}`;
            createdUrls.push({ url, blob });
            return url;
        },
        revokeObjectURL: (url) => revokedUrls.push(url),
        fallbackSpeak: async (text, config) => {
            fallbackCalls.push({ text, config });
            return true;
        },
        timeoutMs: 25,
        ...overrides.options,
    });

    return { runtime, fetchCalls, fallbackCalls, createdUrls, revokedUrls };
}

test('uses provider audio as the primary tutorial voice without invoking fallback', async () => {
    const context = harness();
    const result = await context.runtime.speak('Welcome to your tutorial.', { locale: 'en-US' });

    assert.equal(result.source, 'provider');
    assert.equal(context.fetchCalls.length, 1);
    assert.equal(context.fetchCalls[0].url, '/onboarding/speech');
    assert.equal(MockAudio.instances.length, 1);
    assert.equal(MockAudio.instances[0].playCalls, 1);
    assert.equal(context.fallbackCalls.length, 0);
});

test('does not start fallback while a provider request is still inside its deadline', async () => {
    const provider = deferred();
    const context = harness({ fetchImpl: () => provider.promise });
    const speaking = context.runtime.speak('Wait for the provider.', { locale: 'en-US' });

    await Promise.resolve();
    assert.equal(context.fallbackCalls.length, 0);

    provider.resolve(response());
    const result = await speaking;
    assert.equal(result.source, 'provider');
    assert.equal(context.fallbackCalls.length, 0);
});

test('falls back exactly once when provider playback is rejected', async () => {
    MockAudio.rejectPlay = true;
    const context = harness();
    MockAudio.rejectPlay = true;

    const result = await context.runtime.speak('Play this once.', { locale: 'en-US' });

    assert.equal(result.source, 'fallback');
    assert.equal(context.fetchCalls.length, 1);
    assert.equal(MockAudio.instances[0].playCalls, 1);
    assert.equal(context.fallbackCalls.length, 1);
    assert.equal(context.fallbackCalls[0].text, 'Play this once.');
});

test('falls back when provider audio never starts playing', async () => {
    class HangingAudio extends MockAudio {
        play() {
            this.playCalls += 1;
            return new Promise(() => {});
        }
    }

    const context = harness({
        options: {
            AudioCtor: HangingAudio,
            playbackStartTimeoutMs: 5,
        },
    });

    const result = await Promise.race([
        context.runtime.speak('Recover from stalled playback.', { locale: 'en-US' }),
        new Promise((_, reject) => setTimeout(() => reject(new Error('playback watchdog did not settle')), 250)),
    ]);

    assert.equal(result.source, 'fallback');
    assert.equal(context.fallbackCalls.length, 1);
});

test('falls back after the provider deadline and always settles', async () => {
    const context = harness({
        fetchImpl: (_url, options) => new Promise((_resolve, reject) => {
            options.signal?.addEventListener('abort', () => {
                const error = new Error('aborted');
                error.name = 'AbortError';
                reject(error);
            }, { once: true });
        }),
        options: { timeoutMs: 5 },
    });

    const result = await Promise.race([
        context.runtime.speak('Do not wait forever.', { locale: 'en-US' }),
        new Promise((_, reject) => setTimeout(() => reject(new Error('voiceover did not settle')), 250)),
    ]);

    assert.equal(result.source, 'fallback');
    assert.equal(context.fallbackCalls.length, 1);
});

test('uses fallback immediately when the provider endpoint is unavailable', async () => {
    const context = harness({ options: { endpoint: '' } });

    const result = await context.runtime.speak('Use the local fallback.', { locale: 'en-US' });

    assert.equal(result.source, 'fallback');
    assert.equal(context.fetchCalls.length, 0);
    assert.equal(context.fallbackCalls.length, 1);
});

test('does not request the endpoint when server-side AI voice is unavailable', async () => {
    const context = harness({ options: { providerAvailable: false } });

    const result = await context.runtime.speak('Use fallback without a pointless request.', { locale: 'en-US' });

    assert.equal(result.source, 'fallback');
    assert.equal(context.fetchCalls.length, 0);
    assert.equal(context.fallbackCalls.length, 1);
});

test('provider opt-out ignores previously cached AI audio', async () => {
    const context = harness();
    await context.runtime.prefetch(['Optional provider audio.'], { locale: 'en-US' });

    const result = await context.runtime.speak('Optional provider audio.', {
        locale: 'en-US',
        providerVoiceover: false,
    });

    assert.equal(result.source, 'fallback');
    assert.equal(context.fetchCalls.length, 1);
    assert.equal(context.fallbackCalls.length, 1);
    assert.equal(MockAudio.instances.length, 0);
});

test('uses fallback and suppresses another provider request for the same failed line during cooldown', async () => {
    const context = harness({ fetchImpl: async () => response('unavailable', false) });

    const result = await context.runtime.speak('Recover from provider failure.', { locale: 'en-US' });
    const cooldownResult = await context.runtime.speak('Recover from provider failure.', { locale: 'en-US' });

    assert.equal(result.source, 'fallback');
    assert.equal(cooldownResult.source, 'fallback');
    assert.equal(context.fetchCalls.length, 1);
    assert.equal(context.fallbackCalls.length, 2);
    assert.equal(MockAudio.instances.length, 0);
});

test('a failed line does not block another line from fetching saved provider audio', async () => {
    let requestCount = 0;
    const context = harness({
        fetchImpl: async () => {
            requestCount += 1;
            return requestCount === 1
                ? response('unavailable', false)
                : response('saved-provider-audio');
        },
    });

    const failedLine = await context.runtime.speak('This line is not saved yet.', { locale: 'en-US' });
    const savedLine = await context.runtime.speak('This line already has saved AI audio.', { locale: 'en-US' });

    assert.equal(failedLine.source, 'fallback');
    assert.equal(savedLine.source, 'provider');
    assert.equal(context.fetchCalls.length, 2);
    assert.equal(context.fallbackCalls.length, 1);
    assert.equal(MockAudio.instances.length, 1);
    assert.equal(MockAudio.instances[0].playCalls, 1);
});

test('rejects non-audio provider responses and falls back exactly once', async () => {
    const context = harness({ fetchImpl: async () => response('{"error":"invalid"}', true, 'application/json') });

    const result = await context.runtime.speak('Reject invalid provider content.', { locale: 'en-US' });

    assert.equal(result.source, 'fallback');
    assert.equal(context.fetchCalls.length, 1);
    assert.equal(context.fallbackCalls.length, 1);
    assert.equal(MockAudio.instances.length, 0);
});

test('deduplicates in-flight prefetches and reuses cached provider audio', async () => {
    const provider = deferred();
    const context = harness({ fetchImpl: () => provider.promise });
    const config = { locale: 'en-US' };

    const firstPrefetch = context.runtime.prefetch(['Cached tutorial line.'], config);
    const secondPrefetch = context.runtime.prefetch(['Cached tutorial line.'], config);
    await Promise.resolve();
    assert.equal(context.fetchCalls.length, 1);

    provider.resolve(response('cached-audio'));
    await Promise.all([firstPrefetch, secondPrefetch]);

    const speaking = context.runtime.speak('Cached tutorial line.', config);
    assert.equal(MockAudio.instances.length, 1, 'cached audio should be created during the user gesture');
    assert.equal(MockAudio.instances[0].playCalls, 1, 'cached audio should call play synchronously');

    const result = await speaking;
    assert.equal(result.source, 'provider');
    assert.equal(context.fetchCalls.length, 1);
});

test('stop aborts a pending request and prevents stale audio or fallback playback', async () => {
    const provider = deferred();
    const context = harness({ fetchImpl: () => provider.promise });
    const speaking = context.runtime.speak('This step is already stale.', { locale: 'en-US' });

    await Promise.resolve();
    context.runtime.stop({ abortPending: true });
    provider.resolve(response('late-audio'));

    const result = await speaking;
    assert.ok(['stale', 'stopped', 'none'].includes(result.source));
    assert.equal(MockAudio.instances.length, 0);
    assert.equal(context.fallbackCalls.length, 0);
});

test('stop pauses active provider audio and revokes its object URL', async () => {
    const context = harness();
    const result = await context.runtime.speak('Stop and clean this audio.', { locale: 'en-US' });

    assert.equal(result.source, 'provider');
    const audio = MockAudio.instances[0];
    const activeUrl = context.createdUrls[0].url;

    context.runtime.stop({ abortPending: true });

    assert.equal(audio.pauseCalls, 1);
    assert.equal(audio.src, '');
    assert.deepEqual(context.revokedUrls, [activeUrl]);
});
