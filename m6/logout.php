<?php
require_once __DIR__ . '/config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
session_unset();
session_destroy();

if (isset($_COOKIE['dompetku_remember'])) {
    dompetku_set_secure_cookie('dompetku_remember', '', time() - 3600);
}
if (isset($_COOKIE['dompetku_session'])) {
    dompetku_set_secure_cookie('dompetku_session', '', time() - 3600);
}
if (isset($_COOKIE['dompetku_trx_cache'])) {
    dompetku_set_secure_cookie('dompetku_trx_cache', '', time() - 3600);
}

header("Location: " . base_url("login.php?msg=logged_out"));
exit;
