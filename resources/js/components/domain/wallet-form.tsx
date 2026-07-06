import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import AmountInput from '@/components/domain/amount-input';
import ColorSwatches from '@/components/domain/color-swatches';
import IconPicker from '@/components/domain/icon-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RUPIAH_PALETTE } from '@/lib/icons';
import { Wallet, WalletType } from '@/types';

const WALLET_TYPES: { value: WalletType; label: string }[] = [
    { value: 'cash', label: 'Tunai' },
    { value: 'bank', label: 'Bank' },
    { value: 'ewallet', label: 'E-wallet' },
    { value: 'other', label: 'Lainnya' },
];

type WalletFormData = {
    name: string;
    type: WalletType;
    initial_balance: string;
    color: string;
    icon: string;
};

interface WalletFormProps {
    wallet?: Wallet;
    submitLabel: string;
    onSuccess?: () => void;
}

/** Form dompet (05-DESIGN.md 4.2 & 4.6): nama, tipe, saldo awal, swatch warna rupiah, ikon. */
export default function WalletForm({ wallet, submitLabel, onSuccess }: WalletFormProps) {
    const isEdit = wallet !== undefined;

    const { data, setData, post, patch, processing, errors } = useForm<WalletFormData>({
        name: wallet?.name ?? '',
        type: wallet?.type ?? 'bank',
        initial_balance: '0',
        color: wallet?.color ?? RUPIAH_PALETTE[2].hex,
        icon: wallet?.icon ?? 'wallet',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (isEdit) {
            patch(route('wallets.update', wallet.id), { onSuccess, preserveScroll: true });
        } else {
            post(route('wallets.store'), { onSuccess, preserveScroll: true });
        }
    };

    return (
        <form className="flex flex-col gap-5" onSubmit={submit}>
            <div className="grid gap-2">
                <Label htmlFor="wallet-name">Nama dompet</Label>
                <Input
                    id="wallet-name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Contoh: BCA, GoPay, Dompet tunai"
                    maxLength={50}
                    required
                    autoFocus={!isEdit}
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="wallet-type">Tipe</Label>
                <Select value={data.type} onValueChange={(value) => setData('type', value as WalletType)}>
                    <SelectTrigger id="wallet-type">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {WALLET_TYPES.map((type) => (
                            <SelectItem key={type.value} value={type.value}>
                                {type.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.type} />
            </div>

            {!isEdit && (
                <div className="grid gap-2">
                    <Label htmlFor="wallet-balance">Saldo awal</Label>
                    <AmountInput id="wallet-balance" value={data.initial_balance} onChange={(value) => setData('initial_balance', value)} />
                    <p className="text-muted-foreground text-xs">Isi saldo dompet saat ini, boleh 0.</p>
                    <InputError message={errors.initial_balance} />
                </div>
            )}

            <div className="grid gap-2">
                <Label>Warna</Label>
                <ColorSwatches value={data.color} onChange={(hex) => setData('color', hex)} />
                <InputError message={errors.color} />
            </div>

            <div className="grid gap-2">
                <Label>Ikon</Label>
                <IconPicker value={data.icon} onChange={(name) => setData('icon', name)} accentColor={data.color} />
                <InputError message={errors.icon} />
            </div>

            <Button type="submit" className="w-full" disabled={processing}>
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                {submitLabel}
            </Button>
        </form>
    );
}
