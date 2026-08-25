<?php
require('../template/header.php');

$conn = new mysqli("localhost", "root", "", "uks");

$id = $_GET['id'] ?? '';

$data = $conn->query("
SELECT * FROM guru 
WHERE id_guru='$id'
")->fetch_assoc();

if (!$data) {
    echo "Data guru tidak ditemukan";
    exit;
}

// ambil kunjungan terakhir
$kunjungan = $conn->query("
SELECT * FROM kunjungan
WHERE id_pasien='$id'
AND jenis_pasien='guru'
ORDER BY id_kunjungan DESC
LIMIT 1
")->fetch_assoc();

$pemeriksaan = null;
$obat_list = [];

if ($kunjungan) {

    $pemeriksaan = $conn->query("
    SELECT * FROM pemeriksaan_klinis
    WHERE id_kunjungan='" . $kunjungan['id_kunjungan'] . "'
    ")->fetch_assoc();

    if ($pemeriksaan) {
        // Ambil semua obat
        $obat_query = $conn->query("
        SELECT o.nama_obat 
        FROM pemeriksaan_obat po
        JOIN obat o ON po.id_obat = o.id_obat
        WHERE po.id_pemeriksaan = '" . $pemeriksaan['id_pemeriksaan'] . "'
        ");

        while ($obat_row = $obat_query->fetch_assoc()) {
            $obat_list[] = $obat_row['nama_obat'];
        }
    }
}
?>

<div class="title-box">
    <h2>Ruang Tunggu Guru</h2>
</div>

<div style="padding:20px; text-align:center;">

    <h3>Data Pasien</h3>

    <p><b>ID:</b> <?= $data['id_guru']; ?></p>
    <p><b>Nama:</b> <?= $data['nama']; ?></p>
    <p><b>Jenis Kelamin:</b> <?= $data['jenis_kelamin']; ?></p>

    <br><br>

    <?php if ($pemeriksaan): ?>

        <h3 style="color:green;">
            Pemeriksaan Selesai
        </h3>

        <div style="
            background:white;
            max-width:500px;
            margin:20px auto;
            padding:20px;
            border-radius:12px;
            box-shadow:0 4px 10px rgba(0,0,0,0.1);
            text-align:left;
        ">

            <p>
                <b>Jenis Penyakit:</b><br>
                <?= $pemeriksaan['jenis_penyakit'] ?>
            </p>

            <p>
                <b>Catatan Admin:</b><br>
                <?= $pemeriksaan['catatan_admin'] ?>
            </p>

            <p>
                <b>Obat Yang Diberikan:</b><br>
                <?= !empty($obat_list) ? implode(', ', $obat_list) : '-' ?>
            </p>

            <p>
                <b>Status Pasien:</b><br>
                <?= $pemeriksaan['status_pasien'] ?>
            </p>

        </div>

    <?php else: ?>

        <h3>Menunggu Pemeriksaan...</h3>

        <div class="loader"></div>

    <?php endif; ?>

</div>

<style>
    .loader {
        border: 6px solid #f3f3f3;
        border-top: 6px solid #1b4f9c;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        margin: 20px auto;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<?php if (!$pemeriksaan): ?>
    <script>
        setInterval(function () {
            location.reload();
        }, 5000);
    </script>
<?php endif; ?>

<?php require('../template/footer.php'); ?>