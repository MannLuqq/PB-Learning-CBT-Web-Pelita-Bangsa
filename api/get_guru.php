<?php
// ============================================================
//  api/get_guru.php — Endpoint mengambil data guru
//  Method: GET
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$db = getDB();

$query  = "SELECT id, nama, email, nis_nip, mata_pelajaran, no_hp FROM users WHERE role = 'guru' AND deleted_at IS NULL ORDER BY mata_pelajaran ASC, nama ASC";
$result = $db->query($query);

$guru_data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $guru_data[] = [
            'id'             => $row['id'],
            'no'             => count($guru_data) + 1,
            'nip'            => $row['nis_nip']        ?? '',
            'nama'           => $row['nama'],
            'email'          => $row['email'],
            'mata_pelajaran' => $row['mata_pelajaran'] ?? '-',
            'no_hp'          => $row['no_hp']          ?? '',
        ];
    }
}

$db->close();
echo json_encode($guru_data);
