<?php
// ============================================================
//  api/import_nilai_csv.php — Import nilai siswa dari file CSV
//  PHP murni, tidak butuh library apapun (kompatibel semua hosting)
//  Method: POST | Field: csv_file (.csv)
//  Format CSV: NIS,Nama,Nilai
//  Baris pertama = header (dilewati)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}

// ── Validasi File Upload ─────────────────────────────────────
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan atau gagal diupload.']);
    exit;
}

$file = $_FILES['csv_file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($ext !== 'csv') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format file harus .csv']);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Ukuran file maksimal 5MB.']);
    exit;
}

// ── Baca CSV (PHP murni, tidak butuh library) ─────────────────
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuka file CSV.']);
    exit;
}

// Deteksi & skip BOM (UTF-8 BOM dari Excel)
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle); // bukan BOM, kembalikan ke awal
}

$rows   = [];
$lineNo = 0;
while (($row = fgetcsv($handle, 2000, ',')) !== false) {
    $lineNo++;
    // Jika separator koma tidak berhasil (misal dari Excel Indonesia pakai semicolon), coba semicolon
    if ($lineNo === 1 && count($row) === 1 && str_contains($row[0] ?? '', ';')) {
        rewind($handle);
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);
        $rows   = [];
        $lineNo = 0;
        while (($row = fgetcsv($handle, 2000, ';')) !== false) {
            $rows[] = $row;
        }
        break;
    }
    $rows[] = $row;
}
fclose($handle);

if (count($rows) < 2) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'File kosong atau hanya berisi header. Pastikan data ada mulai baris ke-2.']);
    exit;
}

// ── Parse Data ───────────────────────────────────────────────
$dataRows = array_slice($rows, 1); // skip header baris pertama
$parsed   = [];
$errors   = [];

foreach ($dataRows as $idx => $row) {
    $lineNum = $idx + 2;

    // Trim semua kolom
    $row = array_map('trim', $row);

    $nis   = $row[0] ?? '';
    $nama  = $row[1] ?? '';
    $nilai = $row[2] ?? '';

    // Skip baris kosong total
    if ($nis === '' && $nama === '' && $nilai === '') continue;

    // Validasi NIS
    if ($nis === '') {
        $errors[] = "Baris $lineNum: NIS kosong — dilewati.";
        continue;
    }

    // Validasi Nilai
    if ($nilai === '') {
        $errors[] = "Baris $lineNum (NIS: $nis): Kolom Nilai kosong — dilewati.";
        continue;
    }

    $nilaiInt = intval($nilai);
    if (!is_numeric($nilai) || $nilaiInt < 0 || $nilaiInt > 100) {
        $errors[] = "Baris $lineNum (NIS: $nis): Nilai '$nilai' tidak valid (harus angka 0–100) — dilewati.";
        continue;
    }

    $parsed[] = [
        'nis'   => $nis,
        'nama'  => $nama,
        'nilai' => $nilaiInt,
    ];
}

if (empty($parsed) && !empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Tidak ada data yang valid di file CSV.',
        'errors'  => $errors,
    ]);
    exit;
}

// Return data ke frontend untuk preview & simpan ke localStorage
echo json_encode([
    'status'  => 'success',
    'message' => count($parsed) . ' data nilai berhasil dibaca dari CSV.',
    'data'    => $parsed,
    'errors'  => $errors,
    'total'   => count($parsed),
]);
