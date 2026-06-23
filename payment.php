<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: auth");
    exit();
}
$trx_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$trx_id) {
    header("Location: history");
    exit();
}
$stmt = $conn->prepare("
    SELECT t.*, m.make, m.model, m.price, 
           TIMESTAMPDIFF(SECOND, t.transaction_date, NOW()) as seconds_passed
    FROM transactions t 
    JOIN motorcycles m ON t.motorcycle_id = m.id 
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->bind_param("ii", $trx_id, $_SESSION['user_id']);
$stmt->execute();
$trx = $stmt->get_result()->fetch_assoc();
if (!$trx) {
    header("Location: history");
    exit();
}
$full_price = $trx['price'] * $trx['quantity'];
$dp_verified = is_booking_dp_verified($conn, $trx_id);
if ($trx['type'] === 'booking' && $dp_verified) {
    $total_price = $full_price * 0.80;
    $payment_title = __('Remaining Balance (80%)', 'Sisa Pelunasan (80%)');
} else {
    $total_price = ($trx['type'] === 'booking') ? ($full_price * 0.20) : $full_price;
    $payment_title = ($trx['type'] === 'booking') ? __('Down Payment (20%)', 'Uang Muka (20%)') : __('Total Amount', 'Total Harga');
}
if (isset($_GET['action']) && $_GET['action'] == 'cancel') {
    if ($trx['status'] !== 'cancelled' && $trx['payment_status'] !== 'paid') {
        $u_stmt = $conn->prepare("UPDATE transactions SET status = 'cancelled', payment_status = 'unpaid' WHERE id = ?");
        $u_stmt->bind_param("i", $trx_id);
        $u_stmt->execute();
        $m_stmt = $conn->prepare("UPDATE motorcycles SET stock = stock + ? WHERE id = ?");
        $m_stmt->bind_param("ii", $trx['quantity'], $trx['motorcycle_id']);
        $m_stmt->execute();
        $_SESSION['flash_message'] = __('Transaction cancelled due to payment timeout.', 'Transaksi telah dibatalkan karena waktu pembayaran habis.');
    }
    header("Location: history");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    if ($trx['status'] !== 'cancelled' && ($trx['payment_status'] == 'unpaid' || ($trx['type'] === 'booking' && $trx['payment_status'] == 'paid' && $dp_verified && $trx['status'] == 'pending'))) {
        $u_stmt = $conn->prepare("UPDATE transactions SET payment_status = 'pending_verification' WHERE id = ?");
        $u_stmt->bind_param("i", $trx_id);
        $u_stmt->execute();
        if ($trx['type'] === 'booking' && $dp_verified) {
            $notif_msg = "Settlement confirmation: #TX-$trx_id || Konfirmasi pelunasan: #TX-$trx_id";
            $_SESSION['flash_message'] = __('Settlement confirmation sent. Awaiting admin verification.', 'Konfirmasi pelunasan berhasil dikirim. Menunggu verifikasi admin.');
        } else {
            $notif_msg = "Payment confirmation: #TX-$trx_id || Konfirmasi pembayaran: #TX-$trx_id";
            $_SESSION['flash_message'] = __('Payment confirmation sent. Awaiting admin verification.', 'Konfirmasi pembayaran berhasil dikirim. Menunggu verifikasi admin.');
        }
        $conn->query("INSERT INTO notifications (target_role, message, link, icon, color, bg) VALUES ('admin', '$notif_msg', 'admin?page=transactions', 'payments', 'text-amber-500', 'bg-amber-100')");
    }
    header("Location: history");
    exit();
}
$expires_in = 600 - intval($trx['seconds_passed']);
if ($expires_in <= 0 && $trx['status'] !== 'cancelled' && $trx['payment_status'] == 'unpaid') {
    header("Location: payment?id=" . $trx_id . "&action=cancel");
    exit();
}
$is_expired = ($trx['status'] === 'cancelled');
if ($trx['type'] === 'booking') {
    if ($trx['status'] === 'confirmed') {
        $is_paid = true;
        $is_pending = false;
    } elseif ($trx['payment_status'] === 'pending_verification') {
        $is_paid = false;
        $is_pending = true;
    } elseif ($trx['payment_status'] === 'paid' && $trx['status'] === 'pending') {
        $is_paid = false;
        $is_pending = false;
    } else {
        $is_paid = false;
        $is_pending = false;
    }
} else {
    $is_pending = ($trx['payment_status'] === 'pending_verification');
    $is_paid = ($trx['payment_status'] === 'paid');
}
$c_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM carts WHERE user_id = ?");
$c_stmt->bind_param("i", $_SESSION['user_id']);
$c_stmt->execute();
$c_res = $c_stmt->get_result()->fetch_assoc();
$cart_count = $c_res['total'] ? $c_res['total'] : 0;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>MotoInfy | Payment</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <?php include 'theme_config.php'; ?>
</head>
<body class="bg-background text-on-surface flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>
    <main class="w-full flex-grow max-w-[800px] mx-auto px-4 py-12 flex flex-col items-center">
        <div class="w-full bg-surface-container-lowest rounded-2xl shadow-sm border border-outline-variant overflow-hidden">
            <div class="p-8 text-center border-b border-slate-100">
                <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Complete Your Payment</h1>
                <p class="text-slate-500">Order ID: <span class="font-bold text-on-surface">#TX-<?= $trx_id ?></span></p>
            </div>
            <div class="p-8 bg-surface-container-low border-b border-slate-100 text-center">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-2">
                    <?= $payment_title ?>
                </p>
                <div class="text-4xl font-black text-emerald-600 mb-4">Rp <?= number_format($total_price, 0, ',', '.') ?></div>
                <?php if ($trx['type'] === 'booking'): ?>
                    <div class="text-xs text-slate-500 mt-2 space-y-1 mb-4">
                        <p><?= __('Total Price (100%)', 'Total Harga Motor (100%)') ?>: <strong>Rp <?= number_format($full_price, 0, ',', '.') ?></strong></p>
                        <p><?= __('Remaining Balance (80%)', 'Sisa Pelunasan (80%)') ?>: <strong>Rp <?= number_format($full_price * 0.80, 0, ',', '.') ?></strong></p>
                        <p class="text-amber-600 font-bold mt-2"><?= __('The motorcycle will be delivered after 100% settlement at the dealership.', 'Unit motor baru akan dikirim setelah pelunasan 100% dilakukan di dealer.') ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!$is_expired && !$is_pending && !$is_paid): ?>
                    <div class="inline-flex items-center gap-2 bg-red-100 text-red-600 px-4 py-2 rounded-full text-sm font-bold animate-pulse">
                        <span class="material-symbols-outlined text-[18px]">timer</span>
                        Time remaining: <span id="timer" class="font-mono">--:--</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="p-8">
                <?php if ($is_expired): ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-red-500 mb-4 block">cancel</span>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2"><?= __('Payment Expired', 'Pembayaran Kedaluwarsa') ?></h2>
                        <p class="text-slate-500 mb-6"><?= __('Payment time has run out and order was cancelled.', 'Waktu pembayaran telah habis dan pesanan dibatalkan.') ?></p>
                        <a href="discover" class="inline-block bg-slate-900 text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800"><?= __('Browse Again', 'Cari Lagi') ?></a>
                    </div>
                <?php elseif ($is_paid): ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-emerald-500 mb-4 block">check_circle</span>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2"><?= __('Payment Successful', 'Pembayaran Sukses') ?></h2>
                        <p class="text-slate-500 mb-6"><?= __('Your payment has been verified.', 'Pembayaran Anda telah diverifikasi.') ?></p>
                        <a href="history" class="inline-block bg-slate-900 text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800"><?= __('View History', 'Lihat Riwayat') ?></a>
                    </div>
                <?php elseif ($is_pending): ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-amber-500 mb-4 block">hourglass_empty</span>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2"><?= __('Verification in Progress', 'Dalam Verifikasi') ?></h2>
                        <p class="text-slate-500 mb-6"><?= __('We are verifying your payment. Please wait a moment.', 'Kami sedang memverifikasi pembayaran Anda. Mohon tunggu sebentar.') ?></p>
                        <a href="history" class="inline-block bg-slate-900 text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800"><?= __('View History', 'Lihat Riwayat') ?></a>
                    </div>
                <?php else: ?>
                    <div class="mb-8">
                        <p class="font-bold text-slate-900 mb-4">Pay to one of the accounts below:</p>
                        <div class="grid gap-4">
                            <div class="border border-outline-variant rounded-lg p-4 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-slate-900">BCA - PT MotoInfy</p>
                                    <p class="font-mono text-slate-500 text-lg">123-456-7890</p>
                                </div>
                                <span class="material-symbols-outlined text-slate-300 text-3xl">account_balance</span>
                            </div>
                            <div class="border border-outline-variant rounded-lg p-4 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-slate-900">Mandiri - PT MotoInfy</p>
                                    <p class="font-mono text-slate-500 text-lg">098-765-4321</p>
                                </div>
                                <span class="material-symbols-outlined text-slate-300 text-3xl">account_balance</span>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="payment?id=<?= $trx_id ?>" class="mt-8 pt-6 border-t border-slate-100 text-center">
                        <p class="text-sm text-slate-500 mb-4"><?= __('Make sure the transfer amount matches up to the last digit.', 'Pastikan nominal transfer sesuai hingga digit terakhir.') ?></p>
                        <button type="submit" name="confirm_payment" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 bg-secondary text-white font-bold px-8 py-4 rounded-xl hover:bg-secondary-container hover:shadow-lg transition-all text-lg">
                            <?= __('I Have Transferred', 'Saya Sudah Transfer') ?>
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <nav class="md:hidden fixed bottom-0 w-full bg-surface-container-lowest border-t border-outline-variant flex justify-around items-center py-3 pb-safe z-50 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
        <a href="/" class="flex flex-col items-center p-2 text-slate-600 hover:text-secondary">
            <span class="material-symbols-outlined text-[24px]">home</span>
        </a>
        <a href="discover" class="flex flex-col items-center p-2 text-slate-600 hover:text-secondary">
            <span class="material-symbols-outlined text-[24px]">travel_explore</span>
        </a>
        <a href="history" class="flex flex-col items-center p-2 text-slate-600 hover:text-secondary">
            <span class="material-symbols-outlined text-[24px]">receipt_long</span>
        </a>
    </nav>
    <?php if (!$is_expired && !$is_pending && !$is_paid): ?>
    <script>
        let expiresIn = <?= $expires_in ?>;
        const timerEl = document.getElementById('timer');
        const updateTimer = () => {
            if (expiresIn <= 0) {
                window.location.href = "payment?id=<?= $trx_id ?>&action=cancel";
                return;
            }
            const minutes = Math.floor(expiresIn / 60);
            const seconds = expiresIn % 60;
            timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            expiresIn--;
        };
        updateTimer();
        setInterval(updateTimer, 1000);
    </script>
    <?php endif; ?>
</body>
</html>
