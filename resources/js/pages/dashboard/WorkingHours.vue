<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { faDigits } from '@/lib/persian';
import { BreadcrumbItem, WeekdayOption, WindowItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { reactive } from 'vue';

const props = defineProps<{
    business: { id: number; name: string };
    windows: WindowItem[];
    weekdays: WeekdayOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'داشبورد', href: '/dashboard' },
    { title: 'ساعات کاری', href: '/working-hours' },
];

interface DraftWindow {
    weekday: string;
    starts_at: string;
    ends_at: string;
}

const drafts = reactive<DraftWindow[]>(
    props.windows.map((window) => ({
        weekday: String(window.weekday),
        starts_at: window.starts_at,
        ends_at: window.ends_at,
    })),
);

function addWindow(): void {
    drafts.push({ weekday: '0', starts_at: '09:00', ends_at: '17:00' });
}

function removeWindow(index: number): void {
    drafts.splice(index, 1);
}

function save(): void {
    router.post(
        route('working-hours.store'),
        { windows: drafts },
        { preserveScroll: true },
    );
}

function weekdayLabel(value: string | number): string {
    const found = props.weekdays.find((w) => w.value === Number(value));

    return found?.label ?? '';
}
</script>

<template>
    <Head title="ساعات کاری" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold">ساعات کاری</h1>
                    <p class="text-sm text-muted-foreground">{{ business.name }} — اسلات‌های رزرو از این بازه‌ها ساخته می‌شوند</p>
                </div>

                <Button variant="outline" @click="addWindow">
                    <Plus class="ml-2 size-4" />
                    بازه جدید
                </Button>
            </div>

            <div class="rounded-xl border bg-background p-4">
                <div v-for="(draft, index) in drafts" :key="index" class="mb-3 flex items-center gap-3">
                    <select v-model="draft.weekday" class="h-9 rounded-md border border-input bg-background px-3 text-sm">
                        <option v-for="weekday in weekdays" :key="weekday.value" :value="String(weekday.value)">
                            {{ weekday.label }}
                        </option>
                    </select>

                    <input
                        v-model="draft.starts_at"
                        type="time"
                        dir="ltr"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    />

                    <span class="text-sm text-muted-foreground">تا</span>

                    <input
                        v-model="draft.ends_at"
                        type="time"
                        dir="ltr"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    />

                    <Button variant="ghost" size="icon" @click="removeWindow(index)">
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </div>

                <p v-if="drafts.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                    بازه‌ای ثبت نشده؛ با دکمه «بازه جدید» شیفت‌های کاری را اضافه کنید.
                </p>

                <div class="mt-4 flex justify-end">
                    <Button :disabled="drafts.length === 0" @click="save">ذخیره ساعات کاری</Button>
                </div>
            </div>

            <div class="rounded-xl border bg-background p-4">
                <h2 class="mb-3 text-sm font-medium">بازه‌های فعلی</h2>

                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="window in windows"
                        :key="window.id"
                        class="inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 text-xs"
                    >
                        {{ window.weekday_name }}
                        <span dir="ltr">{{ faDigits(window.starts_at) }} تا {{ faDigits(window.ends_at) }}</span>
                    </span>
                </div>

                <p v-if="windows.length === 0" class="py-4 text-center text-sm text-muted-foreground">
                    هنوز بازه‌ای ثبت نشده است.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
