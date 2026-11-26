<?php
if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

$is_https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 60 * 60 * 24 * 30);
    ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path'     => '/',
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token() {
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

if (!defined('DOMPETKU_SECRET')) {
    define('DOMPETKU_SECRET', 'dompetku_pbw_secret_key_gunadarma_2026');
}

function dompetku_sign_payload($data) {
    $json = json_encode($data);
    $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $b64, DOMPETKU_SECRET);
    return $b64 . '.' . $sig;
}

function dompetku_verify_payload($token) {
    if (empty($token) || strpos($token, '.') === false) return null;
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return null;
    $b64 = $parts[0];
    $sig = $parts[1];
    $expected_sig = hash_hmac('sha256', $b64, DOMPETKU_SECRET);
    if (!hash_equals($expected_sig, $sig)) return null;
    $json = base64_decode(strtr($b64, '-_', '+/'));
    $data = json_decode($json, true);
    if (!is_array($data)) return null;
    if (isset($data['exp']) && time() > $data['exp']) return null;
    return $data;
}

function dompetku_set_secure_cookie($name, $value, $expire = 0) {
    global $is_https;
    if ($expire === 0) {
        $expire = time() + (86400 * 30);
    }
    setcookie($name, $value, [
        'expires'  => $expire,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'db_kas_mahasiswa';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$pdo = null;
try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 2,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    $pdo->exec("SET time_zone = '+07:00'");
} catch (Throwable $e) {
    try {
        $sqlite_file = sys_get_temp_dir() . '/db_kas_mahasiswa.sqlite';
        $is_new = !file_exists($sqlite_file);
        $pdo = new PDO("sqlite:" . $sqlite_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($is_new) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id_user INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT COLLATE NOCASE UNIQUE,
                    password TEXT,
                    nama_lengkap TEXT
                );
                CREATE TABLE IF NOT EXISTS kategori (
                    id_kategori INTEGER PRIMARY KEY AUTOINCREMENT,
                    nama_kategori TEXT,
                    jenis TEXT,
                    icon TEXT
                );
                CREATE TABLE IF NOT EXISTS transaksi (
                    id_transaksi INTEGER PRIMARY KEY AUTOINCREMENT,
                    id_user INTEGER DEFAULT 1,
                    id_kategori INTEGER,
                    tanggal TEXT,
                    judul TEXT,
                    jenis TEXT,
                    jumlah REAL,
                    catatan TEXT
                );
                CREATE TABLE IF NOT EXISTS riwayat_login (
                    id_riwayat INTEGER PRIMARY KEY AUTOINCREMENT,
                    id_user INTEGER,
                    waktu_login TEXT,
                    ip_address TEXT,
                    user_agent TEXT,
                    status TEXT
                );
                INSERT OR IGNORE INTO users (id_user, username, password, nama_lengkap) 
                VALUES (1, 'admin', '$2y$10$1Zeb6yV0L3w2l3A55sPBgeAVcS8gxoYiTEYQS5Yy7RvEnVF4cYAgC', 'Firdaus Dhuha Prabowo');
                
                INSERT INTO kategori (nama_kategori, jenis, icon) VALUES 
                ('Kiriman Ortu', 'Pemasukan', 'bi-wallet2'),
                ('Gaji Freelance', 'Pemasukan', 'bi-laptop'),
                ('Beasiswa', 'Pemasukan', 'bi-award'),
                ('Makan & Minum', 'Pengeluaran', 'bi-cup-hot'),
                ('Buku & Kuliah', 'Pengeluaran', 'bi-book'),
                ('Belanja Bulanan', 'Pengeluaran', 'bi-cart'),
                ('Transport & Bensin', 'Pengeluaran', 'bi-fuel-pump'),
                ('Kos & Hunian', 'Pengeluaran', 'bi-house-door');
            ");
        }
    } catch (Throwable $sqle) {
        error_log("DB Connection Error: " . $e->getMessage());
        die("Koneksi basis data gagal. Silakan periksa layanan MySQL/MariaDB.");
    }
}

if ($pdo) {
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $col_check = $pdo->query("SHOW COLUMNS FROM transaksi LIKE 'id_user'")->fetch();
            if (!$col_check) {
                $pdo->exec("ALTER TABLE transaksi ADD COLUMN id_user INT NOT NULL DEFAULT 1 AFTER id_transaksi, ADD INDEX idx_transaksi_user (id_user)");
            }
        } elseif ($driver === 'sqlite') {
            $cols = $pdo->query("PRAGMA table_info(transaksi)")->fetchAll();
            $has_uid = false;
            foreach ($cols as $c) {
                if ($c['name'] === 'id_user') {
                    $has_uid = true;
                    break;
                }
            }
            if (!$has_uid) {
                $pdo->exec("ALTER TABLE transaksi ADD COLUMN id_user INTEGER DEFAULT 1");
            }
        }
    } catch (Throwable $ignore_mig) {}
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['dompetku_session'])) {
    $sess_data = dompetku_verify_payload($_COOKIE['dompetku_session']);
    if ($sess_data && !empty($sess_data['id_user'])) {
        $_SESSION['user_id']      = (int)$sess_data['id_user'];
        $_SESSION['username']     = $sess_data['username'];
        $_SESSION['nama_lengkap'] = $sess_data['nama_lengkap'];

        if ($pdo) {
            try {
                $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                if ($driver === 'sqlite') {
                    $u_chk = $pdo->prepare("SELECT id_user FROM users WHERE id_user = ?");
                    $u_chk->execute([$_SESSION['user_id']]);
                    if (!$u_chk->fetch()) {
                        $p_hash = $sess_data['password_hash'] ?? '$2y$10$1Zeb6yV0L3w2l3A55sPBgeAVcS8gxoYiTEYQS5Yy7RvEnVF4cYAgC';
                        $u_res = $pdo->prepare("INSERT OR REPLACE INTO users (id_user, username, password, nama_lengkap) VALUES (?, ?, ?, ?)");
                        $u_res->execute([$_SESSION['user_id'], $_SESSION['username'], $p_hash, $_SESSION['nama_lengkap']]);
                    }
                }
            } catch (Throwable $e) {}
        }
    }
}

function dompetku_sync_vault($pdo, $user_id) {
    if (!$pdo || empty($user_id)) return;
    try {
        $u_stmt = $pdo->prepare("SELECT id_user, username, password, nama_lengkap FROM users WHERE id_user = ?");
        $u_stmt->execute([(int)$user_id]);
        $u_data = $u_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u_data) return;

        $t_stmt = $pdo->prepare("SELECT id_transaksi, id_user, id_kategori, tanggal, judul, jenis, jumlah, catatan FROM transaksi WHERE id_user = ? ORDER BY id_transaksi DESC LIMIT 50");
        $t_stmt->execute([(int)$user_id]);
        $rows = $t_stmt->fetchAll(PDO::FETCH_ASSOC);

        $vault_payload = [
            'id_user'       => (int)$u_data['id_user'],
            'username'      => $u_data['username'],
            'nama_lengkap'  => $u_data['nama_lengkap'],
            'password_hash' => $u_data['password'],
            'items'         => $rows,
            'exp'           => time() + (86400 * 365)
        ];
        dompetku_set_secure_cookie('dompetku_vault', dompetku_sign_payload($vault_payload), time() + (86400 * 365));
    } catch (Throwable $e) {}
}

function dompetku_sync_trx_cookie($pdo, $user_id) {
    if (!$pdo || empty($user_id)) return;
    try {
        $stmt = $pdo->prepare("SELECT id_transaksi, id_user, id_kategori, tanggal, judul, jenis, jumlah, catatan FROM transaksi WHERE id_user = ? ORDER BY id_transaksi DESC LIMIT 50");
        $stmt->execute([(int)$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        dompetku_set_secure_cookie('dompetku_trx_cache', dompetku_sign_payload([
            'user_id' => (int)$user_id,
            'items'   => $rows,
            'exp'     => time() + (86400 * 30)
        ]));
        dompetku_sync_vault($pdo, $user_id);
    } catch (Throwable $e) {}
}

if (!empty($_SESSION['user_id']) && isset($_COOKIE['dompetku_trx_cache']) && $pdo) {
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $t_chk = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE id_user = ?");
            $t_chk->execute([(int)$_SESSION['user_id']]);
            $has_t = (int)$t_chk->fetchColumn();
            if ($has_t === 0) {
                $cached_trx = dompetku_verify_payload($_COOKIE['dompetku_trx_cache']);
                if ($cached_trx && !empty($cached_trx['items']) && $cached_trx['user_id'] == $_SESSION['user_id']) {
                    $ins_t = $pdo->prepare("INSERT OR IGNORE INTO transaksi (id_transaksi, id_user, id_kategori, tanggal, judul, jenis, jumlah, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($cached_trx['items'] as $item) {
                        $ins_t->execute([
                            $item['id_transaksi'],
                            (int)$_SESSION['user_id'],
                            $item['id_kategori'],
                            $item['tanggal'],
                            $item['judul'],
                            $item['jenis'],
                            $item['jumlah'],
                            $item['catatan'] ?? ''
                        ]);
                    }
                }
            }
        }
    } catch (Throwable $e) {}
}

function base_url($path = '') {
    global $is_https;
    $protocol = $is_https ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($script_name, '/m6/') !== false) {
        $pos = strpos($script_name, '/m6/');
        $sub = substr($script_name, 0, $pos + 3);
        $base = $protocol . $host . $sub;
    } else {
        $base = $protocol . $host . '/m6';
    }

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function tgl_indo($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $timestamp = strtotime($tanggal);
    $hari = date('d', $timestamp);
    $bln = (int)date('m', $timestamp);
    $thn = date('Y', $timestamp);
    return "{$hari} " . ($bulan[$bln] ?? '') . " {$thn}";
}
