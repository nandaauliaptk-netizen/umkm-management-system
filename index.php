<?php
session_start();
// Jika tidak ada session login, kembalikan user ke halaman login
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$totalPendapatan = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT SUM(jumlah) AS total FROM pendapatan")
);

$totalPengeluaran = mysqli_fetch_assoc(
    mysqli_query($koneksi, "SELECT SUM(jumlah) AS total FROM pengeluaran")
);

$laba = ($totalPendapatan['total'] ?? 0) - ($totalPengeluaran['total'] ?? 0);

$persentase = 0;

if(($totalPendapatan['total'] ?? 0) > 0){
    $persentase =
        ($laba / $totalPendapatan['total']) * 100;
}

$kesimpulan = "";

if($laba > 0){
    $kesimpulan = "🟢 Usaha Mengalami Keuntungan";
}
elseif($laba < 0){
    $kesimpulan = "🔴 Usaha Mengalami Kerugian";
}
else{
    $kesimpulan = "🟡 Usaha Berada Pada Kondisi Impas";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Finance UMKM</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


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

    <h2>
        Rp <?= number_format($laba,0,',','.') ?>
    </h2>

    <hr>

    <strong>
        Persentase Keuntungan:
        <?= number_format($persentase,2) ?>%
    </strong>

    <br><br>

    <?php
    if($persentase >= 50){
        echo "🚀 Sangat Baik";
    }
    elseif($persentase >= 20){
        echo "👍 Baik";
    }
    elseif($persentase > 0){
        echo "🙂 Cukup";
    }
    else{
        echo "⚠️ Rugi";
    }
    ?>

</div>

<div class="card">

    <h3>📈 Grafik Keuangan</h3>

    <canvas id="grafikKeuangan"></canvas>

    <br>

    <h3><?= $kesimpulan ?></h3>

    <?php if($laba > 0){ ?>
        <p>
            Pendapatan lebih besar daripada pengeluaran sehingga usaha memperoleh keuntungan.
        </p>
    <?php } ?>

    <?php if($laba < 0){ ?>
        <p>
            Pengeluaran lebih besar daripada pendapatan sehingga usaha mengalami kerugian.
        </p>
    <?php } ?>

    <?php if($laba == 0){ ?>
        <p>
            Pendapatan dan pengeluaran sama sehingga usaha berada pada kondisi impas.
        </p>
    <?php } ?>

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

    <a href="edit_pendapatan.php?id=<?= $d['id_pendapatan']; ?>">
        Edit
    </a>

    |

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

    <a href="edit_pengeluaran.php?id=<?= $d['id_pengeluaran']; ?>">
        Edit
    </a>

    |

    <a href="hapus_pengeluaran.php?id=<?= $d['id_pengeluaran']; ?>">
        Hapus
    </a>

</td>
</tr>
<?php } ?>

</table>

<script>

const ctx = document.getElementById('grafikKeuangan');

new Chart(ctx, {

    type: 'bar',

    data: {

        labels: [
            'Pendapatan',
            'Pengeluaran',
            'Laba Bersih'
        ],

        datasets: [

{
    label: 'Pendapatan',

    data: [
        <?= ($totalPendapatan['total'] ?? 0) ?>,
        null,
        null
    ],

    borderColor: 'green',
    backgroundColor: 'green',
    borderWidth: 4
},

{
    label: 'Pengeluaran',

    data: [
        null,
        <?= ($totalPengeluaran['total'] ?? 0) ?>,
        null
    ],

    borderColor: 'red',
    backgroundColor: 'red',
    borderWidth: 4
},

{
    label: 'Laba Bersih',

    data: [
        null,
        null,
        <?= $laba ?>
    ],

    borderColor: 'blue',
    backgroundColor: 'blue',
    borderWidth: 4
}

]

    },

    options: {
        responsive: true
    }

});

</script>

</body>
</html>