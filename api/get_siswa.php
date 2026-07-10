<?php
// ============================================================
//  api/get_siswa.php — Endpoint mengambil data siswa per kelas
//  Method: GET
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$db = getDB();

$query = "SELECT id, nama, nis_nip, kelas FROM users WHERE role = 'siswa' AND deleted_at IS NULL ORDER BY kelas ASC, nama ASC";
$result = $db->query($query);

$siswa_data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $kelas = $row['kelas'] ? $row['kelas'] : 'VII';
        if (!isset($siswa_data[$kelas])) {
            $siswa_data[$kelas] = [];
        }
        $siswa_data[$kelas][] = [
            'id'   => $row['id'],
            'no'   => count($siswa_data[$kelas]) + 1,
            'nis'  => $row['nis_nip'],
            'nama' => $row['nama']
        ];
    }
}

$db->close();

echo json_encode($siswa_data);
