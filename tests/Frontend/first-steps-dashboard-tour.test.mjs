import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const scriptPath = join(root, 'resources/views/shared/user/first-steps-modal-script.blade.php');
const source = readFileSync(scriptPath, 'utf8');
const script = source.slice(source.indexOf('<script data-sr-first-steps-modal-script>') + '<script data-sr-first-steps-modal-script>'.length, source.lastIndexOf('</script>'));

function classList(initial = []) {
    const values = new Set(initial);
    return {
        add(value) { values.add(value); },
        remove(value) { values.delete(value); },
        contains(value) { return values.has(value); },
        toggle(value, force) {
            if (force) values.add(value);
            else values.delete(value);
        },
    };
}

function runFirstSteps(layout, action, { complete = false, confirmed = true } = {}) {
    const dashboardUrl = layout === 'mobile' ? '/dashboard?layout=mobile' : '/dashboard';
    const tourUrl = layout === 'mobile' ? '/dashboard?layout=mobile&onboarding=tour' : '/dashboard?onboarding=tour';
    const assigned = [];
    const requests = [];
    const confirmations = [];
    const listeners = new Map();
    const skipButton = {
        disabled: false,
        setAttribute() {},
        removeAttribute() {},
    };
    const proceedButton = {
        disabled: !complete,
        setAttribute() {},
    };
    const steps = Array.from({ length: 4 }, () => ({ classList: classList(complete ? ['is-done'] : []) }));
    const modal = {
        dataset: {
            srOnboardingUser: '7',
            srFirstStepsComplete: String(complete),
            srFirstStepsAutoShow: 'false',
            srDashboardUrl: dashboardUrl,
            srDashboardTourUrl: tourUrl,
            srOnboardingProgressUrl: '/user/onboarding/progress',
        },
        querySelector(selector) {
            if (selector === '[data-sr-first-steps-proceed]') return proceedButton;
            if (selector === '[data-sr-first-steps-skip]') return skipButton;
            if (selector === '[data-sr-first-steps-footer-copy]') return { textContent: '' };
            return null;
        },
        querySelectorAll(selector) {
            return selector === '[data-sr-onboarding-step]' ? steps : [];
        },
        contains(target) { return target === skipButton || target === proceedButton; },
        addEventListener() {},
    };
    const window = {
        location: new URL(`https://speakready.test${dashboardUrl}`),
        history: {
            replaceState(_state, _title, url) {
                window.location = new URL(url, window.location.origin);
                window.location.assign = assign;
            },
        },
        bootstrap: { Modal: { getOrCreateInstance: () => ({ hide() {}, show() {} }) } },
        SpeakReadyConfirm: layout === 'desktop' ? { show: async () => { confirmations.push('desktop'); return confirmed; } } : undefined,
        SpeakReadyMobileConfirm: layout === 'mobile' ? { show: async () => { confirmations.push('mobile'); return confirmed; } } : undefined,
        sessionStorage: { getItem: () => null, setItem() {}, removeItem() {} },
        addEventListener() {},
        setTimeout() {},
    };
    function assign(url) { assigned.push(new URL(url, window.location.origin).href); }
    window.location.assign = assign;

    const document = {
        body: { classList: classList() },
        getElementById(id) { return id === 'firstStepsOnboardingModal' ? modal : null; },
        querySelector(selector) { return selector === 'meta[name="csrf-token"]' ? { content: 'csrf' } : null; },
        addEventListener(name, listener) { listeners.set(name, listener); },
    };
    const fetch = async (url, options) => {
        requests.push({ url, options });
        return { ok: true, json: async () => ({ onboarding: {} }) };
    };

    runInNewContext(script, { window, document, URL, URLSearchParams, fetch, Promise, CustomEvent: class {} }, { filename: scriptPath });
    const target = action === 'skip' ? skipButton : proceedButton;
    listeners.get('click')({ target: { closest: selector => selector === `[data-sr-first-steps-${action}]` ? target : null }, preventDefault() {} });
    return { assigned, requests, confirmations, tourUrl: new URL(tourUrl, window.location.origin).href };
}

for (const layout of ['desktop', 'mobile']) {
    test(`${layout} First Steps Skip opens the dashboard tour after confirmation`, async () => {
        const layoutPath = join(root, `resources/views/${layout}/layouts/app.blade.php`);
        assert.match(readFileSync(layoutPath, 'utf8'), /@include\('shared\.user\.first-steps-modal-script'\)/);

        const result = runFirstSteps(layout, 'skip');
        await new Promise(resolve => setImmediate(resolve));

        assert.deepEqual(result.confirmations, [layout]);
        assert.deepEqual(result.assigned, [result.tourUrl]);
        assert.equal(result.requests.length, 0, 'Skipping First Steps must not mark the dashboard tutorial as skipped');
    });

    test(`${layout} canceling First Steps Skip keeps setup open`, async () => {
        const result = runFirstSteps(layout, 'skip', { confirmed: false });
        await new Promise(resolve => setImmediate(resolve));

        assert.deepEqual(result.confirmations, [layout]);
        assert.deepEqual(result.assigned, []);
        assert.equal(result.requests.length, 0);
    });

    test(`${layout} completed First Steps Proceed opens the dashboard tour`, () => {
        const result = runFirstSteps(layout, 'proceed', { complete: true });
        assert.deepEqual(result.assigned, [result.tourUrl]);
        assert.equal(result.requests.length, 0);
    });
}
