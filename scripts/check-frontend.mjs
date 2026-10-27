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

for (const relativePath of [
    'public/js/main.js',
    'public/js/media-permission-support.js',
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
