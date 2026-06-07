<?php
session_start();
require_once 'koneksi.php';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        // Deteksi nama kolom username otomatis dari tabel users
        $col_username = 'username'; // default
        $col_result = $koneksi->query("SHOW COLUMNS FROM users");
        if ($col_result) {
            $columns = [];
            while ($col = $col_result->fetch_assoc()) {
                $columns[] = strtolower($col['Field']);
            }
            foreach (['username','user','nama_user','email','name','nama'] as $candidate) {
                if (in_array($candidate, $columns)) {
                    $col_username = $candidate;
                    break;
                }
            }
        }

        $stmt = $koneksi->prepare("SELECT * FROM users WHERE `{$col_username}` = ? LIMIT 1");
        if (!$stmt) {
            $error = 'Kesalahan database: ' . $koneksi->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $stored_pass = $user['password'] ?? $user['pass'] ?? $user['passwd'] ?? '';

                // Support password_hash() dan plain text
                $valid = password_verify($password, $stored_pass) || ($password === $stored_pass);

                if ($valid) {
                    $_SESSION['user']    = $user[$col_username];
                    $_SESSION['user_id'] = $user['id'] ?? 0;
                    $_SESSION['nama']    = $user['nama'] ?? $user['name'] ?? $user[$col_username];
                    header("Location: index.php");
                    exit();
                } else {
                    $error = 'Password yang Anda masukkan salah.';
                }
            } else {
                $error = 'Username tidak ditemukan.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — UMKM Next</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:    #0b1120;
            --navy2:   #111827;
            --panel:   #131f35;
            --border:  rgba(255,255,255,0.07);
            --accent:  #2563eb;
            --accent2: #3b82f6;
            --teal:    #06b6d4;
            --gold:    #f59e0b;
            --text:    #e2e8f0;
            --muted:   #64748b;
            --danger:  #ef4444;
            --success: #10b981;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* ── NOISE OVERLAY ── */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none; z-index: 0;
        }

        /* ══════════════════════════════════════
           LEFT PANEL — Login Form
        ══════════════════════════════════════ */
        .left {
            position: relative; z-index: 1;
            width: 440px; min-width: 340px;
            background: var(--panel);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            justify-content: space-between;
            padding: 40px 44px;
            flex-shrink: 0;
        }

        /* Logo */
        .logo {
            display: flex; align-items: center; gap: 12px;
        }
        .logo-mark {
            width: 44px; height: 44px;
            background: var(--accent);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            position: relative;
            box-shadow: 0 0 20px rgba(37,99,235,0.5);
        }
        .logo-mark svg { width: 26px; height: 26px; }
        .logo-text { line-height: 1; }
        .logo-text span:first-child {
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 800;
            letter-spacing: -0.5px;
            display: block;
        }
        .logo-text span:last-child {
            font-size: 10px; color: var(--muted);
            text-transform: uppercase; letter-spacing: 2px;
        }

        /* Form area */
        .form-area { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 20px 0; }

        .form-area h1 {
            font-family: 'Syne', sans-serif;
            font-size: 30px; font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 6px;
        }
        .form-area p.sub {
            color: var(--muted); font-size: 14px; margin-bottom: 36px;
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
            animation: fadeIn .3s ease;
        }
        .alert.error   { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; }
        .alert.success { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.25); color: #6ee7b7; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

        /* Input group */
        .field { margin-bottom: 18px; }
        .field label {
            display: block; font-size: 12px; font-weight: 500;
            color: var(--muted); text-transform: uppercase;
            letter-spacing: 1.2px; margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap svg.icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            width: 16px; height: 16px; color: var(--muted);
            pointer-events: none;
        }
        .input-wrap input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 16px 13px 42px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; color: var(--text);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .input-wrap input::placeholder { color: #374151; }
        .input-wrap input:focus {
            border-color: var(--accent2);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .toggle-pw {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--muted); display: flex; align-items: center;
        }
        .toggle-pw:hover { color: var(--text); }

        /* Options row */
        .opts {
            display: flex; justify-content: space-between; align-items: center;
            margin: 4px 0 24px;
        }
        .checkbox-wrap {
            display: flex; align-items: center; gap: 8px; cursor: pointer;
        }
        .checkbox-wrap input[type="checkbox"] { display: none; }
        .custom-cb {
            width: 16px; height: 16px;
            border: 1.5px solid var(--border);
            border-radius: 4px; background: transparent;
            display: flex; align-items: center; justify-content: center;
            transition: all .2s;
        }
        .checkbox-wrap input:checked + .custom-cb {
            background: var(--accent); border-color: var(--accent);
        }
        .checkbox-wrap input:checked + .custom-cb::after {
            content: '✓'; font-size: 10px; color: #fff;
        }
        .checkbox-wrap span { font-size: 13px; color: var(--muted); }
        .link { font-size: 13px; color: var(--accent2); text-decoration: none; }
        .link:hover { text-decoration: underline; }

        /* Submit */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
            border: none; border-radius: 10px;
            padding: 14px; font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 700; color: #fff;
            cursor: pointer; letter-spacing: 0.3px;
            transition: transform .15s, box-shadow .2s, opacity .2s;
            box-shadow: 0 4px 24px rgba(37,99,235,0.4);
            position: relative; overflow: hidden;
        }
        .btn-login::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.1));
            opacity: 0; transition: opacity .2s;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 6px 32px rgba(37,99,235,0.55); }
        .btn-login:hover::before { opacity: 1; }
        .btn-login:active { transform: translateY(0); }
        .btn-login.loading { opacity: .7; pointer-events: none; }

        /* Divider */
        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 24px 0; color: var(--muted); font-size: 12px;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        .register-row {
            text-align: center; font-size: 13px; color: var(--muted);
        }

        /* Footer */
        .left-footer {
            font-size: 11px; color: var(--muted);
            text-align: center; line-height: 1.8;
        }

        /* ══════════════════════════════════════
           RIGHT PANEL — Visual / Branding
        ══════════════════════════════════════ */
        .right {
            flex: 1; position: relative; overflow: hidden;
            background: var(--navy2);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
        }

        /* Animated blobs */
        .blob {
            position: absolute; border-radius: 50%;
            filter: blur(80px); opacity: 0.18;
            animation: drift 12s ease-in-out infinite alternate;
        }
        .blob-1 { width: 500px; height: 500px; background: #1e40af; top: -100px; right: -80px; animation-delay: 0s; }
        .blob-2 { width: 400px; height: 400px; background: #0e7490; bottom: -80px; left: 40px; animation-delay: -4s; }
        .blob-3 { width: 300px; height: 300px; background: #7c3aed; top: 40%; right: 20%; animation-delay: -8s; }
        @keyframes drift {
            0%   { transform: translate(0,0) scale(1); }
            100% { transform: translate(30px, -30px) scale(1.1); }
        }

        /* Grid lines */
        .grid-lines {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        /* Content */
        .right-content {
            position: relative; z-index: 2;
            text-align: center; padding: 40px;
            max-width: 560px;
        }

        /* Big logo mark */
        .hero-logo {
            margin: 0 auto 32px;
            position: relative; display: inline-block;
        }
        .hero-logo-bg {
            width: 100px; height: 100px;
            background: linear-gradient(135deg, var(--accent) 0%, #0e7490 100%);
            border-radius: 24px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 60px rgba(37,99,235,0.4), 0 0 120px rgba(6,182,212,0.2);
            animation: pulse-glow 3s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%,100% { box-shadow: 0 0 40px rgba(37,99,235,.4), 0 0 80px rgba(6,182,212,.15); }
            50%      { box-shadow: 0 0 70px rgba(37,99,235,.6), 0 0 140px rgba(6,182,212,.3); }
        }
        .hero-logo-bg svg { width: 56px; height: 56px; }

        .hero-title {
            font-family: 'Syne', sans-serif;
            font-size: 42px; font-weight: 800;
            letter-spacing: -2px; line-height: 1;
            margin-bottom: 10px;
        }
        .hero-title span { color: var(--accent2); }
        .hero-sub {
            font-size: 15px; color: var(--muted); margin-bottom: 48px; line-height: 1.6;
        }

        /* Stats cards */
        .stats-row {
            display: flex; gap: 16px; justify-content: center; margin-bottom: 36px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 24px;
            text-align: left;
            backdrop-filter: blur(10px);
            min-width: 130px;
            transition: transform .3s, border-color .3s;
        }
        .stat-card:hover { transform: translateY(-4px); border-color: rgba(59,130,246,.3); }
        .stat-icon { font-size: 22px; margin-bottom: 8px; }
        .stat-val {
            font-family: 'Syne', sans-serif;
            font-size: 26px; font-weight: 800;
            line-height: 1; margin-bottom: 4px;
        }
        .stat-val.blue  { color: var(--accent2); }
        .stat-val.teal  { color: var(--teal); }
        .stat-val.gold  { color: var(--gold); }
        .stat-val.green { color: var(--success); }
        .stat-lbl { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }

        /* Mini chart */
        .chart-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            padding: 20px 24px 12px;
            backdrop-filter: blur(10px);
        }
        .chart-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 10px;
        }
        .chart-header span:first-child { font-size: 13px; font-weight: 500; color: var(--text); }
        .badge-up {
            background: rgba(16,185,129,.15);
            color: var(--success);
            font-size: 11px; padding: 3px 10px;
            border-radius: 20px; border: 1px solid rgba(16,185,129,.25);
        }
        .chart-legend {
            display: flex; gap: 16px; margin-bottom: 12px;
        }
        .legend-item { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--muted); }
        .legend-dot  { width: 8px; height: 8px; border-radius: 2px; }
        .bars {
            display: flex; align-items: flex-end; gap: 4px;
            height: 80px;
            padding-bottom: 0;
        }
        .bar-group { flex: 1; display: flex; gap: 2px; align-items: flex-end; }
        .bar {
            flex: 1; border-radius: 3px 3px 0 0;
            background: linear-gradient(180deg, var(--accent2) 0%, #1e40af 100%);
            animation: grow .7s cubic-bezier(.34,1.3,.64,1) both;
            transform-origin: bottom;
            min-height: 6px;
        }
        .bar.teal { background: linear-gradient(180deg, var(--teal) 0%, #0e7490 100%); }
        @keyframes grow { from { transform: scaleY(0); } to { transform: scaleY(1); } }
        .bars-labels {
            display: flex; gap: 4px; margin-top: 6px;
        }
        .bar-lbl { flex: 1; font-size: 9px; color: var(--muted); text-align: center; }

        /* Feature pills */
        .features {
            display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 28px;
        }
        .feat-pill {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 12px; color: var(--muted);
            display: flex; align-items: center; gap: 6px;
        }
        .feat-pill .dot {
            width: 6px; height: 6px; border-radius: 50%;
        }

        /* Responsive */
        @media (max-width: 860px) {
            .right { display: none; }
            .left  { width: 100%; }
        }
    </style>
</head>
<body>

<!-- ════ LEFT — LOGIN FORM ════ -->
<div class="left">
    <!-- Logo -->
    <div class="logo">
        <div class="logo-mark">
            <svg viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- U shape -->
                <path d="M3 4 L3 16 Q3 22 9 22 L17 22 Q23 22 23 16 L23 4" stroke="white" stroke-width="3" stroke-linecap="round" fill="none"/>
                <!-- Arrow up-right -->
                <path d="M14 10 L20 4 M17 4 L20 4 L20 7" stroke="#f59e0b" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="logo-text">
            <span>UMKM Next</span>
            <span>Management System</span>
        </div>
    </div>

    <!-- Form -->
    <div class="form-area">
        <h1>Selamat Datang 👋</h1>
        <p class="sub">Masuk ke akun Anda untuk mengelola bisnis UMKM Anda</p>

        <?php if ($error): ?>
        <div class="alert error">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert success">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="field">
                <label>Username</label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <input type="text" name="username" placeholder="Masukkan username Anda"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
                </div>
            </div>

            <div class="field">
                <label>Password</label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <input type="password" name="password" id="passwordInput" placeholder="Masukkan password Anda" required autocomplete="current-password">
                    <button type="button" class="toggle-pw" onclick="togglePassword()" title="Tampilkan password">
                        <svg id="eyeIcon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <div class="opts">
                <label class="checkbox-wrap">
                    <input type="checkbox" name="remember">
                    <div class="custom-cb"></div>
                    <span>Ingat saya</span>
                </label>
                <a href="#" class="link" onclick="alert('Hubungi admin untuk reset password.');return false;">Lupa password?</a>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">Masuk ke Dashboard</button>
        </form>

        <div class="divider">atau</div>
        <p class="register-row">Belum punya akun? <a href="register.php" class="link">Daftar sekarang</a></p>
    </div>

    <!-- Footer -->
    <div class="left-footer">
        &copy; <?= date('Y') ?> UMKM Next Management System<br>
        Dibuat untuk kemajuan UMKM Indonesia 🇮🇩
    </div>
</div>

<!-- ════ RIGHT — BRANDING / VISUAL ════ -->
<div class="right">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="grid-lines"></div>

    <div class="right-content">
        <!-- Hero Logo -->
        <div class="hero-logo">
            <div class="hero-logo-bg">
                <svg viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Big U -->
                    <path d="M8 10 L8 34 Q8 46 20 46 L36 46 Q48 46 48 34 L48 10" stroke="white" stroke-width="5.5" stroke-linecap="round" fill="none"/>
                    <!-- Arrow -->
                    <path d="M30 22 L44 8 M37 8 L44 8 L44 15" stroke="#f59e0b" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <h2 class="hero-title">UMKM <span>Next</span></h2>
        <p class="hero-sub">Platform manajemen bisnis modern untuk<br>UMKM Indonesia yang lebih maju & terorganisir.</p>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-val blue">Rp&nbsp;0</div>
                <div class="stat-lbl">Total Pendapatan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📉</div>
                <div class="stat-val teal">Rp&nbsp;0</div>
                <div class="stat-lbl">Total Pengeluaran</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-val gold">Laba</div>
                <div class="stat-lbl">Laporan Grafik</div>
            </div>
        </div>

        <!-- Mini bar chart -->
        <div class="chart-box">
            <div class="chart-header">
                <span>Visualisasi Arus Kas</span>
                <span class="badge-up">↑ Grafik Real-time</span>
            </div>
            <div class="chart-legend">
                <div class="legend-item"><div class="legend-dot" style="background:var(--accent2)"></div>Pendapatan</div>
                <div class="legend-item"><div class="legend-dot" style="background:var(--teal)"></div>Pengeluaran</div>
            </div>
            <div class="bars">
                <div class="bar-group"><div class="bar" style="height:42px;animation-delay:.05s"></div><div class="bar teal" style="height:28px;animation-delay:.08s"></div></div>
                <div class="bar-group"><div class="bar" style="height:56px;animation-delay:.1s"></div><div class="bar teal" style="height:36px;animation-delay:.13s"></div></div>
                <div class="bar-group"><div class="bar" style="height:48px;animation-delay:.15s"></div><div class="bar teal" style="height:44px;animation-delay:.18s"></div></div>
                <div class="bar-group"><div class="bar" style="height:68px;animation-delay:.2s"></div><div class="bar teal" style="height:38px;animation-delay:.23s"></div></div>
                <div class="bar-group"><div class="bar" style="height:52px;animation-delay:.25s"></div><div class="bar teal" style="height:30px;animation-delay:.28s"></div></div>
                <div class="bar-group"><div class="bar" style="height:80px;animation-delay:.3s"></div><div class="bar teal" style="height:50px;animation-delay:.33s"></div></div>
            </div>
            <div class="bars-labels">
                <span class="bar-lbl">Jan</span>
                <span class="bar-lbl">Feb</span>
                <span class="bar-lbl">Mar</span>
                <span class="bar-lbl">Apr</span>
                <span class="bar-lbl">Mei</span>
                <span class="bar-lbl">Jun</span>
            </div>
        </div>

        <!-- Feature pills -->
        <div class="features">
            <div class="feat-pill"><div class="dot" style="background:var(--accent2)"></div>Kelola Pendapatan</div>
            <div class="feat-pill"><div class="dot" style="background:var(--teal)"></div>Kelola Pengeluaran</div>
            <div class="feat-pill"><div class="dot" style="background:var(--gold)"></div>Laporan Grafik</div>
            <div class="feat-pill"><div class="dot" style="background:var(--success)"></div>Edit & Hapus Data</div>
            <div class="feat-pill"><div class="dot" style="background:#a78bfa"></div>Multi User</div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const inp = document.getElementById('passwordInput');
    const ico = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
    } else {
        inp.type = 'password';
        ico.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
}

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.classList.add('loading');
    btn.textContent = 'Memproses...';
});

// Animate bars with staggered delay
document.querySelectorAll('.bar').forEach((b, i) => {
    b.style.animationDelay = (i * 0.06) + 's';
});
</script>
</body>
</html>