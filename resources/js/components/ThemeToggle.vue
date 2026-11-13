<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { useLocale } from '@/composables/useLocale';
import { Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

const { appearance, updateAppearance } = useAppearance();
const { t } = useLocale();

const isDark = computed(() =>
    appearance.value === 'dark'
    || (appearance.value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches),
);

function toggle(): void {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <Button variant="ghost" size="icon" class="size-8" :aria-label="t('nav.themeToggle')" :title="t('nav.themeToggle')" @click="toggle">
        <Sun v-if="isDark" class="size-4" />
        <Moon v-else class="size-4" />
    </Button>
</template>
