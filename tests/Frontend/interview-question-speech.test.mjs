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

    test(`${layout} waits for AI audio before showing question captions`, async () => {
        const server = deferred();
        const shown = { innerText: '...' };
        const notices = [];
        let finished = false;
        let serverCalls = 0;
        let watchdogs = 0;
        let earlyCaptions = 0;
        const context = evaluate(source, ['speakQuestion'], {
            interviewTerminated: false,
            questionSpeechToken: 0,
            activeSpeechCompletion: null,
            isRecording: false,
            serverAiVoiceEnabled: true,
            window: { speechSynthesis: {}, SpeechSynthesisUtterance() {} },
            document: { getElementById: () => shown },
            renderStaticQuestionCaption() { earlyCaptions++; },
            cleanTranscriptText: value => value.trim(),
            setTimeout: () => { watchdogs++; return 1; },
            cancelQuestionSpeechOutput() { context.questionSpeechToken++; },
            async speakWithServerVoice() {
                serverCalls++;
                await server.promise;
                context.activeSpeechCompletion.resolve('finished');
                return true;
            },
            speakWithDeviceVoice() { assert.fail('interviewer must not use browser speech'); },
            showSessionNotice(message) { notices.push(message); },
            finishSpeakingUi() { assert.fail('server audio is still pending'); },
        });

        const result = context.speakQuestion('Tell me about your project.', { questionId: 12 });
        result.then(() => { finished = true; });
        assert.equal(shown.innerText, 'Tell me about your project.');
        assert.equal(serverCalls, 1);
        assert.equal(watchdogs, 0, 'speech completion must not time out while audio is being fetched');
        assert.equal(earlyCaptions, 0, 'the full question must stay hidden while audio loads');
        await Promise.resolve();
        assert.equal(finished, false);
        assert.deepEqual(notices, []);
        server.resolve();
        assert.equal(await result, 'finished');
        assert.equal(serverCalls, 1);
        assert.equal(earlyCaptions, 0);
    });

    test(`${layout} starts highlighted captions when server audio plays`, async () => {
        const voiceUrl = deferred();
        const events = new Map();
        let captionsStarted = 0;
        let captionsFinished = 0;
        const audio = {
            addEventListener(name, callback) { events.set(name, callback); },
            play: async () => {},
        };
        const context = evaluate(source, ['speakWithServerVoice'], {
            window: { Audio() {} },
            Audio: function Audio() { return audio; },
            interviewerServerAudioPlaybackRate: 1,
            serverVoiceUnavailable: false,
            interviewTerminated: false,
            questionSpeechToken: 3,
            activeQuestionAudio: null,
            serverSpeechUrl: () => voiceUrl.promise,
            startSpeakingUi() { captionsStarted++; },
            finishSpeakingUi() { captionsFinished++; },
            activeSpeechCompletion: { token: 3, timeoutId: null },
            estimatedSpeechTimeoutMs: () => 10000,
            setTimeout: () => 1,
            console,
        });

        const result = context.speakWithServerVoice('Tell me about your project.', 3, true, 12);
        assert.equal(captionsStarted, 0);
        voiceUrl.resolve('blob:question-12');
        assert.equal(await result, true);
        assert.equal(captionsStarted, 0, 'loading and play() must not display captions early');
        events.get('playing')();
        assert.equal(captionsStarted, 1);
        events.get('ended')();
        assert.equal(captionsFinished, 1);
    });

    test(`${layout} keeps the question readable and continues when server audio is unavailable`, async () => {
        const shown = { innerText: '...' };
        const notices = [];
        let finished = 0;
        const context = evaluate(source, ['speakQuestion'], {
            interviewTerminated: false,
            questionSpeechToken: 0,
            activeSpeechCompletion: null,
            isRecording: false,
            serverAiVoiceEnabled: true,
            document: { getElementById: () => shown },
            renderStaticQuestionCaption() {},
            cleanTranscriptText: value => value.trim(),
            cancelQuestionSpeechOutput() { context.questionSpeechToken++; },
            speakWithServerVoice: async () => false,
            speakWithDeviceVoice() { assert.fail('interviewer must not use browser speech'); },
            showSessionNotice(message, type) { notices.push([message, type]); },
            finishSpeakingUi(_text, token) {
                finished++;
                context.activeSpeechCompletion.resolve('finished');
            },
        });

        assert.equal(await context.speakQuestion('Tell me about your project.', { questionId: 12 }), 'finished');
        assert.equal(shown.innerText, 'Tell me about your project.');
        assert.equal(finished, 1);
        assert.equal(notices.length, 1);
        assert.match(notices[0][0], /audio is unavailable/i);
        assert.equal(notices[0][1], 'warning');
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
            setTimeout,
            clearTimeout,
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

    test(`${layout} retries server audio after a transient 503`, async () => {
        let requests = 0;
        const context = evaluate(source, ['serverSpeechUrl'], {
            interviewSessionId: 7,
            serverVoiceUnavailable: false,
            serverSpeechUrlCache: new Map(),
            serverSpeechRequestCache: new Map(),
            cleanTranscriptText: value => value.trim(),
            FormData: class FormData { append() {} },
            URL: { createObjectURL: () => 'blob:question-12' },
            managedFetch() {
                requests++;
                return Promise.resolve(requests === 1
                    ? { ok: false, status: 503 }
                    : { ok: true, blob: async () => ({ size: 256 }) });
            },
            setTimeout,
            clearTimeout,
            console,
        });

        assert.equal(await context.serverSpeechUrl(12), null);
        assert.equal(context.serverVoiceUnavailable, false);
        assert.equal(await context.serverSpeechUrl(12), 'blob:question-12');
        assert.equal(requests, 2);
    });

    test(`${layout} stops waiting when an audio response body stalls`, async () => {
        const timers = [];
        const context = evaluate(source, ['serverSpeechUrl'], {
            interviewSessionId: 7,
            serverVoiceUnavailable: false,
            serverSpeechUrlCache: new Map(),
            serverSpeechRequestCache: new Map(),
            cleanTranscriptText: value => value.trim(),
            FormData: class FormData { append() {} },
            URL: { createObjectURL() { assert.fail('stalled audio must not be cached'); } },
            managedFetch: async () => ({ ok: true, blob: () => new Promise(() => {}) }),
            setTimeout(callback, delay) { timers.push({ callback, delay }); return timers.length; },
            clearTimeout() {},
            console,
        });

        const result = context.serverSpeechUrl(12);
        for (let turn = 0; turn < 10 && timers.length === 0; turn++) await Promise.resolve();
        assert.equal(timers[0]?.delay, 12000);
        timers[0].callback();
        assert.equal(await result, null);
        assert.equal(context.serverSpeechRequestCache.size, 0);
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
            activeSpeechCompletion: { token: 3, timeoutId: null },
            estimatedSpeechTimeoutMs: () => 10000,
            showSessionNotice() {},
            cancelQuestionAudio() {
                context.activeQuestionAudio?.pause();
                context.activeQuestionAudio = null;
            },
            setTimeout(callback, delay) { timers.push({ callback, delay }); },
            console,
        });

        const result = context.speakWithServerVoice('Question', 3, true, 12);
        for (let turn = 0; turn < 10 && timers.length === 0; turn++) await Promise.resolve();
        assert.equal(timers[0]?.delay, 4000);
        timers[0].callback();
        assert.equal(await result, false);
        assert.equal(paused, true);
        playback.resolve();
        await playback.promise;
        events.get('playing')?.();
        assert.equal(speechUiStarts, 0, 'late audio must not start after playback timeout');
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
