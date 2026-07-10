<?php
// ============================================================
//  api/save_jurnal_sikap.php — Endpoint menyimpan jurnal sikap
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

$nis           = trim($data['nis'] ?? '');
$tanggal       = trim($data['tanggal'] ?? '');
$aspek         = trim($data['aspek'] ?? '');
$perilaku      = trim($data['perilaku'] ?? '');
$tindak_lanjut = trim($data['tindak_lanjut'] ?? '');
$nilai         = trim($data['nilai'] ?? 'positif');

if (empty($nis) || empty($tanggal) || empty($aspek) || empty($perilaku) || empty($tindak_lanjut)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi.']);
    exit;
}

if (!in_array($aspek, ['spiritual', 'sosial'])) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Aspek sikap tidak valid.']);
    exit;
}

if (!in_array($nilai, ['positif', 'negatif'])) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Nilai sikap tidak valid.']);
    exit;
}

$db = getDB();

$stmt = $db->prepare("
    INSERT INTO jurnal_sikap (nis, tanggal, aspek, perilaku, tindak_lanjut, nilai)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('ssssss', $nis, $tanggal, $aspek, $perilaku, $tindak_lanjut, $nilai);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Catatan jurnal sikap berhasil disimpan.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan catatan jurnal sikap: ' . $db->error]);
}

$stmt->close();
$db->close();
