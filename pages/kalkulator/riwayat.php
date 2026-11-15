<?php
/**
 * WaterCheck - Riwayat Perhitungan Kaporit
 * File: pages/kalkulator/riwayat.php
 */

$pageTitle = 'Riwayat Perhitungan Kaporit';
$activeMenu = 'kalkulator';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Pagination & Pencarian
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(s.nama_sumber LIKE :search OR k.jenis_desinfektan LIKE :search OR u.nama LIKE :search OR k.catatan LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

try {
    // Total Data
    $countSql = "
        SELECT COUNT(*) 
        FROM kalkulasi_kaporit k
        LEFT JOIN sumber_air s ON k.sumber_air_id = s.id
        LEFT JOIN users u ON k.user_id = u.id
        {$whereSql}
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalRows / $limit));

    // Data List
    $sql = "
        SELECT k.*, s.nama_sumber, u.nama AS nama_petugas
        FROM kalkulasi_kaporit k
        LEFT JOIN sumber_air s ON k.sumber_air_id = s.id
        LEFT JOIN users u ON k.user_id = u.id
        {$whereSql}
        ORDER BY k.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $listRiwayat = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Galat: " . htmlspecialchars($e->getMessage()) . "</div>";
    $listRiwayat = [];
    $totalRows = 0;
    $totalPages = 1;
}

// Handler Hapus Riwayat Kalkulasi
if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
    $deleteId = (int)($_GET['id'] ?? 0);
    if ($deleteId > 0) {
        $delStmt = $pdo->prepare("DELETE FROM kalkulasi_kaporit WHERE id = :id");
        $delStmt->execute([':id' => $deleteId]);
        catatLog($pdo, $currentUser['id'], "Menghapus riwayat kalkulasi kaporit ID {$deleteId}");
        setFlashMessage('success', 'Riwayat kalkulasi kaporit berhasil dihapus.');
        redirect(BASE_URL . 'pages/kalkulator/riwayat.php');
    }
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #184e77;">
            <i class="bi bi-clock-history text-primary me-2"></i>Riwayat Perhitungan Dosis Kaporit
        </h4>
        <p class="text-muted small mb-0">Daftar arsip kalkulasi desinfeksi air yang telah dihitung dan disimpan oleh petugas sanitarian.</p>
    </div>
    <div>
        <a href="<?= BASE_URL; ?>pages/kalkulator/index.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-calculator me-1"></i> Buka Kalkulator Kaporit
        </a>
    </div>
</div>

<!-- Pencarian -->
<div class="card-wc mb-4">
    <div class="card-wc-body p-3">
        <form method="GET" action="<?= BASE_URL; ?>pages/kalkulator/riwayat.php" class="row g-2 align-items-center">
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama sumber air, bahan desinfektan, sanitarian, atau catatan..." value="<?= htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Cari Riwayat
                </button>
                <?php if (!empty($search)): ?>
                    <a href="<?= BASE_URL; ?>pages/kalkulator/riwayat.php" class="btn btn-outline-secondary" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Riwayat -->
<div class="card-wc">
    <div class="card-wc-header d-flex justify-content-between align-items-center">
        <h6 class="card-wc-title">
            <i class="bi bi-card-list text-primary"></i> Data Riwayat Kalkulasi (Total: <?= $totalRows; ?> Catatan)
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table-wc table-hover">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Tanggal Hitung</th>
                    <th>Sumber Air</th>
                    <th>Bahan Desinfektan</th>
                    <th class="text-end">Volume Air</th>
                    <th class="text-center">Kadar Aktif</th>
                    <th class="text-center">Target Dosis</th>
                    <th class="text-end">Kebutuhan Produk</th>
                    <th>Sanitarian</th>
                    <th class="text-center" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($listRiwayat)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard-x fs-1 d-block text-secondary opacity-50 mb-2"></i>
                            Belum ada riwayat perhitungan kaporit yang tersimpan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = $offset + 1;
                    foreach ($listRiwayat as $row): 
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= formatTanggal($row['created_at'], 'datetime'); ?></td>
                            <td>
                                <?php if (!empty($row['nama_sumber'])): ?>
                                    <strong class="text-dark"><?= htmlspecialchars($row['nama_sumber']); ?></strong>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Perhitungan Mandiri</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['jenis_desinfektan']); ?></td>
                            <td class="text-end font-monospace">
                                <?= formatAngka($row['volume_air'], 2); ?> <?= htmlspecialchars($row['satuan_volume']); ?>
                            </td>
                            <td class="text-center font-monospace"><?= formatAngka($row['kadar_klorin_aktif'], 1); ?>%</td>
                            <td class="text-center font-monospace"><?= formatAngka($row['target_dosis'], 2); ?> mg/L</td>
                            <td class="text-end">
                                <span class="badge bg-primary fs-6 font-monospace">
                                    <?= formatAngka($row['kebutuhan_produk_gram'], 2); ?> g
                                </span>
                            </td>
                            <td><small><?= htmlspecialchars($row['nama_petugas'] ?? 'Petugas'); ?></small></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL; ?>pages/laporan/cetak.php?tipe=kalkulasi&id=<?= $row['id']; ?>" target="_blank" class="btn btn-outline-danger" title="Cetak Lembar Kalkulasi (PDF)">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    <a href="?action=hapus&id=<?= $row['id']; ?>" class="btn btn-outline-secondary" title="Hapus Riwayat" data-confirm="Apakah Anda yakin ingin menghapus data riwayat perhitungan kaporit ini?">
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
            <small class="text-muted">Halaman <strong><?= $page; ?></strong> dari <strong><?= $totalPages; ?></strong></small>
            <nav aria-label="Navigasi Halaman">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?q=<?= urlencode($search); ?>&page=<?= $page - 1; ?>">Sebelumnya</a>
                    </li>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= ($p === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?q=<?= urlencode($search); ?>&page=<?= $p; ?>"><?= $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?q=<?= urlencode($search); ?>&page=<?= $page + 1; ?>">Berikutnya</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
