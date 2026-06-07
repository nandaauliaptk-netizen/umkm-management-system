<?php
session_start();
include 'koneksi.php';

// Jika sudah login, langsung lempar ke index.php
if (isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($akses, $_POST['email']);
    $password = $_POST['password'];

    // Cari user berdasarkan email
    $result = mysqli_query($akses, "SELECT * FROM user WHERE email = '$email'");
    
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Cek password (pastikan saat register menggunakan password_hash)
        if (password_verify($password, $row['password'])) {
            // Set session
            $_SESSION['login'] = true;
            $_SESSION['id_user'] = $row['id_user'];
            $_SESSION['nama'] = $row['nama'];
            
            header("Location: index.php");
            exit;
        }
    }
    $error = 'Email atau password salah!';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NEXT UMKM</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        /* Container Utama */
        .login-container {
            background-color: #1a1936;
            width: 950px;
            height: 600px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            display: flex;
            overflow: hidden;
            position: relative;
        }

        /* BAGIAN KIRI: Form Login */
        .login-left {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #fff;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #00f2fe;
            margin-bottom: 40px;
        }

        .brand i {
            font-size: 1.6rem;
            color: #ff007f;
        }

        .login-left h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .login-left p.subtitle {
            color: #b3b2d1;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }

        .error-msg {
            background-color: rgba(255, 0, 127, 0.2);
            border-left: 4px solid #ff007f;
            padding: 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            color: #ff479d;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7d7ba6;
            font-size: 1.1rem;
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            background-color: #262450;
            border: 2px solid transparent;
            border-radius: 12px;
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #00f2fe;
            background-color: #2b295c;
            outline: none;
            box-shadow: 0 0 15px rgba(0, 242, 254, 0.2);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #b3b2d1;
            margin-bottom: 30px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .form-options a {
            color: #7d7ba6;
            text-decoration: none;
            transition: color 0.3s;
        }

        .form-options a:hover {
            color: #00f2fe;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(90deg, #ff007f 0%, #7928ca 100%);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 8px 20px rgba(255, 0, 127, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(255, 0, 127, 0.5);
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
            color: #b3b2d1;
        }

        .register-link a {
            color: #00f2fe;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        /* BAGIAN KANAN: Visualisasi Kreatif */
        .login-right {
            flex: 1.2;
            background: linear-gradient(145deg, #131230 0%, #1e1b4b 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            overflow: hidden;
        }

        /* Efek Ombak Abstrak/Glow */
        .visual-wave {
            position: absolute;
            width: 150%;
            height: 150%;
            background: radial-gradient(circle, rgba(255,0,127,0.15) 0%, rgba(0,242,254,0.1) 50%, transparent 70%);
            top: -25%;
            left: -25%;
            z-index: 1;
            animation: pulse 8s infinite alternate;
        }

        /* Konten Visual UMKM (3 Elemen Representasi) */
        .umkm-showcase {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 25px;
        }

        .umkm-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            padding: 15px 25px;
            border-radius: 16px;
            width: 85%;
            display: flex;
            align-items: center;
            gap: 20px;
            transform: translateY(0);
            transition: all 0.4s ease;
        }

        .umkm-card:hover {
            transform: translateY(-5px) scale(1.02);
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(0, 242, 254, 0.3);
        }

        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.4rem;
        }

        .card-1 .icon-box { background: rgba(0, 242, 254, 0.2); color: #00f2fe; }
        .card-2 .icon-box { background: rgba(255, 0, 127, 0.2); color: #ff007f; }
        .card-3 .icon-box { background: rgba(121, 40, 202, 0.2); color: #a855f7; }

        .card-info h4 {
            color: #fff;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .card-info p {
            color: #b3b2d1;
            font-size: 0.8rem;
        }

        .welcome-text {
            text-align: center;
            color: #fff;
            margin-top: 30px;
            z-index: 2;
        }

        .welcome-text h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(to right, #fff, #b3b2d1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-text p {
            color: #7d7ba6;
            font-size: 0.85rem;
            max-width: 300px;
        }

        @keyframes pulse {
            0% { transform: scale(1) rotate(0deg); }
            100% { transform: scale(1.1) rotate(15deg); }
        }

        /* Responsive */
        @media (max-width: 900px) {
            .login-right { display: none; }
            .login-container { width: 450px; height: auto; }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-left">
            <div class="brand">
                <i class="fa-solid fa-chart-pie"></i>
                NEXT UMKM
            </div>
            
            <h2>Welcome.</h2>
            <p class="subtitle">Silakan masuk untuk mengelola pembukuan digital Anda.</p>

            <?php if(!empty($error)): ?>
                <div class="error-msg">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Ingat Saya
                    </label>
                    <a href="#">Lupa Password?</a>
                </div>

                <button type="submit" name="login" class="btn-login">LOGIN</button>
            </form>

            <div class="register-link">
                Belum punya akun? <a href="register.php">Sign up now</a>
            </div>
        </div>

        <div class="login-right">
            <div class="visual-wave"></div>
            
            <div class="umkm-showcase">
                <div class="umkm-card card-1">
                    <div class="icon-box">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div class="card-info">
                        <h4>Manajemen Dagang Gampang</h4>
                        <p>Pantau perkembangan outlet dan kas tokomu secara realtime.</p>
                    </div>
                </div>

                <div class="umkm-card card-2">
                    <div class="icon-box">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="card-info">
                        <h4>Arus Kas Otomatis</h4>
                        <p>Pencatatan struktur pendapatan & pengeluaran tanpa ribet.</p>
                    </div>
                </div>

                <div class="umkm-card card-3">
                    <div class="icon-box">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="card-info">
                        <h4>Visualisasi Grafik Akurat</h4>
                        <p>Analisis laba bersih dan persentase performa usaha.</p>
                    </div>
                </div>
            </div>

            <div class="welcome-text">
                <h3>Data-Driven Platform</h3>
                <p>Mengubah data transaksi menjadi strategi pertumbuhan bisnis Anda.</p>
            </div>
        </div>
    </div>

</body>
</html>