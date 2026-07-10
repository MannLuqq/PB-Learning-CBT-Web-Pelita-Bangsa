<?php
// ============================================================
//  api/delete_admin_superadmin.php — Hapus akun admin secara permanen
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

$ids = $data['ids'] ?? [];
$id = intval($data['id'] ?? 0);

$db = getDB();

// Jika request adalah bulk delete (mengirim array 'ids')
if (!empty($ids) && is_array($ids)) {
    $sanitizedIds = array_filter(array_map('intval', $ids));
    if (empty($sanitizedIds)) {
        $db->close();
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Daftar ID tidak valid.']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
    $query = "DELETE FROM users WHERE id IN ($placeholders) AND role = 'admin'";
    $stmt = $db->prepare($query);

    $types = str_repeat('i', count($sanitizedIds));
    $stmt->bind_param($types, ...$sanitizedIds);

    if ($stmt->execute()) {
        $affected = $db->affected_rows;
        echo json_encode([
            'status'  => 'success',
            'message' => "$affected akun Admin berhasil dihapus secara permanen.",
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus admin secara massal.']);
    }
    $stmt->close();
    $db->close();
    exit;
}

if (empty($id)) {
    $db->close();
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'ID admin wajib diisi.']);
    exit;
}

// Cek dan Hapus
$stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
$stmt->bind_param('i', $id);

if ($stmt->execute() && $db->affected_rows > 0) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Akun Admin berhasil dihapus secara permanen.',
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus admin. Admin tidak ditemukan atau peran tidak sesuai.']);
}
$stmt->close();
$db->close();
