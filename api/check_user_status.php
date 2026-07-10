<?php
// ============================================================
//  api/check_user_status.php — Cek apakah user masih ada di DB
//  Method: POST
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$userId = intval($data['id'] ?? 0);

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID user tidak valid.']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND deleted_at IS NULL");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan query database.']);
    exit;
}

$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->store_result();

$exists = $stmt->num_rows > 0;

$stmt->close();
$db->close();

if ($exists) {
    echo json_encode(['status' => 'active']);
} else {
    echo json_encode(['status' => 'deleted', 'message' => 'Akun Anda telah dihapus oleh Superadmin.']);
}
