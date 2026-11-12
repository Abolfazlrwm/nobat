<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import { ChartPoint } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    points: ChartPoint[];
}>();

const { t, num, weekdayShort } = useLocale();

const max = computed(() => Math.max(1, ...props.points.map((p) => p.count)));
</script>

<template>
    <Card>
        <CardContent class="pt-6">
            <h3 class="mb-4 text-sm font-medium text-muted-foreground">{{ t('panel.weekChart.title') }}</h3>
            <div class="flex h-40 items-end justify-between gap-2">
                <div v-for="point in points" :key="point.date" class="flex flex-1 flex-col items-center gap-2">
                    <span class="text-xs text-muted-foreground">{{ point.count ? num(point.count) : '' }}</span>
                    <div
                        class="w-full max-w-8 rounded-t-md bg-primary/80 transition-all"
                        :style="{ height: `${(point.count / max) * 100}%`, minHeight: point.count ? '6px' : '2px' }"
                    />
                    <span class="text-[11px] text-muted-foreground">{{ weekdayShort(point.date) }}</span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
