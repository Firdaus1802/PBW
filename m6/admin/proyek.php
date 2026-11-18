<?php
require_once __DIR__ . '/header.php';

$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$sql = "SELECT p.*, k.nama_kategori FROM proyek p 
        JOIN kategori k ON p.id_kategori = k.id_kategori";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE p.judul LIKE ? OR p.teknologi LIKE ? OR k.nama_kategori LIKE ?";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}

$sql .= " ORDER BY p.id_proyek DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$proyek_list = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Manajemen Proyek Portofolio</h3>
        <p class="text-secondary mb-0">Kelola data proyek (Create, Read, Update, Delete) yang terhubung ke basis data.</p>
    </div>
    <a href="proyek_tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Tambah Proyek Baru
    </a>
</div>

<!-- FORM PENCARIAN -->
<div class="card border-0 shadow-sm rounded-3 mb-4 p-3 bg-white">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Cari judul proyek, kategori, teknologi..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-secondary">Cari</button>
            <?php if (!empty($search)): ?>
                <a href="proyek.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- TABEL PROYEK -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 100px;">Gambar</th>
                    <th>Judul & Ringkasan</th>
                    <th>Kategori (FK)</th>
                    <th>Teknologi</th>
                    <th>Status</th>
                    <th>Tgl Selesai</th>
                    <th style="width: 140px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($proyek_list) > 0): ?>
                    <?php $no = 1; foreach ($proyek_list as $p): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <img src="../assets/img/<?= htmlspecialchars($p['gambar']) ?>" alt="" class="rounded border" style="width: 70px; height: 50px; object-fit: cover;" onerror="this.src='../assets/img/proyek_ml_bank.png'">
                            </td>
                            <td>
                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($p['judul']) ?></span>
                                <small class="text-muted d-block text-truncate" style="max-width: 300px;"><?= htmlspecialchars($p['ringkasan']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border"><?= htmlspecialchars($p['nama_kategori']) ?></span>
                            </td>
                            <td>
                                <small class="text-secondary"><?= htmlspecialchars($p['teknologi']) ?></small>
                            </td>
                            <td>
                                <span class="badge <?= ($p['status'] === 'Published') ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $p['status'] ?>
                                </span>
                            </td>
                            <td><small><?= date('d/m/Y', strtotime($p['tanggal_selesai'])) ?></small></td>
                            <td class="text-center">
                                <a href="../detail_proyek.php?id=<?= $p['id_proyek'] ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="proyek_edit.php?id=<?= $p['id_proyek'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="proyek_hapus.php?id=<?= $p['id_proyek'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus proyek ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-folder-x fs-3 d-block mb-2"></i>
                            Tidak ada data proyek ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
