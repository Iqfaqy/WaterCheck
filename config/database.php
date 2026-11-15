<?php
/**
 * WaterCheck - Konfigurasi Koneksi Database
 * File: config/database.php
 *
 * Kompatibel ganda:
 * 1. Lokal XAMPP (Default: localhost, root, '', watercheck_db)
 * 2. Vercel Cloud Serverless (Membaca Environment Variables DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SSL)
 */

// Deteksi apakah sedang berjalan di lingkungan Vercel
$isVercel = getenv('VERCEL') || !empty($_ENV['VERCEL']);

// Baca kredensial dari Environment Variables dengan fallback ke XAMPP lokal
$dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
$dbPort = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
$dbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'watercheck_db');
$dbUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$dbPass = (getenv('DB_PASS') !== false) ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? '');
$dbSsl  = getenv('DB_SSL') ?: ($_ENV['DB_SSL'] ?? 'false');

define('DB_HOST', $dbHost);
define('DB_PORT', $dbPort);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];

    // Dukungan SSL untuk Cloud MySQL (Aiven, TiDB Cloud, PlanetScale, Clever Cloud, Railway, Supabase)
    if ($dbSsl === 'true' || $dbSsl === '1' || getenv('MYSQL_ATTR_SSL_CA')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
    
    // Tampilan bantuan jika dijalankan di Vercel
    if ($isVercel) {
        die("
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <title>Konfigurasi Database Cloud Vercel | WaterCheck</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body class='bg-light py-5'>
            <div class='container' style='max-width: 720px;'>
                <div class='card shadow-sm border-danger'>
                    <div class='card-header bg-danger text-white py-3'>
                        <h5 class='mb-0 fw-bold'>⚠️ Database Cloud Belum Terhubung di Vercel</h5>
                    </div>
                    <div class='card-body p-4'>
                        <p>Aplikasi <strong>WaterCheck</strong> berhasil di-deploy ke Vercel, namun belum dapat terhubung ke server database MySQL remote.</p>
                        <p class='text-muted small'>Karena Vercel adalah platform serverless cloud, Anda memerlukan database MySQL online (gratis) untuk menyimpan data.</p>
                        
                        <h6 class='fw-bold text-dark mt-4'>Langkah Menghubungkan Database:</h6>
                        <ol class='small' style='line-height: 1.8;'>
                            <li>Buat database MySQL gratis di penyedia cloud seperti:
                                <ul>
                                    <li><strong>Aiven for MySQL</strong> (Gratis selamanya / Free Tier)</li>
                                    <li><strong>TiDB Cloud</strong> (Serverless MySQL gratis)</li>
                                    <li><strong>Clever Cloud</strong> (Add-on MySQL gratis)</li>
                                </ul>
                            </li>
                            <li>Impor file <code>database/watercheck_db.sql</code> ke database cloud tersebut.</li>
                            <li>Buka <strong>Vercel Dashboard &rarr; Project Settings &rarr; Environment Variables</strong>, lalu tambahkan:
                                <ul>
                                    <li><code>DB_HOST</code> : <em>host database cloud Anda</em></li>
                                    <li><code>DB_PORT</code> : <em>port (misal: 3306 atau port cloud)</em></li>
                                    <li><code>DB_NAME</code> : <em>nama database Anda</em></li>
                                    <li><code>DB_USER</code> : <em>username database cloud</em></li>
                                    <li><code>DB_PASS</code> : <em>password database cloud</em></li>
                                    <li><code>DB_SSL</code> : <code>true</code> <em>(jika cloud mewajibkan SSL)</em></li>
                                </ul>
                            </li>
                            <li>Lakukan <strong>Redeploy</strong> di Vercel.</li>
                        </ol>

                        <div class='alert alert-secondary small mt-3 mb-0 font-monospace'>
                            Detail Error: " . htmlspecialchars($errorMessage) . "
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ");
    }

    // Tampilan bantuan jika dijalankan di XAMPP lokal
    die("
    <div style='font-family: Arial, sans-serif; background: #fff3cd; color: #856404; padding: 25px; border-radius: 8px; border: 1px solid #ffeeba; max-width: 650px; margin: 40px auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <h3 style='margin-top:0; color: #721c24;'>⚠️ Gagal Terhubung ke Database MySQL!</h3>
        <p>Aplikasi <strong>WaterCheck</strong> tidak dapat terhubung ke server database. Pastikan langkah berikut:</p>
        <ol style='line-height: 1.6;'>
            <li>Layanan <strong>MySQL</strong> di control panel XAMPP sudah berstatus <strong>Running</strong>.</li>
            <li>Database <code>" . htmlspecialchars(DB_NAME) . "</code> telah dibuat atau diimpor dari file <code>database/watercheck_db.sql</code>.</li>
            <li>Username (<code>" . htmlspecialchars(DB_USER) . "</code>) dan password pada <code>config/database.php</code> sudah sesuai.</li>
        </ol>
        <p style='margin-bottom:0; font-size:12px; color:#6c757d;'>Detail Teknis: " . htmlspecialchars($errorMessage) . "</p>
    </div>
    ");
}
