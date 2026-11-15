-- ========================================================
-- DATABASE: watercheck_db
-- Aplikasi: WaterCheck / HydroSan
-- Sistem Informasi Pemeriksaan Kualitas Air & Kalkulator Kebutuhan Desinfeksi
-- Kompatibel: MySQL 5.7+ / MariaDB 10.3+ / XAMPP
-- ========================================================

-- --------------------------------------------------------
-- 1. Tabel users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `log_aktivitas`;
DROP TABLE IF EXISTS `detail_pemeriksaan`;
DROP TABLE IF EXISTS `pemeriksaan`;
DROP TABLE IF EXISTS `kalkulasi_kaporit`;
DROP TABLE IF EXISTS `standar_baku_mutu`;
DROP TABLE IF EXISTS `sumber_air`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'petugas') NOT NULL DEFAULT 'petugas',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_username` (`username`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Tabel sumber_air
-- --------------------------------------------------------
CREATE TABLE `sumber_air` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_sumber` VARCHAR(150) NOT NULL,
  `jenis_sumber` VARCHAR(50) NOT NULL,
  `pemilik` VARCHAR(150) NOT NULL,
  `lokasi` TEXT NOT NULL,
  `volume` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `keterangan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sumber_nama` (`nama_sumber`),
  INDEX `idx_sumber_jenis` (`jenis_sumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Tabel standar_baku_mutu
-- Acuan Resmi: Peraturan Menteri Kesehatan RI No. 2 Tahun 2023
-- --------------------------------------------------------
CREATE TABLE `standar_baku_mutu` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_parameter` VARCHAR(100) NOT NULL,
  `jenis_air` ENUM('Air Minum', 'Air Higiene Sanitasi') NOT NULL,
  `kategori` ENUM('Fisik', 'Kimia', 'Mikrobiologi') NOT NULL DEFAULT 'Fisik',
  `nilai_min` DECIMAL(10,3) NULL,
  `nilai_max` DECIMAL(10,3) NULL,
  `satuan` VARCHAR(50) NOT NULL,
  `operator` ENUM('MIN_MAX', 'MAX', 'MIN', 'EQUALS_ZERO', 'QUALITATIVE', 'SUHU') NOT NULL DEFAULT 'MAX',
  `wajib` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Wajib, 0 = Tambahan/Opsional',
  `keterangan` TEXT NULL,
  `sumber_regulasi` VARCHAR(150) NOT NULL DEFAULT 'Permenkes No. 2 Tahun 2023',
  `tahun_regulasi` VARCHAR(10) NOT NULL DEFAULT '2023',
  `terverifikasi` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Terverifikasi regulasi, 0 = Belum',
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_standar_jenis` (`jenis_air`),
  INDEX `idx_standar_parameter` (`nama_parameter`),
  INDEX `idx_standar_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Tabel pemeriksaan
-- --------------------------------------------------------
CREATE TABLE `pemeriksaan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_pemeriksaan` VARCHAR(50) NOT NULL UNIQUE,
  `sumber_air_id` INT NULL,
  `user_id` INT NULL,
  `tanggal_pemeriksaan` DATE NOT NULL,
  `jenis_air` ENUM('Air Minum', 'Air Higiene Sanitasi') NOT NULL,
  `lokasi` TEXT NOT NULL,
  `kesimpulan` ENUM('Memenuhi Syarat (MS)', 'Tidak Memenuhi Syarat (TMS)', 'Belum Dapat Disimpulkan') NOT NULL DEFAULT 'Belum Dapat Disimpulkan',
  `catatan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_pemeriksaan_nomor` (`nomor_pemeriksaan`),
  INDEX `idx_pemeriksaan_tanggal` (`tanggal_pemeriksaan`),
  INDEX `idx_pemeriksaan_kesimpulan` (`kesimpulan`),
  CONSTRAINT `fk_pemeriksaan_sumber` FOREIGN KEY (`sumber_air_id`) REFERENCES `sumber_air` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pemeriksaan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Tabel detail_pemeriksaan
-- --------------------------------------------------------
CREATE TABLE `detail_pemeriksaan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pemeriksaan_id` INT NOT NULL,
  `standar_id` INT NULL,
  `nilai_hasil` DECIMAL(10,3) NULL,
  `nilai_kualitatif` VARCHAR(100) NULL,
  `satuan` VARCHAR(50) NOT NULL,
  `status` ENUM('Memenuhi Syarat', 'Tidak Memenuhi Syarat', 'Belum Diuji') NOT NULL DEFAULT 'Belum Diuji',
  `metode_pemeriksaan` VARCHAR(100) NULL,
  `keterangan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_detail_pemeriksaan` (`pemeriksaan_id`),
  CONSTRAINT `fk_detail_pemeriksaan` FOREIGN KEY (`pemeriksaan_id`) REFERENCES `pemeriksaan` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_detail_standar` FOREIGN KEY (`standar_id`) REFERENCES `standar_baku_mutu` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Tabel kalkulasi_kaporit
-- --------------------------------------------------------
CREATE TABLE `kalkulasi_kaporit` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sumber_air_id` INT NULL,
  `user_id` INT NULL,
  `jenis_desinfektan` VARCHAR(100) NOT NULL,
  `volume_air` DECIMAL(12,2) NOT NULL,
  `satuan_volume` VARCHAR(10) NOT NULL DEFAULT 'liter',
  `kadar_klorin_aktif` DECIMAL(5,2) NOT NULL,
  `target_dosis` DECIMAL(8,3) NOT NULL,
  `kebutuhan_klorin_aktif` DECIMAL(14,3) NOT NULL,
  `kebutuhan_produk_mg` DECIMAL(14,3) NOT NULL,
  `kebutuhan_produk_gram` DECIMAL(12,3) NOT NULL,
  `catatan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_kalkulasi_created` (`created_at`),
  CONSTRAINT `fk_kalkulasi_sumber` FOREIGN KEY (`sumber_air_id`) REFERENCES `sumber_air` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_kalkulasi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Tabel log_aktivitas
-- --------------------------------------------------------
CREATE TABLE `log_aktivitas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `aktivitas` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_log_user` (`user_id`),
  INDEX `idx_log_created` (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- DATA SEED AWAL
-- ========================================================

-- Akun Pengguna:
-- 1. admin / admin123
-- 2. sanitarian / petugas123
INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`) VALUES
(1, 'Administrator Sanitarian', 'admin', '$2y$10$.o6/oj7dmFIvuAxgA5y8EOKspgyHQZFq7LuMvB8JlR.PVL8BMF/fi', 'admin'),
(2, 'Sanitarian Lapangan SKM', 'sanitarian', '$2y$10$UtF1Wnx1VdfLjhDXgeJAYuCRLcrUV39FgQ.kTwqSSLnsqr/U1MVHy', 'petugas');

-- Standar Baku Mutu Berdasarkan Permenkes No. 2 Tahun 2023
-- Kategori: AIR MINUM
INSERT INTO `standar_baku_mutu` (`id`, `nama_parameter`, `jenis_air`, `kategori`, `nilai_min`, `nilai_max`, `satuan`, `operator`, `wajib`, `keterangan`, `sumber_regulasi`, `tahun_regulasi`, `terverifikasi`, `aktif`) VALUES
(1, 'Suhu', 'Air Minum', 'Fisik', -3.000, 3.000, '°C', 'SUHU', 1, 'Deviasi maksimum ± 3°C dari suhu udara sekitar', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(2, 'Kekeruhan', 'Air Minum', 'Fisik', NULL, 3.000, 'NTU', 'MAX', 1, 'Batas maksimum kekeruhan air minum adalah 3 NTU', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(3, 'TDS (Total Dissolved Solids)', 'Air Minum', 'Fisik', NULL, 300.000, 'mg/L', 'MAX', 1, 'Zat padat terlarut maksimum 300 mg/L', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(4, 'Warna', 'Air Minum', 'Fisik', NULL, 10.000, 'TCU', 'MAX', 1, 'Skala True Color Unit maksimal 10 TCU', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(5, 'Bau', 'Air Minum', 'Fisik', NULL, NULL, '-', 'QUALITATIVE', 1, 'Wajib tidak berbau', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(6, 'Rasa', 'Air Minum', 'Fisik', NULL, NULL, '-', 'QUALITATIVE', 1, 'Wajib tidak berasa / tawar alami', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(7, 'pH (Derajat Keasaman)', 'Air Minum', 'Kimia', 6.500, 8.500, 'pH', 'MIN_MAX', 1, 'Rentang pH yang memenuhi syarat 6,5 hingga 8,5', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(8, 'Escherichia coli', 'Air Minum', 'Mikrobiologi', NULL, 0.000, 'CFU/100 mL', 'EQUALS_ZERO', 1, 'Wajib 0 CFU per 100 mL sampel', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(9, 'Total Coliform', 'Air Minum', 'Mikrobiologi', NULL, 0.000, 'CFU/100 mL', 'EQUALS_ZERO', 1, 'Wajib 0 CFU per 100 mL sampel', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(10, 'Sisa Klor Bebas', 'Air Minum', 'Kimia', 0.200, 0.500, 'mg/L', 'MIN_MAX', 0, 'Diperiksa apabila air diolah dengan klorinasi (0.2 - 0.5 mg/L)', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(11, 'Nitrat (sebagai NO3)', 'Air Minum', 'Kimia', NULL, 50.000, 'mg/L', 'MAX', 0, 'Kadar maksimum 50 mg/L', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(12, 'Nitrit (sebagai NO2)', 'Air Minum', 'Kimia', NULL, 3.000, 'mg/L', 'MAX', 0, 'Kadar maksimum 3 mg/L', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1);

-- Kategori: AIR HIGIENE DAN SANITASI
INSERT INTO `standar_baku_mutu` (`id`, `nama_parameter`, `jenis_air`, `kategori`, `nilai_min`, `nilai_max`, `satuan`, `operator`, `wajib`, `keterangan`, `sumber_regulasi`, `tahun_regulasi`, `terverifikasi`, `aktif`) VALUES
(13, 'Suhu', 'Air Higiene Sanitasi', 'Fisik', -3.000, 3.000, '°C', 'SUHU', 1, 'Deviasi maksimum ± 3°C dari suhu udara sekitar', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(14, 'Kekeruhan', 'Air Higiene Sanitasi', 'Fisik', NULL, 25.000, 'NTU', 'MAX', 1, 'Batas maksimum kekeruhan 25 NTU', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(15, 'TDS (Total Dissolved Solids)', 'Air Higiene Sanitasi', 'Fisik', NULL, 1000.000, 'mg/L', 'MAX', 1, 'Zat padat terlarut maksimum 1000 mg/L', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(16, 'Warna', 'Air Higiene Sanitasi', 'Fisik', NULL, 50.000, 'TCU', 'MAX', 1, 'Skala warna maksimal 50 TCU', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(17, 'Bau', 'Air Higiene Sanitasi', 'Fisik', NULL, NULL, '-', 'QUALITATIVE', 1, 'Wajib tidak berbau', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(18, 'Rasa', 'Air Higiene Sanitasi', 'Fisik', NULL, NULL, '-', 'QUALITATIVE', 1, 'Wajib tidak berasa', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(19, 'pH (Derajat Keasaman)', 'Air Higiene Sanitasi', 'Kimia', 6.500, 8.500, 'pH', 'MIN_MAX', 1, 'Rentang pH yang memenuhi syarat 6,5 hingga 8,5', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(20, 'Escherichia coli', 'Air Higiene Sanitasi', 'Mikrobiologi', NULL, 0.000, 'CFU/100 mL', 'EQUALS_ZERO', 1, 'Wajib 0 CFU per 100 mL', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(21, 'Total Coliform', 'Air Higiene Sanitasi', 'Mikrobiologi', NULL, 50.000, 'CFU/100 mL', 'MAX', 1, 'Batas maksimum 50 CFU per 100 mL sampel', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(22, 'Besi (Fe)', 'Air Higiene Sanitasi', 'Kimia', NULL, 1.000, 'mg/L', 'MAX', 0, 'Kadar besi terlarut maksimal 1.0 mg/L', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1),
(23, 'Mangan (Mn)', 'Air Higiene Sanitasi', 'Kimia', NULL, 0.500, 'mg/L', 'MAX', 0, 'Kadar mangan maksimal 0.5 mg/L', 'Permenkes No. 2 Tahun 2023', '2023', 1, 1);

-- Data Demo: Sumber Air
INSERT INTO `sumber_air` (`id`, `nama_sumber`, `jenis_sumber`, `pemilik`, `lokasi`, `volume`, `keterangan`) VALUES
(1, 'Sumur Gali Puskesmas Sehat', 'Sumur gali', 'Puskesmas Melati', 'Halaman Belakang Puskesmas Melati, Jl. Kesehatan No. 10', 5000.00, 'Digunakan untuk keperluan sanitasi klinik dan cuci tangan'),
(2, 'Tandon Air Asrama Sanitasi', 'Tandon air', 'UPTD Asrama Diklat', 'Gedung A Lt. 3, Jl. Kartini No. 45', 2000.00, 'Tandon polietilena tertutup, pengisian dari sumur bor'),
(3, 'Sumur Bor RT 04 Sukamaju', 'Sumur bor', 'Pengurus RT 04 (Bp. Bambang)', 'Jl. Kenanga RT 04 RW 02, Desa Sukamaju', 3500.00, 'Kedalaman 45 meter, sumber air bersama 18 KK'),
(4, 'Mata Air Tirta Asri', 'Mata air', 'Pemerintah Desa Sumber Rejo', 'Lereng Bukit Asri, Desa Sumber Rejo', 15000.00, 'Penampungan mata air alami sebelum didistribusikan perpipaan');

-- Data Demo: Kalkulasi Kaporit
INSERT INTO `kalkulasi_kaporit` (`id`, `sumber_air_id`, `user_id`, `jenis_desinfektan`, `volume_air`, `satuan_volume`, `kadar_klorin_aktif`, `target_dosis`, `kebutuhan_klorin_aktif`, `kebutuhan_produk_mg`, `kebutuhan_produk_gram`, `catatan`, `created_at`) VALUES
(1, 2, 1, 'Kalsium hipoklorit (kaporit bubuk)', 2000.00, 'liter', 60.00, 2.000, 4000.000, 6666.667, 6.667, 'Desinfeksi rutin tandon asrama setiap bulan', '2026-11-10 09:30:00'),
(2, 1, 2, 'Kaporit granular 70%', 5000.00, 'liter', 70.00, 1.500, 7500.000, 10714.286, 10.714, 'Penyehatan berkala sumur gali puskesmas pasca musim hujan', '2026-11-12 14:15:00');

-- Data Demo: Pemeriksaan Kualitas Air
-- 1. WC-202611-0001: Memenuhi Syarat (MS) pada Air Higiene Sanitasi
INSERT INTO `pemeriksaan` (`id`, `nomor_pemeriksaan`, `sumber_air_id`, `user_id`, `tanggal_pemeriksaan`, `jenis_air`, `lokasi`, `kesimpulan`, `catatan`, `created_at`) VALUES
(1, 'WC-202611-0001', 1, 2, '2026-11-12', 'Air Higiene Sanitasi', 'Puskesmas Melati, Ruang Sanitasi', 'Memenuhi Syarat (MS)', 'Seluruh parameter wajib memenuhi baku mutu Permenkes No. 2/2023 untuk air higiene sanitasi.', '2026-11-12 10:00:00'),
(2, 'WC-202611-0002', 3, 2, '2026-11-14', 'Air Minum', 'Jl. Kenanga RT 04 Sukamaju', 'Tidak Memenuhi Syarat (TMS)', 'Ditemukan kontaminasi bakteri E. coli dan kekeruhan melebihi batas. Direkomendasikan klorinasi dan filtrasi.', '2026-11-14 11:30:00');

-- Detail Pemeriksaan 1 (WC-202611-0001: MS)
INSERT INTO `detail_pemeriksaan` (`pemeriksaan_id`, `standar_id`, `nilai_hasil`, `nilai_kualitatif`, `satuan`, `status`, `metode_pemeriksaan`, `keterangan`) VALUES
(1, 13, 27.500, NULL, '°C', 'Memenuhi Syarat', 'Termometer Digital', 'Suhu udara 27.0°C (deviasi 0.5°C)'),
(1, 14, 4.200, NULL, 'NTU', 'Memenuhi Syarat', 'Turbidimeter', 'Bening visual'),
(1, 15, 210.000, NULL, 'mg/L', 'Memenuhi Syarat', 'TDS Meter', 'Jauh di bawah ambang batas'),
(1, 16, 5.000, NULL, 'TCU', 'Memenuhi Syarat', 'Spektrofotometer', 'Tidak berwarna'),
(1, 17, NULL, 'Tidak Berbau', '-', 'Memenuhi Syarat', 'Organoleptik', 'Normal tidak berbau'),
(1, 18, NULL, 'Tidak Berasa', '-', 'Memenuhi Syarat', 'Organoleptik', 'Tawar normal'),
(1, 19, 7.200, NULL, 'pH', 'Memenuhi Syarat', 'pH Meter Digital', 'Netral stabil'),
(1, 20, 0.000, NULL, 'CFU/100 mL', 'Memenuhi Syarat', 'Membran Filtrasi', 'Nihil E. coli'),
(1, 21, 12.000, NULL, 'CFU/100 mL', 'Memenuhi Syarat', 'Membran Filtrasi', 'Masih di bawah batas maksimal 50');

-- Detail Pemeriksaan 2 (WC-202611-0002: TMS)
INSERT INTO `detail_pemeriksaan` (`pemeriksaan_id`, `standar_id`, `nilai_hasil`, `nilai_kualitatif`, `satuan`, `status`, `metode_pemeriksaan`, `keterangan`) VALUES
(2, 1, 28.000, NULL, '°C', 'Memenuhi Syarat', 'Termometer Digital', 'Suhu udara 27.5°C'),
(2, 2, 5.800, NULL, 'NTU', 'Tidak Memenuhi Syarat', 'Turbidimeter', 'Melebihi baku mutu air minum (maks 3 NTU)'),
(2, 3, 280.000, NULL, 'mg/L', 'Memenuhi Syarat', 'TDS Meter', 'Memenuhi syarat'),
(2, 4, 8.000, NULL, 'TCU', 'Memenuhi Syarat', 'Spektrofotometer', 'Memenuhi syarat'),
(2, 5, NULL, 'Tidak Berbau', '-', 'Memenuhi Syarat', 'Organoleptik', 'Tidak berbau'),
(2, 6, NULL, 'Tidak Berasa', '-', 'Memenuhi Syarat', 'Organoleptik', 'Tidak berasa'),
(2, 7, 6.800, NULL, 'pH', 'Memenuhi Syarat', 'pH Meter Digital', 'Memenuhi syarat'),
(2, 8, 4.000, NULL, 'CFU/100 mL', 'Tidak Memenuhi Syarat', 'Membran Filtrasi', 'Positif E. coli 4 CFU (syarat air minum wajib 0)'),
(2, 9, 18.000, NULL, 'CFU/100 mL', 'Tidak Memenuhi Syarat', 'Membran Filtrasi', 'Positif Coliform 18 CFU (syarat air minum wajib 0)');

-- Log Aktivitas Demo
INSERT INTO `log_aktivitas` (`id`, `user_id`, `aktivitas`, `created_at`) VALUES
(1, 1, 'Inisialisasi sistem dan data awal standar baku mutu Permenkes No. 2/2023', '2026-11-01 08:00:00'),
(2, 2, 'Menambahkan data sumber air: Sumur Gali Puskesmas Sehat', '2026-11-12 09:10:00'),
(3, 2, 'Melakukan perhitungan kaporit untuk Sumur Gali Puskesmas Sehat (10.71 g)', '2026-11-12 14:15:00'),
(4, 2, 'Menginput hasil pemeriksaan kualitas air: WC-202611-0001 (Kesimpulan: MS)', '2026-11-12 10:05:00'),
(5, 2, 'Menginput hasil pemeriksaan kualitas air: WC-202611-0002 (Kesimpulan: TMS)', '2026-11-14 11:35:00');
