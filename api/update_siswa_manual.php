<?php
// ============================================================
//  api/update_siswa_manual.php — Endpoint edit data siswa
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

$id    = intval($data['id']    ?? 0);
$nis   = trim($data['nis']   ?? '');
$nama  = trim($data['nama']  ?? '');
$kelas = trim($data['kelas'] ?? '');

if (empty($id) || empty($nis) || empty($nama) || empty($kelas)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'ID, NIS, nama, dan kelas wajib diisi.']);
    exit;
}

$db = getDB();

// Cek keberadaan siswa
$stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'siswa' AND deleted_at IS NULL");
$stmt->bind_param('i', $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $stmt->close(); $db->close();
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Data siswa tidak ditemukan.']);
    exit;
}
$stmt->close();

// Cek duplikasi NIS di user lain
$stmt = $db->prepare("SELECT id FROM users WHERE nis_nip = ? AND id != ?");
$stmt->bind_param('si', $nis, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close(); $db->close();
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'NIS sudah terdaftar untuk siswa lain.']);
    exit;
}
$stmt->close();

$email = $nis . '@pb-learning.com';

// Update data siswa
$stmt = $db->prepare("
    UPDATE users 
    SET nama = ?, email = ?, nis_nip = ?, kelas = ? 
    WHERE id = ? AND role = 'siswa'
");
$stmt->bind_param('ssssi', $nama, $email, $nis, $kelas, $id);

if ($stmt->execute()) {
    echo json_encode([
        'status'  => 'success',
        'message' => "Data Siswa $nama berhasil diperbarui.",
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data siswa ke database.']);
}
$stmt->close();
$db->close();
