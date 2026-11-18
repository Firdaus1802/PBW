<?php
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

// TAMBAH KATEGORI (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kategori'])) {
    $nama = sanitize($_POST['nama_kategori']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama)));
    $ket  = sanitize($_POST['keterangan']);

    if (!empty($nama)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, slug, keterangan) VALUES (?, ?, ?)");
            $stmt->execute([$nama, $slug, $ket]);
            $msg = 'Kategori baru berhasil ditambahkan!';
        } catch (PDOException $e) {
            $err = 'Gagal menambah kategori: ' . $e->getMessage();
        }
    }
}

// UBAH KATEGORI (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_kategori'])) {
    $id   = (int)$_POST['id_kategori'];
    $nama = sanitize($_POST['nama_kategori']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama)));
    $ket  = sanitize($_POST['keterangan']);

    if (!empty($nama) && $id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori = ?, slug = ?, keterangan = ? WHERE id_kategori = ?");
            $stmt->execute([$nama, $slug, $ket, $id]);
            $msg = 'Kategori berhasil diperbarui!';
        } catch (PDOException $e) {
            $err = 'Gagal memperbarui kategori: ' . $e->getMessage();
        }
    }
}

// HAPUS KATEGORI (DELETE)
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    // Periksa apakah masih ada proyek yang berelasi
    $cek = $pdo->prepare("SELECT COUNT(*) FROM proyek WHERE id_kategori = ?");
    $cek->execute([$id]);
    if ($cek->fetchColumn() > 0) {
        $err = 'Kategori tidak dapat dihapus karena masih memiliki data proyek berelasi (Integritas Relasi FK).';
    } else {
        $del = $pdo->prepare("DELETE FROM kategori WHERE id_kategori = ?");
        $del->execute([$id]);
        $msg = 'Kategori berhasil dihapus!';
    }
}

$kategori_list = $pdo->query("SELECT k.*, COUNT(p.id_proyek) as total_proyek 
                             FROM kategori k 
                             LEFT JOIN proyek p ON k.id_kategori = p.id_kategori 
                             GROUP BY k.id_kategori 
                             ORDER BY k.id_kategori ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Manajemen Kategori Proyek</h3>
        <p class="text-secondary mb-0">Tabel induk (Parent Table) yang berelasi dengan tabel proyek.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-circle me-1"></i> Tambah Kategori
    </button>
</div>

<?php if (!empty($msg)): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $err ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Nama Kategori</th>
                    <th>Slug URL</th>
                    <th>Keterangan / Deskripsi</th>
                    <th>Jumlah Proyek</th>
                    <th style="width: 120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kategori_list as $k): ?>
                    <tr>
                        <td><?= $k['id_kategori'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($k['nama_kategori']) ?></td>
                        <td><code><?= htmlspecialchars($k['slug']) ?></code></td>
                        <td><small class="text-muted"><?= htmlspecialchars($k['keterangan']) ?></small></td>
                        <td><span class="badge bg-primary rounded-pill"><?= $k['total_proyek'] ?> Proyek</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $k['id_kategori'] ?>" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <a href="kategori.php?hapus=<?= $k['id_kategori'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kategori ini?')" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>

                    <!-- MODAL EDIT -->
                    <div class="modal fade" id="modalEdit<?= $k['id_kategori'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Ubah Kategori</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id_kategori" value="<?= $k['id_kategori'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nama Kategori</label>
                                        <input type="text" name="nama_kategori" class="form-control" value="<?= htmlspecialchars($k['nama_kategori']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Keterangan</label>
                                        <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($k['keterangan']) ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit_kategori" class="btn btn-warning fw-semibold">Perbarui</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Kategori Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Cloud Architecture" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Keterangan Singkat</label>
                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Deskripsi fokus domain kategori..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_kategori" class="btn btn-primary fw-semibold">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
