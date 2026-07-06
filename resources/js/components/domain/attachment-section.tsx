import { router } from '@inertiajs/react';
import { FileText, Paperclip, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Transaction } from '@/types';

const MAX_ATTACHMENTS = 5;

interface AttachmentSectionProps {
    transaction: Transaction;
}

/** Lampiran struk pada detail transaksi: unggah, pratinjau, dan hapus. */
export default function AttachmentSection({ transaction }: AttachmentSectionProps) {
    const attachments = transaction.attachments ?? [];
    const [removing, setRemoving] = useState<{ id: number; original_name: string } | null>(null);
    const [uploading, setUploading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const atLimit = attachments.length >= MAX_ATTACHMENTS;

    const onFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        e.target.value = '';
        if (!file) return;

        setUploading(true);
        router.post(
            route('attachments.store', transaction.id),
            { file },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => setUploading(false),
            },
        );
    };

    const confirmRemove = () => {
        if (!removing) return;
        router.delete(route('attachments.destroy', removing.id), {
            preserveScroll: true,
            onFinish: () => setRemoving(null),
        });
    };

    return (
        <div className="grid gap-2">
            <span className="text-muted-foreground text-sm">Lampiran</span>

            {attachments.length > 0 && (
                <ul className="flex flex-col gap-2">
                    {attachments.map((attachment) => (
                        <li key={attachment.id} className="border-border bg-card flex items-center gap-2 rounded-lg border p-2">
                            {attachment.mime_type.startsWith('image/') ? (
                                <img src={attachment.url} alt={attachment.original_name} className="h-10 w-10 shrink-0 rounded object-cover" />
                            ) : (
                                <FileText className="text-muted-foreground h-10 w-10 shrink-0 p-2" />
                            )}
                            <a
                                href={attachment.url}
                                target="_blank"
                                rel="noopener"
                                className="min-w-0 flex-1 truncate text-sm underline-offset-2 hover:underline"
                            >
                                {attachment.original_name}
                            </a>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="text-muted-foreground h-8 w-8 shrink-0"
                                onClick={() => setRemoving({ id: attachment.id, original_name: attachment.original_name })}
                                aria-label={`Hapus lampiran ${attachment.original_name}`}
                            >
                                <Trash2 className="h-4 w-4" />
                            </Button>
                        </li>
                    ))}
                </ul>
            )}

            {atLimit ? (
                <p className="text-muted-foreground text-xs">Maksimal 5 lampiran</p>
            ) : (
                <>
                    <Button type="button" variant="outline" size="sm" disabled={uploading} onClick={() => fileInputRef.current?.click()}>
                        <Paperclip className="h-4 w-4" />
                        Tambah lampiran
                    </Button>
                    <p className="text-muted-foreground text-xs">JPG, PNG, WebP, atau PDF, maksimal 5 MB.</p>
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/webp,application/pdf"
                        hidden
                        onChange={onFileChange}
                    />
                </>
            )}

            <Dialog open={removing !== null} onOpenChange={(open) => !open && setRemoving(null)}>
                <DialogContent className="sm:max-w-[420px]">
                    <DialogHeader>
                        <DialogTitle>Hapus lampiran ini?</DialogTitle>
                        <DialogDescription>{removing?.original_name} akan dihapus permanen dari transaksi ini.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => setRemoving(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={confirmRemove}>
                            Hapus lampiran
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
