import { Head, router } from '@inertiajs/react';
import { Archive, ArchiveRestore, MoreVertical, Pencil, Plus, Trash2, Wallet as WalletIcon } from 'lucide-react';
import { useState } from 'react';

import AmountText from '@/components/domain/amount-text';
import EmptyState from '@/components/domain/empty-state';
import WalletForm from '@/components/domain/wallet-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { iconByName } from '@/lib/icons';
import AppLayout from '@/layouts/app-layout';
import { Wallet } from '@/types';

interface WalletsPageProps {
    wallets: Wallet[];
}

export default function WalletsIndex({ wallets }: WalletsPageProps) {
    const [creating, setCreating] = useState(false);
    const [editing, setEditing] = useState<Wallet | null>(null);
    const [deleting, setDeleting] = useState<Wallet | null>(null);

    const confirmDelete = () => {
        if (!deleting) return;
        router.delete(route('wallets.destroy', deleting.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    const toggleArchive = (wallet: Wallet) => {
        router.post(route('wallets.archive', wallet.id), { archived: !wallet.is_archived }, { preserveScroll: true });
    };

    return (
        <AppLayout
            title="Dompet"
            headerAction={
                <Button size="sm" variant="outline" onClick={() => setCreating(true)}>
                    <Plus className="h-4 w-4" />
                    Dompet baru
                </Button>
            }
        >
            <Head title="Dompet" />

            {wallets.length === 0 ? (
                <EmptyState
                    icon={WalletIcon}
                    message="Belum ada dompet."
                    action={<Button onClick={() => setCreating(true)}>Buat dompet</Button>}
                />
            ) : (
                <div className="grid grid-cols-1 gap-4 py-2 md:grid-cols-2 xl:grid-cols-3">
                    {wallets.map((wallet) => {
                        const Icon = iconByName(wallet.icon);
                        return (
                            <div
                                key={wallet.id}
                                className="bg-card border-border relative overflow-hidden rounded-xl border p-4 shadow-[0_1px_2px_rgb(0_0_0_/_.05)] dark:shadow-none"
                            >
                                <div
                                    className="absolute inset-y-0 left-0 w-1.5"
                                    style={{ backgroundColor: wallet.color ?? 'var(--primary)' }}
                                    aria-hidden
                                />
                                <div className="flex items-start justify-between pl-2">
                                    <div className="flex items-center gap-3">
                                        <div
                                            className="flex h-10 w-10 items-center justify-center rounded-full"
                                            style={{ backgroundColor: `${wallet.color ?? '#0B6B4F'}1F`, color: wallet.color ?? 'var(--primary)' }}
                                        >
                                            <Icon className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-semibold">{wallet.name}</span>
                                                {wallet.is_archived && (
                                                    <Badge variant="secondary" className="text-xs">
                                                        Diarsipkan
                                                    </Badge>
                                                )}
                                            </div>
                                            <span className="text-muted-foreground text-xs">{wallet.type_label}</span>
                                        </div>
                                    </div>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <button
                                                className="text-muted-foreground hover:bg-secondary flex h-9 w-9 items-center justify-center rounded-lg"
                                                aria-label={`Aksi untuk ${wallet.name}`}
                                            >
                                                <MoreVertical className="h-4 w-4" />
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem onClick={() => setEditing(wallet)}>
                                                <Pencil className="mr-2 h-4 w-4" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem onClick={() => toggleArchive(wallet)}>
                                                {wallet.is_archived ? (
                                                    <>
                                                        <ArchiveRestore className="mr-2 h-4 w-4" />
                                                        Aktifkan lagi
                                                    </>
                                                ) : (
                                                    <>
                                                        <Archive className="mr-2 h-4 w-4" />
                                                        Arsipkan
                                                    </>
                                                )}
                                            </DropdownMenuItem>
                                            {wallet.deletable ? (
                                                <DropdownMenuItem className="text-destructive" onClick={() => setDeleting(wallet)}>
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    Hapus
                                                </DropdownMenuItem>
                                            ) : (
                                                <DropdownMenuItem disabled className="max-w-56 text-xs whitespace-normal">
                                                    Punya transaksi, arsipkan saja
                                                </DropdownMenuItem>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>

                                <div className="mt-4 pl-2">
                                    <AmountText amount={wallet.current_balance} variant="balance" className="text-2xl" />
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Dialog buat dompet */}
            <Dialog open={creating} onOpenChange={setCreating}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>Dompet baru</DialogTitle>
                        <DialogDescription>Tambahkan tempat uangmu berada.</DialogDescription>
                    </DialogHeader>
                    <WalletForm submitLabel="Buat dompet" onSuccess={() => setCreating(false)} />
                </DialogContent>
            </Dialog>

            {/* Dialog edit dompet */}
            <Dialog open={editing !== null} onOpenChange={(open) => !open && setEditing(null)}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>Edit dompet</DialogTitle>
                        <DialogDescription>Mengubah dompet tidak mempengaruhi saldo dan transaksinya.</DialogDescription>
                    </DialogHeader>
                    {editing && <WalletForm wallet={editing} submitLabel="Simpan" onSuccess={() => setEditing(null)} />}
                </DialogContent>
            </Dialog>

            {/* Konfirmasi hapus (05-DESIGN.md §5: konsekuensi eksplisit) */}
            <Dialog open={deleting !== null} onOpenChange={(open) => !open && setDeleting(null)}>
                <DialogContent className="sm:max-w-[420px]">
                    <DialogHeader>
                        <DialogTitle>Hapus dompet {deleting?.name}?</DialogTitle>
                        <DialogDescription>
                            Dompet ini belum punya transaksi, jadi bisa dihapus. Aksi ini tidak bisa dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            Hapus dompet
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
