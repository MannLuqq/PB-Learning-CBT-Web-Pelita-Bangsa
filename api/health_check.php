<?php
// ============================================================
//  api/health_check.php — Health check endpoint untuk hosting
//  Verifikasi koneksi database dan tabel-tabel penting
//  AMAN untuk di-keep di server (tidak mengekspose data sensitif)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$results = [];
$allOk = true;

// 1. Cek koneksi database
try {
    require_once '../config/database.php';
    $db = getDB();
    $results['database'] = ['status' => 'ok', 'message' => 'Koneksi database berhasil'];
} catch (Exception $e) {
    $results['database'] = ['status' => 'error', 'message' => 'Gagal konek database: ' . $e->getMessage()];
    $allOk = false;
}

if ($allOk) {
    // 2. Cek tabel users
    $res = $db->query("SHOW TABLES LIKE 'users'");
    if ($res && $res->num_rows > 0) {
        $results['table_users'] = ['status' => 'ok', 'message' => 'Tabel users ada'];
    } else {
        $results['table_users'] = ['status' => 'error', 'message' => 'Tabel users tidak ditemukan'];
        $allOk = false;
    }

    // 3. Cek kolom deleted_at di tabel users
    $res = $db->query("SHOW COLUMNS FROM users LIKE 'deleted_at'");
    if ($res && $res->num_rows > 0) {
        $results['column_deleted_at'] = ['status' => 'ok', 'message' => 'Kolom deleted_at ada'];
    } else {
        $results['column_deleted_at'] = ['status' => 'error', 'message' => 'Kolom deleted_at tidak ada — jalankan api/update_schema_trash.php'];
        $allOk = false;
    }

    // 4. Cek kolom mata_pelajaran di tabel users
    $res = $db->query("SHOW COLUMNS FROM users LIKE 'mata_pelajaran'");
    if ($res && $res->num_rows > 0) {
        $results['column_mata_pelajaran'] = ['status' => 'ok', 'message' => 'Kolom mata_pelajaran ada'];
    } else {
        $results['column_mata_pelajaran'] = ['status' => 'error', 'message' => 'Kolom mata_pelajaran tidak ada — jalankan api/update_schema.php'];
        $allOk = false;
    }

    // 5. Cek tabel absensi
    $res = $db->query("SHOW TABLES LIKE 'absensi'");
    if ($res && $res->num_rows > 0) {
        $results['table_absensi'] = ['status' => 'ok', 'message' => 'Tabel absensi ada'];
    } else {
        $results['table_absensi'] = ['status' => 'error', 'message' => 'Tabel absensi tidak ditemukan'];
        $allOk = false;
    }

    // 6. Cek role enum termasuk superadmin
    $res = $db->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    if ($res && $row = $res->fetch_assoc()) {
        if (strpos($row['Type'], 'superadmin') !== false) {
            $results['enum_superadmin'] = ['status' => 'ok', 'message' => "Role ENUM termasuk 'superadmin'"];
        } else {
            $results['enum_superadmin'] = ['status' => 'error', 'message' => "Role ENUM belum termasuk 'superadmin' — jalankan api/update_schema.php"];
            $allOk = false;
        }
    }

    // 7. Cek jumlah user aktif
    $res = $db->query("SELECT COUNT(*) as total, role FROM users WHERE deleted_at IS NULL GROUP BY role");
    $userCount = [];
    while ($row = $res->fetch_assoc()) {
        $userCount[$row['role']] = $row['total'];
    }
    $results['user_counts'] = ['status' => 'ok', 'data' => $userCount];

    $db->close();
}

echo json_encode([
    'status'  => $allOk ? 'ok' : 'error',
    'message' => $allOk ? '✅ Semua sistem berjalan normal!' : '❌ Ada masalah yang perlu diperbaiki.',
    'checks'  => $results,
    'timestamp' => date('Y-m-d H:i:s'),
]);
