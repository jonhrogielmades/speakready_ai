import { execFileSync } from 'node:child_process';
import { readFileSync, readdirSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const failures = [];

function walk(directory) {
    return readdirSync(directory, { withFileTypes: true }).flatMap(entry => {
        const absolutePath = join(directory, entry.name);

        if (entry.isDirectory()) {
            return walk(absolutePath);
        }

        return [absolutePath];
    });
}

function read(relativePath) {
    const absolutePath = join(root, relativePath);
    return readFileSync(absolutePath, 'utf8');
}

function assert(condition, message) {
    if (!condition) {
        failures.push(message);
    }
}

const viewFiles = walk(join(root, 'resources', 'views')).filter(file => file.endsWith('.blade.php'));
const viewSource = viewFiles.map(file => readFileSync(file, 'utf8')).join('\n');
const shellSource = [
    read('resources/views/mobile/layouts/app.blade.php'),
    read('resources/views/mobile/layouts/admin.blade.php'),
].join('\n');

assert(!/{!!\s*json_encode\(/.test(viewSource), 'Raw json_encode output remains in a Blade view. Use @js(...) for script payloads.');
assert(!/{!!\s*\$chapter->content\b/.test(viewSource), 'Stored chapter HTML is rendered without the ModuleChapterHtml sanitizer.');
assert(!/overflow-x\s*:\s*visible/i.test(shellSource), 'A mobile shell still forces table overflow to remain visible.');
assert(!/table-layout\s*:\s*fixed/i.test(shellSource), 'A mobile shell still forces all tables into fixed layout.');
assert(
    !/fetch\(\s*personalizationForm\.action\b/.test(viewSource),
    'The onboarding personalization request uses form.action, which can be shadowed by named controls. Read the action attribute instead.'
);
assert(
    !/new URL\(\s*form\.action\b/.test(viewSource),
    'A form transition reads form.action through named properties. Read the action attribute to avoid control-name collisions.'
);

const buttonSource = viewSource.replace(/{{[\s\S]*?}}/g, '');
const buttonsWithoutType = [...buttonSource.matchAll(/<button\b[^>]*>/gis)]
    .filter(match => !/\btype\s*=/.test(match[0]));
assert(
    buttonsWithoutType.length === 0,
    `${buttonsWithoutType.length} button element(s) are missing an explicit type attribute: ${buttonsWithoutType.map(match => match[0].replace(/\s+/g, ' ').slice(0, 160)).join(' | ')}`
);

const userUiVersions = [...viewSource.matchAll(/user-ui\.js[\s\S]{0,80}?v=(\d+)/g)].map(match => match[1]);
assert(userUiVersions.length > 0, 'No user-ui.js asset reference was found.');
assert(new Set(userUiVersions).size === 1, `user-ui.js cache versions are inconsistent: ${[...new Set(userUiVersions)].join(', ')}`);

const serviceWorkerVersions = [...viewSource.matchAll(/sw\.js\?v=(\d+)/g)].map(match => match[1]);
assert(serviceWorkerVersions.length > 0, 'No service-worker asset reference was found in the views.');
assert(new Set(serviceWorkerVersions).size === 1, `Service-worker asset versions are inconsistent: ${[...new Set(serviceWorkerVersions)].join(', ')}`);

const worker = read('public/sw.js');
const cacheVersion = worker.match(/const CACHE_NAME = 'speakready-pwa-v(\d+)'/u)?.[1];
const refreshVersion = worker.match(/const CLIENT_REFRESH_VALUE = 'pwa-v(\d+)'/u)?.[1];
assert(cacheVersion && cacheVersion === refreshVersion, 'Service-worker cache and client refresh versions do not match.');

const userUi = read('public/js/user-ui.js');
const dashboardSetupModal = read('resources/views/shared/user/dashboard-setup-tools-modal.blade.php');
const cleanupRuntimeStart = userUi.indexOf('function cleanupUserPageRuntime(options)');
const cleanupRuntimeEnd = userUi.indexOf('function cleanupDetachedUserPageArtifacts()', cleanupRuntimeStart);
const cleanupRuntimeSource = cleanupRuntimeStart !== -1 && cleanupRuntimeEnd !== -1
    ? userUi.slice(cleanupRuntimeStart, cleanupRuntimeEnd)
    : '';
assert(
    /<style\b[^>]*\bdata-page-style="dashboard-setup-tools-modal"[^>]*>/u.test(dashboardSetupModal),
    'Dashboard setup modal styles must expose a stable data-page-style key for partial navigation.'
);
assert(
    cleanupRuntimeSource !== '' && !/userApp\.pageRuntimeCleanups\s*=\s*\[\];\s*return;/u.test(cleanupRuntimeSource),
    'User page cleanup must not return before detached artifacts and transient page state are cleared.'
);
for (const transientClass of [
    'mobile-answer-focused',
    'mobile-answer-keyboard-open',
    'game-match-auto-fullscreen',
    'coach-mobile-page-active',
    'coach-input-focused',
    'coach-keyboard-open',
    'coach-fullscreen-active',
    'interview-session-fullscreen-root',
]) {
    assert(userUi.includes(`'${transientClass}'`), `Transient user page class is not cleaned up: ${transientClass}`);
}
const navigationStyleWait = userUi.indexOf('await waitForNavigationStylePaint(stylePromise)');
const navigationContentSwap = userUi.indexOf('replaceUserContent(content, nextContent)');
const navigationScriptRun = userUi.indexOf('await runPageScripts(html, contentScripts, { skipStyles: true })');
const navigationRevealWait = userUi.indexOf('await waitForUserNavigationPaint()');
const navigationReveal = userUi.indexOf('hideUserNavigationTransition()');
assert(
    navigationStyleWait !== -1 && navigationContentSwap !== -1 && navigationStyleWait < navigationContentSwap,
    'User navigation must wait for destination styles before swapping page HTML.'
);
assert(
    userUi.includes('function waitForNavigationStylePaint(stylePromise)') && userUi.includes('return stylePromise;'),
    'Destination stylesheet failures must propagate to the full-page navigation fallback.'
);
assert(
    navigationScriptRun !== -1 && navigationRevealWait > navigationScriptRun && navigationReveal > navigationRevealWait,
    'User navigation must keep its transition mask until page scripts finish and the initialized UI has painted.'
);

for (const relativePath of [
    'public/js/main.js',
    'public/js/media-permission-support.js',
    'public/js/onboarding-voiceover.js',
    'public/js/user-ui.js',
    'public/js/push-notifications.js',
    'public/sw.js',
]) {
    const absolutePath = join(root, relativePath);

    try {
        execFileSync(process.execPath, ['--check', absolutePath], { stdio: 'pipe' });
    } catch (error) {
        failures.push(`JavaScript syntax check failed: ${relativePath}\n${error.stdout?.toString() || error.stderr?.toString() || error.message}`);
    }
}

if (failures.length > 0) {
    console.error('Frontend checks failed:');
    failures.forEach(failure => console.error(`- ${failure}`));
    process.exitCode = 1;
} else {
    console.log(`Frontend checks passed (${viewFiles.length} Blade views inspected).`);
}
