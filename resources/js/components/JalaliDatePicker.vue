<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { jalaliMonthLength, parseLocal, toDateString, toGregorian, toJalaali } from '@/lib/persian';
import { cn } from '@/lib/utils';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        minDate?: string | null;
        maxDate?: string | null;
    }>(),
    {
        modelValue: null,
        minDate: null,
        maxDate: null,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const { t, isEn, num } = useLocale();

const today = new Date();
const todayJ = toJalaali(today);

const viewYear = ref(todayJ.jy);
const viewMonth = ref(todayJ.jm);

// When the selected value moves outside the visible month, follow it.
watch(
    () => props.modelValue,
    (value) => {
        if (!value) return;

        const { jy, jm } = toJalaali(parseLocal(value));
        viewYear.value = jy;
        viewMonth.value = jm;
    },
    { immediate: true },
);

const monthLength = computed(() => jalaliMonthLength(viewYear.value, viewMonth.value));

/** Index (0-6) of the weekday the month starts on, 0 = Saturday. */
const firstWeekdayIndex = computed(() => {
    const g = toGregorian(viewYear.value, viewMonth.value, 1);
    const jsDay = new Date(g.gy, g.gm - 1, g.gd).getDay();

    return (jsDay + 1) % 7;
});

const monthLabel = computed(() => `${t(`calendar.months.${viewMonth.value - 1}`)} ${num(viewYear.value)}`);

/** Start-of-month chevron: points "back" in each reading direction. */
const prevIcon = computed(() => (isEn.value ? ChevronLeft : ChevronRight));
const nextIcon = computed(() => (isEn.value ? ChevronRight : ChevronLeft));

interface DayCell {
    day: number;
    iso: string;
    disabled: boolean;
    isToday: boolean;
    isSelected: boolean;
}

const days = computed<DayCell[]>(() => {
    const cells: DayCell[] = [];

    for (let i = 0; i < firstWeekdayIndex.value; i++) {
        cells.push({ day: 0, iso: '', disabled: true, isToday: false, isSelected: false });
    }

    for (let day = 1; day <= monthLength.value; day++) {
        const g = toGregorian(viewYear.value, viewMonth.value, day);
        const iso = toDateString(new Date(g.gy, g.gm - 1, g.gd));
        const isToday = todayJ.jy === viewYear.value && todayJ.jm === viewMonth.value && todayJ.jd === day;

        cells.push({
            day,
            iso,
            disabled: isOutOfSelectionRange(iso),
            isToday,
            isSelected: props.modelValue === iso,
        });
    }

    return cells;
});

const weekdayHeaders = computed(() => [0, 1, 2, 3, 4, 5, 6].map((i) => t(`calendar.weekdays.${i}`)));

function isOutOfSelectionRange(iso: string): boolean {
    if (props.minDate && iso < props.minDate) return true;
    if (props.maxDate && iso > props.maxDate) return true;

    return false;
}

function go(delta: number): void {
    let month = viewMonth.value + delta;
    let year = viewYear.value;

    if (month > 12) {
        month = 1;
        year++;
    } else if (month < 1) {
        month = 12;
        year--;
    }

    viewMonth.value = month;
    viewYear.value = year;
}

function select(cell: DayCell): void {
    if (cell.disabled || cell.day === 0) return;

    emit('update:modelValue', cell.iso);
}
</script>

<template>
    <div :dir="isEn ? 'ltr' : 'rtl'" class="w-72 rounded-xl border bg-card p-3 text-card-foreground shadow-sm">
        <div class="mb-2 flex items-center justify-between">
            <Button variant="ghost" size="icon" type="button" :aria-label="t('calendar.prevMonth')" @click="go(-1)">
                <component :is="prevIcon" class="size-4" />
            </Button>
            <div class="text-sm font-medium">{{ monthLabel }}</div>
            <Button variant="ghost" size="icon" type="button" :aria-label="t('calendar.nextMonth')" @click="go(1)">
                <component :is="nextIcon" class="size-4" />
            </Button>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-xs text-muted-foreground">
            <span v-for="(name, index) in weekdayHeaders" :key="index" class="py-1">{{ name }}</span>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <button
                v-for="(cell, index) in days"
                :key="index"
                type="button"
                :disabled="cell.disabled || cell.day === 0"
                :class="
                    cn(
                        'flex h-9 items-center justify-center rounded-md text-sm transition-colors',
                        cell.day === 0 && 'invisible',
                        !cell.disabled && 'hover:bg-accent',
                        cell.isSelected && 'bg-primary font-medium text-primary-foreground hover:bg-primary',
                        cell.isToday && !cell.isSelected && 'font-semibold text-primary',
                        cell.disabled && 'cursor-not-allowed text-muted-foreground/40',
                    )
                "
                @click="select(cell)"
            >
                {{ cell.day ? num(cell.day) : '' }}
            </button>
        </div>
    </div>
</template>
