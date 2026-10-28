<script setup lang="ts">
import BookingWizard from '@/components/booking/BookingWizard.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { faDigits } from '@/lib/persian';
import { Head } from '@inertiajs/vue3';
import { Clock, MapPin, Phone } from 'lucide-vue-next';
import { BusinessWithServices } from '@/types';

const props = defineProps<{
    business: BusinessWithServices;
}>();
</script>

<template>
    <Head :title="business.name" />

    <PublicLayout>
        <section class="mx-auto max-w-6xl px-4 py-8">
            <div class="rounded-2xl border bg-background p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold">{{ business.name }}</h1>
                        <p v-if="business.description" class="mt-2 max-w-2xl text-muted-foreground">
                            {{ business.description }}
                        </p>
                    </div>

                    <div class="space-y-2 text-sm text-muted-foreground">
                        <div v-if="business.phone" class="flex items-center gap-2">
                            <Phone class="size-4" />
                            <span dir="ltr">{{ faDigits(business.phone) }}</span>
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

            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <BookingWizard :business="business" />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">تیم ما</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="member in business.staff"
                            :key="member.id"
                            class="flex items-center gap-3"
                        >
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

                        <p v-if="business.staff.length === 0" class="text-sm text-muted-foreground">
                            هنوز همکاری ثبت نشده است.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>
    </PublicLayout>
</template>
