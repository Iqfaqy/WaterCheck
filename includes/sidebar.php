<?php
/**
 * WaterCheck - Sidebar Navigasi Template
 * File: includes/sidebar.php
 */

$activeMenu = $activeMenu ?? 'dashboard';
$isAdmin = hasRole('admin');
?>
<aside class="app-sidebar">
    <!-- Brand / Logo Header -->
    <a href="<?= BASE_URL; ?>dashboard.php" class="sidebar-brand">
        <img src="<?= BASE_URL; ?>assets/img/logo.svg" alt="WaterCheck Logo">
        <div>
            <h1 class="brand-title"><?= htmlspecialchars(APP_NAME); ?></h1>
            <span class="brand-subtitle"><?= htmlspecialchars(APP_ALT_NAME); ?> • Sanitarian App</span>
        </div>
    </a>

    <!-- Navigasi Menu -->
    <div class="sidebar-menu">
        <div class="menu-category">Utama</div>
        
        <a href="<?= BASE_URL; ?>dashboard.php" class="nav-link-custom <?= ($activeMenu === 'dashboard') ? 'active' : ''; ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-category">Operasional Sanitarian</div>

        <a href="<?= BASE_URL; ?>pages/sumber_air/index.php" class="nav-link-custom <?= ($activeMenu === 'sumber_air') ? 'active' : ''; ?>">
            <i class="bi bi-water"></i>
            <span>Data Sumber Air</span>
        </a>

        <a href="<?= BASE_URL; ?>pages/kalkulator/index.php" class="nav-link-custom <?= ($activeMenu === 'kalkulator') ? 'active' : ''; ?>">
            <i class="bi bi-calculator-fill"></i>
            <span>Kalkulator Kaporit</span>
        </a>

        <a href="<?= BASE_URL; ?>pages/pemeriksaan/tambah.php" class="nav-link-custom <?= ($activeMenu === 'pemeriksaan_tambah') ? 'active' : ''; ?>">
            <i class="bi bi-clipboard2-pulse-fill"></i>
            <span>Pemeriksaan Baru</span>
        </a>

        <a href="<?= BASE_URL; ?>pages/pemeriksaan/index.php" class="nav-link-custom <?= ($activeMenu === 'pemeriksaan') ? 'active' : ''; ?>">
            <i class="bi bi-card-checklist"></i>
            <span>Riwayat Pemeriksaan</span>
        </a>

        <div class="menu-category">Dokumentasi & Laporan</div>

        <a href="<?= BASE_URL; ?>pages/laporan/index.php" class="nav-link-custom <?= ($activeMenu === 'laporan') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-pdf-fill"></i>
            <span>Laporan Hasil</span>
        </a>

        <div class="menu-category">Pengaturan Sistem</div>

        <a href="<?= BASE_URL; ?>pages/standar/index.php" class="nav-link-custom <?= ($activeMenu === 'standar') ? 'active' : ''; ?>">
            <i class="bi bi-sliders"></i>
            <span>Standar Baku Mutu</span>
        </a>

        <?php if ($isAdmin): ?>
        <a href="<?= BASE_URL; ?>pages/pengguna/index.php" class="nav-link-custom <?= ($activeMenu === 'pengguna') ? 'active' : ''; ?>">
            <i class="bi bi-people-fill"></i>
            <span>Manajemen Pengguna</span>
        </a>
        <?php endif; ?>

        <a href="<?= BASE_URL; ?>logout.php" class="nav-link-custom text-danger mt-3" data-confirm="Apakah Anda yakin ingin keluar dari sistem WaterCheck?">
            <i class="bi bi-box-arrow-right text-danger"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                <?= strtoupper(substr($currentUser['nama'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="overflow-hidden">
                <div class="text-truncate fw-semibold text-white" style="font-size: 0.85rem;">
                    <?= htmlspecialchars($currentUser['nama'] ?? 'User'); ?>
                </div>
                <div class="text-white-50" style="font-size: 0.72rem;">
                    <?= ($currentUser['role'] === 'admin') ? 'Administrator' : 'Petugas Lapangan'; ?>
                </div>
            </div>
        </div>
    </div>
</aside>
<main class="app-main">
