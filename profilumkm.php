<?php
session_start();
require_once 'koneksi.php';

// Proteksi halaman
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error   = '';
$success = '';

// Cek apakah tabel profil_umkm sudah ada, jika belum buat otomatis
$koneksi->query("
    CREATE TABLE IF NOT EXISTS profil_umkm (
        id_profil       INT(11) AUTO_INCREMENT PRIMARY KEY,
        id_user         INT(11) NOT NULL,
        nama_usaha      VARCHAR(150) NOT NULL,
        jenis_usaha     VARCHAR(100) NOT NULL,
        deskripsi       TEXT,
        alamat          TEXT,
        kota            VARCHAR(100),
        provinsi        VARCHAR(100),
        kode_pos        VARCHAR(10),
        no_telepon      VARCHAR(20),
        email_usaha     VARCHAR(100),
        website         VARCHAR(150),
        instagram       VARCHAR(100),
        tahun_berdiri   YEAR,
        jumlah_karyawan VARCHAR(50),
        modal_awal      DECIMAL(15,2),
        no_nib          VARCHAR(50),
        logo_path       VARCHAR(255),
        is_complete     TINYINT(1) DEFAULT 0,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user (id_user)
    )
");

// Ambil data profil yang sudah ada (jika pernah diisi)
$existing = null;
$stmt = $koneksi->prepare("SELECT * FROM profil_umkm WHERE id_user = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $existing = $result->fetch_assoc();
}
$stmt->close();

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = (int)($_POST['step'] ?? 1);

    // Ambil dan sanitasi semua input
    $nama_usaha      = trim($_POST['nama_usaha']      ?? '');
    $jenis_usaha     = trim($_POST['jenis_usaha']     ?? '');
    $deskripsi       = trim($_POST['deskripsi']       ?? '');
    $alamat          = trim($_POST['alamat']           ?? '');
    $kota            = trim($_POST['kota']             ?? '');
    $provinsi        = trim($_POST['provinsi']         ?? '');
    $kode_pos        = trim($_POST['kode_pos']         ?? '');
    $no_telepon      = trim($_POST['no_telepon']       ?? '');
    $email_usaha     = trim($_POST['email_usaha']      ?? '');
    $website         = trim($_POST['website']          ?? '');
    $instagram       = trim($_POST['instagram']        ?? '');
    $tahun_berdiri   = (int)($_POST['tahun_berdiri']   ?? date('Y'));
    $jumlah_karyawan = trim($_POST['jumlah_karyawan']  ?? '');
    $modal_awal      = (float)str_replace(['Rp', '.', ' '], '', $_POST['modal_awal'] ?? '0');
    $no_nib          = trim($_POST['no_nib']           ?? '');
    $is_complete     = ($step === 3) ? 1 : 0;

    if (empty($nama_usaha) || empty($jenis_usaha)) {
        $error = 'Nama usaha dan jenis usaha wajib diisi.';
    } else {
        if ($existing) {
            // UPDATE
            $stmt = $koneksi->prepare("
                UPDATE profil_umkm SET
                    nama_usaha=?, jenis_usaha=?, deskripsi=?,
                    alamat=?, kota=?, provinsi=?, kode_pos=?,
                    no_telepon=?, email_usaha=?, website=?, instagram=?,
                    tahun_berdiri=?, jumlah_karyawan=?, modal_awal=?, no_nib=?,
                    is_complete=?
                WHERE id_user=?
            ");
            $stmt->bind_param(
                "ssssssssssssssdii",
                $nama_usaha, $jenis_usaha, $deskripsi,
                $alamat, $kota, $provinsi, $kode_pos,
                $no_telepon, $email_usaha, $website, $instagram,
                $tahun_berdiri, $jumlah_karyawan, $modal_awal, $no_nib,
                $is_complete, $user_id
            );
        } else {
            // INSERT
            $stmt = $koneksi->prepare("
                INSERT INTO profil_umkm
                    (id_user, nama_usaha, jenis_usaha, deskripsi,
                     alamat, kota, provinsi, kode_pos,
                     no_telepon, email_usaha, website, instagram,
                     tahun_berdiri, jumlah_karyawan, modal_awal, no_nib, is_complete)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "isssssssssssssdsi",
                $user_id, $nama_usaha, $jenis_usaha, $deskripsi,
                $alamat, $kota, $provinsi, $kode_pos,
                $no_telepon, $email_usaha, $website, $instagram,
                $tahun_berdiri, $jumlah_karyawan, $modal_awal, $no_nib, $is_complete
            );
        }

        if ($stmt->execute()) {
            if ($step === 3) {
                $_SESSION['profil_complete'] = true;
                $_SESSION['nama_usaha']      = $nama_usaha;
                header("Location: index.php?welcome=1");
                exit();
            } else {
                // Refresh data existing untuk step berikutnya
                $existing = [
                    'nama_usaha'=>$nama_usaha,'jenis_usaha'=>$jenis_usaha,'deskripsi'=>$deskripsi,
                    'alamat'=>$alamat,'kota'=>$kota,'provinsi'=>$provinsi,'kode_pos'=>$kode_pos,
                    'no_telepon'=>$no_telepon,'email_usaha'=>$email_usaha,'website'=>$website,
                    'instagram'=>$instagram,'tahun_berdiri'=>$tahun_berdiri,
                    'jumlah_karyawan'=>$jumlah_karyawan,'modal_awal'=>$modal_awal,'no_nib'=>$no_nib,
                ];
                $success = 'step' . ($step + 1); // sinyal pindah step
            }
        } else {
            $error = 'Gagal menyimpan: ' . $stmt->error;
        }
        $stmt->close();
    }
}

$v = $existing ?? [];
function val($key, $default = '') {
    global $v;
    return htmlspecialchars($v[$key] ?? $default);
}

$jenis_list = [
    'Kuliner & F&B', 'Fashion & Pakaian', 'Kerajinan Tangan',
    'Pertanian & Perkebunan', 'Peternakan & Perikanan',
    'Jasa & Layanan', 'Teknologi & Digital', 'Retail & Perdagangan',
    'Kesehatan & Kecantikan', 'Pendidikan & Pelatihan', 'Lainnya',
];
$karyawan_list = ['1-5 orang', '6-10 orang', '11-25 orang', '26-50 orang', '50+ orang'];
$provinsi_list = [
    'Aceh','Sumatera Utara','Sumatera Barat','Riau','Kepulauan Riau','Jambi',
    'Sumatera Selatan','Kepulauan Bangka Belitung','Bengkulu','Lampung',
    'DKI Jakarta','Jawa Barat','Banten','Jawa Tengah','DI Yogyakarta','Jawa Timur',
    'Bali','Nusa Tenggara Barat','Nusa Tenggara Timur',
    'Kalimantan Barat','Kalimantan Tengah','Kalimantan Selatan','Kalimantan Timur','Kalimantan Utara',
    'Sulawesi Utara','Gorontalo','Sulawesi Tengah','Sulawesi Barat','Sulawesi Selatan','Sulawesi Tenggara',
    'Maluku','Maluku Utara','Papua Barat','Papua',
];
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
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:    #0b1120;
            --panel:   #131f35;
            --surface: #111827;
            --border:  rgba(255,255,255,0.07);
            --accent:  #2563eb;
            --accent2: #3b82f6;
            --teal:    #06b6d4;
            --gold:    #f59e0b;
            --text:    #e2e8f0;
            --muted:   #64748b;
            --success: #10b981;
            --danger:  #ef4444;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--panel);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: 60px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar .logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .logo-mark {
            width: 38px; height: 38px;
            background: rgba(255,255,255,0.08);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 14px rgba(99,51,220,0.35);
        }
        .logo-mark svg { width: 22px; height: 22px; }
        .logo-text { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 800; color: var(--text); }
        .topbar-user {
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; color: var(--muted);
        }
        .avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--teal));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 800; color: #fff;
        }

        /* ── MAIN ── */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px 60px;
        }

        /* ── PROGRESS HEADER ── */
        .progress-header {
            width: 100%; max-width: 800px;
            margin-bottom: 36px;
        }
        .ph-title {
            font-family: 'Syne', sans-serif;
            font-size: 26px; font-weight: 800; letter-spacing: -1px;
            margin-bottom: 6px;
        }
        .ph-sub { font-size: 14px; color: var(--muted); margin-bottom: 28px; }

        /* Steps indicator */
        .steps-bar {
            display: flex; align-items: center; gap: 0;
        }
        .step-item {
            display: flex; align-items: center; gap: 10px;
            flex: 1;
        }
        .step-item:last-child { flex: 0; }
        .step-circle {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 800;
            flex-shrink: 0;
            transition: all .3s;
        }
        .step-circle.done {
            background: var(--success);
            color: #fff;
            box-shadow: 0 0 16px rgba(16,185,129,.4);
        }
        .step-circle.active {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 0 16px rgba(37,99,235,.5);
        }
        .step-circle.pending {
            background: rgba(255,255,255,0.06);
            border: 1.5px solid var(--border);
            color: var(--muted);
        }
        .step-info { flex: 1; }
        .step-info .s-num { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }
        .step-info .s-name { font-size: 13px; font-weight: 500; }
        .step-line {
            height: 2px; flex: 1; margin: 0 10px;
            background: var(--border);
            border-radius: 2px;
            transition: background .5s;
        }
        .step-line.done { background: var(--success); }
        .step-line.active { background: var(--accent); }

        /* ── CARD FORM ── */
        .form-card {
            width: 100%; max-width: 800px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
        }
        .card-header {
            padding: 24px 32px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 14px;
        }
        .card-header-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .card-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 700; margin-bottom: 2px;
        }
        .card-header p { font-size: 13px; color: var(--muted); }

        .card-body { padding: 32px; }

        /* ── GRID FORM ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
        .col-span-2 { grid-column: span 2; }
        .col-span-3 { grid-column: span 3; }

        /* ── FIELD ── */
        .field { display: flex; flex-direction: column; gap: 7px; }
        .field label {
            font-size: 11px; font-weight: 600;
            color: var(--muted); text-transform: uppercase; letter-spacing: 1.2px;
        }
        .field label .req { color: var(--danger); margin-left: 2px; }

        .input-wrap { position: relative; }
        .input-wrap .ico {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            width: 15px; height: 15px; color: var(--muted); pointer-events: none;
        }
        .input-wrap.textarea-wrap .ico { top: 16px; transform: none; }

        input[type="text"], input[type="email"], input[type="url"],
        input[type="number"], input[type="tel"], select, textarea {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px 12px 40px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; color: var(--text); outline: none;
            transition: border-color .2s, box-shadow .2s;
            -webkit-appearance: none;
        }
        input.no-ico, select.no-ico, textarea.no-ico { padding-left: 14px; }
        input::placeholder, textarea::placeholder { color: #2d3748; }
        input:focus, select:focus, textarea:focus {
            border-color: var(--accent2);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        select { cursor: pointer; }
        select option { background: #1e2a3a; color: var(--text); }
        textarea { resize: vertical; min-height: 90px; line-height: 1.6; }

        /* Prefix input (Rp) */
        .prefix-wrap { position: relative; }
        .prefix-wrap .prefix {
            position: absolute; left: 0; top: 0; bottom: 0;
            display: flex; align-items: center;
            padding: 0 12px;
            background: rgba(255,255,255,0.06);
            border-right: 1px solid var(--border);
            border-radius: 10px 0 0 10px;
            font-size: 13px; color: var(--muted);
        }
        .prefix-wrap input { padding-left: 52px; }

        /* Hint text */
        .hint { font-size: 11px; color: var(--muted); margin-top: 4px; }

        /* ── SECTION LABEL ── */
        .section-label {
            display: flex; align-items: center; gap: 10px;
            margin: 24px 0 16px;
        }
        .section-label span {
            font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 700; color: var(--text);
        }
        .section-label::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        /* ── ALERT ── */
        .alert {
            padding: 12px 16px; border-radius: 10px;
            font-size: 13px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
            animation: fadeIn .3s ease;
        }
        .alert svg { width: 16px; height: 16px; flex-shrink: 0; }
        .alert.error   { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; }
        .alert.success { background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.25); color: #6ee7b7; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

        /* ── CARD FOOTER ── */
        .card-footer {
            padding: 20px 32px;
            border-top: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; border-radius: 10px; border: none;
            font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700;
            cursor: pointer; text-decoration: none;
            transition: transform .15s, box-shadow .2s, opacity .2s;
        }
        .btn svg { width: 16px; height: 16px; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #1d4ed8);
            color: #fff; box-shadow: 0 4px 20px rgba(37,99,235,.4);
        }
        .btn-primary:hover { box-shadow: 0 6px 28px rgba(37,99,235,.55); }
        .btn-ghost {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            color: var(--text);
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.09); }
        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: #fff; box-shadow: 0 4px 20px rgba(16,185,129,.35);
        }
        .btn.loading { opacity: .7; pointer-events: none; }

        /* ── SKIP LINK ── */
        .skip-row {
            text-align: center; margin-top: 20px;
            font-size: 13px; color: var(--muted);
        }
        .skip-row a { color: var(--accent2); }

        /* ── STEP PANELS ── */
        .step-panel { display: none; }
        .step-panel.active { display: block; }

        /* ── REVIEW CARD ── */
        .review-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
        }
        .review-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 10px; padding: 14px 16px;
        }
        .review-item .ri-label {
            font-size: 10px; color: var(--muted); text-transform: uppercase;
            letter-spacing: 1px; margin-bottom: 4px;
        }
        .review-item .ri-val {
            font-size: 14px; color: var(--text); font-weight: 500;
        }
        .review-item.span-2 { grid-column: span 2; }

        /* ── COMPLETE BADGE ── */
        .complete-banner {
            text-align: center; padding: 32px;
        }
        .check-anim {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg, var(--success), #059669);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 0 40px rgba(16,185,129,.4);
            animation: pop .4s cubic-bezier(.34,1.56,.64,1);
        }
        @keyframes pop { from { transform:scale(0); } to { transform:scale(1); } }
        .check-anim svg { width: 36px; height: 36px; }

        @media (max-width: 700px) {
            .form-grid, .form-grid.cols-3 { grid-template-columns: 1fr; }
            .col-span-2, .col-span-3 { grid-column: span 1; }
            .review-grid { grid-template-columns: 1fr; }
            .review-item.span-2 { grid-column: span 1; }
            .card-body, .card-footer { padding: 20px; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
    <a href="index.php" class="logo">
        <div class="logo-mark">
            <svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 4 L2 16 Q2 22 8 22 L12 22 Q14 22 14 19" stroke="white" stroke-width="2.8" stroke-linecap="round" fill="none"/>
                <path d="M16 22 L16 8 L24 22 L24 8" stroke="white" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                <path d="M20 6 L26 2 M23.5 2 L26 2 L26 4.5" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <span class="logo-text">UMKM Next</span>
    </a>
    <div class="topbar-user">
        <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
        <div class="avatar"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></div>
    </div>
</header>

<!-- MAIN -->
<main class="main">

    <!-- PROGRESS HEADER -->
    <div class="progress-header">
        <h1 class="ph-title">Lengkapi Profil UMKM Anda</h1>
        <p class="ph-sub">Isi informasi bisnis Anda agar sistem dapat bekerja secara optimal. Hanya butuh beberapa menit.</p>

        <div class="steps-bar">
            <!-- Step 1 -->
            <div class="step-item">
                <div class="step-circle <?= (isset($success) && in_array($success, ['step2','step3'])) ? 'done' : 'active' ?>" id="sc1">
                    <?php if (in_array($success ?? '', ['step2','step3'])): ?>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <?php else: ?>1<?php endif; ?>
                </div>
                <div class="step-info">
                    <div class="s-num">Langkah 1</div>
                    <div class="s-name">Identitas Usaha</div>
                </div>
            </div>
            <div class="step-line <?= in_array($success ?? '', ['step2','step3']) ? 'done' : '' ?>" id="sl1"></div>

            <!-- Step 2 -->
            <div class="step-item">
                <div class="step-circle <?= ($success ?? '') === 'step3' ? 'done' : (($success ?? '') === 'step2' ? 'active' : 'pending') ?>" id="sc2">
                    <?php if (($success ?? '') === 'step3'): ?>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <?php else: ?>2<?php endif; ?>
                </div>
                <div class="step-info">
                    <div class="s-num">Langkah 2</div>
                    <div class="s-name">Lokasi & Kontak</div>
                </div>
            </div>
            <div class="step-line" id="sl2"></div>

            <!-- Step 3 -->
            <div class="step-item">
                <div class="step-circle pending" id="sc3">3</div>
                <div class="step-info">
                    <div class="s-num">Langkah 3</div>
                    <div class="s-name">Konfirmasi</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FORM CARD -->
    <div class="form-card">
        <form method="POST" id="profileForm">

        <?php if ($error): ?>
        <div style="padding: 0 32px; padding-top: 24px;">
            <div class="alert error">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ══════════ STEP 1: Identitas Usaha ══════════ -->
        <div class="step-panel <?= (!$success || $success === '') ? 'active' : '' ?>" id="panel1">
            <div class="card-header">
                <div class="card-header-icon" style="background:rgba(37,99,235,0.15)">🏢</div>
                <div>
                    <h2>Identitas Usaha</h2>
                    <p>Informasi dasar mengenai bisnis UMKM Anda</p>
                </div>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <!-- Nama Usaha -->
                    <div class="field col-span-2">
                        <label>Nama Usaha <span class="req">*</span></label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <input type="text" name="nama_usaha" placeholder="Contoh: Warung Mbok Sri" value="<?= val('nama_usaha') ?>" required>
                        </div>
                    </div>

                    <!-- Jenis Usaha -->
                    <div class="field">
                        <label>Jenis / Kategori Usaha <span class="req">*</span></label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <select name="jenis_usaha" required>
                                <option value="">— Pilih Kategori —</option>
                                <?php foreach ($jenis_list as $j): ?>
                                <option value="<?= $j ?>" <?= val('jenis_usaha') === $j ? 'selected' : '' ?>><?= $j ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Tahun Berdiri -->
                    <div class="field">
                        <label>Tahun Berdiri</label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <input type="number" name="tahun_berdiri" placeholder="<?= date('Y') ?>" min="1900" max="<?= date('Y') ?>" value="<?= val('tahun_berdiri', date('Y')) ?>">
                        </div>
                    </div>

                    <!-- Jumlah Karyawan -->
                    <div class="field">
                        <label>Jumlah Karyawan</label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <select name="jumlah_karyawan">
                                <option value="">— Pilih —</option>
                                <?php foreach ($karyawan_list as $k): ?>
                                <option value="<?= $k ?>" <?= val('jumlah_karyawan') === $k ? 'selected' : '' ?>><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Modal Awal -->
                    <div class="field">
                        <label>Estimasi Modal Awal</label>
                        <div class="prefix-wrap">
                            <span class="prefix">Rp</span>
                            <input type="text" name="modal_awal" class="no-ico" placeholder="0" id="modalInput" value="<?= number_format((float)($v['modal_awal'] ?? 0), 0, ',', '.') ?>">
                        </div>
                        <span class="hint">Opsional — hanya untuk referensi internal</span>
                    </div>

                    <!-- NIB -->
                    <div class="field">
                        <label>No. NIB / SIUP</label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <input type="text" name="no_nib" placeholder="Nomor izin usaha (opsional)" value="<?= val('no_nib') ?>">
                        </div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="field col-span-2">
                        <label>Deskripsi Usaha</label>
                        <div class="input-wrap textarea-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                            <textarea name="deskripsi" placeholder="Ceritakan singkat tentang produk/layanan bisnis Anda..."><?= val('deskripsi') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="index.php" class="btn btn-ghost">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Lewati
                </a>
                <button type="button" class="btn btn-primary" onclick="goStep(2)">
                    Lanjut
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <!-- ══════════ STEP 2: Lokasi & Kontak ══════════ -->
        <div class="step-panel <?= ($success ?? '') === 'step2' ? 'active' : '' ?>" id="panel2">
            <div class="card-header">
                <div class="card-header-icon" style="background:rgba(6,182,212,0.15)">📍</div>
                <div>
                    <h2>Lokasi & Kontak</h2>
                    <p>Informasi alamat dan cara menghubungi bisnis Anda</p>
                </div>
            </div>
            <div class="card-body">
                <div class="section-label"><span>🗺 Alamat Usaha</span></div>
                <div class="form-grid">
                    <div class="field col-span-2">
                        <label>Alamat Lengkap</label>
                        <div class="input-wrap textarea-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <textarea name="alamat" placeholder="Jl. Contoh No. 1, RT/RW 01/01, Kelurahan..."><?= val('alamat') ?></textarea>
                        </div>
                    </div>

                    <div class="field">
                        <label>Kota / Kabupaten</label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                            <input type="text" name="kota" placeholder="Contoh: Surabaya" value="<?= val('kota') ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label>Provinsi</label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                            <select name="provinsi">
                                <option value="">— Pilih Provinsi —</option>
                                <?php foreach ($provinsi_list as $p): ?>
                                <option value="<?= $p ?>" <?= val('provinsi') === $p ? 'selected' : '' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label>Kode Pos</label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <input type="text" name="kode_pos" placeholder="60000" maxlength="5" value="<?= val('kode_pos') ?>">
                        </div>
                    </div>
                </div>

                <div class="section-label" style="margin-top:28px"><span>📞 Kontak & Media Sosial</span></div>
                <div class="form-grid">
                    <div class="field">
                        <label>No. Telepon / WhatsApp</label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <input type="tel" name="no_telepon" placeholder="08xxxxxxxxxx" value="<?= val('no_telepon') ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label>Email Usaha</label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <input type="email" name="email_usaha" placeholder="usaha@email.com" value="<?= val('email_usaha') ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label>Instagram</label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37zm1.5-4.87h.01"/></svg>
                            <input type="text" name="instagram" placeholder="@namaakun" value="<?= val('instagram') ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label>Website / Toko Online</label>
                        <div class="input-wrap">
                            <svg class="ico" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                            <input type="url" name="website" placeholder="https://tokosaya.com" value="<?= val('website') ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-ghost" onclick="goStep(1)">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Kembali
                </button>
                <button type="button" class="btn btn-primary" onclick="goStep(3)">
                    Lanjut
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <!-- ══════════ STEP 3: Review & Konfirmasi ══════════ -->
        <div class="step-panel <?= ($success ?? '') === 'step3' ? 'active' : '' ?>" id="panel3">
            <div class="card-header">
                <div class="card-header-icon" style="background:rgba(16,185,129,0.15)">✅</div>
                <div>
                    <h2>Review & Konfirmasi</h2>
                    <p>Periksa kembali data Anda sebelum disimpan</p>
                </div>
            </div>
            <div class="card-body">
                <div id="reviewContent" class="review-grid">
                    <!-- Diisi oleh JS -->
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-ghost" onclick="goStep(2)">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Ubah Data
                </button>
                <button type="button" class="btn btn-success" onclick="submitFinal()" id="submitBtn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan & Mulai Dashboard
                </button>
            </div>
        </div>

        <input type="hidden" name="step" id="stepInput" value="1">
        </form>
    </div>

    <p class="skip-row">
        Ingin melengkapi nanti? <a href="index.php">Lewati ke Dashboard →</a>
    </p>

</main>

<script>
let currentStep = 1;
const formData  = {};

// Kumpulkan data saat pindah step
function collectData() {
    const form = document.getElementById('profileForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(el => {
        if (el.name && el.name !== 'step') formData[el.name] = el.value;
    });
}

// Restore data ke form
function restoreData() {
    Object.entries(formData).forEach(([name, val]) => {
        const el = document.querySelector(`[name="${name}"]`);
        if (el) el.value = val;
    });
}

function goStep(n) {
    // Validasi step 1
    if (n > 1 && currentStep === 1) {
        const nama = document.querySelector('[name="nama_usaha"]').value.trim();
        const jenis = document.querySelector('[name="jenis_usaha"]').value;
        if (!nama || !jenis) {
            showInlineError('Nama usaha dan jenis usaha wajib diisi sebelum melanjutkan.');
            return;
        }
    }

    collectData();
    clearInlineError();

    // Update panels
    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel' + n).classList.add('active');

    // Update step circles
    updateStepBar(n);
    currentStep = n;

    // Kalau step 3, render review
    if (n === 3) renderReview();

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateStepBar(active) {
    for (let i = 1; i <= 3; i++) {
        const sc = document.getElementById('sc' + i);
        sc.classList.remove('done', 'active', 'pending');
        if (i < active) {
            sc.classList.add('done');
            sc.innerHTML = '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
        } else if (i === active) {
            sc.classList.add('active');
            sc.textContent = i;
        } else {
            sc.classList.add('pending');
            sc.textContent = i;
        }
    }
    for (let i = 1; i <= 2; i++) {
        const sl = document.getElementById('sl' + i);
        sl.className = 'step-line ' + (i < active ? 'done' : i === active ? 'active' : '');
    }
}

function renderReview() {
    const labels = {
        nama_usaha:      'Nama Usaha',
        jenis_usaha:     'Jenis Usaha',
        tahun_berdiri:   'Tahun Berdiri',
        jumlah_karyawan: 'Jumlah Karyawan',
        modal_awal:      'Modal Awal',
        no_nib:          'No. NIB / SIUP',
        deskripsi:       'Deskripsi',
        alamat:          'Alamat',
        kota:            'Kota',
        provinsi:        'Provinsi',
        kode_pos:        'Kode Pos',
        no_telepon:      'No. Telepon',
        email_usaha:     'Email Usaha',
        instagram:       'Instagram',
        website:         'Website',
    };
    const wide = ['deskripsi', 'alamat'];
    let html = '';
    Object.entries(labels).forEach(([key, label]) => {
        const val = formData[key] || '<span style="color:var(--muted);font-style:italic">—</span>';
        const display = key === 'modal_awal' && formData[key]
            ? 'Rp ' + parseInt(formData[key].replace(/\./g, '') || '0').toLocaleString('id-ID')
            : val;
        const span = wide.includes(key) ? ' span-2' : '';
        html += `<div class="review-item${span}">
            <div class="ri-label">${label}</div>
            <div class="ri-val">${display}</div>
        </div>`;
    });
    document.getElementById('reviewContent').innerHTML = html;
}

function submitFinal() {
    collectData();
    const form   = document.getElementById('profileForm');
    const hidden = document.getElementById('stepInput');
    hidden.value = 3;

    // Isi hidden inputs dari formData
    Object.entries(formData).forEach(([name, val]) => {
        let el = form.querySelector(`[name="${name}"]`);
        if (!el) {
            el = document.createElement('input');
            el.type = 'hidden';
            el.name = name;
            form.appendChild(el);
        }
        el.value = val;
    });

    const btn = document.getElementById('submitBtn');
    btn.classList.add('loading');
    btn.textContent = 'Menyimpan...';
    form.submit();
}

// Error inline
function showInlineError(msg) {
    let el = document.getElementById('inlineErr');
    if (!el) {
        el = document.createElement('div');
        el.id = 'inlineErr';
        el.className = 'alert error';
        el.style.margin = '0 32px 16px';
        el.innerHTML = `<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/></svg> <span></span>`;
        const panel = document.getElementById('panel' + currentStep);
        panel.querySelector('.card-body').prepend(el);
    }
    el.querySelector('span').textContent = msg;
    el.style.display = 'flex';
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function clearInlineError() {
    const el = document.getElementById('inlineErr');
    if (el) el.style.display = 'none';
}

// Format angka Rupiah di input modal
const modalInput = document.getElementById('modalInput');
if (modalInput) {
    modalInput.addEventListener('input', function () {
        let raw = this.value.replace(/\D/g, '');
        this.value = raw ? parseInt(raw).toLocaleString('id-ID') : '';
    });
}

// Restore step dari PHP success signal
<?php if ($success === 'step2'): ?>
goStep(2);
<?php elseif ($success === 'step3'): ?>
goStep(3);
<?php endif; ?>
</script>
</body>
</html>