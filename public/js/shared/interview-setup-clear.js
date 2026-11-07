(function () {
    'use strict';

    const fieldsByPanel = {
        'panel-basic': [
            'category_id', 'interview_focus', 'job_category', 'target_position', 'custom_position',
            'role_skills', 'role_interests', 'role_qualifications', 'role_experience', 'role_experience_level',
        ],
        'panel-structure': ['difficulty', 'num_questions', 'time_limit'],
        'panel-inclusive': ['camera_detection'],
        'panel-content': ['live_feedback_mode', 'question_types[]'],
        'panel-response': ['response_mode', 'interviewer_avatar'],
    };

    function controlsForName(form, name) {
        return Array.from(form.elements || []).filter((control) => control.name === name);
    }

    function clearControl(control) {
        if (control.type === 'radio' || control.type === 'checkbox') {
            control.checked = false;
        } else {
            control.value = '';
        }
    }

    function clearPanel(form, panelId) {
        if (panelId === 'panel-summary') {
            if (!form) return false;
            Object.keys(fieldsByPanel).forEach((stepId) => clearPanel(form, stepId));
            return true;
        }

        const names = fieldsByPanel[panelId];
        if (!form || !names) return false;

        const selectedAvatar = panelId === 'panel-response'
            ? controlsForName(form, 'interviewer_avatar').find((control) => control.checked)
            : null;

        names.forEach((name) => controlsForName(form, name).forEach(clearControl));

        if (panelId === 'panel-basic') {
            const position = controlsForName(form, 'target_position')[0];
            if (position) {
                position.dataset.selectedTarget = '';
                position.dataset.targetKind = 'job';
                delete position.dataset.customTarget;
                position.removeAttribute('value');
            }

            for (const id of ['customPositionInput', 'customTargetPositionInput']) {
                const custom = document.getElementById(id);
                if (!custom) continue;
                custom.value = '';
                custom.dataset.active = 'false';
            }

            for (const id of ['targetPositionSearch', 'targetPositionCustomInput', 'customTargetPositionDraft']) {
                const field = document.getElementById(id);
                if (!field) continue;
                field.value = '';
                field.removeAttribute('aria-invalid');
            }

            for (const id of ['targetPositionCustomError', 'customTargetPositionError']) {
                const error = document.getElementById(id);
                if (error) error.hidden = true;
            }

            for (const id of ['suggestedRoles', 'roleSuggestionResults']) {
                document.getElementById(id)?.replaceChildren();
            }
            for (const id of ['suggestRolesStatus', 'roleSuggestionStatus']) {
                const status = document.getElementById(id);
                if (status) status.textContent = '';
            }
        }

        if (selectedAvatar) {
            selectedAvatar.dispatchEvent(new Event('change', { bubbles: true }));
        }

        return true;
    }

    window.SpeakReadyInterviewSetupClear = { clearPanel };
})();
