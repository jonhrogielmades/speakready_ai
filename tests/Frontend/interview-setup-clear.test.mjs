import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const source = readFileSync(join(root, 'public/js/shared/interview-setup-clear.js'), 'utf8');

function control(name, value, type = 'text', checked = false) {
    const events = [];
    const attributes = new Map([['value', value]]);
    return {
        name, value, type, checked, events,
        dataset: {},
        removeAttribute(name) { attributes.delete(name); },
        getAttribute(name) { return attributes.get(name) ?? null; },
        dispatchEvent(event) { events.push(event.type); },
    };
}

function setup() {
    const controls = [
        control('category_id', '7'),
        control('interview_focus', 'Job Interview', 'hidden'),
        control('job_category', 'Maritime and Seafaring'),
        control('target_position', 'Seaman', 'hidden'),
        control('custom_position', 'Seaman', 'hidden'),
        control('role_skills', 'navigation'),
        control('role_experience_level', 'mid'),
        control('difficulty', 'hard', 'radio', true),
        control('num_questions', '10'),
        control('time_limit', '2'),
        control('camera_detection', '1', 'radio', true),
        control('live_feedback_mode', 'coaching', 'radio', true),
        control('question_types[]', 'Technical', 'checkbox', true),
        control('response_mode', 'voice', 'radio', true),
        control('interviewer_avatar', 'role_specialist', 'radio', true),
        control('separate_language_scoring', '0', 'hidden'),
    ];
    const byName = (name) => controls.find((item) => item.name === name);
    byName('target_position').dataset.selectedTarget = 'Seaman';
    byName('target_position').dataset.customTarget = 'true';

    const extras = Object.fromEntries([
        'customPositionInput', 'customTargetPositionInput', 'targetPositionSearch',
        'targetPositionCustomInput', 'customTargetPositionDraft',
    ].map((id) => [id, { value: 'Seaman', dataset: { active: 'true' }, removeAttribute() {} }]));
    extras.targetPositionCustomError = { hidden: false };
    extras.customTargetPositionError = { hidden: false };
    extras.suggestedRoles = { cleared: false, replaceChildren() { this.cleared = true; } };
    extras.roleSuggestionResults = { cleared: false, replaceChildren() { this.cleared = true; } };
    extras.suggestRolesStatus = { textContent: 'Suggested role' };
    extras.roleSuggestionStatus = { textContent: 'Suggested role' };

    const window = {};
    const document = { getElementById(id) { return extras[id] ?? null; } };
    runInNewContext(source, {
        window, document,
        Event: class Event { constructor(type) { this.type = type; } },
    });

    return { api: window.SpeakReadyInterviewSetupClear, form: { elements: controls }, byName, extras };
}

test('Clear removes only the selected setup step choices', () => {
    for (const [panel, cleared, preserved] of [
        ['panel-basic', 'category_id', 'difficulty'],
        ['panel-structure', 'num_questions', 'camera_detection'],
        ['panel-inclusive', 'camera_detection', 'time_limit'],
        ['panel-content', 'live_feedback_mode', 'response_mode'],
        ['panel-response', 'response_mode', 'category_id'],
    ]) {
        const { api, form, byName } = setup();
        const original = { value: byName(preserved).value, checked: byName(preserved).checked };
        assert.equal(api.clearPanel(form, panel), true, panel);
        const field = byName(cleared);
        if (field.type === 'radio' || field.type === 'checkbox') assert.equal(field.checked, false, panel);
        else assert.equal(field.value, '', panel);
        assert.equal(byName(preserved).value, original.value, panel);
        assert.equal(byName(preserved).checked, original.checked, panel);
        assert.equal(byName('separate_language_scoring').value, '0', panel);
        if (panel === 'panel-structure') {
            assert.equal(byName('difficulty').checked, false);
            assert.equal(byName('time_limit').value, '');
        }
        if (panel === 'panel-content') {
            assert.equal(byName('question_types[]').checked, false);
        }
        if (panel === 'panel-response') {
            assert.equal(byName('interviewer_avatar').checked, false);
        }
    }
});

test('Clear Details removes hidden target fallbacks and stale picker and role suggestions', () => {
    const { api, form, byName, extras } = setup();
    api.clearPanel(form, 'panel-basic');

    assert.equal(byName('target_position').value, '');
    assert.equal(byName('target_position').dataset.selectedTarget, '');
    assert.equal(byName('target_position').getAttribute('value'), null);
    assert.equal(byName('target_position').dataset.customTarget, undefined);
    assert.equal(byName('interview_focus').value, '');
    assert.equal(byName('custom_position').value, '');
    assert.equal(byName('role_experience_level').value, '');
    assert.equal(extras.customPositionInput.dataset.active, 'false');
    assert.equal(extras.customTargetPositionInput.value, '');
    assert.equal(extras.targetPositionSearch.value, '');
    assert.equal(extras.targetPositionCustomError.hidden, true);
    assert.equal(extras.suggestedRoles.cleared, true);
    assert.equal(extras.roleSuggestionStatus.textContent, '');
});

test('Clear Response notifies the interviewer picker to remove its preview', () => {
    const { api, form, byName } = setup();
    const avatar = byName('interviewer_avatar');
    api.clearPanel(form, 'panel-response');

    assert.equal(avatar.checked, false);
    assert.deepEqual(avatar.events, ['change']);
    assert.equal(byName('response_mode').checked, false);
});

test('Clear all removes the entire setup and preserves fixed options', () => {
    const { api, form, byName, extras } = setup();

    assert.equal(api.clearPanel(form, 'panel-summary'), true);
    for (const name of [
        'category_id', 'interview_focus', 'job_category', 'target_position', 'custom_position',
        'role_skills', 'role_experience_level', 'difficulty', 'num_questions', 'time_limit',
        'camera_detection', 'live_feedback_mode', 'question_types[]', 'response_mode', 'interviewer_avatar',
    ]) {
        const field = byName(name);
        if (field.type === 'radio' || field.type === 'checkbox') assert.equal(field.checked, false, name);
        else assert.equal(field.value, '', name);
    }
    assert.equal(byName('separate_language_scoring').value, '0');
    assert.equal(extras.suggestedRoles.cleared, true);
});

test('desktop and mobile Clear refresh the summary and save the cleared draft immediately', () => {
    for (const layout of ['desktop', 'mobile']) for (const panelId of ['panel-basic', 'panel-summary']) {
        const view = readFileSync(join(root, `resources/views/${layout}/interview/setup.blade.php`), 'utf8');
        const marker = "document.querySelectorAll('[data-clear-setup-step]').forEach(button => {";
        const start = view.indexOf(marker);
        const end = view.indexOf("document.querySelectorAll('input[name=\"camera_detection\"]')", start);
        assert.ok(start >= 0 && end > start, `${layout} Clear handler must be present`);

        const { api, form, byName } = setup();
        const calls = { close: 0, sync: 0, cancel: 0, validation: [], summary: 0, navigation: [], saved: [] };
        const setupStepState = { index: panelId === 'panel-summary' ? 5 : 0 };
        const visitedSetupStepIds = new Set(['panel-basic', 'panel-structure']);
        let click;
        const button = {
            dataset: { clearSetupStep: panelId },
            addEventListener(type, callback) {
                assert.equal(type, 'click');
                click = callback;
            },
        };
        const context = {
            window: { SpeakReadyInterviewSetupClear: api },
            document: {
                querySelectorAll(selector) {
                    assert.equal(selector, '[data-clear-setup-step]');
                    return [button];
                },
            },
            setupDraftFormElement: form,
            setupStepState,
            visitedSetupStepIds,
            setupDraftApi: {
                save(_, options) {
                    calls.saved.push({
                        position: byName('target_position').value,
                        category: byName('category_id').value,
                        difficulty: byName('difficulty').checked,
                        options,
                    });
                },
            },
            setSetupTargetDropdownOpen() { calls.close++; },
            syncSetupTargetFieldCopy() { calls.sync++; },
            cancelRoleSuggestionRequest() { calls.cancel++; },
            markSetupValidation(items) { calls.validation.push(Array.from(items)); },
            updateSummary() { calls.summary++; },
            showSetupStep(index) {
                calls.navigation.push(index);
                setupStepState.index = index;
                visitedSetupStepIds.add('panel-basic');
                calls.summary++;
            },
        };

        runInNewContext(`let setupValidationVisible = true;\n${view.slice(start, end)}`, context);
        assert.equal(typeof click, 'function', layout);
        click();

        assert.equal(calls.close, 1, layout);
        assert.equal(calls.sync, 1, layout);
        assert.equal(calls.cancel, 1, layout);
        assert.deepEqual(calls.validation, [[]], layout);
        assert.equal(calls.summary, 1, layout);
        assert.equal(calls.saved.length, 1, layout);
        assert.equal(calls.saved[0].position, '', layout);
        assert.equal(calls.saved[0].category, '', layout);
        assert.equal(calls.saved[0].difficulty, panelId === 'panel-basic', layout);
        assert.equal(calls.saved[0].options.activeStepIndex, 0, layout);
        assert.deepEqual(calls.navigation, panelId === 'panel-summary' ? [0] : [], layout);
        assert.deepEqual(
            Array.from(calls.saved[0].options.visitedStepIds),
            panelId === 'panel-summary' ? ['panel-basic'] : ['panel-basic', 'panel-structure'],
            layout,
        );
    }
});
