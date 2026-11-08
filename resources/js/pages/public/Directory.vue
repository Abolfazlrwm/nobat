<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { faDigits } from '@/lib/persian';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
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
    Users,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { BusinessSummary } from '@/types';

const props = defineProps<{
    businesses: BusinessSummary[];
}>();

const GITHUB_URL = 'https://github.com/Abolfazlrwm/nobat';

const totalServices = computed(() => props.businesses.reduce((sum, b) => sum + (b.services_count ?? 0), 0));
const totalStaff = computed(() => props.businesses.reduce((sum, b) => sum + (b.staff_count ?? 0), 0));

const heroStats = computed(() => [
    { value: faDigits(props.businesses.length), label: 'کسب‌وکار فعال' },
    { value: faDigits(totalServices.value), label: 'خدمت آماده‌ی رزرو' },
    { value: faDigits(totalStaff.value), label: 'همکار آنلاین' },
]);

const features = [
    {
        icon: Building2,
        title: 'چندکسب‌وکاری',
        description:
            'هر کسب‌وکار دنیای مستقل خودش را دارد: خدمات، پرسنل، ساعات کاری و صفحه‌ی اختصاصی رزرو با آدرس اختصاصی.',
    },
    {
        icon: CalendarClock,
        title: 'رزرو لحظه‌ای',
        description:
            'اسلات‌های آزاد بر اساس شیفت واقعی پرسنل به‌صورت زنده محاسبه می‌شوند؛ دو نفر هرگز روی یک نوبت نمی‌جنگند.',
    },
    {
        icon: Users,
        title: 'مدیریت پرسنل و شیفت',
        description:
            'ساعات کاری هر همکار را جداگانه تعریف کن و تغییر شیفت‌ها را بدون تماس تلفنی و جدول اکسل مدیریت کن.',
    },
    {
        icon: MessageSquareText,
        title: 'پیامک یادآوری',
        description:
            'درایور کاوه‌نگار پشت یک قرارداد ساده‌ی پیامک نشسته است؛ اگر سرویس‌دهنده‌ی دیگری خواستی، فقط یک درایور جدید بنویس.',
    },
    {
        icon: BarChart3,
        title: 'داشبورد مدیریتی',
        description:
            'وضعیت هر رزرو، نوبت‌های امروز و گردش کار روزانه در یک نگاه؛ تغییر وضعیت با یک کلیک اعمال می‌شود.',
    },
    {
        icon: Languages,
        title: 'RTL و تقویم جلالی',
        description:
            'رابط کاملاً راست‌به‌چپ با فونت وزیرمتن و اعداد فارسی؛ برای کاربر ایرانی، طبیعی — نه ترجمه‌شده — حس می‌شود.',
    },
];

const steps = [
    {
        title: 'کسب‌وکار را انتخاب کن',
        description: 'از فهرست کسب‌وکارهای فعال، کلینیک یا سالن مورد نظرت را باز کن و خدماتش را ببین.',
    },
    {
        title: 'خدمت و زمان دلخواه را بچین',
        description: 'فقط اسلات‌های واقعاً خالی نمایش داده می‌شوند؛ روز، ساعت و پرسنل را خودت انتخاب می‌کنی.',
    },
    {
        title: 'تأیید بگیر، یادآوری بگیر',
        description: 'کد پیگیری فوری صادر می‌شود و پیش از موعد نوبت، یادآوری پیامکی دریافت می‌کنی.',
    },
];

const techStack = ['Laravel 13', 'Inertia 2', 'Vue 3 + TypeScript', 'Tailwind CSS', 'Pest', 'Docker', 'Render'];

const demoAccounts = [
    { label: 'مدیر کل سامانه', email: 'admin@nobat.ir' },
    { label: 'مدیر کلینیک پارس', email: 'manager@nobat.ir' },
    { label: 'پرسنل کلینیک پارس', email: 'staff@nobat.ir' },
    { label: 'مدیر سالن آفتاب', email: 'salon@nobat.ir' },
];

const bookingPreviewRows = [
    'ویزیت عمومی — دوشنبه، ۱۴:۳۰',
    'کوتاهی مو — سه‌شنبه، ۱۰:۰۰',
    'کنسل با یک کلیک، اسلات فوراً آزاد می‌شود',
];
</script>

<template>
    <Head title="نوبت — سامانه نوبت‌دهی آنلاین" />

    <PublicLayout>
        <section class="relative overflow-hidden">
            <!-- decorative background -->
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
                <div
                    class="absolute -top-32 start-[-10%] h-96 w-96 rounded-full bg-gradient-to-br from-primary/15 to-transparent blur-3xl"
                ></div>
                <div
                    class="absolute top-40 end-[-8%] h-80 w-80 rounded-full bg-gradient-to-bl from-primary/10 to-transparent blur-3xl"
                ></div>
            </div>

            <FlashMessages />

            <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 pb-16 pt-14 lg:grid-cols-2 lg:pb-24 lg:pt-20">
                <div>
                    <Badge variant="secondary" class="gap-1.5 rounded-full px-3 py-1 text-xs">
                        <Sparkles class="h-3.5 w-3.5 text-amber-500" />
                        متن‌باز و رایگان — نسخه {{ faDigits('1.3.0') }}
                    </Badge>

                    <h1 class="mt-5 text-4xl font-bold leading-[1.25] tracking-tight md:text-5xl md:leading-[1.25]">
                        نوبت آنلاین،
                        <span
                            class="bg-gradient-to-l from-primary via-primary/80 to-primary/40 bg-clip-text text-transparent"
                        >
                            بدون تماس و بدون معطلی
                        </span>
                    </h1>

                    <p class="mt-5 max-w-xl text-lg leading-8 text-muted-foreground">
                        نوبت، نوبت‌دهی آنلاین را برای کلینیک‌ها، سالن‌ها و هر کسب‌وکار نوبت‌محور ساده می‌کند: مشتری
                        خودش روز و ساعت را برمی‌دارد، پرسنل شیفتش را مدیریت می‌کند و یادآوری پیامکی خودکار می‌رود.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Button as-child size="lg" class="gap-2">
                            <a href="#businesses">
                                رزرو نوبت
                                <ArrowLeft class="h-4 w-4" />
                            </a>
                        </Button>
                        <Button as-child size="lg" variant="outline">
                            <Link :href="route('login')">ورود به داشبورد دمو</Link>
                        </Button>
                        <Button as-child size="lg" variant="ghost" class="gap-2 text-muted-foreground">
                            <a :href="GITHUB_URL" target="_blank" rel="noopener">
                                <Github class="h-4 w-4" />
                                گیت‌هاب
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
                    <div
                        class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-tr from-primary/10 via-transparent to-primary/5 blur-xl"
                    ></div>
                    <Card class="rotate-1 rounded-2xl border-border/60 shadow-xl transition-transform duration-500 hover:rotate-0">
                        <CardHeader class="flex-row items-center justify-between space-y-0">
                            <CardTitle class="text-base">رزرو نوبت جدید</CardTitle>
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
                            <p class="pt-1 text-center text-xs text-muted-foreground">
                                همین الان، بدون حتی یک تماس تلفنی
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- features -->
        <section class="border-y bg-background">
            <div class="mx-auto max-w-6xl px-4 py-16">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-2xl font-bold tracking-tight md:text-3xl">هر چیزی که یک کسب‌وکار نوبت‌محور لازم دارد</h2>
                    <p class="mt-3 text-muted-foreground">
                        از صفحه‌ی رزرو عمومی تا داشبورد مدیریتی؛ همه چیز از همان ابتدا فارسی، راست‌به‌چپ و جلالی.
                    </p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="feature in features"
                        :key="feature.title"
                        class="group transition-all hover:-translate-y-0.5 hover:shadow-md"
                    >
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
                <h2 class="text-2xl font-bold tracking-tight md:text-3xl">رزرو در سه قدم</h2>
                <p class="mt-3 text-muted-foreground">از انتخاب کسب‌وکار تا کد پیگیری، کمتر از یک دقیقه.</p>
            </div>

            <ol class="mt-10 grid gap-6 md:grid-cols-3">
                <li v-for="(step, index) in steps" :key="step.title" class="relative rounded-xl border bg-background p-6">
                    <span
                        class="absolute -top-4 start-6 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                    >
                        {{ faDigits(index + 1) }}
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
                    <h2 class="text-2xl font-bold tracking-tight md:text-3xl">کسب‌وکارهای فعال</h2>
                    <p class="mt-3 text-muted-foreground">یکی را انتخاب کن و همین حالا نوبت بگیر.</p>
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
                                    {{ faDigits(business.services_count ?? 0) }} خدمت
                                </Badge>
                                <Badge variant="secondary" class="gap-1">
                                    <Phone class="size-3" />
                                    {{ faDigits(business.staff_count ?? 0) }} همکار
                                </Badge>
                            </div>

                            <Button as-child class="w-full">
                                <Link :href="`/b/${business.slug}`">مشاهده و رزرو نوبت</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="businesses.length === 0" class="mt-16 text-center text-muted-foreground">
                    هنوز کسب‌وکاری ثبت نشده است.
                </div>
            </div>
        </section>

        <!-- demo panel -->
        <section class="mx-auto max-w-6xl px-4 py-16">
            <div class="relative overflow-hidden rounded-3xl bg-primary px-6 py-12 text-primary-foreground md:px-12">
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute -top-24 end-[-5%] h-72 w-72 rounded-full bg-white/10 blur-3xl"
                ></div>

                <div class="grid items-center gap-8 lg:grid-cols-2">
                    <div>
                        <Badge class="gap-1.5 border-white/20 bg-white/10 text-primary-foreground hover:bg-white/15">
                            <Zap class="h-3.5 w-3.5 text-amber-300" />
                            دموی زنده
                        </Badge>
                        <h2 class="mt-4 text-2xl font-bold md:text-3xl">قبل از هر چیز، سامانه را زنده ببین</h2>
                        <p class="mt-3 max-w-lg leading-8 text-primary-foreground/80">
                            با حساب‌های آماده‌ی دمو وارد شو: از نمای مدیر کل تا پرسنل کلینیک. رمز همه‌ی حساب‌ها
                            <code dir="ltr" class="rounded bg-white/15 px-1.5 py-0.5 font-mono text-sm">password</code>
                            است و با یک کلیک وارد می‌شوی.
                        </p>
                        <Button as-child size="lg" variant="secondary" class="mt-6 gap-2">
                            <Link :href="route('login')">
                                ورود با یک کلیک
                                <ArrowLeft class="h-4 w-4" />
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
                <p class="text-sm font-medium text-muted-foreground">ساخته‌شده با تکنولوژی‌های مدرن و آزمون‌پذیر</p>
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
                    تاریخچه‌ی نسخه‌ها را در CHANGELOG بخوان
                </a>
            </div>
        </section>
    </PublicLayout>
</template>
