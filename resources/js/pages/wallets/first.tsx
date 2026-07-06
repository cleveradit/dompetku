import { Head } from '@inertiajs/react';

import WalletForm from '@/components/domain/wallet-form';

/** 05-DESIGN.md 4.2: layar satu tujuan, tanpa navigasi lain yang mengalihkan. */
export default function FirstWallet() {
    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center p-6">
            <Head title="Buat dompet pertama" />
            <div className="w-full max-w-[400px]">
                <span className="text-primary block text-center text-2xl font-bold tracking-tight">Dompetku</span>
                <div className="bg-card border-border mt-6 rounded-xl border p-6 sm:p-8">
                    <div className="mb-6 space-y-1.5 text-center">
                        <h1 className="text-xl font-semibold">Buat dompet pertamamu</h1>
                        <p className="text-muted-foreground text-sm">
                            Dompet adalah tempat uangmu berada: rekening bank, e-wallet, atau uang tunai.
                        </p>
                    </div>
                    <WalletForm submitLabel="Buat dompet" />
                </div>
            </div>
        </div>
    );
}
