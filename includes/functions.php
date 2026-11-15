<?php
/**
 * WaterCheck - Kumpulan Fungsi Pembantu (Helper & Business Logic)
 * File: includes/functions.php
 */

require_once __DIR__ . '/../config/app.php';

/**
 * Memformat tanggal ke dalam format bahasa Indonesia
 * @param string|null $date
 * @param string $format 'lengkap' | 'pendek' | 'datetime'
 * @return string
 */
function formatTanggal(?string $date, string $format = 'lengkap'): string {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }

    $timestamp = strtotime($date);
    if (!$timestamp) {
        return htmlspecialchars($date);
    }

    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $bulanIndoPendek = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];

    $d = date('j', $timestamp);
    $m = (int)date('n', $timestamp);
    $y = date('Y', $timestamp);
    $his = date('H:i', $timestamp);

    if ($format === 'pendek') {
        return "{$d} {$bulanIndoPendek[$m]} {$y}";
    } elseif ($format === 'datetime') {
        return "{$d} {$bulanIndo[$m]} {$y}, {$his} WIB";
    }

    return "{$d} {$bulanIndo[$m]} {$y}";
}

/**
 * Memformat angka dengan pemisah ribuan titik dan desimal koma (Standar Indonesia)
 * @param float|int|string|null $number
 * @param int $decimals
 * @return string
 */
function formatAngka($number, int $decimals = 2): string {
    if ($number === null || $number === '') {
        return '-';
    }
    $num = (float)$number;
    // Jika bilangan bulat, tampilkan tanpa desimal kecuali dipaksa
    if (floor($num) == $num && $decimals === 0) {
        return number_format($num, 0, ',', '.');
    }
    return number_format($num, $decimals, ',', '.');
}

/**
 * Memformat nilai mata uang Rupiah
 * @param float|int|null $val
 * @return string
 */
function formatRupiah($val): string {
    if ($val === null) return 'Rp 0';
    return 'Rp ' . number_format((float)$val, 0, ',', '.');
}

/**
 * Menghasilkan nomor pemeriksaan otomatis yang unik (Contoh: WC-202611-0001)
 * @param PDO $pdo
 * @return string
 */
function generateNomorPemeriksaan(PDO $pdo): string {
    $prefix = 'WC-' . date('Ym') . '-';
    $stmt = $pdo->prepare("SELECT nomor_pemeriksaan FROM pemeriksaan WHERE nomor_pemeriksaan LIKE :prefix ORDER BY id DESC LIMIT 1");
    $stmt->execute([':prefix' => $prefix . '%']);
    $lastRecord = $stmt->fetch();

    if ($lastRecord && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastRecord['nomor_pemeriksaan'], $matches)) {
        $nextNumber = (int)$matches[1] + 1;
    } else {
        $nextNumber = 1;
    }

    return $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Menghitung kebutuhan produk kaporit secara sistematis
 * Rumus:
 * V (liter) = V (m³) * 1000 (jika m³)
 * Kebutuhan klorin aktif (mg) = Volume (L) * Target dosis (mg/L)
 * Kebutuhan produk (mg) = Klorin aktif (mg) / (Kadar klorin / 100)
 * Kebutuhan produk (g) = Kebutuhan produk (mg) / 1000
 *
 * @param float $volumeAir
 * @param string $satuanVolume 'liter' | 'm3'
 * @param float $kadarKlorinPersen
 * @param float $targetDosisMgL
 * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
 */
function hitungKebutuhanKaporit(float $volumeAir, string $satuanVolume, float $kadarKlorinPersen, float $targetDosisMgL): array {
    // Validasi nilai masukan
    if ($volumeAir <= 0) {
        return ['success' => false, 'data' => null, 'error' => 'Volume air harus lebih besar dari 0.'];
    }
    if ($kadarKlorinPersen <= 0 || $kadarKlorinPersen > 100) {
        return ['success' => false, 'data' => null, 'error' => 'Kadar klorin aktif harus antara 0,01% dan 100%.'];
    }
    if ($targetDosisMgL <= 0) {
        return ['success' => false, 'data' => null, 'error' => 'Target dosis klorin harus lebih besar dari 0 mg/L.'];
    }

    // 1. Konversi volume ke Liter
    $volumeLiter = ($satuanVolume === 'm3') ? ($volumeAir * 1000.0) : $volumeAir;

    // 2. Kebutuhan klorin aktif murni (mg)
    $kebutuhanKlorinAktifMg = $volumeLiter * $targetDosisMgL;

    // 3. Kebutuhan produk kaporit komersial (mg)
    $faktorKadar = $kadarKlorinPersen / 100.0;
    if ($faktorKadar <= 0) {
        return ['success' => false, 'data' => null, 'error' => 'Terjadi kesalahan perhitungan (pembagian dengan nol).'];
    }
    $kebutuhanProdukMg = $kebutuhanKlorinAktifMg / $faktorKadar;

    // 4. Konversi ke Gram
    $kebutuhanProdukGram = $kebutuhanProdukMg / 1000.0;

    return [
        'success' => true,
        'error'   => null,
        'data'    => [
            'volume_input'           => $volumeAir,
            'satuan_volume'          => $satuanVolume,
            'volume_liter'           => $volumeLiter,
            'kadar_klorin_aktif'     => $kadarKlorinPersen,
            'target_dosis'           => $targetDosisMgL,
            'kebutuhan_klorin_aktif' => $kebutuhanKlorinAktifMg,
            'kebutuhan_produk_mg'    => $kebutuhanProdukMg,
            'kebutuhan_produk_gram'  => $kebutuhanProdukGram
        ]
    ];
}

/**
 * Evaluasi status parameter baku mutu air
 *
 * @param string $operator MIN_MAX | MAX | MIN | EQUALS_ZERO | QUALITATIVE | SUHU
 * @param float|null $nilaiHasil Nilai numerik hasil uji
 * @param float|null $nilaiMin Batas minimum baku mutu
 * @param float|null $nilaiMax Batas maksimum baku mutu
 * @param string|null $nilaiKualitatif Nilai teks (e.g. 'Tidak Berbau')
 * @return string 'Memenuhi Syarat' | 'Tidak Memenuhi Syarat' | 'Belum Diuji'
 */
function evaluasiParameter(string $operator, ?float $nilaiHasil, ?float $nilaiMin, ?float $nilaiMax, ?string $nilaiKualitatif = null): string {
    // Jika bertipe kualitatif (Bau / Rasa / Kejernihan)
    if ($operator === 'QUALITATIVE') {
        if ($nilaiKualitatif === null || trim($nilaiKualitatif) === '') {
            return 'Belum Diuji';
        }
        $val = strtolower(trim($nilaiKualitatif));
        // Kata kunci yang memenuhi syarat: tidak berbau, tidak berasa, tawar, jernih
        if (str_contains($val, 'tidak') || str_contains($val, 'tawar') || str_contains($val, 'jernih') || str_contains($val, 'normal')) {
            return 'Memenuhi Syarat';
        }
        return 'Tidak Memenuhi Syarat';
    }

    // Jika nilai numerik belum diisi
    if ($nilaiHasil === null) {
        return 'Belum Diuji';
    }

    switch ($operator) {
        case 'MIN_MAX':
            if ($nilaiMin !== null && $nilaiHasil < $nilaiMin) {
                return 'Tidak Memenuhi Syarat';
            }
            if ($nilaiMax !== null && $nilaiHasil > $nilaiMax) {
                return 'Tidak Memenuhi Syarat';
            }
            return 'Memenuhi Syarat';

        case 'MAX':
            if ($nilaiMax !== null && $nilaiHasil > $nilaiMax) {
                return 'Tidak Memenuhi Syarat';
            }
            return 'Memenuhi Syarat';

        case 'MIN':
            if ($nilaiMin !== null && $nilaiHasil < $nilaiMin) {
                return 'Tidak Memenuhi Syarat';
            }
            return 'Memenuhi Syarat';

        case 'EQUALS_ZERO':
            // Khusus mikrobiologi E. coli dan Coliform pada air minum (harus 0)
            if ($nilaiHasil > 0) {
                return 'Tidak Memenuhi Syarat';
            }
            return 'Memenuhi Syarat';

        case 'SUHU':
            // Permenkes: Deviasi maksimum 3°C dari suhu udara (di sini diizinkan deviasi -3 s/d +3 atau nilai mutlak)
            // Nilai min -3, max 3 menunjukkan deviasi
            if ($nilaiMin !== null && $nilaiMax !== null) {
                // Jika input langsung deviasi atau suhu mutlak 20-35
                if ($nilaiHasil < 10 && $nilaiHasil > -10) {
                    return (abs($nilaiHasil) <= 3.0) ? 'Memenuhi Syarat' : 'Tidak Memenuhi Syarat';
                }
                // Jika input adalah suhu aktual air (standar umum Indonesia 25-32°C)
                return ($nilaiHasil >= 20.0 && $nilaiHasil <= 35.0) ? 'Memenuhi Syarat' : 'Tidak Memenuhi Syarat';
            }
            return 'Memenuhi Syarat';

        default:
            return 'Belum Diuji';
    }
}

/**
 * Menentukan kesimpulan akhir pemeriksaan air berdasarkan kumpulan status parameter
 *
 * Aturan:
 * 1. TMS: jika ada minimal 1 parameter wajib yang berstatus 'Tidak Memenuhi Syarat'
 * 2. Belum Dapat Disimpulkan: ada parameter wajib yang 'Belum Diuji' dan tidak ada TMS
 * 3. MS: seluruh parameter wajib yang dipersyaratkan telah diuji dan 'Memenuhi Syarat'
 *
 * @param array $parameters List array parameter: [['status' => ..., 'wajib' => 1], ...]
 * @return string 'Memenuhi Syarat (MS)' | 'Tidak Memenuhi Syarat (TMS)' | 'Belum Dapat Disimpulkan'
 */
function tentukanKesimpulanPemeriksaan(array $parameters): string {
    if (empty($parameters)) {
        return 'Belum Dapat Disimpulkan';
    }

    $hasTms = false;
    $hasBelumDiuji = false;
    $wajibCount = 0;

    foreach ($parameters as $p) {
        $isWajib = (bool)($p['wajib'] ?? 1);
        $status = $p['status'] ?? 'Belum Diuji';

        if ($status === 'Tidak Memenuhi Syarat') {
            $hasTms = true;
            // Jika ada yang TMS, langsung TMS
            break;
        }

        if ($isWajib) {
            $wajibCount++;
            if ($status === 'Belum Diuji') {
                $hasBelumDiuji = true;
            }
        }
    }

    if ($hasTms) {
        return 'Tidak Memenuhi Syarat (TMS)';
    }

    if ($hasBelumDiuji || $wajibCount === 0) {
        return 'Belum Dapat Disimpulkan';
    }

    return 'Memenuhi Syarat (MS)';
}

/**
 * Melakukan pengalihan URL (Redirect) secara aman
 * @param string $url
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * Menyimpan flash message ke session untuk ditampilkan di turn berikutnya
 * @param string $type 'success' | 'danger' | 'warning' | 'info'
 * @param string $message
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Mengambil dan menghapus flash message dari session
 * @return array|null ['type' => ..., 'message' => ...]
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitasi data string dari potensi XSS
 * @param mixed $data
 * @return mixed
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    return is_string($data) ? htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8') : $data;
}

/**
 * Mencatat aktivitas pengguna ke tabel log_aktivitas
 * @param PDO $pdo
 * @param int|null $userId
 * @param string $aktivitas
 */
function catatLog(PDO $pdo, ?int $userId, string $aktivitas): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (user_id, aktivitas, created_at) VALUES (:user_id, :aktivitas, NOW())");
        $stmt->execute([
            ':user_id'   => $userId,
            ':aktivitas' => $aktivitas
        ]);
    } catch (Exception $e) {
        // Log silently agar tidak menghentikan alur utama aplikasi
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
