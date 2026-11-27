# Tahap 1: Planning & Requirement Analysis

Proyek: **KasMahasiswa: Sistem Informasi Pengelolaan Kas Mahasiswa Berbasis Web**  
Mata Kuliah: **Pemrograman Berbasis Web (PBW)**  
Pengembang: **Firdaus Dhuha Prabowo**  
Institusi: **Program Studi Sistem Informasi, Universitas Gunadarma**

---

## 1. Latar Belakang Masalah

Dalam dinamika kehidupan perkuliahan mahasiswa, pengelolaan keuangan merupakan aspek krusial yang menentukan kelancaran berbagai kegiatan akademis maupun non-akademis. Baik dalam lingkup kas pribadi mahasiswa (khususnya mahasiswa rantau atau yang tinggal di indekos) maupun kas kolektif (kas kelas, kas kelompok praktikum, serta kepanitiaan organisasi mahasiswa), pencatatan aliran dana masuk dan keluar masih didominasi oleh metode konvensional.

Metode konvensional yang sering digunakan meliputi:

1. **Pencatatan Buku Kas Manual**: Rentan mengalami kerusakan fisik, basah, tercecer, dan tidak dapat dibagikan secara bersamaan kepada seluruh anggota kelas.
2. **Spreadsheet Manual Tanpa Validasi**: File spreadsheet yang diedit secara bergantian atau dibagikan lewat grup obrolan (WhatsApp/Telegram) sering mengalami konflik versi (*file conflict*), kesalahan rumus hitung saldo (*human error*), serta tidak memiliki kontrol hak akses otentikasi yang jelas.
3. **Ketidaktransparanan Aliran Dana**: Kurangnya keterbukaan informasi riwayat mutasi kas sering menimbulkan kecurigaan dan kesalahpahaman antara bendahara kelas dengan para mahasiswa pembayar kas.

Berdasarkan permasalahan nyata tersebut, dikembangkanlah sistem informasi **KasMahasiswa**, sebuah aplikasi web dinamis yang dirancang untuk menyediakan media pencatatan kas yang terstruktur, aman, transparan, dan dapat diakses dengan mudah dari berbagai perangkat melalui peramban web.

---

## 2. Tujuan Pengembangan Sistem

Tujuan utama dari pengembangan sistem KasMahasiswa adalah:

1. Menyediakan platform pembukuan kas digital yang praktis, cepat, dan akuntabel bagi mahasiswa dan bendahara kelas.
2. Mengotomatiskan perhitungan saldo berjalan (*running balance*), total akumulasi kas masuk (pemasukan), dan total kas keluar (pengeluaran).
3. Memberikan klasifikasi visual yang jelas terhadap pos-pos anggaran keuangan melalui sistem kategori dinamis.
4. Menyediakan fitur pelaporan fleksibel berbasis rentang tanggal dan fungsi ekspor data langsung ke format spreadsheet (Microsoft Excel) untuk kebutuhan audit dan arsip.
5. Memenuhi seluruh standar kompetensi mata kuliah Pemrograman Berbasis Web Universitas Gunadarma, mencakup penerapan siklus SDLC, arsitektur basis data relasional normalisasi 3NF, keamanan kueri PDO, dan antarmuka modern responsif.

---

## 3. Target Pengguna (Aktor Sistem)

Aplikasi KasMahasiswa dirancang untuk melayani dua profil pengguna utama:

1. **Bendahara Kelas / Organisasi**: Bertindak sebagai pengelola utama kas kolektif yang mencatat iuran anggota, mengelola kategori kas, menginput pengeluaran kegiatan kelas/praktikum, dan menyusun laporan pertanggungjawaban keuangan berkala.
2. **Mahasiswa Mandiri (Personal Finance)**: Menggunakan sistem sebagai buku saku keuangan pribadi untuk mengelola uang saku, pendapatan freelance, biaya kuliah, serta membatasi pengeluaran konsumtif bulanan.

---

## 4. Nilai Manfaat Sistem

| Penerima Manfaat | Nilai Manfaat yang Diperoleh |
| --- | --- |
| **Bendahara** | Efisiensi waktu pencatatan hingga 70% dibanding buku manual; penghitungan saldo otomatis tanpa risiko salah rumus; kemudahan ekspor laporan pembukuan. |
| **Anggota Mahasiswa** | Transparansi penuh atas aliran kas kelas; akses informasi mutasi dana secara jelas; menumbuhkan kepercayaan dalam organisasi kelas. |
| **Dosen / Evaluator** | Menjadi bukti artefak proyek rekayasa perangkat lunak yang utuh, bersih, aman dari celah keamanan dasar, dan siap digunakan (*production-ready prototype*). |

---

## 5. Ruang Lingkup Sistem

### A. Ruang Lingkup Termasuk (In-Scope)

- Modul Otentikasi: Pendaftaran pengguna baru, login berbasis session, hash sandi standar BCRYPT, dan logout aman.
- Modul Dashboard: Tampilan metrik total saldo, total pemasukan, total pengeluaran, serta daftar 5 transaksi terbaru.
- Modul Manajemen Kategori: Tambah, lihat, dan kelola kategori kas dengan ikon Bootstrap Icons dan kode warna pembeda.
- Modul Manajemen Transaksi (CRUD): Pencatatan kas masuk/keluar, edit transaksi yang salah, hapus transaksi dengan dialog konfirmasi.
- Modul Laporan & Filter: Filter transaksi berdasarkan rentang tanggal mulai dan tanggal akhir, filter jenis kas, serta tabel rekapitulasi.
- Modul Ekspor: Ekspor hasil filter laporan transaksi kas ke format file Excel (`.xls`).

### B. Ruang Lingkup Tidak Termasuk (Out-of-Scope / Fase Lanjutan)

- Integrasi *Payment Gateway* otomatis (pembayaran iuran kas via QRIS/VA Bank).
- Fitur multi-tenant organisasi dengan hirarki permission bertingkat (Superadmin vs Multi-Bendahara).
- Pengiriman notifikasi tagihan iuran kas otomatis via WhatsApp API.

---

## 6. Kebutuhan Fungsional (Functional Requirements)

| Kode Kebutuhan | Nama Fitur | Deskripsi Fungsional |
| --- | --- | --- |
| **FR-01** | Registrasi Akun | Sistem memungkinkan pengguna baru mendaftarkan akun dengan memasukkan nama lengkap, username unik, dan password. |
| **FR-02** | Login Pengguna | Sistem memvalidasi kredensial username dan password terenkripsi untuk mengizinkan akses ke area internal. |
| **FR-03** | Proteksi Sesi | Sistem melindungi halaman dashboard, transaksi, kategori, dan laporan dari akses tanpa login (*unauthenticated access*). |
| **FR-04** | Ringkasan Saldo | Sistem menghitung dan menampilkan Total Pemasukan, Total Pengeluaran, dan Saldo Bersih secara real-time. |
| **FR-05** | Master Kategori | Sistem mengizinkan pengguna membuat kategori transaksi baru dengan atribut nama, jenis (Pemasukan/Pengeluaran), ikon, dan warna. |
| **FR-06** | Catat Transaksi | Sistem menyediakan formulir pencatatan kas masuk atau keluar dengan tanggal, judul, kategori terkait, nominal uang, dan catatan opsional. |
| **FR-07** | Edit Transaksi | Sistem memungkinkan pengguna memperbarui rincian transaksi kas yang telah tersimpan. |
| **FR-08** | Hapus Transaksi | Sistem menyediakan fitur penghapusan data transaksi yang keliru dengan konfirmasi keamanan. |
| **FR-09** | Filter Laporan | Sistem dapat menyaring riwayat transaksi berdasarkan periode tanggal tertentu dan klasifikasi jenis transaksi. |
| **FR-10** | Ekspor Excel | Sistem dapat menghasilkan dan mengunduh berkas laporan rekapitulasi dalam format spreadsheet Microsoft Excel. |

---

## 7. Kebutuhan Non-Fungsional (Non-Functional Requirements)

### A. Performa & Efisiensi

- Waktu respon pemuatan halaman pada lingkungan localhost rata-rata di bawah 1 detik (< 1000 ms).
- Kueri basis data dioptimalkan menggunakan indeks pada *Primary Key* dan *Foreign Key*.

### B. Keamanan Sistem

- Seluruh kueri interaksi basis data wajib menggunakan PDO *Prepared Statements* untuk mengeliminasi celah SQL Injection.
- Seluruh nilai dinamis yang dirender ke antarmuka HTML wajib melewati sanitasi `htmlspecialchars()` guna mencegah celah Cross-Site Scripting (XSS).
- Password disimpan dalam bentuk hash irreversible menggunakan fungsi bawaan PHP `password_hash($pass, PASSWORD_BCRYPT)`.
- Menggunakan session regeneration untuk memitigasi serangan *Session Fixation*.

### C. Antarmuka Pengguna & Portabilitas

- Antarmuka berbasis web responsif menggunakan Bootstrap 5.3 yang menyesuaikan ukuran layar ponsel pintar, tablet, dan komputer desktop.
- Mengadopsi prinsip desain bersih bernuansa modern (*Apple Clean Human Interface*).
- Kompatibel dengan semua peramban modern (Google Chrome, Microsoft Edge, Mozilla Firefox, Safari).

---

## 8. Asumsi dan Batasan

1. **Lingkungan Server**: Aplikasi ditargetkan berjalan pada lingkungan server Apache dengan PHP versi 8.0 ke atas dan MariaDB 10.4 / MySQL 8.0 bawaan bundel XAMPP.
2. **Koneksi Jaringan**: Dirancang dapat berjalan secara mandiri (*self-contained*) pada jaringan lokal tanpa ketergantungan wajib pada koneksi internet luar.
3. **Mata Uang**: Satuan mata uang baku yang digunakan adalah Rupiah Indonesia (IDR) dengan format bilangan desimal dua digit di belakang koma pada basis data.
