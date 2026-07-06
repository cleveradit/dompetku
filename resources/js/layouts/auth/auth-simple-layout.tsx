import { Link } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
}

/** 05-DESIGN.md 4.1: kartu tunggal terpusat maks 400px, wordmark "Dompetku". */
export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div className="w-full max-w-[400px]">
                <div className="flex flex-col gap-6">
                    <Link href={route('home')} className="flex items-center justify-center gap-2 font-semibold">
                        <span className="text-primary text-2xl font-bold tracking-tight">Dompetku</span>
                    </Link>

                    <div className="bg-card border-border rounded-xl border p-6 shadow-[0_1px_2px_rgb(0_0_0_/_.05)] sm:p-8 dark:shadow-none">
                        <div className="mb-6 space-y-1.5 text-center">
                            <h1 className="text-xl font-semibold">{title}</h1>
                            <p className="text-muted-foreground text-sm">{description}</p>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
