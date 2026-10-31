import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const read = relativePath => readFileSync(join(root, relativePath), 'utf8');
const session = read('resources/views/desktop/interview/session.blade.php');
const sessionCss = read('public/css/desktop/interview/session.css');

function functionSource(name, nextName) {
    const start = session.indexOf(`function ${name}`);
    const end = session.indexOf(`function ${nextName}`, start);

    assert.ok(start >= 0 && end > start, `${name} source was not found`);

    return session.slice(start, end);
}

test('desktop Begin Interview uses its user gesture before starting the session', () => {
    const confirmSource = functionSource('confirmInterviewStart()', 'cancelInterviewStart()');
    const fullscreenRequest = confirmSource.indexOf('enterInterviewFullscreen({ auto: true });');
    const sessionStart = confirmSource.indexOf('startInterviewSession({ requestBrowserFullscreen: false });');

    assert.match(session, /data-fullscreen-entry="user-gesture"/);
    assert.ok(fullscreenRequest >= 0, 'Begin Interview must request fullscreen');
    assert.ok(sessionStart > fullscreenRequest, 'fullscreen must be requested before the session starts');
});

test('desktop session load waits at the start gate instead of making a blocked fullscreen request', () => {
    const startupStart = session.indexOf("document.addEventListener('DOMContentLoaded', () => {");
    const startupEnd = session.indexOf('\n </script>', startupStart);
    const startupSource = session.slice(startupStart, startupEnd);

    assert.ok(startupStart >= 0 && startupEnd > startupStart, 'desktop session startup source was not found');
    assert.match(startupSource, /requestBrowser:\s*false/);
    assert.match(startupSource, /setInterviewStartModalVisible\(true\);/);
    assert.doesNotMatch(startupSource, /startInterviewSession\(\);/);
});

test('desktop fullscreen keeps a CSS fallback and retries native fullscreen on a user gesture', () => {
    assert.match(session, /function requestInterviewBrowserFullscreen\(\)/);
    assert.match(session, /root\.requestFullscreen\(\)/);
    assert.match(session, /root\.webkitRequestFullscreen\(\)/);
    assert.match(session, /let interviewFullscreenBusy = false;/);
    assert.match(session, /let interviewFullscreenRequestGeneration = 0;/);
    assert.match(session, /function armInterviewFullscreenRetry\(\)/);
    assert.match(session, /interviewFullscreenRetryEvents = \['pointerdown', 'keydown', 'touchstart', 'click'\]/);
    assert.match(session, /event\?\.type === 'keydown' && event\.key === 'Escape'/);
    assert.match(session, /const requestGeneration = \+\+interviewFullscreenRequestGeneration;/);
    assert.match(session, /interviewFullscreenRequestGeneration \+= 1;/);
    assert.match(session, /requestGeneration!== interviewFullscreenRequestGeneration/);
    assert.match(session, /if \(!interviewSessionBrowserFullscreenRequested\) \{\s*exitInterviewBrowserFullscreen\(\);/);
    assert.match(session, /console\.warn\('Interview browser fullscreen request was blocked:'/);
});

test('desktop fullscreen locks the outer viewport and keeps response scrolling internal', () => {
    assert.match(session, /css\/desktop\/interview\/session\.css\?v=68/);
    assert.match(sessionCss, /html\.interview-session-fullscreen-root[\s\S]*?overflow:\s*hidden !important;/);
    assert.match(sessionCss, /interview-session-browser-fullscreen #dashboard \.db-content[\s\S]*?overflow:\s*hidden !important;/);
    assert.match(sessionCss, /interview-session-browser-fullscreen \.desktop-response-column \.response-panel[\s\S]*?overflow-y:\s*auto !important;/);
});
