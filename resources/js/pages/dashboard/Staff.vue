<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, StaffItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

defineProps<{
    business: { id: number; name: string };
    staff: StaffItem[];
}>();

const { t } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.staff.title'), href: '/staff' },
]);

const showDialog = ref(false);
const editingStaff = ref<StaffItem | null>(null);

const form = reactive({
    name: '',
    specialty: '',
    is_active: true,
});

function openCreate(): void {
    editingStaff.value = null;
    form.name = '';
    form.specialty = '';
    form.is_active = true;
    showDialog.value = true;
}

function openEdit(member: StaffItem): void {
    editingStaff.value = member;
    form.name = member.name;
    form.specialty = member.specialty ?? '';
    form.is_active = member.is_active ?? true;
    showDialog.value = true;
}

function save(): void {
    if (editingStaff.value) {
        router.put(route('staff.update', editingStaff.value.id), form, {
            preserveScroll: true,
            onSuccess: () => (showDialog.value = false),
        });
    } else {
        router.post(route('staff.store'), form, {
            preserveScroll: true,
            onSuccess: () => (showDialog.value = false),
        });
    }
}

function destroy(member: StaffItem): void {
    if (!confirm(t('panel.common.confirmDeleteName', { name: member.name }))) return;

    router.delete(route('staff.destroy', member.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('panel.staff.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold">{{ t('panel.staff.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ business.name }}</p>
                </div>

                <Button @click="openCreate">
                    <Plus class="ml-2 size-4" />
                    {{ t('panel.staff.new') }}
                </Button>
            </div>

            <div class="overflow-x-auto rounded-xl border bg-background">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-right text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('panel.staff.thName') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.staff.thSpecialty') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.staff.thAccount') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.common.status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="member in staff" :key="member.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ member.name }}</td>
                            <td class="px-4 py-3">{{ member.specialty ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <Badge v-if="member.has_account" variant="secondary">{{ t('panel.common.has') }}</Badge>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="member.is_active ? 'secondary' : 'outline'">
                                    {{ member.is_active ? t('panel.common.active') : t('panel.common.inactive') }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <Button variant="ghost" size="icon" @click="openEdit(member)">
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="destroy(member)">
                                        <Trash2 class="size-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="staff.length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">{{ t('panel.staff.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Dialog v-model:open="showDialog">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{{ editingStaff ? t('panel.staff.editTitle') : t('panel.staff.newTitle') }}</DialogTitle>
                        <DialogDescription>{{ t('panel.staff.desc') }}</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">{{ t('panel.staff.name') }}</Label>
                            <Input id="name" v-model="form.name" :placeholder="t('panel.staff.namePlaceholder')" />
                        </div>

                        <div class="space-y-2">
                            <Label for="specialty">{{ t('panel.staff.specialty') }}</Label>
                            <Input id="specialty" v-model="form.specialty" :placeholder="t('panel.staff.specialtyPlaceholder')" />
                        </div>

                        <div class="flex items-center justify-between">
                            <Label for="active">{{ t('panel.staff.isActive') }}</Label>
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
