import { Head } from '@inertiajs/react';

import DeleteUser from '@/components/delete-user';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

export default function Account() {
    return (
        <AppLayout title="Pengaturan">
            <Head title="Akun" />

            <SettingsLayout>
                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
