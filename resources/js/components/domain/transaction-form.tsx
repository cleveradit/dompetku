import { useForm, usePage } from '@inertiajs/react';
import { ArrowRight, LoaderCircle, Repeat } from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';

import AmountInput from '@/components/domain/amount-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { iconByName } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { Category, SharedData, Transaction, TransactionType, Wallet } from '@/types';

interface TransactionFormOptions {
    wallets: Pick<Wallet, 'id' | 'name' | 'color' | 'icon' | 'is_archived' | 'current_balance'>[];
    categories: Category[];
    lastWalletId: number | null;
    today: string;
}

interface TransactionFormProps {
    transaction?: Transaction;
    onSuccess?: () => void;
}

type FormData = {
    type: TransactionType;
    amount: string;
    category_id: number | null;
    wallet_id: number | null;
    destination_wallet_id: number | null;
    occurred_on: string;
    description: string;
    recurring: boolean;
    frequency: 'daily' | 'weekly' | 'monthly' | 'yearly';
    interval: number;
    end_on: string;
};

const TYPE_TABS: { value: TransactionType; label: string }[] = [
    { value: 'expense', label: 'Keluar' },
    { value: 'income', label: 'Masuk' },
    { value: 'transfer', label: 'Transfer' },
];

/** 05-DESIGN.md 4.5: layar terpenting — urutan field dioptimalkan kecepatan. */
export default function TransactionForm({ transaction, onSuccess }: TransactionFormProps) {
    const { transactionForm } = usePage<SharedData & { transactionForm: TransactionFormOptions | null }>().props;
    const options = transactionForm;
    const isEdit = transaction !== undefined;
    const [showAllCategories, setShowAllCategories] = useState(false);

    const { data, setData, post, patch, processing, errors, reset, clearErrors, transform } = useForm<FormData>({
        type: transaction?.type ?? 'expense',
        amount: transaction?.amount ?? '',
        category_id: transaction?.category?.id ?? null,
        wallet_id: transaction?.wallet?.id ?? options?.lastWalletId ?? options?.wallets[0]?.id ?? null,
        destination_wallet_id: transaction?.destination_wallet?.id ?? null,
        occurred_on: transaction?.occurred_on ?? options?.today ?? '',
        description: transaction?.description ?? '',
        recurring: false,
        frequency: 'monthly',
        interval: 1,
        end_on: '',
    });

    const categoriesOfType = useMemo(
        () => (options?.categories ?? []).filter((category) => category.type === data.type),
        [options?.categories, data.type],
    );
    const visibleCategories = showAllCategories ? categoriesOfType : categoriesOfType.slice(0, 8);
    const hiddenCount = categoriesOfType.length - 8;

    const wallets = options?.wallets ?? [];
    const yesterday = useMemo(() => {
        if (!options?.today) return '';
        const d = new Date(options.today + 'T00:00:00');
        d.setDate(d.getDate() - 1);
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }, [options?.today]);

    const switchType = (type: TransactionType) => {
        clearErrors();
        setData((current) => ({
            ...current,
            type,
            category_id: null,
            destination_wallet_id: null,
        }));
        setShowAllCategories(false);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const handlers = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onSuccess?.();
            },
        };

        if (isEdit) {
            patch(route('transactions.update', transaction.id), handlers);
        } else if (data.recurring) {
            // 05-DESIGN.md 4.5: toggle "Jadikan berulang" menjadwalkan transaksi
            // mulai tanggal yang dipilih (US-16).
            transform((current) => ({
                ...current,
                next_run_on: current.occurred_on,
                end_on: current.end_on === '' ? null : current.end_on,
            }));
            post(route('recurring.store'), handlers);
        } else if (data.type === 'transfer') {
            post(route('transfers.store'), handlers);
        } else {
            post(route('transactions.store'), handlers);
        }
    };

    if (!options) return null;

    return (
        <form className="flex flex-col gap-5" onSubmit={submit}>
            {/* 1. Segmented control */}
            <div className="bg-secondary grid grid-cols-3 gap-1 rounded-lg p-1" role="tablist" aria-label="Tipe transaksi">
                {TYPE_TABS.map((tab) => (
                    <button
                        key={tab.value}
                        type="button"
                        role="tab"
                        aria-selected={data.type === tab.value}
                        onClick={() => switchType(tab.value)}
                        className={cn(
                            'min-h-9 rounded-md text-sm font-semibold transition-colors',
                            data.type === tab.value
                                ? tab.value === 'expense'
                                    ? 'bg-card text-expense shadow-xs'
                                    : tab.value === 'income'
                                      ? 'bg-card text-income shadow-xs'
                                      : 'bg-card text-transfer shadow-xs'
                                : 'text-muted-foreground',
                        )}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {/* 2. Nominal besar */}
            <div className="grid gap-2">
                <Label htmlFor="tx-amount" className="sr-only">
                    Nominal
                </Label>
                <AmountInput id="tx-amount" size="lg" value={data.amount} onChange={(value) => setData('amount', value)} autoFocus={!isEdit} />
                <InputError message={errors.amount} />
            </div>

            {/* 3. Kategori (atau dompet tujuan untuk transfer) */}
            {data.type === 'transfer' ? (
                <div className="grid gap-2">
                    <Label htmlFor="tx-destination">Dompet tujuan</Label>
                    <Select
                        value={data.destination_wallet_id !== null ? String(data.destination_wallet_id) : undefined}
                        onValueChange={(value) => setData('destination_wallet_id', Number(value))}
                    >
                        <SelectTrigger id="tx-destination">
                            <SelectValue placeholder="Pilih dompet tujuan" />
                        </SelectTrigger>
                        <SelectContent>
                            {wallets
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
                    <div className="grid grid-cols-4 gap-1.5">
                        {visibleCategories.map((category) => {
                            const Icon = iconByName(category.icon);
                            const selected = data.category_id === category.id;
                            return (
                                <button
                                    key={category.id}
                                    type="button"
                                    onClick={() => setData('category_id', category.id)}
                                    className={cn(
                                        'flex min-h-11 flex-col items-center justify-center gap-1 rounded-lg border px-1 py-2 text-[11px] font-medium transition-colors',
                                        'focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none',
                                        selected ? 'border-transparent text-white' : 'border-border text-muted-foreground hover:bg-secondary',
                                    )}
                                    style={selected ? { backgroundColor: category.color ?? 'var(--primary)' } : undefined}
                                >
                                    <Icon className="h-4 w-4" />
                                    <span className="max-w-full truncate">{category.name}</span>
                                </button>
                            );
                        })}
                        {!showAllCategories && hiddenCount > 0 && (
                            <button
                                type="button"
                                onClick={() => setShowAllCategories(true)}
                                className="border-border text-muted-foreground hover:bg-secondary flex min-h-11 flex-col items-center justify-center gap-1 rounded-lg border border-dashed px-1 py-2 text-[11px] font-medium"
                            >
                                +{hiddenCount} lainnya
                            </button>
                        )}
                    </div>
                    <InputError message={errors.category_id} />
                </div>
            )}

            {/* 4. Dompet, tanggal, catatan */}
            <div className="grid gap-2">
                <Label htmlFor="tx-wallet">{data.type === 'transfer' ? 'Dari dompet' : 'Dompet'}</Label>
                <Select
                    value={data.wallet_id !== null ? String(data.wallet_id) : undefined}
                    onValueChange={(value) => setData('wallet_id', Number(value))}
                >
                    <SelectTrigger id="tx-wallet">
                        <SelectValue placeholder="Pilih dompet" />
                    </SelectTrigger>
                    <SelectContent>
                        {wallets.map((wallet) => (
                            <SelectItem key={wallet.id} value={String(wallet.id)}>
                                {wallet.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.wallet_id} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="tx-date">Tanggal</Label>
                <div className="flex items-center gap-2">
                    <Input
                        id="tx-date"
                        type="date"
                        max={options.today}
                        value={data.occurred_on}
                        onChange={(e) => setData('occurred_on', e.target.value)}
                        className="flex-1"
                    />
                    <Button
                        type="button"
                        variant={data.occurred_on === options.today ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => setData('occurred_on', options.today)}
                    >
                        Hari ini
                    </Button>
                    <Button
                        type="button"
                        variant={data.occurred_on === yesterday ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => setData('occurred_on', yesterday)}
                    >
                        Kemarin
                    </Button>
                </div>
                <InputError message={errors.occurred_on} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="tx-description">Catatan (opsional)</Label>
                <Input
                    id="tx-description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Contoh: makan siang bareng tim"
                    maxLength={255}
                />
                <InputError message={errors.description} />
            </div>

            {data.type === 'transfer' && (
                <div className="text-muted-foreground bg-secondary/60 flex items-center gap-2 rounded-lg px-3 py-2 text-xs">
                    <ArrowRight className="h-3.5 w-3.5 shrink-0" />
                    Transfer memindahkan uang antar dompetmu dan tidak dihitung sebagai pengeluaran.
                </div>
            )}

            {/* Toggle "Jadikan berulang" (05-DESIGN.md 4.5, Fase 2) */}
            {!isEdit && (
                <div className="grid gap-3">
                    <label className="flex min-h-11 cursor-pointer items-center justify-between gap-3">
                        <span className="flex items-center gap-2 text-sm font-medium">
                            <Repeat className="text-muted-foreground h-4 w-4" />
                            Jadikan berulang
                        </span>
                        <Checkbox checked={data.recurring} onCheckedChange={(checked) => setData('recurring', checked === true)} />
                    </label>

                    {data.recurring && (
                        <div className="bg-secondary/40 grid gap-3 rounded-lg p-3">
                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-1.5">
                                    <Label className="text-xs">Frekuensi</Label>
                                    <Select
                                        value={data.frequency}
                                        onValueChange={(value) => setData('frequency', value as FormData['frequency'])}
                                    >
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
                                <div className="grid gap-1.5">
                                    <Label htmlFor="tx-interval" className="text-xs">
                                        Interval
                                    </Label>
                                    <Input
                                        id="tx-interval"
                                        type="number"
                                        min={1}
                                        max={365}
                                        value={data.interval}
                                        onChange={(e) => setData('interval', Number(e.target.value))}
                                    />
                                    <InputError message={errors.interval} />
                                </div>
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="tx-end-on" className="text-xs">
                                    Berakhir pada (opsional)
                                </Label>
                                <Input id="tx-end-on" type="date" value={data.end_on} onChange={(e) => setData('end_on', e.target.value)} />
                                <InputError message={errors.end_on} />
                            </div>
                            <p className="text-muted-foreground text-xs">
                                Tercatat otomatis mulai tanggal di atas, diulang sesuai frekuensi.
                            </p>
                        </div>
                    )}
                </div>
            )}

            {/* 5. Simpan */}
            <Button type="submit" className="w-full" size="lg" disabled={processing}>
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                Simpan
            </Button>
        </form>
    );
}
