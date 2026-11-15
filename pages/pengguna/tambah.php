<?php
/**
 * WaterCheck - Tambah Pengguna Baru
 * File: pages/pengguna/tambah.php
 */

$pageTitle = 'Tambah Pengguna Baru';
$activeMenu = 'pengguna';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

checkAdmin();

$errors = [];
$nama = '';
$username = '';
$role = 'petugas';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $nama      = trim($_POST['nama'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $role      = trim($_POST['role'] ?? 'petugas');

    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Token CSRF tidak valid.';
    } elseif (empty($nama) || empty($username) || empty($password)) {
        $errors[] = 'Nama lengkap, username, dan password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password minimal terdiri dari 6 karakter.';
    } elseif (!in_array($role, ['admin', 'petugas'])) {
        $errors[] = 'Peran (role) pengguna tidak valid.';
    } else {
        // Cek keunikan username
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmtCheck->execute([':username' => $username]);
        if ($stmtCheck->fetch()) {
            $errors[] = "Username '{$username}' sudah digunakan oleh pengguna lain. Silakan pilih username lain.";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (nama, username, password, role, created_at)
                    VALUES (:nama, :username, :password, :role, NOW())
                ");
                $stmt->execute([
                    ':nama'     => $nama,
                    ':username' => $username,
                    ':password' => $hash,
                    ':role'     => $role
                ]);

                catatLog($pdo, $currentUser['id'], "Membuat akun pengguna baru: '{$username}' ({$role})");

                setFlashMessage('success', "Akun pengguna '{$username}' berhasil dibuat!");
                redirect(BASE_URL . 'pages/pengguna/index.php');

            } catch (PDOException $e) {
                $errors[] = 'Gagal menyimpan akun: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0" style="color: #184e77;">
                <i class="bi bi-person-plus-fill text-primary me-2"></i>Tambah Pengguna Baru
            </h4>
            <a href="<?= BASE_URL; ?>pages/pengguna/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Perbaiki Kesalahan:</h6>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card-wc">
            <div class="card-wc-header">
                <h6 class="card-wc-title">
                    <i class="bi bi-person-badge text-primary"></i> Formulir Akun Sanitarian / Administrator
                </h6>
            </div>
            <div class="card-wc-body">
                <form action="" method="POST" autocomplete="off">
                    <?= csrfField(); ?>

                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama" name="nama" placeholder="Contoh: Budi Santoso, S.K.M." value="<?= htmlspecialchars($nama); ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">@</span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="budisanitarian" value="<?= htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="form-text">Gunakan huruf kecil tanpa spasi.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required>
                    </div>

                    <div class="mb-4">
                        <label for="role" class="form-label">Hak Akses / Peran (Role) <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="petugas" <?= ($role === 'petugas') ? 'selected' : ''; ?>>Petugas Lapangan / Sanitarian</option>
                            <option value="admin" <?= ($role === 'admin') ? 'selected' : ''; ?>>Administrator (Akses Penuh)</option>
                        </select>
                        <div class="form-text">Administrator memiliki izin mengelola akun pengguna dan mengubah standar baku mutu.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL; ?>pages/pengguna/index.php" class="btn btn-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="bi bi-save me-1"></i> Simpan Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>
