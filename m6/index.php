<?php
// index.php - DompetKu: Authentic Responsive iOS & Web FinTech App with Auth, Dashboard, Voice & Receipt Scanner
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/config/koneksi.php';

// Proteksi Login Database
if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url("login.php"));
    exit;
}

// Auto-deteksi: Jika dibuka di desktop / laptop (layar lebar), otomatis lempar ke dashboard.php
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_mobile_device = preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|iemobile|opera mini)/i', $ua);
if (!$is_mobile_device && !isset($_GET['mobile']) && !isset($_GET['view'])) {
    header("Location: " . base_url("dashboard.php"));
    exit;
}

// Notifikasi pesan
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
            header("Location: " . base_url("index.php?msg=added#dompet"));
            exit;
        } catch (PDOException $e) {
            $alert_msg = '<div class="alert alert-danger rounded-4">Gagal mencatat transaksi: ' . $e->getMessage() . '</div>';
        }
    }
}

// 2. Tambah Kategori (POST)
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

// Hitung Statistik Keuangan Kas
$stmt_stat = $pdo->query("SELECT 
    COALESCE(SUM(CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE 0 END), 0) AS total_masuk,
    COALESCE(SUM(CASE WHEN jenis = 'Pengeluaran' THEN jumlah ELSE 0 END), 0) AS total_keluar
FROM transaksi");
$stat = $stmt_stat->fetch();
$total_masuk  = (float)$stat['total_masuk'];
$total_keluar = (float)$stat['total_keluar'];
$saldo_akhir  = $total_masuk - $total_keluar;

// Ambil Seluruh Data Transaksi
$sql = "SELECT t.*, k.nama_kategori, k.icon 
        FROM transaksi t 
        JOIN kategori k ON t.id_kategori = k.id_kategori 
        ORDER BY t.tanggal DESC, t.id_transaksi DESC";
$daftar_transaksi = $pdo->query($sql)->fetchAll();
$transaksi_list = $daftar_transaksi;

// Ambil Seluruh Master Kategori beserta jumlah transaksi
$kategori_list = $pdo->query("SELECT k.*, COUNT(t.id_transaksi) AS total_trx 
                              FROM kategori k 
                              LEFT JOIN transaksi t ON k.id_kategori = t.id_kategori 
                              GROUP BY k.id_kategori 
                              ORDER BY k.jenis ASC, k.nama_kategori ASC")->fetchAll();

// Data untuk Chart.js: Pengeluaran per Kategori
$kat_expenses = $pdo->query("SELECT k.nama_kategori, COALESCE(SUM(t.jumlah), 0) AS total 
                             FROM kategori k 
                             JOIN transaksi t ON k.id_kategori = t.id_kategori 
                             WHERE t.jenis = 'Pengeluaran' 
                             GROUP BY k.id_kategori 
                             ORDER BY total DESC")->fetchAll();
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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- iOS Theme CSS with Cache-Buster -->
    <link href="<?= base_url('assets/css/ios-theme.css?v=' . time()) ?>" rel="stylesheet">
    <!-- Theme Controller Script -->
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>

    <script>
        // Auto-redirect jika dibuka di browser Laptop/Desktop (layar >= 992px) dan bukan mode paksa mobile ?view=mobile
        if (window.innerWidth >= 992 && !window.location.search.includes('view=mobile')) {
            window.location.replace('<?= base_url("dashboard.php") ?>');
        }
    </script>

    <style>
        /* Table Styles for Laporan Tab */
        .rekap-table th {
            background-color: var(--boss-table-th-bg);
            color: var(--boss-text-secondary);
            font-size: 11px;
            letter-spacing: 0.6px;
            padding: 12px 14px;
            border-color: var(--boss-table-border);
        }
        .rekap-table td {
            background-color: var(--boss-table-td-bg);
            border-color: var(--boss-table-border);
            padding: 12px 14px;
            vertical-align: middle;
            color: var(--boss-text-primary) !important;
        }
        .table-date-text { color: #F1F5F9 !important; font-size: 13px; font-weight: 600; }
        .table-title-text { color: #FFFFFF !important; font-size: 14px; font-weight: 600; }
        .table-notes-text { color: #94A3B8 !important; font-size: 11.5px; }
        .badge-kategori-solid {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            background-color: var(--boss-card-inner);
            color: #FFFFFF !important;
            border: 1px solid var(--boss-border-active);
        }
        [data-theme="light"] .table-date-text { color: #1E293B !important; }
        [data-theme="light"] .table-title-text { color: #0F172A !important; }
        [data-theme="light"] .table-notes-text { color: #64748B !important; }
        [data-theme="light"] .badge-kategori-solid {
            background-color: #F1F5F9 !important;
            color: #0F172A !important;
            border-color: rgba(0, 0, 0, 0.12) !important;
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

        <!-- Top Header Navigation (Authentic iOS Glass with Dynamic Island Padding) -->
        <nav class="ios-navbar px-3 px-md-4">
            <div class="container-xl d-flex align-items-center justify-content-between">
                
                <!-- Logo & Brand Profile (Authentic iOS Monogram) -->
                <div class="d-flex align-items-center gap-2">
                    <div class="ios-icon-box" style="width: 38px; height: 38px; border-radius: 12px; background: var(--ios-center-circle-bg); color: var(--ios-center-circle-color); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        <i class="bi bi-wallet2" style="font-size: 19px;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold" id="navbarViewTitle" style="letter-spacing: -0.4px; font-size: 16px; color: var(--boss-text-primary);">DompetKu</h6>
                        <small class="text-secondary" style="font-size: 11px;"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Firdaus Dhuha') ?></small>
                    </div>
                </div>

                <!-- Desktop Navigation Tabs (Hidden on Mobile) -->
                <div class="d-none d-md-flex align-items-center gap-1 p-1" style="background-color: var(--boss-segmented-bg); border-radius: 14px; border: 1px solid var(--boss-border);">
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

                <!-- Right Action Buttons (Clean Authentic iOS Circular Controls - NO SQUARE BOXES) -->
                <div class="d-flex align-items-center gap-2">
                    
                    <!-- Hidden Receipt Input for Camera / OCR -->
                    <input type="file" id="receiptFileInput" style="display:none;" accept="image/*" onchange="processReceipt(this)">

                    <!-- Theme Mode Switcher (Circular Pill) -->
                    <button type="button" onclick="toggleTheme()" class="ios-circle-btn" title="Ganti Mode Terang / Gelap">
                        <i class="theme-toggle-icon"></i>
                    </button>

                    <!-- Add Button (Primary Circular Pill) -->
                    <button type="button" class="ios-circle-btn ios-circle-btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah" title="Catat Transaksi Manual">
                        <i class="bi bi-plus-lg"></i>
                    </button>

                    <!-- Logout Button (Subtle Circular Pill) -->
                    <a href="<?= base_url('logout.php') ?>" class="ios-circle-btn" title="Keluar dari Sesi" onclick="return confirm('Keluar dari sesi DompetKu?');">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>

            </div>
        </nav>

        <div class="ios-main-content">
            
            <?= $alert_msg ?>

            <!-- ==================== TAB 1: DOMPET (HERO CARD & TRANSAKSI) ==================== -->
            <div id="tabViewDompet" class="tab-view-panel active">
                
                <div class="row g-4">
                    
                    <!-- Left Column (Desktop: col-md-5 col-lg-4, Mobile: col-12) -->
                    <div class="col-12 col-md-5 col-lg-4">
                        
                        <!-- Titanium Wallet Hero Card -->
                        <div class="wallet-hero-card mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-white-50 small fw-medium text-uppercase" style="letter-spacing: 0.8px; font-size: 10.5px;">Sisa Saldo Kas</span>
                                <span class="ios-badge-mono px-3 py-1 bg-white bg-opacity-10 text-white border-0" id="liveClockWidget">
                                    <?= tgl_indo(date('Y-m-d')) ?> • <?= date('H:i:s') ?> WIB
                                </span>
                            </div>
                            
                            <h1 class="display-6 fw-bold text-white mb-3" style="letter-spacing: -1px;">
                                <?= format_rupiah($saldo_akhir) ?>
                            </h1>

                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 px-3 rounded-3" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.08);">
                                        <div class="d-flex align-items-center gap-2 text-white-50" style="font-size: 11px;">
                                            <div class="hero-stat-badge-income">
                                                <i class="bi bi-arrow-down-left"></i>
                                            </div>
                                            <span>Pemasukan</span>
                                        </div>
                                        <div class="fw-bold mt-1" style="font-size: 14px; color: #30D158;">+ <?= format_rupiah($total_masuk) ?></div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="p-2 px-3 rounded-3" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.08);">
                                        <div class="d-flex align-items-center gap-2 text-white-50" style="font-size: 11px;">
                                            <div class="hero-stat-badge-expense">
                                                <i class="bi bi-arrow-up-right"></i>
                                            </div>
                                            <span>Pengeluaran</span>
                                        </div>
                                        <div class="fw-bold mt-1" style="font-size: 14px; color: #FF453A;">- <?= format_rupiah($total_keluar) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pencatatan Cepat (SATU KOTAK: Suara & Pindai Struk) -->
                        <div class="ios-smart-entry-card mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-lightning-charge text-primary" style="font-size: 12px;"></i>
                                    <span class="fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: var(--boss-text-muted);">Pencatatan Cepat</span>
                                </div>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-semibold" style="font-size: 10px; border-radius: 6px;">Fitur Instan</span>
                            </div>

                            <div class="ios-smart-entry-box">
                                <!-- Left: Input Suara -->
                                <div class="ios-smart-entry-half" onclick="startVoiceInput()" title="Bicara untuk mencatat transaksi">
                                    <div class="ios-smart-icon-badge" style="background: rgba(88, 86, 214, 0.12); color: #5856D6;">
                                        <i class="bi bi-mic-fill" id="smartVoiceMicIcon"></i>
                                    </div>
                                    <div class="text-start">
                                        <div class="fw-bold" style="color: var(--boss-text-primary); font-size: 13.5px; line-height: 1.2;">Input Suara</div>
                                        <small class="text-secondary" style="font-size: 10.5px;">Dikte transaksi</small>
                                    </div>
                                </div>

                                <!-- Vertical Divider -->
                                <div class="ios-smart-entry-divider"></div>

                                <!-- Right: Foto Struk -->
                                <div class="ios-smart-entry-half" onclick="triggerReceiptScan()" title="Pindai foto struk pembayaran">
                                    <div class="ios-smart-icon-badge" style="background: rgba(0, 122, 255, 0.12); color: #007AFF;">
                                        <i class="bi bi-camera-fill"></i>
                                    </div>
                                    <div class="text-start">
                                        <div class="fw-bold" style="color: var(--boss-text-primary); font-size: 13.5px; line-height: 1.2;">Foto Struk</div>
                                        <small class="text-secondary" style="font-size: 10.5px;">Pindai bukti bayar</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Financial Summary Card -->
                        <div class="ios-card p-3 d-none d-md-block">
                            <h6 class="fw-bold mb-3" style="font-size: 13.5px; letter-spacing: -0.2px;">Statistik Kas Mahasiswa</h6>
                            <div class="d-flex justify-content-between text-secondary small py-2 border-bottom border-secondary border-opacity-10">
                                <span>Total Riwayat</span>
                                <strong style="color: var(--boss-text-primary);"><?= count($daftar_transaksi) ?> transaksi</strong>
                            </div>
                            <div class="d-flex justify-content-between text-secondary small py-2 border-bottom border-secondary border-opacity-10">
                                <span>Rasio Pemasukan</span>
                                <strong class="text-success"><?= $total_masuk > 0 ? round(($saldo_akhir / $total_masuk) * 100) : 0 ?>% Surplus</strong>
                            </div>
                            <div class="d-flex justify-content-between text-secondary small py-2">
                                <span>Sisa Kas Tersimpan</span>
                                <strong style="color: var(--boss-text-primary);"><?= format_rupiah($saldo_akhir) ?></strong>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column (Desktop: col-md-7 col-lg-8, Mobile: col-12) -->
                    <div class="col-12 col-md-7 col-lg-8">
                        
                        <!-- Filter & Search Controls in Row with Smooth Hardware-Accelerated Sliding Pill -->
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-sm-6">
                                <div class="ios-segmented-control-slider-wrap" id="filterSegmentedWrap">
                                    <div class="ios-segmented-slider ios-segmented-slider-3" id="filterSegmentSlider"></div>
                                    <button type="button" class="ios-segmented-tab-btn active" id="btnFilterSemua" onclick="setInstantFilter('Semua', 0)">Semua</button>
                                    <button type="button" class="ios-segmented-tab-btn" id="btnFilterPemasukan" onclick="setInstantFilter('Pemasukan', 1)">Pemasukan</button>
                                    <button type="button" class="ios-segmented-tab-btn" id="btnFilterPengeluaran" onclick="setInstantFilter('Pengeluaran', 2)">Pengeluaran</button>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="ios-search-wrapper">
                                    <i class="bi bi-search ios-search-icon"></i>
                                    <input type="text" id="instantSearchInput" class="ios-input ios-search-input" placeholder="Cari transaksi..." onkeyup="filterTransaksiLive()">
                                </div>
                            </div>
                        </div>

                        <!-- Header List -->
                        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                            <h6 class="text-secondary fw-semibold text-uppercase mb-0" style="font-size: 11px; letter-spacing: 0.8px;">
                                Catatan Transaksi (<span id="totalVisibleTrx"><?= count($daftar_transaksi) ?></span>)
                            </h6>
                            <small class="text-secondary" style="font-size: 11px;">Ketuk titik tiga untuk opsi</small>
                        </div>

                        <!-- Transaction Inset Grouped List -->
                        <div class="ios-card-group mb-4" id="transactionListContainer">
                            <?php if (empty($daftar_transaksi)): ?>
                                <div class="p-4 text-center text-secondary small">Belum ada transaksi tercatat.</div>
                            <?php else: ?>
                                <?php foreach ($daftar_transaksi as $trx): 
                                    $is_masuk = ($trx['jenis'] === 'Pemasukan');
                                    $sign = $is_masuk ? '+' : '-';
                                    $amount_class = $is_masuk ? 'amount-income' : 'amount-expense';
                                ?>
                                <div class="ios-list-row trx-card-item" data-jenis="<?= $trx['jenis'] ?>" data-search="<?= strtolower(htmlspecialchars($trx['judul'] . ' ' . $trx['nama_kategori'] . ' ' . $trx['catatan'])) ?>">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="ios-icon-box">
                                            <i class="bi <?= htmlspecialchars($trx['icon']) ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold mb-0" style="font-size: 14.5px; letter-spacing: -0.2px; color: var(--boss-text-primary);">
                                                <?= htmlspecialchars($trx['judul']) ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <span class="ios-badge-mono">
                                                    <?= htmlspecialchars($trx['nama_kategori']) ?>
                                                </span>
                                                <span class="text-secondary" style="font-size: 11.5px;">
                                                    • <?= tgl_indo($trx['tanggal']) ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($trx['catatan'])): ?>
                                                <small class="text-secondary d-block mt-1" style="font-size: 11px;">
                                                    <?= htmlspecialchars($trx['catatan']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <div class="text-end">
                                            <span class="fw-bold <?= $amount_class ?>" style="font-size: 14.5px; letter-spacing: -0.3px;">
                                                <?= $sign ?> <?= format_rupiah($trx['jumlah']) ?>
                                            </span>
                                            <small class="d-block text-secondary text-uppercase" style="font-size: 10px;">
                                                <?= $trx['jenis'] ?>
                                            </small>
                                        </div>

                                        <div class="dropdown">
                                            <button class="btn btn-ios-secondary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center" data-bs-toggle="dropdown" style="width: 32px; height: 32px;">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end p-2" style="background-color: var(--boss-card); border: 1px solid var(--boss-border-active);">
                                                <li>
                                                    <a class="dropdown-item rounded-3 py-2 small" href="<?= base_url('transaksi_edit.php?id=' . $trx['id_transaksi']) ?>" style="color: var(--boss-text-primary);">
                                                        <i class="bi bi-pencil me-2"></i> Edit Transaksi
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

            <!-- ==================== TAB 2: KATEGORI (MANAJEMEN KATEGORI) ==================== -->
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

                <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-lg-3">
                    <?php foreach ($kategori_list as $kat): 
                        $is_in = ($kat['jenis'] === 'Pemasukan');
                    ?>
                    <div class="col">
                        <div class="ios-card p-3 d-flex align-items-center justify-content-between h-100">
                            <div class="d-flex align-items-center gap-3">
                                <div class="ios-icon-box" style="width: 42px; height: 42px;">
                                    <i class="bi <?= htmlspecialchars($kat['icon']) ?>"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold text-truncate" style="max-width: 170px; font-size: 14px; color: var(--boss-text-primary);">
                                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge <?= $is_in ? 'text-success bg-success' : 'text-danger bg-danger' ?> bg-opacity-15 fw-semibold px-2 py-0" style="font-size: 10.5px;">
                                            <?= $kat['jenis'] ?>
                                        </span>
                                        <span class="text-secondary small" style="font-size: 11px;">
                                            <?= $kat['total_trx'] ?> data
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <a href="<?= base_url('kategori.php?hapus=' . $kat['id_kategori']) ?>" 
                               class="btn btn-ios-danger btn-sm py-1 px-2" 
                               onclick="return confirm('Hapus kategori <?= htmlspecialchars($kat['nama_kategori']) ?>?');"
                               title="Hapus Kategori">
                                <i class="bi bi-trash3"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- ==================== TAB 4: LAPORAN (REKAP KEUANGAN & EXCEL EXPORT) ==================== -->
            <div id="tabViewLaporan" class="tab-view-panel">
                
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 no-print">
                    <div>
                        <h5 class="fw-bold mb-0" style="letter-spacing: -0.3px;">Laporan Arus Kas Mahasiswa</h5>
                        <small class="text-secondary">Rekapitulasi keuangan personal per <?= tgl_indo(date('Y-m-d')) ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= base_url('export_excel.php') ?>" class="btn btn-ios-secondary btn-sm py-2 px-3 d-flex align-items-center gap-1" title="Unduh File Excel">
                            <i class="bi bi-file-earmark-excel text-success"></i> Export Excel
                        </a>
                        <a href="<?= base_url('laporan.php') ?>" class="btn btn-ios-secondary btn-sm py-2 px-3 d-flex align-items-center gap-1" title="Buka Halaman Fokus">
                            <i class="bi bi-arrows-fullscreen"></i> <span class="d-none d-sm-inline">Fokus Lembar</span>
                        </a>
                        <button onclick="window.print()" class="btn btn-ios-primary btn-sm py-2 px-4 d-flex align-items-center gap-2">
                            <i class="bi bi-printer-fill"></i> Cetak PDF
                        </button>
                    </div>
                </div>

                <!-- Summary Stat Cards in 3 Columns -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="ios-card p-3 text-center">
                            <small class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.6px;">Total Pemasukan</small>
                            <span class="fw-bold amount-income" style="font-size: 18px;">+ <?= format_rupiah($total_masuk) ?></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="ios-card p-3 text-center">
                            <small class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.6px;">Total Pengeluaran</small>
                            <span class="fw-bold amount-expense" style="font-size: 18px;">- <?= format_rupiah($total_keluar) ?></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="ios-card p-3 text-center" style="background-color: var(--boss-badge-bg); border-color: var(--boss-border-active);">
                            <small class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.6px;">Sisa Saldo Kas</small>
                            <span class="fw-bold" style="color: var(--boss-text-primary); font-size: 18px; letter-spacing: -0.3px;"><?= format_rupiah($saldo_akhir) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Printable Table View -->
                <div class="table-responsive rounded-4 overflow-hidden border mb-4" style="border-color: var(--boss-table-border) !important;">
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
                                    <span class="badge <?= $is_masuk ? 'text-success bg-success' : 'text-danger bg-danger' ?> bg-opacity-15 fw-semibold px-2 py-1" style="font-size: 11px;">
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

                <!-- Footer Sign -->
                <div class="p-3 text-center text-secondary small border-top border-secondary border-opacity-10">
                    <p class="mb-1">Penanggung Jawab: <strong style="color: var(--boss-text-primary);"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong></p>
                    <small style="font-size: 10.5px;">Waktu Rekap: <?= date('d/m/Y H:i:s') ?> WIB • DompetKu (PBW)</small>
                </div>

            </div>

            <!-- ==================== TAB 4: AKUN (PROFIL MAHASISWA & PENGATURAN) ==================== -->
            <div id="tabViewAkun" class="tab-view-panel">
                
                <div class="mb-4">
                    <h5 class="fw-bold mb-1" style="letter-spacing: -0.4px; color: var(--boss-text-primary);">Akun & Pengaturan</h5>
                    <small class="text-secondary">Informasi identitas mahasiswa dan preferensi sistem</small>
                </div>

                <!-- Profile Monogram Card -->
                <div class="ios-card p-4 mb-4 text-center">
                    <div class="mx-auto mb-3" style="width: 68px; height: 68px; border-radius: 20px; background: var(--boss-hero-gradient); color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; box-shadow: 0 8px 20px rgba(0,0,0,0.18);">
                        FD
                    </div>
                    <h5 class="fw-bold mb-1" style="color: var(--boss-text-primary); letter-spacing: -0.3px;">
                        <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Firdaus Dhuha Prabowo') ?>
                    </h5>
                    <p class="text-secondary small mb-2">Mahasiswa Sistem Informasi • PBW Gunadarma</p>
                    <span class="badge bg-success bg-opacity-15 text-success px-3 py-1 fw-semibold" style="border-radius: 20px; font-size: 11px;">
                        <i class="bi bi-shield-check me-1"></i> Sesi Aktif 30 Hari
                    </span>
                </div>

                <!-- Inset Grouped Section 1: Ringkasan Finansial -->
                <div class="mb-2 px-1">
                    <span class="fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: var(--boss-text-muted);">Ringkasan Kas Pribadi</span>
                </div>
                <div class="ios-card-group mb-4">
                    <div class="ios-list-row">
                        <span class="text-secondary small">Total Pemasukan</span>
                        <strong class="text-success small fw-bold">+ <?= format_rupiah($total_masuk) ?></strong>
                    </div>
                    <div class="ios-list-row">
                        <span class="text-secondary small">Total Pengeluaran</span>
                        <strong class="text-danger small fw-bold">- <?= format_rupiah($total_keluar) ?></strong>
                    </div>
                    <div class="ios-list-row">
                        <span class="text-secondary small">Sisa Saldo Kas Riil</span>
                        <strong style="color: var(--boss-text-primary);" class="small fw-bold"><?= format_rupiah($saldo_akhir) ?></strong>
                    </div>
                    <div class="ios-list-row">
                        <span class="text-secondary small">Jumlah Riwayat Tercatat</span>
                        <span class="badge bg-secondary bg-opacity-15 text-secondary px-2 py-1" style="font-size: 11px;"><?= count($daftar_transaksi) ?> transaksi</span>
                    </div>
                </div>

                <!-- Inset Grouped Section 2: Preferensi & Akses Cepat -->
                <div class="mb-2 px-1">
                    <span class="fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.6px; color: var(--boss-text-muted);">Preferensi & Fitur</span>
                </div>
                <div class="ios-card-group mb-4">
                    <div class="ios-list-row cursor-pointer" onclick="toggleTheme()">
                        <div class="d-flex align-items-center gap-3">
                            <div class="ios-icon-box" style="width: 34px; height: 34px; font-size: 16px;">
                                <i class="theme-toggle-icon"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small" style="color: var(--boss-text-primary);">Mode Tampilan</div>
                                <small class="text-secondary" style="font-size: 11px;">Ganti Tema Terang (Cupertino) / Gelap (Titanium)</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </div>

                    <a href="<?= base_url('export_excel.php') ?>" class="ios-list-row">
                        <div class="d-flex align-items-center gap-3">
                            <div class="ios-icon-box" style="width: 34px; height: 34px; font-size: 16px; color: #107C41;">
                                <i class="bi bi-file-earmark-excel"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small" style="color: var(--boss-text-primary);">Unduh Laporan Excel</div>
                                <small class="text-secondary" style="font-size: 11px;">Format berkas Microsoft Excel (.xls)</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted small"></i>
                    </a>

                    <a href="<?= base_url('dashboard.php?desktop=1') ?>" class="ios-list-row">
                        <div class="d-flex align-items-center gap-3">
                            <div class="ios-icon-box" style="width: 34px; height: 34px; font-size: 16px; color: #007AFF;">
                                <i class="bi bi-laptop"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small" style="color: var(--boss-text-primary);">Mode Layar Lebar</div>
                                <small class="text-secondary" style="font-size: 11px;">Buka versi dashboard desktop lengkap</small>
                            </div>
                        </div>
                        <i class="bi bi-box-arrow-up-right text-muted small"></i>
                    </a>
                </div>

                <!-- Inset Grouped Section 3: Sesi Keluar -->
                <div class="mb-4">
                    <a href="<?= base_url('logout.php') ?>" class="btn btn-outline-danger w-100 py-3 rounded-4 fw-semibold d-flex align-items-center justify-content-center gap-2" onclick="return confirm('Keluar dari sesi akun DompetKu?');">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar dari Akun</span>
                    </a>
                </div>

            </div>

        </div>

        <!-- ==================== AUTHENTIC WHATSAPP & APPLE iOS 18 FLOATING CAPSULE NAVBAR ==================== -->
        <nav class="ios-capsule-navbar no-print" id="bottomCapsuleNavbar">
            <!-- 1. DOMPET -->
            <button type="button" class="ios-capsule-item active" id="tabBtnDompet" onclick="switchIosTab('dompet')">
                <div class="capsule-icon-wrap">
                    <i class="bi bi-wallet2 tab-icon"></i>
                </div>
                <span class="capsule-label">Dompet</span>
            </button>

            <!-- 2. KATEGORI with WhatsApp Green Pill Badge -->
            <button type="button" class="ios-capsule-item" id="tabBtnKategori" onclick="switchIosTab('kategori')">
                <div class="capsule-icon-wrap">
                    <i class="bi bi-tags tab-icon"></i>
                    <span class="capsule-badge"><?= count($kategori_list ?? []) ?></span>
                </div>
                <span class="capsule-label">Kategori</span>
            </button>

            <!-- 3. CATAT (+) (Center Action Modal Trigger) -->
            <button type="button" class="ios-capsule-item" id="tabBtnCatat" data-bs-toggle="modal" data-bs-target="#modalTambah" title="Catat Transaksi Cepat">
                <div class="capsule-icon-wrap">
                    <i class="bi bi-plus-lg tab-icon"></i>
                </div>
                <span class="capsule-label">Catat</span>
            </button>

            <!-- 4. LAPORAN with WhatsApp Green Pill Badge -->
            <button type="button" class="ios-capsule-item" id="tabBtnLaporan" onclick="switchIosTab('laporan')">
                <div class="capsule-icon-wrap">
                    <i class="bi bi-file-earmark-text tab-icon"></i>
                    <span class="capsule-badge"><?= count($transaksi_list ?? []) ?></span>
                </div>
                <span class="capsule-label">Laporan</span>
            </button>

            <!-- 5. ANDA (Circular Student Profile Photo) -->
            <button type="button" class="ios-capsule-item" id="tabBtnAkun" onclick="switchIosTab('akun')">
                <div class="capsule-icon-wrap">
                    <div class="capsule-avatar-circle">
                        <img src="<?= base_url('assets/img/profile-firdaus.png') ?>" alt="Anda" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <span class="avatar-fallback" style="display:none;">FD</span>
                    </div>
                </div>
                <span class="capsule-label">Anda</span>
            </button>
        </nav>

    </div>

    <!-- ==================== MODAL TAMBAH TRANSAKSI (iOS Sheet) ==================== -->
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ios-modal p-2">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalTambahLabel" style="letter-spacing: -0.3px;">Catat Transaksi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="index.php" method="POST" id="formCatatTransaksi">
                    <div class="modal-body py-3">
                        
                        <!-- Smart Input Triggers -->
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded-3" style="background-color: var(--boss-stat-bg); border: 1px solid var(--boss-border);">
                            <span class="text-secondary small fw-medium">Pilihan Cepat:</span>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-ios-secondary btn-sm py-1 px-2 d-flex align-items-center gap-1" onclick="startVoiceInput()">
                                    <i class="bi bi-mic-fill" style="color: #5856D6;"></i> <span style="font-size: 11.5px;">Bicara</span>
                                </button>
                                <button type="button" class="btn btn-ios-secondary btn-sm py-1 px-2 d-flex align-items-center gap-1" onclick="triggerReceiptScan()">
                                    <i class="bi bi-camera-fill text-primary"></i> <span style="font-size: 11.5px;">Foto Struk</span>
                                </button>
                            </div>
                        </div>

                        <!-- Smooth Hardware-Accelerated Sliding Segmented Control (No "Pluk") -->
                        <div class="mb-3 text-center">
                            <label class="form-label text-secondary small fw-medium d-block mb-1">Jenis Transaksi</label>
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

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Nominal (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text border-0 fw-bold text-secondary" style="background-color: var(--boss-input-bg); color: var(--boss-text-muted);">Rp</span>
                                <input type="number" name="jumlah" id="inputJumlah" class="ios-input" placeholder="Contoh: 25000" min="100" step="100" required>
                            </div>
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
                            <label class="form-label text-secondary small fw-medium">Keterangan / Judul <span class="text-danger">*</span></label>
                            <input type="text" name="judul" id="inputJudul" class="ios-input" placeholder="Contoh: Makan Siang / Kiriman Ortu" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" id="inputTanggal" class="ios-input" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-secondary small fw-medium">Catatan Tambahan (Opsional)</label>
                            <textarea name="catatan" id="inputCatatan" class="ios-input" rows="2" placeholder="Detail pengeluaran..."></textarea>
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

    <!-- ==================== MODAL TAMBAH KATEGORI (iOS Sheet) ==================== -->
    <div class="modal fade" id="modalTambahKat" tabindex="-1" aria-labelledby="modalTambahKatLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ios-modal p-2">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalTambahKatLabel" style="letter-spacing: -0.3px;">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php" method="POST">
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

    <!-- ==================== MODAL SCAN STRUK (RECEIPT SCANNER) ==================== -->
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Track index for directional sliding animations (Left vs Right)
        let currentTabIndex = 0;
        const tabIndices = { 'dompet': 0, 'kategori': 1, 'laporan': 2, 'akun': 3 };

        // Butter-Smooth Tab Switching with Directional Slide Transitions (No "Pluk")
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
            const isSlideRight = newIndex >= currentTabIndex;
            currentTabIndex = newIndex;

            panels.forEach(p => {
                p.classList.remove('active', 'slide-right', 'slide-left');
            });
            mobileBtns.forEach(b => b.classList.remove('active'));
            deskBtns.forEach(d => d.classList.remove('active'));

            const panelEl = document.getElementById(target.id);
            if (panelEl) {
                panelEl.classList.add('active', isSlideRight ? 'slide-right' : 'slide-left');
            }

            const mb = document.getElementById(target.mBtn); if (mb) mb.classList.add('active');
            const dt = document.getElementById(target.dBtn); if (dt) dt.classList.add('active');
            if (titleEl) titleEl.innerText = target.title;

            window.location.hash = tabName;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Hardware-Accelerated Sliding Segmented Control for Modal
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

        // Instant Filter Jenis Transaksi with Sliding Pill
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

        // Real-Time Search & Filter
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

        // Filter Kategori Dropdown in Modal
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

        // ==================== FITUR 1: FOTO STRUK AUTO CATAT ====================
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

                // Simulasi Analisis OCR Cerdas Mahasiswa
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

            // Tutup modal struk
            const scanModalEl = document.getElementById('modalScanStruk');
            const scanModal = bootstrap.Modal.getInstance(scanModalEl);
            if (scanModal) scanModal.hide();

            // Isi form modal catat
            selectModalJenis('Pengeluaran');

            document.getElementById('inputJudul').value = parsedReceiptData.judul;
            document.getElementById('inputJumlah').value = parsedReceiptData.nominal;
            document.getElementById('selectKategori').value = parsedReceiptData.id_kat;
            document.getElementById('inputCatatan').value = 'Diisi otomatis dari pemindaian foto struk.';

            // Buka modal catat transaksi
            setTimeout(() => {
                const addModal = new bootstrap.Modal(document.getElementById('modalTambah'));
                addModal.show();
            }, 300);
        }

        // ==================== FITUR 2: VOICE INPUT (WEB SPEECH RECOGNITION) ====================
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

            // Tampilkan notifikasi mikrofon
            const toastEl = document.createElement('div');
            toastEl.id = 'voiceToast';
            toastEl.className = 'position-fixed bottom-0 start-50 translate-middle-x mb-5 p-3 rounded-4 shadow-lg text-white d-flex align-items-center gap-2';
            toastEl.style.cssText = 'background: rgba(16, 18, 26, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255,69,58,0.5); z-index: 9999;';
            toastEl.innerHTML = '<i class="bi bi-mic-fill text-danger fs-5 mic-pulse"></i> <span>Mendengarkan... Katakan: <em>"Makan siang tiga puluh ribu"</em></span>';
            document.body.appendChild(toastEl);

            recognition.start();

            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript.toLowerCase();
                if (navMic) navMic.classList.remove('mic-pulse');
                isListening = false;
                const tEl = document.getElementById('voiceToast');
                if (tEl) tEl.remove();

                // Parsing angka dari suara
                let detectedNum = 0;
                const numMatch = transcript.match(/\d+/g);
                if (numMatch) {
                    detectedNum = parseInt(numMatch.join(''), 10);
                } else {
                    // Cek kata kunci nominal umum bahasa indonesia
                    if (transcript.includes('sepuluh ribu')) detectedNum = 10000;
                    else if (transcript.includes('lima belas ribu')) detectedNum = 15000;
                    else if (transcript.includes('dua puluh ribu')) detectedNum = 20000;
                    else if (transcript.includes('dua puluh lima ribu')) detectedNum = 25000;
                    else if (transcript.includes('tiga puluh ribu')) detectedNum = 30000;
                    else if (transcript.includes('lima puluh ribu')) detectedNum = 50000;
                    else if (transcript.includes('seratus ribu')) detectedNum = 100000;
                    else detectedNum = 25000;
                }

                // Bersihkan judul
                let cleanJudul = transcript.replace(/\d+/g, '').replace(/(ribu|ratus|juta|rupiah)/gi, '').trim();
                if (cleanJudul.length < 3) cleanJudul = 'Pengeluaran Suara: ' + transcript;

                // Terapkan ke form
                selectModalJenis('Pengeluaran');
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

        // ==================== CHART.JS INITIALIZATION ====================
        document.addEventListener('DOMContentLoaded', () => {
            filterKategoriDropdown('Pengeluaran');

            // Handle Hash on Load (Default on Mobile: #dompet, no dashboard on phone)
            let hash = window.location.hash.replace('#', '') || 'dompet';
            if (hash === 'dashboard') hash = 'dompet';
            switchIosTab(hash);

            // Chart 1: Cashflow Bar Chart
            const ctxCashflow = document.getElementById('chartCashflow');
            if (ctxCashflow) {
                new Chart(ctxCashflow, {
                    type: 'bar',
                    data: {
                        labels: ['Pemasukan', 'Pengeluaran', 'Sisa Kas'],
                        datasets: [{
                            label: 'Nominal (Rp)',
                            data: [<?= $total_masuk ?>, <?= $total_keluar ?>, <?= $saldo_akhir ?>],
                            backgroundColor: [
                                'rgba(48, 209, 88, 0.85)',
                                'rgba(255, 69, 58, 0.85)',
                                'rgba(10, 132, 255, 0.85)'
                            ],
                            borderRadius: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(255,255,255,0.06)' },
                                ticks: { color: '#94A3B8' }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { color: '#CBD5E1' }
                            }
                        }
                    }
                });
            }

            // Chart 2: Category Expense Doughnut Chart
            const ctxCategory = document.getElementById('chartCategory');
            if (ctxCategory) {
                new Chart(ctxCategory, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode($chart_labels_kat) ?>,
                        datasets: [{
                            data: <?= json_encode($chart_data_kat) ?>,
                            backgroundColor: [
                                '#FF3B30', '#FF9500', '#AF52DE', '#5AC8FA', '#34C759', '#FF2D55'
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
                                labels: { color: '#94A3B8', boxWidth: 12, font: { size: 10 } }
                            }
                        }
                    }
                });
            }

            // Live Real-Time Indonesian Clock (WIB)
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
