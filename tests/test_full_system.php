<?php
/**
 * WaterCheck - Comprehensive System End-to-End Test Suite
 * File: tests/test_full_system.php
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

echo "=================================================================\n";
echo "    WATERCHECK / HYDROSAN - COMPREHENSIVE END-TO-END TEST SUITE  \n";
echo "=================================================================\n\n";

$passCount = 0;
$totalTests = 0;

function it($title, $condition) {
    global $passCount, $totalTests;
    $totalTests++;
    if ($condition) {
        $passCount++;
        echo " [OK] Test " . str_pad((string)$totalTests, 2, '0', STR_PAD_LEFT) . ": {$title}\n";
    } else {
        echo " [FAIL] Test " . str_pad((string)$totalTests, 2, '0', STR_PAD_LEFT) . ": {$title}\n";
    }
}

// -------------------------------------------------------------
// 1. DATABASE & USERS
// -------------------------------------------------------------
$stmt = $pdo->query("SHOW TABLES FROM watercheck_db");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
$expectedTables = ['users', 'sumber_air', 'standar_baku_mutu', 'pemeriksaan', 'detail_pemeriksaan', 'kalkulasi_kaporit', 'log_aktivitas'];
$hasAllTables = count(array_intersect($expectedTables, $tables)) === count($expectedTables);
it("Integritas Skema Database (7 Tabel Relasional InnoDB)", $hasAllTables);

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(['admin']);
$admin = $stmt->fetch();
it("Autentikasi Admin ('admin' / 'admin123')", $admin && password_verify('admin123', $admin['password']) && $admin['role'] === 'admin');

$stmt->execute(['sanitarian']);
$petugas = $stmt->fetch();
it("Autentikasi Petugas ('sanitarian' / 'petugas123')", $petugas && password_verify('petugas123', $petugas['password']) && $petugas['role'] === 'petugas');

// -------------------------------------------------------------
// 2. CRUD SUMBER AIR
// -------------------------------------------------------------
$testSumberName = "Sumber Uji Laboratorium " . time();
$stmt = $pdo->prepare("INSERT INTO sumber_air (nama_sumber, jenis_sumber, pemilik, lokasi, volume, keterangan) VALUES (?, 'Sumur gali', 'Dinkes', 'Lab Kesling', 4500, 'Test CRUD')");
$stmt->execute([$testSumberName]);
$testSumberId = (int)$pdo->lastInsertId();
it("Create Sumber Air (ID: {$testSumberId})", $testSumberId > 0);

$stmt = $pdo->prepare("SELECT * FROM sumber_air WHERE id = ?");
$stmt->execute([$testSumberId]);
$fetchedSumber = $stmt->fetch();
it("Read Sumber Air", $fetchedSumber && $fetchedSumber['nama_sumber'] === $testSumberName);

$stmt = $pdo->prepare("UPDATE sumber_air SET volume = 5000 WHERE id = ?");
$stmt->execute([$testSumberId]);
$stmt = $pdo->prepare("SELECT volume FROM sumber_air WHERE id = ?");
$stmt->execute([$testSumberId]);
it("Update Sumber Air", (float)$stmt->fetchColumn() === 5000.0);

$stmt = $pdo->prepare("DELETE FROM sumber_air WHERE id = ?");
$stmt->execute([$testSumberId]);
$stmt = $pdo->prepare("SELECT id FROM sumber_air WHERE id = ?");
$stmt->execute([$testSumberId]);
it("Delete Sumber Air", !$stmt->fetch());

// -------------------------------------------------------------
// 3. KALKULATOR KAPORIT
// -------------------------------------------------------------
$calc1 = hitungKebutuhanKaporit(1000, 'liter', 60, 2);
$isAccurate1 = $calc1['success'] && (abs($calc1['data']['kebutuhan_produk_gram'] - 3.3333) < 0.01);
it("Kalkulasi Kaporit Spesifikasi 1.000 L, 60%, 2 mg/L = 3.33 gram", $isAccurate1);

$calc2 = hitungKebutuhanKaporit(2, 'm3', 70, 1.5);
// 2 m3 = 2000 L, aktif = 2000 * 1.5 = 3000 mg, produk mg = 3000 / 0.70 = 4285.71 mg = 4.29 gram
$isAccurate2 = $calc2['success'] && (abs($calc2['data']['kebutuhan_produk_gram'] - 4.2857) < 0.01);
it("Kalkulasi Kaporit Konversi 2 m³, 70%, 1.5 mg/L = 4.29 gram", $isAccurate2);

$calcBad = hitungKebutuhanKaporit(-100, 'liter', 60, 2);
it("Validasi Pencegahan Nilai Volume Negatif", !$calcBad['success']);

// Simpan kalkulasi ke DB
$stmt = $pdo->prepare("INSERT INTO kalkulasi_kaporit (sumber_air_id, user_id, jenis_desinfektan, volume_air, satuan_volume, kadar_klorin_aktif, target_dosis, kebutuhan_klorin_aktif, kebutuhan_produk_mg, kebutuhan_produk_gram, created_at) VALUES (NULL, ?, 'Kalsium Hipoklorit', 1000, 'liter', 60, 2, 2000, 3333.33, 3.33, NOW())");
$stmt->execute([$admin['id']]);
$testKalkulasiId = (int)$pdo->lastInsertId();
it("Penyimpanan Kalkulasi Kaporit ke Riwayat (ID: {$testKalkulasiId})", $testKalkulasiId > 0);

// -------------------------------------------------------------
// 4. PEMERIKSAAN KUALITAS AIR & EVALUASI OTOMATIS
// -------------------------------------------------------------
it("Evaluasi pH 7.4 (Baku Mutu 6.5 - 8.5) -> Memenuhi Syarat", evaluasiParameter('MIN_MAX', 7.4, 6.5, 8.5) === 'Memenuhi Syarat');
it("Evaluasi pH 9.1 (Baku Mutu 6.5 - 8.5) -> Tidak Memenuhi Syarat", evaluasiParameter('MIN_MAX', 9.1, 6.5, 8.5) === 'Tidak Memenuhi Syarat');
it("Evaluasi Kekeruhan 1.5 NTU (Baku Mutu <= 3 NTU) -> Memenuhi Syarat", evaluasiParameter('MAX', 1.5, null, 3.0) === 'Memenuhi Syarat');
it("Evaluasi Kekeruhan 6.2 NTU (Baku Mutu <= 3 NTU) -> Tidak Memenuhi Syarat", evaluasiParameter('MAX', 6.2, null, 3.0) === 'Tidak Memenuhi Syarat');
it("Evaluasi E. coli 0 CFU/100 mL -> Memenuhi Syarat", evaluasiParameter('EQUALS_ZERO', 0, null, 0) === 'Memenuhi Syarat');
it("Evaluasi E. coli 2 CFU/100 mL -> Tidak Memenuhi Syarat", evaluasiParameter('EQUALS_ZERO', 2, null, 0) === 'Tidak Memenuhi Syarat');
it("Evaluasi Kualitatif Bau ('Tidak Berbau') -> Memenuhi Syarat", evaluasiParameter('QUALITATIVE', null, null, null, 'Tidak Berbau') === 'Memenuhi Syarat');
it("Evaluasi Kualitatif Bau ('Berbau Busuk') -> Tidak Memenuhi Syarat", evaluasiParameter('QUALITATIVE', null, null, null, 'Berbau Busuk') === 'Tidak Memenuhi Syarat');

// Kesimpulan
$msSet = [
    ['status' => 'Memenuhi Syarat', 'wajib' => 1],
    ['status' => 'Memenuhi Syarat', 'wajib' => 1],
    ['status' => 'Belum Diuji', 'wajib' => 0] // opsional boleh belum diuji
];
it("Penentuan Kesimpulan Akhir: Memenuhi Syarat (MS)", tentukanKesimpulanPemeriksaan($msSet) === 'Memenuhi Syarat (MS)');

$tmsSet = [
    ['status' => 'Memenuhi Syarat', 'wajib' => 1],
    ['status' => 'Tidak Memenuhi Syarat', 'wajib' => 1]
];
it("Penentuan Kesimpulan Akhir: Tidak Memenuhi Syarat (TMS)", tentukanKesimpulanPemeriksaan($tmsSet) === 'Tidak Memenuhi Syarat (TMS)');

$pendingSet = [
    ['status' => 'Memenuhi Syarat', 'wajib' => 1],
    ['status' => 'Belum Diuji', 'wajib' => 1]
];
it("Penentuan Kesimpulan Akhir: Belum Dapat Disimpulkan", tentukanKesimpulanPemeriksaan($pendingSet) === 'Belum Dapat Disimpulkan');

// -------------------------------------------------------------
// 5. DOMPDF PDF RENDERING ENGINE
// -------------------------------------------------------------
try {
    $options = new Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'Helvetica');
    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml("<html><body><h2>Uji Cetak PDF WaterCheck</h2><p>Laporan Resmi Sanitarian</p></body></html>");
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $outputPdf = $dompdf->output();
    $isPdfValid = str_starts_with($outputPdf, '%PDF-');
    it("Rendering Engine Dompdf (Menghasilkan Dokumen PDF Valid)", $isPdfValid);
} catch (Exception $e) {
    it("Rendering Engine Dompdf", false);
}

// -------------------------------------------------------------
// 6. STANDAR BAKU MUTU PERMENKES NO. 2 TAHUN 2023
// -------------------------------------------------------------
$stmt = $pdo->query("SELECT COUNT(*) FROM standar_baku_mutu WHERE jenis_air = 'Air Minum'");
$countMinum = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM standar_baku_mutu WHERE jenis_air = 'Air Higiene Sanitasi'");
$countSanitasi = (int)$stmt->fetchColumn();
it("Data Baku Mutu Air Minum Permenkes 2/2023 ({$countMinum} Parameter)", $countMinum >= 10);
it("Data Baku Mutu Air Higiene Sanitasi Permenkes 2/2023 ({$countSanitasi} Parameter)", $countSanitasi >= 10);

echo "\n=================================================================\n";
echo "HASIL AKHIR: {$passCount} DARI {$totalTests} PENGUJIAN LULUS (" . round(($passCount / $totalTests) * 100) . "%)\n";
echo "=================================================================\n";
