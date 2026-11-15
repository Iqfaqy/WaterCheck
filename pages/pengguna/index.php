<?php
/**
 * WaterCheck - Manajemen Pengguna Sistem
 * File: pages/pengguna/index.php
 */

$pageTitle = 'Manajemen Pengguna';
$activeMenu = 'pengguna';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

checkAdmin();

try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, nama ASC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #184e77;">
            <i class="bi bi-people-fill text-primary me-2"></i>Manajemen Pengguna Sistem
        </h4>
        <p class="text-muted small mb-0">Kelola akun administrator dan sanitarian pelaksana lapangan.</p>
    </div>
    <div>
        <a href="<?= BASE_URL; ?>pages/pengguna/tambah.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna Baru
        </a>
    </div>
</div>

<div class="card-wc">
    <div class="card-wc-header d-flex justify-content-between align-items-center">
        <h6 class="card-wc-title">
            <i class="bi bi-person-lines-fill text-primary"></i> Daftar Akun Pengguna Terdaftar (Total: <?= count($users); ?>)
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table-wc table-hover">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Peran (Role)</th>
                    <th>Terdaftar Sejak</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($users as $u): 
                    $isSelf = ($u['id'] == $currentUser['id']);
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    <?= strtoupper(substr($u['nama'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong class="text-dark"><?= htmlspecialchars($u['nama']); ?></strong>
                                    <?php if ($isSelf): ?>
                                        <span class="badge bg-info-subtle text-info border ms-1" style="font-size: 0.65rem;">Akun Anda</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><code class="text-primary fs-6">@<?= htmlspecialchars($u['username']); ?></code></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="bi bi-shield-check me-1"></i> Administrator
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <i class="bi bi-person-badge me-1"></i> Sanitarian (Petugas)
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= formatTanggal($u['created_at'], 'pendek'); ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL; ?>pages/pengguna/edit.php?id=<?= $u['id']; ?>" class="btn btn-outline-primary" title="Edit Akun">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if (!$isSelf): ?>
                                    <a href="<?= BASE_URL; ?>pages/pengguna/hapus.php?id=<?= $u['id']; ?>" class="btn btn-outline-danger" title="Hapus Akun" data-confirm="Apakah Anda yakin ingin menghapus akun '<?= htmlspecialchars($u['username']); ?>'?">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary disabled" title="Tidak dapat menghapus akun sendiri">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
