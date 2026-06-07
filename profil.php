// Cek apakah data sudah ada
$query = "SELECT * FROM profil_umkm WHERE id_user = " . $_SESSION['id_user'];
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if ($data) {
    echo "<h1>Profil: " . $data['nama_usaha'] . "</h1>";
    // Tampilkan data...
} else {
    // Tampilkan form input
    echo '<form action="simpan_profil.php" method="POST">
            <input type="text" name="nama_usaha" placeholder="Nama Usaha" required>
            <input type="text" name="pemilik" placeholder="Nama Pemilik">
            <textarea name="alamat" placeholder="Alamat"></textarea>
            <input type="text" name="telepon" placeholder="No Telepon">
            <textarea name="deskripsi" placeholder="Deskripsi Usaha"></textarea>
            <button type="submit">Simpan Profil</button>
          </form>';
}