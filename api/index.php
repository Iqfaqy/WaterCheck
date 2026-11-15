<?php
/**
 * WaterCheck - Vercel Serverless Function Dispatcher / Front Controller
 * File: api/index.php
 *
 * Mengarahkan setiap request HTTP dari Vercel ke script PHP yang sesuai.
 */

// Sembunyikan pesan Deprecated notice agar tampilan web bersih
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

// 1. Ekstrak path URL yang diminta
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Direktori root aplikasi
$rootDir = realpath(__DIR__ . '/..');

// 2. Routing ke halaman utama
if ($path === '' || $path === 'index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF']    = '/index.php';
    require_once $rootDir . '/index.php';
    exit;
}

// 3. Normalisasi path target
$targetPath = $rootDir . '/' . $path;
$realTarget = realpath($targetPath);

// 4. Pastikan file berada di dalam root aplikasi (Mencegah Directory Traversal)
if ($realTarget && str_starts_with($realTarget, $rootDir) && is_file($realTarget)) {
    // Jika file PHP, eksekusi file tersebut
    if (str_ends_with($realTarget, '.php')) {
        $_SERVER['SCRIPT_NAME'] = '/' . $path;
        $_SERVER['PHP_SELF']    = '/' . $path;
        require_once $realTarget;
        exit;
    }

    // Jika file statis (CSS, JS, SVG, PNG, dll) diteruskan ke dispatcher
    $ext = strtolower(pathinfo($realTarget, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'  => 'text/css; charset=UTF-8',
        'js'   => 'application/javascript; charset=UTF-8',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'ico'  => 'image/x-icon',
        'json' => 'application/json; charset=UTF-8',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf'
    ];

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
        header('Cache-Control: public, max-age=86400');
        readfile($realTarget);
        exit;
    }
}

// 5. Cek jika URL diakses tanpa ekstensi .php (contoh: /login atau /dashboard)
if (is_file($rootDir . '/' . $path . '.php')) {
    $script = $rootDir . '/' . $path . '.php';
    $_SERVER['SCRIPT_NAME'] = '/' . $path . '.php';
    $_SERVER['PHP_SELF']    = '/' . $path . '.php';
    require_once $script;
    exit;
}

// 6. Cek jika request mengarah ke folder (contoh: /pages/sumber_air/)
if (is_dir($rootDir . '/' . $path) && is_file($rootDir . '/' . $path . '/index.php')) {
    $script = $rootDir . '/' . $path . '/index.php';
    $_SERVER['SCRIPT_NAME'] = '/' . $path . '/index.php';
    $_SERVER['PHP_SELF']    = '/' . $path . '/index.php';
    require_once $script;
    exit;
}

// 7. Halaman 404 jika tidak ditemukan
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>404 - Halaman Tidak Ditemukan | WaterCheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="text-center p-4">
        <h1 class="display-1 fw-bold text-primary">404</h1>
        <h3 class="fw-bold text-dark">Halaman Tidak Ditemukan</h3>
        <p class="text-muted">Halaman atau berkas <code>/<?= htmlspecialchars($path); ?></code> tidak tersedia di server.</p>
        <a href="/" class="btn btn-primary mt-2">Kembali ke Dashboard WaterCheck</a>
    </div>
</body>
</html>
