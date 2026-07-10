<?php
// ============================================================
//  api/get_jurnal_sikap.php — Endpoint mengambil data jurnal sikap
//  Method: GET
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$db = getDB();

$kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';
$nis   = isset($_GET['nis']) ? trim($_GET['nis']) : '';

$query = "SELECT js.id, js.nis, js.tanggal, js.aspek, js.perilaku, js.tindak_lanjut, js.nilai, u.nama, u.kelas 
          FROM jurnal_sikap js 
          JOIN users u ON js.nis = u.nis_nip 
          WHERE 1=1";

if (!empty($kelas)) {
    $query .= " AND u.kelas = '" . $db->real_escape_string($kelas) . "'";
}
if (!empty($nis)) {
    $query .= " AND js.nis = '" . $db->real_escape_string($nis) . "'";
}

$query .= " ORDER BY js.tanggal DESC, js.id DESC";

$result = $db->query($query);
$logs = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'id'            => $row['id'],
            'nis'           => $row['nis'],
            'nama'          => $row['nama'],
            'kelas'         => $row['kelas'],
            'tanggal'       => $row['tanggal'],
            'aspek'         => $row['aspek'],
            'perilaku'      => $row['perilaku'],
            'tindak_lanjut' => $row['tindak_lanjut'],
            'nilai'         => $row['nilai']
        ];
    }
}

$db->close();
echo json_encode($logs);
