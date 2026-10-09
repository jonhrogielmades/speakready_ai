(function () {
    'use strict';

    var SUBMIT_DELAY_MS = 120;

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function getOverlay() {
        return document.querySelector('[data-related-prep-loader]') || document.getElementById('setupTransitionOverlay');
    }

    function text(value, fallback) {
        value = String(value || '').trim();
        return value || fallback || '';
    }

    function setText(root, selector, value) {
        var element = root ? root.querySelector(selector) : null;
        if (element) element.textContent = value;
    }

    function setLoadingItem(overlay, key, title, detail) {
        var item = overlay ? overlay.querySelector('[data-loading-item="' + key + '"]') : null;
        if (!item) return;

        setText(item, '[data-loading-title]', title);
        setText(item, '[data-loading-detail]', detail);
    }

    function getLoaderType(source, overlay) {
        return source?.dataset.srRelatedLoaderForm
            || source?.dataset.srRelatedLoaderLink
            || overlay?.dataset.relatedPrepLoader
            || 'default';
    }

    function getContextLabel(source) {
        if (!source) return '';

        if (source.dataset.srLoaderLabel) return text(source.dataset.srLoaderLabel);

        var levelNode = source.closest?.('.level-node');
        if (levelNode?.dataset.levelTitle) return text(levelNode.dataset.levelTitle);

        var moduleCard = source.closest?.('.module-card');
        var moduleTitle = moduleCard?.querySelector('.module-card-title');
        if (moduleTitle) return text(moduleTitle.textContent);

        return text(source.textContent);
    }

    function getTargetPosition(form) {
        var field = form ? form.querySelector('[name="target_position"]') : null;
        if (!field) return '';

        if (field.tagName === 'SELECT') {
            var selected = field.options[field.selectedIndex];
            return text(selected ? selected.textContent : field.value);
        }

        return text(field.value);
    }

    function setProgress(overlay, value) {
        var progress = Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
        var bar = overlay ? overlay.querySelector('#setupLoadingProgressBar, .interview-prep-progress-bar') : null;
        var percent = overlay ? overlay.querySelector('#setupLoadingPercent, .interview-prep-progress-percent') : null;
        var track = overlay ? overlay.querySelector('.interview-prep-progress-track') : null;

        if (bar) bar.style.width = progress + '%';
        if (percent) percent.textContent = progress + '%';
        if (track) track.setAttribute('aria-valuenow', String(progress));
    }

    function resetChecklist(overlay) {
        if (!overlay) return;

        overlay.querySelectorAll('.interview-prep-check-row').forEach(function (row) {
            row.classList.remove('is-checking', 'is-complete');
            var check = row.querySelector('.interview-prep-row-check');
            if (check) check.setAttribute('aria-label', 'Pending');
        });
    }

    function startChecklist(overlay) {
        if (!overlay) return [];

        var timers = [];
        resetChecklist(overlay);
        Array.from(overlay.querySelectorAll('.interview-prep-check-row')).forEach(function (row, index) {
            timers.push(window.setTimeout(function () {
                row.classList.add('is-checking');
                var check = row.querySelector('.interview-prep-row-check');
                if (check) check.setAttribute('aria-label', 'Checking');

                timers.push(window.setTimeout(function () {
                    row.classList.remove('is-checking');
                    row.classList.add('is-complete');
                    if (check) check.setAttribute('aria-label', 'Complete');
                }, 220));
            }, 140 + (index * 190)));
        });

        return timers;
    }

    function completeChecklist(overlay) {
        if (!overlay) return;

        overlay.querySelectorAll('.interview-prep-check-row').forEach(function (row) {
            row.classList.remove('is-checking');
            row.classList.add('is-complete');
            var check = row.querySelector('.interview-prep-row-check');
            if (check) check.setAttribute('aria-label', 'Complete');
        });
    }

    function updateOverlayCopy(overlay, source) {
        var type = getLoaderType(source, overlay);
        var target = getTargetPosition(source) || getContextLabel(source) || 'selected target position';
        var title = overlay.querySelector('#setupLoadingTitle, .interview-prep-title');
        var description = overlay.querySelector('#setupLoadingDescription, .interview-prep-subtitle');

        if (type === 'modules') {
            if (title) title.innerHTML = 'Preparing Your <span>Modules...</span>';
            if (description) description.textContent = 'This will just take a few moments.';
            setLoadingItem(overlay, 'details', 'Position selected', target);
            setLoadingItem(overlay, 'structure', 'Module library configured', 'Role-specific action modules loading');
            setLoadingItem(overlay, 'camera', 'Preparation path checked', 'Topics and filters synced');
            setLoadingItem(overlay, 'coaching', 'Coaching goals prepared', 'Skill focus and practice actions ready');
            setLoadingItem(overlay, 'response', 'Related modules ready', 'Opening your module list');
            return;
        }

        if (type === 'challenge-start') {
            if (title) title.innerHTML = 'Preparing Your <span>Challenge...</span>';
            if (description) description.textContent = 'This will just take a few moments.';
            setLoadingItem(overlay, 'details', 'Challenge selected', target);
            setLoadingItem(overlay, 'structure', 'Level goals checked', 'Questions and success checklist ready');
            setLoadingItem(overlay, 'camera', 'Readiness checked', 'Energy and progress synced');
            setLoadingItem(overlay, 'coaching', 'Coaching goals prepared', 'Skill focus and scoring guide ready');
            setLoadingItem(overlay, 'response', 'Challenge ready', 'Opening your challenge room');
            return;
        }

        if (type === 'module-open') {
            if (title) title.innerHTML = 'Opening Your <span>Module...</span>';
            if (description) description.textContent = 'This will just take a few moments.';
            setLoadingItem(overlay, 'details', 'Module selected', target);
            setLoadingItem(overlay, 'structure', 'Learning path checked', 'Chapters and activities loading');
            setLoadingItem(overlay, 'camera', 'Progress synced', 'Your module status is up to date');
            setLoadingItem(overlay, 'coaching', 'Practice goals prepared', 'Action steps and examples ready');
            setLoadingItem(overlay, 'response', 'Module ready', 'Opening your action module');
            return;
        }

        if (title) title.innerHTML = 'Preparing Your <span>Challenges...</span>';
        if (description) description.textContent = 'This will just take a few moments.';
        setLoadingItem(overlay, 'details', 'Position selected', target);
        setLoadingItem(overlay, 'structure', 'Challenge path configured', 'Role-specific levels loading');
        setLoadingItem(overlay, 'camera', 'Readiness checked', 'Energy and progress synced');
        setLoadingItem(overlay, 'coaching', 'Coaching goals prepared', 'Skill focus and checklists ready');
        setLoadingItem(overlay, 'response', 'Challenge journey ready', 'Opening related challenges');
    }

    function submitForm(form) {
        window.setTimeout(function () {
            if (typeof HTMLFormElement !== 'undefined' && HTMLFormElement.prototype.submit) {
                HTMLFormElement.prototype.submit.call(form);
                return;
            }

            form.submit();
        }, SUBMIT_DELAY_MS);
    }

    function navigateTo(url) {
        window.setTimeout(function () {
            window.location.assign(url);
        }, SUBMIT_DELAY_MS);
    }

    function startProgress(overlay, done) {
        var progress = 19;
        var timers = startChecklist(overlay);

        setProgress(overlay, progress);

        var interval = window.setInterval(function () {
            var step = progress < 50 ? 10 : (progress < 78 ? 7 : (progress < 92 ? 5 : 2));
            progress = Math.min(100, progress + step);
            setProgress(overlay, progress);

            if (progress >= 100) {
                window.clearInterval(interval);
                timers.forEach(function (timer) {
                    window.clearTimeout(timer);
                });
                completeChecklist(overlay);
                done();
            }
        }, 120);
    }

    function activateLoader(source, done) {
        var overlay = getOverlay();
        if (!overlay) return done();

        if (overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }

        updateOverlayCopy(overlay, source);
        resetChecklist(overlay);
        setProgress(overlay, 19);
        overlay.classList.add('active');
        document.documentElement.classList.add('finish-transition-active');
        document.body.classList.add('finish-transition-active');
        startProgress(overlay, done);
    }

    function targetPositionIsPresent(form) {
        var target = form.querySelector('[name="target_position"]');
        if (!target || text(target.value)) return true;

        var selectButton = form.querySelector('[data-position-select] button, .challenge-position-select-button, .module-position-select-button');
        if (selectButton) {
            selectButton.classList.add('is-invalid');
            selectButton.focus({ preventScroll: true });
        } else {
            target.focus({ preventScroll: true });
        }

        return false;
    }

    function initPrepLoaderForms() {
        document.querySelectorAll('[data-sr-related-loader-form]').forEach(function (form) {
            if (form.dataset.srRelatedLoaderInitialized === 'true') return;
            form.dataset.srRelatedLoaderInitialized = 'true';

            form.addEventListener('submit', function (event) {
                if (event.defaultPrevented) return;
                if (form.dataset.srRelatedLoaderSubmitting === 'true') return;

                if (!targetPositionIsPresent(form)) {
                    event.preventDefault();
                    return;
                }

                if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                    return;
                }

                event.preventDefault();
                form.dataset.srRelatedLoaderSubmitting = 'true';
                activateLoader(form, function () {
                    submitForm(form);
                });
            });
        });
    }

    function initPrepLoaderLinks() {
        document.querySelectorAll('[data-sr-related-loader-link]').forEach(function (link) {
            if (link.dataset.srRelatedLoaderInitialized === 'true') return;
            link.dataset.srRelatedLoaderInitialized = 'true';

            link.addEventListener('click', function (event) {
                if (event.defaultPrevented) return;
                if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                if (link.target && link.target !== '_self') return;

                var href = link.href;
                if (!href || link.getAttribute('aria-disabled') === 'true' || link.classList.contains('disabled')) return;

                event.preventDefault();
                activateLoader(link, function () {
                    navigateTo(href);
                });
            });
        });
    }

    onReady(initPrepLoaderForms);
    onReady(initPrepLoaderLinks);
    window.SpeakReadyInterviewPrepLoader = {
        refresh: function () {
            initPrepLoaderForms();
            initPrepLoaderLinks();
        }
    };
})();
