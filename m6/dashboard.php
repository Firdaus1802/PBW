<?php
// dashboard.php - Dedicated Desktop Dashboard (Apple iOS / macOS Pro Design System)
// Mendukung Mode Gelap (Dark Titanium) & Mode Terang (Cupertino Light), Fullset PBW Gunadarma
require_once __DIR__ . '/config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url("login.php"));
    exit;
}

// Auto-deteksi perangkat: Jika diakses lewat browser HP (smartphone), otomatis lempar ke index.php
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_mobile_device = preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|iemobile|opera mini)/i', $ua);
if ($is_mobile_device && !isset($_GET['desktop'])) {
    header("Location: " . base_url("index.php"));
    exit;
}

// Notifikasi pesan
$alert_msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $alert_msg = '<div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Transaksi kas berhasil dicatat ke dalam database!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } elseif ($_GET['msg'] === 'updated') {
        $alert_msg = '<div class="alert alert-info alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> Data transaksi berhasil diperbarui!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } elseif ($_GET['msg'] === 'deleted') {
        $alert_msg = '<div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-trash-fill me-2"></i> Transaksi telah dihapus dari sistem.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } elseif ($_GET['msg'] === 'kat_added') {
        $alert_msg = '<div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Kategori baru berhasil ditambahkan!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } elseif ($_GET['msg'] === 'kat_deleted') {
        $alert_msg = '<div class="alert alert-secondary alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-trash-fill me-2"></i> Kategori berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } elseif ($_GET['msg'] === 'kat_error') {
        $alert_msg = '<div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-shield-exclamation me-2"></i> Kategori tidak dapat dihapus karena masih digunakan pada data transaksi.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// 1. Tambah Transaksi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_transaksi'])) {
    $id_kategori = (int)$_POST['id_kategori'];
    $tanggal     = sanitize($_POST['tanggal']);
    $judul       = sanitize($_POST['judul']);
    $jenis       = sanitize($_POST['jenis']);
    $jumlah      = (float)$_POST['jumlah'];
    $catatan     = sanitize($_POST['catatan']);

    if (!empty($judul) && $id_kategori > 0 && $jumlah > 0 && !empty($tanggal)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO transaksi (id_kategori, tanggal, judul, jenis, jumlah, catatan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_kategori, $tanggal, $judul, $jenis, $jumlah, $catatan]);
            header("Location: " . base_url("dashboard.php?msg=added#transaksiSection"));
            exit;
        } catch (PDOException $e) {
            $alert_msg = '<div class="alert alert-danger rounded-4">Gagal mencatat transaksi: ' . $e->getMessage() . '</div>';
        }
    }
}

// 2. Sunting Transaksi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_transaksi'])) {
    $id_trx      = (int)$_POST['id_transaksi'];
    $id_kategori = (int)$_POST['id_kategori'];
    $tanggal     = sanitize($_POST['tanggal']);
    $judul       = sanitize($_POST['judul']);
    $jenis       = sanitize($_POST['jenis']);
    $jumlah      = (float)$_POST['jumlah'];
    $catatan     = sanitize($_POST['catatan']);

    if ($id_trx > 0 && !empty($judul) && $id_kategori > 0 && $jumlah > 0 && !empty($tanggal)) {
        try {
            $stmt_up = $pdo->prepare("UPDATE transaksi SET id_kategori = ?, tanggal = ?, judul = ?, jenis = ?, jumlah = ?, catatan = ? WHERE id_transaksi = ?");
            $stmt_up->execute([$id_kategori, $tanggal, $judul, $jenis, $jumlah, $catatan, $id_trx]);
            header("Location: " . base_url("dashboard.php?msg=updated#transaksiSection"));
            exit;
        } catch (PDOException $e) {
            $alert_msg = '<div class="alert alert-danger rounded-4">Gagal memperbarui transaksi: ' . $e->getMessage() . '</div>';
        }
    }
}

// 3. Hapus Transaksi (GET)
if (isset($_GET['hapus_trx'])) {
    $id_trx_del = (int)$_GET['hapus_trx'];
    if ($id_trx_del > 0) {
        $stmt_del = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
        $stmt_del->execute([$id_trx_del]);
        header("Location: " . base_url("dashboard.php?msg=deleted#transaksiSection"));
        exit;
    }
}

// 4. Tambah Kategori (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = sanitize($_POST['nama_kategori']);
    $jenis         = sanitize($_POST['jenis']);
    $icon          = sanitize($_POST['icon']);

    if (!empty($nama_kategori) && !empty($jenis)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, jenis, icon, warna) VALUES (?, ?, ?, '#9CA3AF')");
            $stmt->execute([$nama_kategori, $jenis, $icon]);
            header("Location: " . base_url("dashboard.php?msg=kat_added#kategoriSection"));
            exit;
        } catch (PDOException $e) {
            $alert_msg = '<div class="alert alert-danger rounded-4">Gagal menambahkan kategori: ' . $e->getMessage() . '</div>';
        }
    }
}

// 8. Hapus Kategori (GET)
if (isset($_GET['hapus_kat'])) {
    $id_kat = (int)$_GET['hapus_kat'];
    if ($id_kat > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM kategori WHERE id_kategori = ?");
            $stmt->execute([$id_kat]);
            header("Location: " . base_url("dashboard.php?msg=kat_deleted#kategoriSection"));
            exit;
        } catch (PDOException $e) {
            header("Location: " . base_url("dashboard.php?msg=kat_error#kategoriSection"));
            exit;
        }
    }
}

// Statistik Kas
$stmt_stat = $pdo->query("SELECT 
    COALESCE(SUM(CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE 0 END), 0) AS total_masuk,
    COALESCE(SUM(CASE WHEN jenis = 'Pengeluaran' THEN jumlah ELSE 0 END), 0) AS total_keluar
FROM transaksi");
$stat = $stmt_stat->fetch();
$total_masuk  = (float)$stat['total_masuk'];
$total_keluar = (float)$stat['total_keluar'];
$saldo_akhir  = $total_masuk - $total_keluar;

// Rasio Akumulasi Kas
$rasio_simpan = ($total_masuk > 0) ? max(0, min(100, round(($saldo_akhir / $total_masuk) * 100))) : 0;

// Data Transaksi
$sql_trx = "SELECT t.*, k.nama_kategori, k.icon 
            FROM transaksi t 
            JOIN kategori k ON t.id_kategori = k.id_kategori 
            ORDER BY t.tanggal DESC, t.id_transaksi DESC";
$daftar_transaksi = $pdo->query($sql_trx)->fetchAll();
$transaksi_list = $daftar_transaksi; // Alias for compatibility

// Data Master Kategori
$kategori_list = $pdo->query("SELECT k.*, COUNT(t.id_transaksi) AS total_trx 
                              FROM kategori k 
                              LEFT JOIN transaksi t ON k.id_kategori = t.id_kategori 
                              GROUP BY k.id_kategori 
                              ORDER BY k.jenis ASC, k.nama_kategori ASC")->fetchAll();

// Data Donut Chart & Rekapitulasi Beban per Kategori
$kat_expenses = $pdo->query("SELECT 
                                k.id_kategori,
                                k.nama_kategori, 
                                k.icon,
                                COALESCE(SUM(t.jumlah), 0) AS total,
                                COUNT(t.id_transaksi) AS total_trx
                             FROM kategori k 
                             LEFT JOIN transaksi t ON k.id_kategori = t.id_kategori AND t.jenis = 'Pengeluaran'
                             WHERE k.jenis = 'Pengeluaran' 
                             GROUP BY k.id_kategori, k.nama_kategori, k.icon 
                             ORDER BY total DESC")->fetchAll();
$donut_labels = [];
$donut_values = [];
foreach ($kat_expenses as $ke) {
    if ((float)$ke['total'] > 0) {
        $donut_labels[] = $ke['nama_kategori'];
        $donut_values[] = (float)$ke['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DompetKu Pro Dashboard — Universitas Gunadarma</title>
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Authentic iOS Theme CSS -->
    <link href="<?= base_url('assets/css/ios-theme.css?v=' . time()) ?>" rel="stylesheet">
    <!-- Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Theme Script Persistence -->
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>

    <script>
        // Auto-redirect jika resolusi layar adalah smartphone (< 992px)
        if (window.innerWidth < 992 && !window.location.search.includes('desktop=1')) {
            window.location.replace("<?= base_url('index.php') ?>");
        }
    </script>

    <style>
        /* ==================== APPLE PRO / LUXURY SLATE DESIGN TOKENS ==================== */
        /* Elegant Monochrome & Slate with Subtle Muted Tones (No Jamet, No Generic Blue-White) */
        :root, [data-theme="dark"], [data-bs-theme="dark"] {
            --app-bg: #090A0F;
            --app-sidebar-bg: #0E1017;
            --app-topbar-bg: rgba(14, 16, 23, 0.85);
            --app-card-bg: #141620;
            --app-card-inner: #1C1E2A;
            --app-border: rgba(255, 255, 255, 0.08);
            --app-border-hover: rgba(255, 255, 255, 0.16);
            --app-text-primary: #F8FAFC;
            --app-text-secondary: #94A3B8;
            --app-text-muted: #64748B;
            --app-input-bg: #1A1D27;
            --app-input-border: rgba(255, 255, 255, 0.12);
            --app-table-th: #181A24;
            --app-table-td: #141620;
            --app-table-border: rgba(255, 255, 255, 0.06);
            --app-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            
            /* Executive Hero Card Tokens (Dark Titanium) */
            --app-hero-bg: #141620;
            --app-hero-border: rgba(255, 255, 255, 0.10);
            --app-hero-text: #F8FAFC;
            --app-hero-subtext: #94A3B8;
            --app-hero-progress-bg: rgba(255, 255, 255, 0.10);
            --app-hero-btn-bg: #FFFFFF;
            --app-hero-btn-color: #090A0F;
            --app-hero-btn-border: transparent;
            
            /* Primary Button & Active Nav */
            --app-btn-primary-bg: #FFFFFF;
            --app-btn-primary-text: #090A0F;
            --app-nav-active-bg: rgba(255, 255, 255, 0.10);
            --app-nav-active-text: #FFFFFF;
            --app-nav-active-border: rgba(255, 255, 255, 0.08);
            --app-brand-icon-bg: #1C1E2A;
            --app-brand-icon-color: #F8FAFC;
            
            /* Subdued, Mature Financial Accents */
            --ios-green: #10B981;
            --ios-red: #F43F5E;
            --ios-orange: #F59E0B;
            --ios-slate: #64748B;
        }

        [data-theme="light"], [data-bs-theme="light"] {
            --app-bg: #F8F9FA; /* Soft warm oyster gray, high-end and gentle on eyes */
            --app-sidebar-bg: #FFFFFF;
            --app-topbar-bg: rgba(255, 255, 255, 0.90);
            --app-card-bg: #FFFFFF;
            --app-card-inner: #F1F3F5;
            --app-border: #E9ECEF;
            --app-border-hover: #DEE2E6;
            --app-text-primary: #1A1D20;
            --app-text-secondary: #495057;
            --app-text-muted: #868E96;
            --app-input-bg: #F1F3F5;
            --app-input-border: #CED4DA;
            --app-table-th: #F8F9FA;
            --app-table-td: #FFFFFF;
            --app-table-border: #E9ECEF;
            --app-shadow: 0 4px 18px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.02);
            
            /* Executive Hero Card Tokens (Clean Porcelain) */
            --app-hero-bg: #FFFFFF;
            --app-hero-border: #E9ECEF;
            --app-hero-text: #1A1D20;
            --app-hero-subtext: #495057;
            --app-hero-progress-bg: #E9ECEF;
            --app-hero-btn-bg: #1A1D20;
            --app-hero-btn-color: #FFFFFF;
            --app-hero-btn-border: transparent;
            
            /* Primary Button & Active Nav */
            --app-btn-primary-bg: #1A1D20;
            --app-btn-primary-text: #FFFFFF;
            --app-nav-active-bg: #1A1D20;
            --app-nav-active-text: #FFFFFF;
            --app-nav-active-border: transparent;
            --app-brand-icon-bg: #1A1D20;
            --app-brand-icon-color: #FFFFFF;
            
            /* Subdued, Mature Financial Accents */
            --ios-green: #059669;
            --ios-red: #E11D48;
            --ios-orange: #D97706;
            --ios-slate: #495057;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 85px;
        }

        .scroll-target,
        .ios-section-card,
        [id$="Section"] {
            scroll-margin-top: 85px;
        }

        * {
            box-sizing: border-box;
            transition: background-color 0.25s ease, border-color 0.25s ease;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Zero window-level scrollbar - pure native app feel */
            background-color: var(--app-bg);
            color: var(--app-text-primary);
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .macos-app-layout {
            display: flex;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            background-color: var(--app-bg);
        }

        /* ==================== APPLE macOS SIDEBAR (PINNED & STATIONARY) ==================== */
        .macos-sidebar {
            width: 260px;
            height: 100vh;
            background-color: var(--app-sidebar-bg);
            border-right: 1px solid var(--app-border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            overflow-y: auto;
            position: relative;
            z-index: 1020;
            user-select: none;
        }

        .macos-sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .macos-sidebar::-webkit-scrollbar-thumb {
            background: rgba(142, 142, 147, 0.2);
            border-radius: 10px;
        }

        .macos-sidebar .brand-header {
            padding: 20px;
            border-bottom: 1px solid var(--app-border);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .macos-sidebar .brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            background: var(--app-brand-icon-bg);
            color: var(--app-brand-icon-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            border: 1px solid var(--app-border);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .macos-sidebar .sidebar-heading {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            color: var(--app-text-muted);
            padding: 0 20px;
            margin-top: 18px;
            margin-bottom: 8px;
        }

        .macos-sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            margin: 3px 12px;
            border-radius: 10px;
            color: var(--app-text-secondary);
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .macos-sidebar .nav-link:hover {
            color: var(--app-text-primary);
            background-color: var(--app-card-inner);
        }

        .macos-sidebar .nav-link.active {
            color: var(--app-nav-active-text);
            background: var(--app-nav-active-bg);
            border: 1px solid var(--app-nav-active-border);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        .macos-sidebar .nav-link i {
            font-size: 16px;
        }

        .sidebar-footer-card {
            background-color: var(--app-card-inner);
            border: 1px solid var(--app-border);
            border-radius: 14px;
            padding: 12px 14px;
            margin: 16px 12px;
            flex-shrink: 0;
        }

        /* ==================== RIGHT VIEWPORT WRAPPER ==================== */
        .macos-main-viewport {
            flex: 1;
            height: 100vh;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            position: relative;
        }

        /* ==================== APPLE macOS TOPBAR (PINNED) ==================== */
        .macos-topbar {
            height: 64px;
            flex-shrink: 0;
            background-color: var(--app-topbar-bg);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border-bottom: 1px solid var(--app-border);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1010;
        }

        .topbar-search-input {
            background-color: var(--app-input-bg);
            border: 1px solid var(--app-input-border);
            border-radius: 20px;
            color: var(--app-text-primary);
            font-size: 13px;
            padding: 7px 16px 7px 38px;
            width: 260px;
            outline: none;
            transition: all 0.2s ease;
        }

        .topbar-search-input:focus {
            border-color: var(--app-text-muted);
            box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.2);
            width: 320px;
        }

        /* ==================== INDEPENDENT RIGHT SCROLL CONTAINER ==================== */
        .macos-content-area {
            flex: 1;
            height: calc(100vh - 64px);
            overflow-y: auto;
            overflow-x: hidden;
            scroll-behavior: smooth;
            padding: 28px 36px 80px;
            min-width: 0;
        }

        .macos-content-area::-webkit-scrollbar {
            width: 8px;
        }
        .macos-content-area::-webkit-scrollbar-track {
            background: transparent;
        }
        .macos-content-area::-webkit-scrollbar-thumb {
            background: rgba(142, 142, 147, 0.3);
            border-radius: 10px;
        }
        .macos-content-area::-webkit-scrollbar-thumb:hover {
            background: rgba(142, 142, 147, 0.5);
        }

        /* ==================== APPLE iOS STAT CARDS ==================== */
        .ios-kpi-card {
            background-color: var(--app-card-bg);
            border: 1px solid var(--app-border);
            border-radius: 18px;
            padding: 20px 22px;
            box-shadow: var(--app-shadow);
            transition: transform 0.2s ease, border-color 0.2s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .ios-kpi-card:hover {
            transform: translateY(-2px);
            border-color: var(--app-border-hover);
        }

        .ios-kpi-pill {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background-color: var(--app-card-inner);
            border: 1px solid var(--app-border);
            color: var(--app-text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        /* ==================== APPLE HERO CARD (ADAPTIVE TO LIGHT/DARK) ==================== */
        .ios-hero-banner {
            background: var(--app-hero-bg);
            border-radius: 20px;
            border: 1px solid var(--app-hero-border);
            padding: 24px 28px;
            box-shadow: var(--app-shadow);
            color: var(--app-hero-text);
            position: relative;
            overflow: hidden;
            transition: all 0.25s ease;
        }

        .hero-amount-text {
            color: var(--app-hero-text);
        }

        .hero-subtext {
            color: var(--app-hero-subtext);
            line-height: 1.5;
        }

        .hero-progress {
            background-color: var(--app-hero-progress-bg);
        }

        .ios-hero-pill {
            background-color: var(--app-card-inner);
            color: var(--app-text-secondary);
            border: 1px solid var(--app-border);
            font-size: 11px;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .btn-hero-action {
            background: var(--app-hero-btn-bg);
            color: var(--app-hero-btn-color) !important;
            border: 1px solid var(--app-hero-btn-border);
            border-radius: 10px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .btn-hero-action:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* ==================== CONTENT CARDS ==================== */
        .ios-section-card {
            background-color: var(--app-card-bg);
            border: 1px solid var(--app-border);
            border-radius: 18px;
            box-shadow: var(--app-shadow);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .ios-card-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--app-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ios-card-header h6 {
            margin: 0;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: -0.2px;
            color: var(--app-text-primary);
        }

        /* ==================== HIGH-CONTRAST TABLES ==================== */
        .table-ios {
            margin-bottom: 0;
            color: var(--app-text-primary);
        }

        .table-ios th {
            background-color: var(--app-table-th);
            color: var(--app-text-muted);
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.8px;
            border-bottom: 1px solid var(--app-table-border);
            border-top: none;
            padding: 13px 18px;
        }

        .table-ios td {
            background-color: var(--app-table-td);
            color: var(--app-text-primary);
            border-color: var(--app-table-border);
            padding: 13px 18px;
            vertical-align: middle;
            font-size: 13.5px;
        }

        .badge-kategori-pill {
            background-color: var(--app-card-inner);
            color: var(--app-text-primary);
            border: 1px solid var(--app-border);
            font-size: 11.5px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .table-date-text {
            color: var(--app-text-primary);
            font-weight: 600;
            font-size: 13px;
        }

        .table-title-text {
            color: var(--app-text-primary);
            font-weight: 600;
            font-size: 14px;
            letter-spacing: -0.2px;
        }

        /* ==================== BUTTONS ==================== */
        .btn-ios-primary {
            background-color: var(--app-btn-primary-bg);
            color: var(--app-btn-primary-text) !important;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            padding: 8px 18px;
            font-size: 13.5px;
            transition: all 0.2s ease;
        }

        .btn-ios-primary:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .btn-ios-secondary {
            background-color: var(--app-card-inner);
            color: var(--app-text-primary);
            border: 1px solid var(--app-border);
            border-radius: 12px;
            font-weight: 600;
            padding: 8px 16px;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .btn-ios-secondary:hover {
            color: var(--app-text-primary);
            border-color: var(--app-border-hover);
        }

        /* Modal Styles */
        .modal-ios-content {
            background-color: var(--app-card-bg);
            border: 1px solid var(--app-border);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            color: var(--app-text-primary);
        }

        .modal-ios-input {
            background-color: var(--app-input-bg);
            border: 1px solid var(--app-input-border);
            border-radius: 12px;
            color: var(--app-text-primary);
            padding: 10px 14px;
            font-size: 14px;
            width: 100%;
            outline: none;
        }

        .modal-ios-input:focus {
            border-color: var(--app-text-muted);
            box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.2);
            background-color: var(--app-input-bg);
            color: var(--app-text-primary);
        }

        /* ==================== AUTHENTIC APPLE iOS SLIDING SEGMENTED CONTROL ==================== */
        .ios-segmented-control-slider-wrap {
            position: relative;
            display: flex;
            background-color: var(--app-card-inner, #171923);
            border: 1px solid var(--app-border, rgba(255, 255, 255, 0.1));
            border-radius: 12px;
            padding: 3px;
            user-select: none;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .ios-segmented-slider {
            position: absolute;
            top: 3px;
            bottom: 3px;
            left: 3px;
            width: calc(50% - 3px);
            background-color: var(--app-card-bg, #0E1017);
            border-radius: 9px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
            transition: transform 0.28s cubic-bezier(0.25, 1, 0.5, 1);
            z-index: 1;
            pointer-events: none;
        }

        .ios-segmented-tab-btn {
            position: relative;
            flex: 1;
            z-index: 2;
            background: transparent !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            padding: 9px 14px;
            font-size: 13.5px;
            font-weight: 600;
            text-align: center;
            color: var(--app-text-muted, #8E8E93);
            transition: color 0.22s ease, transform 0.15s ease;
            cursor: pointer;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: -0.2px;
            -webkit-tap-highlight-color: transparent;
        }

        .ios-segmented-tab-btn:active {
            transform: scale(0.96);
        }

        .ios-segmented-tab-btn.active.type-expense {
            color: #FF453A !important;
            font-weight: 700;
        }

        .ios-segmented-tab-btn.active.type-income {
            color: #30D158 !important;
            font-weight: 700;
        }


        /* ==================== AUTHENTIC APPLE iOS SLIDING SEGMENTED CONTROL ==================== */
        .ios-segmented-control-slider-wrap {
            position: relative;
            display: flex;
            background-color: var(--app-card-inner, #171923);
            border: 1px solid var(--app-border, rgba(255, 255, 255, 0.1));
            border-radius: 12px;
            padding: 3px;
            user-select: none;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .ios-segmented-slider {
            position: absolute;
            top: 3px;
            bottom: 3px;
            left: 3px;
            width: calc(50% - 3px);
            background-color: var(--app-card-bg, #0E1017);
            border-radius: 9px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.25);
            transition: transform 0.28s cubic-bezier(0.25, 1, 0.5, 1);
            z-index: 1;
            pointer-events: none;
        }

        .ios-segmented-tab-btn {
            position: relative;
            flex: 1;
            z-index: 2;
            background: transparent !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            padding: 9px 14px;
            font-size: 13.5px;
            font-weight: 600;
            text-align: center;
            color: var(--app-text-muted, #8E8E93);
            transition: color 0.22s ease, transform 0.15s ease;
            cursor: pointer;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: -0.2px;
            -webkit-tap-highlight-color: transparent;
        }

        .ios-segmented-tab-btn:active {
            transform: scale(0.96);
        }

        .ios-segmented-tab-btn.active.type-expense {
            color: #FF453A !important;
            font-weight: 700;
        }

        .ios-segmented-tab-btn.active.type-income {
            color: #30D158 !important;
            font-weight: 700;
        }


        /* ==================== APPLE CINEMATIC ENTRANCE ANIMATIONS ==================== */
        @keyframes macosRevealFadeOut {
            0% { opacity: 1; pointer-events: all; }
            100% { opacity: 0; pointer-events: none; visibility: hidden; }
        }

        @keyframes macosCardCascade {
            0% {
                opacity: 0;
                transform: translateY(18px);
                filter: blur(4px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        @keyframes macosSidebarSlide {
            0% {
                opacity: 0;
                transform: translateX(-24px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes macosTopbarSlide {
            0% {
                opacity: 0;
                transform: translateY(-16px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .macos-reveal-overlay {
            position: fixed;
            inset: 0;
            background-color: var(--app-bg);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            animation: macosRevealFadeOut 0.4s cubic-bezier(0.16, 1, 0.3, 1) 0.28s forwards;
            pointer-events: none;
        }

        .macos-reveal-spinner {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: var(--app-brand-icon-bg);
            color: var(--app-brand-icon-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            animation: macosRevealPulse 1.2s ease-in-out infinite;
        }

        @keyframes macosRevealPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.08); opacity: 0.88; }
        }

        .macos-sidebar {
            animation: macosSidebarSlide 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .macos-topbar {
            animation: macosTopbarSlide 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .ios-hero-banner {
            animation: macosCardCascade 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.08s both;
        }

        .col-sm-6:nth-child(1) .ios-kpi-card { animation: macosCardCascade 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.10s both; }
        .col-sm-6:nth-child(2) .ios-kpi-card { animation: macosCardCascade 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.14s both; }
        .col-sm-6:nth-child(3) .ios-kpi-card { animation: macosCardCascade 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.18s both; }
        .col-sm-6:nth-child(4) .ios-kpi-card { animation: macosCardCascade 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.22s both; }
        .ios-section-card { animation: macosCardCascade 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.24s both; }
    </style>
</head>
<body>

<!-- Native macOS Reveal Overlay (Dissolves Smoothly on Load) -->
<div class="macos-reveal-overlay">
    <div class="macos-reveal-spinner">
        <i class="bi bi-wallet2"></i>
    </div>
</div>

<div class="macos-app-layout">

    <!-- ==================== APPLE macOS SIDEBAR ==================== -->
    <div class="macos-sidebar d-none d-lg-flex">
        
        <!-- Brand Header -->
        <div class="brand-header">
            <div class="brand-icon">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0" style="letter-spacing: -0.3px; color: var(--app-text-primary);">DompetKu Pro</h6>
                <small style="color: var(--app-text-muted); font-size: 11px;">PBW Universitas Gunadarma</small>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="my-3 flex-grow-1">
            <div class="sidebar-heading">Menu Utama</div>
            
            <a href="#overviewSection" class="nav-link active">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard Overview</span>
            </a>
            <a href="#transaksiSection" class="nav-link">
                <i class="bi bi-receipt"></i>
                <span>Riwayat Transaksi</span>
            </a>
            <a href="#kategoriSection" class="nav-link">
                <i class="bi bi-tags"></i>
                <span>Master Kategori</span>
            </a>
            <a href="#laporanSection" class="nav-link">
                <i class="bi bi-file-earmark-text"></i>
                <span>Rekap Laporan Kas</span>
            </a>

            <div class="sidebar-heading mt-4">Integrasi & Ekspor</div>
            
            <a href="<?= base_url('export_excel.php') ?>" class="nav-link">
                <i class="bi bi-file-earmark-excel text-success"></i>
                <span>Unduh Excel (.xls)</span>
            </a>
        </div>

        <!-- Bottom Student Info Card -->
        <div class="sidebar-footer-card">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary bg-opacity-20 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 13px;">
                    FD
                </div>
                <div>
                    <div class="fw-bold small text-truncate" style="color: var(--app-text-primary); max-width: 140px;">
                        <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Firdaus Dhuha') ?>
                    </div>
                    <small style="color: var(--app-text-muted); font-size: 10.5px;">Sistem Informasi • PBW</small>
                </div>
            </div>
        </div>

    </div>

    <!-- ==================== MAIN CONTENT WRAPPER ==================== -->
    <div class="macos-main-viewport">

        <!-- Topbar Header -->
        <div class="macos-topbar">
            
            <!-- Left Search Input -->
            <div class="position-relative">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary" style="font-size: 13px;"></i>
                <input type="text" id="topSearchInput" class="topbar-search-input" placeholder="Cari transaksi kas..." onkeyup="filterAdminTransaksi()">
            </div>

            <!-- Right Controls -->
            <div class="d-flex align-items-center gap-3">
                
                <!-- Live Realtime WIB Clock Pill -->
                <span class="badge border px-3 py-2 fw-semibold d-none d-md-flex align-items-center gap-2" id="wibClockBadge" style="background-color: var(--app-card-inner); border-color: var(--app-border) !important; color: var(--app-text-secondary); font-size: 12px;">
                    <span class="rounded-circle bg-success" style="width: 7px; height: 7px; display: inline-block;"></span>
                    <span id="liveTimeText"><?= tgl_indo(date('Y-m-d')) ?> • <?= date('H:i:s') ?> WIB</span>
                </span>

                <!-- THEME TOGGLE BUTTON (DARK TITANIUM & CUPERTINO LIGHT) -->
                <button type="button" onclick="toggleTheme()" class="btn btn-ios-secondary py-1 px-3 d-flex align-items-center gap-2" title="Ganti Mode Terang / Gelap">
                    <i class="theme-toggle-icon fs-6"></i>
                    <span class="d-none d-sm-inline small fw-semibold" id="themeModeText">Mode Tampilan</span>
                </button>

                <!-- Divider -->
                <div style="width: 1px; height: 26px; background-color: var(--app-border);"></div>

                <!-- User Profile & Logout -->
                <div class="d-flex align-items-center gap-2">
                    <div class="text-end d-none d-md-block">
                        <div class="fw-bold small" style="color: var(--app-text-primary); line-height: 1.1;">
                            <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Firdaus Dhuha') ?>
                        </div>
                        <small style="color: var(--app-text-muted); font-size: 10.5px;">Mahasiswa (PBW)</small>
                    </div>

                    <a href="<?= base_url('logout.php') ?>" class="btn btn-sm btn-outline-danger p-1 px-2 rounded-3" title="Keluar dari sesi" onclick="return confirm('Keluar dari sesi admin?');">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>

            </div>

        </div>

        <!-- Main Body Content Area -->
        <div class="macos-content-area">
            
            <div id="overviewSection" class="scroll-target mb-4">
                
                <?= $alert_msg ?>

                <!-- Page Title & Top Actions -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1" style="letter-spacing: -0.5px; color: var(--app-text-primary);">
                            Dashboard Finansial Mahasiswa
                        </h4>
                        <p class="small mb-0" style="color: var(--app-text-secondary);">
                            Manajemen Arus Kas Riil & Kewajiban Finansial • Database MySQL Universitas Gunadarma
                        </p>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= base_url('export_excel.php') ?>" class="btn btn-ios-secondary d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-excel text-success"></i>
                            <span>Unduh Excel</span>
                        </a>
                        <button type="button" class="btn btn-ios-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="bi bi-plus-lg"></i>
                            <span>Catat Transaksi</span>
                        </button>
                    </div>
                </div>

                <!-- ==================== 4 APPLE iOS STAT CARDS ==================== -->
                <div class="row g-3 mb-4">

                    <!-- 1. TOTAL PEMASUKAN -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="ios-kpi-card">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-bold text-uppercase" style="color: var(--app-text-muted); font-size: 11px; letter-spacing: 0.6px;">
                                    Total Pemasukan
                                </span>
                                <div class="ios-kpi-pill">
                                    <i class="bi bi-arrow-down-left"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0" style="color: var(--ios-green); letter-spacing: -0.5px;">
                                + <?= format_rupiah($total_masuk) ?>
                            </h4>
                            <small style="color: var(--app-text-muted); font-size: 11px;">Bulan berjalan • Kas Masuk</small>
                        </div>
                    </div>

                    <!-- 2. TOTAL PENGELUARAN -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="ios-kpi-card">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-bold text-uppercase" style="color: var(--app-text-muted); font-size: 11px; letter-spacing: 0.6px;">
                                    Total Pengeluaran
                                </span>
                                <div class="ios-kpi-pill">
                                    <i class="bi bi-arrow-up-right"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0" style="color: var(--ios-red); letter-spacing: -0.5px;">
                                - <?= format_rupiah($total_keluar) ?>
                            </h4>
                            <small style="color: var(--app-text-muted); font-size: 11px;">Bulan berjalan • Kas Keluar</small>
                        </div>
                    </div>

                    <!-- 3. SALDO KAS FISIK -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="ios-kpi-card">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-bold text-uppercase" style="color: var(--app-text-muted); font-size: 11px; letter-spacing: 0.6px;">
                                    Saldo Kas Fisik
                                </span>
                                <div class="ios-kpi-pill">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0" style="color: var(--app-text-primary); letter-spacing: -0.5px;">
                                <?= format_rupiah($saldo_akhir) ?>
                            </h4>
                            <small style="color: var(--app-text-muted); font-size: 11px;">Pemasukan dikurangi Pengeluaran</small>
                        </div>
                    </div>

                    <!-- 4. TOTAL MUTASI TRANSAKSI -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="ios-kpi-card">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-bold text-uppercase" style="color: var(--app-text-muted); font-size: 11px; letter-spacing: 0.6px;">
                                    Total Mutasi
                                </span>
                                <div class="ios-kpi-pill">
                                    <i class="bi bi-receipt"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0" style="color: var(--app-text-primary); letter-spacing: -0.5px;">
                                <?= count($daftar_transaksi) ?> Transaksi
                            </h4>
                            <small style="color: var(--app-text-muted); font-size: 11px;">Pencatatan kas aktif</small>
                        </div>
                    </div>

                </div>

                <!-- ==================== RINGKASAN ARUS KAS FORMAL ==================== -->
                <div class="ios-hero-banner mb-4">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-8">
                            <span class="badge ios-hero-pill mb-2 fw-semibold">
                                <i class="bi bi-shield-check me-1"></i> Rekapitulasi Kas Keuangan Personal
                            </span>
                            <h2 class="display-6 fw-bold mb-2 hero-amount-text" style="letter-spacing: -1px;">
                                Total Saldo Kas: <?= format_rupiah($saldo_akhir) ?>
                            </h2>
                            <p class="small mb-3 hero-subtext">
                                Akumulasi saldo riil kas aktif berdasarkan penerimaan <strong><?= format_rupiah($total_masuk) ?></strong> dan pengeluaran <strong><?= format_rupiah($total_keluar) ?></strong> yang tercatat di sistem secara transparan.
                            </p>
                            <div class="progress hero-progress" style="height: 6px; border-radius: 10px; max-width: 420px;">
                                <div class="progress-bar" role="progressbar" style="width: <?= $rasio_simpan ?>%; background-color: var(--ios-green);" aria-valuenow="<?= $rasio_simpan ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="hero-subtext mt-1 d-block" style="font-size: 10.5px;"><?= $rasio_simpan ?>% rasio saldo kas tersimpan</small>
                        </div>
                        <div class="col-12 col-md-4 text-md-end">
                            <a href="#transaksiSection" class="btn btn-hero-action py-2 px-4 fw-semibold rounded-3 shadow-sm">
                                <i class="bi bi-receipt me-1"></i> Riwayat Transaksi
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ==================== 2 CHARTS ROW (APPLE FINTECH STYLE) ==================== -->
            <div class="row g-4 mb-4">

                <!-- Left: Line Chart (Arus Kas) -->
                <div class="col-12 col-lg-8">
                    <div class="ios-section-card h-100 mb-0">
                        <div class="ios-card-header">
                            <div>
                                <h6 class="fw-bold mb-0">Tren Arus Kas (Pemasukan vs Pengeluaran)</h6>
                                <small style="color: var(--app-text-muted); font-size: 11.5px;">Visualisasi Data Riil Database MySQL</small>
                            </div>
                            <span class="badge px-2 py-1 fw-semibold" style="background-color: var(--app-card-inner); color: var(--app-text-secondary); border: 1px solid var(--app-border); font-size: 11px;">Arus Kas</span>
                        </div>
                        <div class="p-4" style="height: 310px; position: relative;">
                            <canvas id="chartCashflowIos"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Right: Donut Chart (Proporsi Pengeluaran) -->
                <div class="col-12 col-lg-4">
                    <div class="ios-section-card h-100 mb-0">
                        <div class="ios-card-header">
                            <div>
                                <h6 class="fw-bold mb-0">Proporsi Pengeluaran</h6>
                                <small style="color: var(--app-text-muted); font-size: 11.5px;">Kategori Pengeluaran Terbesar</small>
                            </div>
                            <span class="badge px-2 py-1 fw-semibold" style="background-color: var(--app-card-inner); color: var(--app-text-secondary); border: 1px solid var(--app-border); font-size: 11px;">Kategori</span>
                        </div>
                        <div class="p-3" style="height: 240px; position: relative;">
                            <canvas id="chartCategoryIos"></canvas>
                        </div>
                        <div class="px-3 pb-3 pt-1 text-center small text-secondary" style="font-size: 11px;">
                            <?php if (!empty($donut_labels)): ?>
                                <?php 
                                $palette = ['#334155', '#475569', '#64748B', '#059669', '#D97706'];
                                foreach (array_slice($donut_labels, 0, 4) as $idx => $lbl): 
                                    $col = $palette[$idx % count($palette)];
                                ?>
                                    <span class="me-2 d-inline-block">
                                        <i class="bi bi-circle-fill" style="color: <?= $col ?>;"></i> <?= htmlspecialchars($lbl) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ==================== SECTION 1: RIWAYAT TRANSAKSI KAS ==================== -->
            <div id="transaksiSection" class="ios-section-card mb-4">
                <div class="ios-card-header">
                    <div>
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-receipt me-1"></i> Data Riwayat Transaksi Kas
                        </h6>
                        <small style="color: var(--app-text-muted); font-size: 11.5px;">Daftar pemasukan dan pengeluaran mahasiswa</small>
                    </div>

                    <button type="button" class="btn btn-ios-primary btn-sm py-1 px-3 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalTambah">
                        <i class="bi bi-plus-lg"></i> <span>Catat Baru</span>
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-ios align-middle" id="tableAdminTrx">
                        <thead>
                            <tr>
                                <th style="width: 5%;" class="text-center">No</th>
                                <th style="width: 14%;">Tanggal</th>
                                <th style="width: 22%;">Kategori</th>
                                <th style="width: 32%;">Keterangan & Catatan</th>
                                <th style="width: 10%;" class="text-center">Jenis</th>
                                <th style="width: 12%;" class="text-end">Nominal</th>
                                <th style="width: 7%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($daftar_transaksi as $row): 
                                $is_masuk = ($row['jenis'] === 'Pemasukan');
                            ?>
                            <tr class="admin-trx-row" data-search="<?= strtolower(htmlspecialchars($row['judul'] . ' ' . $row['nama_kategori'] . ' ' . $row['catatan'])) ?>">
                                <td class="text-center" style="color: var(--app-text-muted);"><?= $no++ ?></td>
                                <td>
                                    <span class="table-date-text"><?= tgl_indo($row['tanggal']) ?></span>
                                </td>
                                <td>
                                    <span class="badge-kategori-pill">
                                        <i class="bi <?= htmlspecialchars($row['icon']) ?>"></i>
                                        <?= htmlspecialchars($row['nama_kategori']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="table-title-text"><?= htmlspecialchars($row['judul']) ?></span>
                                    <?php if (!empty($row['catatan'])): ?>
                                        <small class="d-block mt-1" style="color: var(--app-text-muted); font-size: 11.5px;"><?= htmlspecialchars($row['catatan']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $is_masuk ? 'bg-success' : 'bg-danger' ?> bg-opacity-20 <?= $is_masuk ? 'text-success' : 'text-danger' ?> px-2 py-1" style="font-size: 11px; border-radius: 8px;">
                                        <?= $row['jenis'] ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold <?= $is_masuk ? 'text-success' : 'text-danger' ?>">
                                    <?= ($is_masuk ? '+' : '-') ?> <?= format_rupiah($row['jumlah']) ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-ios-secondary btn-sm p-1 px-2" onclick='openEditModal(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Sunting Data">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="<?= base_url('dashboard.php?hapus_trx=' . $row['id_transaksi']) ?>" class="btn btn-outline-danger btn-sm p-1 px-2 rounded-3" onclick="return confirm('Hapus transaksi ini?');" title="Hapus">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==================== SECTION 2: MASTER KATEGORI ==================== -->
            <div id="kategoriSection" class="ios-section-card mb-4">
                <div class="ios-card-header">
                    <div>
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-tags me-1"></i> Master Kategori Transaksi
                        </h6>
                        <small style="color: var(--app-text-muted); font-size: 11.5px;">Relasi Foreign Key dengan Data Transaksi Kas</small>
                    </div>

                    <button type="button" class="btn btn-ios-primary btn-sm py-1 px-3 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalTambahKat">
                        <i class="bi bi-plus-lg"></i> <span>Kategori Baru</span>
                    </button>
                </div>

                <div class="p-3">
                    <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-4">
                        <?php foreach ($kategori_list as $kat): 
                            $is_in = ($kat['jenis'] === 'Pemasukan');
                        ?>
                        <div class="col">
                            <div class="p-3 rounded-4 d-flex align-items-center justify-content-between" style="background-color: var(--app-card-inner); border: 1px solid var(--app-border);">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="ios-kpi-pill">
                                        <i class="bi <?= htmlspecialchars($kat['icon']) ?>" style="font-size: 16px;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small" style="color: var(--app-text-primary);"><?= htmlspecialchars($kat['nama_kategori']) ?></div>
                                        <span class="badge <?= $is_in ? 'text-success bg-success' : 'text-danger bg-danger' ?> bg-opacity-20 px-2 py-0" style="font-size: 10px;">
                                            <?= $kat['jenis'] ?> • <?= $kat['total_trx'] ?> data
                                        </span>
                                    </div>
                                </div>
                                <a href="<?= base_url('dashboard.php?hapus_kat=' . $kat['id_kategori']) ?>" 
                                   class="btn btn-outline-danger btn-sm p-1 px-2 rounded-3"
                                   onclick="return confirm('Hapus kategori <?= htmlspecialchars($kat['nama_kategori']) ?>?');"
                                   title="Hapus Kategori">
                                    <i class="bi bi-trash3"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ==================== SECTION 4: REKAP LAPORAN KAS (EXECUTIVE SUMMARY & BREAKDOWN) ==================== -->
            <div id="laporanSection" class="ios-section-card mb-4">
                <div class="ios-card-header">
                    <div>
                        <h6 class="fw-bold mb-0">
                            <i class="bi bi-file-earmark-text me-1"></i> Rekapitulasi Laporan Keuangan Kas
                        </h6>
                        <small style="color: var(--app-text-muted); font-size: 11.5px;">Ringkasan agregasi arus kas, beban kategori, & ekspor dokumen resmi</small>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= base_url('export_excel.php') ?>" class="btn btn-ios-secondary btn-sm py-1 px-3 d-flex align-items-center gap-1">
                            <i class="bi bi-file-earmark-excel text-success"></i> Unduh Excel (.xls)
                        </a>
                        <button onclick="window.print()" class="btn btn-ios-secondary btn-sm py-1 px-3 d-flex align-items-center gap-1">
                            <i class="bi bi-printer"></i> Cetak Dokumen
                        </button>
                    </div>
                </div>

                <div class="p-4">
                    <!-- Ringkasan Eksekutif 5 Indikator Finansial -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md">
                            <div class="p-3 rounded-3" style="background-color: var(--app-card-inner); border: 1px solid var(--app-border);">
                                <small class="text-uppercase fw-bold d-block mb-1" style="color: var(--app-text-muted); font-size: 10px; letter-spacing: 0.5px;">Total Pemasukan</small>
                                <span class="fw-bold" style="color: var(--ios-green); font-size: 15px;">+ <?= format_rupiah($total_masuk) ?></span>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="p-3 rounded-3" style="background-color: var(--app-card-inner); border: 1px solid var(--app-border);">
                                <small class="text-uppercase fw-bold d-block mb-1" style="color: var(--app-text-muted); font-size: 10px; letter-spacing: 0.5px;">Total Pengeluaran</small>
                                <span class="fw-bold" style="color: var(--ios-red); font-size: 15px;">- <?= format_rupiah($total_keluar) ?></span>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="p-3 rounded-3" style="background-color: var(--app-card-inner); border: 1px solid var(--app-border);">
                                <small class="text-uppercase fw-bold d-block mb-1" style="color: var(--app-text-muted); font-size: 10px; letter-spacing: 0.5px;">Surplus Kas Riil</small>
                                <span class="fw-bold" style="color: var(--app-text-primary); font-size: 15px;"><?= format_rupiah($saldo_akhir) ?></span>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="p-3 rounded-3" style="background-color: var(--app-card-inner); border: 1px solid var(--app-border);">
                                <small class="text-uppercase fw-bold d-block mb-1" style="color: var(--app-text-muted); font-size: 10px; letter-spacing: 0.5px;">Rasio Tabungan</small>
                                <span class="fw-bold" style="color: var(--ios-green); font-size: 15px;"><?= $rasio_simpan ?>% Terjaga</span>
                            </div>
                        </div>
                        <div class="col-12 col-md">
                            <div class="p-3 rounded-3" style="background-color: var(--app-card-inner); border: 1px solid var(--app-border);">
                                <small class="text-uppercase fw-bold d-block mb-1" style="color: var(--app-text-muted); font-size: 10px; letter-spacing: 0.5px;">Status Likuiditas</small>
                                <span class="fw-bold" style="color: var(--app-text-primary); font-size: 15px;">Kas Bersih Aman</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Rekapitulasi Beban per Kategori (Aggregated Breakdown) -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="fw-bold small text-uppercase" style="color: var(--app-text-secondary); letter-spacing: 0.5px;">
                            Distribusi Pengeluaran Berdasarkan Kategori
                        </span>
                        <span class="small" style="color: var(--app-text-muted); font-size: 12px;">
                            Total Beban Kas: <strong><?= format_rupiah($total_keluar) ?></strong>
                        </span>
                    </div>

                    <div class="table-responsive rounded-3 border" style="border-color: var(--app-border) !important;">
                        <table class="table table-ios align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 6%;" class="text-center">No</th>
                                    <th style="width: 28%;">Kategori Pengeluaran</th>
                                    <th style="width: 18%;" class="text-center">Frekuensi Transaksi</th>
                                    <th style="width: 22%;" class="text-end">Total Akumulasi</th>
                                    <th style="width: 26%;">Porsi Beban Finansial</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (empty($kat_expenses)): 
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4" style="color: var(--app-text-muted);">
                                            Belum ada data transaksi pengeluaran untuk direkap.
                                        </td>
                                    </tr>
                                <?php 
                                else:
                                    $no_rekap = 1; 
                                    foreach ($kat_expenses as $rekap): 
                                        $nominal = (float)$rekap['total'];
                                        $persen  = ($total_keluar > 0) ? round(($nominal / $total_keluar) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td class="text-center" style="color: var(--app-text-muted);"><?= $no_rekap++ ?></td>
                                    <td>
                                        <span class="badge-kategori-pill">
                                            <i class="bi <?= htmlspecialchars($rekap['icon'] ?? 'bi-tag') ?>"></i>
                                            <?= htmlspecialchars($rekap['nama_kategori']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary bg-opacity-15 px-2 py-1" style="color: var(--app-text-secondary); border-radius: 6px; font-size: 11.5px;">
                                            <?= (int)$rekap['total_trx'] ?> kali mutasi
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold" style="color: var(--app-text-primary);">
                                        <?= format_rupiah($nominal) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; background-color: var(--app-card-inner); border-radius: 10px;">
                                                <div class="progress-bar" style="width: <?= min(100, $persen) ?>%; background-color: var(--ios-slate); border-radius: 10px;"></div>
                                            </div>
                                            <span class="small fw-semibold" style="color: var(--app-text-muted); min-width: 42px; font-size: 11px;">
                                                <?= $persen ?>%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endforeach; 
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- ==================== MODAL TAMBAH TRANSAKSI ==================== -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-ios-content p-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTambahLabel" style="color: var(--app-text-primary);">
                    Catat Transaksi Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="<?= base_url('dashboard.php') ?>" method="POST" id="formCatatTransaksi">
                <div class="modal-body py-3">

                    <div class="mb-3 text-center">
                        <label class="form-label small fw-bold d-block mb-1" style="color: var(--app-text-secondary);">Jenis Transaksi</label>
                        <div class="ios-segmented-control-slider-wrap" id="modalDeskJenisWrap">
                            <div class="ios-segmented-slider" id="modalDeskJenisSlider"></div>
                            <button type="button" class="ios-segmented-tab-btn active type-expense" id="btnModalDeskPengeluaran" onclick="selectDeskModalJenis('Pengeluaran')">
                                <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                            </button>
                            <button type="button" class="ios-segmented-tab-btn" id="btnModalDeskPemasukan" onclick="selectDeskModalJenis('Pemasukan')">
                                <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                            </button>
                        </div>
                        <input type="hidden" name="jenis" id="hiddenDeskInputJenis" value="Pengeluaran">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah" id="inputJumlah" class="modal-ios-input" placeholder="Contoh: 25000" min="100" step="100" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Kategori <span class="text-danger">*</span></label>
                        <select name="id_kategori" id="selectKategori" class="modal-ios-input" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= $kat['id_kategori'] ?>" data-jenis="<?= $kat['jenis'] ?>">
                                    [<?= $kat['jenis'] ?>] <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Keterangan / Judul <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="inputJudul" class="modal-ios-input" placeholder="Contoh: Makan Siang / Kiriman Ortu" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Tanggal Transaksi <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="inputTanggal" class="modal-ios-input" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Catatan Tambahan (Opsional)</label>
                        <textarea name="catatan" id="inputCatatan" class="modal-ios-input" rows="2" placeholder="Detail catatan..."></textarea>
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-ios-secondary w-100 mb-1" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_transaksi" class="btn btn-ios-primary w-100">
                        Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL SUN TING / EDIT TRANSAKSI ==================== -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-ios-content p-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalEditLabel" style="color: var(--app-text-primary);">
                    Sunting Data Transaksi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="<?= base_url('dashboard.php') ?>" method="POST" id="formEditTransaksi">
                <input type="hidden" name="id_transaksi" id="editIdTransaksi">
                
                <div class="modal-body py-3">
                    <div class="mb-3 text-center">
                        <label class="form-label small fw-bold d-block mb-1" style="color: var(--app-text-secondary);">Jenis Transaksi</label>
                        <div class="btn-group w-100 p-1 rounded-3" style="background-color: var(--app-card-inner); border: 1px solid var(--app-border);" role="group">
                            <input type="radio" class="btn-check" name="jenis" id="editRadioPengeluaran" value="Pengeluaran" onchange="filterEditKategoriDropdown('Pengeluaran')">
                            <label class="btn btn-outline-danger fw-bold border-0 rounded-3" for="editRadioPengeluaran">
                                <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                            </label>

                            <input type="radio" class="btn-check" name="jenis" id="editRadioPemasukan" value="Pemasukan" onchange="filterEditKategoriDropdown('Pemasukan')">
                            <label class="btn btn-outline-success fw-bold border-0 rounded-3" for="editRadioPemasukan">
                                <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah" id="editJumlah" class="modal-ios-input" min="100" step="100" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Kategori <span class="text-danger">*</span></label>
                        <select name="id_kategori" id="editSelectKategori" class="modal-ios-input" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= $kat['id_kategori'] ?>" data-jenis="<?= $kat['jenis'] ?>">
                                    [<?= $kat['jenis'] ?>] <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Keterangan / Judul <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="editJudul" class="modal-ios-input" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Tanggal Transaksi <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="editTanggal" class="modal-ios-input" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Catatan Tambahan (Opsional)</label>
                        <textarea name="catatan" id="editCatatan" class="modal-ios-input" rows="2"></textarea>
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-ios-secondary w-100 mb-1" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_transaksi" class="btn btn-ios-primary w-100">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL TAMBAH KATEGORI ==================== -->
<div class="modal fade" id="modalTambahKat" tabindex="-1" aria-labelledby="modalTambahKatLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-ios-content p-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTambahKatLabel" style="color: var(--app-text-primary);">
                    Tambah Kategori Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('dashboard.php') ?>" method="POST">
                <div class="modal-body py-3">
                    <div class="mb-3 text-center">
                        <label class="form-label small fw-bold d-block mb-1" style="color: var(--app-text-secondary);">Jenis Kategori</label>
                        <div class="btn-group w-100 p-1 rounded-3" style="background-color: var(--app-card-inner); border: 1px solid var(--app-border);" role="group">
                            <input type="radio" class="btn-check" name="jenis" id="katPengeluaran" value="Pengeluaran" checked>
                            <label class="btn btn-outline-danger fw-bold border-0 rounded-3" for="katPengeluaran">Pengeluaran</label>

                            <input type="radio" class="btn-check" name="jenis" id="katPemasukan" value="Pemasukan">
                            <label class="btn btn-outline-success fw-bold border-0 rounded-3" for="katPemasukan">Pemasukan</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" class="modal-ios-input" placeholder="Contoh: Tabungan / Hiburan" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="color: var(--app-text-secondary);">Ikon Kategori</label>
                        <select name="icon" class="modal-ios-input">
                            <option value="bi-tag">Tag / Umum (bi-tag)</option>
                            <option value="bi-cup-hot">Makanan & Minuman (bi-cup-hot)</option>
                            <option value="bi-book">Buku & Kuliah (bi-book)</option>
                            <option value="bi-cart">Belanja & Kebutuhan (bi-cart)</option>
                            <option value="bi-fuel-pump">Bensin & Transport (bi-fuel-pump)</option>
                            <option value="bi-house-door">Kos & Hunian (bi-house-door)</option>
                            <option value="bi-wallet2">Uang Saku & Dompet (bi-wallet2)</option>
                            <option value="bi-laptop">Freelance & Kerja (bi-laptop)</option>
                            <option value="bi-controller">Game & Hiburan (bi-controller)</option>
                            <option value="bi-heart-pulse">Kesehatan (bi-heart-pulse)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-ios-secondary w-100 mb-1" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_kategori" class="btn btn-ios-primary w-100">
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Real-Time Search Transaksi
    function filterAdminTransaksi() {
        const query = (document.getElementById('topSearchInput').value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.admin-trx-row');

        rows.forEach(row => {
            const text = row.getAttribute('data-search') || '';
            if (query === '' || text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Sliding Segmented Control Toggle untuk Modal Catat Transaksi
    function selectDeskModalJenis(jenis) {
        const slider = document.getElementById('modalDeskJenisSlider');
        const btnExp = document.getElementById('btnModalDeskPengeluaran');
        const btnInc = document.getElementById('btnModalDeskPemasukan');
        const input = document.getElementById('hiddenDeskInputJenis');

        if (input) input.value = jenis;

        if (jenis === 'Pemasukan') {
            if (slider) slider.style.transform = 'translateX(100%)';
            if (btnInc) btnInc.classList.add('active', 'type-income');
            if (btnExp) btnExp.classList.remove('active', 'type-expense');
        } else {
            if (slider) slider.style.transform = 'translateX(0%)';
            if (btnExp) btnExp.classList.add('active', 'type-expense');
            if (btnInc) btnInc.classList.remove('active', 'type-income');
        }
        filterKategoriDropdown(jenis);
    }

    // Filter Kategori Dropdown Tambah
    function filterKategoriDropdown(jenisTerpilih) {
        const select = document.getElementById('selectKategori');
        const options = select.querySelectorAll('option');
        let firstMatched = false;
        
        options.forEach(opt => {
            if (!opt.value) return;
            if (opt.getAttribute('data-jenis') === jenisTerpilih) {
                opt.style.display = 'block';
                if (!firstMatched) {
                    select.value = opt.value;
                    firstMatched = true;
                }
            } else {
                opt.style.display = 'none';
            }
        });
    }

    // Modal Edit Transaksi
    function openEditModal(trx) {
        if (!trx) return;
        document.getElementById('editIdTransaksi').value = trx.id_transaksi;
        document.getElementById('editJudul').value = trx.judul;
        document.getElementById('editJumlah').value = trx.jumlah;
        document.getElementById('editTanggal').value = trx.tanggal;
        document.getElementById('editCatatan').value = trx.catatan || '';

        if (trx.jenis === 'Pemasukan') {
            document.getElementById('editRadioPemasukan').checked = true;
        } else {
            document.getElementById('editRadioPengeluaran').checked = true;
        }
        filterEditKategoriDropdown(trx.jenis);
        document.getElementById('editSelectKategori').value = trx.id_kategori;

        const editModal = new bootstrap.Modal(document.getElementById('modalEdit'));
        editModal.show();
    }

    function filterEditKategoriDropdown(jenisTerpilih) {
        const select = document.getElementById('editSelectKategori');
        const options = select.querySelectorAll('option');
        options.forEach(opt => {
            if (!opt.value) return;
            if (opt.getAttribute('data-jenis') === jenisTerpilih) {
                opt.style.display = 'block';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    // Real-Time WIB Clock
    function updateWibClock() {
        const now = new Date();
        const options = { 
            timeZone: 'Asia/Jakarta', 
            weekday: 'long', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        };
        const clockEl = document.getElementById('liveTimeText');
        if (clockEl) {
            clockEl.innerText = now.toLocaleString('id-ID', options) + ' WIB';
        }
    }
    setInterval(updateWibClock, 1000);

    // ==================== APPLE macOS KINETIC SMOOTH SCROLL ENGINE ====================
    let isKineticScrolling = false;

    function smoothScrollToTarget(container, targetY, duration = 460) {
        isKineticScrolling = true;
        const startY = container.scrollTop;
        const diff = targetY - startY;
        const startTime = performance.now();

        // Authentic Apple Quartic Ease-Out curve (rapid acceleration, silky-soft landing)
        function easeOutQuart(t) {
            return 1 - Math.pow(1 - t, 4);
        }

        function step(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const ease = easeOutQuart(progress);
            
            container.scrollTop = startY + diff * ease;

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                container.scrollTop = targetY;
                setTimeout(() => { isKineticScrolling = false; }, 80);
            }
        }
        requestAnimationFrame(step);
    }

    // Sidebar Smooth Navigation - Left stays stationary, only the right pane smoothly scrolls!
    document.querySelectorAll('.macos-sidebar .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                e.preventDefault();
                const targetId = href.substring(1);
                const targetEl = document.getElementById(targetId);
                const contentArea = document.querySelector('.macos-content-area');
                
                if (targetEl && contentArea) {
                    document.querySelectorAll('.macos-sidebar .nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');

                    // Smoothly scroll only the right content container with Apple physics
                    const targetTop = Math.max(0, targetEl.offsetTop - 15);
                    smoothScrollToTarget(contentArea, targetTop, 460);

                    history.pushState(null, null, href);
                }
            }
        });
    });

    // Auto-sync active link while scrolling the right panel
    const rightContentArea = document.querySelector('.macos-content-area');
    const navSections = ['overviewSection', 'transaksiSection', 'kategoriSection', 'laporanSection'];
    
    if (rightContentArea) {
        rightContentArea.addEventListener('scroll', () => {
            if (isKineticScrolling) return; // Prevent jitter while auto-scrolling

            let current = 'overviewSection';
            const scrollPos = rightContentArea.scrollTop + 80;

            navSections.forEach(secId => {
                const el = document.getElementById(secId);
                if (el && el.offsetTop <= scrollPos) {
                    current = secId;
                }
            });

            document.querySelectorAll('.macos-sidebar .nav-link').forEach(link => {
                const href = link.getAttribute('href');
                if (href === '#' + current) {
                    link.classList.add('active');
                } else if (href && href.startsWith('#')) {
                    link.classList.remove('active');
                }
            });
        }, { passive: true });
    }

    // ==================== INTERACTIVE CHART.JS (APPLE iOS / macOS STYLE) ====================
    let cashflowChart = null;
    let categoryChart = null;

    function renderCharts() {
        const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';
        const textColor = isDark ? '#94A3B8' : '#64748B';

        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        Chart.defaults.color = textColor;

        // 1. Line Chart Arus Kas
        const ctxLine = document.getElementById('chartCashflowIos');
        if (ctxLine) {
            if (cashflowChart) cashflowChart.destroy();

            const chartStroke = isDark ? '#CBD5E1' : '#334155';
            const gradient = ctxLine.getContext('2d').createLinearGradient(0, 0, 0, 280);
            gradient.addColorStop(0, isDark ? 'rgba(203, 213, 225, 0.16)' : 'rgba(51, 65, 85, 0.10)');
            gradient.addColorStop(1, 'rgba(0, 0, 0, 0.0)');

            cashflowChart = new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: ["1 Nov", "4 Nov", "7 Nov", "10 Nov", "13 Nov", "16 Nov", "17 Nov"],
                    datasets: [{
                        label: "Arus Kas (Rp)",
                        lineTension: 0.35,
                        backgroundColor: gradient,
                        borderColor: chartStroke,
                        borderWidth: 2,
                        pointRadius: 3.5,
                        pointBackgroundColor: chartStroke,
                        pointBorderColor: isDark ? "#141620" : "#FFFFFF",
                        pointBorderWidth: 2,
                        pointHoverRadius: 5.5,
                        data: [2500000, 1650000, 2400000, 2275000, 2190000, 2130000, <?= (int)$saldo_akhir ?>],
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDark ? 'rgba(20, 22, 32, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                            titleColor: isDark ? '#FFFFFF' : '#0F172A',
                            bodyColor: isDark ? '#E2E8F0' : '#334155',
                            borderColor: isDark ? 'rgba(255, 255, 255, 0.12)' : 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1,
                            padding: 10,
                            callbacks: {
                                label: function(context) {
                                    return ' Sisa Kas: Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        },
                        y: {
                            grid: { color: gridColor },
                            ticks: {
                                color: textColor,
                                callback: function(value) {
                                    return 'Rp ' + (value / 1000).toLocaleString('id-ID') + 'k';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Donut Chart Proporsi Pengeluaran
        const ctxDonut = document.getElementById('chartCategoryIos');
        if (ctxDonut) {
            if (categoryChart) categoryChart.destroy();

            const donutLabels = <?= json_encode($donut_labels) ?>;
            const donutData   = <?= json_encode($donut_values) ?>;
            const donutPalette = isDark 
                ? ['#CBD5E1', '#94A3B8', '#64748B', '#10B981', '#F59E0B'] 
                : ['#334155', '#475569', '#64748B', '#059669', '#D97706'];

            categoryChart = new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: donutLabels.length > 0 ? donutLabels : ["Kos", "Buku Kuliah", "Makan Minum", "Bensin", "Ngopi"],
                    datasets: [{
                        data: donutData.length > 0 ? donutData : [850000, 125000, 85000, 60000, 45000],
                        backgroundColor: donutPalette,
                        hoverOffset: 4,
                        borderWidth: 2,
                        borderColor: isDark ? '#141620' : '#FFFFFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDark ? 'rgba(19, 25, 39, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                            titleColor: isDark ? '#FFFFFF' : '#0F172A',
                            bodyColor: isDark ? '#E2E8F0' : '#334155',
                            borderWidth: 1,
                            borderColor: isDark ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.1)',
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.label + ': Rp ' + context.parsed.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    cutout: '75%'
                }
            });
        }
    }

    // Inisialisasi Chart saat halaman siap
    document.addEventListener('DOMContentLoaded', () => {
        renderCharts();
    });

    // Observer untuk re-render chart saat theme di-toggle
    const observer = new MutationObserver(() => {
        renderCharts();
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
</script>

</body>
</html>
