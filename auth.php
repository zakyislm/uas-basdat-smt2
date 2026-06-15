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
                
                log_action($conn, $user['id'], "Berhasil login ke dalam sistem");
                
                header("Location: index");
                exit();
            } else {
                $error = 'Kata sandi salah. Silakan periksa kembali.';
            }
        } else {
            $error = 'Email tidak terdaftar.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Alamat email sudah terdaftar. Silakan gunakan email lain atau masuk.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $new_user_id = $conn->insert_id;
                log_action($conn, $new_user_id, "Mendaftar akun baru dengan email $email");

                $conn->query("INSERT INTO notifications (user_id, message, link, icon, color, bg) VALUES ($new_user_id, 'Selamat datang di MotoTrack Pro! Temukan motor impian Anda.', 'discover.php', 'campaign', 'text-secondary', 'bg-red-100')");

                $u_name = $conn->real_escape_string($username);
                $conn->query("INSERT INTO notifications (target_role, message, link, icon, color, bg) VALUES ('admin', 'Unverified user: $u_name', 'admin.php?page=users', 'person_add', 'text-blue-500', 'bg-blue-100')");
                
                $success = 'Pendaftaran berhasil! Silakan masuk.';
            } else {
                $error = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoTrack Pro | Authentication</title>
    
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet" />
    
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary": "#bb0112",
                        "on-surface-variant": "#45464d",
                        "surface-container-lowest": "#ffffff",
                        "outline-variant": "#c6c6cd",
                        "error-container": "#ffdad6",
                        "on-error-container": "#93000a",
                        "error": "#ba1a1a",
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                    },
                    "spacing": {
                        "base": "4px",
                        "stack-sm": "8px",
                        "stack-md": "16px",
                        "margin-mobile": "16px",
                        "stack-lg": "32px",
                        "margin-desktop": "32px",
                        "container-max": "1280px"
                    },
                    "fontFamily": {
                        "label-md": ["Hanken Grotesk"],
                        "label-sm": ["Hanken Grotesk"],
                        "body-sm": ["Hanken Grotesk"],
                        "headline-sm": ["Hanken Grotesk"],
                        "body-md": ["Hanken Grotesk"],
                    },
                    "fontSize": {
                        "label-md": ["14px", { "lineHeight": "1", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "label-sm": ["12px", { "lineHeight": "1", "fontWeight": "500" }],
                        "body-sm": ["14px", { "lineHeight": "1.5", "fontWeight": "400" }],
                        "body-md": ["16px", { "lineHeight": "1.6", "fontWeight": "400" }],
                        "headline-sm": ["20px", { "lineHeight": "1.4", "fontWeight": "600" }],
                    }
                },
            },
        }
    </script>
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Hanken Grotesk', sans-serif;
        }

        .auth-card {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        .tab-active {
            border-bottom: 2px solid #bb0112;
            color: #bb0112;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">
    
    <header class="w-full top-0 sticky z-50 bg-surface-container-lowest border-b border-outline-variant shadow-sm">
        <div class="flex justify-between items-center px-4 md:px-8 py-2 w-full max-w-[1280px] mx-auto h-16">
            <div class="flex items-center gap-4">
                <a href="/" class="text-xl font-bold text-secondary">MotoTrack Pro</a>
            </div>
            
            <div class="flex items-center gap-2">
                <nav class="hidden md:flex items-center gap-2">
                    <a href="/" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Home">
                        <span class="material-symbols-outlined text-[24px]">home</span>
                    </a>
                </nav>
            </div>
        </div>
    </header>

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

            <div class="bg-white rounded-xl auth-card overflow-hidden">
                
                <div class="flex border-b border-outline-variant">
                    <button class="flex-1 py-stack-md font-label-md text-label-md transition-all duration-200 tab-active" id="login-tab" onclick="switchAuth('login')">
                        LOGIN
                    </button>
                    <button class="flex-1 py-stack-md font-label-md text-label-md text-on-surface-variant transition-all duration-200" id="register-tab" onclick="switchAuth('register')">
                        REGISTER
                    </button>
                </div>
                <div class="p-stack-lg">
                    
                    <form method="POST" action="auth" class="space-y-stack-md" id="login-form">
                        <input type="hidden" name="action" value="login">
                        <div class="space-y-base">
                            <label class="font-label-sm text-label-sm text-on-surface-variant block" for="login-email">EMAIL ADDRESS</label>
                            <input class="w-full px-stack-md py-stack-sm border border-outline-variant rounded-lg focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all font-body-md text-body-md" id="login-email" name="email" placeholder="dealer@mototrack.pro" type="email" required />
                        </div>
                        <div class="space-y-base">
                            <div class="flex justify-between items-center">
                                <label class="font-label-sm text-label-sm text-on-surface-variant block" for="login-password">PASSWORD</label>
                                <a class="text-[12px] text-secondary hover:underline font-semibold cursor-pointer" onclick="alert('Sorry, accounts now are managed by its admin. If you believe this was a mistake, please contact admin for next steps.')">FORGOT?</a>
                            </div>
                            <input class="w-full px-stack-md py-stack-sm border border-outline-variant rounded-lg focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all font-body-md text-body-md" id="login-password" name="password" placeholder="••••••••" type="password" required />
                        </div>
                        <div class="pt-stack-sm">
                            <button class="w-full bg-secondary text-white font-label-md text-label-md py-stack-md rounded-lg hover:bg-opacity-90 active:scale-[0.98] transition-all shadow-md" type="submit">
                                SIGN IN TO DEALER PORTAL
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="auth" class="hidden space-y-stack-md" id="register-form">
                        <input type="hidden" name="action" value="register">
                        <div class="space-y-base">
                            <label class="font-label-sm text-label-sm text-on-surface-variant block" for="reg-name">FULL NAME</label>
                            <input class="w-full px-stack-md py-stack-sm border border-outline-variant rounded-lg focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all font-body-md text-body-md" id="reg-name" name="username" placeholder="John Doe" type="text" required />
                        </div>
                        <div class="space-y-base">
                            <label class="font-label-sm text-label-sm text-on-surface-variant block" for="reg-email">EMAIL ADDRESS</label>
                            <input class="w-full px-stack-md py-stack-sm border border-outline-variant rounded-lg focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all font-body-md text-body-md" id="reg-email" name="email" placeholder="john@dealership.com" type="email" required />
                        </div>
                        <div class="space-y-base">
                            <label class="font-label-sm text-label-sm text-on-surface-variant block" for="reg-password">PASSWORD</label>
                            <input class="w-full px-stack-md py-stack-sm border border-outline-variant rounded-lg focus:outline-none focus:border-secondary focus:ring-1 focus:ring-secondary transition-all font-body-md text-body-md" id="reg-password" name="password" placeholder="Create a secure password" type="password" required />
                        </div>
                        <div class="flex items-start gap-stack-sm pt-base">
                            <input class="mt-1 rounded border-outline-variant text-secondary focus:ring-secondary" id="terms" type="checkbox" required />
                            <label class="font-body-sm text-body-sm text-on-surface-variant" for="terms">
                                I agree to the <a class="text-secondary underline" href="terms.html" target="_blank">Terms of Service</a> and <a class="text-secondary underline" href="privacy.html" target="_blank">Privacy Policy</a>.
                            </label>
                        </div>
                        <div class="pt-stack-sm">
                            <button class="w-full bg-secondary text-white font-label-md text-label-md py-stack-md rounded-lg hover:bg-opacity-90 active:scale-[0.98] transition-all shadow-md" type="submit">
                                CREATE DEALER ACCOUNT
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

    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-white border-t border-slate-200 z-[999]" style="padding-bottom: env(safe-area-inset-bottom);">
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
