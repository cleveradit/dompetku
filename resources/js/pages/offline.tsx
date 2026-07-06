import { Head } from '@inertiajs/react';
import { WifiOff } from 'lucide-react';

import { Button } from '@/components/ui/button';

export default function Offline() {
    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6">
            <Head title="Offline" />
            <div className="w-full max-w-[400px] text-center">
                <span className="text-primary text-2xl font-bold tracking-tight">Dompetku</span>
                <div className="bg-card border-border mt-6 rounded-xl border p-8">
                    <div className="bg-secondary text-muted-foreground mx-auto flex h-12 w-12 items-center justify-center rounded-full">
                        <WifiOff className="h-6 w-6" />
                    </div>
                    <h1 className="mt-4 text-xl font-semibold">Kamu sedang offline</h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Dompetku membutuhkan koneksi internet. Sambungkan lagi lalu coba ulang.
                    </p>
                    <Button className="mt-6 w-full" onClick={() => (window.location.href = '/dashboard')}>
                        Coba lagi
                    </Button>
                </div>
            </div>
        </div>
    );
}
