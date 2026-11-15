<?php
/**
 * WaterCheck - Edit Data Sumber Air
 * File: pages/sumber_air/edit.php
 */

$pageTitle = 'Edit Sumber Air';
$activeMenu = 'sumber_air';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

$id = (int)($_GET['id'] ?? 0);

// Ambil data sumber air saat ini
$stmt = $pdo->prepare("SELECT * FROM sumber_air WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$sumber = $stmt->fetch();

if (!$sumber) {
    setFlashMessage('danger', 'Data sumber air tidak ditemukan atau telah dihapus.');
    redirect(BASE_URL . 'pages/sumber_air/index.php');
}

$errors = [];
$namaSumber  = $sumber['nama_sumber'];
$jenisSumber = $sumber['jenis_sumber'];
$pemilik     = $sumber['pemilik'];
$lokasi      = $sumber['lokasi'];
$volume      = $sumber['volume'];
$keterangan  = $sumber['keterangan'] ?? '';

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
        $errors[] = 'Token keamanan (CSRF) tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
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

    // 3. Simpan Perubahan ke Database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE sumber_air 
                SET nama_sumber = :nama_sumber,
                    jenis_sumber = :jenis_sumber,
                    pemilik = :pemilik,
                    lokasi = :lokasi,
                    volume = :volume,
                    keterangan = :keterangan,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':nama_sumber'  => $namaSumber,
                ':jenis_sumber' => $jenisSumber,
                ':pemilik'      => $pemilik,
                ':lokasi'       => $lokasi,
                ':volume'       => (float)$volumeInput,
                ':keterangan'   => !empty($keterangan) ? $keterangan : null,
                ':id'           => $id
            ]);

            catatLog($pdo, $currentUser['id'], "Memperbarui data sumber air: '{$namaSumber}' (ID: {$id})");

            setFlashMessage('success', "Perubahan data sumber air '{$namaSumber}' berhasil disimpan!");
            redirect(BASE_URL . 'pages/sumber_air/index.php');
        } catch (PDOException $e) {
            $errors[] = 'Gagal memperbarui database: ' . $e->getMessage();
        }
    }
}

$daftarJenis = ['Sumur gali', 'Sumur bor', 'Mata air', 'Tandon air', 'Penampungan air lainnya'];
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0" style="color: #184e77;">
                <i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Sumber Air
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
                    <i class="bi bi-info-circle text-primary"></i> Perbarui Formulir Sumber Air #<?= $id; ?>
                </h6>
            </div>
            <div class="card-wc-body">
                <form action="" method="POST" autocomplete="off">
                    <?= csrfField(); ?>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="nama_sumber" class="form-label">Nama Sumber Air <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_sumber" name="nama_sumber" value="<?= htmlspecialchars($namaSumber); ?>" required>
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
                            <input type="text" class="form-control" id="pemilik" name="pemilik" value="<?= htmlspecialchars($pemilik); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="volume" class="form-label">Estimasi Volume Air (Liter) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control font-monospace" id="volume" name="volume" value="<?= htmlspecialchars((string)$volume); ?>" required>
                                <span class="input-group-text bg-light text-muted">Liter</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="lokasi" class="form-label">Alamat Lengkap / Titik Lokasi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="lokasi" name="lokasi" rows="3" required><?= htmlspecialchars($lokasi); ?></textarea>
                        </div>

                        <div class="col-12">
                            <label for="keterangan" class="form-label">Keterangan Tambahan (Opsional)</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="2"><?= htmlspecialchars($keterangan); ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= BASE_URL; ?>pages/sumber_air/index.php" class="btn btn-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
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
