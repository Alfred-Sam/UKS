<?php
session_start();
$conn = new mysqli("localhost", "root", "", "uks");

$id = intval($_GET['id'] ?? 0);

$query = $conn->query("SELECT * FROM kunjungan WHERE id_kunjungan='$id'");
$kunjungan = $query->fetch_assoc();

$cek = $conn->query("SELECT * FROM pemeriksaan_klinis WHERE id_kunjungan='$id'");
$data_pemeriksaan = $cek->fetch_assoc();

$obat_list = [];

if ($kunjungan) {
    if ($kunjungan['jenis_pasien'] == 'siswa') {
        $pasien = $conn->query("SELECT * FROM t_siswa WHERE NIS='" . $kunjungan['id_pasien'] . "'")->fetch_assoc();
    } elseif ($kunjungan['jenis_pasien'] == 'guru') {
        $pasien = $conn->query("SELECT * FROM guru WHERE id_guru='" . $kunjungan['id_pasien'] . "'")->fetch_assoc();
    } elseif ($kunjungan['jenis_pasien'] == 'karyawan') {
        $pasien = $conn->query("SELECT * FROM karyawan WHERE id_karyawan='" . $kunjungan['id_pasien'] . "'")->fetch_assoc();
    }
}

// Ambil semua obat
if ($data_pemeriksaan) {
    $obat_query = $conn->query("
        SELECT o.nama_obat, po.jumlah 
        FROM pemeriksaan_obat po
        JOIN obat o ON po.id_obat = o.id_obat
        WHERE po.id_pemeriksaan = '" . $data_pemeriksaan['id_pemeriksaan'] . "'
        ORDER BY po.id ASC
    ");

    while ($row = $obat_query->fetch_assoc()) {
        $qty = intval($row['jumlah'] ?? 1);
        $obat_list[] = ($qty > 1) ? $row['nama_obat'] . " ({$qty})" : $row['nama_obat'];
    }
}

$halaman_kembali = '';

if ($kunjungan) {
    if ($kunjungan['jenis_pasien'] == 'siswa') {
        $halaman_kembali = 'semua_hal_admin/hal_siswa.php';
    } elseif ($kunjungan['jenis_pasien'] == 'guru') {
        $halaman_kembali = 'semua_hal_admin/hal_guru.php';
    } elseif ($kunjungan['jenis_pasien'] == 'karyawan') {
        $halaman_kembali = 'semua_hal_admin/hal_karyawan.php';
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Lihat Data Pasien</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background: #1f5faa;
            margin: 0;
        }

        .header {
            background: #1b4f9c;
            color: white;
            text-align: center;
            padding: 20px;
            font-weight: bold;
            border-bottom: 5px solid #ffd700;
        }

        .form-box {
            background: white;
            max-width: 500px;
            margin: 40px auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        h2 {
            text-align: center;
        }

        textarea,
        input,
        select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: #f1f1f1;
            font-family: 'Segoe UI';
        }

        .btn {
            width: 100%;
            background: #1b4f9c;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            background: #ffd700;
            color: black;
        }
    </style>
</head>

<body>

    <div class="header">
        DETAIL PEMERIKSAAN
    </div>

    <div class="form-box">
        <h2>Data Pasien</h2>

        <?php if ($pasien && $kunjungan): ?>

            <?php if ($kunjungan['jenis_pasien'] == 'siswa'): ?>
                <p><b>NIS:</b> <?= $pasien['NIS']; ?></p>
                <p><b>Nama:</b> <?= $pasien['NAMA_SISWA']; ?></p>
                <p><b>Jenis Kelamin:</b> <?= $pasien['JENIS_KELAMIN']; ?></p>

            <?php elseif ($kunjungan['jenis_pasien'] == 'guru'): ?>
                <p><b>Nama:</b> <?= $pasien['nama']; ?></p>
                <p><b>Jenis Kelamin:</b> <?= $pasien['jenis_kelamin']; ?></p>

            <?php elseif ($kunjungan['jenis_pasien'] == 'karyawan'): ?>
                <p><b>Nama:</b> <?= $pasien['nama']; ?></p>
                <p><b>Jenis Kelamin:</b> <?= $pasien['jenis_kelamin']; ?></p>
            <?php endif; ?>

            <p><b>Keluhan:</b> <?= $kunjungan['keluhan']; ?></p>

        <?php else: ?>
            <p style="color:red;">Data tidak ditemukan</p>
        <?php endif; ?>

        <hr>

        <h2>Hasil Pemeriksaan</h2>

        <label>Catatan</label>
        <textarea readonly><?= $data_pemeriksaan['catatan_admin'] ?? '-' ?></textarea>

        <label>Jenis Penyakit</label>
        <input type="text" readonly value="<?= $data_pemeriksaan['jenis_penyakit'] ?? '-' ?>">

        <label>Obat</label>
        <input type="text" readonly value="<?= !empty($obat_list) ? implode(', ', $obat_list) : '-' ?>">

        <label>Status Pasien</label>
        <input type="text" readonly value="<?= $data_pemeriksaan['status_pasien'] ?? '-' ?>">

        <br><br>

        <a href="<?= $halaman_kembali ?>" class="btn btn-center"
            style="display:block; margin:20px auto; width:200px; text-align:center;">
            Kembali
        </a>
        <p style="font-size:13px; color:#666; margin-top:20px; text-align:left;">
            Tanggal Kunjungan:
            <?= date('d-m-Y', strtotime($kunjungan['tanggal_kunjungan'])) ?>
            (<?= date('H:i', strtotime($kunjungan['tanggal_kunjungan'])) ?>)
        </p>

    </div>

</body>

</html>