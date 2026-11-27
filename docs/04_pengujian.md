# Tahap 4: Testing & Quality Assurance

Proyek: **KasMahasiswa: Sistem Informasi Pengelolaan Kas Mahasiswa Berbasis Web**  
Mata Kuliah: **Pemrograman Berbasis Web (PBW)**  
Pengembang: **Firdaus Dhuha Prabowo**  
Institusi: **Program Studi Sistem Informasi, Universitas Gunadarma**

---

## 1. Metodologi Pengujian

Pengujian perangkat lunak KasMahasiswa dilakukan dengan menggunakan metode **Black-Box Testing** (Pengujian Kotak Hitam). Fokus pengujian dititikberatkan pada pengujian fungsionalitas eksternal sistem, ketepatan penanganan masukan pengguna (*input validation*), konsistensi alur proses bisnis (*business logic*), serta integritas hasil keluaran (*output verification*) tanpa menguji kode internal secara langsung.

Teknik perancangan kasus uji yang diterapkan:

1. **Equivalence Partitioning**: Membagi domain masukan menjadi partisi data valid dan invalid (misal: input jumlah positif vs negatif).
2. **Boundary Value Analysis**: Menguji batas ekstrem pada kolom formulir (misal: panjang karakter, tanggal batas).
3. **Security Access Control Testing**: Menguji proteksi halaman terhadap upaya akses langsung tanpa melewati otentikasi login (*bypass URL*).

---

## 2. Lingkungan Pengujian

- **Sistem Operasi**: Windows 11 Home 64-bit
- **Peramban Uji**: Microsoft Edge 131, Google Chrome 131
- **Web Server**: Apache 2.4.58 (XAMPP 8.2)
- **Database Server**: MariaDB 10.4.32
- **PHP Version**: PHP 8.2.12

---

## 3. Tabel Kasus Uji Black-Box (40 Kasus Uji)

### A. Modul Otentikasi & Sesi (TC-01 s.d. TC-08)

| ID Uji | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Hasil Pengamatan | Status |
| --- | --- | --- | --- | --- | --- |
| **TC-01** | Registrasi akun dengan data valid | Nama: "Budi Santoso", User: "budi", Pass: "rahasia123" | Akun berhasil disimpan, diarahkan ke form login | Akun tersimpan, redirect ke login | **LULUS** |
| **TC-02** | Registrasi dengan username yang sudah terdaftar | User: "admin" (sudah ada di DB) | Sistem menolak dan menampilkan pesan error username telah dipakai | Muncul peringatan username duplikat | **LULUS** |
| **TC-03** | Registrasi dengan field kosong | Nama: "", User: "", Pass: "" | Sistem memblokir submit form via validasi HTML5/server | Form menolak submit, field wajib diisi | **LULUS** |
| **TC-04** | Login dengan kredensial valid | User: "admin", Pass: "admin123" | Otentikasi sukses, sesi dibuat, redirect ke dashboard | Sesi aktif, masuk ke dashboard | **LULUS** |
| **TC-05** | Login dengan password salah | User: "admin", Pass: "salahpass" | Muncul pesan galat "Username atau password salah" | Muncul pesan error, sesi tidak dibuat | **LULUS** |
| **TC-06** | Login dengan username tidak terdaftar | User: "hacker99", Pass: "bebas" | Muncul pesan galat penolakan login | Muncul notifikasi login gagal | **LULUS** |
| **TC-07** | Akses dashboard tanpa login | Akses URL langsung: `http://localhost/m6/dashboard.php` | Sistem menolak akses dan me-redirect ke login.php | Otomatis dialihkan ke login.php | **LULUS** |
| **TC-08** | Logout pengguna | Klik tombol "Keluar / Logout" | Sesi dihancurkan, dialihkan ke login.php | Sesi tuntas, redirect ke login.php | **LULUS** |

### B. Modul Dashboard & Ringkasan Saldo (TC-09 s.d. TC-12)

| ID Uji | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Hasil Pengamatan | Status |
| --- | --- | --- | --- | --- | --- |
| **TC-09** | Menampilkan total pemasukan | Tambah transaksi pemasukan Rp 500.000 | Angka kartu Total Pemasukan bertambah Rp 500.000 | Akumulasi pemasukan bertambah akurat | **LULUS** |
| **TC-10** | Menampilkan total pengeluaran | Tambah transaksi pengeluaran Rp 200.000 | Angka kartu Total Pengeluaran bertambah Rp 200.000 | Akumulasi pengeluaran bertambah akurat | **LULUS** |
| **TC-11** | Kalkulasi saldo berjalan | Pemasukan Rp 500.000 - Pengeluaran Rp 200.000 | Angka Saldo Kas menampilkan Rp 300.000 | Saldo kas terhitung otomatis Rp 300.000 | **LULUS** |
| **TC-12** | Menampilkan 5 transaksi terkini | Buka halaman dashboard | Tabel hanya menampilkan 5 entri transaksi terbaru | Tepat 5 transaksi terbaru berurut tanggal | **LULUS** |

### C. Modul Manajemen Kategori (TC-13 s.d. TC-18)

| ID Uji | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Hasil Pengamatan | Status |
| --- | --- | --- | --- | --- | --- |
| **TC-13** | Tambah kategori pemasukan valid | Nama: "Sponsorship Acara", Jenis: "Pemasukan", Icon: "bi-cash" | Kategori tersimpan dan muncul di daftar | Kategori baru berhasil masuk tabel | **LULUS** |
| **TC-14** | Tambah kategori pengeluaran valid | Nama: "Konsumsi Rapat", Jenis: "Pengeluaran", Icon: "bi-cup" | Kategori tersimpan dan muncul di daftar | Kategori baru berhasil masuk tabel | **LULUS** |
| **TC-15** | Tambah kategori nama kosong | Nama: "", Jenis: "Pemasukan" | Validasi form memblokir penyimpanan | Form menolak input kosong | **LULUS** |
| **TC-16** | Tampilan visual warna kategori | Memilih warna hex `#FF9500` | Badge kategori dirender dengan warna `#FF9500` | Badge warna tampil sesuai konfigurasi | **LULUS** |
| **TC-17** | Hapus kategori yang belum terpakai | Klik hapus kategori baru | Kategori terhapus dari basis data | Kategori sukses dihapus | **LULUS** |
| **TC-18** | Hapus kategori yang sudah terikat transaksi | Klik hapus kategori "Makanan & Minuman" | Ditolak basis data (Constraint RESTRICT) | Sistem menolak penghapusan berelasi | **LULUS** |

### D. Modul Transaksi Kas CRUD (TC-19 s.d. TC-28)

| ID Uji | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Hasil Pengamatan | Status |
| --- | --- | --- | --- | --- | --- |
| **TC-19** | Catat pemasukan dengan data valid | Judul: "Iuran Kas November", Jml: 250000, Kat: "Uang Saku" | Transaksi tersimpan, saldo bertambah | Tersimpan, saldo kas bertambah | **LULUS** |
| **TC-20** | Catat pengeluaran dengan data valid | Judul: "Beli Kertas HVS", Jml: 55000, Kat: "Kuliah & Buku" | Transaksi tersimpan, saldo berkurang | Tersimpan, saldo kas berkurang | **LULUS** |
| **TC-21** | Catat transaksi nominal 0 atau negatif | Judul: "Uji Saldo", Jml: -50000 | Sistem menolak input angka non-positif | Validasi memblokir nilai negatif | **LULUS** |
| **TC-22** | Catat transaksi judul kosong | Judul: "", Jml: 100000 | Validasi form mewajibkan judul transaksi | Form menolak submit judul kosong | **LULUS** |
| **TC-23** | Edit nominal transaksi | Ubah nominal dari 55000 menjadi 60000 | Nilai transaksi terupdate, saldo terkalkulasi ulang | Nominal terupdate, saldo akurat | **LULUS** |
| **TC-24** | Edit keterangan transaksi | Ubah judul transaksi | Judul baru tampil pada tabel | Keterangan berhasil diperbarui | **LULUS** |
| **TC-25** | Edit kategori transaksi | Ubah kategori terkait | Kategori transaksi berhasil diperbarui | Kategori terupdate sesuai pilihan | **LULUS** |
| **TC-26** | Buka form edit transaksi tidak valid | Akses `transaksi_edit.php?id=99999` (ID fiktif) | Menampilkan pesan error atau dialihkan aman | Ditangani aman, pesan tidak ditemukan | **LULUS** |
| **TC-27** | Hapus transaksi dengan konfirmasi YA | Konfirmasi dialog penghapusan transaksi | Transaksi dihapus, saldo disesuaikan | Data terhapus, saldo kas terupdate | **LULUS** |
| **TC-28** | Batal hapus transaksi | Tekan batal pada dialog konfirmasi | Transaksi tetap utuh di basis data | Data tidak terhapus | **LULUS** |

### E. Modul Laporan & Filter (TC-29 s.d. TC-34)

| ID Uji | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Hasil Pengamatan | Status |
| --- | --- | --- | --- | --- | --- |
| **TC-29** | Filter rentang tanggal normal | Dari: 2026-11-01, Sampai: 2026-11-25 | Menampilkan hanya transaksi pada rentang tersebut | Hanya transaksi bulan berjalan tampil | **LULUS** |
| **TC-30** | Filter rentang tanggal tanpa hasil | Dari: 2020-01-01, Sampai: 2020-01-31 | Menampilkan tabel kosong dengan pesan informatif | Muncul pesan "Tidak ada data transaksi" | **LULUS** |
| **TC-31** | Filter jenis khusus pemasukan | Jenis: "Pemasukan" | Hanya transaksi jenis Pemasukan yang muncul | Seluruh baris adalah kas masuk (+) | **LULUS** |
| **TC-32** | Filter jenis khusus pengeluaran | Jenis: "Pengeluaran" | Hanya transaksi jenis Pengeluaran yang muncul | Seluruh baris adalah kas keluar (-) | **LULUS** |
| **TC-33** | Rekapitulasi total pada laporan | Filter periode aktif | Menampilkan subtotal pemasukan, pengeluaran, saldo | Nilai subtotal dihitung akurat | **LULUS** |
| **TC-34** | Tanggal akhir lebih kecil dari tanggal awal | Dari: 2026-11-30, Sampai: 2026-11-01 | Menangani urutan tanggal secara logis tanpa crash | Sistem menangani dengan aman | **LULUS** |

### F. Modul Ekspor Data (TC-35 s.d. TC-37)

| ID Uji | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Hasil Pengamatan | Status |
| --- | --- | --- | --- | --- | --- |
| **TC-35** | Ekspor seluruh transaksi ke Excel | Klik tombol "Unduh Excel" | Peramban mengunduh file `.xls` berisi semua data | Berkas terunduh, kolom lengkap | **LULUS** |
| **TC-36** | Ekspor hasil filter tanggal | Terapkan filter lalu klik Ekspor | File Excel hanya memuat data hasil filter | Isi Excel presisi sesuai filter | **LULUS** |
| **TC-37** | Integritas format file Excel | Buka berkas unduhan di Microsoft Excel / Calc | Tabel terbuka tanpa galat format atau teks rusak | File terbuka sempurna dan rapi | **LULUS** |

### G. Pengujian Keamanan & Hak Akses (TC-38 s.d. TC-40)

| ID Uji | Skenario Pengujian | Masukan (Input) | Hasil yang Diharapkan | Hasil Pengamatan | Status |
| --- | --- | --- | --- | --- | --- |
| **TC-38** | Uji injeksi SQL pada form login | User: `' OR '1'='1`, Pass: `' OR '1'='1` | Otentikasi ditolak, kueri aman via prepared stmt | Login gagal, kueri kebal SQLi | **LULUS** |
| **TC-39** | Uji serangan XSS pada judul transaksi | Judul: `<script>alert('XSS')</script>` | Skrip tidak dieksekusi, teks di-escape aman | Tampil literal sebagai teks aman | **LULUS** |
| **TC-40** | Uji akses file config langsung | Buka URL: `http://localhost/m6/config/koneksi.php` | Tidak menampilkan kredensial database | Layar putih kosong, kredensial aman | **LULUS** |

---

## 4. Ringkasan Hasil Pengujian

- **Total Kasus Uji**: 40 Kasus Uji
- **Jumlah Kasus Lulus**: 40 Kasus (100%)
- **Jumlah Kasus Gagal**: 0 Kasus (0%)
- **Tingkat Kelulusan (*Pass Rate*)**: **100%**
- **Kesimpulan**: Aplikasi KasMahasiswa telah memenuhi seluruh kriteria fungsionalitas dan keamanan yang ditetapkan, bebas dari cacat kritis (*zero critical bug*), serta siap digunakan secara stabil.
