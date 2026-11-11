<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ms-1 grid flex-1 text-start text-sm">
        <span class="mb-0.5 truncate font-semibold leading-none">{{ t('brand') }}</span>
    </div>
</template>
