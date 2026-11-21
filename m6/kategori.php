<?php
require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url("login.php"));
    exit;
}

$alert_msg = '';

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

if (isset($_GET['hapus'])) {
    $id_del = (int)$_GET['hapus'];
    if ($id_del > 0) {
        try {
            $stmt_del = $pdo->prepare("DELETE FROM kategori WHERE id_kategori = ?");
            $stmt_del->execute([$id_del]);
            header("Location: " . base_url("index.php?msg=kat_deleted#kategori"));
            exit;
        } catch (PDOException $e) {
            header("Location: " . base_url("index.php?msg=kat_error#kategori"));
            exit;
        }
    }
}

$user_id = (int)$_SESSION['user_id'];
$stmt_kat = $pdo->prepare("SELECT k.*, COUNT(t.id_transaksi) AS total_trx 
                              FROM kategori k 
                              LEFT JOIN transaksi t ON k.id_kategori = t.id_kategori AND t.id_user = ? 
                              GROUP BY k.id_kategori 
                              ORDER BY k.jenis ASC, k.nama_kategori ASC");
$stmt_kat->execute([$user_id]);
$kategori_list = $stmt_kat->fetchAll();
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
    <title>Kelola Kategori - DompetKu</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <link href="<?= base_url('assets/css/ios-theme.css?v=' . time()) ?>" rel="stylesheet">
    
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>

    <script>
        if (window.innerWidth >= 992 && !window.location.search.includes('view=mobile')) {
            window.location.replace('<?= base_url("dashboard.php#kategoriSection") ?>');
        }
    </script>
</head>
<body>

    <div class="ios-app-shell">

        
        <nav class="ios-navbar py-2 px-3 px-md-4 no-print">
            <div class="container-xl d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= base_url('index.php#dompet') ?>" class="btn btn-ios-secondary btn-sm py-1 px-3 d-flex align-items-center gap-1" title="Kembali">
                        <i class="bi bi-chevron-left"></i> <span class="d-none d-sm-inline">Dashboard</span>
                    </a>
                    <div>
                        <h6 class="mb-0 fw-bold" style="letter-spacing: -0.3px;">Master Kategori</h6>
                        <small class="text-secondary" style="font-size: 10.5px;">DompetKu Mahasiswa</small>
                    </div>
                </div>

                
                <div class="d-none d-md-flex align-items-center gap-1 p-1" style="background-color: var(--boss-segmented-bg); border-radius: 14px; border: 1px solid var(--boss-border);">
                    <a href="<?= base_url('index.php#dompet') ?>" class="ios-desktop-tab-btn">
                        <i class="bi bi-wallet2 me-1"></i> Dompet
                    </a>
                    <a href="<?= base_url('kategori.php') ?>" class="ios-desktop-tab-btn active">
                        <i class="bi bi-tags me-1"></i> Kategori
                    </a>
                    <a href="<?= base_url('laporan.php') ?>" class="ios-desktop-tab-btn">
                        <i class="bi bi-file-earmark-text me-1"></i> Laporan
                    </a>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button onclick="toggleTheme()" class="btn btn-ios-secondary btn-sm py-1 px-2 d-flex align-items-center" title="Ganti Mode Terang / Gelap">
                        <i class="theme-toggle-icon fs-6"></i>
                    </button>
                    <button type="button" class="btn btn-ios-primary btn-sm py-1 px-3" data-bs-toggle="modal" data-bs-target="#modalTambahKat">
                        <i class="bi bi-plus-lg me-1"></i> Baru
                    </button>
                </div>
            </div>
        </nav>

        <div class="ios-main-content">
            
            <?= $alert_msg ?>

            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-secondary small fw-semibold text-uppercase" style="letter-spacing: 0.6px; font-size: 11px;">Daftar Kategori Kas</span>
                <span class="text-secondary small"><?= count($kategori_list) ?> Terdaftar</span>
            </div>

            
            <div class="ios-card-group">
                <?php foreach ($kategori_list as $kat): 
                    $is_in = ($kat['jenis'] === 'Pemasukan');
                ?>
                <div class="ios-list-row">
                    <div class="d-flex align-items-center gap-3">
                        <div class="ios-icon-box" style="width: 38px; height: 38px;">
                            <i class="bi <?= htmlspecialchars($kat['icon']) ?>"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size: 14.5px; color: var(--boss-text-primary);">
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge fw-semibold px-2 py-0.5" style="background: <?= $is_in ? 'rgba(48, 209, 88, 0.12)' : 'rgba(255, 69, 58, 0.12)' ?>; color: <?= $is_in ? 'var(--boss-green)' : 'var(--boss-red)' ?>; font-size: 10.5px; border-radius: 6px;">
                                    <?= $kat['jenis'] ?>
                                </span>
                                <span class="text-secondary small" style="font-size: 11px;">
                                    • <?= $kat['total_trx'] ?> transaksi
                                </span>
                            </div>
                        </div>
                    </div>

                    <div>
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

        
        <nav class="ios-tab-bar d-flex d-md-none no-print">
            <a href="<?= base_url('index.php#dompet') ?>" class="ios-tab-item">
                <i class="bi bi-wallet2 tab-icon"></i>
                <span>Dompet</span>
            </a>

            <a href="<?= base_url('index.php#kategori') ?>" class="ios-tab-item active">
                <i class="bi bi-tags tab-icon"></i>
                <span>Kategori</span>
            </a>

            <a href="<?= base_url('index.php#laporan') ?>" class="ios-tab-item">
                <i class="bi bi-file-earmark-text tab-icon"></i>
                <span>Laporan</span>
            </a>
        </nav>

    </div>

    
    <div class="modal fade" id="modalTambahKat" tabindex="-1" aria-labelledby="modalTambahKatLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content ios-modal p-2">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalTambahKatLabel" style="letter-spacing: -0.3px;">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="kategori.php" method="POST">
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

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
