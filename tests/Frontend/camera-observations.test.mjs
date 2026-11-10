import assert from 'node:assert/strict';
import test from 'node:test';
import '../../public/js/shared/camera-observations.js';

const { evaluate, createTracker, render, renderUnavailable } = globalThis.SpeakReadyCameraObservations;
const eye = (x, y) => [{ x: x - 2, y }, { x, y }, { x: x + 2, y }];
function face({ score = .95, noseX = 320, box = { x: 220, y: 130, width: 200, height: 230 } } = {}) {
    return { detection: { score, box }, landmarks: {
        getLeftEye: () => eye(270, 210), getRightEye: () => eye(370, 210),
        getNose: () => [...eye(noseX, 250), { x: noseX, y: 255 }],
    } };
}
function pose({ width = 640, height = 480, dx = 0, dy = 0, roll = 0, hips = true, visibility = .9 } = {}) {
    const points = [];
    const p = (x, y) => ({ x: (x + dx) / width, y: (y + dy) / height, visibility, presence: .95 });
    points[11] = p(220, 200);
    points[12] = p(420, 200 + roll);
    if (hips) {
        points[23] = p(240, 380);
        points[24] = p(400, 380);
    }
    return points;
}
function input(overrides = {}) {
    return { faces: [face()], poses: [pose()], width: 640, height: 480, capturedAtMs: 1000, ...overrides };
}
function repeated(overrides = {}, count = 3, state = {}) {
    let result;
    for (let index = 0; index < count; index++) result = evaluate(input({ ...overrides, capturedAtMs: 1000 + index * 500 }), state);
    return result;
}

test('valid observations require repeated frames and return explicit estimates', () => {
    const state = {};
    const first = evaluate(input(), state);
    assert.equal(first.observation_status, 'calibrating');
    assert.equal(first.camera_facing, null);
    assert.equal(first.upright_posture, null);
    evaluate(input({ capturedAtMs: 1500 }), state);
    const stable = evaluate(input({ capturedAtMs: 2000 }), state);
    assert.equal(stable.camera_facing, true);
    assert.equal(stable.upright_posture, true);
    assert.equal(stable.shoulders_level, true);
    assert.equal(stable.pose_confidence, 90);
    assert.equal(stable.sample_quality, 90);
    assert.equal(stable.observation_status, 'measured');
});

test('missing, weak, and impossible face confidence never becomes a reliable face', () => {
    for (const score of [undefined, null, NaN, .74, 1.1, -1]) {
        const invalid = face();
        invalid.detection.score = score;
        const sample = repeated({ faces: [invalid], poses: [] });
        assert.equal(sample.face_detected, false);
        assert.equal(sample.camera_facing, null);
        assert.equal(sample.sample_quality, 0);
        assert.equal(sample.observation_status, 'low_confidence');
    }
});

test('pose confidence and in-frame bounds are required, including missing visibility', () => {
    for (const changed of [
        { visibility: undefined }, { visibility: .64 }, { visibility: 1.1 },
        { presence: .2 }, { presence: 1.1 }, { x: -0.1 }, { y: 1.1 }, { x: NaN },
    ]) {
        const points = pose();
        Object.assign(points[11], changed);
        const sample = repeated({ faces: [], poses: [points] });
        assert.equal(sample.pose_detected, false);
        assert.equal(sample.shoulders_level, null);
        assert.equal(sample.upright_posture, null);
        assert.equal(sample.movement_score, null);
    }
});

test('a pose never fabricates face or head-direction observations', () => {
    const sample = repeated({ faces: null });
    assert.equal(sample.pose_detected, true);
    assert.equal(sample.face_detected, false);
    assert.equal(sample.face_count, null);
    assert.equal(sample.face_confidence, null);
    assert.equal(sample.camera_facing, null);
    assert.equal(sample.centered, null);
    assert.equal(sample.observation_status, 'measured');
});

test('shoulders alone cannot be interpreted as torso alignment', () => {
    const sample = repeated({ poses: [pose({ hips: false })] });
    assert.equal(sample.shoulders_level, true);
    assert.equal(sample.torso_visible, false);
    assert.equal(sample.upright_posture, null);
    const clipped = pose();
    clipped[24].visibility = .1;
    assert.equal(repeated({ poses: [clipped] }).upright_posture, null);
});

test('off-center framing does not change confidence or invent a poor posture', () => {
    const sample = repeated({ faces: [face({ box: { x: 10, y: 130, width: 200, height: 230 } })] });
    assert.equal(sample.centered, false);
    assert.equal(sample.framing_quality, false);
    assert.equal(sample.face_confidence, 95);
    assert.equal(sample.sample_quality, 90);
    assert.equal(sample.upright_posture, true);
});

test('multiple faces or poses invalidate all cues and reset temporal history', () => {
    for (const multiple of [{ faces: [face(), face()] }, { poses: [pose(), pose()] }]) {
        const state = {};
        repeated({}, 3, state);
        const ambiguous = evaluate(input({ ...multiple, capturedAtMs: 2500 }), state);
        assert.equal(ambiguous.observation_status, 'multiple_people');
        assert.equal(ambiguous.camera_facing, null);
        assert.equal(ambiguous.upright_posture, null);
        assert.equal(ambiguous.movement_score, null);
        assert.equal(ambiguous.sample_quality, 0);
        assert.equal(evaluate(input({ capturedAtMs: 3000 }), state).camera_facing, null);
    }
});

test('uncertain yaw and long capture gaps clear previously stable observations', () => {
    const state = {};
    repeated({}, 3, state);
    const uncertain = evaluate(input({ faces: [face({ noseX: 350 })], capturedAtMs: 2500 }), state);
    assert.equal(uncertain.camera_facing, null);
    assert.equal(evaluate(input({ capturedAtMs: 3000 }), state).camera_facing, null);
    repeated({}, 3, state);
    const afterPause = evaluate(input({ capturedAtMs: 5500 }), state);
    assert.equal(afterPause.camera_facing, null);
    assert.equal(afterPause.shoulders_level, null);
    assert.equal(afterPause.upright_posture, null);
    assert.equal(afterPause.movement_score, null);
});

test('pose angles use pixel aspect ratio on wide and tall frames', () => {
    for (const height of [480, 1280]) {
        const sample = repeated({ height, poses: [pose({ height, roll: 50 })], faces: [] });
        assert.equal(sample.shoulders_level, false, 'the same physical shoulder angle must not change with frame height');
    }
});

test('movement is normalized by time and body size and resets after lost tracking', () => {
    const moving = (step, interval, scale = 1) => {
        const state = {};
        let sample;
        for (let i = 0; i < 5; i++) {
            const points = pose({ dx: i * step });
            for (const p of points.filter(Boolean)) { p.x *= scale; p.y *= scale; }
            sample = evaluate(input({ faces: [], poses: [points], width: 640, height: 480, capturedAtMs: 1000 + i * interval }), state);
        }
        return sample;
    };
    assert.ok(Math.abs(moving(10, 500).movement_score - moving(20, 1000).movement_score) <= 1);
    assert.ok(Math.abs(moving(10, 500).movement_score - moving(10, 500, .75).movement_score) <= 1);
    const state = {};
    repeated({}, 4, state);
    evaluate(input({ faces: [], poses: [], capturedAtMs: 3000 }), state);
    assert.equal(evaluate(input({ capturedAtMs: 3500 }), state).movement_score, null);
});

function harness({ faceResult = async () => [face()] } = {}) {
    let clock = 1000;
    const captures = [], detectorInputs = [];
    const canvas = { width: 0, height: 0, getContext: () => ({ drawImage(video) { captures.push(video.currentTime); canvas.frame = video.currentTime; } }) };
    const video = { srcObject: {}, readyState: 2, paused: false, ended: false, videoWidth: 640, videoHeight: 480, currentTime: 1 };
    const config = {
        faceReady: true, faceApi: {
            TinyFaceDetectorOptions: class {},
            detectAllFaces(frame) { detectorInputs.push(frame); return { withFaceLandmarks: () => faceResult(frame) }; },
        },
        poseLandmarker: { detectForVideo(frame, timestamp) { detectorInputs.push(frame); assert.equal(timestamp, clock); return { landmarks: [pose()] }; } },
        questionKey: 'question-1', current: () => true, atSeconds: 1,
    };
    const tracker = createTracker({ now: () => clock, createCanvas: () => canvas });
    return { video, config, tracker, captures, detectorInputs, advance(ms = 500) { clock += ms; video.currentTime += ms / 1000; } };
}

test('both models inspect one frozen canvas and publish complete provenance', async () => {
    const h = harness();
    const sample = await h.tracker.sample(h.video, h.config);
    assert.equal(h.captures.length, 1);
    assert.equal(h.detectorInputs[0], h.detectorInputs[1]);
    assert.notEqual(h.detectorInputs[0], h.video);
    assert.equal(sample.sample_index, 1);
    assert.equal(sample.frame_time_ms, 1000);
    assert.equal(sample.captured_at_ms, 1000);
    assert.equal(sample.detection_age_ms, 0);
    assert.equal(sample.face_count, 1);
    assert.equal(sample.pose_count, 1);
    assert.match(sample.capture_session_id, /^[A-Za-z0-9_-]{1,64}$/);
});

test('duplicate video frames are rejected independently from the sampling clock', async () => {
    const h = harness();
    await h.tracker.sample(h.video, h.config);
    h.advance();
    h.video.currentTime = 1;
    assert.equal(await h.tracker.sample(h.video, h.config), null);
    assert.equal(h.captures.length, 1);
});

test('unavailable, paused, or camera-off sources never invoke detection or canvas creation', async () => {
    for (const condition of ['off', 'paused', 'no-models', 'not-current', 'not-ready']) {
        const h = harness();
        if (condition === 'off') h.video.srcObject = null;
        if (condition === 'paused') h.video.paused = true;
        if (condition === 'no-models') { h.config.faceReady = false; h.config.poseLandmarker = null; }
        if (condition === 'not-current') h.config.current = () => false;
        if (condition === 'not-ready') h.video.readyState = 1;
        assert.equal(await h.tracker.sample(h.video, h.config), null);
        assert.equal(h.captures.length, 0);
        assert.equal(h.detectorInputs.length, 0);
    }
});

test('pending detection cannot overwrite its frozen frame and stale work is discarded', async () => {
    let resolveFace;
    const h = harness({ faceResult: () => new Promise(resolve => { resolveFace = resolve; }) });
    const pending = h.tracker.sample(h.video, h.config);
    h.advance(1600);
    assert.equal(await h.tracker.sample(h.video, h.config), null);
    assert.equal(h.captures.length, 1);
    resolveFace([face()]);
    assert.equal(await pending, null);
});

test('question cancellation and stream replacement discard async observations', async () => {
    for (const reason of ['question', 'stream']) {
        let resolveFace;
        const h = harness({ faceResult: () => new Promise(resolve => { resolveFace = resolve; }) });
        const pending = h.tracker.sample(h.video, h.config);
        if (reason === 'question') h.config.current = () => false;
        else h.video.srcObject = {};
        resolveFace([face()]);
        assert.equal(await pending, null);
    }
});

test('a new question resets calibration while keeping sample identity monotonic', async () => {
    const h = harness();
    for (let i = 0; i < 3; i++) { await h.tracker.sample(h.video, h.config); h.advance(); }
    h.config.questionKey = 'question-2';
    const sample = await h.tracker.sample(h.video, h.config);
    assert.equal(sample.sample_index, 4);
    assert.equal(sample.camera_facing, null);
    assert.equal(sample.shoulders_level, null);
    assert.equal(sample.movement_score, null);
});

test('model exceptions remain unknown instead of generating a favorable estimate', async () => {
    const h = harness({ faceResult: async () => { throw new Error('model unavailable'); } });
    h.config.poseLandmarker.detectForVideo = () => { throw new Error('model unavailable'); };
    const sample = await h.tracker.sample(h.video, h.config);
    assert.equal(sample.observation_status, 'model_unavailable');
    assert.equal(sample.face_count, null);
    assert.equal(sample.pose_count, null);
    assert.equal(sample.sample_quality, 0);
});

test('stream restarts and fresh browser lifetimes namespace resetting media timestamps', async () => {
    const h = harness();
    const initial = await h.tracker.sample(h.video, h.config);
    h.advance();
    h.video.srcObject = {};
    h.video.currentTime = 0;
    const restarted = await h.tracker.sample(h.video, h.config);
    assert.equal(restarted.frame_time_ms, 0);
    assert.notEqual(restarted.capture_session_id, initial.capture_session_id);
    assert.equal(restarted.camera_facing, null);
    const fresh = harness();
    const reloaded = await fresh.tracker.sample(fresh.video, fresh.config);
    assert.notEqual(reloaded.capture_session_id, initial.capture_session_id);
    assert.equal(reloaded.sample_index, initial.sample_index);
    assert.equal(reloaded.captured_at_ms, initial.captured_at_ms);
});

test('live labels describe measurements and can clear all stale values', () => {
    const labels = {};
    render(repeated({ faces: [face({ noseX: 370 })] }), (id, text) => { labels[id] = text; });
    assert.equal(labels.stPosture, 'Sideways head turn estimated');
    assert.doesNotMatch(Object.values(labels).join(' '), /confidence|eye contact|nervous|emotion|personality/i);
    renderUnavailable((id, text) => { labels[id] = text; });
    assert.ok(Object.values(labels).every(value => value === 'Not measured / checking'));
});
