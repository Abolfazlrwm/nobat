<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';
import { BookOpen, Download, KeyRound } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    // The OpenAPI structure straight from App\Support\ApiSpec — the page
    // is a renderer, never a second source of truth.
    spec: Record<string, any>;
}>();

const { t, num } = useLocale();

const paths = computed(() => Object.entries(props.spec.paths ?? {}));
const serverUrl = computed(() => props.spec.servers?.[0]?.url ?? '');
const info = computed(() => props.spec.info ?? {});

const methodStyles: Record<string, string> = {
    get: 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300',
    post: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
    delete: 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300',
};

function entriesOf(value: any): [string, any][] {
    return Object.entries(value ?? {});
}
</script>

<template>
    <Head :title="t('landing.apiDocsTitle')">
        <meta name="description" :content="t('landing.apiDocsSubtitle')">
    </Head>

    <PublicLayout>
        <section class="mx-auto max-w-4xl px-4 py-12">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <BookOpen class="size-5" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ t('landing.apiDocsTitle') }}</h1>
                        <Badge variant="secondary" class="mt-1 font-mono text-xs">
                            OpenAPI {{ spec.openapi }}
                            · v{{ num(info.version) }}
                        </Badge>
                    </div>
                </div>
                <Button as-child variant="outline" class="gap-2">
                    <a :href="route('docs.spec')" download="openapi.json">
                        <Download class="size-4" />
                        {{ t('landing.apiDocsDownload') }}
                    </a>
                </Button>
            </div>

            <p class="mt-4 leading-7 text-muted-foreground">{{ info.description }}</p>
            <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ t('landing.apiDocsSubtitle') }}</p>

            <!-- auth -->
            <Card class="mt-8">
                <CardHeader class="flex-row items-center gap-3 space-y-0">
                    <KeyRound class="size-4 text-primary" />
                    <CardTitle class="text-base">{{ t('landing.apiDocsAuth') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm leading-7 text-muted-foreground">{{ t('landing.apiDocsAuthDesc') }}</p>
                    <p class="mt-3 font-mono text-xs" dir="ltr">
                        Authorization: Bearer &lt;token&gt;
                    </p>
                </CardContent>
            </Card>

            <!-- base url -->
            <p class="mt-6 text-sm text-muted-foreground">
                {{ t('landing.apiDocsBaseUrl') }}:
                <code dir="ltr" class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">{{ serverUrl }}</code>
            </p>

            <!-- endpoints -->
            <div class="mt-10 space-y-6">
                <Card v-for="[path, pathItem] in paths" :key="path">
                    <CardHeader>
                        <CardTitle class="font-mono text-sm" dir="ltr">{{ path }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div v-for="[method, op] in entriesOf(pathItem)" :key="method" class="border-t pt-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded px-2 py-0.5 font-mono text-xs font-bold uppercase"
                                    :class="methodStyles[method] ?? 'bg-muted text-foreground'"
                                    dir="ltr"
                                >
                                    {{ method }}
                                </span>
                                <span class="text-sm font-semibold">{{ op.summary }}</span>
                            </div>

                            <p v-if="op.description" class="mt-2 text-sm leading-7 text-muted-foreground">{{ op.description }}</p>

                            <!-- parameters -->
                            <div v-if="op.parameters?.length" class="mt-3 overflow-x-auto rounded-lg border">
                                <table class="w-full text-xs">
                                    <thead class="bg-muted/50 text-start text-muted-foreground">
                                        <tr>
                                            <th class="px-3 py-2 font-medium">name</th>
                                            <th class="px-3 py-2 font-medium">in</th>
                                            <th class="px-3 py-2 font-medium">description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="param in op.parameters" :key="param.name" class="border-t">
                                            <td class="px-3 py-2 font-mono" dir="ltr">{{ param.name }}</td>
                                            <td class="px-3 py-2 text-muted-foreground" dir="ltr">{{ param.in }}</td>
                                            <td class="px-3 py-2 text-muted-foreground">{{ param.description ?? '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- curl example -->
                            <pre
                                v-if="op['x-curl']"
                                dir="ltr"
                                class="mt-3 overflow-x-auto rounded-lg border bg-muted/40 p-3 font-mono text-xs leading-6"
                            >{{ op['x-curl'] }}</pre>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </section>
    </PublicLayout>
</template>
