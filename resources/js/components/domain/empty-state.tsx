import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

interface EmptyStateProps {
    icon: LucideIcon;
    message: string;
    action?: ReactNode;
}

/** 05-DESIGN.md §5: ikon garis sederhana, satu kalimat ajakan, satu tombol. */
export default function EmptyState({ icon: Icon, message, action }: EmptyStateProps) {
    return (
        <div className="flex flex-col items-center justify-center gap-4 py-12 text-center">
            <Icon className="text-muted-foreground h-10 w-10" strokeWidth={1.5} />
            <p className="text-muted-foreground text-sm">{message}</p>
            {action}
        </div>
    );
}
