import { readFile, writeFile } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { transform } from 'lightningcss';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const checkOnly = process.argv.includes('--check');
const stylesheets = [
    'desktop/style.css',
    'mobile/style.css',
    'desktop/dashboard.css',
    'mobile/dashboard.css',
    'desktop/admin/dashboard.css',
    'mobile/admin/dashboard.css',
];

for (const stylesheet of stylesheets) {
    const sourcePath = join(root, 'public', 'css', stylesheet);
    const outputPath = sourcePath.replace(/\.css$/u, '.min.css');
    const source = await readFile(sourcePath);
    const result = transform({
        filename: sourcePath,
        code: source,
        minify: true,
        sourceMap: false,
    });

    if (result.warnings.length > 0) {
        throw new Error(`${stylesheet}: ${result.warnings.map(warning => warning.message).join('; ')}`);
    }

    if (checkOnly) {
        const existing = await readFile(outputPath);
        if (!existing.equals(result.code)) {
            throw new Error(`${stylesheet}: minified CSS is out of date. Run npm run build.`);
        }
    } else {
        await writeFile(outputPath, result.code);
    }

    console.log(`${stylesheet}: ${source.length} -> ${result.code.length} bytes`);
}
