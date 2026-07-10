<?php
// ============================================================
//  api/get_absensi.php — Endpoint mengambil data absensi
//  Method: GET
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$db = getDB();

$query = "
    SELECT a.nis, a.tanggal, a.status, u.kelas
    FROM absensi a
    JOIN users u ON a.nis = u.nis_nip
    ORDER BY a.tanggal ASC
";

$result = $db->query($query);
$abs_data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $key = $row['tanggal'] . '_' . ($row['kelas'] ? $row['kelas'] : 'VII');
        if (!isset($abs_data[$key])) {
            $abs_data[$key] = [];
        }
        $abs_data[$key][$row['nis']] = $row['status'];
    }
}

$db->close();

echo json_encode($abs_data);
