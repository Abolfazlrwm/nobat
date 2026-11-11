<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDateTime } from '@/lib/persian';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { KeyRound, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface TokenRow {
    id: number;
    name: string;
    last_used_at?: string | null;
    created_at?: string | null;
}

const props = defineProps<{
    issued?: string | null;
    tokens: TokenRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'کلیدهای API', href: '/api-tokens' },
];

const form = useForm({ name: '' });

function createToken(): void {
    form.post(route('api-tokens.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function revoke(token: TokenRow): void {
    router.delete(route('api-tokens.destroy', { apiToken: token.id }), { preserveScroll: true });
}

const copied = ref(false);

async function copyToken(): Promise<void> {
    if (!props.issued) return;

    await navigator.clipboard.writeText(props.issued);
    copied.value = true;
}
</script>

<template>
    <Head title="کلیدهای API" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div>
                <h1 class="text-xl font-bold">کلیدهای API</h1>
                <p class="text-sm text-muted-foreground">
                    توکن Bearer برای استفاده از API عمومی نوبت — راهنمای کامل در
                    <a class="font-medium text-primary underline" href="#" @click.prevent>docs/API.md</a>
                </p>
            </div>

            <Card v-if="issued" class="border-emerald-500/40 bg-emerald-500/5">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <KeyRound class="size-4 text-emerald-600" />
                        توکن ساخته شد — فقط همین یک بار نمایش داده می‌شود
                    </CardTitle>
                    <CardDescription>
                        این مقدار را همین حالا جایی امن ذخیره کنید؛ سرور فقط هش آن را نگه می‌دارد و بعداً قابل بازیابی نیست.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <code class="block overflow-x-auto rounded-md bg-muted p-3 text-xs" dir="ltr">{{ issued }}</code>
                    <Button size="sm" variant="outline" @click="copyToken">
                        {{ copied ? 'کپی شد ✓' : 'کپی توکن' }}
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">توکن جدید</CardTitle>
                    <CardDescription>یک نام برای شناسایی این توکن انتخاب کنید (مثلاً «داشبورد شخصی»).</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="flex flex-wrap items-center gap-2" @submit.prevent="createToken">
                        <Input v-model="form.name" type="text" placeholder="نام توکن" class="h-9 w-64" />
                        <Button size="sm" class="gap-1" :disabled="form.processing">
                            <Plus class="size-4" />
                            ساخت توکن
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">توکن‌های فعال</CardTitle>
                    <CardDescription>توکن‌های شما با تاریخ ساخت و آخرین استفاده — حذف فوری اعتبار را قطع می‌کند.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-for="token in props.tokens" :key="token.id" class="flex items-center justify-between gap-3 rounded-md border p-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 font-medium">
                                <KeyRound class="size-4 shrink-0 text-muted-foreground" />
                                <span class="truncate">{{ token.name }}</span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                ساخته: {{ token.created_at ? faDateTime(token.created_at) : '—' }} • آخرین استفاده:
                                {{ token.last_used_at ? faDateTime(token.last_used_at) : 'هرگز' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge v-if="token.last_used_at" variant="secondary">استفاده‌شده</Badge>
                            <Button size="sm" variant="ghost" class="text-red-600 hover:text-red-700" @click="revoke(token)">
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                    <p v-if="props.tokens.length === 0" class="text-sm text-muted-foreground">هنوز توکنی نساخته‌اید.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
