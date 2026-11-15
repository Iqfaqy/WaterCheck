<?php
/**
 * WaterCheck - Konfigurasi Koneksi Database
 * File: config/database.php
 * Menggunakan PDO (PHP Data Objects) dengan Prepared Statements
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'watercheck_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Tampilan pesan error yang aman dan informatif untuk Sanitarian / Pengembang lokal
    $errorMessage = $e->getMessage();
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
