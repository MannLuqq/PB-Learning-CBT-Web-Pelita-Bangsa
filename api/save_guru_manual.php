<?php
// ============================================================
//  api/save_guru_manual.php — Endpoint tambah guru manual
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

$nip            = trim($data['nip']            ?? '');
$nama           = trim($data['nama']           ?? '');
$email          = trim($data['email']          ?? '');
$no_hp          = trim($data['no_hp']          ?? '');
$mata_pelajaran = trim($data['mata_pelajaran'] ?? '');

// Validasi field wajib
if (empty($nip) || empty($nama) || empty($email) || empty($mata_pelajaran)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'NUPTK, nama, email, dan mata pelajaran wajib diisi.']);
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

// Cek duplikasi NUPTK
$stmt = $db->prepare("SELECT id FROM users WHERE nis_nip = ?");
$stmt->bind_param('s', $nip);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close(); $db->close();
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'NUPTK sudah terdaftar di database.']);
    exit;
}
$stmt->close();

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

// Password default: pbresetpass
$password = password_hash('pbresetpass', PASSWORD_BCRYPT);
$role = 'guru';

$stmt = $db->prepare("
    INSERT INTO users (nama, email, password, role, nis_nip, mata_pelajaran, no_hp)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('sssssss', $nama, $email, $password, $role, $nip, $mata_pelajaran, $no_hp);

if ($stmt->execute()) {
    echo json_encode([
        'status'  => 'success',
        'message' => "Guru $nama ($mata_pelajaran) berhasil disimpan ke database.",
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan guru ke database.']);
}
$stmt->close();
$db->close();
