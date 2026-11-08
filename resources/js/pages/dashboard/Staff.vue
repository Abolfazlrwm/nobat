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
import { BreadcrumbItem, StaffItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

defineProps<{
    business: { id: number; name: string };
    staff: StaffItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'پرسنل', href: '/staff' },
];

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
    if (!confirm(`حذف «${member.name}» مطمئن هستید؟`)) return;

    router.delete(route('staff.destroy', member.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="پرسنل" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold">پرسنل</h1>
                    <p class="text-sm text-muted-foreground">{{ business.name }}</p>
                </div>

                <Button @click="openCreate">
                    <Plus class="ml-2 size-4" />
                    همکار جدید
                </Button>
            </div>

            <div class="overflow-x-auto rounded-xl border bg-background">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-right text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">نام</th>
                            <th class="px-4 py-3 font-medium">تخصص</th>
                            <th class="px-4 py-3 font-medium">حساب کاربری</th>
                            <th class="px-4 py-3 font-medium">وضعیت</th>
                            <th class="px-4 py-3 font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="member in staff" :key="member.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-medium">{{ member.name }}</td>
                            <td class="px-4 py-3">{{ member.specialty ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <Badge v-if="member.has_account" variant="secondary">دارد</Badge>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="member.is_active ? 'secondary' : 'outline'">
                                    {{ member.is_active ? 'فعال' : 'غیرفعال' }}
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
                            <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">
                                هنوز همکاری اضافه نشده است.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Dialog v-model:open="showDialog">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{{ editingStaff ? 'ویرایش همکار' : 'همکار جدید' }}</DialogTitle>
                        <DialogDescription>
                            همکاران در ویزارد رزرو قابل انتخاب هستند و می‌توانند نوبت‌های خود را در داشبورد ببینند.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">نام</Label>
                            <Input id="name" v-model="form.name" placeholder="مثلاً دکتر سارا محمدی" />
                        </div>

                        <div class="space-y-2">
                            <Label for="specialty">تخصص</Label>
                            <Input id="specialty" v-model="form.specialty" placeholder="مثلاً پزشک عمومی" />
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
