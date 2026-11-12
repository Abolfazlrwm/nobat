<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import WeekChart from '@/components/WeekChart.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, ChartPoint } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CalendarCheck2, CalendarX2, Download, FileSpreadsheet, ListChecks, Wallet } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

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
    from: string;
    to: string;
    stats: { total: number; completed: number; cancelled: number; revenue: number };
    by_status: StatusRow[];
    top_services: ServiceRow[];
    series: ChartPoint[];
}>();

const { t, num, price } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.reports.title'), href: '/reports' },
]);

const range = reactive({
    from: props.from,
    to: props.to,
});

function applyRange(): void {
    router.get(route('reports.index'), range, { preserveState: true });
}

const statCards = computed(() => [
    { key: 'total', label: t('panel.reports.statTotal'), icon: ListChecks, value: () => num(props.stats.total) },
    { key: 'completed', label: t('panel.reports.statCompleted'), icon: CalendarCheck2, value: () => num(props.stats.completed) },
    { key: 'cancelled', label: t('panel.reports.statCancelled'), icon: CalendarX2, value: () => num(props.stats.cancelled) },
    { key: 'revenue', label: t('panel.reports.statRevenue'), icon: Wallet, value: () => price(props.stats.revenue) },
]);
</script>

<template>
    <Head :title="t('panel.reports.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold">{{ t('panel.reports.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('panel.reports.subtitle') }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Input v-model="range.from" type="date" class="h-9 w-36" />
                    <span class="text-sm text-muted-foreground">{{ t('panel.reports.to') }}</span>
                    <Input v-model="range.to" type="date" class="h-9 w-36" />
                    <Button size="sm" variant="outline" @click="applyRange">{{ t('panel.reports.apply') }}</Button>
                    <Button size="sm" variant="secondary" class="gap-1" as-child>
                        <a :href="route('reports.export', { ...range, format: 'excel' })">
                            <FileSpreadsheet class="size-4" />
                            {{ t('panel.reports.exportExcel') }}
                        </a>
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1" as-child>
                        <a :href="route('reports.export', range)">
                            <Download class="size-4" />
                            {{ t('panel.reports.exportCsv') }}
                        </a>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card v-for="card in statCards" :key="card.key">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ card.label }}</CardTitle>
                        <component :is="card.icon" class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ card.value() }}</div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">{{ t('panel.reports.trend') }}</CardTitle>
                    <CardDescription>{{ t('panel.reports.trendDesc') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <WeekChart :points="series" />
                </CardContent>
            </Card>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('panel.reports.statusSplit') }}</CardTitle>
                        <CardDescription>{{ t('panel.reports.statusSplitDesc') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="row in by_status" :key="row.status" class="flex items-center justify-between text-sm">
                            <StatusBadge :status="row.status" />
                            <span class="font-semibold">{{ num(row.count) }}</span>
                        </div>
                        <p v-if="by_status.length === 0" class="text-sm text-muted-foreground">{{ t('panel.reports.emptyStatus') }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('panel.reports.topServices') }}</CardTitle>
                        <CardDescription>{{ t('panel.reports.topServicesDesc') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="service in top_services" :key="service.name" class="flex items-center justify-between text-sm">
                            <div>
                                <span class="font-medium">{{ service.name }}</span>
                                <span class="block text-xs text-muted-foreground">
                                    {{ num(service.count) }} {{ t('panel.reports.appointmentsUnit') }} • {{ price(service.revenue) }}
                                </span>
                            </div>
                            <span class="rounded-md bg-muted px-2 py-1 text-xs font-semibold">
                                {{ num(service.count) }}
                            </span>
                        </div>
                        <p v-if="top_services.length === 0" class="text-sm text-muted-foreground">{{ t('panel.reports.emptyStats') }}</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
