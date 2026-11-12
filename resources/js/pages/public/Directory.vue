<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { BusinessSummary } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    BarChart3,
    Building2,
    CalendarClock,
    CalendarDays,
    CheckCircle2,
    Github,
    Languages,
    MapPin,
    MessageSquareText,
    Phone,
    Sparkles,
    Star,
    Users,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    businesses: BusinessSummary[];
}>();

const { t, isFa, num } = useLocale();

const GITHUB_URL = 'https://github.com/Abolfazlrwm/nobat';
const APP_VERSION = '1.11.0';

const totalServices = computed(() => props.businesses.reduce((sum, b) => sum + (b.services_count ?? 0), 0));
const totalStaff = computed(() => props.businesses.reduce((sum, b) => sum + (b.staff_count ?? 0), 0));

const heroStats = computed(() => [
    { value: num(props.businesses.length), label: t('landing.statBusinesses') },
    { value: num(totalServices.value), label: t('landing.statServices') },
    { value: num(totalStaff.value), label: t('landing.statStaff') },
]);

const featureIcons = [Building2, CalendarClock, Users, MessageSquareText, BarChart3, Languages];

const features = computed(() =>
    featureIcons.map((icon, i) => ({
        icon,
        title: t(`landing.features.${i}.title`),
        description: t(`landing.features.${i}.description`),
    })),
);

const steps = computed(() =>
    [0, 1, 2].map((i) => ({
        title: t(`landing.steps.${i}.title`),
        description: t(`landing.steps.${i}.description`),
    })),
);

const techStack = ['Laravel 13', 'Inertia 2', 'Vue 3 + TypeScript', 'Tailwind CSS', 'Pest', 'Docker', 'Render'];

const demoAccounts = computed(() => [
    { label: t('landing.demoSuperAdmin'), email: 'admin@nobat.ir' },
    { label: t('landing.demoClinicManager'), email: 'manager@nobat.ir' },
    { label: t('landing.demoClinicStaff'), email: 'staff@nobat.ir' },
    { label: t('landing.demoSalonManager'), email: 'salon@nobat.ir' },
]);

const bookingPreviewRows = computed(() => [0, 1, 2].map((i) => t(`landing.previewRows.${i}`)));

const forwardIcon = isFa.value ? ArrowLeft : ArrowRight;
const heroGradient = isFa.value
    ? 'bg-gradient-to-l from-primary via-primary/80 to-primary/40'
    : 'bg-gradient-to-r from-primary via-primary/80 to-primary/40';
</script>

<template>
    <Head :title="t('landing.pageTitle')" />

    <PublicLayout>
        <section class="relative overflow-hidden">
            <!-- decorative background -->
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute -top-32 start-[-10%] h-96 w-96 rounded-full bg-gradient-to-br from-primary/15 to-transparent blur-3xl"></div>
                <div class="absolute end-[-8%] top-40 h-80 w-80 rounded-full bg-gradient-to-bl from-primary/10 to-transparent blur-3xl"></div>
            </div>

            <FlashMessages />

            <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 pb-16 pt-14 lg:grid-cols-2 lg:pb-24 lg:pt-20">
                <div>
                    <Badge variant="secondary" class="gap-1.5 rounded-full px-3 py-1 text-xs">
                        <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                        {{ t('landing.badge', { version: num(APP_VERSION) }) }}
                    </Badge>

                    <h1 class="mt-5 text-4xl font-bold leading-[1.25] tracking-tight md:text-5xl md:leading-[1.25]">
                        {{ t('landing.heroTitleA') }}
                        <span :class="['bg-clip-text text-transparent', heroGradient]">
                            {{ t('landing.heroTitleB') }}
                        </span>
                    </h1>

                    <p class="mt-5 max-w-xl text-lg leading-8 text-muted-foreground">
                        {{ t('landing.heroSubtitle') }}
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Button as-child size="lg" class="gap-2">
                            <a href="#businesses">
                                {{ t('landing.ctaBook') }}
                                <component :is="forwardIcon" class="h-4 w-4" />
                            </a>
                        </Button>
                        <Button as-child size="lg" variant="outline">
                            <Link :href="route('login')">{{ t('landing.ctaDashboard') }}</Link>
                        </Button>
                        <Button as-child size="lg" variant="ghost" class="gap-2 text-muted-foreground">
                            <a :href="GITHUB_URL" target="_blank" rel="noopener">
                                <Github class="h-4 w-4" />
                                {{ t('landing.ctaGithub') }}
                            </a>
                        </Button>
                    </div>

                    <dl v-if="businesses.length > 0" class="mt-10 grid max-w-md grid-cols-3 gap-4">
                        <div v-for="stat in heroStats" :key="stat.label" class="text-center sm:text-start">
                            <dt class="order-2 text-xs text-muted-foreground">{{ stat.label }}</dt>
                            <dd class="order-1 text-2xl font-bold text-primary">{{ stat.value }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- decorative booking preview -->
                <div class="relative mx-auto hidden w-full max-w-md lg:block">
                    <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-tr from-primary/10 via-transparent to-primary/5 blur-xl"></div>
                    <Card class="rotate-1 rounded-2xl border-border/60 shadow-xl transition-transform duration-500 hover:rotate-0">
                        <CardHeader class="flex-row items-center justify-between space-y-0">
                            <CardTitle class="text-base">{{ t('landing.previewTitle') }}</CardTitle>
                            <CalendarDays class="h-5 w-5 text-primary" />
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-for="row in bookingPreviewRows"
                                :key="row"
                                class="flex items-center justify-between rounded-lg border bg-muted/40 px-3 py-2.5 text-sm"
                            >
                                <span class="text-foreground/90">{{ row }}</span>
                                <CheckCircle2 class="h-4 w-4 shrink-0 text-green-600" />
                            </div>
                            <p class="pt-1 text-center text-xs text-muted-foreground">{{ t('landing.previewNote') }}</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- features -->
        <section class="border-y bg-background">
            <div class="mx-auto max-w-6xl px-4 py-16">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-2xl font-bold tracking-tight md:text-3xl">{{ t('landing.featuresTitle') }}</h2>
                    <p class="mt-3 text-muted-foreground">
                        {{ t('landing.featuresSubtitle') }}
                    </p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="feature in features" :key="feature.title" class="group transition-all hover:-translate-y-0.5 hover:shadow-md">
                        <CardHeader>
                            <div
                                class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground"
                            >
                                <component :is="feature.icon" class="h-5 w-5" />
                            </div>
                            <CardTitle class="text-base">{{ feature.title }}</CardTitle>
                            <CardDescription class="leading-7">{{ feature.description }}</CardDescription>
                        </CardHeader>
                    </Card>
                </div>
            </div>
        </section>

        <!-- how it works -->
        <section class="mx-auto max-w-6xl px-4 py-16">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-2xl font-bold tracking-tight md:text-3xl">{{ t('landing.stepsTitle') }}</h2>
                <p class="mt-3 text-muted-foreground">{{ t('landing.stepsSubtitle') }}</p>
            </div>

            <ol class="mt-10 grid gap-6 md:grid-cols-3">
                <li v-for="(step, index) in steps" :key="step.title" class="relative rounded-xl border bg-background p-6">
                    <span
                        class="absolute -top-4 start-6 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                    >
                        {{ num(index + 1) }}
                    </span>
                    <h3 class="mt-2 font-semibold">{{ step.title }}</h3>
                    <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ step.description }}</p>
                </li>
            </ol>
        </section>

        <!-- businesses directory -->
        <section id="businesses" class="scroll-mt-16 border-y bg-background">
            <div class="mx-auto max-w-6xl px-4 py-16">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-2xl font-bold tracking-tight md:text-3xl">{{ t('landing.dirTitle') }}</h2>
                    <p class="mt-3 text-muted-foreground">{{ t('landing.dirSubtitle') }}</p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="business in businesses" :key="business.id" class="transition-all hover:-translate-y-0.5 hover:shadow-md">
                        <CardHeader>
                            <CardTitle class="text-lg">{{ business.name }}</CardTitle>
                            <CardDescription class="line-clamp-2 min-h-10">{{ business.description }}</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                <MapPin class="size-4 shrink-0" />
                                <span class="line-clamp-1">{{ business.address }}</span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="secondary" class="gap-1">
                                    <CalendarClock class="size-3" />
                                    {{ t('landing.serviceCount', { n: num(business.services_count ?? 0) }) }}
                                </Badge>
                                <Badge variant="secondary" class="gap-1">
                                    <Phone class="size-3" />
                                    {{ t('landing.staffCount', { n: num(business.staff_count ?? 0) }) }}
                                </Badge>
                                <Badge v-if="business.rating_avg" variant="secondary" class="gap-1 text-amber-600 dark:text-amber-400">
                                    <Star class="size-3 fill-amber-400 text-amber-400" />
                                    {{ num(business.rating_avg ?? 0) }} {{ t('landing.ratingOutOf') }}
                                    <span class="text-muted-foreground">{{ t('landing.reviewCount', { n: num(business.rating_count ?? 0) }) }}</span>
                                </Badge>
                            </div>

                            <Button as-child class="w-full">
                                <Link :href="`/b/${business.slug}`">{{ t('landing.viewAndBook') }}</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="businesses.length === 0" class="mt-16 text-center text-muted-foreground">{{ t('landing.empty') }}</div>
            </div>
        </section>

        <!-- demo panel -->
        <section class="mx-auto max-w-6xl px-4 py-16">
            <div class="relative overflow-hidden rounded-3xl bg-primary px-6 py-12 text-primary-foreground md:px-12">
                <div aria-hidden="true" class="pointer-events-none absolute -top-24 end-[-5%] h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>

                <div class="grid items-center gap-8 lg:grid-cols-2">
                    <div>
                        <Badge class="gap-1.5 border-white/20 bg-white/10 text-primary-foreground hover:bg-white/15">
                            <Zap class="h-3.5 w-3.5 text-amber-300" />
                            {{ t('landing.demoBadge') }}
                        </Badge>
                        <h2 class="mt-4 text-2xl font-bold md:text-3xl">{{ t('landing.demoTitle') }}</h2>
                        <p class="mt-3 max-w-lg leading-8 text-primary-foreground/80">
                            {{ t('landing.demoText1') }}
                            <code dir="ltr" class="rounded bg-white/15 px-1.5 py-0.5 font-mono text-sm">password</code>
                            {{ t('landing.demoText2') }}
                        </p>
                        <Button as-child size="lg" variant="secondary" class="mt-6 gap-2">
                            <Link :href="route('login')">
                                {{ t('landing.demoCta') }}
                                <component :is="forwardIcon" class="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <div
                            v-for="account in demoAccounts"
                            :key="account.email"
                            class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur transition-colors hover:bg-white/15"
                        >
                            <p class="text-sm font-medium">{{ account.label }}</p>
                            <p dir="ltr" class="mt-1 text-xs text-primary-foreground/70">{{ account.email }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- tech stack -->
        <section class="mx-auto max-w-6xl px-4 pb-20">
            <div class="flex flex-col items-center gap-5 rounded-2xl border bg-background px-6 py-8">
                <p class="text-sm font-medium text-muted-foreground">{{ t('landing.techNote') }}</p>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <Badge v-for="tech in techStack" :key="tech" variant="secondary" class="px-3 py-1 text-xs">
                        {{ tech }}
                    </Badge>
                </div>
                <a
                    :href="`${GITHUB_URL}/blob/main/CHANGELOG.md`"
                    target="_blank"
                    rel="noopener"
                    class="text-xs text-muted-foreground underline-offset-4 hover:underline"
                >
                    {{ t('landing.changelogLink') }}
                </a>
            </div>
        </section>
    </PublicLayout>
</template>
