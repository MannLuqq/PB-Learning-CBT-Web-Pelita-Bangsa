<?php
// ============================================================
//  api/save_admin_superadmin.php — Buat admin baru
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

$nama     = trim($data['nama']     ?? '');
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (empty($nama) || empty($email) || empty($password)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Nama, email, dan password wajib diisi.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
    exit;
}

$db = getDB();

// Cek duplikasi Email
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close(); $db->close();
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar. Gunakan email lain.']);
    exit;
}
$stmt->close();

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$role = 'admin';

$stmt = $db->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $nama, $email, $hashedPassword, $role);

if ($stmt->execute()) {
    echo json_encode([
        'status'  => 'success',
        'message' => "Admin $nama berhasil dibuat.",
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat admin di database.']);
}
$stmt->close();
$db->close();
