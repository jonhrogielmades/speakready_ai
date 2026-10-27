import assert from 'node:assert/strict';
import test from 'node:test';

await import('../../public/js/media-permission-support.js');

const support = globalThis.SpeakReadyMediaPermissions;

function namedError(name) {
    const error = new Error(name);
    error.name = name;
    return error;
}

test('builds portable camera and microphone constraints', () => {
    assert.deepEqual(support.constraintsFor(['camera', 'microphone']), {
        video: { facingMode: { ideal: 'user' } },
        audio: { echoCancellation: true, noiseSuppression: true },
    });
});

test('stops every media track after a successful permission probe', async () => {
    let audioStopped = false;
    let videoStopped = false;
    const result = await support.probe('camera', {
        isSecureContext: true,
        documentRef: {},
        navigatorRef: {
            mediaDevices: {
                getUserMedia: async () => ({
                    getTracks: () => [
                        { stop: () => { audioStopped = true; } },
                        { stop: () => { videoStopped = true; } },
                    ],
                }),
            },
        },
    });

    assert.equal(result.status, 'granted');
    assert.equal(audioStopped, true);
    assert.equal(videoStopped, true);
});

test('requests camera and microphone together from one user gesture', async () => {
    let receivedConstraints = null;
    let stoppedTracks = 0;
    const result = await support.probeTools(['camera', 'microphone'], {
        isSecureContext: true,
        documentRef: {},
        navigatorRef: {
            mediaDevices: {
                getUserMedia: async (constraints) => {
                    receivedConstraints = constraints;

                    return {
                        getTracks: () => [
                            { stop: () => { stoppedTracks += 1; } },
                            { stop: () => { stoppedTracks += 1; } },
                        ],
                    };
                },
            },
        },
    });

    assert.equal(result.status, 'granted');
    assert.deepEqual(receivedConstraints, {
        video: { facingMode: { ideal: 'user' } },
        audio: { echoCancellation: true, noiseSuppression: true },
    });
    assert.equal(stoppedTracks, 2);
});

test('reports the exact media tool blocked by Permissions-Policy', async () => {
    const result = await support.probeTools(['camera', 'microphone'], {
        isSecureContext: true,
        documentRef: {
            permissionsPolicy: {
                allowsFeature: (tool) => tool !== 'camera',
            },
        },
        navigatorRef: { mediaDevices: { getUserMedia: async () => { throw new Error('must not run'); } } },
    });

    assert.equal(result.reason, 'policy_blocked');
    assert.deepEqual(result.policyBlockedTools, ['camera']);
});

test('blocks media when the response Permissions-Policy disallows it', async () => {
    const result = await support.probe('camera', {
        isSecureContext: true,
        documentRef: { permissionsPolicy: { allowsFeature: () => false } },
        navigatorRef: { mediaDevices: { getUserMedia: async () => { throw new Error('must not run'); } } },
    });

    assert.deepEqual({ status: result.status, reason: result.reason }, {
        status: 'policy_blocked',
        reason: 'policy_blocked',
    });
});

test('requires HTTPS and a supported media API', async () => {
    const insecure = await support.probe('microphone', {
        isSecureContext: false,
        documentRef: {},
        navigatorRef: {},
    });
    const unsupported = await support.probe('microphone', {
        isSecureContext: true,
        documentRef: {},
        navigatorRef: {},
    });

    assert.equal(insecure.reason, 'insecure_context');
    assert.equal(unsupported.reason, 'unsupported');
});

for (const [name, reason] of [
    ['NotAllowedError', 'blocked'],
    ['SecurityError', 'blocked'],
    ['NotFoundError', 'not_found'],
    ['OverconstrainedError', 'not_found'],
    ['NotReadableError', 'busy'],
    ['AbortError', 'busy'],
]) {
    test(`classifies ${name} as ${reason}`, async () => {
        const result = await support.probe('camera', {
            isSecureContext: true,
            documentRef: {},
            navigatorRef: {
                mediaDevices: {
                    getUserMedia: async () => { throw namedError(name); },
                },
            },
        });

        assert.equal(result.status, name);
        assert.equal(result.reason, reason);
    });
}
