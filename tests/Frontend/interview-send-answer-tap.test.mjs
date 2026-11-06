import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');

function functionSource(source, name) {
    const start = source.indexOf(`function ${name}(`);
    assert.ok(start >= 0, `${name} was not found`);
    const open = source.indexOf('{', start);
    let depth = 0;
    for (let index = open; index < source.length; index++) {
        if (source[index] === '{') depth++;
        if (source[index] === '}' && --depth === 0) return source.slice(start, index + 1);
    }
    assert.fail(`${name} has no closing brace`);
}

for (const layout of ['desktop', 'mobile']) {
    test(`${layout} Send Answer submits the first focused tap once and keeps click/keyboard fallback`, () => {
        const source = readFileSync(join(root, `resources/views/${layout}/interview/session.blade.php`), 'utf8');
        assert.match(source, /if \(!answerListenersBound\) \{[\s\S]*?bindSendAnswerButton\(\);/);
        assert.match(source, /class="next-btn-class send-answer-btn response-send-answer-btn btn-shine" disabled/);

        const listeners = new Map();
        const captures = [];
        let blurred = 0;
        const textarea = { blur() { blurred++; } };
        const button = {
            disabled: false,
            addEventListener(name, listener) { listeners.set(name, listener); },
            setPointerCapture(id) { captures.push(id); },
        };
        let submissions = 0;
        let now = 1000;
        const context = {
            Math,
            Date: { now: () => now },
            document: { activeElement: textarea, querySelector: () => button },
            answerTextareaElement: () => textarea,
            submitAnswer: () => { submissions++; },
        };
        vm.createContext(context);
        vm.runInContext(functionSource(source, 'bindSendAnswerButton'), context);
        context.bindSendAnswerButton();

        let defaultPrevented = false;
        listeners.get('pointerdown')({ pointerId: 7, button: 0, isPrimary: true, clientX: 10, clientY: 20, preventDefault() { defaultPrevented = true; } });
        assert.equal(defaultPrevented, true, 'a focused textarea must not shift the button before pointerup');
        assert.deepEqual(captures, [7]);
        listeners.get('pointerup')({ pointerId: 7, clientX: 10, clientY: 20 });
        assert.equal(submissions, 1, 'pointer release submits even if the browser drops the click while closing the keyboard');
        assert.equal(blurred, 1, 'the keyboard closes only after the tap is recognized');
        listeners.get('click')({ detail: 1 });
        assert.equal(submissions, 1, 'the compatibility click must not submit twice');

        context.document.activeElement = null;
        defaultPrevented = false;
        now += 1000;
        listeners.get('pointerdown')({ pointerId: 8, button: 0, isPrimary: true, clientX: 10, clientY: 20, preventDefault() { defaultPrevented = true; } });
        assert.equal(defaultPrevented, false);
        listeners.get('pointerup')({ pointerId: 8, clientX: 11, clientY: 21 });
        assert.equal(submissions, 2);
        listeners.get('click')({ detail: 1 });
        assert.equal(submissions, 2);

        listeners.get('click')({ detail: 0 });
        assert.equal(submissions, 3, 'keyboard activation still submits');
        now += 1000;
        listeners.get('click')({ detail: 1 });
        assert.equal(submissions, 4, 'click-only browsers still submit');

        listeners.get('pointerdown')({ pointerId: 9, button: 0, isPrimary: true, clientX: 10, clientY: 20, preventDefault() {} });
        listeners.get('pointerup')({ pointerId: 9, clientX: 40, clientY: 20 });
        listeners.get('click')({ detail: 1 });
        assert.equal(submissions, 4, 'dragging off the button is not a tap');

        listeners.get('pointerdown')({ pointerId: 10, button: 0, isPrimary: true, clientX: 10, clientY: 20, preventDefault() {} });
        listeners.get('pointercancel')();
        listeners.get('pointerup')({ pointerId: 10, clientX: 10, clientY: 20 });
        assert.equal(submissions, 4, 'cancelled gestures do not send');

        button.disabled = true;
        listeners.get('click')({ detail: 0 });
        assert.equal(submissions, 4, 'disabled state still blocks submission');
    });
}
