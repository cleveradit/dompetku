import { Head, Link, router } from '@inertiajs/react';
import { useRef, useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

interface ImportResult {
    imported: number;
    failed: { line: number; reason: string }[];
}

interface DataSettingsProps {
    lastImportResult?: ImportResult | null;
}

export default function DataSettings({ lastImportResult }: DataSettingsProps) {
    const [importing, setImporting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const onFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        e.target.value = '';
        if (!file) return;

        setImporting(true);
        router.post(
            route('imports.store'),
            { file },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => setImporting(false),
            },
        );
    };

    return (
        <AppLayout title="Pengaturan">
            <Head title="Data" />

            <SettingsLayout>
                <div className="space-y-10">
                    <div className="space-y-6">
                        <HeadingSmall title="Export" description="Unduh transaksi sesuai filter yang aktif" />
                        <p className="text-muted-foreground text-sm">
                            Export tersedia di halaman Transaksi saat filter aktif.{' '}
                            <Link href={route('transactions.index')} className="text-primary underline underline-offset-4">
                                Buka halaman Transaksi
                            </Link>
                        </p>
                    </div>

                    <div className="space-y-6">
                        <HeadingSmall title="Import" description="Impor transaksi pemasukan/pengeluaran dari file CSV" />

                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" asChild>
                                <a href={route('imports.template')}>Unduh template CSV</a>
                            </Button>
                            <Button type="button" disabled={importing} onClick={() => fileInputRef.current?.click()}>
                                {importing ? 'Mengimpor...' : 'Import transaksi'}
                            </Button>
                            <input ref={fileInputRef} type="file" accept=".csv,text/csv" hidden onChange={onFileChange} />
                        </div>
                        <p className="text-muted-foreground text-xs">
                            Gunakan template di atas. Kategori dan dompet dicocokkan berdasarkan nama; transfer tidak didukung. Ukuran file
                            maksimal 2 MB.
                        </p>

                        {lastImportResult && (
                            <div className="border-border bg-card rounded-xl border p-4">
                                <p className="text-sm font-medium">{lastImportResult.imported} transaksi ter-import</p>

                                {lastImportResult.failed.length > 0 && (
                                    <div className="mt-3 space-y-1">
                                        <p className="text-warning text-sm font-medium">{lastImportResult.failed.length} baris gagal</p>
                                        <ul className="text-destructive space-y-1 text-xs">
                                            {lastImportResult.failed.map((failure) => (
                                                <li key={failure.line}>
                                                    Baris {failure.line}: {failure.reason}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
