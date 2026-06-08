<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }
if (!isset($_SESSION['reg_email'])) { header('Location: register.php'); exit(); }

$email    = $_SESSION['reg_email'];
$token    = $_SESSION['reg_token'] ?? '';
$otp_demo = strtoupper(substr($token, 0, 6)); // DEMO — produksi kirim via email SMTP
$error    = '';
$success  = '';

if (isset($_GET['resend'])) {
    $success = 'Kode baru telah dikirim ke ' . $email . '.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = strtoupper(trim(implode('', $_POST['otp'] ?? [])));
    if (strlen($input) < 6) {
        $error = 'Masukkan 6 digit kode verifikasi.';
    } elseif ($input !== $otp_demo) {
        $error = 'Kode salah. Periksa kembali atau minta kode baru.';
    } else {
        $upd = $koneksi->prepare('UPDATE users SET is_verified=1, token_verifikasi=NULL WHERE email=? AND is_verified=0');
        $upd->bind_param('s', $email);
        $upd->execute();
        if ($upd->affected_rows > 0) {
            unset($_SESSION['reg_email'], $_SESSION['reg_token']);
            $_SESSION['flash_success'] = 'Akun berhasil diverifikasi! Silakan login.';
            header('Location: login.php'); exit();
        } else {
            $error = 'Verifikasi gagal atau akun sudah aktif.';
        }
        $upd->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verifikasi — UMKM Next</title>
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
.auth-right .glow {
    position:absolute; inset:0;
    background:
        radial-gradient(ellipse 70% 60% at 60% 30%, rgba(16,185,129,0.45) 0%, transparent 60%),
        radial-gradient(ellipse 55% 70% at 30% 70%, rgba(6,182,212,0.35) 0%, transparent 60%),
        radial-gradient(ellipse 45% 45% at 80% 75%, rgba(37,99,235,0.25) 0%, transparent 55%);
    filter:blur(44px);
    animation:glow-shift 9s ease-in-out infinite alternate;
}
@keyframes glow-shift { 0%{transform:scale(1)} 100%{transform:scale(1.05)} }
.dot-grid { position:absolute; inset:0; background-image:radial-gradient(rgba(255,255,255,0.06) 1px,transparent 1px); background-size:34px 34px; }
.overlay { position:absolute; inset:0; background:rgba(6,10,22,0.45); }
.auth-right-content { position:relative; z-index:2; padding:48px 40px; text-align:center; max-width:400px; }

.auth-head { margin:28px 0 24px; }
.step-pill {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.25);
    border-radius:99px; padding:5px 12px;
    font-size:11px; font-weight:700; color:var(--success); margin-bottom:14px;
}
.step-pill em {
    width:18px; height:18px; background:var(--success); border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:10px; color:#fff; font-style:normal; font-weight:800;
}

.email-chip {
    display:inline-flex; align-items:center; gap:7px;
    background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.2);
    border-radius:99px; padding:6px 14px;
    font-size:13px; font-weight:600; color:var(--accent2);
    margin-bottom:24px;
}
.email-chip svg { width:13px; height:13px; }

/* OTP boxes */
.otp-row { display:flex; gap:10px; margin-bottom:8px; }
.otp-box {
    width:52px; height:62px;
    background:var(--bg-glass); border:1.5px solid var(--border);
    border-radius:var(--radius-md);
    font-family:'Syne',sans-serif; font-size:24px; font-weight:800;
    color:var(--text); text-align:center;
    outline:none; text-transform:uppercase;
    transition:border-color var(--tr), box-shadow var(--tr), transform .15s;
}
.otp-box:focus { border-color:var(--accent2); box-shadow:0 0 0 3px rgba(59,130,246,.2); transform:scale(1.06); }
.otp-box.filled { border-color:rgba(16,185,129,.4); background:rgba(16,185,129,.06); }
.otp-box.err    { border-color:rgba(239,68,68,.5); animation:shake .3s; }
@keyframes shake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)} }

.resend-row { font-size:13px; color:var(--text-muted); margin:12px 0 22px; }
.resend-row a { color:var(--accent2); }

/* Demo hint box */
.demo-box {
    background:var(--bg-glass); border:1px solid var(--border);
    border-radius:var(--radius-sm); padding:10px 14px;
    font-size:12px; color:var(--text-muted);
    display:flex; align-items:center; gap:10px; margin-bottom:18px;
}
.demo-box strong { font-family:'Syne',sans-serif; font-size:18px; letter-spacing:4px; color:var(--gold); }

.auth-footer { font-size:11px; color:var(--text-muted); text-align:center; margin-top:24px; line-height:1.8; }

/* flow steps right panel */
.flow-steps { margin-top:32px; text-align:left; }
.flow-step { display:flex; gap:16px; position:relative; }
.flow-step:not(:last-child)::after { content:''; position:absolute; left:16px; top:34px; width:2px; height:calc(100% - 4px); background:linear-gradient(180deg,rgba(16,185,129,0.4),transparent); }
.fs-circle { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-family:'Syne',sans-serif; font-size:12px; font-weight:800; flex-shrink:0; margin-top:2px; }
.fs-circle.done { background:var(--success); color:#fff; }
.fs-circle.now  { background:var(--success); color:#fff; box-shadow:0 0 18px rgba(16,185,129,.6); }
.fs-circle.next { background:rgba(255,255,255,0.06); border:1px solid var(--border); color:var(--text-muted); }
.fs-body { padding-bottom:20px; }
.fs-name { font-family:'Syne',sans-serif; font-size:14px; font-weight:700; }
.fs-name.done { color:var(--success); }
.fs-name.now  { color:#fff; }
.fs-name.next { color:var(--text-muted); }
.fs-desc { font-size:12px; color:var(--text-muted); margin-top:2px; }

@media(max-width:860px){ .auth-right{display:none} .auth-left{width:100%} }
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
      <div class="step-pill"><em>2</em> Langkah 2 dari 4 — Verifikasi Email</div>
      <h1>Cek Email Anda 📬</h1>
      <p class="text-muted">Kami telah mengirim kode verifikasi ke</p>
      <div class="email-chip mt-sm">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <?= htmlspecialchars($email) ?>
      </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/></svg>
      <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- Demo hint — hapus di produksi -->
    <div class="demo-box">
      🧪 Kode demo: <strong><?= htmlspecialchars($otp_demo) ?></strong>
      <span style="font-size:10px">(hapus di production)</span>
    </div>

    <form method="POST" id="otpForm">
      <label class="form-label">Masukkan 6 Digit Kode OTP</label>
      <div class="otp-row">
        <input type="text" name="otp[]" class="otp-box" maxlength="1" autocomplete="off">
        <input type="text" name="otp[]" class="otp-box" maxlength="1" autocomplete="off">
        <input type="text" name="otp[]" class="otp-box" maxlength="1" autocomplete="off">
        <input type="text" name="otp[]" class="otp-box" maxlength="1" autocomplete="off">
        <input type="text" name="otp[]" class="otp-box" maxlength="1" autocomplete="off">
        <input type="text" name="otp[]" class="otp-box" maxlength="1" autocomplete="off">
      </div>

      <div class="resend-row">
        Tidak menerima? <a href="verifikasi.php?resend=1" id="resendLink">Kirim ulang</a>
        <span id="cd" style="font-size:12px"></span>
      </div>

      <button type="submit" class="btn btn-success btn-full btn-lg" id="vBtn">
        ✓ Verifikasi & Aktifkan Akun
      </button>
    </form>

    <a href="register.php" class="btn btn-secondary btn-full mt-sm">← Kembali ke Register</a>

    <div class="auth-footer">&copy; <?= date('Y') ?> UMKM Next · Indonesia 🇮🇩</div>
  </div>

  <!-- RIGHT -->
  <div class="auth-right">
    <div class="glow"></div><div class="dot-grid"></div><div class="overlay"></div>
    <div class="auth-right-content">
      <div style="font-size:72px;margin-bottom:20px;animation:float 3s ease-in-out infinite">📨</div>
      @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
      <h2 style="font-size:2rem;color:#fff;letter-spacing:-1px;margin-bottom:10px">Hampir <span class="text-gradient">Selesai!</span></h2>
      <p style="font-size:13px;color:rgba(255,255,255,.35);margin-bottom:4px">Satu langkah lagi untuk mengaktifkan akun Anda</p>

      <div class="flow-steps">
        <div class="flow-step">
          <div class="fs-circle done">✓</div>
          <div class="fs-body"><div class="fs-name done">Buat Akun</div><div class="fs-desc">Selesai.</div></div>
        </div>
        <div class="flow-step">
          <div class="fs-circle now">2</div>
          <div class="fs-body"><div class="fs-name now">Verifikasi Email</div><div class="fs-desc">Masukkan kode OTP yang dikirim ke email.</div></div>
        </div>
        <div class="flow-step">
          <div class="fs-circle next">3</div>
          <div class="fs-body"><div class="fs-name next">Login</div><div class="fs-desc">Masuk dengan akun yang aktif.</div></div>
        </div>
        <div class="flow-step">
          <div class="fs-circle next">4</div>
          <div class="fs-body"><div class="fs-name next">Profil UMKM</div><div class="fs-desc">Lengkapi data bisnis Anda.</div></div>
        </div>
      </div>
    </div>
  </div>

</div>
<script>
const boxes = document.querySelectorAll('.otp-box');
boxes.forEach((b,i)=>{
    b.addEventListener('input',e=>{
        const v=(e.target.value.replace(/[^a-zA-Z0-9]/g,'')).toUpperCase();
        e.target.value=v.slice(-1);
        e.target.classList.toggle('filled',v.length>0);
        if(v&&i<5)boxes[i+1].focus();
    });
    b.addEventListener('keydown',e=>{
        if(e.key==='Backspace'&&!b.value&&i>0){boxes[i-1].value='';boxes[i-1].classList.remove('filled');boxes[i-1].focus();}
    });
    b.addEventListener('paste',e=>{
        e.preventDefault();
        const p=(e.clipboardData.getData('text')||'').replace(/[^a-zA-Z0-9]/g,'').toUpperCase();
        p.slice(0,6).split('').forEach((ch,idx)=>{if(boxes[idx]){boxes[idx].value=ch;boxes[idx].classList.add('filled');}});
        const last=Math.min(p.length,6)-1;if(last>=0)boxes[last].focus();
    });
});
boxes[0]?.focus();

// Countdown resend
let s=60;const rl=document.getElementById('resendLink'),cd=document.getElementById('cd');
rl.style.cssText='pointer-events:none;opacity:.4';
const t=setInterval(()=>{s--;cd.textContent=`(${s}s)`;if(s<=0){clearInterval(t);cd.textContent='';rl.style.cssText='';}},1000);

document.getElementById('otpForm').addEventListener('submit',()=>{
    const btn=document.getElementById('vBtn');
    btn.classList.add('loading'); btn.textContent='Memverifikasi...';
});
</script>
</body>
</html>