<?php
/**
 * WaterCheck - Pusat Laporan & Ekspor Dokumen Resmi
 * File: pages/laporan/index.php
 */

$pageTitle = 'Pusat Laporan & Cetak PDF';
$activeMenu = 'laporan';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Ambil 10 pemeriksaan terakhir
try {
    $stmt = $pdo->query("
        SELECT p.*, s.nama_sumber, u.nama AS nama_petugas
        FROM pemeriksaan p
        LEFT JOIN sumber_air s ON p.sumber_air_id = s.id
        LEFT JOIN users u ON p.user_id = u.id
        ORDER BY p.tanggal_pemeriksaan DESC, p.id DESC
        LIMIT 10
    ");
    $listPemeriksaan = $stmt->fetchAll();

    // Ambil 10 kalkulasi kaporit terakhir
    $stmtKalkulasi = $pdo->query("
        SELECT k.*, s.nama_sumber, u.nama AS nama_petugas
        FROM kalkulasi_kaporit k
        LEFT JOIN sumber_air s ON k.sumber_air_id = s.id
        LEFT JOIN users u ON k.user_id = u.id
        ORDER BY k.created_at DESC
        LIMIT 10
    ");
    $listKalkulasi = $stmtKalkulasi->fetchAll();

} catch (PDOException $e) {
    $listPemeriksaan = [];
    $listKalkulasi = [];
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #184e77;">
            <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>Pusat Laporan & Ekspor Dokumen
        </h4>
        <p class="text-muted small mb-0">Cetak lembar laporan resmi pemeriksaan kualitas air dan rekomendasi klorinasi berstandar A4.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom 1: Laporan Pemeriksaan Kualitas Air -->
    <div class="col-lg-6">
        <div class="card-wc h-100">
            <div class="card-wc-header d-flex justify-content-between align-items-center">
                <h6 class="card-wc-title">
                    <i class="bi bi-clipboard2-check-fill text-primary"></i> 1. Laporan Pemeriksaan Kualitas Air
                </h6>
                <a href="<?= BASE_URL; ?>pages/pemeriksaan/index.php" class="btn btn-sm btn-outline-primary">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-wc-body p-0">
                <div class="table-responsive">
                    <table class="table-wc table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No. Dokumen</th>
                                <th>Tanggal</th>
                                <th>Kesimpulan</th>
                                <th class="text-center">Cetak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listPemeriksaan)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada pemeriksaan air.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listPemeriksaan as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-primary font-monospace"><?= htmlspecialchars($p['nomor_pemeriksaan']); ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($p['nama_sumber'] ?? 'Pengujian Mandiri'); ?></small>
                                        </td>
                                        <td><?= formatTanggal($p['tanggal_pemeriksaan'], 'pendek'); ?></td>
                                        <td>
                                            <?php if ($p['kesimpulan'] === 'Memenuhi Syarat (MS)'): ?>
                                                <span class="badge-status badge-ms" style="font-size: 0.72rem;">MS</span>
                                            <?php elseif ($p['kesimpulan'] === 'Tidak Memenuhi Syarat (TMS)'): ?>
                                                <span class="badge-status badge-tms" style="font-size: 0.72rem;">TMS</span>
                                            <?php else: ?>
                                                <span class="badge-status badge-pending" style="font-size: 0.72rem;">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL; ?>pages/laporan/cetak.php?tipe=pemeriksaan&id=<?= $p['id']; ?>" target="_blank" class="btn btn-outline-danger" title="Cetak Format PDF">
                                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                                </a>
                                                <a href="<?= BASE_URL; ?>pages/laporan/cetak.php?tipe=pemeriksaan&id=<?= $p['id']; ?>&html=1" target="_blank" class="btn btn-outline-secondary" title="Pratinjau HTML / Cetak Browser">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom 2: Laporan Perhitungan Dosis Kaporit -->
    <div class="col-lg-6">
        <div class="card-wc h-100">
            <div class="card-wc-header d-flex justify-content-between align-items-center">
                <h6 class="card-wc-title">
                    <i class="bi bi-calculator-fill text-warning"></i> 2. Laporan Rekomendasi Klorinasi Air
                </h6>
                <a href="<?= BASE_URL; ?>pages/kalkulator/riwayat.php" class="btn btn-sm btn-outline-primary">
                    Lihat Semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-wc-body p-0">
                <div class="table-responsive">
                    <table class="table-wc table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Sumber Air</th>
                                <th>Dosis Produk</th>
                                <th class="text-center">Cetak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listKalkulasi)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada kalkulasi kaporit tersimpan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listKalkulasi as $k): ?>
                                    <tr>
                                        <td><?= formatTanggal($k['created_at'], 'pendek'); ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($k['nama_sumber'] ?? 'Perhitungan Mandiri'); ?></div>
                                            <small class="text-muted"><?= formatAngka($k['volume_air'], 0); ?> <?= htmlspecialchars($k['satuan_volume']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary font-monospace">
                                                <?= formatAngka($k['kebutuhan_produk_gram'], 2); ?> g
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL; ?>pages/laporan/cetak.php?tipe=kalkulasi&id=<?= $k['id']; ?>" target="_blank" class="btn btn-outline-danger" title="Cetak Format PDF">
                                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                                </a>
                                                <a href="<?= BASE_URL; ?>pages/laporan/cetak.php?tipe=kalkulasi&id=<?= $k['id']; ?>&html=1" target="_blank" class="btn btn-outline-secondary" title="Pratinjau HTML / Cetak Browser">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
