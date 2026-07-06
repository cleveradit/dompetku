import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

/** US-20: hapus akun beserta seluruh data, konfirmasi password (04-NFR.md S-13). */
export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm({ password: '' });

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('account.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        clearErrors();
        reset();
    };

    return (
        <div className="space-y-6">
            <HeadingSmall title="Zona berbahaya" description="Hapus akun beserta seluruh datanya" />
            <div className="border-destructive/30 bg-destructive/5 space-y-4 rounded-lg border p-4">
                <div className="text-destructive relative space-y-0.5">
                    <p className="font-medium">Hati-hati</p>
                    <p className="text-sm">Seluruh dompet, transaksi, kategori, anggaran, dan lampiranmu akan terhapus permanen.</p>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button variant="destructive">Hapus akun</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>Yakin mau menghapus akunmu?</DialogTitle>
                        <DialogDescription>
                            Setelah akun dihapus, seluruh datamu ikut terhapus permanen dan tidak bisa dikembalikan. Masukkan password untuk
                            melanjutkan.
                        </DialogDescription>
                        <form className="space-y-6" onSubmit={deleteUser}>
                            <div className="grid gap-2">
                                <Label htmlFor="password" className="sr-only">
                                    Password
                                </Label>

                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    ref={passwordInput}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Password"
                                    autoComplete="current-password"
                                />

                                <InputError message={errors.password} />
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button variant="secondary" onClick={closeModal}>
                                        Batal
                                    </Button>
                                </DialogClose>

                                <Button variant="destructive" disabled={processing} asChild>
                                    <button type="submit">Hapus akun</button>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
