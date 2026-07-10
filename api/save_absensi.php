<?php
// ============================================================
//  api/save_absensi.php — Endpoint menyimpan absensi siswa
//  Method: POST | Content-Type: application/json
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
    exit;
}

$tanggal   = trim($data['tanggal'] ?? '');
$absStatus = $data['absStatus'] ?? [];

if (empty($tanggal) || empty($absStatus)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Tanggal dan status absensi wajib diisi.']);
    exit;
}

$db = getDB();

$stmt = $db->prepare("
    INSERT INTO absensi (nis, tanggal, status)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE status = VALUES(status)
");

$success = true;
foreach ($absStatus as $nis => $status) {
    $stmt->bind_param('sss', $nis, $tanggal, $status);
    if (!$stmt->execute()) {
        $success = false;
    }
}
$stmt->close();
$db->close();

if ($success) {
    echo json_encode(['status' => 'success', 'message' => 'Absensi berhasil disimpan ke database.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Sebagian data gagal disimpan.']);
}
