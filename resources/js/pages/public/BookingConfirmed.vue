<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { faDateTime, faDigits, faPrice } from '@/lib/persian';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2 } from 'lucide-vue-next';
import { BookingConfirmation } from '@/types';

const props = defineProps<{
    appointment: BookingConfirmation;
}>();
</script>

<template>
    <Head title="نوبت ثبت شد" />

    <PublicLayout>
        <section class="mx-auto max-w-xl px-4 py-12">
            <Card>
                <CardHeader class="items-center text-center">
                    <CheckCircle2 class="mx-auto size-12 text-emerald-500" />
                    <CardTitle class="mt-2 text-xl">نوبت شما با موفقیت ثبت شد</CardTitle>
                    <CardDescription>جزئیات نوبت را در ادامه ببینید؛ کد پیگیری را نگه دارید.</CardDescription>
                </CardHeader>

                <CardContent class="space-y-4">
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

                    <div class="flex gap-2">
                        <Button as-child class="flex-1">
                            <Link :href="route('home')">صفحه اصلی</Link>
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
