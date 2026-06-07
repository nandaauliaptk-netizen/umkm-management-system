<?php
include 'koneksi.php';
session_start();

// Jika sudah login, langsung lempar ke halaman utama
if (isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

    $result = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email'");
    
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Cek apakah password cocok dengan yang di-hash
        if (password_verify($password, $row['password'])) {
            // Set session login
            $_SESSION['login']   = true;
            $_SESSION['id_user'] = $row['id_user'];
            $_SESSION['nama']    = $row['nama'];

            echo "<script>alert('Login Berhasil!'); window.location='index.php';</script>";
            exit;
        }
    }
    $error = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login - Manajemen UMKM</title>
</head>
<body>
    <h2>Login Sistem UMKM</h2>
    
    <?php if (isset($error)) : ?>
        <p style="color: red; font-style: italic;">Email atau Password salah!</p>
    <?php endif; ?>

    <form action="" method="POST">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit" name="login">Masuk</button>
    </form>
    <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
</body>
</html>