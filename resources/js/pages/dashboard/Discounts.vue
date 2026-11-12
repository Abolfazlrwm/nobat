<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, DiscountCodeItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, TicketPercent } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

defineProps<{
    business: { id: number; name: string };
    codes: DiscountCodeItem[];
}>();

const { t, num, price, isFa } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.discounts.title'), href: '/discounts' },
]);

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

/** The server sends Persian type/value labels; recompute them for the active locale. */
const typeLabel = (code: DiscountCodeItem): string =>
    isFa.value ? code.type_label : code.type === 'percent' ? t('panel.discounts.typePercent') : t('panel.discounts.typeFixed');

const valueLabel = (code: DiscountCodeItem): string =>
    isFa.value ? code.value_label : code.type === 'percent' ? `${num(code.value)}%` : price(code.value);

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
    if (!confirm(t('panel.common.confirmDeleteCode', { code: code.code }))) return;

    router.delete(route('discounts.destroy', code.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('panel.discounts.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="flex items-center gap-2 text-xl font-bold">
                        <TicketPercent class="size-5 text-primary" />
                        {{ t('panel.discounts.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">{{ business.name }}</p>
                </div>

                <Button @click="openCreate">
                    <Plus class="ml-2 size-4" />
                    {{ t('panel.discounts.new') }}
                </Button>
            </div>

            <div class="overflow-x-auto rounded-xl border bg-background">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-right text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('panel.discounts.thCode') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.discounts.thType') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.discounts.thValue') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.discounts.thMin') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.discounts.thUsed') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.discounts.thDeadline') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.common.status') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('panel.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="code in codes" :key="code.id" class="border-b last:border-0">
                            <td class="px-4 py-3 font-mono font-bold" dir="ltr">{{ code.code }}</td>
                            <td class="px-4 py-3">{{ typeLabel(code) }}</td>
                            <td class="px-4 py-3" dir="ltr">{{ valueLabel(code) }}</td>
                            <td class="px-4 py-3">{{ code.min_amount ? price(code.min_amount) : '—' }}</td>
                            <td class="px-4 py-3">
                                {{ num(code.used_count) }}
                                <span v-if="code.max_uses"> / {{ num(code.max_uses) }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                <span v-if="code.expires_at || code.starts_at" dir="ltr">
                                    {{ code.starts_at ?? '…' }} → {{ code.expires_at ?? '…' }}
                                </span>
                                <span v-else>{{ t('panel.discounts.noDeadline') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="code.is_active ? 'secondary' : 'outline'">
                                    {{ code.is_active ? t('panel.common.active') : t('panel.common.inactive') }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <Button variant="ghost" size="icon" @click="openEdit(code)">
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" @click="toggle(code)">
                                        {{ code.is_active ? t('panel.common.inactive') : t('panel.discounts.activate') }}
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="destroy(code)">
                                        <Trash2 class="size-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="codes.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-muted-foreground">
                                {{ t('panel.discounts.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Dialog v-model:open="showDialog">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{{ editingCode ? t('panel.discounts.editTitle') : t('panel.discounts.newTitle') }}</DialogTitle>
                        <DialogDescription>{{ t('panel.discounts.desc') }}</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="code">{{ t('panel.discounts.codeLabel') }}</Label>
                                <Input id="code" v-model="form.code" placeholder="WELCOME10" dir="ltr" class="font-mono" />
                            </div>
                            <div class="space-y-2">
                                <Label for="type">{{ t('panel.discounts.typeLabel') }}</Label>
                                <select
                                    id="type"
                                    v-model="form.type"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="percent">{{ t('panel.discounts.typePercent') }}</option>
                                    <option value="fixed">{{ t('panel.discounts.fixedOption') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="value">{{ form.type === 'percent' ? t('panel.discounts.valuePercent') : t('panel.discounts.valueFixed') }}</Label>
                                <Input id="value" v-model="form.value" type="number" :min="form.type === 'percent' ? 1 : 1"
                                    :max="form.type === 'percent' ? 100 : undefined" dir="ltr" />
                            </div>
                            <div class="space-y-2">
                                <Label for="min_amount">{{ t('panel.discounts.minAmount') }}</Label>
                                <Input id="min_amount" v-model="form.min_amount" type="number" min="0" dir="ltr" :placeholder="t('panel.common.optional')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="max_uses">{{ t('panel.discounts.maxUses') }}</Label>
                                <Input id="max_uses" v-model="form.max_uses" type="number" min="1" dir="ltr" :placeholder="t('panel.common.optional')" />
                            </div>
                            <div class="space-y-2">
                                <Label for="per_user_limit">{{ t('panel.discounts.perUser') }}</Label>
                                <Input id="per_user_limit" v-model="form.per_user_limit" type="number" min="1" dir="ltr" :placeholder="t('panel.common.optional')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <Label for="starts_at">{{ t('panel.discounts.startsAt') }}</Label>
                                <Input id="starts_at" v-model="form.starts_at" type="date" dir="ltr" />
                            </div>
                            <div class="space-y-2">
                                <Label for="expires_at">{{ t('panel.discounts.expiresAt') }}</Label>
                                <Input id="expires_at" v-model="form.expires_at" type="date" dir="ltr" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <Label for="active">{{ t('panel.discounts.isActive') }}</Label>
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
