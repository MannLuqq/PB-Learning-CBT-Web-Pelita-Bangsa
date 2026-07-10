<?php
// ============================================================
//  api/delete_guru_manual.php — DINONAKTIFKAN
//  Penghapusan guru sekarang hanya via delete_user_superadmin.php
// ============================================================

header('Content-Type: application/json');
http_response_code(403);
echo json_encode([
    'status'  => 'error',
    'message' => '🚫 Penghapusan data guru hanya dapat dilakukan oleh Superadmin.',
]);
