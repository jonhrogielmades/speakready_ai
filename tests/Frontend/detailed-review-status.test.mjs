import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const source = readFileSync(join(root, 'public/js/detailed-review-status.js'), 'utf8');

function eventTarget(extra = {}) {
    const handlers = new Map();
    return Object.assign({
        addEventListener(event, handler) {
            const callbacks = handlers.get(event) || [];
            callbacks.push(handler);
            handlers.set(event, callbacks);
        },
        emit(event) { (handlers.get(event) || []).forEach(handler => handler()); },
    }, extra);
}

function harness(options = {}) {
    let now = options.now || 1000000;
    let nextId = 0;
    let reloads = 0;
    const timers = new Map();
    const requests = [];
    const storage = options.storage || new Map();
    const heading = { textContent: '' };
    const detail = { textContent: '' };
    const retry = { hidden: true };
    const reload = eventTarget({ hidden: true });
    const elements = {
        '[data-review-status-title]': heading,
        '[data-review-status-message]': detail,
        '[data-review-retry]': retry,
        '[data-review-reload]': reload,
    };
    const panel = {
        dataset: {
            reviewStatus: options.status || 'processing',
            reviewStatusUrl: options.url || '/session/42/review/status',
        },
        querySelector(selector) { return elements[selector] || null; },
    };
    const document = eventTarget({
        hidden: !!options.hidden,
        readyState: 'complete',
        querySelector() { return options.noPanel ? null : panel; },
    });
    const window = eventTarget({
        navigator: { onLine: options.online !== false },
        location: {
            href: 'https://speakready.test/session/42/review',
            origin: 'https://speakready.test',
            reload() { reloads += 1; },
        },
        history: {
            state: options.historyState || {},
            replaceState(state) {
                if (options.historyUnavailable) throw new Error('Unavailable');
                this.state = state;
            },
        },
        sessionStorage: {
            getItem(key) {
                if (options.storageUnavailable) throw new Error('Unavailable');
                return storage.get(key) ?? null;
            },
            setItem(key, value) {
                if (options.storageUnavailable) throw new Error('Unavailable');
                storage.set(key, value);
            },
        },
        setTimeout(callback, delay = 0) {
            const id = ++nextId;
            timers.set(id, { callback, at: now + delay });
            return id;
        },
        clearTimeout(id) { timers.delete(id); },
        fetch(url, init) {
            requests.push({ url, init });
            if (options.fetch) return options.fetch(url, init, requests.length);
            const result = options.responses?.[requests.length - 1] ?? { status: 'processing', retry_after_ms: 5000 };
            if (result instanceof Error) return Promise.reject(result);
            return Promise.resolve({ ok: true, json: async () => result });
        },
    });

    runInNewContext(source, { window, document, URL, AbortController, Date: { now: () => now } });

    async function settle() {
        for (let step = 0; step < 12; step += 1) await Promise.resolve();
    }
    async function advance(ms) {
        const target = now + ms;
        let iterations = 0;
        while (true) {
            const entry = [...timers.entries()].sort((a, b) => a[1].at - b[1].at)[0];
            if (!entry || entry[1].at > target) break;
            assert.ok(++iterations < 200, 'Unexpected polling loop');
            now = entry[1].at;
            timers.delete(entry[0]);
            entry[1].callback();
            await settle();
        }
        now = target;
        await settle();
    }
    return { window, document, heading, detail, retry, reload, requests, storage, advance, settle, timers, get reloads() { return reloads; } };
}

test('desktop and mobile use the shared pending-only poller and preserve the manual POST fallback', () => {
    const view = readFileSync(join(root, 'resources/views/shared/user/review-content.blade.php'), 'utf8');
    assert.match(view, /@if\(!\$sessionEndedEarly && \(\$providerReviewPending \?\? false\)\)/);
    assert.match(view, /route\('user\.review\.status', \$sessionRecord\)/);
    assert.match(view, /method="POST" action="{{ route\('user\.review\.refresh', \$sessionRecord\) }}" data-review-retry @if\(\$providerReviewCanContinue\) hidden @endif/);
    assert.match(view, /role="status" aria-live="polite" aria-atomic="true"/);
    assert.match(view, /detailed-review-status\.js\?v=1/);
    for (const layout of ['desktop', 'mobile']) {
        const wrapper = readFileSync(join(root, `resources/views/${layout}/user/review.blade.php`), 'utf8');
        assert.match(wrapper, /@include\('shared\.user\.review-content'/);
    }
});

test('does nothing when there are no pending provider reviews', async () => {
    const ui = harness({ noPanel: true });
    await ui.advance(180000);
    assert.equal(ui.requests.length, 0);
});

test('reads status without mutations and refreshes automatically after processing and waiting', async () => {
    const ui = harness({ responses: [{ status: 'processing' }, { status: 'waiting', retry_after_ms: 7000 }, { status: 'ready' }] });
    await ui.advance(0);
    assert.equal(ui.requests.length, 1);
    assert.equal(ui.retry.hidden, true);
    assert.equal(ui.requests[0].init.method, 'GET');
    assert.equal(ui.requests[0].init.credentials, 'same-origin');
    assert.equal(ui.requests[0].init.cache, 'no-store');
    await ui.advance(5000);
    await ui.advance(7000);
    assert.equal(ui.reloads, 1);
    await ui.advance(180000);
    assert.equal(ui.requests.length, 3);
});

test('a stale page cannot enter an automatic reload loop', async () => {
    const storage = new Map();
    const first = harness({ storage, responses: [{ status: 'ready' }] });
    await first.advance(0);
    assert.equal(first.reloads, 1);
    const second = harness({ storage, responses: [{ status: 'ready' }] });
    await second.advance(0);
    assert.equal(second.reloads, 0);
    assert.equal(second.retry.hidden, true);
    assert.equal(second.reload.hidden, false);
    second.reload.emit('click');
    assert.equal(second.reloads, 1);
});

test('history state prevents reload loops if session storage is unavailable', async () => {
    const first = harness({ storageUnavailable: true, responses: [{ status: 'ready' }] });
    await first.advance(0);
    const second = harness({ storageUnavailable: true, historyState: first.window.history.state, responses: [{ status: 'ready' }] });
    await second.advance(0);
    assert.equal(first.reloads, 1);
    assert.equal(second.reloads, 0);
    assert.equal(second.reload.hidden, false);
});

test('falls back to an explicit reload if persistent guards are unavailable', async () => {
    const ui = harness({ storageUnavailable: true, historyUnavailable: true, responses: [{ status: 'ready' }] });
    await ui.advance(0);
    assert.equal(ui.reloads, 0);
    assert.equal(ui.reload.hidden, false);
});

for (const status of ['failed', 'unavailable']) {
    test(`${status} shows recovery immediately and does not poll`, async () => {
        const ui = harness({ status });
        await ui.advance(180000);
        assert.equal(ui.requests.length, 0);
        assert.equal(ui.retry.hidden, false);
    });

    test(`a ${status} result ends automatic polling and uses plain text messages`, async () => {
        const ui = harness({ responses: [{ status, message: '<img src=x onerror=alert(1)>' }] });
        await ui.advance(180000);
        assert.equal(ui.requests.length, 1);
        assert.equal(ui.retry.hidden, false);
        assert.equal(ui.detail.textContent, '<img src=x onerror=alert(1)>');
    });
}

test('pauses on hidden pages and offline, resumes when visible and online', async () => {
    const ui = harness({ hidden: true });
    await ui.advance(5000);
    assert.equal(ui.requests.length, 0);
    ui.document.hidden = false;
    ui.document.emit('visibilitychange');
    await ui.advance(0);
    assert.equal(ui.requests.length, 1);
    ui.window.navigator.onLine = false;
    ui.window.emit('offline');
    await ui.advance(10000);
    assert.equal(ui.requests.length, 1);
    ui.window.navigator.onLine = true;
    ui.window.emit('online');
    await ui.advance(0);
    assert.equal(ui.requests.length, 2);
});

test('pagehide cancels in-flight work and ignores its late ready result', async () => {
    let resolve;
    const ui = harness({ fetch: () => new Promise(done => { resolve = done; }) });
    await ui.advance(0);
    ui.window.emit('pagehide');
    assert.equal(ui.requests[0].init.signal.aborted, true);
    resolve({ ok: true, json: async () => ({ status: 'ready' }) });
    await ui.settle();
    await ui.advance(180000);
    assert.equal(ui.reloads, 0);
    assert.equal(ui.requests.length, 1);
});

test('repeated network or invalid status failures reveal recovery instead of polling forever', async () => {
    const ui = harness({ responses: [new Error('Network'), { unexpected: 'shape' }, new Error('Network')] });
    await ui.advance(180000);
    assert.equal(ui.requests.length, 3);
    assert.equal(ui.retry.hidden, false);
});

test('a successful status clears transient connection failures', async () => {
    const ui = harness({ responses: [new Error('Network'), { status: 'processing' }, new Error('Network'), { status: 'ready' }] });
    await ui.advance(30000);
    assert.equal(ui.reloads, 1);
    assert.equal(ui.retry.hidden, true);
});

test('bounds delays and total polling duration', async () => {
    const ui = harness({ responses: [{ status: 'processing', retry_after_ms: -1 }, { status: 'waiting', retry_after_ms: 9999999 }] });
    await ui.advance(1999);
    assert.equal(ui.requests.length, 1);
    await ui.advance(1);
    assert.equal(ui.requests.length, 2);
    await ui.advance(14999);
    assert.equal(ui.requests.length, 2);
    await ui.advance(1);
    assert.equal(ui.requests.length, 3);
    await ui.advance(300000);
    assert.equal(ui.retry.hidden, false);
    assert.ok(ui.requests.length <= 60);
    assert.equal(ui.timers.size, 0);
});

test('allows a second queued provider attempt to finish before the five-minute polling limit', async () => {
    const ui = harness();
    await ui.advance(240000);
    assert.equal(ui.retry.hidden, true);
    assert.ok(ui.requests.length > 36);
    await ui.advance(60000);
    assert.equal(ui.retry.hidden, false);
    assert.equal(ui.requests.length, 60);
});

test('times out stalled requests without allowing overlapping polls', async () => {
    const ui = harness({ fetch: (_url, init) => new Promise((_resolve, reject) => {
        init.signal.addEventListener('abort', () => reject(new Error('Aborted')));
    }) });
    await ui.advance(14000);
    assert.equal(ui.requests.length, 1);
    await ui.advance(6000);
    assert.equal(ui.requests.length, 2);
    assert.equal(ui.requests[0].init.signal.aborted, true);
    await ui.advance(180000);
    assert.equal(ui.requests.length, 3);
    assert.equal(ui.retry.hidden, false);
});

test('refuses cross-origin status requests', async () => {
    const ui = harness({ url: 'https://other.test/review/status' });
    await ui.advance(180000);
    assert.equal(ui.requests.length, 0);
    assert.equal(ui.retry.hidden, false);
});
