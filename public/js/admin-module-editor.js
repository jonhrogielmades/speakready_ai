(function () {
    'use strict';

    var toolbarOptions = [
        [{ header: [3, false] }],
        ['bold', 'italic'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['clean']
    ];

    function readStoredTab(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (error) {
            return null;
        }
    }

    function storeTab(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (error) {
            // Tab persistence is optional when storage is unavailable.
        }
    }

    function activateTab(target) {
        if (target !== '#basic' && target !== '#chapters') {
            return;
        }

        var trigger = document.querySelector('#moduleEditTabs [data-bs-target="' + target + '"]');
        if (!trigger) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(trigger).show();
            return;
        }

        document.querySelectorAll('#moduleEditTabs .nav-link').forEach(function (button) {
            button.classList.toggle('active', button === trigger);
            button.setAttribute('aria-selected', button === trigger ? 'true' : 'false');
        });
        document.querySelectorAll('#moduleEditTabsContent > .tab-pane').forEach(function (pane) {
            var selected = '#' + pane.id === target;
            pane.classList.toggle('show', selected);
            pane.classList.toggle('active', selected);
        });
    }

    function editorSource(container) {
        var sourceId = container.getAttribute('data-content-input');
        return sourceId ? document.getElementById(sourceId) : null;
    }

    function showBasicEditor(container, source) {
        container.hidden = true;
        source.hidden = false;

        var note = source.parentElement.querySelector('.admin-chapter-editor-fallback-note');
        if (note) {
            note.hidden = false;
        }
    }

    function initializeEditor(container) {
        if (container.dataset.editorInitialized === 'true') {
            if (container.speakReadyQuill) {
                window.requestAnimationFrame(function () {
                    container.speakReadyQuill.update();
                });
            }
            return;
        }

        var source = editorSource(container);
        if (!source) {
            return;
        }

        if (typeof window.Quill !== 'function') {
            showBasicEditor(container, source);
            return;
        }

        try {
            container.hidden = false;
            var quill = new window.Quill(container, {
                theme: 'snow',
                formats: ['header', 'bold', 'italic', 'list'],
                modules: { toolbar: toolbarOptions }
            });

            if (source.value.trim() !== '') {
                quill.clipboard.dangerouslyPasteHTML(source.value);
            }

            container.speakReadyQuill = quill;
            container.dataset.editorInitialized = 'true';
            source.hidden = true;

            var note = source.parentElement.querySelector('.admin-chapter-editor-fallback-note');
            if (note) {
                note.hidden = true;
            }

            window.requestAnimationFrame(function () {
                quill.update();
            });
        } catch (error) {
            showBasicEditor(container, source);
            window.console.warn('The rich-text chapter editor could not be initialized.', error);
        }
    }

    function initializeModalEditors(modal) {
        modal.querySelectorAll('[data-chapter-editor]').forEach(initializeEditor);

        var firstInvalid = modal.querySelector('.is-invalid');
        var firstTitle = modal.querySelector('input[name="title"]');
        window.setTimeout(function () {
            (firstInvalid || firstTitle)?.focus();
        }, 100);
    }

    function prepareForm(form) {
        form.addEventListener('submit', function (event) {
            form.querySelectorAll('[data-chapter-editor]').forEach(function (container) {
                var source = editorSource(container);
                if (source && container.speakReadyQuill) {
                    source.value = container.speakReadyQuill.root.innerHTML;
                }
            });

            if (!form.checkValidity()) {
                event.preventDefault();
                form.classList.add('was-validated');
                return;
            }

            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';
            var submitButton = event.submitter || form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i>'
                    + (submitButton.getAttribute('data-saving-label') || 'Saving...');
            }
        });
    }

    function openModal(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
            return;
        }

        initializeModalEditors(modal);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var config = document.getElementById('adminModuleChapterEditorConfig');
        if (!config) {
            return;
        }

        var storageKey = 'activeModuleTab_' + config.getAttribute('data-module-id');
        var editChapterId = config.getAttribute('data-edit-chapter-id');
        var shouldOpenAdd = config.getAttribute('data-open-add-chapter') === '1';
        var activeTab = config.getAttribute('data-active-module-tab') || readStoredTab(storageKey);

        if (editChapterId || shouldOpenAdd) {
            activeTab = '#chapters';
        }
        if (activeTab) {
            activateTab(activeTab);
        }

        document.querySelectorAll('#moduleEditTabs [data-bs-toggle="pill"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function (event) {
                var target = event.target.getAttribute('data-bs-target');
                if (target === '#basic' || target === '#chapters') {
                    storeTab(storageKey, target);
                }
            });
        });

        document.querySelectorAll('.admin-chapter-modal').forEach(function (modal) {
            modal.addEventListener('shown.bs.modal', function () {
                initializeModalEditors(modal);
            });
        });

        document.querySelectorAll('.admin-chapter-form').forEach(prepareForm);

        if (editChapterId) {
            openModal('editChapterModal' + editChapterId);
        } else if (shouldOpenAdd) {
            openModal('addChapterModal');
        }
    });
})();
