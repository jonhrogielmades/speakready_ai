import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const questionTypes = ['Behavioral', 'Situational', 'Technical', 'Personal'];

for (const layout of ['desktop', 'mobile']) {
    const source = readFileSync(join(root, `resources/views/${layout}/interview/setup.blade.php`), 'utf8');

    test(`${layout} setup offers and preserves the four supported question types`, () => {
        const groupStart = source.indexOf('id="questionTypeGroup"');
        const options = source.slice(groupStart).match(/@foreach\(\[([\s\S]*?)\] as \$questionType => \$questionIcon\)/)?.[1];
        assert.ok(options, 'question type choices must be present');
        const actualTypes = [...options.matchAll(/'([^']+)'\s*=>/g)].map(match => match[1]);
        assert.deepEqual(actualTypes, questionTypes);
        assert.match(source, /\$selectedQuestionTypes = old\('question_types', \[\]\);/);
        assert.match(source, /name="question_types\[\]" value="\{\{ \$questionType \}\}" \{\{ in_array\(\$questionType, \$selectedQuestionTypes, true\)\? 'checked': '' \}\}/);
    });

    test(`${layout} setup keeps server validated choices instead of restoring a stale draft`, () => {
        const expression = source.match(/const restoredSetupDraft = ([^;]+);/)?.[1];
        assert.ok(expression, 'draft restore decision must be present');
        assert.match(expression, /@json\(!\$errors->any\(\)\)/);

        for (const hasValidationErrors of [false, true]) {
            let restoreCount = 0;
            const setupDraftApi = { restore() { restoreCount++; return { values: { 'question_types[]': ['Technical'] } }; } };
            const setupDraftFormElement = {};
            const renderedExpression = expression.replace('@json(!$errors->any())', String(!hasValidationErrors));
            const restored = Function('setupDraftApi', 'setupDraftFormElement', `return ${renderedExpression};`)(setupDraftApi, setupDraftFormElement);
            assert.equal(restoreCount, hasValidationErrors ? 0 : 1);
            assert.equal(restored === null, hasValidationErrors);
        }
    });
}
