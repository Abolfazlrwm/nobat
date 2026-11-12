<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import WeekChart from '@/components/WeekChart.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { AppointmentItem, BreadcrumbItem, BusinessSummary, ChartPoint, StatCardData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Clock3, Hourglass, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    stats: StatCardData;
    today_appointments: AppointmentItem[];
    upcoming: AppointmentItem[];
    chart: ChartPoint[];
    businesses: BusinessSummary[];
    business_id: number | null;
}>();

const { t, num, price, dateTime, statusLabel } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [{ title: t('panel.dashboard.title'), href: '/dashboard' }]);

const statCards = computed(() => [
    { key: 'today_count', label: t('panel.dashboard.statToday'), icon: CalendarDays, value: () => num(props.stats.today_count) },
    { key: 'pending_count', label: t('panel.dashboard.statPending'), icon: Hourglass, value: () => num(props.stats.pending_count) },
    { key: 'week_count', label: t('panel.dashboard.statWeek'), icon: Clock3, value: () => num(props.stats.week_count) },
    { key: 'month_revenue', label: t('panel.dashboard.statRevenue'), icon: Wallet, value: () => price(props.stats.month_revenue) },
]);

function changeBusiness(businessId: number): void {
    router.get(route('dashboard'), { business_id: businessId }, { preserveState: true });
}
</script>

<template>
    <Head :title="t('panel.dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold">{{ t('panel.dashboard.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('panel.dashboard.subtitle') }}</p>
                </div>

                <select
                    v-if="businesses.length"
                    :value="business_id ?? ''"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    @change="changeBusiness(Number(($event.target as HTMLSelectElement).value))"
                >
                    <option value="" disabled>{{ t('panel.dashboard.chooseBusiness') }}</option>
                    <option v-for="business in businesses" :key="business.id" :value="business.id">
                        {{ business.name }}
                    </option>
                </select>
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

            <WeekChart :points="chart" />

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('panel.dashboard.todayTitle') }}</CardTitle>
                        <CardDescription>{{ t('panel.dashboard.todayDesc') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Link
                            v-for="appointment in today_appointments"
                            :key="appointment.id"
                            :href="route('appointments.index')"
                            class="flex items-center justify-between rounded-lg border p-3 text-sm transition-colors hover:bg-accent"
                        >
                            <div>
                                <div class="font-medium">{{ appointment.customer_name }}</div>
                                <div class="text-xs text-muted-foreground">{{ appointment.service_name }} — {{ appointment.staff_name ?? '' }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <StatusBadge
                                    :status="appointment.status"
                                    :label="statusLabel(appointment.status, appointment.status_label)"
                                />
                                <span class="text-xs text-muted-foreground" dir="ltr">
                                    {{ dateTime(appointment.starts_at).split(' - ')[1] }}
                                </span>
                            </div>
                        </Link>

                        <p v-if="today_appointments.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                            {{ t('panel.dashboard.emptyToday') }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('panel.dashboard.upcomingTitle') }}</CardTitle>
                        <CardDescription>{{ t('panel.dashboard.upcomingDesc') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div
                            v-for="appointment in upcoming"
                            :key="appointment.id"
                            class="flex items-center justify-between rounded-lg border p-3 text-sm"
                        >
                            <div>
                                <div class="font-medium">{{ appointment.customer_name }}</div>
                                <div class="text-xs text-muted-foreground">{{ appointment.business_name }} — {{ appointment.service_name }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Badge variant="outline" dir="ltr">{{ dateTime(appointment.starts_at) }}</Badge>
                            </div>
                        </div>

                        <p v-if="upcoming.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                            {{ t('panel.dashboard.emptyUpcoming') }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
