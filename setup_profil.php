<?php
// ================================================================
// setup_profil.php — Isi Profil UMKM setelah Login Pertama
// Kolom DB: id_umkm, id_user, nama_usaha, pemilik, alamat,
//           telepon, deskripsi, jenis_usaha, kategori,
//           no_telp, jumlah_karyawan, nomor_nib
// ================================================================
require_once 'koneksi.php';

// Wajib login
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

// Jika profil sudah lengkap, langsung ke dashboard
if (isset($_SESSION['profil_lengkap']) && $_SESSION['profil_lengkap']) {
    header('Location: index.php');
    exit;
}

$id_user = (int)$_SESSION['id_user'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil & sanitasi semua input sesuai kolom tabel profil_umkm
    $nama_usaha      = trim($_POST['nama_usaha']      ?? '');
    $pemilik         = trim($_POST['pemilik']         ?? '');
    $alamat          = trim($_POST['alamat']          ?? '');
    $telepon         = trim($_POST['telepon']         ?? '');
    $no_telp         = trim($_POST['no_telp']         ?? '');
    $deskripsi       = trim($_POST['deskripsi']       ?? '');
    $jenis_usaha     = trim($_POST['jenis_usaha']     ?? '');
    $kategori        = trim($_POST['kategori']        ?? '');
    $jumlah_karyawan = (int)($_POST['jumlah_karyawan'] ?? 0);
    $nomor_nib       = trim($_POST['nomor_nib']       ?? '');

    // Validasi wajib
    if (empty($nama_usaha) || empty($pemilik) || empty($alamat)) {
        $error = 'Nama usaha, pemilik, dan alamat wajib diisi.';
    } else {
        // Cek apakah sudah ada profil untuk user ini
        $cek = $conn->prepare("SELECT id_umkm FROM profil_umkm WHERE id_user = ?");
        $cek->bind_param('i', $id_user);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            // Update
            $stmt = $conn->prepare("
                UPDATE profil_umkm SET
                    nama_usaha      = ?,
                    pemilik         = ?,
                    alamat          = ?,
                    telepon         = ?,
                    no_telp         = ?,
                    deskripsi       = ?,
                    jenis_usaha     = ?,
                    kategori        = ?,
                    jumlah_karyawan = ?,
                    nomor_nib       = ?
                WHERE id_user = ?
            ");
            $stmt->bind_param('ssssssssiis',
                $nama_usaha, $pemilik, $alamat, $telepon, $no_telp,
                $deskripsi, $jenis_usaha, $kategori, $jumlah_karyawan,
                $nomor_nib, $id_user
            );
        } else {
            // Insert baru
            $stmt = $conn->prepare("
                INSERT INTO profil_umkm
                    (id_user, nama_usaha, pemilik, alamat, telepon, no_telp,
                     deskripsi, jenis_usaha, kategori, jumlah_karyawan, nomor_nib)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('issssssssis',
                $id_user, $nama_usaha, $pemilik, $alamat, $telepon, $no_telp,
                $deskripsi, $jenis_usaha, $kategori, $jumlah_karyawan, $nomor_nib
            );
        }
        $cek->close();

        if ($stmt->execute()) {
            // Update flag profil_dilengkapi di tabel users
            $upd = $conn->prepare("UPDATE users SET profil_dilengkapi = 1 WHERE id_user = ?");
            $upd->bind_param('i', $id_user);
            $upd->execute();
            $upd->close();

            // Update session
            $_SESSION['profil_lengkap'] = true;
            $_SESSION['nama_usaha']     = $nama_usaha;

            header('Location: index.php?welcome=1');
            exit;
        } else {
            $error = 'Gagal menyimpan profil: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Prefill jika data sudah ada
$prefill = [];
$pre = $conn->prepare("SELECT * FROM profil_umkm WHERE id_user = ?");
$pre->bind_param('i', $id_user);
$pre->execute();
$res = $pre->get_result();
if ($res->num_rows > 0) {
    $prefill = $res->fetch_assoc();
}
$pre->close();

$nama_user = htmlspecialchars($_SESSION['nama'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup Profil UMKM — UMKM NEXT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
.setup-page {
    min-height: 100vh;
    background: var(--bg-base);
    padding: 40px 24px 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.setup-header {
    text-align: center;
    margin-bottom: 36px;
    max-width: 540px;
}
.setup-logo {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    text-decoration: none;
}
.setup-logo-mark {
    width: 46px; height: 46px;
    background: var(--grad-brand);
    border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center;
    box-shadow: var(--shadow-accent);
}
.setup-logo-mark svg { width: 26px; height: 26px; }
.setup-brand { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; color: var(--text); }

.setup-steps {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    justify-content: center;
}
.step-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    background: var(--border);
}
.step-dot.active { background: var(--accent2); width: 28px; border-radius: 5px; }
.step-dot.done { background: var(--success); }

.setup-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    padding: 40px;
    width: 100%;
    max-width: 640px;
    box-shadow: var(--shadow-md);
}
.setup-card-title {
    font-size: 1.3rem;
    margin-bottom: 4px;
}
.setup-card-sub {
    color: var(--text-muted);
    font-size: 13px;
    margin-bottom: 28px;
}

.section-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 24px 0 20px;
}
.section-divider span {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-muted);
    white-space: nowrap;
}
.section-divider::before,
.section-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

.alert-error {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: var(--radius-sm);
    padding: 12px 16px;
    color: var(--danger);
    font-size: 13px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.skip-link {
    text-align: center;
    margin-top: 14px;
    font-size: 12px;
    color: var(--text-muted);
}
</style>
</head>
<body>
<div class="setup-page">

    <!-- Header -->
    <div class="setup-header">
        <a href="#" class="setup-logo">
            <div class="setup-logo-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <span class="setup-brand">UMKM NEXT</span>
        </a>

        <div class="setup-steps">
            <div class="step-dot done"></div>
            <div class="step-dot active"></div>
            <div class="step-dot"></div>
        </div>

        <h2 style="font-size:1.6rem; margin-bottom:8px;">Lengkapi Profil UMKM</h2>
        <p style="color:var(--text-muted); font-size:14px;">
            Halo, <strong style="color:var(--text);"><?= $nama_user ?></strong>! Sebelum mulai, isi informasi usaha Anda agar data lebih lengkap.
        </p>
    </div>

    <!-- Card Form -->
    <div class="setup-card">
        <h3 class="setup-card-title">Informasi Usaha</h3>
        <p class="setup-card-sub">Semua field bertanda * wajib diisi</p>

        <?php if ($error): ?>
        <div class="alert-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="setup_profil.php">

            <!-- IDENTITAS USAHA -->
            <div class="section-divider"><span>Identitas Usaha</span></div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Usaha *</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                        <input class="form-control" type="text" name="nama_usaha"
                               placeholder="Contoh: Warung Makan Barokah"
                               value="<?= htmlspecialchars($prefill['nama_usaha'] ?? $_POST['nama_usaha'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Pemilik *</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input class="form-control" type="text" name="pemilik"
                               placeholder="Nama lengkap pemilik"
                               value="<?= htmlspecialchars($prefill['pemilik'] ?? $_POST['pemilik'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Usaha *</label>
                <div class="input-wrap">
                    <svg class="input-icon" style="top:14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <textarea class="form-control" name="alamat" rows="2"
                              placeholder="Jalan, Kelurahan, Kecamatan, Kota" required><?= htmlspecialchars($prefill['alamat'] ?? $_POST['alamat'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Telepon</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.68A2 2 0 012 .02h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7a2 2 0 011.72 2.03z"/></svg>
                        <input class="form-control" type="text" name="telepon"
                               placeholder="Telepon kantor / usaha"
                               value="<?= htmlspecialchars($prefill['telepon'] ?? $_POST['telepon'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">No. HP / WhatsApp</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        <input class="form-control" type="text" name="no_telp"
                               placeholder="08xx-xxxx-xxxx"
                               value="<?= htmlspecialchars($prefill['no_telp'] ?? $_POST['no_telp'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- DETAIL USAHA -->
            <div class="section-divider"><span>Detail Usaha</span></div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Jenis Usaha</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        <input class="form-control" type="text" name="jenis_usaha"
                               placeholder="Contoh: Kuliner, Jasa, Retail"
                               value="<?= htmlspecialchars($prefill['jenis_usaha'] ?? $_POST['jenis_usaha'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        <input class="form-control" type="text" name="kategori"
                               placeholder="Contoh: Makanan & Minuman"
                               value="<?= htmlspecialchars($prefill['kategori'] ?? $_POST['kategori'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Jumlah Karyawan</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                        <input class="form-control" type="number" name="jumlah_karyawan" min="0"
                               placeholder="0"
                               value="<?= htmlspecialchars($prefill['jumlah_karyawan'] ?? $_POST['jumlah_karyawan'] ?? '0') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor NIB</label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <input class="form-control" type="text" name="nomor_nib"
                               placeholder="Nomor Induk Berusaha (opsional)"
                               value="<?= htmlspecialchars($prefill['nomor_nib'] ?? $_POST['nomor_nib'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi Usaha</label>
                <div class="input-wrap">
                    <svg class="input-icon" style="top:14px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="17" y1="18" x2="3" y2="18"/></svg>
                    <textarea class="form-control" name="deskripsi" rows="3"
                              placeholder="Ceritakan singkat tentang usaha Anda..."><?= htmlspecialchars($prefill['deskripsi'] ?? $_POST['deskripsi'] ?? '') ?></textarea>
                </div>
            </div>

            <button class="btn btn-primary btn-full mt-md" type="submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><polyline points="20 6 9 17 4 12"/></svg>
                Simpan & Lanjutkan ke Dashboard
            </button>
        </form>

        <div class="skip-link">
            Ingin isi nanti? <a href="index.php?skip=1">Lewati langkah ini</a>
        </div>
    </div>
</div>
</body>
</html>