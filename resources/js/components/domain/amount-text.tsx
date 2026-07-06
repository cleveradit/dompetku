import { useCurrency } from '@/hooks/use-currency';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';
import { TransactionType } from '@/types';

interface AmountTextProps {
    amount: string;
    /** Aturan tanda & warna 05-DESIGN.md 2.4. */
    variant?: TransactionType | 'balance' | 'plain';
    className?: string;
    hideZeroDecimals?: boolean;
}

/** Semua nominal: mono tabular (05-DESIGN.md 2.4). */
export default function AmountText({ amount, variant = 'plain', className, hideZeroDecimals = true }: AmountTextProps) {
    const currency = useCurrency();

    const isNegative = amount.trim().startsWith('-');

    const config = {
        income: { sign: 'income' as const, color: 'text-income' },
        expense: { sign: 'expense' as const, color: 'text-expense' },
        transfer: { sign: 'none' as const, color: 'text-foreground' },
        balance: { sign: 'auto' as const, color: isNegative ? 'text-expense' : 'text-foreground' },
        plain: { sign: 'auto' as const, color: '' },
    }[variant];

    return (
        <span className={cn('font-money font-semibold', config.color, className)}>
            {formatMoney(amount, currency, { sign: config.sign, hideZeroDecimals })}
        </span>
    );
}
