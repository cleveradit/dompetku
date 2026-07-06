import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

export default function Appearance() {
    return (
        <AppLayout title="Pengaturan">
            <Head title="Tampilan" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Tampilan" description="Pilih tema terang, gelap, atau ikuti sistem" />
                    <AppearanceTabs />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
