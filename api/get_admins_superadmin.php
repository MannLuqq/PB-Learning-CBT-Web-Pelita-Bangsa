<?php
// ============================================================
//  api/get_admins_superadmin.php — Ambil semua admin aktif
//  HANYA bisa diakses oleh role superadmin
//  Method: GET
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
            'message' => '🚫 Akses ditolak. Hanya Superadmin yang dapat mengakses data ini.',
        ]);
        exit;
    }
}

$db = getDB();
$query = "SELECT id, nama, email, created_at FROM users WHERE role = 'admin' AND deleted_at IS NULL ORDER BY nama ASC";
$result = $db->query($query);
$admins = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = [
            'id'         => intval($row['id']),
            'nama'       => $row['nama'],
            'email'      => $row['email'],
            'created_at' => $row['created_at']
        ];
    }
}

$db->close();
echo json_encode($admins);
