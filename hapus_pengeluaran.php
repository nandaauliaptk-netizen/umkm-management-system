
<?php
session_start();
// Memastikan hanya user yang sudah login yang bisa menghapus data
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$id = $_GET['id'];

mysqli_query($koneksi, "DELETE FROM pengeluaran WHERE id_pengeluaran='$id'");

header("Location: index.php");
exit;
?>