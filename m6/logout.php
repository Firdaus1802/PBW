<?php
// logout.php - Keluar dari Sesi DompetKu
require_once __DIR__ . '/config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
session_unset();
session_destroy();

// Hapus Cookie Remember Me 30 Hari
if (isset($_COOKIE['dompetku_remember'])) {
    setcookie('dompetku_remember', '', time() - 3600, '/');
}

header("Location: " . base_url("login.php?msg=logged_out"));
exit;
