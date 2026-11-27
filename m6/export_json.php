<?php
require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url("login.php"));
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    $stmt_trx = $pdo->prepare("
        SELECT t.id_transaksi, t.tanggal, t.judul, t.jenis, t.jumlah, t.catatan,
               k.nama_kategori, k.icon
        FROM transaksi t
        LEFT JOIN kategori k ON t.id_kategori = k.id_kategori
        WHERE t.id_user = ?
        ORDER BY t.tanggal DESC, t.id_transaksi DESC
    ");
    $stmt_trx->execute([$user_id]);
    $transaksi = $stmt_trx->fetchAll();

    $budget = null;
    try {
        $st_b = $pdo->prepare("SELECT limit_bulanan FROM budget_settings WHERE id_user = ?");
        $st_b->execute([$user_id]);
        $budget = $st_b->fetch() ?: null;
    } catch (Throwable $ignore) {}

    $goal = null;
    try {
        $st_g = $pdo->prepare("SELECT nama_target, target_nominal FROM savings_goals WHERE id_user = ?");
        $st_g->execute([$user_id]);
        $goal = $st_g->fetch() ?: null;
    } catch (Throwable $ignore) {}

    $payload = [
        'exported_at' => date('c'),
        'app'         => 'KasMahasiswa',
        'user'        => [
            'id_user'      => $user_id,
            'username'     => $_SESSION['username'] ?? '',
            'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        ],
        'budget_limit' => $budget,
        'savings_goal' => $goal,
        'total_items'  => count($transaksi),
        'transaksi'    => $transaksi,
    ];

    $filename = 'Backup_KasMahasiswa_' . date('Y-m-d') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    error_log("Gagal ekspor JSON: " . $e->getMessage());
    header("Location: " . base_url("dashboard.php?msg=error"));
    exit;
}
