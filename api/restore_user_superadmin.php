<?php
// ============================================================
//  api/restore_user_superadmin.php — Restore user soft-delete
//  HANYA bisa diakses oleh role superadmin
//  Method: POST | Body JSON: { "id": int }
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();
require_once '../config/database.php';

// ── Cek Role Superadmin ──────────────────────────────────────
$sessionRole = $_SESSION['edu_role'] ?? '';
if ($sessionRole !== 'superadmin') {
    $token = $_SERVER['HTTP_X_SUPERADMIN_TOKEN'] ?? '';
    if (empty($token) || $token !== 'SA_' . date('Ymd')) {
        http_response_code(403);
        echo json_encode([
            'status'  => 'error',
            'message' => '🚫 Akses ditolak. Hanya Superadmin yang dapat mengakses data ini.',
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

$ids = $data['ids'] ?? [];
$userId = intval($data['id'] ?? 0);

$db = getDB();

// Jika request adalah bulk restore (mengirim array 'ids')
if (!empty($ids) && is_array($ids)) {
    $sanitizedIds = array_filter(array_map('intval', $ids));
    if (empty($sanitizedIds)) {
        $db->close();
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Daftar ID tidak valid.']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
    $query = "UPDATE users SET deleted_at = NULL WHERE id IN ($placeholders) AND deleted_at IS NOT NULL";
    $stmt = $db->prepare($query);

    $types = str_repeat('i', count($sanitizedIds));
    $stmt->bind_param($types, ...$sanitizedIds);

    if ($stmt->execute()) {
        $affected = $db->affected_rows;
        echo json_encode([
            'status'  => 'success',
            'message' => "$affected data berhasil dipulihkan.",
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal memulihkan data secara massal.']);
    }
    $stmt->close();
    $db->close();
    exit;
}

if ($userId <= 0) {
    $db->close();
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'ID user tidak valid.']);
    exit;
}

// Pastikan user ada dan soft-deleted
$check = $db->prepare("SELECT nama, role FROM users WHERE id = ? AND deleted_at IS NOT NULL");
$check->bind_param('i', $userId);
$check->execute();
$user = $check->get_result()->fetch_assoc();
$check->close();

if (!$user) {
    $db->close();
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan di tempat sampah atau sudah aktif.']);
    exit;
}

$stmt = $db->prepare("UPDATE users SET deleted_at = NULL WHERE id = ?");
$stmt->bind_param('i', $userId);

if ($stmt->execute() && $db->affected_rows > 0) {
    echo json_encode([
        'status'  => 'success',
        'message' => "Data {$user['role']} \"{$user['nama']}\" berhasil dipulihkan.",
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal memulihkan data.']);
}
$stmt->close();
$db->close();
