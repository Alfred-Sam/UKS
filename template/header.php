<!DOCTYPE html>
<html>

<head>
    <title>UKS BOPKRI 1 Yogyakarta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        body {
            margin: 0;
            font-family: Arial;
            background: #1f5faa;

            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .content {
            background: white;
            width: 80%;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);

            flex: 1;
        }

        .header {
            background: #1b4f9c;
            color: white;
            padding: 15px 25px;
            font-weight: bold;

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
            font-size: 20px;
            font-weight: bold;
            white-space: nowrap;
        }

        .footer {
            background: #1b4f9c;
            color: white;
            text-align: center;
            padding: 10px;

            border-top: 7px solid white;
            border-bottom: 5px solid #ffd700;
        }

        /* .section-title{
    border-bottom:2px solid #dcdcdc;
    padding-bottom:10px;
    margin-bottom:20px;
    } */
        .title-box {
            background: #eeeeee;
            padding: 15px 20px;

            margin-left: -20px;
            margin-right: -20px;
            margin-top: -20px;

            border-bottom: 1px solid #dcdcdc;

            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .title-box h2 {
            margin: 0;
        }
    </style>

</head>

<body>

    <div class="header">
        <a href="javascript:history.back()" class="logo-link">
            <img src="/UKS/assets/images/logo.png" alt="Logo UKS" class="logo">
        </a>

        <div class="header-title">
            SELAMAT DATANG DI UKS BOPKRI 1 YOGYAKARTA
        </div>
    </div>

    <!-- <div style="background:#154a8a; padding:10px; text-align:center;">
        <a href="index.php" style="color:white; margin:15px;">Home</a>
        <a href="pasien.php" style="color:white; margin:15px;">Data Pasien</a>
        <a href="obat.php" style="color:white; margin:15px;">Data Obat</a>
    </div> -->
    <script>
        function goBackOrRefresh() {
            if (document.referrer && document.referrer !== window.location.href) {
                history.back();
            } else {
                location.reload();
            }
        }
    </script>
    <div class="content">