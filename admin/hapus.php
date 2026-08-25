<?php
$conn = new mysqli("localhost","root","","uks");

$id = $_GET['id'];

// hapus dari pemeriksaan klinis (kalau ada)
$conn->query("DELETE FROM pemeriksaan_klinis WHERE id_kunjungan='$id'");

// hapus dari kunjungan (WAJIB)
$conn->query("DELETE FROM kunjungan WHERE id_kunjungan='$id'");

header("Location: dashboard.php?status=hapus");
exit;