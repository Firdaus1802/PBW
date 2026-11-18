<?php
require_once __DIR__ . '/header.php';

// Ambil Ringkasan Statistik
$count_proyek = $pdo->query("SELECT COUNT(*) FROM proyek")->fetchColumn();
$count_kategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
$count_layanan = $pdo->query("SELECT COUNT(*) FROM layanan")->fetchColumn();
$count_pesan = $pdo->query("SELECT COUNT(*) FROM pesan_kontak")->fetchColumn();

// Ambil 5 Proyek Terbaru
$recent_proyek = $pdo->query("SELECT p.*, k.nama_kategori FROM proyek p 
                              JOIN kategori k ON p.id_kategori = k.id_kategori 
                              ORDER BY p.id_proyek DESC LIMIT 5")->fetchAll();

// Ambil 5 Pesan Terbaru
$recent_pesan = $pdo->query("SELECT * FROM pesan_kontak ORDER BY id_pesan DESC LIMIT 5")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Dashboard Administrator</h3>
        <p class="text-secondary mb-0">Selamat datang di Content Management System (CMS) Portofolio Mahasiswa.</p>
    </div>
    <a href="proyek_tambah.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Tambah Proyek Baru
    </a>
</div>

<!-- KARTU STATISTIK -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="bg-primary-subtle text-primary p-3 rounded-3 me-3">
                    <i class="bi bi-folder-fill fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Total Proyek</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $count_proyek ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="bg-success-subtle text-success p-3 rounded-3 me-3">
                    <i class="bi bi-tags-fill fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Kategori Bidang</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $count_kategori ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="bg-warning-subtle text-warning p-3 rounded-3 me-3">
                    <i class="bi bi-briefcase-fill fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Layanan Solusi</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $count_layanan ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="bg-danger-subtle text-danger p-3 rounded-3 me-3">
                    <i class="bi bi-envelope-fill fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Pesan Klien</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $count_pesan ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- PROYEK TERAKHIR -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                <h5 class="fw-bold mb-0">Daftar Proyek Terbaru</h5>
                <a href="proyek.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Judul Proyek</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_proyek) > 0): ?>
                            <?php foreach ($recent_proyek as $rp): ?>
                                <tr>
                                    <td>
                                        <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($rp['judul']) ?></span>
                                        <small class="text-muted"><?= htmlspecialchars($rp['teknologi']) ?></small>
                                    </td>
                                    <td><span class="badge bg-info-subtle text-info border"><?= htmlspecialchars($rp['nama_kategori']) ?></span></td>
                                    <td>
                                        <span class="badge <?= ($rp['status'] === 'Published') ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $rp['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="proyek_edit.php?id=<?= $rp['id_proyek'] ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada proyek.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PESAN TERAKHIR -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                <h5 class="fw-bold mb-0">Pesan Masuk Terbaru</h5>
                <a href="pesan.php" class="btn btn-sm btn-outline-danger">Buka Kotak Masuk</a>
            </div>
            <div class="list-group list-group-flush">
                <?php if (count($recent_pesan) > 0): ?>
                    <?php foreach ($recent_pesan as $msg): ?>
                        <div class="list-group-item p-3">
                            <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($msg['nama_pengirim']) ?></h6>
                                <small class="text-muted"><?= date('d/m/Y', strtotime($msg['created_at'])) ?></small>
                            </div>
                            <small class="text-primary fw-semibold d-block mb-1"><?= htmlspecialchars($msg['subjek']) ?></small>
                            <p class="mb-1 text-secondary small text-truncate"><?= htmlspecialchars($msg['isi_pesan']) ?></p>
                            <span class="badge <?= ($msg['status_baca'] === 'Belum Dibaca') ? 'bg-danger' : 'bg-light text-dark border' ?>">
                                <?= $msg['status_baca'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">Belum ada pesan masuk.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
