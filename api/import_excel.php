<?php
// ============================================================
//  api/import_excel.php — Import data siswa dari file .xlsx
//  Menggunakan PhpSpreadsheet (PHP murni, tanpa Python)
//  Method: POST | Field: excel_file (.xlsx)
//  Kolom Excel: A=NIS, B=Nama, C=Kelas
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/database.php';

// ── Validasi Method ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}

// ── Validasi File Upload ─────────────────────────────────────
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan atau gagal diupload.']);
    exit;
}

$file = $_FILES['excel_file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format file harus .xlsx, .xls, atau .csv']);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Ukuran file maksimal 10MB.']);
    exit;
}

$sheetsData = [];

if ($ext === 'csv') {
    // Baca CSV dengan PHP murni
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal membuka file CSV.']);
        exit;
    }
    
    // Deteksi & skip BOM (UTF-8 BOM dari Excel)
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }
    
    $firstLine = fgets($handle);
    rewind($handle);
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }
    $sep = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
    
    $csvRows = [];
    while (($row = fgetcsv($handle, 2000, $sep)) !== false) {
        $csvRows[] = array_map('trim', $row);
    }
    fclose($handle);
    
    $sheetsData[] = [
        'title' => 'CSV',
        'rows'  => $csvRows
    ];
} else {
    // ── Autoload PhpSpreadsheet ─────────────────────────────────
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Library PhpSpreadsheet belum terinstall. Pastikan folder vendor/ ada di server atau gunakan format .csv.'
        ]);
        exit;
    }
    require_once $autoload;

    // ── Baca Excel dengan PhpSpreadsheet ────────────────────────
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetsData[] = [
                'title' => $sheet->getTitle(),
                'rows'  => $sheet->toArray(null, true, true, false)
            ];
        }
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file Excel: ' . $e->getMessage()]);
        exit;
    }
}

// Check if there is any data in all sheets combined
$hasData = false;
foreach ($sheetsData as $sheet) {
    if (!empty($sheet['rows']) && count($sheet['rows']) >= 1) {
        $hasData = true;
        break;
    }
}

if (!$hasData) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'File kosong. Pastikan data siswa ada dalam sheet Excel.']);
    exit;
}

// ── Fungsi Helper ───────────────────────────────────────────
function detectKelasFromText($text) {
    $text = strtoupper($text);
    if (strpos($text, 'VIII') !== false) return 'VIII';
    if (strpos($text, 'VII') !== false) return 'VII';
    if (strpos($text, 'IX') !== false) return 'IX';
    if (preg_match('/\b(7|VII)\b/', $text)) return 'VII';
    if (preg_match('/\b(8|VIII)\b/', $text)) return 'VIII';
    if (preg_match('/\b(9|IX)\b/', $text)) return 'IX';
    return '';
}

function cleanNis($nis) {
    $nis = trim((string)$nis);
    if ($nis === '' || strtolower($nis) === 'none') {
        return '';
    }
    // Hapus suffix desimal .0 dari excel
    if (preg_match('/^\d+\.0$/', $nis)) {
        $nis = substr($nis, 0, -2);
    }
    // Ubah format notasi ilmiah kembali ke angka
    if (stripos($nis, 'e') !== false && is_numeric($nis)) {
        $nis = sprintf('%.0f', (double)$nis);
    }
    return $nis;
}

// ── Proses Data ke Database ──────────────────────────────────
$db = getDB();

$saved  = 0;
$skipped = 0;
$errors = [];

$stmt = $db->prepare("
    INSERT INTO users (nis_nip, nama, kelas, email, password, role, is_active)
    VALUES (?, ?, ?, ?, ?, 'siswa', 1)
    ON DUPLICATE KEY UPDATE
        nama    = VALUES(nama),
        kelas   = VALUES(kelas)
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan query: ' . $db->error]);
    exit;
}

foreach ($sheetsData as $sheetData) {
    $sheetTitle = $sheetData['title'];
    $rows = $sheetData['rows'];
    
    $headerIndex = -1;
    $colName = -1;
    $colNis = -1;
    $colKelas = -1;
    
    // Cari baris header pada 6 baris pertama
    foreach ($rows as $rowIndex => $row) {
        if ($rowIndex > 5) break;
        $foundName = false;
        $tempColName = -1;
        
        foreach ($row as $colIndex => $val) {
            if ($val === null) continue;
            $valClean = strtolower(trim((string)$val));
            if (strpos($valClean, 'nama') !== false) {
                $tempColName = $colIndex;
                $foundName = true;
            }
        }
        
        if ($foundName) {
            $headerIndex = $rowIndex;
            $colName = $tempColName;
            // Deteksi kolom lain pada baris header yang sama
            foreach ($row as $colIndex => $val) {
                if ($val === null) continue;
                $valClean = strtolower(trim((string)$val));
                if ($colIndex === $colName) continue;
                if (strpos($valClean, 'nis') !== false || $valClean === 'nip') {
                    $colNis = $colIndex;
                }
                if (strpos($valClean, 'kelas') !== false) {
                    $colKelas = $colIndex;
                }
            }
            break;
        }
    }
    
    // Tentukan kelas default untuk sheet ini
    $sheetKelas = detectKelasFromText($sheetTitle);
    if ($sheetKelas === '' && $headerIndex !== -1) {
        for ($r = 0; $r < $headerIndex; $r++) {
            foreach ($rows[$r] as $cellVal) {
                if ($cellVal !== null) {
                    $detected = detectKelasFromText((string)$cellVal);
                    if ($detected !== '') {
                        $sheetKelas = $detected;
                        break 2;
                    }
                }
            }
        }
    }
    
    $dataStartIndex = ($headerIndex !== -1) ? $headerIndex + 1 : 0;
    $dataRows = array_slice($rows, $dataStartIndex);
    
    foreach ($dataRows as $rowIndex => $row) {
        $lineNum = $dataStartIndex + $rowIndex + 1;
        $locStr = ($sheetTitle !== 'CSV') ? "Sheet '$sheetTitle' Baris $lineNum" : "Baris $lineNum";
        
        $nama = '';
        if ($colName !== -1 && isset($row[$colName])) {
            $nama = trim((string)$row[$colName]);
        } elseif ($headerIndex === -1 && isset($row[1])) {
            $nama = trim((string)$row[1]);
        }
        
        $nis = '';
        if ($colNis !== -1 && isset($row[$colNis])) {
            $nis = trim((string)$row[$colNis]);
        } elseif ($headerIndex === -1 && isset($row[0])) {
            $nis = trim((string)$row[0]);
        }
        
        $kelas = '';
        if ($colKelas !== -1 && isset($row[$colKelas])) {
            $kelas = trim((string)$row[$colKelas]);
        } elseif ($headerIndex === -1 && isset($row[2])) {
            $kelas = trim((string)$row[2]);
        }
        
        if ($kelas === '') {
            $kelas = $sheetKelas;
        }
        
        // Skip baris kosong
        $allEmpty = true;
        foreach ($row as $val) {
            if ($val !== null && trim((string)$val) !== '') {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            $skipped++;
            continue;
        }
        
        if ($nama === '') {
            $errors[] = "$locStr: Nama kosong, dilewati.";
            $skipped++;
            continue;
        }
        
        $nis = cleanNis($nis);
        
        // Jika NIS kosong, buat NIS virtual unik yang deterministik berdasarkan Nama dan Kelas
        // agar tidak terjadi duplikasi jika file di-import berulang kali.
        if ($nis === '') {
            $cleanName = preg_replace('/[^A-Za-z0-9]/', '', $nama);
            $hashVal = substr(md5(strtolower($cleanName . '|' . $kelas)), 0, 8);
            $nis = 'PB' . strtoupper(str_replace(' ', '', $kelas)) . strtoupper($hashVal);
        }
        
        // Password default = NIS (hash bcrypt)
        $passwordHash = password_hash($nis, PASSWORD_BCRYPT);
        $email = $nis . '@pb-learning.com';
        
        $stmt->bind_param('sssss', $nis, $nama, $kelas, $email, $passwordHash);
        
        if ($stmt->execute()) {
            $saved++;
        } else {
            $errors[] = "$locStr: Gagal menyimpan siswa $nama — " . $stmt->error;
        }
    }
}

$stmt->close();
$db->close();

// ── Response ─────────────────────────────────────────────────
if ($saved === 0 && !empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Tidak ada data yang berhasil diimpor.',
        'errors'  => $errors,
    ]);
} else {
    $message = "$saved data siswa berhasil diimpor.";
    if ($skipped > 0) $message .= " ($skipped baris dilewati)";

    echo json_encode([
        'status'  => 'success',
        'message' => $message,
        'saved'   => $saved,
        'skipped' => $skipped,
        'errors'  => $errors,
    ]);
}
