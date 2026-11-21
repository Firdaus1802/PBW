<?php
require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url("login.php"));
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi = ? AND id_user = ?");
        $stmt->execute([$id, $user_id]);
        dompetku_sync_trx_cookie($pdo, $user_id);
        header("Location: " . base_url("index.php?msg=deleted"));
        exit;
    } catch (PDOException $e) {
        die("Gagal menghapus transaksi: " . $e->getMessage());
    }
} else {
    header("Location: " . base_url("index.php"));
    exit;
}
