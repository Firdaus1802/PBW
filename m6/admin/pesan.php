<?php
require_once __DIR__ . '/header.php';

// TANDAI SUDAH DIBACA
if (isset($_GET['baca'])) {
    $id = (int)$_GET['baca'];
    $stmt = $pdo->prepare("UPDATE pesan_kontak SET status_baca = 'Sudah Dibaca' WHERE id_pesan = ?");
    $stmt->execute([$id]);
    header('Location: pesan.php');
    exit;
}

// HAPUS PESAN
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM pesan_kontak WHERE id_pesan = ?");
    $stmt->execute([$id]);
    header('Location: pesan.php');
    exit;
}

$pesan_list = $pdo->query("SELECT * FROM pesan_kontak ORDER BY id_pesan DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Kotak Masuk Pesan Klien</h3>
        <p class="text-secondary mb-0">Kelola pertanyaan dan tawaran proyek dari formulir kontak publik.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Pengirim</th>
                    <th>Subjek & Pesan</th>
                    <th>Tanggal Masuk</th>
                    <th>Status</th>
                    <th style="width: 130px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pesan_list) > 0): ?>
                    <?php $no = 1; foreach ($pesan_list as $msg): ?>
                        <tr class="<?= ($msg['status_baca'] === 'Belum Dibaca') ? 'table-warning' : '' ?>">
                            <td><?= $no++ ?></td>
                            <td>
                                <span class="fw-bold text-dark d-block"><?= htmlspecialchars($msg['nama_pengirim']) ?></span>
                                <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($msg['email']) ?></small>
                            </td>
                            <td>
                                <span class="fw-semibold text-primary d-block"><?= htmlspecialchars($msg['subjek']) ?></span>
                                <small class="text-secondary"><?= nl2br(htmlspecialchars($msg['isi_pesan'])) ?></small>
                            </td>
                            <td><small><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></small></td>
                            <td>
                                <span class="badge <?= ($msg['status_baca'] === 'Belum Dibaca') ? 'bg-danger' : 'bg-success' ?>">
                                    <?= $msg['status_baca'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($msg['status_baca'] === 'Belum Dibaca'): ?>
                                    <a href="pesan.php?baca=<?= $msg['id_pesan'] ?>" class="btn btn-sm btn-outline-success" title="Tandai Sudah Dibaca">
                                        <i class="bi bi-check2-all"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="pesan.php?hapus=<?= $msg['id_pesan'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus pesan ini?')" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada pesan masuk di database.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
