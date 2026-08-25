<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "uks");

$username = $_SESSION['username'];

if (isset($_POST['simpan'])) {

    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi'];

    // ambil data admin
    $query = $conn->query("
        SELECT * FROM admin
        WHERE username='$username'
    ");

    $admin = $query->fetch_assoc();

    // cek password lama
    if (!password_verify($password_lama, $admin['password'])) {

        $error = "Password lama salah!";

    } elseif ($password_baru != $konfirmasi) {

        $error = "Konfirmasi password tidak sama!";

    } else {

        $hash = password_hash($password_baru, PASSWORD_DEFAULT);

        $conn->query("
            UPDATE admin
            SET password='$hash'
            WHERE username='$username'
        ");

        $success = "Password berhasil diubah!";
    }
} //Error: $password_baru == $password_lama 
?>

<!DOCTYPE html>
<html>
 
<head>
    <title>Reset Password</title>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI';
            background: #f4f6f9;
        }

        .header {
            background: #1b4f9c;
            color: white;
            padding: 10px 25px;
            font-weight: bold;
            border-bottom: 5px solid #ffd700;

            display: flex;
            align-items: center;
            justify-content: center;

            min-height: 55px;
        }

        .box {
            background: white;
            width: 400px;
            margin: 40px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .input-group {
            position: relative;
            margin-bottom: 15px;
        }

        .input-group input {
            width: 100%;
            padding: 10px 40px 10px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #666;
        }

        .btn {
            background: #1b4f9c;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }

        .btn:hover {
            background: #ffd700;
            color: black;
        }

        .notif {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>

<body>

    <div class="header">

        <div style="
        font-size:20px;
        font-weight:bold;
    ">
            CHANGE PASSWORD
        </div>

    </div>

    <div class="box">

        <?php if (isset($error)): ?>
            <div class="notif error">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="notif success">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Password Lama</label>

            <div class="input-group">
                <input type="password" name="password_lama" id="password_lama" required>
                <span class="eye" onclick="togglePassword('password_lama')">
                    👁
                </span>
            </div>

            <label>Password Baru</label>

            <div class="input-group">
                <input type="password" name="password_baru" id="password_baru" required>
                <span class="eye" onclick="togglePassword('password_baru')">
                    👁
                </span>
            </div>

            <label>Konfirmasi Password</label>

            <div class="input-group">
                <input type="password" name="konfirmasi" id="konfirmasi" required>
                <span class="eye" onclick="togglePassword('konfirmasi')">
                    👁
                </span>
            </div>

            <button type="submit" name="simpan" class="btn">
                Simpan
            </button>

        </form>

        <br>

        <div style="text-align:center;">

            <a href="dashboard.php" class="btn" style="
        text-decoration:none;
        display:inline-block;
        width:auto;
        padding:10px 20px;
    ">
                ← Kembali ke Dashboard
            </a>

        </div>

    </div>

    <!-- script mata -->
    <script>

        function togglePassword(id) {

            const input = document.getElementById(id);

            if (input.type === "password") {

                input.type = "text";

            } else {

                input.type = "password";

            }
        }

    </script>
</body>

</html>