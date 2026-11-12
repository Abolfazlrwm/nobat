<script setup lang="ts">
import BookingWizard from '@/components/booking/BookingWizard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useLocale } from '@/composables/useLocale';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { BusinessWithServices } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Clock, MapPin, Phone, Star, X } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    business: BusinessWithServices;
}>();

const { t, num, dateShort } = useLocale();

const lightbox = ref<number | null>(null);

const photos = (business: BusinessWithServices) => business.photos ?? [];

const lightboxPhoto = (business: BusinessWithServices) =>
    lightbox.value === null ? null : photos(business)[lightbox.value] ?? null;

function closeLightbox(): void {
    lightbox.value = null;
}

function stepLightbox(business: BusinessWithServices, step: number): void {
    const count = photos(business).length;

    if (lightbox.value === null || count === 0) return;

    lightbox.value = (lightbox.value + step + count) % count;
}
</script>

<template>
    <Head :title="business.name">
        <meta name="description" :content="business.description || business.name">
        <meta property="og:title" :content="business.name">
        <meta property="og:description" :content="business.description || ''">
        <meta property="og:type" content="business.business">
    </Head>

    <PublicLayout>
        <section class="mx-auto max-w-6xl px-4 py-8">
            <div class="rounded-2xl border bg-background p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold">{{ business.name }}</h1>
                        <div v-if="business.reviews_summary?.average" class="mt-2 flex items-center gap-2">
                            <span class="flex gap-0.5">
                                <Star
                                    v-for="i in 5"
                                    :key="i"
                                    class="size-4"
                                    :class="
                                        i <= Math.round(business.reviews_summary?.average ?? 0)
                                            ? 'fill-amber-400 text-amber-400'
                                            : 'text-muted-foreground/30'
                                    "
                                />
                            </span>
                            <span class="text-sm font-semibold text-amber-600 dark:text-amber-400">
                                {{ num(business.reviews_summary?.average ?? 0) }} {{ t('landing.ratingOutOf') }}
                            </span>
                            <span class="text-xs text-muted-foreground">
                                {{ t('landing.reviewCount', { n: num(business.reviews_summary?.count ?? 0) }) }}
                            </span>
                        </div>
                        <p v-if="business.description" class="mt-2 max-w-2xl text-muted-foreground">
                            {{ business.description }}
                        </p>
                    </div>

                    <div class="space-y-2 text-sm text-muted-foreground">
                        <div v-if="business.phone" class="flex items-center gap-2">
                            <Phone class="size-4" />
                            <span dir="ltr">{{ num(business.phone) }}</span>
                        </div>
                        <div v-if="business.address" class="flex items-center gap-2">
                            <MapPin class="size-4" />
                            <span>{{ business.address }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <Badge v-for="service in business.services" :key="service.id" variant="secondary" class="gap-1">
                        <Clock class="size-3" />
                        {{ service.name }}
                    </Badge>
                </div>
            </div>

            <div v-if="business.photos && business.photos.length > 0" class="mt-6">
                <h2 class="mb-3 text-lg font-semibold">{{ t('business.galleryTitle') }}</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <button
                        v-for="(photo, index) in business.photos"
                        :key="photo.id"
                        type="button"
                        class="group relative aspect-video overflow-hidden rounded-xl border"
                        @click="lightbox = index"
                    >
                        <img
                            :src="photo.url"
                            :alt="photo.caption ?? ''"
                            class="size-full object-cover transition-transform group-hover:scale-105"
                            loading="lazy"
                        />
                        <span
                            v-if="photo.caption"
                            class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/70 to-transparent px-2 pb-1 pt-4 text-[11px] text-white"
                        >
                            {{ photo.caption }}
                        </span>
                    </button>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <BookingWizard :business="business" />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">{{ t('business.team') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="member in business.staff" :key="member.id" class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                {{ member.name.charAt(0) }}
                            </span>
                            <span>
                                <span class="block text-sm font-medium">{{ member.name }}</span>
                                <span v-if="member.specialty" class="block text-xs text-muted-foreground">
                                    {{ member.specialty }}
                                </span>
                            </span>
                        </div>

                        <p v-if="business.staff.length === 0" class="text-sm text-muted-foreground">{{ t('business.noStaff') }}</p>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="business.reviews && business.reviews.length > 0" class="mt-6">
                <CardHeader>
                    <CardTitle class="text-base">{{ t('business.reviewsTitle') }}</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <div v-for="review in business.reviews" :key="review.id" class="rounded-xl border bg-muted/40 p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">{{ review.customer_name }}</span>
                            <span class="flex gap-0.5">
                                <Star
                                    v-for="i in 5"
                                    :key="i"
                                    class="size-3.5"
                                    :class="i <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/30'"
                                />
                            </span>
                        </div>
                        <p v-if="review.comment" class="mt-2 text-sm text-muted-foreground">{{ review.comment }}</p>
                        <p v-if="review.created_at" class="mt-2 text-xs text-muted-foreground">
                            {{ dateShort(review.created_at ?? '') }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Dialog :open="lightbox !== null" @update:open="(open: boolean) => (open ? null : closeLightbox())">
                <DialogContent class="max-w-3xl border-none bg-transparent p-2 shadow-none sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle class="text-sm text-white">{{ lightboxPhoto(business)?.caption ?? '' }}</DialogTitle>
                    </DialogHeader>

                    <div v-if="lightboxPhoto(business)" class="space-y-3">
                        <img
                            :src="lightboxPhoto(business)?.url"
                            :alt="lightboxPhoto(business)?.caption ?? ''"
                            class="max-h-[70vh] w-full rounded-lg object-contain"
                        />

                        <div class="flex items-center justify-center gap-2">
                            <Button size="sm" variant="secondary" @click="stepLightbox(business, -1)">
                                <ChevronRight class="size-4" />
                            </Button>
                            <Button size="sm" variant="secondary" @click="closeLightbox()">
                                <X class="size-4" />
                            </Button>
                            <Button size="sm" variant="secondary" @click="stepLightbox(business, 1)">
                                <ChevronLeft class="size-4" />
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </section>
    </PublicLayout>
</template>
