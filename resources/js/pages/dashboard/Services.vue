<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, ServiceItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

defineProps<{
    business: { id: number; name: string };
    services: ServiceItem[];
}>();

const { t, num, price } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.services.title'), href: '/services' },
]);

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
    if (!confirm(t('panel.common.confirmDelete', { name: service.name }))) return;

    router.delete(route('services.destroy', service.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('panel.services.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold">{{ t('panel.services.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ business.name }}</p>
                </div>

                <Button @click="openCreate">
                    <Plus class="ml-2 size-4" />
                    {{ t('panel.services.new') }}
                </Button>
            </div>

            <div class="overflow-x-auto rounded-xl border bg-background">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-right text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('panel.services.thName') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.services.thDuration') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.services.thPrice') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.common.status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="service in services" :key="service.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ service.name }}</td>
                            <td class="px-4 py-3">{{ t('panel.common.minutes', { n: num(service.duration_minutes) }) }}</td>
                            <td class="px-4 py-3">{{ price(service.price) }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="service.is_active ? 'secondary' : 'outline'">
                                    {{ service.is_active ? t('panel.common.active') : t('panel.common.inactive') }}
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
                            <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">{{ t('panel.services.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Dialog v-model:open="showDialog">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{{ editingService ? t('panel.services.editTitle') : t('panel.services.newTitle') }}</DialogTitle>
                        <DialogDescription>{{ t('panel.services.desc') }}</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">{{ t('panel.services.name') }}</Label>
                            <Input id="name" v-model="form.name" :placeholder="t('panel.services.namePlaceholder')" />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="duration">{{ t('panel.services.duration') }}</Label>
                                <Input id="duration" v-model="form.duration_minutes" type="number" min="5" max="480" dir="ltr" />
                            </div>
                            <div class="space-y-2">
                                <Label for="price">{{ t('panel.services.price') }}</Label>
                                <Input id="price" v-model="form.price" type="number" min="0" dir="ltr" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <Label for="active">{{ t('panel.services.isActive') }}</Label>
                            <input id="active" v-model="form.is_active" type="checkbox" class="size-4" />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="showDialog = false">{{ t('panel.common.cancel') }}</Button>
                        <Button @click="save">{{ t('panel.common.save') }}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
