<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useLocale } from '@/composables/useLocale';
import { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Github } from 'lucide-vue-next';

const page = usePage<SharedData>();
const { t } = useLocale();

const GITHUB_URL = 'https://github.com/Abolfazlrwm/nobat';
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-muted/30">
        <header class="sticky top-0 z-40 border-b bg-background/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <Link :href="route('home')" class="flex items-center gap-2">
                    <AppLogo />
                </Link>

                <nav class="flex items-center gap-2">
                    <LanguageSwitcher />
                    <template v-if="page.props.auth.user">
                        <Button variant="ghost" as-child>
                            <Link :href="route('dashboard')">{{ t('nav.dashboard') }}</Link>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link :href="route('logout')" method="post" as="button">{{ t('nav.logout') }}</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button variant="ghost" as-child>
                            <Link :href="route('login')">{{ t('nav.login') }}</Link>
                        </Button>
                        <Button as-child>
                            <Link :href="route('register')">{{ t('nav.register') }}</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t bg-background">
            <div class="mx-auto max-w-6xl px-4 py-10">
                <div class="flex flex-col justify-between gap-8 md:flex-row">
                    <div class="max-w-sm space-y-3">
                        <Link :href="route('home')" class="flex items-center gap-2">
                            <AppLogo />
                        </Link>
                        <p class="text-sm leading-7 text-muted-foreground">
                            {{ t('footer.tagline') }}
                        </p>
                    </div>

                    <div class="flex gap-14">
                        <div>
                            <h3 class="text-sm font-semibold">{{ t('footer.system') }}</h3>
                            <ul class="mt-3 space-y-2 text-sm text-muted-foreground">
                                <li>
                                    <Link class="hover:text-foreground" :href="route('login')">{{ t('footer.dashboardLogin') }}</Link>
                                </li>
                                <li>
                                    <Link class="hover:text-foreground" :href="route('register')">{{ t('footer.registerBusiness') }}</Link>
                                </li>
                                <li>
                                    <a class="hover:text-foreground" href="#businesses">{{ t('footer.activeBusinesses') }}</a>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold">{{ t('footer.project') }}</h3>
                            <ul class="mt-3 space-y-2 text-sm text-muted-foreground">
                                <li>
                                    <a class="flex items-center gap-1.5 hover:text-foreground" :href="GITHUB_URL" target="_blank" rel="noopener">
                                        <Github class="h-3.5 w-3.5" />
                                        {{ t('footer.github') }}
                                    </a>
                                </li>
                                <li>
                                    <a
                                        class="hover:text-foreground"
                                        :href="`${GITHUB_URL}/blob/main/docs/DEPLOYMENT.md`"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        {{ t('footer.deployGuide') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="hover:text-foreground" :href="`${GITHUB_URL}/blob/main/CHANGELOG.md`" target="_blank" rel="noopener">
                                        {{ t('footer.changelog') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <Separator class="my-6" />

                <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-muted-foreground">
                    <span>{{ t('footer.rights') }}</span>
                    <span>{{ t('footer.builtWith') }}</span>
                </div>
            </div>
        </footer>
    </div>
</template>
