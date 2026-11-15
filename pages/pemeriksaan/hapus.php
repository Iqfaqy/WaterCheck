<?php
/**
 * WaterCheck - Hapus Data Pemeriksaan Kualitas Air
 * File: pages/pemeriksaan/hapus.php
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

checkLogin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlashMessage('danger', 'ID pemeriksaan tidak valid.');
    redirect(BASE_URL . 'pages/pemeriksaan/index.php');
}

try {
    $stmt = $pdo->prepare("SELECT nomor_pemeriksaan FROM pemeriksaan WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $pemeriksaan = $stmt->fetch();

    if ($pemeriksaan) {
        $stmtDelete = $pdo->prepare("DELETE FROM pemeriksaan WHERE id = :id");
        $stmtDelete->execute([':id' => $id]);

        $currentUser = currentUser();
        catatLog($pdo, $currentUser['id'] ?? null, "Menghapus data pemeriksaan {$pemeriksaan['nomor_pemeriksaan']} (ID: {$id})");

        setFlashMessage('success', "Arsip pemeriksaan {$pemeriksaan['nomor_pemeriksaan']} berhasil dihapus.");
    } else {
        setFlashMessage('warning', "Data pemeriksaan tidak ditemukan atau sudah dihapus sebelumnya.");
    }
} catch (PDOException $e) {
    setFlashMessage('danger', "Gagal menghapus data pemeriksaan: " . $e->getMessage());
}

redirect(BASE_URL . 'pages/pemeriksaan/index.php');
