# Non-Functional Requirements: Dompetku

> Batasan lintas fitur. Jika requirement di sini bentrok dengan kemudahan implementasi, requirement ini yang menang.

## 1. Role & Permission

Dua peran saja:

| Role | Deskripsi |
|---|---|
| `guest` | Belum login: hanya login, register, lupa/reset password, verifikasi email |
| `user` | Terautentikasi + terverifikasi email: full CRUD atas data MILIKNYA SENDIRI |

Tidak ada role admin. Jangan membuat panel admin, manajemen user, atau kolom `role`.

| Aksi | guest | user |
|---|---|---|
| Register / login / reset password | ✅ | - |
| Dashboard, laporan, tren | ❌ | ✅ |
| CRUD dompet / transaksi / transfer / kategori | ❌ | ✅ (aturan hapus: invariant I-6, I-7) |
| CRUD anggaran / recurring / lampiran | ❌ | ✅ |
| Export / import | ❌ | ✅ |
| Pengaturan, ganti password, hapus akun | ❌ | ✅ |

**Aturan isolasi data (paling penting):**

1. Semua query ke `wallets`, `categories`, `transactions`, `budgets`, `recurring_transactions`, `attachments` WAJIB terfilter `user_id = auth()->id()` (attachment lewat transaksi induknya). Implementasi: global scope `OwnedByUser` + Policy per model.
2. Policy terdaftar untuk semua model di atas; setiap controller method memanggil `authorize`.
3. Feature test wajib per resource: user B mengakses resource user A via ID/URL langsung (termasuk URL file lampiran dan file export) = 403/404, tidak pernah 200.
4. Jangan mengandalkan "ID sulit ditebak" sebagai kontrol akses.

## 2. Aturan Validasi

Semua validasi server side (FormRequest). Client side hanya pelengkap UX.

**Aturan nominal (berlaku untuk semua field uang):** wajib angka, maksimal 2 desimal (`decimal:0,2`), diterima dari frontend sebagai string dengan titik desimal (frontend menangani format tampilan id-ID), min 0.01 kecuali disebut lain, max 999999999999.99.

| Entitas | Field | Aturan |
|---|---|---|
| User | name | required, string, max:100 |
| User | email | required, email:rfc, max:255, unique:users |
| User | password | required, min:8, confirmed; ganti password: `current_password` wajib |
| User | currency | required, in: daftar ISO 4217 yang didukung (minimal IDR, USD, EUR, SGD, MYR) |
| Wallet | name | required, max:50, unique per user (abaikan soft-deleted) |
| Wallet | type | required, in: cash, bank, ewallet, other |
| Wallet | initial_balance | aturan nominal, min:0 |
| Wallet | color / icon | nullable; color regex `#RRGGBB`; icon dalam whitelist ikon 05-DESIGN.md |
| Category | name | required, max:50, unique per (user, type) |
| Category | type | required, in: income, expense; immutable setelah dipakai |
| Transaction | type | required, in: income, expense, transfer |
| Transaction | wallet_id | required, milik user, tidak arsip, tidak soft-deleted |
| Transaction | destination_wallet_id | required_if transfer, prohibited selainnya, != wallet_id, milik user, tidak arsip |
| Transaction | category_id | required_if income/expense, prohibited transfer, milik user, tipe cocok |
| Transaction | amount | aturan nominal |
| Transaction | description | nullable, max:255 |
| Transaction | occurred_on | required, date, <= hari ini (Asia/Jakarta) |
| Budget | category_id | required, kategori expense milik user |
| Budget | month | required, date, dinormalisasi ke tanggal 1 |
| Budget | amount | aturan nominal |
| Recurring | (field transaksi) | sama dengan Transaction (invariant I-9) |
| Recurring | frequency | required, in: daily, weekly, monthly, yearly |
| Recurring | interval | required, integer, min:1, max:365 |
| Recurring | next_run_on / end_on | date; end_on nullable dan > tanggal mulai |
| Attachment | file | required, mimetypes: image/jpeg, image/png, image/webp, application/pdf (cek isi via finfo, bukan ekstensi), max:5120 KB, max 5 per transaksi |
| Import | file | required, mimes:csv,txt, max:2048 KB, header sesuai template |
| Laporan | rentang custom | start <= end; maksimal 366 hari |

Semua input string di-trim; output di-escape (default React/Blade; dilarang `dangerouslySetInnerHTML` untuk data user).

## 3. Penanganan Uang (aturan khusus)

| # | Requirement |
|---|---|
| M-1 | DB: DECIMAL(15,2). PHP: brick/money atau string + bcmath. JS: nominal dikirim/diterima sebagai string; format tampilan via `Intl.NumberFormat` di `lib/money.ts`. `float`, `parseFloat`, dan aritmetika number JS untuk uang DILARANG |
| M-2 | Pembulatan hanya saat menampilkan persentase; nilai tersimpan tidak pernah dibulatkan diam-diam |
| M-3 | Test presisi wajib: penjumlahan berulang 0.10; nilai 0.1 + 0.2 harus 0.30; export/import bolak-balik tidak mengubah nilai |

## 4. Keamanan

| # | Requirement |
|---|---|
| S-1 | Password hash bcrypt (default Laravel); tidak pernah dilog atau di-response |
| S-2 | Rate limit: login 5/menit per email+IP; register & lupa-password 3/menit per IP; endpoint tulis 60/menit per user; upload 10/menit per user |
| S-3 | Ganti/reset password meng-invalidate session lain (`logoutOtherDevices` / hapus session tersimpan) |
| S-4 | CSRF aktif untuk semua request tulis (bawaan Laravel + Inertia, jangan di-disable) |
| S-5 | Session cookie `HttpOnly`, `Secure` (production), `SameSite=Lax`; regenerate setelah login |
| S-6 | `$fillable` eksplisit di semua model; `$guarded = []` dilarang |
| S-7 | Query selalu binding; raw SQL dengan interpolasi string dilarang |
| S-8 | Upload: file disimpan di disk privat (bukan `public/`), nama file di-random, disajikan hanya lewat route ter-otorisasi (streamed response), validasi mime dari isi file |
| S-9 | Anti user-enumeration: pesan lupa-password dan login tidak membedakan email terdaftar/tidak |
| S-10 | Log tidak memuat data finansial ataupun kredensial; error production tanpa stack trace ke user (`APP_DEBUG=false`) |
| S-11 | Production HTTPS; header `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin` |
| S-12 | `composer audit` + `npm audit` di CI; gagal pada vulnerability high/critical |
| S-13 | Hapus akun: memakai `DELETE` dengan konfirmasi password; penghapusan file lampiran fisik ikut dieksekusi |

## 5. Performa

Asumsi beban: 1 user hingga 20 dompet, 100 kategori, 100.000 transaksi, 50 recurring, 24 bulan anggaran.

| # | Requirement |
|---|---|
| P-1 | p95 server response < 400 ms untuk dashboard, daftar transaksi, laporan pada dataset di atas |
| P-2 | Agregasi via SQL dengan index dari 02-DATABASE.md; dilarang memuat semua transaksi ke memori |
| P-3 | Daftar transaksi: pagination server side 25/halaman (infinite scroll di mobile memakai pagination yang sama) |
| P-4 | Anti N+1: eager loading eksplisit; `Model::preventLazyLoading()` aktif di non-production |
| P-5 | Total saldo dashboard membaca `wallets.current_balance` (cache), bukan agregasi on-the-fly |
| P-6 | Bundle JS awal < 300 KB gzip; halaman laporan boleh lazy-load chunk chart; gambar lampiran lazy-load + thumbnail |
| P-7 | Export > 10.000 baris lewat queue; UI tidak nge-block |
| P-8 | Scheduler recurring selesai < 1 menit untuk 10.000 recurring jatuh tempo (query berbasis index `(is_active, next_run_on)`, proses per chunk) |

## 6. Reliabilitas & Integritas Data

| # | Requirement |
|---|---|
| R-1 | Semua operasi multi-tabel `DB::transaction()` + `lockForUpdate()` pada wallet (02-DATABASE.md bagian 4) |
| R-2 | `wallets:recalculate` tersedia, ter-test, dan idempotent |
| R-3 | Recurring generator idempotent (invariant I-11) dan melakukan catch-up hari terlewat |
| R-4 | Soft delete untuk wallets, categories, transactions; hard delete hanya lewat hapus akun |
| R-5 | Timezone aplikasi `Asia/Jakarta`; `occurred_on` DATE tanpa konversi timezone |
| R-6 | Semua perubahan skema lewat migration |

## 7. Kualitas Kode & Testing

| # | Requirement |
|---|---|
| Q-1 | Setiap Action: test happy path + failure path; setiap AC di 03-USER-STORIES.md punya test |
| Q-2 | Coverage >= 80% untuk `app-modules/*/src/Application` dan `Domain` |
| Q-3 | CI: Pint, Larastan level 6, Pest, ESLint + `tsc --noEmit`, build Vite, Playwright, audit. Merge ke `main` hanya jika hijau |
| Q-4 | Tidak ada logika bisnis di controller, model, atau komponen React |
| Q-5 | String UI via `lang/id/` (backend) dan modul teks terpusat (frontend); tanpa hardcode tersebar |
| Q-6 | Playwright smoke: register-verifikasi(login) -> buat dompet -> catat transaksi -> lihat laporan, dijalankan pada viewport 360, 768, 1280, tema light dan dark |

## 8. Usability & Aksesibilitas

Detail visual di 05-DESIGN.md; berikut ambang yang bisa diuji:

| # | Requirement |
|---|---|
| U-1 | Breakpoint didukung: 360px, 768px, 1280px+; tanpa horizontal scroll di 360px |
| U-2 | Target sentuh >= 44x44 px di mobile |
| U-3 | Kontras teks memenuhi WCAG AA (4.5:1 teks normal, 3:1 teks besar) di tema light dan dark |
| U-4 | Semua fungsi dapat dioperasikan keyboard; focus ring terlihat; `prefers-reduced-motion` dihormati (animasi dimatikan) |
| U-5 | Nominal diformat sesuai mata uang akun (IDR: `Rp1.250.000,50`); input nominal memakai masking ribuan dan koma desimal |
| U-6 | Tanggal format `d MMM yyyy` locale id |
| U-7 | Setiap aksi tulis memberi feedback (toast); aksi destruktif memakai dialog konfirmasi; hapus akun memakai konfirmasi password |
| U-8 | Empty state informatif untuk: belum ada dompet, belum ada transaksi, laporan kosong, hasil filter kosong, belum ada anggaran, belum ada recurring |

## 9. Definition of Done (per fitur)

1. Semua AC fitur lulus automated test (Pest, dan Playwright untuk AC [E2E]).
2. Larastan, Pint, ESLint, tsc hijau.
3. Aturan isolasi data (bagian 1) ter-test untuk resource baru.
4. UI diperiksa pada 360px dan 1280px, tema light dan dark, dan sesuai 05-DESIGN.md.
5. Tidak menambah scope di luar PRD (bagian Out of Scope Permanen).
