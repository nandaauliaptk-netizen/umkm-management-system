<?php
session_start();
require_once 'koneksi.php';

// ── Guard ──
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$user_id = (int)$_SESSION['user_id'];
$nama    = $_SESSION['nama'] ?? 'Pengguna';
$inisial = strtoupper(mb_substr($nama, 0, 1));

// ── Cek profil UMKM dari DB (source of truth) ──
$profil = null;
$sp = $koneksi->prepare('SELECT * FROM profil_umkm WHERE id_user=? LIMIT 1');
$sp->bind_param('i', $user_id); $sp->execute();
$rp = $sp->get_result();
if ($rp->num_rows) $profil = $rp->fetch_assoc();
$sp->close();

// Jika profil belum complete → wajib ke profil_umkm
if (!$profil || !$profil['is_complete']) {
    header('Location: profil_umkm.php'); exit();
}

$_SESSION['profil_complete'] = true;
$nama_usaha   = htmlspecialchars($profil['nama_usaha'] ?? '');
$jenis_usaha  = htmlspecialchars($profil['jenis_usaha'] ?? 'UMKM');
$welcome      = (isset($_GET['welcome']) && $_GET['welcome'] === '1');

// ── Stats dari DB ──
function q(mysqli $db, string $sql, int $uid): string {
    $s=$db->prepare($sql); $s->bind_param('i',$uid); $s->execute();
    $s->bind_result($v); $s->fetch(); $s->close();
    return $v ?? '0';
}
$total_pendapatan = (float)q($koneksi,"SELECT COALESCE(SUM(total),0) FROM pendapatan WHERE id_user=? AND MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())",$user_id);
$total_pesanan    = (int)q($koneksi,"SELECT COUNT(*) FROM invoice WHERE id_user=?",$user_id);
$total_produk     = (int)q($koneksi,"SELECT COUNT(*) FROM produk WHERE id_user=? AND is_active=1",$user_id);
$total_pengeluaran= (float)q($koneksi,"SELECT COALESCE(SUM(total),0) FROM pengeluaran WHERE id_user=? AND MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())",$user_id);
$laba_bulan       = $total_pendapatan - $total_pengeluaran;

// ── 5 Transaksi Terbaru (pendapatan + pengeluaran gabung) ──
$txs = [];
$qt = $koneksi->prepare("
    (SELECT 'pendapatan' AS tipe, deskripsi, total, tanggal, kategori FROM pendapatan WHERE id_user=?)
    UNION ALL
    (SELECT 'pengeluaran', deskripsi, total, tanggal, kategori FROM pengeluaran WHERE id_user=?)
    ORDER BY tanggal DESC LIMIT 6
");
$qt->bind_param('ii', $user_id, $user_id);
$qt->execute();
$txs = $qt->get_result()->fetch_all(MYSQLI_ASSOC);
$qt->close();

// ── Chart: pendapatan 6 bulan terakhir ──
$chart = [];
for ($i = 5; $i >= 0; $i--) {
    $tgl = date('Y-m', strtotime("-$i months"));
    $yy  = date('Y', strtotime("-$i months"));
    $mm  = date('m', strtotime("-$i months"));
    $cs  = $koneksi->prepare("SELECT COALESCE(SUM(total),0) FROM pendapatan WHERE id_user=? AND YEAR(tanggal)=? AND MONTH(tanggal)=?");
    $cs->bind_param('iii', $user_id, $yy, $mm);
    $cs->execute(); $cs->bind_result($val); $cs->fetch(); $cs->close();
    $chart[] = ['label' => date('M', strtotime($tgl.'-01')), 'value' => (float)$val];
}
$chart_max = max(array_column($chart, 'value')) ?: 1;

function rupiah(float $n): string { return 'Rp '.number_format($n,0,',','.'); }
function e(mixed $v): string { return htmlspecialchars((string)($v??''),ENT_QUOTES,'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — UMKM Next</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
/* ── Extra dashboard styles (tidak ada di style.css) ── */

/* noise overlay */
body::before {
    content:''; position:fixed; inset:0;
    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events:none; z-index:0;
}
.app-wrapper,.sidebar,.main-content,.topbar,.page-content { position:relative; z-index:1; }

/* User card in sidebar */
.user-card { display:flex; align-items:center; gap:10px; }
.user-avatar {
    width:34px; height:34px; border-radius:50%;
    background:linear-gradient(135deg,var(--accent),var(--teal));
    display:flex; align-items:center; justify-content:center;
    font-family:'Syne',sans-serif; font-size:13px; font-weight:800; color:#fff; flex-shrink:0;
}
.user-name  { font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.user-role  { font-size:11px; color:var(--text-muted); }
.logout-btn {
    width:28px; height:28px; border-radius:var(--radius-sm);
    background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.15);
    display:flex; align-items:center; justify-content:center;
    color:var(--danger); cursor:pointer; text-decoration:none;
    transition:background var(--tr);
}
.logout-btn:hover { background:rgba(239,68,68,.18); }
.logout-btn svg { width:14px; height:14px; }

/* Hamburger (mobile) */
.hamburger {
    display:none; background:none; border:none; cursor:pointer;
    color:var(--text); padding:4px;
}
.hamburger svg { width:20px; height:20px; }

/* Notif btn */
.notif-btn {
    width:36px; height:36px; border-radius:var(--radius-sm);
    background:var(--bg-glass); border:1px solid var(--border);
    display:flex; align-items:center; justify-content:center;
    color:var(--text-muted); cursor:pointer; position:relative;
    transition:background var(--tr);
}
.notif-btn:hover { background:var(--bg-glass-hover); color:var(--text); }
.notif-btn svg { width:16px; height:16px; }
.notif-dot { position:absolute; top:6px; right:6px; width:7px; height:7px; border-radius:50%; background:var(--danger); border:1.5px solid var(--bg-panel); }

/* Welcome banner */
.welcome-banner {
    background:linear-gradient(135deg,rgba(37,99,235,.16),rgba(6,182,212,.10));
    border:1px solid rgba(37,99,235,.28);
    border-radius:var(--radius-lg); padding:18px 22px;
    display:flex; align-items:center; gap:14px; margin-bottom:24px;
    animation:fadeIn .4s ease;
}
.wb-icon { font-size:32px; flex-shrink:0; animation:wave 1.5s ease .4s; }
@keyframes wave{0%,100%{transform:rotate(0)}25%{transform:rotate(20deg)}75%{transform:rotate(-10deg)}}
.wb-text h3 { font-size:16px; margin-bottom:3px; }
.wb-text p  { font-size:12px; color:var(--text-muted); }
.wb-close { background:none; border:none; cursor:pointer; color:var(--text-muted); margin-left:auto; }
.wb-close:hover { color:var(--text); }
.wb-close svg { width:16px; height:16px; }

/* Profile alert */
.profile-alert {
    background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.25);
    border-radius:var(--radius-md); padding:14px 18px;
    display:flex; align-items:center; gap:12px; margin-bottom:24px;
}
.pa-icon { font-size:20px; flex-shrink:0; }
.pa-text strong { font-size:13px; font-weight:600; color:#fcd34d; display:block; margin-bottom:2px; }
.pa-text span { font-size:12px; color:var(--text-muted); }

/* Greeting */
.greeting-row {
    display:flex; align-items:flex-end; justify-content:space-between;
    margin-bottom:24px; flex-wrap:wrap; gap:14px;
}
.greeting-row h1 { font-size:1.8rem; margin-bottom:4px; }
.greeting-row p  { font-size:13px; color:var(--text-muted); }

/* Nav badge */
.nav-badge {
    margin-left:auto; font-size:10px; font-weight:700;
    background:var(--accent); color:#fff;
    padding:1px 7px; border-radius:99px;
}
.nav-badge.warn { background:var(--gold); color:#000; }

/* Stat grid custom colors */
.stat-grid-4 { grid-template-columns:repeat(4,1fr); }

/* Chart bars */
.chart-container { padding:20px; }
.chart-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.chart-bars { display:flex; align-items:flex-end; gap:10px; height:180px; }
.chart-col { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; height:100%; justify-content:flex-end; }
.chart-bar {
    width:100%; border-radius:6px 6px 0 0;
    background:linear-gradient(180deg,var(--accent2) 0%,rgba(59,130,246,.25) 100%);
    transition:filter .2s; cursor:pointer; min-height:4px;
}
.chart-bar:hover { filter:brightness(1.3); }
.chart-bar.last { background:linear-gradient(180deg,var(--teal) 0%,rgba(6,182,212,.25) 100%); }
.chart-month { font-size:10px; color:var(--text-muted); }

/* TX list */
.tx-list { padding:4px 0; }
.tx-row {
    display:flex; align-items:center; gap:12px;
    padding:11px 22px; transition:background var(--tr);
}
.tx-row:hover { background:var(--bg-glass); }
.tx-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.tx-icon.income { background:rgba(16,185,129,.12); }
.tx-icon.expense{ background:rgba(239,68,68,.12); }
.tx-name { font-size:13px; font-weight:500; }
.tx-date { font-size:11px; color:var(--text-muted); }
.tx-amt  { font-size:13px; font-weight:600; margin-left:auto; flex-shrink:0; }

/* 2-col grid */
.grid-2 { display:grid; grid-template-columns:1fr 360px; gap:20px; margin-bottom:24px; }
.grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }

/* Card head */
.card-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
.card-head h3 { font-size:15px; }
.card-head .sub { font-size:12px; color:var(--text-muted); margin-top:2px; }
.card-head a { font-size:12px; color:var(--accent2); text-decoration:none; }
.card-head select { background:var(--bg-glass); border:1px solid var(--border); border-radius:6px; color:var(--text-muted); font-size:12px; padding:5px 10px; outline:none; }

/* Empty state */
.empty-state { text-align:center; padding:36px 20px; color:var(--text-muted); }
.empty-state .em-icon { font-size:40px; margin-bottom:12px; }
.empty-state p { font-size:13px; margin-bottom:12px; }

/* Checklist */
.checklist { display:flex; flex-direction:column; gap:0; }
.cl-item { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--border); }
.cl-item:last-child { border-bottom:none; }
.cl-check {
    width:18px; height:18px; border-radius:5px;
    border:1.5px solid var(--border);
    display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:10px;
}
.cl-check.done { background:var(--success); border-color:var(--success); color:#fff; }
.cl-label { font-size:13px; flex:1; }
.cl-label.done { text-decoration:line-through; color:var(--text-muted); }

/* Tip card */
.tip-card { background:linear-gradient(135deg,rgba(99,51,220,.12),rgba(6,182,212,.08)); border:1px solid rgba(99,51,220,.2); border-radius:var(--radius-md); padding:18px; }
.tip-card h4 { font-size:14px; font-weight:700; margin-bottom:8px; }
.tip-card p  { font-size:12px; color:var(--text-muted); line-height:1.7; margin-bottom:14px; }
.tip-link    { font-size:12px; color:var(--accent2); font-weight:600; display:inline-flex; align-items:center; gap:4px; }

/* Sidebar overlay */
.sidebar-overlay { display:none; position:fixed; inset:0; z-index:99; background:rgba(0,0,0,.5); }
.sidebar-overlay.show { display:block; }

@media(max-width:1100px){ .stat-grid-4{grid-template-columns:repeat(2,1fr)} .grid-2{grid-template-columns:1fr} .grid-3{grid-template-columns:1fr 1fr} }
@media(max-width:768px){ .hamburger{display:flex} .grid-3{grid-template-columns:1fr} }
</style>
</head>
<body>
<div class="app-wrapper">

<!-- ════ SIDEBAR ════ -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <a href="index.php" class="logo">
      <div class="logo-mark">
        <svg viewBox="0 0 28 28" fill="none"><path d="M2 4L2 16Q2 22 8 22L12 22Q14 22 14 19" stroke="white" stroke-width="2.8" stroke-linecap="round" fill="none"/><path d="M16 22L16 8L24 22L24 8" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/><path d="M20 6L26 2M23.5 2L26 2L26 4.5" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="logo-text"><span class="brand-name">UMKM Next</span><span class="brand-sub">Management System</span></div>
    </a>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Utama</div>
    <a href="index.php" class="nav-item active">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      Dashboard
    </a>
    <a href="laporan.php" class="nav-item">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      Laporan Keuangan
    </a>
    <a href="produk.php" class="nav-item">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
      Produk &amp; Stok
      <?php if ($total_produk > 0): ?><span class="nav-badge"><?= $total_produk ?></span><?php endif; ?>
    </a>
    <a href="pendapatan.php" class="nav-item">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Pendapatan
    </a>
    <a href="pengeluaran.php" class="nav-item">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      Pengeluaran
    </a>
    <a href="invoice.php" class="nav-item">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      Invoice
    </a>

    <div class="nav-label">Pengaturan</div>
    <a href="profil_umkm.php?edit=1" class="nav-item">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      Profil Usaha
    </a>
    <a href="#" class="nav-item">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
      Pengaturan
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= $inisial ?></div>
      <div class="flex-1" style="min-width:0">
        <div class="user-name"><?= e($nama) ?></div>
        <div class="user-role"><?= e($jenis_usaha) ?></div>
      </div>
      <a href="logout.php" class="logout-btn" title="Keluar">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      </a>
    </div>
  </div>
</aside>

<!-- ════ MAIN CONTENT ════ -->
<div class="main-content">

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="flex items-center gap-sm">
      <button class="hamburger" onclick="toggleSidebar()" aria-label="Menu">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <span class="topbar-title">Dashboard</span>
    </div>
    <div class="topbar-right">
      <button class="notif-btn" title="Notifikasi">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <div class="notif-dot"></div>
      </button>
    </div>
  </header>

  <!-- PAGE CONTENT -->
  <main class="page-content">

    <?php if ($welcome): ?>
    <!-- Welcome Banner -->
    <div class="welcome-banner" id="welcomeBanner">
      <div class="wb-icon">🎉</div>
      <div class="wb-text">
        <h3>Selamat datang, <?= e($nama) ?>!</h3>
        <p>Profil <strong><?= $nama_usaha ?></strong> berhasil disimpan. Dashboard Anda siap digunakan.</p>
      </div>
      <button class="wb-close" onclick="document.getElementById('welcomeBanner').remove()">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <?php endif; ?>

    <!-- Greeting Row -->
    <div class="greeting-row">
      <div>
        <h1>Halo, <span class="text-gradient"><?= e(explode(' ', $nama)[0]) ?></span> 👋</h1>
        <p>
          <?php
          $h=(int)date('H');
          if($h<11) echo 'Selamat pagi! Semangat memulai hari.';
          elseif($h<15) echo 'Selamat siang! Tetap produktif.';
          elseif($h<18) echo 'Selamat sore! Pantau progres bisnis Anda.';
          else echo 'Selamat malam! Rekap performa hari ini.';
          ?> &nbsp;·&nbsp; <?= date('l, d F Y') ?>
        </p>
      </div>
      <div class="flex gap-sm flex-wrap">
        <a href="pendapatan.php?aksi=tambah" class="btn btn-primary">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Catat Transaksi
        </a>
        <a href="laporan.php" class="btn btn-secondary">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Laporan
        </a>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="stat-grid stat-grid-4 mb-lg">
      <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-value"><?= rupiah($total_pendapatan) ?></div>
        <div class="stat-label">Pendapatan Bulan Ini</div>
        <div class="stat-change up">↑ Real-time dari DB</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🛒</div>
        <div class="stat-value"><?= $total_pesanan ?></div>
        <div class="stat-label">Total Invoice</div>
        <div class="stat-change <?= $total_pesanan>0?'up':'down' ?>">
          <?= $total_pesanan>0?'↑ Ada invoice':'Belum ada invoice' ?>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-value"><?= $total_produk ?></div>
        <div class="stat-label">Produk Aktif</div>
        <div class="stat-change <?= $total_produk>0?'up':'down' ?>">
          <?= $total_produk>0?'↑ Terdaftar':'Belum ada produk' ?>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><?= $laba_bulan>=0?'📈':'📉' ?></div>
        <div class="stat-value"><?= rupiah(abs($laba_bulan)) ?></div>
        <div class="stat-label">Laba Bersih Bulan Ini</div>
        <div class="stat-change <?= $laba_bulan>=0?'up':'down' ?>">
          <?= $laba_bulan>=0?'↑ Untung':'↓ Rugi' ?>
        </div>
      </div>
    </div>

    <!-- Chart + Transaksi -->
    <div class="grid-2">

      <!-- Chart -->
      <div class="card" style="padding:0">
        <div class="chart-container">
          <div class="card-head" style="margin-bottom:8px">
            <div><h3>Grafik Pendapatan</h3><div class="sub">6 bulan terakhir</div></div>
            <select onchange="void(0)">
              <option>6 Bulan</option><option>3 Bulan</option><option>1 Tahun</option>
            </select>
          </div>
          <div class="chart-bars">
            <?php foreach ($chart as $i => $c):
                $pct = $chart_max > 0 ? max(4, round(($c['value']/$chart_max)*100)) : 4;
                $is_last = $i === count($chart)-1;
            ?>
            <div class="chart-col">
              <div class="chart-bar <?= $is_last?'last':'' ?>" style="height:<?= $pct ?>%" title="<?= e($c['label']) ?>: <?= rupiah($c['value']) ?>"></div>
              <span class="chart-month"><?= e($c['label']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Transaksi Terbaru -->
      <div class="card" style="padding:0">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border)">
          <div class="card-head" style="margin:0">
            <div><h3>Transaksi Terbaru</h3><div class="sub"><?= count($txs)>0?count($txs).' transaksi terakhir':'Belum ada data' ?></div></div>
            <a href="pendapatan.php">Lihat semua →</a>
          </div>
        </div>
        <div class="tx-list">
          <?php if (empty($txs)): ?>
          <div class="empty-state">
            <div class="em-icon">📭</div>
            <p>Belum ada transaksi tercatat.</p>
            <a href="pendapatan.php?aksi=tambah" class="btn btn-primary btn-sm">+ Catat Sekarang</a>
          </div>
          <?php else: foreach ($txs as $tx): ?>
          <div class="tx-row">
            <div class="tx-icon <?= $tx['tipe']==='pendapatan'?'income':'expense' ?>">
              <?= $tx['tipe']==='pendapatan'?'💵':'💸' ?>
            </div>
            <div class="flex-1" style="min-width:0">
              <div class="tx-name truncate"><?= e($tx['deskripsi']) ?></div>
              <div class="tx-date"><?= e($tx['tanggal']) ?> · <?= e($tx['kategori']) ?></div>
            </div>
            <div class="tx-amt <?= $tx['tipe']==='pendapatan'?'amount-positive':'amount-negative' ?>">
              <?= $tx['tipe']==='pendapatan'?'+':'-' ?><?= rupiah($tx['total']) ?>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>

    <!-- Bottom Grid -->
    <div class="grid-3">

      <!-- Checklist Onboarding -->
      <div class="card">
        <div class="card-head"><div><h3>Checklist Onboarding</h3><div class="sub">Selesaikan untuk memulai</div></div></div>
        <div class="checklist">
          <div class="cl-item"><div class="cl-check done">✓</div><span class="cl-label done">Buat &amp; verifikasi akun</span></div>
          <div class="cl-item"><div class="cl-check done">✓</div><span class="cl-label done">Lengkapi profil UMKM</span></div>
          <div class="cl-item">
            <div class="cl-check <?= $total_produk>0?'done':'' ?>"><?= $total_produk>0?'✓':'' ?></div>
            <span class="cl-label <?= $total_produk>0?'done':'' ?>">Tambah produk pertama</span>
            <?php if (!$total_produk): ?><a href="produk.php?aksi=tambah" class="badge badge-blue btn-sm" style="text-decoration:none;margin-left:auto">Mulai</a><?php endif; ?>
          </div>
          <div class="cl-item">
            <div class="cl-check <?= $total_pendapatan>0?'done':'' ?>"><?= $total_pendapatan>0?'✓':'' ?></div>
            <span class="cl-label <?= $total_pendapatan>0?'done':'' ?>">Catat transaksi pertama</span>
            <?php if (!$total_pendapatan): ?><a href="pendapatan.php?aksi=tambah" class="badge badge-yellow" style="text-decoration:none;margin-left:auto">Catat</a><?php endif; ?>
          </div>
          <div class="cl-item">
            <div class="cl-check <?= $total_pesanan>0?'done':'' ?>"><?= $total_pesanan>0?'✓':'' ?></div>
            <span class="cl-label <?= $total_pesanan>0?'done':'' ?>">Buat invoice pertama</span>
            <?php if (!$total_pesanan): ?><a href="invoice.php?aksi=buat" class="badge badge-teal" style="text-decoration:none;margin-left:auto">Buat</a><?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Info Usaha -->
      <div class="card">
        <div class="card-head">
          <div><h3>Info Usaha</h3><div class="sub">Profil UMKM Anda</div></div>
          <a href="profil_umkm.php?edit=1">Edit</a>
        </div>
        <?php
        $rows=[
            ['🏢','Nama Usaha',$profil['nama_usaha']??'—'],
            ['🏷','Jenis',$profil['jenis_usaha']??'—'],
            ['📍','Kota',($profil['kota']??'—').', '.($profil['provinsi']??'')],
            ['📞','Telepon',$profil['no_telepon']??'—'],
            ['📅','Berdiri',$profil['tahun_berdiri']??'—'],
        ];
        foreach ($rows as $row): ?>
        <div class="flex items-center gap-sm mb-sm">
          <span style="font-size:15px;width:22px;flex-shrink:0"><?= $row[0] ?></span>
          <div style="flex:1;min-width:0">
            <div class="label-upper" style="font-size:9px"><?= $row[1] ?></div>
            <div style="font-size:13px;font-weight:500" class="truncate"><?= e($row[2]) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Tips -->
      <div class="card">
        <div class="card-head"><h3>💡 Tips UMKM</h3></div>
        <div class="tip-card">
          <h4>Catat setiap transaksi!</h4>
          <p>UMKM yang rutin mencatat keuangan terbukti 3× lebih mudah mendapat akses modal dari perbankan. Mulai dari transaksi kecil sekalipun.</p>
          <a href="#" class="tip-link">Pelajari lebih lanjut →</a>
        </div>
      </div>

    </div>
  </main>
</div><!-- /main-content -->
</div><!-- /app-wrapper -->

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}

// Auto-dismiss welcome banner
<?php if ($welcome): ?>
setTimeout(() => {
    const el = document.getElementById('welcomeBanner');
    if (el) { el.style.transition='opacity .5s'; el.style.opacity='0'; setTimeout(()=>el.remove(),500); }
}, 8000);
<?php endif; ?>
</script>
</body>
</html>