<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, Info, X, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage<{ props: { flash: { success?: string; error?: string; info?: string } } }>();

const dismissed = ref<string[]>([]);

const messages = computed(() => {
    const items: { id: string; type: 'success' | 'error' | 'info'; text: string }[] = [];

    if (page.props.flash.success && !dismissed.value.includes('success')) {
        items.push({ id: 'success', type: 'success', text: page.props.flash.success });
    }

    if (page.props.flash.error && !dismissed.value.includes('error')) {
        items.push({ id: 'error', type: 'error', text: page.props.flash.error });
    }

    if (page.props.flash.info && !dismissed.value.includes('info')) {
        items.push({ id: 'info', type: 'info', text: page.props.flash.info });
    }

    return items;
});

function dismiss(id: string): void {
    dismissed.value.push(id);
}
</script>

<template>
    <div v-if="messages.length" class="space-y-2">
        <div
            v-for="message in messages"
            :key="message.id"
            :class="[
                'flex items-center justify-between rounded-lg border px-4 py-3 text-sm',
                message.type === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200'
                    : message.type === 'info'
                      ? 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200'
                      : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200',
            ]"
        >
            <span class="flex items-center gap-2">
                <CheckCircle2 v-if="message.type === 'success'" class="size-4" />
                <Info v-else-if="message.type === 'info'" class="size-4" />
                <XCircle v-else class="size-4" />
                {{ message.text }}
            </span>
            <button type="button" @click="dismiss(message.id)">
                <X class="size-4" />
            </button>
        </div>
    </div>
</template>
