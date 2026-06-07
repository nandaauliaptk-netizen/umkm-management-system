<?php
session_start();
require_once 'koneksi.php';

// Belum login → ke login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Sudah lengkapi profil → ke dashboard
if (!empty($_SESSION['profil_dilengkapi'])) {
    header('Location: index.php');
    exit();
}

$id_user = $_SESSION['user_id'];
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_usaha   = trim($_POST['nama_usaha']   ?? '');
    $jenis_usaha  = trim($_POST['jenis_usaha']  ?? '');
    $alamat       = trim($_POST['alamat']        ?? '');
    $telepon      = trim($_POST['telepon']       ?? '');
    $deskripsi    = trim($_POST['deskripsi']     ?? '');
    $tahun_berdiri= trim($_POST['tahun_berdiri'] ?? '');

    if ($nama_usaha === '' || $jenis_usaha === '' || $alamat === '' || $telepon === '') {
        $error = 'Nama usaha, jenis usaha, alamat, dan telepon wajib diisi.';
    } else {
        // Cek apakah sudah ada data umkm untuk user ini
        $cek = $koneksi->prepare('SELECT id_umkm FROM umkm WHERE id_user = ?');
        $cek->bind_param('i', $id_user);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            // Update
            $stmt = $koneksi->prepare('UPDATE umkm SET nama_usaha=?, jenis_usaha=?, alamat=?, telepon=?, deskripsi=?, tahun_berdiri=? WHERE id_user=?');
            $stmt->bind_param('ssssssi', $nama_usaha, $jenis_usaha, $alamat, $telepon, $deskripsi, $tahun_berdiri, $id_user);
        } else {
            // Insert
            $stmt = $koneksi->prepare('INSERT INTO umkm (id_user, nama_usaha, jenis_usaha, alamat, telepon, deskripsi, tahun_berdiri) VALUES (?,?,?,?,?,?,?)');
            $stmt->bind_param('issssss', $id_user, $nama_usaha, $jenis_usaha, $alamat, $telepon, $deskripsi, $tahun_berdiri);
        }
        $cek->close();

        if ($stmt->execute()) {
            // Tandai profil sudah dilengkapi
            $upd = $koneksi->prepare('UPDATE users SET profil_dilengkapi = 1 WHERE id_user = ?');
            $upd->bind_param('i', $id_user);
            $upd->execute();
            $upd->close();

            $_SESSION['profil_dilengkapi'] = 1;
            $_SESSION['flash_welcome']     = true;
            header('Location: index.php');
            exit();
        } else {
            $error = 'Gagal menyimpan profil. Silakan coba lagi.';
        }
        $stmt->close();
    }
}

// Ambil nama user
$stmtN = $koneksi->prepare('SELECT nama FROM users WHERE id_user = ?');
$stmtN->bind_param('i', $id_user);
$stmtN->execute();
$stmtN->bind_result($namaUser);
$stmtN->fetch();
$stmtN->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lengkapi Profil UMKM — UMKM Next</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--navy:#0b1120;--panel:#131f35;--surface:#111827;--border:rgba(255,255,255,0.07);--accent:#2563eb;--accent2:#3b82f6;--teal:#06b6d4;--text:#e2e8f0;--muted:#64748b;--danger:#ef4444;--success:#10b981;--gold:#f59e0b}
body{font-family:'DM Sans',sans-serif;background:var(--navy);color:var(--text);min-height:100vh;display:flex;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");pointer-events:none;z-index:0}
.left{position:relative;z-index:1;width:500px;min-width:380px;background:var(--panel);border-right:1px solid var(--border);display:flex;flex-direction:column;justify-content:space-between;padding:36px 44px;flex-shrink:0;overflow-y:auto}
.logo{display:flex;align-items:center;gap:12px}
.logo-mark{width:44px;height:44px;background:rgba(255,255,255,0.08);border:1.5px solid rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(99,51,220,0.35)}
.logo-mark svg{width:26px;height:26px}
.logo-text strong{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;display:block;letter-spacing:-.5px}
.logo-text span{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:2px}
.form-area{flex:1;padding:28px 0}
.step-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.25);border-radius:20px;padding:5px 12px;font-size:11px;color:var(--success);font-weight:600;margin-bottom:20px;width:fit-content}
.step-badge span{width:18px;height:18px;background:var(--success);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff;font-weight:800}
h1{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;letter-spacing:-1px;margin-bottom:6px}
.sub{color:var(--muted);font-size:13px;margin-bottom:28px;line-height:1.6}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px;animation:fadeIn .3s ease}
.alert svg{width:16px;height:16px;flex-shrink:0}
.alert.error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#fca5a5}
@keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-group{margin-bottom:16px}
label{display:block;font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:8px}
.input-wrap{position:relative}
.input-wrap svg.icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--muted);pointer-events:none}
input[type=text],input[type=tel],input[type=number],select,textarea{width:100%;padding:12px 14px 12px 42px;background:rgba(255,255,255,0.04);border:1.5px solid var(--border);border-radius:10px;font-size:14px;color:var(--text);font-family:'DM Sans',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s}
input:focus,select:focus,textarea:focus{border-color:var(--accent2);box-shadow:0 0 0 3px rgba(59,130,246,0.15)}
select{-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;background-size:16px;padding-right:36px}
select option{background:#1e293b;color:var(--text)}
textarea{resize:vertical;min-height:90px;padding-top:12px;padding-bottom:12px;line-height:1.6}
.optional{font-size:10px;color:var(--muted);font-style:italic;margin-left:4px;text-transform:none;letter-spacing:0}
.char-count{font-size:11px;color:var(--muted);text-align:right;margin-top:4px}
hr{border:none;border-top:1px solid var(--border);margin:20px 0}
.btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,var(--success),#059669);border:none;border-radius:12px;color:#fff;font-size:15px;font-weight:700;font-family:'Syne',sans-serif;cursor:pointer;transition:all .2s;letter-spacing:.3px}
.btn-submit:hover{box-shadow:0 4px 20px rgba(16,185,129,0.4);transform:translateY(-1px)}
.btn-submit.loading{opacity:.8;pointer-events:none}
.skip-link{text-align:center;margin-top:14px;font-size:12px}
.skip-link a{color:var(--muted);text-decoration:none}
.skip-link a:hover{color:var(--text)}
.left-footer{font-size:11px;color:var(--muted);line-height:1.6;margin-top:24px}
/* RIGHT */
.right{flex:1;position:relative;z-index:1;display:flex;align-items:center;justify-content:center;overflow:hidden}
.swirl{position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,0.12) 0%,transparent 65%);top:50%;left:50%;transform:translate(-50%,-50%)}
.dot-grid{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,0.06) 1px,transparent 1px);background-size:28px 28px}
.right-content{position:relative;z-index:2;max-width:420px;padding:40px;text-align:center}
.right-emoji{font-size:56px;margin-bottom:20px;display:block;animation:float 3s ease-in-out infinite}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.right-title{font-family:'Syne',sans-serif;font-size:28px;font-weight:800;letter-spacing:-1px;line-height:1.1;margin-bottom:14px}
.right-title span{background:linear-gradient(135deg,#34d399,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.right-sub{color:var(--muted);font-size:14px;line-height:1.7;margin-bottom:28px}
.preview-card{background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:16px;padding:20px;text-align:left;margin-bottom:16px}
.preview-card-header{display:flex;align-items:center;gap:12px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border)}
.preview-avatar{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,var(--accent),var(--teal));display:flex;align-items:center;justify-content:center;font-size:18px}
.preview-name{font-family:'Syne',sans-serif;font-size:15px;font-weight:700}
.preview-type{font-size:12px;color:var(--muted)}
.preview-row{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted);margin-bottom:6px}
.preview-row svg{width:14px;height:14px;flex-shrink:0}
.benefit-list{display:flex;flex-direction:column;gap:8px}
.benefit-item{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--muted);padding:8px 12px;background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:8px}
.benefit-item span:first-child{font-size:16px}
.flow-steps{display:flex;flex-direction:column;gap:12px;text-align:left}
.flow-step{display:flex;align-items:flex-start;gap:12px;padding:10px 14px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:12px}
.fs-num{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;font-family:'Syne',sans-serif}
.fs-num.current{background:var(--success);color:#fff;box-shadow:0 0 12px rgba(16,185,129,0.5)}
.fs-num.done{background:rgba(16,185,129,0.15);color:var(--success);border:1.5px solid rgba(16,185,129,0.3)}
.fs-title{font-size:13px;font-weight:600;margin-bottom:1px}
.fs-title.current{color:var(--success)}
.fs-title.done{color:var(--success)}
.fs-desc{font-size:11px;color:var(--muted)}
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
        <div class="step-badge"><span>✓</span> Langkah 3 dari 3 — Profil Usaha</div>
        <h1>Profil UMKM Anda 🏪</h1>
        <p class="sub">Halo, <strong><?= htmlspecialchars($namaUser) ?></strong>! Lengkapi data usaha Anda agar semua fitur dashboard bisa berjalan optimal.</p>

        <?php if ($error): ?>
        <div class="alert error">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="profilForm">

            <!-- Nama Usaha -->
            <div class="form-group">
                <label for="nama_usaha">Nama Usaha <span style="color:var(--danger)">*</span></label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <input type="text" id="nama_usaha" name="nama_usaha" placeholder="Contoh: Keripik Singkong Bu Sari" value="<?= htmlspecialchars($_POST['nama_usaha'] ?? '') ?>" required maxlength="100">
                </div>
            </div>

            <!-- Jenis Usaha + Tahun -->
            <div class="form-row">
                <div class="form-group">
                    <label for="jenis_usaha">Jenis Usaha <span style="color:var(--danger)">*</span></label>
                    <div class="input-wrap">
                        <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                        <select id="jenis_usaha" name="jenis_usaha" required>
                            <option value="" disabled selected>Pilih jenis</option>
                            <option value="Makanan & Minuman"   <?= ($_POST['jenis_usaha']??'')==='Makanan & Minuman'  ?'selected':'' ?>>Makanan & Minuman</option>
                            <option value="Fashion & Pakaian"  <?= ($_POST['jenis_usaha']??'')==='Fashion & Pakaian' ?'selected':'' ?>>Fashion & Pakaian</option>
                            <option value="Kerajinan Tangan"   <?= ($_POST['jenis_usaha']??'')==='Kerajinan Tangan'  ?'selected':'' ?>>Kerajinan Tangan</option>
                            <option value="Pertanian"          <?= ($_POST['jenis_usaha']??'')==='Pertanian'          ?'selected':'' ?>>Pertanian</option>
                            <option value="Jasa & Servis"      <?= ($_POST['jenis_usaha']??'')==='Jasa & Servis'      ?'selected':'' ?>>Jasa & Servis</option>
                            <option value="Teknologi"          <?= ($_POST['jenis_usaha']??'')==='Teknologi'          ?'selected':'' ?>>Teknologi</option>
                            <option value="Perdagangan"        <?= ($_POST['jenis_usaha']??'')==='Perdagangan'        ?'selected':'' ?>>Perdagangan</option>
                            <option value="Lainnya"            <?= ($_POST['jenis_usaha']??'')==='Lainnya'            ?'selected':'' ?>>Lainnya</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="tahun_berdiri">Tahun Berdiri <span class="optional">(opsional)</span></label>
                    <div class="input-wrap">
                        <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <input type="number" id="tahun_berdiri" name="tahun_berdiri" placeholder="<?= date('Y') ?>" min="1945" max="<?= date('Y') ?>" value="<?= htmlspecialchars($_POST['tahun_berdiri'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Telepon -->
            <div class="form-group">
                <label for="telepon">Nomor Telepon / WhatsApp <span style="color:var(--danger)">*</span></label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <input type="tel" id="telepon" name="telepon" placeholder="Contoh: 081234567890" value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>" required>
                </div>
            </div>

            <!-- Alamat -->
            <div class="form-group">
                <label for="alamat">Alamat Usaha <span style="color:var(--danger)">*</span></label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="top:16px;transform:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <textarea id="alamat" name="alamat" rows="2" placeholder="Jl. Merdeka No. 10, Surabaya, Jawa Timur" required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Deskripsi -->
            <div class="form-group">
                <label for="deskripsi">Deskripsi Usaha <span class="optional">(opsional)</span></label>
                <div class="input-wrap">
                    <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="top:16px;transform:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    <textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Ceritakan tentang usaha Anda..." maxlength="300"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                </div>
                <div class="char-count"><span id="charCount">0</span>/300 karakter</div>
            </div>

            <hr>

            <button type="submit" class="btn-submit" id="submitBtn">
                🚀 Simpan & Masuk ke Dashboard
            </button>
        </form>

        <div class="skip-link">
            <a href="index.php?skip=1">Lewati dulu, lengkapi nanti →</a>
        </div>
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
        <span class="right-emoji">🏪</span>
        <div class="right-title">Hampir<br><span>Selesai!</span></div>
        <p class="right-sub">Profil usaha yang lengkap membantu dashboard memberikan informasi yang lebih akurat dan personal.</p>

        <div class="flow-steps" style="margin-bottom:20px;">
            <div class="flow-step">
                <div class="fs-num done">✓</div>
                <div><div class="fs-title done">Buat Akun</div><div class="fs-desc">Akun berhasil dibuat.</div></div>
            </div>
            <div class="flow-step">
                <div class="fs-num done">✓</div>
                <div><div class="fs-title done">Verifikasi Email</div><div class="fs-desc">Email berhasil diverifikasi.</div></div>
            </div>
            <div class="flow-step">
                <div class="fs-num current">3</div>
                <div><div class="fs-title current">Lengkapi Profil UMKM</div><div class="fs-desc">Isi data bisnis Anda sekarang.</div></div>
            </div>
        </div>

        <div class="benefit-list">
            <div class="benefit-item"><span>📊</span> Dashboard otomatis sesuai jenis usaha</div>
            <div class="benefit-item"><span>🧾</span> Invoice dengan nama usaha & kontak</div>
            <div class="benefit-item"><span>📦</span> Manajemen produk yang terorganisir</div>
        </div>
    </div>
</div>

<script>
// Char counter
const deskInput  = document.getElementById('deskripsi');
const charCount  = document.getElementById('charCount');
deskInput.addEventListener('input', () => {
    charCount.textContent = deskInput.value.length;
});

// Submit loading
document.getElementById('profilForm').addEventListener('submit', () => {
    const btn = document.getElementById('submitBtn');
    btn.classList.add('loading');
    btn.textContent = 'Menyimpan...';
});

// Skip link → tandai profil_dilengkapi sementara via session
// (index.php harus handle GET skip=1)
</script>
</body>
</html>