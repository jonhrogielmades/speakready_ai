import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import vm from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');

function finishHarness(source, replies, { requiresProvider = true, waitMilliseconds = 2000 } = {}) {
    const start = source.indexOf('async function finishInterview()');
    const end = source.indexOf('function finishResponseErrorMessage(', start);
    assert.ok(start >= 0 && end > start, 'finishInterview handler must exist');
    const calls = { requests: [], waits: [], completed: [], draftsCleared: 0, notices: [], readiness: [] };
    const nodes = new Map();
    let now = 1000;
    const node = (id) => {
        if (!nodes.has(id)) nodes.set(id, {
            action: '/interview/finish', value: '', textContent: '', innerHTML: '', hidden: false,
            style: {}, classList: { add() {}, remove() {} }, setAttribute() {}, focus() {},
        });
        return nodes.get(id);
    };
    const context = {
        console: { error() {} },
        Date: { now: () => now },
        FormData: class { constructor(form) { this.form = form; } },
        feedbackSubmissionInFlight: false,
        interviewTerminated: false,
        timerSeconds: 60,
        finishRequiresProviderReview: requiresProvider,
        finishReviewRequestTimeoutMs: 195000,
        finishReviewProcessingRetryWindowMs: 180000,
        document: { getElementById: node },
        window: {
            SpeakReadyInterviewSetupDraft: { clear() { calls.draftsCleared++; } },
            requestAnimationFrame(callback) { callback(); },
        },
        cleanupInterviewProcesses() {}, stopQuestionTimer() {}, startFinishReviewProgress() {},
        startFinishReviewChecklist() {}, setFinishTransitionVisible() {}, resetFinishReviewChecklist() {},
        clearFinishReviewProgressTimer() {},
        setFinishViewReviewReady(ready) { calls.readiness.push(ready); },
        async managedFetch(url, options) {
            calls.requests.push({ url, options });
            const reply = replies[Math.min(calls.requests.length - 1, replies.length - 1)];
            if (reply instanceof Error) throw reply;
            return { ok: reply.status >= 200 && reply.status < 300, ...reply };
        },
        async parseResponsePayload(response) { return { data: response.data || {} }; },
        async waitForFeedbackRetry(delay, attempt) {
            calls.waits.push({ delay, attempt, cleared: calls.draftsCleared, completed: calls.completed.length,
                message: node('finishTransitionMessage').textContent });
            now += waitMilliseconds;
        },
        async completeFinishReviewLoading(url) { calls.completed.push(url); },
        finishResponseErrorMessage() { return 'The feedback service returned an incomplete response.'; },
        showSessionNotice(message) { calls.notices.push(message); },
    };
    vm.createContext(context);
    vm.runInContext(source.slice(start, end), context);
    return { context, calls, nodes };
}

for (const layout of ['desktop', 'mobile']) {
    const source = readFileSync(join(root, `resources/views/${layout}/interview/session.blade.php`), 'utf8');

    test(`${layout} requires server-confirmed question reviews before completing analysis`, async () => {
        assert.match(source, /const finishRequiresProviderReview = @json\(! \$sessionRecord->game_level_id\);/);
        const { context, calls } = finishHarness(source, [
            { status: 200, data: { redirect_url: '/session/7/review', review_ready: true } },
        ]);
        assert.equal(await context.finishInterview(), true);
        assert.deepEqual(calls.completed, ['/session/7/review']);
        assert.equal(calls.draftsCleared, 1);
        assert.equal(calls.requests[0].options.timeoutMs, 195000);
        assert.equal(calls.notices.length, 0);
    });

    for (const reviewReady of [undefined, false, 'true', 1]) {
        test(`${layout} cannot mark a report ready for review_ready=${String(reviewReady)}`, async () => {
            const { context, calls, nodes } = finishHarness(source, [
                { status: 200, data: { redirect_url: '/session/7/review', review_ready: reviewReady } },
            ]);
            assert.equal(await context.finishInterview(), false);
            assert.deepEqual(calls.completed, []);
            assert.equal(calls.draftsCleared, 0, 'keep the draft until every question has ready feedback');
            assert.equal(context.feedbackSubmissionInFlight, false);
            assert.deepEqual(calls.readiness, [false]);
            assert.match(calls.notices[0], /answers are saved.*every question/);
            assert.equal(nodes.get('finishRetryButton').style.display, 'inline-flex');
        });
    }

    test(`${layout} stays analyzing through accepted and in-progress responses`, async () => {
        const { context, calls } = finishHarness(source, [
            { status: 202, data: { redirect_url: '/session/7/review', review_ready: false, retry_after_ms: 1500 } },
            { status: 409, data: { retry_after_ms: 2000 } },
            { status: 200, data: { redirect_url: '/session/7/review', review_ready: true } },
        ]);
        assert.equal(await context.finishInterview(), true);
        assert.equal(calls.requests.length, 3);
        assert.equal(calls.waits.length, 2);
        for (const wait of calls.waits) {
            assert.equal(wait.completed, 0);
            assert.equal(wait.cleared, 0);
            assert.match(wait.message, /analysis is still running/);
        }
        assert.deepEqual(calls.completed, ['/session/7/review']);
    });

    test(`${layout} bounds pending retries without pretending the report is complete`, async () => {
        const { context, calls } = finishHarness(source, [
            { status: 202, data: { redirect_url: '/session/7/review', review_ready: false } },
        ], { waitMilliseconds: 181000 });
        assert.equal(await context.finishInterview(), false);
        assert.equal(calls.requests.length, 2);
        assert.equal(calls.waits.length, 1);
        assert.deepEqual(calls.completed, []);
        assert.equal(calls.draftsCleared, 0);
        assert.match(calls.notices[0], /still processing/);
    });

    test(`${layout} keeps the separate game result protocol compatible`, async () => {
        const { context, calls } = finishHarness(source, [
            { status: 200, data: { redirect_url: '/game/result/7' } },
        ], { requiresProvider: false });
        assert.equal(await context.finishInterview(), true);
        assert.deepEqual(calls.completed, ['/game/result/7']);
    });

    test(`${layout} preserves answers and draft when the provider request times out`, async () => {
        const error = new Error('Request timed out');
        error.name = 'AbortError';
        const { context, calls } = finishHarness(source, [error]);
        assert.equal(await context.finishInterview(), false);
        assert.deepEqual(calls.completed, []);
        assert.equal(calls.draftsCleared, 0);
        assert.match(calls.notices[0], /answers are saved/);
    });
}
