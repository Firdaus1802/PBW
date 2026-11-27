# Tahap 3: Implementation & Development

Proyek: **KasMahasiswa: Sistem Informasi Pengelolaan Kas Mahasiswa Berbasis Web**  
Mata Kuliah: **Pemrograman Berbasis Web (PBW)**  
Pengembang: **Firdaus Dhuha Prabowo**  
Institusi: **Program Studi Sistem Informasi, Universitas Gunadarma**

---

## 1. Arsitektur Kode dan Struktur Direktori

Pengembangan aplikasi KasMahasiswa menerapkan pola modular berbasis PHP native yang memisahkan tanggung jawab konfigurasi, pengelolaan sesi, penanganan basis data, dan tampilan antarmuka. Struktur direktori ditata secara teratur sebagai berikut:

```text
c:\Users\HP\Downloads\PBW\
│
├── m6/
│   ├── config/
│   │   └── koneksi.php          # Konfigurasi PDO, session start, dan fungsi helper
│   ├── assets/
│   │   ├── css/                 # Berkas stylesheet kustom
│   │   ├── js/                  # Skrip JavaScript interaktif
│   │   └── icons/               # Aset ikon dan logo
│   ├── login.php                # Modul autentikasi masuk
│   ├── register.php             # Modul pendaftaran pengguna baru
│   ├── dashboard.php            # Modul utama metrik keuangan dan aksi cepat
│   ├── kategori.php             # Modul CRUD master data kategori
│   ├── transaksi_edit.php       # Modul pembaharuan transaksi kas
│   ├── transaksi_hapus.php      # Modul penghapusan transaksi aman
│   ├── laporan.php              # Modul filter rekapitulasi transaksi
│   ├── export_excel.php         # Modul generator berkas spreadsheet Excel
│   ├── logout.php               # Modul pembersihan sesi aktif
│   └── manifest.json            # Konfigurasi metadata Progressive Web App
│
├── database/
│   └── db_kas_mahasiswa.sql     # Skrip skema database relasional & data awal
├── docs/                        # Dokumentasi SDLC 01 s.d. 05 & Laporan
├── tests/                       # Skrip pengujian otomatis black-box
└── index.php                    # Gerbang perutean awal (redirect ke /m6/index.php)
```

---

## 2. Mekanisme Koneksi Basis Data PDO (`config/koneksi.php`)

Koneksi ke MariaDB dibangun menggunakan ekstensi **PHP Data Objects (PDO)**. PDO dipilih karena memiliki keunggulan fleksibilitas driver basis data, penanganan galat berorientasi objek (*Exception Handling*), serta dukungan bawaan terhadap *prepared statements* yang secara inheren kebal terhadap celah SQL Injection.

Berikut adalah implementasi kode koneksi basis data:

```php
<?php
// m6/config/koneksi.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = "localhost";
$username = "root";
$password = "";
$database = "db_kas_mahasiswa";
$charset  = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$database;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Koneksi basis data gagal: " . $e->getMessage());
}

function base_url($path = '') {
    return '/m6/' . ltrim($path, '/');
}
```

### Keunggulan Konfigurasi:

1. `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`: Menjamin setiap galat sintaks atau eksekusi SQL akan memicu exception terstruktur yang dapat ditangkap oleh blok `try-catch`.
2. `PDO::ATTR_EMULATE_PREPARES => false`: Memaksa PDO menggunakan prepared statement asli pada tingkat server database, memastikan pemisahan mutlak antara kueri SQL dengan data masukan pengguna.

---

## 3. Implementasi Keamanan Autentikasi (`login.php` & `register.php`)

Keamanan kredensial pengguna diimplementasikan menggunakan fungsi kriptografi standar PHP `password_hash()` dengan algoritma BCRYPT saat pendaftaran, serta `password_verify()` saat proses otentikasi masuk.

Cuplikan logika otentikasi:

```php
// Verifikasi login pengguna
$stmt = $pdo->prepare("SELECT id_user, username, password, nama_lengkap FROM users WHERE username = ?");
$stmt->execute([$input_username]);
$user = $stmt->fetch();

if ($user && password_verify($input_password, $user['password'])) {
    // Regenerasi ID sesi untuk mencegah Session Fixation
    session_regenerate_id(true);
    $_SESSION['user_id']      = $user['id_user'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    header("Location: dashboard.php");
    exit;
} else {
    $error = "Username atau kata sandi tidak valid!";
}
```

---

## 4. Implementasi Transaksi Kas & Sanitasi Data (`dashboard.php`)

Setiap data masukan dari formulir pencatatan kas divalidasi secara ketat pada sisi server (*server-side validation*) dan disanitasi sebelum disimpan ke basis data:

```php
// Validasi dan penyimpanan transaksi kas
$judul       = trim($_POST['judul'] ?? '');
$id_kategori = (int)($_POST['id_kategori'] ?? 0);
$tanggal     = $_POST['tanggal'] ?? date('Y-m-d');
$jenis       = in_array($_POST['jenis'], ['Pemasukan', 'Pengeluaran']) ? $_POST['jenis'] : 'Pemasukan';
$jumlah      = (float)($_POST['jumlah'] ?? 0);
$catatan     = trim($_POST['catatan'] ?? '');

if (!empty($judul) && $id_kategori > 0 && $jumlah > 0) {
    $sql = "INSERT INTO transaksi (id_user, id_kategori, tanggal, judul, jenis, jumlah, catatan) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $id_kategori, $tanggal, $judul, $jenis, $jumlah, $catatan]);
}
```

Untuk perlindungan terhadap serangan XSS pada berkas tampilan, seluruh data dinamis dienkapsulasi dengan fungsi sanitasi:

```php
echo htmlspecialchars($transaksi['judul'], ENT_QUOTES, 'UTF-8');
```

---

## 5. Implementasi Ekspor Laporan Excel (`export_excel.php`)

Fitur ekspor laporan keuangan dibuat secara efisien menggunakan pengiriman HTTP header MIME Type Spreadsheet tanpa ketergantungan pada library pihak ketiga yang berat:

```php
// m6/export_excel.php
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Laporan_Kas_Mahasiswa_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Merender tabel HTML rapi yang otomatis dibaca sebagai spreadsheet oleh Microsoft Excel
```
