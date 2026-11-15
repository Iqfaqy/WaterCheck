<?php
/**
 * WaterCheck - Simpan Hasil Perhitungan Kaporit
 * File: pages/kalkulator/proses.php
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'pages/kalkulator/index.php');
}

$csrfToken           = $_POST['csrf_token'] ?? '';
$sumberAirId         = !empty($_POST['sumber_air_id']) ? (int)$_POST['sumber_air_id'] : null;
$jenisDesinfektan   = trim($_POST['jenis_desinfektan'] ?? 'Kaporit');
$volumeAir           = (float)($_POST['volume_air'] ?? 0);
$satuanVolume       = trim($_POST['satuan_volume'] ?? 'liter');
$kadarKlorinAktif   = (float)($_POST['kadar_klorin_aktif'] ?? 0);
$targetDosis         = (float)($_POST['target_dosis'] ?? 0);
$kebutuhanKlorinAktif = (float)($_POST['kebutuhan_klorin_aktif'] ?? 0);
$kebutuhanProdukMg   = (float)($_POST['kebutuhan_produk_mg'] ?? 0);
$kebutuhanProdukGram = (float)($_POST['kebutuhan_produk_gram'] ?? 0);
$catatan             = trim($_POST['catatan'] ?? '');

// 1. Verifikasi CSRF
if (!verifyCsrfToken($csrfToken)) {
    setFlashMessage('danger', 'Token keamanan sesi tidak valid.');
    redirect(BASE_URL . 'pages/kalkulator/index.php');
}

// 2. Validasi Angka
if ($volumeAir <= 0 || $kadarKlorinAktif <= 0 || $targetDosis <= 0 || $kebutuhanProdukGram <= 0) {
    setFlashMessage('danger', 'Gagal menyimpan: Parameter perhitungan tidak valid.');
    redirect(BASE_URL . 'pages/kalkulator/index.php');
}

try {
    $currentUser = currentUser();
    $userId = $currentUser['id'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO kalkulasi_kaporit (
            sumber_air_id, user_id, jenis_desinfektan, volume_air, satuan_volume,
            kadar_klorin_aktif, target_dosis, kebutuhan_klorin_aktif,
            kebutuhan_produk_mg, kebutuhan_produk_gram, catatan, created_at
        ) VALUES (
            :sumber_air_id, :user_id, :jenis_desinfektan, :volume_air, :satuan_volume,
            :kadar_klorin_aktif, :target_dosis, :kebutuhan_klorin_aktif,
            :kebutuhan_produk_mg, :kebutuhan_produk_gram, :catatan, NOW()
        )
    ");

    $stmt->execute([
        ':sumber_air_id'           => $sumberAirId,
        ':user_id'                 => $userId,
        ':jenis_desinfektan'       => $jenisDesinfektan,
        ':volume_air'              => $volumeAir,
        ':satuan_volume'           => $satuanVolume,
        ':kadar_klorin_aktif'      => $kadarKlorinAktif,
        ':target_dosis'            => $targetDosis,
        ':kebutuhan_klorin_aktif'  => $kebutuhanKlorinAktif,
        ':kebutuhan_produk_mg'     => $kebutuhanProdukMg,
        ':kebutuhan_produk_gram'   => $kebutuhanProdukGram,
        ':catatan'                 => !empty($catatan) ? $catatan : null
    ]);

    $lastId = $pdo->lastInsertId();
    catatLog($pdo, $userId, "Menyimpan kalkulasi kaporit ID {$lastId} (Dosis: {$kebutuhanProdukGram} gram, Vol: {$volumeAir} {$satuanVolume})");

    setFlashMessage('success', "Hasil perhitungan kaporit (" . formatAngka($kebutuhanProdukGram, 2) . " gram) berhasil disimpan ke riwayat!");
    redirect(BASE_URL . 'pages/kalkulator/riwayat.php');

} catch (PDOException $e) {
    setFlashMessage('danger', 'Terjadi galat database saat menyimpan perhitungan: ' . $e->getMessage());
    redirect(BASE_URL . 'pages/kalkulator/index.php');
}
