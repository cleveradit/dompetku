import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type SharedData } from '@/types';

interface ProfileProps {
    currencies: Record<string, string>;
    status?: string;
}

export default function Profile({ currencies, status }: ProfileProps) {
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: auth.user.name,
        email: auth.user.email,
        currency: auth.user.currency,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'), { preserveScroll: true });
    };

    return (
        <AppLayout title="Pengaturan">
            <Head title="Pengaturan profil" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Profil" description="Nama, email, dan mata uang tampilan" />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>

                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                placeholder="Nama lengkap"
                                maxLength={100}
                            />

                            <InputError className="mt-2" message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>

                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoComplete="username"
                                placeholder="email@contoh.com"
                            />

                            <InputError className="mt-2" message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="currency">Mata uang</Label>
                            <Select value={data.currency} onValueChange={(value) => setData('currency', value)}>
                                <SelectTrigger id="currency">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(currencies).map(([code, label]) => (
                                        <SelectItem key={code} value={code}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-muted-foreground text-xs">
                                Hanya mengubah format tampilan (simbol & pemisah); nilai angka tidak dikonversi.
                            </p>
                            <InputError className="mt-2" message={errors.currency} />
                        </div>

                        {auth.user.email_verified_at === null && (
                            <div>
                                <p className="text-muted-foreground mt-2 text-sm">
                                    Emailmu belum terverifikasi.{' '}
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="text-primary underline underline-offset-4"
                                    >
                                        Kirim ulang email verifikasi
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="text-income mt-2 text-sm font-medium">
                                        Link verifikasi baru sudah dikirim ke emailmu.
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Simpan</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-muted-foreground text-sm">Tersimpan</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
