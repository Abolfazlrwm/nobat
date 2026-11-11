<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        points: { label: string; value: number }[];
        valueFormatter?: (value: number) => string;
        compact?: boolean;
    }>(),
    {
        valueFormatter: undefined,
        compact: false,
    },
);

const max = computed(() => Math.max(1, ...props.points.map((p) => p.value)));

function formatted(point: { label: string; value: number }): string {
    return props.valueFormatter ? props.valueFormatter(point.value) : String(point.value);
}
</script>

<template>
    <div class="flex h-44 items-end justify-between gap-1">
        <div v-for="point in points" :key="point.label + point.value" class="group flex min-w-0 flex-1 flex-col items-center gap-1.5">
            <span class="max-w-full truncate text-[10px] text-muted-foreground" :class="compact && !point.value ? 'invisible' : ''">
                {{ point.value ? formatted(point) : '' }}
            </span>
            <div
                class="w-full max-w-9 rounded-t-md bg-primary/80 transition-all group-hover:bg-primary"
                :style="{ height: `${(point.value / max) * 100}%`, minHeight: point.value ? '6px' : '2px' }"
            />
            <span class="max-w-full truncate text-[10px] text-muted-foreground">{{ point.label }}</span>
        </div>
    </div>
</template>
