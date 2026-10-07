// records the README clips and screenshots from a local copy of the site, see README.md here.
// run through record.sh, which starts the server and seeds the made-up demo data first
import { chromium } from 'playwright';
import fs from 'node:fs';
const HOST = 'vitafolio.isaacadjei.me', PORT = process.env.DEMO_PORT || '8123';
const PASSWORD = process.env.DEMO_PASSWORD || 'demo-only-password';
const B = `http://${HOST}`, OUT = new URL('./out/', import.meta.url).pathname;
fs.mkdirSync(`${OUT}shots`, { recursive: true }); fs.mkdirSync(`${OUT}video`, { recursive: true });
const VP = { width: 1280, height: 800 };
const only = process.argv[2];
// DEMO_SCHEME=dark records the clips again in the dark theme, saved with a -dark suffix
const SCHEME = process.env.DEMO_SCHEME === 'dark' ? 'dark' : 'light';
const SUFFIX = SCHEME === 'dark' ? '-dark' : '';
// the live hostname is mapped to the local server so addresses on screen match the real site. The
// full chromium build is used because the bare headless shell has no pdf viewer for the latex preview
const b = await chromium.launch({ channel: 'chromium', args: [`--host-resolver-rules=MAP ${HOST} 127.0.0.1:${PORT}`] });

// a visible cursor and a caption bar, since browser video has neither
const overlay = () => { if (window.top !== window) return;
  window.addEventListener('DOMContentLoaded', () => {
    // the demo runs over plain http, so the address hint is shown as the live https one
    const w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    while (w.nextNode()) if (w.currentNode.nodeValue.includes('http://vitafolio.')) w.currentNode.nodeValue = w.currentNode.nodeValue.replaceAll('http://vitafolio.', 'https://vitafolio.');
    const c = document.createElement('div'); c.id = '__cursor';
    Object.assign(c.style, { position: 'fixed', left: '-40px', top: '-40px', width: '22px', height: '22px', borderRadius: '50%', background: 'rgba(17,24,39,.35)', border: '2px solid #fff', boxShadow: '0 0 0 1px #111827', zIndex: 2147483647, pointerEvents: 'none', transform: 'translate(-50%,-50%)', transition: 'width .1s,height .1s' });
    document.body.appendChild(c);
    document.addEventListener('mousemove', e => { c.style.left = e.clientX + 'px'; c.style.top = e.clientY + 'px'; }, true);
    document.addEventListener('mousedown', () => { c.style.width = c.style.height = '16px'; }, true);
    document.addEventListener('mouseup', () => { c.style.width = c.style.height = '22px'; }, true);
    const cap = document.createElement('div'); cap.id = '__caption';
    Object.assign(cap.style, { position: 'fixed', left: '50%', bottom: '28px', transform: 'translateX(-50%)', background: 'rgba(15,23,42,.92)', color: '#fff', font: '600 20px/1.3 system-ui,sans-serif', padding: '12px 22px', borderRadius: '12px', zIndex: 2147483646, pointerEvents: 'none', display: 'none', maxWidth: '80%', textAlign: 'center' });
    document.body.appendChild(cap);
    const saved = sessionStorage.getItem('__caption'); if (saved) { cap.textContent = saved; cap.style.display = 'block'; }
  });
};
const caption = async (p, text) => { await p.evaluate(t => { sessionStorage.setItem('__caption', t); const c = document.getElementById('__caption'); if (c) { c.textContent = t; c.style.display = t ? 'block' : 'none'; } }, text); };
const point = async (p, loc) => { await loc.scrollIntoViewIfNeeded(); const bx = await loc.boundingBox(); await p.mouse.move(bx.x + bx.width / 2, bx.y + bx.height / 2, { steps: 18 }); await p.waitForTimeout(250); };
const click = async (p, loc) => { await point(p, loc); await loc.click(); };
const type = async (p, loc, text, delay = 35) => { await click(p, loc); await loc.fill(''); await loc.pressSequentially(text, { delay }); };
const pause = (p, ms = 900) => p.waitForTimeout(ms);
const shot = (p, name, full = false) => p.screenshot({ path: `${OUT}shots/${name}.png`, fullPage: full });

// signs in once and shares the session with every clip, since the login rate limit stops a fresh
// sign-in for each one
let session;
async function signIn() {
  const c = await b.newContext({ viewport: VP });
  const p = await c.newPage();
  await p.goto(B + '/login'); await p.fill('[name=email]', 'alexmorgan@example.com'); await p.fill('[name=password]', PASSWORD);
  await p.click('main button[type=submit]');
  try { await p.waitForURL('**/dashboard', { timeout: 15000 }); } catch {
    await p.screenshot({ path: `${OUT}sign-in-failed.png`, fullPage: true });
    throw new Error('sign-in failed, see scripts/demo/out/sign-in-failed.png');
  }
  session = await c.storageState(); await c.close();
}
async function ctx(video, scheme = 'light') {
  if (!session) await signIn();
  const c = await b.newContext({ viewport: VP, colorScheme: scheme, storageState: session, deviceScaleFactor: video ? 1 : 2, ...(video ? { recordVideo: { dir: `${OUT}video/raw-${video}`, size: VP } } : {}) });
  await c.addInitScript(overlay);
  const p = await c.newPage();
  await p.goto(B + '/dashboard');
  return { c, p };
}
async function finish(c, video) {
  const p = c.pages()[0]; const v = p.video(); await c.close();
  if (v) { fs.renameSync(await v.path(), `${OUT}video/${video}${SUFFIX}.webm`); fs.rmSync(`${OUT}video/raw-${video}`, { recursive: true, force: true }); }
}
const SLUG = 'alex-morgan-embedded-software-roles';

// clip 1: build a cv
if (!only || only === 'build') {
  const { c, p } = await ctx('build', SCHEME);
  await caption(p, 'Start a CV for the roles you are applying for'); await pause(p, 1200);
  await type(p, p.locator('[name=title]'), 'Embedded software roles'); await pause(p, 400);
  await click(p, p.locator('button:has-text("Create CV")')); await p.waitForURL(/\/cvs\/.+\/edit/);
  await caption(p, 'Write each section in plain text'); await pause(p);
  await type(p, p.locator('[name=headline]'), 'Electronic engineering student, embedded systems');
  await type(p, p.locator('[name=key_language]'), 'C');
  await type(p, p.locator('[name=profile]'), 'Second-year electronic engineering student who enjoys bringing up new boards, writing firmware and automating hardware tests.', 18);
  await type(p, p.locator('[name=skills]'), 'C, Python, KiCad, STM32, FreeRTOS, Git, Linux', 25);
  await click(p, p.locator('[name=experience]'));
  await p.locator('[name=experience]').fill('Hardware test intern, Example Robotics (2025-06 to 2025-09)\n- Wrote Python rigs that test 200 motor boards a day\n- Found a power sequencing bug before production\n\nSociety lead, Northbridge Robotics Society (2024-10 to present)\n- Run weekly STM32 workshops for 30 members');
  await click(p, p.locator('[name=education]'));
  await p.locator('[name=education]').fill('BEng Electronic Engineering, Northbridge University (2024-09 to present)');
  await caption(p, 'Save it and watch the completeness meter fill in'); await pause(p, 600);
  await click(p, p.locator('button:has-text("Save content")')); await p.waitForLoadState('networkidle'); await pause(p, 1200);
  await p.evaluate(() => window.scrollTo({ top: 0, behavior: 'smooth' })); await pause(p, 2500);
  await finish(c, 'build');
}

// clip 2: style and share it
if (!only || only === 'share') {
  const { c, p } = await ctx('share', SCHEME);
  await p.goto(`${B}/cvs/${SLUG}/edit/settings`);
  await caption(p, 'Pick a look and decide who can see it'); await pause(p, 1200);
  const radio = (n, v) => p.locator(`label:has(input[name=${n}][value=${v}])`).first();
  await click(p, radio('theme', 'modern')); await pause(p, 500);
  await click(p, radio('accent', 'teal')); await pause(p, 500);
  await click(p, radio('font', 'sans')); await pause(p, 500);
  await click(p, radio('visibility', 'public')); await pause(p, 500);
  await click(p, p.locator('button:has-text("Save settings")')); await p.waitForLoadState('networkidle'); await pause(p, 1000);
  await caption(p, 'Share one link that always shows the latest version');
  await p.goto(`${B}/cv/${SLUG}`); await pause(p, 1500);
  await p.mouse.move(640, 400); for (let i = 0; i < 6; i++) { await p.mouse.wheel(0, 220); await pause(p, 350); }
  await pause(p, 600); for (let i = 0; i < 6; i++) { await p.mouse.wheel(0, -220); await pause(p, 200); }
  await caption(p, 'Others can find it in the directory'); await p.goto(B + '/'); await pause(p, 1200); await p.mouse.move(640, 400); for (let i = 0; i < 5; i++) { await p.mouse.wheel(0, 140); await pause(p, 250); } await pause(p, 2200);
  await finish(c, 'share');
}

// clip 3: compile a pdf from latex
if (!only || only === 'compile') {
  const { c, p } = await ctx('compile', SCHEME);
  await p.goto(`${B}/cvs/${SLUG}/latex`);
  await caption(p, 'Prefer LaTeX? Start from a template'); await pause(p, 1200);
  const tpl = p.locator('select').first(); await point(p, tpl); await tpl.selectOption({ index: 1 }); await pause(p, 1200);
  await caption(p, 'Compile in the browser, nothing leaves your device until you save');
  await click(p, p.locator('button:has-text("Compile")'));
  await p.waitForFunction(() => !document.body.innerText.includes('Compile to see your PDF here'), null, { timeout: 240000 });
  await p.waitForTimeout(1500);
  await p.locator('iframe').first().scrollIntoViewIfNeeded(); await p.mouse.wheel(0, 120); await p.waitForTimeout(3500);
  await caption(p, 'Save to attach the PDF to this CV');
  await click(p, p.locator('button:has-text("Save")').last()); await pause(p, 2500);
  await finish(c, 'compile');
}

// still screenshots, light and dark, at 2x for sharp README images
if ((!only || only === 'shots') && SCHEME === 'light') {
  for (const scheme of ['light', 'dark']) {
    const { c, p } = await ctx(null, scheme);
    await p.evaluate(() => document.getElementById('__cursor')?.remove());
    const s = async (path, name, full) => { await p.goto(B + path); await p.waitForLoadState('networkidle'); await p.evaluate(() => { document.getElementById('__cursor')?.remove(); document.getElementById('__caption')?.remove(); }); await pause(p, 500); await shot(p, `${name}-${scheme}`, full); };
    await s('/', 'directory');
    await s('/dashboard', 'dashboard');
    await s(`/cvs/${SLUG}/edit/details`, 'editor');
    await s(`/cvs/${SLUG}/edit/settings`, 'look-and-privacy');
    await s(`/cv/${SLUG}`, 'public-cv');
    await s(`/cvs/${SLUG}/latex`, 'latex');
    await p.click('button:has-text("Compile")');
    await p.waitForFunction(() => document.querySelector('iframe'), null, { timeout: 240000 });
    await p.waitForTimeout(3000); await p.locator('iframe').first().scrollIntoViewIfNeeded(); await p.mouse.wheel(0, 120); await p.waitForTimeout(1500);
    await shot(p, `latex-compiled-${scheme}`);
    await c.close();
  }
}
await b.close();
console.log('done');
