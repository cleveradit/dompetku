import { ICONS } from '@/lib/icons';
import { cn } from '@/lib/utils';

interface IconPickerProps {
    value: string | null;
    onChange: (name: string) => void;
    accentColor?: string | null;
}

/** Pemilih ikon dari whitelist lucide (05-DESIGN.md 2.3). */
export default function IconPicker({ value, onChange, accentColor }: IconPickerProps) {
    return (
        <div className="grid max-h-44 grid-cols-8 gap-1.5 overflow-y-auto pr-1" role="radiogroup" aria-label="Ikon">
            {Object.entries(ICONS).map(([name, Icon]) => {
                const selected = value === name;
                return (
                    <button
                        key={name}
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        title={name}
                        onClick={() => onChange(name)}
                        className={cn(
                            'flex h-11 w-full items-center justify-center rounded-lg border transition-colors',
                            'focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none',
                            selected ? 'border-transparent text-white' : 'border-border text-muted-foreground hover:bg-secondary',
                        )}
                        style={selected ? { backgroundColor: accentColor ?? 'var(--primary)' } : undefined}
                    >
                        <Icon className="h-5 w-5" />
                    </button>
                );
            })}
        </div>
    );
}
