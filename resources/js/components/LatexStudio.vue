<script setup>
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import { t } from '../i18n.js';

// apple devices use Cmd where windows and linux use Ctrl; the editor's Mod- bindings already
// follow that, so the labels do too
// chrome reports "macOS", safari "MacIntel", so the check ignores case
const isApple = /mac|iphone|ipad|ipod/i.test(navigator.userAgentData?.platform || navigator.platform || '');
const modKey = isApple ? t('Cmd') : t('Ctrl');
// browsers only start a worker from the page's own site, and the engine lives on another one. So the
// worker script is fetched, its relative imports are pointed at the engine site and it starts from a
// local blob instead. Only the engine's own worker is swapped; the original constructor comes back after
async function withSameOriginWorker(workerUrl, start) {
    const base = workerUrl.slice(0, workerUrl.lastIndexOf('/') + 1);
    const source = (await (await fetch(workerUrl)).text()).replace(/importScripts\('([\w.-]+\.js)'\)/g, (_, file) => `importScripts('${base}${file}')`);
    const blobUrl = URL.createObjectURL(new Blob([source], { type: 'text/javascript' }));
    const NativeWorker = window.Worker;
    window.Worker = class extends NativeWorker {
        constructor(url, options) {
            super(String(url) === workerUrl ? blobUrl : url, options);
        }
    };
    try {
        return await start();
    } finally {
        window.Worker = NativeWorker;
    }
}

function onPageKey(event) {
    if (event.defaultPrevented || event.altKey || !(isApple ? event.metaKey : event.ctrlKey)) return;
    if (event.key === 'Enter') { event.preventDefault(); compile(); }
    else if (event.key.toLowerCase() === 's') { event.preventDefault(); save(); }
}

const props = defineProps({
    saveUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    initialSource: { type: String, default: '' },
    starters: { type: Object, default: () => ({}) },
    templates: { type: Object, default: () => ({}) },
    assetsUrl: { type: String, default: '' },
    viewUrl: { type: String, default: '' },
    nonce: { type: String, default: '' },
});

const source = ref(props.initialSource);
const template = ref('');
const engine = ref('pdflatex');
const plainMode = ref(false);
const status = ref('');
const busy = ref(false);
const log = ref('');
const pdfUrl = ref('');
const lastPdf = shallowRef(null);
const editorHost = ref(null);
const dirty = ref(false);

let view = null;
let runner = null;

// codemirror loads only on this page and only the pieces the editor uses
onMounted(async () => {
    const [{ EditorView, keymap, lineNumbers }, { EditorState }, { defaultKeymap, history, historyKeymap }, { StreamLanguage }, { stex }] = await Promise.all([
        import('@codemirror/view'),
        import('@codemirror/state'),
        import('@codemirror/commands'),
        import('@codemirror/language'),
        import('@codemirror/legacy-modes/mode/stex'),
    ]);
    view = new EditorView({
        parent: editorHost.value,
        state: EditorState.create({
            doc: source.value,
            extensions: [
                lineNumbers(),
                history(),
                StreamLanguage.define(stex),
                EditorView.lineWrapping,
                EditorView.cspNonce.of(props.nonce),
                EditorView.contentAttributes.of({ 'aria-label': t('LaTeX source'), spellcheck: 'false', dir: 'ltr' }),
                keymap.of([
                    { key: 'Mod-Enter', run: () => { compile(); return true; } },
                    { key: 'Mod-s', run: () => { save(); return true; } },
                    ...defaultKeymap,
                    ...historyKeymap,
                ]),
                EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        source.value = update.state.doc.toString();
                        dirty.value = true;
                    }
                }),
            ],
        }),
    });
    window.addEventListener('beforeunload', warnUnsaved);
});

onMounted(() => window.addEventListener('keydown', onPageKey));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onPageKey);
    view?.destroy();
    if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value);
    window.removeEventListener('beforeunload', warnUnsaved);
});

function warnUnsaved(event) {
    if (dirty.value) event.preventDefault();
}

function setSource(text) {
    source.value = text;
    if (view) view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: text } });
}

function onPlainInput(event) {
    setSource(event.target.value);
    dirty.value = true;
}

function useTemplate() {
    if (!template.value) return;
    if (!window.confirm(t('Replace your current LaTeX with this template? Your saved version is kept until you save again.'))) {
        template.value = '';
        return;
    }
    setSource(props.starters[template.value] || '');
    dirty.value = true;
    status.value = t('Template loaded: :name', { name: props.templates[template.value] });
    template.value = '';
}

async function compile() {
    if (busy.value) return;
    if (!props.assetsUrl) {
        status.value = t('Compiling is not available on this site yet. You can still edit and save your LaTeX.');
        return;
    }
    busy.value = true;
    log.value = '';
    try {
        const mod = await import('texlyre-busytex');
        if (!runner) {
            status.value = t('Downloading the LaTeX engine, about 120 MB the first time. After that your browser keeps it.');
            const base = `${props.assetsUrl.replace(/\/$/, '')}/busytex`;
            const collection = (c) => `${base}/texlive-${c}.js`;
            // the core collection loads up front and covers every starter template. The larger
            // collections are a catalogue: a document that uses one of their packages downloads it then
            runner = new mod.BusyTexRunner({
                busytexBasePath: base,
                preloadDataPackages: [collection('basic')],
                catalogDataPackages: ['basic', 'recommended', 'extra'].map(collection),
                onDownloadProgress: (p) => { status.value = t('Downloading the LaTeX engine: :percent%', { percent: Math.min(100, Math.round(p.percent)) }); },
            });
            await withSameOriginWorker(`${base}/busytex_worker.js`, () => runner.initialize(true));
        }
        status.value = t('Compiling…');
        const Engine = { pdflatex: mod.PdfLatex, xelatex: mod.XeLatex, lualatex: mod.LuaLatex }[engine.value];
        const result = await new Engine(runner).compile({ input: source.value, rerun: true });
        log.value = result.log || '';
        if (result.success && result.pdf) {
            lastPdf.value = result.pdf;
            if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value);
            pdfUrl.value = URL.createObjectURL(new Blob([result.pdf], { type: 'application/pdf' }));
            status.value = t('Compiled. Check the preview, then save to attach the PDF to your CV.');
        } else {
            status.value = t('LaTeX reported an error. The log below shows where.');
        }
    } catch (error) {
        status.value = t('The LaTeX engine could not start. Try again with a recent version of Chrome, Edge, Firefox or Safari.');
        log.value = String(error?.message || error);
    } finally {
        busy.value = false;
    }
}

async function save() {
    if (busy.value) return;
    busy.value = true;
    status.value = t('Saving…');
    const body = new FormData();
    body.append('_method', 'PUT');
    body.append('source', source.value);
    body.append('engine', engine.value);
    if (lastPdf.value) body.append('pdf', new Blob([lastPdf.value], { type: 'application/pdf' }), 'cv.pdf');
    try {
        const response = await fetch(props.saveUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': props.csrf, Accept: 'application/json' },
            body,
        });
        if (!response.ok) throw new Error(String(response.status));
        const data = await response.json();
        dirty.value = false;
        status.value = data.pdfError
            ? t('Saved at :time. :error', { time: data.savedAt, error: data.pdfError })
            : lastPdf.value
            ? t('Saved at :time. Your compiled PDF is now this CV’s file.', { time: data.savedAt })
            : t('Saved at :time. Compile to attach a PDF to your CV.', { time: data.savedAt });
    } catch (error) {
        status.value = t('Saving failed. Check your connection and try again.');
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="grid gap-4">
        <div class="card flex flex-wrap items-end gap-3 p-4">
            <div>
                <label for="latex-template" class="field-label text-sm">{{ t('Start from a template') }}</label>
                <select id="latex-template" v-model="template" class="input min-h-10 py-1.5" @change="useTemplate">
                    <option value="">{{ t('Choose a template…') }}</option>
                    <option v-for="(label, key) in templates" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
            <div>
                <label for="latex-engine" class="field-label text-sm">{{ t('Engine') }}</label>
                <select id="latex-engine" v-model="engine" class="input min-h-10 py-1.5">
                    <option value="pdflatex">{{ t('pdfLaTeX (most templates)') }}</option>
                    <option value="xelatex">{{ t('XeLaTeX (system fonts, Unicode)') }}</option>
                    <option value="lualatex">LuaLaTeX</option>
                </select>
            </div>
            <div class="ms-auto flex flex-wrap gap-2">
                <button type="button" class="btn btn-secondary" :disabled="busy" aria-keyshortcuts="Control+Enter Meta+Enter" @click="compile">
                    {{ t('Compile') }} <span class="hidden text-sm font-normal sm:inline">({{ modKey }}+Enter)</span>
                </button>
                <button type="button" class="btn btn-primary" :disabled="busy" aria-keyshortcuts="Control+S Meta+S" @click="save">
                    {{ t('Save') }} <span class="hidden text-sm font-normal sm:inline">({{ modKey }}+S)</span>
                </button>
            </div>
            <p class="w-full text-sm text-muted" role="status" aria-live="polite">{{ status || (dirty ? t('You have unsaved changes.') : t('All changes saved.')) }}</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="card overflow-hidden p-0" aria-labelledby="source-title">
                <div class="flex items-center justify-between border-b border-line px-4 py-2">
                    <h2 id="source-title" class="text-base">{{ t('LaTeX source') }}</h2>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="plainMode" type="checkbox" class="check" /> {{ t('Plain text box') }}
                    </label>
                </div>
                <textarea v-if="plainMode" class="input min-h-[60vh] rounded-none border-0 font-mono text-sm" dir="ltr" :aria-label="t('LaTeX source')"
                    :value="source" @input="onPlainInput"></textarea>
                <div v-show="!plainMode" ref="editorHost" class="latex-editor min-h-[60vh] font-mono text-sm"></div>
            </section>

            <section class="card overflow-hidden p-0" aria-labelledby="preview-title">
                <div class="flex items-center justify-between border-b border-line px-4 py-2">
                    <h2 id="preview-title" class="text-base">{{ t('PDF preview') }}</h2>
                    <a v-if="pdfUrl" :href="pdfUrl" download="cv.pdf" class="text-sm">{{ t('Download this PDF') }}</a>
                </div>
                <iframe v-if="pdfUrl" :src="pdfUrl" :title="t('Compiled PDF preview')" class="h-[60vh] w-full bg-white"></iframe>
                <div v-else class="flex h-[60vh] items-center justify-center p-6 text-center text-muted">
                    <p>{{ t('Compile to see your PDF here. Everything runs in your browser; your LaTeX never leaves your device until you save.') }}</p>
                </div>
            </section>
        </div>

        <details v-if="log" class="card p-4">
            <summary class="cursor-pointer font-semibold">{{ t('Compiler log') }}</summary>
            <pre dir="ltr" class="mt-3 max-h-80 overflow-auto font-mono text-xs whitespace-pre-wrap">{{ log }}</pre>
        </details>
    </div>
</template>
