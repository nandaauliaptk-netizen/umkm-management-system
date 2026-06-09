<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $email    = trim(strtolower($_POST['email'] ?? ''));
    $password =      $_POST['password'] ?? '';
    $confirm  =      $_POST['confirm']  ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek apakah email sudah terdaftar
    $cek = $conn->prepare("SELECT id_user FROM users WHERE email = ? LIMIT 1");
        if (!$cek) {
            $error = 'Kesalahan database: ' . $conn->error;
        } else {
            $cek->bind_param("s", $email);
            $cek->execute();
            $cek->store_result();

            if ($cek->num_rows > 0) {
                $error = 'Email sudah terdaftar, gunakan email lain atau login.';
            } else {
                $hash   = password_hash($password, PASSWORD_DEFAULT);
                $status = 'aktif';
                $stmt   = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
                if (!$stmt) {
                    $error = 'Kesalahan database: ' .  $conn->error;
                } else {
                    $stmt->bind_param("ssss", $nama, $email, $hash, $status);
                    if ($stmt->execute()) {
                        $success = 'Akun berhasil dibuat! Silakan login.';
                    } else {
                        $error = 'Gagal mendaftarkan akun: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
            $cek->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — UMKM Next</title>
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

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none; z-index: 0;
        }

        /* ══ LEFT PANEL ══ */
        .left {
            position: relative; z-index: 1;
            width: 460px; min-width: 340px;
            background: var(--panel);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            justify-content: space-between;
            padding: 36px 44px;
            flex-shrink: 0;
            overflow-y: auto;
        }

        /* Logo */
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo-mark {
            width: 44px; height: 44px;
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(99,51,220,0.35);
            flex-shrink: 0;
        }
        .logo-mark svg { width: 28px; height: 28px; }
        .logo-text { line-height: 1; }
        .logo-text span:first-child {
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 800; letter-spacing: -0.5px; display: block;
        }
        .logo-text span:last-child {
            font-size: 10px; color: var(--muted);
            text-transform: uppercase; letter-spacing: 2px;
        }

        /* Form area */
        .form-area { flex: 1; display: flex; flex-direction: column; justify-content: center; padding: 24px 0 16px; }

        .form-area h1 {
            font-family: 'Syne', sans-serif;
            font-size: 27px; font-weight: 800;
            letter-spacing: -1px; margin-bottom: 5px;
        }
        .form-area p.sub {
            color: var(--muted); font-size: 13.5px; margin-bottom: 28px;
        }

        /* Alert */
        .alert {
            padding: 11px 15px; border-radius: 10px;
            font-size: 13px; margin-bottom: 18px;
            display: flex; align-items: center; gap: 10px;
            animation: fadeIn .3s ease;
        }
        .alert.error   { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; }
        .alert.success { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.25); color: #6ee7b7; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

        /* 2-column grid for nama + username */
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .field { margin-bottom: 16px; }
        .field label {
            display: block; font-size: 11.5px; font-weight: 500;
            color: var(--muted); text-transform: uppercase;
            letter-spacing: 1.2px; margin-bottom: 7px;
        }
        .input-wrap { position: relative; }
        .input-wrap svg.icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            width: 15px; height: 15px; color: var(--muted); pointer-events: none;
        }
        .input-wrap input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px 12px 40px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; color: var(--text); outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .input-wrap input::placeholder { color: #374151; }
        .input-wrap input:focus {
            border-color: var(--accent2);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .toggle-pw {
            position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--muted); display: flex; align-items: center;
        }
        .toggle-pw:hover { color: var(--text); }

        /* Password strength */
        .strength-bar { display: flex; gap: 4px; margin-top: 7px; }
        .sb { flex: 1; height: 3px; border-radius: 2px; background: rgba(255,255,255,0.08); transition: background .3s; }
        .sb.weak   { background: var(--danger); }
        .sb.medium { background: var(--gold); }
        .sb.strong { background: var(--success); }
        .strength-label { font-size: 10px; color: var(--muted); margin-top: 4px; }

        /* Terms */
        .terms-row {
            display: flex; align-items: flex-start; gap: 10px;
            margin: 4px 0 20px;
        }
        .terms-row input[type="checkbox"] {
            position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none;
        }
        .custom-cb {
            width: 16px; height: 16px; min-width: 16px;
            border: 1.5px solid var(--border);
            border-radius: 4px; background: transparent;
            display: flex; align-items: center; justify-content: center;
            transition: all .2s; margin-top: 2px; cursor: pointer;
        }
        .terms-row input:checked + .custom-cb { background: var(--accent); border-color: var(--accent); }
        .terms-row input:checked + .custom-cb::after { content: '✓'; font-size: 10px; color: #fff; }
        .terms-row span { font-size: 12.5px; color: var(--muted); line-height: 1.5; cursor: pointer; }
        .terms-row .link { color: var(--accent2); text-decoration: none; }
        .terms-row .link:hover { text-decoration: underline; }

        /* Submit */
        .btn-register {
            width: 100%;
            background: linear-gradient(135deg, var(--accent) 0%, #1d4ed8 100%);
            border: none; border-radius: 10px;
            padding: 13px; font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 700; color: #fff;
            cursor: pointer; letter-spacing: 0.3px;
            transition: transform .15s, box-shadow .2s, opacity .2s;
            box-shadow: 0 4px 24px rgba(37,99,235,0.4);
            position: relative; overflow: hidden;
        }
        .btn-register::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,0.1));
            opacity: 0; transition: opacity .2s;
        }
        .btn-register:hover { transform: translateY(-1px); box-shadow: 0 6px 32px rgba(37,99,235,0.55); }
        .btn-register:hover::before { opacity: 1; }
        .btn-register:active { transform: translateY(0); }
        .btn-register.loading { opacity: .7; pointer-events: none; }

        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 20px 0; color: var(--muted); font-size: 12px;
        }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        .login-row { text-align: center; font-size: 13px; color: var(--muted); }
        .link { font-size: 13px; color: var(--accent2); text-decoration: none; }
        .link:hover { text-decoration: underline; }

        .left-footer { font-size: 11px; color: var(--muted); text-align: center; line-height: 1.8; padding-top: 16px; }

        /* ══ RIGHT PANEL ══ */
        .right {
            flex: 1; position: relative; overflow: hidden;
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            background: #0a0f1e;
        }

        .swirl {
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 80% 70% at 70% 40%, rgba(99,51,220,0.55) 0%, transparent 65%),
                radial-gradient(ellipse 60% 80% at 30% 70%, rgba(20,100,230,0.45) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 80% 80%, rgba(6,182,212,0.3) 0%, transparent 55%),
                radial-gradient(ellipse 40% 40% at 20% 20%, rgba(139,92,246,0.25) 0%, transparent 50%);
            animation: swirl-shift 10s ease-in-out infinite alternate;
        }
        @keyframes swirl-shift {
            0%   { filter: blur(40px) saturate(1.2); transform: scale(1) rotate(0deg); }
            50%  { filter: blur(50px) saturate(1.4); transform: scale(1.05) rotate(2deg); }
            100% { filter: blur(40px) saturate(1.2); transform: scale(1) rotate(-1deg); }
        }

        .right::after {
            content: ''; position: absolute; inset: 0;
            background: rgba(8,12,28,0.45);
            pointer-events: none; z-index: 1;
        }

        .dot-grid {
            position: absolute; inset: 0; z-index: 2;
            background-image: radial-gradient(rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 36px 36px;
        }

        .right-content {
            position: relative; z-index: 3;
            text-align: center; padding: 48px 40px;
            max-width: 500px;
            display: flex; flex-direction: column; align-items: center;
        }

        .hero-logo-bg {
            width: 90px; height: 90px;
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 22px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 32px;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 40px rgba(99,51,220,0.4), 0 0 80px rgba(6,182,212,0.15);
            animation: logo-pulse 4s ease-in-out infinite;
        }
        @keyframes logo-pulse {
            0%,100% { box-shadow: 0 8px 40px rgba(99,51,220,.4), 0 0 80px rgba(6,182,212,.15); }
            50%      { box-shadow: 0 8px 60px rgba(99,51,220,.65), 0 0 120px rgba(6,182,212,.3); }
        }
        .hero-logo-bg svg { width: 52px; height: 52px; }

        .hero-title {
            font-family: 'Syne', sans-serif;
            font-size: 52px; font-weight: 800;
            letter-spacing: -3px; line-height: 1;
            color: #fff; margin-bottom: 16px;
        }
        .hero-title span {
            background: linear-gradient(135deg, #60a5fa 0%, #06b6d4 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-divider {
            width: 60px; height: 2px;
            background: linear-gradient(90deg, var(--accent2), var(--teal));
            border-radius: 2px; margin-bottom: 28px;
        }

        .hero-tagline {
            font-size: 15px; color: rgba(255,255,255,0.45);
            line-height: 1.7; margin-bottom: 44px;
            font-weight: 300; letter-spacing: 0.2px;
        }

        /* Steps visual */
        .steps {
            display: flex; flex-direction: column; gap: 12px;
            width: 100%; max-width: 320px; text-align: left;
        }
        .step {
            display: flex; align-items: center; gap: 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px; padding: 14px 18px;
            backdrop-filter: blur(8px);
            transition: background .3s, border-color .3s;
        }
        .step:hover { background: rgba(255,255,255,0.07); border-color: rgba(99,102,241,0.3); }
        .step-num {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 800;
            flex-shrink: 0;
        }
        .step-text strong { font-size: 13px; color: rgba(255,255,255,0.9); font-weight: 600; display: block; }
        .step-text span   { font-size: 11px; color: rgba(255,255,255,0.35); }

        .hero-badge {
            margin-top: 36px;
            display: flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 30px; padding: 8px 20px;
            font-size: 12px; color: rgba(255,255,255,0.4);
        }
        .badge-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 8px var(--success);
            animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.4} }

        @media (max-width: 900px) {
            .right { display: none; }
            .left  { width: 100%; }
        }
    </style>
</head>
<body>

<!-- ════ LEFT — REGISTER FORM ════ -->
<div class="left">
    <div class="logo">
        <div class="logo-mark">
            <!-- UN Logo: U + N with upward arrow through N -->
            <svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- U -->
                <path d="M2 4 L2 16 Q2 22 8 22 L12 22 Q14 22 14 19" stroke="white" stroke-width="2.8" stroke-linecap="round" fill="none"/>
                <!-- N body -->
                <path d="M16 22 L16 8 L24 22 L24 8" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <!-- Arrow up-right on N -->
                <path d="M20 6 L26 2 M23.5 2 L26 2 L26 4.5" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="logo-text">
            <span>UMKM Next</span>
            <span>Management System</span>
        </div>
    </div>

    <div class="form-area">
        <h1>Buat Akun Baru ✨</h1>
        <p class="sub">Daftarkan diri Anda untuk mulai mengelola bisnis UMKM</p>

        <?php if ($error): ?>
        <div class="alert error">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert success">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($success) ?> <a href="login.php" class="link" style="margin-left:6px;">Login →</a>
        </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <!-- Nama Lengkap -->
            <div class="field">
                <label>Nama Lengkap</label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <input type="text" name="nama" placeholder="Nama lengkap Anda" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required autocomplete="name">
                </div>
            </div>

            <!-- Email (digunakan sebagai login) -->
            <div class="field">
                <label>Email <span style="font-size:10px;color:var(--muted);text-transform:none;letter-spacing:0">(digunakan untuk login)</span></label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <input type="email" name="email" placeholder="email@contoh.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
            </div>

            <!-- Password -->
            <div class="field">
                <label>Password</label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <input type="password" name="password" id="pwInput" placeholder="Min. 6 karakter" required oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-pw" onclick="togglePw('pwInput','eyeA')" title="Tampilkan">
                        <svg id="eyeA" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                <div class="strength-bar">
                    <div class="sb" id="sb1"></div>
                    <div class="sb" id="sb2"></div>
                    <div class="sb" id="sb3"></div>
                    <div class="sb" id="sb4"></div>
                </div>
                <div class="strength-label" id="slabel"></div>
            </div>

            <!-- Konfirmasi Password -->
            <div class="field">
                <label>Konfirmasi Password</label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <input type="password" name="confirm" id="cfInput" placeholder="Ulangi password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('cfInput','eyeB')" title="Tampilkan">
                        <svg id="eyeB" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <!-- Terms -->
            <div class="terms-row">
                <input type="checkbox" id="agreeChk" name="agree">
                <div class="custom-cb" id="cbVisual" onclick="toggleAgree()"></div>
                <span onclick="toggleAgree()" style="cursor:pointer;">Saya menyetujui <a href="#" class="link" onclick="event.stopPropagation()">syarat &amp; ketentuan</a> penggunaan UMKM Next</span>
            </div>

            <button type="submit" class="btn-register" id="regBtn">Buat Akun Sekarang</button>
        </form>

        <div class="divider">atau</div>
        <p class="login-row">Sudah punya akun? <a href="login.php" class="link">Masuk di sini</a></p>
    </div>

    <div class="left-footer">
        &copy; <?= date('Y') ?> UMKM Next Management System<br>
        Dibuat untuk kemajuan UMKM Indonesia 🇮🇩
    </div>
</div>

<!-- ════ RIGHT — BRANDING ════ -->
<div class="right">
    <div class="swirl"></div>
    <div class="dot-grid"></div>

    <div class="right-content">
        <div class="hero-logo-bg">
            <!-- Large UN logo -->
            <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- U -->
                <path d="M4 8 L4 30 Q4 42 14 42 L22 42 Q26 42 26 36" stroke="white" stroke-width="5" stroke-linecap="round" fill="none"/>
                <!-- N body -->
                <path d="M30 42 L30 14 L46 42 L46 14" stroke="white" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <!-- Arrow -->
                <path d="M39 10 L48 4 M44 4 L48 4 L48 8" stroke="#f59e0b" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <h2 class="hero-title">UMKM <span>Next</span></h2>
        <div class="hero-divider"></div>
        <p class="hero-tagline">Bergabung dan mulai kelola bisnis UMKM<br>Anda secara digital, cepat & terorganisir.</p>

        <!-- Steps -->
        <div class="steps">
            <div class="step">
                <div class="step-num" style="background:rgba(59,130,246,0.15);color:var(--accent2);">1</div>
                <div class="step-text">
                    <strong>Daftar Akun</strong>
                    <span>Isi data diri & buat akun gratis</span>
                </div>
            </div>
            <div class="step">
                <div class="step-num" style="background:rgba(6,182,212,0.15);color:var(--teal);">2</div>
                <div class="step-text">
                    <strong>Catat Transaksi</strong>
                    <span>Input pendapatan & pengeluaran</span>
                </div>
            </div>
            <div class="step">
                <div class="step-num" style="background:rgba(16,185,129,0.15);color:var(--success);">3</div>
                <div class="step-text">
                    <strong>Pantau Laporan</strong>
                    <span>Lihat grafik & analisis bisnis Anda</span>
                </div>
            </div>
        </div>

        <div class="hero-badge">
            <div class="badge-dot"></div>
            Pendaftaran gratis & aman
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePw(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
    } else {
        inp.type = 'password';
        ico.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
}

// Password strength checker
function checkStrength(val) {
    const bars  = [document.getElementById('sb1'), document.getElementById('sb2'),
                [document.getElementById('sb3'), document.getElementById('sb4')]];
    const label = document.getElementById('slabel');
    bars.forEach(b => { b.className = 'sb'; });

    if (!val) { label.textContent = ''; return; }

    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const cls   = score <= 1 ? 'weak' : score === 2 ? 'medium' : 'strong';
    const texts = ['', 'Lemah', 'Lemah', 'Sedang', 'Kuat'];
    for (let i = 0; i < score; i++) bars[i].classList.add(cls);
    label.textContent = texts[score] || '';
    label.style.color = cls === 'weak' ? 'var(--danger)' : cls === 'medium' ? 'var(--gold)' : 'var(--success)';
}



// Toggle checkbox
function toggleAgree() {
    const chk = document.getElementById('agreeChk');
    const vis = document.getElementById('cbVisual');
    chk.checked = !chk.checked;
    if (chk.checked) {
        vis.style.background = 'var(--accent)';
        vis.style.borderColor = 'var(--accent)';
        vis.innerHTML = '<span style="font-size:10px;color:#fff;line-height:1">✓</span>';
    } else {
        vis.style.background = '';
        vis.style.borderColor = '';
        vis.innerHTML = '';
    }
}

// Form submit dengan validasi manual
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const chk = document.getElementById('agreeChk');
    if (!chk.checked) {
        e.preventDefault();
        // Tampilkan pesan error
        let errEl = document.getElementById('frontendError');
        if (!errEl) {
            errEl = document.createElement('div');
            errEl.id = 'frontendError';
            errEl.className = 'alert error';
            errEl.innerHTML = '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg> Harap centang persetujuan syarat &amp; ketentuan terlebih dahulu.';
            this.insertBefore(errEl, this.querySelector('.terms-row'));
        }
        errEl.scrollIntoView({behavior:'smooth', block:'center'});
        return;
    }

    // Validasi konfirmasi password
    const pw  = document.getElementById('pwInput').value;
    const cf  = document.getElementById('cfInput').value;
    if (pw !== cf) {
        e.preventDefault();
        let errEl = document.getElementById('frontendError');
        if (!errEl) {
            errEl = document.createElement('div');
            errEl.id = 'frontendError';
            errEl.className = 'alert error';
            this.insertBefore(errEl, this.querySelector('.terms-row'));
        }
        errEl.innerHTML = '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg> Konfirmasi password tidak cocok.';
        errEl.scrollIntoView({behavior:'smooth', block:'center'});
        return;
    }

    const btn = document.getElementById('regBtn');
    btn.classList.add('loading');
    btn.textContent = 'Memproses...';
});
</script>
</body>
</html>