<?php session_start(); ?>
<!DOCTYPE html>
<html>

<head>
    <title>Login Admin - UKS BOPKRI 1</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
            border-top: 5px solid #ffd700;
            border-bottom: 7px solid white;
        }

        .main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            text-align: center;

            /* ANIMASI MASUK */
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-box h2 {
            margin-bottom: 20px;
            color: #1b4f9c;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
            position: relative;
        }

        .form-group label {
            font-weight: bold;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border-radius: 10px;
            border: 1px solid #ccc;
            outline: none;
            box-sizing: border-box;
            font-size: 14px;
        }

        .form-group input:focus {
            border-color: #1b4f9c;
            box-shadow: 0 0 5px rgba(27, 79, 156, 0.3);
        }

        /* SHOW PASSWORD ICON */
        .toggle-password {
            cursor: pointer;
            font-size: 24px;
            color: #1b4f9c;
            user-select: none;
            margin-left: 10px;
            margin-top: 8px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            background: #1b4f9c;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: #ffd700;
            color: black;
            transform: translateY(-2px);
        }

        /* ERROR MESSAGE HALUS */
        .error {
            background: #ffdddd;
            color: #b30000;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .footer {
            background: #1b4f9c;
            color: white;
            text-align: center;
            padding: 15px;
            border-top: 7px solid white;
            border-bottom: 5px solid #ffd700;
            font-size: 1rem;
            font-weight: bold;
            /* INI YANG PENTING */
            letter-spacing: 1px;
            /* biar lebih rapi kayak header */
            text-transform: uppercase;
        }

        .icon img {
            width: 60px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        LOGIN ADMIN UKS BOPKRI 1 YOGYAKARTA
    </div>

    <div class="main">

        <div class="login-box">

            <div class="icon">
                <img src="https://img.icons8.com/ios-filled/100/1b4f9c/admin-settings-male.png" />
            </div>

            <h2>Admin Login</h2>

            <!-- ERROR MESSAGE -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error">
                    <?= $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
                <?php
            endif; ?>

            <form action="proses_login.php" method="POST">

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>

                <div class="form-group">

                    <label>Password</label>

                    <div style="
        display:flex;
        align-items:center;
    ">

                        <input type="password" name="password" id="password" required style="width:92%;">

                        <span class="toggle-password" onclick="togglePassword()">
                            👁
                        </span>

                    </div>

                </div>

                <button type="submit" class="btn">LOGIN</button>

            </form>

        </div>

    </div>

    <div class="footer">
        SISTEM INFORMASI UKS
    </div>

    <script>
        function togglePassword() {
            let pass = document.getElementById("password");

            if (pass.type === "password") {
                pass.type = "text";
            } else {
                pass.type = "password";
            }
        }
    </script>

</body>

</html>