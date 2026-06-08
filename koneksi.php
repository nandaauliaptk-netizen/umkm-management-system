<?php
// ================================================================
// koneksi.php
// ================================================================
$host    = 'localhost';
$db      = 'db_umkm';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';
 
// Membuat koneksi menggunakan mysqli
$conn = new mysqli($host, $user, $pass, $db);

// Cek jika ada error koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset($charset);
 
// Helper: Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>