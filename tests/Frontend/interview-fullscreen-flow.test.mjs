import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const read = relativePath => readFileSync(join(root, relativePath), 'utf8');
const session = read('resources/views/desktop/interview/session.blade.php');
const sessionViews = [
    'resources/views/desktop/interview/session.blade.php',
    'resources/views/mobile/interview/session.blade.php',
];
const sessionCss = read('public/css/desktop/interview/session.css');

function startupSource(source) {
    const start = source.indexOf("document.addEventListener('DOMContentLoaded', () => {");
    const end = source.indexOf('\n </script>', start);

    assert.ok(start >= 0 && end > start, 'session startup source was not found');

    return source.slice(start, end);
}

test('interview session views omit the duplicate start dialog', () => {
    for (const relativePath of sessionViews) {
        const source = read(relativePath);

        assert.doesNotMatch(source, /id="interviewStartModal"/, `${relativePath} still renders the start dialog`);
        assert.doesNotMatch(source, /<h4 id="interviewStartTitle">Interview Ready<\/h4>/);
    }
});

test('interview session views retain the spoken question caption overlay', () => {
    for (const relativePath of sessionViews) {
        const source = read(relativePath);

        assert.match(source, /class="question-caption-overlay"/, `${relativePath} is missing the question caption overlay`);
        assert.match(source, /id="questionCaptionText"/, `${relativePath} is missing the question caption text`);
    }
});

test('desktop session starts after entering the fullscreen layout and arming native fullscreen retry', () => {
    const startup = startupSource(session);
    const fullscreenLayout = startup.indexOf('enterInterviewFullscreen({ auto: setupRequestedFullscreen || !interviewTerminated, requestBrowser: false });');
    const fullscreenRetry = startup.indexOf('armInterviewFullscreenRetry();');
    const sessionStart = startup.indexOf('startInterviewSession({ requestBrowserFullscreen: false });');

    assert.ok(fullscreenLayout >= 0, 'desktop session must enter the fullscreen layout');
    assert.ok(fullscreenRetry > fullscreenLayout, 'native fullscreen retry must be armed after layout entry');
    assert.ok(sessionStart > fullscreenRetry, 'desktop session must start without a second confirmation');
    assert.doesNotMatch(startup, /setInterviewStartModalVisible\(/);
});

test('mobile session starts automatically after entering its fullscreen layout', () => {
    const startup = startupSource(read('resources/views/mobile/interview/session.blade.php'));
    const fullscreenLayout = startup.indexOf('enterMobileFullscreen({ requestBrowser: false });');
    const sessionStart = startup.indexOf('startInterviewSession();');

    assert.ok(fullscreenLayout >= 0, 'mobile session must enter the fullscreen layout');
    assert.ok(sessionStart > fullscreenLayout, 'mobile session must start without a second confirmation');
    assert.doesNotMatch(startup, /setInterviewStartModalVisible\(/);
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
    assert.match(session, /css\/desktop\/interview\/session\.css\?v=69/);
    assert.match(sessionCss, /html\.interview-session-fullscreen-root[\s\S]*?overflow:\s*hidden !important;/);
    assert.match(sessionCss, /interview-session-browser-fullscreen #dashboard \.db-content[\s\S]*?overflow:\s*hidden !important;/);
    assert.match(sessionCss, /interview-session-browser-fullscreen \.desktop-response-column \.response-panel[\s\S]*?overflow-y:\s*auto !important;/);
});

test('Interview Session does not auto-start its onboarding tutorial on desktop or mobile', () => {
    for (const relativePath of sessionViews) {
        const source = read(relativePath);

        assert.doesNotMatch(source, /onboarding_completed_interview_session/);
        assert.doesNotMatch(source, /scheduleInterviewSessionTour/);
    }
});
