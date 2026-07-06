import { useEffect, useState } from 'react';

import TransactionForm from '@/components/domain/transaction-form';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import { Transaction } from '@/types';

/**
 * Form transaksi global: bottom sheet (mobile) / dialog 480px (desktop),
 * dibuka FAB dari layar mana pun (05-DESIGN.md 4.5) atau event edit.
 */
export default function TransactionSheet() {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Transaction | undefined>(undefined);
    const isMobile = useIsMobile();

    useEffect(() => {
        const openNew = () => {
            setEditing(undefined);
            setOpen(true);
        };
        const openEdit = (event: Event) => {
            setEditing((event as CustomEvent<Transaction>).detail);
            setOpen(true);
        };

        window.addEventListener('dompetku:new-transaction', openNew);
        window.addEventListener('dompetku:edit-transaction', openEdit);
        return () => {
            window.removeEventListener('dompetku:new-transaction', openNew);
            window.removeEventListener('dompetku:edit-transaction', openEdit);
        };
    }, []);

    const title = editing ? 'Edit transaksi' : 'Catat transaksi';
    const description = editing ? 'Saldo dompet akan terkoreksi otomatis.' : 'Isi nominal, pilih kategori, selesai.';
    const close = () => setOpen(false);

    if (isMobile) {
        return (
            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent side="bottom" className="max-h-[92svh] overflow-y-auto rounded-t-2xl px-4 pb-8">
                    <SheetHeader className="px-0">
                        <SheetTitle>{title}</SheetTitle>
                        <SheetDescription>{description}</SheetDescription>
                    </SheetHeader>
                    {open && <TransactionForm key={editing?.id ?? 'new'} transaction={editing} onSuccess={close} />}
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[480px]">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                {open && <TransactionForm key={editing?.id ?? 'new'} transaction={editing} onSuccess={close} />}
            </DialogContent>
        </Dialog>
    );
}

export function openEditTransaction(transaction: Transaction) {
    window.dispatchEvent(new CustomEvent('dompetku:edit-transaction', { detail: transaction }));
}
