<?php
// ============================================================
//  api/delete_jurnal_sikap.php — Endpoint hapus catatan jurnal sikap
//  Method: POST
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
if (!$data) $data = $_POST;

$id = intval($data['id'] ?? 0);

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

$db = getDB();

$stmt = $db->prepare("DELETE FROM jurnal_sikap WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    if ($db->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Catatan jurnal sikap berhasil dihapus.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Catatan jurnal sikap tidak ditemukan.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus catatan jurnal sikap.']);
}
$stmt->close();
$db->close();
