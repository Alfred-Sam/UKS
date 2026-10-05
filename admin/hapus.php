<?php
$conn = new mysqli("localhost","root","","uks");

$id = intval($_GET['id'] ?? 0);

// hapus dari pemeriksaan_obat (jika ada data pemeriksaan)
$res_pemeriksaan = $conn->query("SELECT id_pemeriksaan FROM pemeriksaan_klinis WHERE id_kunjungan='$id'");
while ($row = $res_pemeriksaan->fetch_assoc()) {
    $conn->query("DELETE FROM pemeriksaan_obat WHERE id_pemeriksaan='" . $row['id_pemeriksaan'] . "'");
}

// hapus dari pemeriksaan klinis (kalau ada)
$conn->query("DELETE FROM pemeriksaan_klinis WHERE id_kunjungan='$id'");

// hapus dari kunjungan (WAJIB)
$conn->query("DELETE FROM kunjungan WHERE id_kunjungan='$id'");

header("Location: dashboard.php?status=hapus");
exit;