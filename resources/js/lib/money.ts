/**
 * Money handling (04-NFR.md M-1): amounts travel as decimal strings with a dot
 * separator (e.g. "10500.75"). No float arithmetic is ever performed here —
 * formatting works on string parts and BigInt only.
 */

export type SupportedCurrency = 'IDR' | 'USD' | 'EUR' | 'SGD' | 'MYR';

interface CurrencyConfig {
    symbol: string;
    locale: string;
    /** Space between symbol and number ("Rp1.000" has none). */
    symbolSpace: boolean;
}

const CURRENCIES: Record<SupportedCurrency, CurrencyConfig> = {
    IDR: { symbol: 'Rp', locale: 'id-ID', symbolSpace: false },
    USD: { symbol: '$', locale: 'en-US', symbolSpace: false },
    EUR: { symbol: '€', locale: 'de-DE', symbolSpace: false },
    SGD: { symbol: 'S$', locale: 'en-SG', symbolSpace: false },
    MYR: { symbol: 'RM', locale: 'ms-MY', symbolSpace: false },
};

export const SUPPORTED_CURRENCIES: SupportedCurrency[] = ['IDR', 'USD', 'EUR', 'SGD', 'MYR'];

function config(currency: string): CurrencyConfig {
    return CURRENCIES[(currency as SupportedCurrency) in CURRENCIES ? (currency as SupportedCurrency) : 'IDR'];
}

function separators(locale: string): { group: string; decimal: string } {
    const parts = new Intl.NumberFormat(locale).formatToParts(11111.1);
    return {
        group: parts.find((p) => p.type === 'group')?.value ?? '.',
        decimal: parts.find((p) => p.type === 'decimal')?.value ?? ',',
    };
}

/** Split a decimal string into sign / integer / two-digit fraction, without floats. */
function splitAmount(amount: string): { negative: boolean; int: string; frac: string } {
    const trimmed = amount.trim();
    const negative = trimmed.startsWith('-');
    const unsigned = trimmed.replace(/^[+-]/, '');
    const [int = '0', fracRaw = ''] = unsigned.split('.');
    const frac = (fracRaw + '00').slice(0, 2);
    return { negative, int: int.replace(/^0+(?=\d)/, ''), frac };
}

function groupDigits(int: string, groupSep: string): string {
    return int.replace(/\B(?=(\d{3})+(?!\d))/g, groupSep);
}

export interface FormatMoneyOptions {
    /** Hide ",00" when the fraction is zero (default true, 05-DESIGN.md 2.4). */
    hideZeroDecimals?: boolean;
    /** Prefix "+" / "-" per transaction sign rules; "auto" uses the amount's own sign. */
    sign?: 'income' | 'expense' | 'none' | 'auto';
}

/** "1250000.50" -> "Rp1.250.000,50" (per account currency). */
export function formatMoney(amount: string, currency: string, options: FormatMoneyOptions = {}): string {
    const { hideZeroDecimals = true, sign = 'auto' } = options;
    const cfg = config(currency);
    const sep = separators(cfg.locale);
    const { negative, int, frac } = splitAmount(amount);

    const body = groupDigits(int, sep.group) + (hideZeroDecimals && frac === '00' ? '' : sep.decimal + frac);
    const money = cfg.symbol + (cfg.symbolSpace ? ' ' : '') + body;

    switch (sign) {
        case 'income':
            return '+' + money;
        case 'expense':
            return '-' + money;
        case 'none':
            return money;
        default:
            return (negative ? '-' : '') + money;
    }
}

/** Compact secondary summaries, e.g. "+Rp1,2jt" (rounding allowed here only, 05-DESIGN.md 6.5). */
export function formatMoneyCompact(amount: string, currency: string): string {
    const cfg = config(currency);
    const { negative, int } = splitAmount(amount);
    const digits = int.length;

    let short: string;
    if (digits > 12) short = compactValue(int, 12) + ' T';
    else if (digits > 9) short = compactValue(int, 9) + ' M';
    else if (digits > 6) short = compactValue(int, 6) + 'jt';
    else if (digits > 3) short = compactValue(int, 3) + 'rb';
    else short = int;

    // Indonesian-style short units; for non-IDR fall back to plain formatting.
    if (cfg.locale !== 'id-ID') {
        return formatMoney(amount, currency, { sign: negative ? 'auto' : 'none' });
    }

    return (negative ? '-' : '+') + cfg.symbol + short.replace('.', ',');
}

function compactValue(int: string, cut: number): string {
    const head = int.slice(0, int.length - cut);
    const tail = int.slice(int.length - cut, int.length - cut + 1);
    return tail === '0' ? head : `${head}.${tail}`;
}

/**
 * Parse masked user input ("1.250.000,50" in id-ID) into the canonical wire
 * string "1250000.50". Returns null when the input is not a valid amount.
 */
export function parseAmountInput(masked: string, currency: string): string | null {
    const sep = separators(config(currency).locale);
    const cleaned = masked.trim().split(sep.group).join('').replace(sep.decimal, '.');
    if (!/^\d+(\.\d{1,2})?$/.test(cleaned)) {
        return null;
    }
    return cleaned;
}

/** Re-mask a raw typing buffer for display: digits + at most one decimal separator. */
export function maskAmountInput(raw: string, currency: string): string {
    const sep = separators(config(currency).locale);
    let digitsOnly = '';
    let decimal = '';
    let seenDecimal = false;

    for (const ch of raw) {
        if (/\d/.test(ch)) {
            if (seenDecimal) {
                if (decimal.length < 2) decimal += ch;
            } else {
                digitsOnly += ch;
            }
        } else if (ch === sep.group) {
            // Thousands separator produced by a previous mask pass; ignore it.
            continue;
        } else if ((ch === sep.decimal || ch === '.' || ch === ',') && !seenDecimal) {
            seenDecimal = true;
        }
    }

    digitsOnly = digitsOnly.replace(/^0+(?=\d)/, '');
    if (digitsOnly === '') digitsOnly = seenDecimal ? '0' : '';

    const grouped = groupDigits(digitsOnly, sep.group);
    return seenDecimal ? grouped + sep.decimal + decimal : grouped;
}

/**
 * Format a canonical decimal string ("1250000.5") for display inside the
 * masked input, honouring the currency's own group/decimal separators. Unlike
 * maskAmountInput, this splits on '.' structurally (it is always the
 * canonical decimal point) rather than guessing from characters, so it never
 * misreads a currency's own separators (e.g. group=',' for USD/SGD/MYR).
 */
export function formatAmountForInput(canonical: string, currency: string): string {
    const sep = separators(config(currency).locale);
    const [intPart = '', fracPart = ''] = canonical.split('.');
    const digitsOnly = intPart.replace(/^0+(?=\d)/, '');
    const grouped = groupDigits(digitsOnly, sep.group);
    return fracPart ? grouped + sep.decimal + fracPart : grouped;
}

/** Compare two decimal strings without floats: -1, 0 or 1. */
export function compareAmounts(a: string, b: string): number {
    const pa = splitAmount(a);
    const pb = splitAmount(b);
    const ca = BigInt((pa.negative ? '-' : '') + (pa.int + pa.frac));
    const cb = BigInt((pb.negative ? '-' : '') + (pb.int + pb.frac));
    return ca < cb ? -1 : ca > cb ? 1 : 0;
}

/** Sum decimal strings exactly (BigInt cents), returning a canonical decimal string. */
export function sumAmounts(amounts: string[]): string {
    let cents = 0n;
    for (const amount of amounts) {
        const { negative, int, frac } = splitAmount(amount);
        const value = BigInt(int + frac);
        cents += negative ? -value : value;
    }
    const negative = cents < 0n;
    const abs = (negative ? -cents : cents).toString().padStart(3, '0');
    return (negative ? '-' : '') + abs.slice(0, -2) + '.' + abs.slice(-2);
}
