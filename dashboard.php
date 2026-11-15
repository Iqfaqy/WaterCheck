<?php
/**
 * WaterCheck - Dashboard Utama
 * File: dashboard.php
 */

$pageTitle = 'Dashboard Analitik';
$activeMenu = 'dashboard';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/navbar.php';

// 1. Ambil Data Statistik Real-Time dari Database
try {
    // Total sumber air
    $stmt = $pdo->query("SELECT COUNT(*) FROM sumber_air");
    $totalSumberAir = (int)$stmt->fetchColumn();

    // Total pemeriksaan
    $stmt = $pdo->query("SELECT COUNT(*) FROM pemeriksaan");
    $totalPemeriksaan = (int)$stmt->fetchColumn();

    // Total Memenuhi Syarat (MS)
    $stmt = $pdo->query("SELECT COUNT(*) FROM pemeriksaan WHERE kesimpulan = 'Memenuhi Syarat (MS)'");
    $totalMS = (int)$stmt->fetchColumn();

    // Total Tidak Memenuhi Syarat (TMS)
    $stmt = $pdo->query("SELECT COUNT(*) FROM pemeriksaan WHERE kesimpulan = 'Tidak Memenuhi Syarat (TMS)'");
    $totalTMS = (int)$stmt->fetchColumn();

    // Total Belum Dapat Disimpulkan
    $stmt = $pdo->query("SELECT COUNT(*) FROM pemeriksaan WHERE kesimpulan = 'Belum Dapat Disimpulkan'");
    $totalPending = (int)$stmt->fetchColumn();

    // Total perhitungan kaporit
    $stmt = $pdo->query("SELECT COUNT(*) FROM kalkulasi_kaporit");
    $totalKalkulasi = (int)$stmt->fetchColumn();

    // 5 Pemeriksaan Terkini
    $stmt = $pdo->query("
        SELECT p.*, s.nama_sumber, u.nama AS nama_petugas
        FROM pemeriksaan p
        LEFT JOIN sumber_air s ON p.sumber_air_id = s.id
        LEFT JOIN users u ON p.user_id = u.id
        ORDER BY p.tanggal_pemeriksaan DESC, p.id DESC
        LIMIT 5
    ");
    $recentPemeriksaan = $stmt->fetchAll();

    // Data Grafik Bulanan (Tahun Berjalan)
    $currentYear = date('Y');
    $stmt = $pdo->prepare("
        SELECT MONTH(tanggal_pemeriksaan) AS bulan, COUNT(*) AS jumlah
        FROM pemeriksaan
        WHERE YEAR(tanggal_pemeriksaan) = :tahun
        GROUP BY MONTH(tanggal_pemeriksaan)
    ");
    $stmt->execute([':tahun' => $currentYear]);
    $monthlyDataRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $monthlyCounts = [];
    for ($m = 1; $m <= 12; $m++) {
        $monthlyCounts[] = $monthlyDataRaw[$m] ?? 0;
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Terjadi galat pengambilan data statistik: " . htmlspecialchars($e->getMessage()) . "</div>";
    $totalSumberAir = $totalPemeriksaan = $totalMS = $totalTMS = $totalPending = $totalKalkulasi = 0;
    $recentPemeriksaan = [];
    $monthlyCounts = array_fill(0, 12, 0);
}
?>

<!-- Welcome Banner -->
<div class="card-wc mb-4 p-4" style="background: linear-gradient(120deg, #184e77 0%, #168aad 100%); color: #fff;">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <span class="badge bg-light text-primary mb-2 px-3 py-1 fw-bold">SISTEM KESEHATAN LINGKUNGAN</span>
            <h2 class="fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($currentUser['nama']); ?>!</h2>
            <p class="mb-3 opacity-90" style="font-size: 0.95rem;">
                Kelola pendataan sumber air, periksa parameter mikrobiologi, kimia, fisik, serta lakukan kalkulasi klorinasi desinfeksi secara presisi.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL; ?>pages/kalkulator/index.php" class="btn btn-light text-primary fw-semibold shadow-sm">
                    <i class="bi bi-calculator-fill me-1"></i> Hitung Kaporit
                </a>
                <a href="<?= BASE_URL; ?>pages/pemeriksaan/tambah.php" class="btn btn-outline-light fw-semibold shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Input Pemeriksaan Baru
                </a>
                <a href="<?= BASE_URL; ?>pages/sumber_air/tambah.php" class="btn btn-outline-light fw-semibold shadow-sm">
                    <i class="bi bi-droplet-half me-1"></i> Tambah Sumber Air
                </a>
            </div>
        </div>
        <div class="col-lg-4 text-center text-lg-end mt-3 mt-lg-0 d-none d-lg-block">
            <img src="<?= BASE_URL; ?>assets/img/logo.svg" alt="App Logo" style="width: 110px; height: 110px; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.3));">
        </div>
    </div>
</div>

<!-- 5 Stat Cards -->
<div class="row g-3 mb-4">
    <!-- 1. Sumber Air -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-water"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($totalSumberAir, 0, ',', '.'); ?></div>
                <div class="stat-label">Sumber Air Terdaftar</div>
            </div>
        </div>
    </div>

    <!-- 2. Total Pemeriksaan -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card">
            <div class="stat-icon secondary">
                <i class="bi bi-clipboard2-check"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($totalPemeriksaan, 0, ',', '.'); ?></div>
                <div class="stat-label">Total Pemeriksaan</div>
            </div>
        </div>
    </div>

    <!-- 3. Memenuhi Syarat (MS) -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div>
                <div class="stat-value text-success"><?= number_format($totalMS, 0, ',', '.'); ?></div>
                <div class="stat-label">Memenuhi Syarat (MS)</div>
            </div>
        </div>
    </div>

    <!-- 4. Tidak Memenuhi Syarat (TMS) -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="bi bi-x-circle"></i>
            </div>
            <div>
                <div class="stat-value text-danger"><?= number_format($totalTMS, 0, ',', '.'); ?></div>
                <div class="stat-label">Tidak Memenuhi (TMS)</div>
            </div>
        </div>
    </div>

    <!-- 5. Kalkulasi Kaporit -->
    <div class="col-sm-6 col-xl">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-calculator"></i>
            </div>
            <div>
                <div class="stat-value text-warning"><?= number_format($totalKalkulasi, 0, ',', '.'); ?></div>
                <div class="stat-label">Kalkulasi Kaporit</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Bar/Line Chart: Tren Pemeriksaan Bulanan -->
    <div class="col-lg-8">
        <div class="card-wc h-100 mb-0">
            <div class="card-wc-header">
                <h6 class="card-wc-title">
                    <i class="bi bi-bar-chart-line-fill text-primary"></i> Tren Pemeriksaan Kualitas Air (Tahun <?= date('Y'); ?>)
                </h6>
                <span class="badge bg-light text-muted border">Chart.js</span>
            </div>
            <div class="card-wc-body">
                <canvas id="monthlyChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <!-- Doughnut Chart: Komposisi Hasil Evaluasi -->
    <div class="col-lg-4">
        <div class="card-wc h-100 mb-0">
            <div class="card-wc-header">
                <h6 class="card-wc-title">
                    <i class="bi bi-pie-chart-fill text-primary"></i> Rasio Kesimpulan Mutu
                </h6>
                <span class="badge bg-light text-muted border">Baku Mutu</span>
            </div>
            <div class="card-wc-body d-flex flex-column justify-content-center align-items-center">
                <?php if ($totalPemeriksaan > 0): ?>
                    <div style="position: relative; width: 100%; max-width: 240px;">
                        <canvas id="ratioChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-around w-100 mt-3 pt-2 border-top text-center">
                        <div>
                            <span class="badge-status badge-ms mb-1"><i class="bi bi-check-circle"></i> MS</span>
                            <div class="fw-bold"><?= $totalMS; ?></div>
                        </div>
                        <div>
                            <span class="badge-status badge-tms mb-1"><i class="bi bi-x-circle"></i> TMS</span>
                            <div class="fw-bold"><?= $totalTMS; ?></div>
                        </div>
                        <div>
                            <span class="badge-status badge-pending mb-1"><i class="bi bi-hourglass-split"></i> Pending</span>
                            <div class="fw-bold"><?= $totalPending; ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-clipboard-x fs-1 opacity-50 d-block mb-2"></i>
                        Belum ada data pemeriksaan untuk visualisasi.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Table: 5 Pemeriksaan Terbaru -->
<div class="card-wc">
    <div class="card-wc-header">
        <h6 class="card-wc-title">
            <i class="bi bi-clock-history text-primary"></i> 5 Pemeriksaan Terkini
        </h6>
        <a href="<?= BASE_URL; ?>pages/pemeriksaan/index.php" class="btn btn-sm btn-outline-primary">
            Lihat Semua Riwayat <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table-wc">
            <thead>
                <tr>
                    <th>No. Pemeriksaan</th>
                    <th>Tanggal</th>
                    <th>Sumber Air</th>
                    <th>Peruntukan</th>
                    <th>Sanitarian</th>
                    <th>Kesimpulan</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentPemeriksaan)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block text-secondary opacity-50 mb-1"></i>
                            Belum ada riwayat pemeriksaan yang tercatat di database.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentPemeriksaan as $row): ?>
                        <tr>
                            <td>
                                <strong class="text-primary font-monospace"><?= htmlspecialchars($row['nomor_pemeriksaan']); ?></strong>
                            </td>
                            <td><?= formatTanggal($row['tanggal_pemeriksaan'], 'pendek'); ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_sumber'] ?? 'Sumber Dihapus / Mandiri'); ?></div>
                                <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars(mb_strimwidth($row['lokasi'], 0, 35, '...')); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($row['jenis_air']); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['nama_petugas'] ?? 'Petugas'); ?></td>
                            <td>
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
                                <a href="<?= BASE_URL; ?>pages/pemeriksaan/detail.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-primary py-1 px-2" title="Lihat Detail">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Script Chart.js
$extraJs = "
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Bar Chart Bulanan
    const ctxMonthly = document.getElementById('monthlyChart');
    if (ctxMonthly) {
        new Chart(ctxMonthly, {
            type: 'bar',
            data: {
                labels: " . json_encode($monthlyLabels) . ",
                datasets: [{
                    label: 'Pemeriksaan Selesai',
                    data: " . json_encode($monthlyCounts) . ",
                    backgroundColor: 'rgba(22, 138, 173, 0.75)',
                    borderColor: '#168aad',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    hoverBackgroundColor: '#184e77'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // 2. Doughnut Chart Rasio Kesimpulan
    const ctxRatio = document.getElementById('ratioChart');
    if (ctxRatio && " . ($totalPemeriksaan > 0 ? "true" : "false") . ") {
        new Chart(ctxRatio, {
            type: 'doughnut',
            data: {
                labels: ['MS', 'TMS', 'Pending'],
                datasets: [{
                    data: [{$totalMS}, {$totalTMS}, {$totalPending}],
                    backgroundColor: ['#2a9d8f', '#e76f51', '#e9c46a'],
                    hoverOffset: 4,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '68%'
            }
        });
    }
});
</script>
";

require_once __DIR__ . '/includes/footer.php';
?>
