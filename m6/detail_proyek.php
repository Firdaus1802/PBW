<?php
require_once __DIR__ . '/config/koneksi.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, k.keterangan as ket_kategori 
                       FROM proyek p 
                       JOIN kategori k ON p.id_kategori = k.id_kategori 
                       WHERE p.id_proyek = ?");
$stmt->execute([$id]);
$proyek = $stmt->fetch();

if (!$proyek) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($proyek['judul']) ?> — Detail Portofolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-arrow-left me-2"></i>Kembali ke Portofolio
            </a>
            <a href="admin/login.php" class="btn btn-outline-light btn-sm">Panel Admin</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                    <img src="assets/img/<?= htmlspecialchars($proyek['gambar']) ?>" class="img-fluid w-100" style="max-height: 420px; object-fit: cover;" alt="<?= htmlspecialchars($proyek['judul']) ?>" onerror="this.src='assets/img/proyek_ml_bank.png'">
                    
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary px-3 py-2 rounded-pill fs-6"><?= htmlspecialchars($proyek['nama_kategori']) ?></span>
                            <span class="text-muted"><i class="bi bi-calendar-check me-1"></i>Selesai: <?= tgl_indo($proyek['tanggal_selesai']) ?></span>
                        </div>

                        <h2 class="fw-bold mb-3"><?= htmlspecialchars($proyek['judul']) ?></h2>
                        <p class="lead text-secondary mb-4"><?= htmlspecialchars($proyek['ringkasan']) ?></p>

                        <h5 class="fw-bold border-bottom pb-2">Deskripsi Arsitektur & Pengerjaan</h5>
                        <p class="text-secondary" style="line-height: 1.8;">
                            <?= nl2br(htmlspecialchars($proyek['deskripsi'])) ?>
                        </p>

                        <div class="my-4 p-3 bg-light rounded border">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-tools me-1 text-primary"></i>Teknologi yang Digunakan:</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php 
                                $techs = explode(',', $proyek['teknologi']);
                                foreach ($techs as $t): 
                                ?>
                                    <span class="badge bg-secondary px-3 py-2"><?= trim(htmlspecialchars($t)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-3">
                            <?php if (!empty($proyek['link_demo']) && $proyek['link_demo'] !== '#'): ?>
                                <a href="<?= htmlspecialchars($proyek['link_demo']) ?>" target="_blank" class="btn btn-success px-4">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Live Demo
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($proyek['link_github']) && $proyek['link_github'] !== '#'): ?>
                                <a href="<?= htmlspecialchars($proyek['link_github']) ?>" target="_blank" class="btn btn-dark px-4">
                                    <i class="bi bi-github me-1"></i> Repositori GitHub
                                </a>
                            <?php endif; ?>
                            <a href="index.php#proyek" class="btn btn-outline-secondary px-4">Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
