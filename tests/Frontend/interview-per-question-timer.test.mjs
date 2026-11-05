import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const read = path => readFileSync(join(root, path), 'utf8');
const layouts = ['desktop', 'mobile'];
const modes = ['text', 'voice', 'hybrid'];

function functionSource(source, name) {
    const start = source.indexOf(`function ${name}(`);
    assert.ok(start >= 0, `${name} was not found`);
    const signatureEnd = source.indexOf(') {', start);
    assert.ok(signatureEnd >= 0, `${name} has no function body`);
    const open = signatureEnd + 2;
    let depth = 0;
    for (let index = open; index < source.length; index++) {
        if (source[index] === '{') depth++;
        if (source[index] === '}' && --depth === 0) return source.slice(start, index + 1);
    }
    assert.fail(`${name} has no closing brace`);
}

function timerHarness(source, mode, limitSeconds = 60) {
    const clock = { now: 1_000_000 };
    const intervals = new Map();
    const submissions = [];
    const timer = { innerText: '' };
    const chip = { className: '' };
    let nextIntervalId = 1;
    const context = {
        Date: class extends Date {
            constructor(...args) { super(...(args.length ? args : [clock.now])); }
            static now() { return clock.now; }
        },
        Math,
        FormData,
        canonicalResponseMode: mode,
        perQuestionLimitSeconds: limitSeconds,
        currentQIdx: 0,
        answersData: Array.from({ length: 5 }, () => ({ elapsed_seconds: 0 })),
        questionStartedAt: null,
        questionElapsedSeconds: 0,
        questionTimerInterval: null,
        lastTimelineCaptureAt: 0,
        answerInputEnabled: true,
        isSubmittingAnswer: false,
        interviewEnding: false,
        interviewTerminated: false,
        finalAnswerSubmitted: false,
        timerSeconds: 0,
        interviewChatHistory: [],
        document: { getElementById: id => id === 'questionTimerChip' ? chip : id === 'perQuestionTimer' ? timer : null },
        setInterval(callback) {
            const id = nextIntervalId++;
            intervals.set(id, callback);
            return id;
        },
        clearInterval(id) { intervals.delete(id); },
        captureTranscriptTimeline() {},
        syncHybridAnswerStateFromTextarea() {},
        isHybridTranscriptionMode() { return mode === 'hybrid'; },
        currentAnswerTextareaText() { return ''; },
        observationDataForSubmit() { return {}; },
        questionSnapshot() { return []; },
        managedFetch() { return new Promise(() => {}); },
        submitAnswer(options) { submissions.push({ question: context.currentQIdx, ...options }); },
    };
    const names = [
        'formatSeconds',
        'getQuestionElapsedSeconds',
        'updateQuestionTimerDisplay',
        'startQuestionTimer',
        'stopQuestionTimer',
        'handleQuestionTimeout',
        'autoSaveState',
    ];
    vm.createContext(context);
    vm.runInContext(names.map(name => functionSource(source, name)).join('\n'), context);
    return {
        context, clock, intervals, submissions, timer,
        tick(seconds) {
            clock.now += seconds * 1000;
            for (const callback of [...intervals.values()]) callback();
        },
    };
}

for (const layout of layouts) {
    const setupSource = read(`resources/views/${layout}/interview/setup.blade.php`);
    const sessionSource = read(`resources/views/${layout}/interview/session.blade.php`);

    test(`${layout} setup and session treat the selected minute as a per-question limit`, () => {
        assert.match(setupSource, /<option value="1"[^>]*>1 Minute per Question<\/option>/);
        assert.match(sessionSource, /const perQuestionLimitSeconds = \{\{ \(int\) \(\(\$sessionRecord->time_limit\?\? 0\) \* 60\) \}\};/);
        const loadQuestion = functionSource(sessionSource, 'loadQuestion');
        const speechEnd = loadQuestion.indexOf('await speakQuestion(q.question_text,');
        const inputReady = loadQuestion.indexOf('setAnswerInputEnabled(true);', speechEnd);
        const timerStart = loadQuestion.indexOf('startQuestionTimer();', inputReady);
        assert.ok(speechEnd >= 0 && inputReady > speechEnd && timerStart > inputReady,
            'each question timer should start after the prompt finishes and the answer field is ready');
    });

    for (const mode of modes) {
        test(`${layout} ${mode} gives each of five questions its own full minute`, () => {
            const harness = timerHarness(sessionSource, mode);
            for (let index = 0; index < 5; index++) {
                harness.context.currentQIdx = index;
                harness.context.startQuestionTimer();
                assert.equal(harness.timer.innerText, '01:00', `question ${index + 1} should start with 60 seconds`);

                harness.tick(30);
                assert.equal(harness.timer.innerText, '00:30');
                harness.tick(29);
                assert.equal(harness.timer.innerText, '00:01');
                assert.equal(harness.submissions.length, index);
                harness.tick(1);
                assert.equal(harness.timer.innerText, '00:00');
                assert.deepEqual(harness.submissions.at(-1), { question: index, timedOut: true });
                assert.equal(harness.intervals.size, 0, 'the expired question interval should stop');
                harness.context.stopQuestionTimer();
                assert.equal(harness.context.answersData[index].elapsed_seconds, 60);

                harness.clock.now += 15_000; // The transition must not consume the next question\'s minute.
            }
            assert.equal(harness.submissions.length, 5);
        });
    }

    test(`${layout} resume uses the active question elapsed time, not earlier questions or session duration`, () => {
        const harness = timerHarness(sessionSource, 'text');
        harness.context.answersData[0].elapsed_seconds = 60;
        harness.context.answersData[1].elapsed_seconds = 60;
        harness.context.answersData[2].elapsed_seconds = 60;
        harness.context.answersData[3].elapsed_seconds = 20;
        harness.context.currentQIdx = 3;
        harness.context.timerSeconds = 200;
        harness.context.startQuestionTimer();
        assert.equal(harness.timer.innerText, '00:40');
        harness.tick(39);
        assert.equal(harness.timer.innerText, '00:01');
        assert.equal(harness.submissions.length, 0);
        harness.tick(1);
        assert.deepEqual(harness.submissions, [{ question: 3, timedOut: true }]);
    });

    for (const mode of modes) {
        test(`${layout} ${mode} autosave during the next prompt cannot carry over the previous minute`, () => {
            const harness = timerHarness(sessionSource, mode);
            harness.context.startQuestionTimer();
            harness.tick(45);
            harness.context.stopQuestionTimer();
            assert.equal(harness.context.answersData[0].elapsed_seconds, 45);

            harness.context.currentQIdx = 1;
            harness.clock.now += 10_000; // The next question is being spoken before its timer starts.
            harness.context.autoSaveState();
            assert.equal(harness.context.answersData[1].elapsed_seconds, 0,
                'autosave must not write the previous question elapsed time onto the next answer');
            harness.context.startQuestionTimer();
            assert.equal(harness.timer.innerText, '01:00');
        });
    }

    test(`${layout} the full 60 seconds remain available at the half-second boundary`, () => {
        const harness = timerHarness(sessionSource, 'text');
        harness.context.startQuestionTimer();
        harness.tick(59.5);
        assert.equal(harness.timer.innerText, '00:01');
        assert.equal(harness.submissions.length, 0);
        harness.tick(0.5);
        assert.deepEqual(harness.submissions, [{ question: 0, timedOut: true }]);
    });

    test(`${layout} two- and three-minute selections expire per question`, () => {
        for (const minutes of [2, 3]) {
            const harness = timerHarness(sessionSource, 'text', minutes * 60);
            harness.context.startQuestionTimer();
            assert.equal(harness.timer.innerText, `0${minutes}:00`);
            harness.tick(minutes * 60 - 1);
            assert.equal(harness.timer.innerText, '00:01');
            assert.equal(harness.submissions.length, 0);
            harness.tick(1);
            assert.deepEqual(harness.submissions, [{ question: 0, timedOut: true }]);
        }
    });

    test(`${layout} No Limit does not auto-submit`, () => {
        const harness = timerHarness(sessionSource, 'hybrid', 0);
        harness.context.startQuestionTimer();
        harness.tick(600);
        assert.equal(harness.timer.innerText, 'Self-paced');
        assert.equal(harness.submissions.length, 0);
    });

    test(`${layout} a blocked timeout remains armed until submission is available`, () => {
        const harness = timerHarness(sessionSource, 'text');
        harness.context.startQuestionTimer();
        harness.context.isSubmittingAnswer = true;
        harness.tick(60);
        assert.equal(harness.submissions.length, 0);
        assert.equal(harness.intervals.size, 1, 'a blocked timeout must keep checking the clock');
        harness.context.isSubmittingAnswer = false;
        harness.tick(0);
        assert.deepEqual(harness.submissions, [{ question: 0, timedOut: true }]);
    });
}
