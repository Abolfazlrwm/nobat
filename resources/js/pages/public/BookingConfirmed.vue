<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { faDateTime, faDigits, faPrice } from '@/lib/persian';
import { BookingConfirmation } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { BadgeCheck, CalendarPlus, CheckCircle2, Clock3, CreditCard, ExternalLink, Star } from 'lucide-vue-next';

const props = defineProps<{
    appointment: BookingConfirmation;
}>();

const reviewForm = useForm({
    code: props.appointment.code,
    rating: 0,
    comment: '',
});
</script>

<template>
    <Head title="نوبت شما" />

    <PublicLayout>
        <section class="mx-auto max-w-xl px-4 py-12">
            <FlashMessages />
            <Card>
                <CardHeader class="items-center text-center">
                    <BadgeCheck v-if="appointment.status === 'confirmed'" class="mx-auto size-12 text-emerald-500" />
                    <Clock3 v-else-if="appointment.status === 'pending_payment'" class="mx-auto size-12 text-amber-500" />
                    <CheckCircle2 v-else class="mx-auto size-12 text-emerald-500" />

                    <CardTitle v-if="appointment.status === 'pending_payment'" class="mt-2 text-xl"> نوبت ثبت شد؛ فقط پرداخت مانده </CardTitle>
                    <CardTitle v-else class="mt-2 text-xl">نوبت شما با موفقیت ثبت شد</CardTitle>
                    <CardDescription> جزئیات نوبت را در ادامه ببینید؛ کد پیگیری را نگه دارید. </CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- unpaid booking: payment call-to-action -->
                    <div
                        v-if="appointment.status === 'pending_payment'"
                        class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10"
                    >
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                            برای قطعی‌شدن نوبت، تا
                            {{ appointment.payment_expires_at ? faDateTime(appointment.payment_expires_at) : '۱۵ دقیقه دیگر' }}
                            پرداخت آنلاین را انجام دهید. پس از این زمان، اسلات آزاد می‌شود.
                        </p>
                        <Button as-child class="mt-3 w-full gap-2">
                            <a :href="route('payments.initiate', appointment.code)">
                                <CreditCard class="size-4" />
                                پرداخت آنلاین ({{ faPrice(appointment.payment?.amount ?? appointment.service.price) }})
                            </a>
                        </Button>
                    </div>

                    <!-- paid booking: gateway receipt -->
                    <div
                        v-if="appointment.payment?.status === 'paid'"
                        class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm dark:border-emerald-500/30 dark:bg-emerald-500/10"
                    >
                        <span class="font-semibold text-emerald-800 dark:text-emerald-300">
                            پرداخت‌شده — شماره تراکنش
                            <span dir="ltr" class="font-mono">{{ faDigits(appointment.payment.ref_id ?? '—') }}</span>
                        </span>
                        <a
                            v-if="appointment.payment.invoice_number"
                            :href="route('invoices.show', appointment.payment.invoice_number)"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-1 text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-300"
                        >
                            فاکتور
                            <ExternalLink class="size-3.5" />
                        </a>
                    </div>

                    <div class="rounded-xl border border-dashed p-4 text-center">
                        <div class="text-xs text-muted-foreground">کد پیگیری</div>
                        <div class="mt-1 select-all font-mono text-lg font-bold" dir="ltr">
                            {{ faDigits(appointment.code) }}
                        </div>
                    </div>

                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">کسب‌وکار</dt>
                            <dd class="font-medium">{{ appointment.business.name }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">خدمت</dt>
                            <dd class="font-medium">{{ appointment.service.name }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">پرسنل</dt>
                            <dd class="font-medium">{{ appointment.staff.name }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">زمان</dt>
                            <dd class="font-medium">{{ faDateTime(appointment.starts_at) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">مبلغ</dt>
                            <dd class="font-medium">{{ faPrice(appointment.service.price) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">وضعیت</dt>
                            <dd class="font-medium">{{ appointment.status_label }}</dd>
                        </div>
                    </dl>

                    <!-- completed booking: rate the experience -->
                    <div v-if="appointment.status === 'completed' && !appointment.has_review" class="rounded-xl border bg-muted/40 p-4">
                        <p class="text-sm font-semibold">نوبت شما انجام شد — تجربه‌ات چطور بود؟</p>
                        <div class="mt-2 flex gap-1">
                            <button
                                v-for="i in 5"
                                :key="i"
                                type="button"
                                :aria-label="`${i} ستاره`"
                                class="rounded-md p-1 transition-transform hover:scale-110"
                                @click="reviewForm.rating = i"
                            >
                                <Star class="size-6" :class="i <= reviewForm.rating ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/40'" />
                            </button>
                        </div>
                        <Textarea v-model="reviewForm.comment" :rows="2" class="mt-3" placeholder="متن نظر (اختیاری)" />
                        <InputError :message="reviewForm.errors.rating || reviewForm.errors.code" class="mt-2" />
                        <Button
                            class="mt-3 w-full gap-2"
                            :disabled="reviewForm.processing"
                            @click="reviewForm.post(route('reviews.store', appointment.business.slug))"
                        >
                            <Star class="size-4" />
                            ثبت امتیاز
                        </Button>
                    </div>

                    <div
                        v-else-if="appointment.review"
                        class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-500/30 dark:bg-amber-500/10"
                    >
                        <span class="font-semibold text-amber-800 dark:text-amber-300">امتیاز شما ثبت شد — ممنون!</span>
                        <span class="flex gap-0.5">
                            <Star
                                v-for="i in 5"
                                :key="i"
                                class="size-4"
                                :class="i <= (appointment.review?.rating ?? 0) ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/30'"
                            />
                        </span>
                    </div>

                    <div class="flex gap-2">
                        <Button as-child class="flex-1">
                            <Link :href="route('home')">صفحه اصلی</Link>
                        </Button>
                        <Button as-child variant="outline" class="flex-1 gap-1">
                            <a :href="route('bookings.ics', appointment.code)" download>
                                <CalendarPlus class="size-4" />
                                افزودن به تقویم
                            </a>
                        </Button>
                        <Button as-child variant="outline" class="flex-1">
                            <Link :href="`/b/${appointment.business.slug}`">رزرو نوبت جدید</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </section>
    </PublicLayout>
</template>
