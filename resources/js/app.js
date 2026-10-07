import Alpine from '@alpinejs/csp';
import { t } from './i18n.js';

// the csp build of alpine never evaluates strings, so every component is registered here

// on small screens the menu starts closed; without javascript it simply stays open
Alpine.data('menu', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
    get expanded() { return String(this.open); },
    get navClass() { return this.open ? '' : 'hidden'; },
}));

// filters the help centre's topic cards as you type; the count is announced for screen readers
Alpine.data('helpSearch', () => ({
    query: '',
    shown: 0,
    init() { this.$watch('query', () => this.filter()); this.filter(); },
    filter() {
        const words = this.query.toLowerCase().split(/\s+/).filter(Boolean);
        const items = this.$root.querySelectorAll('[data-help-topic]');
        this.shown = 0;
        items.forEach((item) => {
            const match = words.every((word) => item.dataset.helpTopic.includes(word));
            item.hidden = !match;
            if (match) this.shown += 1;
        });
    },
    get announcement() {
        if (!this.query) return '';
        return this.shown === 1 ? t(':count topic matches.', { count: 1 }) : t(':count topics match.', { count: this.shown });
    },
}));

// the signed-in menu: a disclosure rather than an aria menu, so it is plain links read in order
Alpine.data('accountMenu', () => ({
    open: false,
    toggle() { this.open = !this.open; },
    close() { this.open = false; },
    get expanded() { return String(this.open); },
    get closed() { return !this.open; },
}));

const THEMES = ['system', 'light', 'dark'];
const THEME_NAMES = { system: () => t('System'), light: () => t('Light'), dark: () => t('Dark') };

Alpine.data('themeToggle', () => ({
    choice: document.documentElement.dataset.themeChoice || 'system',
    cycle() {
        this.choice = THEMES[(THEMES.indexOf(this.choice) + 1) % THEMES.length];
        window.__setTheme?.(this.choice);
    },
    get label() { return THEME_NAMES[this.choice](); },
    get isLight() { return this.choice === 'light'; },
    get isDark() { return this.choice === 'dark'; },
    get isSystem() { return this.choice === 'system'; },
    get ariaLabel() { return t('Theme: :name. Select to change.', { name: THEME_NAMES[this.choice]() }); },
}));

Alpine.data('password', () => ({
    visible: false,
    toggle() { this.visible = !this.visible; },
    get type() { return this.visible ? 'text' : 'password'; },
    get label() { return this.visible ? t('Hide') : t('Show'); },
    get pressed() { return String(this.visible); },
}));

// a live character count that is announced politely, not on every keystroke
Alpine.data('counter', () => ({
    count: 0,
    max: 0,
    init() {
        const field = this.$el.querySelector('textarea, input');
        this.max = Number(field?.getAttribute('maxlength') || 0);
        this.count = field?.value.length || 0;
        field?.addEventListener('input', () => { this.count = field.value.length; });
    },
    get text() { return this.max ? t(':count of :max characters', { count: this.count, max: this.max }) : t(':count characters', { count: this.count }); },
}));

// choosing a file or a Vitafolio CV selects its option, so the CV that gets checked is the one just picked
Alpine.data('cvSource', () => ({
    init() {
        const pick = (value) => { const radio = this.$el.querySelector(`input[name="source"][value="${value}"]`); if (radio) radio.checked = true; };
        this.$el.querySelector('input[name="resume"]')?.addEventListener('change', (e) => { if (e.target.files.length) pick('file'); });
        this.$el.querySelector('select[name="cv"]')?.addEventListener('change', () => pick('cv'));
    },
}));

Alpine.data('toast', () => ({
    shown: true,
    init() { setTimeout(() => { this.shown = false; }, 7000); },
    dismiss() { this.shown = false; },
}));

Alpine.data('copyLink', () => ({
    copied: false,
    async copy() {
        try {
            await navigator.clipboard.writeText(this.$root.dataset.url);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2500);
        } catch (e) { /* the visible link can still be copied by hand */ }
    },
    get label() { return this.copied ? t('Link copied') : t('Copy link'); },
    get announcement() { return this.copied ? t('Link copied to the clipboard') : ''; },
}));

// passkeys: the library only loads on pages that use it and only when the browser supports them
const loadPasskeys = () => import('@laravel/passkeys').then((module) => module.Passkeys);
const PASSKEY_FAILED = () => t('That passkey did not work. Try again or sign in with your password.');

Alpine.data('passkeyLogin', () => ({
    supported: false,
    busy: false,
    message: '',
    async init() {
        if (!window.PublicKeyCredential) return;
        this.supported = true;
        // saved passkeys also appear in the email field's autofill list where the browser allows it
        try {
            const Passkeys = await loadPasskeys();
            if (await Passkeys.isAutofillSupported()) {
                Passkeys.autofill({ remember: this.remember() }).then((response) => this.finish(response)).catch(() => {});
            }
        } catch (e) { /* the button still works */ }
    },
    remember() { return Boolean(document.querySelector('input[name="remember"]')?.checked); },
    finish(response) { if (response?.redirect) window.location.href = response.redirect; },
    async signIn() {
        this.busy = true;
        this.message = '';
        try {
            const Passkeys = await loadPasskeys();
            Passkeys.cancel();
            this.finish(await Passkeys.verify({ remember: this.remember() }));
        } catch (e) {
            this.message = e?.name === 'NotAllowedError' ? t('Signing in with a passkey was cancelled.') : PASSKEY_FAILED();
        } finally {
            this.busy = false;
        }
    },
    get unsupported() { return !this.supported; },
    get buttonLabel() { return this.busy ? t('Waiting for your passkey…') : t('Sign in with a passkey'); },
}));

// a sensible default name, so the list says which device each passkey lives on
function deviceName() {
    const ua = navigator.userAgent;
    const device = /iPhone/.test(ua) ? 'iPhone' : /iPad/.test(ua) ? 'iPad' : /Android/.test(ua) ? 'Android'
        : /Mac/.test(ua) ? 'Mac' : /Windows/.test(ua) ? 'Windows' : /Linux/.test(ua) ? 'Linux' : t('This device');
    return t(':device passkey', { device });
}

Alpine.data('passkeyRegister', () => ({
    supported: Boolean(window.PublicKeyCredential),
    name: deviceName(),
    busy: false,
    message: '',
    async add() {
        this.busy = true;
        this.message = '';
        try {
            const Passkeys = await loadPasskeys();
            await Passkeys.register({ name: this.name.trim().slice(0, 60) || deviceName() });
            window.location.href = this.$root.dataset.doneUrl;
        } catch (e) {
            this.message = e?.name === 'NotAllowedError'
                ? t('Adding the passkey was cancelled.')
                : t('The passkey could not be added. If you already have one for this device, it may be saved already.');
        } finally {
            this.busy = false;
        }
    },
    get unsupported() { return !this.supported; },
    get buttonLabel() { return this.busy ? t('Waiting for your device…') : t('Add a passkey'); },
}));

window.Alpine = Alpine;
Alpine.start();

// flowcharts in the documentation and changelog load the drawing library only when a page has one
if (document.querySelector('pre > code.language-mermaid')) {
    import('./diagrams.js').then(({ prepareDiagrams, renderDiagrams }) => renderDiagrams(prepareDiagrams()));
}

// vue islands: an element with data-vue="Name" gets that component, with props from data-props.
// each component is loaded on demand, so pages without one never download vue at all
const islands = import.meta.glob('./components/*.vue');
document.querySelectorAll('[data-vue]').forEach(async (el) => {
    const loader = islands[`./components/${el.dataset.vue}.vue`];
    if (!loader) return;
    const [{ createApp }, component] = await Promise.all([import('vue'), loader()]);
    let props = {};
    try { props = JSON.parse(el.dataset.props || '{}'); } catch (e) { /* bad props render the defaults */ }
    // the server-rendered fallback inside the element is replaced once the component is ready
    createApp(component.default, props).mount(el);
});

// a thin bar at the top shows that the next page is on its way. a download also fires
// beforeunload without leaving the page, so the bar hides itself again after a while
const progress = document.getElementById('page-progress');
let progressTimer;
function stopProgress() {
    if (!progress) return;
    progress.hidden = true;
    progress.classList.remove('is-loading');
    clearTimeout(progressTimer);
}
window.addEventListener('beforeunload', () => {
    if (!progress) return;
    progress.hidden = false;
    progress.classList.add('is-loading');
    progressTimer = setTimeout(stopProgress, 10000);
});

// a submitted form disables its button and says what it is doing, so it cannot be sent twice.
// the busy words are english, so a page in another language says "please wait" in its own words
// the check runs after every other submit handler, so a cancelled confirmation leaves it alone
const BUSY = { save: 'Saving', send: 'Sending', upload: 'Uploading', create: 'Creating', add: 'Adding', change: 'Changing', set: 'Setting', update: 'Updating', delete: 'Deleting', remove: 'Removing', compile: 'Compiling', sign: 'Signing', connect: 'Connecting', import: 'Importing', duplicate: 'Duplicating', confirm: 'Confirming', reset: 'Resetting', report: 'Reporting', publish: 'Publishing' };
function restoreButtons() {
    document.querySelectorAll('button[aria-busy="true"]').forEach((button) => {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        if (button.dataset.label) button.textContent = button.dataset.label;
    });
}
document.addEventListener('submit', (event) => {
    setTimeout(() => {
        if (event.defaultPrevented) return;
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.method === 'dialog') return;
        const button = event.submitter instanceof HTMLButtonElement ? event.submitter : form.querySelector('button[type="submit"], button:not([type])');
        if (!button) return;
        const label = (button.textContent || '').trim();
        const word = label.split(/\s+/)[0].toLowerCase();
        button.dataset.label = button.textContent;
        button.setAttribute('aria-busy', 'true');
        button.disabled = true;
        const english = document.documentElement.lang.startsWith('en');
        button.textContent = english && BUSY[word] ? `${BUSY[word]}${label.slice(word.length)}…` : t('Please wait…');
        // a form that downloads a file never leaves the page, so the button comes back on its own
        setTimeout(restoreButtons, 10000);
    }, 0);
});
// a page restored from the back-forward cache shows its buttons and bar as they were before
window.addEventListener('pageshow', () => { stopProgress(); restoreButtons(); });

// destructive actions ask first in a proper dialog: keyboard trapped, escape cancels and focus
// returns to the button afterwards. browsers without dialog support fall back to confirm()
const confirmDialog = document.getElementById('confirm-dialog');
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed === 'yes') {
            delete form.dataset.confirmed;
            return;
        }
        event.preventDefault();
        // older browsers lack requestSubmit; submit() skips the handlers, which is fine once confirmed
        const proceed = () => {
            form.dataset.confirmed = 'yes';
            if (typeof form.requestSubmit === 'function') form.requestSubmit(event.submitter ?? undefined);
            else form.submit();
        };
        if (!confirmDialog || typeof confirmDialog.showModal !== 'function') {
            if (window.confirm(form.dataset.confirm)) proceed();
            return;
        }
        confirmDialog.querySelector('#confirm-text').textContent = form.dataset.confirm;
        confirmDialog.returnValue = '';
        confirmDialog.addEventListener('close', () => { if (confirmDialog.returnValue === 'ok') proceed(); }, { once: true });
        confirmDialog.showModal();
    });
});

// back to top appears once a page has been scrolled well down
const backToTop = document.getElementById('back-to-top');
const readingBar = document.getElementById('reading-progress');
const reading = document.querySelector('[data-reading]');
if (readingBar && reading) readingBar.hidden = false;
function onScroll() {
    if (backToTop) backToTop.hidden = window.scrollY < 800;
    if (readingBar && reading) {
        const total = document.documentElement.scrollHeight - window.innerHeight;
        readingBar.style.transform = `scaleX(${total > 0 ? Math.min(1, window.scrollY / total) : 0})`;
    }
}
window.addEventListener('scroll', onScroll, { passive: true });
onScroll();
backToTop?.addEventListener('click', () => {
    const smooth = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    window.scrollTo({ top: 0, behavior: smooth ? 'smooth' : 'auto' });
    document.querySelector('.site-header a')?.focus({ preventScroll: true });
});

// every code block in long-form pages gets a copy button
const copyStatus = document.getElementById('copy-status');
document.querySelectorAll('.prose pre').forEach((pre) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'copy-code btn btn-sm btn-secondary no-print';
    button.textContent = t('Copy');
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(pre.querySelector('code')?.textContent ?? pre.textContent);
            button.textContent = t('Copied');
            if (copyStatus) copyStatus.textContent = t('Code copied to the clipboard');
            setTimeout(() => { button.textContent = t('Copy'); if (copyStatus) copyStatus.textContent = ''; }, 2000);
        } catch {
            button.textContent = t('Select and copy');
        }
    });
    pre.classList.add('has-copy');
    pre.append(button);
});
document.querySelectorAll('[data-print]').forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', () => window.print());
});

document.querySelectorAll('[data-reload]').forEach((link) => {
    link.addEventListener('click', (event) => {
        event.preventDefault();
        window.location.reload();
    });
});

// after a failed submit, focus goes to the error summary so it is read out first
document.querySelector('[data-focus-first]')?.focus();

// only built sites register the worker; in development it would serve stale files
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
