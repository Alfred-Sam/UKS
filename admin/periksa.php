<?php
session_start();
$conn = new mysqli("localhost", "root", "", "uks");

$id = intval($_GET['id'] ?? 0);

$query = $conn->query("SELECT * FROM kunjungan WHERE id_kunjungan = '$id'");
$kunjungan = $query->fetch_assoc();

$cek = $conn->query("SELECT * FROM pemeriksaan_klinis WHERE id_kunjungan = '$id'");
$data_pemeriksaan = $cek->fetch_assoc();

$pasien = null;

if ($kunjungan) {
    if ($kunjungan['jenis_pasien'] == 'siswa') {
        $pasien = $conn->query("SELECT * FROM t_siswa WHERE NIS='" . $kunjungan['id_pasien'] . "'")->fetch_assoc();
    } elseif ($kunjungan['jenis_pasien'] == 'guru') {
        $pasien = $conn->query("SELECT * FROM guru WHERE id_guru='" . $kunjungan['id_pasien'] . "'")->fetch_assoc();
    } elseif ($kunjungan['jenis_pasien'] == 'karyawan') {
        $pasien = $conn->query("SELECT * FROM karyawan WHERE id_karyawan='" . $kunjungan['id_pasien'] . "'")->fetch_assoc();
    }
}

// Ambil obat yang sudah dipilih (untuk edit)
$obat_terpilih = [];
if ($data_pemeriksaan) {
    $res = $conn->query("SELECT id_obat FROM pemeriksaan_obat WHERE id_pemeriksaan = '" . $data_pemeriksaan['id_pemeriksaan'] . "'");
    while ($row = $res->fetch_assoc()) {
        $obat_terpilih[] = $row['id_obat'];
    }
}

// SIMPAN
if (isset($_POST['simpan'])) {

    $catatan = $conn->real_escape_string($_POST['catatan']);
    $status = $conn->real_escape_string($_POST['status']);
    $jenis_penyakit = $conn->real_escape_string($_POST['jenis_penyakit']);
    $id_obat_array = $_POST['id_obat'] ?? [];

    if ($data_pemeriksaan) {
        $id_pemeriksaan = $data_pemeriksaan['id_pemeriksaan'];

        $conn->query("UPDATE pemeriksaan_klinis SET 
                    catatan_admin = '$catatan',
                    status_pasien = '$status',
                    jenis_penyakit = '$jenis_penyakit'
                    WHERE id_kunjungan = '$id'");

        // Hapus obat lama
        $conn->query("DELETE FROM pemeriksaan_obat WHERE id_pemeriksaan = '$id_pemeriksaan'");

    } else {
        $conn->query("INSERT INTO pemeriksaan_klinis 
                    (id_kunjungan, catatan_admin, status_pasien, jenis_penyakit)
                    VALUES ('$id', '$catatan', '$status', '$jenis_penyakit')");

        $id_pemeriksaan = $conn->insert_id;
    }

    // Insert semua obat yang dipilih
    foreach ($id_obat_array as $id_obat) {
        $id_obat = intval($id_obat);
        if ($id_obat > 0) {
            $conn->query("INSERT INTO pemeriksaan_obat (id_pemeriksaan, id_obat) 
                     VALUES ('$id_pemeriksaan', '$id_obat')");
        }
    }

    header("Location: ../admin/dashboard.php?status=sukses");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Periksa Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Style tetap sama seperti sebelumnya */
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
            margin: 10px 0 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            width: 100%;
            background: #1b4f9c;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #ffd700;
            color: black;
        }
    </style>
</head>

<body>

    <div class="header">INPUT PEMERIKSAAN</div>

    <div class="form-box">
        <h2>Form Pemeriksaan</h2>

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
            <p style="color:red;">Data pasien tidak ditemukan</p>
        <?php endif; ?>

        <form method="POST">
            <label>Catatan</label>
            <textarea name="catatan" required><?= $data_pemeriksaan['catatan_admin'] ?? '' ?></textarea>

            <label>Kategori Keluhan</label>
            <select name="jenis_penyakit" id="jenis_penyakit" required>
                <option value="">-- Pilih Kategori Keluhan --</option>
                <?php
                $kategori = ["Pusing", "Demam", "Batuk / Flu", "Sakit Perut", "Luka Ringan", "Nyeri Haid", "Cedera", "Kelelahan", "Mimisan", "Alergi", "Iritasi Mata", "Tegang / Stres Ringan", "Keluhan Kulit Ringan", "Sakit Gigi", "Masalah Tenggorokan", "Masuk Angin", "Lainnya"];
                foreach ($kategori as $item):
                    $selected = ($data_pemeriksaan['jenis_penyakit'] ?? '') == $item ? 'selected' : '';
                    ?>
                    <option value="<?= $item ?>" <?= $selected ?>><?= $item ?></option>
                <?php endforeach; ?>
            </select>

            <label style="display:block; margin-top:15px;">
                Obat Yang Diberikan <small>(Ctrl/Command untuk pilih banyak)</small>
            </label>
            <select name="id_obat[]" id="id_obat" multiple style="width:100%; height:160px;">  <!-- error -->
                <?php
                $query_obat = $conn->query("SELECT * FROM obat ORDER BY nama_obat ASC");
                while ($obat = $query_obat->fetch_assoc()):
                    $selected = in_array($obat['id_obat'], $obat_terpilih) ? 'selected' : '';
                    ?>
                    <option value="<?= $obat['id_obat'] ?>" <?= $selected ?>>
                        <?= $obat['nama_obat'] ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label style="display:block; margin-top:18px;">Status Pasien</label>
            <select name="status" required>
                <option value="Istirahat di UKS" <?= (($data_pemeriksaan['status_pasien'] ?? '') == 'Istirahat di UKS') ? 'selected' : '' ?>>Istirahat di UKS</option>
                <option value="Dipulangkan" <?= (($data_pemeriksaan['status_pasien'] ?? '') == 'Dipulangkan') ? 'selected' : '' ?>>Dipulangkan</option>
                <option value="Dirujuk" <?= (($data_pemeriksaan['status_pasien'] ?? '') == 'Dirujuk') ? 'selected' : '' ?>>
                    Dirujuk</option>
                <option value="Kembali melanjutkan aktivitas" <?= (($data_pemeriksaan['status_pasien'] ?? '') == 'Kembali melanjutkan aktivitas') ? 'selected' : '' ?>>Kembali melanjutkan aktivitas</option>
            </select>

            <button type="submit" name="simpan">Simpan Pemeriksaan</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#id_obat').select2({ placeholder: "Cari obat...", width: '100%' });
            $('#jenis_penyakit').select2({ placeholder: "Cari kategori...", width: '100%' });
        });
    </script>

</body>

</html>