<?php
session_start();

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

require('../template/header.php');
?>

<h2>Selamat Datang, <?= $_SESSION['username']; ?> 👋</h2>

<a href="logout.php">Logout</a>

<?php require('../template/footer.php'); ?>