import { Head, router, useForm } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Copy, LoaderCircle, MoreVertical, Pencil, Plus, Target, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import AmountInput from '@/components/domain/amount-input';
import AmountText from '@/components/domain/amount-text';
import EmptyState from '@/components/domain/empty-state';
import InputError from '@/components/input-error';
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
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCurrency } from '@/hooks/use-currency';
import AppLayout from '@/layouts/app-layout';
import { iconByName } from '@/lib/icons';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';

interface BudgetItem {
    id: number;
    category_id: number;
    category_name: string;
    category_color: string | null;
    category_icon: string | null;
    amount: string;
    spent: string;
    remaining: string;
    percent: number;
    status: 'ok' | 'warning' | 'danger';
}

interface CategoryOption {
    id: number;
    name: string;
    color: string | null;
    icon: string | null;
    has_budget: boolean;
}

interface BudgetsPageProps {
    month: string;
    monthLabel: string;
    navigation: { prev: string; next: string };
    budgets: BudgetItem[];
    summary: { total_budget: string; total_spent: string };
    categories: CategoryOption[];
    canCopyPreviousMonth: boolean;
}

export default function BudgetsIndex({ month, monthLabel, navigation, budgets, summary, categories, canCopyPreviousMonth }: BudgetsPageProps) {
    const currency = useCurrency();
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<BudgetItem | null>(null);
    const [deleting, setDeleting] = useState<BudgetItem | null>(null);

    const goTo = (target: string) => router.get(route('budgets.index'), { month: target }, { preserveState: true });

    const copyPrevious = () => router.post(route('budgets.copy'), { month }, { preserveScroll: true });

    const confirmDelete = () => {
        if (!deleting) return;
        router.delete(route('budgets.destroy', deleting.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AppLayout
            title="Anggaran"
            headerAction={
                <Button
                    size="sm"
                    variant="outline"
                    onClick={() => {
                        setEditing(null);
                        setFormOpen(true);
                    }}
                >
                    <Plus className="h-4 w-4" />
                    Anggaran
                </Button>
            }
        >
            <Head title="Anggaran" />

            <div className="flex flex-col gap-4 pt-2 pb-6">
                {/* Navigasi bulan */}
                <div className="flex items-center justify-between gap-2">
                    <Button variant="outline" size="icon" onClick={() => goTo(navigation.prev)} aria-label="Bulan sebelumnya">
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <span className="text-sm font-semibold">{monthLabel}</span>
                    <Button variant="outline" size="icon" onClick={() => goTo(navigation.next)} aria-label="Bulan berikutnya">
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                </div>

                {budgets.length > 0 && (
                    <div className="bg-card border-border rounded-xl border p-4">
                        <p className="text-muted-foreground text-xs">Terpakai bulan ini</p>
                        <p className="text-sm">
                            <AmountText amount={summary.total_spent} variant="plain" className="text-lg" /> {' dari '}
                            <AmountText amount={summary.total_budget} variant="plain" className="text-lg" />
                        </p>
                    </div>
                )}

                {budgets.length === 0 ? (
                    <EmptyState
                        icon={Target}
                        message="Belum ada anggaran bulan ini. Tetapkan batas per kategori supaya pengeluaran terkendali."
                        action={
                            <div className="flex flex-col items-center gap-2 sm:flex-row">
                                <Button
                                    onClick={() => {
                                        setEditing(null);
                                        setFormOpen(true);
                                    }}
                                >
                                    <Plus className="h-4 w-4" />
                                    Buat anggaran
                                </Button>
                                {canCopyPreviousMonth && (
                                    <Button variant="outline" onClick={copyPrevious}>
                                        <Copy className="h-4 w-4" />
                                        Salin dari bulan lalu
                                    </Button>
                                )}
                            </div>
                        }
                    />
                ) : (
                    <div className="flex flex-col gap-3">
                        {budgets.map((budget) => {
                            const Icon = iconByName(budget.category_icon);
                            const color = budget.category_color ?? 'var(--primary)';
                            const barColor =
                                budget.status === 'danger' ? 'var(--destructive)' : budget.status === 'warning' ? 'var(--warning)' : color;

                            return (
                                <div key={budget.id} className="bg-card border-border rounded-xl border p-4">
                                    <div className="flex items-center gap-3">
                                        <span
                                            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                            style={{ backgroundColor: `color-mix(in srgb, ${color} 12%, transparent)`, color }}
                                        >
                                            <Icon className="h-5 w-5" />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="truncate text-sm font-semibold">{budget.category_name}</span>
                                                <span
                                                    className={cn('shrink-0 text-xs font-medium', {
                                                        'text-warning': budget.status === 'warning',
                                                        'text-danger': budget.status === 'danger',
                                                        'text-muted-foreground': budget.status === 'ok',
                                                    })}
                                                >
                                                    {budget.status === 'danger'
                                                        ? `Melebihi ${formatMoney(budget.remaining.replace('-', ''), currency)}`
                                                        : budget.status === 'warning'
                                                          ? 'Hampir habis'
                                                          : `${Math.round(budget.percent)}%`}
                                                </span>
                                            </div>
                                            <div className="bg-secondary mt-2 h-2.5 overflow-hidden rounded-full">
                                                <div
                                                    className="h-full rounded-full transition-all"
                                                    style={{ width: `${Math.min(100, budget.percent)}%`, backgroundColor: barColor }}
                                                />
                                            </div>
                                            <p className="font-money text-muted-foreground mt-1.5 text-xs font-medium tabular-nums">
                                                {formatMoney(budget.spent, currency)} / {formatMoney(budget.amount, currency)}
                                            </p>
                                        </div>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <button
                                                    className="text-muted-foreground hover:bg-secondary flex h-9 w-9 items-center justify-center rounded-lg"
                                                    aria-label={`Aksi anggaran ${budget.category_name}`}
                                                >
                                                    <MoreVertical className="h-4 w-4" />
                                                </button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem
                                                    onClick={() => {
                                                        setEditing(budget);
                                                        setFormOpen(true);
                                                    }}
                                                >
                                                    <Pencil className="mr-2 h-4 w-4" />
                                                    Ubah nominal
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        router.get(route('transactions.index'), {
                                                            categories: [budget.category_id],
                                                            type: 'expense',
                                                        })
                                                    }
                                                >
                                                    Lihat transaksinya
                                                </DropdownMenuItem>
                                                <DropdownMenuItem className="text-destructive" onClick={() => setDeleting(budget)}>
                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                    Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            );
                        })}

                        {canCopyPreviousMonth && (
                            <Button variant="ghost" size="sm" className="mx-auto" onClick={copyPrevious}>
                                <Copy className="h-4 w-4" />
                                Salin dari bulan lalu
                            </Button>
                        )}
                    </div>
                )}
            </div>

            {/* Form anggaran */}
            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="sm:max-w-[420px]">
                    <DialogHeader>
                        <DialogTitle>{editing ? `Anggaran ${editing.category_name}` : 'Anggaran baru'}</DialogTitle>
                        <DialogDescription>Batas pengeluaran untuk satu kategori pada {monthLabel}.</DialogDescription>
                    </DialogHeader>
                    <BudgetForm
                        month={month}
                        categories={categories}
                        editing={editing}
                        onSuccess={() => {
                            setFormOpen(false);
                            setEditing(null);
                        }}
                    />
                </DialogContent>
            </Dialog>

            {/* Konfirmasi hapus */}
            <Dialog open={deleting !== null} onOpenChange={(open) => !open && setDeleting(null)}>
                <DialogContent className="sm:max-w-[420px]">
                    <DialogHeader>
                        <DialogTitle>Hapus anggaran {deleting?.category_name}?</DialogTitle>
                        <DialogDescription>Transaksimu tidak berubah; hanya batas anggarannya yang dihapus.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            Hapus anggaran
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function BudgetForm({
    month,
    categories,
    editing,
    onSuccess,
}: {
    month: string;
    categories: CategoryOption[];
    editing: BudgetItem | null;
    onSuccess: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        category_id: editing ? editing.category_id : (categories.find((c) => !c.has_budget)?.id ?? null),
        month,
        amount: editing?.amount ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('budgets.store'), { preserveScroll: true, onSuccess });
    };

    return (
        <form className="flex flex-col gap-5" onSubmit={submit}>
            <div className="grid gap-2">
                <Label htmlFor="budget-category">Kategori pengeluaran</Label>
                <Select
                    value={data.category_id !== null ? String(data.category_id) : undefined}
                    onValueChange={(value) => setData('category_id', Number(value))}
                    disabled={editing !== null}
                >
                    <SelectTrigger id="budget-category">
                        <SelectValue placeholder="Pilih kategori" />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.map((category) => (
                            <SelectItem key={category.id} value={String(category.id)}>
                                {category.name}
                                {category.has_budget && !editing ? ' (sudah ada — akan diperbarui)' : ''}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.category_id} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="budget-amount">Nominal anggaran</Label>
                <AmountInput id="budget-amount" value={data.amount} onChange={(value) => setData('amount', value)} autoFocus={editing !== null} />
                <InputError message={errors.amount} />
            </div>

            <Button type="submit" className="w-full" disabled={processing}>
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                Simpan
            </Button>
        </form>
    );
}
