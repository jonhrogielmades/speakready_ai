import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const source = readFileSync(join(root, 'public/js/interview-setup-draft.js'), 'utf8');
const storageKey = 'test.interview.setupDraft';

function setupDraft(initialDraft = null) {
    const stored = new Map(initialDraft ? [[storageKey, JSON.stringify(initialDraft)]] : []);
    const localStorage = {
        getItem(key) { return stored.get(key) ?? null; },
        setItem(key, value) { stored.set(key, String(value)); },
        removeItem(key) { stored.delete(key); },
    };
    const window = {
        localStorage,
        SpeakReadyInterviewSetupDraftConfig: { storageKey },
    };
    runInNewContext(source, {
        window,
        Event: class Event { constructor(type) { this.type = type; } },
        CustomEvent: class CustomEvent { constructor(type) { this.type = type; } },
        console,
    });
    return { api: window.SpeakReadyInterviewSetupDraft, stored };
}

function control(name, value, type = 'text', checked = false) {
    const events = [];
    return {
        name,
        value,
        type,
        tagName: 'INPUT',
        checked,
        dataset: {},
        events,
        dispatchEvent(event) { events.push(event.type); },
    };
}

function setupForm() {
    const avatarOne = control('interviewer_avatar', 'friendly_recruiter', 'radio');
    const avatarTwo = control('interviewer_avatar', 'role_specialist', 'radio', true);
    const difficultyOne = control('difficulty', 'easy', 'radio');
    const difficultyTwo = control('difficulty', 'hard', 'radio');
    const questions = control('num_questions', '');
    const events = [];
    const form = {
        elements: [avatarOne, avatarTwo, difficultyOne, difficultyTwo, questions],
        dispatchEvent(event) { events.push(event.type); },
    };
    return { form, avatarOne, avatarTwo, difficultyOne, difficultyTwo, questions, events };
}

test('legacy saved avatar is ignored while other setup fields are restored', () => {
    const { api, stored } = setupDraft({
        version: 1,
        values: {
            interviewer_avatar: 'role_specialist',
            difficulty: 'hard',
            num_questions: '6',
        },
        visited_step_ids: ['panel-basic'],
    });
    const { form, avatarOne, avatarTwo, difficultyTwo, questions, events } = setupForm();

    const restored = api.restore(form);

    assert.ok(restored);
    assert.equal(avatarOne.checked, false);
    assert.equal(avatarTwo.checked, false);
    assert.deepEqual(avatarTwo.events, ['change']);
    assert.equal(difficultyTwo.checked, true);
    assert.equal(questions.value, '6');
    assert.deepEqual(events, ['speakready:interview-setup-draft-restored']);

    api.save(form);
    const savedValues = JSON.parse(stored.get(storageKey)).values;
    assert.equal(Object.hasOwn(savedValues, 'interviewer_avatar'), false);
    assert.equal(savedValues.difficulty, 'hard');
    assert.equal(savedValues.num_questions, '6');
});

test('a fresh setup clears browser-restored avatar even without a saved draft', () => {
    const { api } = setupDraft();
    const { form, avatarOne, avatarTwo } = setupForm();

    assert.equal(api.restore(form), null);
    assert.equal(avatarOne.checked, false);
    assert.equal(avatarTwo.checked, false);
});

test('desktop and mobile clear browser restoration on a new load but keep bfcache and validation choices', () => {
    for (const layout of ['desktop', 'mobile']) {
        const view = readFileSync(join(root, `resources/views/${layout}/interview/setup.blade.php`), 'utf8');
        const guard = view.match(/if \((!event\.persisted && @json\(!\$errors->any\(\)\))\)\s*\{?\s*setupDraftApi\?\.clearInterviewerSelection\(setupForm\);/)?.[1];
        assert.ok(guard, `${layout} must guard pageshow avatar clearing`);

        for (const [persisted, hasErrors, expectedClear] of [
            [false, false, true],
            [true, false, false],
            [false, true, false],
        ]) {
            let clears = 0;
            const setupDraftApi = { clearInterviewerSelection() { clears++; } };
            const event = { persisted };
            const setupForm = {};
            const renderedGuard = guard.replace('@json(!$errors->any())', String(!hasErrors));
            Function('event', 'setupDraftApi', 'setupForm', `if (${renderedGuard}) setupDraftApi?.clearInterviewerSelection(setupForm);`)(event, setupDraftApi, setupForm);
            assert.equal(clears > 0, expectedClear, `${layout} persisted=${persisted}, errors=${hasErrors}`);
        }
    }
});
