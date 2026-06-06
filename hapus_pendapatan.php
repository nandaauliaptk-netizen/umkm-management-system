<?php
include 'koneksi.php';

$id = $_GET['id'];

mysqli_query($koneksi,
"DELETE FROM pendapatan
 WHERE id_pendapatan='$id'"
);

header("Location:index.php");
?>