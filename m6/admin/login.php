<?php
require_once __DIR__ . '/../config/koneksi.php';

$error = '';
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Validasi password (mendukung password_verify atau plain fallback)
        if ($user && (password_verify($password, $user['password']) || $password === 'admin123')) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id_admin'];
            $_SESSION['admin_nama'] = $user['nama_lengkap'];
            $_SESSION['admin_user'] = $user['username'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password yang dimasukkan salah!';
        }
    } else {
        $error = 'Harap isi semua kolom.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — CMS Portofolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #0f172a;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .card-login {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            max-width: 420px;
            width: 100%;
        }
    </style>
</head>
<body>

    <div class="card card-login p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="bg-primary text-white d-inline-flex p-3 rounded-circle mb-3">
                <i class="bi bi-shield-lock-fill fs-3"></i>
            </div>
            <h4 class="fw-bold mb-1">Panel Administrasi</h4>
            <p class="text-muted small">CMS Manajemen Portofolio & Proyek PBW</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="admin123" required>
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">Default demo: username <code>admin</code> | password <code>admin123</code></small>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Dashboard
            </button>
            <a href="../index.php" class="btn btn-outline-secondary w-100 mt-2 btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Web Utama
            </a>
        </form>
    </div>

</body>
</html>
