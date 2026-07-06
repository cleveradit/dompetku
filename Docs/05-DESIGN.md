# Design Spec: Dompetku

> Sumber kebenaran tampilan dan interaksi. Semua komponen frontend WAJIB memakai token di sini. Dilarang memakai tampilan default shadcn/ui tanpa penyesuaian token, dan dilarang menambah warna/font di luar dokumen ini.

## 1. Arah Desain

**Konsep: "buku kas modern".** Dompetku terasa seperti buku catatan keuangan yang rapi dan tenang, bukan dashboard fintech yang ramai. Prinsip:

1. **Angka adalah bintang.** Nominal selalu elemen paling menonjol di layar, diset dengan huruf monospace tabular sehingga digit sejajar rapi seperti kolom buku kas.
2. **Tenang dan dapat dipercaya.** Permukaan bersih, satu warna primer, warna kuat hanya untuk makna (income/expense/status anggaran).
3. **Satu tangan, satu ibu jari.** Aksi utama selalu terjangkau ibu jari di mobile; input transaksi <= 10 detik.
4. **Elemen tanda tangan (signature): palet pecahan rupiah.** Warna preset untuk dompet dan kategori diturunkan dari warna uang kertas rupiah, memberi identitas yang khas Indonesia tanpa perlu ornamen.

## 2. Design Tokens

### 2.1 Warna inti

| Token | Light | Dark | Pemakaian |
|---|---|---|---|
| `bg` | `#FAFAF8` | `#101815` | Latar halaman |
| `surface` | `#FFFFFF` | `#18221E` | Kartu, sheet, dialog |
| `surface-2` | `#F1F1EC` | `#202B26` | Input, baris hover, chip |
| `border` | `#E3E3DC` | `#2C3833` | Garis pemisah 1px |
| `ink` | `#1A2620` | `#E9EDEA` | Teks utama |
| `ink-muted` | `#5C6B63` | `#96A39B` | Teks sekunder, label |
| `primary` | `#0B6B4F` | `#3DA47E` | Tombol utama, tautan, elemen aktif |
| `primary-ink` | `#FFFFFF` | `#0E1512` | Teks di atas primary |

### 2.2 Warna makna (jangan dipakai untuk dekorasi)

| Token | Light | Dark | Pemakaian |
|---|---|---|---|
| `income` | `#1B8A5A` | `#4CC08A` | Nominal & indikator pemasukan |
| `expense` | `#C13A3A` | `#E6716B` | Nominal & indikator pengeluaran, saldo negatif |
| `transfer` | `#3E5BAA` | `#8AA3E8` | Indikator transfer |
| `warning` | `#B45309` | `#E8A33D` | Anggaran "hampir habis" (>= 80%) |
| `danger` | `#C13A3A` | `#E6716B` | Anggaran terlampaui, aksi destruktif |

### 2.3 Palet pecahan rupiah (preset warna dompet & kategori)

Diambil dari warna dominan uang kertas rupiah. Tampilkan sebagai pilihan swatch berurutan nominal:

| Nama | Hex | Asal |
|---|---|---|
| Merah Seratus | `#B94A48` | Rp100.000 |
| Biru Lima Puluh | `#3E5BAA` | Rp50.000 |
| Hijau Dua Puluh | `#2E7D5B` | Rp20.000 |
| Ungu Sepuluh | `#6D5BA8` | Rp10.000 |
| Cokelat Lima | `#8A5A3B` | Rp5.000 |
| Abu Dua | `#6B7280` | Rp2.000 |
| Kuning Seribu | `#A08C3B` | Rp1.000 |

Kategori default memakai palet ini (contoh: Makan & Minum = Merah Seratus + ikon `utensils`, Transportasi = Biru Lima Puluh + `bus`, Gaji = Hijau Dua Puluh + `banknote`). Ikon dari lucide, whitelist ±40 ikon relevan keuangan/kehidupan sehari-hari didefinisikan di `lib/icons.ts`.

### 2.4 Tipografi

| Peran | Font | Aturan |
|---|---|---|
| UI & body | Plus Jakarta Sans (400/500/600/700) | Font Indonesia; dipakai untuk semua teks antarmuka |
| Nominal & data | IBM Plex Mono (500/600) | SEMUA angka uang, `font-variant-numeric: tabular-nums` |

Skala (mobile / desktop): display saldo 32/40px semibold mono; judul halaman 20/24px semibold; judul kartu 16px semibold; body 14/15px; label & meta 12/13px medium `ink-muted`; nominal di daftar 15px mono semibold.

Aturan nominal: pemasukan diawali `+` warna `income`; pengeluaran diawali `-` warna `expense`; transfer tanpa tanda warna `ink` dengan ikon panah; saldo negatif warna `expense`. Format mengikuti mata uang akun, IDR: `Rp1.250.000,50` (desimal `,00` boleh disembunyikan bila nol).

### 2.5 Bentuk, jarak, elevasi, gerak

- Radius: input & tombol 10px, kartu 14px, sheet/dialog 18px, chip/badge penuh (pill).
- Spacing berbasis 4px; padding kartu 16px (mobile) / 20px (desktop); jarak antar section 24px.
- Elevasi: andalkan border `border` + shadow sangat halus (`0 1px 2px rgb(0 0 0 / .05)`); dark mode tanpa shadow, cukup perbedaan surface.
- Gerak: durasi 150-200ms ease-out; sheet naik dari bawah; toast dari bawah (mobile) / kanan atas (desktop); count-up saldo total 400ms sekali saat load dashboard. Semua animasi mati saat `prefers-reduced-motion`.

## 3. Layout & Navigasi

| Breakpoint | Layout |
|---|---|
| < 768px | Bottom navigation 4 tab + FAB tengah; header ringkas (judul + avatar); konten satu kolom |
| 768-1279px | Sama dengan mobile, konten maksimal 640px terpusat; grid kartu 2 kolom |
| >= 1280px | Sidebar kiri 240px; konten maksimal 1040px; FAB digantikan tombol "+ Catat" di header |

Bottom nav (mobile): **Beranda, Transaksi, [FAB +], Laporan, Anggaran**. Dompet dikelola dari Beranda (tautan "Kelola" pada section dompet); Pengaturan lewat avatar di header. Sidebar (desktop): Beranda, Transaksi, Dompet, Laporan, Anggaran, Pengaturan.

FAB: lingkaran 56px `primary`, ikon plus, selalu membuka form transaksi (bottom sheet). Ini satu-satunya elemen mengambang.

## 4. Spesifikasi Layar

### 4.1 Autentikasi (login, register, lupa/reset password, verifikasi)

Kartu tunggal terpusat maksimal 400px di atas `bg`, logo wordmark "Dompetku" di atasnya. Tanpa ilustrasi stok. Register menyertakan pilihan mata uang (default IDR, select dengan pencarian). Error validasi inline di bawah field, bukan alert global.

### 4.2 Onboarding dompet pertama

Setelah verifikasi, user tanpa dompet diarahkan ke layar satu tujuan: "Buat dompet pertamamu". Form: nama, tipe, saldo awal, swatch warna rupiah, ikon. Tanpa navigasi lain yang mengalihkan.

### 4.3 Beranda (dashboard)

```
┌──────────────────────────────┐
│ Dompetku              (◯ava) │
│                              │
│ Total saldo                  │
│ Rp12.480.500,25   ← mono 32px, count-up
│ +Rp1,2jt bulan ini  ← delta, income color
│                              │
│ Dompet                Kelola │
│ ┌───────────┐ ┌───────────┐  │
│ │▮BCA       │ │▮GoPay     │  │  ← kartu: strip warna dompet,
│ │Rp8.200.000│ │Rp480.500  │  │    nama, saldo mono
│ └───────────┘ └───────────┘  │  (scroll horizontal jika >2)
│                              │
│ Bulan ini                    │
│ Masuk +Rp5.000.000           │
│ Keluar -Rp3.750.000          │
│ [Anggaran teratas: 3 bar]    │
│                              │
│ Transaksi terakhir     Semua │
│ ◯ Makan Siang   -Rp35.000    │
│ ◯ Gaji Juli   +Rp5.000.000   │
│ ...                          │
├──────────────────────────────┤
│ Beranda Transaksi ⊕ Laporan Anggaran │
└──────────────────────────────┘
```

Baris transaksi (dipakai di semua daftar): kiri lingkaran ikon kategori berlatar warna kategori 12% opacity, tengah nama kategori + catatan/`nama dompet` sebagai meta, kanan nominal mono. Tap membuka detail (sheet) dengan aksi Edit, Hapus, dan lampirannya.

### 4.4 Transaksi (daftar)

Search bar + tombol filter (membuka sheet filter: rentang tanggal, kategori multi, dompet, tipe, rentang nominal; chip filter aktif tampil di bawah search dan bisa dilepas satu-satu). Daftar dikelompokkan per tanggal dengan subtotal harian di header grup. Bila filter aktif: baris ringkasan "N transaksi, total Rp X" + tombol Export (Fase 2). Infinite scroll di mobile, pagination di desktop.

### 4.5 Form transaksi (layar terpenting)

Bottom sheet (mobile) / dialog 480px (desktop). Urutan field dioptimalkan kecepatan:

1. Segmented control 3 pilihan: **Keluar | Masuk | Transfer** (default Keluar).
2. **Input nominal besar** (mono 32px, terfokus otomatis, keypad numerik, masking `1.250.000,50`).
3. Kategori: grid chip ikon+nama, 8 terlihat + "Lainnya" (untuk transfer: field ini berganti menjadi pilihan Dompet tujuan).
4. Dompet (default dompet terakhir dipakai), Tanggal (default hari ini, cepat pilih "Kemarin"), Catatan (opsional), Lampiran (Fase 2), toggle "Jadikan berulang" (Fase 2, mengungkap field frekuensi).
5. Tombol lebar penuh **Simpan**; sukses = sheet menutup + toast "Transaksi tersimpan".

### 4.6 Dompet

Grid kartu dompet (1 kolom mobile, 3 desktop): strip warna, ikon, nama, tipe, saldo mono besar, badge "Diarsipkan" bila arsip. Aksi per kartu: Edit, Arsipkan/Aktifkan, Hapus (hapus hanya muncul bila memenuhi syarat; bila tidak, item menu menampilkan penjelasan "Punya transaksi, arsipkan saja").

### 4.7 Laporan

Header: pemilih interval (segmented: Harian, Mingguan, Bulanan, Tahunan, Custom) + navigasi periode `‹ Juli 2026 ›` + filter dompet. Isi: tiga angka ringkas (Masuk, Keluar, Selisih), grafik tren (bar per hari untuk bulanan, per bulan untuk tahunan; Recharts, warna `primary`, tooltip nominal mono), lalu breakdown kategori: baris ikon + nama + bar proporsi warna kategori + nominal + persen, terurut terbesar, tap = drill-down daftar transaksi. Fase 3: tab kedua "Tren saldo" (grafik garis).

### 4.8 Anggaran (Fase 2)

Header bulan `‹ Juli 2026 ›` + ringkasan "Terpakai Rp X dari Rp Y". Daftar kartu per kategori: ikon, nama, progress bar (warna kategori; `warning` saat >= 80%, `danger` saat >= 100% dengan label "Melebihi Rp Z"), teks "Rp terpakai / Rp anggaran" mono. Tombol "+ Anggaran" dan, saat bulan kosong, tombol "Salin dari bulan lalu" pada empty state.

### 4.9 Pengaturan

Daftar section: Profil (nama, email readonly), Keamanan (ganti password), Tampilan (Terang/Gelap/Ikuti sistem), Mata uang, Kategori (kelola), Transaksi berulang (kelola, Fase 2), Data (export/import, Fase 2/3), Zona berbahaya (Hapus akun: dialog merah dengan konfirmasi password).

## 5. States

- **Empty state**: ikon garis sederhana (lucide, `ink-muted`), satu kalimat ajakan bertindak + satu tombol. Contoh: "Belum ada transaksi bulan ini" + [Catat transaksi]. Nada mengundang, bukan meminta maaf.
- **Loading**: skeleton berbentuk konten aslinya (kartu, baris daftar, grafik); tanpa spinner layar penuh.
- **Error form**: pesan inline di bawah field, spesifik ("Tanggal tidak boleh di masa depan"), tanpa kata "maaf".
- **Error server**: toast `danger` dengan aksi "Coba lagi" bila relevan.
- **Konfirmasi destruktif**: dialog dengan konsekuensi eksplisit ("Menghapus transaksi ini akan mengembalikan saldo BCA sebesar Rp35.000") dan tombol merah bernama aksinya ("Hapus transaksi"), bukan "OK".

## 6. Copywriting UI

1. Bahasa Indonesia, sapaan "kamu", kalimat aktif, sentence case (bukan Title Case).
2. Tombol menyebut aksinya persis: "Simpan", "Catat transaksi", "Hapus dompet"; hindari "Submit"/"OK". Nama aksi konsisten dari tombol sampai toast ("Simpan" -> "Tersimpan").
3. Istilah baku dan konsisten di seluruh aplikasi: dompet, transaksi, pemasukan, pengeluaran, transfer, kategori, anggaran, transaksi berulang, lampiran, laporan.
4. Error menjelaskan apa yang terjadi dan cara memperbaikinya; tidak menyalahkan user, tidak samar.
5. Angka penting tidak dibulatkan di tempat yang butuh presisi (daftar, detail); pembulatan ringkas ("+Rp1,2jt") hanya untuk delta/ringkasan sekunder.

## 7. Checklist Kualitas Visual (tiap layar sebelum dianggap selesai)

1. Dicek di 360px, 768px, 1280px, tema light dan dark.
2. Semua warna dan ukuran berasal dari token bagian 2.
3. Nominal memakai mono tabular dan aturan tanda/warna bagian 2.4.
4. Focus ring terlihat pada semua elemen interaktif; kontras AA terpenuhi.
5. Empty, loading, dan error state terimplementasi, bukan hanya happy path.
6. `prefers-reduced-motion` mematikan animasi.
