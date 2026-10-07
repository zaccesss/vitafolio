<script setup>
import { onMounted, ref } from 'vue';
import { t } from '../i18n.js';

const props = defineProps({
    order: { type: Array, required: true },
    labels: { type: Object, required: true },
    saveUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});

const items = ref([...props.order]);
const status = ref('');
const list = ref(null);

// dragging is a convenience; the move buttons do the same job from the keyboard
onMounted(async () => {
    const { default: Sortable } = await import('sortablejs');
    Sortable.create(list.value, {
        handle: '[data-drag]',
        animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 150,
        onEnd: ({ oldIndex, newIndex }) => {
            if (oldIndex === newIndex) return;
            const next = [...items.value];
            next.splice(newIndex, 0, next.splice(oldIndex, 1)[0]);
            items.value = next;
            save(t(':section moved to position :position.', { section: props.labels[items.value[newIndex]], position: newIndex + 1 }));
        },
    });
});

function move(index, delta) {
    const target = index + delta;
    if (target < 0 || target >= items.value.length) return;
    const next = [...items.value];
    [next[index], next[target]] = [next[target], next[index]];
    items.value = next;
    save(t(':section moved to position :position.', { section: props.labels[next[target]], position: target + 1 }));
}

async function save(message) {
    try {
        const response = await fetch(props.saveUrl, {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': props.csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ order: items.value }),
        });
        if (!response.ok) throw new Error();
        status.value = t(':message Saved.', { message });
    } catch (e) {
        status.value = t('The new order could not be saved. Please try again.');
    }
}
</script>

<template>
    <div>
        <ol ref="list" class="grid gap-2">
            <li v-for="(key, index) in items" :key="key" class="flex items-center gap-3 rounded-xl border-2 border-line bg-surface px-3 py-2">
                <span data-drag class="cursor-grab select-none text-muted" aria-hidden="true" :title="t('Drag to reorder')">⠿</span>
                <span class="flex-1 font-semibold">{{ index + 1 }}. {{ labels[key] }}</span>
                <button type="button" class="btn btn-sm btn-secondary" :disabled="index === 0" @click="move(index, -1)">
                    {{ t('Move up') }}<span class="sr-only"> {{ labels[key] }}</span>
                </button>
                <button type="button" class="btn btn-sm btn-secondary" :disabled="index === items.length - 1" @click="move(index, 1)">
                    {{ t('Move down') }}<span class="sr-only"> {{ labels[key] }}</span>
                </button>
            </li>
        </ol>
        <p class="mt-2 text-sm text-muted" role="status" aria-live="polite">{{ status }}</p>
    </div>
</template>
