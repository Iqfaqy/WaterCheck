<?php
/**
 * WaterCheck - Riwayat Pemeriksaan Kualitas Air
 * File: pages/pemeriksaan/index.php
 */

$pageTitle = 'Riwayat Pemeriksaan Kualitas Air';
$activeMenu = 'pemeriksaan';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Filter & Pagination
$search      = trim($_GET['q'] ?? '');
$filterJenis = trim($_GET['jenis_air'] ?? '');
$filterStatus= trim($_GET['kesimpulan'] ?? '');
$dateStart   = trim($_GET['date_start'] ?? '');
$dateEnd     = trim($_GET['date_end'] ?? '');

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(p.nomor_pemeriksaan LIKE :search OR s.nama_sumber LIKE :search OR p.lokasi LIKE :search OR u.nama LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($filterJenis)) {
    $whereClauses[] = "p.jenis_air = :jenis_air";
    $params[':jenis_air'] = $filterJenis;
}

if (!empty($filterStatus)) {
    $whereClauses[] = "p.kesimpulan = :kesimpulan";
    $params[':kesimpulan'] = $filterStatus;
}

if (!empty($dateStart)) {
    $whereClauses[] = "p.tanggal_pemeriksaan >= :date_start";
    $params[':date_start'] = $dateStart;
}

if (!empty($dateEnd)) {
    $whereClauses[] = "p.tanggal_pemeriksaan <= :date_end";
    $params[':date_end'] = $dateEnd;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

try {
    // Hitung Total Data
    $countSql = "
        SELECT COUNT(*) 
        FROM pemeriksaan p
        LEFT JOIN sumber_air s ON p.sumber_air_id = s.id
        LEFT JOIN users u ON p.user_id = u.id
        {$whereSql}
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalRows / $limit));

    // Ambil Data List
    $sql = "
        SELECT p.*, s.nama_sumber, s.jenis_sumber, u.nama AS nama_petugas
        FROM pemeriksaan p
        LEFT JOIN sumber_air s ON p.sumber_air_id = s.id
        LEFT JOIN users u ON p.user_id = u.id
        {$whereSql}
        ORDER BY p.tanggal_pemeriksaan DESC, p.id DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $riwayatList = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Galat: " . htmlspecialchars($e->getMessage()) . "</div>";
    $riwayatList = [];
    $totalRows = 0;
    $totalPages = 1;
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #184e77;">
            <i class="bi bi-card-checklist text-primary me-2"></i>Riwayat Pemeriksaan Kualitas Air
        </h4>
        <p class="text-muted small mb-0">Arsip data hasil pengujian parameter air minum dan higiene sanitasi beserta evaluasi kesimpulannya.</p>
    </div>
    <div>
        <a href="<?= BASE_URL; ?>pages/pemeriksaan/tambah.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Input Pemeriksaan Baru
        </a>
    </div>
</div>

<!-- Filter dan Pencarian Multi-Kriteria -->
<div class="card-wc mb-4">
    <div class="card-wc-body p-3">
        <form method="GET" action="<?= BASE_URL; ?>pages/pemeriksaan/index.php" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Cari Kata Kunci:</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="No. Uji, sumber, lokasi, petugas..." value="<?= htmlspecialchars($search); ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label small mb-1">Peruntukan Air:</label>
                <select name="jenis_air" class="form-select form-select-sm">
                    <option value="">-- Semua Jenis --</option>
                    <option value="Air Minum" <?= ($filterJenis === 'Air Minum') ? 'selected' : ''; ?>>Air Minum</option>
                    <option value="Air Higiene Sanitasi" <?= ($filterJenis === 'Air Higiene Sanitasi') ? 'selected' : ''; ?>>Air Higiene Sanitasi</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small mb-1">Kesimpulan:</label>
                <select name="kesimpulan" class="form-select form-select-sm">
                    <option value="">-- Semua Kesimpulan --</option>
                    <option value="Memenuhi Syarat (MS)" <?= ($filterStatus === 'Memenuhi Syarat (MS)') ? 'selected' : ''; ?>>Memenuhi Syarat (MS)</option>
                    <option value="Tidak Memenuhi Syarat (TMS)" <?= ($filterStatus === 'Tidak Memenuhi Syarat (TMS)') ? 'selected' : ''; ?>>Tidak Memenuhi Syarat (TMS)</option>
                    <option value="Belum Dapat Disimpulkan" <?= ($filterStatus === 'Belum Dapat Disimpulkan') ? 'selected' : ''; ?>>Belum Dapat Disimpulkan</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1">Rentang Tanggal:</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="date_start" class="form-control" value="<?= htmlspecialchars($dateStart); ?>">
                    <span class="input-group-text bg-light text-muted">s/d</span>
                    <input type="date" name="date_end" class="form-control" value="<?= htmlspecialchars($dateEnd); ?>">
                </div>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel"></i> Terapkan
                </button>
                <?php if (!empty($search) || !empty($filterJenis) || !empty($filterStatus) || !empty($dateStart) || !empty($dateEnd)): ?>
                    <a href="<?= BASE_URL; ?>pages/pemeriksaan/index.php" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Daftar Pemeriksaan -->
<div class="card-wc">
    <div class="card-wc-header d-flex justify-content-between align-items-center">
        <h6 class="card-wc-title">
            <i class="bi bi-table text-primary"></i> Data Hasil Pengujian Lapangan (Total: <?= $totalRows; ?> Pemeriksaan)
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table-wc table-hover">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nomor Pemeriksaan</th>
                    <th>Tanggal</th>
                    <th>Sumber Air</th>
                    <th>Peruntukan</th>
                    <th>Petugas Sanitarian</th>
                    <th class="text-center">Kesimpulan</th>
                    <th class="text-center" style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayatList)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block text-secondary opacity-50 mb-2"></i>
                            Tidak ditemukan data riwayat pemeriksaan yang sesuai dengan kriteria filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = $offset + 1;
                    foreach ($riwayatList as $row): 
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td>
                                <a href="<?= BASE_URL; ?>pages/pemeriksaan/detail.php?id=<?= $row['id']; ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                    <?= htmlspecialchars($row['nomor_pemeriksaan']); ?>
                                </a>
                            </td>
                            <td><?= formatTanggal($row['tanggal_pemeriksaan'], 'pendek'); ?></td>
                            <td>
                                <?php if (!empty($row['nama_sumber'])): ?>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_sumber']); ?></div>
                                    <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars(mb_strimwidth($row['lokasi'], 0, 35, '...')); ?></small>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Pengujian Mandiri</span>
                                    <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($row['lokasi'], 0, 35, '...')); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($row['jenis_air']); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['nama_petugas'] ?? 'Petugas'); ?></td>
                            <td class="text-center">
                                <?php if ($row['kesimpulan'] === 'Memenuhi Syarat (MS)'): ?>
                                    <span class="badge-status badge-ms">
                                        <i class="bi bi-check-circle-fill"></i> MS
                                    </span>
                                <?php elseif ($row['kesimpulan'] === 'Tidak Memenuhi Syarat (TMS)'): ?>
                                    <span class="badge-status badge-tms">
                                        <i class="bi bi-x-circle-fill"></i> TMS
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status badge-pending">
                                        <i class="bi bi-hourglass-split"></i> Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL; ?>pages/pemeriksaan/detail.php?id=<?= $row['id']; ?>" class="btn btn-outline-info" title="Lihat Rincian Parameter">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL; ?>pages/laporan/cetak.php?tipe=pemeriksaan&id=<?= $row['id']; ?>" target="_blank" class="btn btn-outline-danger" title="Cetak Laporan PDF">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    <a href="<?= BASE_URL; ?>pages/pemeriksaan/edit.php?id=<?= $row['id']; ?>" class="btn btn-outline-primary" title="Edit Data">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= BASE_URL; ?>pages/pemeriksaan/hapus.php?id=<?= $row['id']; ?>" class="btn btn-outline-secondary" title="Hapus" data-confirm="Hapus data pemeriksaan <?= htmlspecialchars($row['nomor_pemeriksaan']); ?>?">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="card-wc-body border-top py-3 d-flex justify-content-between align-items-center">
            <small class="text-muted">Halaman <strong><?= $page; ?></strong> dari <strong><?= $totalPages; ?></strong> (Total <?= $totalRows; ?> data)</small>
            <nav aria-label="Navigasi Halaman">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?q=<?= urlencode($search); ?>&jenis_air=<?= urlencode($filterJenis); ?>&kesimpulan=<?= urlencode($filterStatus); ?>&date_start=<?= urlencode($dateStart); ?>&date_end=<?= urlencode($dateEnd); ?>&page=<?= $page - 1; ?>">Sebelumnya</a>
                    </li>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= ($p === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?q=<?= urlencode($search); ?>&jenis_air=<?= urlencode($filterJenis); ?>&kesimpulan=<?= urlencode($filterStatus); ?>&date_start=<?= urlencode($dateStart); ?>&date_end=<?= urlencode($dateEnd); ?>&page=<?= $p; ?>"><?= $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?q=<?= urlencode($search); ?>&jenis_air=<?= urlencode($filterJenis); ?>&kesimpulan=<?= urlencode($filterStatus); ?>&date_start=<?= urlencode($dateStart); ?>&date_end=<?= urlencode($dateEnd); ?>&page=<?= $page + 1; ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
