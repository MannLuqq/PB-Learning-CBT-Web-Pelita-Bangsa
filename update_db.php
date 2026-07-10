<?php
// ============================================================
//  update_db.php — Jalankan untuk menambah tabel jurnal_sikap & buku_induk
//  Akses: http://localhost/PB-Learning/update_db.php
// ============================================================

require_once 'config/database.php';

$db = getDB();

// 1. Buat tabel jurnal_sikap
$q1 = "
    CREATE TABLE IF NOT EXISTS jurnal_sikap (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        nis            VARCHAR(30) NOT NULL,
        tanggal        DATE NOT NULL,
        aspek          ENUM('spiritual', 'sosial') NOT NULL,
        perilaku       TEXT NOT NULL,
        tindak_lanjut  TEXT NOT NULL,
        nilai          ENUM('positif', 'negatif') NOT NULL DEFAULT 'positif',
        created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($db->query($q1)) {
    echo "✅ Tabel <b>jurnal_sikap</b> berhasil dibuat atau sudah ada.<br>";
} else {
    echo "❌ Gagal membuat tabel jurnal_sikap: " . $db->error . "<br>";
}

// 2. Buat tabel buku_induk
$q2 = "
    CREATE TABLE IF NOT EXISTS buku_induk (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        nis            VARCHAR(30) NOT NULL UNIQUE,
        nisn           VARCHAR(30) DEFAULT NULL,
        tempat_lahir   VARCHAR(100) DEFAULT NULL,
        tanggal_lahir  DATE DEFAULT NULL,
        jenis_kelamin  ENUM('Laki-laki', 'Perempuan') DEFAULT NULL,
        agama          VARCHAR(50) DEFAULT NULL,
        alamat         TEXT DEFAULT NULL,
        nama_ayah      VARCHAR(100) DEFAULT NULL,
        nama_ibu       VARCHAR(100) DEFAULT NULL,
        pekerjaan_ortu VARCHAR(100) DEFAULT NULL,
        no_hp_ortu     VARCHAR(20) DEFAULT NULL,
        created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($db->query($q2)) {
    echo "✅ Tabel <b>buku_induk</b> berhasil dibuat atau sudah ada.<br>";
} else {
    echo "❌ Gagal membuat tabel buku_induk: " . $db->error . "<br>";
}

$db->close();
echo "<br><b>Database update completed!</b>";
