<?php
// ============================================================
//  api/delete_user_superadmin.php — Endpoint hapus siswa/guru
//  HANYA bisa diakses oleh role superadmin
//  Method: POST | Body JSON: { "id": int, "type": "siswa"|"guru" }
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once '../config/database.php';

// ── Cek Role Superadmin ──────────────────────────────────────
// Cek dari session PHP
$sessionRole = $_SESSION['edu_role'] ?? '';

// Fallback: cek header X-Superadmin-Token (untuk request non-session)
if ($sessionRole !== 'superadmin') {
    $token = $_SERVER['HTTP_X_SUPERADMIN_TOKEN'] ?? '';
    // Token tambahan wajib dikirim dari frontend
    if (empty($token) || $token !== 'SA_' . date('Ymd')) {
        http_response_code(403);
        echo json_encode([
            'status'  => 'error',
            'message' => '🚫 Akses ditolak. Hanya Superadmin yang dapat menghapus data.',
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

$userIds = $data['ids'] ?? [];
$userId  = intval($data['id']      ?? 0);
$nisNip  = trim($data['nis_nip']   ?? '');
$type    = trim($data['type']      ?? ''); // 'siswa' atau 'guru'

if (!in_array($type, ['siswa', 'guru'])) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Tipe user tidak valid (siswa/guru).']);
    exit;
}

$db = getDB();

// Jika request adalah bulk delete (mengirim array 'ids')
if (!empty($userIds) && is_array($userIds)) {
    $sanitizedIds = array_filter(array_map('intval', $userIds));
    if (empty($sanitizedIds)) {
        $db->close();
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Daftar ID tidak valid.']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
    $query = "UPDATE users SET deleted_at = NOW() WHERE id IN ($placeholders) AND role = ?";
    $stmt = $db->prepare($query);

    $types = str_repeat('i', count($sanitizedIds)) . 's';
    $params = array_merge($sanitizedIds, [$type]);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $affected = $db->affected_rows;
        echo json_encode([
            'status'  => 'success',
            'message' => "$affected data {$type} berhasil dipindahkan ke tempat sampah.",
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan data ke tempat sampah.']);
    }
    $stmt->close();
    $db->close();
    exit;
}

// Jika ID tidak dikirim, cari by NIS/NIP
if ($userId <= 0 && !empty($nisNip)) {
    $find = $db->prepare("SELECT id FROM users WHERE nis_nip = ? AND role = ?");
    $find->bind_param('ss', $nisNip, $type);
    $find->execute();
    $row = $find->get_result()->fetch_assoc();
    $find->close();
    if ($row) {
        $userId = $row['id'];
    } else {
        $db->close();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => "Data $type dengan NIS/NIP $nisNip tidak ditemukan."]);
        exit;
    }
}

if ($userId <= 0) {
    $db->close();
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'ID atau NIS/NIP user tidak valid.']);
    exit;
}

// Pastikan user yang akan dihapus memang role yang sesuai
$check = $db->prepare("SELECT nama, role FROM users WHERE id = ?");
$check->bind_param('i', $userId);
$check->execute();
$user = $check->get_result()->fetch_assoc();
$check->close();

if (!$user) {
    $db->close();
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
    exit;
}

if ($user['role'] !== $type) {
    $db->close();
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => "User ini bukan $type, tidak dapat dihapus."]);
    exit;
}

// Blokir penghapusan akun superadmin/admin
if (in_array($user['role'], ['admin', 'superadmin'])) {
    $db->close();
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akun admin/superadmin tidak dapat dihapus lewat endpoint ini.']);
    exit;
}

$stmt = $db->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ? AND role = ?");
$stmt->bind_param('is', $userId, $type);

if ($stmt->execute() && $db->affected_rows > 0) {
    echo json_encode([
        'status'  => 'success',
        'message' => "Data {$type} \"{$user['nama']}\" berhasil dipindahkan ke tempat sampah.",
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan data ke tempat sampah.']);
}
$stmt->close();
$db->close();

