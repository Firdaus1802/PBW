<!DOCTYPE html>
<html>
<head>
    <style>
        table {
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: 15px;
        }
        th, td {
            border: 1.5px solid black;
            padding: 6px 16px;
        }
        th {
            background-color: #bfe0f7;
            font-weight: bold;
            text-align: center;
        }
        td {
            text-align: left;
        }
        .tidak-lulus {
            background-color: #f7a9a1;
        }
    </style>
</head>
<body>

<?php
function hitungRata($nilai) {
    return number_format(array_sum($nilai) / count($nilai), 1);
}

$mahasiswa = [
    [
        'nama' => 'Andi',
        'nilai' => [85, 84, 84]
    ],
    [
        'nama' => 'Eko',
        'nilai' => [40, 50]
    ]
];
?>

<table>
    <tr><th>Nama</th><th>Rata-rata</th><th>Grade</th></tr>

    <?php foreach ($mahasiswa as $mhs):
        $rata = hitungRata($mhs['nilai']);
        $grade = match(true) {
            $rata >= 85 => 'A',
            $rata >= 70 => 'B',
            default => 'C'
        };
        $status = ($grade === 'C') ? 'tidak-lulus' : 'lulus';
    ?>
    <tr class="<?= $status ?>">
        <td><?= $mhs['nama'] ?></td>
        <td><?= $rata ?></td>
        <td><?= $grade ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
