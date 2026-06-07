<!-- Header Dashboard (Pastikan sudah include header.php atau struktur app-wrapper) -->
<div class="page-header">
    <h2>Tambah Pendapatan</h2>
    <p>Masukkan detail transaksi pendapatan baru.</p>
</div>

<div class="card" style="max-width: 600px;">
    <form action="proses_pendapatan.php" method="POST">
        <div class="form-group">
            <label class="form-label">Jumlah (Rp)</label>
            <div class="input-wrap">
                <input class="form-control no-icon" type="number" name="jumlah" placeholder="Contoh: 50000" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Keterangan</label>
            <div class="input-wrap">
                <input class="form-control no-icon" type="text" name="keterangan" placeholder="Sumber pendapatan..." required>
            </div>
        </div>

        <div class="modal-footer" style="margin-top: 10px; border-top: none; padding-top: 10px;">
            <a href="index.php" class="btn btn-secondary">Batal</a>
            <button type="submit" name="submit" class="btn btn-primary">
                Simpan Pendapatan
            </button>
        </div>
    </form>
</div>