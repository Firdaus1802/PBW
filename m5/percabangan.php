<?php
$nama = "Firdaus";
$nilai = 40;

// Evaluasi Rentang
if ($nilai >= 85):
    $grade = "A";
elseif ($nilai >= 70):
    $grade = "B";
else:
    $grade = "C";
endif;

// Evaluasi Ketat (Match)
$kategori = match ($grade) {
    "A" => "Sangat Baik",
    "B" => "Baik",
    default => "Cukup",
};
?>
<p>Saya dengan nama: <?= $nama ?> Nilai <?= $nilai ?> mendapat grade <?= $grade ?>.</p>
<p>Kategori: <?= $kategori ?></p>

