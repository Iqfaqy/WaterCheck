<?php
/**
 * WaterCheck - Automated Testing Unit Tahap 1
 * File: tests/test_tahap1.php
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

echo "========================================================\n";
echo "   PENGUJIAN SISTEM WATERCHECK / HYDROSAN - TAHAP 1     \n";
echo "========================================================\n\n";

$passCount = 0;
$totalTests = 0;

function runTest($title, $condition) {
    global $passCount, $totalTests;
    $totalTests++;
    if ($condition) {
        $passCount++;
        echo " [PASS] Test {$totalTests}: {$title}\n";
    } else {
        echo " [FAIL] Test {$totalTests}: {$title}\n";
    }
}

// 1. Database Connection & Demo Accounts
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute(['admin']);
$admin = $stmt->fetch();
runTest("Autentikasi Akun Admin ('admin' / 'admin123')", $admin && password_verify('admin123', $admin['password']) && $admin['role'] === 'admin');

$stmt->execute(['sanitarian']);
$petugas = $stmt->fetch();
runTest("Autentikasi Akun Petugas ('sanitarian' / 'petugas123')", $petugas && password_verify('petugas123', $petugas['password']) && $petugas['role'] === 'petugas');

// 2. Proteksi Password Salah
runTest("Validasi Penolakan Password Salah", !password_verify('wrongpass', $admin['password']));

// 3. CSRF Protection
$token = generateCsrfToken();
runTest("CSRF Token Generation & Verification", verifyCsrfToken($token) && !verifyCsrfToken('invalid_token'));

// 4. Kalkulator Kaporit - Contoh Spesifikasi (1000 L, 60% aktif, dosis 2 mg/L)
$calc = hitungKebutuhanKaporit(1000, 'liter', 60, 2);
$isCalcAccurate = $calc['success'] && (abs($calc['data']['kebutuhan_produk_gram'] - 3.3333) < 0.01);
runTest("Kalkulator Kaporit (1.000 L, 60%, 2 mg/L -> 3.33 g)", $isCalcAccurate);

// 5. Kalkulator Kaporit - Input Volume m3 (1 m3 = 1000 L)
$calcM3 = hitungKebutuhanKaporit(1, 'm3', 60, 2);
runTest("Kalkulator Kaporit Konversi m³ ke Liter (1 m³ -> 3.33 g)", $calcM3['success'] && (abs($calcM3['data']['kebutuhan_produk_gram'] - 3.3333) < 0.01));

// 6. Validasi Kalkulator (Tolak Volume <= 0, Kadar <= 0 atau > 100)
$calcZeroVol = hitungKebutuhanKaporit(0, 'liter', 60, 2);
$calcOverKadar = hitungKebutuhanKaporit(1000, 'liter', 105, 2);
runTest("Validasi Kalkulator (Tolak volume nol dan kadar > 100%)", !$calcZeroVol['success'] && !$calcOverKadar['success']);

// 7. Evaluasi Baku Mutu Permenkes No. 2/2023
runTest("Evaluasi pH Netral (7.2 -> Memenuhi Syarat)", evaluasiParameter('MIN_MAX', 7.2, 6.5, 8.5) === 'Memenuhi Syarat');
runTest("Evaluasi pH Asam (5.8 -> Tidak Memenuhi Syarat)", evaluasiParameter('MIN_MAX', 5.8, 6.5, 8.5) === 'Tidak Memenuhi Syarat');
runTest("Evaluasi Kekeruhan Air Minum (2.1 NTU <= 3 NTU -> MS)", evaluasiParameter('MAX', 2.1, null, 3.0) === 'Memenuhi Syarat');
runTest("Evaluasi Kekeruhan Air Minum (4.8 NTU > 3 NTU -> TMS)", evaluasiParameter('MAX', 4.8, null, 3.0) === 'Tidak Memenuhi Syarat');
runTest("Evaluasi Mikrobiologi E. coli (0 CFU -> MS)", evaluasiParameter('EQUALS_ZERO', 0, null, 0) === 'Memenuhi Syarat');
runTest("Evaluasi Mikrobiologi E. coli (3 CFU -> TMS)", evaluasiParameter('EQUALS_ZERO', 3, null, 0) === 'Tidak Memenuhi Syarat');
runTest("Evaluasi Kualitatif Bau ('Tidak Berbau' -> MS)", evaluasiParameter('QUALITATIVE', null, null, null, 'Tidak Berbau') === 'Memenuhi Syarat');
runTest("Evaluasi Kualitatif Bau ('Berbau Kaporit Menyengat' -> TMS)", evaluasiParameter('QUALITATIVE', null, null, null, 'Berbau Kaporit Menyengat') === 'Tidak Memenuhi Syarat');

// 8. Penentuan Kesimpulan Akhir
$msArray = [
    ['status' => 'Memenuhi Syarat', 'wajib' => 1],
    ['status' => 'Memenuhi Syarat', 'wajib' => 1]
];
runTest("Penentuan Kesimpulan Akhir: Memenuhi Syarat (MS)", tentukanKesimpulanPemeriksaan($msArray) === 'Memenuhi Syarat (MS)');

$tmsArray = [
    ['status' => 'Memenuhi Syarat', 'wajib' => 1],
    ['status' => 'Tidak Memenuhi Syarat', 'wajib' => 1]
];
runTest("Penentuan Kesimpulan Akhir: Tidak Memenuhi Syarat (TMS)", tentukanKesimpulanPemeriksaan($tmsArray) === 'Tidak Memenuhi Syarat (TMS)');

$pendingArray = [
    ['status' => 'Memenuhi Syarat', 'wajib' => 1],
    ['status' => 'Belum Diuji', 'wajib' => 1]
];
runTest("Penentuan Kesimpulan Akhir: Belum Dapat Disimpulkan", tentukanKesimpulanPemeriksaan($pendingArray) === 'Belum Dapat Disimpulkan');

// 9. Format Tanggal & Angka Bahasa Indonesia
runTest("Format Tanggal Indonesia ('2026-11-15' -> '15 November 2026')", formatTanggal('2026-11-15') === '15 November 2026');
runTest("Format Angka Indonesia (1250.5 -> '1.250,50')", formatAngka(1250.5, 2) === '1.250,50');

echo "\n========================================================\n";
echo "HASIL PENGUJIAN: {$passCount} DARI {$totalTests} PENGUJIAN LULUS (" . round(($passCount / $totalTests) * 100) . "%)\n";
echo "========================================================\n";
