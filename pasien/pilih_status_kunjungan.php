<!DOCTYPE html>
<html>

<head>
    <title>UKS BOPKRI 1 Yogyakarta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #1f5faa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            background: #1b4f9c;
            color: white;
            text-align: center;
            padding: 20px;
            font-weight: bold;
            font-size: 1.2rem;
            border-top: 5px solid #ffd700;
            border-bottom: 7px solid white;
        }

        .main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: white;
            text-align: center;
            padding: 20px;
        }

        h2 {
            margin-bottom: 40px;
            font-size: 26px;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
            max-width: 450px;
            /* Lebih fleksibel untuk mobile */
        }

        .btn {
            display: flex;
            align-items: center;
            background: white;
            color: #1b4f9c;
            padding: 15px 20px;
            border-radius: 15px;
            text-decoration: none;
            font-weight: bold;
            font-size: 20px;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            box-sizing: border-box;
        }

        .btn:hover {
            background: #ffd700;
            color: black;
            transform: translateY(-3px);
            /* Efek melayang sedikit */
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        /* ICON kiri */
        .btn .icon {
            width: 50px;
            display: flex;
            justify-content: flex-start;
        }

        .btn .icon img {
            width: 35px;
            height: 35px;
        }

        /* TEXT di tengah */
        .btn .text {
            flex: 1;
            text-align: center;
        }

        /* Penyeimbang (Spacer) agar teks benar-benar di tengah */
        .btn::after {
            content: "";
            width: 50px;
        }

        .footer {
            background: #1b4f9c;
            color: white;
            text-align: center;
            padding: 15px;
            border-top: 7px solid white;
            border-bottom: 5px solid #ffd700;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .header {
            background: #1b4f9c;
            color: white;
            padding: 20px;
            font-weight: bold;
            font-size: 1.2rem;
            border-top: 5px solid #ffd700;
            border-bottom: 7px solid white;

            display: flex;
            align-items: center;
            position: relative;
        }

        .logo {
            height: 55px;
            width: auto;
            cursor: pointer;
        }

        .logo-link {
            display: flex;
            align-items: center;
        }

        .header-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
        }
    </style>
</head>

<body>

    <div class="header">
        <a href="" class="logo-link">
            <img src="../assets/images/logo.png" alt="Logo UKS" class="logo">
        </a>

        <div class="header-title">
            SELAMAT DATANG DI UKS BOPKRI 1 YOGYAKARTA
        </div>
    </div>

    <div class="main">
        <h2>Silahkan Pilih Status Kunjungan</h2>

        <div class="btn-group">
            <a href="pasien_siswa.php" class="btn">
                <span class="icon">
                    <img src="https://img.icons8.com/ios-filled/50/1b4f9c/student-male.png" />
                </span>
                <span class="text">SISWA</span>
            </a>

            <a href="pasien_guru.php" class="btn">
                <span class="icon">
                    <img src="https://img.icons8.com/ios-filled/50/1b4f9c/teacher.png" />
                </span>
                <span class="text">GURU</span>
            </a>

            <a href="pasien_karyawan.php" class="btn">
                <span class="icon">
                    <img src="https://img.icons8.com/ios-filled/50/1b4f9c/businessman.png" />
                </span>
                <span class="text">KARYAWAN</span>
            </a>
        </div>
    </div>

    <div class="footer">
        BERSAMA MENJAGA KESEHATAN SEKOLAH
    </div>

</body>

</html>