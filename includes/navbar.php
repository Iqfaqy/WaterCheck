<?php
/**
 * WaterCheck - Top Navbar Template
 * File: includes/navbar.php
 */

$flash = getFlashMessage();
?>
<header class="app-navbar">
    <div class="d-flex align-items-center gap-3">
        <!-- Mobile Sidebar Toggle -->
        <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>

        <!-- Page Heading / Breadcrumb -->
        <div class="d-none d-sm-block">
            <h5 class="mb-0 fw-bold text-dark" style="font-size: 1.1rem;"><?= htmlspecialchars($pageTitle ?? 'Dashboard'); ?></h5>
            <small class="text-muted"><?= htmlspecialchars(APP_TAGLINE); ?></small>
        </div>
    </div>

    <!-- Right Side: Clock & User Dropdown -->
    <div class="d-flex align-items-center gap-3">
        <!-- Live Clock -->
        <div class="navbar-clock d-none d-md-flex" id="liveClock">
            <i class="bi bi-clock me-1"></i> Memuat waktu...
        </div>

        <!-- User Dropdown -->
        <div class="dropdown">
            <div class="user-profile-badge" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['nama'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="d-none d-sm-block text-start">
                    <div class="fw-semibold text-dark" style="font-size: 0.88rem; line-height: 1.1;">
                        <?= htmlspecialchars($currentUser['nama'] ?? 'User'); ?>
                    </div>
                    <span class="badge <?= ($currentUser['role'] === 'admin') ? 'bg-primary' : 'bg-secondary'; ?>" style="font-size: 0.65rem;">
                        <?= ($currentUser['role'] === 'admin') ? 'ADMINISTRATOR' : 'SANITARIAN'; ?>
                    </span>
                </div>
                <i class="bi bi-chevron-down text-muted ms-1" style="font-size: 0.8rem;"></i>
            </div>
            
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius: 10px; min-width: 200px;">
                <li class="px-3 py-2 border-bottom">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($currentUser['nama'] ?? 'User'); ?></div>
                    <small class="text-muted">@<?= htmlspecialchars($currentUser['username'] ?? ''); ?></small>
                </li>
                <?php if (hasRole('admin')): ?>
                <li>
                    <a class="dropdown-item py-2" href="<?= BASE_URL; ?>pages/pengguna/index.php">
                        <i class="bi bi-people me-2 text-primary"></i> Kelola Pengguna
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a class="dropdown-item py-2" href="<?= BASE_URL; ?>pages/kalkulator/index.php">
                        <i class="bi bi-calculator me-2 text-primary"></i> Kalkulator Kaporit
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item py-2 text-danger" href="<?= BASE_URL; ?>logout.php" data-confirm="Apakah Anda yakin ingin keluar dari sistem?">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- Alert Flash Message -->
<div class="px-4 pt-3 pb-0">
    <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']); ?> alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 8px;">
        <div class="d-flex align-items-center gap-2">
            <?php if ($flash['type'] === 'success'): ?>
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
            <?php elseif ($flash['type'] === 'danger'): ?>
                <i class="bi bi-exclamation-octagon-fill fs-5 text-danger"></i>
            <?php elseif ($flash['type'] === 'warning'): ?>
                <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
            <?php else: ?>
                <i class="bi bi-info-circle-fill fs-5 text-info"></i>
            <?php endif; ?>
            <div><?= htmlspecialchars($flash['message']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
</div>

<div class="app-content">
