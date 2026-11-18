<?php
require_once __DIR__ . '/header.php';

$msg = '';
$err = '';

// TAMBAH LAYANAN (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_layanan'])) {
    $nama  = sanitize($_POST['nama_layanan']);
    $icon  = sanitize($_POST['icon']);
    $desk  = sanitize($_POST['deskripsi']);
    $harga = (int)$_POST['harga_mulai'];

    if (!empty($nama) && !empty($desk)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO layanan (nama_layanan, icon, deskripsi, harga_mulai) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama, $icon, $desk, $harga]);
            $msg = 'Layanan baru berhasil ditambahkan!';
        } catch (PDOException $e) {
            $err = 'Gagal menambah layanan: ' . $e->getMessage();
        }
    }
}

// UBAH LAYANAN (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_layanan'])) {
    $id    = (int)$_POST['id_layanan'];
    $nama  = sanitize($_POST['nama_layanan']);
    $icon  = sanitize($_POST['icon']);
    $desk  = sanitize($_POST['deskripsi']);
    $harga = (int)$_POST['harga_mulai'];

    if (!empty($nama) && $id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE layanan SET nama_layanan = ?, icon = ?, deskripsi = ?, harga_mulai = ? WHERE id_layanan = ?");
            $stmt->execute([$nama, $icon, $desk, $harga, $id]);
            $msg = 'Layanan berhasil diperbarui!';
        } catch (PDOException $e) {
            $err = 'Gagal memperbarui layanan: ' . $e->getMessage();
        }
    }
}

// HAPUS LAYANAN (DELETE)
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $del = $pdo->prepare("DELETE FROM layanan WHERE id_layanan = ?");
    $del->execute([$id]);
    $msg = 'Layanan berhasil dihapus!';
}

$layanan_list = $pdo->query("SELECT * FROM layanan ORDER BY id_layanan ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Manajemen Layanan & Jasa</h3>
        <p class="text-secondary mb-0">Kelola paket solusi teknologi yang ditampilkan pada halaman publik.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahLayanan">
        <i class="bi bi-plus-circle me-1"></i> Tambah Layanan Baru
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
                    <th style="width: 70px;">Ikon</th>
                    <th>Nama Layanan</th>
                    <th>Deskripsi Layanan</th>
                    <th>Harga Mulai</th>
                    <th style="width: 120px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($layanan_list as $lay): ?>
                    <tr>
                        <td><?= $lay['id_layanan'] ?></td>
                        <td class="text-center fs-4 text-primary"><i class="bi <?= htmlspecialchars($lay['icon']) ?>"></i></td>
                        <td class="fw-bold"><?= htmlspecialchars($lay['nama_layanan']) ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($lay['deskripsi']) ?></small></td>
                        <td class="fw-semibold text-success"><?= format_rupiah($lay['harga_mulai']) ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditLay<?= $lay['id_layanan'] ?>" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <a href="layanan.php?hapus=<?= $lay['id_layanan'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus layanan ini?')" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>

                    <!-- MODAL EDIT -->
                    <div class="modal fade" id="modalEditLay<?= $lay['id_layanan'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Ubah Layanan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="id_layanan" value="<?= $lay['id_layanan'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nama Layanan</label>
                                        <input type="text" name="nama_layanan" class="form-control" value="<?= htmlspecialchars($lay['nama_layanan']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Ikon Bootstrap</label>
                                        <input type="text" name="icon" class="form-control" value="<?= htmlspecialchars($lay['icon']) ?>" required>
                                        <small class="text-muted">Contoh: <code>bi-code-slash</code>, <code>bi-cpu</code>, <code>bi-graph-up</code></small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Deskripsi</label>
                                        <textarea name="deskripsi" class="form-control" rows="3" required><?= htmlspecialchars($lay['deskripsi']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Harga Mulai (Rp)</label>
                                        <input type="number" name="harga_mulai" class="form-control" value="<?= $lay['harga_mulai'] ?>" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit_layanan" class="btn btn-warning fw-semibold">Perbarui</button>
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
<div class="modal fade" id="modalTambahLayanan" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Layanan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Layanan</label>
                    <input type="text" name="nama_layanan" class="form-control" placeholder="Contoh: Database Architecture" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ikon Bootstrap</label>
                    <input type="text" name="icon" class="form-control" value="bi-gear-fill" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3" placeholder="Rincian layanan yang diberikan..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Harga Mulai (Rp)</label>
                    <input type="number" name="harga_mulai" class="form-control" placeholder="1000000" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_layanan" class="btn btn-primary fw-semibold">Simpan Layanan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
