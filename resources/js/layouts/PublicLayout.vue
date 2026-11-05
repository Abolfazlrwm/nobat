<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage<SharedData>();
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-muted/30">
        <header class="border-b bg-background">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <Link :href="route('home')" class="flex items-center gap-2">
                    <AppLogo />
                </Link>

                <nav class="flex items-center gap-2">
                    <template v-if="page.props.auth.user">
                        <Button variant="ghost" as-child>
                            <Link :href="route('dashboard')">داشبورد</Link>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link :href="route('logout')" method="post" as="button">خروج</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button variant="ghost" as-child>
                            <Link :href="route('login')">ورود</Link>
                        </Button>
                        <Button as-child>
                            <Link :href="route('register')">ثبت‌نام</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t bg-background">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 text-xs text-muted-foreground">
                <span>نوبت — سامانه نوبت‌دهی آنلاین</span>
                <span>ساخته‌شده با Laravel و Vue</span>
            </div>
        </footer>
    </div>
</template>
