<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "uks");

//Filter Bulan
$filter_bulan = $_GET['bulan'] ?? date('m');
$filter_tahun = $_GET['tahun'] ?? date('Y');

// Filter minimal kunjungan (3 / 4 / 5)
$min_kunjungan = isset($_GET['min_kunjungan']) ? intval($_GET['min_kunjungan']) : 3;
if (!in_array($min_kunjungan, [3, 4, 5])) {
    $min_kunjungan = 3;
}

// =====================================
// <!-- TOP KUNJUNGAN BULAN INI -->
// =====================================

// Siswa bulan ini
$sql_siswa = "
SELECT 
    t_siswa.NAMA_SISWA AS nama,
    COUNT(kunjungan.id_kunjungan) AS total
FROM kunjungan
INNER JOIN t_siswa ON kunjungan.id_pasien = t_siswa.NIS
WHERE kunjungan.jenis_pasien = 'siswa'
AND MONTH(kunjungan.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(kunjungan.tanggal_kunjungan) = '$filter_tahun'
GROUP BY t_siswa.NIS
HAVING total >= $min_kunjungan
ORDER BY total DESC
";
$top_siswa = $conn->query($sql_siswa);


// GURU BULAN INI

$sql_guru = "
SELECT 
    guru.nama AS nama,
    COUNT(kunjungan.id_kunjungan) AS total
FROM kunjungan
INNER JOIN guru ON kunjungan.id_pasien = guru.id_guru
WHERE kunjungan.jenis_pasien = 'guru'
AND MONTH(kunjungan.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(kunjungan.tanggal_kunjungan) = '$filter_tahun'
GROUP BY guru.id_guru
HAVING total >= $min_kunjungan
ORDER BY total DESC
";
$top_guru = $conn->query($sql_guru);

// KARYAWAN Bulan ini

$sql_karyawan = "
SELECT 
    karyawan.nama AS nama,
    COUNT(kunjungan.id_kunjungan) AS total
FROM kunjungan
INNER JOIN karyawan ON kunjungan.id_pasien = karyawan.id_karyawan
WHERE kunjungan.jenis_pasien = 'karyawan'
AND MONTH(kunjungan.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(kunjungan.tanggal_kunjungan) = '$filter_tahun'
GROUP BY karyawan.id_karyawan
HAVING total >= $min_kunjungan
ORDER BY total DESC
";
$top_karyawan = $conn->query($sql_karyawan);

// AUTO HAPUS DATA BELUM DIPERIKSA LEBIH DARI 24 JAM
$conn->query("
DELETE k FROM kunjungan k
LEFT JOIN pemeriksaan_klinis p 
ON k.id_kunjungan = p.id_kunjungan
WHERE p.id_kunjungan IS NULL
AND k.created_at < NOW() - INTERVAL 1 DAY
");


// KUNJUNGAN BELUM DIPERIKSA
$query_kunjungan = "
SELECT k.*, 
CASE 
    WHEN k.jenis_pasien='siswa' THEN s.NAMA_SISWA
    WHEN k.jenis_pasien='guru' THEN g.nama
    WHEN k.jenis_pasien='karyawan' THEN kr.nama
END AS nama_pasien

FROM kunjungan k
LEFT JOIN pemeriksaan_klinis p ON k.id_kunjungan = p.id_kunjungan
LEFT JOIN t_siswa s ON k.id_pasien = s.NIS
LEFT JOIN guru g ON k.id_pasien = g.id_guru
LEFT JOIN karyawan kr ON k.id_pasien = kr.id_karyawan

WHERE p.id_kunjungan IS NULL

ORDER BY k.id_kunjungan ASC
";
$result = $conn->query($query_kunjungan);




// === PAGINATION UNTUK SISWA, GURU, KARYAWAN ===
$page_siswa = $_GET['page_siswa'] ?? 1;
$page_guru = $_GET['page_guru'] ?? 1;
$page_karyawan = $_GET['page_karyawan'] ?? 1;

$limit = 5;

// 🔽 DATA SUDAH DIPERIKSA (BULAN INI)
// SISWA
$siswa = $conn->query(
    "
SELECT k.*, s.NAMA_SISWA as nama, p.jenis_penyakit
FROM pemeriksaan_klinis p
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
JOIN t_siswa s ON k.id_pasien = s.NIS
WHERE k.jenis_pasien='siswa'
AND MONTH(k.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'
ORDER BY k.id_kunjungan DESC
LIMIT $limit OFFSET " . ($page_siswa - 1) * $limit
);

// GURU
$guru = $conn->query(
    "
SELECT k.*, g.nama, p.jenis_penyakit
FROM pemeriksaan_klinis p
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
JOIN guru g ON k.id_pasien = g.id_guru
WHERE k.jenis_pasien='guru'
AND MONTH(k.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'
ORDER BY k.id_kunjungan DESC
LIMIT $limit OFFSET " . ($page_guru - 1) * $limit
);

// KARYAWAN
$karyawan = $conn->query(
    "
SELECT k.*, kr.nama, p.jenis_penyakit
FROM pemeriksaan_klinis p
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
JOIN karyawan kr ON k.id_pasien = kr.id_karyawan
WHERE k.jenis_pasien='karyawan'
AND MONTH(k.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'
ORDER BY k.id_kunjungan DESC
LIMIT $limit OFFSET " . ($page_karyawan - 1) * $limit
);

// Hitung total untuk pagination
$total_siswa_query = $conn->query("SELECT COUNT(*) as total FROM pemeriksaan_klinis p JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan WHERE k.jenis_pasien='siswa' AND MONTH(k.tanggal_kunjungan) = '$filter_bulan' AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'")->fetch_assoc();
$total_guru_query = $conn->query("SELECT COUNT(*) as total FROM pemeriksaan_klinis p JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan WHERE k.jenis_pasien='guru' AND MONTH(k.tanggal_kunjungan) = '$filter_bulan' AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'")->fetch_assoc();
$total_karyawan_query = $conn->query("SELECT COUNT(*) as total FROM pemeriksaan_klinis p JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan WHERE k.jenis_pasien='karyawan' AND MONTH(k.tanggal_kunjungan) = '$filter_bulan' AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'")->fetch_assoc();

$total_page_siswa = ceil($total_siswa_query['total'] / $limit);
$total_page_guru = ceil($total_guru_query['total'] / $limit);
$total_page_karyawan = ceil($total_karyawan_query['total'] / $limit);


$stat_siswa = $conn->query("
SELECT COUNT(*) as total FROM pemeriksaan_klinis p
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
WHERE k.jenis_pasien='siswa'
AND MONTH(k.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'
")->fetch_assoc();

$stat_guru = $conn->query("
SELECT COUNT(*) as total FROM pemeriksaan_klinis p
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
WHERE k.jenis_pasien='guru'
AND MONTH(k.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'
")->fetch_assoc();

$stat_karyawan = $conn->query("
SELECT COUNT(*) as total FROM pemeriksaan_klinis p
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
WHERE k.jenis_pasien='karyawan'
AND MONTH(k.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'
")->fetch_assoc();

//statistik perhitungan total
$total_siswa = $stat_siswa['total'];
$total_guru = $stat_guru['total'];
$total_karyawan = $stat_karyawan['total'];

$total_semua = $total_siswa + $total_guru + $total_karyawan;

if ($total_semua == 0) {
    $persen_siswa = 0;
    $persen_guru = 0;
    $persen_karyawan = 0;
} else {
    $persen_siswa = ($total_siswa / $total_semua) * 100;
    $persen_guru = ($total_guru / $total_semua) * 100;
    $persen_karyawan = ($total_karyawan / $total_semua) * 100;
}

$terbanyak = max($total_siswa, $total_guru, $total_karyawan);

$label = '';
if ($terbanyak == $total_siswa)
    $label = 'Siswa';
elseif ($terbanyak == $total_guru)
    $label = 'Guru';
else
    $label = 'Karyawan';

$total_semua = $total_siswa + $total_guru + $total_karyawan;

// hindari error bagi 0
if ($total_semua == 0) {
    $persen_siswa = 0;
    $persen_guru = 0;
    $persen_karyawan = 0;
} else {
    $persen_siswa = ($total_siswa / $total_semua) * 100;
    $persen_guru = ($total_guru / $total_semua) * 100;
    $persen_karyawan = ($total_karyawan / $total_semua) * 100;
}

// LIST OBAT YANG DIKELUARKAN
$query_obat_keluar = $conn->query("
SELECT o.nama_obat, COUNT(*) as jumlah
FROM pemeriksaan_obat po
JOIN pemeriksaan_klinis p ON po.id_pemeriksaan = p.id_pemeriksaan
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
JOIN obat o ON po.id_obat = o.id_obat
WHERE MONTH(k.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'
GROUP BY o.nama_obat
ORDER BY jumlah DESC
");

$list_obat = [];

while ($row = $query_obat_keluar->fetch_assoc()) {

    $list_obat[$row['nama_obat']] = $row['jumlah'];

}

// pagination list obat
$page_obat = $_GET['page_obat'] ?? 1;

$limit_obat = 5;
$total_data_obat = count($list_obat);
$total_page_obat = ceil($total_data_obat / $limit_obat);
$start_obat = ($page_obat - 1) * $limit_obat;

$list_obat = array_slice(
    $list_obat,
    $start_obat,
    $limit_obat,
    true
);

// PENYAKIT PALING BANYAK
$penyakit = $conn->query("
SELECT jenis_penyakit, COUNT(*) as jumlah
FROM pemeriksaan_klinis p
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
WHERE MONTH(k.tanggal_kunjungan) = '$filter_bulan'
AND YEAR(k.tanggal_kunjungan) = '$filter_tahun'
AND jenis_penyakit IS NOT NULL
AND jenis_penyakit != ''
GROUP BY jenis_penyakit
ORDER BY jumlah DESC
");

$list_penyakit = [];

while ($p = $penyakit->fetch_assoc()) {

    $list_penyakit[] = $p;

}

$page_penyakit = $_GET['page_penyakit'] ?? 1;
$limit_penyakit = 5;
$total_data_penyakit = count($list_penyakit);
$total_page_penyakit = ceil($total_data_penyakit / $limit_penyakit);
$start_penyakit = ($page_penyakit - 1) * $limit_penyakit;

$list_penyakit = array_slice(
    $list_penyakit,
    $start_penyakit,
    $limit_penyakit
);

?>
<!DOCTYPE html>
<html>

<head>
    <title>Dashboard Admin</title>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI';
            background: #f4f6f9;
        }

        .header {
            background: #1b4f9c;
            color: white;
            padding: 10px 25px;
            font-weight: bold;
            border-bottom: 5px solid #ffd700;

            display: flex;
            align-items: center;
            position: relative;
        }

        .header-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 20px;
            font-weight: bold;
            white-space: nowrap;
        }

        .container {
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        th {
            background: #1b4f9c;
            color: white;
            padding: 12px;
        }

        td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        tr {
            transition: 0.2s;
        }

        tr:hover {
            background: #f1f3f5;
        }

        .btn {
            background: #1b4f9c;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        .btn:hover {
            background: #ffd700;
            color: black;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }

        .belum {
            background: #ff4d4d;
            color: white;
        }

        .sudah {
            background: #28a745;
            color: white;
        }

        .logo {
            height: 55px;
            width: auto;
            cursor: pointer;
        }

        .logo-link {
            display: flex;
            align-items: center;
        }

        .profile-menu {
            margin-left: auto;
            position: relative;
        }

        .profile-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: white;
            color: #1b4f9c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            font-weight: bold;
        }

        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            background: white;
            min-width: 180px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            z-index: 999;
        }

        .dropdown a {
            display: block;
            padding: 12px 15px;
            text-decoration: none;
            color: black;
            font-size: 14px;
        }

        .dropdown a:hover {
            background: #f1f1f1;
        }
    </style>
</head>

<body>

    <div class="header">

        <a href="" class="logo-link">
            <img src="../assets/images/logo.png" alt="Logo UKS" class="logo">
        </a>

        <div class="header-title">
            DASHBOARD ADMIN UKS
        </div>

        <div class="profile-menu">

            <div class="profile-icon" onclick="toggleMenu()">
                👤
            </div>

            <div class="dropdown" id="dropdownMenu">

                <a href="reset_password.php">
                    Change Password
                </a>

                <a href="logout.php">
                    Logout
                </a>

            </div>

        </div>

    </div>
    <?php if (isset($_GET['status'])): ?>
        <div id="notif-sukses" style="
    background:#d4edda;
    padding:10px;
    border-radius:8px;
    margin:10px;
">
            Data berhasil disimpan
        </div>
    <?php endif; ?>
    <script>
        setTimeout(() => {

            const notif = document.getElementById("notif-sukses");

            if (notif) {
                notif.style.transition = "0.5s";
                notif.style.opacity = "0";

                setTimeout(() => {
                    notif.style.display = "none";
                }, 500);
            }

        }, 5000);
    </script>

    <div class="container">


        <h3>Daftar Kunjungan Belum Diperiksa</h3>

        <div style="
    max-height:350px;
    overflow-y:auto;
    background:white;
    border-radius:10px;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
">

            <table>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Pasien</th>
                    <th>Aksi</th>
                </tr>

                <?php
                $no = 1;
                while ($row = $result->fetch_assoc()):
                    ?>

                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $row['nama_pasien'] ?></td>
                        <td><?= strtoupper($row['jenis_pasien']) ?></td>

                        <td style="display:flex; gap:5px; justify-content:center;">

                            <a href="periksa.php?id=<?= $row['id_kunjungan'] ?>" class="btn">
                                Periksa
                            </a>

                            <button type="button" class="btn" style="background:#dc3545;"
                                onclick="bukaModalHapus(<?= $row['id_kunjungan'] ?>)">

                                Hapus

                            </button>

                        </td>
                    </tr>

                <?php endwhile; ?>

            </table>

        </div>

        <div style="
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:30px;
    margin-bottom:15px;
">

            <h3 style="margin:0;">
                Daftar Kunjungan
                <?= date('F', mktime(0, 0, 0, $filter_bulan, 1)) ?>
                <?= $filter_tahun ?>
            </h3>

            <form method="GET" style="display:flex; gap:10px;">

                <!-- BULAN -->
                <select name="bulan" style="
            padding:8px;
            border-radius:8px;
            border:1px solid #ccc;
        ">

                    <?php for ($i = 1; $i <= 12; $i++): ?>

                        <option value="<?= sprintf('%02d', $i) ?>" <?= ($filter_bulan == sprintf('%02d', $i)) ? 'selected' : '' ?>>

                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>

                        </option>

                    <?php endfor; ?>

                </select>

                <!-- TAHUN -->
                <select name="tahun" style="
            padding:8px;
            border-radius:8px;
            border:1px solid #ccc;
        ">

                    <?php

                    $tahun_query = $conn->query("
                SELECT DISTINCT YEAR(tanggal_kunjungan) as tahun
                FROM kunjungan
                ORDER BY tahun DESC
            ");

                    while ($data_tahun = $tahun_query->fetch_assoc()):

                        ?>

                        <option value="<?= $data_tahun['tahun'] ?>" <?= ($filter_tahun == $data_tahun['tahun']) ? 'selected' : '' ?>>

                            <?= $data_tahun['tahun'] ?>

                        </option>

                    <?php endwhile; ?>

                </select>

                <button type="submit" class="btn">
                    Tampilkan
                </button>

            </form>

        </div>

        <div style="display:flex; gap:20px; flex-wrap:wrap;">

            <!-- SISWA 2-->
            <div
                style="flex:1; background:white; padding:15px; border-radius:10px; display:flex; flex-direction:column;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h4 style="margin:0;">Siswa</h4>
                    <a href="semua_hal_admin/hal_siswa.php"
                        style="text-decoration:none; color:#1b4f9c; font-size:14px; font-weight:600;">
                        Daftar Periksa Siswa
                    </a>
                </div>

                <div style="flex:1; overflow-y:auto;">
                    <table style="width:100%; box-shadow:none;">
                        <tr>
                            <th style="background:none; color:black; text-align:left;">Nama</th>
                            <th style="background:none; color:black; text-align:right;">Jenis Penyakit</th>
                        </tr>
                        <?php if ($siswa->num_rows == 0): ?>

                            <tr>
                                <td colspan="2" style="
        text-align:center;
        padding:20px;
        color:gray;
    ">
                                    Belum ada pasien siswa
                                </td>
                            </tr>

                        <?php else: ?>
                            <?php while ($row = $siswa->fetch_assoc()): ?>
                                <tr>
                                    <td style="text-align:left;"><?= $row['nama'] ?></td>
                                    <td style="text-align:right;">
                                        <span style="background:#e9ecef; padding:4px 8px; border-radius:6px; font-size:12px;">
                                            <?= $row['jenis_penyakit'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Pagination Siswa -->
                <?php if ($total_page_siswa > 1): ?>
                    <div style="margin-top:15px; display:flex; justify-content:center; gap:6px;">
                        <?php for ($i = 1; $i <= $total_page_siswa; $i++): ?>
                            <a href="?bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>&page_siswa=<?= $i ?>"
                                style="padding:5px 10px; border-radius:6px; text-decoration:none; background:<?= ($page_siswa == $i) ? '#1b4f9c' : '#eee' ?>; color:<?= ($page_siswa == $i) ? 'white' : 'black' ?>;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- GURU -->
            <div
                style="flex:1; background:white; padding:15px; border-radius:10px; display:flex; flex-direction:column;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h4 style="margin:0;">Guru</h4>
                    <a href="semua_hal_admin/hal_guru.php"
                        style="text-decoration:none; color:#1b4f9c; font-size:14px; font-weight:600;">
                        Daftar Periksa Guru
                    </a>
                </div>

                <div style="flex:1; overflow-y:auto;">
                    <table style="width:100%; box-shadow:none;">
                        <tr>
                            <th style="background:none; color:black; text-align:left;">Nama</th>
                            <th style="background:none; color:black; text-align:right;">Jenis Penyakit</th>
                        </tr>
                        <?php if ($guru->num_rows == 0): ?>

                            <tr>
                                <td colspan="2" style="
        text-align:center;
        padding:20px;
        color:gray;
    ">
                                    Belum ada pasien guru
                                </td>
                            </tr>

                        <?php else: ?>
                            <?php while ($row = $guru->fetch_assoc()): ?>
                                <tr>
                                    <td style="text-align:left;"><?= $row['nama'] ?></td>
                                    <td style="text-align:right;">
                                        <span style="background:#e9ecef; padding:4px 8px; border-radius:6px; font-size:12px;">
                                            <?= $row['jenis_penyakit'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Pagination Guru -->
                <?php if ($total_page_guru > 1): ?>
                    <div style="margin-top:15px; display:flex; justify-content:center; gap:6px;">
                        <?php for ($i = 1; $i <= $total_page_guru; $i++): ?>
                            <a href="?bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>&page_guru=<?= $i ?>"
                                style="padding:5px 10px; border-radius:6px; text-decoration:none; background:<?= ($page_guru == $i) ? '#1b4f9c' : '#eee' ?>; color:<?= ($page_guru == $i) ? 'white' : 'black' ?>;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- KARYAWAN -->
            <div
                style="flex:1; background:white; padding:15px; border-radius:10px; display:flex; flex-direction:column;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h4 style="margin:0;">Karyawan</h4>
                    <a href="semua_hal_admin/hal_karyawan.php"
                        style="text-decoration:none; color:#1b4f9c; font-size:14px; font-weight:600;">
                        Daftar Periksa Karyawan
                    </a>
                </div>

                <div style="flex:1; overflow-y:auto;">
                    <table style="width:100%; box-shadow:none;">
                        <tr>
                            <th style="background:none; color:black; text-align:left;">Nama</th>
                            <th style="background:none; color:black; text-align:right;">Jenis Penyakit</th>
                        </tr>
                        <?php if ($karyawan->num_rows == 0): ?>

                            <tr>
                                <td colspan="2" style="
        text-align:center;
        padding:20px;
        color:gray;
    ">
                                    Belum ada pasien karyawan
                                </td>
                            </tr>

                        <?php else: ?>
                            <?php while ($row = $karyawan->fetch_assoc()): ?>
                                <tr>
                                    <td style="text-align:left;"><?= $row['nama'] ?></td>
                                    <td style="text-align:right;">
                                        <span style="background:#e9ecef; padding:4px 8px; border-radius:6px; font-size:12px;">
                                            <?= $row['jenis_penyakit'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Pagination Karyawan -->
                <?php if ($total_page_karyawan > 1): ?>
                    <div style="margin-top:15px; display:flex; justify-content:center; gap:6px;">
                        <?php for ($i = 1; $i <= $total_page_karyawan; $i++): ?>
                            <a href="?bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>&page_karyawan=<?= $i ?>"
                                style="padding:5px 10px; border-radius:6px; text-decoration:none; background:<?= ($page_karyawan == $i) ? '#1b4f9c' : '#eee' ?>; color:<?= ($page_karyawan == $i) ? 'white' : 'black' ?>;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>


        <!-- Statistik Bulan ini -->
        <div style="
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:30px;
    margin-bottom:15px;">

            <h3 style="margin:0;">
                Statistik
                <?= date('F', mktime(0, 0, 0, $filter_bulan, 1)) ?>
                <?= $filter_tahun ?>
            </h3>
        </div>

        <div style="display:flex; gap:20px; margin-bottom:20px; align-items:stretch; min-height: 340px;">

            <!-- 🔵 PIE CHART (KIRI) -->
            <div style="flex:1; background:white; padding:20px; border-radius:10px; text-align:center;">
                <h4 style="margin-top:0; margin-bottom:15px;">
                    Kunjungan Bulan Ini
                </h4>
                <div style="
            width:150px;
            height:150px;
            margin:0 auto;
            border-radius:50%;
            background: conic-gradient(
                #007bff 0% <?= $persen_siswa ?>%,
                #28a745 <?= $persen_siswa ?>% <?= $persen_siswa + $persen_guru ?>%,
                #dc3545 <?= $persen_siswa + $persen_guru ?>% 100%
            );
            display:flex;
            align-items:center;
            justify-content:center;
            color:white;
            font-weight:bold;
        ">
                    <?= $total_semua ?>
                </div>

                <p style="margin-top:10px;">
                    <b>Total Pasien</b>
                </p>

                <div style="font-size:13px;">
                    <div>🔵 Siswa (<?= $total_siswa ?>)</div>
                    <div>🟢 Guru (<?= $total_guru ?>)</div>
                    <div>🔴 Karyawan (<?= $total_karyawan ?>)</div>
                </div>
            </div>

            <!-- 📋 List Obat yang dikeluarkan -->
            <div id="obat" style="
    flex:1;
    min-width:0;
    background:white;
    padding:20px;
    border-radius:10px;
    display:flex;
    flex-direction:column;
    height: fit-content;
    min-height: 320px;   /* dikurangi biar tidak terlalu tinggi */
">

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h4 style="margin:0;">List Obat Yang Dikeluarkan</h4>
                    <a href="detail_v_obat.php"
                        style="text-decoration:none; color:#1b4f9c; font-size:14px; font-weight:600;">
                        Detail Obat
                    </a>
                </div>

                <div style="flex:1; display:flex; flex-direction:column;">
                    <?php if (empty($list_obat)): ?>
                        <p style="color:#999; text-align:center; margin:auto;">Belum ada obat yang dikeluarkan bulan ini</p>
                    <?php else: ?>
                        <?php foreach ($list_obat as $nama => $jumlah): ?>
                            <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:10px;
                    padding-bottom:8px;
                    border-bottom:1px solid #eee;
        ">
                                <span><?= htmlspecialchars($nama) ?></span>
                                <span
                                    style="background:#1b4f9c; color:white; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:600;">
                                    <?= $jumlah ?>x
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination tetap di bawah -->
                <?php if ($total_page_obat > 1): ?>
                    <div style="margin-top: auto; padding-top: 15px; display:flex; justify-content:center; gap:6px;">
                        <?php for ($i = 1; $i <= $total_page_obat; $i++): ?>
                            <a href="?bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>&page_obat=<?= $i ?>#obat"
                                style="padding:6px 12px; border-radius:6px; text-decoration:none; background:<?= ($page_obat == $i) ? '#1b4f9c' : '#eee' ?>; color:<?= ($page_obat == $i) ? 'white' : 'black' ?>;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            </div>

            <!-- 🦠 Kategori Keluhan -->
            <div id="penyakit" style="
    flex:1;
    min-width:0;
    background:white;
    padding:20px;
    border-radius:10px;
    display:flex;
    flex-direction:column;
    height: fit-content;
    min-height: 320px;
">

                <h4 style="margin:0 0 15px 0;">Kategori Keluhan</h4>

                <div style="flex:1; display:flex; flex-direction:column;">
                    <?php if (empty($list_penyakit)): ?>
                        <p style="color:#999; text-align:center; margin:auto;">Belum ada data keluhan bulan ini</p>
                    <?php else: ?>
                        <?php foreach ($list_penyakit as $row): ?>
                            <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    margin-bottom:10px;
                    padding-bottom:8px;
                    border-bottom:1px solid #eee;
        ">
                                <span style="flex:1;"><?= htmlspecialchars($row['jenis_penyakit']) ?></span>
                                <span
                                    style="background:#dc3545; color:white; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:600;">
                                    <?= $row['jumlah'] ?>x
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination tetap di bawah -->
                <?php if ($total_page_penyakit > 1): ?>
                    <div style="margin-top: auto; padding-top: 15px; display:flex; justify-content:center; gap:6px;">
                        <?php for ($i = 1; $i <= $total_page_penyakit; $i++): ?>
                            <a href="?bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>&page_penyakit=<?= $i ?>#penyakit"
                                style="padding:6px 12px; border-radius:6px; text-decoration:none; background:<?= ($page_penyakit == $i) ? '#1b4f9c' : '#eee' ?>; color:<?= ($page_penyakit == $i) ? 'white' : 'black' ?>;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>


        <!-- ================================= -->
        <!-- PASIEN SERING BERKUNJUNG -->
        <!-- ================================= -->

        <div style="
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:30px;
    margin-bottom:15px;
">
            <h3 style="margin:0;">Pasien Sering Berkunjung</h3>

            <form method="GET" style="display:flex; gap:10px; align-items:center;">
                <input type="hidden" name="bulan" value="<?= $filter_bulan ?>">
                <input type="hidden" name="tahun" value="<?= $filter_tahun ?>">

                <label style="font-size:14px;">Minimal kunjungan:</label>
                <select name="min_kunjungan" onchange="this.form.submit()"
                    style="padding:8px; border-radius:8px; border:1px solid #ccc;">
                    <option value="3" <?= $min_kunjungan == 3 ? 'selected' : '' ?>>3 kali</option>
                    <option value="4" <?= $min_kunjungan == 4 ? 'selected' : '' ?>>4 kali</option>
                    <option value="5" <?= $min_kunjungan == 5 ? 'selected' : '' ?>>≥ 5 kali</option>
                </select>
            </form>
        </div>

        <div style="display:flex; gap:20px; margin-bottom:20px; align-items:stretch;">

            <!-- SISWA -->
            <div
                style="flex:1; background:white; padding:20px; border-radius:10px; display:flex; flex-direction:column;">
                <h4 style="margin:0 0 15px 0;">🏫 Siswa</h4>
                <div style="flex:1;">
                    <?php
                    $no = 1;
                    if ($top_siswa->num_rows > 0):
                        while ($row = $top_siswa->fetch_assoc()):
                            ?>
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #eee;">
                                <span><?= $no ?>. <?= htmlspecialchars($row['nama']) ?></span>
                                <span
                                    style="background:#007bff; color:white; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:600;">
                                    <?= $row['total'] ?>x
                                </span>
                            </div>
                            <?php
                            $no++;
                        endwhile;
                    else:
                        ?>
                        <p style="color:#999; text-align:center; margin:20px 0;">Belum ada data siswa</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GURU -->
            <div
                style="flex:1; background:white; padding:20px; border-radius:10px; display:flex; flex-direction:column;">
                <h4 style="margin:0 0 15px 0;">👨‍🏫 Guru</h4>
                <div style="flex:1;">
                    <?php
                    $no = 1;
                    if ($top_guru->num_rows > 0):
                        while ($row = $top_guru->fetch_assoc()):
                            ?>
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #eee;">
                                <span><?= $no ?>. <?= htmlspecialchars($row['nama']) ?></span>
                                <span
                                    style="background:#28a745; color:white; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:600;">
                                    <?= $row['total'] ?>x
                                </span>
                            </div>
                            <?php
                            $no++;
                        endwhile;
                    else:
                        ?>
                        <p style="color:#999; text-align:center; margin:20px 0;">Belum ada data guru</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KARYAWAN -->
            <div
                style="flex:1; background:white; padding:20px; border-radius:10px; display:flex; flex-direction:column;">
                <h4 style="margin:0 0 15px 0;">👔 Karyawan</h4>
                <div style="flex:1;">
                    <?php
                    $no = 1;
                    if ($top_karyawan->num_rows > 0):
                        while ($row = $top_karyawan->fetch_assoc()):
                            ?>
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:8px; border-bottom:1px solid #eee;">
                                <span><?= $no ?>. <?= htmlspecialchars($row['nama']) ?></span>
                                <span
                                    style="background:#dc3545; color:white; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:600;">
                                    <?= $row['total'] ?>x
                                </span>
                            </div>
                            <?php
                            $no++;
                        endwhile;
                    else:
                        ?>
                        <p style="color:#999; text-align:center; margin:20px 0;">Belum ada data karyawan</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- MODAL HAPUS -->

        <div id="modalHapus" style="
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.5);
    justify-content:center;
    align-items:center;
    z-index:999;
">

            <div style="
        background:white;
        padding:25px;
        border-radius:12px;
        width:320px;
        text-align:center;
        box-shadow:0 4px 20px rgba(0,0,0,0.2);
    ">

                <h3 style="margin-top:0;">
                    Hapus Data
                </h3>

                <p>
                    Yakin ingin menghapus data ini?
                </p>

                <div style="
            display:flex;
            justify-content:center;
            gap:10px;
            margin-top:20px;
        ">

                    <button onclick="tutupModalHapus()" class="btn" style="background:gray;">

                        Tidak

                    </button>

                    <a id="btnHapusData" href="" class="btn" style="background:#dc3545;">

                        Ya, Hapus

                    </a>

                </div>

            </div>

        </div>

        <script>

            function bukaModalHapus(id) {

                document.getElementById('modalHapus').style.display = 'flex';

                document.getElementById('btnHapusData').href =
                    'hapus.php?id=' + id;

            }

            function tutupModalHapus() {

                document.getElementById('modalHapus').style.display = 'none';

            }

        </script>

        <script>
            function toggleMenu() {
                const menu = document.getElementById("dropdownMenu");
                if (menu.style.display === "block") {
                    menu.style.display = "none";
                } else {
                    menu.style.display = "block";
                }
            }

            // Tutup menu kalau klik di luar
            window.onclick = function (e) {
                if (!e.target.closest('.profile-menu')) {
                    const menu = document.getElementById("dropdownMenu");
                    if (menu) menu.style.display = "none";
                }
            }
        </script>
</body>

</html>