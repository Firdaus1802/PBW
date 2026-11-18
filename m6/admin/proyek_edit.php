<?php
require_once __DIR__ . '/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM proyek WHERE id_proyek = ?");
$stmt->execute([$id]);
$proyek = $stmt->fetch();

if (!$proyek) {
    header('Location: proyek.php');
    exit;
}

$error = '';
$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul           = sanitize($_POST['judul']);
    $id_kategori     = (int)$_POST['id_kategori'];
    $ringkasan       = sanitize($_POST['ringkasan']);
    $deskripsi       = sanitize($_POST['deskripsi']);
    $teknologi       = sanitize($_POST['teknologi']);
    $link_demo       = sanitize($_POST['link_demo']);
    $link_github     = sanitize($_POST['link_github']);
    $status          = sanitize($_POST['status']);
    $tanggal_selesai = sanitize($_POST['tanggal_selesai']);
    $gambar          = $proyek['gambar'];

    // Handle Upload Gambar Baru
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['gambar']['tmp_name'];
        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['gambar']['name']);
        $target_dir = __DIR__ . '/../assets/img/';
        
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($file_tmp, $target_dir . $file_name)) {
                $gambar = $file_name;
            }
        } else {
            $error = 'Format file gambar tidak didukung.';
        }
    }

    if (empty($error)) {
        if (!empty($judul) && $id_kategori > 0 && !empty($ringkasan) && !empty($deskripsi)) {
            try {
                $stmt = $pdo->prepare("UPDATE proyek SET 
                    id_kategori = ?, judul = ?, ringkasan = ?, deskripsi = ?, 
                    teknologi = ?, link_demo = ?, link_github = ?, gambar = ?, 
                    status = ?, tanggal_selesai = ? 
                    WHERE id_proyek = ?");
                $stmt->execute([
                    $id_kategori, $judul, $ringkasan, $deskripsi, $teknologi, 
                    $link_demo, $link_github, $gambar, $status, $tanggal_selesai, $id
                ]);
                echo "<script>alert('Data proyek berhasil diperbarui!'); window.location.href='proyek.php';</script>";
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui database: ' . $e->getMessage();
            }
        } else {
            $error = 'Harap isi semua kolom wajib.';
        }
    }
}
?>

<div class="mb-4">
    <a href="proyek.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar</a>
    <h3 class="fw-bold text-dark mb-1">Ubah Data Proyek</h3>
    <p class="text-secondary mb-0">Implementasi operasi <strong>Update</strong> pada basis data relasional.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error ?>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
    <form method="POST" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Judul Proyek <span class="text-danger">*</span></label>
                <input type="text" name="judul" class="form-control" value="<?= htmlspecialchars($proyek['judul']) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Kategori Bidang (Relasi FK) <span class="text-danger">*</span></label>
                <select name="id_kategori" class="form-select" required>
                    <?php foreach ($kategori_list as $k): ?>
                        <option value="<?= $k['id_kategori'] ?>" <?= ($k['id_kategori'] == $proyek['id_kategori']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Ringkasan Singkat <span class="text-danger">*</span></label>
                <input type="text" name="ringkasan" class="form-control" value="<?= htmlspecialchars($proyek['ringkasan']) ?>" required>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Deskripsi Lengkap & Arsitektur <span class="text-danger">*</span></label>
                <textarea name="deskripsi" class="form-control" rows="5" required><?= htmlspecialchars($proyek['deskripsi']) ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Teknologi yang Digunakan</label>
                <input type="text" name="teknologi" class="form-control" value="<?= htmlspecialchars($proyek['teknologi']) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Status Publikasi</label>
                <select name="status" class="form-select">
                    <option value="Published" <?= ($proyek['status'] === 'Published') ? 'selected' : '' ?>>Published (Tampil)</option>
                    <option value="Draft" <?= ($proyek['status'] === 'Draft') ? 'selected' : '' ?>>Draft (Arsip)</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Tanggal Selesai Proyek</label>
                <input type="date" name="tanggal_selesai" class="form-control" value="<?= $proyek['tanggal_selesai'] ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">URL Tautan GitHub Repository</label>
                <input type="url" name="link_github" class="form-control" value="<?= htmlspecialchars($proyek['link_github']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">URL Tautan Live Demo</label>
                <input type="url" name="link_demo" class="form-control" value="<?= htmlspecialchars($proyek['link_demo']) ?>">
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Gambar Saat Ini:</label>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <img src="../assets/img/<?= htmlspecialchars($proyek['gambar']) ?>" alt="" class="rounded border" style="width: 120px; height: 80px; object-fit: cover;" onerror="this.src='../assets/img/proyek_ml_bank.png'">
                    <span class="text-muted small">File: <code><?= htmlspecialchars($proyek['gambar']) ?></code></span>
                </div>
                <label class="form-label fw-semibold">Ganti Gambar (Opsional)</label>
                <input type="file" name="gambar" class="form-control" accept="image/*">
                <small class="text-muted">Biarkan kosong jika tidak ingin mengganti gambar.</small>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-warning px-4 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> Perbarui Proyek
                </button>
                <a href="proyek.php" class="btn btn-secondary px-3">Batal</a>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
