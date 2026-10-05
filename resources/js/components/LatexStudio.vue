<script setup>
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';

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
                EditorView.contentAttributes.of({ 'aria-label': 'LaTeX source', spellcheck: 'false' }),
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

onBeforeUnmount(() => {
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
    if (!window.confirm('Replace your current LaTeX with this template? Your saved version is kept until you save again.')) {
        template.value = '';
        return;
    }
    setSource(props.starters[template.value] || '');
    dirty.value = true;
    status.value = `Template loaded: ${props.templates[template.value]}`;
    template.value = '';
}

async function compile() {
    if (busy.value) return;
    if (!props.assetsUrl) {
        status.value = 'Compiling is not available on this site yet. You can still edit and save your LaTeX.';
        return;
    }
    busy.value = true;
    log.value = '';
    try {
        const mod = await import('texlyre-busytex');
        if (!runner) {
            status.value = 'Downloading the LaTeX engine. The first time takes a minute or two; after that it is cached.';
            runner = new mod.BusyTexRunner({ busytexBasePath: `${props.assetsUrl.replace(/\/$/, '')}/busytex` });
            await runner.initialize(true);
        }
        status.value = 'Compiling…';
        const Engine = { pdflatex: mod.PdfLatex, xelatex: mod.XeLatex, lualatex: mod.LuaLatex }[engine.value];
        const result = await new Engine(runner).compile({ input: source.value, rerun: true });
        log.value = result.log || '';
        if (result.success && result.pdf) {
            lastPdf.value = result.pdf;
            if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value);
            pdfUrl.value = URL.createObjectURL(new Blob([result.pdf], { type: 'application/pdf' }));
            status.value = 'Compiled. Check the preview, then save to attach the PDF to your CV.';
        } else {
            status.value = 'LaTeX reported an error. The log below shows where.';
        }
    } catch (error) {
        status.value = 'The LaTeX engine could not start. Try again with a recent version of Chrome, Edge, Firefox or Safari.';
        log.value = String(error?.message || error);
    } finally {
        busy.value = false;
    }
}

async function save() {
    if (busy.value) return;
    busy.value = true;
    status.value = 'Saving…';
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
            ? `Saved at ${data.savedAt}. ${data.pdfError}`
            : lastPdf.value
            ? `Saved at ${data.savedAt}. Your compiled PDF is now this CV's file.`
            : `Saved at ${data.savedAt}. Compile to attach a PDF to your CV.`;
    } catch (error) {
        status.value = 'Saving failed. Check your connection and try again.';
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="grid gap-4">
        <div class="card flex flex-wrap items-end gap-3 p-4">
            <div>
                <label for="latex-template" class="field-label text-sm">Start from a template</label>
                <select id="latex-template" v-model="template" class="input min-h-10 py-1.5" @change="useTemplate">
                    <option value="">Choose a template…</option>
                    <option v-for="(label, key) in templates" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
            <div>
                <label for="latex-engine" class="field-label text-sm">Engine</label>
                <select id="latex-engine" v-model="engine" class="input min-h-10 py-1.5">
                    <option value="pdflatex">pdfLaTeX (most templates)</option>
                    <option value="xelatex">XeLaTeX (system fonts, Unicode)</option>
                    <option value="lualatex">LuaLaTeX</option>
                </select>
            </div>
            <div class="ml-auto flex flex-wrap gap-2">
                <button type="button" class="btn btn-secondary" :disabled="busy" @click="compile">
                    Compile <span class="hidden text-sm font-normal sm:inline">(Ctrl+Enter)</span>
                </button>
                <button type="button" class="btn btn-primary" :disabled="busy" @click="save">
                    Save <span class="hidden text-sm font-normal sm:inline">(Ctrl+S)</span>
                </button>
            </div>
            <p class="w-full text-sm text-muted" role="status" aria-live="polite">{{ status || (dirty ? 'You have unsaved changes.' : 'All changes saved.') }}</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="card overflow-hidden p-0" aria-labelledby="source-title">
                <div class="flex items-center justify-between border-b border-line px-4 py-2">
                    <h2 id="source-title" class="text-base">LaTeX source</h2>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="plainMode" type="checkbox" class="check" /> Plain text box
                    </label>
                </div>
                <textarea v-if="plainMode" class="input min-h-[60vh] rounded-none border-0 font-mono text-sm" aria-label="LaTeX source"
                    :value="source" @input="onPlainInput"></textarea>
                <div v-show="!plainMode" ref="editorHost" class="latex-editor min-h-[60vh] font-mono text-sm"></div>
            </section>

            <section class="card overflow-hidden p-0" aria-labelledby="preview-title">
                <div class="flex items-center justify-between border-b border-line px-4 py-2">
                    <h2 id="preview-title" class="text-base">PDF preview</h2>
                    <a v-if="pdfUrl" :href="pdfUrl" download="cv.pdf" class="text-sm">Download this PDF</a>
                </div>
                <iframe v-if="pdfUrl" :src="pdfUrl" title="Compiled PDF preview" class="h-[60vh] w-full bg-white"></iframe>
                <div v-else class="flex h-[60vh] items-center justify-center p-6 text-center text-muted">
                    <p>Compile to see your PDF here. Everything runs in your browser; your LaTeX never leaves your device until you save.</p>
                </div>
            </section>
        </div>

        <details v-if="log" class="card p-4">
            <summary class="cursor-pointer font-semibold">Compiler log</summary>
            <pre class="mt-3 max-h-80 overflow-auto font-mono text-xs whitespace-pre-wrap">{{ log }}</pre>
        </details>
    </div>
</template>
