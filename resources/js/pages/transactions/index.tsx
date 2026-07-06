import { Head, router, usePage } from '@inertiajs/react';
import { ChevronDown, Download, Filter, Receipt, SearchIcon, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

import AmountText from '@/components/domain/amount-text';
import AttachmentSection from '@/components/domain/attachment-section';
import EmptyState from '@/components/domain/empty-state';
import TransactionListItem from '@/components/domain/transaction-list-item';
import { openEditTransaction } from '@/components/domain/transaction-sheet';
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
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import AmountInput from '@/components/domain/amount-input';
import { openTransactionSheet } from '@/layouts/app-layout';
import AppLayout from '@/layouts/app-layout';
import { formatDate, formatDayHeading } from '@/lib/date';
import { sumAmounts } from '@/lib/money';
import { Category, SharedData, Transaction, Wallet } from '@/types';

interface Filters {
    q: string;
    start: string | null;
    end: string | null;
    categories: number[];
    wallet: number | null;
    type: string | null;
    min: string | null;
    max: string | null;
}

interface PageProps {
    transactions: Transaction[];
    pagination: { current_page: number; last_page: number; total: number; next_page_url: string | null };
    filters: Filters;
    summary: { count: number; net_total: string } | null;
    exportReady: { url: string; name: string } | null;
}

interface FormOptions {
    wallets: Pick<Wallet, 'id' | 'name' | 'color' | 'icon' | 'is_archived'>[];
    categories: Category[];
}

export default function TransactionsIndex({ transactions, pagination, filters, summary, exportReady }: PageProps) {
    const { transactionForm } = usePage<SharedData & { transactionForm: FormOptions | null }>().props;
    const [items, setItems] = useState<Transaction[]>(transactions);
    const [search, setSearch] = useState(filters.q);
    const [filterOpen, setFilterOpen] = useState(false);
    const [detail, setDetail] = useState<Transaction | null>(null);
    const [deleting, setDeleting] = useState<Transaction | null>(null);
    const searchTimeout = useRef<ReturnType<typeof setTimeout>>(null);

    useEffect(() => {
        if (pagination.current_page === 1) {
            setItems(transactions);
        } else {
            setItems((prev) => {
                const known = new Set(prev.map((t) => t.id));
                return [...prev, ...transactions.filter((t) => !known.has(t.id))];
            });
        }
    }, [transactions, pagination.current_page]);

    // Sinkronkan detail dengan data terbaru (mis. setelah lampiran diunggah/dihapus).
    useEffect(() => {
        setDetail((current) => {
            if (!current) return current;
            const fresh = transactions.find((t) => t.id === current.id);
            return fresh ?? current;
        });
    }, [transactions]);

    const applyFilters = (next: Partial<Filters>) => {
        const merged = { ...filters, ...next };
        router.get(
            route('transactions.index'),
            {
                q: merged.q || undefined,
                start: merged.start || undefined,
                end: merged.end || undefined,
                categories: merged.categories.length > 0 ? merged.categories : undefined,
                wallet: merged.wallet ?? undefined,
                type: merged.type ?? undefined,
                min: merged.min || undefined,
                max: merged.max || undefined,
            },
            { preserveState: true, preserveScroll: true, only: ['transactions', 'pagination', 'filters', 'summary'] },
        );
    };

    const onSearchChange = (value: string) => {
        setSearch(value);
        if (searchTimeout.current) clearTimeout(searchTimeout.current);
        searchTimeout.current = setTimeout(() => applyFilters({ q: value }), 350);
    };

    const loadMore = () => {
        if (!pagination.next_page_url) return;
        router.get(pagination.next_page_url, {}, { preserveState: true, preserveScroll: true, only: ['transactions', 'pagination'] });
    };

    const groups = useMemo(() => {
        const map = new Map<string, Transaction[]>();
        for (const item of items) {
            const list = map.get(item.occurred_on) ?? [];
            list.push(item);
            map.set(item.occurred_on, list);
        }
        return [...map.entries()];
    }, [items]);

    const dailyNet = (list: Transaction[]) =>
        sumAmounts(list.map((t) => (t.type === 'income' ? t.amount : t.type === 'expense' ? `-${t.amount}` : '0')));

    const activeFilterChips = useMemo(() => {
        const chips: { key: string; label: string; clear: Partial<Filters> }[] = [];
        const categories = transactionForm?.categories ?? [];
        const wallets = transactionForm?.wallets ?? [];

        if (filters.start) chips.push({ key: 'start', label: `Dari ${formatDate(filters.start)}`, clear: { start: null } });
        if (filters.end) chips.push({ key: 'end', label: `Sampai ${formatDate(filters.end)}`, clear: { end: null } });
        for (const id of filters.categories) {
            const category = categories.find((c) => c.id === id);
            if (category) {
                chips.push({ key: `cat-${id}`, label: category.name, clear: { categories: filters.categories.filter((c) => c !== id) } });
            }
        }
        if (filters.wallet !== null) {
            const wallet = wallets.find((w) => w.id === filters.wallet);
            chips.push({ key: 'wallet', label: wallet?.name ?? 'Dompet', clear: { wallet: null } });
        }
        if (filters.type) {
            const labels: Record<string, string> = { income: 'Pemasukan', expense: 'Pengeluaran', transfer: 'Transfer' };
            chips.push({ key: 'type', label: labels[filters.type] ?? filters.type, clear: { type: null } });
        }
        if (filters.min) chips.push({ key: 'min', label: `≥ ${filters.min}`, clear: { min: null } });
        if (filters.max) chips.push({ key: 'max', label: `≤ ${filters.max}`, clear: { max: null } });

        return chips;
    }, [filters, transactionForm]);

    const clearAll = () =>
        applyFilters({ q: '', start: null, end: null, categories: [], wallet: null, type: null, min: null, max: null });

    const exportTransactions = (format: 'csv' | 'xlsx') => {
        window.location.href = route('exports.transactions', {
            q: filters.q || undefined,
            start: filters.start || undefined,
            end: filters.end || undefined,
            categories: filters.categories.length > 0 ? filters.categories : undefined,
            wallet: filters.wallet ?? undefined,
            type: filters.type ?? undefined,
            min: filters.min || undefined,
            max: filters.max || undefined,
            format,
        });
    };

    const confirmDelete = () => {
        if (!deleting) return;
        router.delete(route('transactions.destroy', deleting.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(null);
                setDetail(null);
            },
        });
    };

    const hasAnyFilter = activeFilterChips.length > 0 || filters.q !== '';

    return (
        <AppLayout
            title="Transaksi"
            headerAction={
                <Button size="sm" variant="outline" onClick={() => setFilterOpen(true)}>
                    <Filter className="h-4 w-4" />
                    Filter
                </Button>
            }
        >
            <Head title="Transaksi" />

            {/* Search */}
            <div className="relative py-2">
                <SearchIcon className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                <Input value={search} onChange={(e) => onSearchChange(e.target.value)} placeholder="Cari catatan transaksi…" className="pl-9" />
            </div>

            {/* Chip filter aktif */}
            {activeFilterChips.length > 0 && (
                <div className="flex flex-wrap items-center gap-1.5 pb-2">
                    {activeFilterChips.map((chip) => (
                        <Badge key={chip.key} variant="secondary" className="gap-1 rounded-full">
                            {chip.label}
                            <button onClick={() => applyFilters(chip.clear)} aria-label={`Hapus filter ${chip.label}`}>
                                <X className="h-3 w-3" />
                            </button>
                        </Badge>
                    ))}
                    <Button variant="ghost" size="sm" className="h-6 px-2 text-xs" onClick={clearAll}>
                        Bersihkan filter
                    </Button>
                </div>
            )}

            {/* Export siap diunduh */}
            {exportReady && (
                <div className="bg-secondary/60 mb-2 flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm">
                    <span className="truncate">Export siap diunduh: {exportReady.name}</span>
                    <Button asChild size="sm" variant="outline" className="shrink-0">
                        <a href={exportReady.url}>
                            <Download className="h-4 w-4" />
                            Unduh
                        </a>
                    </Button>
                </div>
            )}

            {/* Ringkasan hasil filter (AC-17.3) */}
            {summary && (
                <div className="bg-secondary/60 text-muted-foreground mb-2 flex items-center justify-between rounded-lg px-3 py-2 text-sm">
                    <span>{summary.count} transaksi</span>
                    <div className="flex items-center gap-3">
                        <AmountText amount={summary.net_total} variant="balance" className="text-sm" />
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button size="sm" variant="outline">
                                    <Download className="h-4 w-4" />
                                    Export
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={() => exportTransactions('csv')}>Export CSV</DropdownMenuItem>
                                <DropdownMenuItem onClick={() => exportTransactions('xlsx')}>Export Excel</DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            )}

            {items.length === 0 ? (
                hasAnyFilter ? (
                    <EmptyState
                        icon={SearchIcon}
                        message="Tidak ada hasil. Coba longgarkan filter atau ubah kata kuncinya."
                        action={
                            <Button variant="outline" onClick={clearAll}>
                                Bersihkan filter
                            </Button>
                        }
                    />
                ) : (
                    <EmptyState
                        icon={Receipt}
                        message="Belum ada transaksi. Mulai catat pengeluaran atau pemasukan pertamamu."
                        action={<Button onClick={openTransactionSheet}>Catat transaksi</Button>}
                    />
                )
            ) : (
                <div className="flex flex-col gap-4 pb-4">
                    {groups.map(([date, list]) => (
                        <section key={date}>
                            <header className="text-muted-foreground flex items-baseline justify-between px-2 pb-1 text-xs font-medium">
                                <span>{formatDayHeading(date)}</span>
                                <AmountText amount={dailyNet(list)} variant="balance" className="text-xs" />
                            </header>
                            <div className="bg-card border-border divide-border divide-y rounded-xl border px-2">
                                {list.map((transaction) => (
                                    <TransactionListItem key={transaction.id} transaction={transaction} onClick={() => setDetail(transaction)} />
                                ))}
                            </div>
                        </section>
                    ))}

                    {pagination.next_page_url && (
                        <Button variant="outline" onClick={loadMore} className="mx-auto">
                            <ChevronDown className="h-4 w-4" />
                            Muat lebih banyak
                        </Button>
                    )}
                </div>
            )}

            {/* Sheet filter */}
            <FilterSheet
                open={filterOpen}
                onOpenChange={setFilterOpen}
                filters={filters}
                options={transactionForm}
                onApply={(next) => {
                    setFilterOpen(false);
                    applyFilters(next);
                }}
            />

            {/* Detail transaksi */}
            <Sheet open={detail !== null} onOpenChange={(open) => !open && setDetail(null)}>
                <SheetContent side="bottom" className="mx-auto max-w-[640px] rounded-t-2xl px-4 pb-8">
                    {detail && (
                        <>
                            <SheetHeader className="px-0">
                                <SheetTitle>
                                    {detail.type === 'transfer'
                                        ? 'Transfer'
                                        : (detail.category?.name ?? 'Transaksi')}
                                </SheetTitle>
                            </SheetHeader>
                            <div className="flex flex-col gap-3">
                                <AmountText amount={detail.amount} variant={detail.type} className="text-[32px]" hideZeroDecimals={false} />
                                <dl className="text-sm">
                                    <DetailRow label="Tanggal" value={formatDate(detail.occurred_on)} />
                                    {detail.type === 'transfer' ? (
                                        <>
                                            <DetailRow label="Dari" value={detail.wallet?.name ?? '-'} />
                                            <DetailRow label="Ke" value={detail.destination_wallet?.name ?? '-'} />
                                        </>
                                    ) : (
                                        <DetailRow label="Dompet" value={detail.wallet?.name ?? '-'} />
                                    )}
                                    {detail.description && <DetailRow label="Catatan" value={detail.description} />}
                                    {detail.is_recurring && <DetailRow label="Sumber" value="Transaksi berulang" />}
                                </dl>
                                <AttachmentSection transaction={detail} />
                                <div className="mt-2 grid grid-cols-2 gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            setDetail(null);
                                            openEditTransaction(detail);
                                        }}
                                    >
                                        Edit
                                    </Button>
                                    <Button variant="destructive" onClick={() => setDeleting(detail)}>
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>

            {/* Konfirmasi hapus dengan konsekuensi eksplisit (05-DESIGN.md §5) */}
            <Dialog open={deleting !== null} onOpenChange={(open) => !open && setDeleting(null)}>
                <DialogContent className="sm:max-w-[420px]">
                    <DialogHeader>
                        <DialogTitle>Hapus transaksi ini?</DialogTitle>
                        <DialogDescription>
                            Menghapus transaksi ini akan mengembalikan saldo {deleting?.wallet?.name ?? 'dompet'} seperti sebelum transaksi
                            dicatat.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            Hapus transaksi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="border-border flex justify-between gap-4 border-b py-2 last:border-0">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-right font-medium">{value}</dd>
        </div>
    );
}

interface FilterSheetProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    filters: Filters;
    options: FormOptions | null;
    onApply: (filters: Partial<Filters>) => void;
}

function FilterSheet({ open, onOpenChange, filters, options, onApply }: FilterSheetProps) {
    const [draft, setDraft] = useState<Filters>(filters);

    useEffect(() => {
        if (open) setDraft(filters);
    }, [open, filters]);

    const toggleCategory = (id: number) => {
        setDraft((d) => ({
            ...d,
            categories: d.categories.includes(id) ? d.categories.filter((c) => c !== id) : [...d.categories, id],
        }));
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent side="bottom" className="mx-auto max-h-[92svh] max-w-[640px] overflow-y-auto rounded-t-2xl px-4 pb-8">
                <SheetHeader className="px-0">
                    <SheetTitle>Filter transaksi</SheetTitle>
                </SheetHeader>

                <div className="flex flex-col gap-5">
                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="filter-start">Dari tanggal</Label>
                            <Input
                                id="filter-start"
                                type="date"
                                value={draft.start ?? ''}
                                onChange={(e) => setDraft((d) => ({ ...d, start: e.target.value || null }))}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="filter-end">Sampai tanggal</Label>
                            <Input
                                id="filter-end"
                                type="date"
                                value={draft.end ?? ''}
                                onChange={(e) => setDraft((d) => ({ ...d, end: e.target.value || null }))}
                            />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label>Tipe</Label>
                        <Select
                            value={draft.type ?? 'all'}
                            onValueChange={(value) => setDraft((d) => ({ ...d, type: value === 'all' ? null : value }))}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua tipe</SelectItem>
                                <SelectItem value="expense">Pengeluaran</SelectItem>
                                <SelectItem value="income">Pemasukan</SelectItem>
                                <SelectItem value="transfer">Transfer</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-2">
                        <Label>Dompet</Label>
                        <Select
                            value={draft.wallet !== null ? String(draft.wallet) : 'all'}
                            onValueChange={(value) => setDraft((d) => ({ ...d, wallet: value === 'all' ? null : Number(value) }))}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua dompet</SelectItem>
                                {(options?.wallets ?? []).map((wallet) => (
                                    <SelectItem key={wallet.id} value={String(wallet.id)}>
                                        {wallet.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-2">
                        <Label>Kategori</Label>
                        <div className="flex flex-wrap gap-1.5">
                            {(options?.categories ?? []).map((category) => (
                                <button
                                    key={category.id}
                                    type="button"
                                    onClick={() => toggleCategory(category.id)}
                                    className={
                                        draft.categories.includes(category.id)
                                            ? 'bg-primary text-primary-foreground rounded-full px-3 py-1.5 text-xs font-medium'
                                            : 'bg-secondary text-muted-foreground rounded-full px-3 py-1.5 text-xs font-medium'
                                    }
                                >
                                    {category.name}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="filter-min">Nominal minimal</Label>
                            <AmountInput id="filter-min" value={draft.min ?? ''} onChange={(value) => setDraft((d) => ({ ...d, min: value || null }))} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="filter-max">Nominal maksimal</Label>
                            <AmountInput id="filter-max" value={draft.max ?? ''} onChange={(value) => setDraft((d) => ({ ...d, max: value || null }))} />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-2">
                        <Button
                            variant="outline"
                            onClick={() =>
                                setDraft({ q: draft.q, start: null, end: null, categories: [], wallet: null, type: null, min: null, max: null })
                            }
                        >
                            Reset
                        </Button>
                        <Button onClick={() => onApply(draft)}>Terapkan</Button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
