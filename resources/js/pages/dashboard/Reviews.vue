<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDigits } from '@/lib/persian';
import { BreadcrumbItem, ReviewItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Check, MessageSquareText, Star, Trash2 } from 'lucide-vue-next';

defineProps<{
    reviews: (ReviewItem & { service?: string | null })[];
    stats: { pending: number; approved: number; average: number | null };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'نظرات مشتریان', href: '/reviews' },
];

function approve(id: number): void {
    router.patch(route('reviews.approve', id), {}, { preserveScroll: true });
}

function remove(id: number): void {
    router.delete(route('reviews.destroy', id), { preserveScroll: true });
}
</script>

<template>
    <Head title="نظرات مشتریان" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div>
                <h1 class="text-xl font-bold">نظرات مشتریان</h1>
                <p class="text-sm text-muted-foreground">نظرات مشتریان را بررسی کنید؛ فقط نظرات تأییدشده در صفحه‌ی کسب‌وکار نمایش داده می‌شوند.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">در انتظار تأیید</CardTitle>
                        <MessageSquareText class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ faDigits(stats.pending) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">تأییدشده</CardTitle>
                        <Check class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ faDigits(stats.approved) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">میانگین امتیاز</CardTitle>
                        <Star class="size-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-1 text-2xl font-bold">
                            {{ stats.average ? faDigits(stats.average) : '—' }}
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
                                <Badge v-if="review.is_approved" variant="secondary" class="text-emerald-600">تأییدشده</Badge>
                                <Badge v-else variant="outline" class="text-amber-600">در انتظار تأیید</Badge>
                            </div>
                            <p v-if="review.comment" class="text-sm text-muted-foreground">{{ review.comment }}</p>
                            <p class="text-xs text-muted-foreground">
                                خدمت: {{ review.service ?? '—' }}
                                <span v-if="review.created_at"> • {{ faDigits(review.created_at ?? '') }}</span>
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Button v-if="!review.is_approved" size="sm" class="gap-1" @click="approve(review.id)">
                                <Check class="size-4" />
                                تأیید
                            </Button>
                            <Button size="sm" variant="outline" class="gap-1 text-destructive" @click="remove(review.id)">
                                <Trash2 class="size-4" />
                                حذف
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <p v-if="reviews.length === 0" class="py-10 text-center text-muted-foreground">هنوز نظری ثبت نشده است.</p>
            </div>
        </div>
    </AppLayout>
</template>
