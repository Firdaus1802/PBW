<?php
// transaksi_hapus.php - Hapus Transaksi Kas
require_once __DIR__ . '/config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
        $stmt->execute([$id]);
        header("Location: " . base_url("index.php?msg=deleted"));
        exit;
    } catch (PDOException $e) {
        die("Gagal menghapus transaksi: " . $e->getMessage());
    }
} else {
    header("Location: " . base_url("index.php"));
    exit;
}
