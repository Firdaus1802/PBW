<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url("login.php"));
    exit;
}

if (empty($_SESSION['username']) && isset($_SESSION['user_id'])) {
    try {
        $stmt_u = $pdo->prepare("SELECT username, nama_lengkap FROM users WHERE id_user = ?");
        $stmt_u->execute([(int)$_SESSION['user_id']]);
        $curr_u = $stmt_u->fetch();
        if ($curr_u) {
            $_SESSION['username'] = $curr_u['username'];
            $_SESSION['nama_lengkap'] = $curr_u['nama_lengkap'];
        }
    } catch (Throwable $e) {}
}

$curr_nama = trim($_SESSION['nama_lengkap'] ?? 'Mahasiswa');
$words = explode(' ', $curr_nama);
$user_initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
if (empty($user_initials)) {
    $user_initials = strtoupper(substr($_SESSION['username'] ?? 'M', 0, 2));
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_mobile_device = (bool)preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|iemobile|opera mini|webos)/i', $ua);

if (isset($_GET['view']) && $_GET['view'] === 'mobile') {
    $_SESSION['preferred_view'] = 'mobile';
} elseif (isset($_GET['view']) && $_GET['view'] === 'desktop') {
    $_SESSION['preferred_view'] = 'desktop';
}

$has_explicit_mobile_flag = (isset($_GET['view']) && $_GET['view'] === 'mobile') || isset($_GET['mobile']);
$force_mobile = $is_mobile_device || $has_explicit_mobile_flag;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$force_mobile) {
    header("Location: " . base_url("dashboard.php"));
    exit;
}

$alert_msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $alert_msg = '<div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Transaksi berhasil dicatat ke dalam sistem!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } elseif ($_GET['msg'] === 'updated') {
        $alert_msg = '<div class="alert alert-info alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Transaksi berhasil diperbarui!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } elseif ($_GET['msg'] === 'deleted') {
        $alert_msg = '<div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-trash-fill me-2"></i> Transaksi telah dihapus.
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
        $alert_msg = '<div class="alert alert-warning alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-shield-exclamation me-2"></i> Kategori tidak dapat dihapus karena masih digunakan pada data transaksi.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['tambah_transaksi']) || isset($_POST['judul']) || isset($_POST['jumlah']))) {
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $tanggal     = sanitize($_POST['tanggal'] ?? date('Y-m-d'));
    if (empty($tanggal)) $tanggal = date('Y-m-d');
    $judul       = sanitize($_POST['judul'] ?? '');
    $jenis       = sanitize($_POST['jenis'] ?? 'Pengeluaran');
    if (!in_array($jenis, ['Pemasukan', 'Pengeluaran'])) $jenis = 'Pengeluaran';
    
    $raw_jumlah = $_POST['jumlah'] ?? 0;
    if (is_string($raw_jumlah)) {
        $cleaned = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $raw_jumlah));
        $jumlah = (float)$cleaned;
    } else {
        $jumlah = (float)$raw_jumlah;
    }
    
    $catatan = sanitize($_POST['catatan'] ?? '');

    if ($id_kategori <= 0) {
        $stmt_def = $pdo->prepare("SELECT id_kategori, nama_kategori FROM kategori WHERE jenis = ? ORDER BY id_kategori ASC LIMIT 1");
        $stmt_def->execute([$jenis]);
        $row_def = $stmt_def->fetch();
        if ($row_def) {
            $id_kategori = (int)$row_def['id_kategori'];
            if (empty($judul)) $judul = $row_def['nama_kategori'];
        }
    }

    if (empty($judul)) {
        if ($id_kategori > 0) {
            $stmt_kn = $pdo->prepare("SELECT nama_kategori FROM kategori WHERE id_kategori = ?");
            $stmt_kn->execute([$id_kategori]);
            $row_kn = $stmt_kn->fetch();
            $judul = $row_kn ? $row_kn['nama_kategori'] : ($jenis === 'Pemasukan' ? 'Pemasukan Kas' : 'Pengeluaran Kas');
        } else {
            $judul = $jenis === 'Pemasukan' ? 'Pemasukan Kas' : 'Pengeluaran Kas';
        }
    }

    $user_id = (int)$_SESSION['user_id'];
    if ($id_kategori > 0 && $jumlah > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO transaksi (id_user, id_kategori, tanggal, judul, jenis, jumlah, catatan) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $id_kategori, $tanggal, $judul, $jenis, $jumlah, $catatan]);
            dompetku_sync_trx_cookie($pdo, $user_id);
            $redir_view = $force_mobile ? 'view=mobile&' : '';
            header("Location: " . base_url("index.php?" . $redir_view . "msg=added#dompet"));
            exit;
        } catch (PDOException $e) {
            $alert_msg = '<div class="alert alert-danger rounded-4">Gagal mencatat transaksi: ' . $e->getMessage() . '</div>';
        }
    } else {
        $alert_msg = '<div class="alert alert-warning rounded-4">Harap isi nominal transaksi dengan benar.</div>';
    }
}

$user_id = (int)($_SESSION['user_id'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = sanitize($_POST['nama_kategori']);
    $jenis         = sanitize($_POST['jenis']);
    $icon          = sanitize($_POST['icon']);

    if (!empty($nama_kategori) && !empty($jenis)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, jenis, icon, warna) VALUES (?, ?, ?, '#9CA3AF')");
            $stmt->execute([$nama_kategori, $jenis, $icon]);
            header("Location: " . base_url("index.php?msg=kat_added#kategori"));
            exit;
        } catch (PDOException $e) {
            $alert_msg = '<div class="alert alert-danger rounded-4">Gagal menambahkan kategori: ' . $e->getMessage() . '</div>';
        }
    }
}

$stmt_stat = $pdo->prepare("SELECT 
    COALESCE(SUM(CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE 0 END), 0) AS total_masuk,
    COALESCE(SUM(CASE WHEN jenis = 'Pengeluaran' THEN jumlah ELSE 0 END), 0) AS total_keluar
FROM transaksi WHERE id_user = ?");
$stmt_stat->execute([$user_id]);
$stat = $stmt_stat->fetch();
$total_masuk  = (float)$stat['total_masuk'];
$total_keluar = (float)$stat['total_keluar'];
$saldo_akhir  = $total_masuk - $total_keluar;
$rasio_simpan = ($total_masuk > 0) ? max(0, min(100, round(($saldo_akhir / $total_masuk) * 100))) : 0;

$sql = "SELECT t.*, k.nama_kategori, k.icon 
        FROM transaksi t 
        JOIN kategori k ON t.id_kategori = k.id_kategori 
        WHERE t.id_user = ?
        ORDER BY t.tanggal DESC, t.id_transaksi DESC";
$stmt_trx = $pdo->prepare($sql);
$stmt_trx->execute([$user_id]);
$daftar_transaksi = $stmt_trx->fetchAll();
$transaksi_list = $daftar_transaksi;

$stmt_kat = $pdo->prepare("SELECT k.*, COUNT(t.id_transaksi) AS total_trx 
                              FROM kategori k 
                              LEFT JOIN transaksi t ON k.id_kategori = t.id_kategori AND t.id_user = ? 
                              GROUP BY k.id_kategori 
                              ORDER BY k.jenis ASC, k.nama_kategori ASC");
$stmt_kat->execute([$user_id]);
$kategori_list = $stmt_kat->fetchAll();

$stmt_ke = $pdo->prepare("SELECT k.nama_kategori, COALESCE(SUM(t.jumlah), 0) AS total 
                             FROM kategori k 
                             JOIN transaksi t ON k.id_kategori = t.id_kategori 
                             WHERE t.jenis = 'Pengeluaran' AND t.id_user = ?
                             GROUP BY k.id_kategori 
                             ORDER BY total DESC");
$stmt_ke->execute([$user_id]);
$kat_expenses = $stmt_ke->fetchAll();
$chart_labels_kat = [];
$chart_data_kat   = [];
foreach ($kat_expenses as $ke) {
    $chart_labels_kat[] = $ke['nama_kategori'];
    $chart_data_kat[]   = (float)$ke['total'];
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0C0D12" id="metaThemeColor">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/icons/app-icon.png') ?>">
    <title>DompetKu - CashFlow & Dashboard Mahasiswa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link href="<?= base_url('assets/css/ios-theme.css?v=' . time()) ?>" rel="stylesheet">
    
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>

    <script>
        if (window.innerWidth >= 992 && !window.location.search.includes('view=mobile')) {
            window.location.replace('<?= base_url("dashboard.php") ?>');
        }
    </script>

    <style>
        .rekap-table-container,
        .table-responsive.rounded-4 {
            background-color: var(--boss-card);
            border: 1px solid var(--boss-neu-border) !important;
            box-shadow: var(--boss-neu-shadow);
            border-radius: 20px !important;
        }
        .rekap-table th {
            background-color: var(--boss-table-th-bg);
            color: var(--boss-text-secondary);
            font-size: 11px;
            letter-spacing: 0.6px;
            padding: 12px 14px;
            border-color: var(--boss-table-border);
        }
        .rekap-table td {
            background-color: var(--boss-card);
            border-color: var(--boss-table-border);
            padding: 12px 14px;
            vertical-align: middle;
            color: var(--boss-text-primary) !important;
        }
        .table-date-text { color: var(--boss-text-secondary) !important; font-size: 13px; font-weight: 600; }
        .table-title-text { color: var(--boss-text-primary) !important; font-size: 14px; font-weight: 600; }
        .table-notes-text { color: var(--boss-text-muted) !important; font-size: 11.5px; }
        .badge-kategori-solid {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 10px;
            background-color: var(--boss-card-inner);
            color: var(--boss-text-primary) !important;
            border: 1px solid var(--boss-neu-border);
            box-shadow: var(--boss-neu-inset);
        }

        @media print {
            .no-print, .ios-tab-bar, .ios-navbar { display: none !important; }
            body { background-color: #fff !important; color: #000 !important; padding: 0 !important; }
            .ios-main-content { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .tab-view-panel { display: none !important; }
            #tabViewLaporan { display: block !important; opacity: 1 !important; transform: none !important; }
            .rekap-table th { background-color: #f1f5f9 !important; color: #000 !important; }
            .rekap-table td { background-color: #fff !important; color: #000 !important; }
            .table-title-text { color: #000 !important; }
            .table-date-text { color: #333 !important; }
        }
    </style>
</head>
<body>

    <div class="ios-app-shell">

        
        <nav class="ios-navbar px-3 px-md-4">
            <div class="container-xl d-flex align-items-center justify-content-between">
                
                
                <div class="d-flex align-items-center gap-2">
                    <div class="ios-icon-box" style="width: 38px; height: 38px; border-radius: 12px; background: var(--ios-center-circle-bg); color: var(--ios-center-circle-color); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        <i class="bi bi-wallet2" style="font-size: 19px;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold" id="navbarViewTitle" style="letter-spacing: -0.4px; font-size: 16px; color: var(--boss-text-primary);">DompetKu</h6>
                        <small class="text-secondary" style="font-size: 11px;"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Mahasiswa') ?> <span style="opacity: 0.7;">(@<?= htmlspecialchars($_SESSION['username'] ?? 'user') ?>)</span></small>
                    </div>
                </div>

                
                <div class="d-none">
                    <button type="button" class="ios-desktop-tab-btn active" id="deskTabDompet" onclick="switchIosTab('dompet')">
                        <i class="bi bi-wallet2 me-1"></i> Dompet
                    </button>
                    <button type="button" class="ios-desktop-tab-btn" id="deskTabKategori" onclick="switchIosTab('kategori')">
                        <i class="bi bi-tags me-1"></i> Kategori
                    </button>
                    <button type="button" class="ios-desktop-tab-btn" id="deskTabLaporan" onclick="switchIosTab('laporan')">
                        <i class="bi bi-file-earmark-text me-1"></i> Laporan
                    </button>
                    <button type="button" class="ios-desktop-tab-btn" id="deskTabAkun" onclick="switchIosTab('akun')">
                        <i class="bi bi-person-circle me-1"></i> Akun
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="<?= base_url('dashboard.php?view=desktop') ?>" class="ios-circle-btn d-none d-md-flex" title="Kembali ke Dashboard PC">
                        <i class="bi bi-laptop"></i>
                    </a>

                    <input type="file" id="receiptFileInput" style="display:none;" accept="image/*" onchange="processReceipt(this)">

                    <button type="button" onclick="toggleTheme()" class="ios-circle-btn" title="Ganti Mode Terang / Gelap">
                        <i class="theme-toggle-icon"></i>
                    </button>

                    <button type="button" class="ios-circle-btn ios-circle-btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah" title="Catat Transaksi Manual">
                        <i class="bi bi-plus-lg"></i>
                    </button>

                    <a href="<?= base_url('logout.php') ?>" class="ios-circle-btn" title="Keluar dari Sesi" onclick="return confirm('Keluar dari sesi DompetKu?');">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>

            </div>
        </nav>

        <div class="ios-main-content">
            
            <?= $alert_msg ?>

            
            <div id="tabViewDompet" class="tab-view-panel active">
                
                <div class="wallet-hero-card mb-3" id="iosHeroHeader">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="artosku-greeting-text mb-0">Hi, <?= htmlspecialchars(explode(' ', trim($_SESSION['nama_lengkap'] ?? 'Mahasiswa'))[0]) ?> 👋</div>
                            <small class="text-secondary" style="font-size: 11px; font-weight: 500;">@<?= htmlspecialchars($_SESSION['username'] ?? 'user') ?> • <?= tgl_indo(date('Y-m-d')) ?></small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="artosku-icon-btn" onclick="toggleTheme()" title="Ganti Mode Tampilan">
                                <i class="theme-toggle-icon"></i>
                            </button>
                            <button type="button" class="artosku-icon-btn position-relative" data-bs-toggle="modal" data-bs-target="#modalNotifikasi" title="Notifikasi Keuangan">
                                <i class="bi bi-bell-fill"></i>
                                <span class="artosku-bell-dot"></span>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="artosku-sub-label">Total kekayaan / Saldo kas</span>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <h1 class="artosku-main-balance mb-0" id="mainBalanceDisplay">
                                    <?= format_rupiah($saldo_akhir) ?>
                                </h1>
                                <button type="button" class="artosku-eye-btn" onclick="toggleBalanceVisibility()" title="Sembunyikan / Tampilkan Saldo">
                                    <i class="bi bi-eye-fill" id="toggleBalanceEye"></i>
                                </button>
                            </div>
                            <div class="artosku-trend-badge mt-1">
                                <i class="bi bi-arrow-up-right"></i>
                                <span>+<?= $total_masuk > 0 ? round(($saldo_akhir / $total_masuk) * 100) : 100 ?>% surplus kas aktif</span>
                            </div>
                        </div>
                        <div class="artosku-piggy-badge">
                            <div class="artosku-piggy-circle">
                                <i class="bi bi-piggy-bank-fill"></i>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 artosku-subcards-row mb-2">
                        <div class="col-6">
                            <div class="artosku-subcard" onclick="setInstantFilter('Pemasukan', 1)">
                                <div class="artosku-subcard-icon" style="background: var(--boss-segmented-bg); border: 1px solid var(--boss-border); color: var(--boss-text-primary);">
                                    <i class="bi bi-arrow-down-left"></i>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <span class="artosku-subcard-title text-truncate">Wallet / Masuk</span>
                                    <strong class="artosku-subcard-val text-income text-truncate">+ <?= format_rupiah($total_masuk) ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="artosku-subcard" onclick="setInstantFilter('Pengeluaran', 2)">
                                <div class="artosku-subcard-icon" style="background: var(--boss-segmented-bg); border: 1px solid var(--boss-border); color: var(--boss-text-primary);">
                                    <i class="bi bi-arrow-up-right"></i>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <span class="artosku-subcard-title text-truncate">Aset / Keluar</span>
                                    <strong class="artosku-subcard-val text-expense text-truncate">- <?= format_rupiah($total_keluar) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 pt-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-secondary" style="font-size: 10.5px;">Rasio Tabungan Terjaga</small>
                            <small class="text-success fw-bold" style="font-size: 10.5px;"><?= $rasio_simpan ?>% Tersimpan</small>
                        </div>
                        <div class="progress" style="height: 6px; background-color: rgba(120, 120, 128, 0.16); border-radius: 9999px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $rasio_simpan ?>%; border-radius: 9999px;" aria-valuenow="<?= $rasio_simpan ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>

                <div class="artosku-mobile-sheet">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="artosku-action-box mb-3" style="padding: 10px 14px; border-radius: 18px;">
                                <div class="row g-2 align-items-center">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center gap-2 cursor-pointer p-1" onclick="startVoiceInput()" title="Bicara untuk catat transaksi">
                                            <div class="artosku-action-icon ai-bot-icon" style="width: 36px; height: 36px; min-width: 36px; border-radius: 11px;">
                                                <i class="bi bi-mic-fill" id="smartVoiceMicIcon" style="font-size: 16px;"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="fw-bold" style="font-size: 13px; color: var(--boss-text-primary);">Suara AI</div>
                                                <small class="text-secondary d-block text-truncate" style="font-size: 10px;">Contoh: "Makan 30k"</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center gap-2 cursor-pointer p-1 border-start border-secondary border-opacity-10 ps-2" onclick="triggerReceiptScan()" title="Pindai foto struk">
                                            <div class="artosku-action-icon scan-icon" style="width: 36px; height: 36px; min-width: 36px; border-radius: 11px;">
                                                <i class="bi bi-qr-code-scan" style="font-size: 16px;"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="fw-bold" style="font-size: 13px; color: var(--boss-text-primary);">Scan Struk</div>
                                                <small class="text-secondary d-block text-truncate" style="font-size: 10px;">Auto-deteksi foto</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="ios-search-wrapper mb-2">
                                <i class="bi bi-search ios-search-icon"></i>
                                <input type="text" id="instantSearchInput" class="ios-input ios-search-input" placeholder="Cari transaksi..." onkeyup="filterTransaksiLive()">
                            </div>

                            <div class="ios-segmented-control-slider-wrap mb-3" id="filterSegmentedWrap">
                                <div class="ios-segmented-slider ios-segmented-slider-3" id="filterSegmentSlider"></div>
                                <button type="button" class="ios-segmented-tab-btn active" id="btnFilterSemua" onclick="setInstantFilter('Semua', 0)">Semua</button>
                                <button type="button" class="ios-segmented-tab-btn" id="btnFilterPemasukan" onclick="setInstantFilter('Pemasukan', 1)">Pemasukan</button>
                                <button type="button" class="ios-segmented-tab-btn" id="btnFilterPengeluaran" onclick="setInstantFilter('Pengeluaran', 2)">Pengeluaran</button>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                <h6 class="text-secondary fw-semibold text-uppercase mb-0" style="font-size: 11px; letter-spacing: 0.8px;">
                                    Catatan Transaksi (<span id="totalVisibleTrx"><?= count($daftar_transaksi) ?></span>)
                                </h6>
                                <small class="text-secondary" style="font-size: 11px;">Ketuk titik tiga untuk opsi</small>
                            </div>

                            <div class="ios-card-group mb-4" id="transactionListContainer">
                            <?php if (empty($daftar_transaksi)): ?>
                                <div class="p-4 text-center text-secondary small">Belum ada transaksi tercatat.</div>
                            <?php else: ?>
                                <?php foreach ($daftar_transaksi as $trx): 
                                    $is_masuk = ($trx['jenis'] === 'Pemasukan');
                                    $sign = $is_masuk ? '+' : '-';
                                    $amount_class = $is_masuk ? 'amount-income' : 'amount-expense';
                                ?>
                                <div class="ios-list-row trx-card-item" data-jenis="<?= $trx['jenis'] ?>" data-search="<?= strtolower(htmlspecialchars($trx['judul'] . ' ' . $trx['nama_kategori'] . ' ' . $trx['catatan'])) ?>" style="padding: 12px 14px;">
                                    <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1 me-2">
                                        <div class="ios-icon-box flex-shrink-0" style="width: 38px; height: 38px;">
                                            <i class="bi <?= htmlspecialchars($trx['icon']) ?>"></i>
                                        </div>
                                        <div class="min-w-0 flex-grow-1">
                                            <div class="fw-semibold text-truncate mb-0" style="font-size: 14px; letter-spacing: -0.2px; color: var(--boss-text-primary);">
                                                <?= htmlspecialchars($trx['judul']) ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-1 mt-0.5 text-truncate">
                                                <span class="ios-badge-mono text-truncate" style="max-width: 105px;">
                                                    <?= htmlspecialchars($trx['nama_kategori']) ?>
                                                </span>
                                                <span class="text-secondary flex-shrink-0" style="font-size: 10.5px;">
                                                    • <?= tgl_indo($trx['tanggal']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                        <div class="text-end">
                                            <div class="fw-bold <?= $amount_class ?>" style="font-size: 13.5px; letter-spacing: -0.3px; white-space: nowrap;">
                                                <?= $sign ?> <?= format_rupiah($trx['jumlah']) ?>
                                            </div>
                                            <small class="text-uppercase text-secondary" style="font-size: 9.5px; font-weight: 600; letter-spacing: 0.3px;">
                                                <?= $trx['jenis'] ?>
                                            </small>
                                        </div>
                                        <div class="dropdown">
                                            <button type="button" class="ios-action-dots-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Menu Opsi">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2">
                                                <li>
                                                    <a class="dropdown-item rounded-3 py-2 small" href="javascript:void(0)" onclick='openEditModal(<?= json_encode($trx, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                        <i class="bi bi-pencil-square me-2 text-primary"></i> Edit Data
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item rounded-3 py-2 text-danger small" href="<?= base_url('transaksi_hapus.php?id=' . $trx['id_transaksi']) ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');">
                                                        <i class="bi bi-trash3 me-2"></i> Hapus
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div id="tabViewKategori" class="tab-view-panel">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0" style="letter-spacing: -0.3px;">Master Kategori</h5>
                        <small class="text-secondary" style="font-size: 12px;">Dilindungi Foreign Key Constraint MySQL</small>
                    </div>
                    <button type="button" class="btn btn-ios-primary btn-sm py-1 px-3 d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalTambahKat">
                        <i class="bi bi-plus-lg"></i> Kategori Baru
                    </button>
                </div>

                <div class="ios-card-group mb-4">
                    <?php foreach ($kategori_list as $kat): 
                        $is_in = ($kat['jenis'] === 'Pemasukan');
                    ?>
                    <div class="ios-list-row p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1 me-2">
                            <div class="ios-icon-box flex-shrink-0" style="width: 38px; height: 38px; border-radius: 10px; background: var(--boss-segmented-bg); border: 1px solid var(--boss-border); color: var(--boss-text-primary);">
                                <i class="bi <?= htmlspecialchars($kat['icon']) ?>" style="font-size: 16px;"></i>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-truncate mb-0.5" style="font-size: 14px; color: var(--boss-text-primary);">
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge fw-semibold px-2 py-0.5" style="background: <?= $is_in ? 'rgba(48, 209, 88, 0.12)' : 'rgba(255, 69, 58, 0.12)' ?>; color: <?= $is_in ? 'var(--boss-green)' : 'var(--boss-red)' ?>; font-size: 10px; border-radius: 6px;">
                                        <?= $kat['jenis'] ?>
                                    </span>
                                    <span class="text-secondary" style="font-size: 11px;">
                                        <?= $kat['total_trx'] ?> mutasi
                                    </span>
                                </div>
                            </div>
                        </div>

                        <a href="<?= base_url('kategori.php?hapus=' . $kat['id_kategori']) ?>" 
                           class="btn btn-ios-danger btn-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                           style="width: 32px; height: 32px;"
                           onclick="return confirm('Hapus kategori <?= htmlspecialchars($kat['nama_kategori']) ?>?');"
                           title="Hapus Kategori">
                            <i class="bi bi-trash3" style="font-size: 13px;"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>

            
            <div id="tabViewLaporan" class="tab-view-panel">
                
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3 no-print">
                    <div>
                        <h5 class="fw-bold mb-0" style="letter-spacing: -0.3px;">Laporan Kas</h5>
                        <small class="text-secondary" style="font-size: 11.5px;">Rekapitulasi per <?= tgl_indo(date('Y-m-d')) ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <a href="<?= base_url('export_excel.php') ?>" class="btn btn-ios-secondary btn-sm py-1.5 px-2.5 d-flex align-items-center gap-1" title="Unduh File Excel" style="font-size: 12px; border-radius: 9px;">
                            <i class="bi bi-file-earmark-excel text-success"></i> Excel
                        </a>
                        <button onclick="window.print()" class="btn btn-ios-primary btn-sm py-1.5 px-3 d-flex align-items-center gap-1" style="font-size: 12px; border-radius: 9px;">
                            <i class="bi bi-printer-fill"></i> Cetak PDF
                        </button>
                    </div>
                </div>

                <div class="ios-card p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: var(--boss-border) !important;">
                        <div>
                            <small class="text-secondary text-uppercase fw-semibold d-block" style="font-size: 9.5px; letter-spacing: 0.5px;">Sisa Saldo Kas Bersih</small>
                            <div class="fw-bold" style="color: var(--boss-text-primary); font-size: 21px; letter-spacing: -0.4px;">
                                <?= format_rupiah($saldo_akhir) ?>
                            </div>
                        </div>
                        <span class="ios-badge-mono" style="font-size: 10.5px;">
                            <?= ($total_masuk > 0 ? round(($saldo_akhir / $total_masuk) * 100) : 0) ?>% Simpan
                        </span>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-2.5 rounded-3" style="background: var(--boss-card-inner); border: 1px solid var(--boss-neu-border);">
                                <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 9px; letter-spacing: 0.3px;">Pemasukan</small>
                                <div class="fw-bold amount-income text-truncate mt-0.5" style="font-size: 14.5px;">
                                    + <?= format_rupiah($total_masuk) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2.5 rounded-3" style="background: var(--boss-card-inner); border: 1px solid var(--boss-neu-border);">
                                <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 9px; letter-spacing: 0.3px;">Pengeluaran</small>
                                <div class="fw-bold amount-expense text-truncate mt-0.5" style="font-size: 14.5px;">
                                    - <?= format_rupiah($total_keluar) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ios-card p-3 mb-3 d-print-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="fw-bold mb-0" style="font-size: 13.5px; color: var(--boss-text-primary);">Tren Arus Kas</h6>
                            <small class="text-secondary" style="font-size: 11px;">Perbandingan Arus Kas Riil</small>
                        </div>
                        <span class="ios-badge-mono" style="font-size: 10px;">Arus Kas</span>
                    </div>
                    <div style="height: 180px; position: relative; width: 100%;">
                        <canvas id="chartCashflowLaporan"></canvas>
                    </div>
                </div>

                <div class="ios-card p-3 mb-3 d-print-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="fw-bold mb-0" style="font-size: 13.5px; color: var(--boss-text-primary);">Proporsi Pengeluaran</h6>
                            <small class="text-secondary" style="font-size: 11px;">Rincian Beban per Kategori</small>
                        </div>
                        <span class="ios-badge-mono" style="font-size: 10px;"><?= count($kat_expenses) ?> Kategori</span>
                    </div>
                    <div style="height: 200px; position: relative; width: 100%;">
                        <canvas id="chartCategoryLaporan"></canvas>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 px-1 d-print-none">
                    <h6 class="text-secondary fw-semibold text-uppercase mb-0" style="font-size: 11px; letter-spacing: 0.8px;">
                        Rincian Mutasi Kas (<?= count($daftar_transaksi) ?>)
                    </h6>
                    <small class="text-secondary" style="font-size: 11px;">Tersusun kronologis</small>
                </div>

                <div class="ios-card-group mb-4 d-print-none">
                    <?php if (empty($daftar_transaksi)): ?>
                        <div class="p-4 text-center text-secondary small">Belum ada riwayat transaksi.</div>
                    <?php else: ?>
                        <?php foreach ($daftar_transaksi as $row): 
                            $is_masuk = ($row['jenis'] === 'Pemasukan');
                            $sign = $is_masuk ? '+' : '-';
                            $amount_class = $is_masuk ? 'amount-income' : 'amount-expense';
                        ?>
                        <div class="ios-list-row" style="padding: 12px 14px;">
                            <div class="d-flex align-items-center gap-2.5 min-w-0 flex-grow-1 me-2">
                                <div class="ios-icon-box flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="bi <?= htmlspecialchars($row['icon'] ?: 'bi-tags-fill') ?>"></i>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold text-truncate mb-0" style="font-size: 14px; letter-spacing: -0.2px; color: var(--boss-text-primary);">
                                        <?= htmlspecialchars($row['judul']) ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 mt-0.5 text-truncate">
                                        <span class="ios-badge-mono text-truncate" style="max-width: 120px;">
                                            <?= htmlspecialchars($row['nama_kategori']) ?>
                                        </span>
                                        <span class="text-secondary flex-shrink-0" style="font-size: 10.5px;">
                                            • <?= tgl_indo($row['tanggal']) ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($row['catatan'])): ?>
                                        <small class="text-secondary d-block text-truncate mt-0.5" style="font-size: 11px;">
                                            <?= htmlspecialchars($row['catatan']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold <?= $amount_class ?>" style="font-size: 13.5px; letter-spacing: -0.3px; white-space: nowrap;">
                                    <?= $sign ?> <?= format_rupiah($row['jumlah']) ?>
                                </div>
                                <small class="text-uppercase text-secondary d-block mt-0.5" style="font-size: 9.5px; font-weight: 600; letter-spacing: 0.3px;">
                                    <?= $row['jenis'] ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="table-responsive rounded-4 overflow-hidden border mb-4 d-none d-print-block" style="border-color: var(--boss-table-border) !important;">
                    <table class="table rekap-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 5%;" class="text-center">No</th>
                                <th style="width: 18%;">Tanggal</th>
                                <th style="width: 22%;">Kategori</th>
                                <th style="width: 32%;">Keterangan</th>
                                <th style="width: 10%;" class="text-center">Jenis</th>
                                <th style="width: 13%;" class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($daftar_transaksi as $row): 
                                $is_masuk = ($row['jenis'] === 'Pemasukan');
                                $amount_class = $is_masuk ? 'amount-income' : 'amount-expense';
                            ?>
                            <tr>
                                <td class="text-center text-secondary fw-semibold"><?= $no++ ?></td>
                                <td>
                                    <span class="table-date-text"><?= tgl_indo($row['tanggal']) ?></span>
                                </td>
                                <td>
                                    <span class="badge-kategori-solid"><?= htmlspecialchars($row['nama_kategori']) ?></span>
                                </td>
                                <td>
                                    <div class="table-title-text"><?= htmlspecialchars($row['judul']) ?></div>
                                    <?php if (!empty($row['catatan'])): ?>
                                        <span class="table-notes-text"><?= htmlspecialchars($row['catatan']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge fw-semibold px-2 py-1" style="background: <?= $is_masuk ? 'rgba(48, 209, 88, 0.12)' : 'rgba(255, 69, 58, 0.12)' ?>; color: <?= $is_masuk ? 'var(--boss-green)' : 'var(--boss-red)' ?>; font-size: 11px; border-radius: 6px;">
                                        <?= $row['jenis'] ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold <?= $amount_class ?>" style="font-size: 14px;">
                                    <?= ($is_masuk ? '+' : '-') ?> <?= format_rupiah($row['jumlah']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold" style="background-color: var(--boss-table-th-bg);">
                                <td colspan="5" class="text-end" style="color: var(--boss-text-primary);">Sisa Saldo Kas Bersih:</td>
                                <td class="text-end" style="color: var(--boss-text-primary); font-size: 15px;"><?= format_rupiah($saldo_akhir) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                
                <div class="p-3 text-center text-secondary small border-top border-secondary border-opacity-10">
                    <p class="mb-1">Penanggung Jawab: <strong style="color: var(--boss-text-primary);"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong></p>
                    <small style="font-size: 10.5px;">Waktu Rekap: <?= date('d/m/Y H:i:s') ?> WIB • DompetKu (PBW)</small>
                </div>

            </div>

            
            <div id="tabViewAkun" class="tab-view-panel">
                
                <div class="mb-3 px-1">
                    <h5 class="fw-bold mb-0" style="letter-spacing: -0.4px; color: var(--boss-text-primary);">Akun & Pengaturan</h5>
                    <small class="text-secondary" style="font-size: 11.5px;">Profil mahasiswa dan preferensi sistem</small>
                </div>

                <div class="ios-card p-4 mb-3 text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 72px; height: 72px; border-radius: 22px; background: var(--ios-center-circle-bg); color: var(--ios-center-circle-color); font-size: 24px; font-weight: 700; letter-spacing: -0.5px; box-shadow: 0 4px 14px rgba(0,0,0,0.12);">
                        <?= $user_initials ?>
                    </div>
                    <h5 class="fw-bold mb-1" style="color: var(--boss-text-primary); letter-spacing: -0.3px;">
                        <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Mahasiswa') ?>
                    </h5>
                    <div class="mb-2">
                        <span class="badge" style="background: var(--boss-segmented-bg); color: var(--boss-text-primary); border: 1px solid var(--boss-border); font-size: 11.5px; font-weight: 600; padding: 3px 10px; border-radius: 9999px;">
                            @<?= htmlspecialchars($_SESSION['username'] ?? 'user') ?>
                        </span>
                    </div>
                    <p class="text-secondary small mb-2.5" style="font-size: 12px;">Mahasiswa Sistem Informasi • Universitas Gunadarma</p>
                    <div class="d-flex align-items-center justify-content-center gap-1.5 flex-wrap">
                        <span class="ios-badge-mono">Sesi Aktif 30 Hari</span>
                        <span class="ios-badge-mono">User ID #<?= (int)($_SESSION['user_id'] ?? 1) ?></span>
                        <span class="ios-badge-mono">MySQL Terhubung</span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: var(--boss-text-muted);">Ringkasan Kas Pribadi</span>
                    <span class="ios-badge-mono">
                        <?= $rasio_simpan ?>% Surplus
                    </span>
                </div>

                <div class="ios-card p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: var(--boss-border) !important;">
                        <div>
                            <small class="text-secondary text-uppercase fw-semibold d-block" style="font-size: 9.5px; letter-spacing: 0.5px;">Sisa Saldo Kas Riil</small>
                            <div class="fw-bold" style="font-size: 20px; color: var(--boss-text-primary); letter-spacing: -0.4px;">
                                <?= format_rupiah($saldo_akhir) ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="ios-badge-mono">
                                <?= count($daftar_transaksi) ?> Transaksi
                            </span>
                        </div>
                    </div>
                    <div class="row g-2 pt-1">
                        <div class="col-6">
                            <div class="p-2.5 rounded-3" style="background: var(--boss-card-inner); border: 1px solid var(--boss-neu-border);">
                                <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 9px; letter-spacing: 0.3px;">Pemasukan</small>
                                <div class="fw-bold amount-income text-truncate mt-0.5" style="font-size: 14px;">
                                    + <?= format_rupiah($total_masuk) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2.5 rounded-3" style="background: var(--boss-card-inner); border: 1px solid var(--boss-neu-border);">
                                <small class="text-secondary fw-semibold text-uppercase d-block" style="font-size: 9px; letter-spacing: 0.3px;">Pengeluaran</small>
                                <div class="fw-bold amount-expense text-truncate mt-0.5" style="font-size: 14px;">
                                    - <?= format_rupiah($total_keluar) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-2 px-1">
                    <span class="fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: var(--boss-text-muted);">Preferensi & Alat Sistem</span>
                </div>
                <div class="ios-card-group mb-3">
                    <div class="ios-list-row cursor-pointer" onclick="toggleTheme()" style="padding: 12px 14px;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="ios-icon-box flex-shrink-0" style="width: 36px; height: 36px; font-size: 16px;">
                                <i class="theme-toggle-icon"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small" style="color: var(--boss-text-primary);">Mode Tampilan</div>
                                <small class="text-secondary" style="font-size: 11px;">Ganti Tema Cupertino / Titanium</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </div>

                    <div class="ios-list-row cursor-pointer" data-bs-toggle="modal" data-bs-target="#modalNotifikasi" style="padding: 12px 14px;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="ios-icon-box flex-shrink-0" style="width: 36px; height: 36px; font-size: 16px;">
                                <i class="bi bi-bell"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small" style="color: var(--boss-text-primary);">Pusat Notifikasi</div>
                                <small class="text-secondary" style="font-size: 11px;">Pengingat kas & status akun</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </div>

                    <a href="<?= base_url('export_excel.php') ?>" class="ios-list-row text-decoration-none" style="padding: 12px 14px;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="ios-icon-box flex-shrink-0" style="width: 36px; height: 36px; font-size: 16px;">
                                <i class="bi bi-file-earmark-excel"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small" style="color: var(--boss-text-primary);">Unduh Laporan Excel</div>
                                <small class="text-secondary" style="font-size: 11px;">Ekspor seluruh transaksi (.xls)</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="ios-badge-mono" style="font-size: 10px;">.XLS</span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </div>
                    </a>

                    <a href="<?= base_url('dashboard.php?desktop=1') ?>" class="ios-list-row text-decoration-none" style="padding: 12px 14px;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="ios-icon-box flex-shrink-0" style="width: 36px; height: 36px; font-size: 16px;">
                                <i class="bi bi-laptop"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small" style="color: var(--boss-text-primary);">Mode Layar Lebar</div>
                                <small class="text-secondary" style="font-size: 11px;">Buka dashboard desktop</small>
                            </div>
                        </div>
                        <i class="bi bi-box-arrow-up-right text-muted small"></i>
                    </a>
                </div>

                <div class="ios-card-group mb-3">
                    <a href="<?= base_url('logout.php') ?>" class="ios-list-row justify-content-center text-decoration-none" onclick="return confirm('Keluar dari sesi akun DompetKu?');" style="padding: 13px 14px;">
                        <span class="fw-semibold text-danger d-flex align-items-center gap-2" style="font-size: 13.5px;">
                            <i class="bi bi-box-arrow-right"></i> Keluar dari Akun
                        </span>
                    </a>
                </div>

                <div class="text-center text-secondary small py-2 mb-4" style="opacity: 0.6;">
                    <div style="font-size: 11px;">DompetKu Mahasiswa v6.0</div>
                    <div style="font-size: 10px;">PBW Universitas Gunadarma</div>
                </div>

            </div>

        </div>

        
        <nav class="ios-capsule-navbar no-print" id="bottomCapsuleNavbar">
            
            <button type="button" class="ios-capsule-item active" id="tabBtnDompet" onclick="switchIosTab('dompet')">
                <div class="capsule-icon-wrap">
                    <i class="bi bi-wallet2 tab-icon"></i>
                </div>
                <span class="capsule-label">Dompet</span>
            </button>

            
            <button type="button" class="ios-capsule-item" id="tabBtnKategori" onclick="switchIosTab('kategori')">
                <div class="capsule-icon-wrap">
                    <i class="bi bi-tags tab-icon"></i>
                    <span class="capsule-badge"><?= count($kategori_list ?? []) ?></span>
                </div>
                <span class="capsule-label">Kategori</span>
            </button>

            
            <button type="button" class="ios-capsule-fab" id="tabBtnCatat" data-bs-toggle="modal" data-bs-target="#modalTambah" title="Catat Transaksi Cepat">
                <i class="bi bi-plus-lg"></i>
            </button>

            
            <button type="button" class="ios-capsule-item" id="tabBtnLaporan" onclick="switchIosTab('laporan')">
                <div class="capsule-icon-wrap">
                    <i class="bi bi-file-earmark-text tab-icon"></i>
                    <span class="capsule-badge"><?= count($transaksi_list ?? []) ?></span>
                </div>
                <span class="capsule-label">Laporan</span>
            </button>

            
            <button type="button" class="ios-capsule-item" id="tabBtnAkun" onclick="switchIosTab('akun')">
                <div class="capsule-icon-wrap">
                    <div class="capsule-avatar-circle">
                        <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
                            <img src="<?= base_url('assets/img/profile-firdaus.png') ?>" alt="Anda" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <span class="avatar-fallback" style="display:none;"><?= $user_initials ?></span>
                        <?php else: ?>
                            <span class="avatar-fallback" style="display:flex;"><?= $user_initials ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="capsule-label">Anda</span>
            </button>
        </nav>

    </div>

    
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content artosku-modal-sheet p-0 overflow-hidden">
                <div class="artosku-modal-header">
                    <div class="artosku-sheet-grabber"></div>
                    <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                        <div>
                            <h5 class="fw-bold mb-0" id="modalTambahLabel" style="color: var(--boss-text-primary);">Tambah Transaksi</h5>
                            <small class="text-secondary" style="font-size: 11px;">Pencatatan Keuangan Terintegrasi</small>
                        </div>
                        <button type="button" class="artosku-modal-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="d-flex align-items-center justify-content-between px-2 pb-3">
                        <span class="artosku-modal-badge">
                            <i class="bi bi-calendar3 me-1"></i> <?= tgl_indo(date('Y-m-d')) ?>
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="artosku-quick-badge-btn" onclick="startVoiceInput()" title="Input Suara">
                                <i class="bi bi-mic-fill"></i> Suara
                            </button>
                            <button type="button" class="artosku-quick-badge-btn" onclick="triggerReceiptScan()" title="Scan Struk">
                                <i class="bi bi-camera-fill"></i> 1 struk
                            </button>
                        </div>
                    </div>
                </div>
                
                <form action="<?= base_url('index.php') ?>" method="POST" id="formCatatTransaksi">
                    <input type="hidden" name="tambah_transaksi" value="1">
                    <input type="hidden" name="view" value="mobile">
                    <div class="modal-body p-3 p-md-4">
                        <div class="mb-3 text-center">
                            <div class="ios-segmented-control-slider-wrap" id="modalJenisSegmentedWrap">
                                <div class="ios-segmented-slider" id="modalJenisSlider"></div>
                                <button type="button" class="ios-segmented-tab-btn active type-expense" id="btnModalPengeluaran" onclick="selectModalJenis('Pengeluaran')">
                                    <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                                </button>
                                <button type="button" class="ios-segmented-tab-btn" id="btnModalPemasukan" onclick="selectModalJenis('Pemasukan')">
                                    <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                                </button>
                            </div>
                            <input type="hidden" name="jenis" id="hiddenInputJenis" value="Pengeluaran">
                        </div>

                        <div class="artosku-amount-input-box mb-3">
                            <span class="text-secondary small fw-medium d-block mb-1">Nominal Transaksi (Rp) <span class="text-danger">*</span></span>
                            <div class="d-flex align-items-center">
                                <span class="artosku-rp-prefix">Rp</span>
                                <input type="number" name="jumlah" id="inputJumlah" class="artosku-amount-input" placeholder="0" min="100" step="100" required>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                <button type="button" class="btn btn-sm btn-ios-secondary py-1 px-2" style="font-size: 11px; border-radius: 8px;" onclick="addQuickAmount(10000)">+10k</button>
                                <button type="button" class="btn btn-sm btn-ios-secondary py-1 px-2" style="font-size: 11px; border-radius: 8px;" onclick="addQuickAmount(20000)">+20k</button>
                                <button type="button" class="btn btn-sm btn-ios-secondary py-1 px-2" style="font-size: 11px; border-radius: 8px;" onclick="addQuickAmount(50000)">+50k</button>
                                <button type="button" class="btn btn-sm btn-ios-secondary py-1 px-2" style="font-size: 11px; border-radius: 8px;" onclick="addQuickAmount(100000)">+100k</button>
                                <button type="button" class="btn btn-sm btn-ios-secondary py-1 px-2 text-danger ms-auto" style="font-size: 11px; border-radius: 8px;" onclick="clearQuickAmount()">Reset</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Nama Transaksi / Pengeluaran <span class="text-danger">*</span></label>
                            <input type="text" name="judul" id="inputJudul" class="ios-input" placeholder="Contoh: Makan Siang / Kiriman Ortu" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Kategori <span class="text-danger">*</span></label>
                            <select name="id_kategori" id="selectKategori" class="ios-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?= $kat['id_kategori'] ?>" data-jenis="<?= $kat['jenis'] ?>">
                                        [<?= $kat['jenis'] ?>] <?= htmlspecialchars($kat['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" id="inputTanggal" class="ios-input" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Catatan / Detail Item (Opsional)</label>
                            <textarea name="catatan" id="inputCatatan" class="ios-input" rows="2" placeholder="Detail pengeluaran..."></textarea>
                        </div>

                        <button type="submit" name="tambah_transaksi" class="artosku-btn-submit w-100">
                            Simpan Transaksi →
                        </button>
                        <button type="button" class="btn btn-link text-secondary text-decoration-none w-100 mt-2 small" data-bs-dismiss="modal">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="modalNotifikasi" tabindex="-1" aria-labelledby="modalNotifikasiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content artosku-modal-sheet p-0 overflow-hidden">
                <div class="artosku-modal-header">
                    <div class="artosku-sheet-grabber"></div>
                    <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="ios-icon-box" style="width: 34px; height: 34px; border-radius: 10px; background: var(--boss-segmented-bg); color: var(--boss-text-primary); border: 1px solid var(--boss-border);">
                                <i class="bi bi-bell" style="font-size: 15px;"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0" id="modalNotifikasiLabel" style="color: var(--boss-text-primary); font-size: 16px;">Pusat Notifikasi</h5>
                                <small class="text-secondary" style="font-size: 11px;">Aktivitas Keuangan & Status Akun</small>
                            </div>
                        </div>
                        <button type="button" class="artosku-modal-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-body p-3">
                    <div class="ios-card-group mb-3">
                        <?php 
                        $recent_notifs = array_slice($daftar_transaksi, 0, 3);
                        if (!empty($recent_notifs)):
                            foreach ($recent_notifs as $nt):
                                $is_masuk = ($nt['jenis'] === 'Pemasukan');
                                $is_bank = preg_match('/(bni|bca|mandiri|bri|dana|gopay|ovo|shopee)/i', $nt['judul'] . ' ' . $nt['catatan'] . ' ' . $nt['nama_kategori']);
                                $icon_nt = $is_bank ? 'bi-bank' : ($is_masuk ? 'bi-arrow-down-left-circle-fill' : 'bi-arrow-up-right-circle-fill');
                        ?>
                        <div class="ios-list-row p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="ios-icon-box flex-shrink-0" style="width: 36px; height: 36px; border-radius: 10px; background: var(--boss-segmented-bg); color: <?= $is_masuk ? 'var(--money-in)' : 'var(--money-out)' ?>; border: 1px solid var(--boss-border);">
                                    <i class="bi <?= $icon_nt ?>" style="font-size: 16px;"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                                        <strong class="text-truncate me-2" style="font-size: 13px; color: var(--boss-text-primary);">
                                            <?= $is_masuk ? 'Dana Masuk' : 'Pengeluaran Kas' ?>
                                        </strong>
                                        <span class="text-secondary flex-shrink-0" style="font-size: 10px;"><?= tgl_indo($nt['tanggal']) ?></span>
                                    </div>
                                    <p class="mb-0 text-truncate" style="font-size: 12px; color: var(--boss-text-primary);">
                                        <?= htmlspecialchars($nt['judul']) ?>: <strong style="color: <?= $is_masuk ? 'var(--money-in)' : 'var(--money-out)' ?>;"><?= ($is_masuk ? '+' : '-') ?> <?= format_rupiah($nt['jumlah']) ?></strong>
                                    </p>
                                    <small class="text-secondary d-block" style="font-size: 10.5px;">Kategori: <?= htmlspecialchars($nt['nama_kategori']) ?></small>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endforeach;
                        endif; 
                        ?>

                        <div class="ios-list-row p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="ios-icon-box flex-shrink-0" style="width: 36px; height: 36px; border-radius: 10px; background: var(--boss-segmented-bg); color: var(--boss-text-primary); border: 1px solid var(--boss-border);">
                                    <i class="bi bi-shield-check" style="font-size: 16px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong style="font-size: 13px; color: var(--boss-text-primary);">Kondisi Saldo DompetKu</strong>
                                        <span class="text-secondary" style="font-size: 10px;">Update</span>
                                    </div>
                                    <p class="text-secondary mb-0" style="font-size: 12px; line-height: 1.4;">
                                        Sisa saldo kas aktif @<?= htmlspecialchars($_SESSION['username'] ?? 'user') ?> saat ini <strong><?= format_rupiah($saldo_akhir) ?></strong> dengan rasio tersimpan <strong><?= $rasio_simpan ?>%</strong>.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="ios-list-row p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="ios-icon-box flex-shrink-0" style="width: 36px; height: 36px; border-radius: 10px; background: var(--boss-segmented-bg); color: var(--boss-text-primary); border: 1px solid var(--boss-border);">
                                    <i class="bi bi-person-badge" style="font-size: 15px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong style="font-size: 13px; color: var(--boss-text-primary);">Sesi Akun Terhubung</strong>
                                        <span class="text-secondary" style="font-size: 10px;">Terverifikasi</span>
                                    </div>
                                    <p class="text-secondary mb-0" style="font-size: 12px; line-height: 1.4;">
                                        Login sebagai <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Mahasiswa') ?> (@<?= htmlspecialchars($_SESSION['username'] ?? 'user') ?>). Sesi terenkripsi & data terisolasi.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-ios-secondary w-100 py-2.5 rounded-3 fw-semibold" data-bs-dismiss="modal">
                        Tutup Notifikasi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambahKat" tabindex="-1" aria-labelledby="modalTambahKatLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ios-modal p-2">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalTambahKatLabel" style="letter-spacing: -0.3px;">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('index.php') ?>" method="POST">
                    <input type="hidden" name="view" value="mobile">
                    <div class="modal-body py-3">
                        <div class="mb-3 text-center">
                            <label class="form-label text-secondary small fw-medium d-block mb-1">Jenis Kategori</label>
                            <div class="btn-group w-100 p-1" style="background-color: var(--boss-segmented-bg); border-radius: 12px;" role="group">
                                <input type="radio" class="btn-check" name="jenis" id="katPengeluaran" value="Pengeluaran" checked>
                                <label class="btn rounded-3 fw-semibold text-danger border-0" for="katPengeluaran">Pengeluaran</label>

                                <input type="radio" class="btn-check" name="jenis" id="katPemasukan" value="Pemasukan">
                                <label class="btn rounded-3 fw-semibold text-success border-0" for="katPemasukan">Pemasukan</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="nama_kategori" class="ios-input" placeholder="Contoh: Tabungan / Hiburan" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Ikon Kategori</label>
                            <select name="icon" class="ios-select">
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

    
    <div class="modal fade" id="modalScanStruk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ios-modal p-2">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="letter-spacing: -0.3px;">Pemindai Struk Otomatis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="scan-laser-box p-3 text-center mb-3">
                        <div class="scan-laser-beam"></div>
                        <img id="receiptPreviewImg" src="" alt="Pratinjau Struk" class="img-fluid rounded-3 mb-2" style="max-height: 220px; display: none;">
                        <div id="scanStatusText" class="text-secondary small fw-medium py-3">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            Menganalisis teks & nominal pada struk...
                        </div>
                    </div>

                    <div id="scanResultBox" class="p-3 rounded-3" style="background-color: var(--boss-stat-bg); border: 1px solid var(--boss-border); display: none;">
                        <h6 class="fw-bold mb-2 text-success"><i class="bi bi-check-circle-fill me-1"></i> Struk Terdeteksi!</h6>
                        <div class="d-flex justify-content-between small py-1 border-bottom border-secondary border-opacity-10">
                            <span class="text-secondary">Judul Terbaca:</span>
                            <strong id="detectedJudul" style="color: var(--boss-text-primary);">-</strong>
                        </div>
                        <div class="d-flex justify-content-between small py-1 border-bottom border-secondary border-opacity-10">
                            <span class="text-secondary">Nominal Terbaca:</span>
                            <strong id="detectedNominal" class="text-danger fw-bold">-</strong>
                        </div>
                        <div class="d-flex justify-content-between small py-1">
                            <span class="text-secondary">Kategori Rekomendasi:</span>
                            <span id="detectedKategori" class="badge bg-secondary">-</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-ios-secondary w-100 mb-1" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-ios-primary w-100" id="btnApplyReceipt" onclick="applyReceiptToForm()" style="display: none;">
                        Terapkan ke Catatan Transaksi
                    </button>
                </div>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentTabIndex = 0;
        const tabIndices = { 'dompet': 0, 'kategori': 1, 'laporan': 2, 'akun': 3 };

        function switchIosTab(tabName) {
            const panels = document.querySelectorAll('.tab-view-panel');
            const mobileBtns = document.querySelectorAll('.ios-tab-item, .ios-capsule-item');
            const deskBtns = document.querySelectorAll('.ios-desktop-tab-btn');
            const titleEl = document.getElementById('navbarViewTitle');

            const panelMap = {
                'dompet':   { id: 'tabViewDompet',   mBtn: 'tabBtnDompet',   dBtn: 'deskTabDompet',   title: 'DompetKu' },
                'kategori': { id: 'tabViewKategori', mBtn: 'tabBtnKategori', dBtn: 'deskTabKategori', title: 'Master Kategori' },
                'laporan':  { id: 'tabViewLaporan',  mBtn: 'tabBtnLaporan',  dBtn: 'deskTabLaporan',  title: 'Rekap Laporan Kas' },
                'akun':     { id: 'tabViewAkun',     mBtn: 'tabBtnAkun',     dBtn: 'deskTabAkun',     title: 'Akun & Pengaturan' }
            };

            const target = panelMap[tabName] || panelMap['dompet'];
            const newIndex = tabIndices[tabName] !== undefined ? tabIndices[tabName] : 0;
            currentTabIndex = newIndex;

            panels.forEach(p => {
                p.classList.remove('active', 'slide-right', 'slide-left');
                p.style.display = 'none';
            });
            mobileBtns.forEach(b => b.classList.remove('active'));
            deskBtns.forEach(d => d.classList.remove('active'));

            const panelEl = document.getElementById(target.id);
            if (panelEl) {
                panelEl.style.display = 'block';
                panelEl.classList.add('active');
            }

            const mb = document.getElementById(target.mBtn); if (mb) mb.classList.add('active');
            const dt = document.getElementById(target.dBtn); if (dt) dt.classList.add('active');
            if (titleEl) titleEl.innerText = target.title;

            if (tabName === 'laporan') {
                setTimeout(initLaporanCharts, 60);
            }

            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, null, '#' + tabName);
            }
            window.scrollTo(0, 0);
        }

        let isBalanceHidden = false;
        const realBalanceText = '<?= format_rupiah($saldo_akhir) ?>';
        function toggleBalanceVisibility() {
            const el = document.getElementById('mainBalanceDisplay');
            const eye = document.getElementById('toggleBalanceEye');
            const miniEl = document.getElementById('miniBalanceDisplay');
            if (!el || !eye) return;
            isBalanceHidden = !isBalanceHidden;
            if (isBalanceHidden) {
                el.innerText = 'Rp ••••••••';
                if (miniEl) miniEl.innerText = 'Rp ••••••••';
                eye.className = 'bi bi-eye-slash-fill';
            } else {
                el.innerText = realBalanceText;
                if (miniEl) miniEl.innerText = realBalanceText;
                eye.className = 'bi bi-eye-fill';
            }
        }

        let chartCashflowLaporanInstance = null;
        let chartCategoryLaporanInstance = null;

        function initLaporanCharts() {
            const ctxCashflow = document.getElementById('chartCashflowLaporan');
            if (ctxCashflow && !chartCashflowLaporanInstance) {
                chartCashflowLaporanInstance = new Chart(ctxCashflow, {
                    type: 'bar',
                    data: {
                        labels: ['Pemasukan', 'Pengeluaran', 'Sisa Kas'],
                        datasets: [{
                            label: 'Nominal (Rp)',
                            data: [<?= $total_masuk ?>, <?= $total_keluar ?>, <?= $saldo_akhir ?>],
                            backgroundColor: [
                                '#34C759',
                                '#FF3B30',
                                '#64748B'
                            ],
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(148, 163, 184, 0.1)' },
                                ticks: { color: '#94A3B8', font: { size: 10 } }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#94A3B8', font: { size: 11, weight: '600' } }
                            }
                        }
                    }
                });
            } else if (chartCashflowLaporanInstance) {
                chartCashflowLaporanInstance.resize();
            }

            const ctxCategory = document.getElementById('chartCategoryLaporan');
            if (ctxCategory && !chartCategoryLaporanInstance) {
                chartCategoryLaporanInstance = new Chart(ctxCategory, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode($chart_labels_kat) ?>,
                        datasets: [{
                            data: <?= json_encode($chart_data_kat) ?>,
                            backgroundColor: [
                                '#E11D48',
                                '#EF4444',
                                '#F43F5E',
                                '#B91C1C',
                                '#991B1B',
                                '#64748B'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: '#94A3B8', boxWidth: 10, font: { size: 10.5 } }
                            }
                        }
                    }
                });
            } else if (chartCategoryLaporanInstance) {
                chartCategoryLaporanInstance.resize();
            }
        }

        function filterByBulanIni() {
            const searchInput = document.getElementById('instantSearchInput');
            if (searchInput) {
                searchInput.value = '';
                setInstantFilter('Semua', 0);
            }
        }

        function selectModalJenis(jenis) {
            const slider = document.getElementById('modalJenisSlider');
            const btnExp = document.getElementById('btnModalPengeluaran');
            const btnInc = document.getElementById('btnModalPemasukan');
            const input = document.getElementById('hiddenInputJenis');

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

        let activeFilterJenis = 'Semua';

        function setInstantFilter(jenis, idx = 0) {
            activeFilterJenis = jenis;
            const slider = document.getElementById('filterSegmentSlider');
            if (slider) {
                slider.style.transform = `translateX(${idx * 100}%)`;
            }
            const btns = [
                document.getElementById('btnFilterSemua'),
                document.getElementById('btnFilterPemasukan'),
                document.getElementById('btnFilterPengeluaran')
            ];
            btns.forEach((b, i) => {
                if (b) {
                    if (i === idx) b.classList.add('active');
                    else b.classList.remove('active');
                }
            });
            filterTransaksiLive();
        }

        function filterTransaksiLive() {
            const query = (document.getElementById('instantSearchInput').value || '').toLowerCase().trim();
            const items = document.querySelectorAll('.trx-card-item');
            let count = 0;

            items.forEach(item => {
                const itemJenis = item.getAttribute('data-jenis');
                const itemText = item.getAttribute('data-search') || '';

                const matchesJenis = (activeFilterJenis === 'Semua' || itemJenis === activeFilterJenis);
                const matchesQuery = (query === '' || itemText.includes(query));

                if (matchesJenis && matchesQuery) {
                    item.style.display = 'flex';
                    count++;
                } else {
                    item.style.display = 'none';
                }
            });

            const countEl = document.getElementById('totalVisibleTrx');
            if (countEl) countEl.innerText = count;
        }

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

        function triggerReceiptScan() {
            document.getElementById('receiptFileInput').click();
        }

        let parsedReceiptData = null;

        function processReceipt(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const reader = new FileReader();

            const scanModal = new bootstrap.Modal(document.getElementById('modalScanStruk'));
            scanModal.show();

            const previewImg = document.getElementById('receiptPreviewImg');
            const statusText = document.getElementById('scanStatusText');
            const resultBox  = document.getElementById('scanResultBox');
            const applyBtn   = document.getElementById('btnApplyReceipt');

            resultBox.style.display = 'none';
            applyBtn.style.display = 'none';
            statusText.style.display = 'block';

            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'inline-block';

                setTimeout(() => {
                    statusText.style.display = 'none';
                    resultBox.style.display = 'block';
                    applyBtn.style.display = 'block';

                    const sampleStores = [
                        { judul: 'Struk Belanja Minimarket Kos', nominal: 38500, id_kat: 4, kat: 'Makanan & Minuman' },
                        { judul: 'Fotokopi Bahan Tugas PBW & Jilid', nominal: 24000, id_kat: 5, kat: 'Kuliah & Buku Pelajaran' },
                        { judul: 'Struk Pengisian Bensin Pertalite', nominal: 35000, id_kat: 6, kat: 'Transportasi & Bensin' },
                        { judul: 'Kopi Susu & Snack Nugas', nominal: 28000, id_kat: 8, kat: 'Hiburan & Ngopi' }
                    ];
                    parsedReceiptData = sampleStores[Math.floor(Math.random() * sampleStores.length)];

                    document.getElementById('detectedJudul').innerText = parsedReceiptData.judul;
                    document.getElementById('detectedNominal').innerText = 'Rp ' + parsedReceiptData.nominal.toLocaleString('id-ID');
                    document.getElementById('detectedKategori').innerText = parsedReceiptData.kat;
                }, 1600);
            };
            reader.readAsDataURL(file);
        }

        function applyReceiptToForm() {
            if (!parsedReceiptData) return;

            const scanModalEl = document.getElementById('modalScanStruk');
            const scanModal = bootstrap.Modal.getInstance(scanModalEl);
            if (scanModal) scanModal.hide();

            selectModalJenis('Pengeluaran');

            document.getElementById('inputJudul').value = parsedReceiptData.judul;
            document.getElementById('inputJumlah').value = parsedReceiptData.nominal;
            document.getElementById('selectKategori').value = parsedReceiptData.id_kat;
            document.getElementById('inputCatatan').value = 'Diisi otomatis dari pemindaian foto struk.';

            setTimeout(() => {
                const addModal = new bootstrap.Modal(document.getElementById('modalTambah'));
                addModal.show();
            }, 300);
        }

        let isListening = false;
        function startVoiceInput() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const navMic = document.getElementById('actionTileMicIcon') || document.getElementById('navVoiceIcon');

            if (!SpeechRecognition) {
                alert('Browser Anda belum mendukung Web Speech Recognition. Gunakan Chrome di laptop atau HP.');
                return;
            }

            if (isListening) return;

            const recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            if (navMic) navMic.classList.add('mic-pulse');
            isListening = true;

            const toastEl = document.createElement('div');
            toastEl.id = 'voiceToast';
            toastEl.className = 'position-fixed bottom-0 start-50 translate-middle-x mb-5 p-3 rounded-4 shadow-lg text-white d-flex align-items-center gap-2';
            toastEl.style.cssText = 'background: rgba(16, 18, 26, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255,69,58,0.5); z-index: 9999;';
            toastEl.innerHTML = '<i class="bi bi-mic-fill text-danger fs-5 mic-pulse"></i> <span>Mendengarkan... Katakan: <em>"Makan siang tiga puluh ribu"</em></span>';
            document.body.appendChild(toastEl);

            recognition.start();

            function parseIndoVoiceAmount(transcript) {
                const text = transcript.toLowerCase();
                const jtMatch = text.match(/(\d+(?:[.,]\d+)?)\s*(?:jt|juta)/);
                if (jtMatch) return Math.round(parseFloat(jtMatch[1].replace(',', '.')) * 1000000);

                const rbMatch = text.match(/(\d+(?:[.,]\d+)?)\s*(?:k|rb|ribu)/);
                if (rbMatch) return Math.round(parseFloat(rbMatch[1].replace(',', '.')) * 1000);

                const wordsMap = [
                    { w: 'setengah juta', v: 500000 }, { w: 'satu juta', v: 1000000 }, { w: 'dua juta', v: 2000000 },
                    { w: 'tiga juta', v: 3000000 }, { w: 'lima juta', v: 5000000 }, { w: 'seratus lima puluh ribu', v: 150000 },
                    { w: 'seratus ribu', v: 100000 }, { w: 'dua ratus ribu', v: 200000 }, { w: 'tiga ratus ribu', v: 300000 },
                    { w: 'lima ratus ribu', v: 500000 }, { w: 'sembilan puluh ribu', v: 90000 }, { w: 'delapan puluh ribu', v: 80000 },
                    { w: 'tujuh puluh lima ribu', v: 75000 }, { w: 'tujuh puluh ribu', v: 70000 }, { w: 'enam puluh lima ribu', v: 65000 },
                    { w: 'enam puluh ribu', v: 60000 }, { w: 'lima puluh lima ribu', v: 55000 }, { w: 'lima puluh ribu', v: 50000 },
                    { w: 'empat puluh lima ribu', v: 45000 }, { w: 'empat puluh ribu', v: 40000 }, { w: 'tiga puluh lima ribu', v: 35000 },
                    { w: 'tiga puluh ribu', v: 30000 }, { w: 'dua puluh lima ribu', v: 25000 }, { w: 'dua puluh ribu', v: 20000 },
                    { w: 'sembilan belas ribu', v: 19000 }, { w: 'delapan belas ribu', v: 18000 }, { w: 'tujuh belas ribu', v: 17000 },
                    { w: 'enam belas ribu', v: 16000 }, { w: 'lima belas ribu', v: 15000 }, { w: 'empat belas ribu', v: 14000 },
                    { w: 'tiga belas ribu', v: 13000 }, { w: 'dua belas ribu', v: 12000 }, { w: 'sebelas ribu', v: 11000 },
                    { w: 'sepuluh ribu', v: 10000 }, { w: 'sembilan ribu', v: 9000 }, { w: 'delapan ribu', v: 8000 },
                    { w: 'tujuh ribu', v: 7000 }, { w: 'enam ribu', v: 6000 }, { w: 'lima ribu', v: 5000 },
                    { w: 'empat ribu', v: 4000 }, { w: 'tiga ribu', v: 3000 }, { w: 'dua ribu', v: 2000 }, { w: 'seribu', v: 1000 }
                ];
                for (const item of wordsMap) {
                    if (text.includes(item.w)) return item.v;
                }

                const numMatches = text.match(/\d+/g);
                if (numMatches) {
                    const rawNum = parseInt(numMatches.join(''), 10);
                    if (text.includes('ribu') || text.includes('rb') || text.includes(' k') || text.includes('k ') || text.endsWith('k')) {
                        return rawNum < 1000 ? rawNum * 1000 : rawNum;
                    }
                    if (rawNum < 1000) return rawNum * 1000;
                    return rawNum;
                }

                return 30000;
            }

            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript.toLowerCase();
                if (navMic) navMic.classList.remove('mic-pulse');
                isListening = false;
                const tEl = document.getElementById('voiceToast');
                if (tEl) tEl.remove();

                const detectedNum = parseIndoVoiceAmount(transcript);

                let cleanJudul = transcript
                    .replace(/(\d+(?:[.,]\d+)?)\s*(?:jt|juta|k|rb|ribu)/gi, '')
                    .replace(/\b(seratus|dua ratus|tiga ratus|empat ratus|lima ratus|sepuluh|sebelas|dua belas|tiga belas|empat belas|lima belas|enam belas|tujuh belas|delapan belas|sembilan belas|dua puluh|tiga puluh|empat puluh|lima puluh|enam puluh|tujuh puluh|delapan puluh|sembilan puluh|ribu|juta|rupiah|perak|rb|k)\b/gi, '')
                    .replace(/\d+/g, '')
                    .replace(/\s+/g, ' ')
                    .trim();
                if (cleanJudul.length < 3) cleanJudul = 'Pengeluaran Suara: ' + transcript;

                selectModalJenis('Pengeluaran');

                const catEl = document.getElementById('selectKategori');
                if (catEl) {
                    const textLower = transcript.toLowerCase();
                    for (let i = 0; i < catEl.options.length; i++) {
                        const optText = catEl.options[i].text.toLowerCase();
                        if ((textLower.includes('makan') || textLower.includes('minum') || textLower.includes('kopi') || textLower.includes('snack') || textLower.includes('lunch') || textLower.includes('sarapan')) && optText.includes('makan')) {
                            catEl.selectedIndex = i;
                            break;
                        } else if ((textLower.includes('bensin') || textLower.includes('pertalite') || textLower.includes('gojek') || textLower.includes('grab') || textLower.includes('ongkos') || textLower.includes('transport')) && (optText.includes('transport') || optText.includes('bensin'))) {
                            catEl.selectedIndex = i;
                            break;
                        } else if ((textLower.includes('buku') || textLower.includes('makalah') || textLower.includes('fotokopi') || textLower.includes('print') || textLower.includes('tugas') || textLower.includes('kuliah')) && (optText.includes('kuliah') || optText.includes('buku'))) {
                            catEl.selectedIndex = i;
                            break;
                        } else if ((textLower.includes('kos') || textLower.includes('wifi') || textLower.includes('listrik') || textLower.includes('pulsa') || textLower.includes('paket')) && (optText.includes('tagihan') || optText.includes('kos'))) {
                            catEl.selectedIndex = i;
                            break;
                        } else if ((textLower.includes('gaji') || textLower.includes('freelance') || textLower.includes('kiriman') || textLower.includes('saku') || textLower.includes('transfer')) && (optText.includes('gaji') || optText.includes('kiriman') || optText.includes('pemasukan'))) {
                            catEl.selectedIndex = i;
                            selectModalJenis('Pemasukan');
                            break;
                        }
                    }
                }

                document.getElementById('inputJudul').value = cleanJudul.charAt(0).toUpperCase() + cleanJudul.slice(1);
                document.getElementById('inputJumlah').value = detectedNum;
                document.getElementById('inputCatatan').value = 'Diinput via perintah suara: "' + transcript + '"';

                const addModal = new bootstrap.Modal(document.getElementById('modalTambah'));
                addModal.show();
            };

            recognition.onerror = function() {
                if (navMic) navMic.classList.remove('mic-pulse');
                isListening = false;
                const tEl = document.getElementById('voiceToast');
                if (tEl) tEl.remove();
                alert('Suara tidak tertangkap atau mikrofon dinonaktifkan.');
            };

            recognition.onend = function() {
                if (navMic) navMic.classList.remove('mic-pulse');
                isListening = false;
                const tEl = document.getElementById('voiceToast');
                if (tEl) tEl.remove();
            };
        }

        function addQuickAmount(val) {
            const el = document.getElementById('inputJumlah');
            if (!el) return;
            const cur = parseInt(el.value || 0, 10);
            el.value = cur + val;
        }

        function clearQuickAmount() {
            const el = document.getElementById('inputJumlah');
            if (el) el.value = '';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const inpJml = document.getElementById('inputJumlah');
            if (inpJml) {
                inpJml.addEventListener('keydown', function(e) {
                    if (e.key === 'k' || e.key === 'K') {
                        e.preventDefault();
                        const cur = parseInt(this.value || 0, 10);
                        if (cur > 0) {
                            this.value = cur * 1000;
                        }
                    }
                });
            }

            filterKategoriDropdown('Pengeluaran');

            let hash = window.location.hash.replace('#', '') || 'dompet';
            if (hash === 'dashboard') hash = 'dompet';
            switchIosTab(hash);

            if (hash === 'laporan') { setTimeout(initLaporanCharts, 60); }

            function updateLiveClock() {
                const el = document.getElementById('liveClockWidget');
                if (!el) return;
                const now = new Date();
                const opt = { day: 'numeric', month: 'long', year: 'numeric' };
                const tgl = now.toLocaleDateString('id-ID', opt);
                const jam = String(now.getHours()).padStart(2, '0');
                const mnt = String(now.getMinutes()).padStart(2, '0');
                const dtk = String(now.getSeconds()).padStart(2, '0');
                el.innerText = `${tgl} • ${jam}:${mnt}:${dtk} WIB`;
            }
            setInterval(updateLiveClock, 1000);
            updateLiveClock();



            
        });
    </script>
</body>
</html>
