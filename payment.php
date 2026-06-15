<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$trx_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$trx_id) {
    header("Location: history.php");
    exit();
}

// Fetch transaction details
$stmt = $conn->prepare("
    SELECT t.*, m.make, m.model, m.price 
    FROM transactions t 
    JOIN motorcycles m ON t.motorcycle_id = m.id 
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->bind_param("ii", $trx_id, $_SESSION['user_id']);
$stmt->execute();
$trx = $stmt->get_result()->fetch_assoc();

if (!$trx) {
    header("Location: history.php");
    exit();
}

$total_price = $trx['price'] * $trx['quantity'];

// Handle cancel/expire
if (isset($_GET['action']) && $_GET['action'] == 'cancel') {
    if ($trx['status'] !== 'cancelled' && $trx['payment_status'] !== 'paid') {
        $u_stmt = $conn->prepare("UPDATE transactions SET status = 'cancelled', payment_status = 'unpaid' WHERE id = ?");
        $u_stmt->bind_param("i", $trx_id);
        $u_stmt->execute();
        
        // Return stock
        $m_stmt = $conn->prepare("UPDATE motorcycles SET stock = stock + ? WHERE id = ?");
        $m_stmt->bind_param("ii", $trx['quantity'], $trx['motorcycle_id']);
        $m_stmt->execute();
        
        $_SESSION['flash_message'] = "Transaksi telah dibatalkan karena waktu pembayaran habis.";
    }
    header("Location: history.php");
    exit();
}

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    if ($trx['status'] !== 'cancelled' && $trx['payment_status'] == 'unpaid') {
        $u_stmt = $conn->prepare("UPDATE transactions SET payment_status = 'pending_verification' WHERE id = ?");
        $u_stmt->bind_param("i", $trx_id);
        $u_stmt->execute();
        $_SESSION['flash_message'] = "Konfirmasi pembayaran berhasil dikirim. Menunggu verifikasi admin.";
    }
    header("Location: history.php");
    exit();
}

// Check expiration via PHP (10 minutes = 600 seconds)
$created_at = strtotime($trx['transaction_date']);
$now = time();
$expires_in = ($created_at + 600) - $now;

if ($expires_in <= 0 && $trx['status'] !== 'cancelled' && $trx['payment_status'] == 'unpaid') {
    // Auto cancel
    header("Location: payment.php?id=" . $trx_id . "&action=cancel");
    exit();
}

$is_expired = ($trx['status'] === 'cancelled');
$is_pending = ($trx['payment_status'] === 'pending_verification');
$is_paid = ($trx['payment_status'] === 'paid');

// Calculate cart count for navbar
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
    <!-- TopNavBar -->
    <header class="w-full top-0 sticky z-50 bg-surface-container-lowest border-b border-outline-variant shadow-sm">
        <div class="flex justify-between items-center px-4 md:px-8 py-2 w-full max-w-[1280px] mx-auto h-16">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-xl font-bold text-secondary">MotoTrack Pro</a>
            </div>
            
            <div class="flex items-center gap-2">
                <nav class="hidden md:flex items-center gap-2 mr-2 border-r border-outline-variant pr-4">
                    <a href="index.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Home">
                        <span class="material-symbols-outlined text-[24px]">home</span>
                    </a>
                    <a href="discover.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Discover">
                        <span class="material-symbols-outlined text-[24px]">travel_explore</span>
                    </a>
                    <a href="history.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="History">
                        <span class="material-symbols-outlined text-[24px]">receipt_long</span>
                    </a>
                </nav>

                <?php include 'notifications_ui.php'; ?>
                <a href="cart.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50 relative" title="Cart">
                    <span class="material-symbols-outlined text-[24px]">shopping_cart</span>
                    <?php if ($cart_count > 0): ?>
                        <span class="absolute top-0 right-0 bg-secondary text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center border-2 border-white"><?= $cart_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="settings.php" class="text-slate-600 hover:text-secondary p-2 transition-colors flex items-center justify-center rounded-full hover:bg-slate-50" title="Settings">
                    <span class="material-symbols-outlined text-[24px]">settings</span>
                </a>
            </div>
        </div>
    </header>

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
                        <a href="discover.php" class="inline-block bg-slate-900 text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800">Browse Again</a>
                    </div>
                <?php elseif ($is_paid): ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-emerald-500 mb-4 block">check_circle</span>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">Payment Successful</h2>
                        <p class="text-slate-500 mb-6">Pembayaran Anda telah diverifikasi.</p>
                        <a href="history.php" class="inline-block bg-slate-900 text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800">View History</a>
                    </div>
                <?php elseif ($is_pending): ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-6xl text-amber-500 mb-4 block">hourglass_empty</span>
                        <h2 class="text-2xl font-bold text-slate-900 mb-2">Verification in Progress</h2>
                        <p class="text-slate-500 mb-6">Kami sedang memverifikasi pembayaran Anda. Mohon tunggu sebentar.</p>
                        <a href="history.php" class="inline-block bg-slate-900 text-white font-bold px-6 py-3 rounded-lg hover:bg-slate-800">View History</a>
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
                    
                    <form method="POST" action="payment.php?id=<?= $trx_id ?>" class="mt-8 pt-6 border-t border-slate-100 text-center">
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

    <!-- Mobile Bottom Nav -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-white border-t border-outline-variant flex justify-around items-center py-3 z-[999] shadow-[0_-4px_20px_rgba(0,0,0,0.05)]" style="padding-bottom: env(safe-area-inset-bottom);">
        <a href="index.php" class="flex flex-col items-center p-2 text-slate-600 hover:text-secondary">
            <span class="material-symbols-outlined text-[24px]">home</span>
        </a>
        <a href="discover.php" class="flex flex-col items-center p-2 text-slate-600 hover:text-secondary">
            <span class="material-symbols-outlined text-[24px]">travel_explore</span>
        </a>
        <a href="history.php" class="flex flex-col items-center p-2 text-slate-600 hover:text-secondary">
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
