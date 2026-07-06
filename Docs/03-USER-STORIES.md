# User Stories & Acceptance Criteria: Dompetku

> Setiap AC format Given-When-Then dan wajib dibuktikan minimal satu automated test (Pest, ditambah Playwright untuk AC berlabel [E2E]). Fitur selesai hanya jika SEMUA AC hijau. Kerjakan berurutan per fase sesuai PRD.

---

# FASE 1

## Epic 1: Autentikasi (Identity)

### US-01 Registrasi akun (F-01)

- **AC-01.1** Given pengunjung di halaman registrasi, When mengisi nama, email valid belum terdaftar, password >= 8 karakter + konfirmasi cocok, dan memilih mata uang (default IDR), Then akun dibuat, email verifikasi terkirim, 12 kategori default ter-seed (8 expense, 4 income), user login dan diarahkan ke pembuatan dompet pertama.
- **AC-01.2** Given email sudah terdaftar, When submit, Then error validasi "email sudah digunakan", tidak ada akun baru.
- **AC-01.3** Given password < 8 karakter atau konfirmasi tidak cocok, When submit, Then error validasi pada field terkait.
- **AC-01.4** Given akun baru belum verifikasi email, When mencoba membuka halaman selain halaman verifikasi, Then diarahkan ke halaman "cek email kamu" dengan tombol kirim ulang (throttle 1x/menit).
- **AC-01.5** Given link verifikasi diklik, Then `email_verified_at` terisi dan user diarahkan ke aplikasi.

### US-02 Login dan logout (F-02)

- **AC-02.1** Given user terverifikasi, When login dengan kredensial benar, Then session terbentuk dan diarahkan ke dashboard.
- **AC-02.2** Given password salah, When login, Then pesan error generik (tidak membocorkan apakah email terdaftar), tanpa session.
- **AC-02.3** Given 5 kegagalan login dalam 1 menit untuk email+IP sama, When mencoba lagi, Then ditolak 429 dengan pesan throttle.
- **AC-02.4** Given user login, When logout, Then session hancur; mengakses halaman aplikasi mengarahkan ke login.
- **AC-02.5** Given pengunjung belum login, When membuka URL aplikasi mana pun, Then diarahkan ke login.

### US-03 Lupa password (F-02)

- **AC-03.1** Given user terdaftar, When meminta reset password, Then email berisi link reset terkirim; respon UI sama persis untuk email tidak terdaftar (anti enumeration).
- **AC-03.2** Given link reset valid (< 60 menit), When mengisi password baru valid, Then password terganti, semua session lama ter-invalidate, user bisa login dengan password baru.
- **AC-03.3** Given link kadaluarsa atau sudah dipakai, When dibuka, Then pesan error dengan tombol minta link baru.

### US-04 Ganti password (F-03)

- **AC-04.1** Given user login, When mengisi password lama benar + password baru valid, Then password ter-update, notifikasi sukses, session aktif tetap hidup, session lain di-logout paksa.
- **AC-04.2** Given password lama salah, When submit, Then error "password saat ini tidak cocok", tidak ada perubahan.

## Epic 2: Dompet (Wallet)

### US-05 Menambah dompet (F-04)

- **AC-05.1** Given user login, When membuat dompet dengan nama unik, tipe, saldo awal >= 0 (boleh desimal, contoh 1.500.000,50), warna, dan ikon, Then dompet tersimpan dengan `current_balance` = saldo awal.
- **AC-05.2** Given dompet bernama "BCA" sudah ada, When membuat "BCA" lagi, Then error nama duplikat.
- **AC-05.3** Given saldo awal negatif, > 2 desimal, atau bukan angka, When submit, Then error validasi.
- **AC-05.4** Given user A punya dompet, When user B mengaksesnya via URL langsung, Then respon 403/404, tidak pernah 200.

### US-06 Edit, arsip, hapus dompet (F-04)

- **AC-06.1** Given dompet milik user, When mengubah nama/tipe/warna/ikon, Then tersimpan tanpa mempengaruhi saldo dan transaksi.
- **AC-06.2** Given dompet aktif, When diarsipkan, Then hilang dari pilihan transaksi baru, tetapi saldo dan historinya tetap tampil (badge "Diarsipkan"), dan bisa di-unarchive.
- **AC-06.3** Given dompet tanpa transaksi dan tanpa recurring, When dihapus, Then soft delete dan hilang dari semua tampilan.
- **AC-06.4** Given dompet dengan transaksi (termasuk sebagai tujuan transfer) atau recurring aktif, When mencoba hapus, Then ditolak dengan pesan yang menyarankan arsip.

## Epic 3: Transaksi (Ledger)

### US-07 Mencatat income/expense (F-05)

- **AC-07.1** Given dompet aktif, When mencatat expense (dompet, kategori expense, nominal > 0 hingga 2 desimal, tanggal), Then transaksi tersimpan dan `current_balance` berkurang tepat sebesar nominal (uji kasus desimal: saldo 100,00 dikurangi 0,10 tiga kali = 99,70 tepat).
- **AC-07.2** Given kondisi sama, When mencatat income dengan kategori income, Then saldo bertambah sebesar nominal.
- **AC-07.3** Given form expense, When memilih kategori bertipe income (atau sebaliknya), Then pilihan tidak tersedia dan submit paksa ditolak validasi.
- **AC-07.4** Given nominal 0, negatif, > 2 desimal, atau bukan angka, When submit, Then error validasi, saldo tidak berubah.
- **AC-07.5** Given tanggal transaksi di masa depan, When submit, Then error "tanggal tidak boleh di masa depan".
- **AC-07.6** Given dompet terarsip, When mencoba mencatat ke dompet itu, Then tidak ada di pilihan dan submit paksa ditolak.
- **AC-07.7** Given transaksi tersimpan, When nominal/dompet/tipe diedit, Then saldo dompet lama dan baru terkoreksi benar (reverse lama, apply baru).
- **AC-07.8** Given transaksi tersimpan, When dihapus, Then soft delete dan saldo kembali seperti sebelum transaksi ada.
- **AC-07.9** Given expense yang membuat saldo negatif, When disimpan, Then TETAP diterima; saldo tampil negatif dengan gaya sesuai 05-DESIGN.md (aplikasi mencatat realita, tidak memblokir).
- **AC-07.10 [E2E]** Given user di dashboard pada viewport 360px, When menekan FAB dan mengisi form transaksi umum, Then transaksi tersimpan dalam <= 10 detik interaksi normal dan toast sukses muncul.

### US-08 Transfer antar dompet (F-06)

- **AC-08.1** Given >= 2 dompet aktif, When transfer X dari A ke B, Then satu record `type=transfer` tersimpan, saldo A berkurang X, saldo B bertambah X.
- **AC-08.2** Given dompet asal = tujuan, When submit, Then error validasi.
- **AC-08.3** Given laporan pengeluaran, When periode mencakup transfer, Then transfer TIDAK dihitung sebagai expense/income.
- **AC-08.4** Given transfer tersimpan, When dihapus, Then saldo kedua dompet kembali seperti semula.

### US-09 Kategori (F-07)

- **AC-09.1** Given user login, When membuat kategori dengan nama unik per tipe + warna + ikon, Then tersedia di form transaksi sesuai tipenya.
- **AC-09.2** Given kategori belum dipakai, When dihapus, Then soft delete.
- **AC-09.3** Given kategori dipakai transaksi/recurring/budget, When mencoba hapus, Then ditolak dengan pesan jumlah pemakainya.
- **AC-09.4** Given kategori sudah dipakai transaksi, When mencoba mengubah tipenya, Then ditolak; nama/warna/ikon tetap boleh diubah.

## Epic 4: Laporan & Dashboard (Reporting)

### US-10 Laporan per interval (F-08)

- **AC-10.1** Given user punya transaksi, When membuka laporan bulanan, Then tampil total expense, total income, net, dan breakdown expense per kategori (nominal + persen) terurut dari terbesar.
- **AC-10.2** Given interval harian/mingguan/tahunan, When dipilih, Then batas periode kalender benar (minggu mulai Senin, timezone Asia/Jakarta).
- **AC-10.3** Given rentang custom valid (mulai <= akhir, maksimal 366 hari), When diterapkan, Then laporan mencakup `occurred_on` dalam rentang inklusif.
- **AC-10.4** Given rentang custom tidak valid, When submit, Then error validasi.
- **AC-10.5** Given periode tanpa transaksi, When laporan dibuka, Then empty state sesuai 05-DESIGN.md, bukan halaman kosong/error.
- **AC-10.6** Given laporan bulanan, Then tersedia grafik tren pengeluaran per hari; laporan tahunan menampilkan tren per bulan.
- **AC-10.7** Given breakdown kategori, When satu kategori diklik, Then tampil daftar transaksinya pada periode sama.
- **AC-10.8** Given filter dompet, When satu dompet dipilih, Then semua angka hanya menghitung dompet itu.
- **AC-10.9** Given transaksi soft-deleted, Then tidak ikut dihitung di laporan mana pun.
- **AC-10.10** Given navigasi periode (panah kiri/kanan), When ditekan, Then berpindah ke periode sebelum/sesudah dengan interval sama.

### US-11 Dashboard (F-09)

- **AC-11.1** Given user login, When membuka dashboard, Then tampil: total saldo dompet non-arsip, kartu per dompet, total income & expense bulan berjalan, dan 10 transaksi terakhir.
- **AC-11.2** Given user punya budget (Fase 2), Then dashboard juga menampilkan ringkasan 3 anggaran dengan progres tertinggi.
- **AC-11.3** Given dashboard, When menekan FAB, Then form transaksi terbuka dengan tanggal default hari ini dan dompet default = dompet terakhir dipakai.

## Epic 5: Pengaturan & Tampilan (F-10, F-11)

### US-12 Pengaturan akun

- **AC-12.1** Given halaman pengaturan, When mengubah nama, Then tersimpan dengan notifikasi sukses.
- **AC-12.2** Given halaman pengaturan, When mengubah mata uang akun, Then seluruh format nominal aplikasi mengikuti (simbol & pemisah), nilai angka tidak dikonversi.

### US-13 Dark mode & responsif [E2E]

- **AC-13.1** Given preferensi sistem dark, When user pertama kali membuka aplikasi, Then tema dark aktif; user bisa memaksa light/dark/system di pengaturan dan pilihan bertahan antar sesi.
- **AC-13.2** Given viewport 360px, When membuka dashboard, form transaksi, laporan, dan anggaran, Then tanpa horizontal scroll, target sentuh >= 44px, navigasi bottom bar berfungsi.
- **AC-13.3** Given viewport 768px dan >= 1280px, Then layout mengikuti spesifikasi breakpoint 05-DESIGN.md tanpa elemen terpotong.

---

# FASE 2

## Epic 6: Anggaran (Budget)

### US-14 Mengelola anggaran (F-12)

- **AC-14.1** Given user login, When menetapkan anggaran nominal > 0 untuk kategori expense pada bulan tertentu, Then anggaran tersimpan; satu kategori hanya punya satu anggaran per bulan (upsert).
- **AC-14.2** Given kategori income, When mencoba dianggarkan, Then tidak tersedia di pilihan dan submit paksa ditolak.
- **AC-14.3** Given anggaran ada, When dihapus, Then hilang tanpa mempengaruhi transaksi.
- **AC-14.4** Given bulan baru tanpa anggaran, When menekan "Salin dari bulan lalu", Then semua anggaran bulan sebelumnya tersalin ke bulan berjalan (yang sudah ada tidak ditimpa).

### US-15 Progres anggaran (F-12)

- **AC-15.1** Given anggaran Makan Rp 1.000.000 dan total expense kategori itu bulan ini Rp 400.000, When halaman anggaran dibuka, Then tampil progres 40% dengan sisa Rp 600.000.
- **AC-15.2** Given pemakaian >= 80% dan < 100%, Then status "hampir habis"; >= 100% status "melebihi anggaran", keduanya dengan gaya visual 05-DESIGN.md.
- **AC-15.3** Given transfer pada kategori apa pun tidak ada (transfer tak berkategori), Then transfer tidak pernah mengurangi anggaran.
- **AC-15.4** Given transaksi expense diedit/dihapus, When halaman anggaran dimuat ulang, Then progres mencerminkan kondisi terbaru.

## Epic 7: Transaksi Berulang (Ledger)

### US-16 Mengelola recurring (F-13)

- **AC-16.1** Given user login, When membuat recurring (tipe, dompet, kategori, nominal, frekuensi + interval, tanggal mulai, opsional tanggal akhir), Then tersimpan dengan `next_run_on` = tanggal mulai.
- **AC-16.2** Given recurring aktif jatuh tempo hari ini, When scheduler harian berjalan, Then transaksi nyata tercipta dengan `occurred_on` = tanggal jatuh tempo, saldo ter-update, `next_run_on` maju sesuai frekuensi, dan `last_run_on` terisi.
- **AC-16.3** Given scheduler tidak berjalan 3 hari (server mati), When berjalan lagi, Then semua kemunculan yang terlewat tercipta dengan tanggal masing-masing (catch-up), tanpa duplikat walau command dijalankan dua kali (idempotent, invariant I-11).
- **AC-16.4** Given recurring dijeda (is_active = false), When jatuh tempo lewat, Then tidak ada transaksi tercipta; saat diaktifkan lagi, `next_run_on` disesuaikan ke jatuh tempo berikutnya di masa depan (tanpa rapel masa jeda).
- **AC-16.5** Given `end_on` terlampaui, Then recurring berhenti otomatis (is_active = false).
- **AC-16.6** Given recurring diedit (nominal/kategori), Then hanya transaksi MENDATANG yang terpengaruh; transaksi yang sudah tercipta tidak berubah.
- **AC-16.7** Given transaksi hasil recurring, When dilihat di daftar transaksi, Then bertanda "berulang" dan tetap bisa diedit/dihapus seperti transaksi biasa.

## Epic 8: Pencarian, Filter, Lampiran, Export

### US-17 Pencarian & filter transaksi (F-14)

- **AC-17.1** Given daftar transaksi, When mengetik kata kunci, Then hasil terfilter berdasarkan `description` (case-insensitive, partial match) dengan pagination tetap berjalan.
- **AC-17.2** Given filter kombinasi (rentang tanggal + kategori + dompet + tipe + rentang nominal), When diterapkan bersamaan, Then hasil memenuhi SEMUA filter (AND).
- **AC-17.3** Given filter aktif, Then jumlah hasil dan total nominalnya ditampilkan, serta tombol "bersihkan filter" tersedia.
- **AC-17.4** Given filter menghasilkan 0 transaksi, Then empty state "tidak ada hasil" dengan saran melonggarkan filter.

### US-18 Lampiran struk (F-15)

- **AC-18.1** Given form transaksi, When melampirkan file JPG/PNG/WebP/PDF <= 5 MB, Then file tersimpan di disk privat dan thumbnail/ikon tampil di detail transaksi.
- **AC-18.2** Given file tipe lain atau > 5 MB, When diunggah, Then ditolak dengan pesan jelas sebelum submit.
- **AC-18.3** Given transaksi sudah punya 5 lampiran, When menambah lagi, Then ditolak.
- **AC-18.4** Given lampiran milik user A, When user B mengakses URL file-nya, Then 403/404; file hanya tersaji lewat route ter-otorisasi, bukan folder publik.
- **AC-18.5** Given lampiran dihapus atau transaksinya dihapus permanen, Then file fisik ikut terhapus.

### US-19 Export (F-16)

- **AC-19.1** Given daftar transaksi dengan filter aktif, When menekan Export CSV/Excel, Then file terunduh berisi persis baris hasil filter, kolom: tanggal, tipe, kategori, dompet, dompet tujuan, nominal, catatan.
- **AC-19.2** Given hasil filter > 10.000 baris, When export, Then diproses lewat queue dan user diberi tahu file siap diunduh (link di halaman yang sama).
- **AC-19.3** Given nominal desimal, Then nilai di file export mempertahankan 2 desimal tanpa pembulatan salah.

### US-20 Hapus akun (F-17)

- **AC-20.1** Given halaman pengaturan akun, When user mengetik password benar pada dialog konfirmasi berbahaya, Then akun beserta SELURUH data (dompet, transaksi, kategori, anggaran, recurring, file lampiran) terhapus permanen dan user diarahkan ke halaman perpisahan.
- **AC-20.2** Given password salah pada konfirmasi, Then penghapusan batal, tidak ada data berubah.

---

# FASE 3

## Epic 9: Tren, Import, PWA

### US-21 Tren saldo (F-18)

- **AC-21.1** Given user punya histori transaksi, When membuka tab tren di laporan, Then grafik garis saldo total per hari untuk periode terpilih, dihitung dari initial_balance + mutasi kumulatif (bukan dari cache).

### US-22 Import CSV (F-19)

- **AC-22.1** Given user mengunduh template CSV dari aplikasi, When mengunggah file sesuai template, Then baris valid ter-import sebagai transaksi (saldo ter-update), dan ringkasan hasil tampil: jumlah sukses, jumlah gagal beserta alasan per baris.
- **AC-22.2** Given file dengan sebagian baris tidak valid (kategori tak dikenal, nominal salah, tanggal masa depan), Then hanya baris valid yang masuk; tidak ada import setengah-jadi per baris (per baris atomic).
- **AC-22.3** Given file bukan CSV atau > 2 MB, Then ditolak sebelum diproses.

### US-23 PWA (F-20)

- **AC-23.1** Given aplikasi dibuka di browser mobile modern, When memenuhi kriteria installable (manifest, ikon, service worker minimal), Then prompt "Tambahkan ke layar utama" tersedia dan aplikasi terbuka standalone dengan splash screen.
- **AC-23.2** Given tidak ada koneksi, When aplikasi dibuka, Then halaman offline yang menjelaskan aplikasi membutuhkan koneksi (bukan error browser mentah).
