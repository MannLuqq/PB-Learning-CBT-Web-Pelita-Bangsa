<?php
// ============================================================
//  api/register.php — Endpoint registrasi akun
//  Method: POST | Content-Type: application/json
//  Roles yang bisa didaftarkan: siswa, guru
//  Role admin & superadmin hanya bisa dibuat via SQL langsung
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$nama           = trim($data['nama']           ?? '');
$email          = trim($data['email']          ?? '');
$password       = trim($data['password']       ?? '');
$role           = trim($data['role']           ?? 'siswa');
$nis_nip        = trim($data['nis_nip']        ?? '');
$kelas          = trim($data['kelas']          ?? '');
$no_hp          = trim($data['no_hp']          ?? '');
$mata_pelajaran = trim($data['mata_pelajaran'] ?? '');

// ── Validasi ──────────────────────────────────────────────
$errors = [];

if (empty($nama))     $errors[] = 'Nama lengkap wajib diisi.';
if (empty($email))    $errors[] = 'Email wajib diisi.';
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
if (empty($password)) $errors[] = 'Password wajib diisi.';
elseif (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

// Blokir role admin & superadmin dari registrasi publik
if (in_array($role, ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Role ini tidak dapat didaftarkan secara publik.']);
    exit;
}

if (!in_array($role, ['siswa', 'guru'])) $errors[] = 'Role tidak valid.';

// Jika guru, mata pelajaran wajib diisi
$mapelList = [
    'TIK', 'Matematika', 'Seni Rupa', 'Qiraati', 'Bahasa Indonesia',
    'IPS', 'PAI', 'Bahasa Inggris', 'Bahasa Arab', 'Fiqih dan Aqidah', 'IPA'
];
if ($role === 'guru') {
    if (empty($mata_pelajaran)) {
        $errors[] = 'Mata pelajaran wajib dipilih untuk akun guru.';
    } elseif (!in_array($mata_pelajaran, $mapelList)) {
        $errors[] = 'Mata pelajaran tidak valid.';
    }
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
    exit;
}

$db = getDB();

// Cek email sudah terdaftar
$check = $db->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param('s', $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    $db->close();
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar. Gunakan email lain.']);
    exit;
}
$check->close();

$hashedPass = password_hash($password, PASSWORD_BCRYPT);

// Jika bukan guru, kosongkan mata_pelajaran
if ($role !== 'guru') $mata_pelajaran = null;

$stmt = $db->prepare("
    INSERT INTO users (nama, email, password, role, nis_nip, kelas, mata_pelajaran, no_hp)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('ssssssss', $nama, $email, $hashedPass, $role, $nis_nip, $kelas, $mata_pelajaran, $no_hp);

if ($stmt->execute()) {
    $stmt->close();
    $db->close();
    echo json_encode([
        'status'  => 'success',
        'message' => 'Akun berhasil dibuat! Silakan login.',
    ]);
} else {
    $stmt->close();
    $db->close();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data. Coba lagi.']);
}
