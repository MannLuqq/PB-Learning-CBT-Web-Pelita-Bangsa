<?php
// ============================================================
//  api/save_siswa_manual.php — Endpoint tambah siswa manual
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

$nis   = trim($data['nis']   ?? '');
$nama  = trim($data['nama']  ?? '');
$kelas = trim($data['kelas'] ?? '');

if (empty($nis) || empty($nama) || empty($kelas)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi.']);
    exit;
}

$db = getDB();

// Cek duplikasi NIS
$stmt = $db->prepare("SELECT id FROM users WHERE nis_nip = ?");
$stmt->bind_param('s', $nis);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    $db->close();
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'NIS sudah terdaftar di database.']);
    exit;
}
$stmt->close();

$email = $nis . '@pb-learning.com';
$password = password_hash('pbresetpass', PASSWORD_BCRYPT);
$role = 'siswa';

$stmt = $db->prepare("INSERT INTO users (nama, email, password, role, nis_nip, kelas) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssss', $nama, $email, $password, $role, $nis, $kelas);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Siswa berhasil disimpan ke database.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan siswa ke database.']);
}
$stmt->close();
$db->close();
