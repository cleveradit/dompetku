# Dokumentasi Proyek Dompetku (untuk Agentic AI)

Folder ini adalah sumber kebenaran pembangunan aplikasi. Baca SEMUA file sebelum menulis kode. Pemilik proyek hanya menilai hasil akhir dari sisi pengalaman pengguna; seluruh kualitas teknis adalah tanggung jawabmu dan dijaga oleh dokumen ini.

## Urutan baca

1. `00-PRD.md` — apa yang dibangun (3 fase, semuanya wajib) dan apa yang DILARANG dibangun
2. `01-ARCHITECTURE.md` — stack (Laravel + Inertia + React), bounded context DDD, struktur folder, konvensi
3. `02-DATABASE.md` — ERD, tabel, constraint, invariant, strategi saldo (nominal DECIMAL 2 desimal)
4. `03-USER-STORIES.md` — acceptance criteria Given-When-Then; setiap AC = minimal 1 test
5. `04-NFR.md` — permission, validasi, penanganan uang, keamanan, performa, Definition of Done
6. `05-DESIGN.md` — sistem desain, layout, spesifikasi tiap layar, states, copywriting

## Aturan kerja

1. Konflik antar dokumen: `00-PRD` > `04-NFR` > `02-DATABASE` > `05-DESIGN` > `01-ARCHITECTURE` > `03-USER-STORIES`.
2. Jangan menambah tabel, kolom, fitur, library, warna, atau font di luar dokumen. Jika terasa perlu, BERHENTI dan tanyakan ke pemilik proyek.
3. Kerjakan berurutan: Fase 1 (Epic 1 sampai 5) -> Fase 2 (Epic 6 sampai 8) -> Fase 3 (Epic 9). Selesaikan test sebuah story sebelum lanjut.
4. Uang tidak pernah float: DECIMAL di DB, Money/string di PHP, string di props, format di `lib/money.ts`.
5. Setiap selesai satu story jalankan: `pint --test`, `phpstan`, `pest`, `eslint`, `tsc --noEmit`. Semua hijau sebelum lanjut.
6. Setiap selesai satu layar, periksa checklist visual di `05-DESIGN.md` bagian 7 (360/768/1280, light/dark).
7. Cek Definition of Done di `04-NFR.md` bagian 9 sebelum menandai fitur selesai.
