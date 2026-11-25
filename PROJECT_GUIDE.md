# PROJECT_GUIDE: KasMahasiswa

> Dokumen panduan internal, keputusan arsitektur, dan pelacakan progres pengembangan proyek akhir Pemrograman Berbasis Web (PBW).

## Identitas Proyek
- **Judul Proyek:** KasMahasiswa: Sistem Informasi Pengelolaan Kas Mahasiswa Berbasis Web
- **Mata Kuliah:** Pemrograman Berbasis Web (PBW)
- **Institusi:** Program Studi Sistem Informasi, Fakultas Ilmu Komputer dan Teknologi Informasi, Universitas Gunadarma
- **Tahun Akademik:** 2026/2027
- **Pengembang:** Firdaus Dhuha Prabowo
- **Repositori Direktori:** `c:\Users\HP\Downloads\PBW`
- **Tujuan Utama:** Mengembangkan aplikasi web dinamis berbasis PHP dan MySQL yang memenuhi seluruh kaidah siklus hidup pengembangan perangkat lunak (*Software Development Life Cycle* - SDLC).

## Syarat Wajib Tugas Dosen
1. Berjalan dengan stabil pada lingkungan localhost (XAMPP / Laragon) menggunakan PHP native dan MySQL/MariaDB.
2. Menyediakan fungsionalitas CRUD lengkap (*Create, Read, Update, Delete*) yang terhubung aktif ke basis data.
3. Struktur basis data relasional dengan *Primary Key* (PK) dan *Foreign Key* (FK) yang teruji serta ternormalisasi (minimal 3NF).
4. Pemisahan konfigurasi koneksi basis data ke berkas modular tersendiri dengan penanganan galat (*error handling PDO*) dan kueri aman (*prepared statements*).
5. Dokumentasi lengkap 5 tahapan SDLC (Analisis, Desain Database, Mockup UI, Implementasi, Pengujian Black-Box, dan Rencana Deployment).
6. Luaran berkas laporan formal proyek akhir dalam format PDF siap kumpul.

## Ruang Lingkup Sistem

**Fase 1: Implementasi Utama (Selesai)**
- Modul Otentikasi Pengguna: Registrasi, Login, validasi password terenkripsi, proteksi sesi.
- Modul Dashboard Finansial: Kartu statistik akumulasi kas, saldo berjalan, dan riwayat mutasi transaksi terkini.
- Modul Transaksi Kas: Form input dinamis (pemasukan/pengeluaran), edit transaksi, dan hapus transaksi.
- Modul Kategori Transaksi: Pengelompokan jenis kas dengan ikon dan kode warna.
- Modul Pelaporan & Ekspor: Filter transaksi berdasarkan rentang tanggal dan jenis, serta ekspor laporan ke format Excel.
- Artefak SDLC Dokumen 01 s.d. 05 dan Laporan HTML/PDF.
- Skrip uji otomatis Black-Box HTTP/CLI (`tests/uji_blackbox.php`).

**Fase 2: Rencana Pengembangan Lanjutan**
- Pembuatan grafik analitik interaktif (Chart.js / ApexCharts) untuk memvisualisasikan tren pengeluaran bulanan.
- Fitur multi-akun organisasi (dukungan banyak kelas atau kepanitiaan dalam satu sistem).
- Pengiriman notifikasi tagihan kas berkala via integrasi Webhook WhatsApp / Telegram Bot.
- Migrasi arsitektur menjadi *Progressive Web App* (PWA) dengan sinkronisasi luring (*offline sync*).

## Keputusan Desain & Arsitektur

1. **Pemilihan Teknologi**:
   - Memilih PHP Native dengan ekstensi PDO daripada framework berat (seperti Laravel), agar ringan dijalankan pada lingkungan laboratorium kampus dan mematuhi syarat perkuliahan dasar PBW.
   - Menggunakan Bootstrap 5.3 dikombinasikan dengan sentuhan palet modern ala Apple Human Interface Guidelines untuk memberikan tampilan yang segar, profesional, dan responsif di smartphone.
2. **Keamanan Basis Data**:
   - Menghindari kueri string concatenation. Semua kueri wajib menggunakan `PDO::prepare()` dan `execute()` dengan array parameter untuk mengeliminasi celah SQL Injection.
   - Menerapkan `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')` pada setiap rendering teks ke view HTML.
3. **Struktur Basis Data**:
   - Tabel `users`: Menyimpan kredensial otentikasi.
   - Tabel `kategori`: Menyimpan master data pengelompokan kas beserta visual icon dan warna.
   - Tabel `transaksi`: Menyimpan log mutasi kas yang terikat ke `id_user` dan `id_kategori`.

## Progres & Ceklis SDLC
- [x] Tahap 1: Analisis Kebutuhan & Perencanaan (`docs/01_analisis_kebutuhan.md`)
- [x] Tahap 2a: Desain Basis Data & Kamus Data (`docs/02a_desain_database.md`)
- [x] Tahap 2b: Desain Antarmuka & Mockup UI (`docs/02b_mockup.md`)
- [x] Tahap 3: Implementasi Arsitektur Kode (`docs/03_implementasi.md`)
- [x] Tahap 4: Pengujian Black-Box Komprehensif (`docs/04_pengujian.md` & `tests/uji_blackbox.php`)
- [x] Tahap 5: Rencana Deployment & Pemeliharaan (`docs/05_deploy_pemeliharaan.md`)
- [x] Laporan Proyek Akhir Lengkap (`docs/laporan/laporan.html` & `docs/laporan/laporan.pdf`)
