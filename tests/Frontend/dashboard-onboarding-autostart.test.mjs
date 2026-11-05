import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const dashboardViews = [
    'resources/views/desktop/dashboard.blade.php',
    'resources/views/mobile/dashboard.blade.php',
];

function dashboardStartupScript(relativePath, requested) {
    const source = readFileSync(join(root, relativePath), 'utf8');
    const start = source.lastIndexOf('document.addEventListener("DOMContentLoaded", function() {');
    const end = source.indexOf('</script>', start);
    assert.ok(start >= 0 && end > start, `${relativePath} dashboard tour startup script was not found`);

    const script = source.slice(start, end).replace(/@json\([^\r\n;]*\)/g, expression => {
        if (expression.includes("dashboard_tour_completed")) return 'false';
        if (expression.includes("dashboard_tour_skipped")) return 'false';
        if (expression.includes("user.onboarding.progress")) return '"/onboarding/progress"';
        if (expression.includes('dashboardTourAutoStart')) return String(requested);
        throw new Error(`Unexpected Blade expression in ${relativePath}: ${expression}`);
    });

    assert.doesNotMatch(script, /@json\(/, `${relativePath} still has an unrendered Blade expression`);
    return script;
}

function runDashboardStartup(relativePath, requested) {
    const events = [];
    const timers = [];
    let now = 0;
    let domReady;
    const window = {
        location: new URL('https://speakready.test/dashboard?source=registration&onboarding=tour'),
        history: {
            state: { dashboard: true },
            replaceState(state, _title, url) {
                events.push({ type: 'replaceState', url });
                window.location = new URL(url, window.location.origin);
                this.state = state;
            },
        },
        setTimeout(callback, delay = 0) {
            timers.push({ callback, due: now + delay });
        },
        addEventListener() {},
    };
    const document = {
        addEventListener(type, callback) {
            if (type === 'DOMContentLoaded') domReady = callback;
        },
        getElementById() { return null; },
        querySelector() { return null; },
    };

    window.createSpeakReadyTour = () => {
        const registeredScope = `${window.location.pathname}${window.location.search}`;
        events.push({ type: 'register', scope: registeredScope });
        return {
            start() {
                // The actual tour controller checks scope now and in its own timer.
                if (`${window.location.pathname}${window.location.search}` !== registeredScope) return false;
                window.setTimeout(() => {
                    if (`${window.location.pathname}${window.location.search}` === registeredScope) {
                        events.push({ type: 'start', scope: registeredScope });
                    }
                });
                return true;
            },
        };
    };

    runInNewContext(dashboardStartupScript(relativePath, requested), {
        window, document, URL, URLSearchParams, fetch: () => { throw new Error('Unexpected progress request'); },
    }, { filename: relativePath });
    assert.equal(typeof domReady, 'function', `${relativePath} did not register DOMContentLoaded`);
    domReady();

    while (timers.length) {
        timers.sort((left, right) => left.due - right.due);
        const timer = timers.shift();
        now = timer.due;
        timer.callback();
    }

    return { events, url: window.location.href };
}

for (const relativePath of dashboardViews) {
    const layout = relativePath.includes('/mobile/') ? 'mobile' : 'desktop';

    test(`${layout} dashboard cleans tour query before registering and starts an authorized tour`, () => {
        const { events, url } = runDashboardStartup(relativePath, true);

        assert.deepEqual(events.map(event => event.type), ['replaceState', 'register', 'start']);
        assert.equal(events[0].url, '/dashboard?source=registration');
        assert.equal(events[1].scope, '/dashboard?source=registration');
        assert.equal(events[2].scope, events[1].scope);
        assert.equal(url, 'https://speakready.test/dashboard?source=registration');
    });

    test(`${layout} dashboard cleans a stale tour query without starting an unauthorized tour`, () => {
        const { events, url } = runDashboardStartup(relativePath, false);

        assert.deepEqual(events.map(event => event.type), ['replaceState', 'register']);
        assert.equal(events[1].scope, '/dashboard?source=registration');
        assert.equal(url, 'https://speakready.test/dashboard?source=registration');
    });
}
