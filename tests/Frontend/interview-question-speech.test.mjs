import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const layouts = ['desktop', 'mobile'];

function functionSource(source, name) {
    const functionStart = source.indexOf(`function ${name}(`);
    assert.ok(functionStart >= 0, `${name} was not found`);
    const start = source.slice(functionStart - 6, functionStart) === 'async ' ? functionStart - 6 : functionStart;
    const open = source.indexOf('{', source.indexOf(') {', functionStart));
    let depth = 0;
    for (let index = open; index < source.length; index++) {
        if (source[index] === '{') depth++;
        if (source[index] === '}' && --depth === 0) return source.slice(start, index + 1);
    }
    assert.fail(`${name} has no closing brace`);
}

function deferred() {
    let resolve;
    const promise = new Promise(done => { resolve = done; });
    return { promise, resolve };
}

function evaluate(source, names, context) {
    vm.createContext(context);
    vm.runInContext(names.map(name => functionSource(source, name)).join('\n'), context);
    return context;
}

for (const layout of layouts) {
    const source = readFileSync(join(root, `resources/views/${layout}/interview/session.blade.php`), 'utf8');

    test(`${layout} shows and speaks an uncached question without awaiting server TTS`, async () => {
        const server = deferred();
        const shown = { innerText: '...' };
        const spoken = [];
        const context = evaluate(source, ['speakQuestion'], {
            interviewTerminated: false,
            questionSpeechToken: 0,
            activeSpeechCompletion: null,
            isRecording: false,
            serverAiVoiceEnabled: true,
            serverVoiceUnavailable: false,
            serverSpeechUrlCache: new Map(),
            window: { speechSynthesis: {}, SpeechSynthesisUtterance() {} },
            document: { getElementById: () => shown },
            renderStaticQuestionCaption() {},
            cleanTranscriptText: value => value.trim(),
            estimatedSpeechTimeoutMs: () => 10000,
            setTimeout: () => 1,
            cancelQuestionSpeechOutput() { context.questionSpeechToken++; },
            serverSpeechUrl() { return server.promise; },
            speakWithServerVoice() { assert.fail('uncached server speech should not hold up a question'); },
            speakWithDeviceVoice(text, token) {
                spoken.push(text);
                context.activeSpeechCompletion.resolve('finished');
            },
        });

        const result = context.speakQuestion('Tell me about your project.', { questionId: 12 });
        assert.equal(shown.innerText, 'Tell me about your project.');
        assert.deepEqual(spoken, ['Tell me about your project.']);
        assert.equal(await result, 'finished');
        server.resolve('blob:later');
        await server.promise;
        assert.equal(spoken.length, 1, 'late server audio must not interrupt device speech');
    });

    test(`${layout} coalesces speech requests and caches completed audio`, async () => {
        const response = deferred();
        let requests = 0;
        const context = evaluate(source, ['serverSpeechUrl'], {
            interviewSessionId: 7,
            serverVoiceUnavailable: false,
            serverSpeechUrlCache: new Map(),
            serverSpeechRequestCache: new Map(),
            cleanTranscriptText: value => value.trim(),
            FormData: class FormData { append() {} },
            URL: { createObjectURL: () => 'blob:question-12' },
            managedFetch(_url, options) {
                requests++;
                assert.equal(options.timeoutMs, 12000);
                return response.promise;
            },
            console,
        });

        const first = context.serverSpeechUrl(12);
        const second = context.serverSpeechUrl(12);
        assert.equal(requests, 1);
        response.resolve({ ok: true, blob: async () => ({ size: 256 }) });
        assert.equal(await first, 'blob:question-12');
        assert.equal(await second, 'blob:question-12');
        assert.equal(await context.serverSpeechUrl(12), 'blob:question-12');
        assert.equal(requests, 1);
    });

    test(`${layout} bounds server playback and ignores late audio starts`, async () => {
        const playback = deferred();
        const timers = [];
        const events = new Map();
        let paused = false;
        let speechUiStarts = 0;
        const audio = {
            addEventListener(name, callback) { events.set(name, callback); },
            play() { return playback.promise; },
            pause() { paused = true; },
        };
        const context = evaluate(source, ['speakWithServerVoice'], {
            window: { Audio() {}, speechSynthesis: {}, SpeechSynthesisUtterance() {} },
            Audio: function Audio() { return audio; },
            interviewerServerAudioPlaybackRate: 1,
            serverVoiceUnavailable: false,
            interviewTerminated: false,
            questionSpeechToken: 3,
            activeQuestionAudio: null,
            serverSpeechUrl: async () => 'blob:cached',
            startSpeakingUi() { speechUiStarts++; },
            finishSpeakingUi() {},
            speakWithDeviceVoice() {},
            cancelQuestionAudio() {
                context.activeQuestionAudio?.pause();
                context.activeQuestionAudio = null;
            },
            setTimeout(callback, delay) { timers.push({ callback, delay }); },
            console,
        });

        const result = context.speakWithServerVoice('Question', 3, true, 12);
        for (let turn = 0; turn < 10 && timers.length === 0; turn++) await Promise.resolve();
        assert.equal(timers[0]?.delay, 700);
        timers[0].callback();
        assert.equal(await result, false);
        assert.equal(paused, true);
        playback.resolve();
        await playback.promise;
        events.get('playing')?.();
        assert.equal(speechUiStarts, 0, 'late audio must not overlap device fallback');
    });

    test(`${layout} releases the question when device speech never starts`, () => {
        const timers = [];
        let utterance;
        let cancels = 0;
        let spokenUiStarts = 0;
        let finishes = 0;
        const context = evaluate(source, ['speakWithDeviceVoice'], {
            interviewTerminated: false,
            questionSpeechToken: 4,
            speechLocale: 'en-US',
            interviewerSpeechRate: 1,
            preferredVoice: null,
            currentAmplitude: 0,
            window: {
                speechSynthesis: {
                    cancel() { cancels++; },
                    speak(value) { utterance = value; },
                },
                SpeechSynthesisUtterance: function SpeechSynthesisUtterance() {},
            },
            SpeechSynthesisUtterance: function SpeechSynthesisUtterance() {},
            setTimeout(callback, delay) { timers.push({ callback, delay }); return timers.length; },
            clearTimeout() {},
            startSpeakingUi() { spokenUiStarts++; },
            finishSpeakingUi() { finishes++; },
            console,
        });

        context.speakWithDeviceVoice('Question', 4, true);
        assert.equal(timers[0]?.delay, 1200);
        assert.equal(finishes, 0);
        timers[0].callback();
        assert.equal(cancels, 2);
        assert.equal(finishes, 1);
        utterance.onstart();
        utterance.onend();
        assert.equal(spokenUiStarts, 0, 'late device speech must stay canceled');
        assert.equal(finishes, 1);
    });

    test(`${layout} ignores completion from an older question load`, async () => {
        const firstSpeech = deferred();
        const secondSpeech = deferred();
        const speeches = [firstSpeech, secondSpeech];
        const elements = { aiQuestionText: { innerText: '' }, qCounter: { innerText: '' } };
        const enabled = [];
        let timerStarts = 0;
        const context = evaluate(source, ['loadQuestion'], {
            interviewTerminated: false,
            interviewEnding: false,
            questionLoadToken: 0,
            currentQIdx: 0,
            questions: [
                { id: 1, question_text: 'First question' },
                { id: 2, question_text: 'Second question' },
            ],
            targetQuestionCount: 2,
            document: { getElementById: id => elements[id] },
            displayedQuestionIds: new Set(),
            answersData: [],
            defaultAnswerState: () => ({}),
            stopQuestionTimer() {},
            resetAiCoachPanel() {},
            setAnswerInputEnabled(value) { enabled.push(value); },
            isOpeningQuestion: () => false,
            questionDisplayNumber: index => index + 1,
            appendChatMessage() {},
            updateQuestionTimerDisplay() {},
            setCurrentAnswerTextareaText() {},
            applyResponseModeUi() {},
            resetSpeechRecognitionBufferFromTextarea() {},
            renderVoiceSessionPanel() {},
            setRepeatPrompt() {},
            speakQuestion: () => speeches.shift().promise,
            startQuestionTimer() { timerStarts++; },
            triggerAnalysis() {},
            scheduleStateSave() {},
            updateSendAnswerButtonState() {},
            isSubmittingAnswer: false,
        });

        const first = context.loadQuestion(0);
        const second = context.loadQuestion(1);
        firstSpeech.resolve('cancelled');
        await first;
        assert.equal(context.questionLoadToken, 2);
        assert.equal(enabled.filter(Boolean).length, 0);
        assert.equal(timerStarts, 0);
        secondSpeech.resolve('finished');
        await second;
        assert.equal(enabled.filter(Boolean).length, 1);
        assert.equal(timerStarts, 1);
    });
}
