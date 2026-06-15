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

$total_price = $trx['price'] * $trx['quantity'];

if (isset($_GET['action']) && $_GET['action'] == 'cancel') {
    if ($trx['status'] !== 'cancelled' && $trx['payment_status'] !== 'paid') {
        $u_stmt = $conn->prepare("UPDATE transactions SET status = 'cancelled', payment_status = 'unpaid' WHERE id = ?");
        $u_stmt->bind_param("i", $trx_id);
        $u_stmt->execute();

        $m_stmt = $conn->prepare("UPDATE motorcycles SET stock = stock + ? WHERE id = ?");
        $m_stmt->bind_param("ii", $trx['quantity'], $trx['motorcycle_id']);
        $m_stmt->execute();
        
        $_SESSION['flash_message'] = "Transaksi telah dibatalkan karena waktu pembayaran habis.";
    }
    header("Location: history");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    if ($trx['status'] !== 'cancelled' && $trx['payment_status'] == 'unpaid') {
        $u_stmt = $conn->prepare("UPDATE transactions SET payment_status = 'pending_verification' WHERE id = ?");
        $u_stmt->bind_param("i", $trx_id);
        $u_stmt->execute();

        $conn->query("INSERT INTO notifications (target_role, message, link, icon, color, bg) VALUES ('admin', 'Konfirmasi pembayaran: #TX-$trx_id', 'admin.php?page=transactions', 'payments', 'text-amber-500', 'bg-amber-100')");
        
        $_SESSION['flash_message'] = "Konfirmasi pembayaran berhasil dikirim. Menunggu verifikasi admin.";
    }
    header("Location: history");
    exit();
}

$expires_in = 600 - intval($trx['seconds_passed']);

if ($expires_in <= 0 && $trx['status'] !== 'cancelled' && $trx['payment_status'] == 'unpaid') {
    
    header("Location: payment.php?id=" . $trx_id . "&action=cancel");
    exit();
}

$is_expired = ($trx['status'] === 'cancelled');
$is_pending = ($trx['payment_status'] === 'pending_verification');
$is_paid = ($trx['payment_status'] === 'paid');

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
    <title>MotoTrack Pro | Payment</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Hanken Grotesk', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary": "#bb0112",
                        "secondary-container": "#e02928",
                        "background": "#f7f9fb",
                        "surface-container-lowest": "#ffffff",
                        "outline-variant": "#c6c6cd"
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-background text-slate-800 flex flex-col min-h-screen pb-16 md:pb-0">
    <?php include 'header.php'; ?>

    <main class="w-full flex-grow max-w-[800px] mx-auto px-4 py-12 flex flex-col items-center">
        
        <div class="w-full bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-8 text-center border-b border-slate-100">
                <h1 class="text-2xl font-extrabold text-slate-900 mb-2">Complete Your Payment</h1>
                <p class="text-slate-500">Order ID: <span class="font-bold text-slate-700">#TX-<?= $trx_id ?></span></p>
            </div>
            
            <div class="p-8 bg-slate-50 border-b border-slate-100 text-center">
                <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-2">Total Amount</p>
                <div class="text-4xl font-black text-emerald-600 mb-4">Rp <?= number_format($total_price, 0, ',', '.') ?></div>
                
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
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">Payment Expired</h2>
                        <p class="text-slate-500 mb-6">Waktu pembayaran telah habis dan pesanan dibatalkan.</p>
                        <a href="discover" class="inline-block bg-slate-900 text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800">Browse Again</a>
                    </div>
                <?php elseif ($is_paid): ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-emerald-500 mb-4 block">check_circle</span>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">Payment Successful</h2>
                        <p class="text-slate-500 mb-6">Pembayaran Anda telah diverifikasi.</p>
                        <a href="history" class="inline-block bg-slate-900 text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800">View History</a>
                    </div>
                <?php elseif ($is_pending): ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-amber-500 mb-4 block">hourglass_empty</span>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">Verification in Progress</h2>
                        <p class="text-slate-500 mb-6">Kami sedang memverifikasi pembayaran Anda. Mohon tunggu sebentar.</p>
                        <a href="history" class="inline-block bg-slate-900 text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800">View History</a>
                    </div>
                <?php else: ?>
                    <div class="mb-8">
                        <p class="font-bold text-slate-900 mb-4">Pay to one of the accounts below:</p>
                        <div class="grid gap-4">
                            <div class="border border-slate-200 rounded-lg p-4 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-slate-900">BCA - PT MotoTrack Pro</p>
                                    <p class="font-mono text-slate-500 text-lg">123-456-7890</p>
                                </div>
                                <span class="material-symbols-outlined text-slate-300 text-3xl">account_balance</span>
                            </div>
                            <div class="border border-slate-200 rounded-lg p-4 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-slate-900">Mandiri - PT MotoTrack Pro</p>
                                    <p class="font-mono text-slate-500 text-lg">098-765-4321</p>
                                </div>
                                <span class="material-symbols-outlined text-slate-300 text-3xl">account_balance</span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="payment?id=<?= $trx_id ?>" class="mt-8 pt-6 border-t border-slate-100 text-center">
                        <p class="text-sm text-slate-500 mb-4">Pastikan nominal transfer sesuai hingga digit terakhir.</p>
                        <button type="submit" name="confirm_payment" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 bg-secondary text-white font-bold px-8 py-4 rounded-xl hover:bg-secondary-container hover:shadow-lg transition-all text-lg">
                            Saya Sudah Transfer
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
    </main>

    <nav class="md:hidden fixed bottom-0 w-full bg-white border-t border-outline-variant flex justify-around items-center py-3 pb-safe z-50 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
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
        // Setup timer
        let expiresIn = <?= $expires_in ?>;
        const timerEl = document.getElementById('timer');
        
        const updateTimer = () => {
            if (expiresIn <= 0) {
                window.location.href = "payment.php?id=<?= $trx_id ?>&action=cancel";
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
