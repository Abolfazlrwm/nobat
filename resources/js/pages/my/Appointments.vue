<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarPlus, CreditCard, ExternalLink, FileDown, RefreshCw, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface SeriesOccurrence {
    code: string;
    starts_at: string;
    status: string;
    status_label: string;
}

interface MyAppointment {
    code: string;
    starts_at: string;
    ends_at: string;
    status: string;
    status_label: string;
    cancellable: boolean;
    business: { name: string; slug: string };
    service_name: string;
    staff_name?: string | null;
    invoice_pdf_url?: string | null;
    series_token?: string | null;
    series: SeriesOccurrence[];
}

const props = defineProps<{
    upcoming: MyAppointment[];
    past: MyAppointment[];
}>();

const { t, dateTime } = useLocale();

const cancellingCode = ref('');

const cancel = (item: MyAppointment): void => {
    if (! window.confirm(t('my.confirmCancel'))) return;

    cancellingCode.value = item.code;
    router.post(route('bookings.guest_cancel', item.code), {}, {
        onFinish: () => {
            cancellingCode.value = '';
        },
    });
};
</script>

<template>
    <PublicLayout>
        <Head :title="t('my.pageTitle')" />

        <section class="mx-auto w-full max-w-4xl px-4 py-10">
            <h1 class="text-2xl font-bold tracking-tight">{{ t('my.title') }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('my.desc') }}</p>

            <FlashMessages class="mt-4" />

            <h2 class="mt-8 text-lg font-semibold">{{ t('my.upcoming') }}</h2>
            <div v-if="upcoming.length" class="mt-3 grid gap-3">
                <Card v-for="item in upcoming" :key="item.code" class="py-0">
                    <CardContent class="py-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-medium">
                                    <Link :href="route('businesses.show', item.business.slug)" class="hover:underline">
                                        {{ item.business.name }}
                                    </Link>
                                    — {{ item.service_name }}
                                </div>
                                <div class="mt-1 text-sm text-muted-foreground" dir="ltr">
                                    {{ dateTime(item.starts_at) }}
                                </div>
                                <div v-if="item.staff_name" class="text-sm text-muted-foreground">
                                    {{ item.staff_name }}
                                </div>
                            </div>
                            <Badge variant="secondary">{{ item.status_label }}</Badge>
                        </div>

                        <div
                            v-if="item.series_token && item.series.length"
                            class="mt-3 rounded-lg border border-dashed border-border bg-muted/30 p-3 text-sm"
                        >
                            <span class="font-medium">{{ t('my.series') }}</span>
                            <span
                                v-for="sibling in item.series"
                                :key="sibling.code"
                                class="ml-2 inline-block text-muted-foreground"
                                dir="ltr"
                            >
                                {{ dateTime(sibling.starts_at) }} · {{ sibling.status_label }}
                            </span>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <Button v-if="item.status === 'pending_payment'" as-child>
                                <a :href="route('payments.initiate', item.code)">
                                    <CreditCard class="h-4 w-4" />
                                    {{ t('my.pay') }}
                                </a>
                            </Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('bookings.show', item.code)">
                                    <ExternalLink class="h-4 w-4" />
                                    {{ t('my.track') }}
                                </Link>
                            </Button>
                            <Button variant="outline" as-child>
                                <a :href="route('bookings.ics', item.code)">
                                    <CalendarPlus class="h-4 w-4" />
                                    {{ t('my.ics') }}
                                </a>
                            </Button>
                            <Button v-if="item.invoice_pdf_url" variant="outline" as-child>
                                <a :href="item.invoice_pdf_url" target="_blank" rel="noopener">
                                    <FileDown class="h-4 w-4" />
                                    {{ t('my.invoicePdf') }}
                                </a>
                            </Button>
                            <Button
                                v-if="item.cancellable"
                                variant="outline"
                                class="text-red-600"
                                :disabled="cancellingCode === item.code"
                                @click="cancel(item)"
                            >
                                <XCircle class="h-4 w-4" />
                                {{ cancellingCode === item.code ? t('my.cancelling') : t('my.cancel') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
            <p v-else class="mt-3 text-sm text-muted-foreground">
                {{ t('my.empty') }}
                <Link :href="route('home')" class="underline">{{ t('my.emptyLink') }}</Link>
            </p>

            <template v-if="past.length">
                <h2 class="mt-10 text-lg font-semibold">{{ t('my.past') }}</h2>
                <div class="mt-3 grid gap-2">
                    <Card v-for="item in past" :key="item.code" class="py-0">
                        <CardContent class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div class="text-sm">
                                <span class="font-medium">{{ item.business.name }}</span>
                                — {{ item.service_name }}
                                <span class="text-muted-foreground" dir="ltr"> · {{ dateTime(item.starts_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge variant="outline">{{ item.status_label }}</Badge>
                                <a
                                    v-if="item.invoice_pdf_url"
                                    :href="item.invoice_pdf_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-sm text-muted-foreground underline"
                                >
                                    {{ t('my.invoicePdf') }}
                                </a>
                                <Link
                                    :href="route('bookings.show', item.code)"
                                    class="text-sm text-muted-foreground underline"
                                >
                                    {{ t('my.track') }}
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </template>

            <p class="mt-8 flex items-center gap-1.5 text-xs text-muted-foreground">
                <RefreshCw class="h-3 w-3" />
                {{ t('my.desc') }}
            </p>
        </section>
    </PublicLayout>
</template>
