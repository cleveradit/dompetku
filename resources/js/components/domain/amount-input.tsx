import { useEffect, useState } from 'react';

import { useCurrency } from '@/hooks/use-currency';
import { formatAmountForInput, maskAmountInput, parseAmountInput } from '@/lib/money';
import { cn } from '@/lib/utils';

interface AmountInputProps {
    /** Canonical decimal string ("1250000.5") or empty string. */
    value: string;
    onChange: (value: string) => void;
    id?: string;
    autoFocus?: boolean;
    /** Input nominal besar pada form transaksi (05-DESIGN.md 4.5). */
    size?: 'lg' | 'md';
    placeholder?: string;
    disabled?: boolean;
}

/** 04-NFR.md U-5: masking ribuan dan koma desimal; nilai dikirim sebagai string. */
export default function AmountInput({ value, onChange, id, autoFocus, size = 'md', placeholder = '0', disabled }: AmountInputProps) {
    const currency = useCurrency();
    const [display, setDisplay] = useState(() => (value ? formatAmountForInput(value, currency) : ''));

    useEffect(() => {
        if (value === '') {
            setDisplay('');
            return;
        }
        const parsed = parseAmountInput(display, currency);
        if (parsed !== value) {
            setDisplay(formatAmountForInput(value, currency));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [value]);

    const handleChange = (raw: string) => {
        const masked = maskAmountInput(raw, currency);
        setDisplay(masked);
        const parsed = parseAmountInput(masked, currency);
        onChange(parsed ?? '');
    };

    return (
        <input
            id={id}
            type="text"
            inputMode="decimal"
            autoFocus={autoFocus}
            disabled={disabled}
            value={display}
            placeholder={placeholder}
            onChange={(e) => handleChange(e.target.value)}
            className={cn(
                'font-money border-input bg-secondary/50 w-full rounded-lg border text-foreground tabular-nums transition-colors',
                'focus-visible:ring-ring focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none',
                'placeholder:text-muted-foreground/60 disabled:opacity-50',
                size === 'lg' ? 'px-4 py-3 text-[32px] font-semibold' : 'h-10 px-3 text-[15px] font-semibold',
            )}
        />
    );
}
