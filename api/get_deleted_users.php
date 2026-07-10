<?php
// ============================================================
//  api/get_deleted_users.php — Endpoint mengambil data user terhapus (soft-delete)
//  HANYA bisa diakses oleh role superadmin
//  Method: GET
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

$db = getDB();
$query = "SELECT id, nama, email, role, kelas, nis_nip, mata_pelajaran, deleted_at 
          FROM users 
          WHERE deleted_at IS NOT NULL 
          ORDER BY deleted_at DESC";

$result = $db->query($query);
$deleted_users = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $deleted_users[] = [
            'id'             => intval($row['id']),
            'nama'           => $row['nama'],
            'email'          => $row['email'],
            'role'           => $row['role'],
            'kelas'          => $row['kelas'] ?? '',
            'nis_nip'        => $row['nis_nip'] ?? '',
            'mata_pelajaran' => $row['mata_pelajaran'] ?? '',
            'deleted_at'     => $row['deleted_at'],
        ];
    }
}

$db->close();
echo json_encode($deleted_users);
