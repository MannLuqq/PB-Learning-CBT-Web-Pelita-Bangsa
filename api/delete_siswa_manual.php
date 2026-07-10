<?php
// ============================================================
//  api/delete_siswa_manual.php — DINONAKTIFKAN
//  Penghapusan siswa sekarang hanya via delete_user_superadmin.php
// ============================================================

header('Content-Type: application/json');
http_response_code(403);
echo json_encode([
    'status'  => 'error',
    'message' => '🚫 Penghapusan data siswa hanya dapat dilakukan oleh Superadmin.',
]);
