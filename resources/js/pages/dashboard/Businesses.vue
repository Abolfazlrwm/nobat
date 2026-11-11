<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDigits } from '@/lib/persian';
import { AdminBusiness, BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Power } from 'lucide-vue-next';

defineProps<{
    businesses: AdminBusiness[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'کسب‌وکارها', href: '/businesses' },
];

function toggle(business: AdminBusiness): void {
    router.patch(route('businesses.toggle', business.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="کسب‌وکارها" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div>
                <h1 class="text-xl font-bold">کسب‌وکارها</h1>
                <p class="text-sm text-muted-foreground">مدیریت همه کسب‌وکارهای ثبت‌شده در سامانه</p>
            </div>

            <div class="overflow-x-auto rounded-xl border bg-background">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-right text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">نام</th>
                            <th class="px-4 py-3 font-medium">مدیر</th>
                            <th class="px-4 py-3 font-medium">خدمات</th>
                            <th class="px-4 py-3 font-medium">پرسنل</th>
                            <th class="px-4 py-3 font-medium">نوبت‌ها</th>
                            <th class="px-4 py-3 font-medium">وضعیت</th>
                            <th class="px-4 py-3 font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="business in businesses" :key="business.id" class="border-b last:border-0">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ business.name }}</div>
                                <div class="text-xs text-muted-foreground" dir="ltr">{{ business.slug }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ business.owner_name }}</div>
                                <div class="text-xs text-muted-foreground" dir="ltr">{{ business.owner_email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ faDigits(business.services_count) }}</td>
                            <td class="px-4 py-3">{{ faDigits(business.staff_count) }}</td>
                            <td class="px-4 py-3">{{ faDigits(business.appointments_count) }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge
                                    :status="business.is_active ? 'confirmed' : 'cancelled'"
                                    :label="business.is_active ? 'فعال' : 'غیرفعال'"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <Button :variant="business.is_active ? 'outline' : 'default'" size="sm" @click="toggle(business)">
                                    <Power class="ml-1 size-4" />
                                    {{ business.is_active ? 'غیرفعال کن' : 'فعال کن' }}
                                </Button>
                            </td>
                        </tr>

                        <tr v-if="businesses.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">کسب‌وکاری ثبت نشده است.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
