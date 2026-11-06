import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const workerSource = readFileSync(resolve('public/sw.js'), 'utf8');

function createWorker() {
    const listeners = new Map();
    const responses = new Map();
    const fetchModes = [];
    const cache = {
        match: async request => responses.get(request.url),
        put: async (request, response) => { responses.set(request.url, response); },
    };
    const context = {
        URL,
        self: {
            location: { origin: 'https://speakready.test' },
            addEventListener: (name, handler) => listeners.set(name, handler),
        },
        caches: { open: async () => cache },
        fetch: async (_request, options) => {
            fetchModes.push(options?.cache);
            return { ok: true, clone() { return this; } };
        },
    };

    runInNewContext(workerSource, context);

    async function request(path) {
        const event = {
            request: {
                method: 'GET',
                mode: 'cors',
                url: `https://speakready.test${path}`,
                headers: { get: () => '' },
            },
            respondWith(promise) { this.response = promise; },
        };
        listeners.get('fetch')(event);
        return event.response;
    }

    return { request, fetchModes };
}

test('reuses versioned minified CSS without a network request', async () => {
    const worker = createWorker();

    await worker.request('/css/desktop/style.min.css?v=123');
    await worker.request('/css/desktop/style.min.css?v=123');
    assert.deepEqual(worker.fetchModes, ['default']);

    await worker.request('/css/desktop/style.min.css?v=124');
    assert.deepEqual(worker.fetchModes, ['default', 'default']);
});

test('continues to revalidate stable code assets', async () => {
    const worker = createWorker();

    await worker.request('/css/desktop/guest.css?v=3');
    await worker.request('/css/desktop/guest.css?v=3');
    assert.deepEqual(worker.fetchModes, ['no-cache', 'no-cache']);

    await worker.request('/css/desktop/style.min.css');
    await worker.request('/css/desktop/style.min.css');
    assert.deepEqual(worker.fetchModes, ['no-cache', 'no-cache', 'no-cache', 'no-cache']);
});
