import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, MailCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <AuthLayout title="Cek email kamu" description="Kami sudah mengirim link verifikasi ke emailmu. Klik link itu untuk mulai memakai Dompetku.">
            <Head title="Verifikasi email" />

            <div className="mb-6 flex justify-center">
                <div className="bg-primary/10 text-primary flex h-14 w-14 items-center justify-center rounded-full">
                    <MailCheck className="h-7 w-7" />
                </div>
            </div>

            {status === 'verification-link-sent' && (
                <div className="text-income mb-4 text-center text-sm font-medium">
                    Link verifikasi baru sudah dikirim ke emailmu.
                </div>
            )}

            <form onSubmit={submit} className="space-y-6 text-center">
                <Button disabled={processing} variant="secondary" className="w-full">
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    Kirim ulang email verifikasi
                </Button>

                <TextLink href={route('logout')} method="post" className="mx-auto block text-sm">
                    Keluar
                </TextLink>
            </form>
        </AuthLayout>
    );
}
