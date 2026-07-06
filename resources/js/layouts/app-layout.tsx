import { Link, router, usePage } from '@inertiajs/react';
import { ChartPie, Home, LogOut, Plus, Receipt, Settings, Target, Wallet as WalletIcon } from 'lucide-react';
import { type ReactNode } from 'react';

import TransactionSheet from '@/components/domain/transaction-sheet';
import Toaster from '@/components/toaster';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { SharedData } from '@/types';

interface AppLayoutProps {
    children: ReactNode;
    /** Judul halaman pada header ringkas mobile. */
    title?: string;
    /** Aksi tambahan di kanan header (mis. tombol filter). */
    headerAction?: ReactNode;
}

const NAV_ITEMS = [
    { title: 'Beranda', href: '/dashboard', icon: Home },
    { title: 'Transaksi', href: '/transactions', icon: Receipt },
    { title: 'Laporan', href: '/reports', icon: ChartPie },
    { title: 'Anggaran', href: '/budgets', icon: Target },
] as const;

const SIDEBAR_ITEMS = [
    { title: 'Beranda', href: '/dashboard', icon: Home },
    { title: 'Transaksi', href: '/transactions', icon: Receipt },
    { title: 'Dompet', href: '/wallets', icon: WalletIcon },
    { title: 'Laporan', href: '/reports', icon: ChartPie },
    { title: 'Anggaran', href: '/budgets', icon: Target },
    { title: 'Pengaturan', href: '/settings/profile', icon: Settings },
] as const;

export function openTransactionSheet() {
    window.dispatchEvent(new CustomEvent('dompetku:new-transaction'));
}

/** 05-DESIGN.md §3: bottom nav 4 tab + FAB (mobile), sidebar 240px (>=1280). */
export default function AppLayout({ children, title, headerAction }: AppLayoutProps) {
    const { auth } = usePage<SharedData>().props;
    const getInitials = useInitials();
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    const isActive = (href: string) => currentPath === href || currentPath.startsWith(href + '/');

    const userMenu = (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button className="focus-visible:ring-ring rounded-full focus-visible:ring-2 focus-visible:outline-none" aria-label="Menu akun">
                    <Avatar className="h-9 w-9">
                        <AvatarFallback className="bg-primary/10 text-primary text-sm font-semibold">
                            {getInitials(auth.user?.name ?? '')}
                        </AvatarFallback>
                    </Avatar>
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>
                    <div className="text-sm font-medium">{auth.user?.name}</div>
                    <div className="text-muted-foreground truncate text-xs font-normal">{auth.user?.email}</div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href="/settings/profile" className="w-full cursor-pointer">
                        <Settings className="mr-2 h-4 w-4" />
                        Pengaturan
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem className="cursor-pointer" onClick={() => router.post(route('logout'))}>
                    <LogOut className="mr-2 h-4 w-4" />
                    Keluar
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );

    return (
        <div className="bg-background min-h-svh">
            {/* Sidebar desktop >= 1280px */}
            <aside className="bg-sidebar border-sidebar-border fixed inset-y-0 left-0 z-40 hidden w-60 flex-col border-r xl:flex">
                <div className="flex h-16 items-center px-6">
                    <Link href="/dashboard" className="text-primary text-xl font-bold tracking-tight">
                        Dompetku
                    </Link>
                </div>
                <nav className="flex flex-1 flex-col gap-1 px-3 py-2">
                    {SIDEBAR_ITEMS.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                                isActive(item.href)
                                    ? 'bg-primary/10 text-primary'
                                    : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                            )}
                        >
                            <item.icon className="h-5 w-5" />
                            {item.title}
                        </Link>
                    ))}
                </nav>
            </aside>

            <div className="xl:pl-60">
                {/* Header */}
                <header className="bg-background/80 sticky top-0 z-30 backdrop-blur">
                    <div className="mx-auto flex h-16 max-w-[640px] items-center justify-between gap-3 px-4 xl:max-w-[1040px] xl:px-8">
                        <h1 className="truncate text-xl font-semibold xl:text-2xl">{title ?? 'Dompetku'}</h1>
                        <div className="flex items-center gap-3">
                            {headerAction}
                            <button
                                onClick={openTransactionSheet}
                                className={cn(
                                    'bg-primary text-primary-foreground hidden h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold xl:flex',
                                    'focus-visible:ring-ring transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:outline-none',
                                )}
                            >
                                <Plus className="h-4 w-4" />
                                Catat
                            </button>
                            {userMenu}
                        </div>
                    </div>
                </header>

                {/* Konten: satu kolom, maks 640px di bawah 1280, maks 1040px di desktop */}
                <main className="mx-auto max-w-[640px] px-4 pb-28 xl:max-w-[1040px] xl:px-8 xl:pb-12">{children}</main>
            </div>

            {/* Bottom nav + FAB < 1280px */}
            <nav className="bg-card border-border fixed inset-x-0 bottom-0 z-40 border-t xl:hidden" aria-label="Navigasi utama">
                <div className="mx-auto grid h-16 max-w-[640px] grid-cols-5 items-center">
                    {NAV_ITEMS.slice(0, 2).map((item) => (
                        <BottomNavLink key={item.href} item={item} active={isActive(item.href)} />
                    ))}
                    <div className="relative flex justify-center">
                        <button
                            onClick={openTransactionSheet}
                            aria-label="Catat transaksi"
                            className={cn(
                                'bg-primary text-primary-foreground absolute -top-9 flex h-14 w-14 items-center justify-center rounded-full shadow-lg',
                                'focus-visible:ring-ring transition-opacity hover:opacity-90 focus-visible:ring-2 focus-visible:outline-none',
                            )}
                        >
                            <Plus className="h-7 w-7" />
                        </button>
                    </div>
                    {NAV_ITEMS.slice(2).map((item) => (
                        <BottomNavLink key={item.href} item={item} active={isActive(item.href)} />
                    ))}
                </div>
            </nav>

            <TransactionSheet />
            <Toaster />
        </div>
    );
}

function BottomNavLink({ item, active }: { item: (typeof NAV_ITEMS)[number]; active: boolean }) {
    return (
        <Link
            href={item.href}
            className={cn(
                'flex h-full min-h-11 flex-col items-center justify-center gap-0.5 text-[11px] font-medium transition-colors',
                active ? 'text-primary' : 'text-muted-foreground',
            )}
        >
            <item.icon className="h-5 w-5" />
            {item.title}
        </Link>
    );
}
