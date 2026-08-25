<?php
$conn = new mysqli("localhost", "root", "", "uks");

$username = "admin";
$password = password_hash("admin123", PASSWORD_DEFAULT);

$conn->query("INSERT INTO admin (username, password) VALUES ('$username', '$password')");

echo "Admin berhasil dibuat";

//TOP SECRET SHHHHH HEHEHE