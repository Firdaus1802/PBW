CREATE TABLE IF NOT EXISTS kredit_paylater (
  id_kredit INT NOT NULL AUTO_INCREMENT,
  nama_tagihan VARCHAR(100) NOT NULL,
  jumlah_tagihan DECIMAL(12,2) NOT NULL,
  jatuh_tempo DATE NOT NULL,
  status ENUM('Belum Lunas', 'Lunas') DEFAULT 'Belum Lunas',
  catatan VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_kredit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO kredit_paylater (nama_tagihan, jumlah_tagihan, jatuh_tempo, status, catatan) VALUES
('Spaylater - Beli Headset & Mouse', 180000.00, '2026-11-25', 'Belum Lunas', 'Cicilan perlengkapan kuliah PBW'),
('Cicilan Modul Lab Komputer', 120000.00, '2026-11-28', 'Belum Lunas', 'Modul praktikum sistem informasi'),
('Pinjam Uang Bensin Teman', 50000.00, '2026-11-20', 'Lunas', 'Sudah diganti pas kiriman ortu masuk');
