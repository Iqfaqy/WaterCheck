<?php
/**
 * WaterCheck - Tambah Sumber Air Baru
 * File: pages/sumber_air/tambah.php
 */

$pageTitle = 'Tambah Sumber Air';
$activeMenu = 'sumber_air';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

$errors = [];
$namaSumber = '';
$jenisSumber = 'Sumur gali';
$pemilik = '';
$lokasi = '';
$volume = '';
$keterangan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken   = $_POST['csrf_token'] ?? '';
    $namaSumber  = trim($_POST['nama_sumber'] ?? '');
    $jenisSumber = trim($_POST['jenis_sumber'] ?? '');
    $pemilik     = trim($_POST['pemilik'] ?? '');
    $lokasi      = trim($_POST['lokasi'] ?? '');
    $volumeInput = trim($_POST['volume'] ?? '0');
    $keterangan  = trim($_POST['keterangan'] ?? '');

    // 1. Verifikasi CSRF
    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Token keamanan (CSRF) tidak valid atau telah kedaluwarsa. Silakan coba lagi.';
    }

    // 2. Validasi Server-Side
    if (empty($namaSumber)) {
        $errors[] = 'Nama sumber air wajib diisi.';
    }
    if (empty($jenisSumber)) {
        $errors[] = 'Jenis sumber air wajib dipilih.';
    }
    if (empty($pemilik)) {
        $errors[] = 'Nama pemilik atau instansi pengelola wajib diisi.';
    }
    if (empty($lokasi)) {
        $errors[] = 'Alamat atau lokasi sumber air wajib diisi.';
    }
    if (!is_numeric($volumeInput) || (float)$volumeInput < 0) {
        $errors[] = 'Volume air harus berupa angka numerik positif (minimal 0).';
    }

    // 3. Simpan ke Database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO sumber_air (nama_sumber, jenis_sumber, pemilik, lokasi, volume, keterangan, created_at)
                VALUES (:nama_sumber, :jenis_sumber, :pemilik, :lokasi, :volume, :keterangan, NOW())
            ");
            $stmt->execute([
                ':nama_sumber'  => $namaSumber,
                ':jenis_sumber' => $jenisSumber,
                ':pemilik'      => $pemilik,
                ':lokasi'       => $lokasi,
                ':volume'       => (float)$volumeInput,
                ':keterangan'   => !empty($keterangan) ? $keterangan : null
            ]);

            $newId = $pdo->lastInsertId();
            catatLog($pdo, $currentUser['id'], "Menambahkan sumber air baru: '{$namaSumber}' (ID: {$newId})");

            setFlashMessage('success', "Sumber air '{$namaSumber}' berhasil disimpan ke sistem!");
            redirect(BASE_URL . 'pages/sumber_air/index.php');
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan ke database: ' . $e->getMessage();
        }
    }
}

$daftarJenis = ['Sumur gali', 'Sumur bor', 'Mata air', 'Tandon air', 'Penampungan air lainnya'];
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0" style="color: #184e77;">
                <i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Sumber Air Baru
            </h4>
            <a href="<?= BASE_URL; ?>pages/sumber_air/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Perbaiki Kesalahan Berikut:</h6>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card-wc">
            <div class="card-wc-header">
                <h6 class="card-wc-title">
                    <i class="bi bi-info-circle text-primary"></i> Formulir Pendataan Sarana Air Bersih
                </h6>
            </div>
            <div class="card-wc-body">
                <form action="" method="POST" autocomplete="off">
                    <?= csrfField(); ?>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="nama_sumber" class="form-label">Nama Sumber Air <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_sumber" name="nama_sumber" placeholder="Contoh: Sumur Gali RW 05" value="<?= htmlspecialchars($namaSumber); ?>" required autofocus>
                            <div class="form-text">Beri nama pengenal yang spesifik di lapangan.</div>
                        </div>

                        <div class="col-md-5">
                            <label for="jenis_sumber" class="form-label">Jenis Sumber Air <span class="text-danger">*</span></label>
                            <select class="form-select" id="jenis_sumber" name="jenis_sumber" required>
                                <?php foreach ($daftarJenis as $j): ?>
                                    <option value="<?= htmlspecialchars($j); ?>" <?= ($jenisSumber === $j) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($j); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="pemilik" class="form-label">Nama Pemilik / Instansi Pengelola <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pemilik" name="pemilik" placeholder="Contoh: Puskesmas Melati / Bp. Haryanto" value="<?= htmlspecialchars($pemilik); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="volume" class="form-label">Estimasi Volume Air (Liter) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control font-monospace" id="volume" name="volume" placeholder="Contoh: 3000" value="<?= htmlspecialchars($volume); ?>" required>
                                <span class="input-group-text bg-light text-muted">Liter</span>
                            </div>
                            <div class="form-text">Gunakan volume tandon atau debit tampungan saat ini.</div>
                        </div>

                        <div class="col-12">
                            <label for="lokasi" class="form-label">Alamat Lengkap / Titik Lokasi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="lokasi" name="lokasi" rows="3" placeholder="Masukkan alamat jalan, RT/RW, kelurahan, atau koordinat lokasi sarana air" required><?= htmlspecialchars($lokasi); ?></textarea>
                        </div>

                        <div class="col-12">
                            <label for="keterangan" class="form-label">Keterangan Tambahan (Opsional)</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="2" placeholder="Kondisi fisik sarana, pelindung bibir sumur, jarak dari jamban/septic tank, dll"><?= htmlspecialchars($keterangan); ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= BASE_URL; ?>pages/sumber_air/index.php" class="btn btn-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="bi bi-save me-1"></i> Simpan Data Sumber Air
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
