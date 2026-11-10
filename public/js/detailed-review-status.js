(function () {
    'use strict';

    function startReviewStatus() {
        var panel = document.querySelector('[data-detailed-review-status]');
        if (!panel || panel.dataset.reviewStatusBound === 'true') return;
        panel.dataset.reviewStatusBound = 'true';

        var title = panel.querySelector('[data-review-status-title]');
        var message = panel.querySelector('[data-review-status-message]');
        var retry = panel.querySelector('[data-review-retry]');
        var reload = panel.querySelector('[data-review-reload]');
        var statusUrl;
        var timer = null;
        var request = null;
        var generation = 0;
        var stopped = false;
        var attempts = 0;
        var failures = 0;
        var startedAt = Date.now();
        var maximumWait = 300000;
        var maximumAttempts = 60;
        var nextDelay = 5000;

        function setText(heading, detail) {
            if (title) title.textContent = heading;
            if (message) message.textContent = detail;
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

        function offerRetry(detail, heading) {
            stop();
            setText(heading || 'Your AI answer reviews need another attempt.', detail);
            if (retry) retry.hidden = false;
        }

        try {
            statusUrl = new URL(panel.dataset.reviewStatusUrl, window.location.href);
            if (statusUrl.origin !== window.location.origin) throw new Error('Invalid status URL');
        } catch (_) {
            offerRetry('Automatic updates are unavailable. You can request another attempt below.');
            return;
        }

        function canPoll() {
            return !stopped && !document.hidden && window.navigator.onLine !== false;
        }

        function exhausted() {
            if (Date.now() - startedAt < maximumWait && attempts < maximumAttempts) return false;
            offerRetry('Your reviews are taking longer than expected. Your responses are saved; you can request another attempt below.');
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
                setText('Your AI reviews are ready.', 'Reload this page to display the latest saved report.');
                if (reload) reload.hidden = false;
                return;
            }
            setText('Your AI reviews are ready.', 'Updating your report…');
            window.location.reload();
        }

        function applyStatus(result) {
            if (!result || !['processing', 'waiting', 'ready', 'unavailable', 'failed'].includes(result.status)) {
                throw new Error('Invalid review status');
            }
            failures = 0;
            var detail = typeof result.message === 'string' ? result.message.trim().slice(0, 500) : '';
            if (result.status === 'ready') {
                reloadReadyReview();
            } else if (result.status === 'failed' || result.status === 'unavailable') {
                offerRetry(detail || 'Some questions are still waiting for a validated AI review. You can request another attempt below.');
            } else {
                setText('Your AI answer reviews are being prepared.', detail || 'This page updates automatically when the reviews are ready. You can keep reading your saved responses below.');
                var delay = Number(result.retry_after_ms);
                nextDelay = Number.isFinite(delay) ? Math.max(2000, Math.min(15000, delay)) : 5000;
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
                    offerRetry('We could not check your AI reviews automatically. Your responses are saved; you can request another attempt below.');
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

        if (['failed', 'unavailable'].includes(panel.dataset.reviewStatus)) {
            offerRetry('Some questions are still waiting for a validated AI review. You can request another attempt below.');
            return;
        }
        if (retry) retry.hidden = true;
        if (reload) reload.addEventListener('click', function () { window.location.reload(); });
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
