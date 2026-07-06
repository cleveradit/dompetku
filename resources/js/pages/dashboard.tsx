import { Head, Link } from '@inertiajs/react';
import { Receipt } from 'lucide-react';
import { useState } from 'react';

import AmountText from '@/components/domain/amount-text';
import EmptyState from '@/components/domain/empty-state';
import TransactionListItem from '@/components/domain/transaction-list-item';
import { openEditTransaction } from '@/components/domain/transaction-sheet';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import AppLayout, { openTransactionSheet } from '@/layouts/app-layout';
import { formatDate } from '@/lib/date';
import { formatMoneyCompact } from '@/lib/money';
import { iconByName } from '@/lib/icons';
import { useCurrency } from '@/hooks/use-currency';
import { cn } from '@/lib/utils';
import { Transaction, Wallet } from '@/types';

interface BudgetProgress {
    id: number;
    category_name: string;
    category_color: string | null;
    amount: string;
    spent: string;
    percent: number;
    status: 'ok' | 'warning' | 'danger';
}

interface DashboardProps {
    summary: {
        total_balance: string;
        wallets: Wallet[];
        month: { income: string; expense: string; net: string; label: string };
        recent_transactions: Transaction[];
        top_budgets: BudgetProgress[];
    };
}

export default function Dashboard({ summary }: DashboardProps) {
    const currency = useCurrency();
    const [detail, setDetail] = useState<Transaction | null>(null);
    const netIsPositive = !summary.month.net.startsWith('-') && summary.month.net !== '0.00';

    return (
        <AppLayout title="Dompetku">
            <Head title="Beranda" />

            <div className="flex flex-col gap-6 pt-2 pb-6">
                {/* Total saldo */}
                <section>
                    <p className="text-muted-foreground text-sm">Total saldo</p>
                    <AmountText amount={summary.total_balance} variant="balance" className="text-[32px] xl:text-[40px]" />
                    {summary.month.net !== '0.00' && (
                        <p className={cn('text-sm font-medium', netIsPositive ? 'text-income' : 'text-expense')}>
                            {formatMoneyCompact(summary.month.net, currency)} bulan ini
                        </p>
                    )}
                </section>

                {/* Kartu dompet */}
                <section>
                    <header className="flex items-center justify-between pb-2">
                        <h2 className="font-semibold">Dompet</h2>
                        <Link href={route('wallets.index')} className="text-primary text-sm font-medium">
                            Kelola
                        </Link>
                    </header>
                    <div className="scrollbar-none -mx-4 flex gap-3 overflow-x-auto px-4 pb-1 xl:mx-0 xl:grid xl:grid-cols-3 xl:overflow-visible xl:px-0">
                        {summary.wallets.map((wallet) => {
                            const Icon = iconByName(wallet.icon);
                            return (
                                <div
                                    key={wallet.id}
                                    className="bg-card border-border relative w-44 shrink-0 overflow-hidden rounded-xl border p-4 xl:w-auto"
                                >
                                    <div
                                        className="absolute inset-y-0 left-0 w-1.5"
                                        style={{ backgroundColor: wallet.color ?? 'var(--primary)' }}
                                        aria-hidden
                                    />
                                    <div className="flex items-center gap-2 pl-2">
                                        <Icon className="h-4 w-4" style={{ color: wallet.color ?? 'var(--primary)' }} />
                                        <span className="truncate text-sm font-medium">{wallet.name}</span>
                                    </div>
                                    <div className="mt-2 pl-2">
                                        <AmountText amount={wallet.current_balance} variant="balance" className="text-lg" />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>

                {/* Bulan ini */}
                <section className="bg-card border-border rounded-xl border p-4">
                    <h2 className="pb-3 font-semibold">Bulan ini · {summary.month.label}</h2>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <p className="text-muted-foreground text-xs">Masuk</p>
                            <AmountText amount={summary.month.income} variant="income" className="text-[15px]" />
                        </div>
                        <div>
                            <p className="text-muted-foreground text-xs">Keluar</p>
                            <AmountText amount={summary.month.expense} variant="expense" className="text-[15px]" />
                        </div>
                    </div>

                    {summary.top_budgets.length > 0 && (
                        <div className="mt-4 flex flex-col gap-3">
                            <p className="text-muted-foreground text-xs font-medium">Anggaran teratas</p>
                            {summary.top_budgets.map((budget) => (
                                <div key={budget.id}>
                                    <div className="flex items-baseline justify-between text-xs">
                                        <span className="font-medium">{budget.category_name}</span>
                                        <span className="text-muted-foreground">{Math.round(budget.percent)}%</span>
                                    </div>
                                    <div className="bg-secondary mt-1 h-2 overflow-hidden rounded-full">
                                        <div
                                            className={cn('h-full rounded-full', {
                                                'bg-warning': budget.status === 'warning',
                                                'bg-danger': budget.status === 'danger',
                                            })}
                                            style={{
                                                width: `${Math.min(100, budget.percent)}%`,
                                                backgroundColor:
                                                    budget.status === 'ok' ? (budget.category_color ?? 'var(--primary)') : undefined,
                                            }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                {/* Transaksi terakhir */}
                <section>
                    <header className="flex items-center justify-between pb-2">
                        <h2 className="font-semibold">Transaksi terakhir</h2>
                        <Link href={route('transactions.index')} className="text-primary text-sm font-medium">
                            Semua
                        </Link>
                    </header>
                    {summary.recent_transactions.length === 0 ? (
                        <EmptyState
                            icon={Receipt}
                            message="Belum ada transaksi bulan ini."
                            action={<Button onClick={openTransactionSheet}>Catat transaksi</Button>}
                        />
                    ) : (
                        <div className="bg-card border-border divide-border divide-y rounded-xl border px-2">
                            {summary.recent_transactions.map((transaction) => (
                                <TransactionListItem key={transaction.id} transaction={transaction} onClick={() => setDetail(transaction)} />
                            ))}
                        </div>
                    )}
                </section>
            </div>

            {/* Detail ringkas dengan aksi edit */}
            <Sheet open={detail !== null} onOpenChange={(open) => !open && setDetail(null)}>
                <SheetContent side="bottom" className="mx-auto max-w-[640px] rounded-t-2xl px-4 pb-8">
                    {detail && (
                        <>
                            <SheetHeader className="px-0">
                                <SheetTitle>{detail.type === 'transfer' ? 'Transfer' : (detail.category?.name ?? 'Transaksi')}</SheetTitle>
                            </SheetHeader>
                            <div className="flex flex-col gap-3">
                                <AmountText amount={detail.amount} variant={detail.type} className="text-[32px]" hideZeroDecimals={false} />
                                <p className="text-muted-foreground text-sm">
                                    {formatDate(detail.occurred_on)}
                                    {detail.description ? ` · ${detail.description}` : ''}
                                </p>
                                <div className="grid grid-cols-2 gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            setDetail(null);
                                            openEditTransaction(detail);
                                        }}
                                    >
                                        Edit
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <Link href={route('transactions.index')}>Lihat semua</Link>
                                    </Button>
                                </div>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </AppLayout>
    );
}
