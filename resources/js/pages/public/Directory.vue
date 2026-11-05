<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { faDigits } from '@/lib/persian';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, MapPin, Phone } from 'lucide-vue-next';
import { BusinessSummary } from '@/types';

defineProps<{
    businesses: BusinessSummary[];
}>();
</script>

<template>
    <Head title="نوبت‌دهی آنلاین" />

    <PublicLayout>
        <section class="mx-auto max-w-6xl px-4 py-12">
            <FlashMessages />

            <div class="mx-auto max-w-2xl text-center">
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl">
                    نوبت بگیر، بدون تماس و بدون معطلی
                </h1>
                <p class="mt-3 text-muted-foreground">
                    کسب‌وکار مورد نظرت را انتخاب کن، خدمت و زمان دلخواه را پیدا کن و نوبتت را در چند ثانیه رزرو کن.
                </p>
            </div>

            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="business in businesses" :key="business.id" class="transition-shadow hover:shadow-md">
                    <CardHeader>
                        <CardTitle class="text-lg">{{ business.name }}</CardTitle>
                        <CardDescription class="line-clamp-2 min-h-10">
                            {{ business.description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <MapPin class="size-4 shrink-0" />
                            <span class="line-clamp-1">{{ business.address }}</span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary" class="gap-1">
                                <CalendarClock class="size-3" />
                                {{ faDigits(business.services_count ?? 0) }} خدمت
                            </Badge>
                            <Badge variant="secondary" class="gap-1">
                                <Phone class="size-3" />
                                {{ faDigits(business.staff_count ?? 0) }} همکار
                            </Badge>
                        </div>

                        <Link
                            :href="`/b/${business.slug}`"
                            class="inline-flex h-9 w-full items-center justify-center rounded-md bg-primary text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            مشاهده و رزرو نوبت
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <div v-if="businesses.length === 0" class="mt-16 text-center text-muted-foreground">
                هنوز کسب‌وکاری ثبت نشده است.
            </div>
        </section>
    </PublicLayout>
</template>
