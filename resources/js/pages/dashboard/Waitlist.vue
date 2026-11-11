<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDate, faDigits } from '@/lib/persian';
import { BreadcrumbItem, WaitlistItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { BellRing, Ban, Trash2, Hourglass } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    business: { id: number; name: string };
    entries: WaitlistItem[];
    counts: { waiting: number; notified: number; total: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'لیست انتظار', href: '/waitlist' },
];

const filter = ref<'all' | 'waiting' | 'notified' | 'closed'>('all');

const filtered = computed(() => {
    if (filter.value === 'all') return props.entries;
    if (filter.value === 'waiting') return props.entries.filter((e) => e.status === 'waiting');
    if (filter.value === 'notified') return props.entries.filter((e) => e.status === 'notified');

    return props.entries.filter((e) => e.status === 'booked' || e.status === 'cancelled');
});

const tabs: { key: 'all' | 'waiting' | 'notified' | 'closed'; label: string }[] = [
    { key: 'waiting', label: `در انتظار (${faDigits(props.counts.waiting)})` },
    { key: 'notified', label: `مطلع شده (${faDigits(props.counts.notified)})` },
    { key: 'closed', label: 'بسته‌شده' },
    { key: 'all', label: 'همه' },
];

function notify(entry: WaitlistItem): void {
    router.patch(route('waitlist.notify', entry.id), {}, { preserveScroll: true });
}

function cancel(entry: WaitlistItem): void {
    router.patch(route('waitlist.cancel', entry.id), {}, { preserveScroll: true });
}

function destroy(entry: WaitlistItem): void {
    if (!confirm(`حذف ثبت‌نام «${entry.customer_name}» مطمئن هستید؟`)) return;

    router.delete(route('waitlist.destroy', entry.id), { preserveScroll: true });
}

const statusVariant = (status: string): 'secondary' | 'outline' | 'default' =>
    status === 'waiting' ? 'default' : status === 'notified' ? 'secondary' : 'outline';
</script>

<template>
    <Head title="لیست انتظار" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div>
                <h1 class="flex items-center gap-2 text-xl font-bold">
                    <Hourglass class="size-5 text-primary" />
                    لیست انتظار
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ business.name }} — مشتری‌هایی که برای روزهای پرِ رزرو ثبت‌نام کرده‌اند.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    :class="[
                        'rounded-full border px-4 py-1.5 text-sm transition-colors',
                        filter === tab.key
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-accent',
                    ]"
                    @click="filter = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div class="space-y-3">
                <div
                    v-for="entry in filtered"
                    :key="entry.id"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-background p-4"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ entry.customer_name }}</span>
                            <span class="font-mono text-sm text-muted-foreground" dir="ltr">{{ entry.customer_phone }}</span>
                            <Badge :variant="statusVariant(entry.status)">{{ entry.status_label }}</Badge>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                            <span>{{ faDate(entry.desired_date) }}</span>
                            <span v-if="entry.service">{{ entry.service.name }}</span>
                            <span v-if="entry.staff">— {{ entry.staff.name }}</span>
                            <span v-if="entry.notified_at">(مطلع‌سازی: {{ faDate(entry.notified_at) }})</span>
                            <span v-if="entry.note">{{ entry.note }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <Button v-if="entry.status === 'waiting'" variant="outline" size="sm" class="gap-2" @click="notify(entry)">
                            <BellRing class="size-4" />
                            مطلع شد
                        </Button>
                        <Button
                            v-if="entry.status === 'waiting' || entry.status === 'notified'"
                            variant="ghost"
                            size="icon"
                            title="لغو"
                            @click="cancel(entry)"
                        >
                            <Ban class="size-4 text-amber-600" />
                        </Button>
                        <Button variant="ghost" size="icon" @click="destroy(entry)">
                            <Trash2 class="size-4 text-destructive" />
                        </Button>
                    </div>
                </div>

                <div v-if="filtered.length === 0" class="rounded-xl border border-dashed px-4 py-10 text-center text-muted-foreground">
                    چیزی برای نمایش نیست.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
