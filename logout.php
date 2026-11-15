<?php
/**
 * WaterCheck - Proses Logout Pengguna
 * File: logout.php
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'] ?? null;
    $nama   = $_SESSION['nama'] ?? 'Pengguna';
    
    // Catat log logout
    catatLog($pdo, $userId, "Pengguna {$nama} telah logout dari aplikasi.");
}

// Hapus seluruh data session
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Mulai sesi baru untuk menampung flash message logout
session_start();
setFlashMessage('info', 'Anda telah berhasil keluar dari sistem WaterCheck.');
redirect(BASE_URL . 'login.php');
