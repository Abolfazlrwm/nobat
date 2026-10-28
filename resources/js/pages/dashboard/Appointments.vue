<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDateTime, faDigits } from '@/lib/persian';
import { AppointmentItem, BreadcrumbItem, Paginated, WeekdayOption } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { MoreHorizontal } from 'lucide-vue-next';
import { reactive } from 'vue';

const props = defineProps<{
    appointments: Paginated<AppointmentItem>;
    filters: { status: string | null; date: string | null; staff_id: number | null };
    statuses: { value: string; label: string }[];
    staff_options: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'نوبت‌ها', href: '/appointments' },
];

const filters = reactive({
    status: props.filters.status ?? '',
    date: props.filters.date ?? '',
    staff_id: props.filters.staff_id ? String(props.filters.staff_id) : '',
});

function applyFilters(): void {
    router.get(
        route('appointments.index'),
        {
            status: filters.status || undefined,
            date: filters.date || undefined,
            staff_id: filters.staff_id || undefined,
        },
        { preserveState: true },
    );
}

function setStatus(appointment: AppointmentItem, status: string): void {
    router.patch(route('appointments.status', appointment.id), { status }, { preserveScroll: true });
}
</script>

<template>
    <Head title="نوبت‌ها" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold">نوبت‌ها</h1>
                    <p class="text-sm text-muted-foreground">مدیریت و پیگیری نوبت‌های ثبت‌شده</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select v-model="filters.status" class="h-9 rounded-md border border-input bg-background px-3 text-sm" @change="applyFilters">
                        <option value="">همه وضعیت‌ها</option>
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>

                    <select v-model="filters.staff_id" class="h-9 rounded-md border border-input bg-background px-3 text-sm" @change="applyFilters">
                        <option value="">همه پرسنل</option>
                        <option v-for="staff in staff_options" :key="staff.id" :value="String(staff.id)">
                            {{ staff.name }}
                        </option>
                    </select>

                    <input
                        v-model="filters.date"
                        type="date"
                        dir="ltr"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        @change="applyFilters"
                    />
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border bg-background">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-right text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">مشتری</th>
                            <th class="px-4 py-3 font-medium">خدمت</th>
                            <th class="px-4 py-3 font-medium">پرسنل</th>
                            <th class="px-4 py-3 font-medium">زمان</th>
                            <th class="px-4 py-3 font-medium">وضعیت</th>
                            <th class="px-4 py-3 font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="appointment in appointments.data" :key="appointment.id" class="border-b last:border-0">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ appointment.customer_name }}</div>
                                <div class="text-xs text-muted-foreground" dir="ltr">{{ faDigits(appointment.customer_phone) }}</div>
                            </td>
                            <td class="px-4 py-3">{{ appointment.service_name }}</td>
                            <td class="px-4 py-3">{{ appointment.staff_name }}</td>
                            <td class="px-4 py-3 text-xs">{{ faDateTime(appointment.starts_at) }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="appointment.status" :label="appointment.status_label" />
                            </td>
                            <td class="px-4 py-3">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal class="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="start">
                                        <DropdownMenuLabel>تغییر وضعیت</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            v-for="status in statuses"
                                            :key="status.value"
                                            :disabled="status.value === appointment.status"
                                            @click="setStatus(appointment, status.value)"
                                        >
                                            {{ status.label }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>

                        <tr v-if="appointments.data.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
                                نوبتی با این فیلترها پیدا نشد.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="appointments.last_page > 1" class="flex items-center justify-center gap-1">
                <Button
                    v-for="(link, index) in appointments.links"
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
    </AppLayout>
</template>
