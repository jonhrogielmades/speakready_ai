const { chromium } = require('C:/Users/rogiel/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

const baseUrl = 'http://127.0.0.1:8000';
const chromePath = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

function round(value) {
  return Math.round(value * 100) / 100;
}

async function login(page) {
  await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#loginForm', { state: 'attached' });
  await Promise.all([
    page.waitForLoadState('networkidle').catch(() => {}),
    page.locator('#loginForm').evaluate((form) => {
      form.querySelector('#loginEmail').value = 'codex-preview@example.test';
      form.querySelector('#loginPass').value = 'password';
      form.requestSubmit();
    }),
  ]);
}

async function measure(page, theme) {
  await page.evaluate((themeName) => {
    localStorage.setItem('theme', themeName);
    if (window.SpeakReadyTheme) window.SpeakReadyTheme.apply(themeName, false);
  }, theme);
  await page.goto(`${baseUrl}/drills/voice`, { waitUntil: 'networkidle' });
  await page.waitForSelector('#voice-rehearsal-page .vr-practice-flow');
  await page.waitForTimeout(700);
  await page.screenshot({
    path: `C:/laragon/www/speakready_ai/storage/app/codex-voice-desktop-${theme}.png`,
    fullPage: false,
  });
  return page.evaluate(() => {
    const selectors = [
      '.vr-shell',
      '.sr-page-hero.vr-hero',
      '.vr-practice-flow',
      '.vr-option-panel',
      '.vr-prompt-card',
      '.voice-live-stats',
      '.vr-transcript-wrap',
      '#analysisPanel',
      '.instant-feedback-panel',
      '.intention-coach-panel',
    ];
    const box = (selector) => {
      const node = document.querySelector(selector);
      if (!node) return null;
      const rect = node.getBoundingClientRect();
      const style = window.getComputedStyle(node);
      return {
        left: round(rect.left),
        top: round(rect.top),
        width: round(rect.width),
        height: round(rect.height),
        display: style.display,
        gridColumn: style.gridColumn,
        gridRow: style.gridRow,
        backgroundColor: style.backgroundColor,
        color: style.color,
      };
    };

    const viewport = { width: window.innerWidth, height: window.innerHeight };
    const overflowX = document.documentElement.scrollWidth - document.documentElement.clientWidth;
    const bottomGap = Math.max(0, viewport.height - document.querySelector('#voice-rehearsal-page').getBoundingClientRect().bottom);
    const hiddenCount = selectors
      .map((selector) => document.querySelector(selector))
      .filter(Boolean)
      .filter((node) => {
        const rect = node.getBoundingClientRect();
        return rect.width <= 0 || rect.height <= 0;
      }).length;

    return {
      theme: document.documentElement.dataset.theme,
      viewport,
      overflowX,
      bottomGap: round(bottomGap),
      boxes: Object.fromEntries(selectors.map((selector) => [selector, box(selector)])),
      hiddenCount,
      activeStyles: [...document.querySelectorAll('link[data-page-style]')].map((link) => link.href),
    };
  });
}

(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath: chromePath,
    args: ['--disable-gpu', '--no-sandbox'],
  });
  const context = await browser.newContext({ viewport: { width: 1600, height: 1000 } });
  await context.addInitScript(() => {
    for (const key of [
      'onboarding_completed_drills_voice',
      'onboarding_completed_desktop_drills_voice',
      'onboarding_completed',
    ]) {
      localStorage.setItem(key, 'true');
    }
  });
  const page = await context.newPage();
  await login(page);
  const dark = await measure(page, 'dark');
  const light = await measure(page, 'light');
  console.log(JSON.stringify({ dark, light }, null, 2));
  await browser.close();
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
