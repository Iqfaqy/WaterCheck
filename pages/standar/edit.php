<?php
/**
 * WaterCheck - Edit Standar Baku Mutu
 * File: pages/standar/edit.php
 */

$pageTitle = 'Edit Standar Baku Mutu';
$activeMenu = 'standar';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

checkAdmin();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM standar_baku_mutu WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$standar = $stmt->fetch();

if (!$standar) {
    setFlashMessage('danger', 'Data standar baku mutu tidak ditemukan.');
    redirect(BASE_URL . 'pages/standar/index.php');
}

$errors = [];
$namaParameter  = $standar['nama_parameter'];
$jenisAir       = $standar['jenis_air'];
$kategori       = $standar['kategori'];
$nilaiMin       = $standar['nilai_min'];
$nilaiMax       = $standar['nilai_max'];
$satuan         = $standar['satuan'];
$operator       = $standar['operator'];
$wajib          = $standar['wajib'];
$keterangan     = $standar['keterangan'] ?? '';
$sumberRegulasi = $standar['sumber_regulasi'];
$tahunRegulasi  = $standar['tahun_regulasi'];
$terverifikasi  = $standar['terverifikasi'];
$aktif          = $standar['aktif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken      = $_POST['csrf_token'] ?? '';
    $namaParameter  = trim($_POST['nama_parameter'] ?? '');
    $jenisAir       = trim($_POST['jenis_air'] ?? 'Air Minum');
    $kategori       = trim($_POST['kategori'] ?? 'Kimia');
    $nilaiMinRaw    = trim($_POST['nilai_min'] ?? '');
    $nilaiMaxRaw    = trim($_POST['nilai_max'] ?? '');
    $satuan         = trim($_POST['satuan'] ?? '');
    $operator       = trim($_POST['operator'] ?? 'MAX');
    $wajib          = isset($_POST['wajib']) ? 1 : 0;
    $keterangan     = trim($_POST['keterangan'] ?? '');
    $sumberRegulasi = trim($_POST['sumber_regulasi'] ?? 'Permenkes No. 2 Tahun 2023');
    $tahunRegulasi  = trim($_POST['tahun_regulasi'] ?? '2023');
    $terverifikasi  = isset($_POST['terverifikasi']) ? 1 : 0;
    $aktif          = isset($_POST['aktif']) ? 1 : 0;

    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Token keamanan CSRF tidak valid.';
    } elseif (empty($namaParameter) || empty($satuan)) {
        $errors[] = 'Nama parameter dan satuan wajib diisi.';
    } else {
        $valMin = ($nilaiMinRaw !== '' && is_numeric($nilaiMinRaw)) ? (float)$nilaiMinRaw : null;
        $valMax = ($nilaiMaxRaw !== '' && is_numeric($nilaiMaxRaw)) ? (float)$nilaiMaxRaw : null;

        try {
            $stmt = $pdo->prepare("
                UPDATE standar_baku_mutu 
                SET nama_parameter = :nama,
                    jenis_air = :jenis,
                    kategori = :kategori,
                    nilai_min = :min,
                    nilai_max = :max,
                    satuan = :satuan,
                    operator = :operator,
                    wajib = :wajib,
                    keterangan = :keterangan,
                    sumber_regulasi = :sumber,
                    tahun_regulasi = :tahun,
                    terverifikasi = :terverifikasi,
                    aktif = :aktif,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':nama'          => $namaParameter,
                ':jenis'         => $jenisAir,
                ':kategori'      => $kategori,
                ':min'           => $valMin,
                ':max'           => $valMax,
                ':satuan'        => $satuan,
                ':operator'      => $operator,
                ':wajib'         => $wajib,
                ':keterangan'    => !empty($keterangan) ? $keterangan : null,
                ':sumber'        => $sumberRegulasi,
                ':tahun'         => $tahunRegulasi,
                ':terverifikasi' => $terverifikasi,
                ':aktif'         => $aktif,
                ':id'            => $id
            ]);

            catatLog($pdo, $currentUser['id'], "Memperbarui parameter baku mutu '{$namaParameter}' (ID: {$id})");

            setFlashMessage('success', "Parameter baku mutu '{$namaParameter}' berhasil diperbarui!");
            redirect(BASE_URL . "pages/standar/index.php?jenis_air=" . urlencode($jenisAir));

        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan perubahan: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0" style="color: #184e77;">
                <i class="bi bi-pencil-square text-primary me-2"></i>Edit Standar: <?= htmlspecialchars($namaParameter); ?>
            </h4>
            <a href="<?= BASE_URL; ?>pages/standar/index.php?jenis_air=<?= urlencode($jenisAir); ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Perbaiki Kesalahan:</h6>
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
                    <i class="bi bi-sliders text-primary"></i> Perbarui Parameter Baku Mutu #<?= $id; ?>
                </h6>
            </div>
            <div class="card-wc-body">
                <form action="" method="POST" autocomplete="off">
                    <?= csrfField(); ?>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="nama_parameter" class="form-label">Nama Parameter <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_parameter" name="nama_parameter" value="<?= htmlspecialchars($namaParameter); ?>" required>
                        </div>

                        <div class="col-md-5">
                            <label for="jenis_air" class="form-label">Jenis Peruntukan Air <span class="text-danger">*</span></label>
                            <select class="form-select" id="jenis_air" name="jenis_air" required>
                                <option value="Air Minum" <?= ($jenisAir === 'Air Minum') ? 'selected' : ''; ?>>Air Minum</option>
                                <option value="Air Higiene Sanitasi" <?= ($jenisAir === 'Air Higiene Sanitasi') ? 'selected' : ''; ?>>Air Higiene Sanitasi</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="kategori" class="form-label">Kategori Parameter <span class="text-danger">*</span></label>
                            <select class="form-select" id="kategori" name="kategori" required>
                                <option value="Fisik" <?= ($kategori === 'Fisik') ? 'selected' : ''; ?>>Fisik</option>
                                <option value="Kimia" <?= ($kategori === 'Kimia') ? 'selected' : ''; ?>>Kimia</option>
                                <option value="Mikrobiologi" <?= ($kategori === 'Mikrobiologi') ? 'selected' : ''; ?>>Mikrobiologi</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="operator" class="form-label">Operator Evaluasi <span class="text-danger">*</span></label>
                            <select class="form-select font-monospace" id="operator" name="operator" required>
                                <option value="MAX" <?= ($operator === 'MAX') ? 'selected' : ''; ?>>MAX (Maksimal)</option>
                                <option value="MIN_MAX" <?= ($operator === 'MIN_MAX') ? 'selected' : ''; ?>>MIN_MAX (Rentang Nilai)</option>
                                <option value="MIN" <?= ($operator === 'MIN') ? 'selected' : ''; ?>>MIN (Minimal)</option>
                                <option value="EQUALS_ZERO" <?= ($operator === 'EQUALS_ZERO') ? 'selected' : ''; ?>>EQUALS_ZERO (Wajib 0)</option>
                                <option value="QUALITATIVE" <?= ($operator === 'QUALITATIVE') ? 'selected' : ''; ?>>QUALITATIVE (Bau / Rasa)</option>
                                <option value="SUHU" <?= ($operator === 'SUHU') ? 'selected' : ''; ?>>SUHU (Deviasi Udara &plusmn;3°C)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="satuan" class="form-label">Satuan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="satuan" name="satuan" value="<?= htmlspecialchars($satuan); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="nilai_min" class="form-label">Nilai Minimum (Batas Bawah)</label>
                            <input type="number" step="any" class="form-control font-monospace" id="nilai_min" name="nilai_min" value="<?= ($nilaiMin !== null) ? (float)$nilaiMin : ''; ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="nilai_max" class="form-label">Nilai Maksimum (Batas Atas)</label>
                            <input type="number" step="any" class="form-control font-monospace" id="nilai_max" name="nilai_max" value="<?= ($nilaiMax !== null) ? (float)$nilaiMax : ''; ?>">
                        </div>

                        <div class="col-md-8">
                            <label for="sumber_regulasi" class="form-label">Sumber Regulasi Resmi</label>
                            <input type="text" class="form-control" id="sumber_regulasi" name="sumber_regulasi" value="<?= htmlspecialchars($sumberRegulasi); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label for="tahun_regulasi" class="form-label">Tahun Regulasi</label>
                            <input type="text" class="form-control" id="tahun_regulasi" name="tahun_regulasi" value="<?= htmlspecialchars($tahunRegulasi); ?>" required>
                        </div>

                        <div class="col-12">
                            <label for="keterangan" class="form-label">Keterangan / Catatan Regulasi</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="2"><?= htmlspecialchars($keterangan); ?></textarea>
                        </div>

                        <div class="col-12 mt-3">
                            <div class="d-flex flex-wrap gap-4 p-3 bg-light rounded-3 border">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="wajib" name="wajib" value="1" <?= ($wajib == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="wajib">Parameter Wajib</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="terverifikasi" name="terverifikasi" value="1" <?= ($terverifikasi == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="terverifikasi">Terverifikasi Sesuai Permenkes</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="aktif" name="aktif" value="1" <?= ($aktif == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="aktif">Status Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= BASE_URL; ?>pages/standar/index.php?jenis_air=<?= urlencode($jenisAir); ?>" class="btn btn-secondary">
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
