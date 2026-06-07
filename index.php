<?php
session_start();
require_once 'koneksi.php';

// Proteksi halaman — harus login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama    = $_SESSION['nama'] ?? 'Pengguna';

// Cek status profil UMKM (sumber kebenaran dari DB, bukan hanya session)
$profil = null;
$stmt = $koneksi->prepare("SELECT * FROM profil_umkm WHERE id_user = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $profil = $res->fetch_assoc();
    }
    $stmt->close();
}

// Sync session dengan data DB yang terbaru
if ($profil && $profil['is_complete']) {
    $_SESSION['profil_complete'] = true;
    $_SESSION['nama_usaha']      = $profil['nama_usaha'] ?? '';
}

$profil_complete = !empty($profil) && !empty($profil['is_complete']);
$nama_usaha      = htmlspecialchars($profil['nama_usaha'] ?? '');
$welcome         = isset($_GET['welcome']) && $_GET['welcome'] === '1';

// Inisial avatar
$inisial = strtoupper(mb_substr($nama, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — UMKM Next</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:    #0b1120;
            --navy2:   #0d1526;
            --panel:   #131f35;
            --surface: #111827;
            --border:  rgba(255,255,255,0.07);
            --accent:  #2563eb;
            --accent2: #3b82f6;
            --teal:    #06b6d4;
            --gold:    #f59e0b;
            --text:    #e2e8f0;
            --muted:   #64748b;
            --success: #10b981;
            --danger:  #ef4444;
            --warn:    #f59e0b;
            --sidebar-w: 240px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* ══ NOISE OVERLAY ══ */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
            pointer-events: none; z-index: 0;
        }

        /* ══════════════════════════
           SIDEBAR
        ══════════════════════════ */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--panel);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            z-index: 40;
            transition: transform .3s ease;
        }

        .sidebar-logo {
            padding: 20px 20px 18px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
        }
        .logo-mark {
            width: 38px; height: 38px;
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 14px rgba(99,51,220,0.35);
            flex-shrink: 0;
        }
        .logo-mark svg { width: 22px; height: 22px; }
        .logo-label { line-height: 1.2; }
        .logo-label strong { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 800; display: block; }
        .logo-label span { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 1.5px; }

        /* Nav groups */
        .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }
        .nav-group-label {
            font-size: 9px; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: 2px;
            padding: 0 8px; margin: 16px 0 6px;
        }
        .nav-group-label:first-child { margin-top: 0; }

        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 10px; border-radius: 9px;
            font-size: 13px; color: var(--muted);
            text-decoration: none; cursor: pointer; border: none; background: none;
            width: 100%; text-align: left;
            transition: background .15s, color .15s;
            margin-bottom: 2px;
        }
        .nav-item svg { width: 16px; height: 16px; flex-shrink: 0; }
        .nav-item:hover { background: rgba(255,255,255,0.06); color: var(--text); }
        .nav-item.active {
            background: rgba(37,99,235,0.15);
            color: var(--accent2);
            font-weight: 600;
        }
        .nav-item .badge {
            margin-left: auto; font-size: 10px; font-weight: 700;
            background: var(--accent); color: #fff;
            padding: 1px 6px; border-radius: 20px;
        }

        /* Sidebar footer (user info) */
        .sidebar-footer {
            padding: 14px 16px;
            border-top: 1px solid var(--border);
        }
        .user-card {
            display: flex; align-items: center; gap: 10px;
        }
        .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--teal));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 800; color: #fff;
            flex-shrink: 0;
        }
        .user-info { flex: 1; min-width: 0; }
        .user-info .u-name { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-info .u-role { font-size: 11px; color: var(--muted); }
        .logout-btn {
            width: 28px; height: 28px; border-radius: 7px;
            background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.15);
            display: flex; align-items: center; justify-content: center;
            color: #ef4444; cursor: pointer; flex-shrink: 0;
            transition: background .2s;
        }
        .logout-btn:hover { background: rgba(239,68,68,0.18); }
        .logout-btn svg { width: 14px; height: 14px; }

        /* ══════════════════════════
           MAIN CONTENT
        ══════════════════════════ */
        .main-wrap {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex; flex-direction: column;
            min-height: 100vh;
            position: relative; z-index: 1;
        }

        /* Top bar (mobile hamburger + page title) */
        .topbar {
            position: sticky; top: 0; z-index: 30;
            background: rgba(11,17,32,0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            height: 58px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-left { display: flex; align-items: center; gap: 14px; }
        .hamburger {
            display: none; background: none; border: none; cursor: pointer;
            color: var(--text); padding: 4px;
        }
        .hamburger svg { width: 20px; height: 20px; }
        .page-title { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 800; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .notif-btn {
            width: 34px; height: 34px; border-radius: 9px;
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            color: var(--muted); cursor: pointer;
            position: relative;
            transition: background .2s;
        }
        .notif-btn:hover { background: rgba(255,255,255,0.09); color: var(--text); }
        .notif-btn svg { width: 16px; height: 16px; }
        .notif-dot {
            position: absolute; top: 6px; right: 6px;
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--danger);
            border: 1.5px solid var(--navy);
        }

        /* ══ CONTENT ══ */
        .content { padding: 28px; }

        /* ══════════════════════════
           WELCOME BANNER (welcome=1)
        ══════════════════════════ */
        .welcome-banner {
            background: linear-gradient(135deg, rgba(37,99,235,0.18) 0%, rgba(6,182,212,0.12) 100%);
            border: 1px solid rgba(37,99,235,0.3);
            border-radius: 16px;
            padding: 20px 24px;
            display: flex; align-items: center; gap: 16px;
            margin-bottom: 28px;
            animation: slideDown .4s ease;
        }
        @keyframes slideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:none; } }
        .wb-icon {
            font-size: 36px; flex-shrink: 0;
            animation: wave 1.5s ease-in-out 0.5s;
        }
        @keyframes wave {
            0%,100%{transform:rotate(0)} 25%{transform:rotate(20deg)} 75%{transform:rotate(-10deg)}
        }
        .wb-text { flex: 1; }
        .wb-text h2 { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 800; margin-bottom: 4px; }
        .wb-text p { font-size: 13px; color: var(--muted); }
        .wb-close {
            background: none; border: none; cursor: pointer;
            color: var(--muted); padding: 4px;
        }
        .wb-close:hover { color: var(--text); }
        .wb-close svg { width: 16px; height: 16px; }

        /* ══════════════════════════
           INCOMPLETE PROFILE ALERT
        ══════════════════════════ */
        .profile-alert {
            background: rgba(245,158,11,0.1);
            border: 1px solid rgba(245,158,11,0.25);
            border-radius: 14px;
            padding: 16px 20px;
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 28px;
        }
        .pa-icon { font-size: 22px; flex-shrink: 0; }
        .pa-text { flex: 1; }
        .pa-text strong { font-size: 14px; font-weight: 600; color: #fcd34d; display: block; margin-bottom: 2px; }
        .pa-text span { font-size: 12px; color: var(--muted); }
        .pa-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 8px; border: none;
            background: var(--gold); color: #000;
            font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700;
            cursor: pointer; text-decoration: none;
            transition: transform .15s, box-shadow .2s;
            flex-shrink: 0;
        }
        .pa-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(245,158,11,0.4); }
        .pa-skip {
            font-size: 12px; color: var(--muted); text-decoration: none; flex-shrink: 0;
            margin-left: 8px;
        }
        .pa-skip:hover { color: var(--text); }

        /* ══════════════════════════
           GREETING ROW
        ══════════════════════════ */
        .greeting-row {
            display: flex; align-items: flex-end; justify-content: space-between;
            margin-bottom: 28px; flex-wrap: wrap; gap: 16px;
        }
        .greeting-row h1 {
            font-family: 'Syne', sans-serif;
            font-size: 28px; font-weight: 800; letter-spacing: -1px;
        }
        .greeting-row h1 span {
            background: linear-gradient(135deg, #60a5fa 0%, #06b6d4 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .greeting-row p { font-size: 13px; color: var(--muted); margin-top: 4px; }

        /* Quick action buttons */
        .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .qa-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 16px; border-radius: 9px; border: none;
            font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500;
            cursor: pointer; text-decoration: none;
            transition: transform .15s, box-shadow .2s, background .2s;
        }
        .qa-btn svg { width: 15px; height: 15px; }
        .qa-primary {
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            color: #fff; box-shadow: 0 4px 16px rgba(37,99,235,0.35);
        }
        .qa-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(37,99,235,.5); }
        .qa-ghost {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border); color: var(--text);
        }
        .qa-ghost:hover { background: rgba(255,255,255,0.09); transform: translateY(-1px); }

        /* ══════════════════════════
           STATS GRID
        ══════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            position: relative; overflow: hidden;
            transition: border-color .2s, transform .2s;
        }
        .stat-card:hover { border-color: rgba(255,255,255,0.14); transform: translateY(-2px); }
        .stat-card::before {
            content: ''; position: absolute;
            top: -30px; right: -20px;
            width: 80px; height: 80px; border-radius: 50%;
            opacity: 0.12;
        }
        .stat-card.blue::before   { background: var(--accent2); }
        .stat-card.teal::before   { background: var(--teal); }
        .stat-card.gold::before   { background: var(--gold); }
        .stat-card.green::before  { background: var(--success); }

        .stat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
        .stat-icon {
            width: 40px; height: 40px; border-radius: 11px;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .stat-icon.blue  { background: rgba(59,130,246,0.15); }
        .stat-icon.teal  { background: rgba(6,182,212,0.15); }
        .stat-icon.gold  { background: rgba(245,158,11,0.15); }
        .stat-icon.green { background: rgba(16,185,129,0.15); }

        .stat-delta {
            font-size: 11px; padding: 3px 8px; border-radius: 20px;
            font-weight: 600;
        }
        .stat-delta.up   { background: rgba(16,185,129,0.15); color: var(--success); }
        .stat-delta.down { background: rgba(239,68,68,0.12); color: var(--danger); }
        .stat-delta.flat { background: rgba(100,116,139,0.15); color: var(--muted); }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 26px; font-weight: 800; letter-spacing: -1px;
            margin-bottom: 4px;
        }
        .stat-label { font-size: 12px; color: var(--muted); }

        /* ══════════════════════════
           MAIN GRID (chart + list)
        ══════════════════════════ */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 20px;
            margin-bottom: 28px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        .card-head {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-head h3 { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; }
        .card-head .ch-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .card-body-pad { padding: 22px; }

        /* Chart placeholder */
        .chart-area {
            padding: 22px;
            height: 240px;
            position: relative;
        }
        .chart-bars {
            display: flex; align-items: flex-end; gap: 10px;
            height: 100%; padding-bottom: 28px;
        }
        .chart-bar-wrap {
            flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px;
        }
        .chart-bar {
            width: 100%; border-radius: 6px 6px 0 0;
            background: linear-gradient(180deg, var(--accent2) 0%, rgba(59,130,246,0.3) 100%);
            transition: filter .2s;
            cursor: pointer;
            min-height: 4px;
        }
        .chart-bar:hover { filter: brightness(1.3); }
        .chart-bar.teal-bar {
            background: linear-gradient(180deg, var(--teal) 0%, rgba(6,182,212,0.3) 100%);
        }
        .chart-month { font-size: 10px; color: var(--muted); }

        /* Chart y-axis labels */
        .chart-y {
            position: absolute; left: 22px; top: 22px; bottom: 50px;
            display: flex; flex-direction: column; justify-content: space-between;
            pointer-events: none;
        }
        .chart-y span { font-size: 9px; color: var(--muted); }

        /* Transaction list */
        .tx-list { padding: 8px 0; }
        .tx-item {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 22px;
            transition: background .15s;
            cursor: pointer;
        }
        .tx-item:hover { background: rgba(255,255,255,0.03); }
        .tx-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .tx-info { flex: 1; min-width: 0; }
        .tx-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tx-date { font-size: 11px; color: var(--muted); }
        .tx-amt { font-size: 13px; font-weight: 600; flex-shrink: 0; }
        .tx-amt.pos { color: var(--success); }
        .tx-amt.neg { color: var(--danger); }

        .view-all {
            display: block; text-align: center;
            padding: 14px;
            font-size: 12px; color: var(--accent2);
            text-decoration: none;
            border-top: 1px solid var(--border);
            transition: background .15s;
        }
        .view-all:hover { background: rgba(255,255,255,0.03); }

        /* ══════════════════════════
           BOTTOM GRID
        ══════════════════════════ */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }

        /* Todo / checklist */
        .todo-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }
        .todo-item:last-child { border-bottom: none; }
        .todo-check {
            width: 18px; height: 18px; border-radius: 5px;
            border: 1.5px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; cursor: pointer;
            transition: all .2s;
        }
        .todo-check.checked { background: var(--success); border-color: var(--success); }
        .todo-check.checked::after { content: '✓'; font-size: 10px; color: #fff; }
        .todo-label { font-size: 13px; flex: 1; }
        .todo-label.done { text-decoration: line-through; color: var(--muted); }
        .todo-badge { font-size: 10px; padding: 2px 7px; border-radius: 20px; font-weight: 600; }
        .tb-gold  { background: rgba(245,158,11,0.15); color: var(--gold); }
        .tb-teal  { background: rgba(6,182,212,0.15); color: var(--teal); }
        .tb-blue  { background: rgba(59,130,246,0.15); color: var(--accent2); }
        .tb-green { background: rgba(16,185,129,0.15); color: var(--success); }

        /* Info / tip card */
        .tip-card {
            background: linear-gradient(135deg, rgba(99,51,220,0.12) 0%, rgba(6,182,212,0.08) 100%);
            border: 1px solid rgba(99,51,220,0.2);
            border-radius: 14px;
            padding: 20px;
        }
        .tip-card h4 { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; margin-bottom: 8px; }
        .tip-card p { font-size: 12px; color: var(--muted); line-height: 1.7; margin-bottom: 14px; }
        .tip-link {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; color: var(--accent2); text-decoration: none; font-weight: 600;
        }
        .tip-link svg { width: 13px; height: 13px; }

        /* ══ RESPONSIVE ══ */
        @media (max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .main-grid { grid-template-columns: 1fr; }
            .bottom-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 760px) {
            :root { --sidebar-w: 0px; }
            .sidebar { transform: translateX(-240px); --sidebar-w: 240px; }
            .sidebar.open { transform: translateX(0); }
            .main-wrap { margin-left: 0; }
            .hamburger { display: flex; }
            .content { padding: 16px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .bottom-grid { grid-template-columns: 1fr; }
            .greeting-row h1 { font-size: 22px; }
            .topbar { padding: 0 16px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }

        /* Sidebar overlay (mobile) */
        .sidebar-overlay {
            display: none; position: fixed; inset: 0; z-index: 39;
            background: rgba(0,0,0,0.5);
        }
        .sidebar-overlay.active { display: block; }
    </style>
</head>
<body>

<!-- ════ SIDEBAR ════ -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<aside class="sidebar" id="sidebar">

    <div class="sidebar-logo">
        <div class="logo-mark">
            <svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 4 L2 16 Q2 22 8 22 L12 22 Q14 22 14 19" stroke="white" stroke-width="2.8" stroke-linecap="round" fill="none"/>
                <path d="M16 22 L16 8 L24 22 L24 8" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M20 6 L26 2 M23.5 2 L26 2 L26 4.5" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="logo-label">
            <strong>UMKM Next</strong>
            <span>Management System</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group-label">Utama</div>
        <a href="index.php" class="nav-item active">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="#" class="nav-item">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Laporan Keuangan
        </a>
        <a href="#" class="nav-item">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            Produk & Stok
            <span class="badge">3</span>
        </a>
        <a href="#" class="nav-item">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Pelanggan
        </a>
        <a href="#" class="nav-item">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Pesanan
        </a>

        <div class="nav-group-label">Pengaturan</div>
        <a href="profil_umkm.php" class="nav-item">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Profil Usaha
            <?php if (!$profil_complete): ?>
            <span class="badge" style="background:var(--warn);color:#000">!</span>
            <?php endif; ?>
        </a>
        <a href="#" class="nav-item">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
            Pengaturan
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="avatar"><?= $inisial ?></div>
            <div class="user-info">
                <div class="u-name"><?= htmlspecialchars($nama) ?></div>
                <div class="u-role"><?= $profil_complete ? htmlspecialchars($profil['jenis_usaha'] ?? 'UMKM') : 'Pemilik UMKM' ?></div>
            </div>
            <a href="logout.php" class="logout-btn" title="Keluar">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </a>
        </div>
    </div>
</aside>

<!-- ════ MAIN ════ -->
<div class="main-wrap">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="page-title">Dashboard</span>
        </div>
        <div class="topbar-right">
            <button class="notif-btn" title="Notifikasi">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <div class="notif-dot"></div>
            </button>
        </div>
    </header>

    <!-- CONTENT -->
    <div class="content">

        <?php if ($welcome): ?>
        <!-- WELCOME BANNER -->
        <div class="welcome-banner" id="welcomeBanner">
            <div class="wb-icon">🎉</div>
            <div class="wb-text">
                <h2>Selamat datang, <?= htmlspecialchars($nama) ?>!</h2>
                <p>Profil UMKM <strong><?= $nama_usaha ?></strong> berhasil disimpan. Dashboard Anda sudah siap digunakan.</p>
            </div>
            <button class="wb-close" onclick="document.getElementById('welcomeBanner').remove()" title="Tutup">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <?php endif; ?>

        <?php if (!$profil_complete): ?>
        <!-- PROFILE INCOMPLETE ALERT -->
        <div class="profile-alert">
            <div class="pa-icon">⚠️</div>
            <div class="pa-text">
                <strong>Profil UMKM belum dilengkapi</strong>
                <span>Lengkapi profil usaha Anda agar fitur-fitur dashboard dapat bekerja secara optimal.</span>
            </div>
            <a href="profil_umkm.php" class="pa-btn">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Lengkapi Sekarang
            </a>
            <a href="#" class="pa-skip" onclick="this.closest('.profile-alert').remove(); return false;">Nanti saja</a>
        </div>
        <?php endif; ?>

        <!-- GREETING ROW -->
        <div class="greeting-row">
            <div>
                <h1>Halo, <span><?= htmlspecialchars(explode(' ', $nama)[0]) ?></span> 👋</h1>
                <p>
                    <?php
                    $jam = (int)date('H');
                    if ($jam < 11) echo "Selamat pagi! Semangat memulai hari.";
                    elseif ($jam < 15) echo "Selamat siang! Tetap produktif ya.";
                    elseif ($jam < 18) echo "Selamat sore! Pantau terus progres bisnis Anda.";
                    else echo "Selamat malam! Rekap performa hari ini.";
                    ?>
                    &nbsp;·&nbsp; <?= date('l, d F Y') ?>
                </p>
            </div>
            <div class="quick-actions">
                <a href="#" class="qa-btn qa-primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Catat Transaksi
                </a>
                <a href="#" class="qa-btn qa-ghost">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Laporan
                </a>
            </div>
        </div>

        <!-- STATS GRID -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-top">
                    <div class="stat-icon blue">💰</div>
                    <span class="stat-delta up">+12%</span>
                </div>
                <div class="stat-value">Rp 0</div>
                <div class="stat-label">Pendapatan Bulan Ini</div>
            </div>
            <div class="stat-card teal">
                <div class="stat-top">
                    <div class="stat-icon teal">🛒</div>
                    <span class="stat-delta flat">–</span>
                </div>
                <div class="stat-value">0</div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card gold">
                <div class="stat-top">
                    <div class="stat-icon gold">📦</div>
                    <span class="stat-delta down">–</span>
                </div>
                <div class="stat-value">0</div>
                <div class="stat-label">Produk Terdaftar</div>
            </div>
            <div class="stat-card green">
                <div class="stat-top">
                    <div class="stat-icon green">👥</div>
                    <span class="stat-delta flat">–</span>
                </div>
                <div class="stat-value">0</div>
                <div class="stat-label">Pelanggan</div>
            </div>
        </div>

        <!-- MAIN GRID: Chart + Transaksi -->
        <div class="main-grid">

            <!-- Chart -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3>Grafik Pendapatan</h3>
                        <div class="ch-sub">6 bulan terakhir</div>
                    </div>
                    <select style="background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:7px;color:var(--muted);font-size:12px;padding:5px 10px;outline:none;">
                        <option>6 Bulan</option>
                        <option>3 Bulan</option>
                        <option>1 Tahun</option>
                    </select>
                </div>
                <div class="chart-area">
                    <!-- Dummy chart bars — ganti dengan data nyata dari DB -->
                    <div class="chart-bars">
                        <?php
                        $bulan = ['Jan','Feb','Mar','Apr','Mei','Jun'];
                        $heights = [30, 55, 42, 70, 60, 80]; // persen — ganti dengan data DB
                        foreach ($bulan as $i => $b):
                            $h = $heights[$i];
                            $is_last = ($i === count($bulan) - 1);
                        ?>
                        <div class="chart-bar-wrap">
                            <div class="chart-bar <?= $is_last ? 'teal-bar' : '' ?>" style="height:<?= $h ?>%"
                                 title="<?= $b ?>: Rp 0 (demo)"></div>
                            <span class="chart-month"><?= $b ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Transaksi terbaru -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3>Transaksi Terbaru</h3>
                        <div class="ch-sub">Belum ada data</div>
                    </div>
                </div>
                <div class="tx-list">
                    <!-- Kosong state -->
                    <div style="padding:40px 22px;text-align:center;color:var(--muted);">
                        <div style="font-size:36px;margin-bottom:12px">📭</div>
                        <div style="font-size:13px;">Belum ada transaksi tercatat.</div>
                        <a href="#" style="font-size:12px;color:var(--accent2);text-decoration:none;display:inline-block;margin-top:8px;">+ Catat transaksi pertama</a>
                    </div>
                </div>
                <a href="#" class="view-all">Lihat semua transaksi →</a>
            </div>
        </div>

        <!-- BOTTOM GRID -->
        <div class="bottom-grid">

            <!-- Checklist Onboarding -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3>Checklist Onboarding</h3>
                        <div class="ch-sub">Selesaikan untuk memulai</div>
                    </div>
                </div>
                <div class="card-body-pad">
                    <div class="todo-item">
                        <div class="todo-check checked"></div>
                        <span class="todo-label done">Buat akun & login</span>
                        <span class="todo-badge tb-green">Selesai</span>
                    </div>
                    <div class="todo-item">
                        <div class="todo-check <?= $profil_complete ? 'checked' : '' ?>"></div>
                        <span class="todo-label <?= $profil_complete ? 'done' : '' ?>">Lengkapi profil UMKM</span>
                        <?php if ($profil_complete): ?>
                        <span class="todo-badge tb-green">Selesai</span>
                        <?php else: ?>
                        <a href="profil_umkm.php" class="todo-badge tb-gold" style="text-decoration:none;">Mulai</a>
                        <?php endif; ?>
                    </div>
                    <div class="todo-item">
                        <div class="todo-check"></div>
                        <span class="todo-label">Tambah produk pertama</span>
                        <span class="todo-badge tb-teal">Segera</span>
                    </div>
                    <div class="todo-item">
                        <div class="todo-check"></div>
                        <span class="todo-label">Catat transaksi</span>
                        <span class="todo-badge tb-blue">Berikutnya</span>
                    </div>
                    <div class="todo-item">
                        <div class="todo-check"></div>
                        <span class="todo-label">Lihat laporan pertama</span>
                        <span class="todo-badge tb-blue">Berikutnya</span>
                    </div>
                </div>
            </div>

            <!-- Info Usaha -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3>Info Usaha</h3>
                        <div class="ch-sub">Ringkasan profil UMKM</div>
                    </div>
                    <a href="profil_umkm.php" style="font-size:12px;color:var(--accent2);text-decoration:none;">Edit</a>
                </div>
                <div class="card-body-pad">
                    <?php if ($profil_complete): ?>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <?php
                        $info_rows = [
                            ['🏢', 'Nama Usaha', $profil['nama_usaha'] ?? '—'],
                            ['🏷', 'Jenis Usaha', $profil['jenis_usaha'] ?? '—'],
                            ['📍', 'Kota', ($profil['kota'] ?? '—') . ', ' . ($profil['provinsi'] ?? '')],
                            ['📞', 'Telepon', $profil['no_telepon'] ?? '—'],
                            ['📅', 'Berdiri', $profil['tahun_berdiri'] ?? '—'],
                        ];
                        foreach ($info_rows as $r): ?>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:15px;flex-shrink:0;width:22px"><?= $r[0] ?></span>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:1px"><?= $r[1] ?></div>
                                <div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($r[2]) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div style="text-align:center;padding:24px 0;color:var(--muted);">
                        <div style="font-size:32px;margin-bottom:10px">🏢</div>
                        <div style="font-size:13px;margin-bottom:14px;">Profil usaha belum diisi.</div>
                        <a href="profil_umkm.php" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;background:var(--accent);color:#fff;text-decoration:none;font-size:12px;font-weight:600;">
                            Lengkapi Profil
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tips -->
            <div class="card">
                <div class="card-head">
                    <h3>💡 Tips UMKM</h3>
                </div>
                <div class="card-body-pad">
                    <div class="tip-card">
                        <h4>Catat setiap transaksi!</h4>
                        <p>UMKM yang rutin mencatat keuangan terbukti 3× lebih mudah mendapatkan akses modal dari perbankan. Mulai dari transaksi kecil sekalipun.</p>
                        <a href="#" class="tip-link">
                            Pelajari lebih lanjut
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>

        </div><!-- /bottom-grid -->
    </div><!-- /content -->
</div><!-- /main-wrap -->

<script>
// Sidebar toggle (mobile)
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('active');
}

// Auto-remove welcome banner setelah 8 detik
<?php if ($welcome): ?>
setTimeout(() => {
    const el = document.getElementById('welcomeBanner');
    if (el) { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }
}, 8000);
<?php endif; ?>
</script>
</body>
</html>