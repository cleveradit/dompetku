import { Head, Link, router } from '@inertiajs/react';
import { ChartPie, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

import AmountText from '@/components/domain/amount-text';
import EmptyState from '@/components/domain/empty-state';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import { iconByName } from '@/lib/icons';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';
import { Wallet } from '@/types';

interface CategoryBreakdown {
    id: number | null;
    name: string;
    color: string | null;
    icon: string | null;
    amount: string;
    percent: number;
}

interface WalletBreakdown {
    id: number;
    name: string;
    color: string | null;
    expense: string;
    income: string;
}

interface ReportsProps {
    interval: string;
    period: { start: string; end: string; label: string };
    navigation: { prev_anchor: string; next_anchor: string; can_go_next: boolean };
    walletId: number | null;
    wallets: Wallet[];
    totals: { income: string; expense: string; net: string };
    trend: { key: string; expense: string; income: string }[];
    categories: CategoryBreakdown[];
    incomeCategories: CategoryBreakdown[];
    walletBreakdown: WalletBreakdown[];
}

const INTERVALS = [
    { value: 'daily', label: 'Harian' },
    { value: 'weekly', label: 'Mingguan' },
    { value: 'monthly', label: 'Bulanan' },
    { value: 'yearly', label: 'Tahunan' },
    { value: 'custom', label: 'Custom' },
];

export default function ReportsIndex(props: ReportsProps) {
    const { interval, period, navigation, walletId, wallets, totals, trend, categories, incomeCategories, walletBreakdown } = props;
    const currency = useCurrency();
    const [breakdownTab, setBreakdownTab] = useState<'expense' | 'income'>('expense');
    const [customStart, setCustomStart] = useState(period.start);
    const [customEnd, setCustomEnd] = useState(period.end);

    const visit = (params: Record<string, unknown>) => {
        router.get(route('reports.index'), { interval, wallet: walletId ?? undefined, ...params }, { preserveState: true, preserveScroll: true });
    };

    const isEmpty = totals.income === '0.00' && totals.expense === '0.00';
    const activeBreakdown = breakdownTab === 'expense' ? categories : incomeCategories;

    const chartData = trend.map((point) => ({
        key: point.key,
        label: interval === 'yearly' ? point.key.slice(5) : point.key.slice(8),
        value: Number(point.expense),
        raw: point.expense,
    }));

    return (
        <AppLayout title="Laporan">
            <Head title="Laporan" />

            <div className="flex flex-col gap-4 pt-2 pb-6">
                {/* Pemilih interval */}
                <div className="bg-secondary flex gap-1 overflow-x-auto rounded-lg p-1">
                    {INTERVALS.map((item) => (
                        <button
                            key={item.value}
                            onClick={() => (item.value === 'custom' ? visit({ interval: 'custom', start: period.start, end: period.end }) : visit({ interval: item.value, anchor: undefined, start: undefined, end: undefined }))}
                            className={cn(
                                'min-h-9 shrink-0 rounded-md px-3 text-sm font-medium transition-colors',
                                interval === item.value ? 'bg-card text-foreground shadow-xs' : 'text-muted-foreground',
                            )}
                        >
                            {item.label}
                        </button>
                    ))}
                </div>

                {/* Navigasi periode */}
                {interval !== 'custom' ? (
                    <div className="flex items-center justify-between gap-2">
                        <Button variant="outline" size="icon" onClick={() => visit({ anchor: navigation.prev_anchor })} aria-label="Periode sebelumnya">
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <span className="text-sm font-semibold">{period.label}</span>
                        <Button
                            variant="outline"
                            size="icon"
                            disabled={!navigation.can_go_next}
                            onClick={() => visit({ anchor: navigation.next_anchor })}
                            aria-label="Periode berikutnya"
                        >
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                    </div>
                ) : (
                    <div className="flex flex-wrap items-end gap-2">
                        <div className="grid flex-1 gap-1">
                            <Label htmlFor="custom-start" className="text-xs">
                                Dari
                            </Label>
                            <Input id="custom-start" type="date" value={customStart} onChange={(e) => setCustomStart(e.target.value)} />
                        </div>
                        <div className="grid flex-1 gap-1">
                            <Label htmlFor="custom-end" className="text-xs">
                                Sampai
                            </Label>
                            <Input id="custom-end" type="date" value={customEnd} onChange={(e) => setCustomEnd(e.target.value)} />
                        </div>
                        <Button onClick={() => visit({ start: customStart, end: customEnd })}>Terapkan</Button>
                    </div>
                )}

                {/* Filter dompet */}
                <Select
                    value={walletId !== null ? String(walletId) : 'all'}
                    onValueChange={(value) =>
                        visit({
                            wallet: value === 'all' ? undefined : Number(value),
                            anchor: interval === 'custom' ? undefined : period.start,
                            start: interval === 'custom' ? period.start : undefined,
                            end: interval === 'custom' ? period.end : undefined,
                        })
                    }
                >
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Semua dompet" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua dompet</SelectItem>
                        {wallets.map((wallet) => (
                            <SelectItem key={wallet.id} value={String(wallet.id)}>
                                {wallet.name}
                                {wallet.is_archived ? ' (diarsipkan)' : ''}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {/* Tiga angka ringkas */}
                <div className="grid grid-cols-3 gap-3">
                    <div className="bg-card border-border rounded-xl border p-3">
                        <p className="text-muted-foreground text-xs">Masuk</p>
                        <AmountText amount={totals.income} variant="income" className="text-sm sm:text-[15px]" />
                    </div>
                    <div className="bg-card border-border rounded-xl border p-3">
                        <p className="text-muted-foreground text-xs">Keluar</p>
                        <AmountText amount={totals.expense} variant="expense" className="text-sm sm:text-[15px]" />
                    </div>
                    <div className="bg-card border-border rounded-xl border p-3">
                        <p className="text-muted-foreground text-xs">Selisih</p>
                        <AmountText amount={totals.net} variant="balance" className="text-sm sm:text-[15px]" />
                    </div>
                </div>

                {isEmpty ? (
                    <EmptyState
                        icon={ChartPie}
                        message="Belum ada transaksi pada periode ini."
                        action={
                            <Button variant="outline" onClick={() => visit({ anchor: navigation.prev_anchor })}>
                                Lihat periode sebelumnya
                            </Button>
                        }
                    />
                ) : (
                    <>
                        {/* Grafik tren pengeluaran */}
                        <section className="bg-card border-border rounded-xl border p-4">
                            <h2 className="pb-3 text-sm font-semibold">
                                Tren pengeluaran {interval === 'yearly' ? 'per bulan' : 'per hari'}
                            </h2>
                            <div className="h-48">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={chartData} margin={{ top: 4, right: 4, bottom: 0, left: 4 }}>
                                        <CartesianGrid vertical={false} stroke="var(--border)" strokeDasharray="3 3" />
                                        <XAxis
                                            dataKey="label"
                                            tickLine={false}
                                            axisLine={false}
                                            tick={{ fontSize: 10, fill: 'var(--muted-foreground)' }}
                                            interval="preserveStartEnd"
                                        />
                                        <YAxis hide />
                                        <Tooltip
                                            cursor={{ fill: 'var(--secondary)' }}
                                            content={({ active, payload }) =>
                                                active && payload?.[0] ? (
                                                    <div className="bg-popover border-border rounded-lg border px-3 py-2 text-xs shadow-md">
                                                        <span className="font-money font-semibold">
                                                            {formatMoney(String(payload[0].payload.raw), currency)}
                                                        </span>
                                                    </div>
                                                ) : null
                                            }
                                        />
                                        <Bar dataKey="value" fill="var(--primary)" radius={[3, 3, 0, 0]} maxBarSize={24} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </section>

                        {/* Breakdown kategori */}
                        <section className="bg-card border-border rounded-xl border p-4">
                            <div className="flex items-center justify-between pb-3">
                                <h2 className="text-sm font-semibold">Per kategori</h2>
                                <div className="bg-secondary flex gap-1 rounded-lg p-0.5">
                                    <button
                                        onClick={() => setBreakdownTab('expense')}
                                        className={cn(
                                            'rounded-md px-2.5 py-1 text-xs font-medium',
                                            breakdownTab === 'expense' ? 'bg-card text-expense shadow-xs' : 'text-muted-foreground',
                                        )}
                                    >
                                        Keluar
                                    </button>
                                    <button
                                        onClick={() => setBreakdownTab('income')}
                                        className={cn(
                                            'rounded-md px-2.5 py-1 text-xs font-medium',
                                            breakdownTab === 'income' ? 'bg-card text-income shadow-xs' : 'text-muted-foreground',
                                        )}
                                    >
                                        Masuk
                                    </button>
                                </div>
                            </div>

                            {activeBreakdown.length === 0 ? (
                                <p className="text-muted-foreground py-4 text-center text-sm">
                                    Tidak ada {breakdownTab === 'expense' ? 'pengeluaran' : 'pemasukan'} pada periode ini.
                                </p>
                            ) : (
                                <div className="flex flex-col gap-1">
                                    {activeBreakdown.map((category) => {
                                        const Icon = iconByName(category.icon);
                                        const color = category.color ?? 'var(--primary)';
                                        return (
                                            <Link
                                                key={category.id ?? 'none'}
                                                href={route('transactions.index', {
                                                    categories: category.id !== null ? [category.id] : undefined,
                                                    type: breakdownTab,
                                                    start: period.start,
                                                    end: period.end,
                                                })}
                                                className="hover:bg-secondary/60 flex items-center gap-3 rounded-lg px-2 py-2.5 transition-colors"
                                            >
                                                <span
                                                    className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                                    style={{ backgroundColor: `color-mix(in srgb, ${color} 12%, transparent)`, color }}
                                                >
                                                    <Icon className="h-4 w-4" />
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <span className="flex items-baseline justify-between gap-2">
                                                        <span className="truncate text-sm font-medium">{category.name}</span>
                                                        <span className="flex shrink-0 items-baseline gap-2">
                                                            <AmountText amount={category.amount} variant="plain" className="text-sm" />
                                                            <span className="text-muted-foreground w-10 text-right text-xs">
                                                                {category.percent.toFixed(1)}%
                                                            </span>
                                                        </span>
                                                    </span>
                                                    <span className="bg-secondary mt-1.5 block h-1.5 overflow-hidden rounded-full">
                                                        <span
                                                            className="block h-full rounded-full"
                                                            style={{ width: `${Math.min(100, category.percent)}%`, backgroundColor: color }}
                                                        />
                                                    </span>
                                                </span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            )}
                        </section>

                        {/* Breakdown dompet */}
                        {walletBreakdown.length > 0 && (
                            <section className="bg-card border-border rounded-xl border p-4">
                                <h2 className="pb-3 text-sm font-semibold">Per dompet</h2>
                                <div className="flex flex-col gap-2">
                                    {walletBreakdown.map((wallet) => (
                                        <div key={wallet.id} className="flex items-center gap-3 px-2 py-1.5">
                                            <span
                                                className="h-3 w-3 shrink-0 rounded-full"
                                                style={{ backgroundColor: wallet.color ?? 'var(--primary)' }}
                                                aria-hidden
                                            />
                                            <span className="min-w-0 flex-1 truncate text-sm font-medium">{wallet.name}</span>
                                            <span className="flex shrink-0 gap-3 text-sm">
                                                <AmountText amount={wallet.income} variant="income" className="text-xs" />
                                                <AmountText amount={wallet.expense} variant="expense" className="text-xs" />
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
