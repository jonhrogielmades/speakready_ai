import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const sessionViews = [
    'resources/views/desktop/interview/session.blade.php',
    'resources/views/mobile/interview/session.blade.php',
];

for (const relativePath of sessionViews) {
    const source = readFileSync(join(root, relativePath), 'utf8');
    const checklistStart = source.indexOf('function startFinishReviewChecklist()');
    const checklistEnd = source.indexOf('function setFinishTransitionVisible', checklistStart);
    const checklistSource = source.slice(checklistStart, checklistEnd);

    test(`${relativePath} waits for the supported report-finalization window`, () => {
        const requestTimeout = Number(source.match(/const finishReviewRequestTimeoutMs = (\d+);/)?.[1]);
        const retryWindow = Number(source.match(/const finishReviewProcessingRetryWindowMs = (\d+);/)?.[1]);

        assert.ok(requestTimeout >= 195_000, 'request timeout must allow a 180-second server run plus transport overhead');
        assert.ok(retryWindow >= 180_000, 'processing retries must cover the server finalization deadline');
        assert.match(source, /timeoutMs: finishReviewRequestTimeoutMs/);
        assert.doesNotMatch(source, /timeoutMs: 45000/);
    });

    test(`${relativePath} only completes checklist rows after server success`, () => {
        assert.ok(checklistStart >= 0 && checklistEnd > checklistStart, 'finish checklist functions were not found');
        assert.match(checklistSource, /aria-label', 'In progress'/);
        assert.doesNotMatch(checklistSource, /classList\.add\('is-complete'\)/);
        assert.match(source, /function completeFinishReviewChecklist\(\)[\s\S]*?updateFinishReviewChecklistDetails\(true\)[\s\S]*?classList\.add\('is-complete'\)/);
    });

    test(`${relativePath} focuses retry after a report failure`, () => {
        assert.match(source, /requestAnimationFrame\(\(\) => retryButton\?\.focus\(\)\)/);
        assert.match(source, /error\?\.name === 'AbortError'/);
    });
}
