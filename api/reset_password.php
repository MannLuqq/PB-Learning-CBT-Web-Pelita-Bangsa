<?php
// ============================================================
//  api/reset_password.php — Reset password user ke default (pbresetpass)
//  Hanya Admin & Superadmin yang bisa mereset siswa/guru
//  Method: POST | Body JSON: { "id": int, "role": "siswa"|"guru"|"admin" }
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

$id   = intval($data['id']   ?? 0);
$role = trim($data['role']   ?? '');

// Validasi
if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'ID user tidak valid.']);
    exit;
}

// Hanya boleh mereset role siswa, guru, atau admin
$allowedRoles = ['siswa', 'guru', 'admin'];
if (!in_array($role, $allowedRoles)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Role tidak valid untuk reset password.']);
    exit;
}

$db = getDB();

// Pastikan user ada, rolenya sesuai, dan tidak terhapus
$stmt = $db->prepare("SELECT nama, role FROM users WHERE id = ? AND role = ? AND deleted_at IS NULL");
$stmt->bind_param('is', $id, $role);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $db->close();
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
    exit;
}

// Hash password default
$defaultPassword = password_hash('pbresetpass', PASSWORD_BCRYPT);

$stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ? AND role = ?");
$stmt->bind_param('sis', $defaultPassword, $id, $role);

if ($stmt->execute() && $db->affected_rows > 0) {
    echo json_encode([
        'status'  => 'success',
        'message' => "Password {$user['nama']} berhasil direset ke password default. Informasikan kepada yang bersangkutan untuk login dengan password: pbresetpass dan segera menggantinya.",
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mereset password.']);
}
$stmt->close();
$db->close();
