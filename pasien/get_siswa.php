<?php
$conn = new mysqli("localhost", "root", "", "uks");

$nis = $_GET['nis'] ?? '';

$result = $conn->query("SELECT * FROM t_siswa WHERE NIS LIKE '%$nis%' LIMIT 5");

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);