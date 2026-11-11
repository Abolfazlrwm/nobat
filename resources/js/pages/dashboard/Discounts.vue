<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDigits, faPrice } from '@/lib/persian';
import { BreadcrumbItem, DiscountCodeItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, TicketPercent } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

defineProps<{
    business: { id: number; name: string };
    codes: DiscountCodeItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'کدهای تخفیف', href: '/discounts' },
];

const showDialog = ref(false);
const editingCode = ref<DiscountCodeItem | null>(null);

const form = reactive({
    code: '',
    type: 'percent',
    value: '10',
    min_amount: '',
    max_uses: '',
    per_user_limit: '',
    starts_at: '',
    expires_at: '',
    is_active: true,
});

function openCreate(): void {
    editingCode.value = null;
    form.code = '';
    form.type = 'percent';
    form.value = '10';
    form.min_amount = '';
    form.max_uses = '';
    form.per_user_limit = '';
    form.starts_at = '';
    form.expires_at = '';
    form.is_active = true;
    showDialog.value = true;
}

function openEdit(code: DiscountCodeItem): void {
    editingCode.value = code;
    form.code = code.code;
    form.type = code.type;
    form.value = String(code.value);
    form.min_amount = code.min_amount ? String(code.min_amount) : '';
    form.max_uses = code.max_uses ? String(code.max_uses) : '';
    form.per_user_limit = code.per_user_limit ? String(code.per_user_limit) : '';
    form.starts_at = code.starts_at ?? '';
    form.expires_at = code.expires_at ?? '';
    form.is_active = code.is_active ?? true;
    showDialog.value = true;
}

function save(): void {
    const payload = {
        code: form.code,
        type: form.type,
        value: Number(form.value),
        min_amount: form.min_amount === '' ? null : Number(form.min_amount),
        max_uses: form.max_uses === '' ? null : Number(form.max_uses),
        per_user_limit: form.per_user_limit === '' ? null : Number(form.per_user_limit),
        starts_at: form.starts_at === '' ? null : form.starts_at,
        expires_at: form.expires_at === '' ? null : form.expires_at,
        is_active: form.is_active,
    };

    if (editingCode.value) {
        router.put(route('discounts.update', editingCode.value.id), payload, {
            preserveScroll: true,
            onSuccess: () => (showDialog.value = false),
        });
    } else {
        router.post(route('discounts.store'), payload, {
            preserveScroll: true,
            onSuccess: () => (showDialog.value = false),
        });
    }
}

function toggle(code: DiscountCodeItem): void {
    router.patch(route('discounts.toggle', code.id), {}, { preserveScroll: true });
}

function destroy(code: DiscountCodeItem): void {
    if (!confirm(`حذف کد «${code.code}» مطمئن هستید؟`)) return;

    router.delete(route('discounts.destroy', code.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="کدهای تخفیف" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="flex items-center gap-2 text-xl font-bold">
                        <TicketPercent class="size-5 text-primary" />
                        کدهای تخفیف
                    </h1>
                    <p class="text-sm text-muted-foreground">{{ business.name }}</p>
                </div>

                <Button @click="openCreate">
                    <Plus class="ml-2 size-4" />
                    کد جدید
                </Button>
            </div>

            <div class="overflow-x-auto rounded-xl border bg-background">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-right text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">کد</th>
                            <th class="px-4 py-3 font-medium">نوع</th>
                            <th class="px-4 py-3 font-medium">مقدار</th>
                            <th class="px-4 py-3 font-medium">حداقل خرید</th>
                            <th class="px-4 py-3 font-medium">مصرف</th>
                            <th class="px-4 py-3 font-medium">مهلت</th>
                            <th class="px-4 py-3 font-medium">وضعیت</th>
                            <th class="px-4 py-3 font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="code in codes" :key="code.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-mono font-bold" dir="ltr">{{ code.code }}</td>
                            <td class="px-4 py-3">{{ code.type_label }}</td>
                            <td class="px-4 py-3" dir="ltr">{{ code.value_label }}</td>
                            <td class="px-4 py-3">{{ code.min_amount ? faPrice(code.min_amount) : '—' }}</td>
                            <td class="px-4 py-3">
                                {{ faDigits(code.used_count) }}
                                <span v-if="code.max_uses"> / {{ faDigits(code.max_uses) }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                <span v-if="code.expires_at || code.starts_at" dir="ltr">
                                    {{ code.starts_at ?? '…' }} → {{ code.expires_at ?? '…' }}
                                </span>
                                <span v-else>بدون مهلت</span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="code.is_active ? 'secondary' : 'outline'">
                                    {{ code.is_active ? 'فعال' : 'غیرفعال' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <Button variant="ghost" size="icon" @click="openEdit(code)">
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" @click="toggle(code)">
                                        {{ code.is_active ? 'غیرفعال' : 'فعال‌سازی' }}
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="destroy(code)">
                                        <Trash2 class="size-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="codes.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-muted-foreground">
                                هنوز کد تخفیفی ساخته نشده است؛ با «کد جدید» شروع کنید.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Dialog v-model:open="showDialog">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{{ editingCode ? 'ویرایش کد تخفیف' : 'کد تخفیف جدید' }}</DialogTitle>
                        <DialogDescription>
                            کد در صفحه‌ی رزرو به مشتری نشان داده می‌شود؛ سقف مصرف و مهلت را همین‌جا تعیین کنید.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="code">کد (انگلیسی)</Label>
                                <Input id="code" v-model="form.code" placeholder="WELCOME10" dir="ltr" class="font-mono" />
                            </div>
                            <div class="space-y-2">
                                <Label for="type">نوع تخفیف</Label>
                                <select
                                    id="type"
                                    v-model="form.type"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="percent">درصدی</option>
                                    <option value="fixed">مبلغ ثابت (تومان)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="value">{{ form.type === 'percent' ? 'درصد تخفیف' : 'مبلغ تخفیف (تومان)' }}</Label>
                                <Input id="value" v-model="form.value" type="number" :min="form.type === 'percent' ? 1 : 1"
                                    :max="form.type === 'percent' ? 100 : undefined" dir="ltr" />
                            </div>
                            <div class="space-y-2">
                                <Label for="min_amount">حداقل مبلغ خرید (تومان)</Label>
                                <Input id="min_amount" v-model="form.min_amount" type="number" min="0" dir="ltr" placeholder="اختیاری" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="max_uses">سقف مصرف کل</Label>
                                <Input id="max_uses" v-model="form.max_uses" type="number" min="1" dir="ltr" placeholder="اختیاری" />
                            </div>
                            <div class="space-y-2">
                                <Label for="per_user_limit">سقف برای هر مشتری</Label>
                                <Input id="per_user_limit" v-model="form.per_user_limit" type="number" min="1" dir="ltr" placeholder="اختیاری" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="starts_at">شروع اعتبار</Label>
                                <Input id="starts_at" v-model="form.starts_at" type="date" dir="ltr" />
                            </div>
                            <div class="space-y-2">
                                <Label for="expires_at">پایان اعتبار</Label>
                                <Input id="expires_at" v-model="form.expires_at" type="date" dir="ltr" />
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
