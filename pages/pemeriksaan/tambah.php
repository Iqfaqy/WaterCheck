<?php
/**
 * WaterCheck - Input Pemeriksaan Kualitas Air Baru
 * File: pages/pemeriksaan/tambah.php
 */

$pageTitle = 'Pemeriksaan Kualitas Air Baru';
$activeMenu = 'pemeriksaan_tambah';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Ambil sumber air dari database
try {
    $stmt = $pdo->query("SELECT id, nama_sumber, jenis_sumber, lokasi FROM sumber_air ORDER BY nama_sumber ASC");
    $listSumber = $stmt->fetchAll();
} catch (PDOException $e) {
    $listSumber = [];
}

// Cek preset dari query parameter
$selectedSumberId = (int)($_GET['sumber_id'] ?? 0);
$selectedJenisAir = $_GET['jenis_air'] ?? 'Air Minum';
if (!in_array($selectedJenisAir, ['Air Minum', 'Air Higiene Sanitasi'])) {
    $selectedJenisAir = 'Air Minum';
}

$nomorOtomatis = generateNomorPemeriksaan($pdo);

// Ambil semua standar baku mutu aktif yang terverifikasi untuk jenis air terpilih
try {
    $stmt = $pdo->prepare("
        SELECT * FROM standar_baku_mutu 
        WHERE jenis_air = :jenis_air AND aktif = 1 AND terverifikasi = 1 
        ORDER BY FIELD(kategori, 'Fisik', 'Kimia', 'Mikrobiologi'), wajib DESC, id ASC
    ");
    $stmt->execute([':jenis_air' => $selectedJenisAir]);
    $standarList = $stmt->fetchAll();
} catch (PDOException $e) {
    $standarList = [];
}

// Lokasi otomatis jika sumber dipilih
$defaultLokasi = '';
if ($selectedSumberId > 0) {
    foreach ($listSumber as $s) {
        if ($s['id'] == $selectedSumberId) {
            $defaultLokasi = $s['lokasi'];
            break;
        }
    }
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #184e77;">
            <i class="bi bi-clipboard2-pulse-fill text-primary me-2"></i>Pemeriksaan Kualitas Air Baru
        </h4>
        <p class="text-muted small mb-0">Input hasil uji parameter fisik, kimia, dan mikrobiologi berdasarkan acuan baku mutu Permenkes No. 2 Tahun 2023.</p>
    </div>
    <div>
        <a href="<?= BASE_URL; ?>pages/pemeriksaan/index.php" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Riwayat Pemeriksaan
        </a>
    </div>
</div>

<form action="<?= BASE_URL; ?>pages/pemeriksaan/proses.php" method="POST" id="formPemeriksaan" autocomplete="off">
    <?= csrfField(); ?>

    <!-- Bagian 1: Data Umum Pemeriksaan -->
    <div class="card-wc mb-4">
        <div class="card-wc-header">
            <h6 class="card-wc-title">
                <i class="bi bi-card-heading text-primary"></i> 1. Informasi Umum Pengambilan Sampel
            </h6>
            <span class="badge bg-primary font-monospace"><?= htmlspecialchars($nomorOtomatis); ?></span>
        </div>
        <div class="card-wc-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nomor_pemeriksaan" class="form-label">Nomor Pemeriksaan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace fw-bold bg-light" id="nomor_pemeriksaan" name="nomor_pemeriksaan" value="<?= htmlspecialchars($nomorOtomatis); ?>" readonly required>
                </div>

                <div class="col-md-4">
                    <label for="tanggal_pemeriksaan" class="form-label">Tanggal Pengujian <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="tanggal_pemeriksaan" name="tanggal_pemeriksaan" value="<?= date('Y-m-d'); ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="jenis_air" class="form-label">Jenis Peruntukan Air <span class="text-danger">*</span></label>
                    <select class="form-select fw-semibold text-primary" id="jenis_air" name="jenis_air" onchange="gantiJenisAir(this.value)" required>
                        <option value="Air Minum" <?= ($selectedJenisAir === 'Air Minum') ? 'selected' : ''; ?>>Air Minum</option>
                        <option value="Air Higiene Sanitasi" <?= ($selectedJenisAir === 'Air Higiene Sanitasi') ? 'selected' : ''; ?>>Air untuk Keperluan Higiene dan Sanitasi</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="sumber_air_id" class="form-label">Pilih Sarana Sumber Air</label>
                    <select class="form-select" id="sumber_air_id" name="sumber_air_id" onchange="updateLokasi(this)">
                        <option value="">-- Pengujian Mandiri / Sumber Air Tidak Terdaftar --</option>
                        <?php foreach ($listSumber as $s): ?>
                            <option value="<?= $s['id']; ?>" data-lokasi="<?= htmlspecialchars($s['lokasi']); ?>" <?= ($selectedSumberId == $s['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($s['nama_sumber']); ?> (<?= htmlspecialchars($s['jenis_sumber']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="lokasi" class="form-label">Titik Lokasi Pengambilan Sampel <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="lokasi" name="lokasi" placeholder="Alamat titik kran, koordinat, atau lokasi sumur" value="<?= htmlspecialchars($defaultLokasi); ?>" required>
                </div>

                <div class="col-12">
                    <label for="catatan" class="form-label">Catatan Lapangan Tambahan (Opsional)</label>
                    <textarea class="form-control" id="catatan" name="catatan" rows="2" placeholder="Kondisi cuaca saat pengambilan sampel, suhu udara sekitar, kondisi fisik wadah penampung, dsb."></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Bagian 2: Parameter Baku Mutu Permenkes No. 2/2023 -->
    <div class="card-wc mb-4">
        <div class="card-wc-header d-flex justify-content-between align-items-center">
            <h6 class="card-wc-title">
                <i class="bi bi-eyedropper text-primary"></i> 2. Parameter Kualitas Air (Baku Mutu: <?= htmlspecialchars($selectedJenisAir); ?>)
            </h6>
            <span class="badge bg-light text-dark border">
                <i class="bi bi-book me-1"></i> Permenkes No. 2 Tahun 2023
            </span>
        </div>
        <div class="card-wc-body">
            <div class="alert alert-info py-2 small mb-4">
                <i class="bi bi-info-circle-fill me-1"></i>
                <strong>Petunjuk Sanitarian:</strong> Parameter bertanda <span class="badge bg-danger py-1">Wajib</span> menentukan kesimpulan kelayakan air. Parameter kualitatif (Bau dan Rasa) gunakan pilihan yang sesuai kondisi organoleptik.
            </div>

            <?php
            // Kelompokkan parameter berdasarkan Kategori: Fisik, Kimia, Mikrobiologi
            $grouped = [];
            foreach ($standarList as $st) {
                $grouped[$st['kategori']][] = $st;
            }
            ?>

            <?php foreach (['Fisik', 'Kimia', 'Mikrobiologi'] as $kategori): ?>
                <?php if (!empty($grouped[$kategori])): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <?php if ($kategori === 'Fisik'): ?>
                                <i class="bi bi-thermometer-half text-primary fs-5"></i>
                            <?php elseif ($kategori === 'Kimia'): ?>
                                <i class="bi bi-radioactive text-warning fs-5"></i>
                            <?php else: ?>
                                <i class="bi bi-virus text-danger fs-5"></i>
                            <?php endif; ?>
                            <h6 class="fw-bold mb-0 text-primary-dark">Parameter <?= htmlspecialchars($kategori); ?></h6>
                            <span class="badge bg-secondary-subtle text-secondary small"><?= count($grouped[$kategori]); ?> Parameter</span>
                        </div>

                        <div class="row g-3">
                            <?php foreach ($grouped[$kategori] as $std): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="p-3 border rounded-3 bg-white h-100 shadow-sm">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($std['nama_parameter']); ?></span>
                                            <?php if ($std['wajib']): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">Wajib</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">Tambahan</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Informasi Standar Nilai -->
                                        <div class="text-muted small mb-2" style="font-size: 0.8rem;">
                                            Standar: 
                                            <strong class="text-primary">
                                                <?php
                                                if ($std['operator'] === 'MIN_MAX') {
                                                    echo formatAngka($std['nilai_min'], 1) . ' – ' . formatAngka($std['nilai_max'], 1) . ' ' . $std['satuan'];
                                                } elseif ($std['operator'] === 'MAX') {
                                                    echo '&le; ' . formatAngka($std['nilai_max'], 1) . ' ' . $std['satuan'];
                                                } elseif ($std['operator'] === 'EQUALS_ZERO') {
                                                    echo '0 ' . $std['satuan'];
                                                } elseif ($std['operator'] === 'QUALITATIVE') {
                                                    echo 'Tidak Berbau / Berasa';
                                                } elseif ($std['operator'] === 'SUHU') {
                                                    echo 'Suhu udara &plusmn; 3°C';
                                                }
                                                ?>
                                            </strong>
                                        </div>

                                        <input type="hidden" name="standar_id[]" value="<?= $std['id']; ?>">
                                        <input type="hidden" name="satuan[<?= $std['id']; ?>]" value="<?= htmlspecialchars($std['satuan']); ?>">

                                        <!-- Input Nilai Hasil -->
                                        <div class="mb-2">
                                            <?php if ($std['operator'] === 'QUALITATIVE'): ?>
                                                <label class="form-label small text-muted mb-1">Hasil Pengamatan:</label>
                                                <select class="form-select form-select-sm" name="nilai_kualitatif[<?= $std['id']; ?>]">
                                                    <option value="Tidak Berbau" <?= str_contains(strtolower($std['nama_parameter']), 'bau') ? 'selected' : ''; ?>>Tidak Berbau / Normal</option>
                                                    <option value="Berbau">Berbau Asing / Menyengat</option>
                                                    <option value="Tidak Berasa" <?= str_contains(strtolower($std['nama_parameter']), 'rasa') ? 'selected' : ''; ?>>Tidak Berasa / Tawar</option>
                                                    <option value="Berasa">Berasa Asin / Asam / Logam</option>
                                                    <option value="Belum Diuji">-- Belum Diuji --</option>
                                                </select>
                                            <?php else: ?>
                                                <label class="form-label small text-muted mb-1">Nilai Pengukuran (<?= htmlspecialchars($std['satuan']); ?>):</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="any" class="form-control font-monospace" name="nilai_hasil[<?= $std['id']; ?>]" placeholder="Masukkan hasil ukur">
                                                    <span class="input-group-text bg-light text-muted small"><?= htmlspecialchars($std['satuan']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Metode Uji & Keterangan -->
                                        <div class="row g-1">
                                            <div class="col-6">
                                                <input type="text" class="form-control form-control-sm" name="metode[<?= $std['id']; ?>]" placeholder="Metode/Alat" style="font-size: 0.75rem;">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" class="form-control form-control-sm" name="keterangan_param[<?= $std['id']; ?>]" placeholder="Keterangan" style="font-size: 0.75rem;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?= BASE_URL; ?>pages/pemeriksaan/index.php" class="btn btn-secondary">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                    <i class="bi bi-save2 me-1"></i> Simpan & Evaluasi Pemeriksaan
                </button>
            </div>
        </div>
    </div>
</form>

<script>
// Fungsi ubah jenis peruntukan air
function gantiJenisAir(jenis) {
    const sumberId = document.getElementById('sumber_air_id').value;
    const url = new URL(window.location.href);
    url.searchParams.set('jenis_air', jenis);
    if (sumberId) {
        url.searchParams.set('sumber_id', sumberId);
    }
    window.location.href = url.toString();
}

// Fungsi update otomatis lokasi saat memilih sumber air
function updateLokasi(selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const lokasi = selectedOption.getAttribute('data-lokasi');
    if (lokasi) {
        document.getElementById('lokasi').value = lokasi;
    }
}
</script>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
