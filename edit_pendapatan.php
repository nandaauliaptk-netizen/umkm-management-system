<?php
include 'koneksi.php';

$id = $_GET['id'];

$data = mysqli_fetch_assoc(
    mysqli_query(
        $koneksi,
        "SELECT * FROM pendapatan WHERE id_pendapatan='$id'"
    )
);

if(isset($_POST['update'])){

    $tanggal = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    $jumlah = $_POST['jumlah'];

    mysqli_query(
        $koneksi,
        "UPDATE pendapatan
        SET
        tanggal='$tanggal',
        keterangan='$keterangan',
        jumlah='$jumlah'
        WHERE id_pendapatan='$id'"
    );

    header("Location:index.php");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Pendapatan</title>
</head>

<body>

<h2>Edit Pendapatan</h2>

<form method="POST">

Tanggal
<br>
<input type="date"
name="tanggal"
value="<?= $data['tanggal']; ?>">
<br><br>

Keterangan
<br>
<input type="text"
name="keterangan"
value="<?= $data['keterangan']; ?>">
<br><br>

Jumlah
<br>
<input type="number"
name="jumlah"
value="<?= $data['jumlah']; ?>">
<br><br>

<button type="submit"
name="update">
Update
</button>

</form>

</body>
</html>