<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, CustomerHistoryItem, CustomerSummary } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarCheck2, Hourglass, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    business: { id: number; name: string };
    customer: CustomerSummary;
    history: CustomerHistoryItem[];
}>();

const { t, num, price, dateTime, statusLabel } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.customers.title'), href: '/customers' },
    { title: props.customer.name, href: `/customers/${props.customer.phone}` },
]);

const statCards = computed(() => [
    { key: 'appointments', label: t('panel.customers.statAppointments'), icon: CalendarCheck2, value: () => num(props.customer.appointments_count) },
    { key: 'completed', label: t('panel.customers.statCompleted'), icon: CalendarCheck2, value: () => num(props.customer.completed_count) },
    { key: 'spent', label: t('panel.customers.statSpent'), icon: Wallet, value: () => price(props.customer.total_spent) },
    { key: 'no_show', label: t('panel.customers.statNoShow'), icon: Hourglass, value: () => `${num(props.customer.no_show_rate)}%` },
]);
</script>

<template>
    <Head :title="t('panel.customers.pageTitle', { name: customer.name })" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold">{{ customer.name }}</h1>
                    <p class="font-mono text-sm text-muted-foreground" dir="ltr">{{ customer.phone }}</p>
                </div>

                <Button variant="ghost" size="sm" class="gap-1" as-child>
                    <Link :href="route('customers.index')">
                        <ArrowRight class="size-4" />
                        {{ t('panel.customers.backToCustomers') }}
                    </Link>
                </Button>
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

            <div class="rounded-xl border bg-background">
                <div class="border-b px-4 py-3">
                    <h2 class="font-semibold">{{ t('panel.customers.historyTitle') }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ t('panel.customers.historyDesc', { n: num(history.length), name: business.name }) }}
                    </p>
                </div>

                <div class="divide-y">
                    <div
                        v-for="item in history"
                        :key="item.id"
                        class="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-medium" dir="ltr">{{ dateTime(item.starts_at) }}</span>
                                <StatusBadge :status="item.status" :label="statusLabel(item.status, item.status_label)" />
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                                <span v-if="item.service_name">{{ item.service_name }}</span>
                                <span v-if="item.staff_name">— {{ item.staff_name }}</span>
                                <span class="font-mono text-xs" dir="ltr">{{ item.code }}</span>
                            </div>
                        </div>

                        <span class="text-sm font-semibold">{{ price(item.price) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
