<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "uks");

// ambil daftar tahun yang ada datanya
$tahun_list = $conn->query("
    SELECT DISTINCT YEAR(tanggal_kunjungan) as tahun 
    FROM kunjungan 
    WHERE jenis_pasien = 'siswa'
    ORDER BY tahun DESC
");

// filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tahun = (isset($_GET['tahun']) && $_GET['tahun'] !== '') ? intval($_GET['tahun']) : '';
$bulan_dari = isset($_GET['bulan_dari']) ? $_GET['bulan_dari'] : '';
$bulan_sampai = isset($_GET['bulan_sampai']) ? $_GET['bulan_sampai'] : '';

// pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$start = ($page - 1) * $limit;

// ==================== QUERY UTAMA ====================
$query = "
SELECT 
    k.id_kunjungan,
    s.NAMA_SISWA as nama,
    s.JENIS_KELAMIN,
    p.jenis_penyakit,
    GROUP_CONCAT(
        IF(po.jumlah > 1, CONCAT(o.nama_obat, ' (', po.jumlah, ')'), o.nama_obat)
        ORDER BY po.id ASC
        SEPARATOR ', '
    ) as nama_obat,
    p.status_pasien,
    k.tanggal_kunjungan
FROM pemeriksaan_klinis p
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
JOIN t_siswa s ON k.id_pasien = s.NIS
LEFT JOIN pemeriksaan_obat po ON p.id_pemeriksaan = po.id_pemeriksaan
LEFT JOIN obat o ON po.id_obat = o.id_obat
WHERE k.jenis_pasien = 'siswa'
";

// filter search
if ($search != '') {
    $search_esc = $conn->real_escape_string($search);
    $query .= " AND (
        s.NAMA_SISWA LIKE '%$search_esc%'
        OR s.JENIS_KELAMIN LIKE '%$search_esc%'
        OR p.jenis_penyakit LIKE '%$search_esc%'
        OR p.status_pasien LIKE '%$search_esc%'
        OR DATE_FORMAT(k.tanggal_kunjungan, '%d-%m-%Y') LIKE '%$search_esc%'
    )";
}

// filter tahun
if ($tahun !== '' && $tahun > 0) {
    $query .= " AND YEAR(k.tanggal_kunjungan) = $tahun";
}

// Filter bulan 
$bd = ($bulan_dari != '' && $bulan_dari != 'semua') ? intval($bulan_dari) : null;
$bs = ($bulan_sampai != '' && $bulan_sampai != 'semua') ? intval($bulan_sampai) : null;

if ($bd !== null && $bs === null)
    $bs = $bd;
if ($bs !== null && $bd === null)
    $bd = $bs;

if ($bd !== null && $bs !== null && $bd > $bs) {
    $tmp = $bd;
    $bd = $bs;
    $bs = $tmp;
}

$filter_bulan_sql = '';
if ($bd !== null && $bs !== null) {
    $filter_bulan_sql = " AND MONTH(k.tanggal_kunjungan) BETWEEN $bd AND $bs";
}

$query .= $filter_bulan_sql;

// Hitung total data
$total_query_count = "
SELECT COUNT(DISTINCT k.id_kunjungan) as total 
FROM pemeriksaan_klinis p
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
JOIN t_siswa s ON k.id_pasien = s.NIS
WHERE k.jenis_pasien = 'siswa'
";

if ($search != '') {
    $search_esc = $conn->real_escape_string($search);
    $total_query_count .= " AND (
        s.NAMA_SISWA LIKE '%$search_esc%'
        OR s.JENIS_KELAMIN LIKE '%$search_esc%'
        OR p.jenis_penyakit LIKE '%$search_esc%'
        OR p.status_pasien LIKE '%$search_esc%'
        OR DATE_FORMAT(k.tanggal_kunjungan, '%d-%m-%Y') LIKE '%$search_esc%'
    )";
}
if ($tahun !== '' && $tahun > 0) {
    $total_query_count .= " AND YEAR(k.tanggal_kunjungan) = $tahun";
}
$total_query_count .= $filter_bulan_sql;

$total_data = $conn->query($total_query_count)->fetch_assoc()['total'] ?? 0;
$total_page = ceil($total_data / $limit);

// GROUP BY + ORDER + LIMIT
$query .= " GROUP BY k.id_kunjungan, s.NAMA_SISWA, s.JENIS_KELAMIN, p.jenis_penyakit, p.status_pasien, k.tanggal_kunjungan 
            ORDER BY k.tanggal_kunjungan DESC 
            LIMIT $start, $limit";

$result = $conn->query($query);

// ==================== RINGKASAN BERAPA KALI BERKUNJUNG ====================
$ringkasan_query = "
SELECT 
    s.NAMA_SISWA as nama,
    COUNT(DISTINCT k.id_kunjungan) as total_kunjungan
FROM pemeriksaan_klinis p
JOIN kunjungan k ON p.id_kunjungan = k.id_kunjungan
JOIN t_siswa s ON k.id_pasien = s.NIS
WHERE k.jenis_pasien = 'siswa'
";

if ($search != '') {
    $search_esc = $conn->real_escape_string($search);
    $ringkasan_query .= " AND s.NAMA_SISWA LIKE '%$search_esc%'";
}
if ($tahun !== '' && $tahun > 0) {
    $ringkasan_query .= " AND YEAR(k.tanggal_kunjungan) = $tahun";
}
$ringkasan_query .= $filter_bulan_sql;

$ringkasan_query .= " GROUP BY s.NIS, s.NAMA_SISWA ORDER BY total_kunjungan DESC, s.NAMA_SISWA ASC";
$ringkasan = $conn->query($ringkasan_query);

// ==================== CEK PASIEN BELUM DIPERIKSA ====================
$tahun_filter = $tahun ?: null;
$belum_diperiksa = 0;
if ($tahun_filter) {
    $cek = $conn->query("
        SELECT COUNT(*) as total 
        FROM kunjungan k
        LEFT JOIN pemeriksaan_klinis p ON k.id_kunjungan = p.id_kunjungan
        WHERE k.jenis_pasien = 'siswa'
        AND YEAR(k.tanggal_kunjungan) = $tahun_filter
        AND p.id_kunjungan IS NULL
    ");
    $belum_diperiksa = $cek->fetch_assoc()['total'] ?? 0;
}

$nama_bulan = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];
?>

<!DOCTYPE html>
<html>

<head>
    <title>Data Siswa</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI';
            background: #f4f6f9;
        }

        .header {
            background: #1b4f9c;
            color: white;
            text-align: center;
            padding: 20px;
            font-weight: bold;
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

        tr:hover {
            background: #f1f1f1;
        }

        .btn {
            background: #1b4f9c;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #ffd700;
            color: black;
        }

        select,
        input[type=text] {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
</head>

<body>

    <div class="header">Daftar Periksa Siswa</div>

    <div class="container">

        <?php if ($belum_diperiksa > 0): ?>
            <div id="notif-belum"
                style="background:#dc3545; color:white; padding:14px 20px; margin-bottom:20px; border-radius:6px;">
                ⚠️ <strong>Peringatan!</strong> Masih ada <strong><?= $belum_diperiksa ?></strong> kunjungan siswa tahun
                <strong><?= $tahun_filter ?></strong> yang belum diperiksa.
            </div>
        <?php endif; ?>

        <form method="GET" style="margin-bottom:20px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">

            <input type="text" name="search" placeholder="Cari nama / kategori keluhan..."
                value="<?= htmlspecialchars($search) ?>" style="width:250px;">

            <!-- Tahun -->
            <select name="tahun">
                <option value="">-- Semua Tahun --</option>
                <?php
                $tahun_list->data_seek(0);
                while ($t = $tahun_list->fetch_assoc()):
                    ?>
                    <option value="<?= $t['tahun'] ?>" <?= ($tahun == $t['tahun']) ? 'selected' : '' ?>>
                        <?= $t['tahun'] ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <!-- Bulan Dari -->
            <select name="bulan_dari">
                <option value="semua">-- Bulan Dari --</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>" <?= ($bulan_dari == $i) ? 'selected' : '' ?>>
                        <?= $nama_bulan[$i] ?>
                    </option>
                <?php endfor; ?>
            </select>

            <!-- Bulan Sampai -->
            <select name="bulan_sampai">
                <option value="semua">-- Bulan Sampai --</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>" <?= ($bulan_sampai == $i) ? 'selected' : '' ?>>
                        <?= $nama_bulan[$i] ?>
                    </option>
                <?php endfor; ?>
            </select>

            <button type="submit" class="btn">🔍 Tampilkan</button>
            <a href="hal_siswa.php" class="btn" style="background:gray;">Reset</a>

            <!-- Download tetap pakai tahun -->
            <button type="button" class="btn" style="background:#28a745;" onclick="downloadData()">
                📥 Download PDF
            </button>
        </form>

        <a href="../dashboard.php" class="btn">← Kembali</a>
        <br><br>

        <table>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Kategori Keluhan</th>
                <th>Obat</th>
                <th>Status</th>
                <th>Tanggal Kunjungan</th>
                <th>Aksi</th>
            </tr>

            <?php
            $no = $start + 1;
            if ($result->num_rows == 0):
                ?>
                <tr>
                    <td colspan="8" style="padding:30px; color:gray;">Belum ada data</td>
                </tr>
            <?php else:
                while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td style="text-align:left;"><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= $row['JENIS_KELAMIN'] ?></td>
                        <td style="text-align:left;"><?= htmlspecialchars($row['jenis_penyakit'] ?? '-') ?></td>
                        <td style="text-align:left;"><?= $row['nama_obat'] ? htmlspecialchars($row['nama_obat']) : '-' ?></td>
                        <td style="text-align:left;"><?= htmlspecialchars($row['status_pasien'] ?? '-') ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal_kunjungan'])) ?></td>
                        <td>
                            <a href="../lihat.php?id=<?= $row['id_kunjungan'] ?>" class="btn">Lihat</a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
        </table>

        <br>

        <!-- Pagination -->
        <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap;">
            <?php for ($i = 1; $i <= $total_page; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&tahun=<?= $tahun ?>&bulan_dari=<?= $bulan_dari ?>&bulan_sampai=<?= $bulan_sampai ?>"
                    style="padding:8px 12px; border-radius:6px; text-decoration:none; background:<?= ($page == $i) ? '#1b4f9c' : '#eee' ?>; color:<?= ($page == $i) ? 'white' : 'black' ?>;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>

        <!-- ==================== RINGKASAN BERAPA KALI ==================== -->
        <?php if ($ringkasan && $ringkasan->num_rows > 0): ?>
            <div
                style="margin-top:40px; background:white; padding:20px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
                <h3 style="margin-top:0;">Ringkasan Jumlah Kunjungan</h3>
                <p style="color:#666; font-size:14px;">
                    Menampilkan berapa kali setiap siswa berkunjung
                    <?php if ($tahun): ?>tahun <?= $tahun ?><?php endif; ?>
                    <?php if ($bulan_dari != 'semua' && $bulan_sampai != 'semua' && $bulan_dari && $bulan_sampai): ?>
                        (<?= $nama_bulan[intval($bulan_dari)] ?> – <?= $nama_bulan[intval($bulan_sampai)] ?>)
                    <?php endif; ?>
                </p>

                <table style="margin-top:15px;">
                    <tr>
                        <th style="width:60px;">No</th>
                        <th style="text-align:left;">Nama Siswa</th>
                        <th style="width:150px;">Jumlah Kunjungan</th>
                    </tr>
                    <?php
                    $no_r = 1;
                    while ($r = $ringkasan->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= $no_r++ ?></td>
                            <td style="text-align:left;"><?= htmlspecialchars($r['nama']) ?></td>
                            <td>
                                <span
                                    style="background:#1b4f9c; color:white; padding:4px 12px; border-radius:6px; font-weight:600;">
                                    <?= $r['total_kunjungan'] ?>x
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function downloadData() {
            let tahun = document.querySelector('select[name="tahun"]').value;
            if (tahun) {
                window.location.href = "download_siswa.php?tahun=" + tahun;
            } else {
                alert("Pilih tahun terlebih dahulu untuk download!");
            }
        }
    </script>

</body>

</html>