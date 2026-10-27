(function (root) {
    'use strict';

    function featureAllowed(documentRef, tool) {
        const policy = documentRef && (documentRef.permissionsPolicy || documentRef.featurePolicy);
        if (!policy || typeof policy.allowsFeature !== 'function') return true;

        try {
            return policy.allowsFeature(tool);
        } catch (error) {
            return true;
        }
    }

    function issueReason(error) {
        const name = error && error.name ? error.name : '';
        if (name === 'NotFoundError' || name === 'OverconstrainedError') return 'not_found';
        if (name === 'NotReadableError' || name === 'AbortError') return 'busy';
        if (name === 'NotAllowedError' || name === 'SecurityError') return 'blocked';

        return 'unavailable';
    }

    function constraintsFor(tools) {
        const requested = Array.isArray(tools) ? tools : [tools];
        const constraints = {};

        if (requested.includes('camera')) {
            constraints.video = { facingMode: { ideal: 'user' } };
        }
        if (requested.includes('microphone')) {
            constraints.audio = { echoCancellation: true, noiseSuppression: true };
        }

        return constraints;
    }

    function normalizeTools(tools) {
        const requested = Array.isArray(tools) ? tools : [tools];

        return [...new Set(requested)].filter((tool) => tool === 'camera' || tool === 'microphone');
    }

    async function probeTools(tools, options) {
        const settings = options || {};
        const navigatorRef = settings.navigatorRef || root.navigator;
        const documentRef = settings.documentRef || root.document;
        const secure = settings.isSecureContext !== undefined
            ? settings.isSecureContext
            : root.isSecureContext;
        const requestedTools = normalizeTools(tools);
        const policyBlockedTools = requestedTools.filter((tool) => !featureAllowed(documentRef, tool));

        if (policyBlockedTools.length) {
            return {
                status: 'policy_blocked',
                reason: 'policy_blocked',
                error: null,
                requestedTools,
                policyBlockedTools,
            };
        }

        if (secure === false) {
            return { status: 'insecure_context', reason: 'insecure_context', error: null, requestedTools };
        }

        if (!navigatorRef || !navigatorRef.mediaDevices || typeof navigatorRef.mediaDevices.getUserMedia !== 'function') {
            return { status: 'unsupported', reason: 'unsupported', error: null, requestedTools };
        }

        try {
            const stream = await navigatorRef.mediaDevices.getUserMedia(constraintsFor(requestedTools));
            const tracks = stream && typeof stream.getTracks === 'function' ? stream.getTracks() : [];
            tracks.forEach((track) => {
                if (track && typeof track.stop === 'function') track.stop();
            });

            return { status: 'granted', reason: null, error: null, requestedTools };
        } catch (error) {
            return {
                status: error && error.name ? error.name : 'error',
                reason: issueReason(error),
                error,
                requestedTools,
            };
        }
    }

    async function probe(tool, options) {
        return probeTools([tool], options);
    }

    root.SpeakReadyMediaPermissions = Object.freeze({
        constraintsFor,
        featureAllowed,
        issueReason,
        probe,
        probeTools,
    });
})(typeof globalThis !== 'undefined' ? globalThis : window);
