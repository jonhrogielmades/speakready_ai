// Optional real-browser smoke test. First generate snapshots with:
//   PowerShell: $env:SR_FONT_SIZE_BROWSER_SNAPSHOT='1'; php artisan test --filter=UserFontSizeControlTest
//   Bash: SR_FONT_SIZE_BROWSER_SNAPSHOT=1 php artisan test --filter=UserFontSizeControlTest
// Then run: node tests/Frontend/user-font-size.browser.mjs
import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { existsSync, readFileSync, mkdtempSync, rmSync } from 'node:fs';
import { createServer } from 'node:http';
import { tmpdir } from 'node:os';
import { extname, join, resolve, sep } from 'node:path';

const projectRoot = resolve(import.meta.dirname, '..', '..');
const publicRoot = join(projectRoot, 'public');
const snapshotRoot = join(projectRoot, 'storage', 'app', 'font-size-browser');
const chrome = [
  process.env.CHROME_PATH,
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium',
].find(path => path && existsSync(path));

assert.ok(chrome, 'Install Chrome/Edge or set CHROME_PATH for the browser smoke test.');
for (const layout of ['desktop', 'mobile']) {
  assert.ok(existsSync(join(snapshotRoot, `${layout}.html`)), `Missing ${layout} HTML snapshot. Generate it with the command above.`);
}

let origin;
const server = createServer((request, response) => {
  const url = new URL(request.url, 'http://localhost');
  const layout = /^\/__font_size\/(desktop|mobile)$/.exec(url.pathname)?.[1];
  if (layout) {
    const html = readFileSync(join(snapshotRoot, `${layout}.html`), 'utf8')
      .replaceAll('http://localhost:8000', origin)
      .replaceAll('http://127.0.0.1:8000', origin);
    response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    response.end(html);
    return;
  }

  const asset = resolve(publicRoot, `.${decodeURIComponent(url.pathname)}`);
  if (!asset.startsWith(publicRoot + sep) || !existsSync(asset)) {
    response.writeHead(404);
    response.end('Not found');
    return;
  }
  const mime = {
    '.css': 'text/css', '.js': 'text/javascript', '.png': 'image/png',
    '.jpg': 'image/jpeg', '.svg': 'image/svg+xml', '.woff2': 'font/woff2',
    '.json': 'application/json', '.ico': 'image/x-icon',
  }[extname(asset).toLowerCase()] ?? 'application/octet-stream';
  response.writeHead(200, { 'Content-Type': mime });
  response.end(readFileSync(asset));
});

await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
origin = `http://127.0.0.1:${server.address().port}`;

const profile = mkdtempSync(join(tmpdir(), 'speakready-font-size-'));
const browser = spawn(chrome, [
  '--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
  '--disable-background-networking', '--remote-allow-origins=*',
  '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank',
], { stdio: 'ignore' });

const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
const deadline = Date.now() + 20000;
let debuggerPort;
while (Date.now() < deadline) {
  const portFile = join(profile, 'DevToolsActivePort');
  if (existsSync(portFile)) {
    debuggerPort = Number(readFileSync(portFile, 'utf8').split('\n')[0]);
    break;
  }
  if (browser.exitCode !== null) break;
  await pause(100);
}
assert.ok(debuggerPort, 'Chrome/Edge did not open its debugging port.');

let websocket;
let sequence = 0;
const pending = new Map();

function command(method, params = {}) {
  return new Promise((resolve, reject) => {
    const id = ++sequence;
    pending.set(id, { resolve, reject });
    websocket.send(JSON.stringify({ id, method, params }));
  });
}

function evaluate(expression) {
  return command('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true })
    .then(result => {
      if (result.exceptionDetails) throw new Error(result.exceptionDetails.text);
      return result.result.value;
    });
}

async function until(predicate, description, timeout = 25000) {
  const end = Date.now() + timeout;
  while (Date.now() < end) {
    const result = await predicate();
    if (result) return result;
    await pause(150);
  }
  throw new Error(`Timed out waiting for ${description}`);
}

async function clickAt(x, y, touch = false) {
  if (touch) {
    const point = { x, y, id: 1, radiusX: 1, radiusY: 1, force: 1 };
    await command('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [point] });
    await command('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
  } else {
    await command('Input.dispatchMouseEvent', { type: 'mouseMoved', x, y });
    await command('Input.dispatchMouseEvent', { type: 'mousePressed', x, y, button: 'left', clickCount: 1 });
    await command('Input.dispatchMouseEvent', { type: 'mouseReleased', x, y, button: 'left', clickCount: 1 });
  }
}

async function centerAndHit(selector) {
  return evaluate(`(() => {
    const element = document.querySelector(${JSON.stringify(selector)});
    if (!element) return null;
    const box = element.getBoundingClientRect();
    const x = box.left + box.width / 2, y = box.top + box.height / 2;
    const hit = document.elementFromPoint(x, y);
    return { x, y, width: box.width, height: box.height,
      hit: hit?.outerHTML?.slice(0, 200), hitsTarget: hit === element || element.contains(hit) };
  })()`);
}

try {
  const tabs = await fetch(`http://127.0.0.1:${debuggerPort}/json/list`).then(response => response.json());
  const page = tabs.find(tab => tab.type === 'page');
  assert.ok(page, 'Browser has no page target.');
  websocket = new WebSocket(page.webSocketDebuggerUrl);
  websocket.addEventListener('message', event => {
    const message = JSON.parse(event.data);
    if (!message.id) return;
    const promise = pending.get(message.id);
    if (!promise) return;
    pending.delete(message.id);
    message.error ? promise.reject(new Error(message.error.message)) : promise.resolve(message.result);
  });
  await new Promise((resolve, reject) => {
    websocket.addEventListener('open', resolve, { once: true });
    websocket.addEventListener('error', reject, { once: true });
  });
  await command('Page.enable');
  await command('Runtime.enable');
  await command('Network.enable');
  await command('Network.setBlockedURLs', { urls: ['https://*'] });
  // The install offer is an unrelated full-screen modal that can auto-open
  // while this test is clicking the header controls.
  await command('Page.addScriptToEvaluateOnNewDocument', {
    source: 'try { localStorage.setItem("speakready.pwa.install.dismissedAt", String(Date.now())); } catch (error) {}',
  });

  for (const scenario of [
    { layout: 'desktop', width: 1440, height: 900, touch: false, id: '#dbFontSizeBtn' },
    { layout: 'desktop', width: 1200, height: 800, touch: false, id: '#dbFontSizeBtn' },
    { layout: 'desktop', width: 1101, height: 768, touch: false, id: '#dbFontSizeBtn' },
    { layout: 'desktop', width: 1024, height: 768, touch: false, id: '#dbFontSizeBtn' },
    { layout: 'desktop', width: 992, height: 768, touch: false, id: '#dbFontSizeBtn' },
    { layout: 'mobile', width: 390, height: 844, touch: true, id: '#mobFontSizeBtn' },
    { layout: 'mobile', width: 390, height: 844, touch: true, id: '#mobFontSizeBtn', theme: 'dark' },
    { layout: 'mobile', width: 320, height: 720, touch: true, id: '#mobFontSizeBtn' },
    { layout: 'mobile', width: 280, height: 653, touch: true, id: '#mobFontSizeBtn' },
  ]) {
    await command('Emulation.setDeviceMetricsOverride', {
      width: scenario.width, height: scenario.height, deviceScaleFactor: 1, mobile: scenario.touch,
    });
    await command('Emulation.setTouchEmulationEnabled', { enabled: scenario.touch, maxTouchPoints: 1 });
    await command('Page.navigate', { url: `${origin}/__font_size/${scenario.layout}` });
    await until(() => evaluate(`Boolean(document.querySelector(${JSON.stringify(scenario.id)}) && window.SpeakReadyFontSize)`), 'font control setup');
    if (scenario.theme) {
      await evaluate(`window.SpeakReadyTheme.apply(${JSON.stringify(scenario.theme)}, false)`);
    }

    const toggle = await centerAndHit(scenario.id);
    assert.ok(toggle?.hitsTarget, `${scenario.layout} ${scenario.width}px toggle is covered: ${JSON.stringify(toggle)}`);
    await clickAt(toggle.x, toggle.y, scenario.touch);
    const expanded = await evaluate(`document.querySelector(${JSON.stringify(scenario.id)}).getAttribute('aria-expanded')`);
    assert.equal(expanded, 'true', `${scenario.layout} ${scenario.width}px toggle did not open`);

    const increase = await centerAndHit('[data-font-size-action="increase"]');
    assert.ok(increase?.hitsTarget, `${scenario.layout} ${scenario.width}px increase action is covered: ${JSON.stringify(increase)}`);
    const originalTextSize = await evaluate('parseFloat(getComputedStyle(document.querySelector(".sr-font-size-menu-title")).fontSize)');
    await clickAt(increase.x, increase.y, scenario.touch);
    const scale = await evaluate('window.SpeakReadyFontSize.get()');
    assert.equal(scale, 1.1, `${scenario.layout} ${scenario.width}px increase failed`);
    const enlargedTextSize = await evaluate('parseFloat(getComputedStyle(document.querySelector(".sr-font-size-menu-title")).fontSize)');
    assert.ok(enlargedTextSize > originalTextSize, 'visible text did not grow');
    const increaseAgain = await centerAndHit('[data-font-size-action="increase"]');
    assert.ok(increaseAgain?.hitsTarget, 'increase action moved under another element');
    await clickAt(increaseAgain.x, increaseAgain.y, scenario.touch);
    assert.equal(await evaluate('window.SpeakReadyFontSize.get()'), 1.2);
    assert.equal(await evaluate('document.querySelector("[data-font-size-action=increase]").disabled'), true);
    assert.equal(await evaluate('localStorage.getItem("speakready.user.fontScale.v1")'), '1.2');

    await command('Page.reload', { ignoreCache: true });
    await until(() => evaluate('Boolean(window.SpeakReadyFontSize)'), 'control after reload');
    assert.equal(await evaluate('window.SpeakReadyFontSize.get()'), 1.2, 'scale did not persist after reload');

    const toggleAgain = await centerAndHit(scenario.id);
    assert.ok(toggleAgain?.hitsTarget, `${scenario.layout} ${scenario.width}px toggle after reload is covered: ${JSON.stringify(toggleAgain)}`);
    await clickAt(toggleAgain.x, toggleAgain.y, scenario.touch);
    assert.equal(await evaluate(`document.querySelector(${JSON.stringify(scenario.id)}).getAttribute('aria-expanded')`), 'true', 'toggle did not reopen after reload');
    for (const expected of [1.1, 1, 0.9, 0.8]) {
      const decrease = await centerAndHit('[data-font-size-action="decrease"]');
      assert.ok(decrease?.hitsTarget, `${scenario.layout} ${scenario.width}px decrease action is covered: ${JSON.stringify(decrease)}`);
      await clickAt(decrease.x, decrease.y, scenario.touch);
      assert.equal(await evaluate('window.SpeakReadyFontSize.get()'), expected);
    }
    assert.equal(await evaluate('document.querySelector("[data-font-size-action=decrease]").disabled'), true);

    const reset = await centerAndHit('[data-font-size-action="reset"]');
    assert.ok(reset?.hitsTarget, `${scenario.layout} ${scenario.width}px reset action is covered: ${JSON.stringify(reset)}`);
    await clickAt(reset.x, reset.y, scenario.touch);
    assert.equal(await evaluate('window.SpeakReadyFontSize.get()'), 1);
    assert.equal(await evaluate(`document.querySelector(${JSON.stringify(scenario.id)}).getAttribute('aria-expanded')`), 'false');
    assert.equal(await evaluate('localStorage.getItem("speakready.user.fontScale.v1")'), '1');
    const escapeToggle = await centerAndHit(scenario.id);
    await clickAt(escapeToggle.x, escapeToggle.y, scenario.touch);
    await command('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
    await command('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape', windowsVirtualKeyCode: 27 });
    assert.equal(await evaluate(`document.querySelector(${JSON.stringify(scenario.id)}).getAttribute('aria-expanded')`), 'false', 'Escape did not close menu');
    console.log(`PASS ${scenario.layout} ${scenario.width}px${scenario.theme ? ` ${scenario.theme}` : ''}: pointer/touch, grow, shrink, reset, persistence`);
  }
} finally {
  websocket?.close();
  browser.kill();
  await new Promise(resolve => browser.once('exit', resolve));
  server.close();
  rmSync(profile, { recursive: true, force: true });
}
