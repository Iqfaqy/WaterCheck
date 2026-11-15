<?php
/**
 * WaterCheck - Detail Sumber Air & Histori Terkait
 * File: pages/sumber_air/detail.php
 */

$pageTitle = 'Detail Sumber Air';
$activeMenu = 'sumber_air';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

$id = (int)($_GET['id'] ?? 0);

// Ambil data sumber air
$stmt = $pdo->prepare("SELECT * FROM sumber_air WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$sumber = $stmt->fetch();

if (!$sumber) {
    setFlashMessage('danger', 'Data sumber air tidak ditemukan.');
    redirect(BASE_URL . 'pages/sumber_air/index.php');
}

// Ambil riwayat pemeriksaan terkait sumber air ini
$stmt = $pdo->prepare("
    SELECT p.*, u.nama AS nama_petugas
    FROM pemeriksaan p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.sumber_air_id = :id
    ORDER BY p.tanggal_pemeriksaan DESC, p.id DESC
");
$stmt->execute([':id' => $id]);
$riwayatPemeriksaan = $stmt->fetchAll();

// Ambil riwayat kalkulasi kaporit terkait sumber air ini
$stmt = $pdo->prepare("
    SELECT k.*, u.nama AS nama_petugas
    FROM kalkulasi_kaporit k
    LEFT JOIN users u ON k.user_id = u.id
    WHERE k.sumber_air_id = :id
    ORDER BY k.created_at DESC
");
$stmt->execute([':id' => $id]);
$riwayatKalkulasi = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>pages/sumber_air/index.php">Data Sumber Air</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($sumber['nama_sumber']); ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0" style="color: #184e77;">
            <i class="bi bi-droplet-half text-primary me-2"></i><?= htmlspecialchars($sumber['nama_sumber']); ?>
        </h4>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL; ?>pages/kalkulator/index.php?sumber_id=<?= $sumber['id']; ?>" class="btn btn-primary shadow-sm btn-sm">
            <i class="bi bi-calculator-fill me-1"></i> Hitung Kaporit
        </a>
        <a href="<?= BASE_URL; ?>pages/pemeriksaan/tambah.php?sumber_id=<?= $sumber['id']; ?>" class="btn btn-outline-primary shadow-sm btn-sm">
            <i class="bi bi-clipboard2-plus-fill me-1"></i> Input Pemeriksaan
        </a>
        <a href="<?= BASE_URL; ?>pages/sumber_air/edit.php?id=<?= $sumber['id']; ?>" class="btn btn-outline-secondary shadow-sm btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit Data
        </a>
        <a href="<?= BASE_URL; ?>pages/sumber_air/index.php" class="btn btn-secondary shadow-sm btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Kartu Informasi Detail -->
    <div class="col-lg-5">
        <div class="card-wc h-100">
            <div class="card-wc-header">
                <h6 class="card-wc-title">
                    <i class="bi bi-info-circle-fill text-primary"></i> Identitas Sarana Air
                </h6>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    <?= htmlspecialchars($sumber['jenis_sumber']); ?>
                </span>
            </div>
            <div class="card-wc-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted" style="width: 140px;">Nama Sarana:</td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($sumber['nama_sumber']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Jenis Sarana:</td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sumber['jenis_sumber']); ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Pemilik / Pengelola:</td>
                        <td class="fw-semibold"><?= htmlspecialchars($sumber['pemilik']); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kapasitas / Volume:</td>
                        <td>
                            <strong class="text-primary font-monospace fs-6"><?= formatAngka($sumber['volume'], 2); ?> Liter</strong>
                            <small class="text-muted">(± <?= formatAngka($sumber['volume'] / 1000.0, 2); ?> m³)</small>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Titik Lokasi:</td>
                        <td><?= nl2br(htmlspecialchars($sumber['lokasi'])); ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Keterangan:</td>
                        <td><?= !empty($sumber['keterangan']) ? nl2br(htmlspecialchars($sumber['keterangan'])) : '<span class="text-muted fst-italic">Tidak ada catatan</span>'; ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Terdaftar Pada:</td>
                        <td><?= formatTanggal($sumber['created_at'], 'datetime'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Ringkasan Statistik Sumber Ini -->
    <div class="col-lg-7">
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon secondary">
                        <i class="bi bi-clipboard2-check"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?= count($riwayatPemeriksaan); ?></div>
                        <div class="stat-label">Riwayat Pemeriksaan</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="bi bi-calculator"></i>
                    </div>
                    <div>
                        <div class="stat-value text-warning"><?= count($riwayatKalkulasi); ?></div>
                        <div class="stat-label">Riwayat Klorinasi</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-chlorine-safety">
            <h6 class="fw-bold mb-1"><i class="bi bi-shield-check me-1"></i> Rekomendasi Sanitarian:</h6>
            <p class="small mb-0">
                Lakukan desinfeksi kaporit dan pengurasan berkala jika sumber air menunjukkan tanda-tanda cemaran coliform atau perubahan kekeruhan di atas batas baku mutu <strong>Permenkes No. 2 Tahun 2023</strong>.
            </p>
        </div>
    </div>
</div>

<!-- Tabs Riwayat Terkait -->
<div class="card-wc">
    <div class="card-wc-header p-2">
        <ul class="nav nav-pills" id="detailTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-2 px-3 fw-semibold" id="pemeriksaan-tab" data-bs-toggle="tab" data-bs-target="#tab-pemeriksaan" type="button" role="tab">
                    <i class="bi bi-clipboard2-pulse me-1"></i> Riwayat Pemeriksaan Air (<?= count($riwayatPemeriksaan); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-2 px-3 fw-semibold" id="kalkulasi-tab" data-bs-toggle="tab" data-bs-target="#tab-kalkulasi" type="button" role="tab">
                    <i class="bi bi-calculator me-1"></i> Riwayat Dosis Kaporit (<?= count($riwayatKalkulasi); ?>)
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="detailTabsContent">
        <!-- Tab 1: Riwayat Pemeriksaan -->
        <div class="tab-pane fade show active" id="tab-pemeriksaan" role="tabpanel">
            <div class="table-responsive">
                <table class="table-wc table-hover mb-0">
                    <thead>
                        <tr>
                            <th>No. Pemeriksaan</th>
                            <th>Tanggal</th>
                            <th>Peruntukan</th>
                            <th>Sanitarian</th>
                            <th>Kesimpulan Akhir</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riwayatPemeriksaan)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block text-secondary opacity-50 mb-1"></i>
                                    Belum ada pemeriksaan kualitas air untuk sumber ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayatPemeriksaan as $p): ?>
                                <tr>
                                    <td><strong class="text-primary font-monospace"><?= htmlspecialchars($p['nomor_pemeriksaan']); ?></strong></td>
                                    <td><?= formatTanggal($p['tanggal_pemeriksaan'], 'lengkap'); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['jenis_air']); ?></span></td>
                                    <td><?= htmlspecialchars($p['nama_petugas'] ?? 'Petugas'); ?></td>
                                    <td>
                                        <?php if ($p['kesimpulan'] === 'Memenuhi Syarat (MS)'): ?>
                                            <span class="badge-status badge-ms"><i class="bi bi-check-circle-fill"></i> MS</span>
                                        <?php elseif ($p['kesimpulan'] === 'Tidak Memenuhi Syarat (TMS)'): ?>
                                            <span class="badge-status badge-tms"><i class="bi bi-x-circle-fill"></i> TMS</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL; ?>pages/pemeriksaan/detail.php?id=<?= $p['id']; ?>" class="btn btn-sm btn-outline-primary py-1 px-2">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                        <a href="<?= BASE_URL; ?>pages/laporan/cetak.php?tipe=pemeriksaan&id=<?= $p['id']; ?>" target="_blank" class="btn btn-sm btn-outline-danger py-1 px-2" title="Cetak PDF">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 2: Riwayat Kalkulasi Kaporit -->
        <div class="tab-pane fade" id="tab-kalkulasi" role="tabpanel">
            <div class="table-responsive">
                <table class="table-wc table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal Hitung</th>
                            <th>Desinfektan</th>
                            <th class="text-end">Volume Dihitung</th>
                            <th class="text-center">Kadar Aktif</th>
                            <th class="text-center">Target Dosis</th>
                            <th class="text-end">Kebutuhan Produk</th>
                            <th>Sanitarian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riwayatKalkulasi)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-calculator fs-3 d-block text-secondary opacity-50 mb-1"></i>
                                    Belum ada catatan perhitungan kaporit untuk sumber air ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayatKalkulasi as $k): ?>
                                <tr>
                                    <td><?= formatTanggal($k['created_at'], 'datetime'); ?></td>
                                    <td><?= htmlspecialchars($k['jenis_desinfektan']); ?></td>
                                    <td class="text-end font-monospace"><?= formatAngka($k['volume_air'], 2); ?> <?= htmlspecialchars($k['satuan_volume']); ?></td>
                                    <td class="text-center"><?= formatAngka($k['kadar_klorin_aktif'], 1); ?>%</td>
                                    <td class="text-center"><?= formatAngka($k['target_dosis'], 2); ?> mg/L</td>
                                    <td class="text-end">
                                        <span class="badge bg-primary fs-6 font-monospace">
                                            <?= formatAngka($k['kebutuhan_produk_gram'], 2); ?> gram
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($k['nama_petugas'] ?? 'Petugas'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
