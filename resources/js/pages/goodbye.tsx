import { Head, Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';

export default function Goodbye() {
    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6">
            <Head title="Sampai jumpa" />
            <div className="w-full max-w-[400px] text-center">
                <span className="text-primary text-2xl font-bold tracking-tight">Dompetku</span>
                <div className="bg-card border-border mt-6 rounded-xl border p-8">
                    <h1 className="text-xl font-semibold">Akunmu sudah dihapus</h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Seluruh datamu sudah dihapus permanen. Terima kasih sudah memakai Dompetku.
                    </p>
                    <Button asChild className="mt-6 w-full">
                        <Link href={route('register')}>Buat akun baru</Link>
                    </Button>
                </div>
            </div>
        </div>
    );
}
