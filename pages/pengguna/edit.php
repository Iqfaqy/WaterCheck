<?php
/**
 * WaterCheck - Edit Data Pengguna
 * File: pages/pengguna/edit.php
 */

$pageTitle = 'Edit Pengguna';
$activeMenu = 'pengguna';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/navbar.php';

checkAdmin();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    setFlashMessage('danger', 'Data pengguna tidak ditemukan.');
    redirect(BASE_URL . 'pages/pengguna/index.php');
}

$isSelf = ($user['id'] == $currentUser['id']);

$errors = [];
$nama     = $user['nama'];
$username = $user['username'];
$role     = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken   = $_POST['csrf_token'] ?? '';
    $nama        = trim($_POST['nama'] ?? '');
    $username    = trim($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $roleInput   = trim($_POST['role'] ?? 'petugas');

    // Jika mengedit akun sendiri, tidak boleh mengubah role diri sendiri menjadi non-admin
    if ($isSelf) {
        $roleInput = 'admin';
    }

    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Token CSRF tidak valid.';
    } elseif (empty($nama) || empty($username)) {
        $errors[] = 'Nama lengkap dan username tidak boleh kosong.';
    } elseif (!empty($newPassword) && strlen($newPassword) < 6) {
        $errors[] = 'Password baru minimal terdiri dari 6 karakter.';
    } else {
        // Cek jika username diubah dan sudah dipakai akun lain
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1");
        $stmtCheck->execute([':username' => $username, ':id' => $id]);
        if ($stmtCheck->fetch()) {
            $errors[] = "Username '{$username}' sudah digunakan oleh pengguna lain.";
        } else {
            try {
                if (!empty($newPassword)) {
                    // Update dengan password baru
                    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                    $stmtUpdate = $pdo->prepare("
                        UPDATE users 
                        SET nama = :nama, username = :username, password = :password, role = :role, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmtUpdate->execute([
                        ':nama'     => $nama,
                        ':username' => $username,
                        ':password' => $hash,
                        ':role'     => $roleInput,
                        ':id'       => $id
                    ]);
                } else {
                    // Update tanpa mengubah password
                    $stmtUpdate = $pdo->prepare("
                        UPDATE users 
                        SET nama = :nama, username = :username, role = :role, updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmtUpdate->execute([
                        ':nama'     => $nama,
                        ':username' => $username,
                        ':role'     => $roleInput,
                        ':id'       => $id
                    ]);
                }

                // Jika user yang diedit adalah user yang sedang login, update sesi
                if ($isSelf) {
                    $_SESSION['nama'] = $nama;
                    $_SESSION['username'] = $username;
                }

                catatLog($pdo, $currentUser['id'], "Memperbarui data akun pengguna: '{$username}' (ID: {$id})");

                setFlashMessage('success', "Data pengguna '{$username}' berhasil diperbarui!");
                redirect(BASE_URL . 'pages/pengguna/index.php');

            } catch (PDOException $e) {
                $errors[] = 'Gagal menyimpan perubahan: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="fw-bold mb-0" style="color: #184e77;">
                <i class="bi bi-person-gear text-primary me-2"></i>Edit Pengguna: @<?= htmlspecialchars($username); ?>
            </h4>
            <a href="<?= BASE_URL; ?>pages/pengguna/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Batal & Kembali
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
                    <i class="bi bi-person-badge text-primary"></i> Perbarui Akun #<?= $id; ?>
                </h6>
            </div>
            <div class="card-wc-body">
                <form action="" method="POST" autocomplete="off">
                    <?= csrfField(); ?>

                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($nama); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">@</span>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">Ubah Password (Opsional)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <div class="form-text">Isi hanya jika ingin mengganti password akun ini.</div>
                    </div>

                    <div class="mb-4">
                        <label for="role" class="form-label">Peran (Role) Pengguna <span class="text-danger">*</span></label>
                        <?php if ($isSelf): ?>
                            <input type="text" class="form-control bg-light" value="Administrator" readonly>
                            <input type="hidden" name="role" value="admin">
                            <div class="form-text text-muted">Anda tidak dapat menurunkan hak akses akun Anda sendiri saat sedang login.</div>
                        <?php else: ?>
                            <select class="form-select" id="role" name="role" required>
                                <option value="petugas" <?= ($role === 'petugas') ? 'selected' : ''; ?>>Petugas Lapangan / Sanitarian</option>
                                <option value="admin" <?= ($role === 'admin') ? 'selected' : ''; ?>>Administrator (Akses Penuh)</option>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL; ?>pages/pengguna/index.php" class="btn btn-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
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
