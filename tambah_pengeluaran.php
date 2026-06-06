<?php
include 'koneksi.php';

if(isset($_POST['simpan'])){

    $tanggal = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    $jumlah = $_POST['jumlah'];

    mysqli_query($koneksi,"
        INSERT INTO pengeluaran
        (id_umkm,tanggal,keterangan,jumlah)
        VALUES
        (1,'$tanggal','$keterangan','$jumlah')
    ");

    header("Location:index.php");
}
?>

<h2>Tambah Pengeluaran</h2>

<form method="POST">

    Tanggal <br>
    <input type="date" name="tanggal" required>
    <br><br>

    Keterangan <br>
    <input type="text" name="keterangan" required>
    <br><br>

    Jumlah <br>
    <input type="number" name="jumlah" required>
    <br><br>

    <button name="simpan">Simpan</button>

</form>