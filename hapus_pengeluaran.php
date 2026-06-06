<?php
include 'koneksi.php';

$id = $_GET['id'];

mysqli_query($koneksi,
"DELETE FROM pengeluaran
 WHERE id_pengeluaran='$id'"
);

header("Location:index.php");
?>