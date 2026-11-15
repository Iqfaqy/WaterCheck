<?php
/**
 * WaterCheck - Kalkulator Dosis Kaporit Desinfeksi Air
 * File: pages/kalkulator/index.php
 */

$pageTitle = 'Kalkulator Kaporit';
$activeMenu = 'kalkulator';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Ambil daftar sumber air dari database untuk opsi pilihan
try {
    $stmt = $pdo->query("SELECT id, nama_sumber, jenis_sumber, volume, lokasi FROM sumber_air ORDER BY nama_sumber ASC");
    $listSumber = $stmt->fetchAll();
} catch (PDOException $e) {
    $listSumber = [];
}

// Cek apakah ada parameter sumber_id dari query string
$selectedSumberId = (int)($_GET['sumber_id'] ?? 0);
$defaultVolume = '';
$defaultSatuan = 'liter';

if ($selectedSumberId > 0) {
    foreach ($listSumber as $s) {
        if ($s['id'] == $selectedSumberId) {
            $defaultVolume = $s['volume'];
            break;
        }
    }
}

// Inisialisasi variabel input & hasil
$hasilPerhitungan = null;
$calcError = null;
$isSubmitted = false;

$sumberAirIdInput = $selectedSumberId ?: null;
$jenisWadahInput = 'Tandon / Reservoir air';
$volumeInput = $defaultVolume;
$satuanVolumeInput = 'liter';
$jenisDesinfektanInput = 'Kalsium hipoklorit (kaporit bubuk)';
$kadarKlorinInput = '60';
$targetDosisInput = '2.0';
$catatanInput = '';

// Proses Perhitungan jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isSubmitted = true;
    $csrfToken             = $_POST['csrf_token'] ?? '';
    $sumberAirIdInput      = !empty($_POST['sumber_air_id']) ? (int)$_POST['sumber_air_id'] : null;
    $jenisWadahInput       = trim($_POST['jenis_wadah'] ?? 'Tandon / Reservoir air');
    $volumeInput           = trim($_POST['volume_air'] ?? '');
    $satuanVolumeInput     = trim($_POST['satuan_volume'] ?? 'liter');
    $jenisDesinfektanInput = trim($_POST['jenis_desinfektan'] ?? 'Kalsium hipoklorit (kaporit bubuk)');
    $kadarKlorinInput      = trim($_POST['kadar_klorin_aktif'] ?? '');
    $targetDosisInput      = trim($_POST['target_dosis'] ?? '');
    $catatanInput          = trim($_POST['catatan'] ?? '');

    // Verifikasi CSRF
    if (!verifyCsrfToken($csrfToken)) {
        $calcError = 'Token keamanan (CSRF) tidak valid atau telah kedaluwarsa.';
    } elseif (!is_numeric($volumeInput) || !is_numeric($kadarKlorinInput) || !is_numeric($targetDosisInput)) {
        $calcError = 'Semua nilai input volume, kadar klorin aktif, dan target dosis harus berupa angka numerik valid.';
    } else {
        $volNum   = (float)$volumeInput;
        $kadarNum = (float)$kadarKlorinInput;
        $dosisNum = (float)$targetDosisInput;

        // Panggil fungsi perhitungan standar di includes/functions.php
        $res = hitungKebutuhanKaporit($volNum, $satuanVolumeInput, $kadarNum, $dosisNum);

        if ($res['success']) {
            $hasilPerhitungan = $res['data'];
            // Tambahkan metadata tambahan ke hasil
            $hasilPerhitungan['jenis_desinfektan'] = $jenisDesinfektanInput;
            $hasilPerhitungan['jenis_wadah']       = $jenisWadahInput;
            $hasilPerhitungan['sumber_air_id']      = $sumberAirIdInput;
            $hasilPerhitungan['catatan']            = $catatanInput;
            $hasilPerhitungan['tanggal_hitung']     = date('Y-m-d H:i:s');
        } else {
            $calcError = $res['error'];
        }
    }
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #184e77;">
            <i class="bi bi-calculator-fill text-primary me-2"></i>Kalkulator Dosis Kaporit Desinfeksi
        </h4>
        <p class="text-muted small mb-0">Hitung estimasi kebutuhan bahan klorin aktif untuk penyehatan sarana air bersih secara presisi.</p>
    </div>
    <div>
        <a href="<?= BASE_URL; ?>pages/kalkulator/riwayat.php" class="btn btn-outline-primary shadow-sm">
            <i class="bi bi-clock-history me-1"></i> Lihat Riwayat Perhitungan
        </a>
    </div>
</div>

<!-- Safety Banner -->
<div class="alert alert-chlorine-safety mb-4 shadow-sm">
    <div class="d-flex align-items-start gap-3">
        <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
        <div>
            <h6 class="fw-bold mb-1 text-primary-dark">Pemberitahuan Keselamatan Teknis & Prosedural Sanitarian:</h6>
            <p class="small mb-1">
                <strong>Perhitungan ini merupakan estimasi teoritis.</strong> Pastikan dosis operasional ditentukan berdasarkan kondisi fisik air, kebutuhan klorin (*chlorine demand*), waktu kontak (*contact time* minimal 30 menit), serta pengujian sisa klor bebas (0,2 – 0,5 mg/L) di titik pemanfaatan.
            </p>
            <p class="small mb-0 text-muted">
                <em>*Jangan otomatis memberikan dosis kaporit tinggi tanpa pengujian residu klor, dan selalu gunakan Alat Pelindung Diri (APD: sarung tangan, masker) saat melarutkan kaporit pekat.</em>
            </p>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Formulir Input -->
    <div class="col-lg-6">
        <div class="card-wc h-100">
            <div class="card-wc-header">
                <h6 class="card-wc-title">
                    <i class="bi bi-sliders text-primary"></i> Parameter Masukan Klorinasi
                </h6>
            </div>
            <div class="card-wc-body">
                <?php if ($calcError): ?>
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <i class="bi bi-x-circle-fill me-1"></i> <?= htmlspecialchars($calcError); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL; ?>pages/kalkulator/index.php" method="POST" id="kalkulatorForm" autocomplete="off">
                    <?= csrfField(); ?>

                    <!-- Pilihan Sumber Air Terdaftar -->
                    <div class="mb-3">
                        <label for="sumber_air_id" class="form-label">Sumber Air Terdaftar (Opsional)</label>
                        <select class="form-select" id="sumber_air_id" name="sumber_air_id" onchange="autoFillVolume(this)">
                            <option value="">-- Hitung Mandiri (Tanpa Kaitkan Sumber Air) --</option>
                            <?php foreach ($listSumber as $s): ?>
                                <option value="<?= $s['id']; ?>" data-volume="<?= $s['volume']; ?>" <?= ($sumberAirIdInput == $s['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($s['nama_sumber']); ?> (<?= htmlspecialchars($s['jenis_sumber']); ?> - <?= formatAngka($s['volume'], 0); ?> L)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Memilih sumber air akan otomatis mengisi data volume tampungan.</div>
                    </div>

                    <!-- Jenis Wadah / Penampungan -->
                    <div class="mb-3">
                        <label for="jenis_wadah" class="form-label">Jenis Penampungan Air <span class="text-danger">*</span></label>
                        <select class="form-select" id="jenis_wadah" name="jenis_wadah" required>
                            <option value="Tandon / Reservoir air" <?= ($jenisWadahInput === 'Tandon / Reservoir air') ? 'selected' : ''; ?>>Tandon / Reservoir Tertutup</option>
                            <option value="Sumur gali" <?= ($jenisWadahInput === 'Sumur gali') ? 'selected' : ''; ?>>Sumur Gali</option>
                            <option value="Sumur bor" <?= ($jenisWadahInput === 'Sumur bor') ? 'selected' : ''; ?>>Sumur Bor</option>
                            <option value="Bak penampungan terbuka" <?= ($jenisWadahInput === 'Bak penampungan terbuka') ? 'selected' : ''; ?>>Bak Penampungan Terbuka</option>
                            <option value="Drum / Wadah lainnya" <?= ($jenisWadahInput === 'Drum / Wadah lainnya') ? 'selected' : ''; ?>>Drum / Wadah Lainnya</option>
                        </select>
                    </div>

                    <!-- Volume Air & Satuan -->
                    <div class="mb-3">
                        <label for="volume_air" class="form-label">Volume Air <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01" class="form-control font-monospace fs-6" id="volume_air" name="volume_air" placeholder="Contoh: 1000" value="<?= htmlspecialchars((string)$volumeInput); ?>" required>
                            <select class="form-select" name="satuan_volume" id="satuan_volume" style="max-width: 120px;">
                                <option value="liter" <?= ($satuanVolumeInput === 'liter') ? 'selected' : ''; ?>>Liter (L)</option>
                                <option value="m3" <?= ($satuanVolumeInput === 'm3') ? 'selected' : ''; ?>>Meter³ (m³)</option>
                            </select>
                        </div>
                        <div class="form-text">Contoh pengujian: Masukkan <strong>1.000 liter</strong> atau <strong>1 m³</strong>.</div>
                    </div>

                    <!-- Jenis Bahan Desinfektan -->
                    <div class="mb-3">
                        <label for="jenis_desinfektan" class="form-label">Jenis Bahan Desinfektan <span class="text-danger">*</span></label>
                        <select class="form-select" id="jenis_desinfektan" name="jenis_desinfektan" onchange="autoFillKadar(this)" required>
                            <option value="Kalsium hipoklorit (kaporit bubuk)" <?= ($jenisDesinfektanInput === 'Kalsium hipoklorit (kaporit bubuk)') ? 'selected' : ''; ?>>Kalsium Hipoklorit (Kaporit Bubuk)</option>
                            <option value="Kaporit granular" <?= ($jenisDesinfektanInput === 'Kaporit granular') ? 'selected' : ''; ?>>Kaporit Granular</option>
                            <option value="Kaporit tablet (TCCA 90%)" <?= ($jenisDesinfektanInput === 'Kaporit tablet (TCCA 90%)') ? 'selected' : ''; ?>>Kaporit Tablet (TCCA 90%)</option>
                            <option value="Natrium hipoklorit (klorin cair / pemutih)" <?= ($jenisDesinfektanInput === 'Natrium hipoklorit (klorin cair / pemutih)') ? 'selected' : ''; ?>>Natrium Hipoklorit (Cair / Pemutih 5.25%)</option>
                            <option value="Produk klorin lainnya" <?= ($jenisDesinfektanInput === 'Produk klorin lainnya') ? 'selected' : ''; ?>>Produk Klorin Lainnya (Kadar Custom)</option>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Kadar Klorin Aktif (%) -->
                        <div class="col-md-6">
                            <label for="kadar_klorin_aktif" class="form-label">Kadar Klorin Aktif (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" max="100" class="form-control font-monospace" id="kadar_klorin_aktif" name="kadar_klorin_aktif" placeholder="60" value="<?= htmlspecialchars((string)$kadarKlorinInput); ?>" required>
                                <span class="input-group-text bg-light text-muted">%</span>
                            </div>
                            <div class="form-text">Periksa label kemasan produk.</div>
                        </div>

                        <!-- Target Dosis (mg/L) -->
                        <div class="col-md-6">
                            <label for="target_dosis" class="form-label">Target Dosis Klorin <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" class="form-control font-monospace" id="target_dosis" name="target_dosis" placeholder="2.0" value="<?= htmlspecialchars((string)$targetDosisInput); ?>" required>
                                <span class="input-group-text bg-light text-muted">mg/L</span>
                            </div>
                            <div class="form-text">Dosis umum: 1.0 - 2.5 mg/L.</div>
                        </div>
                    </div>

                    <!-- Catatan Petugas -->
                    <div class="mb-4">
                        <label for="catatan" class="form-label">Catatan Lapangan / Keterangan</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="2" placeholder="Tujuan desinfeksi, kondisi air keruh/jernih, nomor batch kaporit"><?= htmlspecialchars($catatanInput); ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                            <i class="bi bi-calculator me-1"></i> Hitung Kebutuhan Kaporit
                        </button>
                        <a href="<?= BASE_URL; ?>pages/kalkulator/index.php" class="btn btn-outline-secondary btn-lg" title="Reset Form">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Hasil Perhitungan -->
    <div class="col-lg-6">
        <?php if ($hasilPerhitungan): ?>
            <div class="card-wc border-primary shadow mb-4" style="border-width: 2px;">
                <div class="card-wc-header bg-primary text-white">
                    <h6 class="card-wc-title text-white">
                        <i class="bi bi-check-circle-fill"></i> Hasil Perhitungan Kebutuhan Kaporit
                    </h6>
                    <span class="badge bg-light text-primary"><?= date('d/m/Y H:i'); ?> WIB</span>
                </div>
                <div class="card-wc-body p-4">
                    <!-- Angka Utama -->
                    <div class="text-center p-3 mb-4 rounded-3" style="background: linear-gradient(135deg, #e0f2fe 0%, #d8f3dc 100%); border: 1px solid #bae6fd;">
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.8rem; letter-spacing: 1px;">Kebutuhan Produk Kaporit</span>
                        <div class="display-5 fw-bold font-monospace text-primary my-1">
                            <?= formatAngka($hasilPerhitungan['kebutuhan_produk_gram'], 2); ?> <span class="fs-4">gram</span>
                        </div>
                        <div class="text-muted small">
                            Setara dengan <strong><?= formatAngka($hasilPerhitungan['kebutuhan_produk_mg'], 2); ?> mg</strong> bahan produk komersial
                        </div>
                    </div>

                    <!-- Rincian Parameter dan Langkah Perhitungan -->
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-receipt text-primary me-1"></i> Rincian Parameter:
                    </h6>
                    <table class="table table-sm table-borderless mb-4">
                        <tr>
                            <td class="text-muted" style="width: 50%;">Volume Air Efektif:</td>
                            <td class="fw-bold font-monospace text-end">
                                <?= formatAngka($hasilPerhitungan['volume_liter'], 2); ?> Liter
                                <?php if ($hasilPerhitungan['satuan_volume'] === 'm3'): ?>
                                    <small class="text-muted fw-normal">(<?= formatAngka($hasilPerhitungan['volume_input'], 2); ?> m³)</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jenis Bahan Desinfektan:</td>
                            <td class="fw-semibold text-end"><?= htmlspecialchars($hasilPerhitungan['jenis_desinfektan']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kadar Klorin Aktif:</td>
                            <td class="fw-bold font-monospace text-end"><?= formatAngka($hasilPerhitungan['kadar_klorin_aktif'], 2); ?> %</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Target Dosis Klorin:</td>
                            <td class="fw-bold font-monospace text-end"><?= formatAngka($hasilPerhitungan['target_dosis'], 2); ?> mg/L (ppm)</td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted pt-2">Kebutuhan Klorin Aktif Murni:</td>
                            <td class="fw-bold font-monospace text-end pt-2 text-secondary">
                                <?= formatAngka($hasilPerhitungan['kebutuhan_klorin_aktif'], 2); ?> mg
                            </td>
                        </tr>
                    </table>

                    <!-- Langkah Rumus Transparan -->
                    <div class="p-3 bg-light rounded-3 mb-4 border">
                        <div class="fw-semibold small text-primary-dark mb-1"><i class="bi bi-info-circle me-1"></i> Tahapan Rumus yang Digunakan:</div>
                        <ol class="small text-muted mb-0 ps-3" style="line-height: 1.6;">
                            <li><strong>Klorin Aktif (mg)</strong> = <?= formatAngka($hasilPerhitungan['volume_liter'], 0); ?> L &times; <?= formatAngka($hasilPerhitungan['target_dosis'], 2); ?> mg/L = <strong><?= formatAngka($hasilPerhitungan['kebutuhan_klorin_aktif'], 2); ?> mg</strong></li>
                            <li><strong>Kebutuhan Produk (mg)</strong> = <?= formatAngka($hasilPerhitungan['kebutuhan_klorin_aktif'], 2); ?> mg &divide; (<?= formatAngka($hasilPerhitungan['kadar_klorin_aktif'], 2); ?> / 100) = <strong><?= formatAngka($hasilPerhitungan['kebutuhan_produk_mg'], 2); ?> mg</strong></li>
                            <li><strong>Kebutuhan Produk (gram)</strong> = <?= formatAngka($hasilPerhitungan['kebutuhan_produk_mg'], 2); ?> mg &divide; 1.000 = <strong class="text-primary"><?= formatAngka($hasilPerhitungan['kebutuhan_produk_gram'], 2); ?> gram</strong></li>
                        </ol>
                    </div>

                    <!-- Tombol Aksi Simpan, Reset & Cetak -->
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <!-- Form Simpan Perhitungan -->
                            <form action="<?= BASE_URL; ?>pages/kalkulator/proses.php" method="POST">
                                <?= csrfField(); ?>
                                <input type="hidden" name="sumber_air_id" value="<?= $hasilPerhitungan['sumber_air_id'] ?? ''; ?>">
                                <input type="hidden" name="jenis_desinfektan" value="<?= htmlspecialchars($hasilPerhitungan['jenis_desinfektan']); ?>">
                                <input type="hidden" name="volume_air" value="<?= $hasilPerhitungan['volume_input']; ?>">
                                <input type="hidden" name="satuan_volume" value="<?= $hasilPerhitungan['satuan_volume']; ?>">
                                <input type="hidden" name="kadar_klorin_aktif" value="<?= $hasilPerhitungan['kadar_klorin_aktif']; ?>">
                                <input type="hidden" name="target_dosis" value="<?= $hasilPerhitungan['target_dosis']; ?>">
                                <input type="hidden" name="kebutuhan_klorin_aktif" value="<?= $hasilPerhitungan['kebutuhan_klorin_aktif']; ?>">
                                <input type="hidden" name="kebutuhan_produk_mg" value="<?= $hasilPerhitungan['kebutuhan_produk_mg']; ?>">
                                <input type="hidden" name="kebutuhan_produk_gram" value="<?= $hasilPerhitungan['kebutuhan_produk_gram']; ?>">
                                <input type="hidden" name="catatan" value="<?= htmlspecialchars($hasilPerhitungan['catatan']); ?>">

                                <button type="submit" class="btn btn-success w-100 shadow-sm">
                                    <i class="bi bi-save me-1"></i> Simpan Riwayat
                                </button>
                            </form>
                        </div>
                        <div class="col-sm-6">
                            <a href="<?= BASE_URL; ?>pages/kalkulator/index.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-repeat me-1"></i> Hitung Ulang
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Placeholder Panduan jika belum ada perhitungan -->
            <div class="card-wc h-100">
                <div class="card-wc-header">
                    <h6 class="card-wc-title">
                        <i class="bi bi-journal-medical text-primary"></i> Panduan Desinfeksi Kaporit di Lapangan
                    </h6>
                </div>
                <div class="card-wc-body">
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-calculator fs-1 text-primary opacity-50 d-block mb-3"></i>
                        <h6 class="fw-bold text-dark">Hasil Perhitungan Akan Tampil di Sini</h6>
                        <p class="small text-muted px-4">
                            Isi formulir di sebelah kiri dan klik tombol <strong>"Hitung Kebutuhan Kaporit"</strong> untuk menampilkan rincian matematis dan dosis gram produk.
                        </p>
                    </div>

                    <div class="border-top pt-3 mt-2">
                        <h6 class="fw-bold text-primary-dark mb-2" style="font-size: 0.9rem;">Tabel Kadar Bahan Klorin Komersial Umum:</h6>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Kaporit Bubuk (Kalsium Hipoklorit)
                                <span class="badge bg-primary rounded-pill">60% – 65%</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Kaporit Granular
                                <span class="badge bg-primary rounded-pill">70%</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Tablet TCCA (Trichloroisocyanuric)
                                <span class="badge bg-primary rounded-pill">90%</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                Pemutih Rumah Tangga (NaOCl Cair)
                                <span class="badge bg-secondary rounded-pill">5.25%</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto fill volume saat memilih sumber air terdaftar
function autoFillVolume(selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const volume = selectedOption.getAttribute('data-volume');
    if (volume && volume > 0) {
        document.getElementById('volume_air').value = volume;
        document.getElementById('satuan_volume').value = 'liter';
    }
}

// Auto fill estimasi kadar klorin saat memilih bahan desinfektan
function autoFillKadar(selectElem) {
    const val = selectElem.value;
    const kadarInput = document.getElementById('kadar_klorin_aktif');
    if (val.includes('Kaporit granular')) {
        kadarInput.value = '70';
    } else if (val.includes('Kalsium hipoklorit')) {
        kadarInput.value = '60';
    } else if (val.includes('TCCA')) {
        kadarInput.value = '90';
    } else if (val.includes('Natrium hipoklorit')) {
        kadarInput.value = '5.25';
    }
}
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
