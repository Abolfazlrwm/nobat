<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDigits, faPrice } from '@/lib/persian';
import { BreadcrumbItem, ServiceItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

defineProps<{
    business: { id: number; name: string };
    services: ServiceItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'خدمات', href: '/services' },
];

const showDialog = ref(false);
const editingService = ref<ServiceItem | null>(null);

const form = reactive({
    name: '',
    duration_minutes: '30',
    price: '0',
    is_active: true,
    sort_order: '0',
});

function openCreate(): void {
    editingService.value = null;
    form.name = '';
    form.duration_minutes = '30';
    form.price = '0';
    form.is_active = true;
    form.sort_order = '0';
    showDialog.value = true;
}

function openEdit(service: ServiceItem): void {
    editingService.value = service;
    form.name = service.name;
    form.duration_minutes = String(service.duration_minutes);
    form.price = String(service.price);
    form.is_active = service.is_active ?? true;
    form.sort_order = String(service.sort_order ?? 0);
    showDialog.value = true;
}

function save(): void {
    if (editingService.value) {
        router.put(route('services.update', editingService.value.id), form, {
            preserveScroll: true,
            onSuccess: () => (showDialog.value = false),
        });
    } else {
        router.post(route('services.store'), form, {
            preserveScroll: true,
            onSuccess: () => (showDialog.value = false),
        });
    }
}

function destroy(service: ServiceItem): void {
    if (!confirm(`حذف خدمت «${service.name}» مطمئن هستید؟`)) return;

    router.delete(route('services.destroy', service.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="خدمات" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold">خدمات</h1>
                    <p class="text-sm text-muted-foreground">{{ business.name }}</p>
                </div>

                <Button @click="openCreate">
                    <Plus class="ml-2 size-4" />
                    خدمت جدید
                </Button>
            </div>

            <div class="overflow-x-auto rounded-xl border bg-background">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-right text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">نام خدمت</th>
                            <th class="px-4 py-3 font-medium">مدت</th>
                            <th class="px-4 py-3 font-medium">قیمت</th>
                            <th class="px-4 py-3 font-medium">وضعیت</th>
                            <th class="px-4 py-3 font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="service in services" :key="service.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ service.name }}</td>
                            <td class="px-4 py-3">{{ faDigits(service.duration_minutes) }} دقیقه</td>
                            <td class="px-4 py-3">{{ faPrice(service.price) }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="service.is_active ? 'secondary' : 'outline'">
                                    {{ service.is_active ? 'فعال' : 'غیرفعال' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <Button variant="ghost" size="icon" @click="openEdit(service)">
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="destroy(service)">
                                        <Trash2 class="size-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="services.length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">
                                هنوز خدمتی ثبت نشده است.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Dialog v-model:open="showDialog">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{{ editingService ? 'ویرایش خدمت' : 'خدمت جدید' }}</DialogTitle>
                        <DialogDescription>
                            مدت زمان هر خدمت تعیین می‌کند بازه‌های رزرو با چه گام‌هایی ساخته شوند.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">نام خدمت</Label>
                            <Input id="name" v-model="form.name" placeholder="مثلاً ویزیت عمومی" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="duration">مدت (دقیقه)</Label>
                                <Input id="duration" v-model="form.duration_minutes" type="number" min="5" max="480" dir="ltr" />
                            </div>
                            <div class="space-y-2">
                                <Label for="price">قیمت (تومان)</Label>
                                <Input id="price" v-model="form.price" type="number" min="0" dir="ltr" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <Label for="active">فعال باشد؟</Label>
                            <input id="active" v-model="form.is_active" type="checkbox" class="size-4" />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="showDialog = false">انصراف</Button>
                        <Button @click="save">ذخیره</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
