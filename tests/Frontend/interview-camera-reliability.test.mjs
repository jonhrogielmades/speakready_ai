import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const paths = [
    'resources/views/desktop/interview/session.blade.php',
    'resources/views/mobile/interview/session.blade.php',
];

function deferred() {
    let resolve, reject;
    const promise = new Promise((yes, no) => { resolve = yes; reject = no; });
    return { promise, resolve, reject };
}

function harness(source, sample = async () => ({ observation_status: 'measured' })) {
    const calls = { sample: [], render: [], clear: 0 };
    const track = { readyState: 'live', enabled: true, muted: false };
    const stream = { getVideoTracks: () => [track] };
    const video = { srcObject: stream, readyState: 2, paused: false, ended: false, videoWidth: 640, videoHeight: 480 };
    const status = { textContent: '', style: {} };
    const shared = {
        createTracker: () => ({ sample: async (...args) => { calls.sample.push(args); return sample(...args); } }),
        render: (...args) => calls.render.push(args),
        renderUnavailable: () => { calls.clear++; },
    };
    const context = vm.createContext({
        window: { SpeakReadyCameraObservations: shared, bodyLanguageModelState: { ready: true, poseLandmarker: {} }, faceFramingModelReady: true, faceapi: {} },
        document: { hidden: false, getElementById: id => id === 'userCamera' ? video : id === 'cameraDetectionStatus' ? status : null },
        cameraDetectionEnabled: true, isRecording: true, interviewTerminated: false,
        cameraObservationTracker: null, cameraTrackingInFlight: false, cameraRecordingEpoch: 1, cameraLastObservationAtMs: 0,
        currentQIdx: 0, recTimerSeconds: 5, answersData: [{ observation_data: { camera_samples: [] } }, { observation_data: { camera_samples: [] } }],
        defaultAnswerState: () => ({ observation_data: { camera_samples: [] } }),
        now: 1000, performance: { now: () => context.now }, setCameraStat: () => {}, console: { warn() {} },
    });
    const start = source.indexOf(' function cameraVideoReady(');
    const end = source.indexOf(' let visualizerInterval', start);
    assert.ok(start >= 0 && end > start, 'camera wrapper exists');
    vm.runInContext(source.slice(start, end), context);
    return { context, calls, video, track, status };
}

for (const path of paths) {
    const source = readFileSync(new URL('../../' + path, import.meta.url), 'utf8');

    test(path + ' only analyzes ready frames during opted-in recording', async () => {
        const changes = [
            ({ context }) => { context.cameraDetectionEnabled = false; },
            ({ context }) => { context.isRecording = false; },
            ({ context }) => { context.interviewTerminated = true; },
            ({ context }) => { context.document.hidden = true; },
            ({ video }) => { video.readyState = 1; },
            ({ video }) => { video.videoWidth = 0; },
            ({ video }) => { video.paused = true; },
            ({ video }) => { video.ended = true; },
            ({ track }) => { track.enabled = false; },
            ({ track }) => { track.muted = true; },
            ({ track }) => { track.readyState = 'ended'; },
        ];
        for (const change of changes) {
            const run = harness(source);
            change(run);
            await run.context.trackBodyLanguageDetection();
            assert.equal(run.calls.sample.length, 0);
            assert.equal(run.context.answersData[0].observation_data.camera_samples.length, 0);
        }
    });

    test(path + ' serializes detection and saves the measured sample to its answer', async () => {
        const pending = deferred();
        const run = harness(source, () => pending.promise);
        const first = run.context.trackBodyLanguageDetection();
        await run.context.trackBodyLanguageDetection();
        assert.equal(run.calls.sample.length, 1);
        const [video, config] = run.calls.sample[0];
        assert.equal(video, run.video);
        assert.equal(config.questionKey, '1:0');
        assert.equal(config.atSeconds, 5);
        assert.equal(config.current(), true);
        const sample = { observation_status: 'measured', sample_index: 1, camera_facing: false };
        pending.resolve(sample);
        await first;
        assert.equal(run.calls.render.length, 1);
        assert.equal(run.context.answersData[0].observation_data.camera_samples[0], sample);
        assert.equal(run.context.answersData[0].observation_data.camera_detection_protocol, 3);
        assert.equal(run.context.cameraTrackingInFlight, false);
    });

    test(path + ' discards a detection after the recording context changes', async () => {
        const changes = [
            ({ context }) => { context.currentQIdx++; },
            ({ context }) => { context.cameraRecordingEpoch++; },
            ({ context }) => { context.isRecording = false; },
            ({ context }) => { context.interviewTerminated = true; },
            ({ context }) => { context.document.hidden = true; },
            ({ video }) => { video.srcObject = { getVideoTracks: () => [] }; },
            ({ track }) => { track.readyState = 'ended'; },
        ];
        for (const change of changes) {
            const pending = deferred();
            const run = harness(source, () => pending.promise);
            const first = run.context.trackBodyLanguageDetection();
            change(run);
            assert.equal(run.calls.sample[0][1].current(), false);
            pending.resolve({ observation_status: 'measured' });
            await first;
            assert.equal(run.calls.render.length, 0);
            assert.equal(run.context.answersData[0].observation_data.camera_samples.length, 0);
            assert.equal(run.context.answersData[1].observation_data.camera_samples.length, 0);
            assert.equal(run.context.cameraTrackingInFlight, false);
        }
    });

    test(path + ' pause and resume cannot reuse the pending recording observation', async () => {
        const pending = deferred();
        let count = 0;
        const run = harness(source, () => ++count === 1 ? pending.promise : { observation_status: 'calibrating' });
        const first = run.context.trackBodyLanguageDetection();
        run.context.isRecording = false;
        run.context.cameraRecordingEpoch++;
        run.context.isRecording = true;
        pending.resolve({ observation_status: 'measured' });
        await first;
        assert.equal(run.calls.render.length, 0);
        await run.context.trackBodyLanguageDetection();
        assert.equal(run.calls.sample[1][1].questionKey, '2:0');
        assert.equal(run.context.answersData[0].observation_data.camera_samples.length, 1);
        assert.equal(run.context.answersData[0].observation_data.camera_samples[0].observation_status, 'calibrating');
        const start = source.indexOf('async function startRecordingInternal(');
        const end = source.indexOf(' function toggleRecordingPause(', start);
        assert.match(source.slice(start, end), /cameraRecordingEpoch\s*\+=\s*1/);
    });

    test(path + ' clears stale cues instead of retaining an old camera estimate', async () => {
        const run = harness(source, async () => null);
        run.context.cameraLastObservationAtMs = 1000;
        run.context.now = 3101;
        await run.context.trackBodyLanguageDetection();
        assert.ok(run.calls.clear >= 1);
        assert.equal(run.calls.render.length, 0);
        assert.equal(run.context.answersData[0].observation_data.camera_samples.length, 0);
    });

    test(path + ' does not clear a new recording because an earlier detection rejected', async () => {
        const pending = deferred();
        const run = harness(source, () => pending.promise);
        const first = run.context.trackBodyLanguageDetection();
        run.context.cameraRecordingEpoch++;
        pending.reject(new Error('old camera failed'));
        await first;
        assert.equal(run.calls.clear, 0);
        assert.equal(run.context.cameraTrackingInFlight, false);
    });

    test(path + ' expires visible cues even while a detection is pending', async () => {
        const pending = deferred();
        const run = harness(source, () => pending.promise);
        run.context.cameraLastObservationAtMs = 1000;
        const first = run.context.trackBodyLanguageDetection();
        run.context.now = 3101;
        await run.context.trackBodyLanguageDetection();
        assert.equal(run.calls.sample.length, 1);
        assert.ok(run.calls.clear >= 1);
        pending.resolve(null);
        await first;
        assert.equal(run.calls.render.length, 0);
    });
}
