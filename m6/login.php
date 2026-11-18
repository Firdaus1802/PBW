<?php
// login.php - Autentikasi Pengguna DompetKu (Database-backed Login dengan Bcrypt)
require_once __DIR__ . '/config/koneksi.php';

// Deteksi jenis perangkat pengguna
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_mobile_device = preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|iemobile|opera mini)/i', $ua);

// Auto-login jika memiliki cookie Remember Me yang valid (30 Hari)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['dompetku_remember'])) {
    $cookie_data = explode(':', $_COOKIE['dompetku_remember']);
    if (count($cookie_data) === 2) {
        $saved_uid = (int)$cookie_data[0];
        $saved_tok = $cookie_data[1];
        try {
            $stmt_auto = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
            $stmt_auto->execute([$saved_uid]);
            $u_auto = $stmt_auto->fetch();
            if ($u_auto) {
                $expected_tok = hash('sha256', $u_auto['id_user'] . $u_auto['password'] . 'dompetku_pbw_secret');
                if (hash_equals($expected_tok, $saved_tok)) {
                    $_SESSION['user_id']      = $u_auto['id_user'];
                    $_SESSION['username']     = $u_auto['username'];
                    $_SESSION['nama_lengkap'] = $u_auto['nama_lengkap'];
                    
                    if ($is_mobile_device) {
                        header("Location: " . base_url("index.php"));
                    } else {
                        header("Location: " . base_url("dashboard.php"));
                    }
                    exit;
                }
            }
        } catch (PDOException $e) {
            // Abaikan dan lanjutkan ke form login
        }
    }
}

// Jika sudah login, langsung arahkan sesuai perangkat
if (isset($_SESSION['user_id'])) {
    if ($is_mobile_device) {
        header("Location: " . base_url("index.php"));
    } else {
        header("Location: " . base_url("dashboard.php"));
    }
    exit;
}

$error_msg = '';
$success_msg = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $success_msg = 'Anda telah berhasil keluar dari sistem.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']      = $user['id_user'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

                // Set Cookie Remember Me 30 Hari agar sesi tetap aktif dan tidak perlu login ulang di HP
                $remember_token = hash('sha256', $user['id_user'] . $user['password'] . 'dompetku_pbw_secret');
                setcookie('dompetku_remember', $user['id_user'] . ':' . $remember_token, time() + (86400 * 30), '/', '', false, true);
                
                $client_is_mobile = (isset($_POST['client_is_mobile']) && $_POST['client_is_mobile'] === '1') || $is_mobile_device;

                if ($client_is_mobile) {
                    header("Location: " . base_url("index.php"));
                } else {
                    header("Location: " . base_url("dashboard.php"));
                }
                exit;
            } else {
                $error_msg = 'Username atau password tidak sesuai. Coba lagi.';
            }
        } catch (PDOException $e) {
            $error_msg = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    } else {
        $error_msg = 'Silakan isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0C0D12" id="metaThemeColor">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/icons/app-icon.png') ?>">
    <title>Masuk - DompetKu</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- iOS Theme CSS -->
    <!-- iOS Theme CSS -->
    <link href="<?= base_url('assets/css/ios-theme.css?v=' . time()) ?>" rel="stylesheet">
    <!-- Theme Toggle Helper Script -->
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>

    <style>
        :root, [data-theme="dark"], [data-bs-theme="dark"] {
            --login-bg: radial-gradient(circle at 50% 15%, #161824 0%, #090A0F 100%);
            --login-card-bg: rgba(22, 24, 34, 0.78);
            --login-card-border: rgba(255, 255, 255, 0.12);
            --login-card-shadow: 0 30px 70px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.12);
            --login-input-bg: rgba(255, 255, 255, 0.07);
            --login-input-border: rgba(255, 255, 255, 0.11);
            --login-input-text: #FFFFFF;
            --login-label-color: #94A3B8;
            --login-placeholder: #64748B;
            --login-btn-primary-bg: #FFFFFF;
            --login-btn-primary-color: #090A0F;
            --login-btn-sec-bg: rgba(255, 255, 255, 0.07);
            --login-btn-sec-color: #F1F5F9;
            --login-btn-sec-border: rgba(255, 255, 255, 0.10);
            --login-logo-bg: linear-gradient(145deg, #222533 0%, #12141C 100%);
            --login-logo-color: #FFFFFF;
            --login-logo-border: rgba(255, 255, 255, 0.16);
            --login-logo-shadow: 0 10px 28px rgba(0, 0, 0, 0.45);
        }

        [data-theme="light"], [data-bs-theme="light"] {
            --login-bg: radial-gradient(circle at 50% 15%, #FFFFFF 0%, #E8E9F0 100%);
            --login-card-bg: rgba(255, 255, 255, 0.82);
            --login-card-border: rgba(0, 0, 0, 0.08);
            --login-card-shadow: 0 24px 50px rgba(0, 0, 0, 0.06), 0 2px 8px rgba(0, 0, 0, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.85);
            --login-input-bg: rgba(118, 118, 128, 0.09);
            --login-input-border: rgba(0, 0, 0, 0.08);
            --login-input-text: #1C1C1E;
            --login-label-color: #3C3C43;
            --login-placeholder: #8E8E93;
            --login-btn-primary-bg: #1C1C1E;
            --login-btn-primary-color: #FFFFFF;
            --login-btn-sec-bg: rgba(118, 118, 128, 0.09);
            --login-btn-sec-color: #1C1C1E;
            --login-btn-sec-border: rgba(0, 0, 0, 0.07);
            --login-logo-bg: linear-gradient(145deg, #1C1C1E 0%, #2C2C2E 100%);
            --login-logo-color: #FFFFFF;
            --login-logo-border: rgba(0, 0, 0, 0.08);
            --login-logo-shadow: 0 8px 22px rgba(0, 0, 0, 0.15);
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: var(--login-bg);
            transition: background 0.35s ease;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--login-card-bg);
            backdrop-filter: blur(32px) saturate(190%);
            -webkit-backdrop-filter: blur(32px) saturate(190%);
            border: 1px solid var(--login-card-border);
            border-radius: 28px;
            padding: 34px 28px;
            box-shadow: var(--login-card-shadow);
            position: relative;
            overflow: hidden;
            animation: iosLoginCardEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
            transition: transform 0.3s ease, background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        @keyframes iosLoginCardEntrance {
            0% {
                opacity: 0;
                transform: scale(0.94) translateY(20px);
                filter: blur(8px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
                filter: blur(0);
            }
        }

        .login-transition-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 12, 17, 0.85);
            backdrop-filter: blur(28px) saturate(180%);
            -webkit-backdrop-filter: blur(28px) saturate(180%);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .login-transition-overlay.show {
            opacity: 1;
            pointer-events: all;
        }

        .login-pulse-badge {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            background: #FFFFFF;
            color: #0F172A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.35);
            margin-bottom: 20px;
            animation: loginAppPulse 1.2s ease-in-out infinite;
        }

        @keyframes loginAppPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.08); opacity: 0.88; }
        }

        .app-logo-badge {
            width: 66px;
            height: 66px;
            border-radius: 20px;
            background: var(--login-logo-bg);
            border: 1px solid var(--login-logo-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: var(--login-logo-color);
            margin: 0 auto 16px;
            box-shadow: var(--login-logo-shadow);
            transition: all 0.3s ease;
        }

        .input-group-ios {
            background-color: var(--login-input-bg);
            border: 1px solid var(--login-input-border);
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .input-group-ios:focus-within {
            border-color: #007AFF !important;
            box-shadow: 0 0 0 3.5px rgba(0, 122, 255, 0.22) !important;
        }

        .input-group-ios .input-icon {
            padding: 0 14px;
            color: var(--login-placeholder);
            font-size: 17px;
        }

        .input-group-ios input {
            background: transparent;
            border: none;
            outline: none;
            padding: 13px 14px 13px 0;
            color: var(--login-input-text);
            width: 100%;
            font-size: 14.5px;
            font-weight: 500;
        }

        .input-group-ios input::placeholder {
            color: var(--login-placeholder);
        }

        .btn-ios-login-primary {
            background-color: var(--login-btn-primary-bg);
            color: var(--login-btn-primary-color) !important;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 14.5px;
            padding: 13px 20px;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
        }

        .btn-ios-login-primary:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-ios-login-primary:active {
            transform: scale(0.98);
        }

        .btn-ios-login-secondary {
            background-color: var(--login-btn-sec-bg);
            color: var(--login-btn-sec-color) !important;
            border: 1px solid var(--login-btn-sec-border);
            border-radius: 14px;
            font-weight: 600;
            font-size: 13px;
            padding: 11px 18px;
            width: 100%;
            transition: all 0.2s ease;
        }

        .btn-ios-login-secondary:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-ios-login-secondary:active {
            transform: scale(0.98);
        }

        .login-label {
            color: var(--login-label-color);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: -0.1px;
            margin-bottom: 6px;
            display: block;
        }
    </style>
</head>
<body>

    <div class="login-card">
        
        <!-- Header Mode Switcher -->
        <div class="d-flex justify-content-end mb-2">
            <button onclick="toggleTheme()" class="btn btn-ios-secondary btn-sm py-1 px-2 d-flex align-items-center" title="Mode Terang / Gelap">
                <i class="theme-toggle-icon fs-6"></i>
            </button>
        </div>

        <!-- App Brand -->
        <div class="text-center mb-4">
            <div class="app-logo-badge">
                <i class="bi bi-wallet2"></i>
            </div>
            <h4 class="fw-bold mb-1" style="letter-spacing: -0.5px; color: var(--boss-text-primary);">DompetKu</h4>
            <p class="text-secondary small mb-0">Sistem Kas & Pencatatan Mahasiswa (PBW)</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger rounded-4 py-2 small mb-3 border-0 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success rounded-4 py-2 small mb-3 border-0 d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i> <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form action="login.php" method="POST" id="loginForm">
            <input type="hidden" name="client_is_mobile" id="clientIsMobile" value="0">
            
            <div class="mb-3">
                <label class="login-label">Username</label>
                <div class="input-group-ios">
                    <span class="input-icon"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="username" id="inputUsername" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="login-label">Kata Sandi</label>
                <div class="input-group-ios">
                    <span class="input-icon"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password" id="inputPassword" placeholder="Masukkan password" required>
                    <button type="button" class="btn btn-sm text-secondary border-0 pe-3" onclick="togglePassVisibility()" tabindex="-1">
                        <i class="bi bi-eye" id="toggleEyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-ios-login-primary mb-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke DompetKu
            </button>

            <!-- Quick Demo 1-Click Login Button -->
            <button type="button" class="btn-ios-login-secondary" onclick="autoFillDemoLogin()">
                <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Masuk Cepat Demo (Firdaus Dhuha)
            </button>

        </form>

        <div class="mt-4 pt-3 border-top border-secondary border-opacity-10 text-center text-secondary small" style="font-size: 11px;">
            Terhubung ke Database MySQL • Password Hash Bcrypt
        </div>

    </div>

    <!-- Login Verification Transition Overlay -->
    <div id="loginTransitionOverlay" class="login-transition-overlay">
        <div class="text-center px-4">
            <div class="login-pulse-badge mx-auto">
                <i class="bi bi-wallet2"></i>
            </div>
            <h5 class="fw-bold text-white mb-1" style="letter-spacing: -0.3px;">Memverifikasi Sesi...</h5>
            <p class="text-white text-opacity-75 small mb-3">Menyiapkan Dashboard Finansial Mahasiswa Gunadarma</p>
            <div class="spinner-border text-white text-opacity-75" style="width: 2rem; height: 2rem; border-width: 2.5px;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function togglePassVisibility() {
            const passInput = document.getElementById('inputPassword');
            const icon = document.getElementById('toggleEyeIcon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                passInput.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        function triggerLoginTransition() {
            const overlay = document.getElementById('loginTransitionOverlay');
            const card = document.querySelector('.login-card');
            if (card) {
                card.style.transform = 'scale(0.96)';
                card.style.opacity = '0.7';
                card.style.filter = 'blur(6px)';
            }
            if (overlay) {
                overlay.classList.add('show');
            }
        }

        function autoFillDemoLogin() {
            document.getElementById('inputUsername').value = 'admin';
            document.getElementById('inputPassword').value = 'admin123';
            if (document.getElementById('clientIsMobile')) {
                document.getElementById('clientIsMobile').value = (window.innerWidth < 992) ? '1' : '0';
            }
            triggerLoginTransition();
            setTimeout(function() {
                document.getElementById('loginForm').submit();
            }, 350);
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            if (document.getElementById('clientIsMobile')) {
                document.getElementById('clientIsMobile').value = (window.innerWidth < 992) ? '1' : '0';
            }
            triggerLoginTransition();
        });
    </script>
</body>
</html>
