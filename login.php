<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$error   = '';
$flash   = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pw    = $_POST['password'] ?? '';

    if (!$email || !$pw) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $koneksi->prepare('SELECT id_user, nama, email, password, is_verified FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'Email tidak terdaftar.';
        } elseif (!$user['is_verified']) {
            // Arahkan ke verifikasi jika belum diverifikasi
            $_SESSION['reg_email'] = $email;
            // Ambil token dari DB
            $tk = $koneksi->prepare('SELECT token_verifikasi FROM users WHERE email=? LIMIT 1');
            $tk->bind_param('s', $email);
            $tk->execute();
            $trow = $tk->get_result()->fetch_assoc();
            $tk->close();
            $_SESSION['reg_token'] = $trow['token_verifikasi'] ?? bin2hex(random_bytes(32));
            $error = 'Akun belum diverifikasi. <a href="verifikasi.php">Klik di sini untuk verifikasi.</a>';
        } elseif (!password_verify($pw, $user['password'])) {
            $error = 'Password salah.';
        } else {
            // ── Login berhasil ──
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];

            // Cek profil UMKM
            $cp = $koneksi->prepare('SELECT is_complete FROM profil_umkm WHERE id_user=? LIMIT 1');
            $cp->bind_param('i', $user['id_user']);
            $cp->execute();
            $pr = $cp->get_result()->fetch_assoc();
            $cp->close();

            if (!$pr || !$pr['is_complete']) {
                // Profil belum lengkap → wajib ke profil_umkm
                header('Location: profil_umkm.php');
            } else {
                $_SESSION['profil_complete'] = true;
                header('Location: index.php');
            }
            exit();
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
<link rel="stylesheet" href="style.css">
<style>
.auth-wrapper { display:flex; min-height:100vh; }
.auth-left {
    width:460px; min-width:340px;
    background:var(--bg-panel); border-right:1px solid var(--border);
    display:flex; flex-direction:column; padding:36px 44px;
    flex-shrink:0; overflow-y:auto;
}
.auth-right {
    flex:1; background:#090e1b; position:relative;
    overflow:hidden; display:flex; align-items:center; justify-content:center;
}
.glow {
    position:absolute; inset:0;
    background:
        radial-gradient(ellipse 75% 65% at 65% 35%, rgba(99,51,220,0.55) 0%, transparent 65%),
        radial-gradient(ellipse 55% 75% at 30% 70%, rgba(20,100,230,0.4) 0%, transparent 60%),
        radial-gradient(ellipse 45% 45% at 80% 80%, rgba(6,182,212,0.3) 0%, transparent 55%);
    filter:blur(44px);
    animation:glow-shift 9s ease-in-out infinite alternate;
}
@keyframes glow-shift{0%{transform:scale(1)}100%{transform:scale(1.05)}}
.dot-grid{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,0.06) 1px,transparent 1px);background-size:34px 34px;}
.overlay{position:absolute;inset:0;background:rgba(6,10,22,0.45);}
.auth-right-content{position:relative;z-index:2;padding:48px 40px;text-align:center;max-width:400px;}

.auth-head{margin:28px 0 24px;}
.step-pill{
    display:inline-flex;align-items:center;gap:6px;
    background:rgba(37,99,235,0.12);border:1px solid rgba(37,99,235,0.25);
    border-radius:99px;padding:5px 12px;
    font-size:11px;font-weight:700;color:var(--accent2);margin-bottom:14px;
}
.step-pill em{width:18px;height:18px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff;font-style:normal;font-weight:800;}

.pw-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:2px;display:flex;align-items:center;}
.pw-eye:hover{color:var(--text);}
.pw-eye svg{width:15px;height:15px;}

.auth-footer{font-size:11px;color:var(--text-muted);text-align:center;margin-top:auto;padding-top:24px;line-height:1.8;}

.flow-steps{margin-top:32px;text-align:left;}
.flow-step{display:flex;gap:16px;position:relative;}
.flow-step:not(:last-child)::after{content:'';position:absolute;left:16px;top:34px;width:2px;height:calc(100% - 4px);background:linear-gradient(180deg,rgba(37,99,235,0.4),transparent);}
.fs-circle{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:12px;font-weight:800;flex-shrink:0;margin-top:2px;}
.fs-circle.done{background:var(--success);color:#fff;}
.fs-circle.now{background:var(--accent);color:#fff;box-shadow:0 0 18px rgba(37,99,235,.6);}
.fs-circle.next{background:rgba(255,255,255,0.06);border:1px solid var(--border);color:var(--text-muted);}
.fs-body{padding-bottom:20px;}
.fs-name{font-family:'Syne',sans-serif;font-size:14px;font-weight:700;}
.fs-name.done{color:var(--success);}
.fs-name.now{color:#fff;}
.fs-name.next{color:var(--text-muted);}
.fs-desc{font-size:12px;color:var(--text-muted);margin-top:2px;}

@media(max-width:860px){.auth-right{display:none}.auth-left{width:100%}}
</style>
</head>
<body>
<div class="auth-wrapper">

  <!-- LEFT -->
  <div class="auth-left">
    <a href="register.php" class="logo">
      <div class="logo-mark">
        <svg viewBox="0 0 28 28" fill="none"><path d="M2 4L2 16Q2 22 8 22L12 22Q14 22 14 19" stroke="white" stroke-width="2.8" stroke-linecap="round" fill="none"/><path d="M16 22L16 8L24 22L24 8" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/><path d="M20 6L26 2M23.5 2L26 2L26 4.5" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="logo-text"><span class="brand-name">UMKM Next</span><span class="brand-sub">Management System</span></div>
    </a>

    <div class="auth-head">
      <div class="step-pill"><em>3</em> Langkah 3 dari 4 — Login</div>
      <h1>Selamat Datang 👋</h1>
      <p class="text-muted">Masuk ke akun UMKM Next Anda</p>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-success">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/></svg>
      <?= htmlspecialchars($flash) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg>
      <?= $error /* sengaja tidak di-escape karena ada tag <a> */ ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
      <div class="form-group">
        <label class="form-label">Email</label>
        <div class="input-wrap">
          <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <input class="form-control" type="email" name="email" placeholder="contoh@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" style="display:flex;justify-content:space-between">
          Password
          <a href="#" style="font-size:11px;text-transform:none;letter-spacing:0;color:var(--accent2)">Lupa password?</a>
        </label>
        <div class="input-wrap">
          <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <input class="form-control" type="password" name="password" id="pwLogin" placeholder="Password Anda" required>
          <button type="button" class="pw-eye" onclick="toggleEye()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg mt-md" id="loginBtn">
        Masuk ke Dashboard →
      </button>
    </form>

    <div class="divider">belum punya akun?</div>
    <a href="register.php" class="btn btn-secondary btn-full">Daftar Akun Baru</a>

    <div class="auth-footer">&copy; <?= date('Y') ?> UMKM Next · Indonesia 🇮🇩</div>
  </div>

  <!-- RIGHT -->
  <div class="auth-right">
    <div class="glow"></div><div class="dot-grid"></div><div class="overlay"></div>
    <div class="auth-right-content">
      <h2 style="font-size:2rem;color:#fff;letter-spacing:-1px;margin-bottom:10px">Masuk &amp; <span class="text-gradient">Kelola</span></h2>
      <p style="font-size:13px;color:rgba(255,255,255,.35);margin-bottom:4px">Anda sudah hampir sampai!</p>

      <div class="flow-steps">
        <div class="flow-step">
          <div class="fs-circle done">✓</div>
          <div class="fs-body"><div class="fs-name done">Buat Akun</div><div class="fs-desc">Selesai.</div></div>
        </div>
        <div class="flow-step">
          <div class="fs-circle done">✓</div>
          <div class="fs-body"><div class="fs-name done">Verifikasi Email</div><div class="fs-desc">Selesai.</div></div>
        </div>
        <div class="flow-step">
          <div class="fs-circle now">3</div>
          <div class="fs-body"><div class="fs-name now">Login</div><div class="fs-desc">Masuk dengan akun yang sudah aktif.</div></div>
        </div>
        <div class="flow-step">
          <div class="fs-circle next">4</div>
          <div class="fs-body"><div class="fs-name next">Profil UMKM</div><div class="fs-desc">Lengkapi data bisnis untuk akses penuh.</div></div>
        </div>
      </div>
    </div>
  </div>

</div>
<script>
function toggleEye() {
    const el = document.getElementById('pwLogin');
    el.type = el.type === 'password' ? 'text' : 'password';
}
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.classList.add('loading');
    btn.textContent = 'Masuk...';
});
</script>
</body>
</html>