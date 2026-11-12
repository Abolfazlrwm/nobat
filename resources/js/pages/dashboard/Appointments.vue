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
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { AppointmentItem, BreadcrumbItem, Paginated } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { MoreHorizontal } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

const props = defineProps<{
    appointments: Paginated<AppointmentItem>;
    filters: { status: string | null; date: string | null; staff_id: number | null };
    statuses: { value: string; label: string }[];
    staff_options: { id: number; name: string }[];
}>();

const { t, num, dateTime, statusLabel } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.appointments.title'), href: '/appointments' },
]);

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
    <Head :title="t('panel.appointments.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold">{{ t('panel.appointments.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('panel.appointments.subtitle') }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select v-model="filters.status" class="h-9 rounded-md border border-input bg-background px-3 text-sm" @change="applyFilters">
                        <option value="">{{ t('panel.appointments.allStatuses') }}</option>
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                            {{ statusLabel(status.value, status.label) }}
                        </option>
                    </select>

                    <select v-model="filters.staff_id" class="h-9 rounded-md border border-input bg-background px-3 text-sm" @change="applyFilters">
                        <option value="">{{ t('panel.appointments.allStaff') }}</option>
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
                            <th class="px-4 py-3 font-medium">{{ t('panel.appointments.customer') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.appointments.service') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.appointments.staff') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.appointments.time') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.appointments.status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.appointments.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="appointment in appointments.data" :key="appointment.id" class="border-b last:border-0">
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ appointment.customer_name }}</div>
                                <div class="text-xs text-muted-foreground" dir="ltr">{{ num(appointment.customer_phone) }}</div>
                            </td>
                            <td class="px-4 py-3">{{ appointment.service_name }}</td>
                            <td class="px-4 py-3">{{ appointment.staff_name }}</td>
                            <td class="px-4 py-3 text-xs" dir="ltr">{{ dateTime(appointment.starts_at) }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge
                                    :status="appointment.status"
                                    :label="statusLabel(appointment.status, appointment.status_label)"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal class="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="start">
                                        <DropdownMenuLabel>{{ t('panel.appointments.changeStatus') }}</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            v-for="status in statuses"
                                            :key="status.value"
                                            :disabled="status.value === appointment.status"
                                            @click="setStatus(appointment, status.value)"
                                        >
                                            {{ statusLabel(status.value, status.label) }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>

                        <tr v-if="appointments.data.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">{{ t('panel.appointments.empty') }}</td>
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
