<?php
$conn = new mysqli("localhost", "root", "", "uks");

$nama = $_GET['nama'] ?? '';

$query = "SELECT * FROM karyawan WHERE nama LIKE '%$nama%'";
$result = $conn->query($query);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);