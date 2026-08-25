<?php
session_start();

$conn = new mysqli("localhost", "root", "", "uks");

$username = $_POST['username'];
$password = $_POST['password'];

$query = $conn->query("SELECT * FROM admin WHERE username='$username'");
$data = $query->fetch_assoc();


if ($data && password_verify($password, $data['password'])) {

    $_SESSION['admin'] = true;
    $_SESSION['username'] = $data['username'];

    header("Location: dashboard.php");
    exit;

}
else {
    $_SESSION['error'] = "Username atau Password salah!";
    header("Location: login.php");
    exit;
}
