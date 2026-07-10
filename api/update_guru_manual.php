<?php
// ============================================================
//  api/update_guru_manual.php — Endpoint edit data guru
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

$id             = intval($data['id']             ?? 0);
$nip            = trim($data['nip']            ?? '');
$nama           = trim($data['nama']           ?? '');
$email          = trim($data['email']          ?? '');
$no_hp          = trim($data['no_hp']          ?? '');
$mata_pelajaran = trim($data['mata_pelajaran'] ?? '');

// Validasi field wajib
if (empty($id) || empty($nip) || empty($nama) || empty($email) || empty($mata_pelajaran)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'ID, NUPTK, nama, email, dan mata pelajaran wajib diisi.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
    exit;
}

// Validasi mata pelajaran
$mapelList = [
    'TIK', 'Matematika', 'Seni Rupa', 'Qiraati', 'Bahasa Indonesia',
    'IPS', 'PAI', 'Bahasa Inggris', 'Bahasa Arab', 'Fiqih dan Aqidah', 'IPA'
];
if (!in_array($mata_pelajaran, $mapelList)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Mata pelajaran tidak valid.']);
    exit;
}

$db = getDB();

// Cek keberadaan guru
$stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'guru' AND deleted_at IS NULL");
$stmt->bind_param('i', $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $stmt->close(); $db->close();
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Data guru tidak ditemukan.']);
    exit;
}
$stmt->close();

// Cek duplikasi NUPTK di user lain
$stmt = $db->prepare("SELECT id FROM users WHERE nis_nip = ? AND id != ?");
$stmt->bind_param('si', $nip, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close(); $db->close();
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'NUPTK sudah digunakan oleh akun lain.']);
    exit;
}
$stmt->close();

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

// Update data guru
$stmt = $db->prepare("
    UPDATE users 
    SET nama = ?, email = ?, nis_nip = ?, mata_pelajaran = ?, no_hp = ? 
    WHERE id = ? AND role = 'guru'
");
$stmt->bind_param('sssssi', $nama, $email, $nip, $mata_pelajaran, $no_hp, $id);

if ($stmt->execute()) {
    echo json_encode([
        'status'  => 'success',
        'message' => "Data Guru $nama berhasil diperbarui.",
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data guru ke database.']);
}
$stmt->close();
$db->close();
