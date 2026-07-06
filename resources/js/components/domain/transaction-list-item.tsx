import { ArrowLeftRight, Repeat } from 'lucide-react';

import AmountText from '@/components/domain/amount-text';
import { iconByName } from '@/lib/icons';
import { Transaction } from '@/types';

interface TransactionListItemProps {
    transaction: Transaction;
    onClick?: () => void;
}

/**
 * Baris transaksi (05-DESIGN.md 4.3): lingkaran ikon kategori 12% opacity,
 * nama + meta, nominal mono di kanan.
 */
export default function TransactionListItem({ transaction, onClick }: TransactionListItemProps) {
    const isTransfer = transaction.type === 'transfer';
    const Icon = isTransfer ? ArrowLeftRight : iconByName(transaction.category?.icon);
    const color = isTransfer ? 'var(--transfer)' : (transaction.category?.color ?? 'var(--primary)');

    const title = isTransfer
        ? `${transaction.wallet?.name ?? '?'} → ${transaction.destination_wallet?.name ?? '?'}`
        : (transaction.category?.name ?? 'Tanpa kategori');

    const meta = [transaction.description, isTransfer ? null : transaction.wallet?.name].filter(Boolean).join(' · ');

    return (
        <button
            type="button"
            onClick={onClick}
            className="hover:bg-secondary/60 flex min-h-14 w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition-colors"
        >
            <span
                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                style={{ backgroundColor: `color-mix(in srgb, ${color} 12%, transparent)`, color }}
                aria-hidden
            >
                <Icon className="h-5 w-5" />
            </span>

            <span className="min-w-0 flex-1">
                <span className="flex items-center gap-1.5">
                    <span className="truncate text-sm font-medium">{title}</span>
                    {transaction.is_recurring && <Repeat className="text-muted-foreground h-3.5 w-3.5 shrink-0" aria-label="Berulang" />}
                </span>
                {meta && <span className="text-muted-foreground block truncate text-xs">{meta}</span>}
            </span>

            <AmountText
                amount={transaction.amount}
                variant={transaction.type}
                className="shrink-0 text-[15px]"
            />
        </button>
    );
}
