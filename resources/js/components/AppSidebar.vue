<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, Briefcase, Building2, CalendarDays, Clock4, Hourglass, LayoutGrid, MessageSquareText, TicketPercent, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);

const canManage = computed(() => {
    if (!user.value) return false;

    return user.value.is_super_admin || user.value.roles.includes('manager');
});

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'داشبورد', href: '/dashboard', icon: LayoutGrid },
        { title: 'نوبت‌ها', href: '/appointments', icon: CalendarDays },
    ];

    if (canManage.value) {
        items.push(
            { title: 'خدمات', href: '/services', icon: Briefcase },
            { title: 'پرسنل', href: '/staff', icon: Users },
            { title: 'ساعات کاری', href: '/working-hours', icon: Clock4 },
            { title: 'لیست انتظار', href: '/waitlist', icon: Hourglass },
            { title: 'کدهای تخفیف', href: '/discounts', icon: TicketPercent },
            { title: 'نظرات مشتریان', href: '/reviews', icon: MessageSquareText },
            { title: 'گزارش‌ها', href: '/reports', icon: BarChart3 },
        );
    }

    if (user.value?.is_super_admin) {
        items.push({ title: 'کسب‌وکارها', href: '/businesses', icon: Building2 });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" side="right">
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
            <NavMain :items="mainNavItems" label="پنل مدیریت" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
