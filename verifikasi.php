<?php
// ================================================================
// verifikasi.php — Halaman Verifikasi Email
// ================================================================
session_start();
require_once 'koneksi.php';

// Sudah login → ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error   = '';
$success = '';
$verified = false;

// ── Verifikasi via link GET (?token=xxx) ──
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
    $stmt  = $koneksi->prepare('SELECT id_user, status_verifikasi FROM users WHERE token_verifikasi = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->bind_result($userId, $statusVer);
    $stmt->fetch();
    $stmt->close();

    if ($userId) {
        if ($statusVer === 'verified') {
            $success  = 'Email sudah terverifikasi sebelumnya. Silakan login.';
            $verified = true;
        } else {
            $upd = $koneksi->prepare('UPDATE users SET status_verifikasi=?, is_verified=1, token_verifikasi=NULL WHERE id_user=?');
            $status = 'verified';
            $upd->bind_param('si', $status, $userId);
            $upd->execute();
            $upd->close();
            $success  = 'Email berhasil diverifikasi! Silakan login sekarang.';
            $verified = true;
        }
    } else {
        $error = 'Token verifikasi tidak valid atau sudah kadaluarsa.';
    }
}

// ── Verifikasi via kode OTP (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode'])) {
    $kode  = trim($_POST['kode']);
    $email = $_SESSION['verify_email'] ?? '';

    if (!$email) {
        $error = 'Sesi verifikasi tidak ditemukan. Silakan daftar ulang.';
    } elseif (!$kode) {
        $error = 'Masukkan kode verifikasi.';
    } else {
        $stmt = $koneksi->prepare('SELECT id_user, token_verifikasi, status_verifikasi FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($userId, $tokenDb, $statusVer);
        $stmt->fetch();
        $stmt->close();

        // Dev mode: token pertama 6 digit hex
        $token6 = substr($tokenDb, 0, 6);

        if ($statusVer === 'verified') {
            $success  = 'Email sudah terverifikasi. Silakan login.';
            $verified = true;
        } elseif ($kode === $token6) {
            $upd = $koneksi->prepare('UPDATE users SET status_verifikasi=?, is_verified=1, token_verifikasi=NULL WHERE id_user=?');
            $status = 'verified';
            $upd->bind_param('si', $status, $userId);
            $upd->execute();
            $upd->close();
            unset($_SESSION['verify_email'], $_SESSION['verify_token']);
            $success  = 'Email berhasil diverifikasi! Mengalihkan ke halaman login...';
            $verified = true;
        } else {
            $error = 'Kode verifikasi salah. Coba lagi.';
        }
    }
}

// ── Resend ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    $email = $_SESSION['verify_email'] ?? '';
    if ($email) {
        $newToken = bin2hex(random_bytes(32));
        $upd = $koneksi->prepare('UPDATE users SET token_verifikasi = ? WHERE email = ?');
        $upd->bind_param('ss', $newToken, $email);
        $upd->execute();
        $upd->close();
        $_SESSION['verify_token'] = $newToken;
        $success = 'Kode verifikasi baru telah dikirim ke email kamu.';
    }
}

$devToken = '';
if (isset($_SESSION['verify_token'])) {
    $devToken = substr($_SESSION['verify_token'], 0, 6);
}
$verifyEmail = $_SESSION['verify_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Verifikasi Email — UMKM Next</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      display: flex; min-height: 100vh;
      align-items: center; justify-content: center;
      padding: 20px;
    }
    .verify-card {
      width: 100%; max-width: 460px;
      background: var(--bg-panel);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 44px 40px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .verify-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 2px;
      background: linear-gradient(90deg, var(--accent), var(--teal));
    }
    .verify-icon {
      width: 72px; height: 72px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 32px; margin: 0 auto 22px;
    }
    .verify-icon.pending { background: rgba(37,99,235,.12); border: 1.5px solid rgba(37,99,235,.25); }
    .verify-icon.ok      { background: rgba(16,185,129,.12); border: 1.5px solid rgba(16,185,129,.25); animation: popIn .5s cubic-bezier(.175,.885,.32,1.275); }
    @keyframes popIn {
      from{transform:scale(.5);opacity:0} to{transform:scale(1);opacity:1}
    }
    h2 { font-size: 1.4rem; margin-bottom: 8px; }
    .verify-sub { color: var(--text-muted); font-size: 13px; line-height: 1.6; margin-bottom: 28px; }
    .email-chip {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(59,130,246,.1); border: 1px solid rgba(59,130,246,.2);
      border-radius: var(--radius-full); padding: 5px 14px;
      font-size: 13px; font-weight: 600; color: var(--accent2);
      margin-bottom: 24px;
    }

    /* OTP Input */
    .otp-wrap { display: flex; gap: 10px; justify-content: center; margin-bottom: 20px; }
    .otp-input {
      width: 52px; height: 58px;
      background: var(--bg-glass);
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      text-align: center;
      font-family: 'Syne', sans-serif;
      font-size: 22px; font-weight: 800;
      color: var(--text); outline: none;
      transition: border-color var(--tr), box-shadow var(--tr);
    }
    .otp-input:focus {
      border-color: var(--accent2);
      box-shadow: 0 0 0 3px rgba(59,130,246,.15);
    }
    .otp-input.filled { border-color: rgba(16,185,129,.4); }

    .alert {
      padding: 12px 16px; border-radius: var(--radius-sm);
      font-size: 13px; margin-bottom: 16px;
      display: flex; align-items: center; gap: 8px;
    }
    .alert.error   { background: rgba(239,68,68,.12);  border: 1px solid rgba(239,68,68,.25);  color: #fca5a5; }
    .alert.success { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.25); color: #6ee7b7; }

    .dev-hint {
      background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.2);
      border-radius: var(--radius-sm); padding: 10px 14px;
      font-size: 12px; color: var(--gold); margin-bottom: 20px; text-align: left;
    }
    .btn-full { width: 100%; padding: 13px; }
    .resend-row { margin-top: 16px; font-size: 13px; color: var(--text-muted); }
    .resend-row button {
      background: none; border: none; color: var(--accent2);
      cursor: pointer; font-weight: 600; font-size: 13px;
      transition: color var(--tr);
    }
    .resend-row button:hover { color: var(--teal); }
    .back-link { margin-top: 20px; font-size: 13px; color: var(--text-muted); display: block; }
    .back-link a { color: var(--accent2); }
  </style>
</head>
<body>

<div class="verify-card">

  <?php if ($verified): ?>
  <!-- ── SUCCESS STATE ── -->
  <div class="verify-icon ok">✅</div>
  <h2>Email Terverifikasi!</h2>
  <p class="verify-sub">Akun kamu sudah aktif dan siap digunakan.</p>
  <?php if ($success): ?>
  <div class="alert success">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?= htmlspecialchars($success) ?>
  </div>
  <?php endif; ?>
  <a href="login.php" class="btn btn-success btn-full">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
    Masuk ke Akun
  </a>
  <script>
    // Auto redirect ke login setelah 3 detik jika ada pesan berhasil verifikasi baru
    <?php if (strpos($success, 'Mengalihkan') !== false): ?>
    setTimeout(() => window.location.href = 'login.php', 2000);
    <?php endif; ?>
  </script>

  <?php else: ?>
  <!-- ── VERIFY STATE ── -->
  <div class="verify-icon pending">📧</div>
  <h2>Verifikasi Email Anda</h2>
  <p class="verify-sub">Masukkan kode 6 digit yang telah dikirim ke email Anda.</p>

  <?php if ($verifyEmail): ?>
  <div class="email-chip">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
    <?= htmlspecialchars($verifyEmail) ?>
  </div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="alert error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <?php if ($success): ?>
  <div class="alert success">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?= htmlspecialchars($success) ?>
  </div>
  <?php endif; ?>

  <?php if ($devToken): ?>
  <div class="dev-hint">
    <strong>🔧 Mode Dev:</strong> Kode verifikasi kamu adalah
    <strong style="font-size:16px;letter-spacing:2px;"> <?= htmlspecialchars($devToken) ?></strong><br>
    <small>(Hapus hint ini di production)</small>
  </div>
  <?php endif; ?>

  <form method="POST" action="verifikasi.php" id="otpForm">
    <div class="otp-wrap">
      <?php for ($i = 1; $i <= 6; $i++): ?>
      <input type="text" class="otp-input" id="otp<?= $i ?>"
             maxlength="1" pattern="[0-9a-fA-F]"
             inputmode="text" autocomplete="off"
             onkeyup="otpNav(event, <?= $i ?>)"
             onpaste="<?= $i === 1 ? 'handlePaste(event)' : '' ?>">
      <?php endfor; ?>
    </div>
    <input type="hidden" name="kode" id="hiddenKode">

    <button type="submit" class="btn btn-primary btn-full" id="btnVerif" onclick="collectOtp()">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
      Verifikasi Sekarang
    </button>
  </form>

  <div class="resend-row">
    Tidak menerima kode?
    <form method="POST" style="display:inline;">
      <input type="hidden" name="resend" value="1">
      <button type="submit">Kirim ulang</button>
    </form>
  </div>

  <span class="back-link">
    <a href="register.php">← Kembali ke Daftar</a> &nbsp;·&nbsp;
    <a href="login.php">Sudah punya akun?</a>
  </span>
  <?php endif; ?>

</div>

<script>
function otpNav(e, idx) {
  const cur = document.getElementById('otp' + idx);
  cur.classList.toggle('filled', cur.value.length > 0);
  if (e.key === 'Backspace' && cur.value === '' && idx > 1) {
    document.getElementById('otp' + (idx - 1)).focus();
  } else if (cur.value && idx < 6) {
    document.getElementById('otp' + (idx + 1)).focus();
  }
}

function collectOtp() {
  let kode = '';
  for (let i = 1; i <= 6; i++) {
    kode += document.getElementById('otp' + i).value;
  }
  document.getElementById('hiddenKode').value = kode;
}

function handlePaste(e) {
  e.preventDefault();
  const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\s/g,'');
  for (let i = 0; i < Math.min(text.length, 6); i++) {
    const inp = document.getElementById('otp' + (i + 1));
    if (inp) { inp.value = text[i]; inp.classList.add('filled'); }
  }
  document.getElementById('otp6').focus();
}

// Auto focus ke input pertama
document.getElementById('otp1').focus();
</script>

</body>
</html>