<?php
// ============================================================
//  api/import_absensi_excel.php — Import absensi dari file CSV
//  Method: POST (multipart/form-data)
//  Fields: excel_file (CSV), kelas (string), tanggal (date Y-m-d)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Matikan display errors HTML agar tidak merusak JSON jika ada error kecil
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}

// Validasi file
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan atau gagal diupload.']);
    exit;
}

$kelas   = trim($_POST['kelas']   ?? '');
$tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));

if (empty($kelas)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Kelas wajib diisi.']);
    exit;
}

$file = $_FILES['excel_file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($ext !== 'csv') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format file harus .csv. Silakan Save As ke CSV terlebih dahulu jika menggunakan Excel.']);
    exit;
}

// Baca file
$tmpPath = $file['tmp_name'];
$allRows = [];

$handle = fopen($tmpPath, 'r');
if (!$handle) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file CSV.']);
    exit;
}

// Deteksi separator
$firstLine = fgets($handle);
rewind($handle);
$sep = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

while (($row = fgetcsv($handle, 1000, $sep)) !== false) {
    $allRows[] = array_map('trim', $row);
}
fclose($handle);

if (empty($allRows)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'File CSV kosong.']);
    exit;
}

// Helper untuk prepare statement dengan error handling valid JSON
function prepareQuery($db, $sql) {
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Database error: ' . $db->error,
            'sql'     => $sql
        ]);
        exit;
    }
    return $stmt;
}

// Cari header row secara dinamis (biasanya baris ke-1 atau ke-2)
$headerRowIdx = -1;
$idxNama = -1;
$idxNis  = -1;
$idxKelas = -1;
$idxStatus = -1;
$idxKeterangan = -1;

$scanLimit = min(5, count($allRows));
for ($r = 0; $r < $scanLimit; $r++) {
    $row = $allRows[$r];
    $tempIdxNama = -1;
    $tempIdxNis = -1;
    $tempIdxKelas = -1;
    $tempIdxStatus = -1;
    $tempIdxKeterangan = -1;

    foreach ($row as $idx => $colValue) {
        // Hapus BOM UTF-8 dan karakter non-printable
        $colName = strtolower(trim($colValue));
        $colName = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $colName);
        $colName = trim($colName);

        if (strpos($colName, 'nuptk') !== false || strpos($colName, 'nisn') !== false || strpos($colName, 'nis') !== false || strpos($colName, 'nip') !== false) {
            $tempIdxNis = $idx;
        } elseif (strpos($colName, 'nama') !== false || strpos($colName, 'siswa') !== false) {
            $tempIdxNama = $idx;
        } elseif (strpos($colName, 'status') !== false || strpos($colName, 'kehadiran') !== false || strpos($colName, 'absen') !== false) {
            $tempIdxStatus = $idx;
        } elseif (strpos($colName, 'keterangan') !== false || strpos($colName, 'ket') !== false || strpos($colName, 'notes') !== false) {
            $tempIdxKeterangan = $idx;
        } elseif (strpos($colName, 'kelas') !== false) {
            $tempIdxKelas = $idx;
        }
    }

    // Header valid minimal mendeteksi (Nama ATAU NIS) DAN Status Kehadiran
    if (($tempIdxNama !== -1 || $tempIdxNis !== -1) && $tempIdxStatus !== -1) {
        $headerRowIdx = $r;
        $idxNama = $tempIdxNama;
        $idxNis = $tempIdxNis;
        $idxKelas = $tempIdxKelas;
        $idxStatus = $tempIdxStatus;
        $idxKeterangan = $tempIdxKeterangan;
        break;
    }
}

// Fallback jika tidak terdeteksi
if ($headerRowIdx === -1) {
    $idxNis = 0;
    $idxNama = 1;
    $idxStatus = 2;
    $idxKeterangan = 3;
    $idxKelas = -1;
    $startRowIdx = 0;
} else {
    $startRowIdx = $headerRowIdx + 1;
}

// Filter baris data saja (mulai setelah header row)
$dataRows = [];
for ($i = $startRowIdx; $i < count($allRows); $i++) {
    $row = $allRows[$i];
    // Lewati baris kosong
    if (empty($row) || (count($row) === 1 && $row[0] === null) || trim(implode('', $row)) === '') {
        continue;
    }
    $dataRows[] = [
        'original_row_num' => $i + 1,
        'data' => $row
    ];
}

if (empty($dataRows)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada data siswa untuk diimpor.']);
    exit;
}

$db = getDB();

$saved  = 0;
$errors = [];

// Prepare query absensi
$stmt = prepareQuery($db, "
    INSERT INTO absensi (nis, tanggal, status, keterangan)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE status = VALUES(status), keterangan = VALUES(keterangan)
");

foreach ($dataRows as $item) {
    $row = $item['data'];
    $csvLineNum = $item['original_row_num'];

    // Cek kolom minimal
    $requiredCols = max($idxNis, $idxNama, $idxStatus);
    if (count($row) <= $requiredCols) {
        $errors[] = "Baris $csvLineNum: kolom tidak lengkap.";
        continue;
    }

    $nis        = ($idxNis !== -1 && isset($row[$idxNis])) ? trim($row[$idxNis]) : '';
    $nama       = ($idxNama !== -1 && isset($row[$idxNama])) ? trim($row[$idxNama]) : '';
    $statusRaw  = ($idxStatus !== -1 && isset($row[$idxStatus])) ? strtolower(trim($row[$idxStatus])) : '';
    $keterangan = ($idxKeterangan !== -1 && isset($row[$idxKeterangan])) ? trim($row[$idxKeterangan]) : '';
    $rowKelas   = ($idxKelas !== -1 && isset($row[$idxKelas])) ? trim($row[$idxKelas]) : '';

    // Validasi kelas jika ada di baris
    if ($idxKelas !== -1 && !empty($rowKelas) && !empty($kelas)) {
        $normRowKelas = strtoupper(str_replace(['kelas', ' '], '', $rowKelas));
        $normSelected = strtoupper(str_replace(['kelas', ' '], '', $kelas));
        if ($normRowKelas !== $normSelected) {
            $errors[] = "Baris $csvLineNum: Kelas '$rowKelas' tidak sesuai dengan kelas terpilih '$kelas'.";
            continue;
        }
    }

    $studentId = 0;
    $dbNis = '';

    // 1. Cari berdasarkan NIS dari CSV (mendukung pencarian eksak & padding leading zero)
    if (!empty($nis)) {
        $findStmt = prepareQuery($db, "SELECT id, nis_nip FROM users WHERE (nis_nip = ? OR nis_nip = ?) AND role = 'siswa' AND deleted_at IS NULL LIMIT 1");
        $nisPadded = is_numeric($nis) ? str_pad($nis, 10, '0', STR_PAD_LEFT) : $nis;
        $findStmt->bind_param('ss', $nis, $nisPadded);
        $findStmt->execute();
        $resFind = $findStmt->get_result();
        if ($resFind->num_rows > 0) {
            $rowUser = $resFind->fetch_assoc();
            $studentId = $rowUser['id'];
            $dbNis = $rowUser['nis_nip'];
        }
        $findStmt->close();
    }

    // 2. Cari berdasarkan Nama & Kelas jika belum ketemu
    if (empty($dbNis) && !empty($nama)) {
        $findStmt = prepareQuery($db, "SELECT id, nis_nip FROM users WHERE nama = ? AND kelas = ? AND role = 'siswa' AND deleted_at IS NULL LIMIT 1");
        $findStmt->bind_param('ss', $nama, $kelas);
        $findStmt->execute();
        $resFind = $findStmt->get_result();
        if ($resFind->num_rows > 0) {
            $rowUser = $resFind->fetch_assoc();
            $studentId = $rowUser['id'];
            $dbNis = $rowUser['nis_nip'];
        }
        $findStmt->close();
    }

    // 3. Cari berdasarkan Nama saja jika belum ketemu (mengabaikan kelas)
    if (empty($dbNis) && !empty($nama)) {
        $findStmt = prepareQuery($db, "SELECT id, nis_nip FROM users WHERE nama = ? AND role = 'siswa' AND deleted_at IS NULL LIMIT 1");
        $findStmt->bind_param('s', $nama);
        $findStmt->execute();
        $resFind = $findStmt->get_result();
        if ($resFind->num_rows > 0) {
            $rowUser = $resFind->fetch_assoc();
            $studentId = $rowUser['id'];
            $dbNis = $rowUser['nis_nip'];
        }
        $findStmt->close();
    }

    // 4. Jika siswa ada di DB tetapi tidak punya NIS, buatkan NIS virtual otomatis
    if ($studentId > 0 && empty($dbNis)) {
        do {
            $dbNis = 'PB' . strtoupper(str_replace(' ', '', $kelas)) . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $checkStmt = prepareQuery($db, "SELECT id FROM users WHERE nis_nip = ?");
            $checkStmt->bind_param('s', $dbNis);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->num_rows > 0;
            $checkStmt->close();
        } while ($exists);

        $updateStmt = prepareQuery($db, "UPDATE users SET nis_nip = ?, email = ? WHERE id = ?");
        $email = $dbNis . '@pb-learning.com';
        $updateStmt->bind_param('ssi', $dbNis, $email, $studentId);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // 5. Jika siswa benar-benar baru (tidak ada di DB), buatkan siswa baru secara otomatis
    if ($studentId === 0) {
        if (empty($nama)) {
            $errors[] = "Baris $csvLineNum: Nama kosong, dilewati.";
            continue;
        }

        do {
            $dbNis = 'PB' . strtoupper(str_replace(' ', '', $kelas)) . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $checkStmt = prepareQuery($db, "SELECT id FROM users WHERE nis_nip = ?");
            $checkStmt->bind_param('s', $dbNis);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->num_rows > 0;
            $checkStmt->close();
        } while ($exists);

        $email = $dbNis . '@pb-learning.com';
        $passwordHash = password_hash($dbNis, PASSWORD_BCRYPT);
        $role = 'siswa';
        
        $insertUserStmt = prepareQuery($db, "INSERT INTO users (nama, email, password, role, nis_nip, kelas, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $insertUserStmt->bind_param('ssssss', $nama, $email, $passwordHash, $role, $dbNis, $kelas);
        $insertUserStmt->execute();
        $insertUserStmt->close();
    }

    $nis = $dbNis;

    // Normalisasi status
    $statusMap = [
        'h' => 'hadir', 'hadir' => 'hadir', 'present' => 'hadir',
        's' => 'sakit', 'sakit' => 'sakit', 'sick' => 'sakit',
        'i' => 'izin', 'izin' => 'izin', 'permission' => 'izin',
        'a' => 'alpa', 'alpa' => 'alpa', 'alpha' => 'alpa', 'absent' => 'alpa',
    ];
    $status = $statusMap[$statusRaw] ?? '';

    if (empty($status)) {
        $errors[] = "Baris $csvLineNum: status '$statusRaw' tidak dikenal (gunakan: H/Hadir, S/Sakit, I/Izin, A/Alpa).";
        continue;
    }

    $stmt->bind_param('ssss', $nis, $tanggal, $status, $keterangan);
    if ($stmt->execute()) {
        $saved++;
    } else {
        $errors[] = "Baris $csvLineNum: gagal simpan NIS/NUPTK $nis.";
    }
}

$stmt->close();
$db->close();

if ($saved === 0 && !empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Tidak ada data yang berhasil diimpor.',
        'errors'  => $errors,
    ]);
} else {
    echo json_encode([
        'status'  => 'success',
        'message' => "$saved data absensi berhasil diimpor untuk kelas $kelas tanggal $tanggal.",
        'saved'   => $saved,
        'errors'  => $errors,
    ]);
}
