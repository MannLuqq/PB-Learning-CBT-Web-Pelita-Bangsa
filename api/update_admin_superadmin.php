<?php
// ============================================================
//  api/update_admin_superadmin.php — Edit akun admin
//  HANYA bisa diakses oleh role superadmin
//  Method: POST
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();
require_once '../config/database.php';

// Cek Role Superadmin
$sessionRole = $_SESSION['edu_role'] ?? '';
if ($sessionRole !== 'superadmin') {
    $token = $_SERVER['HTTP_X_SUPERADMIN_TOKEN'] ?? '';
    if (empty($token) || $token !== 'SA_' . date('Ymd')) {
        http_response_code(403);
        echo json_encode([
            'status'  => 'error',
            'message' => '🚫 Akses ditolak. Hanya Superadmin yang dapat melakukan tindakan ini.',
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$id    = intval($data['id']    ?? 0);
$nama  = trim($data['nama']     ?? '');
$email = trim($data['email']    ?? '');

if (empty($id) || empty($nama) || empty($email)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'ID, nama, dan email wajib diisi.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
    exit;
}

$db = getDB();

// Pastikan user ada dan role-nya admin
$checkAdmin = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
$checkAdmin->bind_param('i', $id);
$checkAdmin->execute();
if ($checkAdmin->get_result()->num_rows === 0) {
    $checkAdmin->close();
    $db->close();
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Akun Admin tidak ditemukan.']);
    exit;
}
$checkAdmin->close();

// Cek duplikasi Email di user lain
$stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param('si', $email, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close(); $db->close();
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'Email sudah digunakan oleh akun lain.']);
    exit;
}
$stmt->close();

$stmt = $db->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ? AND role = 'admin'");
$stmt->bind_param('ssi', $nama, $email, $id);

if ($stmt->execute()) {
    echo json_encode([
        'status'  => 'success',
        'message' => "Akun Admin $nama berhasil diperbarui.",
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui admin di database.']);
}
$stmt->close();
$db->close();
