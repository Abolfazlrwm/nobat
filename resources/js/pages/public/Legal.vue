<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';

const props = defineProps<{
    doc: 'privacy' | 'terms';
    title: string;
    updated: string;
    sections: { heading: string; body: string[] }[];
}>();

const { t } = useLocale();
</script>

<template>
    <Head :title="props.title" />

    <PublicLayout>
        <section class="mx-auto max-w-3xl px-4 py-12">
            <Card>
                <CardHeader class="items-center text-center">
                    <ShieldCheck class="mx-auto size-10 text-emerald-500" />
                    <CardTitle class="mt-2 text-xl">{{ props.title }}</CardTitle>
                    <CardDescription>{{ props.updated }}</CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <section
                        v-for="(section, i) in props.sections"
                        :key="i"
                        class="space-y-2"
                    >
                        <h2 class="text-base font-semibold">{{ section.heading }}</h2>
                        <p
                            v-for="(paragraph, j) in section.body"
                            :key="j"
                            class="text-sm leading-7 text-muted-foreground"
                        >
                            {{ paragraph }}
                        </p>
                    </section>

                    <p class="border-t pt-4 text-center text-xs text-muted-foreground">
                        {{ t('legal.footerNote') }}
                        <a class="underline-offset-4 hover:underline" :href="route(props.doc === 'privacy' ? 'legal.terms' : 'legal.privacy')">
                            {{ t(props.doc === 'privacy' ? 'legal.termsLink' : 'legal.privacyLink') }}
                        </a>
                    </p>
                </CardContent>
            </Card>
        </section>
    </PublicLayout>
</template>
