<?php
require_once __DIR__ . '/auth_check.php';

$current_page = basename($_SERVER['PHP_SELF']);
// Ambil jumlah pesan belum dibaca
$unread_count = $pdo->query("SELECT COUNT(*) FROM pesan_kontak WHERE status_baca = 'Belum Dibaca'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Portofolio CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background-color: #f1f5f9;
        }
        .sidebar {
            width: 250px;
            background: #0f172a;
            min-height: 100vh;
            color: #e2e8f0;
        }
        .sidebar .nav-link {
            color: #94a3b8;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 12px;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #ffffff;
            background-color: #1e293b;
            border-left: 4px solid #3b82f6;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        .content-area {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- SIDEBAR -->
    <div class="sidebar d-flex flex-column flex-shrink-0">
        <div class="p-4 border-bottom border-secondary text-center">
            <h5 class="fw-bold text-white mb-0"><i class="bi bi-code-square text-primary me-2"></i>CMS Portofolio</h5>
            <small class="text-secondary">Panel CRUD Administrator</small>
        </div>

        <ul class="nav flex-column my-3">
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'index.php') ? 'active' : '' ?>" href="index.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (strpos($current_page, 'proyek') !== false) ? 'active' : '' ?>" href="proyek.php">
                    <i class="bi bi-folder2-open"></i> Kelola Proyek
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'kategori.php') ? 'active' : '' ?>" href="kategori.php">
                    <i class="bi bi-tags"></i> Kategori Proyek
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'layanan.php') ? 'active' : '' ?>" href="layanan.php">
                    <i class="bi bi-briefcase"></i> Layanan Jasa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page === 'pesan.php') ? 'active' : '' ?>" href="pesan.php">
                    <i class="bi bi-envelope"></i> Pesan Masuk
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger ms-auto float-end"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="mt-auto p-3 border-top border-secondary">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary text-white p-2 rounded-circle me-2">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <small class="d-block text-white fw-bold"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></small>
                    <small class="text-secondary">Administrator</small>
                </div>
            </div>
            <a href="../index.php" target="_blank" class="btn btn-outline-light btn-sm w-100 mb-2">
                <i class="bi bi-globe me-1"></i> Lihat Web Publik
            </a>
            <a href="logout.php" class="btn btn-danger btn-sm w-100" onclick="return confirm('Apakah Anda yakin ingin logout?')">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="content-area">
