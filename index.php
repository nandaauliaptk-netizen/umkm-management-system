<?php
// ================================================================
// index.php — Dashboard Utama UMKM NEXT
// ================================================================
require_once 'koneksi.php';

// Wajib login
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

// Jika skip setup profil, update session agar tidak loop
if (isset($_GET['skip'])) {
    $_SESSION['profil_lengkap'] = true;
}

$id_user = (int)$_SESSION['id_user'];
$nama    = htmlspecialchars($_SESSION['nama'] ?? 'Pengguna');
$welcome = isset($_GET['welcome']);

// ── Ambil data profil UMKM
$profil = [];
$q = $conn->prepare("SELECT * FROM profil_umkm WHERE id_user = ?");
$q->bind_param('i', $id_user);
$q->execute();
$res = $q->get_result();
if ($res->num_rows > 0) {
    $profil = $res->fetch_assoc();
    $_SESSION['nama_usaha'] = $profil['nama_usaha'];
}
$q->close();

$nama_usaha = htmlspecialchars($profil['nama_usaha'] ?? 'Usaha Saya');

// ── Statistik ringkas dari tabel lain (aman jika tabel belum ada)
$stat_pendapatan = 0;
$stat_pengeluaran = 0;
$stat_produk = 0;
$stat_invoice = 0;

// Pendapatan bulan ini
$tbl = $conn->query("SHOW TABLES LIKE 'pendapatan'");
if ($tbl && $tbl->num_rows > 0) {
    $sq = $conn->prepare("SELECT COALESCE(SUM(jumlah),0) as total FROM pendapatan WHERE id_user=? AND MONTH(tanggal)=MONTH(NOW()) AND YEAR(tanggal)=YEAR(NOW())");
    if ($sq) { $sq->bind_param('i',$id_user); $sq->execute(); $r=$sq->get_result()->fetch_assoc(); $stat_pendapatan=$r['total']; $sq->close(); }
}

// Pengeluaran bulan ini
$tbl = $conn->query("SHOW TABLES LIKE 'pengeluaran'");
if ($tbl && $tbl->num_rows > 0) {
    $sq = $conn->prepare("SELECT COALESCE(SUM(jumlah),0) as total FROM pengeluaran WHERE id_user=? AND MONTH(tanggal)=MONTH(NOW()) AND YEAR(tanggal)=YEAR(NOW())");
    if ($sq) { $sq->bind_param('i',$id_user); $sq->execute(); $r=$sq->get_result()->fetch_assoc(); $stat_pengeluaran=$r['total']; $sq->close(); }
}

// Produk
$tbl = $conn->query("SHOW TABLES LIKE 'produk'");
if ($tbl && $tbl->num_rows > 0) {
    $sq = $conn->prepare("SELECT COUNT(*) as total FROM produk WHERE id_user=?");
    if ($sq) { $sq->bind_param('i',$id_user); $sq->execute(); $r=$sq->get_result()->fetch_assoc(); $stat_produk=$r['total']; $sq->close(); }
}

// Invoice
$tbl = $conn->query("SHOW TABLES LIKE 'invoice'");
if ($tbl && $tbl->num_rows > 0) {
    $sq = $conn->prepare("SELECT COUNT(*) as total FROM invoice WHERE id_user=?");
    if ($sq) { $sq->bind_param('i',$id_user); $sq->execute(); $r=$sq->get_result()->fetch_assoc(); $stat_invoice=$r['total']; $sq->close(); }
}

$laba = $stat_pendapatan - $stat_pengeluaran;

function formatRp($n) {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

$current_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — UMKM NEXT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
/* Stat cards grid */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--gap-md);
    margin-bottom: var(--gap-lg);
}
.stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 22px 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: border-color var(--tr), box-shadow var(--tr);
}
.stat-card:hover {
    border-color: var(--border-hover);
    box-shadow: var(--shadow-sm);
}
.stat-icon {
    width: 40px; height: 40px;
    border-radius: var(--radius-sm);
    display: flex; align-items: center; justify-content: center;
}
.stat-icon svg { width: 20px; height: 20px; }
.stat-icon.green  { background: rgba(16,185,129,0.12); color: var(--success); }
.stat-icon.red    { background: rgba(239,68,68,0.12);  color: var(--danger); }
.stat-icon.blue   { background: rgba(59,130,246,0.12); color: var(--accent2); }
.stat-icon.gold   { background: rgba(245,158,11,0.12); color: var(--gold); }
.stat-icon.purple { background: rgba(124,58,237,0.12); color: var(--purple); }
.stat-icon.teal   { background: rgba(6,182,212,0.12);  color: var(--teal); }

.stat-label { font-size: 12px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-value { font-family: 'Syne', sans-serif; font-size: 1.45rem; font-weight: 800; color: var(--text); }
.stat-change { font-size: 12px; color: var(--text-muted); }
.stat-change.up   { color: var(--success); }
.stat-change.down { color: var(--danger); }

/* Welcome banner */
.welcome-banner {
    background: linear-gradient(135deg, rgba(37,99,235,0.15) 0%, rgba(124,58,237,0.1) 100%);
    border: 1px solid rgba(37,99,235,0.2);
    border-radius: var(--radius-lg);
    padding: 20px 24px;
    margin-bottom: var(--gap-md);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.welcome-banner-text h3 { font-size: 1.1rem; margin-bottom: 4px; }
.welcome-banner-text p  { font-size: 13px; color: var(--text-muted); }

/* Profile incomplete banner */
.profil-alert {
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.25);
    border-radius: var(--radius-md);
    padding: 14px 20px;
    margin-bottom: var(--gap-md);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    color: var(--gold);
}

/* Quick actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: var(--gap-sm);
    margin-bottom: var(--gap-lg);
}
.qa-btn {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--text);
    font-size: 13px;
    font-weight: 500;
    transition: background var(--tr), border-color var(--tr), transform var(--tr);
}
.qa-btn:hover {
    background: var(--bg-glass-hover);
    border-color: var(--border-hover);
    transform: translateY(-2px);
    text-decoration: none;
    color: var(--text);
}
.qa-btn svg { width: 18px; height: 18px; flex-shrink: 0; }

/* Section header */
.section-hdr {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.section-hdr h3 { font-size: 1.05rem; }

/* Profile card */
.profil-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 24px;
}
.profil-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 24px;
}
.profil-item label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted);
    display: block;
    margin-bottom: 3px;
}
.profil-item span {
    font-size: 14px;
    color: var(--text);
}

/* Grid dashboard bawah */
.dash-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--gap-md);
}

/* Toast */
.toast-container {
    position: fixed; bottom: 24px; right: 24px;
    z-index: 999; display: flex; flex-direction: column; gap: 10px;
}
.toast {
    background: var(--bg-panel);
    border: 1px solid var(--border);
    border-left: 3px solid var(--success);
    border-radius: var(--radius-md);
    padding: 14px 18px;
    min-width: 280px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: var(--shadow-md);
    animation: slideInRight .3s ease;
    font-size: 13px;
}
@keyframes slideInRight {
    from { opacity:0; transform: translateX(30px); }
    to   { opacity:1; transform: none; }
}
</style>
</head>
<body>
<div class="app-wrapper">

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="logo">
            <div class="logo-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <div class="logo-text">
                <span class="brand-name">UMKM NEXT</span>
                <span class="brand-sub">Manajemen Usaha</span>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Menu Utama</div>
        <a href="index.php" class="nav-item <?= $current_page==='dashboard'?'active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a href="produk.php" class="nav-item <?= $current_page==='produk'?'active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
            Produk
        </a>
        <a href="invoice.php" class="nav-item <?= $current_page==='invoice'?'active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Invoice
        </a>

        <div class="nav-label">Keuangan</div>
        <a href="pendapatan.php" class="nav-item <?= $current_page==='pendapatan'?'active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            Pendapatan
        </a>
        <a href="pengeluaran.php" class="nav-item <?= $current_page==='pengeluaran'?'active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
            Pengeluaran
        </a>
        <a href="umkm.php" class="nav-item <?= $current_page==='umkm'?'active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            Laporan
        </a>

        <div class="nav-label">Akun</div>
        <a href="setup_profil.php" class="nav-item <?= $current_page==='profil'?'active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            Profil UMKM
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item" style="color:var(--danger)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </a>
    </div>
</aside>

<!-- ═══════════════ MAIN CONTENT ═══════════════ -->
<div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-title">Dashboard</div>
        <div class="topbar-right" style="display:flex;align-items:center;gap:16px;">
            <div style="text-align:right;">
                <div style="font-size:13px;font-weight:600;"><?= $nama ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?= $nama_usaha ?></div>
            </div>
            <div style="width:38px;height:38px;background:var(--grad-brand);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:14px;">
                <?= strtoupper(mb_substr($_SESSION['nama'] ?? 'U', 0, 1)) ?>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">

        <?php if ($welcome): ?>
        <div class="welcome-banner">
            <div class="welcome-banner-text">
                <h3>🎉 Selamat datang, <?= $nama ?>!</h3>
                <p>Profil UMKM Anda sudah tersimpan. Mulai kelola usaha Anda sekarang.</p>
            </div>
            <button onclick="this.parentElement.remove()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:18px;padding:4px 8px;">✕</button>
        </div>
        <?php endif; ?>

        <?php if (empty($profil)): ?>
        <div class="profil-alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>Profil UMKM belum dilengkapi. <a href="setup_profil.php" style="color:var(--gold);font-weight:600;">Lengkapi sekarang →</a></span>
        </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
                </div>
                <div class="stat-label">Pendapatan Bulan Ini</div>
                <div class="stat-value" style="color:var(--success)"><?= formatRp($stat_pendapatan) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg>
                </div>
                <div class="stat-label">Pengeluaran Bulan Ini</div>
                <div class="stat-value" style="color:var(--danger)"><?= formatRp($stat_pengeluaran) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon <?= $laba >= 0 ? 'teal' : 'red' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <div class="stat-label">Laba Bersih</div>
                <div class="stat-value" style="color:<?= $laba >= 0 ? 'var(--teal)' : 'var(--danger)' ?>"><?= formatRp($laba) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                </div>
                <div class="stat-label">Total Produk</div>
                <div class="stat-value"><?= number_format($stat_produk) ?></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-hdr">
            <h3>Aksi Cepat</h3>
        </div>
        <div class="quick-actions" style="margin-bottom:var(--gap-lg)">
            <a href="pendapatan.php?add=1" class="qa-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Pendapatan
            </a>
            <a href="pengeluaran.php?add=1" class="qa-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Pengeluaran
            </a>
            <a href="produk.php?add=1" class="qa-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent2)" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><line x1="12" y1="2" x2="12" y2="22"/></svg>
                Tambah Produk
            </a>
            <a href="invoice.php?add=1" class="qa-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><line x1="12" y1="2" x2="12" y2="8"/></svg>
                Buat Invoice
            </a>
            <a href="setup_profil.php" class="qa-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Edit Profil UMKM
            </a>
        </div>

        <!-- Dashboard Grid: Profil + Invoice -->
        <div class="dash-grid">

            <!-- Profil UMKM -->
            <div>
                <div class="section-hdr">
                    <h3>Profil UMKM</h3>
                    <a href="setup_profil.php" class="btn btn-ghost" style="font-size:12px;padding:6px 12px;">Edit</a>
                </div>
                <div class="profil-card">
                    <?php if (!empty($profil)): ?>
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border);">
                        <div style="width:50px;height:50px;background:var(--grad-brand);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:18px;color:white;flex-shrink:0;">
                            <?= strtoupper(mb_substr($profil['nama_usaha'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:15px;"><?= htmlspecialchars($profil['nama_usaha']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($profil['jenis_usaha'] ?? '-') ?> · <?= htmlspecialchars($profil['kategori'] ?? '-') ?></div>
                        </div>
                    </div>
                    <div class="profil-row">
                        <div class="profil-item">
                            <label>Pemilik</label>
                            <span><?= htmlspecialchars($profil['pemilik'] ?? '-') ?></span>
                        </div>
                        <div class="profil-item">
                            <label>No. HP</label>
                            <span><?= htmlspecialchars($profil['no_telp'] ?? $profil['telepon'] ?? '-') ?></span>
                        </div>
                        <div class="profil-item">
                            <label>Karyawan</label>
                            <span><?= htmlspecialchars($profil['jumlah_karyawan'] ?? '0') ?> orang</span>
                        </div>
                        <div class="profil-item">
                            <label>NIB</label>
                            <span><?= htmlspecialchars($profil['nomor_nib'] ?? '-') ?></span>
                        </div>
                        <div class="profil-item" style="grid-column:1/-1">
                            <label>Alamat</label>
                            <span><?= htmlspecialchars($profil['alamat'] ?? '-') ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="text-align:center;padding:24px 0;color:var(--text-muted);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:.4"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        <p style="font-size:13px;">Profil belum diisi</p>
                        <a href="setup_profil.php" class="btn btn-primary" style="margin-top:12px;display:inline-flex;font-size:12px;padding:8px 16px;">Lengkapi Sekarang</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ringkasan Invoice -->
            <div>
                <div class="section-hdr">
                    <h3>Invoice Terbaru</h3>
                    <a href="invoice.php" class="btn btn-ghost" style="font-size:12px;padding:6px 12px;">Lihat Semua</a>
                </div>
                <div class="card" style="padding:0;overflow:hidden;">
                    <?php
                    $inv_list = [];
                    $tbl2 = $conn->query("SHOW TABLES LIKE 'invoice'");
                    if ($tbl2 && $tbl2->num_rows > 0) {
                        $iq = $conn->prepare("SELECT * FROM invoice WHERE id_user=? ORDER BY id_invoice DESC LIMIT 5");
                        if ($iq) { $iq->bind_param('i',$id_user); $iq->execute(); $inv_list=$iq->get_result()->fetch_all(MYSQLI_ASSOC); $iq->close(); }
                    }
                    ?>
                    <?php if (!empty($inv_list)): ?>
                    <div class="table-wrap" style="margin:0;">
                        <table class="table">
                            <thead><tr>
                                <th>No. Invoice</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach($inv_list as $inv): ?>
                            <tr>
                                <td style="font-size:13px;"><?= htmlspecialchars($inv['no_invoice'] ?? '-') ?></td>
                                <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($inv['tanggal'] ?? '-') ?></td>
                                <td style="font-size:13px;font-weight:600;"><?= formatRp($inv['total'] ?? 0) ?></td>
                                <td>
                                    <?php
                                    $st = strtolower($inv['status'] ?? '');
                                    $cls = $st === 'lunas' ? 'badge-success' : ($st === 'belum' ? 'badge-warning' : 'badge-info');
                                    ?>
                                    <span class="badge <?= $cls ?>"><?= htmlspecialchars($inv['status'] ?? '-') ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="padding:32px;text-align:center;color:var(--text-muted);">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;display:block;opacity:.4"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <p style="font-size:13px;">Belum ada invoice</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.dash-grid -->

    </main>
</div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<?php if ($welcome): ?>
<div class="toast-container" id="toastContainer">
    <div class="toast">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Profil UMKM berhasil disimpan!
    </div>
</div>
<script>
setTimeout(() => {
    const tc = document.getElementById('toastContainer');
    if (tc) tc.style.opacity = '0', tc.style.transition = 'opacity .4s', setTimeout(()=>tc.remove(), 400);
}, 3500);
</script>
<?php endif; ?>

</body>
</html>