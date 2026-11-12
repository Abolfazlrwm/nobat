<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, WeekdayOption, WindowItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, reactive } from 'vue';

const props = defineProps<{
    business: { id: number; name: string };
    windows: WindowItem[];
    weekdays: WeekdayOption[];
}>();

const { t, num, isFa } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.workingHours.title'), href: '/working-hours' },
]);

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

/** The server sends Persian weekday names; overlay the localized label. */
const weekdayLabel = (value: number, fallback: string): string => (isFa.value ? fallback : t(`panel.weekdays.${value}`));

function addWindow(): void {
    drafts.push({ weekday: '0', starts_at: '09:00', ends_at: '17:00' });
}

function removeWindow(index: number): void {
    drafts.splice(index, 1);
}

function save(): void {
    router.post(route('working-hours.store'), { windows: drafts }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('panel.workingHours.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold">{{ t('panel.workingHours.title') }}</h1>
                    <p class="text-sm text-muted-foreground">{{ t('panel.workingHours.subtitle', { name: business.name }) }}</p>
                </div>

                <Button variant="outline" @click="addWindow">
                    <Plus class="ml-2 size-4" />
                    {{ t('panel.workingHours.add') }}
                </Button>
            </div>

            <div class="rounded-xl border bg-background p-4">
                <div v-for="(draft, index) in drafts" :key="index" class="mb-3 flex items-center gap-3">
                    <select v-model="draft.weekday" class="h-9 rounded-md border border-input bg-background px-3 text-sm">
                        <option v-for="weekday in weekdays" :key="weekday.value" :value="String(weekday.value)">
                            {{ weekdayLabel(weekday.value, weekday.label) }}
                        </option>
                    </select>

                    <input v-model="draft.starts_at" type="time" dir="ltr" class="h-9 rounded-md border border-input bg-background px-3 text-sm" />

                    <span class="text-sm text-muted-foreground">{{ t('panel.common.till') }}</span>

                    <input v-model="draft.ends_at" type="time" dir="ltr" class="h-9 rounded-md border border-input bg-background px-3 text-sm" />

                    <Button variant="ghost" size="icon" @click="removeWindow(index)">
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </div>

                <p v-if="drafts.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                    {{ t('panel.workingHours.emptyDraft') }}
                </p>

                <div class="mt-4 flex justify-end">
                    <Button :disabled="drafts.length === 0" @click="save">{{ t('panel.workingHours.save') }}</Button>
                </div>
            </div>

            <div class="rounded-xl border bg-background p-4">
                <h2 class="mb-3 text-sm font-medium">{{ t('panel.workingHours.currentWindows') }}</h2>

                <div class="flex flex-wrap gap-2">
                    <span v-for="window in windows" :key="window.id" class="inline-flex items-center gap-2 rounded-full bg-muted px-3 py-1 text-xs">
                        {{ weekdayLabel(window.weekday, window.weekday_name) }}
                        <span dir="ltr">{{ num(window.starts_at) }} {{ t('panel.common.till') }} {{ num(window.ends_at) }}</span>
                    </span>
                </div>

                <p v-if="windows.length === 0" class="py-4 text-center text-sm text-muted-foreground">{{ t('panel.workingHours.empty') }}</p>
            </div>
        </div>
    </AppLayout>
</template>
