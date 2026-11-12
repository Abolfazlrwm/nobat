<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDate, faDigits, faPrice } from '@/lib/persian';
import { BreadcrumbItem, CustomerSummary } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Search, UserCheck, UsersRound, Wallet } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    business: { id: number; name: string };
    customers: CustomerSummary[];
    stats: { total: number; active: number; revenue: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'مشتری‌ها', href: '/customers' },
];

const query = ref('');

const filtered = computed(() => {
    const q = query.value.trim();
    if (!q) return props.customers;

    return props.customers.filter((c) => c.name.includes(q) || c.phone.includes(q));
});

const statCards = [
    { key: 'total', label: 'مشتری‌ها', icon: UsersRound, value: () => faDigits(props.stats.total) },
    { key: 'active', label: 'مشتری فعال', icon: UserCheck, value: () => faDigits(props.stats.active) },
    { key: 'revenue', label: 'درآمد کل', icon: Wallet, value: () => faPrice(props.stats.revenue) },
];

const noShowVariant = (rate: number): 'destructive' | 'secondary' | 'outline' =>
    rate >= 50 ? 'destructive' : rate > 0 ? 'secondary' : 'outline';
</script>

<template>
    <Head title="مشتری‌ها" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <div>
                <h1 class="flex items-center gap-2 text-xl font-bold">
                    <UsersRound class="size-5 text-primary" />
                    مشتری‌ها
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ business.name }} — پروفایل مشتری‌ها بر پایه‌ی تاریخچه‌ی نوبت‌ها: مبلغ مصرف، نرخ عدم حضور و آخرین مراجعه.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
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

            <div class="relative max-w-sm">
                <Search class="absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="query" placeholder="جستجو بر اساس نام یا شماره…" class="pr-9" />
            </div>

            <div class="space-y-3">
                <div
                    v-for="customer in filtered"
                    :key="customer.phone"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-background p-4"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ customer.name }}</span>
                            <span class="font-mono text-sm text-muted-foreground" dir="ltr">{{ customer.phone }}</span>
                            <Badge variant="outline">{{ faDigits(customer.completed_count) }} انجام‌شده</Badge>
                            <Badge v-if="customer.no_show_count > 0" :variant="noShowVariant(customer.no_show_rate)">
                                عدم حضور {{ faDigits(customer.no_show_rate) }}٪
                            </Badge>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                            <span>{{ faDigits(customer.appointments_count) }} نوبت • {{ faPrice(customer.total_spent) }}</span>
                            <span v-if="customer.last_visit_at">آخرین مراجعه: {{ faDate(customer.last_visit_at) }}</span>
                        </div>
                    </div>

                    <Button variant="outline" size="sm" as-child>
                        <Link :href="route('customers.show', { phone: customer.phone })">پروفایل</Link>
                    </Button>
                </div>

                <div
                    v-if="filtered.length === 0"
                    class="rounded-xl border border-dashed px-4 py-10 text-center text-muted-foreground"
                >
                    {{ query ? 'مشتری‌ای با این جستجو پیدا نشد.' : 'هنوز نوبتی ثبت نشده تا مشتری‌ای شکل بگیرد.' }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
