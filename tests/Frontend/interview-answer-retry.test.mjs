import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');

function functionSource(source, name) {
    let start = source.indexOf(`function ${name}(`);
    assert.ok(start >= 0, `${name} was not found`);
    if (source.slice(start - 6, start) === 'async ') start -= 6;
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

function submissionHarness(source, mode, { final = false, failures = 1, abort = false, closingFails = false, savedFailure = false } = {}) {
    const calls = { forms: [], renderedAtRequest: [], notices: [], inputStates: [], timerStarts: 0, loaded: [], finish: 0 };
    const renderedMessages = [];
    const previousHistory = [{ role: 'interviewer', text: 'Tell me about your experience.' }];
    const chatContainer = { style: {}, appendChild() {} };
    const thinkingBubble = { remove() {} };
    let requestCount = 0;
    const context = {
        console: { warn() {}, error() {} },
        FormData,
        answerText: mode === 'voice' ? 'I resolved a customer issue.' : 'I resolved a customer issue.',
        canonicalResponseMode: mode,
        questions: [{ id: 1, question_text: 'Tell me about your experience.' }],
        currentQIdx: 0,
        answersData: [{ text: '', speech_transcript: '', elapsed_seconds: 0, voice_duration: 0 }],
        interviewChatHistory: previousHistory.slice(),
        pendingTimedOutSubmission: false,
        isSubmittingAnswer: false,
        interviewEnding: false,
        interviewTerminated: false,
        finalAnswerSubmitted: false,
        pendingSavedAnswerRetry: null,
        voiceSessionRecordings: new Map(mode === 'voice' ? [['q1', { blob: { size: 256 }, transcriptionVerified: true, durationSeconds: 8 }]] : []),
        voiceSessionTranscriptionMaxBytes: 25600 * 1024,
        interviewSessionId: 10,
        document: {
            getElementById(id) { return id === 'chatTranscriptContainer' ? chatContainer : id === 'thinkingBubble' ? thinkingBubble : null; },
            createElement() { return { style: {}, innerHTML: '' }; },
        },
        stopQuestionTimer() {},
        startQuestionTimer() { calls.timerStarts++; },
        updateSendAnswerButtonState() {},
        finalizeCurrentTranscriptionForSubmit: async () => {},
        syncHybridAnswerStateFromTextarea() {},
        currentAnswerTextareaText() { return context.answerText; },
        setCurrentAnswerTextareaText(value) { context.answerText = value; },
        isVoiceTranscriptionMode() { return mode !== 'text'; },
        isVoiceOnlyMode() { return mode === 'voice'; },
        voiceSessionKeyFor() { return 'q1'; },
        submittableVoiceSessionRecording() { return mode === 'voice'; },
        setAnswerInputEnabled(enabled) { calls.inputStates.push(enabled); },
        appendChatMessage(role, text) { renderedMessages.push({ role, text }); context.interviewChatHistory.push({ role, text }); },
        clearSubmittedAnswerInput() { context.answerText = ''; renderedMessages.length = 0; },
        restoreSubmittedAnswerInput(value) { context.answerText = value; },
        restoreChatHistory() { renderedMessages.splice(0, renderedMessages.length, ...context.interviewChatHistory); },
        scheduleStateSave() {},
        isOpeningQuestion() { return false; },
        isLastScoredQuestion() { return final; },
        isPenultimateScoredQuestion() { return false; },
        showSessionNotice(message) { calls.notices.push(message); },
        focusAnswerTextarea() {},
        captureTranscriptTimeline() {},
        observationDataForSubmit() { return {}; },
        appendVoiceSessionRecordingUpload() {},
        getQuestionElapsedSeconds() { return 0; },
        async postFormJsonWithRetry(url, form) {
            calls.forms.push(form);
            calls.renderedAtRequest.push(renderedMessages.map(message => message.text));
            requestCount++;
            if (requestCount <= failures) {
                const error = new Error('Request failed');
                if (abort) error.name = 'AbortError';
                if (savedFailure) {
                    error.status = 503;
                    error.payload = { answer_saved: true, error_code: 'ai_interview_follow_up_unavailable' };
                }
                throw error;
            }
            return { success: true, next_question_id: 2, next_question_text: 'What did you do next?' };
        },
        async saveCurrentAnswer() {
            requestCount++;
            if (requestCount <= failures) throw new Error('Save failed');
        },
        async concludeAndFinishInterview() { if (closingFails) throw new Error('Closing failed'); },
        async finishInterview() { calls.finish++; },
        placeNextQuestion(question) { context.questions.push(question); context.answersData.push({}); return 1; },
        async loadQuestion(index) { calls.loaded.push(index); },
    };
    vm.createContext(context);
    vm.runInContext([
        functionSource(source, 'restoreFailedAnswerSubmission'),
        functionSource(source, 'retrySavedNextQuestion'),
        functionSource(source, 'submitAnswer'),
    ].join('\n'), context);
    return { context, calls, previousHistory, renderedMessages };
}

for (const layout of ['desktop', 'mobile']) {
    const source = readFileSync(join(root, `resources/views/${layout}/interview/session.blade.php`), 'utf8');

    for (const mode of ['text', 'voice', 'hybrid']) {
        test(`${layout} ${mode} restores a failed answer without duplicating conversation context`, async () => {
            const { context, calls, previousHistory } = submissionHarness(source, mode);
            await context.submitAnswer();
            assert.equal(context.isSubmittingAnswer, false);
            assert.equal(context.answerText, 'I resolved a customer issue.');
            assert.deepEqual(context.interviewChatHistory, previousHistory);
            assert.deepEqual(calls.renderedAtRequest[0], ['I resolved a customer issue.'], 'the submitted answer stays visible while waiting');
            assert.equal(calls.timerStarts, 1);

            await context.submitAnswer();
            assert.equal(context.interviewChatHistory.filter(message => message.role === 'user').length, 1);
            assert.deepEqual(calls.loaded, [1]);
        });

        test(`${layout} ${mode} retries question generation without resending a saved answer`, async () => {
            const { context, calls, previousHistory } = submissionHarness(source, mode, { savedFailure: true });
            await context.submitAnswer();
            assert.equal(context.pendingSavedAnswerRetry.questionId, 1);
            assert.equal(context.answerText, '');
            assert.equal(context.interviewChatHistory.length, previousHistory.length + 1);
            assert.equal(calls.timerStarts, 0);
            assert.equal(calls.inputStates.at(-1), false);

            await context.submitAnswer();
            assert.equal(calls.forms[1].get('resume_saved_answer'), '1');
            assert.equal(calls.forms[1].get('question_id'), '1');
            assert.equal(calls.forms[1].has('answer_text'), false);
            assert.equal(calls.forms[1].has('voice_audio'), false);
            assert.equal(context.interviewChatHistory.filter(message => message.role === 'user').length, 1);
            assert.equal(context.pendingSavedAnswerRetry, null);
            assert.deepEqual(calls.loaded, [1]);
        });
    }

    test(`${layout} restores input after an aborted answer request`, async () => {
        const { context, previousHistory } = submissionHarness(source, 'text', { abort: true });
        await context.submitAnswer();
        assert.equal(context.answerText, 'I resolved a customer issue.');
        assert.deepEqual(context.interviewChatHistory, previousHistory);
    });

    test(`${layout} keeps timeout status for manual retry without another automatic submission`, async () => {
        const { context, calls } = submissionHarness(source, 'text');
        context.answerText = '';
        await context.submitAnswer({ timedOut: true });
        assert.equal(context.pendingTimedOutSubmission, true);
        assert.equal(calls.timerStarts, 0, 'an expired timer must stay stopped after a request failure');

        await context.submitAnswer();
        assert.equal(calls.forms[1].get('timed_out'), 'true');
        assert.equal(calls.forms[1].get('is_skipped'), 'true');
        assert.equal(context.pendingTimedOutSubmission, false);
        assert.equal(context.interviewChatHistory.filter(message => message.role === 'user').length, 1);
    });

    test(`${layout} rolls back a failed final answer save`, async () => {
        const { context, previousHistory } = submissionHarness(source, 'hybrid', { final: true });
        await context.submitAnswer();
        assert.equal(context.answerText, 'I resolved a customer issue.');
        assert.deepEqual(context.interviewChatHistory, previousHistory);
        assert.equal(context.finalAnswerSubmitted, false);
        await context.submitAnswer();
        assert.equal(context.interviewChatHistory.filter(message => message.role === 'user').length, 1);
        assert.equal(context.finalAnswerSubmitted, true);
    });

    test(`${layout} does not resubmit a saved final answer when closing fails`, async () => {
        const { context, calls } = submissionHarness(source, 'text', { final: true, failures: 0, closingFails: true });
        await context.submitAnswer();
        assert.equal(context.finalAnswerSubmitted, true);
        assert.equal(context.interviewChatHistory.filter(message => message.role === 'user').length, 1);
        assert.equal(calls.finish, 1);
        assert.equal(context.answerText, '');
    });
}
