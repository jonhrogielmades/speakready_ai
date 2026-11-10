import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const viewPaths = [
    'resources/views/desktop/user/game-session.blade.php',
    'resources/views/mobile/user/game-session.blade.php',
];

function deferred() {
    let resolve, reject;
    const promise = new Promise((yes, no) => { resolve = yes; reject = no; });
    return { promise, resolve, reject };
}

function harness(source, sample = async () => ({ observation_status: 'measured' })) {
    const calls = { sample: [], render: [], clear: 0, status: [], stopped: 0 };
    const track = { readyState: 'live', muted: false, stop() { calls.stopped++; } };
    const stream = { getVideoTracks: () => [track], getTracks: () => [track] };
    const video = { srcObject: stream, readyState: 2, videoWidth: 640, videoHeight: 480, play: () => Promise.resolve() };
    const shared = {
        createTracker: () => ({ sample: async (...args) => { calls.sample.push(args); return sample(...args); } }),
        render: (...args) => calls.render.push(args),
        renderUnavailable: () => { calls.clear++; },
    };
    const context = vm.createContext({
        window: { SpeakReadyCameraObservations: shared, bodyLanguageModelState: { ready: true, poseLandmarker: {} }, faceFramingModelReady: true, faceapi: {} },
        document: { visibilityState: 'visible', getElementById: id => id === 'userCamera' ? video : null, querySelector: () => null },
        navigator: { mediaDevices: { getUserMedia: () => Promise.resolve(stream) } },
        cameraCoachingEnabled: true, challengeSessionStarted: true, isFinishingChallenge: false, isChallengeTransitioning: false,
        gameCameraTracker: null, gameCameraTrackingInFlight: false, gameCameraEpoch: 0, gameCameraLastObservationAtMs: null,
        gameCameraRequestToken: 0, cameraTrackingInterval: 1, currentQIdx: 0, recTimerSeconds: 5,
        now: 1000, performance: { now: () => context.now },
        setGameCameraStat: () => {}, setGameCameraStatus: message => calls.status.push(message),
        clearInterval: () => {}, console: { warn() {}, error() {} },
    });
    const start = source.indexOf(' async function trackBodyLanguage()');
    const end = source.indexOf(' let visualizerInterval', start);
    assert.ok(start >= 0 && end > start, 'camera tracking implementation exists');
    vm.runInContext(source.slice(start, end), context);
    const cameraStart = source.indexOf(' function initCamera()');
    const cameraEnd = source.indexOf(' function setGameCameraStatus(', cameraStart);
    vm.runInContext(source.slice(cameraStart, cameraEnd), context);
    return { context, calls, video, track, stream };
}

for (const path of viewPaths) {
    const source = readFileSync(new URL('../../' + path, import.meta.url), 'utf8');

    test(path + ' gates camera analysis on live, visible, opted-in sessions', async () => {
        const cases = [
            ({ context }) => { context.cameraCoachingEnabled = false; },
            ({ context }) => { context.challengeSessionStarted = false; },
            ({ context }) => { context.isFinishingChallenge = true; },
            ({ context }) => { context.isChallengeTransitioning = true; },
            ({ context }) => { context.document.visibilityState = 'hidden'; },
            ({ video }) => { video.readyState = 1; },
            ({ video }) => { video.videoWidth = 0; },
            ({ track }) => { track.muted = true; },
            ({ track }) => { track.readyState = 'ended'; },
        ];
        for (const change of cases) {
            const run = harness(source);
            change(run);
            await run.context.trackBodyLanguage();
            assert.equal(run.calls.sample.length, 0);
            assert.equal(run.calls.render.length, 0);
        }
    });

    test(path + ' prevents overlapping detection and supplies the shared frame context', async () => {
        const pending = deferred();
        const run = harness(source, () => pending.promise);
        const first = run.context.trackBodyLanguage();
        await run.context.trackBodyLanguage();
        assert.equal(run.calls.sample.length, 1);
        const [video, config] = run.calls.sample[0];
        assert.equal(video, run.video);
        assert.equal(config.faceReady, true);
        assert.equal(config.questionKey, '0:0');
        assert.equal(config.atSeconds, 5);
        assert.equal(config.current(), true);
        pending.resolve({ observation_status: 'measured' });
        await first;
        assert.equal(run.calls.render.length, 1);
        assert.equal(run.context.gameCameraTrackingInFlight, false);
    });

    test(path + ' rejects results after question, lifecycle, or camera changes', async () => {
        const changes = [
            ({ context }) => { context.currentQIdx++; },
            ({ context }) => { context.gameCameraEpoch++; },
            ({ context }) => { context.isFinishingChallenge = true; },
            ({ context }) => { context.isChallengeTransitioning = true; },
            ({ context }) => { context.document.visibilityState = 'hidden'; },
            ({ video }) => { video.srcObject = { getVideoTracks: () => [] }; },
            ({ track }) => { track.readyState = 'ended'; },
        ];
        for (const change of changes) {
            const pending = deferred();
            const run = harness(source, () => pending.promise);
            const first = run.context.trackBodyLanguage();
            change(run);
            assert.equal(run.calls.sample[0][1].current(), false);
            pending.resolve({ observation_status: 'measured' });
            await first;
            assert.equal(run.calls.render.length, 0);
            assert.equal(run.context.gameCameraTrackingInFlight, false);
        }
    });

    test(path + ' clears old cues while fresh frames are unavailable', async () => {
        const run = harness(source, async () => null);
        run.context.gameCameraLastObservationAtMs = 1000;
        run.context.now = 3101;
        await run.context.trackBodyLanguage();
        assert.equal(run.calls.clear, 1);
        assert.equal(run.calls.render.length, 0);
        assert.match(run.calls.status[0], /fresh visible frame/);
    });

    test(path + ' recovers from failed detection without publishing stale errors', async () => {
        const pending = deferred();
        const run = harness(source, () => pending.promise);
        const first = run.context.trackBodyLanguage();
        run.context.currentQIdx++;
        pending.reject(new Error('model failed'));
        await first;
        assert.equal(run.calls.status.length, 0);
        assert.equal(run.calls.render.length, 0);
        assert.equal(run.context.gameCameraTrackingInFlight, false);
    });

    test(path + ' reports ambiguity without claiming camera accuracy', async () => {
        const run = harness(source, async () => ({ observation_status: 'multiple_people' }));
        await run.context.trackBodyLanguage();
        assert.match(run.calls.status[0], /One person at a time/);
        assert.equal(run.context.gameCameraLastObservationAtMs, 1000);
    });

    test(path + ' stops camera access granted after session teardown', async () => {
        const permission = deferred();
        const run = harness(source);
        run.video.srcObject = null;
        run.context.navigator.mediaDevices.getUserMedia = () => permission.promise;
        run.context.initCamera();
        run.context.stopGameCamera();
        permission.resolve(run.stream);
        await permission.promise;
        await Promise.resolve();
        assert.equal(run.calls.stopped, 1);
        assert.equal(run.video.srcObject, null);
    });

    test(path + ' ignores permission errors after session teardown', async () => {
        const permission = deferred();
        const run = harness(source);
        run.context.navigator.mediaDevices.getUserMedia = () => permission.promise;
        run.context.initCamera();
        run.context.stopGameCamera();
        permission.reject(new Error('permission denied'));
        await Promise.resolve();
        await Promise.resolve();
        assert.equal(run.calls.status.length, 0);
    });

    test(path + ' loads pinned models only with optional camera coaching', () => {
        const conditional = source.indexOf('@if(isset($cameraCoachingEnabled) && $cameraCoachingEnabled)');
        const closing = source.indexOf('@endif', conditional);
        const assets = source.slice(conditional, closing);
        assert.match(assets, /js\/shared\/camera-observations\.js/);
        assert.match(assets, /face-api\.js@a86f011d72124e5fb93e59d5c4ab98f699dd5c9c\/weights/);
        assert.match(assets, /pose_landmarker_lite\/float16\/1\/pose_landmarker_lite\.task/);
        assert.match(assets, /numPoses: 2/);
        assert.doesNotMatch(assets, /@master|\/latest\//);
        assert.doesNotMatch(source, /function gameVisibleLandmark|gameCameraMovementBaseline|detectSingleFace/);
    });
}
