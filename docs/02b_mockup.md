# Tahap 2b: System & Interface Design (Mockup Antarmuka)

Proyek: **KasMahasiswa: Sistem Informasi Pengelolaan Kas Mahasiswa Berbasis Web**  
Mata Kuliah: **Pemrograman Berbasis Web (PBW)**  
Pengembang: **Firdaus Dhuha Prabowo**  
Institusi: **Program Studi Sistem Informasi, Universitas Gunadarma**

---

## 1. Panduan Desain Visual (Design System)

Desain antarmuka KasMahasiswa mengusung prinsip estetika **Clean Modern & Responsive Human Interface**, terinspirasi dari antarmuka modern Apple iOS dan macOS yang mengedepankan keterbacaan tinggi (*high legibility*), tata letak lapang (*generous whitespace*), sudut melengkung halus (*rounded corners*), serta hierarki visual yang jelas.

### A. Palet Warna Baku

| Nama Warna | Kode HEX | Peruntukan / Semantik |
| --- | --- | --- |
| **Primary Blue** | `#007AFF` | Warna aksen utama sistem, tombol CTA primer, dan status aktif navigasi. |
| **Success Green** | `#34C759` | Indikator kas masuk (pemasukan), tombol konfirmasi, dan status saldo positif. |
| **Danger Red** | `#FF3B30` | Indikator kas keluar (pengeluaran), tombol hapus, dan pesan peringatan bahaya. |
| **Warning Orange** | `#FF9500` | Indikator kategori sekunder dan peringatan perhatian. |
| **Background Light** | `#F8F9FA` | Latar belakang kanvas aplikasi yang nyaman di mata. |
| **Surface Card** | `#FFFFFF` | Latar kartu konten dengan efek bayangan lembut (*box-shadow: 0 4px 12px rgba(0,0,0,0.05)*). |
| **Text Primary** | `#1C1C1E` | Teks judul dan label utama dengan kontras optimal. |
| **Text Muted** | `#6C757D` | Teks keterangan bantuan, timestamp, dan label pendukung. |

### B. Tipografi & Ikonografi

- **Font Utama**: *Plus Jakarta Sans* / *system-ui*, `-apple-system`, `Segoe UI`, `Roboto`, `sans-serif`.
- **Ikon**: *Bootstrap Icons v1.11.3* yang ringan, konsisten secara geometris, dan mudah dipanggil via class HTML.

---

## 2. Peta Halaman (Sitemap)

```text
[ Gerbang Utama: index.php ]
            |
            +---> [ Belum Login ] ---> login.php <---> register.php
            |
            +---> [ Sudah Login ] ---> dashboard.php (Ringkasan Keuangan)
                                            |
                                            +---> [ Transaksi Kas ]
                                            |         |---> Catat Baru (Modal)
                                            |         |---> transaksi_edit.php
                                            |         +---> transaksi_hapus.php
                                            |
                                            +---> [ Kategori Kas ]
                                            |         +---> kategori.php (CRUD Kategori)
                                            |
                                            +---> [ Laporan & Ekspor ]
                                            |         |---> laporan.php (Filter Periode)
                                            |         +---> export_excel.php
                                            |
                                            +---> logout.php
```

---

## 3. Alur Pengguna (User Flow)

1. **Alur Masuk (Authentication Flow)**:
   - Pengguna membuka web -> jika belum ada sesi login, dialihkan ke `login.php`.
   - Jika belum memiliki akun, pengguna memilih opsi "Daftar Akun" menuju `register.php`.
   - Setelah input data divalidasi, sistem menyimpan akun baru dan pengguna dapat login menuju `dashboard.php`.
2. **Alur Pencatatan Transaksi (Transaction Flow)**:
   - Pengguna menekan tombol "+ Tambah Transaksi" di Dashboard.
   - Form modal muncul meminta input: Tanggal, Judul Transaksi, Jenis (Pemasukan/Pengeluaran), Kategori, dan Jumlah Rupiah.
   - Sistem memvalidasi nominal > 0 -> data tersimpan ke tabel `transaksi` -> saldo total di dashboard langsung diperbarui.
3. **Alur Pelaporan & Ekspor (Reporting Flow)**:
   - Pengguna membuka menu "Laporan".
   - Pengguna menentukan rentang tanggal (misal: 01/11/2026 s.d. 30/11/2026) dan memilih jenis transaksi.
   - Menekan tombol "Filter": tabel menyajikan data transaksi sesuai kriteria beserta subtotal pemasukan, pengeluaran, dan selisih saldo.
   - Menekan tombol "Ekspor Excel": peramban langsung mengunduh berkas `.xls`.

---

## 4. Desain Mockup Antarmuka (Wireframe & Layout)

### A. Mockup Halaman Login (`login.php`)

```text
+-------------------------------------------------------------+
|                                                             |
|                   +-----------------------+                 |
|                   |   [Icon Dompet Kas]   |                 |
|                   |     KasMahasiswa      |                 |
|                   | Kelola Keuangan Mudah |                 |
|                   |-----------------------|                 |
|                   | Username              |                 |
|                   | [ admin             ] |                 |
|                   |                       |                 |
|                   | Password              |                 |
|                   | [ **********        ] |                 |
|                   |                       |                 |
|                   | [   Masuk Sekarang  ] |                 |
|                   |                       |                 |
|                   | Belum punya akun?     |                 |
|                   | [Daftar di sini]      |                 |
|                   +-----------------------+                 |
|                                                             |
+-------------------------------------------------------------+
```

### B. Mockup Halaman Dashboard (`dashboard.php`)

```text
+-------------------------------------------------------------------------+
| [Logo] KasMahasiswa        Dashboard   Kategori   Laporan   [Hai, Firdaus] [Logout] |
+-------------------------------------------------------------------------+
| Selamat Datang, Firdaus Dhuha Prabowo!                                  |
| Berikut ringkasan kas keuangan Anda per hari ini.                       |
|                                                                         |
| +-------------------+  +-------------------+  +-----------------------+ |
| | TOTAL SALDO KAS   |  | TOTAL PEMASUKAN   |  | TOTAL PENGELUARAN     | |
| | Rp 1.450.000      |  | Rp 2.500.000      |  | Rp 1.050.000          | |
| | [Status: Aman]    |  | [Icon Panah Naik] |  | [Icon Panah Turun]    | |
| +-------------------+  +-------------------+  +-----------------------+ |
|                                                                         |
| Transaksi Terkini                        [ + Catat Transaksi Baru ]     |
| +------------+-----------------------+-------------+--------------+---+ |
| | Tanggal    | Keterangan Transaksi  | Kategori    | Jumlah       |Aks| |
| +------------+-----------------------+-------------+--------------+---+ |
| | 24/11/2026 | Kiriman Uang Saku Ortu| Uang Saku   | +Rp1.000.000 |...| |
| | 24/11/2026 | Fotokopi Berkas Laprak| Kuliah/Buku | -Rp   15.000 |...| |
| | 24/11/2026 | Makan Siang Rice Box  | Makanan/Min | -Rp   12.000 |...| |
| | 23/11/2026 | Bensin Motor & Ojol   | Transportasi| -Rp   20.000 |...| |
| | 22/11/2026 | Honor Proyek Desain   | Freelance   | +Rp  500.000 |...| |
| +------------+-----------------------+-------------+--------------+---+ |
|                                              [ Lihat Semua Laporan -> ] |
+-------------------------------------------------------------------------+
```

### C. Mockup Halaman Laporan & Ekspor (`laporan.php`)

```text
+-------------------------------------------------------------------------+
| [Logo] KasMahasiswa        Dashboard   Kategori   Laporan   [Hai, Firdaus] [Logout] |
+-------------------------------------------------------------------------+
| Laporan & Rekapitulasi Kas                                              |
|                                                                         |
| +---------------------------------------------------------------------+ |
| | Dari: [ 01/11/2026 ]  Sampai: [ 30/11/2026 ]  Jenis: [ Semua v ]    | |
| | [ Terapkan Filter ]                           [ Unduh Excel (.xls) ]| |
| +---------------------------------------------------------------------+ |
|                                                                         |
| Ringkasan Periode Ini:                                                  |
| Pemasukan: Rp 2.500.000  |  Pengeluaran: Rp 1.050.000  | Sisa: Rp 1.450.000 |
|                                                                         |
| Data Transaksi Periode Terpilih:                                        |
| +----+------------+--------------------------+-------------+----------+ |
| | No | Tanggal    | Keterangan               | Kategori    | Nominal  | |
| +----+------------+--------------------------+-------------+----------+ |
| | 1  | 01/11/2026 | Saldo Awal Bulan         | Uang Saku   | Rp500.000| |
| | 2  | 05/11/2026 | Pembelian Buku Referensi | Kuliah/Buku | Rp 85.000| |
| | ...| ...        | ...                      | ...         | ...      | |
| +----+------------+--------------------------+-------------+----------+ |
+-------------------------------------------------------------------------+
```
