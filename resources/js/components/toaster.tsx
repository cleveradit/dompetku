import { usePage } from '@inertiajs/react';
import { CheckCircle2, XCircle } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { cn } from '@/lib/utils';
import { SharedData } from '@/types';

interface Toast {
    id: number;
    type: 'success' | 'error';
    message: string;
}

let nextId = 1;

/**
 * 04-NFR.md U-7: setiap aksi tulis memberi feedback. Toast muncul dari bawah
 * (mobile) / kanan atas (desktop), 05-DESIGN.md 2.5.
 */
export default function Toaster() {
    const { flash } = usePage<SharedData>().props;
    const [toasts, setToasts] = useState<Toast[]>([]);
    const lastFlash = useRef<string | null>(null);

    useEffect(() => {
        const message = flash?.success ?? flash?.error;
        if (!message) return;

        const key = `${message}-${Date.now() >> 12}`;
        if (lastFlash.current === key) return;
        lastFlash.current = key;

        const toast: Toast = {
            id: nextId++,
            type: flash?.success ? 'success' : 'error',
            message,
        };

        setToasts((current) => [...current, toast]);
        const timeout = setTimeout(() => {
            setToasts((current) => current.filter((t) => t.id !== toast.id));
        }, 3500);

        return () => clearTimeout(timeout);
    }, [flash]);

    if (toasts.length === 0) return null;

    return (
        <div className="pointer-events-none fixed inset-x-4 bottom-24 z-[60] flex flex-col items-center gap-2 sm:inset-x-auto sm:top-4 sm:right-4 sm:bottom-auto sm:items-end">
            {toasts.map((toast) => (
                <div
                    key={toast.id}
                    role="status"
                    className={cn(
                        'pointer-events-auto flex items-center gap-2.5 rounded-lg border px-4 py-3 text-sm font-medium shadow-lg',
                        'animate-in fade-in slide-in-from-bottom-2 sm:slide-in-from-right-2 duration-200',
                        toast.type === 'success'
                            ? 'bg-card border-border text-foreground'
                            : 'bg-destructive text-destructive-foreground border-transparent',
                    )}
                >
                    {toast.type === 'success' ? <CheckCircle2 className="text-income h-4 w-4 shrink-0" /> : <XCircle className="h-4 w-4 shrink-0" />}
                    {toast.message}
                </div>
            ))}
        </div>
    );
}
