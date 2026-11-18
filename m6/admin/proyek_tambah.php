<?php
require_once __DIR__ . '/header.php';

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

    // Handle Upload Gambar
    $gambar = 'proyek_ml_bank.png'; // default fallback
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
            $error = 'Format file gambar tidak didukung (harus JPG, PNG, atau WEBP).';
        }
    }

    if (empty($error)) {
        if (!empty($judul) && $id_kategori > 0 && !empty($ringkasan) && !empty($deskripsi)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO proyek 
                    (id_kategori, judul, ringkasan, deskripsi, teknologi, link_demo, link_github, gambar, status, tanggal_selesai) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $id_kategori, $judul, $ringkasan, $deskripsi, $teknologi, 
                    $link_demo, $link_github, $gambar, $status, $tanggal_selesai
                ]);
                echo "<script>alert('Proyek berhasil ditambahkan!'); window.location.href='proyek.php';</script>";
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan ke database: ' . $e->getMessage();
            }
        } else {
            $error = 'Harap isi semua kolom wajib bertanda bintang (*).';
        }
    }
}
?>

<div class="mb-4">
    <a href="proyek.php" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar</a>
    <h3 class="fw-bold text-dark mb-1">Tambah Data Proyek Baru</h3>
    <p class="text-secondary mb-0">Implementasi operasi <strong>Create</strong> pada basis data relasional.</p>
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
                <input type="text" name="judul" class="form-control" placeholder="Contoh: Sistem Informasi Kasir Retail" required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Kategori Bidang (Relasi FK) <span class="text-danger">*</span></label>
                <select name="id_kategori" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($kategori_list as $k): ?>
                        <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Ringkasan Singkat <span class="text-danger">*</span></label>
                <input type="text" name="ringkasan" class="form-control" placeholder="Deskripsi ringkas 1-2 kalimat untuk kartu depan" required>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Deskripsi Lengkap & Arsitektur <span class="text-danger">*</span></label>
                <textarea name="deskripsi" class="form-control" rows="5" placeholder="Penjelasan fitur lengkap, arsitektur kode, dan algoritma..." required></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Teknologi yang Digunakan</label>
                <input type="text" name="teknologi" class="form-control" placeholder="Contoh: PHP, MySQL, Bootstrap 5, JavaScript" required>
                <small class="text-muted">Pisahkan dengan tanda koma (,)</small>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Status Publikasi</label>
                <select name="status" class="form-select">
                    <option value="Published" selected>Published (Tampil)</option>
                    <option value="Draft">Draft (Arsip)</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Tanggal Selesai Proyek</label>
                <input type="date" name="tanggal_selesai" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">URL Tautan GitHub Repository</label>
                <input type="url" name="link_github" class="form-control" placeholder="https://github.com/Firdaus1802/...">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">URL Tautan Live Demo</label>
                <input type="url" name="link_demo" class="form-control" placeholder="https://portofolio-daus-virid.vercel.app">
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Unggah Gambar / Tangkapan Layar Proyek</label>
                <input type="file" name="gambar" class="form-control" accept="image/*">
                <small class="text-muted">Format: JPG, PNG, atau WEBP. Jika dikosongkan, gambar bawaan akan digunakan.</small>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Proyek ke Database
                </button>
                <a href="proyek.php" class="btn btn-secondary px-3">Batal</a>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
