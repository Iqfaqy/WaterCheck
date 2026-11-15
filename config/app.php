<?php
/**
 * WaterCheck - Konfigurasi Aplikasi & Sesi Global
 * File: config/app.php
 */

// Pengaturan Timezone Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Informasi Aplikasi
define('APP_NAME', 'WaterCheck');
define('APP_ALT_NAME', 'HydroSan');
define('APP_TAGLINE', 'Solusi Digital Pemeriksaan dan Penyehatan Kualitas Air');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'Sanitarian Development Team');

// Mulai Session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan keamanan cookie session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Deteksi BASE_URL secara dinamis untuk XAMPP maupun PHP Built-in Server
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Tentukan path direktori web relatif terhadap document root
    $docRoot = str_replace('\\', '/', realpath(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ''));
    $appDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    
    $relative = str_replace($docRoot, '', $appDir);
    $relative = trim($relative, '/');
    
    if (!empty($relative)) {
        define('BASE_URL', $protocol . $host . '/' . $relative . '/');
    } else {
        define('BASE_URL', $protocol . $host . '/');
    }
}
