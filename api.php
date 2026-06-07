<?php
// ================================================================
// api.php — REST-like endpoint untuk CRUD Pendapatan & Pengeluaran
// URL pattern: api.php?tipe=pendapatan|pengeluaran&action=...
// ================================================================

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$tipe   = $_GET['tipe']   ?? '';
$action = $_GET['action'] ?? 'list';

// Validasi tipe
if (!in_array($tipe, ['pendapatan', 'pengeluaran'])) {
    echo json_encode(['error' => 'Tipe tidak valid']);
    exit;
}

$table = $tipe; // nama tabel sama dengan tipe

try {
    $db = getDB();

    // ── LIST ──────────────────────────────────────────────────
    if ($action === 'list') {
        $stmt = $db->query(
            "SELECT * FROM `$table` ORDER BY tanggal DESC, id DESC"
        );
        $rows = $stmt->fetchAll();
        echo json_encode(['data' => $rows]);
        exit;
    }

    // ── SUMMARY ───────────────────────────────────────────────
    if ($action === 'summary') {
        $p  = $db->query("SELECT COALESCE(SUM(jumlah),0) AS total, COUNT(*) AS count FROM pendapatan")->fetch();
        $e  = $db->query("SELECT COALESCE(SUM(jumlah),0) AS total, COUNT(*) AS count FROM pengeluaran")->fetch();

        // Monthly chart data (last 6 months)
        $chart = $db->query("
            SELECT
                DATE_FORMAT(tanggal,'%Y-%m') AS bulan,
                COALESCE(SUM(CASE WHEN tipe='p' THEN jumlah ELSE 0 END),0) AS pendapatan,
                COALESCE(SUM(CASE WHEN tipe='e' THEN jumlah ELSE 0 END),0) AS pengeluaran
            FROM (
                SELECT tanggal, jumlah, 'p' AS tipe FROM pendapatan
                UNION ALL
                SELECT tanggal, jumlah, 'e' AS tipe FROM pengeluaran
            ) combined
            WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY bulan
            ORDER BY bulan ASC
        ")->fetchAll();

        echo json_encode([
            'pendapatan' => (float)$p['total'],
            'pengeluaran'=> (float)$e['total'],
            'count_p'    => (int)$p['count'],
            'count_e'    => (int)$e['count'],
            'laba'       => (float)$p['total'] - (float)$e['total'],
            'chart'      => $chart,
        ]);
        exit;
    }

    // ── CREATE ────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $keterangan = trim($body['keterangan'] ?? '');
        $jumlah     = (float)($body['jumlah'] ?? 0);
        $kategori   = trim($body['kategori'] ?? 'Lainnya');
        $tanggal    = trim($body['tanggal'] ?? '');

        // Validasi
        $errors = [];
        if (!$keterangan) $errors[] = 'Keterangan tidak boleh kosong.';
        if ($jumlah <= 0) $errors[] = 'Jumlah harus lebih dari 0.';
        if (!$tanggal || !strtotime($tanggal)) $errors[] = 'Tanggal tidak valid.';
        if ($errors) { echo json_encode(['error' => implode(' ', $errors)]); exit; }

        $stmt = $db->prepare(
            "INSERT INTO `$table` (tanggal, keterangan, kategori, jumlah) VALUES (?,?,?,?)"
        );
        $stmt->execute([$tanggal, $keterangan, $kategori, $jumlah]);

        echo json_encode([
            'success' => true,
            'id'      => $db->lastInsertId(),
            'message' => ucfirst($tipe) . ' berhasil ditambahkan.',
        ]);
        exit;
    }

    // ── DELETE ────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id   = (int)($body['id'] ?? 0);

        if (!$id) { echo json_encode(['error' => 'ID tidak valid']); exit; }

        $stmt = $db->prepare("DELETE FROM `$table` WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => 'Data berhasil dihapus.',
        ]);
        exit;
    }

    echo json_encode(['error' => 'Action tidak dikenali']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
