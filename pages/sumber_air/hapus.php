<?php
/**
 * WaterCheck - Hapus Data Sumber Air
 * File: pages/sumber_air/hapus.php
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

checkLogin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('danger', 'ID sumber air tidak valid.');
    redirect(BASE_URL . 'pages/sumber_air/index.php');
}

try {
    // Ambil data untuk pencatatan log
    $stmt = $pdo->prepare("SELECT nama_sumber FROM sumber_air WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $sumber = $stmt->fetch();

    if ($sumber) {
        $stmtDelete = $pdo->prepare("DELETE FROM sumber_air WHERE id = :id");
        $stmtDelete->execute([':id' => $id]);

        $currentUser = currentUser();
        catatLog($pdo, $currentUser['id'] ?? null, "Menghapus data sumber air: '{$sumber['nama_sumber']}' (ID: {$id})");

        setFlashMessage('success', "Data sumber air '{$sumber['nama_sumber']}' berhasil dihapus dari sistem.");
    } else {
        setFlashMessage('warning', "Data sumber air tidak ditemukan atau sudah dihapus sebelumnya.");
    }
} catch (PDOException $e) {
    setFlashMessage('danger', "Gagal menghapus data sumber air: " . $e->getMessage());
}

redirect(BASE_URL . 'pages/sumber_air/index.php');
