<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$conn = new mysqli("localhost", "root", "", "uks");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');

// ==================== CEK PASIEN BELUM DIPERIKSA ====================
$cek_belum = $conn->query("
    SELECT COUNT(*) as total 
    FROM kunjungan k
    LEFT JOIN pemeriksaan_klinis p ON k.id_kunjungan = p.id_kunjungan
    WHERE k.jenis_pasien = 'karyawan'
    AND YEAR(k.tanggal_kunjungan) = '$tahun'
    AND p.id_kunjungan IS NULL
")->fetch_assoc()['total'];

if ($cek_belum > 0) {
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Peringatan Download</title>
        <style>
            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                background: #f8f9fa;
                margin: 0;
                padding: 40px;
                text-align: center;
            }

            .warning-box {
                max-width: 600px;
                margin: 0 auto;
                background: #f8d7da;
                color: #721c24;
                padding: 30px;
                border-radius: 10px;
                border: 2px solid #dc3545;
            }

            h2 {
                color: #dc3545;
                margin-bottom: 15px;
            }

            button {
                background: #1b4f9c;
                color: white;
                padding: 12px 25px;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                cursor: pointer;
                margin-top: 20px;
            }

            button:hover {
                background: #ffd700;
                color: black;
            }
        </style>
    </head>

    <body>
        <div class="warning-box">
            <h2>⚠️ Tidak Bisa Download</h2>
            <p>Masih ada <strong><?= $cek_belum ?></strong> kunjungan karyawan tahun <strong><?= $tahun ?></strong> yang
                belum diperiksa.</p>
            <p>Silakan periksa terlebih dahulu.</p>
            <button onclick="window.history.back()">← Kembali</button>
        </div>
    </body>

    </html>
    <?php
    exit;
}

// ==================== LANJUT DOWNLOAD PDF ====================
$query = "
SELECT 
    kr.nama,
    kr.jenis_kelamin,
    k.keluhan,
    k.tanggal_kunjungan,
    p.jenis_penyakit,
    GROUP_CONCAT(
        IF(po.jumlah > 1, CONCAT(o.nama_obat, ' (', po.jumlah, ')'), o.nama_obat)
        ORDER BY po.id ASC
        SEPARATOR ', '
    ) as nama_obat,
    p.catatan_admin,
    p.status_pasien
FROM kunjungan k
JOIN karyawan kr ON k.id_pasien = kr.id_karyawan
LEFT JOIN pemeriksaan_klinis p ON k.id_kunjungan = p.id_kunjungan
LEFT JOIN pemeriksaan_obat po ON p.id_pemeriksaan = po.id_pemeriksaan
LEFT JOIN obat o ON po.id_obat = o.id_obat
WHERE k.jenis_pasien='karyawan'
AND YEAR(k.tanggal_kunjungan)='$tahun'
GROUP BY k.id_kunjungan, kr.nama, kr.jenis_kelamin, k.keluhan, k.tanggal_kunjungan, 
         p.jenis_penyakit, p.catatan_admin, p.status_pasien
ORDER BY nama ASC, k.tanggal_kunjungan ASC
";

$result = $conn->query($query);

$tanggal_cetak = date('d F Y');

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Karyawan Tahun ' . $tahun . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; font-size: 11px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #1b4f9c; padding-bottom: 15px; }
        .school-name { font-size: 18px; font-weight: bold; margin: 5px 0; }
        .title { font-size: 16px; font-weight: bold; color: #1b4f9c; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #1b4f9c; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .info { text-align: right; margin-bottom: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">SMA BOPKRI 1</div>
        <div class="title">LAPORAN DATA KUNJUNGAN KARYAWAN</div>
        <div>Tahun ' . $tahun . '</div>
    </div>

    <div class="info">
        Tanggal Cetak: ' . $tanggal_cetak . '<br>
        Dicetak oleh: Bu Devira
    </div>

    <table>
        <tr>
            <th>No</th>
            <th>Nama Karyawan</th>
            <th>Jenis Kelamin</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Keluhan</th>
            <th>Kategori Keluhan</th>
            <th>Obat</th>
            <th>Catatan</th>
            <th>Status</th>
        </tr>';

$no = 1;
while ($row = $result->fetch_assoc()) {
    $html .= '
        <tr>
            <td>' . $no++ . '</td>
            <td>' . htmlspecialchars($row['nama']) . '</td>
            <td>' . ($row['jenis_kelamin'] ?? '-') . '</td>
            <td>' . date('d-m-Y', strtotime($row['tanggal_kunjungan'])) . '</td>
            <td>' . date('H:i', strtotime($row['tanggal_kunjungan'])) . '</td>
            <td>' . htmlspecialchars($row['keluhan'] ?? '-') . '</td>
            <td>' . htmlspecialchars($row['jenis_penyakit'] ?? '-') . '</td>
            <td>' . htmlspecialchars($row['nama_obat'] ?? '-') . '</td>
            <td>' . htmlspecialchars($row['catatan_admin'] ?? '-') . '</td>
            <td>' . htmlspecialchars($row['status_pasien'] ?? '-') . '</td>
        </tr>';
}

$html .= '
    </table>
</body>
</html>';

$options = new Options();
$options->setChroot(__DIR__);
$options->setDefaultFont('Arial');
$options->setIsRemoteEnabled(true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = "Laporan_Kunjungan_Karyawan_{$tahun}.pdf";
$dompdf->stream($filename, array("Attachment" => true));

$conn->close();
exit;
?>