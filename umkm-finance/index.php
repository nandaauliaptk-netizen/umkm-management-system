<?php
// ================================================================
// index.php — Halaman Utama Finance UMKM
// ================================================================
require_once __DIR__ . '/includes/db.php';

// Ambil summary langsung dari DB untuk server-side render awal
try {
    $db = getDB();
    $totalP = (float)$db->query("SELECT COALESCE(SUM(jumlah),0) FROM pendapatan")->fetchColumn();
    $totalE = (float)$db->query("SELECT COALESCE(SUM(jumlah),0) FROM pengeluaran")->fetchColumn();
    $countP = (int)$db->query("SELECT COUNT(*) FROM pendapatan")->fetchColumn();
    $countE = (int)$db->query("SELECT COUNT(*) FROM pengeluaran")->fetchColumn();
} catch (PDOException $e) {
    $totalP = $totalE = $countP = $countE = 0;
}

$laba = $totalP - $totalE;
$pct  = $totalP > 0 ? round(($laba / $totalP) * 100, 2) : 0;

function rp(float $n): string {
    return 'Rp ' . number_format(abs($n), 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Finance UMKM</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="assets/css/finance.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" defer></script>
  <script src="assets/js/finance.js" defer></script>
</head>
<body>

<div class="app-wrapper">

  <!-- ──────────────── SIDEBAR ──────────────── -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <a href="index.php" class="logo">
        <div class="logo-mark">
          <svg viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="3" y="3" width="9" height="9" rx="2" fill="#3b82f6"/>
            <rect x="14" y="3" width="9" height="9" rx="2" fill="#06b6d4" opacity=".7"/>
            <rect x="3" y="14" width="9" height="9" rx="2" fill="#7c3aed" opacity=".7"/>
            <rect x="14" y="14" width="9" height="9" rx="2" fill="#3b82f6" opacity=".5"/>
          </svg>
        </div>
        <div class="logo-text">
          <span class="brand-name">UMKM Next</span>
          <span class="brand-sub">Management</span>
        </div>
      </a>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-label">Utama</div>
      <a href="#" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a href="index.php" class="nav-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        Keuangan
      </a>
      <a href="#" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        Produk
      </a>
      <a href="#" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Pelanggan
      </a>

      <div class="nav-label">Laporan</div>
      <a href="#" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Analitik
      </a>
      <a href="#" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Laporan
      </a>
    </nav>

    <div class="sidebar-footer">
      <a href="#" class="nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>
        Pengaturan
      </a>
    </div>
  </aside>

  <!-- ──────────────── MAIN CONTENT ──────────────── -->
  <div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:14px;">
        <button class="btn-hamburger" id="btnHamburger" onclick="toggleSidebar()">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
        <span class="topbar-title">Keuangan</span>
      </div>
      <div class="topbar-right">
        <span class="live-badge" id="liveBadge">
          <span class="live-dot"></span>
          Live
        </span>
        <div class="topbar-avatar">U</div>
      </div>
    </header>

    <!-- ──────────────── PAGE CONTENT ──────────────── -->
    <main class="page-content">

      <!-- Page Header + Tombol Aksi -->
      <div class="finance-header-row">
        <div class="page-header" style="margin-bottom:0;">
          <h2>Finance UMKM</h2>
          <p>Ringkasan keuangan &amp; transaksi terkini</p>
        </div>
        <div class="btn-group">
          <button class="btn btn-success" onclick="openModal('pendapatan')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Pendapatan
          </button>
          <button class="btn btn-danger" onclick="openModal('pengeluaran')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Pengeluaran
          </button>
        </div>
      </div>

      <!-- ── Stat Cards ── -->
      <div class="stat-grid-finance">
        <div class="stat-card stat-card-income hover-lift">
          <div class="stat-icon">📈</div>
          <div class="stat-label">Total Pendapatan</div>
          <div class="stat-value text-success" id="stat-pendapatan"><?= rp($totalP) ?></div>
          <div class="stat-change up" id="stat-count-p">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
            <?= $countP ?> transaksi
          </div>
        </div>
        <div class="stat-card stat-card-expense hover-lift">
          <div class="stat-icon">📉</div>
          <div class="stat-label">Total Pengeluaran</div>
          <div class="stat-value text-danger" id="stat-pengeluaran"><?= rp($totalE) ?></div>
          <div class="stat-change down" id="stat-count-e">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            <?= $countE ?> transaksi
          </div>
        </div>
        <div class="stat-card hover-lift">
          <div class="stat-icon">🏦</div>
          <div class="stat-label">Total Transaksi</div>
          <div class="stat-value" id="stat-total-trx"><?= $countP + $countE ?></div>
          <div class="stat-change" style="color:var(--text-muted);" id="stat-updated">
            Update: <?= date('H:i:s') ?>
          </div>
        </div>
      </div>

      <!-- ── Laba Bersih Card ── -->
      <div class="laba-card" id="labaCard">
        <div class="laba-main">
          <div class="stat-label">Laba Bersih</div>
          <div class="stat-value <?= $laba >= 0 ? 'text-success' : 'text-danger' ?>" id="stat-laba">
            <?= ($laba < 0 ? '- ' : '') . rp($laba) ?>
          </div>
          <div class="laba-sub">
            Persentase Keuntungan:
            <strong id="stat-pct"><?= $pct ?>%</strong>
          </div>
        </div>
        <div class="laba-meta">
          <div class="laba-pct <?= $laba >= 0 ? 'text-success' : 'text-danger' ?>" id="stat-pct-big">
            <?= $pct ?>%
          </div>
          <span class="laba-status <?= $laba > 0 ? 'status-profit' : ($laba < 0 ? 'status-loss' : 'status-even') ?>" id="stat-status">
            <?php if ($laba > 0): ?>✅ Untung
            <?php elseif ($laba < 0): ?>⚠️ Rugi
            <?php else: ?>— Break Even<?php endif; ?>
          </span>
        </div>
      </div>

      <!-- ── Grafik Keuangan ── -->
      <div class="chart-card">
        <div class="chart-card-header">
          <div style="display:flex;align-items:center;gap:10px;">
            <h3>Grafik Keuangan</h3>
            <span class="live-badge" style="font-size:10px;padding:3px 8px;">
              <span class="live-dot"></span> Real-time
            </span>
          </div>
          <div class="chart-legend">
            <div class="legend-item"><div class="legend-dot" style="background:#10b981;"></div> Pendapatan</div>
            <div class="legend-item"><div class="legend-dot" style="background:#ef4444;"></div> Pengeluaran</div>
            <div class="legend-item"><div class="legend-dot" style="background:#3b82f6;"></div> Laba Bersih</div>
          </div>
        </div>
        <div class="chart-wrap">
          <canvas id="financeChart"></canvas>
        </div>
      </div>

      <!-- ── Tabel Data ── -->
      <div class="tables-grid">

        <!-- Tabel Pendapatan -->
        <div class="section-card">
          <div class="section-card-header">
            <h3><span class="dot-green"></span> Data Pendapatan</h3>
            <span class="badge badge-green" id="badge-count-p"><?= $countP ?> data</span>
          </div>
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Keterangan</th>
                  <th>Kategori</th>
                  <th class="text-right">Jumlah</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="tbody-pendapatan">
                <tr><td colspan="5"><div class="empty-state">Memuat data…</div></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tabel Pengeluaran -->
        <div class="section-card">
          <div class="section-card-header">
            <h3><span class="dot-red"></span> Data Pengeluaran</h3>
            <span class="badge badge-red" id="badge-count-e"><?= $countE ?> data</span>
          </div>
          <div class="table-wrap">
            <table class="table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Keterangan</th>
                  <th>Kategori</th>
                  <th class="text-right">Jumlah</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="tbody-pengeluaran">
                <tr><td colspan="5"><div class="empty-state">Memuat data…</div></td></tr>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /tables-grid -->

    </main>
  </div><!-- /main-content -->
</div><!-- /app-wrapper -->


<!-- ══════════════════════════════════════════════
     MODAL TAMBAH PENDAPATAN
══════════════════════════════════════════════ -->
<div class="modal-overlay hidden" id="modal-pendapatan">
  <div class="modal">
    <div class="modal-header">
      <h3>➕ Tambah Pendapatan</h3>
      <button class="modal-close" onclick="closeModal('pendapatan')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <div class="form-group">
      <label class="form-label">Keterangan</label>
      <div class="input-wrap">
        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <input type="text" class="form-control" id="p-keterangan" placeholder="cth: Penjualan produk A">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Jumlah (Rp)</label>
        <div class="input-wrap">
          <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          <input type="number" class="form-control" id="p-jumlah" placeholder="0" min="1">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Kategori</label>
        <select class="form-control" id="p-kategori">
          <option>Penjualan</option>
          <option>Jasa</option>
          <option>Investasi</option>
          <option>Lainnya</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Tanggal</label>
      <div class="input-wrap">
        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <input type="date" class="form-control" id="p-tanggal">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('pendapatan')">Batal</button>
      <button class="btn btn-success" id="btn-save-p" onclick="simpan('pendapatan')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Simpan
      </button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════
     MODAL TAMBAH PENGELUARAN
══════════════════════════════════════════════ -->
<div class="modal-overlay hidden" id="modal-pengeluaran">
  <div class="modal">
    <div class="modal-header">
      <h3>➕ Tambah Pengeluaran</h3>
      <button class="modal-close" onclick="closeModal('pengeluaran')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <div class="form-group">
      <label class="form-label">Keterangan</label>
      <div class="input-wrap">
        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <input type="text" class="form-control" id="e-keterangan" placeholder="cth: Beli bahan baku">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Jumlah (Rp)</label>
        <div class="input-wrap">
          <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          <input type="number" class="form-control" id="e-jumlah" placeholder="0" min="1">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Kategori</label>
        <select class="form-control" id="e-kategori">
          <option>Bahan Baku</option>
          <option>Operasional</option>
          <option>Gaji</option>
          <option>Marketing</option>
          <option>Lainnya</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Tanggal</label>
      <div class="input-wrap">
        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <input type="date" class="form-control" id="e-tanggal">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('pengeluaran')">Batal</button>
      <button class="btn btn-danger" id="btn-save-e" onclick="simpan('pengeluaran')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Simpan
      </button>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

</body>
</html>
