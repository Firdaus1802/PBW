<?php
require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url("login.php"));
    exit;
}

$sql = "SELECT t.*, k.nama_kategori 
        FROM transaksi t 
        JOIN kategori k ON t.id_kategori = k.id_kategori 
        ORDER BY t.tanggal ASC, t.id_transaksi ASC";
$transaksi_list = $pdo->query($sql)->fetchAll();

$paylater_list = $pdo->query("SELECT * FROM kredit_paylater ORDER BY status ASC, jatuh_tempo ASC")->fetchAll();

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

$filename = "Laporan_Kas_DompetKu_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body { font-family: Calibri, Arial, sans-serif; }
        .header-title { font-size: 16pt; font-weight: bold; text-align: center; }
        .sub-title { font-size: 11pt; color: #555555; text-align: center; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th { background-color: #1F2230; color: #FFFFFF; border: 1px solid #333; padding: 8px 12px; font-size: 11pt; }
        td { border: 1px solid #CCCCCC; padding: 6px 10px; font-size: 10pt; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .masuk { color: #16A34A; font-weight: bold; }
        .keluar { color: #DC2626; font-weight: bold; }
        .bold { font-weight: bold; }
        .summary-box { background-color: #F8FAFC; border: 1px solid #CBD5E1; padding: 10px; font-size: 11pt; }
    </style>
</head>
<body>

    <div class="header-title">LAPORAN ARUS KAS & PENGELUARAN MAHASISWA</div>
    <div class="sub-title">Aplikasi DompetKu • Penanggung Jawab: <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Firdaus Dhuha Prabowo') ?></div>
    <div class="sub-title">Waktu Unduh: <?= date('d/m/Y H:i:s') ?> WIB</div>

    <br>

    
    <table style="width: 50%;">
        <tr>
            <th colspan="2" style="background-color: #0A84FF;">RINGKASAN ARUS KAS</th>
        </tr>
        <tr>
            <td><strong>Total Pemasukan:</strong></td>
            <td class="text-end masuk">+ Rp <?= number_format($total_masuk, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td><strong>Total Pengeluaran:</strong></td>
            <td class="text-end keluar">- Rp <?= number_format($total_keluar, 0, ',', '.') ?></td>
        </tr>
        <tr style="background-color: #E2E8F0;">
            <td><strong>Sisa Saldo Kas Bersih:</strong></td>
            <td class="text-end bold">Rp <?= number_format($saldo, 0, ',', '.') ?></td>
        </tr>
    </table>

    <br><br>

    
    <div class="bold" style="font-size: 12pt;">A. RIWAYAT TRANSAKSI KAS</div>
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th style="width: 120px;">Tanggal</th>
                <th style="width: 200px;">Kategori</th>
                <th style="width: 280px;">Judul Transaksi</th>
                <th style="width: 100px;">Jenis</th>
                <th style="width: 140px;">Nominal (Rp)</th>
                <th style="width: 240px;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($transaksi_list as $row): 
                $is_masuk = ($row['jenis'] === 'Pemasukan');
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= $row['tanggal'] ?></td>
                <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                <td><?= htmlspecialchars($row['judul']) ?></td>
                <td class="text-center bold <?= $is_masuk ? 'masuk' : 'keluar' ?>"><?= $row['jenis'] ?></td>
                <td class="text-end <?= $is_masuk ? 'masuk' : 'keluar' ?>">
                    <?= ($is_masuk ? '' : '-') ?><?= number_format($row['jumlah'], 2, ',', '.') ?>
                </td>
                <td><?= htmlspecialchars($row['catatan'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #F1F5F9; font-weight: bold;">
                <td colspan="5" class="text-end">Total Saldo Akhir:</td>
                <td class="text-end">Rp <?= number_format($saldo, 2, ',', '.') ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <br><br>

    
    <div class="bold" style="font-size: 12pt;">B. PENCATATAN TANGGUNGAN KREDIT & PAYLATER</div>
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th style="width: 280px;">Nama Tagihan / Cicilan</th>
                <th style="width: 140px;">Jumlah (Rp)</th>
                <th style="width: 120px;">Jatuh Tempo</th>
                <th style="width: 120px;">Status</th>
                <th style="width: 240px;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no_k = 1; foreach ($paylater_list as $k): ?>
            <tr>
                <td class="text-center"><?= $no_k++ ?></td>
                <td><?= htmlspecialchars($k['nama_tagihan']) ?></td>
                <td class="text-end bold">Rp <?= number_format($k['jumlah_tagihan'], 2, ',', '.') ?></td>
                <td class="text-center"><?= $k['jatuh_tempo'] ?></td>
                <td class="text-center bold" style="color: <?= $k['status'] === 'Lunas' ? '#16A34A' : '#DC2626' ?>;">
                    <?= $k['status'] ?>
                </td>
                <td><?= htmlspecialchars($k['catatan'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
