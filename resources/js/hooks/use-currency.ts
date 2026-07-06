import { usePage } from '@inertiajs/react';

import { SharedData } from '@/types';

/** Mata uang akun (format tampilan saja, 00-PRD.md §1). */
export function useCurrency(): string {
    const { auth } = usePage<SharedData>().props;
    return auth.user?.currency ?? 'IDR';
}
