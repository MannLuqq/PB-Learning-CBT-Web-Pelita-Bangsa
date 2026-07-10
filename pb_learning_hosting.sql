-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: pb_learning
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `absensi`
--

DROP TABLE IF EXISTS `absensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nis` varchar(30) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('hadir','sakit','izin','alpa') NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nis_tanggal` (`nis`,`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `absensi`
--

LOCK TABLES `absensi` WRITE;
/*!40000 ALTER TABLE `absensi` DISABLE KEYS */;
/*!40000 ALTER TABLE `absensi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buku_induk`
--

DROP TABLE IF EXISTS `buku_induk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buku_induk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nis` varchar(30) NOT NULL,
  `nisn` varchar(30) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `agama` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nama_ayah` varchar(100) DEFAULT NULL,
  `nama_ibu` varchar(100) DEFAULT NULL,
  `pekerjaan_ortu` varchar(100) DEFAULT NULL,
  `no_hp_ortu` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nis` (`nis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buku_induk`
--

LOCK TABLES `buku_induk` WRITE;
/*!40000 ALTER TABLE `buku_induk` DISABLE KEYS */;
/*!40000 ALTER TABLE `buku_induk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jurnal_sikap`
--

DROP TABLE IF EXISTS `jurnal_sikap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jurnal_sikap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nis` varchar(30) NOT NULL,
  `tanggal` date NOT NULL,
  `aspek` enum('spiritual','sosial') NOT NULL,
  `perilaku` text NOT NULL,
  `tindak_lanjut` text NOT NULL,
  `nilai` enum('positif','negatif') NOT NULL DEFAULT 'positif',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jurnal_sikap`
--

LOCK TABLES `jurnal_sikap` WRITE;
/*!40000 ALTER TABLE `jurnal_sikap` DISABLE KEYS */;
/*!40000 ALTER TABLE `jurnal_sikap` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `login_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_logs`
--

LOCK TABLES `login_logs` WRITE;
/*!40000 ALTER TABLE `login_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('siswa','guru','admin','superadmin') NOT NULL DEFAULT 'siswa',
  `nis_nip` varchar(30) DEFAULT NULL COMMENT 'NIS untuk siswa, NIP untuk guru',
  `kelas` varchar(20) DEFAULT NULL COMMENT 'Khusus siswa',
  `mata_pelajaran` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=489 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (444,'Fajri','Fajri1204@gmail.com','$2y$10$lAANlsazcnYAUnpn9pq/KuA4lNXJeVdKjfXuT9nqT3CFnxUgbR9Oy','admin',NULL,NULL,NULL,NULL,NULL,'2026-06-23 21:27:18','2026-06-23 21:27:18',1,NULL),(448,'Aditya Rezky Rakasiwa','24070001@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070001','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(449,'Afkar Adha Gaizan','24070002@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070002','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(450,'Agil Kyan Alkhalifi','24070003@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070003','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(451,'Anggita Meylani Putri','24070004@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070004','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(452,'Aqiela Putri Yulianingsih','24070005@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070005','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(453,'Arizqi Triabudi Wibawa','0143343034@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','0143343034','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(454,'Davin Devara Mahendra','3139688603@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','3139688603','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(455,'Erlangga Ramadhani','24070008@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070008','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(456,'Fatan Al Mainsan','24070009@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070009','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(457,'Kayla Ayunindya Shakila','24070010@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070010','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(458,'Kiandra','24070011@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070011','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(459,'Muhammad Fauzi','24070012@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070012','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(460,'Raden Ayu Raisha Kayla Azzahra','24070013@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070013','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(461,'Radhitya Javas Freyza','24070014@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24070014','VII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(462,'Alisya Marchila Maheswari','0136207912@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','0136207912','VIII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(463,'Arrahman Mastkal','3134617525@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','3134617525','VIII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(464,'Cynara Avila','0124660084@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','0124660084','VIII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(465,'Devika Violinajma Sasmita','3138925476@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','3138925476','VIII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(466,'Dzahra Alia','0122569412@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','0122569412','VIII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(467,'Gaisani Qanitah Putri Law','3138324868@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','3138324868','VIII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(468,'Prasetya Ataya Irana','2103537007@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','2103537007','VIII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(469,'Qyamu Jibriel Bunnaya','3122581425@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','3122581425','VIII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(470,'Violeta Putri Hadiya','3124917362@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','3124917362','VIII',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(471,'Futuhat Makkiyah Mobiyan','3119283411@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','3119283411','IX',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(472,'Keanna Kiyomi Lateisha','0116876774@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','0116876774','IX',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(473,'Rizky Langit Ramadan','24090003@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24090003','IX',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-24 00:26:45',1,NULL),(474,'Ahmad Alatas','24090004@pb-learning.com','$2y$10$zb8/wqQw/MRNuhtoZrh3Re1x.EqU/FDDa3Idzf2pEw3sl.rlCOrAu','siswa','24090004','IX',NULL,NULL,NULL,'2026-06-24 00:26:45','2026-06-25 08:29:14',1,NULL),(477,'Superadmin PB-Learning','Pbpamulangadmin@gmail.com','$2y$10$nGMrn773L0ioYOJf2mCxZehB3vwAUKV3ePFQMBUUKepkJ/riCaOLq','superadmin',NULL,NULL,NULL,NULL,NULL,'2026-06-24 21:54:47','2026-06-24 22:08:57',1,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-25 16:54:18

