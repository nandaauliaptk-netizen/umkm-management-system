<?php
session_start();
require_once 'koneksi.php';

// Sudah login → dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $konfirm  = trim($_POST['konfirm']  ?? '');

    // ── Validasi dasar ──────────────────────────────────────────
    if ($nama === '' || $email === '' || $password === '' || $konfirm === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal 8 karakter.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        // Cek email duplikat
        $cek = $koneksi->prepare('SELECT id_user FROM users WHERE email = ?');
        $cek->bind_param('s', $email);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
        } else {
            // ── Buat token OTP 6 karakter ────────────────────────
            $token  = strtoupper(bin2hex(random_bytes(3))); // 6 hex chars
            $hash   = password_hash($password, PASSWORD_DEFAULT);

            // ── Simpan ke database ────────────────────────────────
            // Kolom: nama, email, password, status_verifikasi, token_verifikasi, is_verified
            $ins = $koneksi->prepare(
                'INSERT INTO users (nama, email, password, status_verifikasi, token_verifikasi, is_verified)
                 VALUES (?, ?, ?, "belum", ?, 0)'
            );
            $ins->bind_param('ssss', $nama, $email, $hash, $token);

            if ($ins->execute()) {
                // Simpan di session untuk halaman verifikasi
                $_SESSION['reg_email'] = $email;
                $_SESSION['reg_token'] = $token;

                // Produksi: kirim email via PHPMailer/SMTP di sini
                // mail($email, 'Kode Verifikasi UMKM Next', "Kode OTP kamu: $token");

                header('Location: verifikasi.php');
                exit();
            } else {
                $error = 'Terjadi kesalahan saat mendaftar. Coba lagi.';
            }
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
<title>Daftar Akun — UMKM Next</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --navy:#0b1120;--panel:#131f35;--border:rgba(255,255,255,0.07);
    --accent:#2563eb;--accent2:#3b82f6;--teal:#06b6d4;
    --text:#e2e8f0;--muted:#64748b;--danger:#ef4444;--success:#10b981;
    --gold:#f59e0b;
}
body{font-family:'DM Sans',sans-serif;background:var(--navy);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");pointer-events:none;z-index:0}

/* ── LEFT PANEL ── */
.left{position:relative;z-index:1;width:480px;min-width:360px;background:var(--panel);border-right:1px solid var(--border);display:flex;flex-direction:column;justify-content:space-between;padding:36px 44px;flex-shrink:0}
.logo{display:flex;align-items:center;gap:12px}
.logo-mark{width:44px;height:44px;background:rgba(255,255,255,0.08);border:1.5px solid rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(99,51,220,0.35)}
.logo-mark svg{width:26px;height:26px}
.logo-text strong{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;display:block;letter-spacing:-.5px}
.logo-text span{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:2px}
.form-area{flex:1;display:flex;flex-direction:column;justify-content:center;padding:24px 0}
.step-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(37,99,235,0.12);border:1px solid rgba(37,99,235,0.25);border-radius:20px;padding:5px 12px;font-size:11px;color:var(--accent2);font-weight:600;margin-bottom:20px;width:fit-content}
.step-badge span{width:18px;height:18px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff;font-weight:800}
h1{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;letter-spacing:-1px;margin-bottom:6px}
.sub{color:var(--muted);font-size:13px;margin-bottom:28px}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px;animation:fadeIn .3s ease}
.alert svg{width:16px;height:16px;flex-shrink:0}
.alert.error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#fca5a5}
.alert.success{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
@keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.form-group{margin-bottom:16px}
label{display:block;font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:8px}
.input-wrap{position:relative}
.input-wrap svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--muted);pointer-events:none}
input[type=text],input[type=email],input[type=password]{width:100%;padding:12px 14px 12px 42px;background:rgba(255,255,255,0.04);border:1.5px solid var(--border);border-radius:10px;font-size:14px;color:var(--text);font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s}
input:focus{border-color:var(--accent2);box-shadow:0 0 0 3px rgba(59,130,246,0.15)}
input.valid{border-color:rgba(16,185,129,0.4)}
input.invalid{border-color:rgba(239,68,68,0.4)}
.hint{font-size:11px;color:var(--muted);margin-top:5px}
.pw-strength{height:3px;border-radius:99px;margin-top:6px;transition:all .3s;background:var(--border)}
.pw-strength.w1{width:25%;background:#ef4444}
.pw-strength.w2{width:50%;background:#f59e0b}
.pw-strength.w3{width:75%;background:#3b82f6}
.pw-strength.w4{width:100%;background:#10b981}
.toggle-pw{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);display:flex;align-items:center;padding:4px}
.toggle-pw:hover{color:var(--text)}
.btn-submit{width:100%;padding:14px;background:var(--accent);border:none;border-radius:12px;color:#fff;font-size:15px;font-weight:700;font-family:'Syne',sans-serif;cursor:pointer;transition:all .2s;margin-top:8px;letter-spacing:.3px;position:relative;overflow:hidden}
.btn-submit:hover{background:#1d4ed8;box-shadow:0 4px 20px rgba(37,99,235,0.5);transform:translateY(-1px)}
.btn-submit:active{transform:none}
.btn-submit.loading{opacity:.8;pointer-events:none}
.divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:var(--muted);font-size:12px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}
.login-link{text-align:center;font-size:13px;color:var(--muted)}
.login-link a{color:var(--accent2);text-decoration:none;font-weight:600}
.login-link a:hover{text-decoration:underline}
.left-footer{font-size:11px;color:var(--muted);line-height:1.6}

/* ── RIGHT PANEL ── */
.right{flex:1;position:relative;z-index:1;display:flex;align-items:center;justify-content:center;overflow:hidden}
.swirl{position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(37,99,235,0.15) 0%,transparent 65%);top:50%;left:50%;transform:translate(-50%,-50%)}
.dot-grid{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,0.06) 1px,transparent 1px);background-size:28px 28px}
.right-content{position:relative;z-index:2;max-width:420px;padding:40px;text-align:center}
.right-emoji{font-size:56px;margin-bottom:20px;display:block;animation:float 3s ease-in-out infinite}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.right-title{font-family:'Syne',sans-serif;font-size:32px;font-weight:800;letter-spacing:-1px;line-height:1.1;margin-bottom:14px}
.right-title span{background:linear-gradient(135deg,#60a5fa,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.right-sub{color:var(--muted);font-size:14px;line-height:1.7;margin-bottom:28px}
.flow-steps{display:flex;flex-direction:column;gap:14px;text-align:left}
.flow-step{display:flex;align-items:flex-start;gap:14px;padding:12px 16px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:12px}
.fs-num{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;font-family:'Syne',sans-serif}
.fs-num.current{background:var(--accent);color:#fff;box-shadow:0 0 12px rgba(37,99,235,0.5)}
.fs-num.done{background:rgba(16,185,129,0.15);color:var(--success);border:1.5px solid rgba(16,185,129,0.3)}
.fs-num.upcoming{background:rgba(255,255,255,0.06);color:var(--muted);border:1.5px solid var(--border)}
.fs-title{font-size:14px;font-weight:600;margin-bottom:2px}
.fs-title.current{color:var(--accent2)}
.fs-title.done{color:var(--success)}
.fs-title.upcoming{color:var(--muted)}
.fs-desc{font-size:12px;color:var(--muted);line-height:1.5}
</style>
</head>
<body>

<!-- ═══════════════ LEFT ═══════════════ -->
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
        <div class="step-badge"><span>1</span> Langkah 1 dari 3 — Buat Akun</div>
        <h1>Daftar Sekarang 🚀</h1>
        <p class="sub">Buat akun gratis dan mulai kelola UMKM kamu secara digital.</p>

        <?php if ($error): ?>
        <div class="alert error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="regForm" novalidate>
            <!-- Nama -->
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <div class="input-wrap">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <input type="text" id="nama" name="nama" placeholder="Nama lengkap Anda" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <div class="input-wrap">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <input type="email" id="email" name="email" placeholder="email@contoh.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <div class="input-wrap">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('password','eyeIcon1')">
                        <svg id="eyeIcon1" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                <div class="pw-strength" id="pwStrength"></div>
                <p class="hint" id="pwHint">Gunakan huruf, angka, dan simbol untuk kata sandi yang kuat</p>
            </div>

            <!-- Konfirmasi Password -->
            <div class="form-group">
                <label for="konfirm">Konfirmasi Kata Sandi</label>
                <div class="input-wrap">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <input type="password" id="konfirm" name="konfirm" placeholder="Ulangi kata sandi" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('konfirm','eyeIcon2')">
                        <svg id="eyeIcon2" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                <p class="hint" id="matchHint"></p>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                Buat Akun & Lanjut Verifikasi →
            </button>
        </form>

        <div class="divider">atau</div>
        <div class="login-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
    </div>

    <div class="left-footer">
        &copy; <?= date('Y') ?> UMKM Next Management System<br>
        Dibuat untuk kemajuan UMKM Indonesia 🇮🇩
    </div>
</div>

<!-- ═══════════════ RIGHT ═══════════════ -->
<div class="right">
    <div class="swirl"></div>
    <div class="dot-grid"></div>
    <div class="right-content">
        <span class="right-emoji">🚀</span>
        <div class="right-title">Kelola UMKM<br><span>Lebih Cerdas</span></div>
        <p class="right-sub">Daftar gratis dan dapatkan akses ke dashboard keuangan, manajemen produk, invoice digital, dan laporan usaha secara real-time.</p>

        <div class="flow-steps">
            <div class="flow-step">
                <div class="fs-num current">1</div>
                <div class="fs-body">
                    <div class="fs-title current">Buat Akun</div>
                    <div class="fs-desc">Isi data dasar untuk membuat akun UMKM Next kamu.</div>
                </div>
            </div>
            <div class="flow-step">
                <div class="fs-num upcoming">2</div>
                <div class="fs-body">
                    <div class="fs-title upcoming">Verifikasi Email</div>
                    <div class="fs-desc">Masukkan kode OTP yang dikirim ke email kamu.</div>
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
// ── Toggle password visibility ─────────────────────────────────
function togglePw(id, iconId) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    document.getElementById(iconId).style.opacity = isHidden ? '0.5' : '1';
}

// ── Password strength meter ────────────────────────────────────
const pwInput    = document.getElementById('password');
const pwStrength = document.getElementById('pwStrength');
const pwHint     = document.getElementById('pwHint');

pwInput.addEventListener('input', () => {
    const val = pwInput.value;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    pwStrength.className = 'pw-strength';
    const labels = ['', 'Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];
    if (score > 0) {
        pwStrength.classList.add('w' + score);
        pwHint.textContent = labels[score];
        pwHint.style.color = ['','#ef4444','#f59e0b','#3b82f6','#10b981'][score];
    } else {
        pwHint.textContent = 'Gunakan huruf, angka, dan simbol';
        pwHint.style.color = '';
    }
    checkMatch();
});

// ── Konfirmasi password match ──────────────────────────────────
const cfInput   = document.getElementById('konfirm');
const matchHint = document.getElementById('matchHint');

function checkMatch() {
    if (!cfInput.value) return;
    const match = pwInput.value === cfInput.value;
    matchHint.textContent = match ? '✓ Kata sandi cocok' : '✗ Kata sandi tidak cocok';
    matchHint.style.color = match ? '#10b981' : '#ef4444';
    cfInput.className = match ? 'valid' : 'invalid';
}
cfInput.addEventListener('input', checkMatch);

// ── Submit loading ─────────────────────────────────────────────
document.getElementById('regForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    btn.classList.add('loading');
    btn.textContent = 'Memproses...';
});
</script>
</body>
</html>