<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useLocale, type AppLocale } from '@/composables/useLocale';
import { Check, Languages } from 'lucide-vue-next';
import { computed } from 'vue';

const { t, locale, setLocale } = useLocale();

const options = computed(() => [
    { value: 'fa' as AppLocale, label: t('langSwitch.fa') },
    { value: 'en' as AppLocale, label: t('langSwitch.en') },
    { value: 'ar' as AppLocale, label: t('langSwitch.ar') },
]);

const current = computed(() => (locale.value === 'en' ? 'EN' : locale.value === 'ar' ? 'ع' : 'فا'));
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="gap-1.5 px-2.5" :aria-label="t('langSwitch.label')">
                <Languages class="size-4 text-muted-foreground" />
                <span class="text-xs font-semibold">{{ current }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="min-w-36">
            <DropdownMenuItem
                v-for="option in options"
                :key="option.value"
                class="cursor-pointer gap-2"
                :class="locale === option.value && 'font-semibold text-foreground'"
                @click="setLocale(option.value)"
            >
                <Check class="size-3.5" :class="locale === option.value ? 'opacity-100' : 'opacity-0'" />
                {{ option.label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
