import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..', '..');
const read = (relativePath) => readFileSync(join(root, relativePath), 'utf8');

const primary = '#2563eb';
const secondary = '#06b6d4';
const legacyBrandGradient = /--grad:\s*linear-gradient\([^;]*#d97706[^;]*#a35405/i;

test('system brand defaults use the established blue and cyan palette', () => {
    const settings = read('app/Support/SystemSettings.php');
    const provider = read('app/Providers/AppServiceProvider.php');

    assert.match(settings, /BRAND_PRIMARY\s*=\s*'#2563eb'/);
    assert.match(settings, /BRAND_SECONDARY\s*=\s*'#06b6d4'/);
    assert.doesNotMatch(settings, /'color_primary'[^\n]*#d97706/i);
    assert.doesNotMatch(settings, /'color_secondary'[^\n]*#a35405/i);
    assert.match(provider, /SystemSettings::BRAND_PRIMARY/);
    assert.match(provider, /SystemSettings::BRAND_SECONDARY/);
});

test('desktop and mobile brand tokens no longer assign orange as a primary color', () => {
    for (const relativePath of ['public/css/desktop/style.css', 'public/css/mobile/style.css']) {
        const source = read(relativePath);

        assert.match(source, new RegExp(`--pur:\\s*${primary}`, 'i'));
        assert.match(source, new RegExp(`--blu:\\s*${secondary}`, 'i'));
        assert.match(source, /--grad:\s*linear-gradient\(135deg,\s*#2563eb(?:\s+0%)?,\s*#06b6d4(?:\s+100%)?\)/i);
        assert.doesNotMatch(source, /--pur:\s*#(?:d97706|f59e0b)/i);
        assert.doesNotMatch(source, legacyBrandGradient);
    }

    const desktop = read('public/css/desktop/style.css');
    assert.match(desktop, /--saas-blue:\s*#2563eb/i);
    assert.doesNotMatch(desktop, /--saas-blue:\s*#(?:d97706|f59e0b)/i);
    assert.match(desktop, /--saas-amber:\s*#d97706/i, 'semantic warning amber must remain distinct from branding');
});

test('desktop Start Interview action resolves through the blue primary token', () => {
    const reports = read('resources/views/desktop/user/reports.blade.php');
    const desktop = read('public/css/desktop/style.css');

    assert.match(reports, /class="btn btn-primary btn-shine report-start-btn"[^>]*>.*Start Interview<\/a>/);
    assert.match(desktop, /:is\(\.btn-primary, \.btn-shine,[\s\S]*?background:\s*var\(--saas-blue\)\s*!important;/);
    assert.match(desktop, /--saas-blue:\s*#2563eb/i);
});

test('layout fallbacks expose primary blue and secondary cyan independently', () => {
    const layouts = [
        'resources/views/desktop/layouts/app.blade.php',
        'resources/views/desktop/layouts/guest.blade.php',
        'resources/views/desktop/layouts/admin.blade.php',
        'resources/views/mobile/layouts/app.blade.php',
        'resources/views/mobile/layouts/guest.blade.php',
        'resources/views/mobile/layouts/admin.blade.php',
    ];

    for (const relativePath of layouts) {
        const source = read(relativePath);

        assert.match(source, /--pur:\s*\{\{\s*\$systemPrimaryColor\s*\?\?\s*'#2563eb'\s*\}\}/);
        assert.match(source, /--blu:\s*\{\{\s*\$systemSecondaryColor\s*\?\?\s*'#06b6d4'\s*\}\}/);
        assert.doesNotMatch(source, /--(?:pur|blu):[^;]*#(?:d97706|a35405)/i);
        assert.doesNotMatch(source, /--grad:[^;]*#d97706[^;]*#a35405/i);
    }
});
