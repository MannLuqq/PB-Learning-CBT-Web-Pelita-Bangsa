<?php
// ============================================================
//  api/export_clean_sql.php — Generate SQL dump bersih untuk hosting
//  Hapus data test, sisakan akun legit saja
//  HAPUS FILE INI setelah digunakan!
// ============================================================

header('Content-Type: text/plain; charset=utf-8');
require_once '../config/database.php';

$db = getDB();

// Hapus akun test dari database lokal dulu
$testIds = [482, 485, 486]; // Julianti, Test Siswa x2
foreach ($testIds as $tid) {
    $del = $db->prepare("DELETE FROM users WHERE id = ? AND (nama LIKE '%Test%' OR email LIKE '%testsiswa%' OR email LIKE '%julianti123%')");
    $del->bind_param('i', $tid);
    $del->execute();
    $del->close();
}

$deleted = $db->affected_rows;
echo "Deleted $deleted test user(s) from database.\n";
echo "Note: Run mysqldump setelah ini untuk membuat SQL dump yang bersih.\n";

$db->close();
