import { Check } from 'lucide-react';

import { RUPIAH_PALETTE } from '@/lib/icons';
import { cn } from '@/lib/utils';

interface ColorSwatchesProps {
    value: string | null;
    onChange: (hex: string) => void;
}

/** Palet pecahan rupiah (05-DESIGN.md 2.3), swatch berurutan nominal. */
export default function ColorSwatches({ value, onChange }: ColorSwatchesProps) {
    return (
        <div className="flex flex-wrap gap-2" role="radiogroup" aria-label="Warna">
            {RUPIAH_PALETTE.map((color) => {
                const selected = value?.toUpperCase() === color.hex.toUpperCase();
                return (
                    <button
                        key={color.hex}
                        type="button"
                        role="radio"
                        aria-checked={selected}
                        title={color.name}
                        onClick={() => onChange(color.hex)}
                        className={cn(
                            'flex h-11 w-11 items-center justify-center rounded-full transition-transform',
                            'focus-visible:ring-ring focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
                            selected && 'scale-110',
                        )}
                        style={{ backgroundColor: color.hex }}
                    >
                        {selected && <Check className="h-5 w-5 text-white" />}
                    </button>
                );
            })}
        </div>
    );
}
