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
async function ctx(video, scheme = 'light', signedIn = true) {
  if (signedIn && !session) await signIn();
  const c = await b.newContext({ viewport: VP, colorScheme: scheme, ...(signedIn ? { storageState: session } : {}), deviceScaleFactor: video ? 1 : 2, ...(video ? { recordVideo: { dir: `${OUT}video/raw-${video}`, size: VP } } : {}) });
  await c.addInitScript(overlay);
  const p = await c.newPage();
  await p.goto(B + (signedIn ? '/dashboard' : '/'));
  return { c, p };
}
async function finish(c, video) {
  const p = c.pages()[0]; const v = p.video(); await c.close();
  if (v) { fs.renameSync(await v.path(), `${OUT}video/${video}${SUFFIX}.webm`); fs.rmSync(`${OUT}video/raw-${video}`, { recursive: true, force: true }); }
}
const SLUG = 'alex-morgan-embedded-software-roles';

// an illustrated avatar for the photo clip, drawn in the browser so no picture of a real person is used
if (!fs.existsSync(`${OUT}avatar.png`)) {
  const a = await b.newPage({ viewport: { width: 800, height: 800 } });
  await a.setContent(`<body style="margin:0"><svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#14b8a6"/><stop offset="1" stop-color="#1e3a8a"/></linearGradient></defs><rect width="800" height="800" fill="url(#g)"/><circle cx="400" cy="330" r="150" fill="#fde7d4"/><path d="M250 270q20-150 150-150t150 150q-60-60-150-60t-150 60z" fill="#2b1d16"/><path d="M130 800q20-250 270-250t270 250z" fill="#f8fafc"/><circle cx="345" cy="330" r="14" fill="#2b1d16"/><circle cx="455" cy="330" r="14" fill="#2b1d16"/><path d="M350 400q50 40 100 0" stroke="#2b1d16" stroke-width="12" fill="none" stroke-linecap="round"/></svg></body>`);
  await a.screenshot({ path: `${OUT}avatar.png` }); await a.close();
}

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

// clip 4: create an account, then confirm the email from the message the local mailer writes to the log
if (!only || only === 'signup') {
  const LOG = new URL('../../storage/logs/laravel.log', import.meta.url).pathname;
  const start = fs.existsSync(LOG) ? fs.statSync(LOG).size : 0;
  const { c, p } = await ctx('signup', SCHEME, false);
  await p.goto(B + '/register');
  await caption(p, 'Create a free account in under a minute'); await pause(p, 1200);
  await type(p, p.locator('[name=name]'), 'Jordan Lee');
  await type(p, p.locator('[name=email]'), 'jordanlee@example.com');
  await type(p, p.locator('[name=password]'), PASSWORD + '-Jordan7', 20);
  await type(p, p.locator('[name=password_confirmation]'), PASSWORD + '-Jordan7', 20);
  await click(p, p.locator('input[name=terms]'));
  await caption(p, 'Or sign up with Google, Microsoft or GitHub');
  await point(p, p.locator('main a[href*="/auth/"]').first()); await pause(p, 1400);
  await caption(p, 'Create the account');
  await click(p, p.locator('main button[type=submit]')); await p.waitForLoadState('networkidle');
  await caption(p, 'Confirm your email address'); await pause(p, 2000);
  // the log mailer writes quoted-printable text, so soft line breaks and encoded equals signs are undone first
  let link;
  for (let i = 0; i < 40 && !link; i++) {
    const text = fs.readFileSync(LOG, 'utf8').slice(start).replace(/=\r?\n/g, '').replaceAll('=3D', '=').replaceAll('&amp;', '&');
    link = text.match(/http:\/\/vitafolio\.isaacadjei\.me\/email\/verify\/[^\s"'<>)]+/)?.[0];
    if (!link) await p.waitForTimeout(250);
  }
  if (!link) throw new Error('no verification link in storage/logs/laravel.log');
  await p.goto(link); await p.waitForLoadState('networkidle');
  await caption(p, 'You are in, with a first CV ready to fill in'); await pause(p, 2600);
  await finish(c, 'signup');
}

// clip 5: photo, handle and connected accounts
if (!only || only === 'profile') {
  const { c, p } = await ctx('profile', SCHEME);
  await p.goto(B + '/settings/photo');
  await caption(p, 'Add a photo and frame it'); await pause(p, 1000);
  await point(p, p.locator('#f-avatar')); await p.setInputFiles('#f-avatar', `${OUT}avatar.png`);
  const frame = p.locator('[aria-label^="Photo framing"]'); await frame.waitFor();
  await pause(p, 600);
  const zoom = p.locator('#avatar-zoom'); await point(p, zoom); await zoom.fill('1.4'); await pause(p, 500);
  await point(p, frame); const fb = await frame.boundingBox();
  await p.mouse.down(); await p.mouse.move(fb.x + fb.width / 2 + 18, fb.y + fb.height / 2 + 12, { steps: 20 }); await p.mouse.up(); await pause(p, 600);
  await click(p, p.locator('button:has-text("Upload photo")')); await p.waitForLoadState('networkidle'); await pause(p, 1400);
  await p.goto(B + '/settings/handle');
  await caption(p, 'Choose the handle in your profile address'); await pause(p, 900);
  await type(p, p.locator('[name=handle]'), 'alex-morgan', 60);
  await click(p, p.locator('button:has-text("Change handle")')); await p.waitForLoadState('networkidle'); await pause(p, 1400);
  await p.goto(B + '/settings/connected');
  await caption(p, 'Connect Google, Microsoft or GitHub to sign in with one click'); await pause(p, 1000);
  await point(p, p.locator('a.btn:has-text("Connect")').first()); await pause(p, 2600);
  await finish(c, 'profile');
}

// clip 6: check a cv against a job advert
if (!only || only === 'check') {
  const { c, p } = await ctx('check', SCHEME);
  await p.goto(B + '/check');
  await caption(p, 'Check a CV the way tracking systems read it'); await pause(p, 1200);
  const pick = p.locator('#f-cv'); await point(p, pick); await pick.selectOption({ index: 0 }); await pause(p, 500);
  await caption(p, 'Paste a job advert to compare keywords');
  await click(p, p.locator('[name=job_advert]'));
  await p.locator('[name=job_advert]').fill('Embedded Firmware Intern at Kestrel Semiconductors. Summer 2027 internship writing C firmware for low-power radio chips. You will use STM32 boards, FreeRTOS, Git, Python test rigs, CI pipelines and Bluetooth Low Energy alongside our silicon team.');
  await pause(p, 600);
  await click(p, p.locator('button:has-text("Check my CV")')); await p.waitForLoadState('networkidle');
  await caption(p, 'See the score, what is missing and how to fix it'); await pause(p, 1500);
  await p.mouse.move(640, 400); for (let i = 0; i < 7; i++) { await p.mouse.wheel(0, 200); await pause(p, 420); }
  await pause(p, 1200);
  await finish(c, 'check');
}

// clip 7: find a role, save it and track it with one found elsewhere
if (!only || only === 'jobs') {
  const { c, p } = await ctx('jobs', SCHEME);
  await p.goto(B + '/jobs');
  await caption(p, 'Student roles in every field, gathered every night'); await pause(p, 1400);
  await click(p, p.locator('a.tab-link:has-text("Internships")')); await p.waitForLoadState('networkidle'); await pause(p, 900);
  const field = p.locator('#job-sector'); await point(p, field); await field.selectOption('hardware'); await pause(p, 400);
  await click(p, p.locator('form[role=search] button[type=submit]')); await p.waitForLoadState('networkidle'); await pause(p, 900);
  await caption(p, 'Save a role to your tracker in one click');
  await click(p, p.locator('main form[action*="/save"] button').first()); await p.waitForLoadState('networkidle'); await pause(p, 1200);
  await p.goto(B + '/applications');
  await caption(p, 'Track every application in one place'); await pause(p, 1400);
  // the role just saved from the jobs page, found by its title so the seeded order never matters
  const update = p.getByRole('link', { name: 'Embedded Firmware Intern' }).locator('xpath=ancestor::*[.//details][1]').locator('details');
  await click(p, update.locator('summary')); await pause(p, 500);
  const status = update.locator('select[name=status]'); await point(p, status); await status.selectOption('applied'); await pause(p, 400);
  await click(p, update.locator('button:has-text("Save changes")')); await p.waitForLoadState('networkidle'); await pause(p, 1000);
  await caption(p, 'Add roles you found anywhere else');
  await click(p, p.locator('summary:has-text("Add an application from somewhere else")')); await pause(p, 500);
  await type(p, p.locator('#f-title'), 'Firmware Placement');
  await type(p, p.locator('#f-company'), 'Example Instruments');
  await p.locator('#f-status').selectOption('applied');
  await click(p, p.locator('button:has-text("Add application")')); await p.waitForLoadState('networkidle'); await pause(p, 2400);
  await finish(c, 'jobs');
}

// clip 8: open a support ticket and follow a reply
if (!only || only === 'support') {
  const { c, p } = await ctx('support', SCHEME);
  await p.goto(B + '/support/new');
  await caption(p, 'Need help? Open a support ticket'); await pause(p, 1200);
  const cat = p.locator('[name=category]'); await point(p, cat); await cat.selectOption('cv'); await pause(p, 400);
  await type(p, p.locator('[name=subject]'), 'Changing the order of CV sections');
  await type(p, p.locator('[name=body]'), 'Can I move Projects above Experience on one CV only?', 25);
  await click(p, p.locator('button:has-text("Open ticket")')); await p.waitForLoadState('networkidle');
  await caption(p, 'Every ticket gets a reference and a confirmation email'); await pause(p, 2000);
  await p.goto(B + '/support/tickets');
  await caption(p, 'Follow each conversation until it is sorted'); await pause(p, 1200);
  await click(p, p.locator('a:has-text("Adding a second email address")')); await p.waitForLoadState('networkidle'); await pause(p, 1200);
  await p.mouse.move(640, 400); for (let i = 0; i < 2; i++) { await p.mouse.wheel(0, 160); await pause(p, 400); } await pause(p, 2000);
  await finish(c, 'support');
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
    await s('/jobs', 'jobs');
    await s('/applications', 'applications');
    await s('/settings/connected', 'connected');
    await p.goto(B + '/check'); await p.locator('[name=job_advert]').fill('Embedded Firmware Intern. C firmware on STM32 boards with FreeRTOS, Git, Python test rigs and Bluetooth Low Energy.');
    await p.click('button:has-text("Check my CV")'); await p.waitForLoadState('networkidle');
    // the report sits under the form, so the screenshot starts at the score
    await p.locator('#result-title').evaluate(e => (e.closest('section') || e).scrollIntoView({ block: 'start' })); await pause(p, 500); await shot(p, `check-${scheme}`);
    await p.goto(B + '/support/tickets'); await p.click('a:has-text("Adding a second email address")'); await p.waitForLoadState('networkidle'); await pause(p, 500); await shot(p, `support-${scheme}`);
    await c.close();
    const out = await ctx(null, scheme, false);
    await out.p.goto(B + '/register'); await out.p.waitForLoadState('networkidle'); await out.p.evaluate(() => document.getElementById('__cursor')?.remove()); await pause(out.p, 500); await shot(out.p, `signup-${scheme}`);
    await out.c.close();
  }
}
await b.close();
console.log('done');
