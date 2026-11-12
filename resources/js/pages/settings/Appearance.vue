<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { useLocale } from '@/composables/useLocale';
import { type BreadcrumbItem } from '@/types';
import { computed } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const { t } = useLocale();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: t('panel.settings.appearanceBreadcrumb'),
        href: '/settings/appearance',
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('panel.settings.appearancePageTitle')" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall :title="t('panel.settings.appearanceTitle')" :description="t('panel.settings.appearanceDesc')" />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
