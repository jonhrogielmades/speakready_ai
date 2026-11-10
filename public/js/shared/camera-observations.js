(function (root) {
    'use strict';

    const MIN_FACE = 0.75;
    const MIN_POSE = 0.65;
    const finite = value => typeof value === 'number' && Number.isFinite(value);
    const percentage = value => Math.round(Math.max(0, Math.min(1, value)) * 100);
    const mean = points => ({ x: points.reduce((sum, p) => sum + p.x, 0) / points.length, y: points.reduce((sum, p) => sum + p.y, 0) / points.length });
    const distance = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
    const median = values => [...values].sort((a, b) => a - b)[Math.floor(values.length / 2)];

    function landmark(point) {
        return Boolean(point && finite(point.x) && finite(point.y) && point.x >= 0 && point.x <= 1 && point.y >= 0 && point.y <= 1
            && finite(point.visibility) && point.visibility >= MIN_POSE && point.visibility <= 1
            && (point.presence === undefined || (finite(point.presence) && point.presence >= MIN_POSE && point.presence <= 1)));
    }

    function stable(state, name, value) {
        if (value === null) {
            delete state.cues[name];
            return null;
        }
        const previous = state.cues[name];
        const count = previous && previous.value === value ? previous.count + 1 : 1;
        state.cues[name] = { value, count };
        return count >= 3 ? value : null;
    }

    function threshold(value, low, high) {
        return value <= low ? true : (value >= high ? false : null);
    }

    function evaluate(input, state = {}) {
        if (finite(state.lastCapture) && (!finite(input.capturedAtMs) || input.capturedAtMs <= state.lastCapture || input.capturedAtMs - state.lastCapture > 2000)) {
            state.cues = {};
            state.previous = null;
            state.movement = [];
        }
        state.lastCapture = input.capturedAtMs;
        state.cues ||= {};
        state.movement ||= [];
        const { faces, poses, width, height, capturedAtMs } = input;
        const faceCount = Array.isArray(faces) ? faces.length : null;
        const poseCount = Array.isArray(poses) ? poses.length : null;
        const multiple = faceCount > 1 || poseCount > 1;
        const sample = {
            face_count: faceCount, pose_count: poseCount, face_detected: false, face_confidence: null,
            face_size_percent: null, face_size_status: null, camera_facing: null, camera_facing_score: null,
            centered: null, framing_quality: null, pose_detected: false, pose_confidence: null,
            shoulders_visible: false, torso_visible: false, shoulders_level: null, upright_posture: null,
            movement_score: null, high_movement: null, sample_quality: 0, observation_status: 'not_visible',
        };
        if (multiple || !finite(width) || !finite(height) || width <= 0 || height <= 0) {
            state.cues = {};
            state.previous = null;
            state.movement = [];
            sample.observation_status = multiple ? 'multiple_people' : 'model_unavailable';
            return sample;
        }

        let facing = null;
        const face = faces?.[0];
        const confidence = face?.detection?.score;
        const box = face?.detection?.box;
        if (finite(confidence) && confidence >= 0 && confidence <= 1) sample.face_confidence = percentage(confidence);
        if (finite(confidence) && confidence >= MIN_FACE && confidence <= 1 && box && [box.x, box.y, box.width, box.height].every(finite)
            && box.width >= 32 && box.height >= 32 && box.x >= 0 && box.y >= 0
            && box.x + box.width <= width && box.y + box.height <= height) {
            sample.face_detected = true;
            const size = box.width * box.height / (width * height) * 100;
            sample.face_size_percent = Math.round(size * 10) / 10;
            sample.face_size_status = size < 3 ? 'too_far' : (size > 42 ? 'too_close' : 'good');
            sample.centered = Math.abs(box.x + box.width / 2 - width / 2) <= width * 0.25
                && Math.abs(box.y + box.height / 2 - height / 2) <= height * 0.30;
            sample.framing_quality = sample.centered && sample.face_size_status === 'good';
            const left = face.landmarks?.getLeftEye?.();
            const right = face.landmarks?.getRightEye?.();
            const nose = face.landmarks?.getNose?.();
            const validPoints = points => Array.isArray(points) && points.length >= 3
                && points.every(p => p && finite(p.x) && finite(p.y) && p.x >= box.x && p.x <= box.x + box.width && p.y >= box.y && p.y <= box.y + box.height);
            if (sample.face_size_status === 'good' && validPoints(left) && validPoints(right) && validPoints(nose)) {
                const l = mean(left), r = mean(right), middle = mean([l, r]);
                const eyeDistance = distance(l, r);
                if (eyeDistance >= 12 && eyeDistance <= box.width * 0.9) {
                    const tip = nose[3] || nose[nose.length - 1];
                    // Projection onto the eye axis avoids interpreting camera/head roll as yaw.
                    const offset = Math.abs(((tip.x - middle.x) * (r.x - l.x) + (tip.y - middle.y) * (r.y - l.y)) / (eyeDistance * eyeDistance));
                    sample.camera_facing_score = percentage(1 - Math.min(offset / 0.7, 1));
                    facing = threshold(offset, 0.22, 0.38);
                }
            }
        }
        sample.camera_facing = stable(state, 'facing', facing);

        const pose = poses?.[0];
        const shoulders = pose && [pose[11], pose[12]];
        const hips = pose && [pose[23], pose[24]];
        let level = null, upright = null;
        if (shoulders?.every(landmark)) {
            const pixels = points => points.map(p => ({ x: p.x * width, y: p.y * height }));
            const shoulderPixels = pixels(shoulders);
            const span = distance(...shoulderPixels);
            if (span >= 35 && span <= width * 0.9) {
                sample.pose_detected = true;
                sample.shoulders_visible = true;
                sample.pose_confidence = percentage(Math.min(...shoulders.map(p => Math.min(p.visibility, p.presence ?? 1))));
                const shoulderCenter = mean(shoulderPixels);
                const roll = Math.atan2(Math.abs(shoulderPixels[0].y - shoulderPixels[1].y), Math.abs(shoulderPixels[0].x - shoulderPixels[1].x)) * 180 / Math.PI;
                level = threshold(roll, 8, 12);
                if (hips.every(landmark)) {
                    const hipCenter = mean(pixels(hips));
                    const torsoHeight = hipCenter.y - shoulderCenter.y;
                    if (torsoHeight >= 35 && distance(...pixels(hips)) >= 20) {
                        sample.torso_visible = true;
                        const lean = Math.atan2(Math.abs(hipCenter.x - shoulderCenter.x), torsoHeight) * 180 / Math.PI;
                        upright = threshold(lean, 10, 15);
                        sample.pose_confidence = Math.min(sample.pose_confidence, percentage(Math.min(...hips.map(p => Math.min(p.visibility, p.presence ?? 1)))));
                    }
                }
                const previous = state.previous;
                const dt = previous ? (capturedAtMs - previous.at) / 1000 : 0;
                if (previous && dt >= 0.15 && dt <= 2 && span / previous.span >= 0.7 && span / previous.span <= 1.4) {
                    const velocity = Math.max(0, distance(shoulderCenter, previous.center) - 2) / ((span + previous.span) / 2) / dt;
                    state.movement.push(Math.min(100, Math.round(velocity * 80)));
                    state.movement = state.movement.slice(-3);
                    if (state.movement.length === 3) {
                        sample.movement_score = median(state.movement);
                        sample.high_movement = sample.movement_score >= 45;
                    }
                } else {
                    state.movement = [];
                }
                state.previous = { at: capturedAtMs, center: shoulderCenter, span };
            }
        }
        if (!sample.pose_detected) {
            state.previous = null;
            state.movement = [];
        }
        sample.shoulders_level = stable(state, 'shoulders', level);
        sample.upright_posture = stable(state, 'upright', upright);
        const available = [sample.face_detected ? sample.face_confidence : null, sample.pose_detected ? sample.pose_confidence : null].filter(finite);
        sample.sample_quality = available.length ? Math.min(...available) : 0;
        sample.observation_status = available.length
            ? ([sample.camera_facing, sample.shoulders_level, sample.upright_posture, sample.movement_score].some(v => v !== null) ? 'measured' : 'calibrating')
            : (faceCount === null && poseCount === null ? 'model_unavailable' : (faceCount || poseCount ? 'low_confidence' : 'not_visible'));
        return sample;
    }

    function createTracker(options = {}) {
        let pending = false, lastAttempt = -Infinity, lastFrame = null, stream = null, question = null, sequence = 0, state = {};
        let captureSessionId = null;
        let canvas;
        const now = options.now || (() => performance.now());
        const newCaptureSessionId = options.newCaptureSessionId || (() => root.crypto.randomUUID?.()
            || Array.from(root.crypto.getRandomValues(new Uint32Array(4)), part => part.toString(16).padStart(8, '0')).join('-'));
        return {
            async sample(video, config) {
                const capturedAt = now();
                if (pending || capturedAt - lastAttempt < 250 || !video?.srcObject || video.readyState < 2 || video.paused || video.ended
                    || !video.videoWidth || !video.videoHeight || !finite(video.currentTime) || config.current?.() === false) return null;
                if (stream !== video.srcObject || question !== config.questionKey) {
                    if (stream !== video.srcObject) captureSessionId = newCaptureSessionId();
                    state = {}; lastFrame = null; stream = video.srcObject; question = config.questionKey;
                }
                const frameTime = Math.round(video.currentTime * 1000);
                if (lastFrame !== null && frameTime <= lastFrame) return null;
                if (!config.faceReady && !config.poseLandmarker) return null;
                lastAttempt = capturedAt; lastFrame = frameTime; pending = true;
                const captureStream = stream;
                try {
                    canvas ||= options.createCanvas?.() || document.createElement('canvas');
                    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    let faces = null, poses = null;
                    if (config.poseLandmarker) {
                        try { poses = config.poseLandmarker.detectForVideo(canvas, capturedAt)?.landmarks ?? []; } catch (_) { poses = null; }
                    }
                    if (config.faceReady && config.faceApi) {
                        try {
                            faces = await config.faceApi.detectAllFaces(canvas, new config.faceApi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.60 })).withFaceLandmarks();
                        } catch (_) { faces = null; }
                    }
                    const age = now() - capturedAt;
                    if (age > 1500 || age < 0 || config.current?.() === false || video.srcObject !== captureStream) {
                        state = {}; return null;
                    }
                    return { ...evaluate({ faces, poses, width: canvas.width, height: canvas.height, capturedAtMs: capturedAt }, state),
                        capture_session_id: captureSessionId, sample_index: ++sequence, captured_at_ms: Math.round(capturedAt), frame_time_ms: frameTime,
                        detection_age_ms: Math.round(age), at_seconds: Math.max(0, Number(config.atSeconds) || 0) };
                } finally { pending = false; }
            },
        };
    }

    function render(sample, setStat) {
        const ambiguous = sample.observation_status === 'multiple_people';
        setStat('stEyeContact', ambiguous ? 'Multiple people — not measured' : (sample.face_detected ? (sample.framing_quality ? 'Face in frame' : 'Adjust camera framing') : 'Face not measured'), sample.framing_quality ? 'text-success' : 'text-secondary');
        setStat('stPosture', sample.camera_facing === null ? 'Not measured / checking' : (sample.camera_facing ? 'No sideways head turn detected' : 'Sideways head turn estimated'), 'text-secondary');
        setStat('stPose', sample.upright_posture === null ? (sample.shoulders_level === null ? 'Shoulders not measured' : 'Shoulders visible; torso not measured') : (sample.upright_posture ? 'Torso alignment observed' : 'Torso lean estimate'), 'text-secondary');
        setStat('stMovement', sample.movement_score === null ? 'Not measured / checking' : (sample.high_movement ? 'More movement observed' : 'Less movement observed'), 'text-secondary');
    }

    function renderUnavailable(setStat) {
        ['stEyeContact', 'stPosture', 'stPose', 'stMovement'].forEach(id => setStat(id, 'Not measured / checking', 'text-secondary'));
    }

    root.SpeakReadyCameraObservations = { createTracker, evaluate, render, renderUnavailable };
})(typeof window === 'undefined' ? globalThis : window);
