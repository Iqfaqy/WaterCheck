<?php
/**
 * WaterCheck - Proses Penyimpanan & Evaluasi Otomatis Pemeriksaan Kualitas Air
 * File: pages/pemeriksaan/proses.php
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'pages/pemeriksaan/tambah.php');
}

$csrfToken           = $_POST['csrf_token'] ?? '';
$nomorPemeriksaan    = trim($_POST['nomor_pemeriksaan'] ?? '');
$tanggalPemeriksaan  = trim($_POST['tanggal_pemeriksaan'] ?? date('Y-m-d'));
$jenisAir            = trim($_POST['jenis_air'] ?? 'Air Minum');
$sumberAirId         = !empty($_POST['sumber_air_id']) ? (int)$_POST['sumber_air_id'] : null;
$lokasi              = trim($_POST['lokasi'] ?? '');
$catatan             = trim($_POST['catatan'] ?? '');

$standarIds          = $_POST['standar_id'] ?? [];
$nilaiHasilList      = $_POST['nilai_hasil'] ?? [];
$nilaiKualitatifList = $_POST['nilai_kualitatif'] ?? [];
$metodeList          = $_POST['metode'] ?? [];
$keteranganList      = $_POST['keterangan_param'] ?? [];

// 1. Verifikasi CSRF
if (!verifyCsrfToken($csrfToken)) {
    setFlashMessage('danger', 'Token keamanan CSRF tidak valid. Silakan coba kembali.');
    redirect(BASE_URL . 'pages/pemeriksaan/tambah.php');
}

// 2. Validasi Kolom Utama
if (empty($nomorPemeriksaan) || empty($tanggalPemeriksaan) || empty($lokasi)) {
    setFlashMessage('danger', 'Nomor pemeriksaan, tanggal, dan lokasi wajib diisi.');
    redirect(BASE_URL . 'pages/pemeriksaan/tambah.php');
}

// 3. Mulai Transaksi Database
try {
    $pdo->beginTransaction();

    $currentUser = currentUser();
    $userId = $currentUser['id'] ?? null;

    // Pastikan nomor pemeriksaan unik
    $stmtCheck = $pdo->prepare("SELECT id FROM pemeriksaan WHERE nomor_pemeriksaan = :no LIMIT 1");
    $stmtCheck->execute([':no' => $nomorPemeriksaan]);
    if ($stmtCheck->fetch()) {
        // Jika duplikat, buat nomor baru otomatis
        $nomorPemeriksaan = generateNomorPemeriksaan($pdo);
    }

    // Insert master data pemeriksaan awal (status default Belum Dapat Disimpulkan)
    $stmtMaster = $pdo->prepare("
        INSERT INTO pemeriksaan (
            nomor_pemeriksaan, sumber_air_id, user_id, tanggal_pemeriksaan,
            jenis_air, lokasi, kesimpulan, catatan, created_at
        ) VALUES (
            :no, :sumber, :user, :tanggal, :jenis, :lokasi, 'Belum Dapat Disimpulkan', :catatan, NOW()
        )
    ");
    $stmtMaster->execute([
        ':no'      => $nomorPemeriksaan,
        ':sumber'  => $sumberAirId,
        ':user'    => $userId,
        ':tanggal' => $tanggalPemeriksaan,
        ':jenis'   => $jenisAir,
        ':lokasi'  => $lokasi,
        ':catatan' => !empty($catatan) ? $catatan : null
    ]);

    $pemeriksaanId = (int)$pdo->lastInsertId();

    // 4. Proses Evaluasi Otomatis Tiap Parameter
    $evaluationSummary = [];

    $stmtInsertDetail = $pdo->prepare("
        INSERT INTO detail_pemeriksaan (
            pemeriksaan_id, standar_id, nilai_hasil, nilai_kualitatif, satuan,
            status, metode_pemeriksaan, keterangan, created_at
        ) VALUES (
            :pemeriksaan_id, :standar_id, :nilai_hasil, :nilai_kualitatif, :satuan,
            :status, :metode, :keterangan, NOW()
        )
    ");

    if (!empty($standarIds)) {
        // Ambil data referensi baku mutu dari database
        $placeholders = str_repeat('?,', count($standarIds) - 1) . '?';
        $stmtStd = $pdo->prepare("SELECT * FROM standar_baku_mutu WHERE id IN ($placeholders)");
        $stmtStd->execute($standarIds);
        $standarMap = [];
        while ($row = $stmtStd->fetch()) {
            $standarMap[$row['id']] = $row;
        }

        foreach ($standarIds as $stdId) {
            $stdId = (int)$stdId;
            if (!isset($standarMap[$stdId])) continue;

            $std = $standarMap[$stdId];
            $operator = $std['operator'];
            $nilaiMin = $std['nilai_min'];
            $nilaiMax = $std['nilai_max'];
            $satuan   = $std['satuan'];
            $isWajib  = (int)$std['wajib'];

            $nilaiHasil = null;
            $nilaiKualitatif = null;

            if ($operator === 'QUALITATIVE') {
                $nilaiKualitatif = $nilaiKualitatifList[$stdId] ?? null;
                if ($nilaiKualitatif === 'Belum Diuji') {
                    $nilaiKualitatif = null;
                }
            } else {
                $rawVal = $nilaiHasilList[$stdId] ?? '';
                if ($rawVal !== '' && is_numeric($rawVal)) {
                    $nilaiHasil = (float)$rawVal;
                }
            }

            // Panggil fungsi evaluasi otomatis baku mutu
            $statusParam = evaluasiParameter($operator, $nilaiHasil, $nilaiMin, $nilaiMax, $nilaiKualitatif);

            // Simpan status untuk penentuan kesimpulan akhir
            $evaluationSummary[] = [
                'status' => $statusParam,
                'wajib'  => $isWajib
            ];

            // Insert detail
            $stmtInsertDetail->execute([
                ':pemeriksaan_id'  => $pemeriksaanId,
                ':standar_id'      => $stdId,
                ':nilai_hasil'     => $nilaiHasil,
                ':nilai_kualitatif'=> $nilaiKualitatif,
                ':satuan'          => $satuan,
                ':status'          => $statusParam,
                ':metode'          => !empty($metodeList[$stdId]) ? trim($metodeList[$stdId]) : null,
                ':keterangan'      => !empty($keteranganList[$stdId]) ? trim($keteranganList[$stdId]) : null
            ]);
        }
    }

    // 5. Tentukan Kesimpulan Akhir (MS / TMS / Belum Dapat Disimpulkan)
    $kesimpulanAkhir = tentukanKesimpulanPemeriksaan($evaluationSummary);

    // Update kesimpulan akhir ke tabel pemeriksaan
    $stmtUpdateMaster = $pdo->prepare("UPDATE pemeriksaan SET kesimpulan = :kesimpulan WHERE id = :id");
    $stmtUpdateMaster->execute([
        ':kesimpulan' => $kesimpulanAkhir,
        ':id'         => $pemeriksaanId
    ]);

    // Commit transaksi
    $pdo->commit();

    catatLog($pdo, $userId, "Menyimpan hasil pemeriksaan {$nomorPemeriksaan} (Kesimpulan: {$kesimpulanAkhir})");

    setFlashMessage('success', "Pemeriksaan {$nomorPemeriksaan} berhasil disimpan dengan kesimpulan: {$kesimpulanAkhir}!");
    redirect(BASE_URL . "pages/pemeriksaan/detail.php?id={$pemeriksaanId}");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlashMessage('danger', 'Gagal memproses pemeriksaan air: ' . $e->getMessage());
    redirect(BASE_URL . 'pages/pemeriksaan/tambah.php');
}
