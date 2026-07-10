<?php
// ============================================================
//  api/save_buku_induk.php — Endpoint menyimpan/update Buku Induk siswa
//  Method: POST | Content-Type: application/json
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
    exit;
}

$nis            = trim($data['nis'] ?? '');
$nisn           = trim($data['nisn'] ?? '');
$tempat_lahir   = trim($data['tempat_lahir'] ?? '');
$tanggal_lahir  = trim($data['tanggal_lahir'] ?? '');
$jenis_kelamin  = trim($data['jenis_kelamin'] ?? '');
$agama          = trim($data['agama'] ?? '');
$alamat         = trim($data['alamat'] ?? '');
$nama_ayah      = trim($data['nama_ayah'] ?? '');
$nama_ibu       = trim($data['nama_ibu'] ?? '');
$pekerjaan_ortu = trim($data['pekerjaan_ortu'] ?? '');
$no_hp_ortu     = trim($data['no_hp_ortu'] ?? '');
$no_hp_siswa    = trim($data['no_hp_siswa'] ?? ''); // dari tabel users

if (empty($nis)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'NIS wajib diisi.']);
    exit;
}

// Validasi format tanggal_lahir jika tidak kosong
if (!empty($tanggal_lahir)) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_lahir)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Format tanggal lahir tidak valid (harus YYYY-MM-DD).']);
        exit;
    }
} else {
    $tanggal_lahir = null;
}

if (!empty($jenis_kelamin) && !in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'])) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Jenis kelamin tidak valid.']);
    exit;
}

$db = getDB();

// 1. Simpan/Update ke tabel buku_induk
$stmt = $db->prepare("
    INSERT INTO buku_induk (nis, nisn, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, alamat, nama_ayah, nama_ibu, pekerjaan_ortu, no_hp_ortu)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        nisn = VALUES(nisn),
        tempat_lahir = VALUES(tempat_lahir),
        tanggal_lahir = VALUES(tanggal_lahir),
        jenis_kelamin = VALUES(jenis_kelamin),
        agama = VALUES(agama),
        alamat = VALUES(alamat),
        nama_ayah = VALUES(nama_ayah),
        nama_ibu = VALUES(nama_ibu),
        pekerjaan_ortu = VALUES(pekerjaan_ortu),
        no_hp_ortu = VALUES(no_hp_ortu)
");

$stmt->bind_param('sssssssssss', $nis, $nisn, $tempat_lahir, $tanggal_lahir, $jenis_kelamin, $agama, $alamat, $nama_ayah, $nama_ibu, $pekerjaan_ortu, $no_hp_ortu);

$success = $stmt->execute();
$stmt->close();

if ($success) {
    // 2. Jika ada no_hp_siswa, update juga ke tabel users
    if (isset($data['no_hp_siswa'])) {
        $stmt_user = $db->prepare("UPDATE users SET no_hp = ? WHERE nis_nip = ? AND role = 'siswa'");
        $stmt_user->bind_param('ss', $no_hp_siswa, $nis);
        $stmt_user->execute();
        $stmt_user->close();
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Data Buku Induk berhasil disimpan.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data Buku Induk: ' . $db->error]);
}

$db->close();
