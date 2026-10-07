<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { t } from '../i18n.js';

const props = defineProps({
    input: { type: String, required: true },
});

// the preview circle in css pixels; the crop is sent as fractions, so this never reaches the server
const VIEW = 224;
const STEP = 8;

const src = ref('');
const natural = ref({ w: 0, h: 0 });
const zoom = ref(1);
const offset = ref({ x: 0, y: 0 });
const status = ref('');
let drag = null;
let fileInput = null;

const baseScale = computed(() => (natural.value.w ? VIEW / Math.min(natural.value.w, natural.value.h) : 1));
const scale = computed(() => baseScale.value * zoom.value);
const shown = computed(() => ({ w: natural.value.w * scale.value, h: natural.value.h * scale.value }));

// fractions of the upright photo, which is what the browser shows and what the server rebuilds
const crop = computed(() => {
    if (!natural.value.w) return null;
    const round = (n) => Math.min(1, Math.max(0, n)).toFixed(5);
    return {
        x: round(-offset.value.x / scale.value / natural.value.w),
        y: round(-offset.value.y / scale.value / natural.value.h),
        size: round(VIEW / scale.value / natural.value.w),
    };
});

const imageStyle = computed(() => ({
    width: `${shown.value.w}px`,
    height: `${shown.value.h}px`,
    transform: `translate(${offset.value.x}px, ${offset.value.y}px)`,
}));

function clamp(x, y) {
    return {
        x: Math.min(0, Math.max(VIEW - shown.value.w, x)),
        y: Math.min(0, Math.max(VIEW - shown.value.h, y)),
    };
}

function centre() {
    zoom.value = 1;
    offset.value = clamp((VIEW - shown.value.w) / 2, (VIEW - shown.value.h) / 2);
}

function setZoom(value) {
    // zoom around the middle of the circle, so the face stays put while zooming
    const before = scale.value;
    const mid = VIEW / 2;
    zoom.value = Math.min(4, Math.max(1, Number(value)));
    const ratio = scale.value / before;
    offset.value = clamp(mid - (mid - offset.value.x) * ratio, mid - (mid - offset.value.y) * ratio);
}

function onFile() {
    const file = fileInput?.files?.[0];
    if (src.value) URL.revokeObjectURL(src.value);
    src.value = '';
    natural.value = { w: 0, h: 0 };
    if (!file || !file.type.startsWith('image/')) return;
    src.value = URL.createObjectURL(file);
}

function onLoad(event) {
    natural.value = { w: event.target.naturalWidth, h: event.target.naturalHeight };
    centre();
    status.value = t('Photo loaded. Drag it, use the arrow keys or the zoom slider to frame it.');
}

function onError() {
    src.value = '';
    status.value = t('That photo cannot be previewed here. It will still be cropped to the centre when you upload it.');
}

function onPointerDown(event) {
    drag = { id: event.pointerId, x: event.clientX - offset.value.x, y: event.clientY - offset.value.y };
    event.currentTarget.setPointerCapture(event.pointerId);
}

function onPointerMove(event) {
    if (!drag || drag.id !== event.pointerId) return;
    offset.value = clamp(event.clientX - drag.x, event.clientY - drag.y);
}

function onPointerUp() {
    drag = null;
}

function onKey(event) {
    const step = event.shiftKey ? STEP * 4 : STEP;
    const moves = { ArrowLeft: [step, 0], ArrowRight: [-step, 0], ArrowUp: [0, step], ArrowDown: [0, -step] };
    if (moves[event.key]) {
        event.preventDefault();
        const [dx, dy] = moves[event.key];
        offset.value = clamp(offset.value.x + dx, offset.value.y + dy);
    } else if (event.key === '+' || event.key === '=') {
        event.preventDefault();
        setZoom(zoom.value + 0.25);
    } else if (event.key === '-') {
        event.preventDefault();
        setZoom(zoom.value - 0.25);
    }
}

onMounted(() => {
    fileInput = document.getElementById(props.input);
    fileInput?.addEventListener('change', onFile);
});

onBeforeUnmount(() => {
    fileInput?.removeEventListener('change', onFile);
    if (src.value) URL.revokeObjectURL(src.value);
});
</script>

<template>
    <div>
        <div v-if="src" class="flex flex-wrap items-center gap-6">
            <div
                class="relative shrink-0 cursor-grab touch-none overflow-hidden size-56 rounded-full bg-raised ring-2 ring-line-strong select-none focus-visible:outline-3 focus-visible:outline-offset-2"
                tabindex="0"
                role="group"
                :aria-label="t('Photo framing. Use the arrow keys to move the photo, plus and minus to zoom.')"
                @pointerdown="onPointerDown"
                @pointermove="onPointerMove"
                @pointerup="onPointerUp"
                @pointercancel="onPointerUp"
                @keydown="onKey"
            >
                <img :src="src" alt="" draggable="false" class="absolute top-0 left-0 max-w-none" dir="ltr" :style="imageStyle" @load="onLoad" @error="onError" />
            </div>
            <div class="grid min-w-48 flex-1 gap-3">
                <label class="field-label" for="avatar-zoom">{{ t('Zoom') }}</label>
                <input id="avatar-zoom" type="range" min="1" max="4" step="0.05" :value="zoom" class="w-full accent-brand"
                    @input="setZoom($event.target.value)" />
                <div>
                    <button type="button" class="btn btn-sm btn-secondary" @click="centre">{{ t('Reset framing') }}</button>
                </div>
                <p class="text-sm text-muted">{{ t('The circle shows how the photo appears on your profile and CVs.') }}</p>
            </div>
        </div>
        <template v-if="crop">
            <input type="hidden" name="crop_x" :value="crop.x" />
            <input type="hidden" name="crop_y" :value="crop.y" />
            <input type="hidden" name="crop_size" :value="crop.size" />
        </template>
        <p class="sr-only" role="status" aria-live="polite">{{ status }}</p>
    </div>
</template>
