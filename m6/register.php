<?php
require_once __DIR__ . '/config/koneksi.php';

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_mobile_device = preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|iemobile|opera mini)/i', $ua);

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
$val_nama = '';
$val_user = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap     = sanitize($_POST['nama_lengkap'] ?? '');
    $username         = strtolower(trim(sanitize($_POST['username'] ?? '')));
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $val_nama = $nama_lengkap;
    $val_user = $username;

    if (empty($nama_lengkap) || strlen($nama_lengkap) < 3) {
        $error_msg = 'Nama lengkap minimal 3 karakter.';
    } elseif (empty($username) || strlen($username) < 3) {
        $error_msg = 'Username minimal 3 karakter.';
    } elseif (!preg_match('/^[a-z0-9_]+$/', $username)) {
        $error_msg = 'Username hanya boleh berisi huruf kecil, angka, dan garis bawah (_).';
    } elseif (strlen($password) < 6) {
        $error_msg = 'Kata sandi minimal 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error_msg = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        try {
            $stmt_check = $pdo->prepare("SELECT id_user FROM users WHERE LOWER(username) = LOWER(?)");
            $stmt_check->execute([$username]);
            if ($stmt_check->fetch()) {
                $error_msg = 'Username sudah terdaftar, silakan gunakan username lain.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt_ins = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap) VALUES (?, ?, ?)");
                $stmt_ins->execute([$username, $hash, $nama_lengkap]);
                $new_id = (int)$pdo->lastInsertId();
                if ($new_id <= 0) {
                    $u_fetch = $pdo->prepare("SELECT id_user FROM users WHERE LOWER(username) = LOWER(?)");
                    $u_fetch->execute([$username]);
                    $new_id = (int)$u_fetch->fetchColumn();
                }

                $_SESSION['user_id']      = $new_id;
                $_SESSION['username']     = $username;
                $_SESSION['nama_lengkap'] = $nama_lengkap;

                $sess_payload = [
                    'id_user'       => (int)$new_id,
                    'username'      => $username,
                    'nama_lengkap'  => $nama_lengkap,
                    'password_hash' => $hash,
                    'exp'           => time() + (86400 * 30)
                ];
                dompetku_set_secure_cookie('dompetku_session', dompetku_sign_payload($sess_payload));

                $remember_token = hash('sha256', $new_id . $hash . 'dompetku_pbw_secret');
                dompetku_set_secure_cookie('dompetku_remember', $new_id . ':' . $remember_token, time() + (86400 * 30));
                dompetku_sync_trx_cookie($pdo, $new_id);

                $client_is_mobile = (isset($_POST['client_is_mobile']) && $_POST['client_is_mobile'] === '1') || $is_mobile_device;
                $target_url = $client_is_mobile ? base_url("index.php") : base_url("dashboard.php");
                $reg_name = htmlspecialchars($nama_lengkap ?: $username);
                ?>
<!DOCTYPE html>
<html lang="id" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Pendaftaran Berhasil - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/ios-theme.css?v=' . time()) ?>" rel="stylesheet">
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>
    <noscript><meta http-equiv="refresh" content="1;url=<?= $target_url ?>"></noscript>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(ellipse at 50% 38%, rgba(99, 102, 241, 0.16) 0%, rgba(6, 182, 212, 0.06) 45%, rgba(7, 8, 14, 0) 75%), #07080E;
            color: #F8FAFC;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, sans-serif;
            overflow: hidden;
            margin: 0;
            padding: 24px;
        }
        [data-theme="light"] body, [data-bs-theme="light"] body {
            background: radial-gradient(ellipse at 50% 38%, rgba(99, 102, 241, 0.12) 0%, rgba(6, 182, 212, 0.05) 45%, rgba(248, 250, 252, 0) 75%), #F8FAFC;
            color: #0F172A;
        }
        .auth-vault-card {
            background: rgba(18, 20, 30, 0.88);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            padding: 40px 30px;
            text-align: center;
            max-width: 380px;
            width: 100%;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.6), 0 0 45px rgba(99, 102, 241, 0.18), inset 0 1px 1px rgba(255, 255, 255, 0.15);
            animation: authCardScale 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
            position: relative;
        }
        [data-theme="light"] .auth-vault-card, [data-bs-theme="light"] .auth-vault-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 24px 50px rgba(100, 116, 139, 0.18), 0 0 35px rgba(99, 102, 241, 0.12), inset 0 1px 1px #FFFFFF;
        }
        .auth-crest-wrap {
            position: relative;
            width: 84px;
            height: 84px;
            margin: 0 auto 22px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-crest-ring {
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #6366F1, #38BDF8, #A855F7, #6366F1);
            animation: ringRotate 2.5s linear infinite;
            filter: blur(3px);
            opacity: 0.75;
        }
        .auth-crest-inner {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #0E1018;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 2px 6px rgba(255, 255, 255, 0.1), 0 8px 24px rgba(0, 0, 0, 0.5);
            animation: crestPop 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }
        [data-theme="light"] .auth-crest-inner, [data-bs-theme="light"] .auth-crest-inner {
            background: #FFFFFF;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.04), 0 8px 24px rgba(100, 116, 139, 0.2);
        }
        .auth-crest-icon {
            font-size: 38px;
            background: linear-gradient(135deg, #FFFFFF 0%, #A5B4FC 45%, #38BDF8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 2px 8px rgba(99, 102, 241, 0.4));
        }
        [data-theme="light"] .auth-crest-icon, [data-bs-theme="light"] .auth-crest-icon {
            background: linear-gradient(135deg, #4338CA 0%, #6366F1 50%, #0284C7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 2px 6px rgba(99, 102, 241, 0.3));
        }
        .auth-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 14px;
            border-radius: 99px;
            background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.3);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 1.2px;
            color: #A5B4FC;
            margin-bottom: 14px;
            text-transform: uppercase;
        }
        [data-theme="light"] .auth-status-pill, [data-bs-theme="light"] .auth-status-pill {
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.25);
            color: #4F46E5;
        }
        .auth-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #38BDF8;
            box-shadow: 0 0 10px #38BDF8;
            animation: dotGlow 1.2s ease-in-out infinite alternate;
        }
        .auth-title {
            font-size: 21px;
            font-weight: 700;
            letter-spacing: -0.4px;
            margin-bottom: 6px;
        }
        .auth-subtitle {
            font-size: 13px;
            color: #94A3B8;
            margin-bottom: 24px;
        }
        [data-theme="light"] .auth-subtitle, [data-bs-theme="light"] .auth-subtitle {
            color: #64748B;
        }
        .auth-progress-track {
            width: 100%;
            height: 4px;
            border-radius: 99px;
            background: rgba(255, 255, 255, 0.08);
            overflow: hidden;
            position: relative;
        }
        [data-theme="light"] .auth-progress-track, [data-bs-theme="light"] .auth-progress-track {
            background: rgba(0, 0, 0, 0.06);
        }
        .auth-progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #6366F1 0%, #38BDF8 50%, #A855F7 100%);
            border-radius: 99px;
            box-shadow: 0 0 16px rgba(56, 189, 248, 0.85), 0 0 6px #6366F1;
            animation: fillLaserProgress 0.82s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes ringRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes dotGlow {
            0% { opacity: 0.5; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1.15); }
        }
        @keyframes authCardScale {
            0% { opacity: 0; transform: scale(0.92) translateY(16px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes crestPop {
            0% { opacity: 0; transform: scale(0.5); }
            70% { transform: scale(1.12); }
            100% { opacity: 1; transform: scale(1); }
        }
        @keyframes fillLaserProgress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="auth-vault-card">
        <div class="auth-crest-wrap">
            <div class="auth-crest-ring"></div>
            <div class="auth-crest-inner">
                <i class="bi bi-wallet2 auth-crest-icon"></i>
            </div>
        </div>
        <div>
            <span class="auth-status-pill">
                <span class="auth-status-dot"></span>
                Brankas Terbuka
            </span>
        </div>
        <div class="auth-title">Pendaftaran Berhasil!</div>
        <div class="auth-subtitle">Menyiapkan akun untuk <?= $reg_name ?>...</div>
        <div class="auth-progress-track">
            <div class="auth-progress-bar"></div>
        </div>
    </div>
    <script>
        setTimeout(function() {
            window.location.replace("<?= $target_url ?>");
        }, 850);
    </script>
</body>
</html>
<?php
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0C0D12" id="metaThemeColor">
    <title>Daftar Akun Baru - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/ios-theme.css?v=' . time()) ?>" rel="stylesheet">
    <script src="<?= base_url('assets/js/theme.js') ?>"></script>

    <style>
        :root, [data-theme="dark"], [data-bs-theme="dark"] {
            --auth-bg: #0C0D12;
            --auth-card-bg: #141620;
            --auth-border: rgba(255, 255, 255, 0.08);
            --auth-border-focus: rgba(255, 255, 255, 0.28);
            --auth-focus-ring: rgba(255, 255, 255, 0.06);
            --auth-input-bg: #1A1D27;
            --auth-text-title: #F8FAFC;
            --auth-text-label: #94A3B8;
            --auth-text-muted: #64748B;
            --auth-text-body: #F8FAFC;
            --auth-placeholder: #475569;
            --auth-btn-primary-bg: #FFFFFF;
            --auth-btn-primary-text: #090A0F;
            --auth-btn-sec-bg: #1C1E2A;
            --auth-btn-sec-text: #CBD5E1;
            --auth-btn-sec-border: rgba(255, 255, 255, 0.08);
            --auth-icon-bg: #1C1E2A;
            --auth-icon-color: #F8FAFC;
            --auth-icon-border: rgba(255, 255, 255, 0.1);
            --auth-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
        }

        [data-theme="light"], [data-bs-theme="light"] {
            --auth-bg: #F8F9FA;
            --auth-card-bg: #FFFFFF;
            --auth-border: #E2E8F0;
            --auth-border-focus: #0F172A;
            --auth-focus-ring: rgba(15, 23, 42, 0.06);
            --auth-input-bg: #F8FAFC;
            --auth-text-title: #0F172A;
            --auth-text-label: #475569;
            --auth-text-muted: #64748B;
            --auth-text-body: #0F172A;
            --auth-placeholder: #94A3B8;
            --auth-btn-primary-bg: #0F172A;
            --auth-btn-primary-text: #FFFFFF;
            --auth-btn-sec-bg: #F1F5F9;
            --auth-btn-sec-text: #334155;
            --auth-btn-sec-border: #E2E8F0;
            --auth-icon-bg: #F1F5F9;
            --auth-icon-color: #0F172A;
            --auth-icon-border: #E2E8F0;
            --auth-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
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
            background-color: var(--auth-bg);
            color: var(--auth-text-body);
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", Arial, sans-serif;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            margin: auto;
        }

        .auth-card {
            background-color: var(--auth-card-bg);
            border: 1px solid var(--auth-border);
            border-radius: 20px;
            padding: 28px 24px;
            box-shadow: var(--auth-shadow);
            transition: background-color 0.25s ease, border-color 0.25s ease;
        }

        .auth-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .auth-brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background-color: var(--auth-icon-bg);
            border: 1px solid var(--auth-icon-border);
            color: var(--auth-icon-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .auth-brand-title {
            font-size: 19px;
            font-weight: 700;
            letter-spacing: -0.3px;
            margin: 0;
            color: var(--auth-text-title);
            line-height: 1.2;
        }

        .auth-brand-subtitle {
            font-size: 12px;
            color: var(--auth-text-muted);
            margin: 0;
        }

        .auth-theme-btn {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background-color: var(--auth-btn-sec-bg);
            border: 1px solid var(--auth-btn-sec-border);
            color: var(--auth-text-label);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.15s ease, background-color 0.2s ease;
            outline: none;
        }

        .auth-theme-btn:active {
            transform: scale(0.92);
        }

        .form-label-custom {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--auth-text-label);
            margin-bottom: 6px;
            display: block;
        }

        .auth-input-group {
            display: flex;
            align-items: center;
            background-color: var(--auth-input-bg);
            border: 1px solid var(--auth-border);
            border-radius: 10px;
            padding: 0 14px;
            height: 44px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-input-group:focus-within {
            border-color: var(--auth-border-focus);
            box-shadow: 0 0 0 3px var(--auth-focus-ring);
        }

        .auth-input-group input {
            background: transparent;
            border: none;
            outline: none;
            width: 100%;
            font-size: 14px;
            color: var(--auth-text-body);
            font-family: inherit;
        }

        .auth-input-group input::placeholder {
            color: var(--auth-placeholder);
        }

        .auth-eye-btn {
            background: transparent;
            border: none;
            outline: none;
            color: var(--auth-text-muted);
            padding: 2px 0 2px 8px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .btn-auth-primary {
            background-color: var(--auth-btn-primary-bg);
            color: var(--auth-btn-primary-text) !important;
            border: none;
            border-radius: 10px;
            height: 44px;
            width: 100%;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: opacity 0.15s ease, transform 0.15s ease;
            margin-top: 10px;
        }

        .btn-auth-primary:hover {
            opacity: 0.92;
        }

        .btn-auth-primary:active {
            transform: scale(0.99);
        }

        .auth-footer {
            margin-top: 22px;
            padding-top: 16px;
            border-top: 1px solid var(--auth-border);
            text-align: center;
            font-size: 11.5px;
            color: var(--auth-text-muted);
        }

        .auth-overlay {
            position: fixed;
            inset: 0;
            background: rgba(12, 13, 18, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .auth-overlay.show {
            opacity: 1;
            pointer-events: all;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-card">
            
            <div class="auth-header">
                <div class="auth-brand">
                    <div class="auth-brand-icon">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <div>
                        <h1 class="auth-brand-title">Daftar Akun</h1>
                        <p class="auth-brand-subtitle">Buat Akun DompetKu Baru</p>
                    </div>
                </div>

                <button type="button" onclick="toggleTheme()" class="auth-theme-btn" title="Mode Tampilan">
                    <i class="theme-toggle-icon fs-6"></i>
                </button>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger rounded-3 py-2 px-3 small mb-3 border-0 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= $error_msg ?></span>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="registerForm">
                <input type="hidden" name="client_is_mobile" id="clientIsMobile" value="0">

                <div class="mb-3">
                    <label class="form-label-custom">Nama Lengkap Mahasiswa</label>
                    <div class="auth-input-group">
                        <input type="text" name="nama_lengkap" id="inputNama" placeholder="cth: Firdaus Dhuha" value="<?= htmlspecialchars($val_nama) ?>" autocomplete="name" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label-custom">Username</label>
                    <div class="auth-input-group">
                        <input type="text" name="username" id="inputUsername" placeholder="cth: firdaus (huruf kecil & angka)" value="<?= htmlspecialchars($val_user) ?>" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label-custom">Kata Sandi (Minimal 6 Karakter)</label>
                    <div class="auth-input-group">
                        <input type="password" name="password" id="inputPassword" placeholder="Buat kata sandi baru" autocomplete="new-password" required>
                        <button type="button" class="auth-eye-btn" onclick="togglePass('inputPassword', 'toggleEyeIcon1')" tabindex="-1">
                            <i class="bi bi-eye" id="toggleEyeIcon1"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label-custom">Ulangi Kata Sandi</label>
                    <div class="auth-input-group">
                        <input type="password" name="confirm_password" id="inputConfirmPassword" placeholder="Ketik ulang kata sandi" autocomplete="new-password" required>
                        <button type="button" class="auth-eye-btn" onclick="togglePass('inputConfirmPassword', 'toggleEyeIcon2')" tabindex="-1">
                            <i class="bi bi-eye" id="toggleEyeIcon2"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth-primary">
                    <span>Daftar & Masuk Otomatis</span>
                    <i class="bi bi-arrow-right"></i>
                </button>

                <div class="text-center mt-3 pt-1">
                    <span style="font-size: 13px; color: var(--auth-text-muted);">Sudah punya akun?</span>
                    <a href="<?= base_url('login.php') ?>" class="fw-semibold text-decoration-none ms-1" style="color: var(--auth-text-title); text-decoration: underline !important; font-size: 13px;">Masuk di sini</a>
                </div>
            </form>

            <div class="auth-footer">
                Universitas Gunadarma • Sistem Informasi PBW
            </div>

        </div>
    </div>

    <div id="registerTransitionOverlay" class="auth-overlay">
        <div class="text-center px-4">
            <div class="spinner-border text-light mb-3" style="width: 2.2rem; height: 2.2rem; border-width: 2.5px;" role="status">
                <span class="visually-hidden">Memuat...</span>
            </div>
            <div class="fw-semibold text-white small">Membuat Akun & Mengamankan Sesi...</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function togglePass(inputId, iconId) {
            const passInput = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                passInput.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        function triggerRegisterTransition() {
            const overlay = document.getElementById('registerTransitionOverlay');
            if (overlay) {
                overlay.classList.add('show');
            }
        }

        document.getElementById('registerForm').addEventListener('submit', function() {
            if (document.getElementById('clientIsMobile')) {
                document.getElementById('clientIsMobile').value = (window.innerWidth < 992) ? '1' : '0';
            }
            triggerRegisterTransition();
        });
    </script>
</body>
</html>
