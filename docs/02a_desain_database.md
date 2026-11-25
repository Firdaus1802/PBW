# Tahap 2a: System & Database Design (Basis Data)

Proyek: **KasMahasiswa: Sistem Informasi Pengelolaan Kas Mahasiswa Berbasis Web**  
Mata Kuliah: **Pemrograman Berbasis Web (PBW)**  
Pengembang: **Firdaus Dhuha Prabowo**  
Institusi: **Program Studi Sistem Informasi, Universitas Gunadarma**

---

## 1. Arsitektur Basis Data

Aplikasi KasMahasiswa menggunakan sistem manajemen basis data relasional (*Relational Database Management System* - RDBMS) **MariaDB 10.4 / MySQL 8.0** dengan engine penyimpanan **InnoDB**. Pemilihan engine InnoDB didasarkan pada kebutuhan integritas data transaksional yang mendukung fitur *Foreign Key Constraints*, transaksi *ACID (Atomicity, Consistency, Isolation, Durability)*, serta performa konkurensi berbasis *row-level locking*.

Database diberi nama: `db_kas_mahasiswa` dengan encoding karakter standar `utf8mb4` dan collation `utf8mb4_unicode_ci` untuk mendukung kelengkapan karakter internasional.

---

## 2. Entity Relationship Diagram (ERD)

Struktur relasi antartabel dalam sistem dirancang secara efisien dengan 3 entitas inti:
1. `users`: Menyimpan data profil akun pengguna / bendahara.
2. `kategori`: Menyimpan pengelompokan pos keuangan (Pemasukan dan Pengeluaran).
3. `transaksi`: Menyimpan catatan mutasi kas yang merujuk pada pengguna pencatat dan kategori terkait.

```
+--------------------+            +------------------------+
|       users        |            |        transaksi       |
+--------------------+            +------------------------+
| PK  id_user        |<-----------| FK  id_user            |
|     username       |    1 : N   | PK  id_transaksi       |
|     password       |            | FK  id_kategori        |--------+
|     nama_lengkap   |            |     tanggal            |        |
|     created_at     |            |     judul              |        |
+--------------------+            |     jenis              |        |
                                  |     jumlah             |        |
                                  |     catatan            |        |
                                  |     created_at         |        |
                                  +------------------------+        |
                                                                    | N : 1
                                  +------------------------+        |
                                  |        kategori        |        |
                                  +------------------------+        |
                                  | PK  id_kategori        |<-------+
                                  |     nama_kategori      |
                                  |     jenis              |
                                  |     icon               |
                                  |     warna              |
                                  +------------------------+
```

### Penjelasan Kardinalitas Relasi:
- **Relasi `users` ke `transaksi` (One-to-Many / 1:N)**: Satu pengguna (bendahara) dapat mencatat banyak transaksi kas, namun satu transaksi kas dicatat secara spesifik oleh satu pengguna.
- **Relasi `kategori` ke `transaksi` (One-to-Many / 1:N)**: Satu kategori kas dapat digunakan oleh banyak catatan transaksi kas, namun satu transaksi kas hanya terikat pada satu kategori spesifik.

---

## 3. Kamus Data (Data Dictionary)

### A. Tabel `users`
Tabel ini berfungsi untuk menyimpan kredensial otentikasi akun pengguna sistem.

| Nama Kolom | Tipe Data | Null | Keterangan / Default |
|---|---|---|---|
| `id_user` | INT(11) | NO | **Primary Key**, Auto Increment. Identifier unik setiap akun. |
| `username` | VARCHAR(50) | NO | **Unique Key**. Nama pengguna untuk keperluan login sistem. |
| `password` | VARCHAR(255) | NO | Hash kata sandi menggunakan algoritma BCRYPT (60-72 karakter). |
| `nama_lengkap` | VARCHAR(100) | NO | Nama lengkap pengguna / bendahara mahasiswa. |
| `created_at` | TIMESTAMP | YES | Waktu pendaftaran akun (`CURRENT_TIMESTAMP`). |

### B. Tabel `kategori`
Tabel master yang menyimpan daftar pos anggaran kas masuk dan kas keluar.

| Nama Kolom | Tipe Data | Null | Keterangan / Default |
|---|---|---|---|
| `id_kategori` | INT(11) | NO | **Primary Key**, Auto Increment. Identifier unik kategori. |
| `nama_kategori` | VARCHAR(100) | NO | Nama pengelompokan kas (contoh: Uang Saku, Konsumsi). |
| `jenis` | ENUM | NO | Nilai: `'Pemasukan'` atau `'Pengeluaran'`. |
| `icon` | VARCHAR(50) | YES | Nama kelas CSS ikon Bootstrap Icons (default: `'bi-tag'`). |
| `warna` | VARCHAR(20) | YES | Kode warna HEX representasi visual (default: `'#007AFF'`). |

### C. Tabel `transaksi`
Tabel utama yang menyimpan riwayat pembukuan transaksi kas.

| Nama Kolom | Tipe Data | Null | Keterangan / Default |
|---|---|---|---|
| `id_transaksi` | INT(11) | NO | **Primary Key**, Auto Increment. Identifier unik transaksi. |
| `id_user` | INT(11) | NO | **Foreign Key** merujuk ke `users(id_user)`. |
| `id_kategori` | INT(11) | NO | **Foreign Key** merujuk ke `kategori(id_kategori)`. |
| `tanggal` | DATE | NO | Tanggal kejadian transaksi kas (format YYYY-MM-DD). |
| `judul` | VARCHAR(150) | NO | Keterangan ringkas transaksi kas. |
| `jenis` | ENUM | NO | Nilai: `'Pemasukan'` atau `'Pengeluaran'`. |
| `jumlah` | DECIMAL(12,2) | NO | Nominal nominal kas dalam mata uang Rupiah (maks 999 milyar). |
| `catatan` | TEXT | YES | Catatan rincian tambahan terkait transaksi. |
| `created_at` | TIMESTAMP | YES | Waktu pembuatan entri transaksi (`CURRENT_TIMESTAMP`). |

---

## 4. Integritas Referensial & Foreign Key Constraints

Untuk menjamin konsistensi dan integritas data (*referential integrity*), skema basis data menerapkan batasan relasi asing sebagai berikut:

```sql
CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`id_user`) 
  REFERENCES `users` (`id_user`) 
  ON UPDATE CASCADE 
  ON DELETE CASCADE,

CONSTRAINT `fk_transaksi_kategori` FOREIGN KEY (`id_kategori`) 
  REFERENCES `kategori` (`id_kategori`) 
  ON UPDATE CASCADE 
  ON DELETE RESTRICT
```

- **`ON DELETE CASCADE` pada `fk_transaksi_user`**: Apabila suatu akun pengguna dihapus, seluruh data riwayat transaksi yang dicatat oleh pengguna tersebut akan otomatis terhapus secara aman.
- **`ON DELETE RESTRICT` pada `fk_transaksi_kategori`**: Kategori yang sedang digunakan oleh satu atau lebih transaksi kas **tidak dapat dihapus**, mencegah terjadinya transaksi yatim (*orphaned records*) yang kehilangan referensi kategori.

---

## 5. Analisis Normalisasi Basis Data

Struktur basis data `db_kas_mahasiswa` telah melalui proses normalisasi hingga tingkat **Third Normal Form (3NF)**:
1. **First Normal Form (1NF)**:
   - Setiap kolom hanya memuat nilai skalar tunggal (*atomic values*).
   - Tidak ada kelompok kolom yang berulang (*repeating groups*).
   - Setiap tabel memiliki Primary Key unik (`id_user`, `id_kategori`, `id_transaksi`).
2. **Second Normal Form (2NF)**:
   - Telah memenuhi syarat 1NF.
   - Seluruh atribut non-kunci bergantung secara fungsional penuh pada Primary Key (*no partial dependency*).
3. **Third Normal Form (3NF)**:
   - Telah memenuhi syarat 2NF.
   - Tidak terdapat ketergantungan transitif (*transitive dependency*). Atribut deskripsi kategori (`nama_kategori`, `jenis`, `icon`, `warna`) dipisahkan ke tabel `kategori`, sehingga tabel `transaksi` hanya menyimpan referensi `id_kategori`.

---

## 6. Kueri Agregasi Utama

Perhitungan statistik keuangan pada dashboard dieksekusi melalui kueri SQL agregasi yang efisien:

```sql
-- 1. Menghitung Total Pemasukan
SELECT COALESCE(SUM(jumlah), 0) AS total_pemasukan 
FROM transaksi 
WHERE id_user = :user_id AND jenis = 'Pemasukan';

-- 2. Menghitung Total Pengeluaran
SELECT COALESCE(SUM(jumlah), 0) AS total_pengeluaran 
FROM transaksi 
WHERE id_user = :user_id AND jenis = 'Pengeluaran';

-- 3. Mengambil Riwayat 5 Transaksi Terakhir Beserta Nama Kategori
SELECT t.*, k.nama_kategori, k.icon, k.warna 
FROM transaksi t
JOIN kategori k ON t.id_kategori = k.id_kategori
WHERE t.id_user = :user_id
ORDER BY t.tanggal DESC, t.id_transaksi DESC
LIMIT 5;
```
