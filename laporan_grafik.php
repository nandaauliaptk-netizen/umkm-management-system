<?php
include 'koneksi.php';

$bulan = [
    1=>"Jan",2=>"Feb",3=>"Mar",4=>"Apr",
    5=>"Mei",6=>"Jun",7=>"Jul",8=>"Agu",
    9=>"Sep",10=>"Okt",11=>"Nov",12=>"Des"
];

$pendapatanData = array_fill(1,12,0);
$pengeluaranData = array_fill(1,12,0);

$queryPendapatan = mysqli_query($koneksi,"
SELECT MONTH(tanggal) AS bulan,
SUM(jumlah) AS total
FROM pendapatan
GROUP BY MONTH(tanggal)
");

while($row = mysqli_fetch_assoc($queryPendapatan)){
    $pendapatanData[$row['bulan']] = $row['total'];
}

$queryPengeluaran = mysqli_query($koneksi,"
SELECT MONTH(tanggal) AS bulan,
SUM(jumlah) AS total
FROM pengeluaran
GROUP BY MONTH(tanggal)
");

while($row = mysqli_fetch_assoc($queryPengeluaran)){
    $pengeluaranData[$row['bulan']] = $row['total'];
}

$labaData = [];

for($i=1;$i<=12;$i++){
    $labaData[$i] =
        $pendapatanData[$i] -
        $pengeluaranData[$i];
}

$totalPendapatan = array_sum($pendapatanData);
$totalPengeluaran = array_sum($pengeluaranData);
$totalLaba = $totalPendapatan - $totalPengeluaran;

if($totalLaba > 0){
    $kesimpulan = "Usaha mengalami KEUNTUNGAN";
}
elseif($totalLaba < 0){
    $kesimpulan = "Usaha mengalami KERUGIAN";
}
else{
    $kesimpulan = "Usaha berada pada kondisi IMPAS";
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Grafik Keuangan UMKM</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
    font-family:Arial;
    margin:40px;
}

.card{
    margin-top:30px;
    padding:20px;
    border-radius:10px;
    background:#f4f4f4;
}

</style>

</head>

<body>

<h1>Grafik Keuangan UMKM</h1>

<canvas id="grafik"></canvas>

<div class="card">

<h2>Ringkasan Analisis</h2>

<p>
Total Pendapatan :
<b>
Rp <?= number_format($totalPendapatan,0,',','.') ?>
</b>
</p>

<p>
Total Pengeluaran :
<b>
Rp <?= number_format($totalPengeluaran,0,',','.') ?>
</b>
</p>

<p>
Total Laba :
<b>
Rp <?= number_format($totalLaba,0,',','.') ?>
</b>
</p>

<h3>
<?= $kesimpulan ?>
</h3>

<?php if($totalLaba > 0){ ?>
<p>
Pendapatan lebih besar daripada pengeluaran sehingga usaha menghasilkan laba.
</p>
<?php } ?>

<?php if($totalLaba < 0){ ?>
<p>
Pengeluaran lebih besar daripada pendapatan sehingga usaha mengalami kerugian.
</p>
<?php } ?>

</div>

<script>

const ctx = document.getElementById('grafik');

new Chart(ctx, {

type:'bar',

data:{

labels:[
'Jan','Feb','Mar','Apr','Mei','Jun',
'Jul','Agu','Sep','Okt','Nov','Des'
],

datasets:[

{
label:'Pendapatan',
data:<?= json_encode(array_values($pendapatanData)) ?>
},

{
label:'Pengeluaran',
data:<?= json_encode(array_values($pengeluaranData)) ?>
},

{
label:'Laba',
data:<?= json_encode(array_values($labaData)) ?>
}

]

},

options:{
responsive:true
}

});

</script>

</body>
</html>