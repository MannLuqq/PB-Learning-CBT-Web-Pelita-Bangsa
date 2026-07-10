<?php
// ============================================================
//  api/login.php — Endpoint login
//  Method: POST | Content-Type: application/json
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');
$role     = trim($data['role']     ?? '');

if (empty($email) || empty($password)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Email dan password wajib diisi.']);
    exit;
}

$db = getDB();

// Cari user berdasarkan email atau NIS/NUPTK (yang tidak dihapus)
$stmt = $db->prepare("
    SELECT id, nama, email, password, role, kelas, nis_nip, mata_pelajaran, is_active
    FROM users
    WHERE (email = ? OR nis_nip = ?) AND deleted_at IS NULL
");
$stmt->bind_param('ss', $email, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $db->close();
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Email atau password salah.']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Cek akun aktif
if (!$user['is_active']) {
    $db->close();
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akun Anda dinonaktifkan. Hubungi administrator.']);
    exit;
}

// Cek role (opsional — strict per role)
if (!empty($role)) {
    // Jika role yang dipilih adalah 'admin', izinkan juga jika user ber-role 'superadmin'
    $roleMatch = ($user['role'] === $role) || ($role === 'admin' && $user['role'] === 'superadmin');
    if (!$roleMatch) {
        $db->close();
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Role tidak sesuai. Periksa pilihan peran Anda.']);
        exit;
    }
}

// Verifikasi password
if (!password_verify($password, $user['password'])) {
    $db->close();
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Email atau password salah.']);
    exit;
}

// Catat log login
$logStmt = $db->prepare("INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)");
$ip = $_SERVER['REMOTE_ADDR'];
$logStmt->bind_param('is', $user['id'], $ip);
$logStmt->execute();
$logStmt->close();

$db->close();

// Set session
$_SESSION['edu_user_id']       = $user['id'];
$_SESSION['edu_name']          = $user['nama'];
$_SESSION['edu_email']         = $user['email'];
$_SESSION['edu_role']          = $user['role'];
$_SESSION['edu_mapel']         = $user['mata_pelajaran'];

// Tentukan redirect dashboard
$dashboardMap = [
    'siswa'      => 'pages/siswa/dashboard.html',
    'guru'       => 'pages/guru/dashboard.html',
    'admin'      => 'pages/admin/dashboard.html',
    'superadmin' => 'pages/superadmin/dashboard.html',
];

echo json_encode([
    'status'   => 'success',
    'message'  => 'Login berhasil!',
    'user'     => [
        'id'             => $user['id'],
        'nama'           => $user['nama'],
        'email'          => $user['email'],
        'role'           => $user['role'],
        'kelas'          => $user['kelas'],
        'mata_pelajaran' => $user['mata_pelajaran'],
    ],
    'redirect' => $dashboardMap[$user['role']] ?? 'index.html',
]);
