<?php
require_once 'config.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $stmt = $conn->prepare("SELECT id, username, password_hash, is_verified, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_verified'] = $user['is_verified'];
                $_SESSION['role'] = $user['role'];
                log_action($conn, $user['id'], "Successfully logged in || Berhasil login ke dalam sistem");
                header("Location: index");
                exit();
                $error = __('Incorrect password. Please check again.', 'Kata sandi salah. Silakan periksa kembali.');
            }
            $error = __('Email is not registered.', 'Email tidak terdaftar.');
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
            $error = __('Email address is already registered. Please use another email or sign in.', 'Alamat email sudah terdaftar. Silakan gunakan email lain atau masuk.');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
            if ($insert_stmt->execute()) {
                $new_user_id = $conn->insert_id;
                log_action($conn, $new_user_id, "Registered new account with email $email || Mendaftar akun baru dengan email $email");
                $conn->query("INSERT INTO notifications (user_id, message, link, icon, color, bg) VALUES ($new_user_id, 'Welcome to MotoInfy! Find your dream motorcycle. || Selamat datang di MotoInfy! Temukan motor impian Anda.', 'discover', 'campaign', 'text-secondary', 'bg-red-100')");
                $u_name = $conn->real_escape_string($username);
                $conn->query("INSERT INTO notifications (target_role, message, link, icon, color, bg) VALUES ('admin', 'Unverified user: $u_name || Pengguna belum diverifikasi: $u_name', 'admin?page=users', 'person_add', 'text-blue-500', 'bg-blue-100')");
                $success = __('Registration successful! Please sign in.', 'Pendaftaran berhasil! Silakan masuk.');
            } else {
                $error = __('An error occurred while registering. Please try again.', 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.');
            }
        }
    }
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoInfy | Authentication</title>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <?php include 'theme_config.php'; ?>
    <style>
        body {
            font-family: 'Hanken Grotesk', sans-serif;
        }
        .auth-card {
            background-color: var(--color-surface);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--color-border);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .tab-active {
            border-bottom: 2px solid var(--color-secondary);
            color: var(--color-secondary);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-background text-on-surface">
    <?php include 'header.php'; ?>
    <main class="flex-grow flex items-center justify-center py-stack-lg px-margin-mobile">
        <div class="w-full max-w-[480px]">
            <div class="mb-stack-md space-y-stack-sm" id="alert-container">
                <?php if ($error): ?>
                <div class="bg-error-container text-on-error-container p-stack-md rounded-lg flex items-center gap-stack-sm border border-error/20">
                    <span class="material-symbols-outlined">error</span>
                    <p class="font-body-sm text-body-sm"><?= htmlspecialchars($error) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($success): ?>
                <div class="bg-emerald-50 text-emerald-900 p-stack-md rounded-lg flex items-center gap-stack-sm border border-emerald-200">
                    <span class="material-symbols-outlined">check_circle</span>
                    <p class="font-body-sm text-body-sm"><?= htmlspecialchars($success) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <div class="bg-surface-container-lowest rounded-xl auth-card overflow-hidden">
                <div class="flex border-b border-outline-variant">
                    <button class="flex-1 py-stack-md font-label-md text-label-md transition-all duration-200 tab-active" id="login-tab" onclick="switchAuth('login')">
                        <?= __('LOGIN', 'MASUK') ?>
                    </button>
                    <button class="flex-1 py-stack-md font-label-md text-label-md text-on-surface-variant transition-all duration-200" id="register-tab" onclick="switchAuth('register')">
                        <?= __('REGISTER', 'DAFTAR') ?>
                    </button>
                </div>
                <div class="p-stack-lg">
                    <form method="POST" action="auth" class="space-y-stack-md" id="login-form">
                        <input type="hidden" name="action" value="login">
                        <div class="space-y-base">
                            <label class="font-label-sm text-label-sm text-on-surface-variant block" for="login-email"><?= __('EMAIL ADDRESS', 'ALAMAT EMAIL') ?></label>
                            <input class="w-full px-stack-md py-stack-sm border border-outline-variant rounded-lg focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all font-body-md text-body-md" id="login-email" name="email" placeholder="dealer@motoinfy.com" type="email" required />
                        </div>
                        <div class="space-y-base">
                            <div class="flex justify-between items-center">
                                <label class="font-label-sm text-label-sm text-on-surface-variant block" for="login-password"><?= __('PASSWORD', 'KATA SANDI') ?></label>
                                <a class="text-[12px] text-secondary hover:underline font-semibold cursor-pointer" onclick="showPopup('<?= __('Sorry, accounts now are managed by its admin. If you believe this was a mistake, please contact admin for next steps.', 'Maaf, akun saat ini dikelola oleh admin. Jika Anda merasa ini adalah kesalahan, silakan hubungi admin untuk langkah berikutnya.') ?>')"><?= __('FORGOT?', 'LUPA?') ?></a>
                            </div>
                            <input class="w-full px-stack-md py-stack-sm border border-outline-variant rounded-lg focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all font-body-md text-body-md" id="login-password" name="password" placeholder="••••••••" type="password" required />
                        </div>
                        <div class="pt-stack-sm">
                            <button class="w-full bg-secondary text-white font-label-md text-label-md py-stack-md rounded-lg hover:bg-opacity-90 active:scale-[0.98] transition-all shadow-md" type="submit">
                                <?= __('SIGN IN TO DEALER PORTAL', 'MASUK PORTAL DEALER') ?>
                            </button>
                        </div>
                    </form>
                    <form method="POST" action="auth" class="hidden space-y-stack-md" id="register-form">
                        <input type="hidden" name="action" value="register">
                        <div class="space-y-base">
                            <label class="font-label-sm text-label-sm text-on-surface-variant block" for="reg-name"><?= __('FULL NAME', 'NAMA LENGKAP') ?></label>
                            <input class="w-full px-stack-md py-stack-sm border border-outline-variant rounded-lg focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all font-body-md text-body-md" id="reg-name" name="username" placeholder="John Doe" type="text" required />
                        </div>
                        <div class="space-y-base">
                            <label class="font-label-sm text-label-sm text-on-surface-variant block" for="reg-email"><?= __('EMAIL ADDRESS', 'ALAMAT EMAIL') ?></label>
                            <input class="w-full px-stack-md py-stack-sm border border-outline-variant rounded-lg focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all font-body-md text-body-md" id="reg-email" name="email" placeholder="john@dealership.com" type="email" required />
                        </div>
                        <div class="space-y-base">
                            <label class="font-label-sm text-label-sm text-on-surface-variant block" for="reg-password"><?= __('PASSWORD', 'KATA SANDI') ?></label>
                            <input class="w-full px-stack-md py-stack-sm border border-outline-variant rounded-lg focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all font-body-md text-body-md" id="reg-password" name="password" placeholder="Create a secure password" type="password" required />
                        </div>
                        <div class="flex items-start gap-stack-sm pt-base">
                            <input class="mt-1 rounded border-outline-variant text-secondary focus:ring-secondary" id="terms" type="checkbox" required />
                            <label class="font-body-sm text-body-sm text-on-surface-variant" for="terms">
                                <?= __('I agree to the', 'Saya setuju dengan') ?> <a class="text-secondary underline" href="terms" target="_blank"><?= __('Terms of Service', 'Syarat Layanan') ?></a> <?= __('and', 'dan') ?> <a class="text-secondary underline" href="privacy" target="_blank"><?= __('Privacy Policy', 'Kebijakan Privasi') ?></a>.
                            </label>
                        </div>
                        <div class="pt-stack-sm">
                            <button class="w-full bg-secondary text-white font-label-md text-label-md py-stack-md rounded-lg hover:bg-opacity-90 active:scale-[0.98] transition-all shadow-md" type="submit">
                                <?= __('CREATE DEALER ACCOUNT', 'BUAT AKUN DEALER') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <p class="mt-stack-lg text-center font-body-sm text-body-sm text-on-surface-variant">
                <span class="opacity-60"></span>
            </p>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-surface-container-lowest border-t border-outline-variant z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex justify-around items-center h-16">
            <a href="/" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">home</span>
                <span class="text-[10px] font-bold mt-1">Home</span>
            </a>
            <a href="discover" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">travel_explore</span>
                <span class="text-[10px] font-bold mt-1">Discover</span>
            </a>
            <a href="history" class="flex flex-col items-center justify-center w-full h-full text-slate-500 hover:text-secondary transition-colors">
                <span class="material-symbols-outlined text-[24px]">receipt_long</span>
                <span class="text-[10px] font-bold mt-1">History</span>
            </a>
        </div>
    </nav>
    <script>
        function switchAuth(mode) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const loginTab = document.getElementById('login-tab');
            const registerTab = document.getElementById('register-tab');
            if (mode === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                loginTab.classList.add('tab-active');
                loginTab.classList.remove('text-on-surface-variant');
                registerTab.classList.remove('tab-active');
                registerTab.classList.add('text-on-surface-variant');
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                registerTab.classList.add('tab-active');
                registerTab.classList.remove('text-on-surface-variant');
                loginTab.classList.remove('tab-active');
                loginTab.classList.add('text-on-surface-variant');
            }
        }
    </script>
    <?php if (isset($_SESSION['flash_message'])): ?>
    <div id="flash-popup" class="fixed top-24 right-4 md:right-8 bg-slate-900 text-white px-6 py-4 rounded-xl shadow-2xl z-[100] flex items-center gap-4 transition-all duration-300 transform translate-y-0 opacity-100">
        <span class="material-symbols-outlined text-secondary">info</span>
        <span class="font-medium text-sm"><?= htmlspecialchars($_SESSION['flash_message']) ?></span>
        <button onclick="document.getElementById('flash-popup').style.opacity='0'; setTimeout(()=>document.getElementById('flash-popup').remove(), 300)" class="text-slate-400 hover:text-white ml-2">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>
    <script>
        setTimeout(() => {
            const popup = document.getElementById('flash-popup');
            if(popup) {
                popup.style.opacity = '0';
                setTimeout(() => popup.remove(), 300);
            }
        }, 3000);
    </script>
    <?php unset($_SESSION['flash_message']); endif; ?>
    <?php include_once 'skeleton.php'; ?>
</body>
</html>
