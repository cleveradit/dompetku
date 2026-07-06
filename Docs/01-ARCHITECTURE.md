# Tech & Architecture: Dompetku

> Aturan main teknis. Presentation layer memakai **Inertia + React** demi kualitas UI kelas aplikasi konsumen (lihat 05-DESIGN.md); backend Laravel dengan modular DDD. Dilarang mengganti framework, menambah library besar, atau mengubah struktur folder tanpa persetujuan pemilik proyek.

## 1. Stack dan Versi

### Backend

| Layer | Teknologi | Versi | Catatan |
|---|---|---|---|
| Bahasa | PHP | 8.3 | `declare(strict_types=1);` di semua file |
| Framework | Laravel | 12.x | Basis proyek: starter kit resmi Laravel 12 + React |
| Auth | Laravel Fortify | 1.x | Headless: register, login, verifikasi email, reset & ganti password |
| Uang | brick/money + ext-bcmath | terbaru | SEMUA aritmetika nominal lewat Money object; float DILARANG |
| Database | MySQL | 8.0 | `utf8mb4`, collation `utf8mb4_unicode_ci` |
| Cache/queue/session | Redis | 7.x | Queue untuk export & email; scheduler untuk recurring |
| Export | maatwebsite/excel | 3.x | CSV & XLSX (Fase 2) |
| Dev env | Docker via Laravel Sail | terbaru | Service: `laravel.test`, `mysql`, `redis`, `mailpit` |

### Frontend

| Layer | Teknologi | Versi | Catatan |
|---|---|---|---|
| Bridge | Inertia.js | 2.x | SPA tanpa API terpisah; data via props, form via `useForm` |
| UI | React + TypeScript | 19.x / 5.x | TS `strict: true` |
| CSS | Tailwind CSS | 4.x | Token desain dari 05-DESIGN.md dipetakan ke `@theme` |
| Komponen | shadcn/ui (Radix) | terbaru | Di-styling ulang sesuai 05-DESIGN.md, bukan tampilan default |
| Ikon | lucide-react | terbaru | |
| Grafik | Recharts | 2.x | Satu-satunya library chart |
| Build | Vite + Node 20 LTS | | |

### Kualitas & CI

| Alat | Aturan |
|---|---|
| Pest 3 | Feature test semua Action & AC |
| Larastan | Level 6 minimum |
| Pint | Preset `laravel` |
| ESLint + Prettier | Konfigurasi starter kit |
| Playwright | E2E smoke di viewport 360/768/1280 (login, catat transaksi, laporan) |
| GitHub Actions | pint -> larastan -> pest -> eslint+tsc -> build -> playwright -> composer/npm audit |

## 2. Pendekatan Arsitektur

**Modular monolith dengan DDD pragmatis.** Satu aplikasi Laravel, domain dipisah per bounded context di `app-modules/`. Aturan inti:

- Logika bisnis hanya di **Action class** (Application layer). Controller Inertia, model, dan komponen React DILARANG berisi logika bisnis.
- Eloquent model boleh dipakai langsung sebagai persistence model di Infrastructure (tidak memaksakan repository pattern penuh).
- Komunikasi antar modul hanya lewat Action/Query modul lain atau Domain Event. DILARANG relasi Eloquent lintas modul; cukup simpan foreign key ID.
- Frontend React adalah lapisan presentasi murni: menerima props dari Inertia, mengirim form ke controller. Tidak ada fetch/axios langsung ke endpoint sendiri.

## 3. Bounded Context

| Modul | Tanggung jawab | Entitas utama |
|---|---|---|
| **Identity** | Registrasi, login, verifikasi email, reset & ganti password, profil, mata uang akun, hapus akun | User |
| **Wallet** | CRUD dompet, arsip, saldo berjalan (cache), rekalkulasi | Wallet |
| **Ledger** | Transaksi income/expense, transfer, kategori, transaksi berulang, lampiran, import CSV | Transaction, Category, RecurringTransaction, Attachment |
| **Budget** | CRUD anggaran bulanan per kategori | Budget |
| **Reporting** | Query agregasi read-only: laporan interval, breakdown, dashboard, progres anggaran, tren saldo, export | (tidak punya tabel sendiri) |
| **Shared** | Value object & util lintas modul | Money helper, DatePeriod, enum ReportInterval |

Arah dependensi (panah = boleh depend ke):

```
Reporting ──▶ Budget ──▶ Ledger ──▶ Wallet ──▶ Identity
     └──────────┴──────────┴──────────┴───────────┘
                          ▼
                        Shared
```

- `Identity` tidak tahu modul lain.
- `Reporting` hanya membaca; ia yang menghitung progres anggaran (Budget hanya menyimpan angka anggaran).
- Satu-satunya jalur mengubah saldo dompet adalah Action `Wallet\AdjustWalletBalance`, dan hanya boleh dipanggil oleh Action di `Ledger`.

## 4. Struktur Folder

```
dompetku/
├── app/                              # Bootstrap Laravel standar, tipis
│   └── Providers/
├── app-modules/
│   ├── identity/
│   │   ├── src/
│   │   │   ├── Domain/Events/UserRegistered.php
│   │   │   ├── Application/Actions/{RegisterUser,ChangePassword,UpdateProfile,DeleteAccount}.php
│   │   │   ├── Infrastructure/Models/User.php
│   │   │   └── Presentation/Http/Controllers/{ProfileController,SettingsController}.php
│   │   ├── database/migrations/
│   │   ├── tests/
│   │   └── IdentityServiceProvider.php
│   ├── wallet/
│   │   ├── src/
│   │   │   ├── Domain/{Enums/WalletType.php, Exceptions/WalletHasTransactions.php}
│   │   │   ├── Application/Actions/{CreateWallet,UpdateWallet,ArchiveWallet,DeleteWallet,
│   │   │   │                        AdjustWalletBalance,RecalculateWalletBalance}.php
│   │   │   ├── Infrastructure/{Models/Wallet.php, Policies/WalletPolicy.php}
│   │   │   └── Presentation/Http/Controllers/WalletController.php
│   │   └── ...
│   ├── ledger/
│   │   ├── src/
│   │   │   ├── Domain/{Enums/{TransactionType,CategoryType,RecurringFrequency}.php,
│   │   │   │          Events/TransactionRecorded.php}
│   │   │   ├── Application/Actions/{RecordTransaction,UpdateTransaction,DeleteTransaction,
│   │   │   │        TransferBetweenWallets,SeedDefaultCategories,CreateCategory,UpdateCategory,
│   │   │   │        DeleteCategory,CreateRecurring,UpdateRecurring,ToggleRecurring,
│   │   │   │        RunDueRecurringTransactions,AttachReceipt,RemoveReceipt,ImportTransactionsCsv}.php
│   │   │   ├── Infrastructure/{Models/{Transaction,Category,RecurringTransaction,Attachment}.php,
│   │   │   │                   Policies/}
│   │   │   └── Presentation/Http/Controllers/{TransactionController,TransferController,
│   │   │                                      CategoryController,RecurringController,
│   │   │                                      AttachmentController}.php
│   │   └── ...
│   ├── budget/
│   │   ├── src/
│   │   │   ├── Application/Actions/{UpsertBudget,DeleteBudget,CopyBudgetsFromPreviousMonth}.php
│   │   │   ├── Infrastructure/{Models/Budget.php, Policies/BudgetPolicy.php}
│   │   │   └── Presentation/Http/Controllers/BudgetController.php
│   │   └── ...
│   ├── reporting/
│   │   ├── src/
│   │   │   ├── Application/Queries/{SpendingByPeriodQuery,SpendingByCategoryQuery,
│   │   │   │        SpendingByWalletQuery,DashboardSummaryQuery,BudgetProgressQuery,
│   │   │   │        BalanceTrendQuery}.php
│   │   │   ├── Application/Exports/TransactionsExport.php
│   │   │   └── Presentation/Http/Controllers/{ReportController,ExportController}.php
│   │   └── ...
│   └── shared/
│       └── src/{ValueObjects/DatePeriod.php, Enums/ReportInterval.php, Support/MoneyFormatter.php}
├── resources/
│   ├── js/
│   │   ├── app.tsx
│   │   ├── layouts/{AppLayout,AuthLayout,SettingsLayout}.tsx
│   │   ├── pages/
│   │   │   ├── auth/{login,register,forgot-password,reset-password,verify-email}.tsx
│   │   │   ├── dashboard.tsx
│   │   │   ├── transactions/{index,form}.tsx
│   │   │   ├── wallets/index.tsx
│   │   │   ├── reports/index.tsx
│   │   │   ├── budgets/index.tsx
│   │   │   └── settings/{profile,password,appearance,account}.tsx
│   │   ├── components/
│   │   │   ├── ui/                    # shadcn/ui hasil styling ulang
│   │   │   └── domain/{AmountText,CategoryBadge,WalletCard,TransactionListItem,
│   │   │              BudgetProgressBar,PeriodPicker,EmptyState}.tsx
│   │   ├── lib/{money.ts,date.ts}
│   │   └── types/index.d.ts
│   └── lang/id/
├── database/                          # seeder global saja
├── tests/                             # Playwright e2e + smoke lintas modul
├── docker-compose.yml
└── .github/workflows/ci.yml
```

Autoload PSR-4 di `composer.json`:

```json
"autoload": {
  "psr-4": {
    "App\\": "app/",
    "Modules\\Identity\\": "app-modules/identity/src/",
    "Modules\\Wallet\\": "app-modules/wallet/src/",
    "Modules\\Ledger\\": "app-modules/ledger/src/",
    "Modules\\Budget\\": "app-modules/budget/src/",
    "Modules\\Reporting\\": "app-modules/reporting/src/",
    "Modules\\Shared\\": "app-modules/shared/src/"
  }
}
```

## 5. Konvensi Kode

1. Satu Action = satu use case = satu method publik `handle()`. Input DTO/parameter bertipe, output model/DTO, gagal = domain exception.
2. Mutasi multi-tabel (transaksi + saldo) WAJIB `DB::transaction()` + `lockForUpdate()` pada baris wallet, di dalam Action.
3. Controller Inertia hanya: authorize (Policy), validasi (FormRequest), panggil Action/Query, `Inertia::render` atau redirect. Maksimal ~15 baris per method.
4. Query laporan di modul Reporting memakai agregasi SQL (`SUM`, `GROUP BY`); dilarang menjumlah koleksi di PHP.
5. Nominal: DECIMAL di DB, string/Money object di PHP, string di props Inertia, diformat di frontend lewat `lib/money.ts`. Tidak pernah `float`/`parseFloat` untuk aritmetika.
6. Recurring dijalankan `RunDueRecurringTransactions` lewat scheduler harian (`schedule:work` di Sail, cron di produksi), idempotent (aman dijalankan dua kali).
7. Setiap Action minimal satu test happy path dan satu failure path.
8. Migration berada di modulnya, didaftarkan lewat ServiceProvider modul.
9. Semua string UI Bahasa Indonesia via `lang/id/` dan konstanta frontend; kode (class, variabel, komentar) English.
10. Komponen React mengikuti token dan spesifikasi 05-DESIGN.md; dilarang memakai warna/ukuran hardcode di luar token.
