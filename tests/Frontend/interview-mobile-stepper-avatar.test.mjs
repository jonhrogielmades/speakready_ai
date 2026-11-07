import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const mobileSetup = readFileSync(join(root, 'resources/views/mobile/interview/setup.blade.php'), 'utf8');

function blockStartingAt(marker) {
    const start = mobileSetup.indexOf(marker);
    assert.notEqual(start, -1, `Mobile setup should contain ${marker}`);
    const openingBrace = mobileSetup.indexOf('{', start + marker.length);
    assert.notEqual(openingBrace, -1, `Missing body for ${marker}`);

    let depth = 0;
    for (let index = openingBrace; index < mobileSetup.length; index++) {
        if (mobileSetup[index] === '{') depth++;
        if (mobileSetup[index] === '}') depth--;
        if (depth === 0) return mobileSetup.slice(start, index + 1);
    }

    throw new Error(`Unclosed body for ${marker}`);
}

function mobileStepperCode() {
    const functions = [
        'getSetupSteps',
        'getRequiredSetupReviewStepIds',
        'isSetupStepComplete',
        'getMaxAccessibleSetupStepIndex',
        'validateSetupStep',
    ].map((name) => blockStartingAt(`function ${name}(`));

    // Older implementations contain this exception. Execute it too, so the
    // regression test detects the behavior rather than inspecting the source.
    if (mobileSetup.includes('function canReviewSummaryWithPendingInterviewer(')) {
        functions.push(blockStartingAt('function canReviewSummaryWithPendingInterviewer('));
    }

    const nextRegistration = blockStartingAt(
        "document.getElementById('setupStepNext')?.addEventListener('click', () =>",
    );

    return [
        'let setupValidationVisible = false;',
        ...functions,
        `${nextRegistration});`,
    ].join('\n');
}

function mobileStepperHarness() {
    const state = {
        index: 4,
        baseSteps: [
            { id: 'panel-basic', label: 'Details' },
            { id: 'panel-structure', label: 'Structure' },
            { id: 'panel-inclusive', label: 'Camera' },
            { id: 'panel-content', label: 'Scenario' },
            { id: 'panel-response', label: 'Response' },
        ],
    };
    const selection = { avatar: false };
    const validation = { marked: [], issues: [] };
    let clickNext;

    const context = vm.createContext({
        setupStepState: state,
        document: {
            getElementById(id) {
                if (id !== 'setupStepNext') return null;
                return {
                    addEventListener(type, callback) {
                        assert.equal(type, 'click');
                        clickNext = callback;
                    },
                };
            },
        },
        missingSetupItems(panelId) {
            return panelId === 'panel-response' && !selection.avatar
                ? [{ type: 'group', name: 'interviewer_avatar', panelId }]
                : [];
        },
        markSetupValidation(missing) { validation.marked.push(missing.map((item) => item.name)); },
        showSetupValidationIssue(item) { validation.issues.push(item?.name); },
        showSetupStep(index) { state.index = index; },
    });

    vm.runInContext(mobileStepperCode(), context);
    assert.equal(typeof clickNext, 'function');
    return { context, state, selection, validation, clickNext };
}

test('mobile Response requires an interviewer before Summary is accessible', () => {
    const page = mobileStepperHarness();

    assert.equal(vm.runInContext('getMaxAccessibleSetupStepIndex()', page.context), 4);
    page.clickNext();
    assert.equal(page.state.index, 4, 'Next should stay on Response');
    assert.deepEqual(page.validation.marked, [['interviewer_avatar']]);
    assert.deepEqual(page.validation.issues, ['interviewer_avatar']);

    page.selection.avatar = true;
    assert.equal(vm.runInContext('getMaxAccessibleSetupStepIndex()', page.context), 5);
    page.clickNext();
    assert.equal(page.state.index, 5, 'a selected interviewer should permit Summary');
});
