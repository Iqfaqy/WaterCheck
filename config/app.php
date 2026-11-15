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

// Deteksi lingkungan Vercel / Serverless
$isVercel = getenv('VERCEL') || !empty($_ENV['VERCEL']);

// Sembunyikan notifikasi Deprecated di lingkungan production / Vercel
if ($isVercel) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    ini_set('display_errors', '0');
}

// Mulai Session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    // Pada Vercel / AWS Lambda, hanya folder /tmp yang dapat ditulisi untuk session
    if ($isVercel || (is_dir('/tmp') && is_writable('/tmp') && DIRECTORY_SEPARATOR === '/')) {
        session_save_path('/tmp');
    }

    // Pengaturan keamanan cookie session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Deteksi Protocol (HTTPS untuk Vercel SSL Reverse Proxy & XAMPP)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocol = $isHttps ? "https://" : "http://";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// Deteksi BASE_URL secara dinamis untuk Vercel maupun XAMPP lokal
if (!defined('BASE_URL')) {
    if (getenv('BASE_URL')) {
        define('BASE_URL', rtrim(getenv('BASE_URL'), '/') . '/');
    } elseif ($isVercel) {
        // Pada Vercel, root URL selalu https://domain.vercel.app/
        define('BASE_URL', $protocol . $host . '/');
    } else {
        // Tentukan path direktori web relatif terhadap document root (XAMPP subfolder)
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
}
