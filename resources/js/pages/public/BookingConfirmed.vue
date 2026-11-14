<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useLocale } from '@/composables/useLocale';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { BookingConfirmation } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BadgeCheck, CalendarPlus, CheckCircle2, Clock3, CreditCard, ExternalLink, Star, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    appointment: BookingConfirmation;
}>();

const { t, num, price, dateTime, statusLabel } = useLocale();

const reviewForm = useForm({
    code: props.appointment.code,
    rating: 0,
    comment: '',
});

const deadline = computed(() =>
    props.appointment.payment_expires_at ? dateTime(props.appointment.payment_expires_at) : t('booking.defaultDeadline'),
);

const displayStatusLabel = computed(() => statusLabel(props.appointment.status, props.appointment.status_label));

const cancellable = computed(() =>
    ['pending', 'pending_payment', 'confirmed'].includes(props.appointment.status));

const cancelForm = useForm({
    customer_phone: '',
});
</script>

<template>
    <Head :title="t('booking.title')" />

    <PublicLayout>
        <section class="mx-auto max-w-xl px-4 py-12">
            <FlashMessages />
            <Card>
                <CardHeader class="items-center text-center">
                    <BadgeCheck v-if="appointment.status === 'confirmed'" class="mx-auto size-12 text-emerald-500" />
                    <Clock3 v-else-if="appointment.status === 'pending_payment'" class="mx-auto size-12 text-amber-500" />
                    <CheckCircle2 v-else class="mx-auto size-12 text-emerald-500" />

                    <CardTitle v-if="appointment.status === 'pending_payment'" class="mt-2 text-xl">{{ t('booking.pendingTitle') }}</CardTitle>
                    <CardTitle v-else class="mt-2 text-xl">{{ t('booking.confirmedTitle') }}</CardTitle>
                    <CardDescription>{{ t('booking.subtitle') }}</CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- unpaid booking: payment call-to-action -->
                    <div
                        v-if="appointment.status === 'pending_payment'"
                        class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10"
                    >
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                            {{ t('booking.payDeadline', { deadline }) }}
                        </p>
                        <Button as-child class="mt-3 w-full gap-2">
                            <a :href="route('payments.initiate', appointment.code)">
                                <CreditCard class="size-4" />
                                {{ t('booking.payCta', { amount: price(appointment.payment?.amount ?? appointment.service.price) }) }}
                            </a>
                        </Button>
                    </div>

                    <!-- paid booking: gateway receipt -->
                    <div
                        v-if="appointment.payment?.status === 'paid'"
                        class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm dark:border-emerald-500/30 dark:bg-emerald-500/10"
                    >
                        <span class="font-semibold text-emerald-800 dark:text-emerald-300">
                            {{ t('booking.paidReceipt') }}
                            <span dir="ltr" class="font-mono">{{ num(appointment.payment.ref_id ?? '—') }}</span>
                        </span>
                        <a
                            v-if="appointment.payment.invoice_url"
                            :href="appointment.payment.invoice_url"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-1 text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-300"
                        >
                            {{ t('booking.invoice') }}
                            <ExternalLink class="size-3.5" />
                        </a>
                    </div>

                    <div class="rounded-xl border border-dashed p-4 text-center">
                        <div class="text-xs text-muted-foreground">{{ t('booking.trackingCode') }}</div>
                        <div class="mt-1 select-all font-mono text-lg font-bold" dir="ltr">
                            {{ num(appointment.code) }}
                        </div>
                    </div>

                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">{{ t('booking.business') }}</dt>
                            <dd class="font-medium">{{ appointment.business.name }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">{{ t('booking.service') }}</dt>
                            <dd class="font-medium">{{ appointment.service.name }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">{{ t('booking.staff') }}</dt>
                            <dd class="font-medium">{{ appointment.staff.name }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">{{ t('booking.time') }}</dt>
                            <dd class="font-medium">{{ dateTime(appointment.starts_at) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">{{ t('booking.amount') }}</dt>
                            <dd class="font-medium" :class="appointment.discount_amount && 'line-through opacity-70'">
                                {{ price(appointment.service.price) }}
                            </dd>
                        </div>
                        <div v-if="appointment.discount_amount" class="flex items-center justify-between">
                            <dt class="text-muted-foreground">
                                {{ t('booking.discount')
                                }}<span v-if="appointment.discount_code" class="font-mono" dir="ltr"> ({{ appointment.discount_code }})</span>
                            </dt>
                            <dd class="font-medium text-emerald-600 dark:text-emerald-400">− {{ price(appointment.discount_amount) }}</dd>
                        </div>
                        <div v-if="appointment.discount_amount" class="flex items-center justify-between">
                            <dt class="text-muted-foreground">{{ t('booking.payableAmount') }}</dt>
                            <dd class="font-semibold">
                                {{ price(Math.max(0, appointment.service.price - (appointment.discount_amount ?? 0))) }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">{{ t('booking.status') }}</dt>
                            <dd class="font-medium">{{ displayStatusLabel }}</dd>
                        </div>
                    </dl>

                    <!-- completed booking: rate the experience -->
                    <div v-if="appointment.status === 'completed' && !appointment.has_review" class="rounded-xl border bg-muted/40 p-4">
                        <p class="text-sm font-semibold">{{ t('booking.reviewPrompt') }}</p>
                        <div class="mt-2 flex gap-1">
                            <button
                                v-for="i in 5"
                                :key="i"
                                type="button"
                                :aria-label="t('booking.starsAria', { n: i })"
                                class="rounded-md p-1 transition-transform hover:scale-110"
                                @click="reviewForm.rating = i"
                            >
                                <Star class="size-6" :class="i <= reviewForm.rating ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/40'" />
                            </button>
                        </div>
                        <Textarea v-model="reviewForm.comment" :rows="2" class="mt-3" :placeholder="t('booking.commentPlaceholder')" />
                        <InputError :message="reviewForm.errors.rating || reviewForm.errors.code" class="mt-2" />
                        <Button
                            class="mt-3 w-full gap-2"
                            :disabled="reviewForm.processing"
                            @click="reviewForm.post(route('reviews.store', appointment.business.slug))"
                        >
                            <Star class="size-4" />
                            {{ t('booking.submitReview') }}
                        </Button>
                    </div>

                    <div
                        v-else-if="appointment.review"
                        class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-500/30 dark:bg-amber-500/10"
                    >
                        <span class="font-semibold text-amber-800 dark:text-amber-300">{{ t('booking.reviewThanks') }}</span>
                        <span class="flex gap-0.5">
                            <Star
                                v-for="i in 5"
                                :key="i"
                                class="size-4"
                                :class="i <= (appointment.review?.rating ?? 0) ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/30'"
                            />
                        </span>
                    </div>

                    <!-- upcoming booking: self-service cancellation -->
                    <div
                        v-if="cancellable"
                        class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/30 dark:bg-rose-500/10"
                    >
                        <p class="text-sm font-semibold text-rose-800 dark:text-rose-300">{{ t('booking.cancelTitle') }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ t('booking.cancelDesc') }}</p>
                        <div class="mt-3 grid gap-2">
                            <Input
                                id="cancel-phone"
                                v-model="cancelForm.customer_phone"
                                type="tel"
                                inputmode="tel"
                                dir="ltr"
                                :placeholder="t('booking.cancelPhonePlaceholder')"
                                autocomplete="off"
                            />
                            <InputError :message="cancelForm.errors.customer_phone" />
                            <Button
                                variant="destructive"
                                class="w-full gap-2"
                                :disabled="cancelForm.processing"
                                @click="cancelForm.post(route('bookings.guest_cancel', appointment.code))"
                            >
                                <XCircle class="size-4" />
                                {{ t('booking.cancelButton') }}
                            </Button>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Button as-child class="flex-1">
                            <Link :href="route('home')">{{ t('booking.home') }}</Link>
                        </Button>
                        <Button as-child variant="outline" class="flex-1 gap-1">
                            <a :href="route('bookings.ics', appointment.code)" download>
                                <CalendarPlus class="size-4" />
                                {{ t('booking.addToCalendar') }}
                            </a>
                        </Button>
                        <Button as-child variant="outline" class="flex-1">
                            <Link :href="`/b/${appointment.business.slug}`">{{ t('booking.newBooking') }}</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </section>
    </PublicLayout>
</template>
