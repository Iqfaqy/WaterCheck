<?php
/**
 * WaterCheck - Data Sumber Air
 * File: pages/sumber_air/index.php
 */

$pageTitle = 'Data Sumber Air';
$activeMenu = 'sumber_air';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Parameter Pencarian dan Pagination
$search = trim($_GET['q'] ?? '');
$jenisFilter = trim($_GET['jenis'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Bangun Query SQL Dinamis
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(s.nama_sumber LIKE :search OR s.lokasi LIKE :search OR s.pemilik LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($jenisFilter)) {
    $whereClauses[] = "s.jenis_sumber = :jenis";
    $params[':jenis'] = $jenisFilter;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

try {
    // Hitung Total Data untuk Pagination
    $countSql = "SELECT COUNT(*) FROM sumber_air s {$whereSql}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalRows / $limit));

    // Ambil Data Sumber Air dengan Agregasi Riwayat Pemeriksaan
    $sql = "
        SELECT s.*, 
               COUNT(DISTINCT p.id) AS total_pemeriksaan,
               COUNT(DISTINCT k.id) AS total_kalkulasi
        FROM sumber_air s
        LEFT JOIN pemeriksaan p ON s.id = p.sumber_air_id
        LEFT JOIN kalkulasi_kaporit k ON s.id = k.sumber_air_id
        {$whereSql}
        GROUP BY s.id
        ORDER BY s.id DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $sumberAirList = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Galat database: " . htmlspecialchars($e->getMessage()) . "</div>";
    $sumberAirList = [];
    $totalRows = 0;
    $totalPages = 1;
}

// Daftar Jenis Sumber Air
$daftarJenis = ['Sumur gali', 'Sumur bor', 'Mata air', 'Tandon air', 'Penampungan air lainnya'];
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #184e77;">
            <i class="bi bi-water text-primary me-2"></i>Data Sumber Air
        </h4>
        <p class="text-muted small mb-0">Kelola informasi sarana air bersih, kapasitas penampungan, dan keterkaitannya dengan riwayat sanitasi.</p>
    </div>
    <div>
        <a href="<?= BASE_URL; ?>pages/sumber_air/tambah.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Sumber Air Baru
        </a>
    </div>
</div>

<!-- Filter dan Pencarian -->
<div class="card-wc mb-4">
    <div class="card-wc-body p-3">
        <form method="GET" action="<?= BASE_URL; ?>pages/sumber_air/index.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama sumber, lokasi, pemilik..." value="<?= htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="jenis" class="form-select">
                    <option value="">-- Semua Jenis Sumber Air --</option>
                    <?php foreach ($daftarJenis as $j): ?>
                        <option value="<?= htmlspecialchars($j); ?>" <?= ($jenisFilter === $j) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($j); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <?php if (!empty($search) || !empty($jenisFilter)): ?>
                    <a href="<?= BASE_URL; ?>pages/sumber_air/index.php" class="btn btn-outline-secondary" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Daftar Sumber Air -->
<div class="card-wc">
    <div class="card-wc-header d-flex justify-content-between align-items-center">
        <h6 class="card-wc-title">
            <i class="bi bi-list-columns-reverse text-primary"></i> Daftar Sarana Sumber Air (Total: <?= $totalRows; ?> Data)
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table-wc table-hover">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nama Sumber Air</th>
                    <th>Jenis</th>
                    <th>Pemilik / Instansi</th>
                    <th>Lokasi</th>
                    <th class="text-end">Volume Air</th>
                    <th class="text-center">Histori</th>
                    <th class="text-center" style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sumberAirList)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-droplet-slash fs-1 d-block text-secondary opacity-50 mb-2"></i>
                            Tidak ditemukan data sumber air yang sesuai dengan kriteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = $offset + 1;
                    foreach ($sumberAirList as $item): 
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td>
                                <a href="<?= BASE_URL; ?>pages/sumber_air/detail.php?id=<?= $item['id']; ?>" class="fw-bold text-dark text-decoration-none">
                                    <?= htmlspecialchars($item['nama_sumber']); ?>
                                </a>
                                <?php if (!empty($item['keterangan'])): ?>
                                    <div class="text-muted small text-truncate" style="max-width: 250px;">
                                        <?= htmlspecialchars($item['keterangan']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border">
                                    <?= htmlspecialchars($item['jenis_sumber']); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($item['pemilik']); ?></td>
                            <td>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars(mb_strimwidth($item['lokasi'], 0, 45, '...')); ?>
                                </small>
                            </td>
                            <td class="text-end font-monospace fw-semibold text-primary">
                                <?= formatAngka($item['volume'], 2); ?> L
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" title="Total Pemeriksaan">
                                    <i class="bi bi-clipboard2-check"></i> <?= $item['total_pemeriksaan']; ?>
                                </span>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="Total Kalkulasi Kaporit">
                                    <i class="bi bi-calculator"></i> <?= $item['total_kalkulasi']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL; ?>pages/sumber_air/detail.php?id=<?= $item['id']; ?>" class="btn btn-outline-info" title="Lihat Detail & Riwayat">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL; ?>pages/sumber_air/edit.php?id=<?= $item['id']; ?>" class="btn btn-outline-primary" title="Edit Data">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= BASE_URL; ?>pages/sumber_air/hapus.php?id=<?= $item['id']; ?>" class="btn btn-outline-danger" title="Hapus Data" data-confirm="Apakah Anda yakin ingin menghapus sumber air '<?= htmlspecialchars(addslashes($item['nama_sumber'])); ?>'? Riwayat pemeriksaan akan tetap dipertahankan.">
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
            <small class="text-muted">
                Menampilkan halaman <strong><?= $page; ?></strong> dari <strong><?= $totalPages; ?></strong> (Total <?= $totalRows; ?> data)
            </small>
            <nav aria-label="Navigasi Halaman">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?q=<?= urlencode($search); ?>&jenis=<?= urlencode($jenisFilter); ?>&page=<?= $page - 1; ?>">Sebelumnya</a>
                    </li>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= ($p === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?q=<?= urlencode($search); ?>&jenis=<?= urlencode($jenisFilter); ?>&page=<?= $p; ?>"><?= $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?q=<?= urlencode($search); ?>&jenis=<?= urlencode($jenisFilter); ?>&page=<?= $page + 1; ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
