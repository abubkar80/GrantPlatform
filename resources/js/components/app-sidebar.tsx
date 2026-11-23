import { Link, usePage } from '@inertiajs/react';
import {
    ClipboardList,
    FileText,
    LayoutGrid,
    UserCheck,
    UserRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const dashboardItem: NavItem = {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutGrid,
};

/** Navigation per role. Add a page here only once its route exists. */
function navFor(role: string): NavItem[] {
    switch (role) {
        case 'admin':
            return [
                dashboardItem,
                {
                    title: 'Applications',
                    href: '/admin/applications',
                    icon: ClipboardList,
                },
                {
                    title: 'Donor approvals',
                    href: '/admin/donors',
                    icon: UserCheck,
                },
            ];
        case 'student':
            return [
                dashboardItem,
                {
                    title: 'My profile',
                    href: '/student/profile',
                    icon: UserRound,
                },
                {
                    title: 'My application',
                    href: '/student/application',
                    icon: FileText,
                },
            ];
        default:
            return [dashboardItem];
    }
}

export function AppSidebar() {
    const { auth } = usePage().props;
    const mainNavItems = navFor(auth.role);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
