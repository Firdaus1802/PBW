<?php
require_once __DIR__ . '/auth_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        // Ambil nama gambar untuk dihapus dari folder jika bukan gambar default
        $stmt = $pdo->prepare("SELECT gambar FROM proyek WHERE id_proyek = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row) {
            $delete_stmt = $pdo->prepare("DELETE FROM proyek WHERE id_proyek = ?");
            $delete_stmt->execute([$id]);

            // Hapus file fisik jika berupa custom upload
            $file_path = __DIR__ . '/../assets/img/' . $row['gambar'];
            if (!empty($row['gambar']) && strpos($row['gambar'], 'proyek_') === false && file_exists($file_path)) {
                @unlink($file_path);
            }
        }
    } catch (PDOException $e) {
        die("Gagal menghapus proyek: " . $e->getMessage());
    }
}

header('Location: proyek.php');
exit;
