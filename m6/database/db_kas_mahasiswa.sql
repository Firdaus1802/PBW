CREATE DATABASE IF NOT EXISTS `db_kas_mahasiswa` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_kas_mahasiswa`;

DROP TABLE IF EXISTS `kredit_paylater`;
DROP TABLE IF EXISTS `savings_goals`;
DROP TABLE IF EXISTS `budget_settings`;
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

CREATE TABLE `kategori` (
  `id_kategori` INT NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `jenis` ENUM('Pemasukan', 'Pengeluaran') NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'bi-tag',
  `warna` VARCHAR(20) DEFAULT '#007AFF',
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transaksi` (
  `id_transaksi` INT NOT NULL AUTO_INCREMENT,
  `id_user` INT NOT NULL DEFAULT 1,
  `id_kategori` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `judul` VARCHAR(150) NOT NULL,
  `jenis` ENUM('Pemasukan', 'Pengeluaran') NOT NULL,
  `jumlah` DECIMAL(12,2) NOT NULL,
  `catatan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaksi`),
  KEY `fk_transaksi_user` (`id_user`),
  KEY `fk_transaksi_kategori` (`id_kategori`),
  CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`id_user`) 
    REFERENCES `users` (`id_user`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_transaksi_kategori` FOREIGN KEY (`id_kategori`) 
    REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`) VALUES
(1, 'admin', '$2y$10$1Zeb6yV0L3w2l3A55sPBgeAVcS8gxoYiTEYQS5Yy7RvEnVF4cYAgC', 'Firdaus Dhuha Prabowo');

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `jenis`, `icon`, `warna`) VALUES
(1, 'Uang Saku / Kiriman Ortu', 'Pemasukan', 'bi-wallet2', '#34C759'),
(2, 'Gaji / Project Freelance', 'Pemasukan', 'bi-laptop', '#007AFF'),
(3, 'Beasiswa / Prestasi', 'Pemasukan', 'bi-award', '#5856D6'),
(4, 'Makanan & Minuman', 'Pengeluaran', 'bi-cup-hot', '#FF9500'),
(5, 'Kuliah & Buku Pelajaran', 'Pengeluaran', 'bi-book', '#AF52DE'),
(6, 'Transportasi & Bensin', 'Pengeluaran', 'bi-fuel-pump', '#5AC8FA'),
(7, 'Kos & Kebutuhan Harian', 'Pengeluaran', 'bi-house-door', '#FF3B30'),
(8, 'Hiburan & Ngopi', 'Pengeluaran', 'bi-controller', '#FF2D55');

CREATE TABLE `budget_settings` (
  `id_user` INT NOT NULL,
  `limit_bulanan` DECIMAL(12,2) NOT NULL DEFAULT 1500000.00,
  PRIMARY KEY (`id_user`),
  CONSTRAINT `fk_budget_user` FOREIGN KEY (`id_user`)
    REFERENCES `users` (`id_user`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `savings_goals` (
  `id_user` INT NOT NULL,
  `nama_target` VARCHAR(100) NOT NULL DEFAULT 'Dana Darurat',
  `target_nominal` DECIMAL(12,2) NOT NULL DEFAULT 2000000.00,
  PRIMARY KEY (`id_user`),
  CONSTRAINT `fk_goal_user` FOREIGN KEY (`id_user`)
    REFERENCES `users` (`id_user`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `budget_settings` (`id_user`, `limit_bulanan`) VALUES (1, 1500000.00);
INSERT INTO `savings_goals` (`id_user`, `nama_target`, `target_nominal`) VALUES (1, 'Dana Darurat', 2000000.00);
