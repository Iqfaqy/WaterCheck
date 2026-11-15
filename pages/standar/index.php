<?php
/**
 * WaterCheck - Pengaturan Standar Baku Mutu
 * File: pages/standar/index.php
 */

$pageTitle = 'Pengaturan Standar Baku Mutu';
$activeMenu = 'standar';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Filter jenis peruntukan air
$filterJenis = trim($_GET['jenis_air'] ?? 'Air Minum');
if (!in_array($filterJenis, ['Air Minum', 'Air Higiene Sanitasi'])) {
    $filterJenis = 'Air Minum';
}

// Toggle status aktif jika diminta
if (isset($_GET['toggle_id']) && hasRole('admin')) {
    $toggleId = (int)$_GET['toggle_id'];
    $stmtToggle = $pdo->prepare("UPDATE standar_baku_mutu SET aktif = IF(aktif = 1, 0, 1) WHERE id = :id");
    $stmtToggle->execute([':id' => $toggleId]);
    catatLog($pdo, $currentUser['id'], "Mengubah status aktif parameter baku mutu ID {$toggleId}");
    setFlashMessage('success', 'Status keaktifan parameter baku mutu berhasil diperbarui.');
    redirect(BASE_URL . "pages/standar/index.php?jenis_air=" . urlencode($filterJenis));
}

try {
    $stmt = $pdo->prepare("
        SELECT * FROM standar_baku_mutu
        WHERE jenis_air = :jenis
        ORDER BY FIELD(kategori, 'Fisik', 'Kimia', 'Mikrobiologi'), wajib DESC, id ASC
    ");
    $stmt->execute([':jenis' => $filterJenis]);
    $standarList = $stmt->fetchAll();
} catch (PDOException $e) {
    $standarList = [];
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #184e77;">
            <i class="bi bi-sliders text-primary me-2"></i>Pengaturan Standar Baku Mutu
        </h4>
        <p class="text-muted small mb-0">Kelola ambang batas parameter kualitas air berdasarkan regulasi resmi Kemenkes RI.</p>
    </div>
    <?php if (hasRole('admin')): ?>
    <div>
        <a href="<?= BASE_URL; ?>pages/standar/tambah.php?jenis_air=<?= urlencode($filterJenis); ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Parameter Baku Mutu
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Tabs Pilihan Jenis Peruntukan Air -->
<div class="card-wc mb-4">
    <div class="card-wc-body p-2">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?= ($filterJenis === 'Air Minum') ? 'active' : ''; ?> fw-semibold py-2 px-3" href="?jenis_air=Air+Minum">
                    <i class="bi bi-cup-straw me-1"></i> Standar Air Minum (Permenkes 2/2023)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($filterJenis === 'Air Higiene Sanitasi') ? 'active' : ''; ?> fw-semibold py-2 px-3" href="?jenis_air=Air+Higiene+Sanitasi">
                    <i class="bi bi-droplet-half me-1"></i> Standar Air Higiene Sanitasi (Permenkes 2/2023)
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Tabel Parameter -->
<div class="card-wc">
    <div class="card-wc-header d-flex justify-content-between align-items-center">
        <h6 class="card-wc-title">
            <i class="bi bi-list-check text-primary"></i> Daftar Parameter: <?= htmlspecialchars($filterJenis); ?> (<?= count($standarList); ?> Item)
        </h6>
        <span class="badge bg-light text-dark border">Sumber: Permenkes No. 2 Tahun 2023</span>
    </div>
    <div class="table-responsive">
        <table class="table-wc table-hover">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Nama Parameter</th>
                    <th>Kategori</th>
                    <th>Batas Nilai Baku Mutu</th>
                    <th>Operator</th>
                    <th>Sifat</th>
                    <th>Status</th>
                    <?php if (hasRole('admin')): ?>
                    <th class="text-center" style="width: 120px;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($standarList)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada parameter untuk kategori ini.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach ($standarList as $row): 
                    ?>
                        <tr class="<?= ($row['aktif'] == 0) ? 'table-light text-muted' : ''; ?>">
                            <td><?= $no++; ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nama_parameter']); ?></strong>
                                <?php if (!empty($row['keterangan'])): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($row['keterangan']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border"><?= htmlspecialchars($row['kategori']); ?></span>
                            </td>
                            <td>
                                <strong class="text-primary font-monospace">
                                    <?php
                                    if ($row['operator'] === 'MIN_MAX') {
                                        echo formatAngka($row['nilai_min'], 1) . ' – ' . formatAngka($row['nilai_max'], 1) . ' ' . $row['satuan'];
                                    } elseif ($row['operator'] === 'MAX') {
                                        echo '&le; ' . formatAngka($row['nilai_max'], 1) . ' ' . $row['satuan'];
                                    } elseif ($row['operator'] === 'MIN') {
                                        echo '&ge; ' . formatAngka($row['nilai_min'], 1) . ' ' . $row['satuan'];
                                    } elseif ($row['operator'] === 'EQUALS_ZERO') {
                                        echo '0 ' . $row['satuan'];
                                    } elseif ($row['operator'] === 'QUALITATIVE') {
                                        echo 'Tidak Berbau / Berasa';
                                    } elseif ($row['operator'] === 'SUHU') {
                                        echo 'Suhu udara &plusmn; 3°C';
                                    }
                                    ?>
                                </strong>
                            </td>
                            <td><code><?= htmlspecialchars($row['operator']); ?></code></td>
                            <td>
                                <?= ($row['wajib'] == 1) ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Wajib</span>' : '<span class="badge bg-light text-muted border">Tambahan</span>'; ?>
                            </td>
                            <td>
                                <?php if ($row['aktif'] == 1): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <?php if (hasRole('admin')): ?>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL; ?>pages/standar/edit.php?id=<?= $row['id']; ?>" class="btn btn-outline-primary" title="Edit Parameter">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?jenis_air=<?= urlencode($filterJenis); ?>&toggle_id=<?= $row['id']; ?>" class="btn btn-outline-<?= ($row['aktif'] == 1) ? 'warning' : 'success'; ?>" title="<?= ($row['aktif'] == 1) ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                        <i class="bi bi-power"></i>
                                    </a>
                                </div>
                            </td>
                            <?php endif; ?>
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
