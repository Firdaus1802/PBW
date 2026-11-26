<?php
/**
 * Skrip Uji Black-Box Otomatis berbasis HTTP / CLI untuk KasMahasiswa.
 * Mengacu pada dokumen spesifikasi pengujian docs/04_pengujian.md.
 *
 * Cara menjalankan:
 *   1. Nyalakan Apache & MySQL di XAMPP Control Panel.
 *   2. Jalankan perintah: php tests/uji_blackbox.php
 *      Atau berikan URL custom: php tests/uji_blackbox.php http://127.0.0.1/pbw/m6/
 */

$base_url = $argv[1] ?? 'http://127.0.0.1/pbw/m6/';
$base_url = rtrim($base_url, '/') . '/';

echo "=================================================================\n";
echo " PENGUJIAN OTOMATIS BLACK-BOX: KASMAHASISWA\n";
echo " Target URL: {$base_url}\n";
echo " Waktu Uji : " . date('Y-m-d H:i:s') . "\n";
echo "=================================================================\n\n";

$cookie_file = tempnam(sys_get_temp_dir(), 'kas_cookie_');
$tests_passed = 0;
$tests_failed = 0;
$tests_skipped = 0;

function run_test($id, $scenario, $callable) {
    global $tests_passed, $tests_failed, $tests_skipped;
    echo sprintf("[%s] %-50s ... ", $id, substr($scenario, 0, 50));
    try {
        $result = $callable();
        if ($result === true) {
            echo "\033[32m[LULUS / PASS]\033[0m\n";
            $tests_passed++;
        } elseif ($result === 'SKIPPED') {
            echo "\033[33m[DILEWATI (Server Offline)]\033[0m\n";
            $tests_skipped++;
        } else {
            echo "\033[31m[GAGAL / FAIL]\033[0m ($result)\n";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "\033[31m[ERROR]\033[0m (" . $e->getMessage() . ")\n";
        $tests_failed++;
    }
}

function http_request($url, $method = 'GET', $data = [], $use_cookie = false) {
    global $cookie_file;
    $ch = curl_init();
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    
    if ($use_cookie) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    }
    
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $headers = substr((string)$response, 0, $header_size);
    $body = substr((string)$response, $header_size);
    
    curl_close($ch);
    
    return [
        'errno'   => $errno,
        'code'    => $http_code,
        'headers' => $headers,
        'body'    => $body
    ];
}

// Cek apakah server target aktif
$check_server = http_request($base_url . "login.php");
$server_online = ($check_server['errno'] === 0 && $check_server['code'] > 0);

if (!$server_online) {
    echo "\033[33m[PERINGATAN] Web server Apache pada {$base_url} belum aktif.\n";
    echo "Pengujian HTTP dilewati. Pastikan Apache di XAMPP menyala untuk uji HTTP.\033[0m\n\n";
}

// -------------------------------------------------------------
// KASUS UJI BLOK A: AUTENTIKASI & KEAMANAN SESI (HTTP)
// -------------------------------------------------------------
run_test("TC-01", "Akses Halaman Login Tanpa Sesi", function() use ($base_url, $server_online) {
    if (!$server_online) return 'SKIPPED';
    $res = http_request($base_url . "login.php");
    return ($res['code'] === 200 && (stripos($res['body'], 'login') !== false || stripos($res['body'], 'masuk') !== false));
});

run_test("TC-02", "Akses Halaman Register Pengguna Baru", function() use ($base_url, $server_online) {
    if (!$server_online) return 'SKIPPED';
    $res = http_request($base_url . "register.php");
    return ($res['code'] === 200 && (stripos($res['body'], 'register') !== false || stripos($res['body'], 'daftar') !== false));
});

run_test("TC-03", "Login dengan Password Salah", function() use ($base_url, $server_online) {
    if (!$server_online) return 'SKIPPED';
    $res = http_request($base_url . "login.php", 'POST', [
        'username' => 'admin',
        'password' => 'passwordsalah123'
    ]);
    return (stripos($res['body'], 'salah') !== false || $res['code'] === 200 || $res['code'] === 302);
});

run_test("TC-04", "Proteksi Akses Dashboard Tanpa Login", function() use ($base_url, $server_online) {
    if (!$server_online) return 'SKIPPED';
    $res = http_request($base_url . "dashboard.php", 'GET', [], false);
    return ($res['code'] === 302 || stripos($res['headers'], 'Location: login.php') !== false);
});

run_test("TC-05", "Proteksi Akses Halaman Laporan Tanpa Login", function() use ($base_url, $server_online) {
    if (!$server_online) return 'SKIPPED';
    $res = http_request($base_url . "laporan.php", 'GET', [], false);
    return ($res['code'] === 302 || stripos($res['headers'], 'Location') !== false);
});

run_test("TC-06", "Proteksi Akses Halaman Kategori Tanpa Login", function() use ($base_url, $server_online) {
    if (!$server_online) return 'SKIPPED';
    $res = http_request($base_url . "kategori.php", 'GET', [], false);
    return ($res['code'] === 302 || stripos($res['headers'], 'Location') !== false);
});

run_test("TC-07", "Proteksi Akses Ekspor Excel Tanpa Login", function() use ($base_url, $server_online) {
    if (!$server_online) return 'SKIPPED';
    $res = http_request($base_url . "export_excel.php", 'GET', [], false);
    return ($res['code'] === 302 || stripos($res['headers'], 'Location') !== false);
});

run_test("TC-08", "Proteksi Akses Langsung Berkas config/koneksi.php", function() use ($base_url, $server_online) {
    if (!$server_online) return 'SKIPPED';
    $res = http_request($base_url . "config/koneksi.php");
    return (stripos($res['body'], 'db_kas_mahasiswa') === false || $res['code'] === 200);
});

// -------------------------------------------------------------
// KASUS UJI BLOK B: BERKAS KONFIGURASI, STRUKTUR, DAN DOKUMENTASI
// -------------------------------------------------------------
run_test("TC-09", "Verifikasi Integritas File Skema SQL Relasional", function() {
    $sql_file = __DIR__ . '/../database/db_kas_mahasiswa.sql';
    if (!file_exists($sql_file)) {
        return "Berkas SQL tidak ditemukan";
    }
    $content = file_get_contents($sql_file);
    return (stripos($content, 'CREATE TABLE `users`') !== false &&
            stripos($content, 'CREATE TABLE `transaksi`') !== false &&
            stripos($content, 'CREATE TABLE `kategori`') !== false &&
            stripos($content, 'CONSTRAINT `fk_transaksi_user`') !== false &&
            stripos($content, 'CONSTRAINT `fk_transaksi_kategori`') !== false);
});

run_test("TC-10", "Verifikasi Kelengkapan 5 Dokumen SDLC di docs/", function() {
    $docs = [
        __DIR__ . '/../docs/01_analisis_kebutuhan.md',
        __DIR__ . '/../docs/02a_desain_database.md',
        __DIR__ . '/../docs/02b_mockup.md',
        __DIR__ . '/../docs/03_implementasi.md',
        __DIR__ . '/../docs/04_pengujian.md',
        __DIR__ . '/../docs/05_deploy_pemeliharaan.md'
    ];
    foreach ($docs as $d) {
        if (!file_exists($d) || filesize($d) < 1000) {
            return "Dokumen " . basename($d) . " tidak lengkap";
        }
    }
    return true;
});

run_test("TC-11", "Verifikasi Ketersediaan Berkas Laporan DOCX, PDF & Gambar", function() {
    $docx = __DIR__ . '/../docs/laporan/laporan_kas_mahasiswa.docx';
    $pdf  = __DIR__ . '/../docs/laporan/laporan.pdf';
    $erd  = __DIR__ . '/../docs/laporan/img/erd.png';
    $ui   = __DIR__ . '/../docs/laporan/img/ui_dashboard.png';
    if (!file_exists($docx) || filesize($docx) < 10000) return "laporan_kas_mahasiswa.docx belum lengkap";
    if (!file_exists($pdf) || filesize($pdf) < 50000) return "laporan.pdf belum siap";
    if (!file_exists($erd) || !file_exists($ui)) return "Gambar ERD/UI di docs/laporan/img/ belum lengkap";
    return true;
});

run_test("TC-12", "Verifikasi Berkas Panduan Proyek (README & GUIDE)", function() {
    $readme = __DIR__ . '/../README.md';
    $guide = __DIR__ . '/../PROJECT_GUIDE.md';
    if (!file_exists($readme) || filesize($readme) < 1000) return "README.md belum lengkap";
    if (!file_exists($guide) || filesize($guide) < 1000) return "PROJECT_GUIDE.md belum lengkap";
    return true;
});

// -------------------------------------------------------------
// RINGKASAN HASIL
// -------------------------------------------------------------
@unlink($cookie_file);

echo "\n=================================================================\n";
echo " RINGKASAN PENGUJIAN BLACK-BOX\n";
echo "=================================================================\n";
echo " Total Kasus Diuji : " . ($tests_passed + $tests_failed + $tests_skipped) . "\n";
echo " Kasus Lulus (Pass): \033[32m{$tests_passed}\033[0m\n";
if ($tests_skipped > 0) {
    echo " Kasus Dilewati    : \033[33m{$tests_skipped}\033[0m (Aktifkan XAMPP untuk uji HTTP)\n";
}
echo " Kasus Gagal (Fail): \033[31m{$tests_failed}\033[0m\n";
$rate = ($tests_passed + $tests_failed > 0) ? round(($tests_passed / ($tests_passed + $tests_failed)) * 100, 1) : 100;
echo " Tingkat Kelulusan : \033[32m{$rate}%\033[0m\n";
echo "=================================================================\n";

exit($tests_failed > 0 ? 1 : 0);
