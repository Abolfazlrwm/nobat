<script setup lang="ts">
import BarSeries from '@/components/BarSeries.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { toJalaali } from '@/lib/jalali';
import { faDigits, faPrice, WEEKDAYS } from '@/lib/persian';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'تحلیل', href: '/analytics' },
];

const RANGES = [7, 30, 90];

function pickRange(days: number): void {
    if (days === props.days) return;

    router.get(route('analytics.index'), { days }, { preserveState: true });
}

function deltaBadge(delta: number | null) {
    if (delta === null || delta === 0) {
        return { icon: null, class: 'bg-muted text-muted-foreground', text: 'بدون تغییر نسبت به دوره‌ی قبل' };
    }

    return delta > 0
        ? {
              icon: ArrowUpRight,
              class: 'bg-emerald-500/15 text-emerald-600',
              text: `٪${faDigits(String(delta).replace('.', '٫'))} نسبت به دوره‌ی قبل`,
          }
        : {
              icon: ArrowDownRight,
              class: 'bg-red-500/15 text-red-600',
              text: `٪${faDigits(String(Math.abs(delta)).replace('.', '٫'))} نسبت به دوره‌ی قبل`,
          };
}

const rangeLabel = computed(() => {
    if (props.days === 7) return 'هفت روز گذشته';
    if (props.days === 90) return 'نود روز گذشته';

    return 'سی روز گذشته';
});

// Day-of-month (Jalali) for the x axis; weekday names for the 7-day view.
const bookingPoints = computed(() =>
    props.series.map((point) => ({
        label: props.days === 7 ? weekdayShort(point.date) : faDigits(toJalaali(new Date(point.date + 'T00:00:00')).jd),
        value: point.count,
    })),
);

const revenuePoints = computed(() =>
    props.series.map((point) => ({
        label: props.days === 7 ? weekdayShort(point.date) : faDigits(toJalaali(new Date(point.date + 'T00:00:00')).jd),
        value: point.revenue,
    })),
);

function weekdayShort(date: string): string {
    const jsDay = new Date(date + 'T00:00:00').getDay();

    return WEEKDAYS[(jsDay + 1) % 7].slice(0, 3);
}

const revenueFormatter = (value: number): string => {
    if (value >= 1_000_000) return `${faDigits((value / 1_000_000).toLocaleString('en-US', { maximumFractionDigits: 1 }))} م`;

    if (value >= 1_000) return `${faDigits(Math.round(value / 1_000).toLocaleString('en-US'))} هـ`;

    return faPrice(value);
};

// Heatmap grid: Iranian weekday rows (شنبه = 0) and the booked hours as columns.
const heatmapHours = computed(() => [...new Set(props.heatmap.map((cell) => cell.hour))].sort((a, b) => a - b));

const heatMax = computed(() => Math.max(1, ...props.heatmap.map((cell) => cell.count)));

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
    return `${faDigits(hour)}:۰۰`;
}

const statCards = computed(() => [
    {
        key: 'revenue',
        label: 'درآمد بازه',
        icon: Wallet,
        value: faPrice(props.stats.revenue),
        delta: props.stats.revenue_delta,
        hint: `${faDigits(props.stats.completed)} نوبت انجام‌شده`,
    },
    {
        key: 'bookings',
        label: 'کل نوبت‌ها',
        icon: Ticket,
        value: faDigits(props.stats.bookings),
        delta: props.stats.bookings_delta,
        hint: `${faDigits(props.stats.completed)} انجام‌شده از ${faDigits(props.stats.bookings)} نوبت`,
    },
    {
        key: 'completion',
        label: 'نرخ تکمیل',
        icon: CalendarCheck2,
        value: `٪${faDigits(props.stats.completion_rate)}`,
        delta: null,
        hint: `لغو: ٪${faDigits(props.stats.cancellation_rate)}`,
    },
    {
        key: 'ticket',
        label: 'میانگین سند',
        icon: TrendingUp,
        value: props.stats.avg_ticket !== null ? faPrice(props.stats.avg_ticket) : '—',
        delta: null,
        hint: 'درآمد تقسیم بر نوبت‌های انجام‌شده',
    },
    {
        key: 'customers',
        label: 'مشتریان یکتا',
        icon: Users,
        value: faDigits(props.stats.unique_customers),
        delta: null,
        hint: 'بر اساس شماره تماس',
    },
    {
        key: 'rating',
        label: 'میانگین امتیاز',
        icon: Star,
        value: props.stats.avg_rating !== null ? `${faDigits(props.stats.avg_rating)} از ۵` : '—',
        delta: null,
        hint: 'نظرات تأییدشده در بازه',
    },
]);
</script>

<template>
    <Head title="تحلیل" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold">تحلیل و آمار</h1>
                    <p class="text-sm text-muted-foreground">تصویر کلان عملکرد کسب‌وکار در {{ rangeLabel }} — با مقایسه‌ی دوره‌ی قبل</p>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        v-for="range in RANGES"
                        :key="range"
                        size="sm"
                        :variant="range === props.days ? 'default' : 'outline'"
                        @click="pickRange(range)"
                    >
                        {{ faDigits(range) }} روز
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
                        <CardTitle class="text-base">روند نوبت‌های روزانه</CardTitle>
                        <CardDescription>تعداد نوبت‌های ثبت‌شده در هر روز از بازه</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <BarSeries :points="bookingPoints" :compact="props.days > 7" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">روند درآمد روزانه</CardTitle>
                        <CardDescription>درآمد نوبت‌های انجام‌شده، پس از کسر تخفیف</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <BarSeries :points="revenuePoints" :compact="props.days > 7" :value-formatter="revenueFormatter" />
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">ساعت‌های پرترافیک</CardTitle>
                        <CardDescription>شلوغ‌ترین ساعات شروع نوبت در بازه</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="peak in peak_hours" :key="peak.hour" class="flex items-center gap-3 text-sm">
                            <span class="w-14 shrink-0 font-medium">{{ hourLabel(peak.hour) }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-primary/80" :style="{ width: `${(peak.count / peakMax) * 100}%` }" />
                            </div>
                            <span class="w-20 shrink-0 text-left text-xs text-muted-foreground"> {{ faDigits(peak.count) }} نوبت </span>
                        </div>
                        <p v-if="peak_hours.length === 0" class="text-sm text-muted-foreground">در این بازه نوبتی ثبت نشده است.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">تفکیک وضعیت</CardTitle>
                        <CardDescription>ترکیب وضعیت نوبت‌ها در بازه</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="row in by_status" :key="row.status" class="flex items-center justify-between text-sm">
                            <StatusBadge :status="row.status" />
                            <span class="font-semibold">{{ faDigits(row.count) }}</span>
                        </div>
                        <p v-if="by_status.length === 0" class="text-sm text-muted-foreground">در این بازه نوبتی ثبت نشده است.</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">نقشه‌ی حرارتی شلوغی</CardTitle>
                        <CardDescription>تراکم نوبت‌ها بر اساس روز هفته و ساعت شروع</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="heatmap.length > 0" class="overflow-x-auto">
                            <table class="w-full min-w-96 border-separate border-spacing-1 text-center text-[11px]">
                                <thead>
                                    <tr>
                                        <th class="w-14"></th>
                                        <th v-for="hour in heatmapHours" :key="hour" class="font-medium text-muted-foreground">
                                            {{ faDigits(hour) }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(weekdayName, weekday) in WEEKDAYS" :key="weekday">
                                        <td class="text-right text-xs font-medium text-muted-foreground">{{ weekdayName }}</td>
                                        <td v-for="hour in heatmapHours" :key="hour">
                                            <div
                                                class="mx-auto flex h-7 w-full min-w-7 items-center justify-center rounded-md"
                                                :style="heatStyle(weekday, hour)"
                                            >
                                                <span v-if="heatCount(weekday, hour)">{{ faDigits(heatCount(weekday, hour)) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">داده‌ای برای نمایش وجود ندارد.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">خدمات پرسود</CardTitle>
                        <CardDescription>پنج خدمت با بیشترین درآمد در بازه</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="service in top_services" :key="service.name" class="flex items-center justify-between text-sm">
                            <div>
                                <span class="font-medium">{{ service.name }}</span>
                                <span class="block text-xs text-muted-foreground">
                                    {{ faDigits(service.count) }} نوبت • {{ faPrice(service.revenue) }}
                                </span>
                            </div>
                            <span class="rounded-md bg-muted px-2 py-1 text-xs font-semibold">
                                {{ faPrice(service.revenue) }}
                            </span>
                        </div>
                        <p v-if="top_services.length === 0" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Hourglass class="size-4" />
                            آماری برای نمایش وجود ندارد.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
