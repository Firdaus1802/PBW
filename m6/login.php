<?php
require_once __DIR__ . '/config/koneksi.php';

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_mobile_device = preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|iemobile|opera mini)/i', $ua);

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
        }
    }
}

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
    <meta name="theme-color" content="#000000" id="metaThemeColor">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= base_url('manifest.json') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/icons/app-icon.png') ?>">
    <title>Masuk - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/ios-theme.css?v=' . time()) ?>" rel="stylesheet">
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>

    <style>
        :root, [data-theme="dark"], [data-bs-theme="dark"] {
            --ios-page-bg: #000000;
            --ios-card-bg: rgba(28, 28, 30, 0.75);
            --ios-card-border: rgba(255, 255, 255, 0.14);
            --ios-card-shadow: 0 30px 80px rgba(0, 0, 0, 0.75), inset 0 1px 0 rgba(255, 255, 255, 0.15);
            --ios-grouped-bg: rgba(255, 255, 255, 0.08);
            --ios-divider: rgba(255, 255, 255, 0.1);
            --ios-text: #FFFFFF;
            --ios-subtext: #8E8E93;
            --ios-placeholder: #636366;
            --ios-accent: #0A84FF;
            --ios-btn-tint-bg: rgba(10, 132, 255, 0.16);
            --ios-btn-tint-color: #0A84FF;
            --ios-circle-btn-bg: rgba(255, 255, 255, 0.1);
            --ios-circle-btn-color: #FFFFFF;
            --ios-grabber: rgba(255, 255, 255, 0.25);
        }

        [data-theme="light"], [data-bs-theme="light"] {
            --ios-page-bg: #F2F2F7;
            --ios-card-bg: rgba(255, 255, 255, 0.85);
            --ios-card-border: rgba(0, 0, 0, 0.08);
            --ios-card-shadow: 0 20px 50px rgba(0, 0, 0, 0.06), 0 2px 8px rgba(0, 0, 0, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.95);
            --ios-grouped-bg: rgba(118, 118, 128, 0.12);
            --ios-divider: rgba(60, 60, 67, 0.18);
            --ios-text: #000000;
            --ios-subtext: #8E8E93;
            --ios-placeholder: #8E8E93;
            --ios-accent: #007AFF;
            --ios-btn-tint-bg: rgba(0, 122, 255, 0.12);
            --ios-btn-tint-color: #007AFF;
            --ios-circle-btn-bg: rgba(118, 118, 128, 0.12);
            --ios-circle-btn-color: #000000;
            --ios-grabber: rgba(60, 60, 67, 0.3);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background-color: var(--ios-page-bg);
            color: var(--ios-text);
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", system-ui, sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(0, 122, 255, 0.12) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .ios-login-card {
            width: 100%;
            max-width: 400px;
            background: var(--ios-card-bg);
            backdrop-filter: blur(40px) saturate(190%);
            -webkit-backdrop-filter: blur(40px) saturate(190%);
            border: 0.5px solid var(--ios-card-border);
            border-radius: 32px;
            padding: 26px 24px 26px;
            box-shadow: var(--ios-card-shadow);
            position: relative;
            z-index: 1;
            animation: iosCardAppear 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease, transform 0.25s ease;
        }

        @keyframes iosCardAppear {
            0% {
                opacity: 0;
                transform: scale(0.95) translateY(16px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .ios-sheet-grabber {
            width: 36px;
            height: 5px;
            border-radius: 9999px;
            background: var(--ios-grabber);
            margin: 0 auto 16px;
            transition: background 0.3s ease;
        }

        .ios-theme-circle-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--ios-circle-btn-bg);
            border: none;
            color: var(--ios-circle-btn-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.15s ease, background 0.2s ease;
            outline: none;
        }

        .ios-theme-circle-btn:active {
            transform: scale(0.9);
        }

        .ios-app-icon-squircle {
            width: 72px;
            height: 72px;
            border-radius: 22.37%;
            background: linear-gradient(135deg, #007AFF 0%, #5856D6 100%);
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 14px;
            box-shadow: 0 10px 24px rgba(0, 122, 255, 0.35), inset 0 1px 1px rgba(255, 255, 255, 0.45);
        }

        .ios-app-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.4px;
            margin-bottom: 2px;
            color: var(--ios-text);
        }

        .ios-app-subtitle {
            font-size: 13px;
            color: var(--ios-subtext);
            margin-bottom: 0;
        }

        .ios-grouped-cells {
            border-radius: 16px;
            overflow: hidden;
            background: var(--ios-grouped-bg);
            margin-bottom: 20px;
            border: 0.5px solid transparent;
            transition: background 0.25s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .ios-grouped-cells:focus-within {
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.25);
            border-color: var(--ios-accent);
        }

        .ios-cell {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            min-height: 48px;
        }

        .ios-cell-icon {
            font-size: 18px;
            color: var(--ios-subtext);
            width: 24px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ios-cell input {
            background: transparent;
            border: none;
            outline: none;
            width: 100%;
            font-size: 15px;
            color: var(--ios-text);
            font-family: inherit;
        }

        .ios-cell input::placeholder {
            color: var(--ios-placeholder);
        }

        .ios-cell-divider {
            height: 0.5px;
            background: var(--ios-divider);
            margin-left: 52px;
        }

        .ios-eye-btn {
            background: transparent;
            border: none;
            outline: none;
            color: var(--ios-subtext);
            padding: 4px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .ios-btn-primary {
            background: var(--ios-accent);
            color: #FFFFFF !important;
            border: none;
            border-radius: 14px;
            height: 48px;
            width: 100%;
            font-size: 15.5px;
            font-weight: 600;
            letter-spacing: -0.2px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 122, 255, 0.3);
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .ios-btn-primary:active {
            transform: scale(0.97);
            opacity: 0.9;
        }

        .ios-btn-tinted {
            background: var(--ios-btn-tint-bg);
            color: var(--ios-btn-tint-color) !important;
            border: none;
            border-radius: 14px;
            height: 44px;
            width: 100%;
            font-size: 13.5px;
            font-weight: 600;
            letter-spacing: -0.2px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 10px;
            cursor: pointer;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .ios-btn-tinted:active {
            transform: scale(0.97);
            opacity: 0.85;
        }

        .ios-footnote {
            font-size: 11.5px;
            color: var(--ios-subtext);
            text-align: center;
            margin-top: 20px;
            letter-spacing: -0.1px;
        }

        .ios-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(28px) saturate(180%);
            -webkit-backdrop-filter: blur(28px) saturate(180%);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .ios-overlay.show {
            opacity: 1;
            pointer-events: all;
        }
    </style>
</head>
<body>

    <div class="ios-login-card">
        <div class="ios-sheet-grabber"></div>

        <div class="d-flex justify-content-end mb-1">
            <button type="button" onclick="toggleTheme()" class="ios-theme-circle-btn" title="Mode Tampilan">
                <i class="theme-toggle-icon fs-6"></i>
            </button>
        </div>

        <div class="text-center mb-4">
            <div class="ios-app-icon-squircle">
                <i class="bi bi-wallet2"></i>
            </div>
            <h4 class="ios-app-title">DompetKu</h4>
            <p class="ios-app-subtitle">Sistem Kas Mahasiswa Gunadarma</p>
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

        <form action="login.php" method="POST" id="loginForm">
            <input type="hidden" name="client_is_mobile" id="clientIsMobile" value="0">

            <div class="ios-grouped-cells">
                <div class="ios-cell">
                    <div class="ios-cell-icon"><i class="bi bi-person"></i></div>
                    <input type="text" name="username" id="inputUsername" placeholder="Username" required autofocus>
                </div>
                <div class="ios-cell-divider"></div>
                <div class="ios-cell">
                    <div class="ios-cell-icon"><i class="bi bi-lock"></i></div>
                    <input type="password" name="password" id="inputPassword" placeholder="Kata Sandi" required>
                    <button type="button" class="ios-eye-btn" onclick="togglePassVisibility()" tabindex="-1">
                        <i class="bi bi-eye" id="toggleEyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="ios-btn-primary">
                <i class="bi bi-arrow-right-circle-fill"></i> Masuk
            </button>

            <button type="button" class="ios-btn-tinted" onclick="autoFillDemoLogin()">
                <i class="bi bi-lightning-charge-fill"></i> Akses Cepat Demo
            </button>
        </form>

        <div class="ios-footnote">
            Database MySQL & SQLite • Bcrypt Hash
        </div>
    </div>

    <div id="loginTransitionOverlay" class="ios-overlay">
        <div class="text-center px-4">
            <div class="ios-app-icon-squircle mx-auto mb-3" style="width: 64px; height: 64px; font-size: 28px;">
                <i class="bi bi-wallet2"></i>
            </div>
            <h5 class="fw-bold text-white mb-1">Memverifikasi Sesi...</h5>
            <p class="text-white text-opacity-75 small mb-3">Menyiapkan Dashboard DompetKu</p>
            <div class="spinner-border text-white text-opacity-75" style="width: 2rem; height: 2rem; border-width: 2.5px;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

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
            const card = document.querySelector('.ios-login-card');
            if (card) {
                card.style.transform = 'scale(0.96)';
                card.style.opacity = '0.7';
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