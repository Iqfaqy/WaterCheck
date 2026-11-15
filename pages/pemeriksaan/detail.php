<?php
/**
 * WaterCheck - Detail Hasil Pemeriksaan Kualitas Air
 * File: pages/pemeriksaan/detail.php
 */

$pageTitle = 'Detail Pemeriksaan Kualitas Air';
$activeMenu = 'pemeriksaan';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

$id = (int)($_GET['id'] ?? 0);

// Ambil data master pemeriksaan
$stmt = $pdo->prepare("
    SELECT p.*, s.nama_sumber, s.jenis_sumber, s.pemilik, u.nama AS nama_petugas, u.username
    FROM pemeriksaan p
    LEFT JOIN sumber_air s ON p.sumber_air_id = s.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$pemeriksaan = $stmt->fetch();

if (!$pemeriksaan) {
    setFlashMessage('danger', 'Data pemeriksaan tidak ditemukan.');
    redirect(BASE_URL . 'pages/pemeriksaan/index.php');
}

// Ambil rincian detail seluruh parameter
$stmtDetail = $pdo->prepare("
    SELECT dp.*, 
           sbm.nama_parameter, sbm.kategori, sbm.nilai_min, sbm.nilai_max, 
           sbm.operator, sbm.wajib, sbm.sumber_regulasi
    FROM detail_pemeriksaan dp
    LEFT JOIN standar_baku_mutu sbm ON dp.standar_id = sbm.id
    WHERE dp.pemeriksaan_id = :id
    ORDER BY FIELD(sbm.kategori, 'Fisik', 'Kimia', 'Mikrobiologi'), sbm.wajib DESC, sbm.id ASC
");
$stmtDetail->execute([':id' => $id]);
$details = $stmtDetail->fetchAll();
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>pages/pemeriksaan/index.php">Riwayat Pemeriksaan</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($pemeriksaan['nomor_pemeriksaan']); ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0" style="color: #184e77;">
            <i class="bi bi-file-earmark-medical-fill text-primary me-2"></i>Hasil Uji: <?= htmlspecialchars($pemeriksaan['nomor_pemeriksaan']); ?>
        </h4>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL; ?>pages/laporan/cetak.php?tipe=pemeriksaan&id=<?= $pemeriksaan['id']; ?>" target="_blank" class="btn btn-danger shadow-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Cetak Laporan PDF
        </a>
        <a href="<?= BASE_URL; ?>pages/pemeriksaan/edit.php?id=<?= $pemeriksaan['id']; ?>" class="btn btn-outline-primary shadow-sm">
            <i class="bi bi-pencil me-1"></i> Edit Pemeriksaan
        </a>
        <a href="<?= BASE_URL; ?>pages/pemeriksaan/hapus.php?id=<?= $pemeriksaan['id']; ?>" class="btn btn-outline-danger shadow-sm" data-confirm="Apakah Anda yakin ingin menghapus arsip pemeriksaan <?= htmlspecialchars($pemeriksaan['nomor_pemeriksaan']); ?>?">
            <i class="bi bi-trash me-1"></i> Hapus
        </a>
        <a href="<?= BASE_URL; ?>pages/pemeriksaan/index.php" class="btn btn-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<!-- Header Evaluasi & Ringkasan Status -->
<div class="row g-4 mb-4">
    <!-- Kolom 1: Kesimpulan Akhir -->
    <div class="col-lg-4">
        <div class="card-wc h-100 text-center p-4 d-flex flex-column justify-content-center align-items-center">
            <span class="text-muted text-uppercase fw-semibold mb-2" style="font-size: 0.8rem; letter-spacing: 1px;">Kesimpulan Evaluasi Baku Mutu</span>
            
            <?php if ($pemeriksaan['kesimpulan'] === 'Memenuhi Syarat (MS)'): ?>
                <div class="badge-status badge-ms py-2 px-3 fs-5 mb-2">
                    <i class="bi bi-check-circle-fill fs-4 me-1"></i> Memenuhi Syarat (MS)
                </div>
                <p class="text-success small mb-0">Air memenuhi seluruh parameter baku mutu wajib Permenkes No. 2 Tahun 2023.</p>
            <?php elseif ($pemeriksaan['kesimpulan'] === 'Tidak Memenuhi Syarat (TMS)'): ?>
                <div class="badge-status badge-tms py-2 px-3 fs-5 mb-2">
                    <i class="bi bi-x-circle-fill fs-4 me-1"></i> Tidak Memenuhi Syarat (TMS)
                </div>
                <p class="text-danger small mb-0">Terdapat parameter wajib yang melebihi atau tidak sesuai standar baku mutu.</p>
            <?php else: ?>
                <div class="badge-status badge-pending py-2 px-3 fs-5 mb-2">
                    <i class="bi bi-hourglass-split fs-4 me-1"></i> Belum Dapat Disimpulkan
                </div>
                <p class="text-warning-emphasis small mb-0">Data parameter wajib belum diuji secara lengkap.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kolom 2: Informasi Sampel & Petugas -->
    <div class="col-lg-8">
        <div class="card-wc h-100">
            <div class="card-wc-header">
                <h6 class="card-wc-title">
                    <i class="bi bi-info-circle text-primary"></i> Data Administratif Pengambilan Sampel
                </h6>
            </div>
            <div class="card-wc-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Nomor Pemeriksaan:</small>
                        <strong class="font-monospace text-primary fs-6"><?= htmlspecialchars($pemeriksaan['nomor_pemeriksaan']); ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Tanggal Pengujian:</small>
                        <strong><?= formatTanggal($pemeriksaan['tanggal_pemeriksaan'], 'lengkap'); ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Jenis Peruntukan Air:</small>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($pemeriksaan['jenis_air']); ?></span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Petugas Sanitarian:</small>
                        <strong><?= htmlspecialchars($pemeriksaan['nama_petugas'] ?? 'Petugas Lapangan'); ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Sarana Sumber Air:</small>
                        <?php if (!empty($pemeriksaan['nama_sumber'])): ?>
                            <a href="<?= BASE_URL; ?>pages/sumber_air/detail.php?id=<?= $pemeriksaan['sumber_air_id']; ?>" class="fw-bold">
                                <?= htmlspecialchars($pemeriksaan['nama_sumber']); ?>
                            </a> (<?= htmlspecialchars($pemeriksaan['jenis_sumber']); ?>)
                        <?php else: ?>
                            <span class="text-muted fst-italic">Pengujian Mandiri / Tidak Terkait Sumber</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Titik Lokasi Pengambilan:</small>
                        <span><?= htmlspecialchars($pemeriksaan['lokasi']); ?></span>
                    </div>
                </div>

                <?php if (!empty($pemeriksaan['catatan'])): ?>
                    <div class="mt-3 pt-2 border-top">
                        <small class="text-muted d-block">Catatan Sanitarian:</small>
                        <div class="p-2 bg-light rounded text-dark small mt-1"><?= nl2br(htmlspecialchars($pemeriksaan['catatan'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Rincian Seluruh Parameter -->
<div class="card-wc">
    <div class="card-wc-header d-flex justify-content-between align-items-center">
        <h6 class="card-wc-title">
            <i class="bi bi-table text-primary"></i> Rincian Pengukuran Parameter & Evaluasi Baku Mutu
        </h6>
        <span class="badge bg-light text-muted border">Total: <?= count($details); ?> Parameter</span>
    </div>
    <div class="table-responsive">
        <table class="table-wc table-hover">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Parameter Kualitas Air</th>
                    <th>Kategori</th>
                    <th>Kadar Standar Baku Mutu</th>
                    <th class="text-center">Hasil Pengukuran</th>
                    <th class="text-center">Status Evaluasi</th>
                    <th>Metode / Alat</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($details)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            Tidak ada parameter uji yang tercatat pada pemeriksaan ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach ($details as $d): 
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td>
                                <strong><?= htmlspecialchars($d['nama_parameter'] ?? 'Parameter'); ?></strong>
                                <?php if ($d['wajib']): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size: 0.65rem;">Wajib</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border">
                                    <?= htmlspecialchars($d['kategori'] ?? 'Fisik'); ?>
                                </span>
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
                            <td class="text-center font-monospace fw-bold">
                                <?php
                                if ($d['operator'] === 'QUALITATIVE') {
                                    echo htmlspecialchars($d['nilai_kualitatif'] ?? '-');
                                } else {
                                    echo ($d['nilai_hasil'] !== null) ? formatAngka($d['nilai_hasil'], 2) . ' ' . htmlspecialchars($d['satuan']) : '<span class="text-muted">-</span>';
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <?php if ($d['status'] === 'Memenuhi Syarat'): ?>
                                    <span class="badge-status badge-ms">
                                        <i class="bi bi-check-circle-fill"></i> MS
                                    </span>
                                <?php elseif ($d['status'] === 'Tidak Memenuhi Syarat'): ?>
                                    <span class="badge-status badge-tms">
                                        <i class="bi bi-x-circle-fill"></i> TMS
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status badge-pending">
                                        <i class="bi bi-dash-circle"></i> Belum Diuji
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($d['metode_pemeriksaan'] ?? '-'); ?></small></td>
                            <td><small class="text-muted"><?= htmlspecialchars($d['keterangan'] ?? '-'); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
