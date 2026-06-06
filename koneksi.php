<?php
$host     = "localhost";
$username = "root";
$password = "";
$database = "db_umkm"; 

// Membuat koneksi
$koneksi = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>