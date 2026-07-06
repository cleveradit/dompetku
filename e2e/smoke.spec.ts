import { expect, Page, test } from '@playwright/test';

const MAILPIT_API = process.env.E2E_MAILPIT_API ?? 'http://mailpit:8025';

/**
 * Smoke lengkap (04-NFR.md Q-6): register -> verifikasi email (via Mailpit)
 * -> buat dompet pertama -> catat transaksi lewat FAB -> lihat laporan.
 */
test('alur inti Dompetku dari registrasi sampai laporan', async ({ page }, testInfo) => {
    const unique = `${testInfo.project.name}-${Date.now()}`;
    const email = `e2e-${unique}@contoh.com`.toLowerCase();
    const isDesktop = (testInfo.project.use.viewport?.width ?? 0) >= 1280;

    // 1. Registrasi
    await page.goto('/register');
    await expect(page.getByRole('heading', { name: 'Buat akun' })).toBeVisible();
    await page.getByLabel('Nama').fill('Pengguna E2E');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password', { exact: true }).fill('rahasia-e2e-123');
    await page.getByLabel('Konfirmasi password').fill('rahasia-e2e-123');
    await page.getByRole('button', { name: 'Buat akun' }).click();

    // 2. Halaman "cek email kamu" lalu verifikasi via link di Mailpit
    await expect(page.getByRole('heading', { name: 'Cek email kamu' })).toBeVisible({ timeout: 15_000 });
    const verifyUrl = await fetchVerificationLink(page, email);
    await page.goto(verifyUrl);

    // 3. Onboarding dompet pertama
    await expect(page.getByRole('heading', { name: 'Buat dompet pertamamu' })).toBeVisible({ timeout: 15_000 });
    await page.getByLabel('Nama dompet').fill('BCA E2E');
    await page.getByLabel('Saldo awal').fill('1000000');
    await page.getByRole('button', { name: 'Buat dompet' }).click();

    // 4. Dashboard tampil dengan saldo
    await expect(page).toHaveURL(/dashboard/, { timeout: 15_000 });
    await expect(page.getByText('Total saldo')).toBeVisible();
    await expect(page.getByText('Rp1.000.000').first()).toBeVisible();

    // 5. Catat transaksi lewat FAB / tombol Catat
    if (isDesktop) {
        await page.getByRole('button', { name: 'Catat', exact: true }).click();
    } else {
        // FAB (aria-label) — bukan tombol empty state yang bertuliskan sama.
        await page.getByLabel('Catat transaksi').click();
    }
    await expect(page.getByRole('heading', { name: 'Catat transaksi' })).toBeVisible();
    // Ketik digit satu per satu (mensimulasikan user asli) agar regresi masking ribuan terdeteksi.
    await page.locator('#tx-amount').pressSequentially('250000');
    await expect(page.locator('#tx-amount')).toHaveValue('250.000');
    await page.getByRole('button', { name: 'Makan & Minum' }).click();
    await page.getByRole('button', { name: 'Simpan' }).click();

    // Toast sukses + saldo terpotong
    await expect(page.getByText('Transaksi tersimpan')).toBeVisible({ timeout: 15_000 });
    await expect(page.getByText('Rp750.000').first()).toBeVisible({ timeout: 15_000 });

    // 6. Laporan menampilkan pengeluaran
    await page.goto('/reports');
    await expect(page.getByText('Keluar', { exact: true }).first()).toBeVisible();
    await expect(page.getByText('Rp250.000').first()).toBeVisible();

    // Tidak ada horizontal scroll (04-NFR.md U-1)
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
    expect(overflow).toBeLessThanOrEqual(0);
});

async function fetchVerificationLink(page: Page, email: string): Promise<string> {
    for (let attempt = 0; attempt < 20; attempt++) {
        const search = await page.request.get(`${MAILPIT_API}/api/v1/search?query=to:${encodeURIComponent(email)}`);
        const body = (await search.json()) as { messages?: { ID: string }[] };

        if (body.messages && body.messages.length > 0) {
            const message = await page.request.get(`${MAILPIT_API}/api/v1/message/${body.messages[0].ID}`);
            const detail = (await message.json()) as { Text?: string; HTML?: string };
            const haystack = `${detail.Text ?? ''}\n${detail.HTML ?? ''}`;
            const match = haystack.match(/https?:\/\/[^\s"'<>]*email\/verify[^\s"'<>]*/);
            if (match) {
                return match[0].replace(/&amp;/g, '&');
            }
        }

        await page.waitForTimeout(1000);
    }

    throw new Error(`Email verifikasi untuk ${email} tidak ditemukan di Mailpit.`);
}
