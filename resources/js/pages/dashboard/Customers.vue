<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, CustomerSummary, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, UserCheck, UsersRound, Wallet } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    business: { id: number; name: string };
    customers: Paginated<CustomerSummary>;
    search?: string;
    stats: { total: number; active: number; revenue: number };
}>();

const { t, num, price, dateShort } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.customers.title'), href: '/customers' },
]);

// Search runs server-side (q param) so it covers every page of the book.
const query = ref(props.search ?? '');

function search(): void {
    router.get(route('customers.index'), { q: query.value.trim() }, { preserveState: true });
}

const statCards = computed(() => [
    { key: 'total', label: t('panel.customers.statTotal'), icon: UsersRound, value: () => num(props.stats.total) },
    { key: 'active', label: t('panel.customers.statActive'), icon: UserCheck, value: () => num(props.stats.active) },
    { key: 'revenue', label: t('panel.customers.statRevenue'), icon: Wallet, value: () => price(props.stats.revenue) },
]);

const noShowVariant = (rate: number): 'destructive' | 'secondary' | 'outline' =>
    rate >= 50 ? 'destructive' : rate > 0 ? 'secondary' : 'outline';
</script>

<template>
    <Head :title="t('panel.customers.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <div>
                <h1 class="flex items-center gap-2 text-xl font-bold">
                    <UsersRound class="size-5 text-primary" />
                    {{ t('panel.customers.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('panel.customers.subtitle', { name: business.name }) }}
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

            <form class="relative max-w-sm" @submit.prevent="search">
                <Search class="absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="query" :placeholder="t('panel.customers.search')" class="pr-9" @keydown.enter.prevent="search" />
            </form>

            <div class="space-y-3">
                <div
                    v-for="customer in customers.data"
                    :key="customer.phone"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-background p-4"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ customer.name }}</span>
                            <span class="font-mono text-sm text-muted-foreground" dir="ltr">{{ customer.phone }}</span>
                            <Badge variant="outline">{{ t('panel.customers.completedBadge', { n: num(customer.completed_count) }) }}</Badge>
                            <Badge v-if="customer.no_show_count > 0" :variant="noShowVariant(customer.no_show_rate)">
                                {{ t('panel.customers.noShowBadge', { n: num(customer.no_show_rate) }) }}
                            </Badge>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                            <span>{{ t('panel.customers.appointmentsLine', { n: num(customer.appointments_count), price: price(customer.total_spent) }) }}</span>
                            <span v-if="customer.last_visit_at">{{ t('panel.customers.lastVisit', { date: dateShort(customer.last_visit_at) }) }}</span>
                        </div>
                    </div>

                    <Button variant="outline" size="sm" as-child>
                        <Link :href="route('customers.show', { phone: customer.phone })">{{ t('panel.customers.profile') }}</Link>
                    </Button>
                </div>

                <div
                    v-if="customers.data.length === 0"
                    class="rounded-xl border border-dashed px-4 py-10 text-center text-muted-foreground"
                >
                    {{ search ? t('panel.customers.emptySearch') : t('panel.customers.empty') }}
                </div>

                <div v-if="customers.last_page > 1" class="flex items-center justify-center gap-1">
                    <Button
                        v-for="(link, index) in customers.links"
                        :key="index"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="router.get(link.url ?? '', {}, { preserveState: true })"
                    >
                        <span v-html="link.label" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
