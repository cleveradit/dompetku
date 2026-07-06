import { defineConfig } from '@playwright/test';

/**
 * 04-NFR.md Q-6: smoke E2E register -> verifikasi -> buat dompet -> catat
 * transaksi -> lihat laporan, pada viewport 360/768/1280, tema light & dark.
 * Dijalankan di dalam container Sail (baseURL localhost, Mailpit API di
 * http://mailpit:8025).
 */
export default defineConfig({
    testDir: './e2e',
    timeout: 90_000,
    retries: 1,
    workers: 1,
    reporter: [['list']],
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://localhost',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'mobile-360-light', use: { viewport: { width: 360, height: 740 }, colorScheme: 'light' } },
        { name: 'mobile-360-dark', use: { viewport: { width: 360, height: 740 }, colorScheme: 'dark' } },
        { name: 'tablet-768-light', use: { viewport: { width: 768, height: 1024 }, colorScheme: 'light' } },
        { name: 'tablet-768-dark', use: { viewport: { width: 768, height: 1024 }, colorScheme: 'dark' } },
        { name: 'desktop-1280-light', use: { viewport: { width: 1280, height: 800 }, colorScheme: 'light' } },
        { name: 'desktop-1280-dark', use: { viewport: { width: 1280, height: 800 }, colorScheme: 'dark' } },
    ],
});
