<?php
/**
 * WaterCheck - Edit Hasil Pemeriksaan Kualitas Air
 * File: pages/pemeriksaan/edit.php
 */

$pageTitle = 'Edit Pemeriksaan Kualitas Air';
$activeMenu = 'pemeriksaan';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

$id = (int)($_GET['id'] ?? 0);

// Ambil data pemeriksaan
$stmt = $pdo->prepare("
    SELECT p.*, s.nama_sumber
    FROM pemeriksaan p
    LEFT JOIN sumber_air s ON p.sumber_air_id = s.id
    WHERE p.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$pemeriksaan = $stmt->fetch();

if (!$pemeriksaan) {
    setFlashMessage('danger', 'Data pemeriksaan tidak ditemukan.');
    redirect(BASE_URL . 'pages/pemeriksaan/index.php');
}

// Ambil detail parameter saat ini
$stmtDetail = $pdo->prepare("
    SELECT dp.*, 
           sbm.nama_parameter, sbm.kategori, sbm.nilai_min, sbm.nilai_max, 
           sbm.operator, sbm.wajib
    FROM detail_pemeriksaan dp
    LEFT JOIN standar_baku_mutu sbm ON dp.standar_id = sbm.id
    WHERE dp.pemeriksaan_id = :id
    ORDER BY FIELD(sbm.kategori, 'Fisik', 'Kimia', 'Mikrobiologi'), sbm.wajib DESC, sbm.id ASC
");
$stmtDetail->execute([':id' => $id]);
$details = $stmtDetail->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken          = $_POST['csrf_token'] ?? '';
    $tanggalPemeriksaan = trim($_POST['tanggal_pemeriksaan'] ?? '');
    $lokasi             = trim($_POST['lokasi'] ?? '');
    $catatan            = trim($_POST['catatan'] ?? '');

    $detailIds          = $_POST['detail_id'] ?? [];
    $nilaiHasilList     = $_POST['nilai_hasil'] ?? [];
    $nilaiKualitatifList= $_POST['nilai_kualitatif'] ?? [];
    $metodeList         = $_POST['metode'] ?? [];
    $keteranganList     = $_POST['keterangan_param'] ?? [];

    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Token CSRF tidak valid atau telah kedaluwarsa.';
    } elseif (empty($tanggalPemeriksaan) || empty($lokasi)) {
        $errors[] = 'Tanggal pengujian dan lokasi wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();

            $evaluationSummary = [];

            $stmtUpdateDetail = $pdo->prepare("
                UPDATE detail_pemeriksaan
                SET nilai_hasil = :nilai_hasil,
                    nilai_kualitatif = :nilai_kualitatif,
                    status = :status,
                    metode_pemeriksaan = :metode,
                    keterangan = :keterangan
                WHERE id = :detail_id AND pemeriksaan_id = :pemeriksaan_id
            ");

            foreach ($details as $d) {
                $detailId = $d['id'];
                $operator = $d['operator'];
                $nilaiMin = $d['nilai_min'];
                $nilaiMax = $d['nilai_max'];
                $isWajib  = (int)$d['wajib'];

                $nilaiHasil = null;
                $nilaiKualitatif = null;

                if ($operator === 'QUALITATIVE') {
                    $nilaiKualitatif = $nilaiKualitatifList[$detailId] ?? null;
                    if ($nilaiKualitatif === 'Belum Diuji') {
                        $nilaiKualitatif = null;
                    }
                } else {
                    $raw = $nilaiHasilList[$detailId] ?? '';
                    if ($raw !== '' && is_numeric($raw)) {
                        $nilaiHasil = (float)$raw;
                    }
                }

                // Evaluasi status parameter
                $statusParam = evaluasiParameter($operator, $nilaiHasil, $nilaiMin, $nilaiMax, $nilaiKualitatif);

                $evaluationSummary[] = [
                    'status' => $statusParam,
                    'wajib'  => $isWajib
                ];

                $stmtUpdateDetail->execute([
                    ':nilai_hasil'     => $nilaiHasil,
                    ':nilai_kualitatif'=> $nilaiKualitatif,
                    ':status'          => $statusParam,
                    ':metode'          => !empty($metodeList[$detailId]) ? trim($metodeList[$detailId]) : null,
                    ':keterangan'      => !empty($keteranganList[$detailId]) ? trim($keteranganList[$detailId]) : null,
                    ':detail_id'       => $detailId,
                    ':pemeriksaan_id'  => $id
                ]);
            }

            // Tentukan kesimpulan baru
            $kesimpulanBaru = tentukanKesimpulanPemeriksaan($evaluationSummary);

            // Update data master pemeriksaan
            $stmtMaster = $pdo->prepare("
                UPDATE pemeriksaan 
                SET tanggal_pemeriksaan = :tanggal,
                    lokasi = :lokasi,
                    kesimpulan = :kesimpulan,
                    catatan = :catatan,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmtMaster->execute([
                ':tanggal'    => $tanggalPemeriksaan,
                ':lokasi'     => $lokasi,
                ':kesimpulan' => $kesimpulanBaru,
                ':catatan'    => !empty($catatan) ? $catatan : null,
                ':id'         => $id
            ]);

            $pdo->commit();

            catatLog($pdo, $currentUser['id'], "Memperbarui data pemeriksaan {$pemeriksaan['nomor_pemeriksaan']} (Kesimpulan: {$kesimpulanBaru})");

            setFlashMessage('success', "Pemeriksaan {$pemeriksaan['nomor_pemeriksaan']} berhasil diperbarui! Kesimpulan: {$kesimpulanBaru}");
            redirect(BASE_URL . "pages/pemeriksaan/detail.php?id={$id}");

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Gagal menyimpan perubahan: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="fw-bold mb-0" style="color: #184e77;">
                    <i class="bi bi-pencil-square text-primary me-2"></i>Edit Pemeriksaan: <?= htmlspecialchars($pemeriksaan['nomor_pemeriksaan']); ?>
                </h4>
                <small class="text-muted">Peruntukan: <?= htmlspecialchars($pemeriksaan['jenis_air']); ?></small>
            </div>
            <a href="<?= BASE_URL; ?>pages/pemeriksaan/detail.php?id=<?= $id; ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Batal & Kembali
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

        <form action="" method="POST" autocomplete="off">
            <?= csrfField(); ?>

            <!-- Data Umum -->
            <div class="card-wc mb-4">
                <div class="card-wc-header">
                    <h6 class="card-wc-title">
                        <i class="bi bi-card-heading text-primary"></i> Data Umum Pengujian
                    </h6>
                </div>
                <div class="card-wc-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nomor Pemeriksaan</label>
                            <input type="text" class="form-control font-monospace bg-light" value="<?= htmlspecialchars($pemeriksaan['nomor_pemeriksaan']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="tanggal_pemeriksaan" class="form-label">Tanggal Pengujian <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_pemeriksaan" name="tanggal_pemeriksaan" value="<?= htmlspecialchars($pemeriksaan['tanggal_pemeriksaan']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sumber Air</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($pemeriksaan['nama_sumber'] ?? 'Pengujian Mandiri'); ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label for="lokasi" class="form-label">Titik Lokasi Pengambilan Sampel <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lokasi" name="lokasi" value="<?= htmlspecialchars($pemeriksaan['lokasi']); ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="catatan" class="form-label">Catatan Tambahan</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="2"><?= htmlspecialchars($pemeriksaan['catatan'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Nilai Parameter -->
            <div class="card-wc mb-4">
                <div class="card-wc-header">
                    <h6 class="card-wc-title">
                        <i class="bi bi-table text-primary"></i> Perbarui Nilai Pengukuran Parameter
                    </h6>
                </div>
                <div class="card-wc-body">
                    <div class="table-responsive">
                        <table class="table-wc">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Standar Baku Mutu</th>
                                    <th style="width: 200px;">Nilai Hasil Uji</th>
                                    <th style="width: 160px;">Metode / Alat</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details as $d): ?>
                                    <input type="hidden" name="detail_id[]" value="<?= $d['id']; ?>">
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($d['nama_parameter']); ?></strong>
                                            <div class="small text-muted"><?= htmlspecialchars($d['kategori']); ?></div>
                                            <?php if ($d['wajib']): ?>
                                                <span class="badge bg-danger-subtle text-danger" style="font-size: 0.65rem;">Wajib</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php
                                                if ($d['operator'] === 'MIN_MAX') {
                                                    echo formatAngka($d['nilai_min'], 1) . ' – ' . formatAngka($d['nilai_max'], 1) . ' ' . $d['satuan'];
                                                } elseif ($d['operator'] === 'MAX') {
                                                    echo '&le; ' . formatAngka($d['nilai_max'], 1) . ' ' . $d['satuan'];
                                                } elseif ($d['operator'] === 'EQUALS_ZERO') {
                                                    echo '0 ' . $d['satuan'];
                                                } elseif ($d['operator'] === 'QUALITATIVE') {
                                                    echo 'Tidak Berbau / Berasa';
                                                } elseif ($d['operator'] === 'SUHU') {
                                                    echo 'Suhu udara &plusmn; 3°C';
                                                }
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($d['operator'] === 'QUALITATIVE'): ?>
                                                <select class="form-select form-select-sm" name="nilai_kualitatif[<?= $d['id']; ?>]">
                                                    <option value="Tidak Berbau" <?= ($d['nilai_kualitatif'] === 'Tidak Berbau') ? 'selected' : ''; ?>>Tidak Berbau / Normal</option>
                                                    <option value="Berbau" <?= ($d['nilai_kualitatif'] === 'Berbau') ? 'selected' : ''; ?>>Berbau Asing</option>
                                                    <option value="Tidak Berasa" <?= ($d['nilai_kualitatif'] === 'Tidak Berasa') ? 'selected' : ''; ?>>Tidak Berasa / Tawar</option>
                                                    <option value="Berasa" <?= ($d['nilai_kualitatif'] === 'Berasa') ? 'selected' : ''; ?>>Berasa Asin / Asam</option>
                                                    <option value="Belum Diuji" <?= empty($d['nilai_kualitatif']) ? 'selected' : ''; ?>>-- Belum Diuji --</option>
                                                </select>
                                            <?php else: ?>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="any" class="form-control font-monospace" name="nilai_hasil[<?= $d['id']; ?>]" value="<?= ($d['nilai_hasil'] !== null) ? (float)$d['nilai_hasil'] : ''; ?>" placeholder="Nilai">
                                                    <span class="input-group-text bg-light text-muted small"><?= htmlspecialchars($d['satuan']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="metode[<?= $d['id']; ?>]" value="<?= htmlspecialchars($d['metode_pemeriksaan'] ?? ''); ?>" placeholder="Alat">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="keterangan_param[<?= $d['id']; ?>]" value="<?= htmlspecialchars($d['keterangan'] ?? ''); ?>" placeholder="Catatan">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="<?= BASE_URL; ?>pages/pemeriksaan/detail.php?id=<?= $id; ?>" class="btn btn-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan & Evaluasi Ulang
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
