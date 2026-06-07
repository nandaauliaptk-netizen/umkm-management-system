<?php
session_start();
require_once 'koneksi.php';

// Sudah login → cek profil dulu
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error   = '';
$flash   = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email dan kata sandi wajib diisi.';
    } else {
        $stmt = $koneksi->prepare('SELECT id_user, nama, password, is_verified, profil_dilengkapi FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id_user, $nama, $hash, $is_verified, $profil_dilengkapi);
        $stmt->fetch();

        if ($stmt->num_rows === 0) {
            $error = 'Email tidak ditemukan.';
        } elseif (!password_verify($password, $hash)) {
            $error = 'Kata sandi salah.';
        } elseif (!$is_verified) {
            // Belum verifikasi → arahkan ke verifikasi
            // Ambil ulang token dari DB
            $stmt2 = $koneksi->prepare('SELECT token_verifikasi FROM users WHERE id_user = ?');
            $stmt2->bind_param('i', $id_user);
            $stmt2->execute();
            $stmt2->bind_result($token);
            $stmt2->fetch();
            $stmt2->close();

            $_SESSION['reg_email'] = $email;
            $_SESSION['reg_token'] = $token;
            header('Location: verifikasi.php');
            exit();
        } else {
            // Login sukses
            $_SESSION['user_id']          = $id_user;
            $_SESSION['nama']             = $nama;
            $_SESSION['profil_dilengkapi']= $profil_dilengkapi;

            // Belum lengkapi profil UMKM → paksa ke setup profil
            if (!$profil_dilengkapi) {
                header('Location: setup_profil.php');
            } else {
                header('Location: index.php');
            }
            exit();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — UMKM Next</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0b1120;--panel:#131f35;--border:rgba(255,255,255,0.07);--accent:#2563eb;--accent2:#3b82f6;--teal:#06b6d4;--text:#e2e8f0;--muted:#64748b;--danger:#ef4444;--success:#10b981;--gold:#f59e0b}
body{font-family:'DM Sans',sans-serif;background:var(--navy);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");pointer-events:none;z-index:0}
.left{position:relative;z-index:1;width:480px;min-width:360px;background:var(--panel);border-right:1px solid var(--border);display:flex;flex-direction:column;justify-content:space-between;padding:36px 44px;flex-shrink:0}
.logo{display:flex;align-items:center;gap:12px}
.logo-mark{width:44px;height:44px;background:rgba(255,255,255,0.08);border:1.5px solid rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(99,51,220,0.35)}
.logo-mark svg{width:26px;height:26px}
.logo-text strong{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;display:block;letter-spacing:-.5px}
.logo-text span{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:2px}
.form-area{flex:1;display:flex;flex-direction:column;justify-content:center;padding:24px 0}
h1{font-family:'Syne',sans-serif;font-size:30px;font-weight:800;letter-spacing:-1px;margin-bottom:6px}
.sub{color:var(--muted);font-size:13px;margin-bottom:28px}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px;animation:fadeIn .3s ease}
.alert svg{width:16px;height:16px;flex-shrink:0}
.alert.error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#fca5a5}
.alert.success{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
@keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.form-group{margin-bottom:16px}
label{display:block;font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:8px}
.input-wrap{position:relative}
.input-wrap svg.icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--muted);pointer-events:none}
input[type=email],input[type=password],input[type=text]{width:100%;padding:12px 14px 12px 42px;background:rgba(255,255,255,0.04);border:1.5px solid var(--border);border-radius:10px;font-size:14px;color:var(--text);font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s}
input:focus{border-color:var(--accent2);box-shadow:0 0 0 3px rgba(59,130,246,0.15)}
.toggle-pw{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);display:flex;align-items:center;padding:4px}
.toggle-pw:hover{color:var(--text)}
.row-forgot{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;font-size:13px}
.row-forgot label{text-transform:none;letter-spacing:0;font-weight:400;margin:0;display:flex;align-items:center;gap:6px;cursor:pointer;color:var(--muted)}
.row-forgot a{color:var(--accent2);text-decoration:none;font-size:12px}
.row-forgot a:hover{text-decoration:underline}
input[type=checkbox]{accent-color:var(--accent2)}
.btn-submit{width:100%;padding:14px;background:var(--accent);border:none;border-radius:12px;color:#fff;font-size:15px;font-weight:700;font-family:'Syne',sans-serif;cursor:pointer;transition:all .2s;letter-spacing:.3px}
.btn-submit:hover{background:#1d4ed8;box-shadow:0 4px 20px rgba(37,99,235,0.5);transform:translateY(-1px)}
.btn-submit.loading{opacity:.8;pointer-events:none}
.divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:var(--muted);font-size:12px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}
.reg-link{text-align:center;font-size:13px;color:var(--muted)}
.reg-link a{color:var(--accent2);text-decoration:none;font-weight:600}
.reg-link a:hover{text-decoration:underline}
.left-footer{font-size:11px;color:var(--muted);line-height:1.6}
.right{flex:1;position:relative;z-index:1;display:flex;align-items:center;justify-content:center;overflow:hidden}
.swirl{position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(37,99,235,0.15) 0%,transparent 65%);top:50%;left:50%;transform:translate(-50%,-50%)}
.dot-grid{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,0.06) 1px,transparent 1px);background-size:28px 28px}
.right-content{position:relative;z-index:2;max-width:400px;padding:40px;text-align:center}
.right-emoji{font-size:56px;margin-bottom:20px;display:block;animation:float 3s ease-in-out infinite}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.right-title{font-family:'Syne',sans-serif;font-size:30px;font-weight:800;letter-spacing:-1px;line-height:1.1;margin-bottom:14px}
.right-title span{background:linear-gradient(135deg,#60a5fa,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.right-sub{color:var(--muted);font-size:14px;line-height:1.7;margin-bottom:28px}
.feature-list{display:flex;flex-direction:column;gap:10px;text-align:left}
.feature-item{display:flex;align-items:center;gap:12px;padding:10px 14px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:10px;font-size:13px;color:var(--muted)}
.feature-item span:first-child{font-size:20px;flex-shrink:0}
</style>
</head>
<body>

<!-- LEFT -->
<div class="left">
    <div class="logo">
        <div class="logo-mark">
            <svg viewBox="0 0 28 28" fill="none">
                <path d="M2 4 L2 16 Q2 22 8 22 L12 22 Q14 22 14 19" stroke="white" stroke-width="2.8" stroke-linecap="round" fill="none"/>
                <path d="M16 22 L16 8 L24 22 L24 8" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M20 6 L26 2 M23.5 2 L26 2 L26 4.5" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="logo-text">
            <strong>UMKM Next</strong>
            <span>Management System</span>
        </div>
    </div>

    <div class="form-area">
        <h1>Selamat Datang 👋</h1>
        <p class="sub">Masuk untuk melanjutkan mengelola usaha Anda.</p>

        <?php if ($error): ?>
        <div class="alert error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($flash): ?>
        <div class="alert success">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($flash) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <input type="email" id="email" name="email" placeholder="email@contoh.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <input type="password" id="password" name="password" placeholder="Kata sandi Anda" required>
                    <button type="button" class="toggle-pw" onclick="togglePw()">
                        <svg id="eyeIcon" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
            </div>

            <div class="row-forgot">
                <label><input type="checkbox" name="ingat"> Ingat saya</label>
                <a href="#">Lupa kata sandi?</a>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">Masuk ke Dashboard →</button>
        </form>

        <div class="divider">belum punya akun?</div>
        <div class="reg-link"><a href="register.php">Daftar sekarang — Gratis!</a></div>
    </div>

    <div class="left-footer">
        &copy; <?= date('Y') ?> UMKM Next Management System<br>
        Dibuat untuk kemajuan UMKM Indonesia 🇮🇩
    </div>
</div>

<!-- RIGHT -->
<div class="right">
    <div class="swirl"></div>
    <div class="dot-grid"></div>
    <div class="right-content">
        <span class="right-emoji">💼</span>
        <div class="right-title">Satu Platform<br><span>Semua Kebutuhan</span></div>
        <p class="right-sub">Kelola keuangan, produk, pesanan, dan laporan UMKM Anda dalam satu dashboard yang mudah digunakan.</p>
        <div class="feature-list">
            <div class="feature-item"><span>💰</span> Laporan keuangan otomatis real-time</div>
            <div class="feature-item"><span>📦</span> Manajemen stok & produk digital</div>
            <div class="feature-item"><span>🧾</span> Invoice & pesanan dalam satu klik</div>
            <div class="feature-item"><span>📊</span> Grafik & analisis performa usaha</div>
        </div>
    </div>
</div>

<script>
function togglePw() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
    document.getElementById('eyeIcon').style.opacity = input.type === 'text' ? '0.5' : '1';
}
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.classList.add('loading');
    btn.textContent = 'Memproses...';
});
</script>
</body>
</html>