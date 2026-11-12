<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, Paginated, WaitlistItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { BellRing, Ban, Trash2, Hourglass } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    business: { id: number; name: string };
    entries: Paginated<WaitlistItem>;
    filter: 'all' | 'waiting' | 'notified' | 'closed';
    counts: { waiting: number; notified: number; total: number };
}>();

const { t, num, dateShort, locale } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.waitlist.title'), href: '/waitlist' },
]);

// The tab filter runs server-side so pagination stays honest.
const filter = computed(() => props.filter);

function setFilter(key: 'all' | 'waiting' | 'notified' | 'closed'): void {
    router.get(route('waitlist.index'), { filter: key }, { preserveState: true });
}

const tabs = computed<{ key: 'all' | 'waiting' | 'notified' | 'closed'; label: string }[]>(() => [
    { key: 'waiting', label: t('panel.waitlist.tabWaiting', { n: num(props.counts.waiting) }) },
    { key: 'notified', label: t('panel.waitlist.tabNotified', { n: num(props.counts.notified) }) },
    { key: 'closed', label: t('panel.waitlist.tabClosed') },
    { key: 'all', label: t('panel.waitlist.tabAll') },
]);

function notify(entry: WaitlistItem): void {
    router.patch(route('waitlist.notify', entry.id), {}, { preserveScroll: true });
}

function cancel(entry: WaitlistItem): void {
    router.patch(route('waitlist.cancel', entry.id), {}, { preserveScroll: true });
}

function destroy(entry: WaitlistItem): void {
    if (!confirm(t('panel.common.confirmDeleteName', { name: entry.customer_name }))) return;

    router.delete(route('waitlist.destroy', entry.id), { preserveScroll: true });
}

const statusVariant = (status: string): 'secondary' | 'outline' | 'default' =>
    status === 'waiting' ? 'default' : status === 'notified' ? 'secondary' : 'outline';

/** Server labels are Persian; overlay the localized waitlist status. */
const waitlistLabel = (entry: WaitlistItem): string =>
    locale.value === 'fa' ? entry.status_label : t(`panel.waitlistStatus.${entry.status}`);
</script>

<template>
    <Head :title="t('panel.waitlist.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div>
                <h1 class="flex items-center gap-2 text-xl font-bold">
                    <Hourglass class="size-5 text-primary" />
                    {{ t('panel.waitlist.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('panel.waitlist.subtitle', { name: business.name }) }}
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
                    @click="setFilter(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div class="space-y-3">
                <div
                    v-for="entry in entries.data"
                    :key="entry.id"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-background p-4"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ entry.customer_name }}</span>
                            <span class="font-mono text-sm text-muted-foreground" dir="ltr">{{ entry.customer_phone }}</span>
                            <Badge :variant="statusVariant(entry.status)">{{ waitlistLabel(entry) }}</Badge>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                            <span>{{ dateShort(entry.desired_date) }}</span>
                            <span v-if="entry.service">{{ entry.service.name }}</span>
                            <span v-if="entry.staff">— {{ entry.staff.name }}</span>
                            <span v-if="entry.notified_at">{{ t('panel.waitlist.notifiedAt', { date: dateShort(entry.notified_at) }) }}</span>
                            <span v-if="entry.note">{{ entry.note }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <Button v-if="entry.status === 'waiting'" variant="outline" size="sm" class="gap-2" @click="notify(entry)">
                            <BellRing class="size-4" />
                            {{ t('panel.waitlist.notify') }}
                        </Button>
                        <Button
                            v-if="entry.status === 'waiting' || entry.status === 'notified'"
                            variant="ghost"
                            size="icon"
                            :title="t('panel.waitlist.cancel')"
                            @click="cancel(entry)"
                        >
                            <Ban class="size-4 text-amber-600" />
                        </Button>
                        <Button variant="ghost" size="icon" @click="destroy(entry)">
                            <Trash2 class="size-4 text-destructive" />
                        </Button>
                    </div>
                </div>

                <div v-if="entries.data.length === 0" class="rounded-xl border border-dashed px-4 py-10 text-center text-muted-foreground">
                    {{ t('panel.waitlist.empty') }}
                </div>

                <div v-if="entries.last_page > 1" class="flex items-center justify-center gap-1">
                    <Button
                        v-for="(link, index) in entries.links"
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
