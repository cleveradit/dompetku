import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

const NAV_ITEMS = [
    { title: 'Profil', url: '/settings/profile' },
    { title: 'Keamanan', url: '/settings/password' },
    { title: 'Tampilan', url: '/settings/appearance' },
    { title: 'Kategori', url: '/categories' },
    { title: 'Transaksi berulang', url: '/recurring' },
    { title: 'Akun', url: '/settings/account' },
];

export default function SettingsLayout({ children }: { children: React.ReactNode }) {
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <div className="py-4">
            <div className="flex flex-col gap-6 lg:flex-row lg:gap-12">
                <aside className="w-full lg:w-48">
                    <nav className="flex gap-1 overflow-x-auto lg:flex-col">
                        {NAV_ITEMS.map((item) => (
                            <Button
                                key={item.url}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('shrink-0 justify-start', {
                                    'bg-secondary': currentPath === item.url,
                                })}
                            >
                                <Link href={item.url} prefetch>
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="lg:hidden" />

                <div className="flex-1">
                    <section className="max-w-xl space-y-10">{children}</section>
                </div>
            </div>
        </div>
    );
}
