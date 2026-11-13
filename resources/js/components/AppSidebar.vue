<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useLocale } from '@/composables/useLocale';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Briefcase,
    Building2,
    CalendarDays,
    Clock4,
    Hourglass,
    Images,
    KeyRound,
    LayoutGrid,
    MessageSquareText,
    TicketPercent,
    TrendingUp,
    Users,
    UsersRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const { t, isEn } = useLocale();

const canManage = computed(() => {
    if (!user.value) return false;

    return user.value.is_super_admin || user.value.roles.includes('manager');
});

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: t('panel.nav.dashboard'), href: '/dashboard', icon: LayoutGrid },
        { title: t('panel.nav.appointments'), href: '/appointments', icon: CalendarDays },
    ];

    if (canManage.value) {
        items.push(
            { title: t('panel.nav.services'), href: '/services', icon: Briefcase },
            { title: t('panel.nav.staff'), href: '/staff', icon: Users },
            { title: t('panel.nav.workingHours'), href: '/working-hours', icon: Clock4 },
            { title: t('panel.nav.gallery'), href: '/gallery', icon: Images },
            { title: t('panel.nav.waitlist'), href: '/waitlist', icon: Hourglass },
            { title: t('panel.nav.discounts'), href: '/discounts', icon: TicketPercent },
            { title: t('panel.nav.reviews'), href: '/reviews', icon: MessageSquareText },
            { title: t('panel.nav.customers'), href: '/customers', icon: UsersRound },
            { title: t('panel.nav.analytics'), href: '/analytics', icon: TrendingUp },
            { title: t('panel.nav.apiTokens'), href: '/api-tokens', icon: KeyRound },
            { title: t('panel.nav.reports'), href: '/reports', icon: BarChart3 },
        );
    }

    if (user.value?.is_super_admin) {
        items.push({ title: t('panel.nav.businesses'), href: '/businesses', icon: Building2 });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" :side="isEn ? 'left' : 'right'">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('home')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" :label="t('panel.nav.managePanel')" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
