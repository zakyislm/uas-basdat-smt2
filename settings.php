<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: auth");
    exit();
}
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['change_email'])) {
        $new_email = trim($_POST['new_email']);
        $current_password = $_POST['current_password'];
        if (password_verify($current_password, $user['password_hash'])) {
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_email->bind_param("si", $new_email, $user_id);
            $check_email->execute();
            if ($check_email->get_result()->num_rows > 0) {
                $error = __("Email is already used by another user.", "Email sudah digunakan oleh pengguna lain.");
            } else {
                $update_email = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $update_email->bind_param("si", $new_email, $user_id);
                $update_email->execute();
                log_action($conn, $user_id, "Mengubah email dari " . $user['email'] . " menjadi " . $new_email);
                $_SESSION['email'] = $new_email; 
                $success = __("Email successfully updated.", "Email berhasil diperbarui.");
                $user['email'] = $new_email;
            }
        } else {
            $error = __("Current password is incorrect. Email update denied.", "Password saat ini salah. Perubahan email ditolak.");
        }
    } elseif (isset($_POST['change_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $current_password = $_POST['current_password'];
        if ($new_password !== $confirm_password) {
            $error = __("New password confirmation does not match.", "Konfirmasi password baru tidak cocok.");
        } else {
            if (password_verify($current_password, $user['password_hash'])) {
                $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                $update_pw = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $update_pw->bind_param("si", $new_hash, $user_id);
                $update_pw->execute();
                log_action($conn, $user_id, "Mengubah password.");
                $success = __("Password successfully updated.", "Password berhasil diperbarui.");
                $user['password_hash'] = $new_hash;
            } else {
                $error = __("Current password is incorrect. Password update denied.", "Password saat ini salah. Perubahan password ditolak.");
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
    <title>MotoInfy | Account Settings</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <?php include 'theme_config.php'; ?>
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>
    <main class="w-full flex-grow max-w-3xl mx-auto px-8 py-12">
        <div class="mb-10">
            <h1 class="text-4xl font-extrabold text-slate-900">
                Account Settings
            </h1>
            <p class="text-slate-500 mt-2 text-lg">Manage your profile, security, and preferences.</p>
        </div>
        <?php if (!empty($error)): ?>
            <div class="mb-6 bg-red-50 text-red-800 border border-red-200 px-6 py-4 rounded-xl font-bold text-sm flex items-center gap-3">
                <span class="material-symbols-outlined text-red-600">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="mb-6 bg-emerald-50 text-emerald-800 border border-emerald-200 px-6 py-4 rounded-xl font-bold text-sm flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 mb-8 shadow-sm">
            <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400">person</span>
                Profile Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Username</p>
                    <p class="font-bold text-slate-900 text-lg"><?= htmlspecialchars($user['username']) ?></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Role</p>
                    <span class="px-3 py-1 bg-surface-container text-on-surface rounded-full text-xs font-bold uppercase inline-block">
                        <?= htmlspecialchars($user['role']) ?>
                    </span>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Current Email</p>
                    <p class="font-bold text-slate-900 text-lg"><?= htmlspecialchars($user['email']) ?></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Status</p>
                    <?php if ($user['is_verified']): ?>
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold uppercase inline-block flex items-center gap-1 w-max">
                            <span class="material-symbols-outlined text-[14px]">verified</span> <?= __('Verified', 'Terverifikasi') ?>
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-bold uppercase inline-block w-max">
                            <?= __('Unverified', 'Belum Terverifikasi') ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 shadow-sm">
                <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-400">mail</span>
                    Change Email
                </h3>
                <form method="POST" action="settings" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-1">New Email Address</label>
                        <input type="email" name="new_email" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-secondary focus:ring-1 focus:ring-secondary outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-1">Current Password <span class="text-xs font-normal text-slate-400">(for security)</span></label>
                        <input type="password" name="current_password" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-secondary focus:ring-1 focus:ring-secondary outline-none transition-all">
                    </div>
                    <button type="submit" name="change_email" class="w-full bg-slate-900 text-white font-bold py-2.5 rounded-lg hover:bg-slate-800 transition-colors mt-2">
                        Update Email
                    </button>
                </form>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 shadow-sm">
                <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-400">lock</span>
                    Change Password
                </h3>
                <form method="POST" action="settings" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-1">New Password</label>
                        <input type="password" name="new_password" required minlength="6" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-secondary focus:ring-1 focus:ring-secondary outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-on-surface mb-1">Confirm New Password</label>
                        <input type="password" name="confirm_password" required minlength="6" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-secondary focus:ring-1 focus:ring-secondary outline-none transition-all">
                    </div>
                    <div class="pt-2 border-t border-slate-100">
                        <label class="block text-sm font-bold text-on-surface mb-1">Current Password <span class="text-xs font-normal text-slate-400">(for security)</span></label>
                        <input type="password" name="current_password" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-secondary focus:ring-1 focus:ring-secondary outline-none transition-all">
                    </div>
                    <button type="submit" name="change_password" class="w-full bg-slate-900 text-white font-bold py-2.5 rounded-lg hover:bg-slate-800 transition-colors mt-2">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
        <div class="border-t border-outline-variant pt-8 mt-8 pb-12">
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold text-red-900">Logout</h3>
                    <p class="text-sm text-red-700 mt-1"><?= __('Your session will end and you will need to log in again.', 'Sesi Anda akan berakhir dan Anda harus masuk kembali.') ?></p>
                </div>
                <a href="logout" class="w-full md:w-auto justify-center bg-red-600 text-white font-bold px-6 py-3 rounded-lg hover:bg-red-700 transition-colors shadow-lg shadow-red-200 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                    Logout Now
                </a>
            </div>
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
</body>
</html>
