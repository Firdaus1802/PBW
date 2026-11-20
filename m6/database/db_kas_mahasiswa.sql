-- Database: db_kas_mahasiswa
-- Sistem Pencatatan Kas & Pengeluaran Mahasiswa (iOS Style)
-- Mata Kuliah: Pemrograman Berbasis Web (PBW)

CREATE DATABASE IF NOT EXISTS `db_kas_mahasiswa` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_kas_mahasiswa`;

-- 1. Tabel Pengguna (Admin / Mahasiswa)
DROP TABLE IF EXISTS `kredit_paylater`;
DROP TABLE IF EXISTS `transaksi`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id_user` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Master Kategori (Relasi 1:N ke Transaksi)
CREATE TABLE `kategori` (
  `id_kategori` INT NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `jenis` ENUM('Pemasukan', 'Pengeluaran') NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'bi-tag',
  `warna` VARCHAR(20) DEFAULT '#007AFF',
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Transaksi Kas
CREATE TABLE `transaksi` (
  `id_transaksi` INT NOT NULL AUTO_INCREMENT,
  `id_kategori` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `judul` VARCHAR(150) NOT NULL,
  `jenis` ENUM('Pemasukan', 'Pengeluaran') NOT NULL,
  `jumlah` DECIMAL(12,2) NOT NULL,
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaksi`),
  KEY `fk_transaksi_kategori` (`id_kategori`),
  CONSTRAINT `fk_transaksi_kategori` FOREIGN KEY (`id_kategori`) 
    REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================
-- SEED DATA (DATA AWAL OTENTIK)
-- ====================================================

-- Data Pengguna: username 'admin', password 'admin123' (Bcrypt Hash)
INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`) VALUES
(1, 'admin', '$2y$10$1Zeb6yV0L3w2l3A55sPBgeAVcS8gxoYiTEYQS5Yy7RvEnVF4cYAgC', 'Firdaus Dhuha Prabowo');

-- Data Master Kategori (Pemasukan & Pengeluaran Mahasiswa)
INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `jenis`, `icon`, `warna`) VALUES
(1, 'Uang Saku / Kiriman Ortu', 'Pemasukan', 'bi-wallet2', '#34C759'),
(2, 'Gaji / Project Freelance', 'Pemasukan', 'bi-laptop', '#007AFF'),
(3, 'Beasiswa / Prestasi', 'Pemasukan', 'bi-award', '#5856D6'),
(4, 'Makanan & Minuman', 'Pengeluaran', 'bi-cup-hot', '#FF9500'),
(5, 'Kuliah & Buku Pelajaran', 'Pengeluaran', 'bi-book', '#AF52DE'),
(6, 'Transportasi & Bensin', 'Pengeluaran', 'bi-fuel-pump', '#5AC8FA'),
(7, 'Kos & Kebutuhan Harian', 'Pengeluaran', 'bi-house-door', '#FF3B30'),
(8, 'Hiburan & Ngopi', 'Pengeluaran', 'bi-controller', '#FF2D55');

-- Data Transaksi Realistis
INSERT INTO `transaksi` (`id_transaksi`, `id_kategori`, `tanggal`, `judul`, `jenis`, `jumlah`, `catatan`) VALUES
(1, 1, '2026-11-01', 'Transfer Uang Saku Bulanan', 'Pemasukan', 2500000.00, 'Kiriman bulanan orang tua awal bulan November'),
(2, 7, '2026-11-02', 'Bayar Uang Kos Bulanan', 'Pengeluaran', 850000.00, 'Pembayaran sewa kamar kos periode November'),
(3, 2, '2026-11-05', 'Pembayaran Pembuatan Web Landing Page', 'Pemasukan', 750000.00, 'Project freelance UMKM toko kue'),
(4, 5, '2026-11-07', 'Beli Buku Pemrograman Web & Fotokopi Modul', 'Pengeluaran', 125000.00, 'Modul praktikum PBW dan buku acuan'),
(5, 4, '2026-11-10', 'Makan Siang & Stok Mie/Camilan Kos', 'Pengeluaran', 85000.00, 'Belanja mingguan di minimarket'),
(6, 6, '2026-11-12', 'Isi Bensin Motor & Servis Ringan', 'Pengeluaran', 60000.00, 'Ganti oli dan bensin pertalite'),
(7, 8, '2026-11-15', 'Nugas Bareng di Cafe Kampus', 'Pengeluaran', 45000.00, 'Kopi americano dan cemilan bareng teman');
