<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, ReviewItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Check, MessageSquareReply, MessageSquareText, Pencil, Star, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineProps<{
    reviews: (ReviewItem & { service?: string | null })[];
    stats: { pending: number; approved: number; average: number | null };
}>();

const { t, num, dateShort } = useLocale();

const replyingId = ref<number | null>(null);
const replyText = ref('');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.reviews.title'), href: '/reviews' },
]);

function approve(id: number): void {
    router.patch(route('reviews.approve', id), {}, { preserveScroll: true });
}

function remove(id: number): void {
    router.delete(route('reviews.destroy', id), { preserveScroll: true });
}

function startReply(id: number, current: string | null): void {
    replyingId.value = replyingId.value === id ? null : id;
    replyText.value = current ?? '';
}

function saveReply(id: number): void {
    if (!replyText.value.trim()) return;

    router.patch(
        route('reviews.reply', id),
        { reply: replyText.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                replyingId.value = null;
                replyText.value = '';
            },
        },
    );
}
</script>

<template>
    <Head :title="t('panel.reviews.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div>
                <h1 class="text-xl font-bold">{{ t('panel.reviews.title') }}</h1>
                <p class="text-sm text-muted-foreground">{{ t('panel.reviews.subtitle') }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('panel.reviews.statPending') }}</CardTitle>
                        <MessageSquareText class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ num(stats.pending) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('panel.reviews.statApproved') }}</CardTitle>
                        <Check class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ num(stats.approved) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('panel.reviews.statAverage') }}</CardTitle>
                        <Star class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-1 text-2xl font-bold">
                            {{ stats.average ? num(stats.average) : '—' }}
                            <Star v-if="stats.average" class="size-5 fill-amber-400 text-amber-400" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-3">
                <Card v-for="review in reviews" :key="review.id">
                    <CardContent class="flex flex-wrap items-start justify-between gap-4 pt-4">
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ review.customer_name }}</span>
                                <span class="flex gap-0.5">
                                    <Star
                                        v-for="i in 5"
                                        :key="i"
                                        class="size-4"
                                        :class="i <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/30'"
                                    />
                                </span>
                                <Badge v-if="review.is_approved" variant="secondary" class="text-emerald-600">{{ t('panel.reviews.approvedBadge') }}</Badge>
                                <Badge v-else variant="outline" class="text-amber-600">{{ t('panel.reviews.pendingBadge') }}</Badge>
                            </div>
                            <p v-if="review.comment" class="text-sm text-muted-foreground">{{ review.comment }}</p>

                            <!-- manager's public answer -->
                            <div
                                v-if="review.reply"
                                class="rounded-xl border bg-muted/60 px-3 py-2"
                            >
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-primary">
                                    <MessageSquareReply class="size-3.5" />
                                    {{ t('panel.reviews.replyLabel') }}
                                </div>
                                <p class="mt-1 text-sm">{{ review.reply }}</p>
                            </div>

                            <div v-if="replyingId === review.id" class="space-y-2">
                                <Textarea
                                    v-model="replyText"
                                    :rows="3"
                                    :placeholder="t('panel.reviews.replyPlaceholder')"
                                    :aria-label="t('panel.reviews.replyPlaceholder')"
                                />
                                <div class="flex gap-2">
                                    <Button size="sm" class="gap-1" :disabled="!replyText.trim()" @click="saveReply(review.id)">
                                        <Check class="size-4" />
                                        {{ t('panel.reviews.replySave') }}
                                    </Button>
                                    <Button size="sm" variant="ghost" @click="replyingId = null">
                                        {{ t('panel.reviews.replyCancel') }}
                                    </Button>
                                </div>
                            </div>

                            <p class="text-xs text-muted-foreground">
                                {{ t('panel.reviews.service') }} {{ review.service ?? '—' }}
                                <span v-if="review.created_at"> • {{ dateShort(review.created_at ?? '') }}</span>
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Button
                                v-if="review.is_approved"
                                size="sm"
                                variant="outline"
                                class="gap-1"
                                @click="startReply(review.id, review.reply)"
                            >
                                <Pencil v-if="review.reply" class="size-4" />
                                <MessageSquareReply v-else class="size-4" />
                                {{ review.reply ? t('panel.reviews.replyEdit') : t('panel.reviews.replyButton') }}
                            </Button>
                            <Button v-if="!review.is_approved" size="sm" class="gap-1" @click="approve(review.id)">
                                <Check class="size-4" />
                                {{ t('panel.reviews.approve') }}
                            </Button>
                            <Button size="sm" variant="outline" class="gap-1 text-destructive" @click="remove(review.id)">
                                <Trash2 class="size-4" />
                                {{ t('panel.common.delete') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <p v-if="reviews.length === 0" class="py-10 text-center text-muted-foreground">{{ t('panel.reviews.empty') }}</p>
            </div>
        </div>
    </AppLayout>
</template>
