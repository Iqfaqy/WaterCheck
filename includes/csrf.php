<?php
/**
 * WaterCheck - Pengamanan Token CSRF (Cross-Site Request Forgery)
 * File: includes/csrf.php
 */

if (!function_exists('generateCsrfToken')) {
    /**
     * Menghasilkan token CSRF acak dan menyimpannya di session
     * @return string
     */
    function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('getCsrfToken')) {
    /**
     * Mengambil token CSRF saat ini
     * @return string
     */
    function getCsrfToken(): string {
        return generateCsrfToken();
    }
}

if (!function_exists('verifyCsrfToken')) {
    /**
     * Memverifikasi validitas token CSRF
     * @param string|null $token
     * @return bool
     */
    function verifyCsrfToken(?string $token): bool {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('csrfField')) {
    /**
     * Menghasilkan input hidden CSRF untuk form HTML
     * @return string
     */
    function csrfField(): string {
        $token = htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
