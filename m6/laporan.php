<?php
require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url("login.php"));
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$sql = "SELECT t.*, k.nama_kategori 
        FROM transaksi t 
        JOIN kategori k ON t.id_kategori = k.id_kategori 
        WHERE t.id_user = ?
        ORDER BY t.tanggal ASC, t.id_transaksi ASC";
$stmt_lap = $pdo->prepare($sql);
$stmt_lap->execute([$user_id]);
$transaksi_list = $stmt_lap->fetchAll();

$total_masuk = 0;
$total_keluar = 0;
foreach ($transaksi_list as $t) {
    if ($t['jenis'] === 'Pemasukan') {
        $total_masuk += $t['jumlah'];
    } else {
        $total_keluar += $t['jumlah'];
    }
}
$saldo = $total_masuk - $total_keluar;
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
    <title>Rekap Laporan Kas - DompetKu</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <link href="<?= base_url('assets/css/ios-theme.css?v=' . time()) ?>" rel="stylesheet">
    
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>

    <script>
        if (window.innerWidth >= 992 && !window.location.search.includes('standalone=true')) {
            window.location.replace('<?= base_url("dashboard.php#laporanSection") ?>');
        }
    </script>

    <style>
        .report-box {
            background-color: var(--boss-card);
            border: 1px solid var(--boss-border);
            border-radius: var(--boss-radius-xl);
            padding: 24px;
            box-shadow: var(--boss-shadow);
        }

        .header-rule {
            border-bottom: 1px solid var(--boss-border);
            padding-bottom: 16px;
            margin-bottom: 22px;
        }

        .summary-stat-box {
            background-color: var(--boss-stat-bg);
            border: 1px solid var(--boss-border);
            border-radius: var(--boss-radius-md);
            padding: 16px;
            text-align: center;
        }

        
        .rekap-table {
            color: var(--boss-text-primary) !important;
            border-color: var(--boss-table-border);
            font-size: 13.5px;
            margin-bottom: 0;
        }

        .rekap-table th {
            background-color: var(--boss-table-th-bg);
            color: var(--boss-text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.8px;
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

        
        .table-date-text {
            color: #F1F5F9 !important;
            font-weight: 600;
            font-size: 13px;
        }

        .table-title-text {
            color: #FFFFFF !important;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: -0.2px;
        }

        .table-notes-text {
            color: #94A3B8 !important;
            font-size: 11.5px;
            margin-top: 2px;
            display: block;
        }

        .badge-kategori-solid {
            font-size: 11.5px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            background-color: var(--boss-card-inner);
            color: #FFFFFF !important;
            border: 1px solid var(--boss-border-active);
            display: inline-block;
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
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
            }
            .ios-app-shell {
                box-shadow: none !important;
                max-width: 100% !important;
                padding-bottom: 0 !important;
            }
            .report-box {
                background-color: #ffffff !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .header-rule {
                border-bottom: 2px solid #000000 !important;
            }
            .summary-stat-box {
                background-color: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
            }
            .rekap-table {
                color: #000000 !important;
                border-color: #cbd5e1 !important;
            }
            .rekap-table th {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                border-color: #cbd5e1 !important;
            }
            .rekap-table td {
                background-color: #ffffff !important;
                color: #000000 !important;
                border-color: #e2e8f0 !important;
            }
            .table-date-text { color: #334155 !important; }
            .table-title-text { color: #000000 !important; }
            .badge-kategori-solid {
                background-color: #f1f5f9 !important;
                color: #000000 !important;
                border: 1px solid #cbd5e1 !important;
            }
        }
    </style>
</head>
<body>

    <div class="ios-app-shell">

        
        <nav class="ios-navbar py-2 px-3 px-md-4 no-print">
            <div class="container-xl d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= base_url('index.php#dompet') ?>" class="btn btn-ios-secondary btn-sm py-1 px-3 d-flex align-items-center gap-1" title="Kembali ke Dashboard">
                        <i class="bi bi-chevron-left"></i> <span class="d-none d-sm-inline">Dashboard</span>
                    </a>
                    <div>
                        <h6 class="mb-0 fw-bold" style="letter-spacing: -0.3px;">Rekap Arus Kas</h6>
                        <small class="text-secondary" style="font-size: 10.5px;">DompetKu Mahasiswa</small>
                    </div>
                </div>

                
                <div class="d-none d-md-flex align-items-center gap-1 p-1" style="background-color: var(--boss-segmented-bg); border-radius: 14px; border: 1px solid var(--boss-border);">
                    <a href="<?= base_url('index.php#dompet') ?>" class="ios-desktop-tab-btn">
                        <i class="bi bi-wallet2 me-1"></i> Dompet
                    </a>
                    <a href="<?= base_url('index.php#kategori') ?>" class="ios-desktop-tab-btn">
                        <i class="bi bi-tags me-1"></i> Kategori
                    </a>
                    <a href="<?= base_url('laporan.php') ?>" class="ios-desktop-tab-btn active">
                        <i class="bi bi-file-earmark-text me-1"></i> Laporan
                    </a>
                </div>

                <div class="d-flex align-items-center gap-2">
                    
                    <button onclick="toggleTheme()" class="btn btn-ios-secondary btn-sm py-1 px-2 d-flex align-items-center" title="Mode Terang / Gelap">
                        <i class="theme-toggle-icon fs-6"></i>
                    </button>

                    
                    <button onclick="window.print()" class="btn btn-ios-primary btn-sm py-1 px-3 d-flex align-items-center gap-1">
                        <i class="bi bi-printer-fill"></i> Cetak PDF
                    </button>
                </div>
            </div>
        </nav>

        <div class="ios-main-content">
            
            <div class="report-box">
                
                
                <div class="header-rule d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-2">
                    <div>
                        <h5 class="fw-bold mb-1" style="letter-spacing: -0.5px; color: var(--boss-text-primary);">LAPORAN ARUS KAS MAHASISWA</h5>
                        <p class="text-secondary small mb-0">Sistem DompetKu • CashFlow Personal Mahasiswa</p>
                    </div>
                    <div class="text-sm-end small">
                        <div class="text-secondary">Pemilik Kas: <strong style="color: var(--boss-text-primary);">Firdaus Dhuha Prabowo</strong></div>
                        <div class="text-secondary">Per Tanggal: <strong style="color: var(--boss-text-primary);"><?= tgl_indo(date('Y-m-d')) ?></strong></div>
                    </div>
                </div>

                
                <div class="row g-2 mb-4">
                    <div class="col-4">
                        <div class="summary-stat-box">
                            <small class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 10px; letter-spacing: 0.6px;">Pemasukan</small>
                            <span class="fw-bold amount-income" style="font-size: 15px;">+ <?= format_rupiah($total_masuk) ?></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="summary-stat-box">
                            <small class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 10px; letter-spacing: 0.6px;">Pengeluaran</small>
                            <span class="fw-bold amount-expense" style="font-size: 15px;">- <?= format_rupiah($total_keluar) ?></span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="summary-stat-box" style="background-color: var(--boss-badge-bg); border-color: var(--boss-border-active);">
                            <small class="text-secondary d-block text-uppercase fw-semibold" style="font-size: 10px; letter-spacing: 0.6px;">Sisa Saldo</small>
                            <span class="fw-bold" style="color: var(--boss-text-primary); font-size: 15px; letter-spacing: -0.3px;"><?= format_rupiah($saldo) ?></span>
                        </div>
                    </div>
                </div>

                
                <div class="table-responsive rounded-4 overflow-hidden border" style="border-color: var(--boss-table-border) !important;">
                    <table class="table rekap-table align-middle">
                        <thead>
                            <tr>
                                <th style="width: 6%;" class="text-center">No</th>
                                <th style="width: 22%;">Tanggal</th>
                                <th style="width: 26%;">Kategori</th>
                                <th style="width: 26%;">Keterangan</th>
                                <th style="width: 20%;" class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($transaksi_list as $row): 
                                $is_masuk = ($row['jenis'] === 'Pemasukan');
                                $amount_class = $is_masuk ? 'amount-income' : 'amount-expense';
                            ?>
                            <tr>
                                <td class="text-center text-secondary fw-semibold"><?= $no++ ?></td>
                                <td>
                                    <span class="table-date-text"><?= tgl_indo($row['tanggal']) ?></span>
                                </td>
                                <td>
                                    <span class="badge-kategori-solid">
                                        <?= htmlspecialchars($row['nama_kategori']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="table-title-text"><?= htmlspecialchars($row['judul']) ?></span>
                                    <?php if (!empty($row['catatan'])): ?>
                                        <span class="table-notes-text"><?= htmlspecialchars($row['catatan']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold <?= $amount_class ?>" style="font-size: 13.5px;">
                                    <?= ($is_masuk ? '+' : '-') ?> <?= format_rupiah($row['jumlah']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold" style="background-color: var(--boss-table-th-bg);">
                                <td colspan="4" class="text-end" style="color: var(--boss-text-primary);">Sisa Saldo Kas Bersih:</td>
                                <td class="text-end" style="color: var(--boss-text-primary); font-size: 15px;"><?= format_rupiah($saldo) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                
                <div class="row mt-4 pt-3" style="border-top: 1px solid var(--boss-border);">
                    <div class="col-7">
                        <small class="text-secondary" style="font-size: 11px;">
                            Dicetak resmi dari Aplikasi DompetKu (PBW)<br>
                            Waktu Rekap: <?= date('d/m/Y H:i:s') ?> WIB
                        </small>
                    </div>
                    <div class="col-5 text-center">
                        <small class="text-secondary" style="font-size: 11px;">Penanggung Jawab,</small>
                        <div style="height: 40px;"></div>
                        <strong style="color: var(--boss-text-primary); font-size: 12.5px;">Firdaus Dhuha Prabowo</strong><br>
                        <small class="text-secondary" style="font-size: 10.5px;">Mahasiswa Sistem Informasi</small>
                    </div>
                </div>

            </div>

        </div>

        
        <nav class="ios-tab-bar d-flex d-md-none no-print">
            <a href="<?= base_url('index.php#dompet') ?>" class="ios-tab-item">
                <i class="bi bi-wallet2 tab-icon"></i>
                <span>Dompet</span>
            </a>

            <a href="<?= base_url('index.php#kategori') ?>" class="ios-tab-item">
                <i class="bi bi-tags tab-icon"></i>
                <span>Kategori</span>
            </a>

            <a href="<?= base_url('laporan.php') ?>" class="ios-tab-item active">
                <i class="bi bi-file-earmark-text tab-icon"></i>
                <span>Laporan</span>
            </a>
        </nav>

    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
