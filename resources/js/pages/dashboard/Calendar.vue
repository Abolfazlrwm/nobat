<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { parseLocal } from '@/lib/persian';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppointmentItem, BreadcrumbItem, CalendarDay, CalendarWaitlistItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Hourglass } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    filters: { jy: number; jm: number; date: string };
    month: { jy: number; jm: number; length: number; first_weekday: number };
    today_jalali: { jy: number; jm: number; jd: number };
    days: CalendarDay[];
    selected: {
        date: string;
        jd: number;
        weekday: number;
        appointments: AppointmentItem[];
        waitlist: CalendarWaitlistItem[];
    };
    businesses: { id: number; name: string }[];
    business_id: number | null;
}>();

const { t, num, dateShort, weekdayShort, statusLabel, isEn } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.nav.dashboard'), href: '/dashboard' },
    { title: t('panel.calendar.title'), href: '/calendar' },
]);

const monthLabel = computed(() => `${t(`calendar.months.${props.month.jm - 1}`)} ${num(props.month.jy)}`);

const weekdayHeaders = computed(() => [0, 1, 2, 3, 4, 5, 6].map((i) => t(`calendar.weekdays.${i}`)));

/** Leading blanks before day 1, then the month's days — grid fills RTL-first. */
const cells = computed<(CalendarDay | null)[]>(() => [
    ...Array.from({ length: props.month.first_weekday }, () => null),
    ...props.days,
]);

/** Start-of-month chevron: points "back" in each reading direction. */
const prevIcon = computed(() => (isEn.value ? ChevronLeft : ChevronRight));
const nextIcon = computed(() => (isEn.value ? ChevronRight : ChevronLeft));

const legend = ['pending', 'confirmed', 'completed', 'cancelled'];

/** Slot-load tint, strongest where the day is busiest. */
const loadTint = (day: CalendarDay): string => {
    if (day.blocking_count === 0) {
        return day.weekday === 6 ? 'bg-red-50 dark:bg-red-950/20' : 'bg-background';
    }

    if (day.blocking_count <= 2) {
        return 'bg-emerald-50 dark:bg-emerald-950/20';
    }

    if (day.blocking_count <= 5) {
        return 'bg-emerald-100 dark:bg-emerald-950/40';
    }

    return 'bg-emerald-200 dark:bg-emerald-950/60';
};

const dotClass: Record<string, string> = {
    pending: 'bg-amber-500',
    pending_payment: 'bg-amber-400',
    confirmed: 'bg-emerald-600',
    cancelled: 'bg-red-400',
    completed: 'bg-sky-600',
    no_show: 'bg-zinc-400',
    expired: 'bg-zinc-300',
};

const visibleCounts = (day: CalendarDay) =>
    ['confirmed', 'pending', 'completed', 'cancelled'].filter((status) => day.counts[status] > 0);

const cellTooltip = (day: CalendarDay): string =>
    t('panel.calendar.cellTooltip', { n: num(day.blocking_count), m: num(day.busy_minutes) });

const timeOf = (iso: string): string => {
    const date = parseLocal(iso);

    return num(`${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`);
};

const waitlistStatusLabel = (entry: CalendarWaitlistItem): string =>
    t(`panel.calendar.waitlist.${entry.status}`, entry.status_label);

// The chosen business stays sticky across month and day navigation.
function goTo(params: Record<string, string | number | undefined>): void {
    router.get(
        route('calendar.index'),
        { business_id: props.business_id ?? undefined, ...params },
        { preserveScroll: true },
    );
}

function shiftMonth(delta: number): void {
    let jy = props.month.jy;
    let jm = props.month.jm + delta;

    if (jm > 12) {
        jm = 1;
        jy += 1;
    } else if (jm < 1) {
        jm = 12;
        jy -= 1;
    }

    goTo({ jy, jm });
}

function selectDay(day: CalendarDay): void {
    goTo({ jy: props.month.jy, jm: props.month.jm, date: day.date });
}

function goToday(): void {
    goTo({ jy: props.today_jalali.jy, jm: props.today_jalali.jm });
}

function switchBusiness(event: Event): void {
    const id = (event.target as HTMLSelectElement).value;

    goTo({ business_id: id || undefined });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('panel.calendar.title')" />
        <FlashMessages />

        <div class="flex-1 space-y-6 p-4 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold">{{ t('panel.calendar.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('panel.calendar.subtitle') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-if="businesses.length"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        :value="business_id ?? ''"
                        @change="switchBusiness"
                    >
                        <option value="">{{ t('panel.calendar.allBusinesses') }}</option>
                        <option v-for="business in businesses" :key="business.id" :value="business.id">
                            {{ business.name }}
                        </option>
                    </select>
                    <Button variant="outline" size="sm" @click="goToday">{{ t('panel.calendar.today') }}</Button>
                    <Button variant="outline" size="icon" :aria-label="t('calendar.prevMonth')" @click="shiftMonth(-1)">
                        <component :is="prevIcon" class="h-4 w-4" />
                    </Button>
                    <span class="min-w-32 text-center text-sm font-semibold">{{ monthLabel }}</span>
                    <Button variant="outline" size="icon" :aria-label="t('calendar.nextMonth')" @click="shiftMonth(1)">
                        <component :is="nextIcon" class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div class="rounded-xl border bg-background p-3 md:p-4">
                <div class="grid grid-cols-7 gap-1.5 text-center text-xs font-medium text-muted-foreground">
                    <div v-for="(header, index) in weekdayHeaders" :key="header" :class="index === 6 ? 'text-red-500' : ''">
                        {{ header }}
                    </div>
                </div>
                <div class="mt-2 grid grid-cols-7 gap-1.5">
                    <template v-for="(cell, index) in cells" :key="cell ? cell.date : `blank-${index}`">
                        <button
                            v-if="cell"
                            type="button"
                            class="relative flex min-h-20 cursor-pointer flex-col rounded-lg border p-2 text-right transition hover:border-primary"
                            :class="[
                                loadTint(cell),
                                cell.is_past ? 'opacity-60' : '',
                                cell.date === filters.date ? 'border-primary ring-2 ring-primary' : '',
                                cell.is_today && cell.date !== filters.date ? 'border-primary' : '',
                            ]"
                            :title="cellTooltip(cell)"
                            @click="selectDay(cell)"
                        >
                            <span
                                class="text-sm font-bold"
                                :class="cell.weekday === 6 ? 'text-red-500' : 'text-foreground'"
                            >{{ num(cell.jd) }}</span>
                            <span
                                v-if="cell.waitlist_count > 0"
                                class="mt-0.5 inline-flex items-center gap-0.5 rounded-full bg-amber-100 px-1.5 text-[10px] font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                            >
                                <Hourglass class="h-2.5 w-2.5" />
                                {{ num(cell.waitlist_count) }}
                            </span>
                            <span class="mt-auto flex flex-wrap gap-x-1.5 gap-y-0.5 pt-1">
                                <span
                                    v-for="status in visibleCounts(cell)"
                                    :key="status"
                                    class="flex items-center gap-1 text-[10px] text-muted-foreground"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="dotClass[status]" />
                                    {{ num(cell.counts[status]) }}
                                </span>
                            </span>
                        </button>
                        <div v-else class="min-h-20 rounded-lg border border-dashed border-transparent" />
                    </template>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 border-t pt-3 text-xs text-muted-foreground">
                    <span v-for="status in legend" :key="status" class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full" :class="dotClass[status]" />
                        {{ statusLabel(status, status) }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <Hourglass class="h-3 w-3 text-amber-600" />
                        {{ t('panel.calendar.waitlistDemand') }}
                    </span>
                </div>
            </div>

            <div class="rounded-xl border bg-background">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b p-4">
                    <h2 class="text-sm font-bold">{{ dateShort(selected.date) }}</h2>
                    <span class="text-xs text-muted-foreground">{{ weekdayShort(selected.date) }}</span>
                </div>

                <div class="divide-y">
                    <div
                        v-for="appointment in selected.appointments"
                        :key="appointment.id"
                        class="flex flex-wrap items-center justify-between gap-2 p-4"
                    >
                        <div class="flex items-center gap-3">
                            <span class="font-semibold" dir="ltr">{{ timeOf(appointment.starts_at) }}</span>
                            <div>
                                <div class="text-sm font-medium">{{ appointment.customer_name }}</div>
                                <div class="text-xs text-muted-foreground" dir="ltr">{{ appointment.customer_phone }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ appointment.service_name }}
                            <span v-if="appointment.staff_name"> · {{ appointment.staff_name }}</span>
                        </div>
                        <StatusBadge :status="appointment.status" :label="statusLabel(appointment.status, appointment.status_label)" />
                    </div>
                    <div v-if="selected.appointments.length === 0" class="p-4 text-sm text-muted-foreground">
                        {{ t('panel.calendar.emptyDay') }}
                    </div>
                </div>

                <div v-if="selected.waitlist.length" class="border-t p-4">
                    <div class="flex items-center gap-2 text-xs font-semibold text-amber-700 dark:text-amber-400">
                        <Hourglass class="h-3.5 w-3.5" />
                        {{ t('panel.calendar.waitlistDemand') }}
                    </div>
                    <div class="mt-2 space-y-1.5">
                        <div
                            v-for="entry in selected.waitlist"
                            :key="entry.id"
                            class="flex flex-wrap items-center justify-between gap-2 text-sm"
                        >
                            <div>
                                <span class="font-medium">{{ entry.customer_name }}</span>
                                <span class="text-xs text-muted-foreground" dir="ltr"> · {{ entry.customer_phone }}</span>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ waitlistStatusLabel(entry) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
