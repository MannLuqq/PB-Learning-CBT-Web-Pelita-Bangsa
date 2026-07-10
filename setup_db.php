<?php
// ============================================================
//  setup_db.php — Jalankan SEKALI untuk membuat database & tabel
//  Akses: http://localhost/PB-Learning/setup_db.php
// ============================================================

$host = 'localhost';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
}

// 1. Buat database
$conn->query("CREATE DATABASE IF NOT EXISTS pb_learning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db('pb_learning');

// 2. Buat tabel users
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        nama        VARCHAR(100)  NOT NULL,
        email       VARCHAR(150)  NOT NULL UNIQUE,
        password    VARCHAR(255)  NOT NULL,
        role        ENUM('siswa','guru','admin') NOT NULL DEFAULT 'siswa',
        nis_nip     VARCHAR(30)   DEFAULT NULL COMMENT 'NIS untuk siswa, NUPTK untuk guru',
        kelas       VARCHAR(20)   DEFAULT NULL COMMENT 'Khusus siswa',
        no_hp       VARCHAR(20)   DEFAULT NULL,
        foto        VARCHAR(255)  DEFAULT NULL,
        created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_active   TINYINT(1)    DEFAULT 1
    ) ENGINE=InnoDB
");

// 3. Buat akun admin
$adminAccounts = [
    ['Fajri', 'Fajri1204@gmail.com', 'fajriadmin123', 'admin', null, null, null],
];

$stmt = $conn->prepare("
    INSERT IGNORE INTO users (nama, email, password, role, nis_nip, kelas, no_hp)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

foreach ($adminAccounts as $acc) {
    $hashedPass = password_hash($acc[2], PASSWORD_BCRYPT);
    $stmt->bind_param('sssssss', $acc[0], $acc[1], $hashedPass, $acc[3], $acc[4], $acc[5], $acc[6]);
    $stmt->execute();
}
$stmt->close();

// 4. Buat tabel login_logs
$conn->query("
    CREATE TABLE IF NOT EXISTS login_logs (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        ip_address VARCHAR(45),
        login_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB
");

// 5. Buat tabel absensi
$conn->query("
    CREATE TABLE IF NOT EXISTS absensi (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        nis        VARCHAR(30) NOT NULL,
        tanggal    DATE NOT NULL,
        status     ENUM('hadir', 'sakit', 'izin', 'alpa') NOT NULL,
        keterangan VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_nis_tanggal (nis, tanggal)
    ) ENGINE=InnoDB
");

// 6. Buat tabel jurnal_sikap
$conn->query("
    CREATE TABLE IF NOT EXISTS jurnal_sikap (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        nis            VARCHAR(30) NOT NULL,
        tanggal        DATE NOT NULL,
        aspek          ENUM('spiritual', 'sosial') NOT NULL,
        perilaku       TEXT NOT NULL,
        tindak_lanjut  TEXT NOT NULL,
        nilai          ENUM('positif', 'negatif') NOT NULL DEFAULT 'positif',
        created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 7. Buat tabel buku_induk
$conn->query("
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$conn->close();
echo "✅ Database <b>pb_learning</b> & semua tabel berhasil dibuat!<br>";
echo "✅ Akun admin sudah ditambahkan.<br><br>";
echo "<b>Akun Admin:</b><br>";
echo "⚙️ Email &nbsp;: Fajri1204@gmail.com<br><br>";
echo "<a href='index.html'>→ Kembali ke Login</a>";
