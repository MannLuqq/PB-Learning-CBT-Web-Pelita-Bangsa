<?php
// ============================================================
//  api/get_buku_induk.php — Endpoint mengambil data Buku Induk siswa
//  Method: GET
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$db = getDB();

$nis = isset($_GET['nis']) ? trim($_GET['nis']) : '';
$kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';

if (!empty($nis)) {
    // Ambil data satu siswa lengkap
    $stmt = $db->prepare("
        SELECT u.nama, u.email, u.nis_nip AS nis, u.kelas, u.no_hp AS no_hp_siswa,
               bi.nisn, bi.tempat_lahir, bi.tanggal_lahir, bi.jenis_kelamin, 
               bi.agama, bi.alamat, bi.nama_ayah, bi.nama_ibu, 
               bi.pekerjaan_ortu, bi.no_hp_ortu
        FROM users u
        LEFT JOIN buku_induk bi ON u.nis_nip = bi.nis
        WHERE u.role = 'siswa' AND u.nis_nip = ?
    ");
    $stmt->bind_param('s', $nis);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Siswa tidak ditemukan.']);
    }
    $stmt->close();
} else {
    // Ambil semua siswa (opsional filter kelas)
    $query = "
        SELECT u.nama, u.nis_nip AS nis, u.kelas, u.no_hp AS no_hp_siswa,
               bi.nisn, bi.tempat_lahir, bi.tanggal_lahir, bi.jenis_kelamin, 
               bi.agama, bi.alamat, bi.nama_ayah, bi.nama_ibu, 
               bi.pekerjaan_ortu, bi.no_hp_ortu
        FROM users u
        LEFT JOIN buku_induk bi ON u.nis_nip = bi.nis
        WHERE u.role = 'siswa'
    ";
    
    if (!empty($kelas)) {
        $query .= " AND u.kelas = '" . $db->real_escape_string($kelas) . "'";
    }
    
    $query .= " ORDER BY u.kelas ASC, u.nama ASC";
    
    $result = $db->query($query);
    $siswa_list = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $siswa_list[] = $row;
        }
    }
    
    echo json_encode(['status' => 'success', 'data' => $siswa_list]);
}

$db->close();
