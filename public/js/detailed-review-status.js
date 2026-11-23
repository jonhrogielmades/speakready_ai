// Detailed Review provider progress and saved local total score: 2026-11-23.
(function () {
    'use strict';

    function startReviewStatus() {
        var panel = document.querySelector('[data-detailed-review-status]');
        if (!panel || panel.dataset.reviewStatusBound === 'true') return;
        panel.dataset.reviewStatusBound = 'true';

        var title = panel.querySelector('[data-review-status-title]');
        var message = panel.querySelector('[data-review-status-message]');
        var reload = panel.querySelector('[data-review-reload]');
        var answerCount = panel.querySelector('[data-review-answer-count]');
        var answerTrack = panel.querySelector('[data-review-answer-track]');
        var answerBar = panel.querySelector('[data-review-answer-bar]');
        var summaryStage = panel.querySelector('[data-review-summary-stage]');
        var statusUrl;
        var timer = null;
        var request = null;
        var generation = 0;
        var stopped = false;
        var attempts = 0;
        var failures = 0;
        var startedAt = Date.now();
        var maximumWait = 600000;
        var maximumAttempts = 300;
        var nextDelay = 2000;

        function setText(heading, detail) {
            if (title) title.textContent = heading;
            if (message) message.textContent = detail;
        }

        function updateProgress(result) {
            var progress = result.provider_progress;
            if (progress && typeof progress === 'object') {
                var total = Number(progress.total);
                var verified = Number(progress.verified);
                if (Number.isSafeInteger(total) && total >= 0 && Number.isSafeInteger(verified) && verified >= 0) {
                    verified = Math.min(verified, total);
                    if (answerCount) answerCount.textContent = verified + ' of ' + total + ' ready';
                    if (answerTrack) {
                        answerTrack.setAttribute('aria-valuemax', String(Math.max(1, total)));
                        answerTrack.setAttribute('aria-valuenow', String(verified));
                    }
                    if (answerBar) answerBar.style.width = (total > 0 ? Math.round(verified * 100 / total) : 0) + '%';
                }
            }
            if (summaryStage) {
                var ready = result.status === 'verified' || result.summary_preview_ready === true;
                summaryStage.textContent = 'Overall AI summary: ' + (ready ? 'ready' : result.status === 'failed' ? 'unavailable right now' : 'being prepared');
            }
        }

        function showPanel() {
            panel.hidden = false;
            panel.setAttribute('aria-hidden', 'false');
        }

        function hidePanel() {
            panel.hidden = true;
            panel.setAttribute('aria-hidden', 'true');
            if (reload) reload.hidden = true;
        }

        function pause() {
            generation += 1;
            if (timer !== null) window.clearTimeout(timer);
            timer = null;
            if (request) {
                window.clearTimeout(request.timeout);
                request.controller.abort();
                request = null;
            }
        }

        function stop() {
            stopped = true;
            pause();
        }

        function showFailedProvider(detail) {
            showPanel();
            setText(
                panel.dataset.summaryPreviewReady === 'true'
                    ? 'Provider AI session summary is available below.'
                    : 'AI provider review is unavailable right now.',
                detail || 'The AI provider review could not be completed right now. Local feedback remains available below.'
            );
            if (reload) reload.hidden = true;
            nextDelay = 15000;
        }

        function offerReload(detail) {
            stop();
            showPanel();
            setText('Your AI review is still being checked.', detail);
            if (reload) {
                reload.textContent = 'Check review status';
                reload.hidden = false;
            }
        }

        if (reload) reload.addEventListener('click', function () { window.location.reload(); });

        try {
            statusUrl = new URL(panel.dataset.reviewStatusUrl, window.location.href);
            if (statusUrl.origin !== window.location.origin) throw new Error('Invalid status URL');
        } catch (_) {
            offerReload('Automatic updates are unavailable. Reload to check your saved review.');
            return;
        }

        function canPoll() {
            return !stopped && !document.hidden && window.navigator.onLine !== false;
        }

        function exhausted() {
            if (Date.now() - startedAt < maximumWait && attempts < maximumAttempts) return false;
            offerReload('Your responses are saved. Reload to check the latest provider review status.');
            return true;
        }

        function schedule(delay) {
            if (!canPoll() || request || timer !== null || exhausted()) return;
            var remaining = maximumWait - (Date.now() - startedAt);
            timer = window.setTimeout(function () {
                timer = null;
                poll();
            }, Math.min(delay, remaining));
        }

        function reloadReadyReview() {
            stop();
            // Persist the guard before reload, including when storage is disabled.
            // A stale cached page must never cause an automatic reload loop.
            var key = 'speakready:review-ready:' + statusUrl.pathname;
            var seen = false;
            var persisted = false;
            var now = Date.now();
            function recent(value) {
                return Number.isFinite(Number(value)) && Number(value) > 0 && now - Number(value) < maximumWait;
            }
            try {
                seen = recent(window.sessionStorage.getItem(key));
                window.sessionStorage.setItem(key, String(now));
                persisted = true;
            } catch (_) { /* History state also survives a same-page reload. */ }
            try {
                var state = window.history.state || {};
                seen = seen || recent(state[key]);
                var updated = Object.assign({}, state);
                updated[key] = now;
                window.history.replaceState(updated, '', window.location.href);
                persisted = true;
            } catch (_) { /* A manual reload is safe if both storage methods fail. */ }
            if (seen || !persisted) {
                showPanel();
                setText('Your AI reviews are ready.', 'Reload this page to display the latest saved report.');
                if (reload) reload.hidden = false;
                return;
            }
            hidePanel();
            setText('Your AI reviews are ready.', 'Updating your report…');
            window.location.reload();
        }

        function reloadNewSessionSummary() {
            if (panel.dataset.summaryPreviewReady === 'true') return false;

            var key = 'speakready:summary-preview-ready:' + statusUrl.pathname;
            var seen = false;
            var persisted = false;
            var now = Date.now();
            function recent(value) {
                return Number.isFinite(Number(value)) && Number(value) > 0 && now - Number(value) < maximumWait;
            }
            try {
                seen = recent(window.sessionStorage.getItem(key));
                window.sessionStorage.setItem(key, String(now));
                persisted = true;
            } catch (_) { /* History state can guard the reload instead. */ }
            try {
                var state = window.history.state || {};
                seen = seen || recent(state[key]);
                var updated = Object.assign({}, state);
                updated[key] = now;
                window.history.replaceState(updated, '', window.location.href);
                persisted = true;
            } catch (_) { /* Offer a manual reload if no guard can be saved. */ }

            stop();
            if (seen || !persisted) {
                showPanel();
                setText('AI session summary is ready.', 'Reload this page to display the new provider summary.');
                if (reload) {
                    reload.textContent = 'Show AI session summary';
                    reload.hidden = false;
                }
            } else {
                window.location.reload();
            }
            return true;
        }

        function applyStatus(result) {
            if (!result || !['pending', 'processing', 'verified', 'failed'].includes(result.status)) {
                throw new Error('Invalid review status');
            }
            failures = 0;
            updateProgress(result);
            var detail = typeof result.message === 'string' ? result.message.trim().slice(0, 500) : '';
            if (result.status === 'verified') {
                reloadReadyReview();
            } else if (result.summary_preview_ready === true) {
                if (reloadNewSessionSummary()) return;
                showPanel();
                setText('Provider AI session summary is available below.', detail || 'The remaining review details are still being prepared.');
                nextDelay = result.status === 'failed' ? 15000 : 2000;
            } else if (result.status === 'failed') {
                showFailedProvider(detail);
            } else {
                showPanel();
                setText('AI provider analysis is in progress.', detail || 'Your interview is complete. This page updates when provider feedback passes validation.');
                var delay = Number(result.retry_after_ms);
                nextDelay = Number.isFinite(delay) ? Math.max(2000, Math.min(15000, delay)) : 2000;
            }
        }

        async function poll() {
            if (!canPoll() || request || exhausted()) return;
            var currentGeneration = generation;
            var active = { controller: new AbortController(), timeout: null };
            request = active;
            attempts += 1;
            active.timeout = window.setTimeout(function () { active.controller.abort(); }, 15000);
            try {
                var response = await window.fetch(statusUrl.href, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { Accept: 'application/json' },
                    signal: active.controller.signal
                });
                if (!response.ok) throw new Error('Status request failed');
                var result = await response.json();
                if (currentGeneration !== generation || !canPoll()) return;
                applyStatus(result);
            } catch (_) {
                if (currentGeneration !== generation || !canPoll()) return;
                failures += 1;
                if (failures >= 3) {
                    offerReload('We could not check automatically. Your responses are saved; reload to check the review status.');
                } else {
                    nextDelay = 5000 * failures;
                    setText('Checking your AI reviews…', 'The connection was interrupted. Automatic checking will try again shortly.');
                }
            } finally {
                window.clearTimeout(active.timeout);
                if (request === active) request = null;
                if (currentGeneration === generation) schedule(nextDelay);
            }
        }

        function resume() {
            if (stopped) return;
            if (!canPoll()) {
                pause();
                if (window.navigator.onLine === false) {
                    setText('Waiting for your connection…', 'Automatic review updates will continue when you are back online. Your responses are saved.');
                }
                return;
            }
            schedule(0);
        }

        if (panel.dataset.reviewStatus === 'failed') {
            var initialDetail = message ? message.textContent.trim() : '';
            showFailedProvider(initialDetail);
        } else {
            showPanel();
        }
        document.addEventListener('visibilitychange', resume);
        window.addEventListener('online', resume);
        window.addEventListener('offline', resume);
        window.addEventListener('pagehide', stop, { once: true });
        resume();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startReviewStatus, { once: true });
    } else {
        startReviewStatus();
    }
}());
