/** Date display helpers (04-NFR.md U-6): `d MMM yyyy`, locale id. */

const DATE_FORMAT = new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
});

const MONTH_YEAR_FORMAT = new Intl.DateTimeFormat('id-ID', {
    month: 'long',
    year: 'numeric',
});

const DAY_MONTH_FORMAT = new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'short',
});

/** Parse "YYYY-MM-DD" as a local date (occurred_on carries no timezone). */
export function parseDate(iso: string): Date {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d);
}

export function todayIso(): string {
    return toIso(new Date());
}

export function toIso(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

/** "2026-07-06" -> "6 Jul 2026". */
export function formatDate(iso: string): string {
    return DATE_FORMAT.format(parseDate(iso));
}

/** "2026-07-01" -> "Juli 2026". */
export function formatMonthYear(iso: string): string {
    return MONTH_YEAR_FORMAT.format(parseDate(iso));
}

/** "2026-07-06" -> "Senin, 6 Jul" — with Hari ini/Kemarin shortcuts for lists. */
export function formatDayHeading(iso: string): string {
    const today = new Date();
    const yesterday = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
    if (iso === toIso(today)) return 'Hari ini';
    if (iso === toIso(yesterday)) return 'Kemarin';
    return DAY_MONTH_FORMAT.format(parseDate(iso));
}
