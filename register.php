<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama']     ?? '');
    $email   = strtolower(trim($_POST['email']    ?? ''));
    $pw      = $_POST['password']  ?? '';
    $pw2     = $_POST['konfirm']   ?? '';

    if (!$nama || !$email || !$pw || !$pw2)
        $error = 'Semua field wajib diisi.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $error = 'Format email tidak valid.';
    elseif (strlen($pw) < 8)
        $error = 'Password minimal 8 karakter.';
    elseif ($pw !== $pw2)
        $error = 'Konfirmasi password tidak cocok.';
    else {
        $cek = $koneksi->prepare('SELECT id_user FROM users WHERE email = ? LIMIT 1');
        $cek->bind_param('s', $email);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Email sudah terdaftar. Silakan login.';
        } else {
            $hash  = password_hash($pw, PASSWORD_BCRYPT);
            $token = bin2hex(random_bytes(32));
            $ins   = $koneksi->prepare('INSERT INTO users (nama, email, password, token_verifikasi, is_verified) VALUES (?,?,?,?,0)');
            $ins->bind_param('ssss', $nama, $email, $hash, $token);
            if ($ins->execute()) {
                $_SESSION['reg_email'] = $email;
                $_SESSION['reg_token'] = $token;
                header('Location: verifikasi.php'); exit();
            } else { $error = 'Gagal menyimpan. Coba lagi.'; }
            $ins->close();
        }
        $cek->close();
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
<link rel="stylesheet" href="style.css">
<style>
/* ── Layout auth split ── */
.auth-wrapper {
    display: flex;
    min-height: 100vh;
}
.auth-left {
    width: 460px;
    min-width: 340px;
    background: var(--bg-panel);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    padding: 36px 44px;
    flex-shrink: 0;
    overflow-y: auto;
}
.auth-right {
    flex: 1;
    background: #090e1b;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.auth-right .glow {
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 75% 65% at 65% 35%, rgba(99,51,220,0.55) 0%, transparent 65%),
        radial-gradient(ellipse 55% 75% at 30% 70%, rgba(20,100,230,0.4) 0%, transparent 60%),
        radial-gradient(ellipse 45% 45% at 80% 80%, rgba(6,182,212,0.3) 0%, transparent 55%);
    filter: blur(44px);
    animation: glow-shift 9s ease-in-out infinite alternate;
}
@keyframes glow-shift {
    0%   { transform: scale(1) rotate(0deg); }
    100% { transform: scale(1.05) rotate(-1.5deg); }
}
.auth-right .dot-grid {
    position: absolute; inset: 0;
    background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
    background-size: 34px 34px;
}
.auth-right .overlay { position: absolute; inset: 0; background: rgba(6,10,22,0.45); }
.auth-right-content {
    position: relative; z-index: 2;
    padding: 48px 40px;
    text-align: center;
    max-width: 400px;
}

/* Steps visual */
.flow-steps { margin-top: 36px; text-align: left; }
.flow-step {
    display: flex; gap: 16px;
    position: relative;
}
.flow-step:not(:last-child)::after {
    content: '';
    position: absolute; left: 16px; top: 36px;
    width: 2px; height: calc(100% - 6px);
    background: linear-gradient(180deg, rgba(99,102,241,0.4), transparent);
}
.fs-circle {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 800;
    flex-shrink: 0; margin-top: 2px;
}
.fs-circle.now  { background: var(--accent); color: #fff; box-shadow: 0 0 18px rgba(37,99,235,.6); }
.fs-circle.next { background: rgba(255,255,255,0.06); border: 1px solid var(--border); color: var(--text-muted); }
.fs-body { padding-bottom: 22px; }
.fs-name { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; }
.fs-name.now  { color: #fff; }
.fs-name.next { color: var(--text-muted); }
.fs-desc { font-size: 12px; color: var(--text-muted); margin-top: 2px; line-height: 1.5; }

/* Auth form extras */
.auth-head { margin: 28px 0 24px; }
.auth-head .step-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(37,99,235,0.12); border: 1px solid rgba(37,99,235,0.25);
    border-radius: 99px; padding: 5px 12px;
    font-size: 11px; font-weight: 700; color: var(--accent2);
    margin-bottom: 14px;
}
.auth-head .step-pill em {
    width: 18px; height: 18px; background: var(--accent); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; color: #fff; font-style: normal; font-weight: 800;
}
.auth-head h1 { font-size: 1.8rem; margin-bottom: 4px; }
.auth-head p  { font-size: 13px; color: var(--text-muted); }

/* Password strength */
.pw-bars { display: flex; gap: 4px; margin-top: 8px; }
.pw-bar  { flex: 1; height: 3px; border-radius: 2px; background: var(--border); transition: background .3s; }
.pw-bar.w { background: var(--danger); }
.pw-bar.m { background: var(--gold); }
.pw-bar.s { background: var(--success); }
.pw-hint  { font-size: 11px; color: var(--text-muted); margin-top: 4px; min-height: 16px; }

/* Toggle password icon button */
.pw-eye {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: var(--text-muted);
    padding: 2px; display: flex; align-items: center;
}
.pw-eye:hover { color: var(--text); }
.pw-eye svg { width: 15px; height: 15px; }

.auth-footer {
    font-size: 11px; color: var(--text-muted);
    text-align: center; margin-top: 24px; line-height: 1.8;
}
@media (max-width: 860px) { .auth-right { display: none; } .auth-left { width: 100%; } }
</style>
</head>
<body>
<div class="auth-wrapper">

  <!-- LEFT: Form -->
  <div class="auth-left">
    <a href="#" class="logo">
      <div class="logo-mark">
        <svg viewBox="0 0 28 28" fill="none">
          <path d="M2 4L2 16Q2 22 8 22L12 22Q14 22 14 19" stroke="white" stroke-width="2.8" stroke-linecap="round" fill="none"/>
          <path d="M16 22L16 8L24 22L24 8" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          <path d="M20 6L26 2M23.5 2L26 2L26 4.5" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="logo-text">
        <span class="brand-name">UMKM Next</span>
        <span class="brand-sub">Management System</span>
      </div>
    </a>

    <div class="auth-head">
      <div class="step-pill"><em>1</em> Langkah 1 dari 4 — Buat Akun</div>
      <h1>Daftar Akun Baru</h1>
      <p>Mulai kelola bisnis Anda bersama UMKM Next</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="regForm">
      <div class="form-group">
        <label class="form-label">Nama Lengkap</label>
        <div class="input-wrap">
          <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <input class="form-control" type="text" name="nama" placeholder="Nama sesuai KTP" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Email</label>
        <div class="input-wrap">
          <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <input class="form-control" type="email" name="email" placeholder="contoh@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <input class="form-control" type="password" name="password" id="pw1" placeholder="Min. 8 karakter" required oninput="strengthCheck(this.value)">
          <button type="button" class="pw-eye" onclick="toggleEye('pw1')"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
        </div>
        <div class="pw-bars"><div class="pw-bar" id="b1"></div><div class="pw-bar" id="b2"></div><div class="pw-bar" id="b3"></div><div class="pw-bar" id="b4"></div></div>
        <div class="pw-hint" id="pwHint">Masukkan password</div>
      </div>

      <div class="form-group">
        <label class="form-label">Konfirmasi Password</label>
        <div class="input-wrap">
          <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          <input class="form-control" type="password" name="konfirm" id="pw2" placeholder="Ulangi password" required>
          <button type="button" class="pw-eye" onclick="toggleEye('pw2')"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg mt-md" id="regBtn">
        Buat Akun & Lanjutkan →
      </button>

      <p class="text-center text-muted mt-sm" style="font-size:11px">
        Dengan mendaftar Anda menyetujui <a href="#">Syarat & Ketentuan</a> kami.
      </p>
    </form>

    <div class="divider">sudah punya akun?</div>
    <a href="login.php" class="btn btn-secondary btn-full">Masuk ke Akun →</a>

    <div class="auth-footer">
      &copy; <?= date('Y') ?> UMKM Next · Dibuat untuk UMKM Indonesia 🇮🇩
    </div>
  </div>

  <!-- RIGHT: Visual -->
  <div class="auth-right">
    <div class="glow"></div>
    <div class="dot-grid"></div>
    <div class="overlay"></div>
    <div class="auth-right-content">
      <h2 style="font-size:2rem;color:#fff;letter-spacing:-1px;margin-bottom:8px">Mulai dari <span class="text-gradient">Sini</span></h2>
      <p style="font-size:13px;color:rgba(255,255,255,.35);margin-bottom:4px">4 langkah mudah untuk memulai</p>

      <div class="flow-steps">
        <div class="flow-step">
          <div class="fs-circle now">1</div>
          <div class="fs-body"><div class="fs-name now">Buat Akun</div><div class="fs-desc">Daftarkan email dan buat password yang kuat.</div></div>
        </div>
        <div class="flow-step">
          <div class="fs-circle next">2</div>
          <div class="fs-body"><div class="fs-name next">Verifikasi Email</div><div class="fs-desc">Masukkan kode OTP untuk mengaktifkan akun.</div></div>
        </div>
        <div class="flow-step">
          <div class="fs-circle next">3</div>
          <div class="fs-body"><div class="fs-name next">Login</div><div class="fs-desc">Masuk ke sistem dengan akun yang sudah aktif.</div></div>
        </div>
        <div class="flow-step">
          <div class="fs-circle next">4</div>
          <div class="fs-body"><div class="fs-name next">Lengkapi Profil UMKM</div><div class="fs-desc">Isi data bisnis dan akses semua fitur.</div></div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function toggleEye(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}
function strengthCheck(v) {
    const bars = [1,2,3,4].map(i => document.getElementById('b'+i));
    const hint = document.getElementById('pwHint');
    bars.forEach(b => b.className = 'pw-bar');
    let sc = 0;
    if (v.length >= 8) sc++;
    if (/[A-Z]/.test(v)) sc++;
    if (/[0-9]/.test(v)) sc++;
    if (/[^A-Za-z0-9]/.test(v)) sc++;
    const cl = sc <= 1 ? 'w' : sc <= 2 ? 'm' : 's';
    const tx = ['','Lemah','Cukup','Kuat','Sangat Kuat'];
    const cl2= sc<=1?'var(--danger)':sc<=2?'var(--gold)':'var(--success)';
    bars.slice(0, sc).forEach(b => b.classList.add(cl));
    hint.textContent = v.length ? (tx[sc] || 'Sangat Kuat') : 'Masukkan password';
    hint.style.color = v.length ? cl2 : 'var(--text-muted)';
}
document.getElementById('regForm').addEventListener('submit', function() {
    const btn = document.getElementById('regBtn');
    btn.classList.add('loading');
    btn.textContent = 'Memproses...';
});
</script>
</body>
</html>