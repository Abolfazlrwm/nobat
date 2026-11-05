<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    status: string;
    label?: string;
}>();

const styles: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    confirmed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    completed: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
    no_show: 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
};

const classes = computed(() => styles[props.status] ?? 'bg-muted text-muted-foreground');
</script>

<template>
    <span :class="cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', classes)">
        {{ label ?? status }}
    </span>
</template>
