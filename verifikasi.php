<?php
session_start();
require_once 'koneksi.php';

// Sudah login → dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Belum register → kembali ke register
if (!isset($_SESSION['reg_email'])) {
    header('Location: register.php');
    exit();
}

$email   = $_SESSION['reg_email'];
$token   = $_SESSION['reg_token'] ?? ''; // produksi: kirim via SMTP, jangan simpan di session
$error   = '';
$success = '';

// ── Simulasi OTP 6 digit dari token ──────────────────────────
// Produksi: generate OTP terpisah, kirim via email (PHPMailer/SMTP)
// Di sini kita pakai 6 digit pertama dari hash token sebagai OTP demo
$otp_demo = strtoupper(substr($token, 0, 6));

// ── Handle resend (demo) ──────────────────────────────────────
if (isset($_GET['resend'])) {
    // Produksi: regenerate token, update DB, kirim ulang email
    $success = 'Kode verifikasi telah dikirim ulang ke ' . $email;
}

// ── Handle submit OTP ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = strtoupper(trim(implode('', $_POST['otp'] ?? [])));

    if (empty($input_otp) || strlen($input_otp) < 6) {
        $error = 'Masukkan 6 digit kode verifikasi.';
    } elseif ($input_otp !== $otp_demo) {
        // Produksi: bandingkan dengan token di DB + cek expiry
        $error = 'Kode verifikasi salah. Periksa kembali atau minta kode baru.';
    } else {
        // ── Aktifkan akun di database ──────────────────────────
        $upd = $koneksi->prepare(
            'UPDATE users SET is_verified = 1, token_verifikasi = NULL WHERE email = ? AND is_verified = 0'
        );
        $upd->bind_param('s', $email);
        $upd->execute();

        if ($upd->affected_rows > 0) {
            // Bersihkan session register
            unset($_SESSION['reg_email'], $_SESSION['reg_token']);

            // Set flash message untuk login
            $_SESSION['flash_success'] = 'Akun berhasil diverifikasi! Silakan login.';
            header('Location: login.php');
            exit();
        } else {
            $error = 'Verifikasi gagal atau akun sudah aktif. Silakan login.';
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
    <title>Verifikasi Akun — UMKM Next</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --navy:   #0b1120; --panel:  #131f35; --border: rgba(255,255,255,0.07);
            --accent: #2563eb; --accent2:#3b82f6; --teal: #06b6d4;
            --text:   #e2e8f0; --muted:  #64748b; --danger: #ef4444; --success:#10b981;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy); color: var(--text);
            min-height: 100vh; display: flex; overflow: hidden;
        }
        body::before {
            content: ''; position: fixed; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none; z-index: 0;
        }

        .left {
            position: relative; z-index: 1;
            width: 480px; min-width: 360px;
            background: var(--panel); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 36px 44px; flex-shrink: 0;
        }

        .logo { display: flex; align-items: center; gap: 12px; }
        .logo-mark {
            width: 44px; height: 44px; background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.15); border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(99,51,220,0.35);
        }
        .logo-mark svg { width: 26px; height: 26px; }
        .logo-text strong { font-family:'Syne',sans-serif; font-size:18px; font-weight:800; display:block; letter-spacing:-.5px; }
        .logo-text span   { font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:2px; }

        .form-area { flex:1; display:flex; flex-direction:column; justify-content:center; padding:24px 0; }

        .step-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(37,99,235,0.12); border: 1px solid rgba(37,99,235,0.25);
            border-radius: 20px; padding: 5px 12px;
            font-size: 11px; color: var(--accent2); font-weight: 600; margin-bottom: 20px;
        }
        .step-badge span {
            width:18px; height:18px; background:var(--accent); border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:10px; color:#fff; font-weight:800;
        }

        h1 { font-family:'Syne',sans-serif; font-size:28px; font-weight:800; letter-spacing:-1px; margin-bottom:6px; }
        .sub { color:var(--muted); font-size:13px; margin-bottom:6px; }
        .email-chip {
            display:inline-flex; align-items:center; gap:6px;
            background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.2);
            border-radius:20px; padding:4px 12px;
            font-size:13px; color:var(--accent2); font-weight:600; margin-bottom:28px;
        }
        .email-chip svg { width:13px; height:13px; }

        /* Alert */
        .alert {
            padding:12px 16px; border-radius:10px; font-size:13px;
            margin-bottom:20px; display:flex; align-items:center; gap:10px;
            animation:fadeIn .3s ease;
        }
        .alert svg { width:16px; height:16px; flex-shrink:0; }
        .alert.error   { background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.25); color:#fca5a5; }
        .alert.success { background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.25); color:#6ee7b7; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:none} }

        /* OTP Input */
        .otp-label {
            font-size:11px; font-weight:600; color:var(--muted);
            text-transform:uppercase; letter-spacing:1.2px; margin-bottom:14px;
        }
        .otp-wrap {
            display: flex; gap: 10px; margin-bottom: 8px;
        }
        .otp-wrap input {
            width: 52px; height: 60px;
            background: rgba(255,255,255,0.04);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-family: 'Syne', sans-serif;
            font-size: 24px; font-weight: 800;
            color: var(--text); text-align: center;
            outline: none;
            transition: border-color .2s, box-shadow .2s, transform .15s;
            text-transform: uppercase;
            caret-color: var(--accent2);
        }
        .otp-wrap input:focus {
            border-color: var(--accent2);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
            transform: scale(1.05);
        }
        .otp-wrap input.filled {
            border-color: rgba(16,185,129,0.4);
            background: rgba(16,185,129,0.06);
        }
        .otp-wrap input.error-otp {
            border-color: rgba(239,68,68,0.5);
            background: rgba(239,68,68,0.06);
            animation: shake .3s ease;
        }
        @keyframes shake {
            0%,100%{transform:translateX(0)} 25%{transform:translateX(-4px)} 75%{transform:translateX(4px)}
        }

        /* Demo hint */
        .demo-hint {
            font-size: 12px; color: var(--muted);
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 8px; padding: 10px 14px;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .demo-hint strong { color: var(--gold, #f59e0b); font-family: 'Syne', sans-serif; font-size: 15px; letter-spacing: 3px; }

        /* Resend */
        .resend-row {
            font-size: 13px; color: var(--muted); margin-bottom: 24px;
        }
        .resend-row a { color: var(--accent2); text-decoration: none; cursor: pointer; }
        .resend-row a:hover { text-decoration: underline; }
        #countdown { color: var(--muted); font-size: 12px; }

        /* Submit */
        .btn-verify {
            width: 100%;
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            border: none; border-radius: 10px;
            padding: 14px; font-family: 'Syne', sans-serif;
            font-size: 15px; font-weight: 700; color: #fff;
            cursor: pointer; margin-top: 4px;
            transition: transform .15s, box-shadow .2s, opacity .2s;
            box-shadow: 0 4px 24px rgba(16,185,129,0.35);
        }
        .btn-verify:hover { transform: translateY(-1px); box-shadow: 0 6px 32px rgba(16,185,129,.5); }
        .btn-verify.loading { opacity:.7; pointer-events:none; }

        .back-row { text-align:center; font-size:13px; color:var(--muted); margin-top:16px; }
        .link { color:var(--accent2); text-decoration:none; }
        .link:hover { text-decoration:underline; }

        .left-footer { font-size:11px; color:var(--muted); text-align:center; line-height:1.8; }

        /* RIGHT */
        .right {
            flex:1; position:relative; overflow:hidden;
            display:flex; flex-direction:column; justify-content:center; align-items:center;
            background:#0a0f1e;
        }
        .swirl {
            position:absolute; inset:0;
            background:
                radial-gradient(ellipse 70% 70% at 60% 30%, rgba(16,185,129,0.4) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 30% 70%, rgba(6,182,212,0.35) 0%, transparent 60%),
                radial-gradient(ellipse 50% 50% at 80% 80%, rgba(37,99,235,0.25) 0%, transparent 55%);
            animation:swirl-shift 10s ease-in-out infinite alternate;
        }
        @keyframes swirl-shift {
            0%{filter:blur(40px);transform:scale(1)} 100%{filter:blur(50px);transform:scale(1.04)}
        }
        .right::after {
            content:''; position:absolute; inset:0;
            background:rgba(8,12,28,0.5); pointer-events:none; z-index:1;
        }
        .dot-grid {
            position:absolute; inset:0; z-index:2;
            background-image:radial-gradient(rgba(255,255,255,0.07) 1px, transparent 1px);
            background-size:36px 36px;
        }
        .right-content { position:relative; z-index:3; text-align:center; padding:48px 40px; max-width:420px; }

        /* Email illustration */
        .email-illus {
            width: 100px; height: 100px; border-radius: 24px;
            background: rgba(16,185,129,0.12);
            border: 1.5px solid rgba(16,185,129,0.25);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 28px;
            font-size: 48px;
            animation: float 3s ease-in-out infinite;
            box-shadow: 0 8px 40px rgba(16,185,129,0.2);
        }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }

        .right-title { font-family:'Syne',sans-serif; font-size:30px; font-weight:800; letter-spacing:-1.5px; color:#fff; margin-bottom:12px; }
        .right-title span { background:linear-gradient(135deg,#34d399,#06b6d4); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .right-sub { font-size:13px; color:rgba(255,255,255,0.35); line-height:1.7; }

        /* Flow steps */
        .flow-steps { display:flex; flex-direction:column; gap:0; text-align:left; margin-top:32px; }
        .flow-step { display:flex; gap:16px; position:relative; }
        .flow-step:not(:last-child)::after {
            content:''; position:absolute; left:17px; top:36px;
            width:2px; height:calc(100% - 8px);
            background:linear-gradient(180deg,rgba(16,185,129,0.4),rgba(6,182,212,0.1));
        }
        .fs-num { width:34px; height:34px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-family:'Syne',sans-serif; font-size:13px; font-weight:800; margin-top:2px; }
        .fs-num.done    { background:var(--success); color:#fff; }
        .fs-num.current { background:var(--success); color:#fff; box-shadow:0 0 20px rgba(16,185,129,.6); }
        .fs-num.upcoming { background:rgba(255,255,255,0.06); border:1.5px solid rgba(255,255,255,0.12); color:var(--muted); }
        .fs-body { padding-bottom:24px; }
        .fs-title { font-family:'Syne',sans-serif; font-size:14px; font-weight:700; margin-bottom:2px; }
        .fs-title.done { color:var(--success); }
        .fs-title.current { color:#fff; }
        .fs-title.upcoming { color:var(--muted); }
        .fs-desc { font-size:12px; color:var(--muted); line-height:1.5; }

        @media (max-width:900px) { .right{display:none} .left{width:100%} }
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
        <div class="step-badge">
            <span>2</span> Langkah 2 dari 3 — Verifikasi Email
        </div>
        <h1>Cek Email Anda 📬</h1>
        <p class="sub">Kami telah mengirim kode verifikasi ke</p>
        <div class="email-chip">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <?= htmlspecialchars($email) ?>
        </div>

        <?php if ($error): ?>
        <div class="alert error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert success">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <!-- Demo hint — hapus di produksi -->
        <div class="demo-hint">
            🧪 Kode demo Anda: <strong><?= htmlspecialchars($otp_demo) ?></strong>
            <span style="font-size:10px;color:var(--muted)">(hapus di produksi)</span>
        </div>

        <form method="POST" id="otpForm">
            <div class="otp-label">Masukkan 6 Digit Kode Verifikasi</div>
            <div class="otp-wrap">
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="text" autocomplete="off">
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="text" autocomplete="off">
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="text" autocomplete="off">
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="text" autocomplete="off">
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="text" autocomplete="off">
                <input type="text" name="otp[]" class="otp-box" maxlength="1" inputmode="text" autocomplete="off">
            </div>

            <div class="resend-row">
                Tidak menerima kode?
                <a href="verifikasi.php?resend=1" id="resendLink">Kirim ulang</a>
                <span id="countdown"></span>
            </div>

            <button type="submit" class="btn-verify" id="verifyBtn">
                ✓ Verifikasi & Aktifkan Akun
            </button>
        </form>

        <div class="back-row">
            <a href="register.php" class="link">← Kembali ke halaman daftar</a>
        </div>
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
        <div class="email-illus">📨</div>
        <div class="right-title">Hampir <span>Selesai!</span></div>
        <p class="right-sub">Verifikasi email memastikan keamanan akun dan mencegah penyalahgunaan.</p>

        <div class="flow-steps">
            <div class="flow-step">
                <div class="fs-num done">✓</div>
                <div class="fs-body">
                    <div class="fs-title done">Buat Akun</div>
                    <div class="fs-desc">Akun berhasil dibuat.</div>
                </div>
            </div>
            <div class="flow-step">
                <div class="fs-num current">2</div>
                <div class="fs-body">
                    <div class="fs-title current">Verifikasi Email</div>
                    <div class="fs-desc">Masukkan kode OTP yang dikirim ke email Anda.</div>
                </div>
            </div>
            <div class="flow-step">
                <div class="fs-num upcoming">3</div>
                <div class="fs-body">
                    <div class="fs-title upcoming">Lengkapi Profil UMKM</div>
                    <div class="fs-desc">Isi data bisnis untuk mengaktifkan semua fitur.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ── OTP Input auto-jump ──────────────────────────────────────
const boxes = document.querySelectorAll('.otp-box');
boxes.forEach((box, i) => {
    box.addEventListener('input', e => {
        const val = e.target.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
        e.target.value = val.slice(-1);
        e.target.classList.toggle('filled', val.length > 0);
        if (val && i < boxes.length - 1) boxes[i + 1].focus();
        checkAllFilled();
    });

    box.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !box.value && i > 0) {
            boxes[i - 1].value = '';
            boxes[i - 1].classList.remove('filled');
            boxes[i - 1].focus();
        }
        if (e.key === 'ArrowLeft' && i > 0) boxes[i - 1].focus();
        if (e.key === 'ArrowRight' && i < boxes.length - 1) boxes[i + 1].focus();
    });

    // Paste handler
    box.addEventListener('paste', e => {
        e.preventDefault();
        const pasted = (e.clipboardData.getData('text') || '').replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
        pasted.slice(0, 6).split('').forEach((ch, idx) => {
            if (boxes[idx]) {
                boxes[idx].value = ch;
                boxes[idx].classList.add('filled');
            }
        });
        const last = Math.min(pasted.length, boxes.length) - 1;
        if (last >= 0) boxes[last].focus();
        checkAllFilled();
    });
});

function checkAllFilled() {
    const all = [...boxes].every(b => b.value.length === 1);
    document.getElementById('verifyBtn').style.opacity = all ? '1' : '0.7';
}

// Focus first box
boxes[0]?.focus();

// ── Countdown resend ─────────────────────────────────────────
let seconds = 60;
const resendLink = document.getElementById('resendLink');
const countdown  = document.getElementById('countdown');
resendLink.style.pointerEvents = 'none';
resendLink.style.opacity = '0.4';

const timer = setInterval(() => {
    seconds--;
    countdown.textContent = `(${seconds}s)`;
    if (seconds <= 0) {
        clearInterval(timer);
        countdown.textContent = '';
        resendLink.style.pointerEvents = '';
        resendLink.style.opacity = '1';
    }
}, 1000);

// ── Submit loading ────────────────────────────────────────────
document.getElementById('otpForm').addEventListener('submit', function() {
    const allFilled = [...boxes].every(b => b.value.length === 1);
    if (!allFilled) { return false; }
    const btn = document.getElementById('verifyBtn');
    btn.classList.add('loading');
    btn.textContent = 'Memverifikasi...';
});
</script>
</body>
</html>