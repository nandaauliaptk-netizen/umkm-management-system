<?php
include 'koneksi.php';

$totalPendapatan = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT SUM(jumlah) AS total FROM pendapatan")
);

$totalPengeluaran = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT SUM(jumlah) AS total FROM pengeluaran")
);

$laba = ($totalPendapatan['total'] ?? 0) - ($totalPengeluaran['total'] ?? 0);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Finance UMKM</title>

    <style>
        body{
            font-family: Arial;
            margin:40px;
        }

        table{
            border-collapse:collapse;
            width:100%;
            margin-top:20px;
        }

        th,td{
            border:1px solid #ddd;
            padding:10px;
        }

        th{
            background:#007bff;
            color:white;
        }

        .card{
            padding:15px;
            margin-bottom:10px;
            border-radius:8px;
            background:#f4f4f4;
        }

        a{
            text-decoration:none;
        }

        .btn{
            padding:8px 12px;
            background:#28a745;
            color:white;
            border-radius:5px;
        }
    </style>
</head>

<body>

<h1>Finance UMKM</h1>

<div class="card">
    <h3>Total Pendapatan</h3>
    Rp <?= number_format($totalPendapatan['total'] ?? 0,0,',','.') ?>
</div>

<div class="card">
    <h3>Total Pengeluaran</h3>
    Rp <?= number_format($totalPengeluaran['total'] ?? 0,0,',','.') ?>
</div>

<div class="card">
    <h3>Laba Bersih</h3>
    Rp <?= number_format($laba,0,',','.') ?>
</div>

<br>

<a class="btn" href="tambah_pendapatan.php">+ Tambah Pendapatan</a>

<a class="btn" href="tambah_pengeluaran.php">
+ Tambah Pengeluaran
</a>

<hr>

<h2>Data Pendapatan</h2>

<table>
<tr>
    <th>NO</th>
    <th>Tanggal</th>
    <th>Keterangan</th>
    <th>Jumlah</th>
    <th>Aksi</th>
</tr>

<?php
$data = mysqli_query($koneksi,"SELECT * FROM pendapatan");
$no = 1;
while($d = mysqli_fetch_array($data)){
?>
<tr>
    <td><?= $no++; ?></td>
    <td><?= $d['tanggal']; ?></td>
    <td><?= $d['keterangan']; ?></td>
    <td>Rp <?= number_format($d['jumlah'],0,',','.'); ?></td>
    <td>
        <a href="hapus_pendapatan.php?id=<?= $d['id_pendapatan']; ?>">
            Hapus
        </a>
    </td>
</tr>
<?php } ?>

</table>

<br><br>

<h2>Data Pengeluaran</h2>

<table>
<tr>
    <th>NO</th>
    <th>Tanggal</th>
    <th>Keterangan</th>
    <th>Jumlah</th>
    <th>Aksi</th>
</tr>

<?php
$data = mysqli_query($koneksi,"SELECT * FROM pengeluaran");
$no = 1;
while($d = mysqli_fetch_array($data)){
?>
<tr>
    <td><?= $no++; ?></td>
    <td><?= $d['tanggal']; ?></td>
    <td><?= $d['keterangan']; ?></td>
    <td>Rp <?= number_format($d['jumlah'],0,',','.'); ?></td>
    <td>
        <a href="hapus_pengeluaran.php?id=<?= $d['id_pengeluaran']; ?>">
            Hapus
        </a>
    </td>
</tr>
<?php } ?>

</table>

</body>
</html>