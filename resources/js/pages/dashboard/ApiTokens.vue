<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { KeyRound, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

const { t, dateTime } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.apiTokens.title'), href: '/api-tokens' },
]);

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
    <Head :title="t('panel.apiTokens.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div>
                <h1 class="text-xl font-bold">{{ t('panel.apiTokens.title') }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('panel.apiTokens.subtitle') }}
                    <a class="font-medium text-primary underline" href="#" @click.prevent>docs/API.md</a>
                </p>
            </div>

            <Card v-if="issued" class="border-emerald-500/40 bg-emerald-500/5">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <KeyRound class="size-4 text-emerald-600" />
                        {{ t('panel.apiTokens.issuedTitle') }}
                    </CardTitle>
                    <CardDescription>{{ t('panel.apiTokens.issuedDesc') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <code class="block overflow-x-auto rounded-md bg-muted p-3 text-xs" dir="ltr">{{ issued }}</code>
                    <Button size="sm" variant="outline" @click="copyToken">
                        {{ copied ? t('panel.common.copied') : t('panel.apiTokens.copy') }}
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">{{ t('panel.apiTokens.newTitle') }}</CardTitle>
                    <CardDescription>{{ t('panel.apiTokens.newDesc') }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="flex flex-wrap items-center gap-2" @submit.prevent="createToken">
                        <Input v-model="form.name" type="text" :placeholder="t('panel.apiTokens.namePlaceholder')" class="h-9 w-64" />
                        <Button size="sm" class="gap-1" :disabled="form.processing">
                            <Plus class="size-4" />
                            {{ t('panel.apiTokens.create') }}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">{{ t('panel.apiTokens.listTitle') }}</CardTitle>
                    <CardDescription>{{ t('panel.apiTokens.listDesc') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-for="token in props.tokens" :key="token.id" class="flex items-center justify-between gap-3 rounded-md border p-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 font-medium">
                                <KeyRound class="size-4 shrink-0 text-muted-foreground" />
                                <span class="truncate">{{ token.name }}</span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ t('panel.apiTokens.created') }} {{ token.created_at ? dateTime(token.created_at) : '—' }} •
                                {{ t('panel.apiTokens.lastUsed') }}
                                {{ token.last_used_at ? dateTime(token.last_used_at) : t('panel.apiTokens.never') }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge v-if="token.last_used_at" variant="secondary">{{ t('panel.apiTokens.usedBadge') }}</Badge>
                            <Button size="sm" variant="ghost" class="text-red-600 hover:text-red-700" @click="revoke(token)">
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                    <p v-if="props.tokens.length === 0" class="text-sm text-muted-foreground">{{ t('panel.apiTokens.empty') }}</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
