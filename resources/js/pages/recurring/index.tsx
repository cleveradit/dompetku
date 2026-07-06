import { Head, router, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle, MoreVertical, Pause, Pencil, Play, Plus, Repeat, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import AmountInput from '@/components/domain/amount-input';
import AmountText from '@/components/domain/amount-text';
import EmptyState from '@/components/domain/empty-state';
import InputError from '@/components/input-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/date';
import { iconByName } from '@/lib/icons';
import { Category, SharedData, TransactionType, Wallet } from '@/types';

interface RecurringItem {
    id: number;
    type: TransactionType;
    amount: string;
    description: string | null;
    frequency: 'daily' | 'weekly' | 'monthly' | 'yearly';
    interval: number;
    next_run_on: string;
    end_on: string | null;
    last_run_on: string | null;
    is_active: boolean;
    wallet_id: number;
    destination_wallet_id: number | null;
    category_id: number | null;
    wallet_name: string;
    destination_wallet_name: string | null;
    category: { id: number; name: string; color: string | null; icon: string | null } | null;
}

interface FormOptions {
    wallets: Pick<Wallet, 'id' | 'name' | 'is_archived'>[];
    categories: Category[];
    today: string;
}

const FREQUENCY_LABELS: Record<string, string> = {
    daily: 'hari',
    weekly: 'minggu',
    monthly: 'bulan',
    yearly: 'tahun',
};

function frequencyLabel(item: Pick<RecurringItem, 'frequency' | 'interval'>): string {
    const unit = FREQUENCY_LABELS[item.frequency];
    return item.interval === 1 ? `Setiap ${unit}` : `Setiap ${item.interval} ${unit}`;
}

export default function RecurringIndex({ recurrings }: { recurrings: RecurringItem[] }) {
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<RecurringItem | null>(null);
    const [deleting, setDeleting] = useState<RecurringItem | null>(null);

    const toggle = (item: RecurringItem) => {
        router.post(route('recurring.toggle', item.id), { active: !item.is_active }, { preserveScroll: true });
    };

    const confirmDelete = () => {
        if (!deleting) return;
        router.delete(route('recurring.destroy', deleting.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AppLayout
            title="Transaksi berulang"
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
                    Baru
                </Button>
            }
        >
            <Head title="Transaksi berulang" />

            {recurrings.length === 0 ? (
                <EmptyState
                    icon={Repeat}
                    message="Belum ada transaksi berulang. Otomasikan tagihan dan pemasukan rutinmu."
                    action={
                        <Button
                            onClick={() => {
                                setEditing(null);
                                setFormOpen(true);
                            }}
                        >
                            Buat transaksi berulang
                        </Button>
                    }
                />
            ) : (
                <div className="flex flex-col gap-3 py-2 pb-6">
                    {recurrings.map((item) => {
                        const Icon = item.type === 'transfer' ? Repeat : iconByName(item.category?.icon);
                        const color = item.type === 'transfer' ? 'var(--transfer)' : (item.category?.color ?? 'var(--primary)');
                        return (
                            <div key={item.id} className="bg-card border-border rounded-xl border p-4">
                                <div className="flex items-center gap-3">
                                    <span
                                        className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                        style={{ backgroundColor: `color-mix(in srgb, ${color} 12%, transparent)`, color }}
                                    >
                                        <Icon className="h-5 w-5" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <span className="truncate text-sm font-semibold">
                                                {item.type === 'transfer'
                                                    ? `${item.wallet_name} → ${item.destination_wallet_name}`
                                                    : (item.category?.name ?? 'Tanpa kategori')}
                                            </span>
                                            {!item.is_active && (
                                                <Badge variant="secondary" className="text-[10px]">
                                                    Dijeda
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-muted-foreground truncate text-xs">
                                            {frequencyLabel(item)}
                                            {item.is_active ? ` · berikutnya ${formatDate(item.next_run_on)}` : ''}
                                            {item.description ? ` · ${item.description}` : ''}
                                        </p>
                                    </div>
                                    <AmountText amount={item.amount} variant={item.type} className="shrink-0 text-[15px]" />
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <button
                                                className="text-muted-foreground hover:bg-secondary flex h-9 w-9 items-center justify-center rounded-lg"
                                                aria-label="Aksi transaksi berulang"
                                            >
                                                <MoreVertical className="h-4 w-4" />
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                onClick={() => {
                                                    setEditing(item);
                                                    setFormOpen(true);
                                                }}
                                            >
                                                <Pencil className="mr-2 h-4 w-4" />
                                                Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem onClick={() => toggle(item)}>
                                                {item.is_active ? (
                                                    <>
                                                        <Pause className="mr-2 h-4 w-4" />
                                                        Jeda
                                                    </>
                                                ) : (
                                                    <>
                                                        <Play className="mr-2 h-4 w-4" />
                                                        Aktifkan
                                                    </>
                                                )}
                                            </DropdownMenuItem>
                                            <DropdownMenuItem className="text-destructive" onClick={() => setDeleting(item)}>
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                Hapus
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Edit transaksi berulang' : 'Transaksi berulang baru'}</DialogTitle>
                        <DialogDescription>
                            {editing
                                ? 'Perubahan hanya mempengaruhi transaksi mendatang.'
                                : 'Transaksi akan tercatat otomatis sesuai jadwal.'}
                        </DialogDescription>
                    </DialogHeader>
                    <RecurringForm
                        key={editing?.id ?? 'new'}
                        editing={editing}
                        onSuccess={() => {
                            setFormOpen(false);
                            setEditing(null);
                        }}
                    />
                </DialogContent>
            </Dialog>

            <Dialog open={deleting !== null} onOpenChange={(open) => !open && setDeleting(null)}>
                <DialogContent className="sm:max-w-[420px]">
                    <DialogHeader>
                        <DialogTitle>Hapus transaksi berulang ini?</DialogTitle>
                        <DialogDescription>
                            Jadwalnya dihapus; transaksi yang sudah tercatat tetap ada dan tidak berubah.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function RecurringForm({ editing, onSuccess }: { editing: RecurringItem | null; onSuccess: () => void }) {
    const { transactionForm } = usePage<SharedData & { transactionForm: FormOptions | null }>().props;
    const options = transactionForm;

    const { data, setData, post, patch, processing, errors } = useForm({
        type: (editing?.type ?? 'expense') as TransactionType,
        wallet_id: editing?.wallet_id ?? options?.wallets[0]?.id ?? null,
        destination_wallet_id: editing?.destination_wallet_id ?? null,
        category_id: editing?.category_id ?? null,
        amount: editing?.amount ?? '',
        description: editing?.description ?? '',
        frequency: editing?.frequency ?? 'monthly',
        interval: editing?.interval ?? 1,
        next_run_on: editing?.next_run_on ?? options?.today ?? '',
        end_on: editing?.end_on ?? '',
    });

    if (!options) return null;

    const categoriesOfType = options.categories.filter((category) => category.type === data.type);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const payload = { preserveScroll: true, onSuccess };
        if (editing) {
            patch(route('recurring.update', editing.id), payload);
        } else {
            post(route('recurring.store'), payload);
        }
    };

    return (
        <form className="flex flex-col gap-4" onSubmit={submit}>
            <div className="grid gap-2">
                <Label>Tipe</Label>
                <Select
                    value={data.type}
                    onValueChange={(value) =>
                        setData((current) => ({ ...current, type: value as TransactionType, category_id: null, destination_wallet_id: null }))
                    }
                >
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="expense">Pengeluaran</SelectItem>
                        <SelectItem value="income">Pemasukan</SelectItem>
                        <SelectItem value="transfer">Transfer</SelectItem>
                    </SelectContent>
                </Select>
                <InputError message={errors.type} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="rec-amount">Nominal</Label>
                <AmountInput id="rec-amount" value={data.amount} onChange={(value) => setData('amount', value)} />
                <InputError message={errors.amount} />
            </div>

            <div className="grid gap-2">
                <Label>{data.type === 'transfer' ? 'Dari dompet' : 'Dompet'}</Label>
                <Select
                    value={data.wallet_id !== null ? String(data.wallet_id) : undefined}
                    onValueChange={(value) => setData('wallet_id', Number(value))}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Pilih dompet" />
                    </SelectTrigger>
                    <SelectContent>
                        {options.wallets.map((wallet) => (
                            <SelectItem key={wallet.id} value={String(wallet.id)}>
                                {wallet.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.wallet_id} />
            </div>

            {data.type === 'transfer' ? (
                <div className="grid gap-2">
                    <Label>Dompet tujuan</Label>
                    <Select
                        value={data.destination_wallet_id !== null ? String(data.destination_wallet_id) : undefined}
                        onValueChange={(value) => setData('destination_wallet_id', Number(value))}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih dompet tujuan" />
                        </SelectTrigger>
                        <SelectContent>
                            {options.wallets
                                .filter((wallet) => wallet.id !== data.wallet_id)
                                .map((wallet) => (
                                    <SelectItem key={wallet.id} value={String(wallet.id)}>
                                        {wallet.name}
                                    </SelectItem>
                                ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.destination_wallet_id} />
                </div>
            ) : (
                <div className="grid gap-2">
                    <Label>Kategori</Label>
                    <Select
                        value={data.category_id !== null ? String(data.category_id) : undefined}
                        onValueChange={(value) => setData('category_id', Number(value))}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Pilih kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            {categoriesOfType.map((category) => (
                                <SelectItem key={category.id} value={String(category.id)}>
                                    {category.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.category_id} />
                </div>
            )}

            <div className="grid grid-cols-2 gap-3">
                <div className="grid gap-2">
                    <Label>Frekuensi</Label>
                    <Select value={data.frequency} onValueChange={(value) => setData('frequency', value as typeof data.frequency)}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="daily">Harian</SelectItem>
                            <SelectItem value="weekly">Mingguan</SelectItem>
                            <SelectItem value="monthly">Bulanan</SelectItem>
                            <SelectItem value="yearly">Tahunan</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.frequency} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="rec-interval">Interval</Label>
                    <Input
                        id="rec-interval"
                        type="number"
                        min={1}
                        max={365}
                        value={data.interval}
                        onChange={(e) => setData('interval', Number(e.target.value))}
                    />
                    <InputError message={errors.interval} />
                </div>
            </div>
            <p className="text-muted-foreground -mt-2 text-xs">Contoh: setiap 2 minggu = frekuensi mingguan, interval 2.</p>

            <div className="grid grid-cols-2 gap-3">
                <div className="grid gap-2">
                    <Label htmlFor="rec-start">Tanggal mulai</Label>
                    <Input id="rec-start" type="date" value={data.next_run_on} onChange={(e) => setData('next_run_on', e.target.value)} />
                    <InputError message={errors.next_run_on} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="rec-end">Tanggal akhir (opsional)</Label>
                    <Input id="rec-end" type="date" value={data.end_on ?? ''} onChange={(e) => setData('end_on', e.target.value)} />
                    <InputError message={errors.end_on} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="rec-description">Catatan (opsional)</Label>
                <Input
                    id="rec-description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Contoh: langganan streaming"
                    maxLength={255}
                />
                <InputError message={errors.description} />
            </div>

            <Button type="submit" className="w-full" disabled={processing}>
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                Simpan
            </Button>
        </form>
    );
}
