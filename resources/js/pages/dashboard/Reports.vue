<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import WeekChart from '@/components/WeekChart.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDigits, faPrice } from '@/lib/persian';
import { BreadcrumbItem, ChartPoint } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CalendarCheck2, CalendarX2, Download, ListChecks, Wallet } from 'lucide-vue-next';
import { reactive } from 'vue';

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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'گزارش‌ها', href: '/reports' },
];

const range = reactive({
    from: props.from,
    to: props.to,
});

function applyRange(): void {
    router.get(route('reports.index'), range, { preserveState: true });
}

const statCards = [
    { key: 'total', label: 'کل نوبت‌ها', icon: ListChecks, value: () => faDigits(props.stats.total) },
    { key: 'completed', label: 'انجام‌شده', icon: CalendarCheck2, value: () => faDigits(props.stats.completed) },
    { key: 'cancelled', label: 'لغوشده', icon: CalendarX2, value: () => faDigits(props.stats.cancelled) },
    { key: 'revenue', label: 'درآمد بازه', icon: Wallet, value: () => faPrice(props.stats.revenue) },
];
</script>

<template>
    <Head title="گزارش‌ها" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold">گزارش‌ها</h1>
                    <p class="text-sm text-muted-foreground">عملکرد نوبت‌ها و درآمد در بازه‌ی دلخواه — با خروجی CSV</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Input v-model="range.from" type="date" class="h-9 w-36" />
                    <span class="text-sm text-muted-foreground">تا</span>
                    <Input v-model="range.to" type="date" class="h-9 w-36" />
                    <Button size="sm" variant="outline" @click="applyRange">اعمال</Button>
                    <Button size="sm" variant="secondary" class="gap-1" as-child>
                        <a :href="route('reports.export', range)">
                            <Download class="size-4" />
                            خروجی CSV
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
                    <CardTitle class="text-base">روند نوبت‌های روزانه</CardTitle>
                    <CardDescription>تعداد نوبت‌های ثبت‌شده در هر روز از بازه</CardDescription>
                </CardHeader>
                <CardContent>
                    <WeekChart :points="series" />
                </CardContent>
            </Card>

            <div class="grid gap-6 lg:grid-cols-2">
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

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">خدمات پرتکرار</CardTitle>
                        <CardDescription>پنج خدمت با بیشترین نوبت و درآمد</CardDescription>
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
                                {{ faDigits(service.count) }}
                            </span>
                        </div>
                        <p v-if="top_services.length === 0" class="text-sm text-muted-foreground">آماری برای نمایش وجود ندارد.</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
