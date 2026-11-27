# Tahap 5: Deployment & Maintenance Plan

Proyek: **KasMahasiswa: Sistem Informasi Pengelolaan Kas Mahasiswa Berbasis Web**  
Mata Kuliah: **Pemrograman Berbasis Web (PBW)**  
Pengembang: **Firdaus Dhuha Prabowo**  
Institusi: **Program Studi Sistem Informasi, Universitas Gunadarma**

---

## 1. Skema Deployment Lingkungan Lokal (Localhost XAMPP)

Untuk keperluan demonstrasi evaluasi tugas mata kuliah Pemrograman Berbasis Web, aplikasi KasMahasiswa di-deploy pada lingkungan server lokal menggunakan **XAMPP**:

### Langkah-langkah Instalasi:

1. **Penyiapan Direktori**: Tempatkan seluruh folder repositori ke dalam direktori publik web server Apache:

   `C:\xampp\htdocs\pbw\`

2. **Inisialisasi Basis Data**:
   - Buka peramban ke `http://localhost/phpmyadmin/`.
   - Buat basis data baru bernama `db_kas_mahasiswa`.
   - Lakukan impor berkas `database/db_kas_mahasiswa.sql`.
3. **Verifikasi Konfigurasi**:
   - Pastikan berkas `m6/config/koneksi.php` memuat setelan:

     `$host = "localhost"; $username = "root"; $password = ""; $database = "db_kas_mahasiswa";`

4. **Pengujian Akses**:
   - Buka peramban di alamat `http://localhost/pbw/` (akan otomatis mengarahkan ke dashboard KasMahasiswa).

---

## 2. Skema Deployment Lingkungan Cloud / Produksi

Untuk implementasi publik agar dapat diakses oleh seluruh anggota kelas dari mana saja, disiapkan dua alternatif skema:

### A. Opsi 1: Shared Hosting cPanel (Rekomendasi Utama)

- **Spesifikasi**: PHP 8.2+, MySQL 8.0, cPanel, SSL Aktif (Let's Encrypt).
- **Prosedur**:
  1. Kompres seluruh berkas proyek menjadi format `.zip`.
  2. Unggah berkas ke folder `public_html` di cPanel File Manager lalu ekstrak.
  3. Buat database dan user database baru di cPanel MySQL Databases.
  4. Impor `db_kas_mahasiswa.sql` via phpMyAdmin cPanel.
  5. Sesuaikan parameter koneksi di `m6/config/koneksi.php`.

### B. Opsi 2: Vercel Serverless PHP (Modern Jamstack)

- Repositori telah dilengkapi berkas konfigurasi `vercel.json` dan perutean `api/index.php`.
- Basis data MySQL dapat dihubungkan ke layanan cloud database gratis seperti *Aiven MySQL* atau *PlanetScale*.

---

## 3. Rencana Pemeliharaan & Pencadangan Data (Maintenance & Backup)

1. **Jadwal Pencadangan Basis Data Rutin**:
   - Bendahara disarankan melakukan ekspor SQL basis data secara berkala (minimal setiap akhir bulan atau setelah pengumpulan kas besar) melalui phpMyAdmin (`mysqldump`).
2. **Pencadangan Berkas Laporan**:
   - Memanfaatkan fitur ekspor laporan ke format Excel secara rutin untuk disimpan di Google Drive kelas sebagai arsip salinan luring (*offline backup*).
3. **Pembaruan Dependensi & Keamanan**:
   - Melakukan audit berkala terhadap fungsi-fungsi PHP deprecated pada versi PHP rilis terbaru.
   - Memastikan hak akses berkas (*file permissions*) pada hosting diatur secara ketat (folder `755`, file `644`).

---

## 4. Rencana Pengembangan Masa Depan (Fase Lanjutan)

1. **Visualisasi Grafik Analitik**: Menambahkan grafik pie chart alokasi pengeluaran per kategori dan grafik garis (*line chart*) tren saldo kas bulanan menggunakan library *Chart.js*.
2. **Dukungan Multi-Kelas**: Menambahkan entitas `organisasi` atau `kelas` sehingga satu sistem dapat digunakan bersama oleh banyak kelas secara terisolasi.
3. **Integrasi Notifikasi Bot**: Mengintegrasikan WhatsApp Gateway / Telegram Bot untuk menyiarkan ringkasan kas bulanan secara otomatis ke grup kelas.
