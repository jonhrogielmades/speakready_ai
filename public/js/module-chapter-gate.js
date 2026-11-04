(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const gate = document.querySelector('[data-chapter-gate]');
        if (!gate) return;

        const cards = Array.from(gate.querySelectorAll('[data-module-chapter-card]'));
        if (cards.length < 2) return;

        const delaySeconds = Math.max(1, Math.floor(Number(gate.dataset.unlockDelayMs || 300000) / 1000));
        let elapsedSeconds = Math.max(0, Number(gate.dataset.elapsedSeconds || 0));
        let unlocked = Math.min(cards.length, Math.max(1, Number(gate.dataset.initialUnlocked || 1)));
        let lastSyncAt = Date.now();
        let requestPending = false;
        let connectionFailed = false;
        let resumePending = false;

        function formatTime(seconds) {
            const remaining = Math.max(0, Math.ceil(seconds));
            return String(Math.floor(remaining / 60)).padStart(2, '0') + ':' + String(remaining % 60).padStart(2, '0');
        }

        function render() {
            const allUnlocked = unlocked >= cards.length;
            const preview = elapsedSeconds + (document.hidden || connectionFailed ? 0 : Math.min(9, Math.floor((Date.now() - lastSyncAt) / 1000)));
            const remaining = allUnlocked ? 0 : delaySeconds - (preview % delaySeconds);

            cards.forEach(function (card, index) {
                const locked = index >= unlocked;
                card.classList.toggle('is-chapter-locked', locked);
                card.classList.toggle('is-chapter-unlocked', !locked);
                card.setAttribute('aria-disabled', locked ? 'true' : 'false');
                const content = card.querySelector('[data-chapter-content]');
                const panel = card.querySelector('[data-chapter-lock-panel]');
                const badge = card.querySelector('[data-chapter-lock-badge]');
                if (content) content.hidden = locked;
                if (panel) panel.hidden = !locked;
                if (badge) badge.classList.toggle('is-unlocked', !locked);
                const icon = badge?.querySelector('i');
                if (icon) icon.className = 'fa-solid ' + (locked ? 'fa-lock' : 'fa-lock-open');
                const label = card.querySelector('[data-chapter-lock-label]');
                if (label) label.textContent = locked ? 'Locked' : 'Unlocked';
                const countdown = card.querySelector('[data-chapter-lock-countdown]');
                if (countdown && locked) countdown.textContent = index === unlocked ? 'Unlocks in ' + formatTime(remaining) : 'Unlocks after earlier chapters';
            });

            const progress = gate.querySelector('[data-chapter-gate-progress]');
            const next = gate.querySelector('[data-chapter-gate-next]');
            const track = gate.querySelector('[data-chapter-gate-track]');
            const message = gate.querySelector('[data-chapter-gate-message]');
            if (progress) progress.textContent = unlocked + ' of ' + cards.length + ' chapters unlocked';
            if (next) next.textContent = allUnlocked ? 'All unlocked' : connectionFailed ? 'Waiting to reconnect' : 'Next in ' + formatTime(remaining);
            if (track) track.style.setProperty('--chapter-gate-progress', (unlocked / cards.length * 100) + '%');
            if (message) message.textContent = allUnlocked ? 'All chapters unlocked.' : connectionFailed ? 'Reading time could not be saved. Reconnect to continue.' : 'Next chapter unlocks after 5:00 while this page is visible.';
            document.querySelectorAll('.module-progress-button-complete').forEach(function (button) {
                button.disabled = !allUnlocked;
                if (allUnlocked) button.removeAttribute('title');
                else button.setAttribute('title', 'Unlock all chapters before marking completed');
            });
        }

        async function pulse() {
            if (document.hidden || requestPending || unlocked >= cards.length) return;
            requestPending = true;
            const resume = resumePending;
            resumePending = false;
            try {
                const response = await fetch(gate.dataset.readingUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': gate.dataset.csrfToken },
                    body: JSON.stringify({ resume }),
                });
                if (!response.ok) throw new Error('Reading time could not be saved');
                const state = await response.json();
                elapsedSeconds = Math.max(elapsedSeconds, Number(state.elapsed_seconds || 0));
                unlocked = Math.min(cards.length, Math.max(unlocked, Number(state.unlocked_count || 1)));
                lastSyncAt = Date.now();
                connectionFailed = false;
            } catch (error) {
                connectionFailed = true;
                resumePending = true;
            } finally {
                requestPending = false;
                render();
            }
        }

        render();
        pulse();
        window.setInterval(function () { pulse(); render(); }, 10000);
        window.setInterval(render, 1000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                resumePending = true;
                pulse();
            }
            render();
        });
    });
})();
