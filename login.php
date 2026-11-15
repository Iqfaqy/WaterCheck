<?php
/**
 * WaterCheck - Halaman Login Pengguna
 * File: login.php
 */

$skipAuth = true;
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

// Jika sudah login, langsung alihkan ke dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . 'dashboard.php');
}

$errorMessage = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $usernameVal = $username;

    // 1. Verifikasi CSRF Token
    if (!verifyCsrfToken($csrfToken)) {
        $errorMessage = 'Sesi form tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.';
    } elseif (empty($username) || empty($password)) {
        $errorMessage = 'Username dan password wajib diisi!';
    } else {
        try {
            // 2. Ambil data pengguna dari database berdasarkan username
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            // 3. Verifikasi Password menggunakan password_verify()
            if ($user && password_verify($password, $user['password'])) {
                // Regenerasi session ID untuk mencegah session fixation attack
                session_regenerate_id(true);

                // Set data sesi
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['nama']      = $user['nama'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];

                // Catat aktivitas login
                catatLog($pdo, (int)$user['id'], "Pengguna {$user['nama']} ({$user['username']}) berhasil login sebagai {$user['role']}.");

                // Set notifikasi sukses dan redirect
                setFlashMessage('success', "Selamat datang di WaterCheck, {$user['nama']}!");
                redirect(BASE_URL . 'dashboard.php');
            } else {
                $errorMessage = 'Username atau password yang Anda masukkan salah. Silakan periksa kembali.';
            }
        } catch (PDOException $e) {
            $errorMessage = 'Terjadi kesalahan sistem saat memproses login. Silakan hubungi admin.';
            error_log("Login Error: " . $e->getMessage());
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars(APP_NAME); ?> (<?= htmlspecialchars(APP_ALT_NAME); ?>)</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL; ?>assets/img/logo.svg">
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= BASE_URL; ?>assets/css/style.css">
</head>
<body class="login-body">

<div class="login-card">
    <div class="login-card-header">
        <img src="<?= BASE_URL; ?>assets/img/logo.svg" alt="WaterCheck Logo" width="72" height="72" class="mb-2">
        <h3 class="fw-bold mb-1" style="color: #184e77; letter-spacing: -0.5px;"><?= htmlspecialchars(APP_NAME); ?></h3>
        <p class="text-muted small mb-0"><?= htmlspecialchars(APP_TAGLINE); ?></p>
    </div>

    <div class="login-card-body">
        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']); ?> alert-dismissible fade show small py-2" role="alert">
                <?= htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show small py-2 d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-6 text-danger"></i>
                <div><?= htmlspecialchars($errorMessage); ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL; ?>login.php" method="POST" autocomplete="off">
            <?= csrfField(); ?>

            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="bi bi-person me-1 text-primary"></i> Username
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person-fill"></i></span>
                    <input type="text" class="form-control border-start-0" id="username" name="username" value="<?= htmlspecialchars($usernameVal); ?>" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="bi bi-shield-lock me-1 text-primary"></i> Password
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" placeholder="Masukkan password" required>
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePasswordBtn">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
                </button>
            </div>
        </form>

        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Password Visibility
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (togglePasswordBtn && passwordInput && togglePasswordIcon) {
        togglePasswordBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            togglePasswordIcon.classList.toggle('bi-eye');
            togglePasswordIcon.classList.toggle('bi-eye-slash');
        });
    }

</script>
</body>
</html>
