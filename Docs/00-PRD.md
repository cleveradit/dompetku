# PRD: Dompetku (Personal Finance App)

> Dokumen acuan utama. Jika ada konflik antar dokumen, urutan prioritas: 00-PRD > 04-NFR > 02-DATABASE > 05-DESIGN > 01-ARCHITECTURE > 03-USER-STORIES.

## 1. Visi Produk

Dompetku adalah aplikasi web keuangan pribadi yang **lengkap**: mencatat pemasukan dan pengeluaran di banyak dompet, memahami pola pengeluaran per periode, mengendalikan anggaran, dan mengotomasi pencatatan rutin. Kualitas pengalaman pengguna setara aplikasi konsumen komersial, bukan admin panel.

- **Platform**: Web responsif (mobile-first), nyaman di HP 360px sampai desktop besar.
- **Target user**: Individu. Data privat per akun, tidak ada sharing.
- **Nominal**: mendukung **2 angka desimal** (contoh: 10.500,75). Mata uang dipilih per akun saat registrasi (default IDR), memengaruhi format tampilan.
- **Bahasa UI**: Bahasa Indonesia.

## 2. Masalah yang Diselesaikan

1. User tidak tahu ke mana uangnya pergi setiap periode.
2. Uang tersebar di banyak tempat (bank, e-wallet, tunai) sehingga sulit melihat gambaran utuh.
3. Pencatatan manual terasa merepotkan, sehingga user berhenti mencatat. Aplikasi harus membuat pencatatan secepat dan semenyenangkan mungkin (transaksi rutin diotomasi, input <= 10 detik).
4. Tahu pengeluaran saja tidak cukup; user butuh alat kendali (anggaran per kategori).

## 3. Goals dan Success Metrics

| Goal | Metric |
|---|---|
| Pencatatan cepat | Input transaksi baru dari layar mana pun: 1 tap ke form, selesai <= 10 detik untuk transaksi umum |
| Pemahaman pola | Laporan harian/mingguan/bulanan/tahunan/custom dengan breakdown kategori & dompet |
| Kendali anggaran | Progres anggaran per kategori terlihat real-time; status boros terlihat sebelum akhir bulan |
| Saldo selalu akurat | Saldo dompet identik dengan hasil rekalkulasi penuh, dibuktikan automated test |
| Nyaman jangka panjang | Transaksi rutin tercatat otomatis tanpa aksi user |

## 4. Ruang Lingkup (dibangun bertahap, semuanya WAJIB dibangun)

Fase adalah **urutan pengerjaan**, bukan pilihan. Aplikasi dianggap selesai setelah Fase 3.

### Fase 1: Fondasi

| Kode | Fitur |
|---|---|
| F-01 | Registrasi akun (nama, email, password, pilihan mata uang) + verifikasi email |
| F-02 | Login, logout, lupa password (reset via email) |
| F-03 | Ganti password (wajib password lama) |
| F-04 | Manajemen dompet: tambah, edit, arsip, hapus; warna & ikon per dompet; jumlah bebas |
| F-05 | Pencatatan transaksi income/expense: dompet, kategori, nominal desimal, tanggal, catatan |
| F-06 | Transfer antar dompet |
| F-07 | Kategori: default ter-seed, user bisa tambah/edit/hapus, dengan warna & ikon |
| F-08 | Laporan per interval: harian, mingguan, bulanan, tahunan, custom; breakdown kategori & dompet; grafik tren |
| F-09 | Dashboard: total saldo, kartu dompet, ringkasan bulan berjalan, transaksi terakhir |
| F-10 | UI responsif penuh + dark mode (mengikuti sistem, bisa dipaksa manual) |
| F-11 | Pengaturan akun: profil, mata uang & format tampilan |

### Fase 2: Kelengkapan

| Kode | Fitur |
|---|---|
| F-12 | Anggaran (budget) bulanan per kategori expense, dengan progres & indikator over-budget, salin dari bulan lalu |
| F-13 | Transaksi berulang (recurring): harian/mingguan/bulanan/tahunan, tercatat otomatis, bisa dijeda |
| F-14 | Pencarian & filter transaksi: teks, rentang tanggal, kategori, dompet, tipe, rentang nominal |
| F-15 | Lampiran struk pada transaksi (foto/PDF), maksimal 5 per transaksi |
| F-16 | Export transaksi ke CSV dan Excel mengikuti filter aktif |
| F-17 | Hapus akun beserta seluruh data (konfirmasi password) |

### Fase 3: Pemolesan

| Kode | Fitur |
|---|---|
| F-18 | Grafik tren saldo total (net balance) antar waktu |
| F-19 | Import transaksi dari CSV (template disediakan aplikasi) |
| F-20 | PWA installable (ikon home screen, splash), tetap butuh koneksi |

## 5. Out of Scope PERMANEN

Agentic AI DILARANG membangun hal berikut walaupun terasa berguna. Jika sebuah keputusan implementasi membutuhkannya, berhenti dan tanyakan ke pemilik proyek:

- Koneksi ke bank / open banking / scraping mutasi otomatis
- Multi-currency antar dompet dan konversi kurs (mata uang dipilih satu kali per akun untuk format tampilan; semua dompet memakai mata uang yang sama)
- Sharing dompet antar user, mode keluarga, atau multi-tenant
- AI insight, auto-kategorisasi, chatbot
- Aplikasi mobile native dan mode offline
- Investasi, portofolio saham/kripto, hutang-piutang, cicilan
- Notifikasi push; email hanya untuk verifikasi, reset password, dan (opsional Fase 3) ringkasan bulanan

## 6. Persona

**Radit, 30-an, pekerja kantoran.** Punya rekening bank, 2 e-wallet, dan uang tunai. Mencatat pengeluaran dari HP sesaat setelah membayar, sering dengan satu tangan. Review keuangan sebulan sekali dari laptop. Ingin tahu: total uang likuid, kategori terboros, dan apakah bulan ini melewati anggaran makan.

## 7. User Flow Utama

1. **Onboarding**: Register (pilih mata uang) -> verifikasi email -> login -> kategori default ter-seed -> diarahkan membuat dompet pertama -> dashboard.
2. **Pencatatan harian**: Dari layar mana pun -> tombol tambah (FAB) -> form transaksi (tipe, nominal, kategori, dompet, tanggal default hari ini) -> simpan -> kembali dengan toast sukses dan saldo ter-update.
3. **Kendali bulanan**: Menu Anggaran -> lihat progres per kategori -> kategori mendekati limit ditandai -> tap kategori untuk melihat transaksinya.
4. **Review**: Menu Laporan -> pilih interval -> total, tren, breakdown kategori -> drill-down ke daftar transaksi -> export bila perlu.

## 8. Dokumen Terkait

| File | Isi |
|---|---|
| `01-ARCHITECTURE.md` | Stack, versi, bounded context DDD, struktur folder, konvensi |
| `02-DATABASE.md` | ERD, tabel, constraint, invariant, strategi saldo |
| `03-USER-STORIES.md` | User story + acceptance criteria Given-When-Then per fitur |
| `04-NFR.md` | Role & permission, validasi, keamanan, performa, Definition of Done |
| `05-DESIGN.md` | Sistem desain, layout, spesifikasi layar, interaksi, copywriting |
