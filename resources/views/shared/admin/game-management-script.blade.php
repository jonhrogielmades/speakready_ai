@php
    $gameFormWithErrors = $errors->any() ? (string) old('_game_form', '') : '';
    $gameLevelWithErrors = (int) old('_game_level_id', 0);
    $initialGameModalId = match ($gameFormWithErrors) {
        'add' => 'addGameModal',
        'generate' => 'generateGameModal',
        'edit' => $gameLevelWithErrors > 0 ? 'editGameModal'.$gameLevelWithErrors : null,
        default => null,
    };
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('sec-admin-game');
    if (!root || root.dataset.gameManagementReady === 'true') return;
    root.dataset.gameManagementReady = 'true';

    const pageCategorySelect = document.getElementById('gameCategorySelect');
    const levelCategoryById = @json($allLevels->pluck('category_id', 'id'));
    const nextLevelNumbers = @json($nextLevelNumbers ?? []);
    const oldInput = @json($errors->any() ? session()->getOldInput() : []);
    const errorFields = @json($errors->any() ? $errors->keys() : []);
    const errorMessages = @json($errors->any() ? $errors->all() : []);
    const initialModalId = @json($initialGameModalId);
    const perPage = 5;

    function currentPageCategoryId() {
        return String(pageCategorySelect?.value || '').replace('#cat-pane-', '');
    }

    function showCategory(targetSelector) {
        if (!targetSelector) return;
        const tabButton = document.querySelector(`[data-bs-target="${targetSelector}"]`);
        if (!tabButton) return;

        if (window.bootstrap?.Tab) {
            bootstrap.Tab.getOrCreateInstance(tabButton).show();
            return;
        }

        document.querySelectorAll('#game-categories-tab .game-cat-pill').forEach(button => {
            button.classList.toggle('active', button === tabButton);
        });
        document.querySelectorAll('#game-categories-tabContent .tab-pane').forEach(pane => {
            const active = `#${pane.id}` === targetSelector;
            pane.classList.toggle('show', active);
            pane.classList.toggle('active', active);
        });
    }

    document.querySelectorAll('.admin-game-panel').forEach(panel => {
        const rows = Array.from(panel.querySelectorAll('.game-level-row'));
        const countLabel = panel.querySelector('[data-game-pagination-count]');
        const prevButton = panel.querySelector('[data-game-prev]');
        const nextButton = panel.querySelector('[data-game-next]');
        if (!countLabel || !prevButton || !nextButton) return;

        let currentPage = 1;

        function renderPage() {
            const totalPages = Math.max(1, Math.ceil(rows.length / perPage));
            currentPage = Math.max(1, Math.min(currentPage, totalPages));
            const start = (currentPage - 1) * perPage;
            const visibleRows = rows.slice(start, start + perPage);

            rows.forEach(row => { row.style.display = 'none'; });
            visibleRows.forEach(row => { row.style.display = ''; });

            const first = rows.length === 0 ? 0 : start + 1;
            const last = start + visibleRows.length;
            countLabel.textContent = `Showing ${first}-${last} of ${rows.length}`;
            prevButton.disabled = currentPage <= 1;
            nextButton.disabled = currentPage >= totalPages || rows.length === 0;
        }

        prevButton.addEventListener('click', function () {
            currentPage--;
            renderPage();
        });
        nextButton.addEventListener('click', function () {
            currentPage++;
            renderPage();
        });
        renderPage();
    });

    pageCategorySelect?.addEventListener('change', function () {
        showCategory(this.value);
    });

    document.querySelectorAll('#game-categories-tab .game-cat-pill').forEach(button => {
        button.addEventListener('shown.bs.tab', function () {
            if (pageCategorySelect) pageCategorySelect.value = this.dataset.bsTarget || '';
        });
    });

    function formCategorySelect(form) {
        return form?.querySelector('select[name="category_id"]') || null;
    }

    function syncPrerequisiteOptions(form) {
        const categorySelect = formCategorySelect(form);
        const prerequisiteSelect = form?.querySelector('select[name="prerequisite_level_id"]');
        if (!categorySelect || !prerequisiteSelect) return;

        const categoryId = String(categorySelect.value || '');
        let selectedStillAvailable = prerequisiteSelect.value === '';

        Array.from(prerequisiteSelect.options).forEach(option => {
            if (option.value === '') return;
            const sameCategory = String(levelCategoryById[option.value] ?? '') === categoryId;
            option.hidden = !sameCategory;
            option.disabled = !sameCategory;
            if (sameCategory && option.selected) selectedStillAvailable = true;
        });

        if (!selectedStillAvailable) prerequisiteSelect.value = '';
    }

    function setSuggestedLevel(form, force, preserveValue = false) {
        if (!form || !['add', 'generate'].includes(form.dataset.gameForm)) return;
        const categorySelect = formCategorySelect(form);
        const levelInput = form.querySelector('input[name="level_number"]');
        if (!categorySelect || !levelInput) return;

        if (!categorySelect.value) {
            levelInput.setCustomValidity('Choose an active game category first.');
            return;
        }

        const nextLevel = nextLevelNumbers[String(categorySelect.value)] ?? null;
        if (nextLevel === null || nextLevel === '') {
            levelInput.setCustomValidity('This category already uses all level numbers from 1 to 100.');
            return;
        }

        levelInput.setCustomValidity('');
        if (!preserveValue && (force || levelInput.value === '')) levelInput.value = String(nextLevel);
    }

    function resetSubmitButton(form) {
        const button = form?.querySelector('button[type="submit"]');
        if (!button) return;
        button.disabled = false;
        button.removeAttribute('aria-busy');
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }
    }

    function setSubmitButtonLoading(form) {
        const button = form?.querySelector('button[type="submit"]');
        if (!button || button.disabled) return;

        button.dataset.originalHtml = button.innerHTML;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.replaceChildren();

        const spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('aria-hidden', 'true');
        button.append(spinner, document.createTextNode(button.dataset.loadingText || 'Saving...'));
    }

    function restoreOldInput(form) {
        if (!form || !oldInput || typeof oldInput !== 'object') return;

        Array.from(form.elements).forEach(control => {
            if (!control.name || control.name.startsWith('_')) return;
            const hasValue = Object.prototype.hasOwnProperty.call(oldInput, control.name);

            if (control.type === 'checkbox') {
                control.checked = hasValue && !['', '0', 'false', null, false].includes(oldInput[control.name]);
                return;
            }

            if (!hasValue || control.type === 'file') return;
            control.value = oldInput[control.name] ?? '';
        });
    }

    function renderServerErrors(form) {
        if (!form || errorMessages.length === 0) return;
        const modalBody = form.querySelector('.modal-body');
        if (!modalBody) return;

        const alert = document.createElement('div');
        alert.className = 'alert alert-danger admin-game-form-errors';
        alert.setAttribute('role', 'alert');

        const heading = document.createElement('div');
        heading.className = 'fw-bold mb-1';
        heading.textContent = 'The request could not be completed.';
        const list = document.createElement('ul');
        list.className = 'mb-0 ps-3';
        errorMessages.forEach(message => {
            const item = document.createElement('li');
            item.textContent = message;
            list.appendChild(item);
        });
        alert.append(heading, list);
        modalBody.prepend(alert);

        errorFields.forEach(field => {
            const control = form.elements.namedItem(field);
            if (control instanceof HTMLElement) control.classList.add('is-invalid');
        });
    }

    document.querySelectorAll('.admin-game-form').forEach(form => {
        const categorySelect = formCategorySelect(form);
        categorySelect?.addEventListener('change', function () {
            syncPrerequisiteOptions(form);
            setSuggestedLevel(form, true);
        });

        syncPrerequisiteOptions(form);
        setSuggestedLevel(form, false);

        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }
            setSubmitButtonLoading(form);
        });

        ['input', 'change'].forEach(eventName => {
            form.addEventListener(eventName, function (event) {
                if (event.target instanceof HTMLElement) event.target.classList.remove('is-invalid');
            });
        });
    });

    document.querySelectorAll('#addGameModal, #generateGameModal, [id^="editGameModal"]').forEach(modal => {
        const form = modal.querySelector('.admin-game-form');
        if (!form) return;

        modal.addEventListener('show.bs.modal', function () {
            if (modal.id !== initialModalId) {
                const categorySelect = formCategorySelect(form);
                const categoryId = currentPageCategoryId();
                if (categorySelect && categoryId && categorySelect.querySelector(`option[value="${categoryId}"]`)) {
                    categorySelect.value = categoryId;
                }
                syncPrerequisiteOptions(form);
                setSuggestedLevel(form, true);
            }
        });

        modal.addEventListener('shown.bs.modal', function () {
            (form.querySelector('.is-invalid') || form.querySelector('input:not([type="hidden"]), select, textarea'))?.focus();
        });

        modal.addEventListener('hidden.bs.modal', function () {
            resetSubmitButton(form);
        });
    });

    if (initialModalId) {
        const initialModal = document.getElementById(initialModalId);
        const initialForm = initialModal?.querySelector('.admin-game-form');
        if (initialModal && initialForm) {
            restoreOldInput(initialForm);
            syncPrerequisiteOptions(initialForm);
            setSuggestedLevel(initialForm, false, true);
            renderServerErrors(initialForm);
            if (window.bootstrap?.Modal) {
                bootstrap.Modal.getOrCreateInstance(initialModal).show();
            }
        }
    }

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('.admin-game-form').forEach(resetSubmitButton);
    });
});
</script>
