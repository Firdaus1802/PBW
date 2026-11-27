# KasMahasiswa: Sistem Informasi Pengelolaan Kas Mahasiswa Berbasis Web

Aplikasi web untuk mencatat, mengelola, dan memonitor keuangan kas mahasiswa dan kas kelas secara transparan, akuntabel, dan rapi. Proyek akhir mata kuliah **Pemrograman Berbasis Web (PBW)** Universitas Gunadarma, dibangun dengan PHP native (PDO) dan MySQL/MariaDB, dan dijalankan di localhost (XAMPP).

Pengelolaan kas kelas atau kas organisasi mahasiswa sering kali masih dilakukan secara konvensional (buku tulis manual atau spreadsheet chat grup) yang rawan hilang, sulit dilacak riwayat perubahannya, dan kurang transparan bagi seluruh anggota. KasMahasiswa hadir sebagai solusi terpusat bagi bendahara dan mahasiswa untuk **mencatat pemasukan/pengeluaran**, **mengelompokkan transaksi berdasarkan kategori**, **menyaring laporan keuangan per periode**, serta **mengekspor rekapitulasi ke format Excel**.

## Fitur Utama

### Manajemen Autentikasi

- **Registrasi Akun**: Pendaftaran akun mahasiswa/bendahara baru dengan validasi username unik dan hashing password yang aman (`password_hash` BCRYPT).
- **Login & Sesi Aman**: Autentikasi pengguna berbasis session PHP dengan regenerasi ID session untuk mencegah *session fixation*.
- **Logout Aman**: Pembersihan session dan proteksi akses halaman privat.

### Dashboard Finansial Interaktif

- **Kartu Ringkasan Keuangan**: Menampilkan akumulasi Total Pemasukan, Total Pengeluaran, Sisa Saldo Kas, dan grafik interaktif *Chart.js* secara real-time.
- **Target Limit Pengeluaran (*Budget Alert*)**: Pengaturan batas pengeluaran bulanan dengan *progress bar* dan indikator warna otomatis (`Aman`, `Mendekati Batas`, `Melewati Batas`).
- **Target Tabungan (*Savings Goal*)**: Pelacakan progres saldo kas terhadap target tabungan yang ditentukan pengguna.
- **Insight Keuangan Otomatis**: Menampilkan rata-rata pengeluaran per hari, pengeluaran terbesar, serta saran batas aman pengeluaran harian hingga akhir bulan.
- ***Live Search* & Filter Instan**: Pencarian cepat judul/kategori dan penyaringan jenis transaksi langsung di tabel dashboard tanpa muat ulang halaman.

### Pengelolaan Kategori Kas Dinamis

- **Kategori Pemasukan & Pengeluaran**: Pengelompokan jenis kas (misal: Uang Saku, Freelance, Biaya Kuliah, Konsumsi, Transportasi).
- **Kustomisasi Visual**: Setiap kategori dapat diberi ikon Bootstrap Icons dan kode warna hex untuk mempermudah identifikasi visual pada laporan.

### Pencatatan Transaksi Kas (CRUD)

- **Catat Transaksi**: Formulir pencatatan kas masuk atau kas keluar dengan input tanggal, judul/keterangan, kategori, nominal (IDR), dan catatan tambahan.
- **Edit & Hapus Transaksi**: Kemudahan merevisi kesalahan pencatatan atau menghapus transaksi dengan dialog konfirmasi keamanan.

### Laporan & Ekspor Data

- **Penyaringan Rentang Tanggal**: Filter transaksi kas berdasarkan tanggal mulai dan tanggal akhir.
- **Penyaringan Jenis Transaksi**: Menampilkan Semua, Khusus Pemasukan, atau Khusus Pengeluaran.
- **Ekspor Excel & Backup JSON**: Ekspor rekapitulasi data kas ke format `.xls` serta cadangan data lengkap ke `.json` (`export_json.php`) untuk arsip pribadi maupun pelaporan resmi.

## Teknologi

| Komponen | Spesifikasi / Teknologi |
| --- | --- |
| Server-Side | PHP 8.x Native dengan PDO (*Prepared Statements*) |
| Database | MariaDB 10.4 / MySQL (Engine InnoDB, Relasi Foreign Key PK-FK, 3NF) |
| Web Server | Apache (XAMPP / Laragon) |
| Front-End | HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, Apple Clean Modern Styling |
| Arsitektur | Clean Modular PHP (Pemisahan koneksi, komponen antarmuka, dan logika bisnis) |

## Cara Menjalankan (XAMPP)

1. **Salin atau Clone Proyek** ke direktori `htdocs` XAMPP Anda, misalnya: `C:/xampp/htdocs/pbw`.
2. **Nyalakan Server**: Buka XAMPP Control Panel, lalu aktifkan layanan **Apache** dan **MySQL**.
3. **Impor Database**:
   - Buka phpMyAdmin di peramban (`http://localhost/phpmyadmin`).
   - Buat database baru bernama `db_kas_mahasiswa` (atau biarkan skrip membuat otomatis).
   - Pilih tab **Import**, lalu pilih berkas `database/db_kas_mahasiswa.sql`.
   - Alternatif CLI terminal: `mysql -u root < database/db_kas_mahasiswa.sql`.
4. **Konfigurasi Database**:
   - Periksa berkas `m6/config/koneksi.php`. Pastikan setelan host (`localhost`), username (`root`), password (`""`), dan nama database (`db_kas_mahasiswa`) sesuai dengan konfigurasi lokal XAMPP Anda.
5. **Buka Aplikasi**:
   - Buka peramban dan akses: `http://localhost/pbw/` (akan otomatis diarahkan ke antarmuka aplikasi di `/m6/index.php`).

### Akun Demo Bawaan

| Username | Password | Keterangan |
| --- | --- | --- |
| `admin` | `admin123` | Akun demo Administrator / Bendahara Mahasiswa |

## Struktur Repositori

```text
├── database/
│   └── db_kas_mahasiswa.sql       # Struktur skema SQL relasional + data awal dummy
├── docs/
│   ├── 01_analisis_kebutuhan.md   # Tahap 1: Planning & Kebutuhan Fungsional
│   ├── 02a_desain_database.md     # Tahap 2a: Skema ERD, Kamus Data & Normalisasi
│   ├── 02b_mockup.md              # Tahap 2b: Desain Antarmuka & Wireframe
│   ├── 03_implementasi.md         # Tahap 3: Arsitektur Kode, PDO & Keamanan
│   ├── 04_pengujian.md            # Tahap 4: Tabel Black-Box Testing (40 Kasus Uji)
│   ├── 05_deploy_pemeliharaan.md  # Tahap 5: Rencana Deployment & Maintenance
│   └── laporan/
│       ├── img/                       # Gambar ERD & tampilan antarmuka aplikasi
│       ├── laporan_kas_mahasiswa.docx # Naskah makalah proyek akhir format Gunadarma
│       └── laporan.pdf                # Berkas cetak laporan PDF siap kumpul
├── tests/
│   └── uji_blackbox.php           # Skrip otomatis uji coba Black-Box via HTTP/CLI
├── m6/                            # Direktori implementasi aplikasi Kas Mahasiswa
│   ├── config/koneksi.php         # Koneksi database PDO dan helper session
│   ├── assets/                    # Aset CSS kustom, JS, font, dan ikon
│   ├── login.php                  # Halaman otentikasi masuk
│   ├── register.php               # Halaman pendaftaran pengguna
│   ├── dashboard.php              # Dashboard ringkasan saldo & transaksi
│   ├── kategori.php               # Kelola kategori pemasukan & pengeluaran
│   ├── transaksi_edit.php         # Form revisi data transaksi kas
│   ├── transaksi_hapus.php        # Endpoint penghapusan kas dengan konfirmasi
│   ├── laporan.php                # Rekapitulasi & filter kas per periode
│   ├── export_excel.php           # Generator berkas Excel kas
│   └── logout.php                 # Terminasi sesi pengguna
├── index.php                      # Gerbang pengalihan utama
├── PROJECT_GUIDE.md               # Panduan teknis & standar pengerjaan proyek
└── README.md                      # Dokumentasi utama proyek
```

## Keamanan Sistem

- **Anti SQL Injection**: Seluruh transaksi basis data menggunakan PDO *Prepared Statements* dengan *parameterized binding*.
- **Anti Cross-Site Scripting (XSS)**: Data masukan pengguna disanitasi sebelum ditampilkan ke peramban menggunakan `htmlspecialchars()`.
- **Proteksi CSRF**: Seluruh formulir `POST` (login, registrasi, transaksi, dan kategori) dilindungi token CSRF yang diverifikasi menggunakan `hash_equals()`.
- **Enkripsi Kredensial & Sesi Aman**: Password disimpan dengan algoritma BCRYPT (`password_hash` / `password_verify`), disertai *rate limiting* login, cookie sesi `httponly` + `SameSite=Lax`, dan *security headers* (`X-Frame-Options`, `X-Content-Type-Options`).
- **Anti-IDOR & Integritas Referensial**: Kueri edit/hapus memverifikasi kepemilikan `id_user`, serta basis data menerapkan *Foreign Key Constraint* (`ON UPDATE CASCADE`, `ON DELETE RESTRICT`).
- **Proteksi Direktori & Error Handling**: Akses langsung ke `database/` dan `m6/config/` diblokir lewat `.htaccess`, dan pesan galat basis data dicatat ke `error_log` tanpa diekspos ke pengguna.

## Dokumentasi & Pengujian

- Seluruh artefak tahapan SDLC dapat dibaca pada folder [`docs/`](docs/).
- Skrip pengujian otomatis dapat dijalankan melalui terminal: `php tests/uji_blackbox.php`.
- Dokumen laporan akhir berformat PDF tersedia di [`docs/laporan/laporan.pdf`](docs/laporan/laporan.pdf).

## Pembuat Proyek

- **Nama**: Firdaus Dhuha Prabowo
- **Perguruan Tinggi**: Universitas Gunadarma
- **Program Studi**: Sistem Informasi
- **Mata Kuliah**: Pemrograman Berbasis Web (PBW)
