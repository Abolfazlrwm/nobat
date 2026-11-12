<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDateTime, faDigits, faPrice } from '@/lib/persian';
import { BreadcrumbItem, CustomerHistoryItem, CustomerSummary } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarCheck2, Hourglass, Wallet } from 'lucide-vue-next';

const props = defineProps<{
    business: { id: number; name: string };
    customer: CustomerSummary;
    history: CustomerHistoryItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'مشتری‌ها', href: '/customers' },
    { title: props.customer.name, href: `/customers/${props.customer.phone}` },
];

const statCards = [
    { key: 'appointments', label: 'کل نوبت‌ها', icon: CalendarCheck2, value: () => faDigits(props.customer.appointments_count) },
    { key: 'completed', label: 'انجام‌شده', icon: CalendarCheck2, value: () => faDigits(props.customer.completed_count) },
    { key: 'spent', label: 'مبلغ مصرف', icon: Wallet, value: () => faPrice(props.customer.total_spent) },
    { key: 'no_show', label: 'نرخ عدم حضور', icon: Hourglass, value: () => `${faDigits(props.customer.no_show_rate)}٪` },
];
</script>

<template>
    <Head :title="`مشتری — ${customer.name}`" />

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
                        بازگشت به مشتری‌ها
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
                    <h2 class="font-semibold">تاریخچه‌ی نوبت‌ها</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ faDigits(history.length) }} نوبت در {{ business.name }} — از جدید به قدیم.
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
                                <span class="font-medium">{{ faDateTime(item.starts_at) }}</span>
                                <StatusBadge :status="item.status" :label="item.status_label" />
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                                <span v-if="item.service_name">{{ item.service_name }}</span>
                                <span v-if="item.staff_name">— {{ item.staff_name }}</span>
                                <span class="font-mono text-xs" dir="ltr">{{ item.code }}</span>
                            </div>
                        </div>

                        <span class="text-sm font-semibold">{{ faPrice(item.price) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
