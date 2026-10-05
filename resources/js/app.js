import Alpine from '@alpinejs/csp';

// the csp build of alpine never evaluates strings, so every component is registered here

// on small screens the menu starts closed; without javascript it simply stays open
Alpine.data('menu', () => ({
    open: false,
    toggle() { this.open = !this.open; },
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
        return this.shown === 1 ? '1 topic matches.' : `${this.shown} topics match.`;
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
const THEME_NAMES = { system: 'System', light: 'Light', dark: 'Dark' };

Alpine.data('themeToggle', () => ({
    choice: document.documentElement.dataset.themeChoice || 'system',
    cycle() {
        this.choice = THEMES[(THEMES.indexOf(this.choice) + 1) % THEMES.length];
        window.__setTheme?.(this.choice);
    },
    get label() { return THEME_NAMES[this.choice]; },
    get ariaLabel() { return `Theme: ${THEME_NAMES[this.choice]}. Select to change.`; },
}));

Alpine.data('password', () => ({
    visible: false,
    toggle() { this.visible = !this.visible; },
    get type() { return this.visible ? 'text' : 'password'; },
    get label() { return this.visible ? 'Hide' : 'Show'; },
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
    get text() { return this.max ? `${this.count} of ${this.max} characters` : `${this.count} characters`; },
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
    get label() { return this.copied ? 'Link copied' : 'Copy link'; },
    get announcement() { return this.copied ? 'Link copied to the clipboard' : ''; },
}));

// passkeys: the library only loads on pages that use it and only when the browser supports them
const loadPasskeys = () => import('@laravel/passkeys').then((module) => module.Passkeys);
const PASSKEY_FAILED = 'That passkey did not work. Try again or sign in with your password.';

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
            this.message = e?.name === 'NotAllowedError' ? 'Signing in with a passkey was cancelled.' : PASSKEY_FAILED;
        } finally {
            this.busy = false;
        }
    },
    get unsupported() { return !this.supported; },
    get buttonLabel() { return this.busy ? 'Waiting for your passkey…' : 'Sign in with a passkey'; },
}));

// a sensible default name, so the list says which device each passkey lives on
function deviceName() {
    const ua = navigator.userAgent;
    const device = /iPhone/.test(ua) ? 'iPhone' : /iPad/.test(ua) ? 'iPad' : /Android/.test(ua) ? 'Android'
        : /Mac/.test(ua) ? 'Mac' : /Windows/.test(ua) ? 'Windows' : /Linux/.test(ua) ? 'Linux' : 'This device';
    return `${device} passkey`;
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
                ? 'Adding the passkey was cancelled.'
                : 'The passkey could not be added. If you already have one for this device, it may be saved already.';
        } finally {
            this.busy = false;
        }
    },
    get unsupported() { return !this.supported; },
    get buttonLabel() { return this.busy ? 'Waiting for your device…' : 'Add a passkey'; },
}));

window.Alpine = Alpine;
Alpine.start();

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

// confirm before destructive actions and print buttons work without alpine too
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
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
