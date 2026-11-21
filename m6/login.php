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
                    dompetku_sync_trx_cookie($pdo, $u_auto['id_user']);
                    
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
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'registered') {
    $success_msg = 'Akun berhasil dibuat! Silakan masuk dengan akun baru Anda.';
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$fail_key = 'login_fails_' . md5($ip);
$fails = $_SESSION[$fail_key] ?? ['count' => 0, 'last_time' => 0];

if ($fails['count'] >= 5 && (time() - $fails['last_time']) < 60) {
    $remaining = 60 - (time() - $fails['last_time']);
    $error_msg = 'Terlalu banyak percobaan login gagal. Mohon tunggu ' . $remaining . ' detik demi keamanan.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim(sanitize($_POST['username'] ?? '')));
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?)");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user && isset($_COOKIE['dompetku_vault'])) {
                $cached_vault = dompetku_verify_payload($_COOKIE['dompetku_vault']);
                if ($cached_vault && strtolower($cached_vault['username'] ?? '') === $username) {
                    if (password_verify($password, $cached_vault['password_hash'])) {
                        $u_restore = $pdo->prepare("INSERT OR REPLACE INTO users (id_user, username, password, nama_lengkap) VALUES (?, ?, ?, ?)");
                        $u_restore->execute([$cached_vault['id_user'], $cached_vault['username'], $cached_vault['password_hash'], $cached_vault['nama_lengkap']]);
                        $user = [
                            'id_user'      => $cached_vault['id_user'],
                            'username'     => $cached_vault['username'],
                            'nama_lengkap' => $cached_vault['nama_lengkap'],
                            'password'     => $cached_vault['password_hash']
                        ];
                        if (!empty($cached_vault['items'])) {
                            $ins_v = $pdo->prepare("INSERT OR IGNORE INTO transaksi (id_transaksi, id_user, id_kategori, tanggal, judul, jenis, jumlah, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            foreach ($cached_vault['items'] as $item) {
                                $ins_v->execute([
                                    $item['id_transaksi'],
                                    (int)$cached_vault['id_user'],
                                    $item['id_kategori'],
                                    $item['tanggal'],
                                    $item['judul'],
                                    $item['jenis'],
                                    $item['jumlah'],
                                    $item['catatan'] ?? ''
                                ]);
                            }
                        }
                    }
                }
            }

            if (!$user && isset($_COOKIE['dompetku_session'])) {
                $cached_sess = dompetku_verify_payload($_COOKIE['dompetku_session']);
                if ($cached_sess && strtolower($cached_sess['username'] ?? '') === $username) {
                    $u_restore = $pdo->prepare("INSERT OR REPLACE INTO users (id_user, username, password, nama_lengkap) VALUES (?, ?, ?, ?)");
                    $u_restore->execute([$cached_sess['id_user'], $cached_sess['username'], $cached_sess['password_hash'], $cached_sess['nama_lengkap']]);
                    $user = [
                        'id_user'      => $cached_sess['id_user'],
                        'username'     => $cached_sess['username'],
                        'nama_lengkap' => $cached_sess['nama_lengkap'],
                        'password'     => $cached_sess['password_hash']
                    ];
                }
            }

            if ($user && password_verify($password, $user['password'])) {
                unset($_SESSION[$fail_key]);
                session_regenerate_id(true);

                $_SESSION['user_id']      = (int)$user['id_user'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

                $sess_payload = [
                    'id_user'       => (int)$user['id_user'],
                    'username'      => $user['username'],
                    'nama_lengkap'  => $user['nama_lengkap'],
                    'password_hash' => $user['password'],
                    'exp'           => time() + (86400 * 30)
                ];
                dompetku_set_secure_cookie('dompetku_session', dompetku_sign_payload($sess_payload));

                $remember_token = hash('sha256', $user['id_user'] . $user['password'] . 'dompetku_pbw_secret');
                dompetku_set_secure_cookie('dompetku_remember', $user['id_user'] . ':' . $remember_token, time() + (86400 * 30));
                dompetku_sync_trx_cookie($pdo, $user['id_user']);
                dompetku_sync_vault($pdo, $user['id_user']);
                
                $client_is_mobile = (isset($_POST['client_is_mobile']) && $_POST['client_is_mobile'] === '1') || $is_mobile_device;

                if ($client_is_mobile) {
                    header("Location: " . base_url("index.php"));
                } else {
                    header("Location: " . base_url("dashboard.php"));
                }
                exit;
            } else {
                $fails['count'] = (time() - $fails['last_time'] > 60) ? 1 : ($fails['count'] + 1);
                $fails['last_time'] = time();
                $_SESSION[$fail_key] = $fails;
                $error_msg = 'Username atau kata sandi tidak sesuai. Silakan periksa kembali.';
            }
        } catch (PDOException $e) {
            $error_msg = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    } else {
        $error_msg = 'Silakan isi username dan kata sandi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0C0D12" id="metaThemeColor">
    <title>Masuk - DompetKu</title>
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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        .auth-container {
            width: 100%;
            max-width: 390px;
        }

        .auth-card {
            background-color: var(--auth-card-bg);
            border: 1px solid var(--auth-border);
            border-radius: 18px;
            padding: 34px 28px;
            box-shadow: var(--auth-shadow);
            transition: background-color 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .auth-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .auth-brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
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
            margin-bottom: 7px;
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
            margin-top: 6px;
        }

        .btn-auth-primary:hover {
            opacity: 0.92;
        }

        .btn-auth-primary:active {
            transform: scale(0.99);
        }

        .btn-auth-secondary {
            background-color: var(--auth-btn-sec-bg);
            color: var(--auth-btn-sec-text) !important;
            border: 1px solid var(--auth-btn-sec-border);
            border-radius: 10px;
            height: 40px;
            width: 100%;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: background-color 0.15s ease, transform 0.15s ease;
            margin-top: 10px;
        }

        .btn-auth-secondary:hover {
            opacity: 0.9;
        }

        .btn-auth-secondary:active {
            transform: scale(0.99);
        }

        .auth-footer {
            margin-top: 24px;
            padding-top: 18px;
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
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <h1 class="auth-brand-title">DompetKu</h1>
                        <p class="auth-brand-subtitle">Sistem Kas Mahasiswa</p>
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

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success rounded-3 py-2 px-3 small mb-3 border-0 d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= $success_msg ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" id="loginForm">
                <input type="hidden" name="client_is_mobile" id="clientIsMobile" value="0">

                <div class="mb-3">
                    <label class="form-label-custom">Username</label>
                    <div class="auth-input-group">
                        <input type="text" name="username" id="inputUsername" placeholder="Masukkan username" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label-custom">Kata Sandi</label>
                    <div class="auth-input-group">
                        <input type="password" name="password" id="inputPassword" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                        <button type="button" class="auth-eye-btn" onclick="togglePassVisibility()" tabindex="-1">
                            <i class="bi bi-eye" id="toggleEyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth-primary">
                    <span>Masuk ke Sistem</span>
                    <i class="bi bi-arrow-right"></i>
                </button>

                <div class="text-center mt-3 pt-1">
                    <span style="font-size: 13px; color: var(--auth-text-muted);">Belum punya akun?</span>
                    <a href="<?= base_url('register.php') ?>" class="fw-semibold text-decoration-none ms-1" style="color: var(--auth-text-title); text-decoration: underline !important; font-size: 13px;">Daftar Akun Baru</a>
                </div>
            </form>

            <div class="auth-footer">
                Universitas Gunadarma • Sistem Informasi PBW
            </div>

        </div>
    </div>

    <div id="loginTransitionOverlay" class="auth-overlay">
        <div class="text-center px-4">
            <div class="spinner-border text-light mb-3" style="width: 2.2rem; height: 2.2rem; border-width: 2.5px;" role="status">
                <span class="visually-hidden">Memuat...</span>
            </div>
            <div class="fw-semibold text-white small">Memverifikasi Sesi...</div>
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
            if (overlay) {
                overlay.classList.add('show');
            }
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