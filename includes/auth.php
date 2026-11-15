<?php
/**
 * WaterCheck - Autentikasi dan Otorisasi Sesi
 * File: includes/auth.php
 */

require_once __DIR__ . '/../config/app.php';

if (!function_exists('isLoggedIn')) {
    /**
     * Memeriksa apakah pengguna sudah login
     * @return bool
     */
    function isLoggedIn(): bool {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['username']);
    }
}

if (!function_exists('currentUser')) {
    /**
     * Mengambil data pengguna yang sedang login dari session
     * @return array|null
     */
    function currentUser(): ?array {
        if (!isLoggedIn()) {
            return null;
        }
        return [
            'id'       => $_SESSION['user_id'],
            'nama'     => $_SESSION['nama'] ?? 'Pengguna',
            'username' => $_SESSION['username'] ?? '',
            'role'     => $_SESSION['role'] ?? 'petugas'
        ];
    }
}

if (!function_exists('hasRole')) {
    /**
     * Memeriksa apakah pengguna memiliki role tertentu
     * @param string $role
     * @return bool
     */
    function hasRole(string $role): bool {
        $user = currentUser();
        return $user && ($user['role'] === $role);
    }
}

if (!function_exists('checkLogin')) {
    /**
     * Middleware: Mengharuskan pengguna sudah login
     * Jika belum login, dialihkan ke halaman login
     */
    function checkLogin(): void {
        if (!isLoggedIn()) {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Silakan masuk (login) terlebih dahulu untuk mengakses sistem WaterCheck.'
            ];
            $target = defined('BASE_URL') ? BASE_URL . 'login.php' : '../../login.php';
            header("Location: " . $target);
            exit;
        }
    }
}

if (!function_exists('checkAdmin')) {
    /**
     * Middleware: Mengharuskan pengguna memiliki hak akses Administrator
     * Jika bukan admin, dialihkan ke dashboard dengan notifikasi larangan
     */
    function checkAdmin(): void {
        checkLogin();
        if (!hasRole('admin')) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Akses ditolak! Halaman ini hanya dapat diakses oleh Administrator.'
            ];
            $target = defined('BASE_URL') ? BASE_URL . 'dashboard.php' : '../../dashboard.php';
            header("Location: " . $target);
            exit;
        }
    }
}
