<?php
// transaksi_edit.php - Sunting Data Transaksi (Dark & Light Mode + APK Ready)
require_once __DIR__ . '/config/koneksi.php';

// Proteksi Login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url("login.php"));
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: " . base_url("index.php"));
    exit;
}

// Ambil data transaksi lama
$stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id_transaksi = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    header("Location: " . base_url("index.php"));
    exit;
}

$error_msg = '';

// Proses Update Transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_transaksi'])) {
    $id_kategori = (int)$_POST['id_kategori'];
    $tanggal     = sanitize($_POST['tanggal']);
    $judul       = sanitize($_POST['judul']);
    $jenis       = sanitize($_POST['jenis']);
    $jumlah      = (float)$_POST['jumlah'];
    $catatan     = sanitize($_POST['catatan']);

    if (!empty($judul) && $id_kategori > 0 && $jumlah > 0 && !empty($tanggal)) {
        try {
            $stmt_update = $pdo->prepare("UPDATE transaksi SET id_kategori = ?, tanggal = ?, judul = ?, jenis = ?, jumlah = ?, catatan = ? WHERE id_transaksi = ?");
            $stmt_update->execute([$id_kategori, $tanggal, $judul, $jenis, $jumlah, $catatan, $id]);
            
            $redirect_target = (isset($_POST['from']) && $_POST['from'] === 'dashboard') 
                ? 'dashboard.php?msg=updated#transaksiSection' 
                : 'index.php?msg=updated';
            header("Location: " . base_url($redirect_target));
            exit;
        } catch (PDOException $e) {
            $error_msg = 'Gagal memperbarui transaksi: ' . $e->getMessage();
        }
    } else {
        $error_msg = 'Harap isi semua kolom wajib dengan benar.';
    }
}

// Master Kategori
$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY jenis ASC, nama_kategori ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0B0C10" id="metaThemeColor">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/icons/app-icon.png') ?>">
    <title>Edit Transaksi - DompetKu</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- iOS Theme CSS -->
    <link href="<?= base_url('assets/css/ios-theme.css?v=' . time()) ?>" rel="stylesheet">
    <!-- Theme Toggle Helper Script -->
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>
</head>
<body>

    <!-- iOS Glass Navbar -->
    <nav class="ios-navbar py-3 px-3 mb-4">
        <div class="container d-flex align-items-center justify-content-between" style="max-width: 660px;">
            <a href="<?= base_url('index.php') ?>" class="btn btn-ios-secondary btn-sm py-1 px-3 d-flex align-items-center gap-1">
                <i class="bi bi-chevron-left"></i> Kembali
            </a>
            <h6 class="mb-0 fw-bold">Edit Transaksi</h6>
            <button onclick="toggleTheme()" class="btn btn-ios-secondary btn-sm py-1 px-2 d-flex align-items-center" title="Ganti Mode Terang / Gelap">
                <i class="theme-toggle-icon fs-6"></i>
            </button>
        </div>
    </nav>

    <div class="container pb-5" style="max-width: 660px;">
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger rounded-4 mb-3"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="ios-card p-4">
            <form action="" method="POST">
                
                <!-- Sliding Segmented Control Toggle Jenis Transaksi -->
                <div class="mb-3 text-center">
                    <label class="form-label text-secondary small fw-medium d-block mb-1">Jenis Transaksi</label>
                    <div class="ios-segmented-control-slider-wrap" id="editJenisWrap">
                        <div class="ios-segmented-slider" id="editJenisSlider" style="<?= ($trx['jenis'] === 'Pemasukan') ? 'transform: translateX(100%);' : 'transform: translateX(0%);' ?>"></div>
                        <button type="button" class="ios-segmented-tab-btn <?= ($trx['jenis'] === 'Pengeluaran') ? 'active type-expense' : '' ?>" id="btnEditPengeluaran" onclick="selectEditJenis('Pengeluaran')">
                            <i class="bi bi-arrow-up-right me-1"></i> Pengeluaran
                        </button>
                        <button type="button" class="ios-segmented-tab-btn <?= ($trx['jenis'] === 'Pemasukan') ? 'active type-income' : '' ?>" id="btnEditPemasukan" onclick="selectEditJenis('Pemasukan')">
                            <i class="bi bi-arrow-down-left me-1"></i> Pemasukan
                        </button>
                    </div>
                    <input type="hidden" name="jenis" id="hiddenEditJenis" value="<?= htmlspecialchars($trx['jenis']) ?>">
                </div>

                <!-- Nominal Jumlah -->
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-medium">Nominal (Rp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text border-0 fw-bold text-secondary" style="background-color: var(--boss-input-bg); color: var(--boss-text-muted);">Rp</span>
                        <input type="number" name="jumlah" class="ios-input" value="<?= (int)$trx['jumlah'] ?>" min="100" step="100" required>
                    </div>
                </div>

                <!-- Judul Transaksi -->
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-medium">Keterangan Transaksi <span class="text-danger">*</span></label>
                    <input type="text" name="judul" class="ios-input" value="<?= htmlspecialchars($trx['judul']) ?>" required>
                </div>

                <!-- Pilihan Kategori -->
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-medium">Kategori <span class="text-danger">*</span></label>
                    <select name="id_kategori" id="selectKategori" class="ios-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategori_list as $kat): ?>
                            <option value="<?= $kat['id_kategori'] ?>" data-jenis="<?= $kat['jenis'] ?>" <?= ($kat['id_kategori'] == $trx['id_kategori']) ? 'selected' : '' ?>>
                                [<?= $kat['jenis'] ?>] <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tanggal Transaksi -->
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-medium">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal" class="ios-input" value="<?= htmlspecialchars($trx['tanggal']) ?>" required>
                </div>

                <!-- Catatan Tambahan -->
                <div class="mb-4">
                    <label class="form-label text-secondary small fw-medium">Catatan Tambahan</label>
                    <textarea name="catatan" class="ios-input" rows="3"><?= htmlspecialchars($trx['catatan'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= base_url('index.php') ?>" class="btn btn-ios-secondary w-50 text-center">Batal</a>
                    <button type="submit" name="update_transaksi" class="btn btn-ios-primary w-50">
                        Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectEditJenis(jenis) {
            const slider = document.getElementById('editJenisSlider');
            const btnExp = document.getElementById('btnEditPengeluaran');
            const btnInc = document.getElementById('btnEditPemasukan');
            const input = document.getElementById('hiddenEditJenis');

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

        function filterKategoriDropdown(jenisTerpilih) {
            const select = document.getElementById('selectKategori');
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
        document.addEventListener('DOMContentLoaded', () => {
            filterKategoriDropdown('<?= $trx['jenis'] ?>');
        });
    </script>
</body>
</html>
