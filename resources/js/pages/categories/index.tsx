import { Head, router, useForm } from '@inertiajs/react';
import { LoaderCircle, MoreVertical, Pencil, Plus, Tag, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import ColorSwatches from '@/components/domain/color-swatches';
import EmptyState from '@/components/domain/empty-state';
import IconPicker from '@/components/domain/icon-picker';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { iconByName, RUPIAH_PALETTE } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { Category, CategoryType } from '@/types';

interface CategoriesPageProps {
    categories: Category[];
}

export default function CategoriesIndex({ categories }: CategoriesPageProps) {
    const [creating, setCreating] = useState(false);
    const [editing, setEditing] = useState<Category | null>(null);
    const [deleting, setDeleting] = useState<Category | null>(null);

    const expenses = categories.filter((category) => category.type === 'expense');
    const incomes = categories.filter((category) => category.type === 'income');

    const confirmDelete = () => {
        if (!deleting) return;
        router.delete(route('categories.destroy', deleting.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AppLayout
            title="Kategori"
            headerAction={
                <Button size="sm" variant="outline" onClick={() => setCreating(true)}>
                    <Plus className="h-4 w-4" />
                    Kategori baru
                </Button>
            }
        >
            <Head title="Kategori" />

            {categories.length === 0 ? (
                <EmptyState icon={Tag} message="Belum ada kategori." action={<Button onClick={() => setCreating(true)}>Buat kategori</Button>} />
            ) : (
                <div className="flex flex-col gap-6 py-2 pb-6">
                    <CategoryGroup title="Pengeluaran" categories={expenses} onEdit={setEditing} onDelete={setDeleting} />
                    <CategoryGroup title="Pemasukan" categories={incomes} onEdit={setEditing} onDelete={setDeleting} />
                </div>
            )}

            <Dialog open={creating} onOpenChange={setCreating}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>Kategori baru</DialogTitle>
                        <DialogDescription>Kelompokkan transaksimu supaya polanya terlihat.</DialogDescription>
                    </DialogHeader>
                    <CategoryForm onSuccess={() => setCreating(false)} />
                </DialogContent>
            </Dialog>

            <Dialog open={editing !== null} onOpenChange={(open) => !open && setEditing(null)}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>Edit kategori</DialogTitle>
                        <DialogDescription>Tipe kategori terkunci bila sudah dipakai transaksi.</DialogDescription>
                    </DialogHeader>
                    {editing && <CategoryForm category={editing} onSuccess={() => setEditing(null)} />}
                </DialogContent>
            </Dialog>

            <Dialog open={deleting !== null} onOpenChange={(open) => !open && setDeleting(null)}>
                <DialogContent className="sm:max-w-[420px]">
                    <DialogHeader>
                        <DialogTitle>Hapus kategori {deleting?.name}?</DialogTitle>
                        <DialogDescription>
                            Kategori yang masih dipakai transaksi, transaksi berulang, atau anggaran tidak bisa dihapus.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => setDeleting(null)}>
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            Hapus kategori
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function CategoryGroup({
    title,
    categories,
    onEdit,
    onDelete,
}: {
    title: string;
    categories: Category[];
    onEdit: (category: Category) => void;
    onDelete: (category: Category) => void;
}) {
    if (categories.length === 0) return null;

    return (
        <section>
            <h2 className="text-muted-foreground px-2 pb-2 text-xs font-medium tracking-wide uppercase">{title}</h2>
            <div className="bg-card border-border divide-border divide-y rounded-xl border">
                {categories.map((category) => {
                    const Icon = iconByName(category.icon);
                    return (
                        <div key={category.id} className="flex min-h-14 items-center gap-3 px-3 py-2">
                            <span
                                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                style={{
                                    backgroundColor: `color-mix(in srgb, ${category.color ?? 'var(--primary)'} 12%, transparent)`,
                                    color: category.color ?? 'var(--primary)',
                                }}
                            >
                                <Icon className="h-5 w-5" />
                            </span>
                            <span className="flex min-w-0 flex-1 items-center gap-2">
                                <span className="truncate text-sm font-medium">{category.name}</span>
                                {category.is_default && (
                                    <Badge variant="secondary" className="text-[10px]">
                                        Bawaan
                                    </Badge>
                                )}
                            </span>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <button
                                        className="text-muted-foreground hover:bg-secondary flex h-9 w-9 items-center justify-center rounded-lg"
                                        aria-label={`Aksi untuk ${category.name}`}
                                    >
                                        <MoreVertical className="h-4 w-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem onClick={() => onEdit(category)}>
                                        <Pencil className="mr-2 h-4 w-4" />
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuItem className="text-destructive" onClick={() => onDelete(category)}>
                                        <Trash2 className="mr-2 h-4 w-4" />
                                        Hapus
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}

function CategoryForm({ category, onSuccess }: { category?: Category; onSuccess: () => void }) {
    const isEdit = category !== undefined;

    const { data, setData, post, patch, processing, errors } = useForm({
        name: category?.name ?? '',
        type: (category?.type ?? 'expense') as CategoryType,
        color: category?.color ?? RUPIAH_PALETTE[0].hex,
        icon: category?.icon ?? 'sparkles',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (isEdit) {
            patch(route('categories.update', category.id), { onSuccess, preserveScroll: true });
        } else {
            post(route('categories.store'), { onSuccess, preserveScroll: true });
        }
    };

    return (
        <form className="flex flex-col gap-5" onSubmit={submit}>
            <div className="grid gap-2">
                <Label htmlFor="category-name">Nama kategori</Label>
                <Input
                    id="category-name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Contoh: Kopi, Langganan, Olahraga"
                    maxLength={50}
                    required
                    autoFocus={!isEdit}
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label>Tipe</Label>
                <div className="bg-secondary grid grid-cols-2 gap-1 rounded-lg p-1">
                    {(['expense', 'income'] as CategoryType[]).map((type) => (
                        <button
                            key={type}
                            type="button"
                            onClick={() => setData('type', type)}
                            className={cn(
                                'min-h-9 rounded-md text-sm font-semibold transition-colors',
                                data.type === type
                                    ? type === 'expense'
                                        ? 'bg-card text-expense shadow-xs'
                                        : 'bg-card text-income shadow-xs'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {type === 'expense' ? 'Pengeluaran' : 'Pemasukan'}
                        </button>
                    ))}
                </div>
                <InputError message={errors.type} />
            </div>

            <div className="grid gap-2">
                <Label>Warna</Label>
                <ColorSwatches value={data.color} onChange={(hex) => setData('color', hex)} />
                <InputError message={errors.color} />
            </div>

            <div className="grid gap-2">
                <Label>Ikon</Label>
                <IconPicker value={data.icon} onChange={(name) => setData('icon', name)} accentColor={data.color} />
                <InputError message={errors.icon} />
            </div>

            <Button type="submit" className="w-full" disabled={processing}>
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                Simpan
            </Button>
        </form>
    );
}
