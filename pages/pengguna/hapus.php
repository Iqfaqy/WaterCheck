<?php
/**
 * WaterCheck - Hapus Data Pengguna
 * File: pages/pengguna/hapus.php
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

checkAdmin();

$id = (int)($_GET['id'] ?? 0);
$currentUser = currentUser();

if ($id <= 0) {
    setFlashMessage('danger', 'ID pengguna tidak valid.');
    redirect(BASE_URL . 'pages/pengguna/index.php');
}

// Keamanan: Larang menghapus diri sendiri
if ($id === (int)$currentUser['id']) {
    setFlashMessage('danger', 'Tindakan dilarang! Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
    redirect(BASE_URL . 'pages/pengguna/index.php');
}

try {
    $stmt = $pdo->prepare("SELECT username, nama FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    if ($user) {
        $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmtDelete->execute([':id' => $id]);

        catatLog($pdo, $currentUser['id'], "Menghapus akun pengguna '{$user['username']}' ({$user['nama']})");

        setFlashMessage('success', "Akun pengguna '{$user['username']}' berhasil dihapus.");
    } else {
        setFlashMessage('warning', 'Pengguna tidak ditemukan.');
    }
} catch (PDOException $e) {
    setFlashMessage('danger', 'Gagal menghapus pengguna: ' . $e->getMessage());
}

redirect(BASE_URL . 'pages/pengguna/index.php');
