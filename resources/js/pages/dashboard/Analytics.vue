<script setup lang="ts">
import BarSeries from '@/components/BarSeries.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { toJalaali } from '@/lib/jalali';
import { AnalyticsHeatCell, AnalyticsHourPeak, AnalyticsPoint, AnalyticsStats, BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpRight, CalendarCheck2, Hourglass, Star, Ticket, TrendingUp, Users, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';

interface StatusRow {
    status: string;
    label: string;
    count: number;
}

interface ServiceRow {
    name: string;
    count: number;
    revenue: number;
}

const props = defineProps<{
    days: number;
    stats: AnalyticsStats;
    series: AnalyticsPoint[];
    by_status: StatusRow[];
    top_services: ServiceRow[];
    peak_hours: AnalyticsHourPeak[];
    heatmap: AnalyticsHeatCell[];
}>();

const { t, num, price, weekdayShort, isFa, isAr } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.analytics.title'), href: '/analytics' },
]);

const RANGES = [7, 30, 90];

// Iranian weekday rows (شنبه = 0); English abbreviations share the same order.
const EN_WEEKDAYS = ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

function pickRange(days: number): void {
    if (days === props.days) return;

    router.get(route('analytics.index'), { days }, { preserveState: true });
}

function deltaBadge(delta: number | null) {
    if (delta === null || delta === 0) {
        return { icon: null, class: 'bg-muted text-muted-foreground', text: t('panel.analytics.noChange') };
    }

    const pretty = num(String(Math.abs(delta)).replace('.', isFa.value ? '٫' : '.'));

    return delta > 0
        ? {
              icon: ArrowUpRight,
              class: 'bg-emerald-500/15 text-emerald-600',
              text: t('panel.analytics.delta', { n: pretty }),
          }
        : {
              icon: ArrowDownRight,
              class: 'bg-red-500/15 text-red-600',
              text: t('panel.analytics.delta', { n: pretty }),
          };
}

const rangeLabel = computed(() => {
    if (props.days === 7) return t('panel.analytics.range7');
    if (props.days === 90) return t('panel.analytics.range90');

    return t('panel.analytics.range30');
});

// Day-of-month for the x axis; weekday names for the 7-day view.
const bookingPoints = computed(() =>
    props.series.map((point) => ({
        label: props.days === 7 ? weekdayShort(point.date) : axisDayLabel(point.date),
        value: point.count,
    })),
);

const revenuePoints = computed(() =>
    props.series.map((point) => ({
        label: props.days === 7 ? weekdayShort(point.date) : axisDayLabel(point.date),
        value: point.revenue,
    })),
);

function axisDayLabel(date: string): string {
    if (isFa.value) {
        return num(toJalaali(new Date(date + 'T00:00:00')).jd);
    }

    return String(new Date(date + 'T00:00:00').getDate());
}

const revenueFormatter = (value: number): string => {
    if (isFa.value) {
        if (value >= 1_000_000) return `${num((value / 1_000_000).toLocaleString('en-US', { maximumFractionDigits: 1 }))} م`;

        if (value >= 1_000) return `${num(Math.round(value / 1_000).toLocaleString('en-US'))} هـ`;

        return price(value);
    }

    if (isAr.value) {
        if (value >= 1_000_000) return `${num((value / 1_000_000).toLocaleString('en-US', { maximumFractionDigits: 1 }))} م`;

        if (value >= 1_000) return `${num(Math.round(value / 1_000).toLocaleString('en-US'))} ألف`;

        return price(value);
    }

    if (value >= 1_000_000) return `${(value / 1_000_000).toLocaleString('en-US', { maximumFractionDigits: 1 })}M`;

    if (value >= 1_000) return `${Math.round(value / 1_000).toLocaleString('en-US')}K`;

    return price(value);
};

// Heatmap grid: Iranian weekday rows (شنبه = 0) and the booked hours as columns.
const heatmapHours = computed(() => [...new Set(props.heatmap.map((cell) => cell.hour))].sort((a, b) => a - b));

const heatMax = computed(() => Math.max(1, ...props.heatmap.map((cell) => cell.count)));

const heatWeekdays = computed(() =>
    Array.from({ length: 7 }, (_, weekday) => ({ weekday, label: isEn.value ? EN_WEEKDAYS[weekday] : t(`panel.weekdays.${weekday}`) })),
);

function heatCount(weekday: number, hour: number): number {
    return props.heatmap.find((cell) => cell.weekday === weekday && cell.hour === hour)?.count ?? 0;
}

function heatStyle(weekday: number, hour: number): Record<string, string> {
    const count = heatCount(weekday, hour);

    if (!count) {
        return { backgroundColor: 'hsl(var(--muted))' };
    }

    const alpha = 0.15 + (count / heatMax.value) * 0.75;

    return {
        backgroundColor: `hsl(var(--primary) / ${alpha.toFixed(2)})`,
        color: 'hsl(var(--primary-foreground))',
    };
}

const peakMax = computed(() => Math.max(1, ...props.peak_hours.map((p) => p.count)));

function hourLabel(hour: number): string {
    return `${num(hour)}:00`;
}

const statCards = computed(() => [
    {
        key: 'revenue',
        label: t('panel.analytics.statRevenue'),
        icon: Wallet,
        value: price(props.stats.revenue),
        delta: props.stats.revenue_delta,
        hint: t('panel.analytics.hintRevenue', { n: num(props.stats.completed) }),
    },
    {
        key: 'bookings',
        label: t('panel.analytics.statBookings'),
        icon: Ticket,
        value: num(props.stats.bookings),
        delta: props.stats.bookings_delta,
        hint: t('panel.analytics.hintBookings', { n: num(props.stats.completed), m: num(props.stats.bookings) }),
    },
    {
        key: 'completion',
        label: t('panel.analytics.statCompletion'),
        icon: CalendarCheck2,
        value: `${isFa.value ? '٪' : ''}${num(props.stats.completion_rate)}${isFa.value ? '' : '%'}`,
        delta: null,
        hint: t('panel.analytics.hintCompletion', { n: num(props.stats.cancellation_rate) }),
    },
    {
        key: 'ticket',
        label: t('panel.analytics.statTicket'),
        icon: TrendingUp,
        value: props.stats.avg_ticket !== null ? price(props.stats.avg_ticket) : '—',
        delta: null,
        hint: t('panel.analytics.hintTicket'),
    },
    {
        key: 'customers',
        label: t('panel.analytics.statCustomers'),
        icon: Users,
        value: num(props.stats.unique_customers),
        delta: null,
        hint: t('panel.analytics.hintCustomers'),
    },
    {
        key: 'rating',
        label: t('panel.analytics.statRating'),
        icon: Star,
        value: props.stats.avg_rating !== null ? t('panel.analytics.ratingValue', { n: num(props.stats.avg_rating) }) : '—',
        delta: null,
        hint: t('panel.analytics.hintRating'),
    },
]);
</script>

<template>
    <Head :title="t('panel.analytics.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold">{{ t('panel.analytics.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('panel.analytics.subtitle', { range: rangeLabel }) }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        v-for="range in RANGES"
                        :key="range"
                        size="sm"
                        :variant="range === props.days ? 'default' : 'outline'"
                        @click="pickRange(range)"
                    >
                        {{ t('panel.analytics.days', { n: num(range) }) }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="card in statCards" :key="card.key">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ card.label }}</CardTitle>
                        <component :is="card.icon" class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent class="space-y-1.5">
                        <div class="text-2xl font-bold">{{ card.value }}</div>
                        <div class="flex items-center gap-2">
                            <Badge
                                v-if="card.delta !== null"
                                :class="deltaBadge(card.delta).class"
                                class="gap-1 rounded px-1.5 py-0 text-[11px] font-medium"
                            >
                                <component :is="deltaBadge(card.delta).icon" v-if="deltaBadge(card.delta).icon" class="size-3" />
                                {{ deltaBadge(card.delta).text }}
                            </Badge>
                            <span v-else class="text-xs text-muted-foreground">{{ card.hint }}</span>
                        </div>
                        <p v-if="card.delta !== null" class="text-xs text-muted-foreground">{{ card.hint }}</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('panel.analytics.bookingTrend') }}</CardTitle>
                        <CardDescription>{{ t('panel.analytics.bookingTrendDesc') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <BarSeries :points="bookingPoints" :compact="props.days > 7" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('panel.analytics.revenueTrend') }}</CardTitle>
                        <CardDescription>{{ t('panel.analytics.revenueTrendDesc') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <BarSeries :points="revenuePoints" :compact="props.days > 7" :value-formatter="revenueFormatter" />
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('panel.analytics.peakHours') }}</CardTitle>
                        <CardDescription>{{ t('panel.analytics.peakHoursDesc') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="peak in peak_hours" :key="peak.hour" class="flex items-center gap-3 text-sm">
                            <span class="w-14 shrink-0 font-medium" dir="ltr">{{ hourLabel(peak.hour) }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-primary/80" :style="{ width: `${(peak.count / peakMax) * 100}%` }" />
                            </div>
                            <span class="w-20 shrink-0 text-left text-xs text-muted-foreground">
                                {{ t('panel.analytics.peakCount', { n: num(peak.count) }) }}
                            </span>
                        </div>
                        <p v-if="peak_hours.length === 0" class="text-sm text-muted-foreground">{{ t('panel.analytics.emptyRange') }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('panel.analytics.statusSplit') }}</CardTitle>
                        <CardDescription>{{ t('panel.analytics.statusSplitDesc') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="row in by_status" :key="row.status" class="flex items-center justify-between text-sm">
                            <StatusBadge :status="row.status" />
                            <span class="font-semibold">{{ num(row.count) }}</span>
                        </div>
                        <p v-if="by_status.length === 0" class="text-sm text-muted-foreground">{{ t('panel.analytics.emptyRange') }}</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('panel.analytics.heatmap') }}</CardTitle>
                        <CardDescription>{{ t('panel.analytics.heatmapDesc') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="heatmap.length > 0" class="overflow-x-auto">
                            <table class="w-full min-w-96 border-separate border-spacing-1 text-center text-[11px]">
                                <thead>
                                    <tr>
                                        <th class="w-14"></th>
                                        <th v-for="hour in heatmapHours" :key="hour" class="font-medium text-muted-foreground">
                                            {{ num(hour) }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in heatWeekdays" :key="row.weekday">
                                        <td class="text-right text-xs font-medium text-muted-foreground">{{ row.label }}</td>
                                        <td v-for="hour in heatmapHours" :key="hour">
                                            <div
                                                class="mx-auto flex h-7 w-full min-w-7 items-center justify-center rounded-md"
                                                :style="heatStyle(row.weekday, hour)"
                                            >
                                                <span v-if="heatCount(row.weekday, hour)">{{ num(heatCount(row.weekday, hour)) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">{{ t('panel.analytics.emptyData') }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('panel.analytics.topServices') }}</CardTitle>
                        <CardDescription>{{ t('panel.analytics.topServicesDesc') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="service in top_services" :key="service.name" class="flex items-center justify-between text-sm">
                            <div>
                                <span class="font-medium">{{ service.name }}</span>
                                <span class="block text-xs text-muted-foreground">
                                    {{ t('panel.analytics.peakCount', { n: num(service.count) }) }} • {{ price(service.revenue) }}
                                </span>
                            </div>
                            <span class="rounded-md bg-muted px-2 py-1 text-xs font-semibold">
                                {{ price(service.revenue) }}
                            </span>
                        </div>
                        <p v-if="top_services.length === 0" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Hourglass class="size-4" />
                            {{ t('panel.analytics.emptyStats') }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
